<x-onboarding-shell :step="1" :subscription="$subscription">
<div x-data="{ stage: 1 }" x-init="$dispatch('setup-step', 1)">
    <section x-show="stage === 2" class="space-y-2"><h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">El juego empieza en tus canchas.</h3><p class="text-sm text-slate-600 dark:text-slate-400">Agrega el nombre, deporte y tarifa por hora de cada cancha. Puedes regresar a este paso sin duplicar las que ya guardaste.</p></section>
    <form method="POST" action="{{ route('onboarding.save-step1') }}"
        x-data="{ courts: {{ Illuminate\Support\Js::from(old('courts', $courts)) }}, savedKeys: {{ Illuminate\Support\Js::from(collect($courts)->where('saved', true)->pluck('key')->values()) }}, limit: {{ Illuminate\Support\Js::from($subscription?->plan?->court_limit) }}, addCourt() { this.courts.push({ key: crypto.randomUUID(), name: '', sport_type: 'padel', surface_type: '', is_covered: 0, default_hourly_rate: 20000, minimum_deposit: 0 }); } }"
        class="space-y-5">
        @csrf
        <section x-show="stage === 1" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
            <div><p class="text-sm font-semibold text-brand-content">01 / Tu club</p><h2 class="mt-2 text-3xl font-bold">¿Cómo se llama tu club?</h2><p class="mt-2 text-sm text-slate-500">Este nombre identificará tu recinto.</p></div>
            <label class="grid gap-2 font-medium">Nombre del club<input x-ref="clubName" name="club_name" value="{{ old('club_name', auth()->user()->club->name) }}" required maxlength="255" class="rounded-xl border-slate-200 bg-slate-50 text-lg dark:border-slate-700 dark:bg-slate-950" autocomplete="organization"></label>
            <button type="button" @click="if ($refs.clubName.reportValidity()) { stage = 2; $dispatch('setup-step', 2); }" class="min-h-14 w-full rounded-xl bg-brand px-6 font-bold text-brand-on focus:ring-2 focus:ring-brand-ring">Continuar a las canchas →</button>
        </section>
        <div x-show="stage === 2" x-cloak x-transition.opacity.duration.150ms class="space-y-5">

        <template x-for="(court, index) in courts" :key="court.key">
            <fieldset class="surface-card grid gap-4 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm sm:grid-cols-2 sm:p-6">
                <legend class="sr-only" x-text="'Cancha ' + (index + 1)"></legend>
                <div class="flex items-center justify-between sm:col-span-2"><p class="font-bold" x-text="'Cancha ' + (index + 1)"></p><button type="button" @click="courts.splice(index, 1)" :disabled="savedKeys.includes(court.key)" class="text-xs text-rose-700 dark:text-rose-300 underline disabled:text-slate-600 dark:disabled:text-slate-400 disabled:no-underline" x-text="savedKeys.includes(court.key) ? 'Guardada' : 'Quitar'"></button></div>
                <input type="hidden" :name="'courts[' + index + '][key]'" :value="court.key">
                <label class="grid gap-1 text-sm">Nombre<input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" :name="'courts[' + index + '][name]'" x-model="court.name" maxlength="255" placeholder="Cancha 1" required></label>
                <label class="grid gap-1 text-sm">Deporte<select class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" :name="'courts[' + index + '][sport_type]'" x-model="court.sport_type"><option value="padel">Pádel</option><option value="futbolito">Futbolito</option><option value="tenis">Tenis</option></select></label>
                <label class="grid gap-1 text-sm">Superficie<input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" :name="'courts[' + index + '][surface_type]'" x-model="court.surface_type" maxlength="100" placeholder="Pasto sintético, arcilla..." required></label>
                <label class="grid gap-1 text-sm">¿Es techada?<select class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" :name="'courts[' + index + '][is_covered]'" x-model="court.is_covered"><option value="0">No</option><option value="1">Sí</option></select></label>
                <label class="grid gap-1 text-sm sm:col-span-2">Tarifa por hora (CLP)<input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" type="number" min="1" max="99999999" step="1" :name="'courts[' + index + '][default_hourly_rate]'" x-model="court.default_hourly_rate" required></label>
                <label class="grid gap-2 rounded-xl border-2 border-brand/30 bg-brand/10 p-4 text-sm sm:col-span-2"><span class="font-bold text-brand-content">Abono m&iacute;nimo para reservar (CLP)</span><input type="number" min="0" max="99999999" step="1" :name="'courts[' + index + '][minimum_deposit]'" x-model="court.minimum_deposit" required class="rounded-xl border-slate-200 bg-white text-slate-900 focus:ring-brand-ring dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"><span class="text-xs text-slate-500">0 exige pago total. Ejemplo: 10000 permite abonar $10.000 o pagar el turno completo.</span></label>
            </fieldset>
        </template>
        <noscript><p role="alert" class="rounded-lg bg-amber-400/5 p-4 text-amber-800 dark:text-amber-200">Activa JavaScript para agregar y configurar tus canchas.</p></noscript>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <button type="button" @click="addCourt()" :disabled="limit !== null && courts.length >= limit" class="rounded-lg border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 px-5 py-3 text-sm font-semibold disabled:opacity-40">+ Agregar cancha</button>
            <p class="text-xs text-slate-600 dark:text-slate-400" x-text="limit === null ? courts.length + ' canchas · Sin límite de plan' : courts.length + ' de ' + limit + ' canchas'"></p>
        </div>
        <div class="border-t border-slate-200 dark:border-slate-800 pt-5"><button type="button" @click="stage = 1; $dispatch('setup-step', 1)" class="mr-5 min-h-12 text-sm underline">Volver al nombre</button><button type="submit" @disabled(! $subscription?->isCurrent() && auth()->user()->club->access_override !== 'allow') class="w-full  bg-brand text-brand-on    font-bold text-brand-on disabled:opacity-40 sm:w-auto inline-flex min-h-14 items-center justify-center rounded-xl px-7 py-4 text-base font-bold shadow-sm focus-visible:ring-2 focus-visible:ring-brand-ring focus-visible:ring-offset-2">Guardar y continuar →</button></div>
        </div>
    </form>
</div>
</x-onboarding-shell>
