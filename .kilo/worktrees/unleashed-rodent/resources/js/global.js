/**
 * Global Telemetry System
 * Yalıhan Emlak - Production-Grade Observability Layer
 *
 * Purpose:
 * - Capture frontend crashes instantly
 * - Measure wizard latency
 * - Log API failures
 * - Early regression detection in production
 *
 * @version 1.0.0
 * @since 2026-02-15
 */

// ✅ Global Telemetry Object
window.__telemetry = window.__telemetry || {
    /**
     * Capture telemetry event
     * @param {string} event - Event name
     * @param {object} payload - Event data
     */
    capture: function (event, payload) {
        console.log('[telemetry]', event, payload);

        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (!csrfMeta) return; // Skip telemetry if CSRF is not available
            
            // Skip if no session cookie exists (avoids unexpected auth drops/401s)
            if (!document.cookie.includes('laravel_session')) return;

            fetch('/admin/telemetry', {
                method: 'POST',
                keepalive: true,
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfMeta.content
                },
                body: JSON.stringify({
                    event: event,
                    payload: payload,
                    url: location.href,
                    ts: Date.now(),
                })
            }).catch(() => { /* silent fail for telemetry */ });
        } catch (error) {
            console.warn('[telemetry] Failed to send telemetry:', error);
        }
    },
};

// ✅ Window Error Hook - Global JavaScript errors
window.addEventListener('error', function (e) {
    window.__telemetry.capture('window_error', {
        message: e.message,
        file: e.filename,
        line: e.lineno,
        column: e.colno,
        stack: e.error ? e.error.stack : null,
    });
});

// ✅ Unhandled Promise Rejections
window.addEventListener('unhandledrejection', function (e) {
    window.__telemetry.capture('unhandled_promise', {
        reason: String(e.reason),
        stack: e.reason && e.reason.stack ? e.reason.stack : null,
    });
});

// ✅ Alpine.js Error Hook
document.addEventListener('alpine:init', function () {
    if (window.Alpine && Alpine.onError) {
        Alpine.onError(function (error, component) {
            window.__telemetry.capture('alpine_error', {
                message: error.message,
                stack: error.stack,
                component: component && component.el ? component.el.tagName : null,
            });
        });
    }
});

// ✅ Export for module systems (optional)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.__telemetry;
}
