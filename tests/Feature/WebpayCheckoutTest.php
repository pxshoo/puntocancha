<?php

namespace Tests\Feature;

use App\Models\BookingPayment;
use App\Models\Club;
use App\Models\Court;
use App\Models\EmailConfirmation;
use App\Models\User;
use App\Services\BookingCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class WebpayCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private array $gatewayResult = [];

    private function court(): Court
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $owner->club->forceFill([
            'webpay_mode' => 'integration', 'marketplace_enabled' => true, 'onboarding_completed_at' => now(),
            'city' => 'Puerto Montt', 'address' => 'Calle 123', 'phone' => '+56912345678',
            'opens_at' => '08:00', 'closes_at' => '23:00', 'slot_minutes' => 60,
            'booking_terms' => 'Abono o pago total.', 'cancellation_policy' => 'Contactar al club.',
        ])->save();
        $court = Court::factory()->create(['default_hourly_rate' => 30000, 'minimum_deposit' => 10000]);
        $this->post('/logout');
        Http::preventStrayRequests();

        return $court;
    }

    private function data(Court $court, array $overrides = []): array
    {
        return array_replace([
            'club_slug' => $court->club_id ? Club::findOrFail($court->club_id)->slug : '',
            'court_id' => $court->id, 'date' => today()->addDay()->toDateString(), 'start_time' => '18:00',
            'customer_email' => 'jugador@example.com', 'payment_choice' => 'deposit', 'quoted_price' => 30000,
            'request_key' => (string) Str::uuid(), 'accept_terms' => 1,
        ], $overrides);
    }

    private function fakeCreate(): void
    {
        Http::fake(fn (Request $request) => Http::response($request->method() === 'POST' ? ['token' => str_repeat('a', 64), 'url' => 'https://webpay3gint.transbank.cl/webpayserver/initTransaction'] : $this->gatewayResult));
    }

    private function fakeResult(BookingPayment $payment, array $overrides = []): void
    {
        $this->gatewayResult = array_replace([
            'buy_order' => $payment->buy_order, 'session_id' => $payment->session_id,
            'amount' => $payment->amount, 'status' => 'AUTHORIZED', 'response_code' => 0, 'authorization_code' => '123456',
        ], $overrides);
    }

    public function test_logged_in_player_checkout_automatically_uses_account_email(): void
    {
        $court = $this->court();
        $player = User::factory()->create(['role' => 'player', 'club_id' => null, 'email' => 'cuenta@example.com', 'phone' => '+56912345678']);
        $this->actingAs($player);
        $this->fakeCreate();
        $this->post(route('webpay.store'), $this->data($court, ['customer_email' => 'otro@example.com']))->assertOk();
        $payment = BookingPayment::firstOrFail();
        $this->assertSame('cuenta@example.com', $payment->booking->customer_email);
        $this->fakeResult($payment);
        app(BookingCheckoutService::class)->settle($payment);
        $confirmation = EmailConfirmation::where('event_key', 'booking:'.$payment->booking_id)->firstOrFail();
        $this->assertSame('cuenta@example.com', $confirmation->recipient);
    }

    public function test_club_settings_deposit_is_displayed_and_used_for_payment(): void
    {
        $court = $this->court();
        $club = Club::findOrFail($court->club_id);
        $owner = User::where('club_id', $club->id)->where('role', 'club_admin')->firstOrFail();
        $this->actingAs($owner);
        $data = $club->only(['name', 'city', 'address', 'phone', 'slot_minutes', 'marketplace_enabled', 'booking_terms', 'cancellation_policy']);
        $data = array_merge($data, ['opens_at' => '08:00', 'closes_at' => '23:00', 'minimum_deposit' => 12000]);
        $this->get(route('club.settings.edit'))->assertOk()->assertSee('name="minimum_deposit"', false);
        $this->put(route('club.settings.update'), $data)->assertSessionHasNoErrors();
        $this->assertSame(12000, $club->fresh()->minimum_deposit);
        $this->put(route('club.settings.update'), array_replace($data, ['minimum_deposit' => -1]))->assertSessionHasErrors('minimum_deposit');
        $this->post('/logout');
        $this->get(route('player.club', $club->slug))->assertOk()->assertSee('12.000');
        $this->fakeCreate();
        $this->post(route('webpay.store'), $this->data($court))->assertOk();
        $this->assertSame(12000, BookingPayment::firstOrFail()->amount);
    }

    public function test_guest_can_choose_deposit_and_only_verified_payment_confirms_booking(): void
    {
        $court = $this->court();
        $this->fakeCreate();
        $this->post(route('webpay.store'), $this->data($court))->assertOk()->assertSee('Continuar a Webpay')->assertSee('no se realizará un cobro real');
        $payment = BookingPayment::firstOrFail();
        $this->assertSame(10000, $payment->amount);
        $this->assertSame('pending_payment', $payment->booking->status);
        $this->assertSame('0.00', $payment->booking->deposit_paid);
        $this->assertNull($payment->booking->player_id);
        $this->assertSame('jugador@example.com', $payment->booking->customer_email);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request['amount'] === 10000 && str_contains($request->url(), '/v1.2/transactions'));
        $this->fakeResult($payment);
        $response = $this->get(route('webpay.return', ['token_ws' => $payment->token]));
        $response->assertRedirect();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertDatabaseCount('email_confirmations', 1);
        $confirmation = EmailConfirmation::first();
        $this->assertSame('123456', $confirmation->payload['details']['Código de autorización']);
        $this->assertTrue($confirmation->payload['demo']);
        $this->assertNotEmpty($confirmation->payload['actions']);
        $this->assertSame('confirmed', $payment->booking->fresh()->status);
        $this->assertSame('10000.00', $payment->booking->fresh()->deposit_paid);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Reserva confirmada')->assertSee('20.000');
        $this->get(route('webpay.return', ['token_ws' => $payment->token]))->assertRedirect();
        $this->assertSame('10000.00', $payment->booking->fresh()->deposit_paid);
        $this->get(route('webpay.receipt', $payment))->assertForbidden();
    }

    public function test_full_payment_is_server_calculated_and_duplicate_request_does_not_create_two_payments(): void
    {
        $court = $this->court();
        $this->fakeCreate();
        $data = $this->data($court, ['payment_choice' => 'full', 'amount' => 1]);
        $this->post(route('webpay.store'), $data)->assertOk();
        $this->post(route('webpay.store'), $data)->assertOk();
        $this->assertDatabaseCount('booking_payments', 1);
        $this->assertSame(30000, BookingPayment::first()->amount);
        $this->post(route('webpay.store'), $this->data($court))->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('booking_payments', 1);
    }

    public function test_rejection_releases_slot_and_pending_unknown_result_does_not_record_payment(): void
    {
        $court = $this->court();
        $this->fakeCreate();
        $this->post(route('webpay.store'), $this->data($court))->assertOk();
        $payment = BookingPayment::firstOrFail();
        $this->fakeResult($payment, ['status' => 'FAILED', 'response_code' => -1]);
        $this->post(route('webpay.return'), ['TBK_TOKEN' => $payment->token])->assertRedirect();
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('canceled', $payment->booking->fresh()->status);
        $this->assertSame('0.00', $payment->booking->fresh()->deposit_paid);
    }

    public function test_amount_mismatch_never_approves_and_scheduled_reconciliation_recovers_payment(): void
    {
        $court = $this->court();
        $this->fakeCreate();
        $this->post(route('webpay.store'), $this->data($court))->assertOk();
        $payment = BookingPayment::firstOrFail();
        $this->fakeResult($payment, ['amount' => 1]);
        $this->get(route('webpay.return', ['token_ws' => $payment->token]))->assertRedirect();
        $this->assertSame('initialized', $payment->fresh()->status);
        $this->assertSame('0.00', $payment->booking->fresh()->deposit_paid);
        $this->travel(21)->minutes();
        $this->fakeResult($payment);
        $this->artisan('webpay:reconcile')->assertSuccessful();
        $this->assertSame('paid', $payment->fresh()->status);
        Http::assertSent(fn (Request $request) => $request->method() === 'GET');
    }

    public function test_abandoned_unpaid_checkout_is_released_after_verified_expiry(): void
    {
        $court = $this->court();
        $this->fakeCreate();
        $this->post(route('webpay.store'), $this->data($court))->assertOk();
        $payment = BookingPayment::firstOrFail();
        $this->travel(21)->minutes();
        $this->fakeResult($payment, ['status' => 'INITIALIZED', 'response_code' => null]);
        $this->artisan('webpay:reconcile')->assertSuccessful();
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('canceled', $payment->booking->fresh()->status);
    }

    public function test_invalid_email_quote_or_foreign_court_does_not_create_a_payment(): void
    {
        $court = $this->court();
        $this->post(route('webpay.store'), $this->data($court, ['customer_email' => 'bad']))->assertSessionHasErrors('customer_email');
        $this->post(route('webpay.store'), $this->data($court, ['quoted_price' => 1]))->assertSessionHasErrors('quoted_price');
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $other = Court::factory()->create();
        $this->post('/logout');
        $this->post(route('webpay.store'), $this->data($court, ['court_id' => $other->id]))->assertNotFound();
        $this->assertDatabaseCount('booking_payments', 0);
        Http::assertNothingSent();
    }

    public function test_gateway_creation_failure_rolls_back_hold_and_zero_minimum_requires_full_payment(): void
    {
        $court = $this->court();
        $court->forceFill(['minimum_deposit' => 0])->saveQuietly();
        $this->post(route('webpay.store'), $this->data($court))->assertSessionHasErrors('payment_choice');
        Http::fake(['*' => Http::response([], 503)]);
        $this->post(route('webpay.store'), $this->data($court, ['payment_choice' => 'full']))->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('booking_payments', 0);
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_modal_accepts_email_for_guests_and_admins_and_bypassing_webpay_is_rejected(): void
    {
        $court = $this->court();
        $club = Club::find($court->club_id);
        $this->get(route('player.club', $club->slug))->assertOk()->assertSee('Ingresa tu correo')->assertSee('Pagar abono')->assertSee('Webpay de pruebas');
        $owner = User::where('club_id', $court->club_id)->where('role', 'club_admin')->firstOrFail();
        $this->actingAs($owner)->get(route('player.club', $club->slug))->assertOk()->assertSee('Ingresa tu correo')->assertDontSee('Usa una cuenta de jugador');
        $player = User::factory()->create(['role' => 'player', 'club_id' => null]);
        $this->actingAs($player);
        $this->post(route('player.book'), $this->data($court))->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('bookings', 0);
    }
}
