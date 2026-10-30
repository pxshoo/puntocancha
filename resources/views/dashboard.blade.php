<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div><h2 class="font-semibold text-xl text-gray-800">Resumen del club</h2><p class="text-sm text-gray-500 mt-1">{{ auth()->user()->club->name }} · {{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</p></div>
            <div class="flex flex-wrap gap-2">
                <a class="rounded-lg bg-indigo-600 text-white px-4 py-2" href="{{ route('pos.index') }}">Nueva venta</a>
                <a class="rounded-lg border border-gray-300 px-4 py-2" href="{{ route('bookings.index') }}">Ver arriendos</a>
            </div>
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
        <x-operation-messages />
        <section class="rounded-xl bg-white p-4 shadow-sm space-y-4">
            <nav aria-label="Período del resumen" class="flex flex-wrap gap-2">
                @foreach (['today' => 'Hoy', 'week' => 'Esta semana', 'month' => 'Este mes'] as $value => $label)
                    <a href="{{ route('dashboard', ['period' => $value]) }}" @if ($period === $value) aria-current="page" @endif class="rounded-lg px-4 py-2 {{ $period === $value ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <form action="{{ route('dashboard') }}" method="GET" class="flex flex-wrap gap-3 items-end">
                <input type="hidden" name="period" value="custom">
                <label class="grid gap-1 text-sm">Desde <input class="rounded-lg border-gray-300" type="date" name="from" value="{{ old('from', $from->toDateString()) }}" required></label>
                <label class="grid gap-1 text-sm">Hasta <input class="rounded-lg border-gray-300" type="date" name="to" value="{{ old('to', $to->toDateString()) }}" required></label>
                <x-primary-button>Aplicar fechas</x-primary-button>
            </form>
        </section>
        @php
            $cards = [
                ['title' => 'Arriendos + ventas POS', 'value' => $metrics['total'], 'detail' => 'Valor registrado en el período; incluye saldos pendientes.'],
                ['title' => 'Arriendos de canchas', 'value' => $metrics['rentals'], 'detail' => $metrics['rentalCount'].' reservas · excluye canceladas.'],
                ['title' => 'Ventas POS', 'value' => $metrics['sales'], 'detail' => $metrics['saleCount'].' ventas · incluye consumos a la cancha.'],
                ['title' => 'Margen bruto POS', 'value' => $metrics['posMargin'], 'detail' => 'Ventas menos costo histórico de mercadería.'],
                ['title' => 'POS cobrado', 'value' => $metrics['collectedPos'], 'detail' => 'Pagos registrados en estas fechas, incluso de ventas anteriores.'],
                ['title' => 'Consumos POS pendientes', 'value' => $metrics['openSales'], 'detail' => 'Ventas del período cargadas a reservas y aún sin cobrar.'],
                ['title' => 'Abonos de arriendos', 'value' => $metrics['deposits'], 'detail' => 'Abonos de las reservas que comienzan en estas fechas.'],
                ['title' => 'Saldo de arriendos', 'value' => $metrics['rentalPending'], 'detail' => 'Valor de las reservas menos sus abonos.'],
            ];
        @endphp
        <section aria-label="Indicadores del período" class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach ($cards as $card)
                <article class="rounded-xl bg-white shadow-sm border border-gray-100 p-5">
                    <h3 class="text-sm text-gray-600">{{ $card['title'] }}</h3>
                    <p class="text-2xl font-bold text-gray-900 mt-2">${{ number_format($card['value'], 2, ',', '.') }}</p>
                    <p class="text-xs text-gray-500 mt-3">{{ $card['detail'] }}</p>
                </article>
            @endforeach
        </section>
        <p class="text-sm text-gray-600">{{ $metrics['activeCourts'] }} canchas activas · {{ $metrics['activeProducts'] }} productos activos. Los arriendos se agrupan por fecha de inicio y las ventas por fecha de registro. El margen no descuenta gastos operativos; su módulo aún no está disponible.</p>
        <section class="rounded-xl bg-white shadow-sm p-5 space-y-4">
            <div class="flex flex-wrap justify-between gap-2"><h3 class="font-semibold text-lg">Actividad diaria</h3><p class="text-sm"><span class="text-indigo-700">■ Arriendos</span> <span class="text-emerald-700 ml-3">■ POS</span></p></div>
            @if ($metrics['rentalCount'] === 0 && $metrics['saleCount'] === 0)
                <p class="text-gray-500 py-8 text-center">Todavía no hay ventas ni arriendos en este período.</p>
            @else
                <div class="overflow-x-auto pb-3" tabindex="0" aria-label="Gráfico diario de arriendos y ventas. Desplázate para ver todas las fechas.">
                    <div class="flex items-end gap-3 min-w-full border-b border-gray-200 pt-5">
                        @foreach ($daily as $day)
                            <div class="shrink-0 flex-1 min-w-10 text-center">
                                <div class="flex items-end justify-center gap-1 h-40" title="{{ $day['date'] }}: Arriendos ${{ number_format($day['rentals'], 2, ',', '.') }} · POS ${{ number_format($day['sales'], 2, ',', '.') }}">
                                    <div class="w-3 rounded-t bg-indigo-500" style="height: {{ $day['rentals'] / $chartMax * 150 }}px"></div>
                                    <div class="w-3 rounded-t bg-emerald-500" style="height: {{ $day['sales'] / $chartMax * 150 }}px"></div>
                                    <span class="sr-only">{{ $day['date'] }}: arriendos {{ $day['rentals'] }}, ventas POS {{ $day['sales'] }}.</span>
                                </div>
                                <p class="text-xs text-gray-500 py-2">{{ $day['date'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </section>
        <div class="grid lg:grid-cols-2 gap-6">
            <section class="rounded-xl bg-white shadow-sm p-5">
                <h3 class="font-semibold text-lg mb-4">Productos más vendidos</h3>
                <table class="w-full text-sm text-left"><thead class="text-gray-500"><tr><th class="py-2">Producto</th><th>Unidades</th><th class="text-right">Ventas</th></tr></thead><tbody class="divide-y">
                    @forelse ($topProducts as $product)
                        <tr><td class="py-3 pr-2">{{ $product->name }}</td><td>{{ $product->units }}</td><td class="text-right">${{ number_format($product->amount, 2, ',', '.') }}</td></tr>
                    @empty<tr><td colspan="3" class="py-6 text-gray-500">Sin ventas de productos en este período.</td></tr>@endforelse
                </tbody></table>
            </section>
            <section class="rounded-xl bg-white shadow-sm p-5">
                <h3 class="font-semibold text-lg mb-4">Arriendos por cancha</h3>
                <table class="w-full text-sm text-left"><thead class="text-gray-500"><tr><th class="py-2">Cancha</th><th>Reservas</th><th class="text-right">Valor</th></tr></thead><tbody class="divide-y">
                    @forelse ($courtTotals as $court)
                        <tr><td class="py-3 pr-2">{{ $court->court?->name }}</td><td>{{ $court->count }}</td><td class="text-right">${{ number_format($court->amount, 2, ',', '.') }}</td></tr>
                    @empty<tr><td colspan="3" class="py-6 text-gray-500">Sin arriendos en este período.</td></tr>@endforelse
                </tbody></table>
            </section>
        </div>
        <div class="grid lg:grid-cols-3 gap-6">
            <section class="rounded-xl bg-white shadow-sm p-5">
                <h3 class="font-semibold text-lg mb-4">Últimas ventas del período</h3>
                <div class="divide-y">@forelse ($recentSales as $sale)
                    <div class="py-3"><div class="flex justify-between gap-2"><p class="font-medium">Venta #{{ $sale->id }}</p><p>${{ number_format($sale->total_amount, 2, ',', '.') }}</p></div><p class="text-xs text-gray-500">{{ $sale->created_at->format('d/m H:i') }} · {{ $sale->status === 'paid' ? 'Pagada' : 'Pendiente' }}</p></div>
                @empty<p class="text-sm text-gray-500 py-4">No hay ventas para mostrar.</p>@endforelse</div>
                <a class="text-sm text-indigo-700 underline" href="{{ route('pos.index') }}">Abrir POS</a>
            </section>
            <section class="rounded-xl bg-white shadow-sm p-5">
                <h3 class="font-semibold text-lg mb-4">Últimos arriendos del período</h3>
                <div class="divide-y">@forelse ($recentBookings as $booking)
                    <div class="py-3"><p class="font-medium">{{ $booking->court?->name }} · {{ $booking->customer_name }}</p><p class="text-xs text-gray-500">{{ $booking->start_time->format('d/m H:i') }} · ${{ number_format($booking->total_price, 2, ',', '.') }}</p></div>
                @empty<p class="text-sm text-gray-500 py-4">No hay arriendos para mostrar.</p>@endforelse</div>
                <a class="text-sm text-indigo-700 underline" href="{{ route('bookings.index') }}">Abrir agenda</a>
            </section>
            <section class="rounded-xl bg-amber-50 border border-amber-200 p-5">
                <h3 class="font-semibold text-lg mb-4">Stock bajo actual</h3>
                <div class="divide-y divide-amber-200">@forelse ($lowStock as $product)
                    <div class="py-3 flex justify-between gap-3"><p class="font-medium">{{ $product->name }}</p><p class="text-amber-900">{{ $product->stock }} un.</p></div>
                @empty<p class="text-sm text-gray-600 py-4">No hay alertas de stock.</p>@endforelse</div>
                <a class="text-sm text-indigo-700 underline" href="{{ route('inventory.index') }}">Revisar inventario</a>
            </section>
        </div>
    </div>
</x-app-layout>
