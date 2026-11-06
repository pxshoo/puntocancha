<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionCheckoutService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_dedicated_owner_login_rejects_club_accounts_and_has_separate_layout(): void
    {
        $this->get(route('superadmin.login'))->assertOk()->assertSee('Acceso de propietario')->assertDontSee('Crea tu cuenta');
        $owner = User::factory()->create();
        $this->post(route('superadmin.login.store'), ['email' => $owner->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $admin = User::factory()->create(['is_superadmin' => true]);
        $this->post(route('superadmin.login.store'), ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('superadmin.index'));
        $this->get(route('superadmin.index'))->assertOk()->assertSee('Todas las cuentas')->assertSee('Clubes registrados')
            ->assertDontSee('Navegación del club')->assertDontSee('Punto de venta')->assertSee($owner->email);
    }

    public function test_account_details_are_private_and_reactivation_is_visible_to_existing_session(): void
    {
        $owner = User::factory()->create(['onboarding_completed' => true]);
        $owner->club->forceFill(['access_override' => 'deny'])->save();
        $this->actingAs($owner)->getJson(route('account.status'))->assertOk()->assertJson(['enabled' => false, 'redirect' => null]);
        $this->get(route('superadmin.account', $owner->club))->assertForbidden();
        $admin = User::factory()->create(['is_superadmin' => true]);
        $this->actingAs($admin)->get(route('superadmin.account', $owner->club))->assertOk()->assertSee($owner->email)->assertSee('Plan contratado')->assertSee('Fecha de contratación')->assertSee('Fin de vigencia');
        $this->post(route('superadmin.access', $owner->club), ['action' => 'grant', 'reason' => 'Reactivación autorizada'])->assertSessionHasNoErrors();
        $this->actingAs($owner->fresh())->getJson(route('account.status'))->assertOk()->assertJson(['enabled' => true, 'redirect' => route('dashboard')]);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_only_superadmins_can_view_or_change_platform_data(): void
    {
        $this->get(route('superadmin.index'))->assertRedirect(route('superadmin.login'));
        $owner = User::factory()->create();
        $this->actingAs($owner)->get(route('superadmin.index'))->assertForbidden();
        $this->post(route('superadmin.access', $owner->club), ['action' => 'grant', 'reason' => 'Intento no permitido'])->assertForbidden();
        $this->assertNull($owner->club->fresh()->access_override);
        $admin = User::factory()->create(['is_superadmin' => true]);
        $this->actingAs($admin)->get(route('superadmin.index'))->assertOk()->assertSee('Superadministración')->assertSee($owner->email);
        $this->patch(route('profile.update'), ['name' => 'Admin', 'email' => $admin->email, 'is_superadmin' => false])->assertSessionHasNoErrors();
        $this->assertTrue($admin->fresh()->is_superadmin);
    }

    public function test_superadmin_can_suspend_grant_and_reset_club_access_with_audit(): void
    {
        $owner = User::factory()->create(['onboarding_completed' => true, 'subscription_checkout_required' => true]);
        $staff = User::factory()->create(['role' => 'staff', 'club_id' => $owner->club_id]);
        $admin = User::factory()->create(['is_superadmin' => true]);
        $url = route('superadmin.access', $owner->club);
        $this->actingAs($admin)->post($url, ['action' => 'grant', 'reason' => 'Cortesía autorizada'])->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($owner->fresh())->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->post($url, ['action' => 'suspend', 'reason' => 'Cuenta suspendida por revisión'])->assertSessionHasNoErrors();
        $this->actingAs($owner->fresh())->get(route('dashboard'))->assertForbidden()->assertSee('Tu acceso está suspendido')->assertSee('Contactar a soporte')->assertSee('Cerrar sesión')->assertDontSee('Navegación del club');
        $this->getJson(route('dashboard'))->assertForbidden()->assertJsonStructure(['message']);
        $this->actingAs($staff)->get(route('pos.index'))->assertForbidden();
        $this->actingAs($admin)->post($url, ['action' => 'reset', 'reason' => 'Volver a condiciones del plan'])->assertSessionHasNoErrors();
        $this->actingAs($owner->fresh())->get(route('dashboard'))->assertRedirect(route('subscription.checkout'));
        $this->assertSame(3, DB::table('admin_audits')->count());
        $this->assertSame($admin->id, DB::table('admin_audits')->value('actor_id'));
    }

    public function test_approval_requires_bank_confirmation_and_matching_amount_and_activates_once(): void
    {
        config(['billing.mode' => 'payment_link', 'billing.payment_link' => 'https://www.webpay.cl/form-pay/437548']);
        $owner = User::factory()->create(['subscription_checkout_required' => true]);
        $this->actingAs($owner);
        $plan = Plan::firstOrCreate(['slug' => 'pro'], ['name' => 'Pro', 'price' => 65000, 'interval' => 'monthly', 'court_limit' => 6]);
        $payment = app(SubscriptionCheckoutService::class)->start($owner, ['key' => $plan->slug, 'billing' => 'monthly'], ['quoted_amount' => 65000, 'request_key' => (string) Str::uuid()]);
        $admin = User::factory()->create(['is_superadmin' => true]);
        $this->actingAs($admin);
        $url = route('superadmin.review', $payment);
        $this->get(route('superadmin.index', ['section' => 'transactions']))->assertOk()
            ->assertSee($payment->buy_order)->assertSee('Revisar transacción')->assertSee('Estado de la cuenta');
        $data = ['action' => 'approve', 'amount' => 65000, 'authorization' => 'AUTH65000', 'reason' => 'Venta cotejada en Transbank'];
        $this->post($url, $data)->assertSessionHasErrors('confirmed_bank');
        $this->post($url, $data + ['confirmed_bank' => 1])->assertSessionHasNoErrors();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->get(route('superadmin.index', ['section' => 'transactions']))->assertOk()
            ->assertSee($payment->buy_order)->assertSee('Aprobado')->assertSee('Estado de la cuenta');
        $this->assertFalse($owner->fresh()->subscription_checkout_required);
        $this->assertSame(1, DB::table('admin_audits')->count());
        $this->post($url, $data + ['confirmed_bank' => 1])->assertSessionHasErrors('payment');
        $this->assertSame(1, DB::table('admin_audits')->count());
    }

    public function test_superadmin_can_login_directly_and_regular_users_cannot_promote_themselves(): void
    {
        $admin = User::factory()->create(['is_superadmin' => true]);
        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])->assertRedirect(route('superadmin.index'));
        $this->get(route('superadmin.index'))->assertOk();
        $this->assertFalse((new User(['is_superadmin' => true]))->is_superadmin ?? false);
    }
}
