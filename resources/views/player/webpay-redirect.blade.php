<x-player-layout :club="$payment->club">
    <section class="mx-auto max-w-lg rounded-2xl border border-slate-200 bg-white p-8 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h1 class="text-2xl font-bold">Continuar a Webpay</h1>
        @if($payment->mode === 'integration')<p class="mt-4 rounded-xl bg-amber-100 p-3 text-sm text-amber-950">Modo de pruebas: no se realizará un cobro real.</p>@endif
        <p class="mt-5">Monto a pagar: <strong>&#36;{{ number_format($payment->amount, 0, ',', '.') }} CLP</strong></p>
        <p class="mt-3 text-sm text-slate-500">Completa el pago en la página segura de Transbank. El turno se confirma después de la aprobación.</p>
        <form method="POST" action="{{ $payment->gateway_url }}" class="mt-6" x-data x-init="$nextTick(() => $el.submit())"><input type="hidden" name="token_ws" value="{{ $payment->token }}"><x-primary-button class="w-full">Ir a Webpay →</x-primary-button></form>
    </section>
</x-player-layout>
