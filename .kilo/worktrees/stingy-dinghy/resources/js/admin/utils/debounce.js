export function debounce(fn, wait = 300) {
    let timeout;
    return function (...args) {
        const later = () => {
            clearTimeout(timeout);
            fn.apply(this, args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

export function throttle(fn, limit = 300) {
    let inThrottle = false;
    return function (...args) {
        if (!inThrottle) {
            fn.apply(this, args);
            inThrottle = true;
            setTimeout(() => (inThrottle = false), limit);
        }
    };
}
