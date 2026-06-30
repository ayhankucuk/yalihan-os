/**
 * Loading State Manager
 *
 * Merkezi loading state yönetimi
 * Context7: C7-LOADING-MANAGER-2025-12-15
 *
 * @version 1.0.0
 * @since 2025-12-15
 */

/* global window */

class LoadingManager {
    static states = new Map();
    static loadingElements = new Map();

    /**
     * Loading state'i ayarla
     *
     * @param {string} key - Loading key (örn: 'photos.upload')
     * @param {boolean} isLoading - Loading durumu
     * @param {HTMLElement|string} target - Optional: Target element veya selector
     */
    static set(key, isLoading, target = null) {
        this.states.set(key, isLoading);

        // Target element varsa UI'ı güncelle
        if (target) {
            this.updateUI(key, target, isLoading);
        } else {
            // Global loading element'ini bul ve güncelle
            const element = document.querySelector(`[data-loading="${key}"]`);
            if (element) {
                this.updateUI(key, element, isLoading);
            }
        }

        // Global loading overlay'i kontrol et
        this.updateGlobalOverlay();
    }

    /**
     * Loading state'i al
     *
     * @param {string} key - Loading key
     * @returns {boolean} Loading durumu
     */
    static get(key) {
        return this.states.get(key) || false;
    }

    /**
     * Herhangi bir loading aktif mi?
     *
     * @returns {boolean}
     */
    static isAnyLoading() {
        for (const isLoading of this.states.values()) {
            if (isLoading) return true;
        }
        return false;
    }

    /**
     * UI'ı güncelle
     *
     * @param {string} key - Loading key
     * @param {HTMLElement|string} target - Target element
     * @param {boolean} isLoading - Loading durumu
     */
    static updateUI(key, target, isLoading) {
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (!element) return;

        // Loading class'ını ekle/çıkar
        if (isLoading) {
            element.classList.add('loading', 'opacity-50', 'cursor-wait');
            element.disabled = true;

            // Spinner ekle (yoksa)
            if (!element.querySelector('.loading-spinner')) {
                const spinner = document.createElement('div');
                spinner.className = 'loading-spinner absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-gray-900/80 z-50';
                spinner.innerHTML = `
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                `;
                element.style.position = 'relative';
                element.appendChild(spinner);
            }
        } else {
            element.classList.remove('loading', 'opacity-50', 'cursor-wait');
            element.disabled = false;

            // Spinner'ı kaldır
            const spinner = element.querySelector('.loading-spinner');
            if (spinner) {
                spinner.remove();
            }
        }

        this.loadingElements.set(key, element);
    }

    /**
     * Global loading overlay'i güncelle
     */
    static updateGlobalOverlay() {
        const overlay = document.getElementById('globalLoadingOverlay');
        if (!overlay) return;

        const isLoading = this.isAnyLoading();
        overlay.classList.toggle('hidden', !isLoading);
    }

    /**
     * Tüm loading state'lerini temizle
     */
    static clearAll() {
        this.states.clear();
        this.loadingElements.clear();
        this.updateGlobalOverlay();
    }

    /**
     * Loading state'i kaldır
     *
     * @param {string} key - Loading key
     */
    static remove(key) {
        this.states.delete(key);
        const element = this.loadingElements.get(key);
        if (element) {
            element.classList.remove('loading', 'opacity-50', 'cursor-wait');
            element.disabled = false;
            const spinner = element.querySelector('.loading-spinner');
            if (spinner) {
                spinner.remove();
            }
        }
        this.loadingElements.delete(key);
        this.updateGlobalOverlay();
    }
}

// Global erişim
if (typeof window !== 'undefined') {
    window.LoadingManager = LoadingManager;
}
