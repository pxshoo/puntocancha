<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl">Inventario</h2>
            <a href="{{ route('inventory.create') }}" class=" bg-brand text-brand-on text-brand-on   font-semibold inline-flex min-h-14 items-center justify-center rounded-xl px-7 py-4 text-base font-bold shadow-sm shadow-emerald-900/10 focus-visible:ring-2 focus-visible:ring-brand-ring focus-visible:ring-offset-2">Nuevo producto</a>
        </div>
    </x-slot>
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
        <x-operation-messages />
        <div class="rounded-xl bg-amber-400/5 border border-amber-400/20 p-4">{{ $lowStock }} producto(s) activo(s) en stock mínimo o agotados.</div>
        <div class="grid gap-4">
            <form method="GET" action="{{ route('inventory.index') }}" class="responsive-filter flex flex-wrap gap-3">
                <label class="grow"><span class="sr-only">Buscar producto o código</span><input class="rounded-lg border-slate-200 dark:border-slate-800 w-full bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" name="q" value="{{ request('q') }}" placeholder="Nombre o código de barras" maxlength="100"></label>
                <x-primary-button>Buscar</x-primary-button>
            </form>

        </div>
        <div class="surface-card overflow-x-auto bg-white dark:bg-slate-900 rounded-xl shadow-sm">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100/40 dark:bg-slate-800/40 text-left"><tr><th class="p-4">Producto</th><th class="p-4">Costo / Precio</th><th class="p-4">Stock</th><th class="p-4">Acciones</th></tr></thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse ($products as $product)
                        <tr>
                            <td class="p-4"><p class="font-semibold">{{ $product->name }}</p><p class="text-slate-600 dark:text-slate-400">{{ $product->category?->name ?? 'Sin categoría' }} · {{ $product->barcode ?? 'Sin código' }}</p>@unless ($product->is_active)<span class="text-slate-600 dark:text-slate-400">Inactivo</span>@endunless</td>
                            <td class="p-4 whitespace-nowrap">${{ number_format($product->cost_price, 2, ',', '.') }} / ${{ number_format($product->sale_price, 2, ',', '.') }}</td>
                            <td class="p-4 font-semibold {{ $product->stock <= $product->alert_min_stock ? 'text-amber-700 dark:text-amber-300' : 'text-slate-900 dark:text-slate-100' }}">{{ $product->stock }} <span class="font-normal text-xs">(mín. {{ $product->alert_min_stock }})</span></td>
                            <td class="p-4 space-y-3">
                                <a class="text-brand-content underline" href="{{ route('inventory.edit', $product) }}">Editar</a>
                                <form method="POST" action="{{ route('inventory.adjust', $product) }}" class="flex flex-wrap gap-2">
                                    @csrf
                                    <label><span class="sr-only">Cantidad de ajuste para {{ $product->name }}</span><input class="rounded border-slate-200 dark:border-slate-800 w-24 text-sm bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" type="number" name="quantity" min="-100000000" max="100000000" placeholder="+ / −" required></label>
                                    <label><span class="sr-only">Motivo del ajuste</span><select name="reason" class="rounded-lg border-slate-200 bg-slate-50 text-sm text-slate-900 focus:ring-brand-ring dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"><option value="Reposición de mercadería">Reposición</option><option value="Compra de mercadería">Compra</option><option value="Merma o pérdida">Merma o pérdida</option><option value="Corrección de inventario">Corrección de inventario</option></select></label>
                                    <x-secondary-button type="submit">Guardar cambios</x-secondary-button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="p-8 text-center text-slate-600 dark:text-slate-400">No hay productos. Agrega tu primer producto para comenzar.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $products->links() }}
        <section data-reveal class="surface-card bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6">
            <h3 class="text-lg font-semibold mb-4">Últimos movimientos</h3>
            <div class="overflow-x-auto"><table class="min-w-full text-sm text-left">
                <thead><tr><th class="py-2">Fecha</th><th>Producto</th><th>Cambio</th><th>Stock final</th><th>Motivo</th></tr></thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">@forelse ($movements as $movement)
                    <tr><td class="py-3 pr-3 whitespace-nowrap">{{ $movement->created_at->format('d/m/Y H:i') }}</td><td class="pr-3">{{ $movement->product->name }}</td><td>{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}</td><td>{{ $movement->stock_after }}</td><td>{{ $movement->reason }} @if ($movement->sale_id) #{{ $movement->sale_id }} @endif</td></tr>
                @empty<tr><td colspan="5" class="py-4 text-slate-600 dark:text-slate-400">Todavía no hay movimientos.</td></tr>@endforelse</tbody>
            </table></div>
        </section>
    </div>
</x-app-layout>
