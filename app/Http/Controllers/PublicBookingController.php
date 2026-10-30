<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Club;
use App\Models\Court;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicBookingController extends Controller
{
    public function __invoke(Request $request, Club $club): View
    {
        abort_unless($club->onboarding_completed_at && $club->opens_at && $club->closes_at, 404);
        $data = $request->validate(['date' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:today', 'before_or_equal:'.today()->addDays(90)->toDateString()]]);
        $date = $data['date'] ?? today()->toDateString();
        $opening = CarbonImmutable::parse($date.' '.$club->opens_at);
        $closing = CarbonImmutable::parse($date.' '.$club->closes_at);
        $courts = Court::withoutGlobalScopes()->where('club_id', $club->id)->where('is_active', true)->orderBy('name')->get();
        $bookings = Booking::withoutGlobalScopes()->where('club_id', $club->id)->where('status', '!=', 'canceled')
            ->where('start_time', '<', $closing)->where('end_time', '>', $opening)->get(['court_id', 'start_time', 'end_time']);
        $availability = $courts->map(function ($court) use ($opening, $closing, $club, $bookings) {
            $slots = [];
            for ($start = $opening; $start->addMinutes($club->slot_minutes)->lte($closing); $start = $start->addMinutes($club->slot_minutes)) {
                $end = $start->addMinutes($club->slot_minutes);
                $busy = $bookings->contains(fn ($booking) => $booking->court_id === $court->id && $booking->start_time->lt($end) && $booking->end_time->gt($start));
                $slots[] = ['start' => $start->format('H:i'), 'end' => $end->format('H:i'), 'available' => ! $busy && $start->isFuture()];
            }

            return ['court' => $court, 'slots' => $slots];
        });

        return view('onboarding.public-bookings', compact('club', 'date', 'availability'));
    }
}
