<x-player-layout>
    <div class="mx-auto max-w-3xl"><a href="{{ auth()->user()?->role === 'club_admin' ? route('subscription.checkout') : url('/') }}" class="text-sm text-brand-content">← Volver</a><h1 class="mt-6 text-3xl font-bold">{{ $title }}</h1><p class="mt-3 text-sm text-slate-500">Versión {{ $version }} · {{ config('billing.provider.name') }} · RUT {{ config('billing.provider.rut') }}</p>
        @unless(config('billing.legal_approved'))<p class="mt-5 rounded-xl bg-amber-50 p-4 text-sm text-amber-950">Versión de demostración. Revisar estos documentos antes de habilitar cobros reales.</p>@endunless
        <div class="mt-7 space-y-6">@foreach($sections as $heading => $content)<section data-reveal class="surface-card rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900"><h2 class="text-lg font-bold">{{ $heading }}</h2><p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-400">{{ $content }}</p></section>@endforeach</div><p class="mt-7 text-sm">Contacto: <a class="text-brand-content underline" href="mailto:{{ config('billing.provider.email') }}">{{ config('billing.provider.email') }}</a></p>
    </div>
</x-player-layout>
