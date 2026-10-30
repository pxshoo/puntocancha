<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Disponibilidad · {{ $club->name }}</title>@vite(['resources/css/app.css', 'resources/js/app.js'])</head>
<body class="min-h-screen bg-slate-950 font-sans text-slate-100">
    <main class="mx-auto max-w-6xl space-y-8 px-5 py-10">
        <a href="{{ url('/') }}" class="text-sm font-bold text-lime-300">PuntoCancha.</a>
        <header><p class="text-xs uppercase tracking-widest text-emerald-300">Disponibilidad del club</p><h1 class="mt-3 text-3xl font-bold">{{ $club->name }}</h1><p class="mt-3 text-sm text-slate-400">Consulta los horarios disponibles. Esta página es informativa; consulta al club para confirmar tu reserva.</p></header>
        @if ($errors->any())<ul role="alert" class="rounded-lg bg-rose-950 p-4 text-sm text-rose-200">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
        <form method="GET" action="{{ route('public.bookings', $club->slug) }}" class="flex flex-wrap items-end gap-4"><label class="grid gap-2 text-sm">Fecha<input type="date" name="date" value="{{ $date }}" min="{{ today()->toDateString() }}" max="{{ today()->addDays(90)->toDateString() }}" class="rounded-lg border-slate-700 bg-slate-900 text-white" required></label><button class="rounded-lg bg-lime-300 px-5 py-2.5 text-sm font-bold text-slate-950">Ver horarios</button></form>
        <div class="grid gap-6 lg:grid-cols-2">
            @foreach ($availability as $item)<section class="rounded-2xl border border-slate-700 bg-slate-900 p-5">
                <h2 class="text-xl font-bold">{{ $item['court']->name }}</h2><p class="mt-2 text-xs text-slate-400">{{ ['padel' => 'Pádel', 'futbolito' => 'Futbolito', 'tenis' => 'Tenis'][$item['court']->sport_type] ?? $item['court']->sport_type }} · {{ $item['court']->surface_type }} · &#36;{{ number_format($item['court']->default_hourly_rate, 0, ',', '.') }} / hora</p>
                <ul class="mt-5 grid gap-2 sm:grid-cols-2">@foreach ($item['slots'] as $slot)<li class="rounded-lg border px-3 py-3 text-xs {{ $slot['available'] ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-200' : 'border-slate-700 text-slate-500' }}">{{ $slot['start'] }}–{{ $slot['end'] }} · {{ $slot['available'] ? 'Disponible' : 'No disponible' }}</li>@endforeach</ul>
            </section>@endforeach
        </div>
        <p class="text-xs text-slate-500">Horarios de Chile · Turnos de {{ $club->slot_minutes }} minutos · No se muestran datos personales de los jugadores.</p>
    </main>
</body>
</html>
