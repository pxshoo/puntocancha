<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourtManagementTest extends TestCase
{
    use RefreshDatabase;

    private function data(array $overrides = []): array
    {
        return array_replace(['name' => 'Pádel Central', 'sport_type' => 'padel', 'surface_type' => 'Pasto sintético', 'is_covered' => 1, 'default_hourly_rate' => 25000, 'is_active' => 1], $overrides);
    }

    public function test_admin_can_create_list_and_update_courts_without_changing_booking_price(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get('/canchas')->assertOk()->assertSee('Crear mi primera cancha');
        $this->get('/canchas/nueva')->assertOk();
        $this->post('/canchas', $this->data(['club_id' => 999, 'minimum_deposit' => 10000]))->assertRedirect(route('courts.index'));
        $court = Court::firstOrFail();
        $this->assertEquals($user->club_id, $court->club_id);
        $this->assertSame(10000, $court->minimum_deposit);
        $this->get('/canchas')->assertOk()->assertSee('Pádel Central')->assertSee('25.000');
        $booking = Booking::factory()->create(['court_id' => $court->id, 'total_price' => 25000]);
        $this->get(route('courts.edit', $court))->assertOk()->assertSee('name="minimum_deposit"', false)->assertSee('value="10000"', false);
        $this->put(route('courts.update', $court), $this->data(['default_hourly_rate' => 30000, 'is_active' => 0]))->assertSessionHasNoErrors();
        $this->assertEquals('30000.00', $court->fresh()->default_hourly_rate);
        $this->assertFalse($court->fresh()->is_active);
        $this->assertEquals('25000.00', $booking->fresh()->total_price);
        $this->post('/reservas', ['court_id' => $court->id])->assertSessionHasErrors('court_id');
        $this->get('/reservas')->assertOk()->assertSee('Administrar canchas y precios');
    }

    public function test_club_cannot_edit_another_clubs_court_and_staff_cannot_create(): void
    {
        $this->actingAs(User::factory()->create());
        $court = Court::factory()->create(['name' => 'Cancha privada']);
        $other = User::factory()->create();
        $this->actingAs($other);
        $this->get('/canchas')->assertOk()->assertDontSee('Cancha privada');
        $this->get(route('courts.edit', $court))->assertNotFound();
        $this->put(route('courts.update', $court), $this->data())->assertNotFound();
        $other->forceFill(['role' => 'staff'])->save();
        $this->get('/canchas/nueva')->assertForbidden();
        $this->post('/canchas', $this->data())->assertForbidden();
    }

    public function test_admin_can_save_edit_clear_and_validate_court_location(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->post(route('courts.store'), $this->data(['address' => 'Av. Lagos 123', 'city' => 'Puerto Montt']))->assertSessionHasNoErrors();
        $court = Court::firstOrFail();
        $this->assertSame('Av. Lagos 123', $court->address);
        $this->get(route('courts.index'))->assertOk()->assertSee('Av. Lagos 123')->assertSee('Puerto Montt');
        $this->get(route('courts.edit', $court))->assertOk()->assertSee('Av. Lagos 123');
        $this->put(route('courts.update', $court), $this->data(['address' => 'Sector sur', 'city' => 'Puerto Varas']))->assertSessionHasNoErrors();
        $this->assertSame('Sector sur', $court->fresh()->address);
        $this->assertSame('Puerto Varas', $court->fresh()->city);
        $this->put(route('courts.update', $court), $this->data(['address' => str_repeat('a', 256), 'city' => str_repeat('a', 101)]))->assertSessionHasErrors(['address', 'city']);
        $this->put(route('courts.update', $court), $this->data(['address' => '', 'city' => '']))->assertSessionHasNoErrors();
        $this->assertNull($court->fresh()->address);
        $this->assertNull($court->fresh()->city);
    }

    public function test_plan_limit_applies_to_creation_and_reactivation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Subscription::create(['user_id' => $user->id, 'plan_id' => 1, 'status' => 'trialing', 'starts_at' => now(), 'ends_at' => now()->addDays(7)]);
        $courts = Court::factory()->count(2)->create();
        $this->post('/canchas', $this->data())->assertSessionHasErrors('is_active');
        $this->post('/canchas', $this->data(['is_active' => 0]))->assertSessionHasNoErrors();
        $inactive = Court::where('is_active', false)->firstOrFail();
        $this->put(route('courts.update', $inactive), $this->data())->assertSessionHasErrors('is_active');
        $this->put(route('courts.update', $courts->first()), $this->data(['is_active' => 0]))->assertSessionHasNoErrors();
        $this->put(route('courts.update', $inactive), $this->data())->assertSessionHasNoErrors();
        $this->assertSame(2, Court::where('is_active', true)->count());
        Subscription::firstOrFail()->update(['ends_at' => now()->subDay()]);
        $this->post('/canchas', $this->data())->assertSessionHasErrors('is_active');
    }

    public function test_guest_and_invalid_prices_are_rejected(): void
    {
        $this->get('/canchas')->assertRedirect('/login');
        $this->actingAs(User::factory()->create());
        $this->post('/canchas', $this->data(['default_hourly_rate' => -1]))->assertSessionHasErrors('default_hourly_rate');
        $this->post('/canchas', $this->data(['sport_type' => 'unknown']))->assertSessionHasErrors('sport_type');
        $this->assertDatabaseCount('courts', 0);
    }
}
