<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TestPlanPricingTest extends TestCase
{
    use RefreshDatabase;

    public function test_prices_are_fixed_and_each_plan_uses_its_own_link(): void
    {
        config(['billing.mode' => 'payment_link', 'billing.test_prices' => true]);
        Http::preventStrayRequests();
        $service = app(SubscriptionCheckoutService::class);
        foreach (['basic' => 100, 'pro' => 150, 'premium' => 200] as $slug => $amount) {
            $this->actingAs($user = User::factory()->create());
            Plan::firstOrCreate(['slug' => $slug], ['name' => $slug, 'price' => 65000, 'interval' => 'monthly', 'court_limit' => null]);
            $link = 'https://www.webpay.cl/form-pay/'.(800000 + $amount);
            config(['billing.fixed_links.'.$slug => $link]);
            $selection = ['key' => $slug, 'billing' => 'annual'];
            $quote = $service->quote($selection);
            $this->assertSame($amount, $quote['amount']);
            $this->assertSame('monthly', $quote['billing']);
            $payment = $service->start($user, $selection, ['quoted_amount' => $amount, 'request_key' => (string) Str::uuid()]);
            $this->assertSame($amount, $payment->amount);
            $this->assertSame($link, $payment->gateway_url);
        }
        Http::assertNothingSent();
    }

    public function test_unconfigured_fixed_link_blocks_payment_instead_of_using_variable_link(): void
    {
        $this->actingAs($user = User::factory()->create());
        Plan::firstOrCreate(['slug' => 'basic'], ['name' => 'Basic', 'price' => 35000, 'interval' => 'monthly', 'court_limit' => 2]);
        config(['billing.mode' => 'payment_link', 'billing.test_prices' => true, 'billing.fixed_links.basic' => null,
            'billing.payment_link' => 'https://www.webpay.cl/form-pay/437548']);
        $this->expectException(ValidationException::class);
        app(SubscriptionCheckoutService::class)->start($user, ['key' => 'basic', 'billing' => 'monthly'],
            ['quoted_amount' => 100, 'request_key' => (string) Str::uuid()]);
    }
}
