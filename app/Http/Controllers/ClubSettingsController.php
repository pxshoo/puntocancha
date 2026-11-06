<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateClubSettingsRequest;
use App\Models\Club;
use App\Models\Court;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ClubSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()->role === 'club_admin', 403);

        $club = $request->user()->club;
        $court = Court::where('is_active', true)->orderBy('id')->first();

        return view('player.club-settings', [
            'club' => $club,
            'suggestions' => [
                'city' => $club->city ?: $court?->city,
                'address' => $club->address ?: $court?->address,
                'phone' => $club->phone ?: $request->user()->phone,
            ],
        ]);
    }

    public function update(UpdateClubSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['hourly_rates'] = array_values($data['hourly_rates'] ?? []);
        DB::transaction(function () use ($data, $request) {
            $club = Club::whereKey($request->user()->club_id)->lockForUpdate()->firstOrFail();
            $minutes = CarbonImmutable::createFromFormat('!H:i', $data['opens_at'])->diffInMinutes(CarbonImmutable::createFromFormat('!H:i', $data['closes_at']));
            if ($minutes < (int) $data['slot_minutes']) {
                throw ValidationException::withMessages(['closes_at' => 'El horario debe permitir al menos un turno completo.']);
            }
            if ($data['marketplace_enabled'] && ! Court::where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['marketplace_enabled' => 'Agrega al menos una cancha activa antes de publicar.']);
            }
            $club->forceFill($data);
            if ($data['marketplace_enabled'] && ! $club->onboarding_completed_at) {
                $club->onboarding_completed_at = now();
            }
            $club->save();
        }, 3);

        return redirect()->route('club.settings.edit')->with('success', 'Datos del club y publicación actualizados.');
    }
}
