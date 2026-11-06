<x-onboarding-shell :step="2" :subscription="$subscription">
    <div x-data="{ stage: {{ $errors->hasAny(['city', 'address', 'phone', 'marketplace_enabled']) ? 5 : 3 }}, seed: {{ old('seed_products', 1) ? 'true' : 'false' }}, next() { const fields = Array.from(this.$refs.hours.querySelectorAll('input, select')); const invalid = fields.find(field => !field.checkValidity()); if (invalid) { invalid.reportValidity(); return; } this.stage = 4; this.$dispatch('setup-step', 4); } }" x-init="$dispatch('setup-step', stage)">
<section x-show="stage === 3" class="space-y-2"><h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Tu horario. Tu quiosco. Todo listo.</h3><p class="text-sm text-slate-600 dark:text-slate-400">Define un horario diario para tu club y la duración de los turnos que se mostrarán en tu enlace público.</p></section>
    <form method="POST" action="{{ route('onboarding.complete') }}" class="space-y-6">
        @csrf
        <div x-show="stage === 3" x-ref="hours" class="space-y-5">
        <fieldset class="surface-card grid gap-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm sm:grid-cols-2">
            <legend class="sr-only">Horarios del club</legend>
            <x-time-picker name="opens_at" label="Hora de apertura" :value="old('opens_at', substr($club->opens_at ?? '08:00', 0, 5))" />
            <x-time-picker name="closes_at" label="Hora de cierre" :value="old('closes_at', substr($club->closes_at ?? '23:00', 0, 5))" />
            <label class="grid gap-2 text-sm font-medium sm:col-span-2">Duración de los turnos<select class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" name="slot_minutes"><option value="60" @selected(old('slot_minutes', $club->slot_minutes) == 60)>60 minutos</option><option value="90" @selected(old('slot_minutes', $club->slot_minutes) == 90)>90 minutos</option></select></label>
            <p class="text-xs text-slate-600 dark:text-slate-400 sm:col-span-2">Horario de Chile (America/Santiago). La hora de cierre debe ser posterior a la apertura, dentro del mismo día.</p>
        </fieldset>
        <button type="button" @click="next()" class="min-h-14 w-full rounded-xl bg-brand px-6 font-bold text-brand-on">Continuar al inventario →</button>
        <a href="{{ route('onboarding.step1') }}" class="inline-block py-3 text-sm underline">Volver a las canchas</a>
        </div>
        <div x-show="stage === 4" x-cloak x-transition.opacity.duration.150ms class="space-y-5">
        <h2 class="text-3xl font-bold">Prepara tu inventario</h2>
        <fieldset class="rounded-2xl border border-brand/20 bg-emerald-500/5 p-6">
            <input type="hidden" name="seed_products" value="0">
            <legend class="sr-only">Catálogo inicial</legend>
            <label class="flex items-start gap-3"><input type="checkbox" x-model="seed" name="seed_products" value="1" @checked(old('seed_products', true)) class="mt-1 rounded border-slate-200 dark:border-slate-800 text-emerald-600 focus:ring-emerald-600 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring"><span><span class="block text-sm font-semibold text-emerald-800 dark:text-emerald-200">Cargar productos iniciales para el quiosco</span><span class="mt-2 block text-sm text-emerald-800 dark:text-emerald-200">Agua mineral, isotónicas, barras de cereal, papas fritas y tarros de pelotas de pádel y tenis.</span></span></label>
            <p class="mt-4 text-xs leading-relaxed text-emerald-800 dark:text-emerald-200">Se crean con stock y costo en cero, y precios de venta sugeridos. Revisa los precios, registra los costos reales y carga tus existencias desde Inventario antes de vender. Los productos que ya tengas se conservan.</p>
        </fieldset>
        <div class="flex items-center justify-between gap-4"><button type="button" @click="stage = 3; $dispatch('setup-step', 3)" class="min-h-12 underline">Volver</button><button type="button" @click="stage = 5; $dispatch('setup-step', 5)" class="min-h-14 rounded-xl bg-brand px-6 font-bold text-brand-on">Revisar y finalizar →</button></div>
        </div>
        <div x-show="stage === 5" x-cloak x-transition.opacity.duration.150ms class="space-y-6">
        <h2 class="text-3xl font-bold">Tu club, listo para jugar.</h2>
        <fieldset class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900" x-data="{ publish: {{ old('marketplace_enabled', $club->marketplace_enabled) ? 'true' : 'false' }} }">
            <legend class="sr-only">Ubicación y publicación</legend>
            <div><h3 class="text-lg font-bold">Que los jugadores encuentren tu club</h3><p class="mt-1 text-sm text-slate-500">Completa tu ubicación y activa la publicación para aparecer en Explorar.</p></div>
            <label class="grid gap-2 text-sm font-medium">Ciudad / Comuna<input name="city" value="{{ old('city', $club->city) }}" maxlength="100" :required="publish" autocomplete="address-level2" placeholder="Puerto Montt" class="rounded-xl border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950"></label>
            <label class="grid gap-2 text-sm font-medium">Dirección (calle y número)<input name="address" value="{{ old('address', $club->address) }}" maxlength="255" :required="publish" autocomplete="street-address" class="rounded-xl border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950"></label>
            <label class="grid gap-2 text-sm font-medium">Teléfono / WhatsApp<input type="tel" name="phone" value="{{ old('phone', $club->phone ?: auth()->user()->phone) }}" maxlength="25" :required="publish" autocomplete="tel" placeholder="+56912345678" class="rounded-xl border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950"></label>
            <input type="hidden" name="marketplace_enabled" value="0">
            <label class="flex items-start gap-3 rounded-xl bg-emerald-500/10 p-4"><input type="checkbox" name="marketplace_enabled" value="1" x-model="publish" class="mt-1 rounded text-emerald-600 focus:ring-emerald-500"><span><span class="block font-bold">Publicar mi club en Explorar</span><span class="mt-1 block text-sm text-slate-500">Al finalizar, los jugadores podrán encontrar tus canchas y reservar.</span></span></label>
        </fieldset>

        <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-6"><p class="text-xl font-bold">{{ $club->name }}</p><p class="mt-3">{{ $club->courts()->count() }} cancha(s) configurada(s)</p><p class="mt-2 text-sm" x-text="'Horario: ' + ($refs.hours.querySelector('[name=opens_at]').value || '') + ' a ' + ($refs.hours.querySelector('[name=closes_at]').value || '')"></p><p class="mt-2 text-sm" x-text="seed ? 'Inventario: catálogo inicial incluido (sin stock).' : 'Inventario: lo agregarás después.'"></p></div>
        <section class="surface-card rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5"><h4 class="text-sm font-bold">Tu enlace público, listo para compartir</h4><p class="mt-2 break-all text-xs text-slate-600 dark:text-slate-400">{{ route('public.bookings', $club->slug) }}</p><p class="mt-2 text-xs text-slate-600 dark:text-slate-400">Se habilitará al completar este paso. Mostrará disponibilidad sin revelar datos de los jugadores.</p></section>
        <div class="flex flex-wrap items-center justify-between gap-4"><button type="button" @click="stage = 4; $dispatch('setup-step', 4)" class="min-h-12 underline">Volver al inventario</button><button type="submit" @disabled(! $subscription?->isCurrent() && auth()->user()->club->access_override !== 'allow') class=" bg-brand text-brand-on    font-bold text-brand-on disabled:opacity-40 inline-flex min-h-14 items-center justify-center rounded-xl px-7 py-4 text-base font-bold shadow-sm focus-visible:ring-2 focus-visible:ring-brand-ring focus-visible:ring-offset-2">Completar y abrir mi agenda →</button></div>
        </div>
    </form>
</div>
</x-onboarding-shell>
