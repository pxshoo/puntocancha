<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private array $gatewayResult = [];

    private function register(): User
    {
        $this->get('/contratar?plan=pro&billing=annual')->assertOk()->assertSee('Crear cuenta')->assertSee('Iniciar sesión');
        $this->post('/register', ['name' => 'Owner', 'club_name' => 'Club', 'phone' => '+56912345678',
            'email' => 'owner@example.com', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertRedirect(route('subscription.checkout'));
        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => Http::response($request->method() === 'POST'
            ? ['token' => Str::random(64), 'url' => 'https://webpay3gint.transbank.cl/webpayserver/initTransaction']
            : $this->gatewayResult));

        return auth()->user();
    }

    private function data(array $overrides = []): array
    {
        return array_replace(['request_key' => (string) Str::uuid(), 'quoted_amount' => 624000,
            'policy_version' => config('billing.version'), 'accept_terms' => 1, 'accept_privacy' => 1], $overrides);
    }

    private function gatewayResponse(SubscriptionPayment $payment, array $overrides = []): void
    {
        $this->gatewayResult = array_replace(['buy_order' => $payment->buy_order, 'session_id' => $payment->session_id,
            'amount' => $payment->amount, 'status' => 'AUTHORIZED', 'response_code' => 0, 'authorization_code' => '123456'], $overrides);
    }

    public function test_registration_requires_payment_before_onboarding_and_keeps_selected_plan(): void
    {
        $user = $this->register();
        $this->assertTrue($user->subscription_checkout_required);
        $this->assertSame('pending_payment', Subscription::first()->status);
        $this->get('/onboarding/paso-1')->assertRedirect(route('subscription.checkout'));
        $this->get('/dashboard')->assertRedirect(route('subscription.checkout'));
        $this->get(route('subscription.checkout'))->assertOk()->assertSee('624.000')->assertSee('condiciones de uso')->assertSee('política de privacidad');
        $this->get(route('legal.show', 'terms'))->assertOk()->assertSee('pxshoo')->assertSee('21.431.110-0');
        $this->get(route('legal.show', 'privacy'))->assertOk()->assertSee('franciscogonzalezandrades@gmail.com');
        $this->post('/logout');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('subscription.checkout'));
        $this->get(route('subscription.checkout'))->assertOk()->assertSee('624.000');
    }

    public function test_consent_version_and_server_price_are_required_before_gateway_call(): void
    {
        $this->register();
        $this->post(route('subscription.store'), $this->data(['accept_terms' => 0, 'accept_privacy' => 0]))->assertSessionHasErrors(['accept_terms', 'accept_privacy']);
        $this->post(route('subscription.store'), $this->data(['policy_version' => 'old']))->assertSessionHasErrors('policy_version');
        $this->post(route('subscription.store'), $this->data(['quoted_amount' => 1]))->assertSessionHasErrors('quoted_amount');
        $this->assertDatabaseCount('subscription_payments', 0);
        Http::assertNothingSent();
    }

    public function test_approved_payment_records_consent_activates_annual_plan_and_is_idempotent(): void
    {
        $user = $this->register();
        $data = $this->data();
        $this->post(route('subscription.store'), $data)->assertOk()->assertSee('Continuar a Webpay');
        $this->post(route('subscription.store'), $data)->assertOk();
        $this->assertDatabaseCount('subscription_payments', 1);
        $payment = SubscriptionPayment::firstOrFail();
        $this->assertSame(624000, $payment->amount);
        $this->assertSame(config('billing.terms'), $payment->terms_snapshot);
        $this->assertSame(config('billing.version'), $payment->privacy_version);
        $this->assertNotNull($payment->accepted_at);
        Http::assertSent(fn (Request $request) => $request->method() === 'POST' && $request['amount'] === 624000
            && str_contains($request['return_url'], '/suscripcion/webpay/retorno')
            && $request->hasHeader('Tbk-Api-Key-Id', config('webpay.integration_code')));
        $this->gatewayResponse($payment);
        $this->get(route('subscription.return', ['token_ws' => $payment->token]))->assertRedirect(route('subscription.result', $payment));
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertFalse($user->fresh()->subscription_checkout_required);
        $subscription = Subscription::firstOrFail();
        $end = $subscription->ends_at->copy();
        $this->assertSame('active', $subscription->status);
        $this->assertSame('annual', $subscription->billing_cycle);
        $this->assertTrue($end->equalTo($subscription->starts_at->copy()->addYearNoOverflow()));
        $this->get(route('subscription.return', ['token_ws' => $payment->token]))->assertRedirect();
        $this->assertTrue($subscription->fresh()->ends_at->equalTo($end));
        $this->get(route('subscription.result', $payment))->assertOk()->assertSee('Configurar mi club');
        $this->actingAs($user->fresh())->get('/onboarding/paso-1')->assertOk();
    }

    public function test_declined_or_mismatched_payment_never_unlocks_club_and_allows_retry_after_rejection(): void
    {
        $user = $this->register();
        $this->post(route('subscription.store'), $this->data())->assertOk();
        $payment = SubscriptionPayment::firstOrFail();
        $this->post(route('subscription.store'), $this->data())->assertSessionHasErrors('payment');
        $this->gatewayResponse($payment, ['amount' => 1]);
        $this->get(route('subscription.return', ['token_ws' => $payment->token]))->assertRedirect();
        $this->assertSame('initialized', $payment->fresh()->status);
        $this->assertTrue($user->fresh()->subscription_checkout_required);
        $this->gatewayResponse($payment, ['status' => 'FAILED', 'response_code' => -1]);
        $this->post(route('subscription.return'), ['TBK_TOKEN' => $payment->token])->assertRedirect();
        $this->assertSame('failed', $payment->fresh()->status);
        $this->get('/onboarding/paso-1')->assertRedirect(route('subscription.checkout'));
    }

    public function test_existing_admin_login_continues_purchase_and_other_users_cannot_read_payment(): void
    {
        $this->register();
        $this->post(route('subscription.store'), $this->data())->assertOk();
        $payment = SubscriptionPayment::firstOrFail();
        $this->post('/logout');
        $other = User::factory()->create();
        $this->get('/contratar?plan=basic&billing=monthly')->assertOk();
        $this->post('/login', ['email' => $other->email, 'password' => 'password'])->assertRedirect(route('subscription.checkout'));
        $this->get(route('subscription.result', $payment))->assertNotFound();
        $this->post(route('subscription.refresh', $payment))->assertNotFound();
        $player = User::factory()->create(['role' => 'player', 'club_id' => null]);
        $this->actingAs($player)->get(route('subscription.checkout'))->assertForbidden();
    }

    public function test_renewal_adds_one_period_and_expired_paid_access_returns_to_checkout(): void
    {
        $user = $this->register();
        $this->post(route('subscription.store'), $this->data())->assertOk();
        $payment = SubscriptionPayment::firstOrFail();
        $this->gatewayResponse($payment);
        $this->get(route('subscription.return', ['token_ws' => $payment->token]))->assertRedirect();
        $this->actingAs($user->fresh());
        $firstEnd = Subscription::firstOrFail()->ends_at->copy();
        $this->post(route('subscription.store'), $this->data())->assertOk();
        $renewal = SubscriptionPayment::where('id', '!=', $payment->id)->firstOrFail();
        $this->gatewayResponse($renewal);
        $this->get(route('subscription.return', ['token_ws' => $renewal->token]))->assertRedirect();
        $this->assertTrue(Subscription::firstOrFail()->ends_at->equalTo($firstEnd->addYearNoOverflow()));
        Subscription::query()->update(['ends_at' => now()->subMinute()]);
        $this->get('/onboarding/paso-1')->assertRedirect(route('subscription.checkout'));
    }

    public function test_production_payment_is_blocked_without_platform_credentials_and_legal_review(): void
    {
        $this->register();
        config(['billing.mode' => 'production', 'billing.commerce_code' => null, 'billing.api_key' => null, 'billing.legal_approved' => false]);
        $this->post(route('subscription.store'), $this->data())->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('subscription_payments', 0);
        Http::assertNothingSent();
    }

    public function test_monthly_quote_and_reconciliation_do_not_affect_court_booking_payments(): void
    {
        $user = $this->register();
        $this->get('/contratar?plan=basic&billing=monthly')->assertRedirect(route('subscription.checkout'));
        $this->post(route('subscription.store'), $this->data(['quoted_amount' => 35000]))->assertOk();
        $payment = SubscriptionPayment::firstOrFail();
        $this->assertSame(35000, $payment->amount);
        $this->travel(21)->minutes();
        $this->gatewayResponse($payment);
        $this->artisan('webpay:reconcile')->assertSuccessful();
        $this->assertFalse($user->fresh()->subscription_checkout_required);
        $this->assertDatabaseCount('booking_payments', 0);
        $this->assertDatabaseCount('bookings', 0);
        $this->assertSame('monthly', Subscription::first()->billing_cycle);
    }
}
