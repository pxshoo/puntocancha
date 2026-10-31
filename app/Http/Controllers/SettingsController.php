<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()->role === 'club_admin', 403);

        return view('settings.index', [
            'club' => $request->user()->club,
            'subscription' => Subscription::query()->with('plan')->latest('id')->first(),
            'courtCount' => $request->user()->club->courts()->count(),
        ]);
    }
}
