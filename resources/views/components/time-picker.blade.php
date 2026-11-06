@props(['name', 'label', 'value' => '08:00'])
<div class="relative min-w-0" x-data="timePicker(@js($value), @js($name === 'closes_at'))" @click.outside="open = false" @keydown.escape.stop="if (open) { open = false; $refs.toggle.focus(); }">
    <label for="time-{{ $name }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
    <div class="flex min-h-16 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 p-2 shadow-sm transition-all duration-200 focus-within:border-brand focus-within:ring-2 focus-within:ring-brand-ring/20 dark:border-slate-700 dark:bg-slate-950">
        <input id="time-{{ $name }}" name="{{ $name }}" type="text" inputmode="numeric" pattern="(?:[01][0-9]|2[0-3]):[0-5][0-9]{{ $name === 'closes_at' ? '|24:00' : '' }}" maxlength="5" placeholder="08:00" value="{{ $value }}" x-model="time" required aria-describedby="hint-{{ $name }}" class="min-w-0 flex-1 border-0 bg-transparent px-3 py-1 text-2xl font-bold tabular-nums tracking-wider text-slate-900 shadow-none focus:ring-0 dark:text-white">
        <button x-cloak x-ref="toggle" type="button" @click="open = !open" :aria-expanded="open" aria-controls="picker-{{ $name }}" aria-label="Elegir {{ mb_strtolower($label) }}" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand-content hover:bg-brand/20 focus:outline-none focus:ring-2 focus:ring-brand-ring"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></button>
    </div>
    <p id="hint-{{ $name }}" class="mt-1.5 text-[11px] text-slate-500 dark:text-slate-400">Formato 24 h · Escribe o elige una hora. {{ $name === 'closes_at' ? '24:00 = medianoche.' : '' }}</p>
    <div x-cloak x-show="open" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" id="picker-{{ $name }}" class="absolute inset-x-0 top-full z-30 mt-2 rounded-2xl border border-slate-200 bg-white p-4 shadow-xl shadow-slate-900/15 dark:border-slate-700 dark:bg-slate-900">
        <div class="mb-3 flex items-center justify-between gap-2"><p class="text-xs font-semibold text-slate-500">Hora</p><span class="rounded-lg bg-brand/10 px-2 py-1 text-sm font-bold tabular-nums text-brand-content" x-text="time"></span></div>
        <div class="grid grid-cols-6 gap-1.5">
            @foreach(range(0, $name === 'closes_at' ? 24 : 23) as $hour)
                <button type="button" @click="setHour({{ $hour }})" :aria-pressed="hour === @js(str_pad((string) $hour, 2, '0', STR_PAD_LEFT))" :class="hour === @js(str_pad((string) $hour, 2, '0', STR_PAD_LEFT)) ? 'bg-brand text-brand-on shadow-sm' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'" class="min-h-10 rounded-lg py-2 text-sm font-semibold tabular-nums transition-all duration-200 focus:ring-2 focus:ring-brand-ring">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}</button>
            @endforeach
        </div>
        <p class="mb-2 mt-4 text-xs font-semibold text-slate-500">Minutos</p>
        <div class="grid grid-cols-4 gap-2">@foreach([0, 15, 30, 45] as $minute)
            <button type="button" @click="setMinute({{ $minute }})" :aria-pressed="minute === @js(str_pad((string) $minute, 2, '0', STR_PAD_LEFT))" :class="minute === @js(str_pad((string) $minute, 2, '0', STR_PAD_LEFT)) ? 'border-brand bg-brand/10 text-brand-content' : 'border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300'" class="min-h-10 rounded-lg border py-2 text-sm font-semibold tabular-nums hover:border-brand focus:ring-2 focus:ring-brand-ring">:{{ str_pad((string) $minute, 2, '0', STR_PAD_LEFT) }}</button>
        @endforeach</div>
        <button type="button" @click="open = false; $refs.toggle.focus()" class="mt-4 flex min-h-11 w-full items-center justify-center rounded-xl bg-brand px-4 text-sm font-bold text-brand-on hover:bg-brand-hover">Listo</button>
    </div>
    <x-input-error :messages="$errors->get($name)" class="mt-2" />
</div>
