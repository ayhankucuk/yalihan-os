/**
 * Context7 Toast Utility System
 * Merkezi toast bildirimleri için JavaScript utility
 *
 * @version 1.0.0
 * @context7-compliant true
 * @neo-design-system true
 */

class ToastUtility {
    constructor() {
        this.toastContainer = null;
        this.toasts = [];
        this.maxToasts = 5;
        this.defaultDuration = 5000;
        this.init();
    }

    init() {
        // Toast container oluştur
        if (!document.getElementById('toast-container')) {
            this.toastContainer = document.createElement('div');
            this.toastContainer.id = 'toast-container';
            this.toastContainer.className =
                'fixed top-4 right-4 z-[9999] flex flex-col gap-3 pointer-events-none';
            this.toastContainer.setAttribute('aria-live', 'polite');
            this.toastContainer.setAttribute('aria-atomic', 'true');
            document.body.appendChild(this.toastContainer);
        } else {
            this.toastContainer = document.getElementById('toast-container');
        }
    }

    /**
     * Ana toast gösterme metodu
     * @param {string} message - Toast mesajı
     * @param {string} type - Toast tipi: success, error, warning, info
     * @param {object} options - Ek seçenekler
     */
    show(message, type = 'info', options = {}) {
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);

        const config = {
            id: toastId,
            message,
            type,
            duration: options.duration || this.defaultDuration,
            dismissible: options.dismissible !== false,
            icon: options.icon || this.getDefaultIcon(type),
            action: options.action || null,
            onClose: options.onClose || null,
            position: options.position || 'top-right',
        };

        // Max toast kontrolü
        if (this.toasts.length >= this.maxToasts) {
            this.remove(this.toasts[0].id);
        }

        const toastElement = this.createToastElement(config);
        this.toastContainer.appendChild(toastElement);
        this.toasts.push({ id: toastId, element: toastElement });

        // Animasyon için küçük delay
        requestAnimationFrame(() => {
            toastElement.classList.remove('opacity-0', 'translate-x-full');
            toastElement.classList.add('opacity-100', 'translate-x-0');
        });

        // Otomatik kapanma
        if (config.duration > 0) {
            setTimeout(() => this.remove(toastId), config.duration);
        }

        return toastId;
    }

    /**
     * Toast elementi oluştur
     */
    createToastElement(config) {
        const toast = document.createElement('div');
        toast.id = config.id;
        toast.className = this.getToastClasses(config.type);
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');

        const iconClass = this.getIconClass(config.type);
        const closeButton = config.dismissible ? this.getCloseButton() : '';
        const actionButton = config.action ? this.getActionButton(config.action) : '';

        toast.innerHTML = `
            <div class="flex items-start gap-3 w-full">
                <div class="flex-shrink-0 mt-0.5">
                    <i class="${iconClass}" aria-hidden="true"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white break-words">
                        ${this.escapeHtml(config.message)}
                    </p>
                    ${actionButton}
                </div>
                ${closeButton}
            </div>
        `;

        // Event listeners
        if (config.dismissible) {
            const closeBtn = toast.querySelector('.toast-close-btn');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    this.remove(config.id);
                    if (config.onClose) config.onClose();
                });
            }
        }

        if (config.action) {
            const actionBtn = toast.querySelector('.toast-action-btn');
            if (actionBtn) {
                actionBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    config.action.onClick();
                    this.remove(config.id);
                });
            }
        }

        return toast;
    }

    /**
     * Toast CSS sınıfları
     */
    getToastClasses(type) {
        const baseClasses =
            'neo-toast pointer-events-auto transform transition-all duration-300 ease-out opacity-0 translate-x-full';
        const typeClasses = {
            success: 'neo-toast-success',
            error: 'neo-toast-error',
            warning: 'neo-toast-warning',
            info: 'neo-toast-info',
        };

        return `${baseClasses} ${typeClasses[type] || typeClasses.info}`;
    }

    /**
     * Icon sınıfı
     */
    getIconClass(type) {
        const icons = {
            success: 'neo-icon neo-icon-check-circle text-green-600 dark:text-green-400',
            error: 'neo-icon neo-icon-alert-circle text-red-600 dark:text-red-400',
            warning: 'neo-icon neo-icon-alert-triangle text-yellow-600 dark:text-yellow-400',
            info: 'neo-icon neo-icon-info text-blue-600 dark:text-blue-400',
        };

        return icons[type] || icons.info;
    }

    /**
     * Varsayılan icon
     */
    getDefaultIcon(type) {
        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ',
        };

        return icons[type] || icons.info;
    }

    /**
     * Kapat butonu HTML
     */
    getCloseButton() {
        return `
            <button type="button"
                    class="toast-close-btn flex-shrink-0 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors"
                    aria-label="Bildirimi kapat">
                <i class="neo-icon neo-icon-x" aria-hidden="true"></i>
            </button>
        `;
    }

    /**
     * Action butonu HTML
     */
    getActionButton(action) {
        return `
            <button type="button"
                    class="toast-action-btn mt-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 transition-colors"
                    aria-label="${this.escapeHtml(action.label)}">
                ${this.escapeHtml(action.label)}
            </button>
        `;
    }

    /**
     * Toast'ı kaldır
     */
    remove(toastId) {
        const toast = this.toasts.find((t) => t.id === toastId);
        if (!toast) return;

        // Animasyon ile kaldır
        toast.element.classList.remove('opacity-100', 'translate-x-0');
        toast.element.classList.add('opacity-0', 'translate-x-full');

        setTimeout(() => {
            if (toast.element.parentElement) {
                toast.element.remove();
            }
            this.toasts = this.toasts.filter((t) => t.id !== toastId);
        }, 300);
    }

    /**
     * Tüm toast'ları temizle
     */
    clearAll() {
        this.toasts.forEach((toast) => {
            this.remove(toast.id);
        });
    }

    /**
     * HTML escape
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Kısayol metodlar
    success(message, options = {}) {
        return this.show(message, 'success', options);
    }

    error(message, options = {}) {
        return this.show(message, 'error', options);
    }

    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }

    info(message, options = {}) {
        return this.show(message, 'info', options);
    }

    /**
     * Laravel session flash mesajları için helper
     */
    static fromSession() {
        // Laravel session mesajlarını otomatik göster
        const successMsg = document.querySelector('meta[name="toast-success"]');
        const errorMsg = document.querySelector('meta[name="toast-error"]');
        const warningMsg = document.querySelector('meta[name="toast-warning"]');
        const infoMsg = document.querySelector('meta[name="toast-info"]');

        if (successMsg) {
            window.toast.success(successMsg.content);
        }
        if (errorMsg) {
            window.toast.error(errorMsg.content);
        }
        if (warningMsg) {
            window.toast.warning(warningMsg.content);
        }
        if (infoMsg) {
            window.toast.info(infoMsg.content);
        }
    }
}

// Global instance oluştur
if (typeof window !== 'undefined') {
    window.toast = new ToastUtility();

    // DOM ready olduğunda session mesajlarını kontrol et
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ToastUtility.fromSession();
        });
    } else {
        ToastUtility.fromSession();
    }
}

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ToastUtility;
}
