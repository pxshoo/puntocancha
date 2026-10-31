<?php

namespace App\Http\Controllers;

use App\Services\MarketplaceService;
use App\Services\WeatherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClubWeatherController extends Controller
{
    public function __invoke(string $slug, Request $request, MarketplaceService $marketplace, WeatherService $weather): JsonResponse
    {
        $club = $marketplace->publishedClubs()->where('slug', $slug)->firstOrFail();
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(90)->toDateString()]]);

        return response()->json($weather->forecast($club->city, $data['date']))->header('Cache-Control', 'private, max-age=60');
    }
}
