<x-onboarding-shell :step="2" :subscription="$subscription">
    <section class="space-y-2"><h3 class="text-2xl font-bold text-slate-900">Tu horario. Tu quiosco. Todo listo.</h3><p class="text-sm text-gray-500">Define un horario diario para tu club y la duración de los turnos que se mostrarán en tu enlace público.</p></section>
    <form method="POST" action="{{ route('onboarding.complete') }}" class="space-y-6">
        @csrf
        <fieldset class="grid gap-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm sm:grid-cols-2">
            <legend class="sr-only">Horarios del club</legend>
            <label class="grid gap-2 text-sm font-medium">Hora de apertura<input type="time" class="rounded-lg border-gray-300" name="opens_at" value="{{ old('opens_at', substr($club->opens_at ?? '08:00', 0, 5)) }}" required></label>
            <label class="grid gap-2 text-sm font-medium">Hora de cierre<input type="time" class="rounded-lg border-gray-300" name="closes_at" value="{{ old('closes_at', substr($club->closes_at ?? '23:00', 0, 5)) }}" required></label>
            <label class="grid gap-2 text-sm font-medium sm:col-span-2">Duración de los turnos<select class="rounded-lg border-gray-300" name="slot_minutes"><option value="60" @selected(old('slot_minutes', $club->slot_minutes) == 60)>60 minutos</option><option value="90" @selected(old('slot_minutes', $club->slot_minutes) == 90)>90 minutos</option></select></label>
            <p class="text-xs text-gray-500 sm:col-span-2">Horario de Chile (America/Santiago). La hora de cierre debe ser posterior a la apertura, dentro del mismo día.</p>
        </fieldset>
        <fieldset class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6">
            <input type="hidden" name="seed_products" value="0">
            <legend class="sr-only">Catálogo inicial</legend>
            <label class="flex items-start gap-3"><input type="checkbox" name="seed_products" value="1" @checked(old('seed_products', true)) class="mt-1 rounded border-gray-300 text-emerald-600 focus:ring-emerald-600"><span><span class="block text-sm font-semibold text-emerald-950">Cargar productos iniciales para el quiosco</span><span class="mt-2 block text-sm text-emerald-900">Agua mineral, isotónicas, barras de cereal, papas fritas y tarros de pelotas de pádel y tenis.</span></span></label>
            <p class="mt-4 text-xs leading-relaxed text-emerald-900">Se crean con stock y costo en cero, y precios de venta sugeridos. Revisa los precios, registra los costos reales y carga tus existencias desde Inventario antes de vender. Los productos que ya tengas se conservan.</p>
        </fieldset>
        <section class="rounded-xl border border-gray-200 bg-white p-5"><h4 class="text-sm font-bold">Tu enlace público, listo para compartir</h4><p class="mt-2 break-all text-xs text-gray-500">{{ route('public.bookings', $club->slug) }}</p><p class="mt-2 text-xs text-gray-500">Se habilitará al completar este paso. Mostrará disponibilidad sin revelar datos de los jugadores.</p></section>
        <div class="flex flex-wrap items-center justify-between gap-4"><a class="text-sm text-gray-600 underline" href="{{ route('onboarding.step1') }}">← Volver a las canchas</a><button type="submit" @disabled(! $subscription?->isCurrent()) class="rounded-lg bg-slate-950 px-6 py-4 text-sm font-bold text-lime-300 disabled:opacity-40">Completar y abrir mi agenda →</button></div>
    </form>
</x-onboarding-shell>
