<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $requiresPayment = $user?->subscription_checkout_required ?? false;
        if ($user?->role === 'club_admin' && ! $requiresPayment
            && ! $request->routeIs('home', 'subscription.*', 'legal.*', 'logout', 'public.bookings', 'club.logo', 'webpay.*')
            && SubscriptionPayment::where('club_id', $user->club_id)->where('status', 'paid')->exists()) {
            $subscription = Subscription::withoutGlobalScope('club')->where('club_id', $user->club_id)->first();
            $requiresPayment = ! $subscription || ! $subscription->isCurrent();
        }
        if ($user && $user->role === 'club_admin' && $requiresPayment
            && ! $request->routeIs('home', 'subscription.*', 'legal.*', 'logout', 'webpay.*', 'public.bookings', 'club.logo')) {
            return redirect()->route('subscription.checkout');
        }
        if ($user && $user->role === 'club_admin' && ! $user->onboarding_completed
            && ! $request->routeIs('home', 'subscription.*', 'legal.*', 'onboarding.*', 'logout', 'public.bookings', 'club.logo', 'webpay.*')) {
            return redirect()->route('onboarding.step1');
        }

        return $next($request);
    }
}
