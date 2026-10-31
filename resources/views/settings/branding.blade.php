<x-app-layout>
    <x-slot name="header"><a href="{{ route('settings.index') }}" class="text-sm text-brand-content">← Configuración</a><h1 class="mt-3 text-2xl font-bold text-slate-900 dark:text-white">Logo y colores</h1><p class="mt-2 text-sm text-slate-500">Tu identidad en el panel y en la página de reservas de tu club.</p></x-slot>
    <div class="mx-auto max-w-7xl px-5 py-8 sm:px-8"><x-operation-messages />
        <form method="POST" action="{{ route('settings.branding.update') }}" enctype="multipart/form-data" class="grid gap-8 lg:grid-cols-3" x-on:puntocancha-theme-changed.window="themeDark = document.documentElement.classList.contains('dark')" x-data="{ themeDark: document.documentElement.classList.contains('dark'), palette: @js(old('brand_palette', $club->brand_palette)), preview: @js($club->logoUrl()), uploaded: false, removed: @js((bool) old('remove_logo')), palettes: @js($palettes), get tone() { return this.palettes[this.palette] || this.palettes.original; }, get previewStyle() { return Object.entries(this.themeDark ? this.tone.dark : this.tone.light).map(([key, value]) => '--brand-' + key + ': ' + value).join(';'); } }">
            @csrf @method('PUT')
            <div class="space-y-6 lg:col-span-2">
                <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-lg font-bold">Logo del club</h2><p class="mt-2 text-sm text-slate-500">PNG, JPG o WebP. Máximo 2 MB; entre 64 y 2.000 píxeles por lado.</p>
                    <label for="logo" class="mt-5 block text-sm font-semibold">Seleccionar imagen</label>
                    <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="mt-3 block w-full rounded-xl border border-slate-300 p-3 text-sm file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-3 file:font-semibold dark:border-slate-700 dark:file:bg-slate-800 dark:file:text-white" @change="if ($event.target.files[0]) { if (uploaded) URL.revokeObjectURL(preview); preview = URL.createObjectURL($event.target.files[0]); uploaded = true; removed = false; }">
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    @if($club->logo_path)<label class="mt-5 flex items-center gap-3 text-sm"><input type="checkbox" name="remove_logo" value="1" x-model="removed" :disabled="uploaded" class="rounded border-slate-300 text-brand focus:ring-brand-ring"> Quitar el logo actual y usar el icono predeterminado</label>@endif
                    <x-input-error :messages="$errors->get('remove_logo')" class="mt-2" />
                </section>
                <section class="rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <h2 class="text-lg font-bold">Colores de tu club</h2><p class="mt-2 text-sm text-slate-500">Elige una paleta. Se adapta automáticamente al modo claro y oscuro de cada visitante.</p>
                    <fieldset class="mt-5 grid gap-3 sm:grid-cols-2"><legend class="sr-only">Paleta de colores</legend>
                        @foreach($palettes as $key => $palette)
                            <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition" :class="palette === @js($key) ? 'border-brand bg-brand/10' : 'border-slate-200 dark:border-slate-700'">
                                <input type="radio" name="brand_palette" value="{{ $key }}" x-model="palette" @checked(old('brand_palette', $club->brand_palette) === $key) class="text-brand focus:ring-brand-ring">
                                <span class="flex gap-1" aria-hidden="true"><span class="h-6 w-6 rounded-full" style="background-color: rgb({{ $palette['light']['primary'] }})"></span><span class="h-6 w-6 rounded-full" style="background-color: rgb({{ $palette['dark']['primary'] }})"></span></span><span class="text-sm font-semibold">{{ $palette['label'] }}</span>
                            </label>
                        @endforeach
                    </fieldset><x-input-error :messages="$errors->get('brand_palette')" class="mt-2" />
                </section>
                <div class="flex flex-wrap gap-4"><button class="inline-flex min-h-14 items-center justify-center rounded-xl bg-brand px-8 text-base font-bold text-brand-on hover:bg-brand-hover">Guardar personalización</button><a href="{{ route('settings.index') }}" class="inline-flex min-h-14 items-center justify-center rounded-xl border border-slate-300 px-6 font-semibold dark:border-slate-700">Volver</a></div>
            </div>
            <aside class="lg:sticky lg:top-6 lg:self-start"><p class="mb-3 text-sm font-semibold text-slate-500">Vista previa</p>
                <div :style="previewStyle" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center gap-3 border-b border-slate-200 p-5 dark:border-slate-800"><template x-if="preview && !removed"><img :src="preview" alt="Vista previa del logo" class="h-12 w-12 rounded-xl bg-white object-contain p-1"></template><span x-show="!preview || removed" class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand text-xl font-bold text-brand-on">PC</span><strong class="min-w-0 truncate">{{ $club->name }}</strong></div>
                    <div class="p-5"><span class="rounded-full bg-brand/10 px-3 py-1 text-xs font-semibold text-brand-content">Disponible para reservar</span><h3 class="mt-5 text-xl font-bold">Tu próximo partido empieza aquí</h3><p class="mt-3 text-sm text-slate-500">Canchas, horarios y una experiencia con los colores de tu club.</p><button type="button" class="mt-6 min-h-14 w-full rounded-xl bg-brand px-5 font-bold text-brand-on">Ver horarios disponibles</button></div>
                </div><p class="mt-4 text-xs leading-5 text-slate-500">La vista previa no guarda cambios. Presiona “Guardar personalización” para aplicarlos.</p>
            </aside>
        </form>
    </div>
</x-app-layout>
