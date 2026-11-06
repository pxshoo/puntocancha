export function initInterfaceMotion() {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    if (reducedMotion.matches || !('IntersectionObserver' in window)) return;
    const candidates = document.querySelectorAll('[data-reveal], main .surface-card, main article.rounded-2xl, main section.rounded-2xl, main form.rounded-xl');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            if (!reducedMotion.matches && entry.target.getClientRects().length) {
                entry.target.classList.add('pc-enter');
                entry.target.addEventListener('animationend', () => entry.target.classList.remove('pc-enter'), { once: true });
            }
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.08 });
    candidates.forEach(target => {
        if (!target.closest('[role="dialog"], dialog, [data-no-motion]') &&
            !target.parentElement?.closest('[data-reveal], .surface-card')) {
            observer.observe(target);
        }
    });
    reducedMotion.addEventListener?.('change', event => {
        if (!event.matches) return;
        observer.disconnect();
        document.querySelectorAll('.pc-enter').forEach(target => target.classList.remove('pc-enter'));
    }, { once: true });
}
