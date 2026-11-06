<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Agenda de canchas</h2></x-slot>
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
        <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-600 p-6 text-white shadow-xl shadow-emerald-900/10 sm:p-8">
            <div aria-hidden="true" class="pointer-events-none absolute -right-12 -top-20 h-72 w-72 rounded-full border-[40px] border-white/10"></div>
            <div class="relative flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
                <div><span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold"><span class="h-2 w-2 rounded-full bg-lime-300"></span>{{ auth()->user()->club->name }}</span><h2 class="mt-4 text-3xl font-extrabold tracking-tight sm:text-4xl">Tu cancha. Tu próxima jugada.</h2><p class="mt-2 text-sm text-emerald-100">Organiza los turnos y recibe a tus jugadores.</p></div>
                <a href="#nueva-reserva" class="inline-flex min-h-14 shrink-0 items-center justify-center gap-3 rounded-xl bg-white px-6 font-bold text-emerald-900 shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-emerald-700"><span class="text-2xl" aria-hidden="true">+</span> Nueva reserva</a>
            </div>
        </section>
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach ([['Reservas del día', $bookings->count(), 'bg-emerald-500', 'Turnos registrados'], ['Canchas activas', $courts->count(), 'bg-indigo-500', 'Listas para jugar'], ['Valor de las reservas', '$'.number_format($bookings->sum('total_price'), 0, ',', '.'), 'bg-amber-500', 'Total agendado, no saldo de caja']] as [$label, $value, $color, $hint])
            <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md dark:border-slate-800 dark:bg-slate-900"><div class="absolute inset-y-0 left-0 w-1 {{ $color }}"></div><p class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ $label }}</p><p class="mt-2 text-3xl font-extrabold text-slate-900 dark:text-white">{{ $value }}</p><p class="mt-1 text-xs text-slate-500">{{ $hint }}</p></div>
            @endforeach
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/30">
            <div><h3 class="font-bold text-emerald-900 dark:text-emerald-200">Enlace público de tu club</h3><p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">Comparte tus horarios con los jugadores.</p></div>
            <div class="flex flex-wrap gap-3"><a href="{{ route('public.bookings', auth()->user()->club->slug) }}" class="inline-flex min-h-12 items-center rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-700 focus:ring-2 focus:ring-emerald-500">Ver portal ↗</a><a href="{{ route('courts.index') }}" class="inline-flex min-h-12 items-center rounded-xl border border-emerald-300 bg-white px-5 text-sm font-semibold text-emerald-800 transition-colors hover:bg-emerald-100 dark:border-emerald-800 dark:bg-slate-900 dark:text-emerald-200">Administrar canchas y precios →</a></div>
        </div>
        @if (session('success')) <p role="status">{{ session('success') }}</p> @endif
        @if (session('error')) <p role="alert">{{ session('error') }}</p> @endif
        @if ($errors->any())
            <ul role="alert">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
        @endif
        <form method="GET" action="{{ route('bookings.index') }}" class="flex flex-wrap items-end gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Fecha <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" type="date" name="date" value="{{ $selectedDate }}" required></label>
            <x-primary-button>Ver reservas</x-primary-button>
        </form>
        <section data-reveal class="surface-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-xl space-y-4">
            <div class="flex items-center justify-between gap-3"><h3 class="text-lg font-bold">Agenda del {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}</h3><span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ $bookings->count() }} reservas</span></div>
            @forelse ($bookings as $booking)
                <article class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 transition-all duration-200 hover:shadow-md dark:border-emerald-900/60 dark:bg-emerald-950/20"><div class="flex items-center gap-4"><div class="rounded-xl bg-emerald-600 px-3 py-2 text-center text-sm font-bold text-white">{{ $booking->start_time->format('H:i') }}<span class="block text-xs font-normal text-emerald-100">{{ $booking->end_time->format('H:i') }}</span></div><div><h4 class="font-bold">{{ $booking->court->name }}</h4><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $booking->customer_name }}</p></div></div><strong class="text-lg text-emerald-700 dark:text-emerald-300">${{ number_format($booking->total_price, 0, ',', '.') }}</strong></article>
                @if(auth()->user()->role === 'club_admin' && $booking->payment?->status === 'manual_review')
                <details class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20"><summary class="cursor-pointer font-semibold text-amber-800 dark:text-amber-200">Verificar pago de esta reserva · &#36;{{ number_format($booking->payment->amount, 0, ',', '.') }}</summary><p class="mt-3 break-all text-xs">Orden: {{ $booking->payment->buy_order }}</p><form method="POST" action="{{ route('bookings.payment.review', $booking->payment) }}" class="mt-4 grid gap-3 sm:grid-cols-2">@csrf<input type="hidden" name="action" value="approve"><label class="grid gap-2 text-sm">Monto comprobado<input name="amount" type="number" min="1" required value="{{ $booking->payment->amount }}" class="rounded-xl dark:bg-slate-950"></label><label class="grid gap-2 text-sm">Autorización de Transbank<input name="authorization" maxlength="50" required class="rounded-xl dark:bg-slate-950"></label><label class="flex items-start gap-3 text-sm sm:col-span-2"><input type="checkbox" name="confirmed_bank" value="1" required class="mt-1 rounded text-brand">Comprobé la venta y el monto en Transbank.</label><x-primary-button>Confirmar pago y reserva</x-primary-button></form><form method="POST" action="{{ route('bookings.payment.review', $booking->payment) }}" class="mt-3">@csrf<input type="hidden" name="action" value="reject"><button class="min-h-12 rounded-xl border border-rose-300 px-4 text-sm font-semibold text-rose-700 dark:text-rose-300">Rechazar y liberar turno</button></form></details>
                @endif
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-5 py-8 text-center dark:border-slate-700 dark:bg-slate-950/40"><svg class="mx-auto mb-3 h-10 w-10 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="3"/><path d="M16 3v4M8 3v4M3 11h18m-9 3v4m-2-2h4"/></svg><p class="font-bold">El día está listo para jugar</p><p class="mt-1 text-sm text-slate-500">No hay reservas para esta fecha.</p><a href="#nueva-reserva" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-emerald-700 dark:text-emerald-300">Crear la primera reserva →</a></div>
            @endforelse
        </section>
        @if ($courts->isEmpty())
            <p>No hay canchas activas configuradas. Agrega una cancha y su tarifa para comenzar a reservar.</p>
        @else
            <form id="nueva-reserva" method="POST" action="{{ route('bookings.store') }}" class="surface-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-xl grid gap-5 sm:grid-cols-2">
                @csrf
                <div class="-mx-6 -mt-6 flex items-center gap-3 rounded-t-xl border-b border-indigo-100 bg-indigo-50 px-6 py-5 dark:border-indigo-900 dark:bg-indigo-950/40 sm:col-span-2"><span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-2xl text-white">+</span><div><h3 class="text-lg font-bold text-slate-900 dark:text-white">Nueva reserva</h3><p class="text-xs text-indigo-600 dark:text-indigo-300">Elige la cancha y completa los datos del jugador.</p></div></div>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Cancha <select class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" name="court_id" required>
                    @foreach ($courts as $court)
                        <option value="{{ $court->id }}" @selected(old('court_id') == $court->id)>{{ $court->name }} (${{ number_format($court->default_hourly_rate, 0, ',', '.') }}/hora)</option>
                    @endforeach
                </select></label>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Nombre <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" name="customer_name" value="{{ old('customer_name') }}" maxlength="255" required></label>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Correo de confirmaci&oacute;n (opcional)<input type="email" name="customer_email" maxlength="255" value="{{ old('customer_email') }}" class="rounded-lg border-slate-200 bg-slate-50 text-slate-900 focus:ring-brand-ring dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100"></label>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Teléfono <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" name="customer_phone" value="{{ old('customer_phone') }}" maxlength="50" required></label>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Fecha <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" type="date" name="booking_date" value="{{ old('booking_date', $selectedDate) }}" required></label>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Hora <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" type="time" name="start_time" value="{{ old('start_time') }}" required></label>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Duración (horas) <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" type="number" name="duration_hours" min="1" max="3" value="{{ old('duration_hours', 1) }}" required></label>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Medio de pago <select class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" name="payment_method" required>
                    @foreach (['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'webpay' => 'Webpay', 'pos' => 'Tarjeta POS'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                    @endforeach
                </select></label>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Abono <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" type="number" name="deposit_paid" min="0" step="0.01" value="{{ old('deposit_paid', 0) }}"></label>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Notas <textarea class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" name="notes">{{ old('notes') }}</textarea></label>
                <div class="border-t border-slate-100 pt-5 dark:border-slate-800 sm:col-span-2"><x-primary-button class="w-full justify-center sm:w-auto">Confirmar reserva →</x-primary-button></div>
            </form>
        @endif
    </div>
</x-app-layout>
