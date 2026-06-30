/**
 * Wizard Telemetry Helper
 * Yalıhan Emlak - Performance & Latency Measurement
 *
 * Usage:
 * ```javascript
 * import { tStart, tEnd } from './wizard/core/telemetry';
 *
 * async function someAction() {
 *     const timer = tStart('action_name');
 *     // ... do work
 *     tEnd(timer, { extra: 'data' });
 * }
 * ```
 *
 * @version 1.0.0
 * @since 2026-02-15
 */

/**
 * Start performance timer
 * @param {string} name - Timer name/event identifier
 * @returns {object} Timer token with start time
 */
export function tStart(name) {
    return {
        name: name,
        t0: performance.now(),
    };
}

/**
 * End performance timer and capture telemetry
 * @param {object} token - Timer token from tStart()
 * @param {object} extra - Additional data to include in telemetry
 * @returns {number} Duration in milliseconds
 */
export function tEnd(token, extra) {
    const duration = Math.round(performance.now() - token.t0);

    // Capture to global telemetry
    if (window.__telemetry && window.__telemetry.capture) {
        window.__telemetry.capture(token.name, {
            duration_ms: duration,
            ...(extra || {}),
        });
    } else {
        
    }

    return duration;
}

/**
 * Measure async function execution time
 * @param {string} name - Metric name
 * @param {Function} fn - Async function to measure
 * @param {object} context - Optional context data
 * @returns {Promise} Function result
 */
export async function measureAsync(name, fn, context = {}) {
    const timer = tStart(name);

    try {
        const result = await fn();
        tEnd(timer, { ...context, success: true });
        return result;
    } catch (error) {
        tEnd(timer, { ...context, success: false, error: error.message });
        throw error;
    }
}

/**
 * Create a telemetry-wrapped fetch function
 * @param {string} eventName - Base event name for telemetry
 * @returns {Function} Wrapped fetch function
 */
export function createTelemetryFetch(eventName) {
    return async function (url, options = {}) {
        const timer = tStart(eventName);

        try {
            const response = await fetch(url, options);

            tEnd(timer, {
                http_durum_kodu: response.status, // ✅ SAB compliant
                basarili: response.ok, // ✅ SAB compliant
                istek_url: url, // ✅ SAB compliant
            });

            return response;
        } catch (error) {
            tEnd(timer, {
                basarili: false, // ✅ SAB compliant
                hata_mesaji: error.message, // ✅ SAB compliant
                istek_url: url, // ✅ SAB compliant
            });

            throw error;
        }
    };
}
