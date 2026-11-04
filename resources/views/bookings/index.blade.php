<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Agenda de canchas</h2></x-slot>
    <div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-6">
        <a class="inline-flex rounded-lg border border-slate-200 dark:border-slate-800 px-4 py-3 text-sm font-semibold text-brand-content hover:bg-white dark:hover:bg-slate-900" href="{{ route('courts.index') }}">Administrar canchas y precios →</a>
        @if (auth()->user()->club->onboarding_completed_at)
            <section data-reveal class="rounded-xl border border-brand/20 bg-emerald-500/5 p-4"><h3 class="text-sm font-semibold text-emerald-800 dark:text-emerald-200">Enlace público de tu club</h3><a class="mt-2 block break-all text-sm text-brand-content underline" href="{{ route('public.bookings', auth()->user()->club->slug) }}">{{ route('public.bookings', auth()->user()->club->slug) }}</a></section>
        @endif
        @if (session('success')) <p role="status">{{ session('success') }}</p> @endif
        @if (session('error')) <p role="alert">{{ session('error') }}</p> @endif
        @if ($errors->any())
            <ul role="alert">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
        @endif
        <form method="GET" action="{{ route('bookings.index') }}" class="responsive-filter flex flex-wrap gap-4 items-end">
            <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Fecha <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" type="date" name="date" value="{{ $selectedDate }}" required></label>
            <x-primary-button>Ver reservas</x-primary-button>
        </form>
        <section data-reveal class="surface-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-xl space-y-4">
            <h3 class="font-semibold">Reservas del {{ $selectedDate }}</h3>
            @forelse ($bookings as $booking)
                <p>{{ $booking->court->name }} · {{ $booking->start_time->format('H:i') }}–{{ $booking->end_time->format('H:i') }} · {{ $booking->customer_name }} · ${{ number_format($booking->total_price, 0, ',', '.') }}</p>
            @empty
                <p>No hay reservas para esta fecha.</p>
            @endforelse
        </section>
        @if ($courts->isEmpty())
            <p>No hay canchas activas configuradas. Agrega una cancha y su tarifa para comenzar a reservar.</p>
        @else
            <form method="POST" action="{{ route('bookings.store') }}" class="surface-card bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 p-6 rounded-xl grid gap-5 sm:grid-cols-2">
                @csrf
                <h3 class="font-semibold sm:col-span-2">Nueva reserva</h3>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Cancha <select class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" name="court_id" required>
                    @foreach ($courts as $court)
                        <option value="{{ $court->id }}" @selected(old('court_id') == $court->id)>{{ $court->name }} (${{ number_format($court->default_hourly_rate, 0, ',', '.') }}/hora)</option>
                    @endforeach
                </select></label>
                <label class="grid gap-2 text-sm text-slate-700 dark:text-slate-300">Nombre <input class="rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 focus:border-brand focus:ring-brand-ring" name="customer_name" value="{{ old('customer_name') }}" maxlength="255" required></label>
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
                <x-primary-button>Reservar</x-primary-button>
            </form>
        @endif
    </div>
</x-app-layout>
