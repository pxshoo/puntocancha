<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && $user->role === 'club_admin' && ! $user->onboarding_completed
            && ! $request->routeIs('onboarding.*', 'logout', 'public.bookings')) {
            return redirect()->route('onboarding.step1');
        }

        return $next($request);
    }
}
