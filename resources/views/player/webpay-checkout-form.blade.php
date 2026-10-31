<form method="POST" action="{{ route('webpay.store') }}" class="mt-5 space-y-4">
    @csrf
    <input type="hidden" name="club_slug" value="{{ $club->slug }}">
    <input type="hidden" name="court_id" :value="selected?.courtId">
    <input type="hidden" name="date" value="{{ $date }}">
    <input type="hidden" name="start_time" :value="selected?.start">
    <input type="hidden" name="quoted_price" :value="selected?.price">
    <input type="hidden" name="request_key" value="{{ $requestKey }}">
    @if($club->webpay_mode === 'integration')<p class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">Webpay de pruebas: no se cobrará dinero real.</p>@endif
    <label class="grid gap-2 text-sm font-semibold">Ingresa tu correo
        <input type="email" name="customer_email" value="{{ old('customer_email', auth()->user()->email ?? '') }}" autocomplete="email" placeholder="tu@correo.cl" maxlength="255" required class="rounded-xl border-slate-200 bg-slate-50 text-slate-900 focus:border-brand focus:ring-brand-ring dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
    </label>
    <p class="text-xs text-slate-500">Este correo quedará asociado a tu reserva. No necesitas crear una cuenta para pagar.</p>
    <fieldset class="grid gap-3"><legend class="mb-3 text-sm font-semibold">¿Cuánto quieres pagar ahora?</legend>
        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700"><input type="radio" name="payment_choice" value="full" x-model="paymentChoice" class="mt-1 text-brand focus:ring-brand-ring"><span><strong class="block">Pagar el total</strong><span class="text-sm text-brand-content" x-text="money(selected?.price || 0)"></span><span class="mt-1 block text-xs text-slate-500">Sin saldo pendiente por el arriendo.</span></span></label>
        <template x-if="selected && selected.minimum > 0 && selected.minimum < selected.price"><label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-4 dark:border-slate-700"><input type="radio" name="payment_choice" value="deposit" x-model="paymentChoice" class="mt-1 text-brand focus:ring-brand-ring"><span><strong class="block">Pagar abono mínimo</strong><span class="text-sm text-brand-content" x-text="money(selected.minimum)"></span><span class="mt-1 block text-xs text-slate-500" x-text="'Saldo a pagar al club: ' + money(selected.price - selected.minimum)"></span></span></label></template>
    </fieldset>
    <label class="flex items-start gap-3 text-xs leading-5"><input type="checkbox" name="accept_terms" value="1" required class="mt-1 rounded text-brand focus:ring-brand-ring">Acepto las condiciones de pago y cancelación del club.</label>
    <button type="submit" class="action-feedback inline-flex min-h-14 w-full items-center justify-center rounded-xl bg-brand px-6 font-bold text-brand-on hover:bg-brand-hover" x-text="'Pagar ' + money(paymentChoice === 'deposit' ? selected?.minimum || 0 : selected?.price || 0) + ' con Webpay →'">Pagar con Webpay →</button>
    <p class="text-center text-xs text-slate-500">El pago se completa en Transbank. La reserva se confirma cuando sea aprobado.</p>
</form>
