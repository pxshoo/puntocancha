<?php

namespace App\Services;

use App\Models\Club;
use App\Models\Court;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnboardingService
{
    public function subscription(User $user): ?Subscription
    {
        return Subscription::with('plan')->where('club_id', $user->club_id)->first();
    }

    private function activePlan(User $user): Plan
    {
        $subscription = $this->subscription($user);
        if (! $subscription || (! $subscription->isCurrent() && $user->club->access_override !== 'allow')) {
            throw ValidationException::withMessages(['subscription' => 'La prueba o suscripción no está vigente. Contacta a soporte para activar tu plan.']);
        }

        return $subscription->plan;
    }

    public function saveCourts(User $user, array $rows, ?string $clubName = null): void
    {
        DB::transaction(function () use ($user, $rows, $clubName) {
            $club = Club::whereKey($user->club_id)->lockForUpdate()->firstOrFail();
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($lockedUser->onboarding_completed) {
                return;
            }
            $plan = $this->activePlan($user);
            $existing = Court::get()->keyBy('setup_key');
            $new = collect($rows)->filter(fn ($row) => ! $existing->has($row['key']))->count();
            if ($plan->court_limit !== null && $existing->count() + $new > $plan->court_limit) {
                throw ValidationException::withMessages(['courts' => "Tu plan permite hasta {$plan->court_limit} canchas. Reduce la cantidad o solicita una mejora de plan."]);
            }
            foreach ($rows as $row) {
                $court = Court::firstOrNew(['setup_key' => $row['key']]);
                $court->fill(collect($row)->except('key')->all())->save();
            }
            if ($clubName !== null) {
                $club->update(['name' => $clubName]);
                $lockedUser->forceFill(['club_name' => $clubName])->save();
            }
        }, 3);
    }

    public function complete(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data) {
            $club = Club::whereKey($user->club_id)->lockForUpdate()->firstOrFail();
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if ($lockedUser->onboarding_completed) {
                return;
            }
            $plan = $this->activePlan($user);
            $courtCount = Court::where('is_active', true)->count();
            if (! $courtCount || ($plan->court_limit !== null && Court::count() > $plan->court_limit)) {
                throw ValidationException::withMessages(['courts' => 'Primero configura al menos una cancha y respeta el límite de tu plan.']);
            }
            $opening = CarbonImmutable::createFromFormat('!H:i', $data['opens_at']);
            $closing = CarbonImmutable::createFromFormat('!H:i', $data['closes_at']);
            if ($opening->diffInMinutes($closing) < (int) $data['slot_minutes']) {
                throw ValidationException::withMessages(['closes_at' => 'El horario debe permitir al menos un turno completo.']);
            }
            $club->forceFill(collect($data)->only(['city', 'address', 'phone', 'marketplace_enabled'])->all());
            $club->forceFill([
                'opens_at' => $data['opens_at'], 'closes_at' => $data['closes_at'],
                'slot_minutes' => $data['slot_minutes'], 'onboarding_completed_at' => now(),
            ])->save();

            if ($data['seed_products'] ?? false) {
                $this->seedCatalog();
            }
            $lockedUser->forceFill(['onboarding_completed' => true])->save();
        }, 3);
        $user->refresh();
    }

    private function seedCatalog(): void
    {
        foreach ([
            ['Bebidas', 'Agua mineral 500 ml', 'PC-AGUA-500', 1500],
            ['Bebidas', 'Bebida isotónica 500 ml', 'PC-ISOTONICA', 2000],
            ['Snacks', 'Barra de cereal', 'PC-CEREAL', 1000],
            ['Snacks', 'Papas fritas', 'PC-PAPAS', 1500],
            ['Accesorios', 'Tarro de pelotas de pádel', 'PC-PELOTAS-PADEL', 7000],
            ['Accesorios', 'Tarro de pelotas de tenis', 'PC-PELOTAS-TENIS', 7000],
        ] as [$categoryName, $name, $barcode, $price]) {
            $category = ProductCategory::firstOrCreate(['name' => $categoryName]);
            Product::firstOrCreate(['barcode' => $barcode], [
                'name' => $name, 'category_id' => $category->id, 'cost_price' => 0,
                'sale_price' => $price, 'alert_min_stock' => 5, 'is_active' => true,
            ]);
        }
    }
}
