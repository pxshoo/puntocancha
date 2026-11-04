<x-app-layout>
    <x-slot name="header"><h1 class="text-2xl font-bold text-slate-900 dark:text-white">Configuración</h1><p class="mt-2 text-sm text-slate-500">Todo lo que necesitas para preparar y personalizar tu club.</p></x-slot>
    <div class="mx-auto max-w-7xl space-y-8 px-5 py-8 sm:px-8">
        <x-operation-messages />
        <section data-reveal class="flex flex-wrap items-center justify-between gap-5 rounded-2xl border border-brand/20 bg-brand/10 p-6">
            <div><x-brand :club="$club" :href="route('settings.branding.edit')" /><p class="mt-4 text-sm font-semibold text-brand-content">Tu espacio en PuntoCancha</p><h2 class="mt-1 text-2xl font-bold">{{ $club->name }}</h2><p class="mt-2 text-sm">{{ $courtCount }} canchas configuradas · {{ $club->marketplace_enabled ? 'Portal público habilitado' : 'Portal público pendiente de publicar' }}</p></div>
            <div class="flex w-full flex-wrap gap-3 sm:w-auto">
                <a href="{{ route('settings.branding.edit') }}" class="action-feedback inline-flex min-h-14 w-full items-center justify-center rounded-xl bg-brand px-6 font-bold text-brand-on shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-brand-hover hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-brand-ring sm:w-auto">Personalizar logo y colores</a>
                <a href="{{ route('club.settings.edit') }}" class="action-feedback inline-flex min-h-14 w-full items-center justify-center rounded-xl border border-brand/30 px-6 font-semibold text-brand-content focus:outline-none focus:ring-2 focus:ring-brand-ring sm:w-auto">Datos del club</a>
            </div>
        </section>
        <div class="grid gap-5 sm:grid-cols-2">
            @foreach([
                ['Cuenta y seguridad', 'Actualiza tus datos personales, correo, teléfono y contraseña.', route('profile.edit'), '01'],
                ['Datos y reservas', 'Nombre, ubicación, horarios y publicación del portal para tus jugadores.', route('club.settings.edit'), '02'],
                ['Logo y colores', 'Dale a tu panel y portal público la identidad visual de tu club.', route('settings.branding.edit'), '03'],
                ['Mi plan', 'Consulta tu suscripción, vigencia y capacidad de canchas.', '#mi-plan', '04']
            ] as [$title, $description, $link, $number])
                <a href="{{ $link }}" class="group rounded-2xl border border-slate-200 bg-white p-6 transition hover:border-brand hover:shadow-lg focus-visible:ring-2 focus-visible:ring-brand-ring dark:border-slate-800 dark:bg-slate-900">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-brand/10 text-sm font-bold text-brand-content">{{ $number }}</span>
                    <h2 class="mt-5 text-lg font-bold text-slate-900 dark:text-white">{{ $title }} <span class="float-right text-brand-content">↗</span></h2><p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ $description }}</p>
                </a>
            @endforeach
        </div>
        <section data-reveal id="mi-plan" class="surface-card scroll-mt-6 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-xl font-bold">Mi plan</h2><a class="mt-3 inline-flex min-h-11 items-center text-sm font-semibold text-brand-content underline" href="{{ url('/').'#planes' }}">Renovar o elegir otro plan</a>
            @if($subscription)
                <p class="mt-4 text-lg font-semibold text-brand-content">{{ $subscription->plan?->name }}</p>
                <dl class="mt-4 grid gap-5 text-sm sm:grid-cols-3">
                    <div><dt class="text-slate-500">Estado</dt><dd class="mt-1 font-semibold">{{ $subscription->isCurrent() ? ($subscription->status === 'trialing' ? 'Prueba gratis activa' : 'Suscripción activa') : 'Suscripción sin vigencia' }}</dd></div>
                    <div><dt class="text-slate-500">Canchas del plan</dt><dd class="mt-1 font-semibold">{{ $subscription->plan?->court_limit ?? 'Ilimitadas' }}</dd></div>
                    <div><dt class="text-slate-500">Vigencia hasta</dt><dd class="mt-1 font-semibold">{{ $subscription->ends_at?->format('d/m/Y') ?? 'Sin fecha de término' }}</dd></div>
                </dl>
            @else
                <p class="mt-4 text-sm text-slate-500">Tu club todavía no tiene una suscripción asociada. Contacta a soporte para revisar tu plan.</p>
            @endif
            <a href="https://wa.me/56931403167?text={{ rawurlencode('Hola, quiero consultar el plan de mi club '.$club->name) }}" class="mt-6 inline-flex min-h-14 items-center justify-center rounded-xl border border-brand px-6 font-semibold text-brand-content">Consultar o cambiar mi plan ↗</a>
        </section>
    </div>
</x-app-layout>
