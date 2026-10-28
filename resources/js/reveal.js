const root = document.documentElement;
const elements = Array.from(document.querySelectorAll('[data-reveal]'));
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

const show = (element) => {
    element.setAttribute('data-reveal-shown', '');
};

if (elements.length > 0 && 'IntersectionObserver' in window && !reducedMotion.matches) {
    root.classList.add('reveal-enabled');

    const observer = new IntersectionObserver(
        (entries) => {
            entries
                .filter((entry) => entry.isIntersecting)
                .forEach((entry, index) => {
                    entry.target.style.setProperty('--reveal-delay', `${Math.min(index, 4) * 70}ms`);
                    show(entry.target);
                    observer.unobserve(entry.target);
                });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.05 },
    );

    elements.forEach((element) => observer.observe(element));

    document.addEventListener('focusin', (event) => {
        const container = event.target.closest('[data-reveal]');

        if (container) {
            show(container);
        }
    });

    reducedMotion.addEventListener('change', (event) => {
        if (!event.matches) {
            return;
        }

        elements.forEach(show);
        root.classList.remove('reveal-enabled');
        observer.disconnect();
    });
}
