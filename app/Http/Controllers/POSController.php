<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettleSaleRequest;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Booking;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class POSController extends Controller
{
    public function index(): View
    {
        return view('pos.index', [
            'products' => Product::with('category')->where('is_active', true)->orderBy('name')->get(),
            'bookings' => Booking::with('court')->whereDate('start_time', today())
                ->where('status', '!=', 'canceled')->orderBy('start_time')->get(),
            'openSales' => Sale::with(['items', 'booking.court'])->where('status', 'open')->latest()->get(),
            'sales' => Sale::with(['items', 'booking.court'])->latest()->paginate(15),
            'requestKey' => (string) Str::uuid(),
        ]);
    }

    public function store(StoreSaleRequest $request, SaleService $service): RedirectResponse
    {
        $sale = $service->create($request->validated());

        return redirect()->route('pos.index')->with('success', "Venta #{$sale->id} registrada.");
    }

    public function settle(SettleSaleRequest $request, Sale $sale, SaleService $service): RedirectResponse
    {
        $service->settle($sale, $request->validated('payment_method'));

        return redirect()->route('pos.index')->with('success', "Venta #{$sale->id} cobrada.");
    }
}
