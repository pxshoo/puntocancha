<x-auth-shell title="Pago por enlace Webpay.cl" subtitle="Cobro real con verificación manual">
<x-operation-messages />
@if($payment->mode === 'payment_link_test')<p class="mb-5 rounded-xl bg-amber-100 p-4 text-sm text-amber-950">Prueba real de $100 CLP. Se cobrará tu tarjeta. No activa ni renueva un plan.</p>@endif
<section @if($payment->mode !== 'payment_link_test') x-data="accessStatus(@js(route('subscription.result', $payment)), true)" x-init="notice = 'Cuando se apruebe tu pago, entrarás automáticamente a configurar tu club.'" @focus.window="check()" @endif class="space-y-4 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
@if($payment->mode !== 'payment_link_test')
<div x-show="verified" x-cloak x-transition.opacity role="status" aria-live="polite" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/95 p-5 text-center text-white backdrop-blur">
    <div class="w-full max-w-md rounded-3xl border border-emerald-400/30 bg-slate-900 p-8 shadow-2xl">
        <div class="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-400 motion-safe:animate-bounce"><svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 4 4L19 6"/></svg></div>
        <h2 class="text-3xl font-extrabold" x-text="paymentApproved ? 'Pago verificado' : 'Acceso habilitado'">Acceso habilitado</h2><p class="mt-3 text-slate-300">Configuración de club en proceso</p>
        <div class="mt-8 h-2 overflow-hidden rounded-full bg-slate-800"><div class="h-full w-full origin-left rounded-full bg-emerald-400 motion-safe:animate-pulse"></div></div><p class="mt-4 text-sm text-slate-400">Vamos a preparar tu club, paso a paso.</p>
    </div>
</div>
@endif
<h2 class="text-lg font-bold">{{ $payment->plan_name }}</h2>
<p class="text-3xl font-bold text-brand-content">&#36;{{ number_format($payment->amount, 0, ',', '.') }} CLP</p>
<p class="text-sm">Referencia: <span class="select-all break-all font-mono">{{ $payment->buy_order }}</span></p>
@if($payment->status === 'manual_review')
<p class="text-sm">Paga el monto indicado en Webpay.cl y conserva tu comprobante. Usa esta referencia en el detalle o identificación, si está disponible. Después vuelve a esta pestaña.</p>
<a href="{{ $payment->gateway_url }}" target="_blank" rel="noopener noreferrer" class="action-feedback flex min-h-14 items-center justify-center rounded-xl bg-brand px-6 font-bold text-brand-on focus:outline-none focus:ring-2 focus:ring-brand-ring">Abrir Webpay.cl y pagar ↗</a>
<p class="rounded-xl bg-amber-400/10 p-4 text-sm">Pendiente de verificación. Conserva tu comprobante: revisaremos la venta en Transbank antes de activar el plan. No pagues nuevamente si ya lo hiciste.</p>
<a @if($payment->mode !== 'payment_link_test') @click.prevent="check(true)" :aria-busy="checking" :aria-disabled="checking" x-text="checking ? 'Consultando pago...' : 'Ya pagué · Comprobar estado'" @endif href="{{ route('subscription.result', $payment) }}" class="flex min-h-12 items-center justify-center rounded-xl border border-emerald-500/30 px-4 text-center text-sm font-semibold text-brand-content transition-colors hover:bg-emerald-500/10 focus:outline-none focus:ring-2 focus:ring-brand-ring">Ya pagué · Comprobar estado</a>@if($payment->mode !== 'payment_link_test')<p role="status" aria-live="polite" class="rounded-xl bg-slate-100 p-3 text-sm text-slate-700 dark:bg-slate-800 dark:text-slate-200" x-text="notice"></p>@endif
@elseif($payment->status === 'paid')
<p class="rounded-xl bg-emerald-100 p-4 text-sm text-emerald-950">{{ $payment->mode === 'payment_link_test' ? 'Pago real de prueba verificado. No se activó un plan.' : 'Pago verificado. Tu plan está activo.' }}</p>
<p class="text-sm">Autorización: {{ $payment->authorization_code }}</p>
@if($payment->mode !== 'payment_link_test')<a href="{{ auth()->user()->onboarding_completed ? route('dashboard') : route('onboarding.step1') }}" class="flex min-h-14 items-center justify-center rounded-xl bg-brand font-bold text-brand-on">Continuar a mi club</a>@endif
@else
<p>Esta solicitud fue cerrada sin activar un plan.</p>
@endif
</section>
@if($payment->status !== 'manual_review')<a href="{{ route('subscription.checkout') }}" class="mt-5 block text-center text-sm text-brand-content underline">Volver a los planes</a>@endif
</x-auth-shell>
