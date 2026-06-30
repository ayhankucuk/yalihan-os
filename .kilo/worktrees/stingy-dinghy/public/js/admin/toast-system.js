/**
 * Context7 Toast Notification System
 *
 * @description Global toast notification system with animations
 * @author Yalıhan Emlak - Context7 Team
 * @date 2025-11-04
 * @version 1.0.0
 *
 * Yalıhan Bekçi Standards:
 * - Pure vanilla JS (NO jQuery!)
 * - CSS animations
 * - Accessibility support
 * - Dark mode compatible
 */

// Prevent multiple initialization
if (typeof window.ToastSystem !== 'undefined') {
    console.warn('ToastSystem already initialized, skipping...');
} else {
    const ToastSystem = {
        container: null,
        toasts: [],

        /**
         * Initialize toast container
         */
        init() {
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'toast-container';
                this.container.className = 'fixed top-4 right-4 z-50 space-y-2';
                this.container.setAttribute('aria-live', 'polite');
                this.container.setAttribute('aria-atomic', 'true');
                document.body.appendChild(this.container);
            }
        },

        /**
         * Show toast notification
         *
         * @param {string} type - Toast type: success, error, warning, info
         * @param {string} message - Toast message
         * @param {number} duration - Duration in milliseconds (default: 3000)
         * @param {object} options - Additional options
         */
        show(type = 'info', message = '', duration = 3000, options = {}) {
            this.init();

            const toast = this.createToast(type, message, options);
            this.container.appendChild(toast);
            this.toasts.push(toast);

            // Trigger animation
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);

            // Auto remove
            if (duration > 0) {
                setTimeout(() => {
                    this.remove(toast);
                }, duration);
            }

            return toast;
        },

        /**
         * Create toast element
         */
        createToast(type, message, options = {}) {
            const toast = document.createElement('div');
            toast.className = `
            toast toast-${type}
            min-w-[300px] max-w-md
            p-4 rounded-lg shadow-lg
            transform translate-x-full
            transition-all duration-300 ease-out
            flex items-center gap-3
            ${this.getToastColors(type)}
        `
                .trim()
                .replace(/\s+/g, ' ');

            // Icon
            const icon = document.createElement('div');
            icon.className = 'text-2xl flex-shrink-0';
            icon.innerHTML = this.getIcon(type);

            // Message
            const messageEl = document.createElement('div');
            messageEl.className = 'flex-1 font-semibold';
            messageEl.textContent = message;

            // Close button
            const closeBtn = document.createElement('button');
            closeBtn.className = 'text-xl hover:opacity-75 transition-opacity';
            closeBtn.innerHTML = '×';
            closeBtn.onclick = () => this.remove(toast);
            closeBtn.setAttribute('aria-label', 'Close notification');

            toast.appendChild(icon);
            toast.appendChild(messageEl);
            toast.appendChild(closeBtn);

            return toast;
        },

        /**
         * Get toast colors based on type
         */
        getToastColors(type) {
            const colors = {
                success: 'bg-green-500 text-white',
                error: 'bg-red-500 text-white',
                warning: 'bg-yellow-500 text-white',
                info: 'bg-blue-500 text-white',
            };
            return colors[type] || colors.info;
        },

        /**
         * Get icon based on type
         */
        getIcon(type) {
            const icons = {
                success: '✓',
                error: '✕',
                warning: '⚠',
                info: 'ℹ',
            };
            return icons[type] || icons.info;
        },

        /**
         * Remove toast
         */
        remove(toast) {
            toast.classList.remove('show');
            toast.classList.add('translate-x-full', 'opacity-0');

            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
                const index = this.toasts.indexOf(toast);
                if (index > -1) {
                    this.toasts.splice(index, 1);
                }
            }, 300);
        },

        /**
         * Helper methods
         */
        success(message, duration) {
            return this.show('success', message, duration);
        },

        error(message, duration) {
            return this.show('error', message, duration);
        },

        warning(message, duration) {
            return this.show('warning', message, duration);
        },

        info(message, duration) {
            return this.show('info', message, duration);
        },

        /**
         * Clear all toasts
         */
        clearAll() {
            this.toasts.forEach((toast) => this.remove(toast));
        },
    };

    // Add CSS for show animation
    if (!document.getElementById('toast-system-styles')) {
        const toastStyleElement = document.createElement('style');
        toastStyleElement.id = 'toast-system-styles';
        toastStyleElement.textContent = `
        .toast.show {
            transform: translateX(0) !important;
            opacity: 1 !important;
        }
    `;
        document.head.appendChild(toastStyleElement);
    }

    // Global availability
    window.ToastSystem = ToastSystem;
    window.showToast = (type, message, duration) => ToastSystem.show(type, message, duration);
    window.toast = {
        success: (msg, dur) => ToastSystem.success(msg, dur),
        error: (msg, dur) => ToastSystem.error(msg, dur),
        warning: (msg, dur) => ToastSystem.warning(msg, dur),
        info: (msg, dur) => ToastSystem.info(msg, dur),
    };
}
