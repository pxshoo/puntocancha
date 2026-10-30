<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterClubRequest;
use App\Models\Plan;
use App\Services\PlanSelection;
use App\Services\RegistrationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(Request $request, PlanSelection $plans): View
    {
        return view('auth.register', [
            'selection' => $plans->capture($request),
            'availablePlans' => Plan::orderBy('id')->get(),
        ]);
    }

    public function store(RegisterClubRequest $request, RegistrationService $service, PlanSelection $plans): RedirectResponse
    {
        $data = $request->validated();
        $plans->capture($request);
        $selection = $request->session()->get('selected_plan', ['key' => 'basic', 'billing' => 'monthly']);
        $user = $service->register($data, $selection['key'], $selection['billing']);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        $plan = isset($data['plan_id']) ? Plan::findOrFail($data['plan_id']) : Plan::where('slug', $selection['key'])->firstOrFail();
        $request->session()->put('selected_plan', ['key' => $plan->slug, 'billing' => $selection['billing']]);

        return redirect()->route('onboarding.step1');
    }
}
