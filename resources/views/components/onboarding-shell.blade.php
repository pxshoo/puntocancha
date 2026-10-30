@props(['step', 'subscription'])
<x-app-layout>
    <x-slot name="header"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="text-xs font-semibold uppercase tracking-widest text-emerald-700">Bienvenido a PuntoCancha</p><h2 class="mt-1 text-xl font-bold">Configuremos tu club</h2></div><p class="text-sm text-gray-500">Paso {{ $step }} de 2</p></div></x-slot>
    <div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-8">
        <div class="grid grid-cols-2 gap-3" aria-label="Progreso: paso {{ $step }} de 2">
            <div class="space-y-2"><div class="h-2 rounded-full bg-emerald-500"></div><p class="text-xs font-semibold text-emerald-700">01 · Tus canchas</p></div>
            <div class="space-y-2"><div class="h-2 rounded-full {{ $step === 2 ? 'bg-emerald-500' : 'bg-gray-200' }}"></div><p class="text-xs font-semibold {{ $step === 2 ? 'text-emerald-700' : 'text-gray-400' }}">02 · Horarios y quiosco</p></div>
        </div>
        <x-operation-messages />
        @if ($subscription?->isCurrent())
            <div class="flex flex-wrap justify-between gap-2 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900"><span class="font-semibold">{{ $subscription->plan->name }} · {{ $subscription->plan->court_limit === null ? 'Canchas ilimitadas' : 'Hasta '.$subscription->plan->court_limit.' canchas' }}</span><span>{{ $subscription->status === 'trialing' ? 'Prueba hasta el '.$subscription->ends_at?->format('d/m/Y H:i') : 'Suscripción activa' }}</span></div>
        @else
            <div role="alert" class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">Tu prueba o suscripción no está vigente. <a href="https://wa.me/56931403167" class="font-semibold underline">Contacta a soporte para activar o mejorar tu plan.</a></div>
        @endif
        {{ $slot }}
        <div class="flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500"><a href="https://wa.me/56931403167" class="underline">¿Necesitas ayuda o un plan con más canchas?</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="underline">Guardar para después y salir</button></form></div>
    </div>
</x-app-layout>
