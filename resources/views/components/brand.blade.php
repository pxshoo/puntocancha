@props(['href' => '/', 'club' => null])
<a href="{{ $href }}" {{ $attributes->class(['inline-flex min-w-0 items-center gap-2.5 text-xl font-extrabold tracking-tight text-slate-900 dark:text-slate-100']) }} aria-label="{{ $club?->name ?? 'PuntoCancha' }}, inicio">
    @if($club?->logoUrl())
        <img src="{{ $club->logoUrl() }}" alt="" class="h-11 w-11 shrink-0 rounded-xl bg-white object-contain p-1">
    @else
        <span class="flex h-10 w-10 shrink-0 -rotate-6 items-center justify-center rounded-xl bg-brand text-brand-on"><svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 12h18M12 3v18M3 7h18M3 17h18"/></svg></span>
    @endif
    <span class="truncate">{{ $club?->name ?? 'PuntoCancha' }}<span class="text-brand-content">.</span></span>
</a>
