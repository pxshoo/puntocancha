<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}"><meta name="robots" content="noindex">
<title>Acceso suspendido · PuntoCancha</title>
<x-theme-init /><x-page-loading-head />
@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pc-interface min-h-screen bg-slate-50 font-sans text-slate-900 antialiased dark:bg-slate-950 dark:text-slate-100">
<x-page-loading />
<div class="relative flex min-h-screen flex-col overflow-hidden">
<div aria-hidden="true" class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-emerald-500/10 blur-3xl"></div>
<header class="relative mx-auto w-full max-w-6xl px-6 py-7"><x-brand :href="url('/')" /></header>
<main class="relative flex flex-1 items-center justify-center px-4 py-6 sm:px-6 sm:py-10">
<section x-data="accessStatus(@js(route('account.status')))" aria-labelledby="suspension-title" class="group relative w-full max-w-xl overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-2xl shadow-slate-900/10 transition-shadow duration-200 hover:shadow-emerald-950/10 motion-safe:animate-in motion-safe:fade-in motion-safe:zoom-in-95 motion-safe:duration-300 motion-reduce:animate-none dark:border-slate-700/60 dark:bg-slate-900 dark:shadow-black/30">
<div class="h-1.5 bg-gradient-to-r from-amber-400 to-orange-500"></div>
<div class="p-6 sm:p-9">
<div class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl border border-amber-200/70 bg-gradient-to-br from-amber-50 to-amber-100 text-amber-700 shadow-sm transition-all duration-200 ease-in-out motion-safe:group-hover:-rotate-3 motion-safe:group-hover:scale-105 dark:border-amber-300/15 dark:from-amber-400/15 dark:to-amber-400/5 dark:text-amber-300"><svg class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 5v2"/></svg></div>
<p class="text-xs font-bold uppercase tracking-widest text-amber-700 dark:text-amber-300">Estado de la cuenta</p>
<h1 id="suspension-title" class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl">Tu acceso está suspendido</h1>
<div class="mt-4 inline-flex max-w-full items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm font-semibold dark:border-slate-700 dark:bg-slate-800/70"><svg class="h-4 w-4 shrink-0 text-slate-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-5h6v5M9 9h.01M15 9h.01M9 12h.01M15 12h.01"/></svg><span class="truncate">{{ $club->name }}</span></div>
<p class="mt-4 text-sm leading-7 text-slate-600 dark:text-slate-400">Contacta a soporte para revisar tu cuenta y solicitar la reactivación.</p>
<div class="mt-6 grid grid-cols-2 gap-3">
<div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 transition-all duration-200 hover:border-emerald-200 hover:shadow-sm dark:border-slate-800 dark:bg-slate-950/40 dark:hover:border-emerald-700/50"><span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">01</span><p class="mt-2 text-sm font-semibold">Contacta a soporte</p><p class="mt-1 text-xs leading-5 text-slate-500">Tu mensaje incluirá el nombre del club.</p></div>
<div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 transition-all duration-200 hover:border-indigo-200 hover:shadow-sm dark:border-slate-800 dark:bg-slate-950/40 dark:hover:border-indigo-700/50"><span class="text-xs font-bold text-indigo-600 dark:text-indigo-300">02</span><p class="mt-2 text-sm font-semibold">Revisamos tu cuenta</p><p class="mt-1 text-xs leading-5 text-slate-500">Te ayudamos a recuperar el acceso.</p></div>
</div>
<div class="mt-7 grid gap-3 sm:grid-cols-2">
<a href="https://wa.me/56931403167?text={{ rawurlencode('Hola, necesito revisar el acceso suspendido de mi club '.$club->name.'.') }}" target="_blank" rel="noopener noreferrer" class="group/support inline-flex min-h-14 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 text-sm font-bold text-white shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 motion-reduce:transform-none motion-reduce:transition-none"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8v.5Z"/></svg>Contactar a soporte</a>
<form method="POST" action="{{ route('logout') }}">@csrf<button class="inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-5 text-sm font-semibold transition-all duration-200 hover:bg-slate-100 hover:shadow-md motion-safe:hover:-translate-y-0.5 motion-reduce:transition-none focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-slate-700 dark:hover:bg-slate-800">Cerrar sesión</button></form>
</div>
<a @click.prevent="check()" :aria-busy="checking" href="{{ route('dashboard') }}" class="mt-5 flex min-h-11 items-center justify-center gap-2 rounded-xl px-3 py-2 text-center text-sm font-semibold text-slate-500 transition-all duration-200 hover:bg-slate-100 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-emerald-300"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 7v5h-5M4 17v-5h5M6.1 7a7 7 0 0 1 11.6-1L20 9M4 15l2.3 3A7 7 0 0 0 18 17"/></svg><span x-text="checking ? 'Comprobando...' : 'Comprobar acceso'">Comprobar acceso</span></a><p role="status" class="mt-2 text-center text-xs text-slate-500" x-text="notice"></p>
</div>
</section>
</main>
<footer class="relative px-5 py-6 text-center text-xs text-slate-500">PuntoCancha · Estamos en tu equipo.</footer>
</div>
<x-theme-toggle />
</body></html>
