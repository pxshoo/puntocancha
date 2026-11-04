@props(['disabled' => false])
<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-brand focus:ring-brand-ring shadow-sm']) }}>
