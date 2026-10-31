<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlayerSearchRequest;
use App\Models\Booking;
use App\Services\MarketplaceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlayerExplorarController extends Controller
{
    public function index(PlayerSearchRequest $request, MarketplaceService $service): View
    {
        $filters = $request->validated();
        $date = $filters['date'] ?? today()->toDateString();
        $sport = $filters['sport'] ?? null;
        $clubs = $service->publishedClubs()
            ->when($filters['city'] ?? null, fn ($query, $city) => $query->whereRaw('LOWER(city) = ?', [mb_strtolower($city)]))
            ->whereHas('courts', fn ($query) => $query->withoutGlobalScope('club')->where('is_active', true)->when($sport, fn ($query) => $query->where('sport_type', $sport)))
            ->with(['courts' => fn ($query) => $query->withoutGlobalScope('club')->where('is_active', true)->when($sport, fn ($query) => $query->where('sport_type', $sport))])
            ->orderBy('name')->paginate(12)->withQueryString();
        $bookings = $service->bookings($clubs->getCollection()->pluck('id'), $date);
        $results = $clubs->getCollection()->map(function ($club) use ($service, $date, $bookings) {
            $matrix = $service->availability($club, $club->courts, $bookings, $date);

            return ['club' => $club, 'available' => $matrix->sum(fn ($row) => collect($row['slots'])->where('available', true)->count()),
                'price' => $club->courts->min('default_hourly_rate'), 'sports' => $club->courts->pluck('sport_type')->unique(),
                'covered' => $club->courts->contains(fn ($court) => (bool) $court->is_covered)];
        });

        return view('player.explorar', [
            'clubs' => $clubs, 'results' => $results, 'date' => $date, 'sport' => $sport,
            'city' => $filters['city'] ?? '', 'cities' => $service->publishedClubs()->select('city')->distinct()->orderBy('city')->pluck('city'),
        ]);
    }

    public function showClub(string $slug, PlayerSearchRequest $request, MarketplaceService $service): View
    {
        $club = $service->publishedClubs()->where('slug', $slug)->firstOrFail();
        $filters = $request->validated();
        $date = $filters['date'] ?? today()->toDateString();
        $sport = $filters['sport'] ?? null;
        $allCourts = $service->courts($club);
        $courts = $sport ? $allCourts->where('sport_type', $sport) : $allCourts;
        $matrix = $service->availability($club, $courts, $service->bookings(collect([$club->id]), $date), $date);
        $days = collect(range(0, 6))->map(fn ($i) => CarbonImmutable::today()->addDays($i));

        return view('player.club-schedule', [
            'club' => $club, 'matrix' => $matrix, 'sports' => $allCourts->pluck('sport_type')->unique(),
            'date' => $date, 'sport' => $sport, 'days' => $days, 'requestKey' => (string) Str::uuid(),
        ]);
    }

    public function myBookings(Request $request): View
    {
        $data = $request->validate(['tab' => ['sometimes', 'in:upcoming,past']]);
        $tab = $data['tab'] ?? 'upcoming';
        $query = Booking::withoutGlobalScope('club')->where('player_id', $request->user()->id);
        if ($tab === 'past') {
            $query->where(fn ($q) => $q->where('end_time', '<=', now())->orWhere('status', 'canceled'))->orderByDesc('start_time');
        } else {
            $query->where('end_time', '>', now())->where('status', '!=', 'canceled')->orderBy('start_time');
        }
        $bookings = $query->with(['club', 'payment', 'court' => fn ($q) => $q->withoutGlobalScope('club')])->paginate(10)->withQueryString();

        return view('player.my-bookings', compact('bookings', 'tab'));
    }
}
