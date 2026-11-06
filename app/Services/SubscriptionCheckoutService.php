<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Court;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SubscriptionCheckoutService
{
    public function __construct(private SubscriptionWebpayService $gateway) {}

    public function quote(array $selection): array
    {
        $plan = Plan::where('slug', $selection['key'])->firstOrFail();
        $monthly = (int) round((float) $plan->price);
        if (config('billing.test_prices')) {
            $monthly = ['basic' => 100, 'pro' => 150, 'premium' => 200][$plan->slug];
            $selection['billing'] = 'monthly';
        }
        $amount = $selection['billing'] === 'annual' ? (int) round($monthly * 12 * .8) : $monthly;
        abort_if($amount < 1, 422);

        return ['plan' => $plan, 'amount' => $amount, 'billing' => $selection['billing']];
    }

    public function start(User $user, array $selection, array $data): SubscriptionPayment
    {
        return DB::transaction(function () use ($user, $selection, $data) {
            Club::whereKey($user->club_id)->lockForUpdate()->firstOrFail();
            $hash = hash_hmac('sha256', $user->id.':'.$data['request_key'], config('app.key'));
            if ($existing = SubscriptionPayment::where('request_hash', $hash)->first()) {
                return $existing;
            }
            if (SubscriptionPayment::where('club_id', $user->club_id)->whereIn('status', ['initialized', 'manual_review'])->exists()) {
                throw ValidationException::withMessages(['payment' => 'Ya tienes un pago en proceso. Revisa su estado antes de pagar nuevamente.']);
            }
            $quote = $this->quote($selection);
            $linkMode = config('billing.mode') === 'payment_link';
            $test = $linkMode && ! empty($data['link_test']);
            if ($test && ! config('billing.link_test_enabled')) {
                throw ValidationException::withMessages(['payment' => 'La prueba de cobro no está habilitada.']);
            }
            $link = config('billing.test_prices') && ! $test ? config('billing.fixed_links.'.$selection['key']) : config('billing.payment_link');
            if ($linkMode && ! preg_match('~^https://www\.webpay\.cl/form-pay/[0-9]+$~', (string) $link)) {
                throw ValidationException::withMessages(['payment' => 'Falta configurar el enlace Webpay.cl de monto fijo para este plan.']);
            }
            if ($test) {
                $quote['amount'] = 100;
            }
            if ($quote['amount'] !== (int) $data['quoted_amount']) {
                throw ValidationException::withMessages(['quoted_amount' => 'El precio cambió. Revisa el total y acepta nuevamente antes de pagar.']);
            }
            if ($quote['plan']->court_limit !== null && Court::withoutGlobalScope('club')->where('club_id', $user->club_id)->where('is_active', true)->count() > $quote['plan']->court_limit) {
                throw ValidationException::withMessages(['payment' => 'Este plan admite menos canchas que las que tienes activas. Desactiva las necesarias antes de contratarlo.']);
            }
            $payment = new SubscriptionPayment;
            $payment->forceFill([
                'id' => (string) Str::uuid(), 'club_id' => $user->club_id, 'user_id' => $user->id,
                'plan_id' => $quote['plan']->id, 'plan_name' => $test ? 'Prueba real de cobro (sin activar plan)' : $quote['plan']->name, 'billing_cycle' => $quote['billing'],
                'amount' => $quote['amount'], 'mode' => $test ? 'payment_link_test' : config('billing.mode'), 'status' => $linkMode ? 'manual_review' : 'initialized',
                'request_hash' => $hash, 'buy_order' => 'SUB'.Str::upper(Str::random(23)), 'session_id' => Str::random(40),
                'terms_version' => config('billing.version'), 'privacy_version' => config('billing.version'),
                'terms_snapshot' => config('billing.terms'), 'privacy_snapshot' => config('billing.privacy'),
                'accepted_at' => now(), 'expires_at' => now()->addMinutes(20),
            ])->save();
            if ($linkMode) {
                $payment->forceFill(['gateway_url' => $link])->save();

                return $payment;
            }
            $response = $this->gateway->create($payment);
            $payment->forceFill(['token' => $response['token'], 'token_hash' => hash('sha256', $response['token']), 'gateway_url' => $response['url']])->save();

            return $payment;
        });
    }

    public function settle(SubscriptionPayment $payment, bool $commit = true): SubscriptionPayment
    {
        if ($payment->status !== 'initialized') {
            return $payment;
        }
        $data = $this->gateway->result($payment, $commit);
        if (($data['buy_order'] ?? null) !== $payment->buy_order || ($data['session_id'] ?? null) !== $payment->session_id
            || ! is_numeric($data['amount'] ?? null) || (float) $data['amount'] !== (float) $payment->amount) {
            throw new RuntimeException('El pago no coincide con la suscripción.');
        }

        return DB::transaction(function () use ($payment, $data) {
            Club::whereKey($payment->club_id)->lockForUpdate()->firstOrFail();
            $locked = SubscriptionPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'initialized') {
                return $locked;
            }
            if (($data['status'] ?? null) === 'AUTHORIZED' && ($data['response_code'] ?? null) === 0) {
                $this->activate($locked);
                $locked->forceFill(['status' => 'paid', 'authorization_code' => $data['authorization_code'] ?? null])->save();
                app(ConfirmationService::class)->subscription($locked);
            } elseif (in_array($data['status'] ?? null, ['FAILED', 'REVERSED', 'NULLIFIED'], true)
                || (($data['status'] ?? null) === 'INITIALIZED' && $locked->expires_at->isPast())) {
                $locked->forceFill(['status' => 'failed'])->save();
            }

            return $locked;
        });
    }

    public function reviewLink(string $order, int $amount, string $authorization, bool $reject = false): SubscriptionPayment
    {
        return DB::transaction(function () use ($order, $amount, $authorization, $reject) {
            $payment = SubscriptionPayment::where('buy_order', $order)->whereIn('mode', ['payment_link', 'payment_link_test'])->firstOrFail();
            Club::whereKey($payment->club_id)->lockForUpdate()->firstOrFail();
            $locked = SubscriptionPayment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'manual_review') {
                throw ValidationException::withMessages(['payment' => 'La solicitud ya fue revisada.']);
            }
            if ($reject) {
                $locked->forceFill(['status' => 'failed'])->save();

                return $locked;
            }
            if ($amount !== $locked->amount || trim($authorization) === '') {
                throw ValidationException::withMessages(['payment' => 'El monto debe coincidir y debes indicar la autorización verificada en Transbank.']);
            }
            if (SubscriptionPayment::where('authorization_code', trim($authorization))->where('status', 'paid')->exists()) {
                throw ValidationException::withMessages(['payment' => 'Esa autorización ya fue registrada.']);
            }
            if ($locked->mode !== 'payment_link_test') {
                $this->activate($locked);
            }
            $locked->forceFill(['status' => 'paid', 'authorization_code' => trim($authorization)])->save();
            app(ConfirmationService::class)->subscription($locked);

            return $locked;
        });
    }

    private function activate(SubscriptionPayment $locked): void
    {
        $subscription = Subscription::withoutGlobalScope('club')->where('club_id', $locked->club_id)->lockForUpdate()->first();
        $renewal = $subscription && $subscription->status === 'active' && $subscription->ends_at?->isFuture() && $subscription->plan_id === $locked->plan_id;
        $start = $renewal ? $subscription->ends_at->copy() : now();
        $end = $locked->billing_cycle === 'annual' ? $start->copy()->addYearNoOverflow() : $start->copy()->addMonthNoOverflow();
        $subscription ??= new Subscription;
        $subscription->forceFill([
            'club_id' => $locked->club_id, 'user_id' => $locked->user_id, 'plan_id' => $locked->plan_id, 'status' => 'active',
            'billing_cycle' => $locked->billing_cycle, 'starts_at' => $renewal ? $subscription->starts_at : now(), 'ends_at' => $end,
        ])->saveQuietly();
        User::where('club_id', $locked->club_id)->where('role', 'club_admin')->update(['subscription_checkout_required' => false]);
    }
}
