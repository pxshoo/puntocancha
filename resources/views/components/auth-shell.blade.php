@props(['title', 'subtitle', 'selection' => null, 'player' => false])
<!DOCTYPE html>
<html lang="es" >
<head>
    <x-theme-init />
    <x-page-loading-head />
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#020617">
    <title>{{ $title }} · PuntoCancha</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="pc-interface bg-slate-50 dark:bg-slate-950 font-['Inter',sans-serif] text-slate-900 dark:text-slate-100 antialiased selection:bg-emerald-600 dark:selection:bg-lime-300 dark:selection:text-slate-950 selection:text-white">
    <x-page-loading />
    <div class="relative min-h-screen lg:grid lg:grid-cols-2">
        <aside class="relative flex flex-col overflow-hidden border-b border-slate-300/10 dark:border-slate-700/10 px-6 py-8 lg:min-h-screen lg:border-b-0 lg:border-r lg:p-12 xl:p-16">
            <div class="pointer-events-none absolute -bottom-40 -left-32 h-[600px] w-[600px] rounded-full bg-emerald-500/10 blur-3xl" aria-hidden="true"></div>
            <a href="{{ url('/') }}" class="relative inline-flex w-fit items-center gap-3 text-xl font-extrabold tracking-tight"><span class="flex h-10 w-10 -rotate-6 items-center justify-center rounded-xl bg-brand text-brand-on text-brand-on"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 12h18M12 3v18M3 7h18M3 17h18"/></svg></span>PuntoCancha<span class="text-brand-content">.</span></a>
            <div class="relative my-auto hidden py-16 lg:block">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-brand-content">Tu club, en su mejor juego</p>
                <h1 class="mt-6 max-w-lg text-5xl font-extrabold leading-[1.12] tracking-tight xl:text-6xl">{{ $player ? 'Tu próximo partido.' : 'El control de tu club.' }}<br><span class="text-brand-content">{{ $player ? 'A un turno de distancia.' : 'En tus manos.' }}</span></h1>
                <p class="mt-6 max-w-sm text-base leading-relaxed text-slate-600 dark:text-slate-400">{{ $player ? 'Encuentra clubes, revisa los horarios y reúne a tu equipo. Menos vueltas para organizarte, más tiempo para jugar.' : 'Canchas, ventas e inventario conectados. Menos tiempo administrando, más tiempo haciendo crecer tu recinto.' }}</p>
                <div class="surface-card relative mt-10 max-w-sm rounded-2xl border border-slate-200 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 p-5">
                    <div class="flex items-center justify-between text-xs"><span class="font-semibold">Todo tu equipo, conectado</span><span class="text-brand-content">● PuntoCancha</span></div>
                    <div class="mt-5 grid grid-cols-3 gap-2">@foreach (($player ? ['Explorar', 'Reservar', 'Jugar'] : ['Reservas', 'Ventas POS', 'Inventario']) as $label)<div class="rounded-lg border border-brand/20 bg-emerald-500/10 py-5 text-center text-xs font-medium text-emerald-800 dark:text-emerald-200">{{ $label }}</div>@endforeach</div>
                    <p class="mt-4 flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand text-brand-on text-brand-on" aria-hidden="true">✓</span>Todo en un mismo lugar. Sin planillas sueltas.</p>
                </div>
            </div>
            <p class="relative hidden text-xs text-slate-600 dark:text-slate-400 lg:block">Pádel · Futbolito · Tenis &nbsp; / &nbsp; © {{ now()->year }} PuntoCancha</p>
        </aside>
        <main class="flex items-center justify-center px-6 py-10 sm:px-12 lg:py-12">
            <div class="w-full max-w-md">
                <a href="{{ $player ? route('player.explore') : url('/').'#planes' }}" class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-400 hover:text-brand-content">{{ $player ? '← Explorar canchas' : '← Volver a los planes' }}</a>
                <p class="mt-9 text-xs font-semibold uppercase tracking-[0.2em] text-brand-content">Bienvenido a tu próximo nivel</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{{ $title }}</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $subtitle }}</p>
                @if ($selection)
                    <section data-reveal aria-label="Plan seleccionado" class="mt-6 rounded-xl border border-emerald-400/30 bg-emerald-400/5 p-4">
                        <div class="flex justify-between gap-4"><div><p class="text-[10px] font-semibold uppercase tracking-widest text-brand-content">Plan seleccionado</p><h3 class="mt-1 font-semibold">{{ $selection['name'] }}</h3></div><p class="text-right text-lg font-bold">&#36;{{ $selection['billing'] === 'annual' ? $selection['annual'] : $selection['price'] }}<span class="block text-[10px] font-normal text-slate-600 dark:text-slate-400">CLP / mes</span></p></div>
                        <p class="mt-2 text-xs text-slate-600 dark:text-slate-400">{{ $selection['billing'] === 'annual' ? 'Pago anual: $'.$selection['total'].' CLP · Ahorro del 20%' : 'Facturación mensual' }}</p>
                    </section>
                @endif
                <div class="mt-7">{{ $slot }}</div>
                <p class="mt-8 text-center text-[11px] leading-relaxed text-slate-600 dark:text-slate-400">{{ $player ? 'Tu acceso es personal. Consulta y gestiona únicamente tus propias reservas.' : 'Tu acceso es personal. Los datos de tu club se mantienen separados de otros recintos.' }}</p>
            </div>
        </main>
    </div>
    <x-theme-toggle />
</body>
</html>
