<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Court;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourtService
{
    public function save(array $data, ?Court $court = null): Court
    {
        return DB::transaction(function () use ($data, $court) {
            Club::whereKey(auth()->user()->club_id)->lockForUpdate()->firstOrFail();
            $court = $court ? Court::whereKey($court->id)->lockForUpdate()->firstOrFail() : new Court;
            $activating = (bool) $data['is_active'] && (! $court->exists || ! $court->is_active);
            if ($activating) {
                $subscription = Subscription::with('plan')->lockForUpdate()->first();
                if ($subscription && ! $subscription->isCurrent()) {
                    throw ValidationException::withMessages(['is_active' => 'Tu suscripción no está vigente. Contacta a soporte para activar más canchas.']);
                }
                $activeCount = Court::where('is_active', true)->count();
                $limit = $subscription ? $subscription->plan->court_limit : max(2, $activeCount);
                if ($limit !== null && $activeCount >= $limit) {
                    throw ValidationException::withMessages(['is_active' => "Tu cuenta permite {$limit} canchas activas. Desactiva una o solicita una mejora de plan."]);
                }
            }
            if (! $court->exists) {
                $court->setup_key = (string) Str::uuid();
            }
            $court->fill($data)->save();

            return $court;
        }, 3);
    }
}
