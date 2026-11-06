<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmClubPaymentRequest;
use App\Models\Booking;
use App\Models\BookingPayment;
use App\Models\Court;
use App\Services\BookingCheckoutService;
use App\Services\BookingPricingService;
use App\Services\BookingService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    protected BookingService $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    public function index(Request $request)
    {
        $selectedDate = $request->query('date', now()->toDateString());
        $courts = Court::where('is_active', true)->get();

        $bookings = Booking::whereDate('start_time', $selectedDate)
            ->where('status', '!=', 'canceled')
            ->with(['court', 'payment'])
            ->get();

        return view('bookings.index', compact('courts', 'bookings', 'selectedDate'));
    }

    public function reviewPayment(ConfirmClubPaymentRequest $request, BookingPayment $payment, BookingCheckoutService $checkout): RedirectResponse
    {
        $checkout->reviewLink($payment, $request->validated());

        return back()->with('success', 'Revisión del pago registrada.');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'court_id' => ['required', Rule::exists('courts', 'id')->where('club_id', $request->user()->club_id)->where('is_active', true)],
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'required|string|max:50',
            'booking_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'duration_hours' => 'required|integer|min:1|max:3',
            'payment_method' => 'required|in:cash,transfer,webpay,pos',
            'deposit_paid' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $court = Court::findOrFail($validated['court_id']);

        $startDateTime = Carbon::parse("{$validated['booking_date']} {$validated['start_time']}");
        $endDateTime = (clone $startDateTime)->addHours((int) $validated['duration_hours']);
        $totalPrice = app(BookingPricingService::class)->quote($court, $request->user()->club, $startDateTime, $endDateTime);

        try {
            $this->bookingService->createBooking([
                'court_id' => $court->id,
                'user_id' => auth()->id(),
                'customer_name' => $validated['customer_name'],
                'customer_phone' => $validated['customer_phone'],
                'customer_email' => $validated['customer_email'] ?? null,
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'total_price' => $totalPrice,
                'deposit_paid' => $validated['deposit_paid'] ?? 0,
                'status' => 'confirmed',
                'payment_method' => $validated['payment_method'],
                'notes' => $validated['notes'] ?? null,
            ]);

            return redirect()->route('bookings.index', ['date' => $validated['booking_date']])
                ->with('success', 'Turno reservado con éxito en '.$court->name);
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
