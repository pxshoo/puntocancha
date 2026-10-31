@php
    $menuItems = [
        ['route' => 'settings.index', 'pattern' => ['settings.*', 'club.settings.*', 'profile.*'], 'label' => 'Configuración', 'icon' => 'M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6M9 9h.01M15 9h.01'],
        ['route' => 'courts.index', 'pattern' => 'courts.*', 'label' => 'Canchas y tarifas', 'icon' => 'M3 3h18v18H3zM3 12h18M12 3v18M3 7h18M3 17h18'],
        ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Resumen del club', 'icon' => 'M3 3v18h18M8 16v-4m5 4V8m5 8V5'],
        ['route' => 'bookings.index', 'pattern' => 'bookings.*', 'label' => 'Agenda de canchas', 'icon' => 'M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z'],
        ['route' => 'pos.index', 'pattern' => 'pos.*', 'label' => 'Punto de venta', 'icon' => 'm4 10 2-7h12l2 7M4 10h16v11H4zM8 16h8M8 6h8M12 10v3'],
        ['route' => 'inventory.index', 'pattern' => 'inventory.*', 'label' => 'Inventario', 'icon' => 'm3 7 9-4 9 4-9 4-9-4Zm0 0v10l9 4 9-4V7M12 11v10'],
    ];
    $settingsItem = array_shift($menuItems);
    $courtItem = array_shift($menuItems);
    array_splice($menuItems, 2, 0, [$courtItem]);
    if (auth()->user()->role === 'club_admin') { $menuItems[] = $settingsItem; }
@endphp
<aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 lg:flex" aria-label="Navegación del club">
    <div class="px-6 py-8"><x-brand :club="auth()->user()?->club" :href="route('dashboard')" /></div>
    <div class="mx-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white/60 dark:bg-slate-900/60 p-4"><p class="text-[9px] font-semibold uppercase tracking-widest text-slate-600 dark:text-slate-400">Tu recinto</p><p class="mt-2 truncate text-sm font-semibold text-slate-700 dark:text-slate-300">{{ auth()->user()->club?->name }}</p><p class="mt-2 text-[10px] text-brand-content">● Espacio de tu club</p></div>
    <nav class="mt-8 space-y-2 px-4">
        <p class="px-3 pb-2 text-[9px] uppercase tracking-widest text-slate-500 dark:text-slate-500">Administración</p>
        @foreach ($menuItems as $item)
            <a href="{{ route($item['route']) }}" @if(request()->routeIs(...(array) $item['pattern'])) aria-current="page" @endif class="flex items-center gap-3 rounded-lg border px-3 py-3 text-sm font-medium transition {{ request()->routeIs(...(array) $item['pattern']) ? 'border-brand/20 bg-brand/10 text-brand-content' : 'border-transparent text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100' }}"><svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $item['icon'] }}"/></svg>{{ $item['label'] }}</a>
        @endforeach
    </nav>
    <div class="mt-auto p-4">
        <a href="https://wa.me/56931403167" class="mb-5 block rounded-xl border border-brand/20 bg-emerald-500/5 p-4"><p class="text-xs font-semibold text-brand-content">Estamos en tu equipo ↗</p><p class="mt-2 text-[11px] leading-relaxed text-slate-600 dark:text-slate-400">¿Necesitas una mano con tu club? Hablemos.</p></a>
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg p-2 hover:bg-white dark:hover:bg-slate-900"><span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-800 text-sm font-bold text-brand-content">{{ mb_substr(auth()->user()->name, 0, 1) }}</span><span class="min-w-0"><span class="block truncate text-xs font-semibold">{{ auth()->user()->name }}</span><span class="text-[10px] text-slate-600 dark:text-slate-400">Mi perfil y acceso</span></span></a>
        <form method="POST" action="{{ route('logout') }}" class="mt-3">@csrf<button type="submit" class="w-full rounded-lg px-3 py-2 text-left text-xs text-slate-600 dark:text-slate-400 hover:bg-white dark:hover:bg-slate-900 hover:text-slate-900 dark:hover:text-slate-100">Cerrar sesión →</button></form>
    </div>
</aside>
<header class="border-b border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950 px-5 py-4 lg:hidden">
    <div class="flex items-center justify-between gap-3"><x-brand :club="auth()->user()?->club" :href="route('dashboard')" /><details class="group relative"><summary class="cursor-pointer list-none rounded-lg border border-slate-200 dark:border-slate-800 px-3 py-2 text-xs font-semibold">Menú <span class="ml-1 text-brand-content" aria-hidden="true">+</span></summary>
        <nav class="absolute right-0 top-12 z-40 w-64 space-y-1 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-3 shadow-2xl" aria-label="Navegación móvil">
            @foreach ($menuItems as $item)<a href="{{ route($item['route']) }}" @if(request()->routeIs(...(array) $item['pattern'])) aria-current="page" @endif class="block rounded-lg px-4 py-3 text-sm {{ request()->routeIs(...(array) $item['pattern']) ? 'bg-brand/10 text-brand-content' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800' }}">{{ $item['label'] }}</a>@endforeach
            <a href="{{ route('profile.edit') }}" class="block rounded-lg px-4 py-3 text-sm text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800">Mi perfil</a>
            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-200 dark:border-slate-800 pt-2">@csrf<button class="w-full rounded-lg px-4 py-3 text-left text-sm text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">Cerrar sesión</button></form>
        </nav>
    </details></div>
</header>
