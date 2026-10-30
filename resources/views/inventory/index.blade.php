<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl">Inventario</h2>
            <a href="{{ route('inventory.create') }}" class="rounded-lg bg-indigo-600 text-white px-4 py-3 font-semibold">Nuevo producto</a>
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
        <x-operation-messages />
        <div class="rounded-xl bg-amber-50 border border-amber-200 p-4">{{ $lowStock }} producto(s) activo(s) en stock mínimo o agotados.</div>
        <div class="grid lg:grid-cols-2 gap-4">
            <form method="GET" action="{{ route('inventory.index') }}" class="flex flex-wrap gap-3">
                <label class="grow"><span class="sr-only">Buscar producto o código</span><input class="rounded-lg border-gray-300 w-full" name="q" value="{{ request('q') }}" placeholder="Nombre o código de barras" maxlength="100"></label>
                <x-primary-button>Buscar</x-primary-button>
            </form>
            <form method="POST" action="{{ route('inventory.categories.store') }}" class="flex flex-wrap gap-3">
                @csrf
                <label class="grow"><span class="sr-only">Nombre de la nueva categoría</span><input class="rounded-lg border-gray-300 w-full" name="name" maxlength="100" placeholder="Nueva categoría" required></label>
                <x-primary-button>Crear categoría</x-primary-button>
            </form>
        </div>
        <div class="overflow-x-auto bg-white rounded-xl shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left"><tr><th class="p-4">Producto</th><th class="p-4">Costo / Precio</th><th class="p-4">Stock</th><th class="p-4">Acciones</th></tr></thead>
                <tbody class="divide-y">
                    @forelse ($products as $product)
                        <tr>
                            <td class="p-4"><p class="font-semibold">{{ $product->name }}</p><p class="text-gray-500">{{ $product->category?->name ?? 'Sin categoría' }} · {{ $product->barcode ?? 'Sin código' }}</p>@unless ($product->is_active)<span class="text-gray-500">Inactivo</span>@endunless</td>
                            <td class="p-4 whitespace-nowrap">${{ number_format($product->cost_price, 2, ',', '.') }} / ${{ number_format($product->sale_price, 2, ',', '.') }}</td>
                            <td class="p-4 font-semibold {{ $product->stock <= $product->alert_min_stock ? 'text-amber-700' : 'text-gray-800' }}">{{ $product->stock }} <span class="font-normal text-xs">(mín. {{ $product->alert_min_stock }})</span></td>
                            <td class="p-4 space-y-3">
                                <a class="text-indigo-700 underline" href="{{ route('inventory.edit', $product) }}">Editar</a>
                                <form method="POST" action="{{ route('inventory.adjust', $product) }}" class="flex flex-wrap gap-2">
                                    @csrf
                                    <label><span class="sr-only">Cantidad de ajuste para {{ $product->name }}</span><input class="rounded border-gray-300 w-24 text-sm" type="number" name="quantity" min="-100000000" max="100000000" placeholder="+ / −" required></label>
                                    <label><span class="sr-only">Motivo del ajuste</span><input class="rounded border-gray-300 text-sm" name="reason" maxlength="255" placeholder="Motivo: compra, merma..." required></label>
                                    <x-secondary-button type="submit">Ajustar</x-secondary-button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-gray-500">No hay productos. Crea una categoría y tu primer producto para comenzar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $products->links() }}
        <section class="bg-white rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Últimos movimientos</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-sm text-left">
                <thead><tr><th class="py-2">Fecha</th><th>Producto</th><th>Cambio</th><th>Stock final</th><th>Motivo</th></tr></thead>
                <tbody class="divide-y">@forelse ($movements as $movement)
                    <tr><td class="py-3 pr-3 whitespace-nowrap">{{ $movement->created_at->format('d/m/Y H:i') }}</td><td class="pr-3">{{ $movement->product->name }}</td><td>{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td><td>{{ $movement->stock_after }}</td><td>{{ $movement->reason }} @if ($movement->sale_id) #{{ $movement->sale_id }} @endif</td></tr>
                @empty<tr><td colspan="5" class="py-4 text-gray-500">Todavía no hay movimientos.</td></tr>@endforelse</tbody>
            </table></div>
        </section>
    </div>
</x-app-layout>
