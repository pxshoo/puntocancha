<div class="fixed bottom-5 right-5 z-40">
    <button type="button" x-data="themeToggle" @click="toggle()" :aria-pressed="dark" :aria-label="dark ? 'Activar modo claro' : 'Activar modo oscuro'" class="inline-flex min-h-12 items-center justify-center gap-3 rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-lg shadow-slate-900/10 transition hover:border-emerald-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-lime-300 dark:shadow-black/30 dark:hover:border-lime-300 dark:focus-visible:ring-lime-300 dark:focus-visible:ring-offset-slate-950">
        <span aria-hidden="true" class="text-lg leading-none" x-text="dark ? '☀' : '☾'">☾</span>
        <span x-text="dark ? 'Modo claro' : 'Modo oscuro'">Modo oscuro</span>
    </button>
</div>
