<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Product;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function registerClub(int $plan = 1): User
    {
        $this->get('/register?plan='.$plan)->assertOk();
        $this->post('/register', [
            'name' => 'Administradora', 'club_name' => 'Club del Sur', 'phone' => '+56912345678',
            'email' => 'admin@example.com', 'password' => 'password', 'password_confirmation' => 'password',
            'role' => 'staff', 'onboarding_completed' => true,
        ])->assertRedirect(route('subscription.checkout'))->assertSessionHasNoErrors();

        $user = auth()->user();
        $user->forceFill(['subscription_checkout_required' => false])->save();
        Subscription::query()->update(['status' => 'active', 'ends_at' => now()->addMonthNoOverflow()]);

        return $user;
    }

    private function courts(int $count): array
    {
        return collect(range(1, $count))->map(fn ($i) => [
            'key' => (string) Str::uuid(), 'name' => 'Cancha '.$i, 'sport_type' => 'padel',
            'surface_type' => 'Pasto sintético', 'is_covered' => 1, 'default_hourly_rate' => 20000,
        ])->all();
    }

    private function hours(bool $seed = false): array
    {
        return ['opens_at' => '08:00', 'closes_at' => '23:00', 'slot_minutes' => 90, 'seed_products' => $seed];
    }

    public function test_paid_admin_can_start_setup_with_numeric_plan(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 30)->setTime(12, 0));
        $user = $this->registerClub(2);
        $this->assertSame('club_admin', $user->role);
        $this->assertFalse($user->onboarding_completed);
        $this->assertSame('Club del Sur', $user->club->name);
        $subscription = Subscription::firstOrFail();
        $this->assertEquals(2, $subscription->plan_id);
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->starts_at->equalTo(now()));
        $this->assertTrue($subscription->ends_at->equalTo(now()->addMonthNoOverflow()));
        $this->assertEquals(6, $subscription->plan->court_limit);
        $this->assertDatabaseCount('users', 1);
        $this->get('/onboarding/paso-1')->assertOk()->assertSee('Paso 1 de 2');
    }

    public function test_incomplete_admin_is_redirected_and_can_log_out_and_resume(): void
    {
        $user = $this->registerClub();
        foreach (['/dashboard', '/reservas', '/pos', '/inventario', '/profile'] as $path) {
            $this->get($path)->assertRedirect(route('onboarding.step1'));
        }
        $this->post('/inventario', [])->assertRedirect(route('onboarding.step1'));
        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('onboarding.step1'));
    }

    public function test_basic_limit_and_idempotent_court_save_are_enforced_on_server(): void
    {
        $this->registerClub();
        $rows = $this->courts(2);
        $this->post('/onboarding/paso-1', ['courts' => $rows])->assertRedirect(route('onboarding.step2'));
        $this->post('/onboarding/paso-1', ['courts' => $rows])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('courts', 2);
        $extra = $this->courts(1);
        $extra[0]['name'] = 'Cancha extra';
        $this->post('/onboarding/paso-1', ['courts' => $extra])->assertSessionHasErrors('courts');
        $this->assertDatabaseCount('courts', 2);
        $rows[0]['name'] = 'Cancha renovada';
        $this->post('/onboarding/paso-1', ['courts' => $rows])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('courts', ['name' => 'Cancha renovada', 'sport_type' => 'padel', 'is_covered' => true]);
    }

    public function test_pro_limit_is_six_and_premium_accepts_more_than_six(): void
    {
        $this->registerClub(2);
        $this->post('/onboarding/paso-1', ['courts' => $this->courts(7)])->assertSessionHasErrors('courts');
        $this->assertDatabaseCount('courts', 0);
        $this->post('/onboarding/paso-1', ['courts' => $this->courts(6)])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('courts', 6);
        Subscription::firstOrFail()->update(['plan_id' => 3]);
        $extra = $this->courts(2);
        $this->post('/onboarding/paso-1', ['courts' => $extra])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('courts', 8);
    }

    public function test_courts_required_before_step_two_and_completion(): void
    {
        $this->registerClub();
        $this->get('/onboarding/paso-2')->assertRedirect(route('onboarding.step1'));
        $this->post('/onboarding/completar', $this->hours())->assertRedirect(route('onboarding.step1'));
        $this->assertFalse(auth()->user()->fresh()->onboarding_completed);
        $this->post('/onboarding/paso-1', ['courts' => []])->assertSessionHasErrors('courts');
        $row = $this->courts(1);
        $row[0]['sport_type'] = 'invalid';
        $row[0]['default_hourly_rate'] = -1;
        $this->post('/onboarding/paso-1', ['courts' => $row])
            ->assertSessionHasErrors(['courts.0.sport_type', 'courts.0.default_hourly_rate']);
    }

    public function test_completion_saves_hours_and_seeds_once_without_inventing_stock(): void
    {
        $user = $this->registerClub();
        $this->post('/onboarding/paso-1', ['courts' => $this->courts(1)])->assertSessionHasNoErrors();
        $this->get('/onboarding/paso-2')->assertOk()->assertSee('Paso 2 de 2');
        $this->post('/onboarding/completar', $this->hours(true))
            ->assertRedirect(route('bookings.index'))->assertSessionHas('public_booking_url');
        $this->assertTrue($user->fresh()->onboarding_completed);
        $club = $user->club->fresh();
        $this->assertSame(90, $club->slot_minutes);
        $this->assertSame('08:00', substr($club->opens_at, 0, 5));
        $this->assertNotNull($club->onboarding_completed_at);
        $this->assertDatabaseCount('products', 6);
        $this->assertDatabaseCount('product_categories', 3);
        $this->assertEquals(0, Product::sum('stock'));
        $this->assertEquals(0, Product::sum('cost_price'));
        $this->post('/onboarding/completar', $this->hours(true))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('products', 6);
        $this->get('/reservas')->assertOk()->assertSee('Enlace público');
        $this->get('/dashboard')->assertOk();
        $this->get('/onboarding/paso-1')->assertRedirect(route('bookings.index'));
    }

    public function test_seed_is_optional_and_invalid_hours_cannot_complete(): void
    {
        $user = $this->registerClub();
        $this->post('/onboarding/paso-1', ['courts' => $this->courts(1)]);
        foreach ([
            ['opens_at' => '23:00', 'closes_at' => '08:00'],
            ['opens_at' => '08:00', 'closes_at' => '08:30'],
            ['slot_minutes' => 45],
        ] as $invalid) {
            $this->post('/onboarding/completar', array_replace($this->hours(), $invalid))->assertSessionHasErrors();
            $this->assertFalse($user->fresh()->onboarding_completed);
        }
        $this->post('/onboarding/completar', $this->hours())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('products', 0);
    }

    public function test_expired_trials_cannot_create_courts_or_complete_setup(): void
    {
        $user = $this->registerClub();
        $subscription = Subscription::firstOrFail();
        $this->travelTo($subscription->ends_at->copy()->addSecond());
        $this->post('/onboarding/paso-1', ['courts' => $this->courts(1)])->assertSessionHasErrors('subscription');
        $this->assertDatabaseCount('courts', 0);
        $this->assertFalse($user->fresh()->onboarding_completed);
        $this->get('/onboarding/paso-1')->assertOk()->assertSee('no está vigente');
    }

    public function test_public_url_only_exposes_availability_of_completed_club(): void
    {
        $user = $this->registerClub();
        $slug = $user->club->slug;
        $this->get('/clubes/'.$slug.'/reservas')->assertNotFound();
        $this->post('/onboarding/paso-1', ['courts' => $this->courts(1)]);
        $this->post('/onboarding/completar', $this->hours());
        $date = today()->addDay()->toDateString();
        $court = Court::firstOrFail();
        Booking::factory()->create([
            'court_id' => $court->id, 'customer_name' => 'Nombre confidencial',
            'customer_phone' => '999888777', 'start_time' => $date.' 08:00:00', 'end_time' => $date.' 09:30:00',
        ]);
        $other = User::factory()->create();
        $this->actingAs($other);
        Court::factory()->create(['name' => 'Cancha de otro club']);
        $this->post('/logout');
        $this->get('/clubes/'.$slug.'/reservas?date='.$date)->assertOk()->assertSee('Disponible')
            ->assertSee('No disponible')->assertSee('08:00')->assertSee('09:30')
            ->assertDontSee('Nombre confidencial')->assertDontSee('999888777')->assertDontSee('Cancha de otro club');
    }

    public function test_guest_cannot_setup_a_club_and_invalid_plan_does_not_create_account(): void
    {
        $this->get('/onboarding/paso-1')->assertRedirect('/login');
        $this->post('/onboarding/paso-1', ['courts' => $this->courts(1)])->assertRedirect('/login');
        $this->post('/register', [
            'name' => 'Invalid', 'club_name' => 'Invalid', 'phone' => '+56912345678',
            'email' => 'invalid@example.com', 'password' => 'password', 'password_confirmation' => 'password',
            'plan_id' => 999,
        ])->assertSessionHasErrors('plan_id');
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_other_club_courts_do_not_consume_new_club_limit(): void
    {
        $other = User::factory()->create();
        $this->actingAs($other);
        Court::factory()->count(3)->create();
        $this->post('/logout');
        $owner = $this->registerClub();
        $this->post('/onboarding/paso-1', ['courts' => $this->courts(2)])->assertSessionHasNoErrors();
        $this->assertSame(2, Court::count());
        $this->assertTrue(Court::get()->every(fn ($court) => $court->club_id === $owner->club_id));
        $this->assertDatabaseCount('courts', 5);
    }
}
