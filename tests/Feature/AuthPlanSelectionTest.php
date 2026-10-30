<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthPlanSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_plan_selection_is_kept_between_login_and_registration(): void
    {
        $this->get('/login?plan=pro&billing=annual')->assertOk()->assertSee('Club Pro')
            ->assertSee('52.000')->assertSee('624.000')
            ->assertSessionHas('selected_plan', ['key' => 'pro', 'billing' => 'annual']);
        $this->get('/register')->assertOk()->assertSee('Club Pro')->assertSee('624.000');
        $this->get('/login')->assertOk()->assertSee('Club Pro');
        $this->get('/login?plan=basic')->assertOk()->assertSee('35.000')
            ->assertSessionHas('selected_plan', ['key' => 'basic', 'billing' => 'monthly']);
    }

    public function test_invalid_plan_and_billing_cannot_replace_selection(): void
    {
        $this->get('/login?plan=pro')->assertOk();
        $this->get('/login?plan=unknown')->assertSessionHasErrors('plan');
        $this->get('/login?plan=pro&billing=free')->assertSessionHasErrors('billing');
        $this->assertSame(['key' => 'pro', 'billing' => 'monthly'], session('selected_plan'));
    }

    public function test_selection_survives_failed_and_successful_login(): void
    {
        $user = User::factory()->create();
        $this->get('/login?plan=pro&billing=annual')->assertOk();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('selected_plan', ['key' => 'pro', 'billing' => 'annual']);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_preserves_plan_and_creates_an_isolated_club(): void
    {
        $this->get('/login?plan=premium&billing=annual')->assertOk();
        $this->get('/register')->assertOk()->assertSee('Complejo Premium');
        $this->post('/register', [
            'name' => 'Club Owner', 'email' => 'owner@example.com',
            'club_name' => 'Club de prueba', 'phone' => '+56912345678',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirect(route('onboarding.step1'))
            ->assertSessionHas('selected_plan', ['key' => 'premium', 'billing' => 'annual']);
        $this->assertAuthenticated();
        $this->assertNotNull(auth()->user()->club_id);
    }

    public function test_landing_links_all_plans_to_login_and_direct_access_has_no_plan(): void
    {
        $this->get('/')->assertOk()->assertSee('/login?plan=basic', false)
            ->assertSee('/login?plan=pro', false)->assertSee('/login?plan=premium', false);
        $this->get('/login')->assertOk()->assertDontSee('Plan seleccionado');
        $this->get('/register')->assertOk()->assertDontSee('Plan seleccionado');
    }
}
