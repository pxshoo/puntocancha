@props(['address' => '', 'city' => '', 'latitude' => null, 'longitude' => null])
<section data-google-maps-key="{{ config('services.google_maps.key') }}" data-google-maps-id="{{ config('services.google_maps.map_id') }}" class="rounded-xl border border-brand/20 bg-brand/5 p-4 sm:col-span-2" aria-label="Ubicación del club">
    <input type="hidden" name="latitude" :value="latitude" value="{{ old('latitude', $latitude ?? '') }}">
    <input type="hidden" name="longitude" :value="longitude" value="{{ old('longitude', $longitude ?? '') }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h3 class="text-sm font-semibold">Ubicación del club</h3><p class="mt-1 text-xs text-slate-500 dark:text-slate-400" x-text="hasPoint ? 'Punto seleccionado. Guarda los cambios para confirmarlo.' : 'Marca la entrada de tu recinto en el mapa.'"></p></div>
        <button type="button" x-ref="openMap" data-google-maps-key="{{ config('services.google_maps.key') }}" data-google-maps-id="{{ config('services.google_maps.map_id') }}" @click="openMap()" class="action-feedback min-h-11 rounded-xl bg-brand px-5 py-3 text-sm font-bold text-brand-on focus:outline-none focus:ring-2 focus:ring-brand-ring">Localizar en el mapa</button>
    </div>
    <p x-show="locationNotice" x-text="locationNotice" role="status" class="mt-3 text-xs text-slate-600 dark:text-slate-300"></p>
    <div x-show="hasPoint" x-cloak class="mt-3 flex flex-wrap items-center gap-3 text-xs">
        <span x-text="latitude + ', ' + longitude"></span>
        <a :href="locationMapUrl" target="_blank" rel="noopener noreferrer" class="underline">Ver punto</a>
        <button type="button" @click="clearPoint()" class="text-rose-500 underline">Quitar punto</button>
    </div>
    <template x-teleport="body">
        <div x-show="mapOpen" x-cloak @keydown.escape.window="if (mapOpen) closeMap()"  class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/75 p-3 backdrop-blur-sm" @click.self="closeMap()">
            <section role="dialog" aria-modal="true" aria-labelledby="location-map-title" @keydown.tab="trapMapFocus($event)" class="w-full max-w-3xl overflow-hidden rounded-2xl border border-slate-200 bg-white text-slate-900 shadow-2xl dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
                <header class="flex items-start justify-between gap-3 p-4">
                    <div><h2 id="location-map-title" class="text-lg font-bold">Marca tu ubicación</h2><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Arrastra para moverte y toca el mapa para colocar el punto.</p></div>
                    <button type="button" @click="closeMap()" class="rounded-lg p-2 focus:ring-2 focus:ring-brand-ring" aria-label="Cerrar mapa">✕</button>
                </header>
                <div class="relative">
                    <div x-ref="mapCanvas" class="h-[45vh] min-h-64 max-h-[440px] bg-slate-100" aria-label="Selecciona la ubicaci&oacute;n en Google Maps"></div>
                    <p x-show="loadingMap" role="status" class="absolute inset-0 flex items-center justify-center bg-white/90 text-sm text-slate-700">Cargando Google Maps...</p>
                </div>
                <footer class="grid gap-3 p-4">
                    <p x-show="mapError" x-text="mapError" role="status" class="text-xs text-rose-500"></p>
                    <p class="text-xs text-slate-500 dark:text-slate-400" x-text="hasPoint ? latitude + ', ' + longitude : 'Selecciona un punto para continuar.'"></p>
                    <button type="button" @click="searchAddress()" :disabled="!locationReady || loadingMap" class="text-left text-sm font-semibold text-brand-content underline">Buscar la dirección escrita</button>
                    <div class="flex flex-wrap justify-between gap-3">
                        <button type="button" @click="locateMe()" :disabled="locating || loadingMap || !!mapError" class="min-h-11 rounded-xl border border-slate-300 px-4 text-sm font-semibold focus:ring-2 focus:ring-brand-ring dark:border-slate-600" x-text="locating ? 'Localizando…' : 'Usar mi ubicación'"></button>
                        <button type="button" @click="applyPoint()" :disabled="!hasPoint || resolving" class="min-h-11 rounded-xl bg-brand px-5 text-sm font-bold text-brand-on disabled:opacity-40 focus:ring-2 focus:ring-brand-ring" x-text="resolving ? 'Buscando dirección...' : 'Usar este punto'">Usar este punto</button>
                    </div>
                    <p class="text-[11px] text-slate-500">Revisa la dirección encontrada y pulsa Guardar cambios. Mapa y direcciones de Google Maps.</p>
                </footer>
            </section>
        </div>
    </template>
</section>
