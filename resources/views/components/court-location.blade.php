@props(['court', 'club'])
@php($location = collect([$court?->address ?: $club->address, $court?->city ?: $club->city])->filter()->implode(', '))
@if($location)
    <div class="mt-3 flex flex-wrap items-center gap-2 text-sm text-slate-600 dark:text-slate-400">
        <svg class="h-4 w-4 shrink-0 text-brand-content" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
        <span>{{ $location }}</span>
        <a href="https://www.google.com/maps/search/?api=1&amp;query={{ rawurlencode($location) }}" target="_blank" rel="noopener noreferrer" class="rounded px-2 py-1 font-semibold text-brand-content underline underline-offset-4">Ver en mapa <span class="sr-only">: {{ $court?->name ?? $club->name }}</span> ↗</a>
    </div>
@endif
