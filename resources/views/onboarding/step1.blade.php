<x-onboarding-shell :step="1" :subscription="$subscription">
    <section class="space-y-2"><h3 class="text-2xl font-bold text-slate-900">El juego empieza en tus canchas.</h3><p class="text-sm text-gray-500">Agrega el nombre, deporte y tarifa por hora de cada cancha. Puedes regresar a este paso sin duplicar las que ya guardaste.</p></section>
    <form method="POST" action="{{ route('onboarding.save-step1') }}"
        x-data="{ courts: {{ Illuminate\Support\Js::from(old('courts', $courts)) }}, savedKeys: {{ Illuminate\Support\Js::from(collect($courts)->where('saved', true)->pluck('key')->values()) }}, limit: {{ Illuminate\Support\Js::from($subscription?->plan?->court_limit) }}, addCourt() { this.courts.push({ key: crypto.randomUUID(), name: '', sport_type: 'padel', surface_type: '', is_covered: 0, default_hourly_rate: 20000 }); } }"
        class="space-y-5">
        @csrf
        <template x-for="(court, index) in courts" :key="court.key">
            <fieldset class="grid gap-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:grid-cols-2 sm:p-6">
                <legend class="sr-only" x-text="'Cancha ' + (index + 1)"></legend>
                <div class="flex items-center justify-between sm:col-span-2"><p class="font-bold" x-text="'Cancha ' + (index + 1)"></p><button type="button" @click="courts.splice(index, 1)" :disabled="savedKeys.includes(court.key)" class="text-xs text-red-700 underline disabled:text-gray-400 disabled:no-underline" x-text="savedKeys.includes(court.key) ? 'Guardada' : 'Quitar'"></button></div>
                <input type="hidden" :name="'courts[' + index + '][key]'" :value="court.key">
                <label class="grid gap-1 text-sm">Nombre<input class="rounded-lg border-gray-300" :name="'courts[' + index + '][name]'" x-model="court.name" maxlength="255" placeholder="Cancha 1" required></label>
                <label class="grid gap-1 text-sm">Deporte<select class="rounded-lg border-gray-300" :name="'courts[' + index + '][sport_type]'" x-model="court.sport_type"><option value="padel">Pádel</option><option value="futbolito">Futbolito</option><option value="tenis">Tenis</option></select></label>
                <label class="grid gap-1 text-sm">Superficie<input class="rounded-lg border-gray-300" :name="'courts[' + index + '][surface_type]'" x-model="court.surface_type" maxlength="100" placeholder="Pasto sintético, arcilla..." required></label>
                <label class="grid gap-1 text-sm">¿Es techada?<select class="rounded-lg border-gray-300" :name="'courts[' + index + '][is_covered]'" x-model="court.is_covered"><option value="0">No</option><option value="1">Sí</option></select></label>
                <label class="grid gap-1 text-sm sm:col-span-2">Tarifa por hora (CLP)<input class="rounded-lg border-gray-300" type="number" min="1" max="99999999" step="1" :name="'courts[' + index + '][default_hourly_rate]'" x-model="court.default_hourly_rate" required></label>
            </fieldset>
        </template>
        <noscript><p role="alert" class="rounded-lg bg-amber-50 p-4 text-amber-900">Activa JavaScript para agregar y configurar tus canchas.</p></noscript>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <button type="button" @click="addCourt()" :disabled="limit !== null && courts.length >= limit" class="rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-semibold disabled:opacity-40">+ Agregar cancha</button>
            <p class="text-xs text-gray-500" x-text="limit === null ? courts.length + ' canchas · Sin límite de plan' : courts.length + ' de ' + limit + ' canchas'"></p>
        </div>
        <div class="border-t border-gray-200 pt-5"><button type="submit" @disabled(! $subscription?->isCurrent()) class="w-full rounded-lg bg-slate-950 px-6 py-4 text-sm font-bold text-lime-300 disabled:opacity-40 sm:w-auto">Guardar y continuar →</button></div>
    </form>
</x-onboarding-shell>
