<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\Court;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClubLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_save_clear_and_publish_exact_point(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        Court::factory()->create();
        $data = ['name' => 'Club mapa', 'city' => 'Puerto Montt', 'address' => 'Calle Sur 123',
            'phone' => '+56912345678', 'opens_at' => '08:00', 'closes_at' => '23:00',
            'slot_minutes' => 60, 'marketplace_enabled' => 1, 'booking_terms' => 'Pago al reservar',
            'cancellation_policy' => 'Consultar con el club', 'latitude' => -41.4693123, 'longitude' => -72.9424123];
        $this->put(route('club.settings.update'), $data)->assertSessionHasNoErrors()->assertRedirect();
        $club = $owner->club->fresh();
        $this->assertEquals(-41.4693123, $club->latitude);
        $this->assertEquals(-72.9424123, $club->longitude);
        config(['services.google_maps.key' => 'test-browser-key']);
        $this->get(route('club.settings.edit'))->assertOk()->assertSee('Marca tu ubicaci')->assertSee('data-google-maps-key="test-browser-key"', false)->assertDontSee('photon.komoot.io');
        $this->post('/logout');
        $this->get(route('player.club', $club->slug))->assertOk()->assertSee(rawurlencode('-41.4693123,-72.9424123'), false);
        $this->actingAs($owner)->put(route('club.settings.update'), array_replace($data, ['latitude' => '', 'longitude' => '']))->assertSessionHasNoErrors();
        $this->assertNull($club->fresh()->latitude);
        $this->assertStringContainsString(rawurlencode('Calle Sur 123, Puerto Montt'), $club->fresh()->mapUrl());
    }

    public function test_coordinates_require_a_valid_pair(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner);
        $this->put(route('club.settings.update'), ['latitude' => 91, 'longitude' => 181])
            ->assertSessionHasErrors(['latitude', 'longitude']);
        $this->put(route('club.settings.update'), ['latitude' => -41])->assertSessionHasErrors('longitude');
        $this->assertNull($owner->club->fresh()->latitude);
    }

    public function test_other_court_address_does_not_use_club_pin(): void
    {
        $club = new Club;
        $club->forceFill(['address' => 'Club 123', 'city' => 'Puerto Montt', 'latitude' => 0, 'longitude' => 0]);
        $this->assertStringContainsString('query=0%2C0', $club->mapUrl());
        $court = new Court;
        $court->forceFill(['address' => 'Otra 456', 'city' => 'Osorno']);
        $this->assertStringContainsString(rawurlencode('Otra 456, Osorno'), $club->mapUrl($court));
    }
}
