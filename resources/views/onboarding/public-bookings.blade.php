<!DOCTYPE html>
<html lang="es">
<head>
    <x-theme-init />
    <x-page-loading-head /><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Disponibilidad · {{ $club->name }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])<x-club-theme :club="$club" /></head>
<body class="min-h-screen bg-slate-50 dark:bg-slate-950 font-sans text-slate-900 dark:text-slate-100">
    <x-page-loading />
    <main class="mx-auto max-w-6xl space-y-8 px-5 py-10">
        <x-brand :club="$club" :href="url('/')" />
        <header><p class="text-xs uppercase tracking-widest text-brand-content">Disponibilidad del club</p><h1 class="mt-3 text-3xl font-bold">{{ $club->name }}</h1><p class="mt-3 text-sm text-slate-600 dark:text-slate-400">Consulta los horarios disponibles. Esta página es informativa; consulta al club para confirmar tu reserva.</p></header>
        @if ($errors->any())<ul role="alert" class="rounded-lg bg-rose-50 dark:bg-rose-950 p-4 text-sm text-rose-800 dark:text-rose-200">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        <form method="GET" action="{{ route('public.bookings', $club->slug) }}" class="flex flex-wrap items-end gap-4"><label class="grid gap-2 text-sm">Fecha<input type="date" name="date" value="{{ $date }}" min="{{ today()->toDateString() }}" max="{{ today()->addDays(90)->toDateString() }}" class="rounded-lg border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100" required></label><button class=" bg-brand text-brand-on    font-bold text-brand-on inline-flex min-h-14 items-center justify-center rounded-xl px-7 py-4 text-base font-bold shadow-sm shadow-emerald-900/10 focus-visible:ring-2 focus-visible:ring-brand-ring focus-visible:ring-offset-2">Ver horarios</button></form>
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($availability as $item)<section data-reveal class="surface-card rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5">
                <h2 class="text-xl font-bold">{{ $item['court']->name }}</h2><p class="mt-2 text-xs text-slate-600 dark:text-slate-400">{{ ['padel' => 'Pádel', 'futbolito' => 'Futbolito', 'tenis' => 'Tenis'][$item['court']->sport_type] ?? $item['court']->sport_type }} · {{ $item['court']->surface_type }} · &#36;{{ number_format($item['court']->default_hourly_rate, 0, ',', '.') }} / hora</p>
                <x-court-location :court="$item['court']" :club="$club" /><ul class="mt-5 grid gap-2 sm:grid-cols-2">@foreach ($item['slots'] as $slot)<li class="rounded-lg border px-3 py-3 text-xs {{ $slot['available'] ? 'border-emerald-500/30 dark:border-lime-300/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-200' : 'border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/20 text-rose-700 dark:text-rose-300' }}">{{ $slot['start'] }}–{{ $slot['end'] }} · {{ $slot['available'] ? 'Disponible' : 'No disponible' }}</li>@endforeach</ul>
            </section>@endforeach
        </div>
        <p class="text-xs text-slate-600 dark:text-slate-400">Horarios de Chile · Turnos de {{ $club->slot_minutes }} minutos · No se muestran datos personales de los jugadores.</p>
    </main>
    <x-theme-toggle />
</body>
</html>
