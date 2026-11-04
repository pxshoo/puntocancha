<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><h2 class="font-semibold text-xl text-slate-900 dark:text-slate-100">Resumen del club</h2><p class="text-sm text-slate-600 dark:text-slate-400 mt-1">{{ auth()->user()->club->name }} · {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p></div>
            <div class="flex flex-wrap gap-2">
                @if(auth()->user()->role === 'club_admin')
                    <a href="{{ route('reports.excel', ['period' => 'custom', 'from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="action-feedback inline-flex min-h-14 items-center justify-center gap-2 rounded-xl border border-brand/30 bg-brand/10 px-6 py-3 font-semibold text-brand-content shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-brand-ring"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M12 3v12m-4-4 4 4 4-4M5 16v5h14v-5"/></svg>Exportar Excel</a>
                @endif
                <a class=" bg-brand text-brand-on text-brand-on   inline-flex min-h-14 items-center justify-center rounded-xl px-7 py-4 text-base font-bold shadow-sm shadow-emerald-900/10 focus-visible:ring-2 focus-visible:ring-brand-ring focus-visible:ring-offset-2" href="{{ route('pos.index') }}">Nueva venta</a>
                <a class="action-feedback inline-flex min-h-14 items-center justify-center rounded-xl border border-slate-200 px-6 py-3 font-semibold dark:border-slate-700" href="{{ route('bookings.index') }}">Ver agenda</a>
            </div>
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
        <x-operation-messages />
        <section data-reveal class="surface-card rounded-xl bg-white dark:bg-slate-900 p-4 shadow-sm space-y-4">
            <nav aria-label="Período del resumen" class="flex flex-wrap gap-2">
                @foreach (['today' => 'Hoy', 'week' => 'Esta semana', 'month' => 'Este mes'] as $value => $label)
                    <a href="{{ route('dashboard', ['period' => $value]) }}" @if ($period === $value) aria-current="page" @endif class="   {{ $period === $value ? 'bg-brand text-brand-on text-brand-on' : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300' }} inline-flex min-h-14 items-center justify-center rounded-xl px-7 py-4 text-base font-bold shadow-sm focus-visible:ring-2 focus-visible:ring-brand-ring focus-visible:ring-offset-2">{{ $label }}</a>
                @endforeach
            </nav>
            <details @if($period === 'custom') open @endif class="border-t border-slate-100 pt-3 dark:border-slate-800"><summary class="cursor-pointer rounded text-xs font-semibold text-slate-600 dark:text-slate-400">Elegir otras fechas</summary>
            <form action="{{ route('dashboard') }}" method="GET" class="responsive-filter mt-4 flex flex-wrap gap-3 items-end">
                <input type="hidden" name="period" value="custom">
                <label class="grid gap-1 text-sm">Desde <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" type="date" name="from" value="{{ old('from', $from->toDateString()) }}" required></label>
                <label class="grid gap-1 text-sm">Hasta <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" type="date" name="to" value="{{ old('to', $to->toDateString()) }}" required></label>
                <x-primary-button>Filtrar</x-primary-button>
            </form>
            </details>
        </section>
        @php
            $cards = [
                ['title' => 'Total registrado', 'value' => $metrics['total'], 'detail' => 'Incluye saldos pendientes', 'icon' => 'M12 3v18m5-14H9a3 3 0 0 0 0 6h6a3 3 0 0 1 0 6H6'],
                ['title' => 'Arriendos', 'value' => $metrics['rentals'], 'detail' => $metrics['rentalCount'].' reservas', 'icon' => 'M3 3h18v18H3zM3 12h18M12 3v18'],
                ['title' => 'Ventas POS', 'value' => $metrics['sales'], 'detail' => $metrics['saleCount'].' ventas', 'icon' => 'M3 3h2l3 12h10l3-8H6M9 21h.01M18 21h.01'],
                ['title' => 'Por cobrar', 'value' => $metrics['rentalPending'] + $metrics['openSales'], 'detail' => 'Arriendos y consumos', 'icon' => 'M12 8v4l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0'],
            ];
        @endphp
        <section data-reveal aria-label="Indicadores del periodo" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <article data-reveal class="surface-card rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between gap-2"><h3 class="text-sm font-medium text-slate-600 dark:text-slate-400">{{ $card['title'] }}</h3><span class="rounded-lg bg-brand/10 p-2 text-brand-content"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $card['icon'] }}"/></svg></span></div>
                    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900 dark:text-slate-100">${{ number_format($card['value'], 0, ',', '.') }}</p>
                    <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ $card['detail'] }}</p>
                </article>
            @endforeach
        </section>
        <details class="rounded-xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <summary class="cursor-pointer rounded text-sm font-semibold text-brand-content">Ver detalle de cobros y margen</summary>
            <dl class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach(['Margen bruto POS' => $metrics['posMargin'], 'POS cobrado' => $metrics['collectedPos'], 'Abonos de arriendos' => $metrics['deposits'], 'Consumos pendientes' => $metrics['openSales']] as $label => $value)
                    <div><dt class="text-xs text-slate-500 dark:text-slate-400">{{ $label }}</dt><dd class="mt-2 text-xl font-bold">${{ number_format($value, 0, ',', '.') }}</dd></div>
                @endforeach
            </dl>
            <p class="mt-5 text-xs leading-5 text-slate-500 dark:text-slate-400">Arriendos por fecha de inicio; ventas por fecha de registro. POS cobrado usa la fecha de pago. El margen POS descuenta mercader&iacute;a, no gastos operativos.</p>
        </details>
        <section data-reveal class="surface-card rounded-xl bg-white dark:bg-slate-900 shadow-sm p-5 space-y-4">
            <div class="flex flex-wrap justify-between gap-2"><h3 class="font-semibold text-lg">Actividad diaria</h3><p class="text-sm"><span class="text-brand-content">■ Arriendos</span> <span class="ml-3 text-violet-600 dark:text-violet-300">■ POS</span></p></div>
            @if ($metrics['rentalCount'] === 0 && $metrics['saleCount'] === 0)
                <p class="text-slate-600 dark:text-slate-400 py-8 text-center">Todavía no hay ventas ni arriendos en este período.</p>
            @else
                <div class="overflow-x-auto pb-3" tabindex="0" aria-label="Gráfico diario de arriendos y ventas. Desplázate para ver todas las fechas.">
                    <div class="flex items-end gap-3 min-w-full border-b border-slate-200 dark:border-slate-800 pt-5">
                        @foreach ($daily as $day)
                            <div class="shrink-0 flex-1 min-w-10 text-center">
                                <div class="flex items-end justify-center gap-1 h-40" title="{{ $day['date'] }}: Arriendos ${{ number_format($day['rentals'], 0, ',', '.') }} · POS ${{ number_format($day['sales'], 0, ',', '.') }}">
                                    <div class="w-3 rounded-t bg-emerald-500" style="height: {{ $day['rentals'] / $chartMax * 150 }}px"></div>
                                    <div class="w-3 rounded-t bg-violet-500" style="height: {{ $day['sales'] / $chartMax * 150 }}px"></div>
                                    <span class="sr-only">{{ $day['date'] }}: arriendos {{ $day['rentals'] }}, ventas POS {{ $day['sales'] }}.</span>
                                </div>
                                <p class="text-xs text-slate-600 dark:text-slate-400 py-2">{{ $day['date'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
        <div class="grid lg:grid-cols-2 gap-6">
            <section data-reveal class="surface-card rounded-xl bg-white dark:bg-slate-900 shadow-sm p-5">
                <h3 class="font-semibold text-lg mb-4">Más vendidos</h3>
                <div class="max-w-full overflow-x-auto"><table class="w-full text-sm text-left"><thead class="text-slate-600 dark:text-slate-400"><tr><th class="py-2">Producto</th><th>Unidades</th><th class="text-right">Ventas</th></tr></thead><tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($topProducts as $product)
                        <tr><td class="py-3 pr-2">{{ $product->name }}</td><td>{{ $product->units }}</td><td class="text-right">${{ number_format($product->amount, 0, ',', '.') }}</td></tr>
                    @empty<tr><td colspan="3" class="py-6 text-slate-600 dark:text-slate-400">Sin ventas de productos en este período.</td></tr>@endforelse
                </tbody></table></div>
            </section>
            <section data-reveal class="surface-card rounded-xl bg-white dark:bg-slate-900 shadow-sm p-5">
                <h3 class="font-semibold text-lg mb-4">Arriendos por cancha</h3>
                <div class="max-w-full overflow-x-auto"><table class="w-full text-sm text-left"><thead class="text-slate-600 dark:text-slate-400"><tr><th class="py-2">Cancha</th><th>Reservas</th><th class="text-right">Valor</th></tr></thead><tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($courtTotals as $court)
                        <tr><td class="py-3 pr-2">{{ $court->court?->name }}</td><td>{{ $court->count }}</td><td class="text-right">${{ number_format($court->amount, 0, ',', '.') }}</td></tr>
                    @empty<tr><td colspan="3" class="py-6 text-slate-600 dark:text-slate-400">Sin arriendos en este período.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        </div>
        <div class="grid lg:grid-cols-3 gap-6">
            <section data-reveal class="surface-card rounded-xl bg-white dark:bg-slate-900 shadow-sm p-5">
                <h3 class="font-semibold text-lg mb-4">Últimas ventas</h3>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">@forelse ($recentSales as $sale)
                    <div class="py-3"><div class="flex justify-between gap-2"><p class="font-medium">Venta #{{ $sale->id }}</p><p>${{ number_format($sale->total_amount, 0, ',', '.') }}</p></div><p class="text-xs text-slate-600 dark:text-slate-400">{{ $sale->created_at->format('d/m H:i') }} · {{ $sale->status === 'paid' ? 'Pagada' : 'Pendiente' }}</p></div>
                @empty<p class="text-sm text-slate-600 dark:text-slate-400 py-4">No hay ventas para mostrar.</p>@endforelse</div>
                <a class="text-sm text-brand-content underline" href="{{ route('pos.index') }}">Abrir POS</a>
            </section>
            <section data-reveal class="surface-card rounded-xl bg-white dark:bg-slate-900 shadow-sm p-5">
                <h3 class="font-semibold text-lg mb-4">Últimas reservas</h3>
                <div class="divide-y divide-slate-200 dark:divide-slate-800">@forelse ($recentBookings as $booking)
                    <div class="py-3"><p class="font-medium">{{ $booking->court?->name }} · {{ $booking->customer_name }}</p><p class="text-xs text-slate-600 dark:text-slate-400">{{ $booking->start_time->format('d/m H:i') }} · ${{ number_format($booking->total_price, 0, ',', '.') }}</p></div>
                @empty<p class="text-sm text-slate-600 dark:text-slate-400 py-4">No hay arriendos para mostrar.</p>@endforelse</div>
                <a class="text-sm text-brand-content underline" href="{{ route('bookings.index') }}">Abrir agenda</a>
            </section>
            <section data-reveal class="rounded-xl bg-amber-400/5 border border-amber-400/20 p-5">
                <h3 class="font-semibold text-lg mb-4">Stock bajo</h3>
                <div class="divide-y divide-amber-400/10">@forelse ($lowStock as $product)
                    <div class="py-3 flex justify-between gap-3"><p class="font-medium">{{ $product->name }}</p><p class="text-amber-800 dark:text-amber-200">{{ $product->stock }} un.</p></div>
                @empty<p class="text-sm text-slate-600 dark:text-slate-400 py-4">No hay alertas de stock.</p>@endforelse</div>
                <a class="text-sm text-brand-content underline" href="{{ route('inventory.index') }}">Revisar inventario</a>
            </section>
        </div>
    </div>
</x-app-layout>
