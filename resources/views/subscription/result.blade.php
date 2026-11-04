<x-auth-shell :title="$payment->status === 'paid' ? 'Tu plan está activo' : ($payment->status === 'failed' ? 'Pago no aprobado' : 'Verificando tu pago')" subtitle="Contratación de PuntoCancha">
    <x-operation-messages />
    @if($payment->mode === 'integration')<p class="mb-5 rounded-xl bg-amber-100 p-3 text-sm text-amber-950">Operación de prueba, sin cobro real.</p>@endif
    <section data-reveal class="surface-card space-y-3 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900"><h2 class="font-bold">{{ $payment->plan_name }}</h2><p class="text-2xl font-bold text-brand-content">&#36;{{ number_format($payment->amount, 0, ',', '.') }} CLP</p><p class="text-sm">{{ $payment->billing_cycle === 'annual' ? 'Pago anual' : 'Pago mensual' }}</p><p class="break-all text-xs text-slate-500">Orden {{ $payment->buy_order }}</p><p class="text-xs text-slate-500">Políticas aceptadas: {{ $payment->accepted_at->format('d/m/Y H:i') }} · Versión {{ $payment->terms_version }}</p></section>
    @if($payment->status === 'paid')
        <a href="{{ auth()->user()->onboarding_completed ? route('dashboard') : route('onboarding.step1') }}" class="action-feedback mt-6 flex min-h-14 items-center justify-center rounded-xl bg-brand px-6 font-bold text-brand-on">{{ auth()->user()->onboarding_completed ? 'Ir al panel →' : 'Configurar mi club →' }}</a>
    @elseif($payment->status === 'initialized')
        <p class="mt-5 text-sm text-slate-500">No vuelvas a pagar hasta conocer el resultado.</p><form method="POST" action="{{ route('subscription.refresh', $payment) }}" class="mt-5">@csrf<x-primary-button class="w-full">Consultar estado</x-primary-button></form><form method="POST" action="{{ route('subscription.resume', $payment) }}" class="mt-3">@csrf<x-secondary-button type="submit" class="w-full">Continuar en Webpay</x-secondary-button></form>
    @else
        <a href="{{ route('subscription.checkout') }}" class="mt-6 flex min-h-14 items-center justify-center rounded-xl bg-brand px-6 font-bold text-brand-on">Revisar y volver a intentar</a>
    @endif
</x-auth-shell>
