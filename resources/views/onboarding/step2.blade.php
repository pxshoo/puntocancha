<x-onboarding-shell :step="2" :subscription="$subscription">
    <section data-reveal class="space-y-2"><h3 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Tu horario. Tu quiosco. Todo listo.</h3><p class="text-sm text-slate-600 dark:text-slate-400">Define un horario diario para tu club y la duración de los turnos que se mostrarán en tu enlace público.</p></section>
    <form method="POST" action="{{ route('onboarding.complete') }}" class="space-y-6">
        @csrf
        <fieldset class="surface-card grid gap-5 rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm sm:grid-cols-2">
            <legend class="sr-only">Horarios del club</legend>
            <x-time-picker name="opens_at" label="Hora de apertura" :value="old('opens_at', substr($club->opens_at ?? '08:00', 0, 5))" />
            <x-time-picker name="closes_at" label="Hora de cierre" :value="old('closes_at', substr($club->closes_at ?? '23:00', 0, 5))" />
            <label class="grid gap-2 text-sm font-medium sm:col-span-2">Duración de los turnos<select class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring" name="slot_minutes"><option value="60" @selected(old('slot_minutes', $club->slot_minutes) == 60)>60 minutos</option><option value="90" @selected(old('slot_minutes', $club->slot_minutes) == 90)>90 minutos</option></select></label>
            <p class="text-xs text-slate-600 dark:text-slate-400 sm:col-span-2">Horario de Chile (America/Santiago). La hora de cierre debe ser posterior a la apertura, dentro del mismo día.</p>
        </fieldset>
        <fieldset class="rounded-2xl border border-brand/20 bg-emerald-500/5 p-6">
            <input type="hidden" name="seed_products" value="0">
            <legend class="sr-only">Catálogo inicial</legend>
            <label class="flex items-start gap-3"><input type="checkbox" name="seed_products" value="1" @checked(old('seed_products', true)) class="mt-1 rounded border-slate-200 dark:border-slate-800 text-emerald-600 focus:ring-emerald-600 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring"><span><span class="block text-sm font-semibold text-emerald-800 dark:text-emerald-200">Cargar productos iniciales para el quiosco</span><span class="mt-2 block text-sm text-emerald-800 dark:text-emerald-200">Agua mineral, isotónicas, barras de cereal, papas fritas y tarros de pelotas de pádel y tenis.</span></span></label>
            <p class="mt-4 text-xs leading-relaxed text-emerald-800 dark:text-emerald-200">Se crean con stock y costo en cero, y precios de venta sugeridos. Revisa los precios, registra los costos reales y carga tus existencias desde Inventario antes de vender. Los productos que ya tengas se conservan.</p>
        </fieldset>
        <section data-reveal class="surface-card rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5"><h4 class="text-sm font-bold">Tu enlace público, listo para compartir</h4><p class="mt-2 break-all text-xs text-slate-600 dark:text-slate-400">{{ route('public.bookings', $club->slug) }}</p><p class="mt-2 text-xs text-slate-600 dark:text-slate-400">Se habilitará al completar este paso. Mostrará disponibilidad sin revelar datos de los jugadores.</p></section>
        <div class="flex flex-wrap items-center justify-between gap-4"><a class="text-sm text-slate-600 dark:text-slate-400 underline" href="{{ route('onboarding.step1') }}">← Volver a las canchas</a><button type="submit" @disabled(! $subscription?->isCurrent()) class=" bg-brand text-brand-on    font-bold text-brand-on disabled:opacity-40 inline-flex min-h-14 items-center justify-center rounded-xl px-7 py-4 text-base font-bold shadow-sm focus-visible:ring-2 focus-visible:ring-brand-ring focus-visible:ring-offset-2">Completar y abrir mi agenda →</button></div>
    </form>
</x-onboarding-shell>
