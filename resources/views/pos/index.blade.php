<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap gap-3 items-center justify-between"><h2 class="font-semibold text-xl">Punto de venta</h2><a class="text-indigo-700 underline" href="{{ route('inventory.index') }}">Administrar inventario</a></div></x-slot>
    @php
        $catalog = $products->map(fn ($product) => [
            'id' => $product->id, 'name' => $product->name, 'category' => $product->category?->name ?? 'Sin categoría',
            'barcode' => $product->barcode, 'price' => $product->sale_price, 'stock' => $product->stock,
        ])->values();
    @endphp
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
        <x-operation-messages />
        <div x-data="posCart({{ Illuminate\Support\Js::from($catalog) }}, {{ Illuminate\Support\Js::from(old('items', [])) }}, {{ Illuminate\Support\Js::from(old('payment_method', 'cash')) }})" class="grid lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 space-y-4">
                <label class="block"><span class="sr-only">Buscar en el catálogo</span><input x-model="search" class="w-full rounded-xl border-gray-300 p-3" placeholder="Buscar bebida, snack o código de barras"></label>
                @if ($products->isEmpty())<p class="rounded-xl bg-white p-6 text-gray-500">No hay productos activos. Agrega productos desde Inventario.</p>@endif
                <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
                    <template x-for="product in filtered" :key="product.id">
                        <button type="button" @click="add(product)" :disabled="quantity(product.id) >= product.stock" class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 text-left min-h-36 hover:border-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <span class="text-xs text-indigo-700" x-text="product.category"></span>
                            <span class="block font-semibold mt-2" x-text="product.name"></span>
                            <span class="block text-xl font-bold mt-3" x-text="money(product.price)"></span>
                            <span class="text-xs text-gray-500" x-text="product.stock + ' disponibles'"></span>
                        </button>
                    </template>
                </div>
                <p x-show="filtered.length === 0 && search" class="text-gray-500">No hay coincidencias.</p>
            </section>
            <form method="POST" action="{{ route('pos.store') }}" @submit="submitting = true" class="bg-white rounded-xl shadow-sm p-5 space-y-4 self-start">
                @csrf
                <input type="hidden" name="request_key" value="{{ old('request_key', $requestKey) }}">
                <h3 class="font-semibold text-lg">Venta actual</h3>
                <p x-show="cart.length === 0" class="text-gray-500 py-6">Toca un producto para agregarlo.</p>
                <template x-for="(item, index) in cart" :key="item.id">
                    <div class="border-b pb-4 space-y-2">
                        <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.id">
                        <div class="flex justify-between gap-3"><span class="font-semibold" x-text="item.name"></span><button type="button" @click="cart = cart.filter(row => row.id !== item.id)" class="text-red-700 text-sm">Quitar</button></div>
                        <div class="flex justify-between items-center gap-3"><label class="text-sm">Cantidad <input type="number" class="w-20 rounded border-gray-300" min="1" :max="item.stock" x-model.number="item.quantity" :name="'items[' + index + '][quantity]'" required></label><span x-text="money(item.quantity * item.price)"></span></div>
                    </div>
                </template>
                <div class="flex justify-between text-xl font-bold"><span>Total</span><span x-text="money(total)"></span></div>
                <p class="text-xs text-gray-500">El precio y el stock se verifican al registrar la venta.</p>
                <label class="grid gap-1">Cobro <select class="rounded border-gray-300" name="payment_method" x-model="method">
                    <option value="cash">Efectivo</option><option value="pos">Tarjeta POS</option><option value="transfer">Transferencia</option><option value="booking">Cargar a reserva</option>
                </select></label>
                <label x-show="method === 'booking'" class="grid gap-1">Reserva de hoy <select class="rounded border-gray-300 w-full" name="booking_id" :disabled="method !== 'booking'" :required="method === 'booking'">
                    <option value="">Seleccionar reserva</option>
                    @foreach ($bookings as $booking)<option value="{{ $booking->id }}" @selected(old('booking_id') == $booking->id)>{{ $booking->start_time->format('H:i') }} · {{ $booking->court->name }} · {{ $booking->customer_name }}</option>@endforeach
                </select></label>
                @if ($bookings->isEmpty())<p x-show="method === 'booking'" class="text-sm text-amber-700">No hay reservas activas para hoy.</p>@endif
                <button type="submit" :disabled="cart.length === 0 || submitting" class="w-full rounded-lg bg-indigo-600 text-white font-semibold p-4 disabled:opacity-50" x-text="submitting ? 'Registrando...' : method === 'booking' ? 'Dejar consumo pendiente' : 'Registrar cobro'"></button>
                <p class="text-xs text-gray-500">Registra cobros realizados en caja. El pago con tarjeta se procesa en tu terminal.</p>
            </form>
        </div>
        <section class="bg-white rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-lg font-semibold">Consumos pendientes por reserva</h3>
            @forelse ($openSales->groupBy('booking_id') as $charges)
                @php($booking = $charges->first()->booking)
                <div class="border rounded-lg p-4 space-y-3">
                    <h4 class="font-semibold">{{ $booking?->court?->name }} · {{ $booking?->customer_name }} · {{ $booking?->start_time?->format('d/m H:i') }}</h4>
                    <p>Total pendiente: <strong>${{ number_format($charges->sum('total_amount'), 2, ',', '.') }}</strong></p>
                    @foreach ($charges as $sale)
                        <div class="flex flex-wrap gap-3 items-center justify-between border-t pt-3">
                            <div><p class="font-semibold">Venta #{{ $sale->id }} · ${{ number_format($sale->total_amount, 2, ',', '.') }}</p><p class="text-sm text-gray-500">@foreach ($sale->items as $item){{ $item->quantity }} × {{ $item->product_name }}{{ $loop->last ? '' : ', ' }}@endforeach</p></div>
                            <form method="POST" action="{{ route('pos.settle', $sale) }}" class="flex flex-wrap gap-2">
                                @csrf
                                <label><span class="sr-only">Medio de pago para venta {{ $sale->id }}</span><select name="payment_method" class="rounded border-gray-300 text-sm"><option value="cash">Efectivo</option><option value="pos">Tarjeta POS</option><option value="transfer">Transferencia</option></select></label>
                                <x-primary-button>Cobrar consumo</x-primary-button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @empty<p class="text-gray-500">No hay consumos pendientes.</p>@endforelse
        </section>
        <section class="bg-white rounded-xl shadow-sm p-5 space-y-4">
            <h3 class="text-lg font-semibold">Historial de ventas</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-sm text-left">
                <thead><tr><th class="p-2">Venta / Fecha</th><th class="p-2">Detalle</th><th class="p-2">Total</th><th class="p-2">Estado / Pago</th></tr></thead>
                <tbody class="divide-y">@forelse ($sales as $sale)
                    <tr><td class="p-2 whitespace-nowrap">#{{ $sale->id }}<br>{{ $sale->created_at->format('d/m/Y H:i') }}</td><td class="p-2">@foreach ($sale->items as $item)<p>{{ $item->quantity }} × {{ $item->product_name }}</p>@endforeach</td><td class="p-2 whitespace-nowrap">${{ number_format($sale->total_amount, 2, ',', '.') }}</td><td class="p-2">{{ $sale->status === 'paid' ? 'Pagada' : 'Pendiente' }}<br>{{ ['cash' => 'Efectivo', 'pos' => 'Tarjeta POS', 'transfer' => 'Transferencia'][$sale->payment_method] ?? 'Cuenta de cancha' }}</td></tr>
                @empty<tr><td colspan="4" class="p-4 text-gray-500">Todavía no hay ventas.</td></tr>@endforelse</tbody>
            </table></div>
            {{ $sales->links() }}
        </section>
    </div>
</x-app-layout>
