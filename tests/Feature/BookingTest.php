<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    public function test_agenda_loads_without_courts(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/reservas')->assertOk()->assertSee('No hay canchas activas');
    }

    public function test_booking_is_saved_displayed_and_overlap_is_rejected(): void
    {
        $user = User::factory()->create();
        $courtId = DB::table('courts')->insertGetId([
            'club_id' => $user->club_id,
            'name' => 'Cancha de prueba', 'default_hourly_rate' => 20000, 'is_active' => true,
        ]);
        $data = [
            'court_id' => $courtId, 'customer_name' => 'Cliente de prueba',
            'customer_phone' => '123456789', 'booking_date' => '2026-11-01',
            'start_time' => '15:00', 'duration_hours' => 2, 'payment_method' => 'cash',
        ];

        $this->actingAs($user)->post('/reservas', $data)
            ->assertRedirect(route('bookings.index', ['date' => '2026-11-01']))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('bookings', [
            'court_id' => $courtId, 'user_id' => $user->id, 'total_price' => 40000,
        ]);
        $this->get('/reservas?date=2026-11-01')->assertOk()
            ->assertSee('Cliente de prueba')->assertSee('Cancha de prueba')->assertSee('15:00');

        $this->post('/reservas', array_replace($data, ['start_time' => '16:00']))
            ->assertSessionHas('error');
        $this->assertDatabaseCount('bookings', 1);

        $this->post('/reservas', array_replace($data, ['start_time' => '17:00']))
            ->assertSessionHas('success');
        $this->assertDatabaseCount('bookings', 2);
    }
}
