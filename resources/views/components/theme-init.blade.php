<script>
    (() => {
        const key = 'puntocancha-theme';
        const apply = (value) => {
            const theme = value === 'dark' ? 'dark' : 'light';
            document.documentElement.classList.toggle('dark', theme === 'dark');
            document.documentElement.style.colorScheme = theme;
            const meta = document.querySelector('meta[name="theme-color"]');
            if (meta) meta.content = theme === 'dark' ? '#020617' : '#f8fafc';
            window.dispatchEvent(new CustomEvent('puntocancha-theme-changed'));
        };
        let saved = 'light';
        try { saved = localStorage.getItem(key) || 'light'; } catch {}
        apply(saved);
        window.PuntoCanchaTheme = {
            set(theme) {
                apply(theme);
                try { localStorage.setItem(key, theme === 'dark' ? 'dark' : 'light'); } catch {}
            },
        };
        window.addEventListener('storage', event => {
            if (event.key === key || event.key === null) apply(event.newValue);
        });
        document.addEventListener('DOMContentLoaded', () => {
            apply(document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        }, { once: true });
    })();
</script>
