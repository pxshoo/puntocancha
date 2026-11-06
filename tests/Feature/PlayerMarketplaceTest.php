<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlayerMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private function club(array $attributes = []): array
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $club = $owner->club;
        $club->forceFill(array_replace([
            'name' => 'Pádel del Sur', 'city' => 'Puerto Montt', 'address' => 'Calle Sur 123',
            'phone' => '+56912345678', 'opens_at' => '08:00', 'closes_at' => '23:00', 'slot_minutes' => 90,
            'marketplace_enabled' => true, 'onboarding_completed_at' => now(),
            'booking_terms' => 'Paga en recepción.', 'cancellation_policy' => 'Contacta al club para cancelar.',
        ], $attributes))->save();
        $court = Court::factory()->create(['name' => 'Cancha Sur 1', 'sport_type' => 'padel', 'default_hourly_rate' => 20000]);

        return [$club, $court, $owner];
    }

    private function player(): User
    {
        return User::factory()->create(['role' => 'player', 'club_id' => null, 'phone' => '+56987654321', 'favorite_sport' => 'padel']);
    }

    private function bookingData(Club $club, Court $court, array $overrides = []): array
    {
        return array_replace(['club_slug' => $club->slug, 'court_id' => $court->id,
            'date' => today()->addDay()->toDateString(), 'start_time' => '08:00',
            'request_key' => (string) Str::uuid(), 'accept_terms' => 1, 'quoted_price' => '30000.00'], $overrides);
    }

    public function test_public_schedule_shows_court_location_and_falls_back_to_club_location(): void
    {
        [$club, $court] = $this->club();
        $court->update(['address' => 'Sector Norte 456', 'city' => 'Puerto Varas']);
        $this->post('/logout');
        $this->get(route('player.club', $club->slug))->assertOk()
            ->assertSee('Sector Norte 456, Puerto Varas')->assertSee('Ver en mapa')
            ->assertSee(rawurlencode('Sector Norte 456, Puerto Varas'), false);
        $court->forceFill(['address' => null, 'city' => null])->saveQuietly();
        $this->get(route('player.club', $club->slug))->assertOk()
            ->assertSee('Calle Sur 123, Puerto Montt');
        $club->forceFill(['marketplace_enabled' => false])->save();
        $date = today()->addDay()->toDateString();
        $this->get(route('public.bookings', ['club' => $club->slug, 'date' => $date, 'sport' => 'padel']))
            ->assertRedirect(route('player.club', ['slug' => $club->slug, 'date' => $date, 'sport' => 'padel']));
    }

    public function test_owner_sees_publication_action_and_location_suggestions_without_auto_publication(): void
    {
        [$club, $court] = $this->club(['marketplace_enabled' => false, 'city' => null, 'address' => null]);
        $court->update(['city' => 'Puerto Varas', 'address' => 'Camino Lago 456']);
        $this->get(route('player.explore'))->assertOk()->assertSee('Configurar y publicar mi club');
        $this->get(route('club.settings.edit'))->assertOk()->assertSee('Puerto Varas')->assertSee('Camino Lago 456')->assertSee('Localizar en el mapa')->assertSee('Usar este punto');
        $this->assertFalse($club->fresh()->marketplace_enabled);
        $this->assertNull($club->fresh()->address);
        $this->post('/logout');
        $this->get(route('player.explore'))->assertOk()->assertDontSee('Configurar y publicar mi club');
    }

    public function test_player_registration_never_creates_a_club_or_grants_admin_role(): void
    {
        $before = Club::count();
        $this->get('/jugadores/registro')->assertOk();
        $this->post('/jugadores/registro', [
            'name' => 'Jugadora', 'email' => 'jugadora@example.com', 'phone' => '+56912345678',
            'favorite_sport' => 'tenis', 'password' => 'password', 'password_confirmation' => 'password',
            'role' => 'club_admin', 'club_id' => 1,
        ])->assertRedirect(route('player.explore'));
        $this->assertAuthenticated();
        $this->assertSame('player', auth()->user()->role);
        $this->assertNull(auth()->user()->club_id);
        $this->assertDatabaseCount('clubs', $before);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertSame('tenis', auth()->user()->favorite_sport);
    }

    public function test_player_login_redirects_to_explore_and_cannot_access_backoffice(): void
    {
        $player = $this->player();
        $this->post('/login', ['email' => $player->email, 'password' => 'password'])->assertRedirect('/explorar');
        foreach (['/dashboard', '/pos', '/inventario', '/canchas', '/reservas', '/onboarding/paso-1', '/club/configuracion/datos'] as $path) {
            $this->get($path)->assertForbidden();
        }
        $this->post('/pos', [])->assertForbidden();
        $this->post('/canchas', [])->assertForbidden();
        $this->post('/inventario', [])->assertForbidden();
        $this->post('/onboarding/paso-1', [])->assertForbidden();
        $this->get('/profile')->assertOk()->assertSee('Portal de jugadores');
        $this->get('/login')->assertRedirect(route('player.explore'));
    }

    public function test_search_filters_city_sport_date_and_only_published_clubs(): void
    {
        [$first] = $this->club();
        $this->club(['name' => 'Club oculto', 'marketplace_enabled' => false]);
        $this->club(['name' => 'Club de Santiago', 'city' => 'Santiago']);
        $this->post('/logout');
        $date = today()->addDay()->toDateString();
        $this->get('/explorar?city=Puerto%20Montt&sport=padel&date='.$date)
            ->assertOk()->assertSee('Pádel del Sur')->assertDontSee('Club oculto')->assertDontSee('Club de Santiago')
            ->assertSee('turnos disponibles');
        $this->get('/explorar?city=Puerto%20Montt&sport=tenis')->assertOk()->assertDontSee('Pádel del Sur');
        $this->get('/explorar?date=invalid')->assertSessionHasErrors('date');
        $this->get('/club/'.$first->slug.'?date='.$date)->assertOk()->assertSee('Cancha Sur 1')->assertSee('30.000');
    }

    public function test_player_booking_uses_server_price_and_is_visible_only_to_owner_and_club(): void
    {
        [$club, $court, $admin] = $this->club();
        $player = $this->player();
        $this->actingAs($player);
        $data = $this->bookingData($club, $court, ['total_price' => 1, 'deposit_paid' => 999999, 'club_id' => 999, 'player_id' => 999]);
        $this->post('/reservas/jugador', $data)->assertRedirect('/mis-reservas')->assertSessionHasNoErrors();
        $booking = Booking::withoutGlobalScopes()->firstOrFail();
        $this->assertEquals($player->id, $booking->player_id);
        $this->assertEquals($club->id, $booking->club_id);
        $this->assertSame('30000.00', $booking->total_price);
        $this->assertSame('0.00', $booking->deposit_paid);
        $this->assertSame('confirmed', $booking->status);
        $this->assertSame('Paga en recepción.', $booking->payment_terms);
        $this->get('/mis-reservas')->assertOk()->assertSee('Cancha Sur 1')->assertSee('Pago pendiente')->assertSee('https://wa.me/56912345678', false);
        $this->post('/reservas/jugador', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 1);

        $other = $this->player();
        $this->actingAs($other)->get('/mis-reservas')->assertOk()->assertDontSee('Cancha Sur 1');
        $this->actingAs($admin)->get('/reservas?date='.$data['date'])->assertOk()->assertSee($player->name);
        $this->assertSame(1, Booking::count());
        [, , $otherAdmin] = $this->club(['name' => 'Otro club']);
        $this->actingAs($otherAdmin);
        $this->assertSame(0, Booking::count());
    }

    public function test_overlap_is_rejected_but_adjacent_slot_and_canceled_slot_are_available(): void
    {
        [$club, $court] = $this->club();
        $first = $this->player();
        $this->actingAs($first)->post('/reservas/jugador', $this->bookingData($club, $court))->assertSessionHasNoErrors();
        $this->actingAs($this->player());
        $this->post('/reservas/jugador', $this->bookingData($club, $court))->assertSessionHasErrors('start_time');
        $this->post('/reservas/jugador', $this->bookingData($club, $court, ['start_time' => '09:30']))->assertSessionHasNoErrors();
        Booking::withoutGlobalScope('club')->where('court_id', $court->id)->where('start_time', today()->addDay()->setTime(8, 0))->update(['status' => 'canceled']);
        $this->post('/reservas/jugador', $this->bookingData($club, $court))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('bookings', 3);
    }

    public function test_invalid_slots_terms_and_cross_club_court_are_rejected(): void
    {
        [$club, $court] = $this->club();
        [, $foreign] = $this->club(['name' => 'Otro club']);
        $this->actingAs($this->player());
        foreach (['07:00', '08:15', '23:00'] as $time) {
            $this->post('/reservas/jugador', $this->bookingData($club, $court, ['start_time' => $time]))->assertSessionHasErrors('start_time');
        }
        $this->post('/reservas/jugador', $this->bookingData($club, $court, ['accept_terms' => 0]))->assertSessionHasErrors('accept_terms');
        $this->post('/reservas/jugador', $this->bookingData($club, $court, ['court_id' => $foreign->id]))->assertNotFound();
        $this->post('/reservas/jugador', $this->bookingData($club, $court, ['date' => today()->subDay()->toDateString()]))->assertSessionHasErrors('date');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_unpublished_clubs_inactive_courts_and_past_slots_cannot_be_booked(): void
    {
        [$club, $court, $admin] = $this->club();
        $court->update(['is_active' => false]);
        $player = $this->player();
        $this->actingAs($player)->post('/reservas/jugador', $this->bookingData($club, $court))->assertNotFound();
        $this->actingAs($admin);
        $court->update(['is_active' => true]);
        $club->forceFill(['marketplace_enabled' => false])->save();
        $this->actingAs($player)->post('/reservas/jugador', $this->bookingData($club, $court))->assertNotFound();
        $this->get('/club/'.$club->slug)->assertNotFound();
        $club->forceFill(['marketplace_enabled' => true])->save();
        $this->travelTo(now()->setTime(12, 0));
        $this->post('/reservas/jugador', $this->bookingData($club, $court, ['date' => today()->toDateString()]))->assertSessionHasErrors('start_time');
    }

    public function test_public_schedule_never_exposes_player_data_and_history_is_scoped(): void
    {
        [$club, $court] = $this->club();
        $player = $this->player();
        $this->actingAs($player)->post('/reservas/jugador', $this->bookingData($club, $court))->assertSessionHasNoErrors();
        $this->post('/logout');
        $this->get('/club/'.$club->slug.'?date='.today()->addDay()->toDateString())->assertOk()->assertSee('No disponible')
            ->assertDontSee($player->email)->assertDontSee($player->phone)->assertDontSee($player->name);
        $this->actingAs($player);
        $this->travel(3)->days();
        $this->get('/mis-reservas?tab=past')->assertOk()->assertSee('Cancha Sur 1')->assertSee('Finalizada');
        $this->get('/mis-reservas')->assertOk()->assertDontSee('Cancha Sur 1');
    }

    public function test_guests_and_admins_cannot_confirm_player_reservations(): void
    {
        $this->get('/mis-reservas')->assertRedirect('/login');
        $this->post('/reservas/jugador', [])->assertRedirect('/login');
        $this->actingAs(User::factory()->create())->post('/reservas/jugador', [])->assertForbidden();
        $this->get('/mis-reservas')->assertForbidden();
    }

    public function test_stale_quote_requires_confirmation_of_the_new_server_price(): void
    {
        [$club, $court] = $this->club();
        $data = $this->bookingData($club, $court);
        $court->update(['default_hourly_rate' => 30000]);
        $this->actingAs($this->player());
        $this->post('/reservas/jugador', $data)->assertSessionHasErrors('quoted_price');
        $this->assertDatabaseCount('bookings', 0);
        $this->post('/reservas/jugador', array_replace($data, ['quoted_price' => '45000.00']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', ['total_price' => '45000.00']);
    }

    public function test_owner_can_publish_own_club_settings_and_player_cannot_change_them(): void
    {
        [$club] = $this->club(['marketplace_enabled' => false]);
        $this->get('/club/configuracion/datos')->assertOk();
        $data = ['name' => 'Mi Club Público', 'city' => 'Osorno', 'address' => 'Calle 100',
            'phone' => '+56912345678', 'image_url' => 'https://example.com/club.jpg',
            'opens_at' => '08:00', 'closes_at' => '23:00', 'slot_minutes' => 60, 'marketplace_enabled' => 1,
            'booking_terms' => 'Pago en caja.', 'cancellation_policy' => 'Consulta en recepción.'];
        $this->put('/club/configuracion/datos', $data)->assertSessionHasNoErrors();
        $this->assertSame('Osorno', $club->fresh()->city);
        $this->assertTrue($club->fresh()->marketplace_enabled);
        $this->put('/club/configuracion/datos', array_replace($data, ['image_url' => 'javascript:alert(1)']))->assertSessionHasErrors('image_url');
        $this->actingAs($this->player())->put('/club/configuracion/datos', $data)->assertForbidden();
    }
}
