@props(['club' => null])
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <x-theme-init />
    <x-page-loading-head /><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#020617"><title>PuntoCancha · Encuentra tu próximo partido</title><link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">@vite(['resources/css/app.css', 'resources/js/app.js'])<x-club-theme :club="$club" /></head>
<body class="pc-interface min-h-screen bg-slate-50 dark:bg-slate-950 font-['Inter',sans-serif] text-slate-700 dark:text-slate-300 antialiased selection:bg-emerald-600 dark:selection:bg-lime-300 dark:selection:text-slate-950 selection:text-white">
    <x-page-loading />
    <a href="#contenido" class="sr-only focus:not-sr-only focus:fixed focus:z-50 focus:bg-emerald-600 dark:focus:bg-lime-300 dark:focus:text-slate-950 focus:p-3 focus:text-slate-950">Saltar al contenido</a>
    <header class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950"><nav class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-5 py-5 sm:px-8" aria-label="Portal de jugadores"><x-brand :href="route('player.explore')" :club="$club" /><div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm"><a href="{{ route('player.explore') }}" class="hover:text-brand-content">Explorar canchas</a>
        @auth
            @if(auth()->user()->role === 'player')<a href="{{ route('player.bookings') }}" class="font-semibold text-brand-content">Mis reservas</a><a href="{{ route('profile.edit') }}" class="max-w-32 truncate text-slate-600 dark:text-slate-400">{{ auth()->user()->name }}</a>@else<a href="{{ route('dashboard') }}" class="text-brand-content">Panel del club</a>@endif
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="text-xs text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100">Salir</button></form>
        @else
            <a href="{{ route('login', ['audience' => 'player']) }}" class="text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100">Ingresar</a><a href="{{ route('player.register') }}" class=" bg-brand text-brand-on   font-bold text-brand-on inline-flex min-h-14 items-center justify-center rounded-xl px-7 py-4 text-base font-bold shadow-sm shadow-emerald-900/10 focus-visible:ring-2 focus-visible:ring-brand-ring focus-visible:ring-offset-2">Crear cuenta</a>
        @endauth
    </div></nav></header>
    @isset($header)<header class="mx-auto max-w-7xl px-5 pt-8 sm:px-8">{{ $header }}</header>@endisset
    <main id="contenido" class="mx-auto max-w-7xl space-y-6 px-5 py-8 sm:px-8"><x-operation-messages />{{ $slot }}</main>
    <footer class="mx-auto mt-10 flex max-w-7xl flex-wrap justify-between gap-4 border-t border-slate-200 dark:border-slate-800 px-5 py-8 text-xs text-slate-600 dark:text-slate-400 sm:px-8"><p>© {{ now()->year }} PuntoCancha · Más deporte, menos vueltas.</p><a href="{{ url('/') }}">¿Administras un club? Conoce PuntoCancha ↗</a></footer>
    <x-theme-toggle />
</body>
</html>
