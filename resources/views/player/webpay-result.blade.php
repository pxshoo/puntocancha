<x-player-layout :club="$payment->club">
    <section class="mx-auto max-w-2xl space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
        <p class="text-sm font-semibold text-brand-content">{{ $payment->club->name }}</p>
        <h1 class="text-2xl font-bold">{{ $payment->status === 'paid' ? 'Pago aprobado · Reserva confirmada' : ($payment->status === 'failed' ? 'El pago no fue aprobado' : 'Pago pendiente de verificación') }}</h1>
        @if($payment->mode === 'integration')<p class="rounded-xl bg-amber-100 p-3 text-sm text-amber-950">Prueba de Webpay. No corresponde a un pago real.</p>@endif
        <p class="text-sm">Orden: {{ $payment->buy_order }} · Reserva #{{ $payment->booking_id }}</p>
        <div class="rounded-xl bg-brand/10 p-5"><h2 class="font-bold">{{ $payment->booking->court->name }}</h2><p class="mt-2">{{ $payment->booking->start_time->format('d/m/Y H:i') }}–{{ $payment->booking->end_time->format('H:i') }}</p><p class="mt-3">Total: &#36;{{ number_format($payment->booking->total_price, 0, ',', '.') }}</p><p>Pagado: &#36;{{ number_format($payment->booking->deposit_paid, 0, ',', '.') }}</p><p class="font-semibold">Saldo con el club: &#36;{{ number_format(max(0, $payment->booking->total_price - $payment->booking->deposit_paid), 0, ',', '.') }}</p></div>
        <p class="text-sm">Correo de contacto: {{ $payment->booking->customer_email }}</p>
        <x-court-location :court="$payment->booking->court" :club="$payment->club" />
        @if($payment->status === 'paid')<p class="text-sm">Autorización Webpay: {{ $payment->authorization_code }}</p><p class="whitespace-pre-line text-sm text-slate-500">{{ $payment->booking->cancellation_policy }}</p>
        @elseif($payment->status === 'initialized')
            <p class="text-sm">No vuelvas a pagar mientras se verifica esta operación. Conserva este enlace para consultar el estado.</p>
            <form method="POST" action="{{ URL::signedRoute('webpay.refresh', ['payment' => $payment->id]) }}">@csrf<x-primary-button>Consultar estado del pago</x-primary-button></form>
        @else<p class="text-sm">El turno fue liberado. Puedes volver a elegir un horario.</p>@endif
        <a class="inline-flex min-h-14 items-center font-semibold text-brand-content" href="{{ route('player.club', $payment->club->slug) }}">← Volver al club</a>
    </section>
</x-player-layout>
