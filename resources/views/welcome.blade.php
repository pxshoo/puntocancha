<!DOCTYPE html>
<html lang="es" class="scroll-smooth motion-reduce:scroll-auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PuntoCancha: reservas, POS, inventario y finanzas para clubes de pádel, futbolito y tenis. Conoce nuestros planes para tu recinto deportivo.">
    <meta name="theme-color" content="#0f172a">
    <title>PuntoCancha · Tu club, en su mejor juego</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-950 font-['Inter',sans-serif] text-slate-100 antialiased selection:bg-lime-300 selection:text-slate-950" x-data="{ annual: false }">
    @php
        $plans = [
            ['name' => 'Club Básico', 'price' => '35.000', 'annual' => '28.000', 'total' => '336.000', 'description' => 'El primer gran paso para poner tu club en orden.', 'cta' => 'Elegir Plan Básico', 'features' => ['Hasta 2 canchas activas', 'Portal de reservas online', 'POS básico: hasta 25 productos', 'Alertas de stock bajo', 'Soporte vía email']],
            ['name' => 'Club Pro', 'price' => '65.000', 'annual' => '52.000', 'total' => '624.000', 'description' => 'Para clubes con alto flujo y un quiosco que no para.', 'cta' => 'Comenzar con Pro', 'features' => ['Hasta 6 canchas activas', 'Portal de reservas personalizado para clientes', 'POS ilimitado + cuentas abiertas por cancha', 'Control de stock y alerta de reposición', 'Dashboard de ingresos, costos y margen bruto', 'Hasta 3 cuentas de recepcionista/cajero', 'Soporte prioritario por WhatsApp']],
            ['name' => 'Complejo Premium', 'price' => '110.000', 'annual' => '88.000', 'total' => '1.056.000', 'description' => 'Toda la potencia para grandes recintos multideporte.', 'cta' => 'Contactar para Premium', 'features' => ['Canchas ilimitadas', 'Todas las funciones Pro incluidas', 'Reporte financiero consolidado y flujo de caja', 'Multi-bodega para cafetería y tienda de pádel', 'Cuentas de personal y staff ilimitadas', 'Asistencia 24/7 y onboarding guiado']],
        ];
    @endphp
    <svg class="hidden" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <symbol id="pc-court" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 12h18M12 3v18M3 7h18M3 17h18"/></symbol>
        <symbol id="pc-arrow" viewBox="0 0 24 24"><path d="M5 12h14m-6-6 6 6-6 6"/></symbol>
        <symbol id="pc-check" viewBox="0 0 24 24"><path d="m5 12 4 4L19 6"/></symbol>
        <symbol id="pc-calendar" viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18m-13 5 2 2 5-5"/></symbol>
        <symbol id="pc-pos" viewBox="0 0 24 24"><path d="m4 10 2-7h12l2 7M4 10h16v11H4zM8 16h8M8 6h8M12 10v3"/></symbol>
        <symbol id="pc-chart" viewBox="0 0 24 24"><path d="M3 3v18h18M8 16v-4m5 4V8m5 8V5"/></symbol>
        <symbol id="pc-plus" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></symbol>
    </svg>
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-50 focus:bg-lime-300 focus:text-slate-950 focus:p-3">Saltar al contenido</a>
    <header class="relative z-20 border-b border-white/10">
        <nav class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-5 lg:px-8" aria-label="Navegación principal">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5 text-xl font-extrabold tracking-tight" aria-label="PuntoCancha, inicio"><span class="flex h-10 w-10 -rotate-6 items-center justify-center rounded-xl bg-lime-300 text-slate-950"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><use href="#pc-court"/></svg></span>PuntoCancha<span class="text-lime-300">.</span></a>
            <div class="hidden items-center gap-6 text-xs font-medium text-slate-300 lg:flex"><a href="#caracteristicas" class="hover:text-lime-300">Características</a><a href="#como-funciona" class="hover:text-lime-300">¿Cómo funciona?</a><a href="#planes" class="hover:text-lime-300">Planes y precios</a><a href="#beneficios" class="hover:text-lime-300">Beneficios</a></div>
            <div class="hidden items-center gap-5 text-sm font-semibold md:flex"><a href="{{ route('login') }}" class="hover:text-lime-300">Iniciar sesión</a><a href="{{ route('register') }}" class="rounded-lg bg-lime-300 px-4 py-3 text-slate-950 transition hover:bg-lime-200">Comenzar prueba gratis ↗</a></div>
            <details class="group relative md:hidden"><summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-700 px-3 py-2 text-sm">Menú <span aria-hidden="true" class="transition group-open:rotate-45">+</span></summary><div class="absolute right-0 top-14 flex w-64 flex-col gap-1 rounded-xl border border-slate-700 bg-slate-900 p-3 shadow-xl">
                @foreach (['caracteristicas' => 'Características', 'como-funciona' => '¿Cómo funciona?', 'planes' => 'Planes y precios', 'beneficios' => 'Beneficios'] as $anchor => $label)<a href="#{{ $anchor }}" onclick="this.closest('details').removeAttribute('open')" class="rounded-lg p-3 hover:bg-slate-800">{{ $label }}</a>@endforeach
                <a href="{{ route('login') }}" class="rounded-lg p-3 hover:bg-slate-800">Iniciar sesión</a><a href="{{ route('register') }}" class="rounded-lg bg-lime-300 p-3 text-center font-semibold text-slate-950">Comenzar prueba gratis</a>
            </div></details>
        </nav>
        <nav class="hidden justify-center gap-6 border-t border-white/5 px-4 py-3 text-xs text-slate-300 md:flex lg:hidden" aria-label="Secciones"><a href="#caracteristicas">Características</a><a href="#como-funciona">¿Cómo funciona?</a><a href="#planes">Planes y precios</a><a href="#beneficios">Beneficios</a></nav>
    </header>
    <main id="contenido">
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute -right-40 top-10 h-[600px] w-[600px] rounded-full bg-emerald-500/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative mx-auto grid max-w-7xl items-center gap-14 px-5 pb-16 pt-14 lg:grid-cols-2 lg:gap-10 lg:px-8 lg:pb-24 lg:pt-24">
                <div>
                    <p class="mb-7 inline-flex items-center gap-2 rounded-full border border-emerald-400/25 bg-emerald-400/5 px-3 py-2 text-[10px] font-semibold uppercase tracking-widest text-emerald-300 sm:text-xs"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-lime-300"></span>El Software Todo en Uno para Recintos Deportivos</p>
                    <h1 class="text-4xl font-extrabold leading-[1.08] tracking-tight sm:text-5xl xl:text-6xl">Administra tus Canchas, Quiosco y Finanzas <span class="text-lime-300">sin Perder un Solo Turno.</span></h1>
                    <p class="mt-7 max-w-xl text-base leading-relaxed text-slate-400 sm:text-lg">La plataforma moderna que digitaliza tus reservas de pádel y fútbol, controla el stock de bebidas y confites en tiempo real y calcula tus ganancias netas sin planillas Excel.</p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row"><a href="#planes" class="inline-flex items-center justify-center gap-4 rounded-lg bg-lime-300 px-6 py-4 text-sm font-bold text-slate-950 transition hover:bg-lime-200">Ver Planes y Precios <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><use href="#pc-arrow"/></svg></a><button type="button" onclick="document.getElementById('demo').showModal()" class="inline-flex items-center justify-center gap-3 rounded-lg border border-slate-700 px-6 py-4 text-sm font-semibold transition hover:bg-slate-800"><span class="text-lime-300" aria-hidden="true">▷</span> Probar Demostración</button></div>
                    <p class="mt-5 flex items-center gap-2 text-xs text-slate-500"><svg class="h-4 w-4 text-emerald-400" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><use href="#pc-check"/></svg>Menos administración. Más tiempo para el deporte.</p>
                </div>
                <div class="relative pb-10 lg:pl-4">
                    <div class="absolute -right-6 -top-8 hidden rotate-12 text-[110px] font-extrabold tracking-tighter text-slate-800/50 sm:block" aria-hidden="true">PLAY.</div>
                    <div class="relative rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl shadow-black/40">
                        <div class="flex items-center justify-between border-b border-slate-800 px-5 py-4"><div class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-lime-300"></span><span class="text-xs font-semibold">Tu club, bajo control</span></div><span class="rounded bg-slate-800 px-2 py-1 text-[9px] text-slate-400">VISTA ILUSTRATIVA</span></div>
                        <div class="p-4 sm:p-6">
                            <div class="mb-6 flex items-center justify-between"><div><p class="text-xs text-slate-400">Agenda de canchas</p><p class="mt-1 font-bold">Un buen día para jugar.</p></div><span class="rounded-lg bg-lime-300 px-3 py-2 text-xs font-bold text-slate-950">+ Reserva</span></div>
                            <div class="mb-5 grid grid-cols-3 gap-2">@foreach ([['Ventas del día', '$248.000'], ['Turnos', '18'], ['Stock', 'Al día']] as [$label, $value])<div class="rounded-lg bg-slate-800/70 p-3"><p class="text-[10px] text-slate-400">{{ $label }}</p><p class="mt-2 text-sm font-bold sm:text-lg">{{ $value }}</p></div>@endforeach</div>
                            <div class="grid grid-cols-3 gap-2 text-center text-[10px] sm:text-xs">
                                @foreach (['Pádel 01', 'Pádel 02', 'Futbolito'] as $court)<p class="py-2 font-medium text-slate-400">{{ $court }}</p>@endforeach
                                @foreach ([['18:00 · Reservado', '19:00 · Disponible', '20:00 · Reservado'], ['18:00 · Disponible', '19:00 · Reservado', '20:00 · Disponible'], ['18:00 · Reservado', '19:00 · Reservado', '20:00 · Disponible']] as $column)<div class="space-y-2">@foreach ($column as $slot)<div class="flex h-16 items-center justify-center rounded-lg border px-1 {{ str_contains($slot, 'Disponible') ? 'border-dashed border-slate-700 text-slate-500' : 'border-emerald-500/30 bg-emerald-500/15 text-emerald-300' }}">{{ $slot }}</div>@endforeach</div>@endforeach
                            </div>
                        </div>
                    </div>
                    <div class="absolute -bottom-1 right-1 flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 text-slate-900 shadow-xl sm:-right-3"><span class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 text-emerald-700"><svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><use href="#pc-pos"/></svg></span><div><p class="text-xs font-bold">Consumo cargado a la cancha</p><p class="mt-1 text-[10px] text-slate-500">2 aguas · Stock actualizado ✓</p></div></div>
                </div>
            </div>
            <div class="border-y border-white/10 bg-slate-900/50"><div class="mx-auto grid max-w-7xl gap-7 px-5 py-8 sm:grid-cols-3 lg:px-8">@foreach ([['+100%', 'Control de Caja'], ['0', 'Fugas de Inventario'], ['24/7', 'Reservas sin WhatsApp manual']] as [$number, $label])<div class="flex items-center justify-center gap-4 sm:border-r sm:border-white/10 last:border-0"><span class="text-3xl font-extrabold tracking-tight text-lime-300">{{ $number }}</span><span class="max-w-36 text-xs leading-relaxed text-slate-300">{{ $label }}</span></div>@endforeach</div></div>
        </section>

        <section id="beneficios" class="scroll-mt-6 bg-slate-50 px-5 py-20 text-slate-900 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl"><div class="mb-10 grid gap-5 lg:grid-cols-2 lg:items-end"><div><p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Recupera el control</p><h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Tu pasión es el deporte.<br>El desorden no debería serlo.</h2></div><p class="max-w-md leading-relaxed text-slate-500 lg:justify-self-end">Deja atrás las planillas sueltas y las cuentas que no cuadran. Lleva todo tu recinto al mismo lugar.</p></div>
                <div class="grid gap-5 md:grid-cols-2">
                    <article class="rounded-2xl border border-slate-200 bg-white p-7 sm:p-9"><p class="text-xs uppercase tracking-widest text-slate-400">Antes del pitazo inicial</p><h3 class="mt-3 text-2xl font-bold">El caos tradicional</h3><ul class="mt-7 space-y-5 text-sm text-slate-500">@foreach (['Cuadernos manchados y planillas que nadie actualiza.', 'Mensajes de WhatsApp a las 11 PM para conseguir un turno.', 'Dos jugadores. La misma cancha. La misma hora.', 'Bebidas que desaparecen sin pasar por caja.'] as $problem)<li class="flex gap-3"><span class="text-rose-500" aria-hidden="true">×</span>{{ $problem }}</li>@endforeach</ul></article>
                    <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-7 sm:p-9"><p class="text-xs uppercase tracking-widest text-emerald-700">Con el equipo correcto</p><h3 class="mt-3 text-2xl font-bold">Con PuntoCancha <span class="text-emerald-600">todo juega a favor.</span></h3><ul class="mt-7 space-y-5 text-sm text-slate-700">@foreach (['Grilla interactiva en tiempo real por cancha.', 'Tus jugadores reservan solos desde la web.', 'Cada botella vendida descuenta stock al instante.', 'El balance del mes, a un clic y sin Excel.'] as $solution)<li class="flex gap-3"><svg class="h-5 w-5 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><use href="#pc-check"/></svg>{{ $solution }}</li>@endforeach</ul></article>
                </div>
            </div>
        </section>
        <section id="caracteristicas" class="scroll-mt-6 bg-white px-5 py-20 text-slate-900 lg:px-8 lg:py-24">
            <div class="mx-auto max-w-7xl"><p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Un solo equipo. Todas las herramientas.</p><h2 class="mt-4 max-w-2xl text-3xl font-bold tracking-tight sm:text-4xl">Desde el primer turno<br>hasta el último cierre de caja.</h2>
                <div class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ([
                        ['icon' => 'calendar', 'title' => 'Agenda Inteligente y Turnos Online', 'body' => 'Cada cancha, cada horario, una sola agenda. Bloquea solapamientos en tiempo real y ofrece reservas online sin llamadas.', 'points' => ['Visualización por cancha', 'Tarifas de día y noche con luz', 'Turnos sin reservas duplicadas']],
                        ['icon' => 'pos', 'title' => 'Punto de Venta e Inventario', 'body' => 'Isotónicas, aguas, cervezas, pelotas y snacks. Vende en segundos en el mesón o carga el consumo directo a la cuenta del turno.', 'points' => ['Cuentas abiertas por cancha', 'Descuento de stock automático', 'Alertas para reponer a tiempo']],
                        ['icon' => 'chart', 'title' => 'Analítica Financiera Automática', 'body' => 'Conoce las horas peak, la ocupación y el rendimiento del recinto. Decisiones con números claros, no con intuiciones.', 'points' => ['Ingresos de canchas y quiosco', 'Registro de gastos operativos', 'Tu ganancia real, sin planillas']],
                    ] as $feature)
                        <article class="rounded-2xl border border-slate-200 p-7 transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg motion-reduce:transform-none"><span class="flex h-12 w-12 items-center justify-center rounded-xl bg-slate-950 text-lime-300"><svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><use href="#pc-{{ $feature['icon'] }}"/></svg></span><h3 class="mt-6 text-xl font-bold leading-snug">{{ $feature['title'] }}</h3><p class="mt-4 text-sm leading-relaxed text-slate-500">{{ $feature['body'] }}</p><ul class="mt-6 space-y-3 text-xs font-medium">@foreach ($feature['points'] as $point)<li class="flex items-center gap-2"><span class="h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>{{ $point }}</li>@endforeach</ul></article>
                    @endforeach
                </div>
                <p class="mt-7 rounded-xl bg-slate-50 p-5 text-center text-xs font-medium leading-relaxed text-slate-600 sm:text-sm">Ingresos Canchas + Ventas Quiosco − Gastos Operativos = <span class="font-bold text-emerald-700">Ganancia Real</span></p>
            </div>
        </section>
        <section id="como-funciona" class="scroll-mt-6 border-t border-slate-200 bg-slate-50 px-5 py-20 text-slate-900 lg:px-8">
            <div class="mx-auto max-w-7xl"><div class="text-center"><p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Listos para salir a la cancha</p><h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Menos vueltas. Más juego.</h2></div><ol class="mt-12 grid gap-8 md:grid-cols-3">
                @foreach ([['Crea tu club', 'Elige tu plan y configura tu recinto de pádel, futbolito o tenis.'], ['Ordena tu operación', 'Agrega tus canchas, horarios, tarifas y productos del quiosco.'], ['Empieza a jugar en equipo', 'Gestiona reservas, registra ventas y revisa los números de tu club.']] as [$title, $body])<li class="border-t border-slate-300 pt-7"><span class="text-xs font-bold tracking-widest text-emerald-700">0{{ $loop->iteration }} /</span><h3 class="mt-4 text-lg font-bold">{{ $title }}</h3><p class="mt-3 max-w-xs text-sm leading-relaxed text-slate-500">{{ $body }}</p></li>@endforeach
            </ol></div>
        </section>
        <section id="planes" class="relative scroll-mt-6 overflow-hidden px-5 py-20 lg:px-8 lg:py-24">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-96 bg-gradient-to-b from-emerald-950/40 to-transparent" aria-hidden="true"></div>
            <div class="relative mx-auto max-w-7xl"><div class="text-center"><p class="text-xs font-bold uppercase tracking-[0.2em] text-lime-300">Una inversión que juega a tu favor</p><h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Un plan para cada cancha.</h2><p class="mt-4 text-sm text-slate-400">Suscripción fija. Sin comisiones por reserva. Sin sorpresas.</p></div>
                <div class="my-10 flex flex-col items-center gap-3"><div class="inline-flex rounded-full border border-slate-700 bg-slate-900 p-1" role="group" aria-label="Frecuencia de facturación"><button type="button" @click="annual = false" :aria-pressed="!annual" :class="!annual ? 'bg-white text-slate-950' : 'text-slate-400'" class="rounded-full px-6 py-2.5 text-sm font-semibold transition">Mensual</button><button type="button" @click="annual = true" :aria-pressed="annual" :class="annual ? 'bg-white text-slate-950' : 'text-slate-400'" class="rounded-full px-6 py-2.5 text-sm font-semibold transition">Anual</button></div><p class="text-xs font-medium text-lime-300">↳ Ahorra un 20% pagando anual</p></div>
                <div class="grid items-stretch gap-6 lg:grid-cols-3">
                    @foreach ($plans as $plan)
                        <article class="relative flex flex-col rounded-2xl border p-7 sm:p-8 {{ $loop->index === 1 ? 'border-emerald-400 bg-slate-900 ring-1 ring-emerald-400' : 'border-slate-800 bg-slate-900/50' }}">
                            @if ($loop->index === 1)<span class="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-lime-300 px-4 py-1 text-[10px] font-extrabold uppercase tracking-widest text-slate-950">Más popular</span>@endif
                            <h3 class="text-lg font-bold">{{ $plan['name'] }}</h3><p class="mt-3 min-h-10 text-xs leading-relaxed text-slate-400">{{ $plan['description'] }}</p>
                            <div class="mt-7 flex items-baseline gap-1" aria-live="polite"><span class="text-4xl font-extrabold tracking-tight" x-text="annual ? '$'.concat('{{ $plan['annual'] }}') : '$'.concat('{{ $plan['price'] }}')">&#36;{{ $plan['price'] }}</span><span class="text-xs text-slate-400">CLP / mes</span></div>
                            <p class="mt-2 min-h-8 text-[11px] text-slate-400" x-text="annual ? 'Cobro anual: $'.concat('{{ $plan['total'] }} CLP') : 'Facturación mensual'">Facturación mensual</p>
                            <a href="{{ $loop->last ? 'https://wa.me/56931403167?text=Hola%2C%20quiero%20conocer%20el%20plan%20Complejo%20Premium%20de%20PuntoCancha' : route('register') }}" class="mt-5 rounded-lg px-4 py-3.5 text-center text-sm font-bold transition {{ $loop->index === 1 ? 'bg-lime-300 text-slate-950 hover:bg-lime-200' : 'border border-slate-600 hover:border-lime-300 hover:text-lime-300' }}">{{ $plan['cta'] }}</a>
                            <div class="my-7 border-t border-slate-800"></div><p class="mb-4 text-[10px] uppercase tracking-widest text-slate-500">Tu club incluye</p><ul class="space-y-4">@foreach ($plan['features'] as $feature)<li class="flex gap-3 text-xs leading-relaxed text-slate-300"><svg class="h-4 w-4 shrink-0 text-lime-300" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><use href="#pc-check"/></svg>{{ $feature }}</li>@endforeach</ul>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
        <section class="bg-white px-5 py-20 text-slate-900 lg:px-8 lg:py-24"><div class="mx-auto grid max-w-7xl gap-10 lg:grid-cols-5">
            <div class="lg:col-span-2"><p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">Sin dudas en el marcador</p><h2 class="mt-4 text-3xl font-bold tracking-tight sm:text-4xl">Buenas preguntas.<br>Respuestas claras.</h2><p class="mt-5 text-sm text-slate-500">Todo lo que necesitas saber antes de dar el siguiente paso.</p></div>
            <div class="divide-y divide-slate-200 border-y border-slate-200 lg:col-span-3">
                @foreach ([
                    ['¿Cobran comisión por cada reserva de los jugadores?', 'No. PuntoCancha funciona con una suscripción fija mensual, sin comisiones por cada reserva y sin sorpresas.'],
                    ['¿Mis clientes necesitan descargar una aplicación pesada?', 'No. Pueden consultar y reservar desde un enlace web en su celular, sin instalar ninguna aplicación.'],
                    ['¿Puedo cargar el consumo de bebidas a la misma reserva de la cancha?', 'Sí. El POS permite vincular las ventas a la cuenta del turno, para saldar los consumos e hidratación al finalizar el partido.'],
                ] as [$question, $answer])<details class="group py-6"><summary class="flex cursor-pointer list-none items-center justify-between gap-5 text-sm font-semibold">{{ $question }}<svg class="h-5 w-5 shrink-0 text-emerald-700 transition group-open:rotate-45" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><use href="#pc-plus"/></svg></summary><p class="mt-4 pr-8 text-sm leading-relaxed text-slate-500">{{ $answer }}</p></details>@endforeach
            </div>
        </div></section>
        <section class="bg-lime-300 px-5 py-16 text-slate-950 lg:px-8"><div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-8 md:flex-row md:items-center"><div><p class="text-xs font-bold uppercase tracking-widest">Tu próxima jugada empieza aquí</p><h2 class="mt-3 text-3xl font-extrabold tracking-tight sm:text-4xl">Que tu club crezca.<br>Que el caos se quede afuera.</h2></div><a href="{{ route('register') }}" class="inline-flex items-center gap-6 rounded-lg bg-slate-950 px-7 py-4 text-sm font-bold text-white transition hover:bg-slate-800">Comenzar prueba gratis <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><use href="#pc-arrow"/></svg></a></div></section>
    </main>
    <footer class="px-5 py-10 lg:px-8"><div class="mx-auto max-w-7xl"><div class="flex flex-col justify-between gap-8 sm:flex-row">
        <div><a href="{{ url('/') }}" class="flex items-center gap-2 text-lg font-bold"><svg class="h-6 w-6 text-lime-300" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><use href="#pc-court"/></svg>PuntoCancha.</a><p class="mt-3 text-xs text-slate-500">Tecnología para clubes que juegan en serio.</p></div>
        <div class="flex flex-wrap items-start gap-6 text-xs text-slate-400"><a href="#planes" class="hover:text-lime-300">Planes y precios</a><button onclick="document.getElementById('terms').showModal()" class="hover:text-lime-300" type="button">Términos</button><a href="https://wa.me/56931403167?text=Hola%2C%20necesito%20informacion%20sobre%20PuntoCancha" class="hover:text-lime-300">Soporte por WhatsApp ↗</a></div>
    </div><div class="mt-8 flex flex-wrap justify-between gap-3 border-t border-slate-800 pt-6 text-[10px] text-slate-500"><p>© {{ now()->year }} PuntoCancha. Todos los derechos reservados.</p><p>Pádel · Futbolito · Tenis</p></div></div></footer>

    <dialog id="demo" aria-labelledby="demo-title" class="w-full max-w-3xl rounded-2xl border border-slate-700 bg-slate-900 p-6 text-slate-100 shadow-2xl backdrop:bg-slate-950/90 sm:p-10">
        <div class="flex items-start justify-between gap-5"><div><p class="text-xs uppercase tracking-widest text-lime-300">Demostración ilustrativa</p><h2 id="demo-title" class="mt-3 text-2xl font-bold">Un turno. Toda la operación conectada.</h2></div><form method="dialog"><button class="rounded-lg border border-slate-600 px-3 py-2 hover:bg-slate-800" aria-label="Cerrar demostración">×</button></form></div>
        <p class="mt-4 text-sm leading-relaxed text-slate-400">Estos datos de ejemplo muestran cómo se conectan una reserva y sus consumos; no son datos de un club real.</p><ol class="mt-8 grid gap-4 sm:grid-cols-3">
            @foreach ([['01 · Reserva', 'Pádel 01 · 19:00', '$20.000'], ['02 · Consumo', '2 aguas a la cuenta', '$3.000'], ['03 · Cierre', 'Total del turno', '$23.000']] as [$step, $detail, $amount])<li class="rounded-xl border border-slate-700 p-5"><p class="text-xs text-lime-300">{{ $step }}</p><p class="mt-4 text-sm">{{ $detail }}</p><p class="mt-2 text-2xl font-bold">{{ $amount }}</p></li>@endforeach
        </ol><a href="{{ route('register') }}" class="mt-8 inline-block rounded-lg bg-lime-300 px-5 py-3 text-sm font-bold text-slate-950">Crear mi cuenta</a>
    </dialog>
    <dialog id="terms" aria-labelledby="terms-title" class="w-full max-w-lg rounded-2xl border border-slate-700 bg-slate-900 p-7 text-slate-100 backdrop:bg-slate-950/90"><h2 id="terms-title" class="text-xl font-bold">Condiciones de contratación</h2><p class="mt-4 text-sm leading-relaxed text-slate-300">Esta página presenta los planes de PuntoCancha. La selección de un plan te lleva al registro y no realiza un cobro. Las condiciones completas de contratación, vigencia de la prueba y soporte deben confirmarse antes de activar una suscripción.</p><form method="dialog" class="mt-6"><button class="rounded-lg bg-lime-300 px-5 py-3 text-sm font-semibold text-slate-950">Entendido</button></form></dialog>
</body>
</html>
