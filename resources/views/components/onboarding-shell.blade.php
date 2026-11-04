@props(['step', 'subscription'])
<!DOCTYPE html>
<html lang="es" >
<head>
    <x-theme-init />
    <x-page-loading-head />
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#020617">
    <title>Paso {{ $step }} de 2 · PuntoCancha</title>
    <link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
<x-club-theme :club="auth()->user()?->club" />
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 font-['Inter',sans-serif] text-slate-700 dark:text-slate-300 antialiased selection:bg-emerald-600 dark:selection:bg-lime-300 dark:selection:text-slate-950 selection:text-white">
    <x-page-loading />
    <header class="border-b border-slate-200 dark:border-slate-800"><div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-5 sm:px-8"><x-brand :club="auth()->user()?->club" /><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" class="text-xs text-slate-600 dark:text-slate-400 hover:text-brand-content">Salir y continuar después ↗</button></form></div></header>
    <main class="relative mx-auto grid max-w-7xl items-start gap-10 px-5 py-10 sm:px-8 lg:grid-cols-3 lg:gap-14 lg:py-16">
        <aside class="relative lg:sticky lg:top-12">
            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-brand-content">Bienvenido a PuntoCancha</p>
            <h1 class="mt-4 text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">Prepara tu club.<br><span class="text-brand-content">El juego comienza.</span></h1>
            <p class="mt-5 max-w-sm text-sm leading-relaxed text-slate-600 dark:text-slate-400">Dos pasos para dejar tu recinto listo. Nosotros ponemos la tecnología; tú pones el deporte.</p>
            <ol class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-1" aria-label="Progreso del asistente">
                @foreach ([1 => ['Tus canchas', 'Deportes, superficies y tarifas'], 2 => ['Horarios y quiosco', 'La última jugada antes de empezar']] as $number => [$title, $description])
                    <li class="flex items-center gap-3 rounded-xl border p-4 {{ $step === $number ? 'border-emerald-500/30 dark:border-lime-300/30 bg-emerald-600/5 dark:bg-lime-300/5' : 'border-slate-200 dark:border-slate-800 bg-white/30 dark:bg-slate-900/30' }}" @if($step === $number) aria-current="step" @endif><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $number <= $step ? 'bg-brand text-brand-on text-brand-on' : 'border border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-400' }}">{{ $number < $step ? '✓' : '0'.$number }}</span><span><span class="block text-sm font-semibold {{ $step === $number ? 'text-slate-900 dark:text-slate-100' : 'text-slate-600 dark:text-slate-400' }}">{{ $title }}</span><span class="mt-1 block text-[10px] text-slate-600 dark:text-slate-400">{{ $description }}</span></span></li>
                @endforeach
            </ol>
            <p class="mt-5 text-xs text-slate-600 dark:text-slate-400">Paso {{ $step }} de 2</p>
            <div class="mt-2 h-1 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800" role="progressbar" aria-label="Configuración del club" aria-valuenow="{{ $step }}" aria-valuemin="0" aria-valuemax="2"><div class="h-full rounded-full bg-brand text-brand-on {{ $step === 1 ? 'w-1/2' : 'w-full' }}"></div></div>
            @if ($subscription?->isCurrent())
                <section data-reveal class="mt-8 rounded-xl border border-emerald-400/20 bg-gradient-to-br from-emerald-100 dark:from-emerald-950 to-white dark:to-slate-900 p-5"><p class="text-[9px] font-semibold uppercase tracking-widest text-brand-content">Tu plan seleccionado</p><h2 class="mt-2 text-lg font-bold text-slate-900 dark:text-slate-100">{{ $subscription->plan->name }}</h2><p class="mt-2 text-xs text-slate-600 dark:text-slate-400">{{ $subscription->plan->court_limit === null ? 'Canchas ilimitadas' : 'Hasta '.$subscription->plan->court_limit.' canchas' }}</p><p class="mt-4 border-t border-brand/20 pt-3 text-[10px] text-emerald-800 dark:text-emerald-200">{{ $subscription->status === 'trialing' ? 'Prueba hasta el '.$subscription->ends_at?->format('d/m/Y H:i') : 'Suscripción activa' }}</p></section>
            @else
                <div role="alert" class="mt-8 rounded-xl border border-amber-400/30 bg-amber-400/5 p-4 text-sm text-amber-800 dark:text-amber-200">Tu prueba o suscripción no está vigente. <a href="https://wa.me/56931403167" class="font-semibold underline">Contacta a soporte para activar o mejorar tu plan.</a></div>
            @endif
            <a href="https://wa.me/56931403167" class="mt-6 inline-block text-xs text-slate-600 dark:text-slate-400 hover:text-brand-content">¿Necesitas ayuda o un plan con más canchas? ↗</a>
        </aside>
        <div class="min-w-0 space-y-6 lg:col-span-2"><x-operation-messages />{{ $slot }}<p class="border-t border-slate-200 dark:border-slate-800 pt-5 text-[10px] text-slate-500 dark:text-slate-500">PuntoCancha · Tu club, en su mejor juego.</p></div>
    </main>
    <x-theme-toggle />
</body>
</html>
