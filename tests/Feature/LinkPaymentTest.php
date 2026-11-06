<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Services\SubscriptionCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class LinkPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function prepareCheckout(): array
    {
        config(['billing.mode' => 'payment_link', 'billing.payment_link' => 'https://www.webpay.cl/form-pay/437548', 'billing.link_test_enabled' => true]);
        Http::preventStrayRequests();
        $user = User::factory()->create(['subscription_checkout_required' => true]);
        $this->actingAs($user);
        $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Club Pro', 'price' => 65000, 'interval' => 'monthly', 'court_limit' => 6]);

        return [$user, ['key' => $plan->slug, 'billing' => 'monthly']];
    }

    private function data(int $amount, bool $test = false): array
    {
        return ['quoted_amount' => $amount, 'request_key' => (string) Str::uuid(), 'link_test' => $test,
            'policy_version' => config('billing.version'), 'accept_terms' => 1, 'accept_privacy' => 1];
    }

    public function test_manual_access_grant_unlocks_setup_without_marking_payment_paid(): void
    {
        [$user, $selection] = $this->prepareCheckout();
        $user->forceFill(['onboarding_completed' => false])->save();
        $payment = app(SubscriptionCheckoutService::class)->start($user, $selection, $this->data(65000));
        $user->club->forceFill(['access_override' => 'allow'])->save();
        $this->getJson(route('subscription.result', $payment))->assertOk()
            ->assertJson(['enabled' => true, 'status' => 'manual_review', 'redirect' => route('onboarding.step1')]);
        $this->assertSame('manual_review', $payment->fresh()->status);
        $user->club->forceFill(['access_override' => 'deny'])->save();
        $this->getJson(route('subscription.result', $payment))->assertForbidden();
    }

    public function test_real_test_link_requires_consent_records_request_and_never_activates_plan(): void
    {
        [$user, $selection] = $this->prepareCheckout();
        $this->withSession(['selected_plan' => $selection])->get(route('subscription.checkout', ['link_test' => 1]))->assertOk()->assertSee('100')->assertSee('Prueba real');
        $this->post(route('subscription.store'), array_replace($this->data(100, true), ['accept_terms' => 0]))->assertSessionHasErrors('accept_terms');
        $this->post(route('subscription.store'), $this->data(100, true))->assertRedirect();
        $payment = SubscriptionPayment::firstOrFail();
        $this->assertSame('manual_review', $payment->status);
        $this->assertSame('payment_link_test', $payment->mode);
        $this->assertSame(100, $payment->amount);
        $this->get(route('subscription.result', $payment))->assertOk()->assertSee('https://www.webpay.cl/form-pay/437548', false)->assertSee($payment->buy_order);
        $this->post(route('subscription.refresh', $payment))->assertRedirect();
        $this->assertSame('manual_review', $payment->fresh()->status);
        $this->getJson(route('subscription.result', $payment))->assertOk()->assertJson(['enabled' => false, 'redirect' => null]);
        $this->artisan('billing:review-link', ['order' => $payment->buy_order, '--amount' => 100, '--authorization' => 'TEST100'])->assertSuccessful();
        $this->assertTrue($user->fresh()->subscription_checkout_required);
        $this->assertSame(0, Subscription::withoutGlobalScope('club')->where('club_id', $user->club_id)->count());
        $user->forceFill(['subscription_checkout_required' => false, 'onboarding_completed' => true])->save();
        $this->get(route('dashboard'))->assertOk();
        Http::assertNothingSent();
    }

    public function test_plan_activation_requires_manual_verified_matching_amount_and_is_not_repeatable(): void
    {
        [$user, $selection] = $this->prepareCheckout();
        $user->forceFill(['onboarding_completed' => false])->save();
        $service = app(SubscriptionCheckoutService::class);
        $payment = $service->start($user, $selection, $this->data(65000));
        $this->assertTrue($user->fresh()->subscription_checkout_required);
        $this->artisan('billing:review-link', ['order' => $payment->buy_order, '--amount' => 100, '--authorization' => 'PLAN65000'])->assertFailed();
        $this->assertSame('manual_review', $payment->fresh()->status);
        $this->artisan('billing:review-link', ['order' => $payment->buy_order, '--amount' => 65000, '--authorization' => 'PLAN65000'])->assertSuccessful();
        $this->assertFalse($user->fresh()->subscription_checkout_required);
        $this->assertSame('active', Subscription::firstOrFail()->status);
        $this->actingAs($user->fresh())->getJson(route('subscription.result', $payment))->assertOk()->assertJson(['enabled' => true, 'redirect' => route('onboarding.step1')]);
        $end = Subscription::firstOrFail()->ends_at->toDateTimeString();
        $this->artisan('billing:review-link', ['order' => $payment->buy_order, '--amount' => 65000, '--authorization' => 'PLAN65000'])->assertFailed();
        $this->assertSame($end, Subscription::firstOrFail()->ends_at->toDateTimeString());
    }

    public function test_request_is_private_and_untrusted_amount_cannot_change_price(): void
    {
        [$user, $selection] = $this->prepareCheckout();
        $this->withSession(['selected_plan' => $selection])->post(route('subscription.store'), $this->data(100))->assertSessionHasErrors('quoted_amount');
        $this->assertDatabaseCount('subscription_payments', 0);
        $payment = app(SubscriptionCheckoutService::class)->start($user, $selection, $this->data(65000));
        $this->actingAs(User::factory()->create())->get(route('subscription.result', $payment))->assertNotFound();
    }
}
