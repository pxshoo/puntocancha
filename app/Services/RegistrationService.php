<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegistrationService
{
    public function register(array $data, string $planKey, string $billing): User
    {
        return DB::transaction(function () use ($data, $planKey, $billing) {
            $plan = isset($data['plan_id']) ? Plan::findOrFail($data['plan_id']) : Plan::where('slug', $planKey)->firstOrFail();
            $user = new User;
            $user->fill(collect($data)->only(['name', 'club_name', 'phone', 'email', 'password'])->all());
            $user->forceFill(['role' => 'club_admin', 'onboarding_completed' => false, 'subscription_checkout_required' => true])->save();
            DB::table('subscriptions')->insert([
                'club_id' => $user->club_id, 'user_id' => $user->id, 'plan_id' => $plan->id,
                'status' => 'pending_payment', 'billing_cycle' => $billing,
                'starts_at' => now(), 'ends_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);

            return $user;
        }, 3);
    }
}
