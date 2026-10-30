<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Services\AnalyticsService;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function index(DashboardRequest $request, AnalyticsService $service): View
    {
        $filters = $request->validated();
        $period = $filters['period'] ?? 'month';
        $today = CarbonImmutable::today();
        [$from, $to] = match ($period) {
            'today' => [$today, $today],
            'week' => [$today->startOfWeek(), $today->endOfWeek()->startOfDay()],
            'custom' => [CarbonImmutable::parse($filters['from']), CarbonImmutable::parse($filters['to'])],
            default => [$today->startOfMonth(), $today->endOfMonth()->startOfDay()],
        };

        return view('dashboard', $service->summary($from, $to) + compact('period', 'from', 'to'));
    }
}
