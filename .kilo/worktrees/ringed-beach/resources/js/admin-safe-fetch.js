/**
 * safeJsonFetch — Yalıhan Admin Panel Güvenli Fetch Helper
 * Context7 Standard: C7-SAFE-FETCH-2025-02-14
 *
 * Tüm admin API çağrıları bu helper üzerinden yapılmalıdır.
 * Sağladıkları:
 *  - credentials: 'same-origin' (session cookie iletimi)
 *  - Accept: application/json (expectsJson() tetikleme)
 *  - X-Requested-With: XMLHttpRequest
 *  - X-CSRF-TOKEN (meta tag'den otomatik)
 *  - Content-type kontrolü (HTML parse engellemesi)
 *  - Auth state standardizasyonu (AUTH_REQUIRED, NON_JSON_RESPONSE)
 */

(function () {
    'use strict';

    /**
     * @param {string} url
     * @param {RequestInit} [opts={}]
     * @returns {Promise<{ok: boolean, data: any|null, error: string|null, authRequired: boolean}>}
     */
    async function safeJsonFetch(url, opts = {}) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        const defaultHeaders = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        // CSRF token sadece mutasyon isteklerinde ekle (GET hariç)
        const method = (opts.method || 'GET').toUpperCase();
        if (method !== 'GET' && method !== 'HEAD') {
            defaultHeaders['X-CSRF-TOKEN'] = csrfToken;
        }

        // Content-Type sadece body varsa ekle (FormData hariç — browser otomatik ayarlar)
        if (opts.body && !(opts.body instanceof FormData)) {
            defaultHeaders['Content-Type'] = 'application/json';
        }

        const mergedHeaders = {
            ...defaultHeaders,
            ...(opts.headers || {}),
        };

        const fetchOpts = {
            ...opts,
            headers: mergedHeaders,
            credentials: 'same-origin',
        };

        try {
            const response = await fetch(url, fetchOpts);

            // HTTP durum kodunu al
            const httpKodu = response.status; // @context7:exempt — Native JS API

            // Auth/permission hataları
            if (httpKodu === 401 || httpKodu === 403 || httpKodu === 419) {
                // response body'yi temizle
                const bodyText = await response.text().catch(() => '');
                const snippet = bodyText.substring(0, 200);

                console.warn(`[safeJsonFetch] AUTH_REQUIRED: ${url} → ${httpKodu}`, snippet);

                return {
                    ok: false,
                    data: null,
                    error: 'AUTH_REQUIRED',
                    httpKodu: httpKodu,
                    authRequired: true,
                };
            }

            // response.ok değilse (4xx/5xx)
            if (!response.ok) {
                const bodyText = await response.text().catch(() => '');
                const snippet = bodyText.substring(0, 200);

                console.error(`[safeJsonFetch] HTTP Error: ${url} → ${httpKodu}`, snippet);

                // JSON parse deneme
                try {
                    const errorData = JSON.parse(bodyText);
                    return {
                        ok: false,
                        data: errorData,
                        error: errorData.message || errorData.error?.message || `HTTP ${httpKodu}`,
                        httpKodu: httpKodu,
                        authRequired: false,
                    };
                } catch {
                    return {
                        ok: false,
                        data: null,
                        error: `HTTP ${httpKodu}: ${snippet}`,
                        httpKodu: httpKodu,
                        authRequired: false,
                    };
                }
            }

            // Content-Type kontrolü — HTML yanıtı JSON olarak parse etme
            const contentType = response.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                const bodyText = await response.text().catch(() => '');
                const snippet = bodyText.substring(0, 200);

                console.error(
                    `[safeJsonFetch] NON_JSON_RESPONSE: ${url}`,
                    `Content-Type: ${contentType}`,
                    snippet
                );

                return {
                    ok: false,
                    data: null,
                    error: 'NON_JSON_RESPONSE',
                    httpKodu: httpKodu,
                    authRequired: false,
                };
            }

            // JSON parse
            const data = await response.json();
            return {
                ok: true,
                data: data,
                error: null,
                httpKodu: httpKodu,
                authRequired: false,
            };
        } catch (networkError) {
            console.error(`[safeJsonFetch] Network Error: ${url}`, networkError.message);
            return {
                ok: false,
                data: null,
                error: `NETWORK_ERROR: ${networkError.message}`,
                httpKodu: 0,
                authRequired: false,
            };
        }
    }

    // Global'e ekle
    window.safeJsonFetch = safeJsonFetch;
})();
