<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Agenda de canchas</h2></x-slot>
    <div class="max-w-7xl mx-auto p-6 space-y-6">
        @if (session('success')) <p role="status">{{ session('success') }}</p> @endif
        @if (session('error')) <p role="alert">{{ session('error') }}</p> @endif
        @if ($errors->any())
            <ul role="alert">@foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach</ul>
        @endif
        <form method="GET" action="{{ route('bookings.index') }}" class="flex gap-4 items-center">
            <label>Fecha <input type="date" name="date" value="{{ $selectedDate }}" required></label>
            <x-primary-button>Ver reservas</x-primary-button>
        </form>
        <section class="bg-white p-6 rounded space-y-4">
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
            <form method="POST" action="{{ route('bookings.store') }}" class="bg-white p-6 rounded grid gap-4">
                @csrf
                <h3 class="font-semibold">Nueva reserva</h3>
                <label>Cancha <select name="court_id" required>
                    @foreach ($courts as $court)
                        <option value="{{ $court->id }}" @selected(old('court_id') == $court->id)>{{ $court->name }} (${{ number_format($court->default_hourly_rate, 0, ',', '.') }}/hora)</option>
                    @endforeach
                </select></label>
                <label>Nombre <input name="customer_name" value="{{ old('customer_name') }}" maxlength="255" required></label>
                <label>Teléfono <input name="customer_phone" value="{{ old('customer_phone') }}" maxlength="50" required></label>
                <label>Fecha <input type="date" name="booking_date" value="{{ old('booking_date', $selectedDate) }}" required></label>
                <label>Hora <input type="time" name="start_time" value="{{ old('start_time') }}" required></label>
                <label>Duración (horas) <input type="number" name="duration_hours" min="1" max="3" value="{{ old('duration_hours', 1) }}" required></label>
                <label>Medio de pago <select name="payment_method" required>
                    @foreach (['cash' => 'Efectivo', 'transfer' => 'Transferencia', 'webpay' => 'Webpay', 'pos' => 'Tarjeta POS'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('payment_method', 'cash') === $value)>{{ $label }}</option>
                    @endforeach
                </select></label>
                <label>Abono <input type="number" name="deposit_paid" min="0" step="0.01" value="{{ old('deposit_paid', 0) }}"></label>
                <label>Notas <textarea name="notes">{{ old('notes') }}</textarea></label>
                <x-primary-button>Reservar</x-primary-button>
            </form>
        @endif
    </div>
</x-app-layout>
