<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SuperAdminService
{
    public function access(User $actor, Club $club, array $data): void
    {
        abort_unless($actor->is_superadmin, 403);
        DB::transaction(function () use ($actor, $club, $data) {
            $locked = Club::whereKey($club->id)->lockForUpdate()->firstOrFail();
            $before = $locked->access_override;
            $after = match ($data['action']) {
                'grant' => 'allow', 'suspend' => 'deny', 'reset' => null,
                default => throw ValidationException::withMessages(['action' => 'Acción de acceso inválida.']),
            };
            $locked->forceFill(['access_override' => $after])->save();
            if ($after === 'allow') {
                User::where('club_id', $club->id)->where('role', 'club_admin')->update(['subscription_checkout_required' => false]);
            }
            if ($data['action'] === 'reset') {
                $subscription = Subscription::withoutGlobalScope('club')->where('club_id', $club->id)->first();
                User::where('club_id', $club->id)->where('role', 'club_admin')->update(['subscription_checkout_required' => ! $subscription?->isCurrent()]);
            }
            $this->audit($actor, $club->id, $data, ['before' => $before, 'after' => $after]);
            if ($before !== $after) {
                app(ConfirmationService::class)->accessChanged($locked);
            }
        });
    }

    public function review(User $actor, SubscriptionPayment $payment, array $data): void
    {
        abort_unless($actor->is_superadmin, 403);
        if (! in_array($data['action'], ['approve', 'reject'], true)) {
            throw ValidationException::withMessages(['action' => 'Acción de pago inválida.']);
        }
        DB::transaction(function () use ($actor, $payment, $data) {
            app(SubscriptionCheckoutService::class)->reviewLink($payment->buy_order, (int) ($data['amount'] ?? 0), $data['authorization'] ?? '', $data['action'] === 'reject');
            $this->audit($actor, $payment->club_id, $data, ['before' => $payment->status, 'after' => $payment->fresh()->status], $payment->id);
        });
    }

    private function audit(User $actor, int $clubId, array $data, array $changes, ?string $paymentId = null): void
    {
        DB::table('admin_audits')->insert(['actor_id' => $actor->id, 'club_id' => $clubId, 'action' => $data['action'],
            'payment_id' => $paymentId, 'reason' => $data['reason'], 'changes' => json_encode($changes, JSON_THROW_ON_ERROR),
            'created_at' => now(), 'updated_at' => now()]);
    }
}
