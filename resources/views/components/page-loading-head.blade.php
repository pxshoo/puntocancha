<style>
    .pc-loader { display: none; }
    .pc-loading .pc-loader { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #0f172a; transition: opacity 180ms ease; }
    .dark.pc-loading .pc-loader { background: #020617; color: #f1f5f9; }
    .pc-loading-leave .pc-loader { opacity: 0; pointer-events: none; }
    .pc-loader-content { display: grid; justify-items: center; gap: 16px; padding: 24px; text-align: center; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
    .pc-loader-logo { display: grid; place-items: center; width: 72px; height: 72px; border-radius: 20px; color: rgb(var(--brand-on, 255 255 255)); background: rgb(var(--brand-primary, 5 150 105)); box-shadow: 0 12px 36px rgb(var(--brand-primary, 5 150 105) / .2); animation: pc-loader-pulse 1.4s ease-in-out infinite; }
    .pc-loader-name { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -.7px; }
    .pc-loader-status { display: flex; align-items: center; gap: 10px; margin: 0; font-size: 13px; color: #64748b; }
    .dark .pc-loader-status { color: #94a3b8; }
    .pc-loader-spinner { width: 15px; height: 15px; border: 2px solid #cbd5e1; border-top-color: rgb(var(--brand-primary, 5 150 105)); border-radius: 50%; animation: pc-loader-spin .8s linear infinite; }
    @keyframes pc-loader-spin { to { transform: rotate(360deg); } }
    @keyframes pc-loader-pulse { 50% { transform: translateY(-3px) rotate(-4deg); } }
    @media (prefers-reduced-motion: reduce) { .pc-loader, .pc-loader-logo, .pc-loader-spinner { animation: none !important; transition: none !important; } }
</style>
<script>
    (() => {
        const root = document.documentElement;
        let finished = false;
        let fallback;
        const finish = () => {
            if (finished) return;
            finished = true;
            clearTimeout(fallback);
            root.classList.add('pc-loading-leave');
            root.removeAttribute('aria-busy');
            setTimeout(() => root.classList.remove('pc-loading', 'pc-loading-leave'), 200);
        };
        root.classList.add('pc-loading');
        root.setAttribute('aria-busy', 'true');
        window.addEventListener('pageshow', finish, { once: true });
        document.addEventListener('DOMContentLoaded', finish, { once: true });
        fallback = setTimeout(finish, 4000);
    })();
</script>
