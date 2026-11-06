<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Court;
use App\Models\EmailConfirmation;
use App\Models\User;
use App\Services\BookingCheckoutService;
use App\Services\BookingPricingService;
use App\Services\MarketplaceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClubBookingPricingTest extends TestCase
{
    use RefreshDatabase;

    private function setupClub(): array
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $club = $owner->club;
        $club->forceFill([
            'city' => 'Puerto Montt', 'address' => 'Calle 123', 'phone' => '+56912345678',
            'marketplace_enabled' => true, 'onboarding_completed_at' => now(),
            'opens_at' => '08:00', 'closes_at' => '23:00', 'slot_minutes' => 60,
            'webpay_mode' => 'payment_link', 'booking_payment_link' => 'https://www.webpay.cl/form-pay/123456',
            'hourly_rates' => [
                ['from' => '12:00', 'until' => '18:00', 'rate' => 25000],
                ['from' => '18:00', 'until' => '20:00', 'rate' => 30000],
                ['from' => '20:00', 'until' => '24:00', 'rate' => 40000],
            ],
        ])->save();
        $court = Court::factory()->create(['default_hourly_rate' => 20000, 'minimum_deposit' => 10000]);

        return [$owner, $club, $court];
    }

    private function settings(): array
    {
        return ['name' => 'Club Sur', 'city' => 'Puerto Montt', 'address' => 'Calle 123', 'phone' => '+56912345678',
            'opens_at' => '08:00', 'closes_at' => '23:00', 'slot_minutes' => 60, 'marketplace_enabled' => 1,
            'booking_terms' => 'Pago al club.', 'cancellation_policy' => 'Contactar al club.',
            'webpay_mode' => 'payment_link', 'booking_payment_link' => 'https://www.webpay.cl/form-pay/123456'];
    }

    public function test_prices_use_boundaries_fallback_crossing_bands_and_midnight(): void
    {
        [, $club, $court] = $this->setupClub();
        $pricing = app(BookingPricingService::class);
        foreach ([['10:00', 60, '20000.00'], ['12:00', 60, '25000.00'], ['18:00', 60, '30000.00'],
            ['20:00', 60, '40000.00'], ['17:30', 90, '42500.00'], ['23:30', 60, '30000.00']] as [$time, $minutes, $expected]) {
            $start = CarbonImmutable::today()->addDay()->setTimeFromTimeString($time);
            $this->assertSame($expected, $pricing->quote($court, $club, $start, $start->addMinutes($minutes)));
        }
        $matrix = app(MarketplaceService::class)->availability($club, collect([$court]), collect(), today()->addDay()->toDateString());
        $this->assertSame('30000.00', collect($matrix->first()['slots'])->firstWhere('start', '18:00')['price']);
    }

    public function test_settings_validate_overlaps_links_and_allow_clearing_rates(): void
    {
        [$owner, $club] = $this->setupClub();
        $this->put(route('club.settings.update'), array_merge($this->settings(), ['hourly_rates' => $club->hourly_rates]))
            ->assertSessionHasNoErrors();
        $this->put(route('club.settings.update'), array_merge($this->settings(), ['hourly_rates' => [
            ['from' => '12:00', 'until' => '19:00', 'rate' => 25000], ['from' => '18:00', 'until' => '20:00', 'rate' => 30000],
        ]]))->assertSessionHasErrors('hourly_rates.1.from');
        $this->put(route('club.settings.update'), array_merge($this->settings(), ['booking_payment_link' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('booking_payment_link');
        $this->put(route('club.settings.update'), $this->settings())->assertSessionHasNoErrors();
        $this->assertSame([], $club->fresh()->hourly_rates);
        $this->assertSame($owner->club_id, $club->id);
        $this->put(route('club.settings.update'), array_merge($this->settings(), ['closes_at' => '24:00']))->assertSessionHasNoErrors();
        $this->assertSame('24:00', substr($club->fresh()->closes_at, 0, 5));
    }

    public function test_link_checkout_uses_real_quote_and_only_owner_can_confirm_verified_amount(): void
    {
        [$owner, $club, $court] = $this->setupClub();
        $this->post('/logout');
        Http::preventStrayRequests();
        $data = ['club_slug' => $club->slug, 'court_id' => $court->id, 'date' => today()->addDay()->toDateString(),
            'start_time' => '20:00', 'quoted_price' => 40000, 'payment_choice' => 'deposit',
            'customer_email' => 'player@example.com', 'request_key' => (string) Str::uuid(), 'accept_terms' => 1];
        $this->post(route('webpay.store'), array_replace($data, ['quoted_price' => 25000]))->assertSessionHasErrors('quoted_price');
        $this->assertDatabaseCount('bookings', 0);
        $this->post(route('webpay.store'), $data)->assertRedirect();
        $payment = BookingPayment::firstOrFail();
        $this->assertSame('manual_review', $payment->status);
        $this->assertSame(10000, $payment->amount);
        $this->assertEquals(40000, $payment->booking->total_price);
        $this->post(route('webpay.store'), $data)->assertRedirect();
        $this->assertDatabaseCount('bookings', 1);
        app(BookingCheckoutService::class)->settle($payment);
        Http::assertNothingSent();
        $confirmation = ['action' => 'approve', 'amount' => 10000, 'authorization' => 'AUTH123', 'confirmed_bank' => 1];
        $this->actingAs(User::factory()->create())->post(route('bookings.payment.review', $payment), $confirmation)->assertForbidden();
        $this->actingAs($owner)->post(route('bookings.payment.review', $payment), array_replace($confirmation, ['amount' => 1]))->assertSessionHasErrors('amount');
        $this->post(route('bookings.payment.review', $payment), $confirmation)->assertSessionHasNoErrors();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('confirmed', $payment->booking->fresh()->status);
        $email = EmailConfirmation::where('event_key', 'booking:'.$payment->booking_id)->firstOrFail();
        $this->assertFalse($email->payload['demo']);
        $this->assertSame('player@example.com', $email->recipient);
        $this->assertEquals(10000, $payment->booking->fresh()->deposit_paid);
        $this->post(route('bookings.payment.review', $payment), $confirmation)->assertSessionHasErrors('payment');
    }

    public function test_backoffice_uses_the_same_price_and_keeps_existing_booking_snapshot(): void
    {
        [, $club, $court] = $this->setupClub();
        $this->post(route('bookings.store'), ['court_id' => $court->id, 'customer_name' => 'Jugador', 'customer_phone' => '912345678',
            'booking_date' => today()->addDay()->toDateString(), 'start_time' => '19:00', 'duration_hours' => 2, 'payment_method' => 'cash'])
            ->assertSessionHasNoErrors();
        $booking = Booking::firstOrFail();
        $this->assertEquals(70000, $booking->total_price);
        $club->forceFill(['hourly_rates' => []])->save();
        $this->assertEquals(70000, $booking->fresh()->total_price);
    }
}
