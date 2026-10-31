<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class WeatherService
{
    public function forecast(string $city, string $date): array
    {
        $today = CarbonImmutable::today('America/Santiago');
        if (! config('weather.enabled') || (! app()->environment(['local', 'testing']) && ! config('weather.api_key'))) {
            return $this->unavailable('El pronóstico aún no está habilitado.');
        }
        if ($date < $today->toDateString() || $date > $today->addDays(15)->toDateString()) {
            return $this->unavailable('El pronóstico estará disponible cuando falten 15 días o menos para el partido.');
        }
        try {
            $cityKey = Str::lower(Str::ascii(trim($city)));
            $coordinates = Cache::remember('weather:city:v1:'.hash('sha256', $cityKey), now()->addDays(30), function () use ($city, $cityKey) {
                $data = Http::connectTimeout(2)->timeout(5)->get('https://geocoding-api.open-meteo.com/v1/search', [
                    'name' => $city, 'count' => 10, 'language' => 'es', 'countryCode' => 'CL',
                ])->throw()->json();
                foreach ($data['results'] ?? [] as $result) {
                    if (($result['country_code'] ?? null) === 'CL' && Str::lower(Str::ascii($result['name'] ?? '')) === $cityKey
                        && is_numeric($result['latitude'] ?? null) && is_numeric($result['longitude'] ?? null)) {
                        return ['latitude' => $result['latitude'], 'longitude' => $result['longitude']];
                    }
                }
                throw new RuntimeException('Ciudad sin coordenadas verificadas.');
            });
            $raw = Cache::remember('weather:forecast:v1:'.hash('sha256', $cityKey).':'.$today->toDateString(), now()->addMinutes(15), function () use ($coordinates) {
                $params = array_merge($coordinates, [
                    'timezone' => 'America/Santiago', 'forecast_days' => 16,
                    'hourly' => 'temperature_2m,relative_humidity_2m,precipitation_probability,weather_code,wind_speed_10m',
                    'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max',
                ]);
                $host = 'https://api.open-meteo.com/v1/forecast';
                if (config('weather.api_key')) {
                    $host = 'https://customer-api.open-meteo.com/v1/forecast';
                    $params['apikey'] = config('weather.api_key');
                }
                $data = Http::connectTimeout(2)->timeout(6)->get($host, $params)->throw()->json();
                if (! is_array($data['hourly']['time'] ?? null) || empty($data['hourly']['time'])) {
                    throw new RuntimeException('Pronóstico incompleto.');
                }
                $data['fetched_at'] = now('America/Santiago')->format('H:i');

                return $data;
            });
            $hours = [];
            foreach ($raw['hourly']['time'] as $index => $time) {
                if (! is_string($time) || substr($time, 0, 10) !== $date) {
                    continue;
                }
                $code = $raw['hourly']['weather_code'][$index] ?? null;
                $hours[substr($time, 11, 5)] = array_merge($this->condition($code), [
                    'time' => substr($time, 11, 5),
                    'temperature' => $this->number($raw['hourly']['temperature_2m'][$index] ?? null),
                    'rain' => $this->number($raw['hourly']['precipitation_probability'][$index] ?? null),
                    'humidity' => $this->number($raw['hourly']['relative_humidity_2m'][$index] ?? null),
                    'wind' => $this->number($raw['hourly']['wind_speed_10m'][$index] ?? null),
                ]);
            }
            if (! $hours) {
                return $this->unavailable('Todavía no hay pronóstico para este día.');
            }
            $days = [];
            foreach ($raw['daily']['time'] ?? [] as $index => $day) {
                $days[$day] = array_merge($this->condition($raw['daily']['weather_code'][$index] ?? null), [
                    'max' => $this->number($raw['daily']['temperature_2m_max'][$index] ?? null),
                    'min' => $this->number($raw['daily']['temperature_2m_min'][$index] ?? null),
                ]);
            }

            return ['available' => true, 'city' => $city, 'date' => $date, 'hours' => $hours, 'days' => $days,
                'updated_at' => $raw['fetched_at'], 'timezone' => 'America/Santiago'];
        } catch (Throwable) {
            return $this->unavailable('No pudimos consultar el clima en esta ciudad. Puedes reservar igualmente.');
        }
    }

    private function number(mixed $value): ?int
    {
        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    private function condition(mixed $code): array
    {
        return match (true) {
            $code === 0 => ['icon' => '☀️', 'label' => 'Despejado'],
            in_array($code, [1, 2], true) => ['icon' => '🌤️', 'label' => 'Parcialmente nublado'],
            $code === 3 => ['icon' => '☁️', 'label' => 'Nublado'],
            in_array($code, [45, 48], true) => ['icon' => '🌫️', 'label' => 'Niebla'],
            in_array($code, [51, 53, 55, 56, 57], true) => ['icon' => '🌦️', 'label' => 'Llovizna'],
            in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true) => ['icon' => '🌧️', 'label' => 'Lluvia'],
            in_array($code, [71, 73, 75, 77, 85, 86], true) => ['icon' => '🌨️', 'label' => 'Nieve'],
            in_array($code, [95, 96, 99], true) => ['icon' => '⛈️', 'label' => 'Tormenta'],
            default => ['icon' => '🌡️', 'label' => 'Sin detalle'],
        };
    }

    private function unavailable(string $message): array
    {
        return ['available' => false, 'message' => $message];
    }
}
