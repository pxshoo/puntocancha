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
        if ($user?->is_superadmin && $request->routeIs('superadmin.*')) {
            return $next($request);
        }
        $override = $user?->club?->access_override;
        if ($override === 'deny' && in_array($user?->role, ['club_admin', 'staff'], true)
            && ! $request->routeIs('account.status', 'logout', 'profile.*', 'webpay.*', 'subscription.return')) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'El acceso de tu club está suspendido. Contacta a soporte.'], 403);
            }

            return response()->view('errors.club-suspended', ['club' => $user->club], 403)
                ->header('Content-Type', 'text/html; charset=UTF-8')->header('Cache-Control', 'private, no-store');
        }
        $requiresPayment = $user?->subscription_checkout_required ?? false;
        if ($user?->role === 'club_admin' && $override !== 'allow' && ! $requiresPayment
            && ! $request->routeIs('home', 'subscription.*', 'legal.*', 'logout', 'public.bookings', 'club.logo', 'webpay.*')
            && SubscriptionPayment::where('club_id', $user->club_id)->where('status', 'paid')->where('mode', '!=', 'payment_link_test')->exists()) {
            $subscription = Subscription::withoutGlobalScope('club')->where('club_id', $user->club_id)->first();
            $requiresPayment = ! $subscription || ! $subscription->isCurrent();
        }
        if ($user && $user->role === 'club_admin' && $override !== 'allow' && $requiresPayment
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
