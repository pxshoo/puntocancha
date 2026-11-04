<x-auth-shell title="Revisa y continúa" subtitle="Acepta las políticas antes de ir a Webpay." :selection="$selection">
    <ol class="mb-6 flex gap-3 text-xs text-slate-500" aria-label="Pasos de contratación"><li>✓ Cuenta</li><li class="font-bold text-brand-content">2. Políticas</li><li>3. Pago</li></ol>
    <x-operation-messages />
    @if(config('billing.mode') === 'integration')<p class="mb-5 rounded-xl bg-amber-100 p-3 text-sm text-amber-950">Demo: Webpay de pruebas, sin cobros reales.</p>@endif
    <div class="mb-6 rounded-2xl border border-brand/20 bg-brand/10 p-5"><p class="text-sm">Total · {{ $quote['billing'] === 'annual' ? '1 año' : '1 mes' }}</p><p class="mt-2 text-3xl font-bold text-brand-content">&#36;{{ number_format($quote['amount'], 0, ',', '.') }} <span class="text-sm font-normal">CLP</span></p><p class="mt-2 text-xs text-slate-500">Pago único. Sin renovación automática.</p></div>
    @if($pending)
        <p class="mb-4 text-sm">Tienes un pago en proceso. Revisa su estado antes de volver a pagar.</p>
        <a href="{{ route('subscription.result', $pending) }}" class="flex min-h-14 items-center justify-center rounded-xl bg-brand px-5 font-bold text-brand-on">Revisar pago pendiente</a>
        <form method="POST" action="{{ route('subscription.resume', $pending) }}" class="mt-3">@csrf<x-secondary-button type="submit" class="w-full">Volver a Webpay</x-secondary-button></form>
    @else
        <form method="POST" action="{{ route('subscription.store') }}" class="space-y-5">
            @csrf<input type="hidden" name="request_key" value="{{ $requestKey }}"><input type="hidden" name="quoted_amount" value="{{ $quote['amount'] }}"><input type="hidden" name="policy_version" value="{{ config('billing.version') }}">
            <label class="flex items-start gap-3 text-sm leading-6"><input type="checkbox" name="accept_terms" value="1" required class="mt-1 rounded text-brand focus:ring-brand-ring"><span>He leído y acepto las <a href="{{ route('legal.show', 'terms') }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-content underline">condiciones de uso ↗</a>.</span></label>
            <label class="flex items-start gap-3 text-sm leading-6"><input type="checkbox" name="accept_privacy" value="1" required class="mt-1 rounded text-brand focus:ring-brand-ring"><span>He leído y acepto la <a href="{{ route('legal.show', 'privacy') }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-brand-content underline">política de privacidad ↗</a>.</span></label>
            <x-primary-button class="w-full">Aceptar y pagar con Webpay →</x-primary-button>
        </form>
    @endif
    <a href="{{ url('/').'#planes' }}" class="mt-5 block text-center text-sm text-slate-500 underline">Cambiar plan</a>
</x-auth-shell>
