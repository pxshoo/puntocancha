<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use Carbon\CarbonImmutable;
use Generator;

class ClubReportService
{
    public function __construct(private AnalyticsService $analytics, private ExcelWorkbook $excel) {}

    public function export(CarbonImmutable $from, CarbonImmutable $to): string
    {
        $metrics = $this->analytics->summary($from, $to)['metrics'];
        $expenses = (float) Expense::whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->sum('amount');
        $summary = [
            ['PuntoCancha', auth()->user()->club->name],
            ['Desde', $from->toDateString()], ['Hasta', $to->toDateString()],
            ['Moneda', 'CLP'],
            ['Ingresos arriendos registrados', $metrics['rentals']],
            ['Ventas POS registradas', $metrics['sales']],
            ['Ingresos totales registrados', $metrics['total']],
            ['Costo histórico mercadería vendida', $metrics['cost']],
            ['Margen bruto POS', $metrics['posMargin']],
            ['Gastos operativos registrados', $expenses],
            ['Ganancia neta estimada', $metrics['total'] - $metrics['cost'] - $expenses],
            ['Arriendos por cobrar', $metrics['rentalPending']],
            ['Consumos por cobrar', $metrics['openSales']],
            ['Criterio', 'Ingresos registrados: incluye pendientes. No representa saldo de caja.'],
            ['Costos', 'Costo histórico al vender, no el costo actual del catálogo.'],
            ['Inventario', 'Existencias actuales a la fecha de exportación; no inventario histórico.'],
            ['Gastos', 'Solo gastos registrados. Si faltan egresos, la ganancia queda sobreestimada.'],
            ['Generado', now()->toDateTimeString()],
        ];

        return $this->excel->create([
            'Resumen ganancias' => $summary,
            'Inventario actual' => $this->inventory(),
            'Ventas y costos' => $this->sales($from, $to),
            'Arriendos' => $this->rentals($from, $to),
            'Gastos' => $this->expenses($from, $to),
        ]);
    }

    private function inventory(): Generator
    {
        yield ['Producto', 'Categoría', 'Código', 'Activo', 'Stock', 'Stock mínimo', 'Costo unitario CLP', 'Precio venta CLP', 'Valor stock al costo CLP'];
        foreach (Product::with('category')->orderBy('id')->lazyById(500) as $product) {
            yield [$product->name, $product->category?->name, $product->barcode, $product->is_active ? 'Sí' : 'No', $product->stock, (int) $product->alert_min_stock, (float) $product->cost_price, (float) $product->sale_price, round($product->stock * (float) $product->cost_price, 2)];
        }
    }

    private function sales(CarbonImmutable $from, CarbonImmutable $to): Generator
    {
        yield ['Venta', 'Fecha', 'Estado', 'Pago', 'Reserva', 'Total CLP', 'Costo histórico CLP', 'Margen CLP'];
        foreach (Sale::where('created_at', '>=', $from)->where('created_at', '<', $to->addDay())->whereIn('status', ['paid', 'open'])->orderBy('id')->lazyById(500) as $sale) {
            yield [$sale->id, $sale->created_at->toDateTimeString(), $sale->status === 'paid' ? 'Pagada' : 'Por cobrar', $sale->payment_method, $sale->booking_id, (float) $sale->total_amount, (float) $sale->cost_amount, round((float) $sale->total_amount - (float) $sale->cost_amount, 2)];
        }
    }

    private function rentals(CarbonImmutable $from, CarbonImmutable $to): Generator
    {
        yield ['Reserva', 'Cancha', 'Inicio', 'Fin', 'Estado', 'Total CLP', 'Abonos CLP', 'Pendiente CLP'];
        foreach (Booking::with('court')->where('start_time', '>=', $from)->where('start_time', '<', $to->addDay())->whereNotIn('status', ['canceled', 'pending_payment'])->orderBy('id')->lazyById(500) as $booking) {
            yield [$booking->id, $booking->court?->name, (string) $booking->start_time, (string) $booking->end_time, $booking->status, (float) $booking->total_price, (float) $booking->deposit_paid, max(0, (float) $booking->total_price - (float) $booking->deposit_paid)];
        }
    }

    private function expenses(CarbonImmutable $from, CarbonImmutable $to): Generator
    {
        yield ['Fecha', 'Categoría', 'Concepto', 'Documento', 'Monto CLP'];
        foreach (Expense::with('category')->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->orderBy('id')->lazyById(500) as $expense) {
            yield [$expense->expense_date->toDateString(), $expense->category?->name, $expense->concept, $expense->receipt_number, (float) $expense->amount];
        }
    }
}
