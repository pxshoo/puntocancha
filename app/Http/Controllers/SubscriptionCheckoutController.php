<?php

namespace App\Http\Controllers;

use App\Http\Requests\StartSubscriptionPaymentRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\PlanSelection;
use App\Services\SubscriptionCheckoutService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class SubscriptionCheckoutController extends Controller
{
    public function select(Request $request, PlanSelection $plans): View|RedirectResponse
    {
        $selection = $plans->capture($request);
        if (! $selection) {
            return redirect('/#planes');
        }
        $request->session()->put('subscription_purchase', true);
        if ($request->user()?->role === 'club_admin') {
            return to_route('subscription.checkout');
        }

        return view('subscription.account', compact('selection'));
    }

    public function checkout(Request $request, PlanSelection $plans, SubscriptionCheckoutService $service): View|RedirectResponse
    {
        abort_unless($request->user()->role === 'club_admin', 403);
        $selection = $plans->capture($request);
        if (! $selection) {
            $subscription = Subscription::with('plan')->where('club_id', $request->user()->club_id)->first();
            if (! $subscription) {
                return redirect('/#planes');
            }
            $request->session()->put('selected_plan', ['key' => $subscription->plan->slug, 'billing' => $subscription->billing_cycle]);
            $selection = $plans->capture($request);
        }
        $quote = $service->quote($selection);
        $pending = SubscriptionPayment::where('user_id', $request->user()->id)->where('club_id', $request->user()->club_id)->whereIn('status', ['initialized', 'manual_review'])->latest()->first();

        $linkTest = config('billing.mode') === 'payment_link' && config('billing.link_test_enabled') && $request->boolean('link_test');
        if ($linkTest) {
            $quote['amount'] = 100;
        }

        return view('subscription.checkout', ['linkTest' => $linkTest, 'selection' => $selection, 'quote' => $quote, 'pending' => $pending, 'requestKey' => (string) Str::uuid()]);
    }

    public function store(StartSubscriptionPaymentRequest $request, SubscriptionCheckoutService $service): Response|RedirectResponse
    {
        $selection = $request->session()->get('selected_plan');
        abort_unless($selection, 422);
        try {
            $payment = $service->start($request->user(), $selection, $request->validated());
        } catch (ValidationException|ModelNotFoundException $exception) {
            throw $exception;
        } catch (Throwable) {
            return to_route('subscription.checkout')->withErrors(['payment' => 'No pudimos iniciar el pago. Tu suscripción no fue activada. Intenta nuevamente.']);
        }

        return $this->handoff($payment);
    }

    public function resume(Request $request, SubscriptionPayment $payment): Response|RedirectResponse
    {
        $this->authorizePayment($request, $payment);

        return $this->handoff($payment);
    }

    private function handoff(SubscriptionPayment $payment): Response|RedirectResponse
    {
        if ($payment->status !== 'initialized' || $payment->expires_at->isPast()) {
            return to_route('subscription.result', $payment);
        }

        return response()->view('subscription.redirect', compact('payment'))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function callback(Request $request, SubscriptionCheckoutService $service): RedirectResponse
    {
        $data = $request->validate(['token_ws' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9]{20,128}$/'],
            'TBK_TOKEN' => ['nullable', 'string', 'regex:/^[a-zA-Z0-9]{20,128}$/']]);
        $token = $data['token_ws'] ?? $data['TBK_TOKEN'] ?? null;
        if (! $token) {
            return to_route('subscription.checkout')->withErrors(['payment' => 'Webpay no devolvió un resultado. Consulta el estado antes de volver a pagar.']);
        }
        $payment = SubscriptionPayment::where('token_hash', hash('sha256', $token))->firstOrFail();
        try {
            $service->settle($payment, isset($data['token_ws']));
        } catch (Throwable) {
            return to_route('subscription.result', $payment)->withErrors(['payment' => 'El pago sigue en verificación. No pagues nuevamente.']);
        }

        return to_route('subscription.result', $payment);
    }

    public function result(Request $request, SubscriptionPayment $payment): Response|JsonResponse
    {
        $this->authorizePayment($request, $payment);
        if ($request->expectsJson()) {
            $enabled = ($payment->status === 'paid' || $request->user()->club->access_override === 'allow') && $payment->mode !== 'payment_link_test'
                && $request->user()->club->access_override !== 'deny';

            return response()->json(['enabled' => $enabled, 'status' => $payment->status,
                'redirect' => $enabled ? ($request->user()->onboarding_completed ? route('dashboard') : route('onboarding.step1')) : null])
                ->header('Cache-Control', 'private, no-store');
        }
        if (in_array($payment->mode, ['payment_link', 'payment_link_test'], true)) {
            return response()->view('subscription.link-payment', compact('payment'))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
        }
        if ($payment->status === 'paid') {
            $request->session()->forget('subscription_purchase');
        }

        return response()->view('subscription.result', compact('payment'))->header('Cache-Control', 'no-store')->header('Referrer-Policy', 'no-referrer');
    }

    public function refresh(Request $request, SubscriptionPayment $payment, SubscriptionCheckoutService $service): RedirectResponse
    {
        $this->authorizePayment($request, $payment);
        try {
            $service->settle($payment, false);
        } catch (Throwable) {
            return to_route('subscription.result', $payment)->withErrors(['payment' => 'No fue posible verificar el estado. Intenta consultar nuevamente.']);
        }

        return to_route('subscription.result', $payment);
    }

    private function authorizePayment(Request $request, SubscriptionPayment $payment): void
    {
        abort_unless($request->user()->role === 'club_admin' && $payment->user_id === $request->user()->id && $payment->club_id === $request->user()->club_id, 404);
    }
}
