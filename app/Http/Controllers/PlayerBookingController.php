<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlayerBookingRequest;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;

class PlayerBookingController extends Controller
{
    public function store(StorePlayerBookingRequest $request, BookingService $service): RedirectResponse
    {
        $booking = $service->createBooking($request->validated());

        return redirect()->route('player.bookings')->with('success', "Reserva #{$booking->id} confirmada. El pago se coordina con el club.");
    }
}
