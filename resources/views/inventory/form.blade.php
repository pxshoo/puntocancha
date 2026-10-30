<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $product->exists ? 'Editar producto' : 'Nuevo producto' }}</h2></x-slot>
    <div class="max-w-3xl mx-auto p-6 space-y-6">
        <a class="text-indigo-700 underline" href="{{ route('inventory.index') }}">Volver al inventario</a>
        <x-operation-messages />
        <form method="POST" action="{{ $product->exists ? route('inventory.update', $product) : route('inventory.store') }}" class="bg-white rounded-xl shadow-sm p-6 grid sm:grid-cols-2 gap-5">
            @csrf
            @if ($product->exists) @method('PUT') @endif
            <label class="grid gap-1 sm:col-span-2">Nombre <input class="rounded border-gray-300" name="name" value="{{ old('name', $product->name) }}" maxlength="255" required></label>
            <label class="grid gap-1">Categoría <select class="rounded border-gray-300" name="category_id">
                <option value="">Sin categoría</option>
                @foreach ($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>@endforeach
            </select></label>
            <label class="grid gap-1">Código de barras <input class="rounded border-gray-300" name="barcode" maxlength="100" value="{{ old('barcode', $product->barcode) }}"></label>
            <label class="grid gap-1">Costo unitario ($) <input class="rounded border-gray-300" type="number" min="0" max="99999999.99" step="0.01" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" required></label>
            <label class="grid gap-1">Precio de venta ($) <input class="rounded border-gray-300" type="number" min="0.01" max="99999999.99" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" required></label>
            @unless ($product->exists)
                <label class="grid gap-1">Stock inicial <input class="rounded border-gray-300" type="number" min="0" max="100000000" name="initial_stock" value="{{ old('initial_stock', 0) }}" required></label>
            @endunless
            <label class="grid gap-1">Alerta de stock mínimo <input class="rounded border-gray-300" type="number" min="0" max="100000000" name="alert_min_stock" value="{{ old('alert_min_stock', $product->alert_min_stock ?? 5) }}" required></label>
            <label class="grid gap-1">Estado <select class="rounded border-gray-300" name="is_active">
                <option value="1" @selected(old('is_active', $product->is_active ?? true) == 1)>Activo</option>
                <option value="0" @selected(old('is_active', $product->is_active ?? true) == 0)>Inactivo</option>
            </select></label>
            <div class="sm:col-span-2"><x-primary-button>Guardar producto</x-primary-button></div>
        </form>
    </div>
</x-app-layout>
