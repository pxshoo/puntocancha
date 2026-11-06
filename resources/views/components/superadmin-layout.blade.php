@props(['title' => 'Administración de PuntoCancha'])
<!doctype html>
<html lang="es" class="scroll-smooth motion-reduce:scroll-auto">
<head><x-theme-init /><x-page-loading-head />
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="preconnect" href="https://fonts.bunny.net"><link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
<title>{{ $title }} · PuntoCancha</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($sections = ['overview' => ['Resumen', 'M3 13h8V3H3zm10 8h8V11h-8zM3 21h8v-6H3zm10-12h8V3h-8z'], 'transactions' => ['Transacciones', 'M3 7h18M5 4h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2ZM7 15h4'], 'clubs' => ['Clubes y acceso', 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5M9 9h.01M15 9h.01'], 'accounts' => ['Cuentas', 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M16 3a4 4 0 0 1 0 8m6 10v-2a4 4 0 0 0-3-3.87M13 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z'], 'bookings' => ['Pagos de reservas', 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z'], 'audit' => ['Historial', 'M3 12a9 9 0 1 0 3-6M3 3v5h5m4-1v5l3 2']])
<body x-data="{ section: @js(is_string(request('section', 'overview')) && array_key_exists(request('section', 'overview'), $sections) ? request('section', 'overview') : 'overview'), valid: @js(array_keys($sections)), init() { this.syncSection(); }, syncSection() { const id = window.location.hash.slice(1); if (this.valid.includes(id)) { this.section = id; this.$nextTick(() => window.scrollTo({ top: 0, behavior: 'instant' })); } } }" @hashchange.window="syncSection()" class="pc-interface min-h-screen bg-slate-50 font-sans text-slate-800 dark:bg-slate-950 dark:text-slate-100">
<x-page-loading />
@if(auth()->user()?->is_superadmin)
<aside class="fixed inset-y-0 left-0 z-30 hidden w-72 flex-col border-r border-slate-800 bg-slate-950 p-6 text-slate-100 lg:flex">
<div class="px-2 py-4"><x-brand :href="route('superadmin.index')" class="!text-white" /><p class="mt-3 text-[10px] font-bold uppercase tracking-[.2em] text-slate-400">Control de plataforma</p></div>
<div class="mt-4 flex items-center gap-3 rounded-2xl border border-emerald-400/20 bg-emerald-400/5 p-4"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-600 text-white"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="m12 3 8 4v6c0 5-8 9-8 9s-8-4-8-9V7l8-4Z"/><path d="m8 12 3 3 5-6"/></svg></span><div><p class="text-sm font-bold">Propietario</p><p class="text-xs text-emerald-300">Acceso completo</p></div></div>
<nav aria-label="Administración general" class="mt-7 space-y-2">@foreach($sections as $id => [$label, $icon])<a href="{{ route('superadmin.index').'#'.$id }}" :aria-current="section === @js($id) ? 'page' : null" :class="section === @js($id) ? 'bg-emerald-400/15 text-emerald-300 ring-1 ring-emerald-400/20' : 'text-slate-400 hover:bg-slate-800/70 hover:text-white'" class="flex min-h-14 items-center gap-3 rounded-xl px-4 py-3 text-base font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-emerald-500"><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>{{ $label }}</a>@endforeach</nav>
<div class="mt-auto border-t border-slate-800 pt-5"><p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p><p class="mt-1 truncate text-xs text-slate-500">{{ auth()->user()->email }}</p><form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button class="min-h-11 w-full rounded-xl border border-slate-700 px-4 py-2 text-sm font-semibold text-slate-300 transition hover:bg-slate-800 focus:ring-2 focus:ring-emerald-500">Cerrar sesión</button></form></div>
</aside>
@endif
<div class="min-h-screen flex flex-col {{ auth()->user()?->is_superadmin ? 'lg:pl-72' : '' }}">
<header class="pc-platform-heading border-b border-slate-200 bg-white/80 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/60 sm:px-8"><div class="flex w-full flex-wrap items-center justify-between gap-3"><div class="lg:hidden"><x-brand :href="route('superadmin.index')" /></div><p class="hidden text-sm text-slate-500 lg:block">PuntoCancha <span class="mx-2 text-slate-300">/</span> Administración de plataforma</p><p class="text-xs text-slate-500">{{ now()->translatedFormat('d M Y') }}</p></div>
@if(auth()->user()?->is_superadmin)<nav aria-label="Administración móvil" class="mt-4 flex gap-2 overflow-x-auto pb-1 lg:hidden">@foreach($sections as $id => [$label, $icon])<a href="{{ route('superadmin.index').'#'.$id }}" :class="section === @js($id) ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'" class="shrink-0 rounded-xl px-4 py-3 text-xs font-semibold focus:ring-2 focus:ring-emerald-500">{{ $label }}</a>@endforeach<form method="POST" action="{{ route('logout') }}">@csrf<button class="whitespace-nowrap rounded-xl border border-slate-200 px-4 py-3 text-xs dark:border-slate-700">Cerrar sesión</button></form></nav>@endif
</header>
<main id="contenido" class="w-full flex-1 pb-10">{{ $slot }}</main>
<footer class="px-6 py-6 text-xs text-slate-400 sm:px-10">© {{ now()->year }} PuntoCancha · Panel del propietario.</footer>
</div>
<x-theme-toggle />
</body></html>
