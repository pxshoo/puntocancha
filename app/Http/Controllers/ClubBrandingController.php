<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateClubBrandingRequest;
use App\Services\ClubBrandingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClubBrandingController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()->role === 'club_admin', 403);

        return view('settings.branding', [
            'club' => $request->user()->club,
            'palettes' => config('branding.palettes'),
        ]);
    }

    public function update(UpdateClubBrandingRequest $request, ClubBrandingService $branding): RedirectResponse
    {
        $branding->update($request->user()->club, $request->validated('brand_palette'), $request->file('logo'), $request->boolean('remove_logo'));

        return to_route('settings.branding.edit')->with('success', 'La identidad de tu club se actualizó correctamente.');
    }
}
