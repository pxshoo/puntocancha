<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\PlanSelection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request, PlanSelection $plans): View
    {
        $playerAccess = ! $request->has('plan') && ($request->query('audience') === 'player' || $request->session()->get('auth_audience') === 'player');
        $request->session()->put('auth_audience', $playerAccess ? 'player' : 'club');

        return view('auth.login', ['selection' => $playerAccess ? null : $plans->capture($request), 'playerAccess' => $playerAccess]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        if ($request->user()->role === 'player') {
            $request->session()->forget('url.intended');

            return redirect()->route('player.explore');
        }

        if ($request->user()->role === 'club_admin' && ! $request->user()->onboarding_completed) {
            return redirect()->route('onboarding.step1');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
