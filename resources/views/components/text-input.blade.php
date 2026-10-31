@props(['disabled' => false])
<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/70 text-slate-900 dark:text-slate-100 placeholder:text-slate-400 dark:placeholder:text-slate-600 focus:border-emerald-500 dark:focus:border-lime-300 focus:ring-emerald-500 dark:focus:ring-lime-300 shadow-sm']) }}>
