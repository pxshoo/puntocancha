<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountAccessController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless(in_array($user->role, ['club_admin', 'staff'], true) && $user->club_id, 403);
        $suspended = $user->club->access_override === 'deny';

        return response()->json(['enabled' => ! $suspended,
            'redirect' => $suspended ? null : ($user->role === 'club_admin' && ! $user->onboarding_completed ? route('onboarding.step1') : route('dashboard'))])
            ->header('Cache-Control', 'private, no-store');
    }
}
