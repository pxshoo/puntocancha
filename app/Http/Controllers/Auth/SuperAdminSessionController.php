<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SuperAdminSessionController extends Controller
{
    public function create(): View
    {
        return view('superadmin.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();
        $request->session()->forget(['url.intended', 'selected_plan', 'subscription_purchase', 'auth_audience']);

        return to_route('superadmin.index');
    }
}
