<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Court;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnalyticsService
{
    public function summary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $until = $to->addDay()->startOfDay();
        $bookings = Booking::where('start_time', '>=', $from)->where('start_time', '<', $until)->where('status', '!=', 'canceled');
        $sales = Sale::where('created_at', '>=', $from)->where('created_at', '<', $until)->whereIn('status', ['paid', 'open']);

        $rental = (clone $bookings)->selectRaw('COUNT(*) AS count, COALESCE(SUM(total_price), 0) AS amount, COALESCE(SUM(deposit_paid), 0) AS deposits')->first();
        $pos = (clone $sales)->selectRaw('COUNT(*) AS count, COALESCE(SUM(total_amount), 0) AS amount, COALESCE(SUM(cost_amount), 0) AS cost')->first();
        $openAmount = (clone $sales)->where('status', 'open')->sum('total_amount');
        $collectedPos = Sale::where('status', 'paid')->where('paid_at', '>=', $from)->where('paid_at', '<', $until)->sum('total_amount');
        $rentalPending = (clone $bookings)->selectRaw('COALESCE(SUM(CASE WHEN total_price > deposit_paid THEN total_price - deposit_paid ELSE 0 END), 0) AS amount')->first()->amount;

        $posDays = $this->byDay($sales, 'created_at', 'total_amount');
        $rentalDays = $this->byDay($bookings, 'start_time', 'total_price');
        $daily = collect();
        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $key = $day->toDateString();
            $daily->push([
                'date' => $day->format('d/m'), 'sales' => (float) ($posDays[$key] ?? 0),
                'rentals' => (float) ($rentalDays[$key] ?? 0),
            ]);
        }

        $topProducts = SaleItem::whereIn('sale_id', (clone $sales)->select('id'))
            ->selectRaw('product_id, MAX(product_name) AS name, SUM(quantity) AS units, SUM(subtotal) AS amount')
            ->groupBy('product_id')->orderByDesc('units')->limit(5)->get();
        $courtTotals = (clone $bookings)->selectRaw('court_id, COUNT(*) AS count, SUM(total_price) AS amount')
            ->with('court')->groupBy('court_id')->orderByDesc('amount')->get();

        return [
            'metrics' => [
                'rentals' => (float) $rental->amount, 'rentalCount' => (int) $rental->count,
                'deposits' => (float) $rental->deposits, 'rentalPending' => (float) $rentalPending,
                'sales' => (float) $pos->amount, 'saleCount' => (int) $pos->count,
                'cost' => (float) $pos->cost, 'posMargin' => (float) $pos->amount - (float) $pos->cost,
                'total' => (float) $rental->amount + (float) $pos->amount,
                'openSales' => (float) $openAmount, 'collectedPos' => (float) $collectedPos,
                'activeCourts' => Court::where('is_active', true)->count(),
                'activeProducts' => Product::where('is_active', true)->count(),
            ],
            'daily' => $daily,
            'chartMax' => max(1, $daily->max(fn ($day) => max($day['sales'], $day['rentals']))),
            'topProducts' => $topProducts,
            'courtTotals' => $courtTotals,
            'recentSales' => (clone $sales)->with('items')->latest()->limit(5)->get(),
            'recentBookings' => (clone $bookings)->with('court')->orderByDesc('start_time')->limit(5)->get(),
            'lowStock' => Product::where('is_active', true)->whereColumn('stock', '<=', 'alert_min_stock')->orderBy('stock')->limit(5)->get(),
        ];
    }

    private function byDay(Builder $query, string $dateColumn, string $amountColumn): Collection
    {
        return (clone $query)->selectRaw("DATE({$dateColumn}) AS day, SUM({$amountColumn}) AS amount")
            ->groupByRaw("DATE({$dateColumn})")->orderBy('day')->pluck('amount', 'day');
    }
}
