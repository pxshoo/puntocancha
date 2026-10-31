<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterPlayerRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PlayerRegistrationController extends Controller
{
    public function create(Request $request): View
    {
        $request->session()->put('auth_audience', 'player');

        return view('player.register');
    }

    public function store(RegisterPlayerRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = new User;
        $user->fill(collect($data)->only(['name', 'email', 'phone', 'password'])->all());
        $user->forceFill(['role' => 'player', 'favorite_sport' => $data['favorite_sport'], 'club_id' => null, 'onboarding_completed' => true])->save();
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('player.explore');
    }
}
