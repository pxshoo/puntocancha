<!DOCTYPE html>
<html lang="es" class="scroll-smooth motion-reduce:scroll-auto">
<head>
    <x-theme-init />
    <x-page-loading-head />
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#020617">
    <title>PuntoCancha · Panel del club</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
<x-club-theme :club="auth()->user()?->club" />
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 font-['Inter',sans-serif] text-slate-700 dark:text-slate-300 antialiased selection:bg-emerald-600 dark:selection:bg-lime-300 dark:selection:text-slate-950 selection:text-white">
    <x-page-loading />
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-50 focus:rounded-lg focus:bg-emerald-600 dark:focus:bg-lime-300 dark:focus:text-slate-950 focus:p-3 focus:text-slate-950">Saltar al contenido</a>
    @include('layouts.navigation')
    <div class="min-h-screen min-w-0 lg:pl-64">
        <div class="border-b border-slate-200/80 dark:border-slate-800/80 bg-white/30 dark:bg-slate-900/30 px-5 py-4 sm:px-8">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-3 text-xs">
                <p class="flex items-center gap-2 text-slate-600 dark:text-slate-400"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400" aria-hidden="true"></span>{{ auth()->user()->club?->name }}</p>
                <p class="text-slate-600 dark:text-slate-400">{{ now()->format('d/m/Y') }} <span class="mx-2 text-slate-700 dark:text-slate-300">/</span> Tu club, en su mejor juego.</p>
            </div>
        </div>
        @isset($header)
            <header class="relative overflow-hidden border-b border-slate-200 dark:border-slate-800 px-4 py-6 sm:px-8 sm:py-8">
                <div class="pointer-events-none absolute -right-20 -top-40 h-80 w-80 rounded-full bg-emerald-500/5 blur-3xl" aria-hidden="true"></div>
                <div class="relative mx-auto max-w-7xl"><p class="mb-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-brand-content">Centro de operaciones</p>{{ $header }}</div>
            </header>
        @endisset
        <main id="contenido" class="relative min-w-0 pb-20 sm:pb-8">{{ $slot }}</main>
        <footer class="mx-5 flex flex-wrap justify-between gap-3 border-t border-slate-200 dark:border-slate-800 py-6 text-[10px] text-slate-600 dark:text-slate-400 sm:mx-8"><p>© {{ now()->year }} PuntoCancha · Hecho para el deporte.</p><a href="https://wa.me/56931403167" class="hover:text-brand-content">Ayuda y soporte ↗</a></footer>
    </div>
    <x-theme-toggle />
</body>
</html>
