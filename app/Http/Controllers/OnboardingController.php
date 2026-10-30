<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompleteOnboardingRequest;
use App\Http\Requests\StoreOnboardingCourtsRequest;
use App\Models\Court;
use App\Services\OnboardingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OnboardingController extends Controller
{
    public function step1(Request $request, OnboardingService $service): View|RedirectResponse
    {
        abort_unless($request->user()->role === 'club_admin', 403);
        if ($request->user()->onboarding_completed) {
            return redirect()->route('bookings.index');
        }
        $courts = Court::get()->map(fn ($court) => [
            'key' => $court->setup_key, 'name' => $court->name, 'sport_type' => $court->sport_type,
            'surface_type' => $court->surface_type, 'is_covered' => (int) $court->is_covered,
            'default_hourly_rate' => (int) $court->default_hourly_rate, 'saved' => true,
        ])->all();

        return view('onboarding.step1', [
            'courts' => $courts ?: [['key' => (string) Str::uuid(), 'name' => '', 'sport_type' => 'padel', 'surface_type' => '', 'is_covered' => 0, 'default_hourly_rate' => 20000, 'saved' => false]],
            'subscription' => $service->subscription($request->user()),
        ]);
    }

    public function saveStep1(StoreOnboardingCourtsRequest $request, OnboardingService $service): RedirectResponse
    {
        $service->saveCourts($request->user(), $request->validated('courts'));

        return redirect()->route('onboarding.step2');
    }

    public function step2(Request $request, OnboardingService $service): View|RedirectResponse
    {
        abort_unless($request->user()->role === 'club_admin', 403);
        if ($request->user()->onboarding_completed) {
            return redirect()->route('bookings.index');
        }
        if (! Court::where('is_active', true)->exists()) {
            return redirect()->route('onboarding.step1')->withErrors(['courts' => 'Configura tus canchas antes de continuar.']);
        }

        return view('onboarding.step2', [
            'club' => $request->user()->club, 'subscription' => $service->subscription($request->user()),
        ]);
    }

    public function complete(CompleteOnboardingRequest $request, OnboardingService $service): RedirectResponse
    {
        if (! Court::where('is_active', true)->exists()) {
            return redirect()->route('onboarding.step1')->withErrors(['courts' => 'Primero debes crear tus canchas.']);
        }
        $service->complete($request->user(), $request->validated());

        return redirect()->route('bookings.index')
            ->with('success', '¡Bienvenido a PuntoCancha! Tu club está listo para comenzar.')
            ->with('public_booking_url', route('public.bookings', $request->user()->club->slug));
    }
}
