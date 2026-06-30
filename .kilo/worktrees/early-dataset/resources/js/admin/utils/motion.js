export function shouldReduceMotion() {
    try {
        const mm = !!(
            window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches
        );
        return Boolean(mm);
    } catch {
        return false;
    }
}

export function safeScrollIntoView(el, options = { behavior: 'smooth', block: 'center' }) {
    if (!el) return;
    const opts = shouldReduceMotion() ? { ...options, behavior: 'auto' } : options;
    try {
        el.scrollIntoView(opts);
    } catch {
        el.scrollIntoView();
    }
}
