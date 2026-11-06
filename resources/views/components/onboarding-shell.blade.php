@props(['step', 'subscription'])
<!DOCTYPE html>
<html lang="es" >
<head>
    <x-theme-init />
    <x-page-loading-head />
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#020617">
    <title>Configura tu club · PuntoCancha</title>
    <link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
<x-club-theme :club="auth()->user()?->club" />
</head>
<body x-data="{ current: {{ $step === 1 ? 1 : 3 }} }" @setup-step.window="current = $event.detail" class="pc-interface min-h-screen bg-slate-50 dark:bg-slate-950 font-['Inter',sans-serif] text-slate-700 dark:text-slate-300 antialiased selection:bg-emerald-600 dark:selection:bg-lime-300 dark:selection:text-slate-950 selection:text-white">
    <x-page-loading />
    <header class="border-b border-slate-200 dark:border-slate-800"><div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-5 sm:px-8"><x-brand :club="auth()->user()?->club" /><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="text-xs text-slate-600 dark:text-slate-400 hover:text-brand-content">Salir y continuar después ↗</button></form></div></header>
    <main class="relative mx-auto grid max-w-7xl items-start gap-10 px-5 py-10 sm:px-8 lg:grid-cols-3 lg:gap-14 lg:py-16">
        <aside class="relative lg:sticky lg:top-12">
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-brand-content">Bienvenido a PuntoCancha</p>
            <h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Prepara tu club.<br><span class="text-brand-content">El juego comienza.</span></h1>
            <p class="mt-5 max-w-sm text-sm leading-relaxed text-slate-600 dark:text-slate-400">Un paso a la vez. Configura tu club y comienza a recibir reservas.</p>
            <ol class="mt-8 space-y-3" aria-label="Configuración del club">
                @foreach ([1 => 'Nombre del club', 2 => 'Canchas y tarifas', 3 => 'Horarios', 4 => 'Inventario', 5 => 'Revisión final'] as $number => $title)
                <li class="flex items-center gap-3 rounded-xl p-3 transition-colors duration-200" :class="current === {{ $number }} ? 'bg-brand/10 text-brand-content' : 'text-slate-500'" :aria-current="current === {{ $number }} ? 'step' : null"><span class="flex h-9 w-9 items-center justify-center rounded-full border text-sm font-bold" :class="current >= {{ $number }} ? 'border-brand bg-brand text-brand-on' : 'border-slate-300 dark:border-slate-700'" x-text="current > {{ $number }} ? '✓' : '{{ $number }}'"></span><span class="font-semibold">{{ $title }}</span></li>
                @endforeach
            </ol>
            @if ($subscription && ($subscription->isCurrent() || auth()->user()->club->access_override === 'allow'))
                <section data-reveal class="mt-8 rounded-xl border border-emerald-400/20 bg-gradient-to-br from-emerald-100 dark:from-emerald-950 to-white dark:to-slate-900 p-5"><p class="text-[9px] font-semibold uppercase tracking-widest text-brand-content">Tu plan seleccionado</p><h2 class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">{{ $subscription->plan->name }}</h2><p class="mt-2 text-xs text-slate-600 dark:text-slate-400">{{ $subscription->plan->court_limit === null ? 'Canchas ilimitadas' : 'Hasta '.$subscription->plan->court_limit.' canchas' }}</p><p class="mt-4 border-t border-brand/20 pt-3 text-[10px] text-emerald-800 dark:text-emerald-200">{{ $subscription->status === 'trialing' ? 'Prueba hasta el '.$subscription->ends_at?->format('d/m/Y H:i') : 'Suscripción activa' }}</p></section>
            @else
                <div role="alert" class="mt-8 rounded-xl border border-amber-400/30 bg-amber-400/5 p-4 text-sm text-amber-800 dark:text-amber-200">Tu prueba o suscripción no está vigente. <a href="https://wa.me/56931403167" class="font-semibold underline">Contacta a soporte para activar o mejorar tu plan.</a></div>
            @endif
            <a href="https://wa.me/56931403167" class="mt-6 inline-block text-xs text-slate-600 dark:text-slate-400 hover:text-brand-content">¿Necesitas ayuda o un plan con más canchas? ↗</a>
        </aside>
        <div class="min-w-0 space-y-6 lg:col-span-2"><x-operation-messages />{{ $slot }}<p class="border-t border-slate-200 dark:border-slate-800 pt-5 text-[10px] text-slate-500 dark:text-slate-500">PuntoCancha · Tu club, en su mejor juego.</p></div>
    </main>
    <footer class="sticky bottom-0 z-30 border-t border-slate-200 bg-white/95 px-5 py-4 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95">
        <div class="mx-auto max-w-7xl"><div class="mb-2 flex justify-between text-xs font-semibold"><span x-text="'Paso ' + current + ' de 5'"></span><span x-text="Math.round((current - 1) / 5 * 100) + '% completado'"></span></div><div role="progressbar" aria-label="Configuración del club" aria-valuemin="0" aria-valuemax="5" :aria-valuenow="current - 1" class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800"><div class="h-full rounded-full bg-brand transition-all duration-500 motion-reduce:transition-none" :style="'width: ' + ((current - 1) / 5 * 100) + '%'"></div></div><p class="mt-2 text-xs text-slate-500">Al finalizar, tu configuración quedará al 100%.</p></div>
    </footer>
    <x-theme-toggle />
</body>
</html>
