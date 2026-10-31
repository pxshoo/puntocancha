<?php

namespace Tests\Feature;

use App\Models\Club;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClubWeatherTest extends TestCase
{
    use RefreshDatabase;

    private function club(): Club
    {
        $owner = User::factory()->create();
        $owner->club->forceFill(['city' => 'Puerto Montt', 'address' => 'Calle 123', 'phone' => '+56912345678',
            'marketplace_enabled' => true, 'onboarding_completed_at' => now(), 'opens_at' => '08:00', 'closes_at' => '23:00', 'slot_minutes' => 60])->save();
        Cache::flush();
        Http::preventStrayRequests();

        return $owner->club;
    }

    private function fakeWeather(string $date): void
    {
        Http::fake([
            'geocoding-api.open-meteo.com/*' => Http::response(['results' => [
                ['name' => 'Puerto Montt', 'country_code' => 'CL', 'latitude' => -41.47, 'longitude' => -72.94],
            ]]),
            'api.open-meteo.com/*' => Http::response([
                'hourly' => ['time' => [$date.'T18:00', $date.'T19:00'], 'temperature_2m' => [14, 13],
                    'relative_humidity_2m' => [72, null], 'precipitation_probability' => [10, 60],
                    'weather_code' => [0, 61], 'wind_speed_10m' => [12, 14]],
                'daily' => ['time' => [$date], 'weather_code' => [61], 'temperature_2m_max' => [15], 'temperature_2m_min' => [8]],
            ]),
        ]);
    }

    public function test_forecast_returns_chilean_city_hourly_weather_and_caches_provider_requests(): void
    {
        $club = $this->club();
        $date = today()->addDay()->toDateString();
        $this->fakeWeather($date);
        $url = route('player.weather', ['slug' => $club->slug, 'date' => $date]);
        $this->getJson($url)->assertOk()->assertJsonPath('available', true)->assertJsonPath('city', 'Puerto Montt')
            ->assertJsonPath('hours.18:00.temperature', 14)->assertJsonPath('hours.19:00.rain', 60)
            ->assertJsonPath('hours.19:00.label', 'Lluvia')->assertJsonPath('hours.19:00.humidity', null);
        $this->getJson($url)->assertOk();
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'countryCode=CL'));
    }

    public function test_api_failure_never_blocks_reservations_and_missing_weather_is_not_invented(): void
    {
        $club = $this->club();
        Http::fake(['*' => Http::response([], 503)]);
        $date = today()->toDateString();
        $this->getJson(route('player.weather', ['slug' => $club->slug, 'date' => $date]))
            ->assertOk()->assertJsonPath('available', false)->assertJsonMissingPath('hours');
        $this->get(route('player.club', $club->slug))->assertOk()->assertSee('Pronóstico del tiempo')->assertSee('Open-Meteo');
    }

    public function test_dates_beyond_forecast_range_make_no_provider_calls(): void
    {
        $club = $this->club();
        $this->getJson(route('player.weather', ['slug' => $club->slug, 'date' => today()->addDays(20)->toDateString()]))
            ->assertOk()->assertJsonPath('available', false);
        Http::assertNothingSent();
    }

    public function test_unpublished_clubs_invalid_dates_and_unknown_locations_are_not_exposed(): void
    {
        $club = $this->club();
        $this->getJson(route('player.weather', ['slug' => $club->slug, 'date' => 'invalid']))->assertUnprocessable();
        $club->forceFill(['marketplace_enabled' => false])->save();
        $this->getJson(route('player.weather', ['slug' => $club->slug, 'date' => today()->toDateString()]))->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_mismatched_city_does_not_use_first_geocoding_result(): void
    {
        $club = $this->club();
        Http::fake(['*' => Http::response(['results' => [['name' => 'Puerto Varas', 'country_code' => 'CL', 'latitude' => -41, 'longitude' => -72]]])]);
        $this->getJson(route('player.weather', ['slug' => $club->slug, 'date' => today()->toDateString()]))
            ->assertOk()->assertJsonPath('available', false);
        Http::assertSentCount(1);
    }
}
