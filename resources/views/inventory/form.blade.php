@php
    $beverages = $categories->first(fn ($category) => mb_strtolower($category->name) === 'bebidas');
    $initialCategory = old('category_choice', $product->exists ? ($product->category_id ? 'existing:'.$product->category_id : '') : ($beverages ? 'existing:'.$beverages->id : 'default:Bebidas'));
@endphp
<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">{{ $product->exists ? 'Editar producto' : 'Nuevo producto' }}</h2></x-slot>
    <div class="max-w-3xl mx-auto p-6 space-y-6">
        <a class="text-brand-content underline" href="{{ route('inventory.index') }}">Volver al inventario</a>
        <x-operation-messages />
        <form x-data="{ categoryChoice: @js($initialCategory) }" method="POST" action="{{ $product->exists ? route('inventory.update', $product) : route('inventory.store') }}" class="bg-white dark:bg-slate-900 rounded-xl shadow-sm p-6 grid sm:grid-cols-2 gap-5">
            @csrf
            @if ($product->exists) @method('PUT') @endif
            <label class="grid gap-1 sm:col-span-2">Nombre <input class="rounded border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" name="name" value="{{ old('name', $product->name) }}" maxlength="255" required></label>
            <div class="grid gap-2">
                <label for="product-category" class="text-sm font-semibold">Categoría</label>
                <select id="product-category" name="category_choice" x-model="categoryChoice" class="rounded-xl border-slate-200 bg-slate-50 text-slate-900 focus:border-brand focus:ring-brand-ring dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100">
                    <optgroup label="Categorías habituales">
                        @foreach(\App\Models\ProductCategory::DEFAULT_NAMES as $name)
                            @php($existingCategory = $categories->first(fn ($category) => mb_strtolower($category->name) === mb_strtolower($name)))
                            @php($choice = $existingCategory ? 'existing:'.$existingCategory->id : 'default:'.$name)
                            <option value="{{ $choice }}" @selected($initialCategory === $choice)>{{ $name }}</option>
                        @endforeach
                    </optgroup>
                    @foreach($categories->reject(fn ($category) => in_array(mb_strtolower($category->name), array_map('mb_strtolower', \App\Models\ProductCategory::DEFAULT_NAMES), true)) as $category)
                        <option value="existing:{{ $category->id }}" @selected($initialCategory === 'existing:'.$category->id)>{{ $category->name }}</option>
                    @endforeach
                    <option value="new" @selected($initialCategory === 'new')>+ Otra categoría...</option>
                    <option value="" @selected($initialCategory === '')>Sin categoría</option>
                </select>
                <x-input-error :messages="$errors->get('category_id')" />
                <x-input-error :messages="$errors->get('default_category')" />
                <div x-show="categoryChoice === 'new'" x-cloak class="grid gap-2">
                    <label for="new-product-category" class="text-sm">Nombre de la nueva categoría</label>
                    <input id="new-product-category" name="new_category" value="{{ old('new_category') }}" :required="categoryChoice === 'new'" :disabled="categoryChoice !== 'new'" maxlength="100" placeholder="Ej. Suplementos" class="rounded-xl border-slate-200 bg-slate-50 focus:border-brand focus:ring-brand-ring dark:border-slate-800 dark:bg-slate-950">
                    <p class="text-xs text-slate-500">Se creará al guardar el producto.</p>
                    <x-input-error :messages="$errors->get('new_category')" />
                </div>
            </div>
            <label class="grid gap-1">Código de barras <input class="rounded border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" name="barcode" maxlength="100" value="{{ old('barcode', $product->barcode) }}"></label>
            <label class="grid gap-1">Costo unitario ($) <input class="rounded border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" type="number" min="0" max="99999999.99" step="0.01" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}" required></label>
            <label class="grid gap-1">Precio de venta ($) <input class="rounded border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" type="number" min="0.01" max="99999999.99" step="0.01" name="sale_price" value="{{ old('sale_price', $product->sale_price) }}" required></label>
            @unless ($product->exists)
                <label class="grid gap-1">Stock inicial <input class="rounded border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" type="number" min="0" max="100000000" name="initial_stock" value="{{ old('initial_stock', 0) }}" required></label>
            @endunless
            <label class="grid gap-1">Alerta de stock mínimo <input class="rounded border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" type="number" min="0" max="100000000" name="alert_min_stock" value="{{ old('alert_min_stock', $product->alert_min_stock ?? 5) }}" required></label>
            <label class="grid gap-1">Estado <select class="rounded border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" name="is_active">
                <option value="1" @selected(old('is_active', $product->is_active ?? true) == 1)>Activo</option>
                <option value="0" @selected(old('is_active', $product->is_active ?? true) == 0)>Inactivo</option>
            </select></label>
            <div class="sm:col-span-2"><x-primary-button>Guardar cambios</x-primary-button></div>
        </form>
    </div>
</x-app-layout>
