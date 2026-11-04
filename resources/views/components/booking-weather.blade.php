@props(['club', 'date'])
<section data-reveal class="overflow-hidden rounded-xl border border-blue-400/20 bg-gradient-to-br from-sky-900 to-blue-600 p-4 text-white shadow-sm" aria-label="Pronóstico del tiempo">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-sm font-semibold">Clima · {{ $club->city }} <span class="ml-1 font-normal text-blue-100">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</span></h2>
        <a href="https://open-meteo.com/" target="_blank" rel="noopener noreferrer" class="rounded text-[10px] text-blue-100 underline underline-offset-2">Open-Meteo</a>
    </div>
    <p x-show="loading" role="status" class="mt-3 text-xs text-blue-100">Consultando el pronóstico…</p>
    <p x-show="!loading && !forecast" x-text="message" class="mt-3 text-xs text-blue-100"></p>
    <template x-if="forecast">
        <div>
            <div class="mt-3 grid items-center gap-4 md:grid-cols-[auto_minmax(0,1fr)]">
                <div class="flex items-center gap-3 md:min-w-52">
                    <span class="text-3xl" x-text="summary?.icon || '🌡️'" :aria-label="summary?.label"></span>
                    <div><p class="text-2xl font-bold" x-text="summary && summary.temperature !== null ? summary.temperature + '°' : '—'"></p><p class="text-[11px] text-blue-100" x-text="summary ? summary.label + ' · ' + summary.time : 'Fuera del horario del club'"></p><p class="mt-1 text-[10px] text-blue-100" x-text="summary ? 'Viento ' + (summary.wind ?? '—') + ' km/h · Humedad ' + (summary.humidity ?? '—') + '%' : ''"></p></div>
                </div>
                <div class="flex min-w-0 gap-2 overflow-x-auto pb-1" tabindex="0" aria-label="Clima durante el horario de apertura">
                    <template x-for="hour in hours" :key="hour.time"><div class="surface-card min-w-16 shrink-0 rounded-lg bg-white/15 px-2 py-2 text-center"><p class="text-[10px] text-blue-100" x-text="hour.time"></p><p class="mt-1 text-sm font-semibold"><span x-text="hour.icon" :aria-label="hour.label"></span> <span x-text="(hour.temperature ?? '—') + '°'"></span></p><p class="mt-1 text-[10px] text-blue-100" x-text="'💧 ' + (hour.rain ?? '—') + '%'"></p></div></template>
                </div>
            </div>
            <div class="mt-2 flex flex-wrap items-center justify-between gap-1 text-[10px] text-blue-100"><p x-text="bestHour ? 'Menor prob. de lluvia: ' + bestHour.time + ' (' + bestHour.rain + '%)' : ''"></p><details><summary class="cursor-pointer rounded underline underline-offset-2">Detalles del pronóstico</summary><p class="mt-2 max-w-sm leading-relaxed" x-text="'Actualizado ' + forecast.updated_at + ' · Hora de Chile continental. Estimación por ciudad, puede cambiar. Ubicaciones: GeoNames.'"></p></details></div>
        </div>
    </template>
    <noscript><p class="mt-3 text-xs">Activa JavaScript para consultar el pronóstico.</p></noscript>
</section>
