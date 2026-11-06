<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerSearchRequest;
use App\Models\Club;
use Illuminate\Http\RedirectResponse;

class PublicBookingController extends Controller
{
    public function __invoke(PlayerSearchRequest $request, Club $club): RedirectResponse
    {
        abort_if($club->access_override === 'deny', 404);
        abort_unless($club->onboarding_completed_at && $club->opens_at && $club->closes_at, 404);
        $filters = $request->validated();

        return redirect()->route('player.club', array_filter([
            'slug' => $club->slug,
            'date' => $filters['date'] ?? today()->toDateString(),
            'sport' => $filters['sport'] ?? null,
        ]));
    }
}
