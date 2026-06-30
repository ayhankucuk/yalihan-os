/**
 * API Helper Utility
 *
 * Merkezi API config kontrolü, hata yönetimi ve standart response handling
 * Context7: C7-API-HELPER-2025-12-15
 *
 * @version 2.0.0
 * @since 2025-12-15
 * @updated 2025-12-15 - Added debouncing, caching, monitoring
 */

/* global window */

class APIHelper {
    // Request cache
    static responseCache = new Map();
    static cacheTTL = 60000; // 1 dakika

    // Debounce timers
    static debounceTimers = new Map();

    // Request monitoring
    static requestLog = [];
    static maxLogSize = 100;
    /**
     * API endpoint'i güvenli şekilde al
     *
     * @param {string} path - Config path (örn: 'admin.photos.upload')
     * @param {object} options - Options
     * @param {boolean} options.required - Endpoint zorunlu mu? (default: true)
     * @param {boolean} options.showError - Hata gösterilsin mi? (default: true)
     * @param {array} options.args - Function endpoint için argümanlar
     * @returns {string|null} Endpoint URL veya null
     */
    static getEndpoint(path, options = {}) {
        const { required = true, showError = true, args = [] } = options;

        const keys = path.split('.');
        let value = window.APIConfig;

        for (const key of keys) {
            if (value === null || value === undefined) {
                if (required) {
                    const errorMsg = `❌ APIConfig.${path} tanımlı değil! api-config.js yüklü mü kontrol edin.`;
                    console.error(errorMsg);
                    if (showError && window.toast) {
                        window.toast.error('API config yüklenemedi. Sayfayı yenileyin.');
                    }
                }
                return null;
            }
            value = value[key];
        }

        // Function endpoint ise çağır
        if (typeof value === 'function') {
            return value(...args);
        }

        return value;
    }

    /**
     * Güvenli fetch wrapper
     *
     * @param {string} path - Config path (örn: 'admin.photos.upload') veya URL string (örn: '/api/v1/...')
     * @param {object} fetchOptions - Fetch options
     * @returns {Promise<Response>}
     */
    static async safeFetch(path, fetchOptions = {}) {
        // Eğer path zaten bir URL string'i ise (http:// veya / ile başlıyorsa) doğrudan kullan
        let endpoint;
        if (path.startsWith('http://') || path.startsWith('https://') || path.startsWith('/')) {
            endpoint = path;
        } else {
            // Config path ise getEndpoint ile al
            endpoint = this.getEndpoint(path, {
                required: true,
                args: fetchOptions.args || [],
            });

            if (!endpoint) {
                throw new APIError(`Endpoint not found: ${path}`, 0);
            }
        }

        // CSRF token otomatik ekle
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const headers = {
            'Accept': 'application/json', // ✅ Laravel'e AJAX isteği olduğunu bildir
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest', // ✅ Laravel'e AJAX isteği olduğunu bildir
            ...fetchOptions.headers,
        };

        // FormData ise Content-Type'ı kaldır (browser otomatik ekler)
        if (fetchOptions.body instanceof FormData) {
            delete headers['Content-Type'];
        }

        // Request loglama (development)
        if (window.ENV === 'development' || window.location.hostname === 'localhost') {
            console.log(`[API] ${fetchOptions.method || 'GET'} ${endpoint}`, fetchOptions);
        }

        try {
            const response = await fetch(endpoint, {
                ...fetchOptions,
                headers,
            });

            return response;
        } catch (error) {
            this.logError(path, error);
            throw error;
        }
    }

    /**
     * Standart response handler
     *
     * @param {Response} response - Fetch response
     * @returns {Promise<object>} Parsed data
     */
    static async handleResponse(response) {
        // Content-Type kontrolü
        const contentType = response.headers.get('content-type');
        const isJson = contentType && contentType.includes('application/json');

        // JSON olmayan response'lar için özel handling
        if (!isJson) {
            // HTML response (örneğin validation error sayfası veya redirect)
            if (response.status >= 300 && response.status < 400) {
                // Redirect response
                const location = response.headers.get('location');
                throw new APIError(
                    `Redirect to: ${location || 'unknown'}`,
                    response.status
                );
            }

            // HTML error sayfası - text olarak oku
            const text = await response.text();
            throw new APIError(
                `Invalid JSON response. Server returned HTML (Status: ${response.status})`,
                response.status,
                { html: text.substring(0, 200) } // İlk 200 karakter
            );
        }

        let data;
        try {
            data = await response.json();
        } catch (error) {
            throw new APIError('Invalid JSON response', response.status);
        }

        if (!response.ok) {
            // Validation errors (422) için özel handling
            if (response.status === 422 && data.errors) {
                throw new APIError(
                    data.message || 'Validation failed',
                    response.status,
                    data.errors
                );
            }

            throw new APIError(
                data.message || 'API request failed',
                response.status,
                data.errors || {}
            );
        }

        // ResponseService format kontrolü
        if (data.success !== undefined) {
            return {
                success: data.success,
                data: data.data || data,
                message: data.message,
                meta: data.meta,
            };
        }

        return { success: true, data };
    }

    /**
     * Güvenli fetch + response handling
     *
     * @param {string} path - Config path
     * @param {object} fetchOptions - Fetch options
     * @param {object} options - Additional options
     * @param {boolean} options.showLoading - Loading state göster (default: false)
     * @param {string} options.loadingKey - Loading key (default: path)
     * @param {HTMLElement|string} options.loadingTarget - Loading target element
     * @returns {Promise<object>} Parsed response data
     */
    static async request(path, fetchOptions = {}, options = {}) {
        const {
            showLoading = false,
            loadingKey = path,
            loadingTarget = null,
        } = options;

        // Loading state'i başlat
        if (showLoading && window.LoadingManager) {
            window.LoadingManager.set(loadingKey, true, loadingTarget);
        }

        try {
            // Request loglama
            this.logRequest(path, {
                method: fetchOptions.method || 'GET',
            });

            const response = await this.safeFetch(path, fetchOptions);
            const result = await this.handleResponse(response);

            // Loading state'i bitir
            if (showLoading && window.LoadingManager) {
                window.LoadingManager.set(loadingKey, false, loadingTarget);
            }

            return result;
        } catch (error) {
            // Loading state'i bitir
            if (showLoading && window.LoadingManager) {
                window.LoadingManager.set(loadingKey, false, loadingTarget);
            }

            this.logError(path, error);
            throw error;
        }
    }

    /**
     * Hata loglama
     *
     * @param {string} path - Config path
     * @param {Error} error - Error object
     */
    static logError(path, error) {
        console.error(`[API Error] ${path}:`, error);

        // Error tracking service'e gönder (varsa)
        if (window.errorTracker) {
            window.errorTracker.captureException(error, { path });
        }

        // Request log'a ekle
        this.logRequest(path, { method: 'ERROR', error: error.message });
    }

    /**
     * Request loglama
     *
     * @param {string} path - Config path
     * @param {object} options - Request options
     */
    static logRequest(path, options = {}) {
        const logEntry = {
            path,
            method: options.method || 'GET',
            timestamp: Date.now(),
            ...options,
        };

        this.requestLog.push(logEntry);

        // Log boyutunu sınırla
        if (this.requestLog.length > this.maxLogSize) {
            this.requestLog.shift();
        }

        // Development'ta console'a yaz
        if (window.ENV === 'development' || window.location.hostname === 'localhost') {
            console.log(`[API Log] ${logEntry.method} ${path}`, logEntry);
        }

        // Analytics'e gönder (varsa)
        if (window.analytics) {
            window.analytics.track('api_request', {
                path,
                method: logEntry.method,
            });
        }
    }

    /**
     * Debounced fetch - Aynı endpoint'e çoklu istekleri önler
     *
     * @param {string} path - Config path
     * @param {object} fetchOptions - Fetch options
     * @param {number} delay - Debounce delay (ms)
     * @returns {Promise<object>} Parsed response data
     */
    static async debouncedRequest(path, fetchOptions = {}, delay = 300) {
        const cacheKey = `${path}:${JSON.stringify(fetchOptions)}`;

        // Mevcut timer varsa iptal et
        if (this.debounceTimers.has(cacheKey)) {
            clearTimeout(this.debounceTimers.get(cacheKey));
        }

        return new Promise((resolve, reject) => {
            const timer = setTimeout(async () => {
                try {
                    const result = await this.request(path, fetchOptions);
                    resolve(result);
                } catch (error) {
                    reject(error);
                } finally {
                    this.debounceTimers.delete(cacheKey);
                }
            }, delay);

            this.debounceTimers.set(cacheKey, timer);
        });
    }

    /**
     * Cached fetch - Response'ları cache'ler
     *
     * @param {string} path - Config path
     * @param {object} fetchOptions - Fetch options
     * @param {number} ttl - Cache TTL (ms)
     * @returns {Promise<object>} Parsed response data
     */
    static async cachedRequest(path, fetchOptions = {}, ttl = this.cacheTTL) {
        // GET request'ler için cache kullan
        if (fetchOptions.method && fetchOptions.method !== 'GET') {
            return this.request(path, fetchOptions);
        }

        const cacheKey = `${path}:${JSON.stringify(fetchOptions)}`;
        const cached = this.responseCache.get(cacheKey);

        // Cache geçerli mi kontrol et
        if (cached && Date.now() - cached.timestamp < ttl) {
            if (window.ENV === 'development') {
                console.log(`[API Cache Hit] ${path}`);
            }
            return cached.data;
        }

        // Cache'de yok, request yap
        const result = await this.request(path, fetchOptions);

        // Cache'e kaydet
        this.responseCache.set(cacheKey, {
            data: result,
            timestamp: Date.now(),
        });

        return result;
    }

    /**
     * Cache'i temizle
     *
     * @param {string} path - Optional: Belirli bir path için cache temizle
     */
    static clearCache(path = null) {
        if (path) {
            // Belirli path için cache temizle
            const keysToDelete = [];
            for (const key of this.responseCache.keys()) {
                if (key.startsWith(path)) {
                    keysToDelete.push(key);
                }
            }
            keysToDelete.forEach(key => this.responseCache.delete(key));
        } else {
            // Tüm cache'i temizle
            this.responseCache.clear();
        }
    }

    /**
     * Request istatistikleri
     *
     * @returns {object} Request statistics
     */
    static getStats() {
        const stats = {
            totalRequests: this.requestLog.length,
            cacheSize: this.responseCache.size,
            debounceTimers: this.debounceTimers.size,
            errors: this.requestLog.filter(log => log.method === 'ERROR').length,
            success: this.requestLog.filter(log => log.method !== 'ERROR').length,
        };

        return stats;
    }
}

/**
 * API Error Class
 */
class APIError extends Error {
    constructor(message, status = 0, errors = {}) {
        super(message);
        this.name = 'APIError';
        this.status = status;
        this.errors = errors;
    }
}

/**
 * Notification Helper
 */
class NotificationHelper {
    /**
     * Bildirim göster
     *
     * @param {string} message - Mesaj
     * @param {string} type - Tip (success, error, warning, info)
     * @param {number} duration - Süre (ms)
     */
    static show(message, type = 'info', duration = 3000) {
        // Context7 toast sistemini kullan
        if (window.toast) {
            window.toast[type](message, duration);
        } else if (window.showNotification) {
            window.showNotification(message, type);
        } else {
            console[type === 'error' ? 'error' : 'log'](`[${type.toUpperCase()}] ${message}`);
        }
    }

    static success(message, duration = 3000) {
        this.show(message, 'success', duration);
    }

    static error(message, duration = 5000) {
        this.show(message, 'error', duration);
    }

    static warning(message, duration = 4000) {
        this.show(message, 'warning', duration);
    }

    static info(message, duration = 3000) {
        this.show(message, 'info', duration);
    }
}

// Global erişim
if (typeof window !== 'undefined') {
    window.APIHelper = APIHelper;
    window.APIError = APIError;
    window.NotificationHelper = NotificationHelper;
}
