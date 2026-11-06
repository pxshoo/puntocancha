<?php

namespace Tests\Feature;

use App\Mail\TransactionConfirmation;
use App\Models\Booking;
use App\Models\Court;
use App\Models\EmailConfirmation;
use App\Models\Product;
use App\Models\User;
use App\Services\BookingService;
use App\Services\ConfirmationService;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class EmailConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_change_notifies_only_club_admins_and_unchanged_state_is_not_repeated(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $staff = User::factory()->create(['club_id' => $owner->club_id, 'role' => 'staff']);
        $admin = User::factory()->create(['is_superadmin' => true]);
        $this->actingAs($admin);
        $data = ['action' => 'suspend', 'reason' => 'Revisión interna de cuenta'];
        $this->post(route('superadmin.access', $owner->club), $data)->assertSessionHasNoErrors();
        $this->post(route('superadmin.access', $owner->club), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('email_confirmations', 1);
        $message = EmailConfirmation::firstOrFail();
        $this->assertSame($owner->email, $message->recipient);
        $this->assertSame('Acceso a tu club suspendido', $message->payload['title']);
        $this->assertStringNotContainsString($data['reason'], json_encode($message->payload));
        $this->assertNotSame($other->email, $message->recipient);
        $this->assertNotSame($staff->email, $message->recipient);
        $this->post(route('superadmin.access', $owner->club), ['action' => 'grant', 'reason' => 'Acceso restablecido'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('email_confirmations', 2);
        $this->assertSame('Acceso a tu club habilitado', EmailConfirmation::latest('id')->firstOrFail()->payload['title']);
    }

    public function test_each_successful_login_creates_notice_for_admin_and_player_but_failed_login_does_not(): void
    {
        $owner = User::factory()->create();
        $this->post('/login', ['email' => $owner->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertDatabaseCount('email_confirmations', 0);
        $this->post('/login', ['email' => $owner->email, 'password' => 'password'])->assertRedirect();
        $this->post('/logout');
        $this->post('/login', ['email' => $owner->email, 'password' => 'password'])->assertRedirect();
        $this->assertDatabaseCount('email_confirmations', 2);
        $this->post('/logout');
        $player = User::factory()->create(['role' => 'player', 'club_id' => null]);
        $this->post('/login', ['email' => $player->email, 'password' => 'password'])->assertRedirect();
        $message = EmailConfirmation::orderByDesc('id')->first();
        $this->assertSame($player->email, $message->recipient);
        $this->assertNull($message->club_id);
        $this->assertSame('Nuevo inicio de sesión', $message->payload['title']);
    }

    public function test_confirmed_reservation_records_one_private_email_and_sends_once(): void
    {
        Mail::fake();
        $this->actingAs(User::factory()->create());
        $booking = Booking::factory()->create(['status' => 'confirmed', 'customer_email' => 'jugador@example.com', 'total_price' => 30000, 'deposit_paid' => 10000]);
        $service = app(ConfirmationService::class);
        $service->booking($booking, true);
        $service->booking($booking, true);
        $this->assertDatabaseCount('email_confirmations', 1);
        $message = EmailConfirmation::first();
        $this->assertSame('jugador@example.com', $message->recipient);
        $this->assertStringNotContainsString('jugador@example.com', DB::table('email_confirmations')->value('recipient'));
        $this->assertSame('$20.000 CLP', $message->payload['details']['Saldo pendiente']);
        $html = (new TransactionConfirmation($message->payload))->render();
        $this->assertStringContainsString('MODO DE PRUEBA', $html);
        $this->assertStringContainsString('Indicaciones para tu partido', $html);
        $this->assertStringContainsString('maps/search', $html);
        $this->artisan('notifications:send')->assertSuccessful();
        $this->artisan('notifications:send')->assertSuccessful();
        Mail::assertSent(TransactionConfirmation::class, fn ($mail) => $mail->hasTo('jugador@example.com'));
        Mail::assertSentCount(1);
        $this->assertNotNull($message->fresh()->sent_at);
    }

    public function test_pending_reservations_do_not_send_and_rollback_discards_confirmation(): void
    {
        $this->actingAs(User::factory()->create());
        $booking = Booking::factory()->create(['status' => 'pending_payment', 'customer_email' => 'jugador@example.com']);
        app(ConfirmationService::class)->booking($booking);
        $this->assertDatabaseCount('email_confirmations', 0);
        $booking->forceFill(['status' => 'confirmed'])->saveQuietly();
        DB::beginTransaction();
        app(ConfirmationService::class)->booking($booking);
        DB::rollBack();
        $this->assertDatabaseCount('email_confirmations', 0);
    }

    public function test_paid_pos_sale_records_confirmation_and_repeated_request_does_not_duplicate_it(): void
    {
        $this->actingAs(User::factory()->create());
        $product = Product::factory()->create(['stock' => 10, 'sale_price' => 1500]);
        $data = ['request_key' => (string) Str::uuid(), 'customer_email' => 'comprador@example.com',
            'payment_method' => 'cash', 'items' => [['product_id' => $product->id, 'quantity' => 2]]];
        $service = app(SaleService::class);
        $sale = $service->create($data);
        $service->create($data);
        $service->settle($sale, 'cash');
        $this->assertDatabaseCount('email_confirmations', 1);
        $this->assertSame('$3.000 CLP', EmailConfirmation::first()->payload['details']['Total pagado']);
        $this->assertCount(1, EmailConfirmation::first()->payload['items']);
    }

    public function test_failed_delivery_keeps_confirmation_pending_and_retries_later(): void
    {
        $this->actingAs(User::factory()->create());
        $booking = Booking::factory()->create(['status' => 'confirmed', 'customer_email' => 'jugador@example.com']);
        app(ConfirmationService::class)->booking($booking);
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('Transport unavailable'));
        $this->artisan('notifications:send')->assertSuccessful();
        $message = EmailConfirmation::first();
        $this->assertNull($message->sent_at);
        $this->assertSame(1, $message->attempts);
        $this->assertTrue($message->next_attempt_at->isFuture());
        $this->assertSame('confirmed', $booking->fresh()->status);
    }

    public function test_missing_gmail_credentials_preserve_pending_emails_without_spending_retries(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => 'smtp.gmail.com',
            'mail.mailers.smtp.username' => null, 'mail.mailers.smtp.password' => null]);
        $this->actingAs(User::factory()->create());
        $booking = Booking::factory()->create(['status' => 'confirmed', 'customer_email' => 'jugador@example.com']);
        app(ConfirmationService::class)->booking($booking);
        $this->artisan('notifications:send')->assertSuccessful();
        Mail::assertNothingSent();
        $message = EmailConfirmation::first();
        $this->assertSame(0, $message->attempts);
        $this->assertNull($message->sent_at);
    }

    public function test_manual_booking_service_creates_email_with_confirmation(): void
    {
        $this->actingAs(User::factory()->create());
        $court = Court::factory()->create();
        app(BookingService::class)->createBooking(['court_id' => $court->id, 'customer_name' => 'Jugador',
            'customer_phone' => '912345678', 'customer_email' => 'manual@example.com',
            'start_time' => now()->addDay()->startOfDay()->addHours(10), 'end_time' => now()->addDay()->startOfDay()->addHours(11),
            'total_price' => 20000, 'status' => 'confirmed']);
        $this->assertSame('manual@example.com', EmailConfirmation::first()->recipient);
    }
}
