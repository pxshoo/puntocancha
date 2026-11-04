export function initInterfaceMotion() {
    const targets = document.querySelectorAll('[data-reveal]');
    if (!targets.length || window.matchMedia('(prefers-reduced-motion: reduce)').matches || !('IntersectionObserver' in window)) return;
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('pc-enter');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.08 });
    targets.forEach(target => observer.observe(target));
}
