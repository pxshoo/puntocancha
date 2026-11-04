<!DOCTYPE html>
<html lang="es" >
<head>
    <x-theme-init />
    <x-page-loading-head />
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#020617">
    <title>Tu acceso · PuntoCancha</title>
    <link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 font-['Inter',sans-serif] text-slate-700 dark:text-slate-300 antialiased">
    <x-page-loading />
    <div class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden px-5 py-12">
        <div class="pointer-events-none absolute -top-52 h-[500px] w-[500px] rounded-full bg-emerald-500/10 blur-3xl" aria-hidden="true"></div>
        <div class="relative w-full max-w-md"><x-brand /><p class="mb-8 mt-5 text-sm text-slate-600 dark:text-slate-400">Tu club, en su mejor juego.</p><div class="surface-card rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/70 dark:bg-slate-900/70 p-6 shadow-2xl sm:p-8">{{ $slot }}</div><a class="mt-6 inline-block text-xs text-slate-600 dark:text-slate-400 hover:text-brand-content" href="{{ route('login') }}">← Volver al acceso</a></div>
    </div>
    <x-theme-toggle />
</body>
</html>
