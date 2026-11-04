<x-auth-shell title="Continuar a Webpay" subtitle="Tu plan se activa después de un pago aprobado.">
    @if($payment->mode === 'integration')<p class="mb-5 rounded-xl bg-amber-100 p-3 text-sm text-amber-950">Prueba de pago. No se cobrará dinero real.</p>@endif
    <h2 class="font-bold">{{ $payment->plan_name }}</h2><p class="mt-2 text-3xl font-bold text-brand-content">&#36;{{ number_format($payment->amount, 0, ',', '.') }} CLP</p>
    <form method="POST" action="{{ $payment->gateway_url }}" class="mt-6" x-data x-init="$nextTick(() => $el.submit())"><input type="hidden" name="token_ws" value="{{ $payment->token }}"><x-primary-button class="w-full">Ir a Webpay →</x-primary-button></form>
</x-auth-shell>
