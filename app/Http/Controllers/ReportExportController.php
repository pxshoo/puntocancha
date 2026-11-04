<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportReportRequest;
use App\Services\ClubReportService;
use Carbon\CarbonImmutable;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function __invoke(ExportReportRequest $request, ClubReportService $reports): BinaryFileResponse
    {
        $filters = $request->validated();
        $today = CarbonImmutable::today();
        [$from, $to] = match ($filters['period'] ?? 'month') {
            'today' => [$today, $today],
            'week' => [$today->startOfWeek(), $today->endOfWeek()->startOfDay()],
            'custom' => [CarbonImmutable::parse($filters['from']), CarbonImmutable::parse($filters['to'])],
            default => [$today->startOfMonth(), $today->endOfMonth()->startOfDay()],
        };
        $path = $reports->export($from, $to);

        return response()->download($path, 'PuntoCancha-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ])->deleteFileAfterSend(true);
    }
}
