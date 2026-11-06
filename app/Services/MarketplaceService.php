<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Court;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MarketplaceService
{
    public function publishedClubs(): Builder
    {
        return Club::where(fn ($query) => $query->whereNull('access_override')->orWhere('access_override', '!=', 'deny'))->where('marketplace_enabled', true)->whereNotNull('onboarding_completed_at')
            ->whereNotNull('city')->whereNotNull('address')->whereNotNull('phone')
            ->whereNotNull('opens_at')->whereNotNull('closes_at')->whereIn('slot_minutes', [60, 90]);
    }

    public function courts(Club $club, ?string $sport = null): Collection
    {
        return Court::withoutGlobalScope('club')->where('club_id', $club->id)->where('is_active', true)
            ->when($sport, fn ($query) => $query->where('sport_type', $sport))->orderBy('name')->get();
    }

    public function bookings(Collection $clubIds, string $date): Collection
    {
        $start = CarbonImmutable::parse($date)->startOfDay();

        return Booking::withoutGlobalScope('club')->whereIn('club_id', $clubIds)->where('status', '!=', 'canceled')
            ->where('start_time', '<', $start->addDay())->where('end_time', '>', $start)
            ->get(['court_id', 'club_id', 'start_time', 'end_time']);
    }

    public function availability(Club $club, Collection $courts, Collection $bookings, string $date): Collection
    {
        $opening = CarbonImmutable::parse($date.' '.$club->opens_at);
        $closing = CarbonImmutable::parse($date.' '.$club->closes_at);

        return $courts->map(function (Court $court) use ($club, $opening, $closing, $bookings) {
            $slots = [];
            for ($start = $opening; $start->addMinutes($club->slot_minutes)->lte($closing); $start = $start->addMinutes($club->slot_minutes)) {
                $end = $start->addMinutes($club->slot_minutes);
                $busy = $bookings->contains(fn ($booking) => $booking->court_id === $court->id && $booking->start_time->lt($end) && $booking->end_time->gt($start));
                $slots[] = [
                    'start' => $start->format('H:i'), 'end' => $end->format('H:i'),
                    'available' => ! $busy && $start->isFuture(),
                    'price' => app(BookingPricingService::class)->quote($court, $club, $start, $end),
                ];
            }

            return ['court' => $court, 'slots' => $slots];
        });
    }

    public function slotPrice(string $rate, int $minutes): string
    {
        [$whole, $fraction] = array_pad(explode('.', $rate), 2, '00');
        $cents = (int) $whole * 100 + (int) str_pad($fraction, 2, '0');
        $price = intdiv($cents * $minutes + 30, 60);

        return intdiv($price, 100).'.'.str_pad((string) ($price % 100), 2, '0', STR_PAD_LEFT);
    }
}
