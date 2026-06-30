// Yalıhan Bekçi - Toast Notifications System
// Context7 uyumlu toast notifications

class ToastNotifications {
    constructor() {
        this.toasts = new Map();
        this.container = null;
        this.defaultOptions = {
            duration: 5000,
            position: 'top-right',
            type: 'info',
            dismissible: true,
            showIcon: true,
            showProgress: true,
        };
        this.init();
    }

    init() {
        this.createContainer();
        this.injectToastCSS();
        this.setupKeyboardShortcuts();
    }

    // 🔔 Toast notifications (Context7 uyumlu)
    createContainer() {
        this.container = document.createElement('div');
        this.container.className = 'toast-container';
        this.container.setAttribute('aria-live', 'polite');
        this.container.setAttribute('aria-label', 'Bildirimler');
        document.body.appendChild(this.container);
    }

    show(message, options = {}) {
        const toastId = this.generateId();
        const toastOptions = { ...this.defaultOptions, ...options };

        const toast = this.createToast(toastId, message, toastOptions);
        this.container.appendChild(toast);
        this.toasts.set(toastId, { element: toast, options: toastOptions });

        // Auto-dismiss
        if (toastOptions.duration > 0) {
            setTimeout(() => {
                this.dismiss(toastId);
            }, toastOptions.duration);
        }

        return toastId;
    }

    createToast(id, message, options) {
        const toast = document.createElement('div');
        toast.className = `toast toast-${options.type} toast-${options.position}`;
        toast.setAttribute('data-toast-id', id);
        toast.setAttribute('role', 'alert');

        const icon = this.getIcon(options.type);
        const progressBar = options.showProgress ? this.createProgressBar(options.duration) : '';

        toast.innerHTML = `
            <div class="toast-content">
                <div class="toast-icon">
                    <i class="${icon}"></i>
                </div>
                <div class="toast-message">
                    <div class="toast-title">${options.title || this.getDefaultTitle(options.type)}</div>
                    <div class="toast-text">${message}</div>
                </div>
                ${options.dismissible ? '<button class="toast-dismiss" aria-label="Kapat">&times;</button>' : ''}
            </div>
            ${progressBar}
        `;

        // Add click to dismiss
        if (options.dismissible) {
            const dismissBtn = toast.querySelector('.toast-dismiss');
            dismissBtn.addEventListener('click', () => this.dismiss(id));
        }

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('toast-show');
        });

        return toast;
    }

    createProgressBar(duration) {
        const progress = document.createElement('div');
        progress.className = 'toast-progress';
        progress.style.setProperty('--duration', `${duration}ms`);
        return progress;
    }

    getIcon(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle',
            loading: 'fas fa-spinner fa-spin',
        };
        return icons[type] || icons.info;
    }

    getDefaultTitle(type) {
        const titles = {
            success: 'Başarılı',
            error: 'Hata',
            warning: 'Uyarı',
            info: 'Bilgi',
            loading: 'Yükleniyor',
        };
        return titles[type] || 'Bildirim';
    }

    dismiss(id) {
        const toastData = this.toasts.get(id);
        if (!toastData) return;

        const toast = toastData.element;
        toast.classList.add('toast-dismiss');

        setTimeout(() => {
            toast.remove();
            this.toasts.delete(id);
        }, 300);
    }

    dismissAll() {
        this.toasts.forEach((_, id) => this.dismiss(id));
    }

    injectToastCSS() {
        const toastCSS = `
            .toast-container {
                position: fixed;
                z-index: 9999;
                pointer-events: none;
            }

            .toast-container.top-right {
                top: 20px;
                right: 20px;
            }

            .toast-container.top-left {
                top: 20px;
                left: 20px;
            }

            .toast-container.bottom-right {
                bottom: 20px;
                right: 20px;
            }

            .toast-container.bottom-left {
                bottom: 20px;
                left: 20px;
            }

            .toast {
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                margin-bottom: 12px;
                max-width: 400px;
                min-width: 300px;
                opacity: 0;
                transform: translateX(100%);
                transition: all 0.3s ease;
                pointer-events: auto;
            }

            .toast.toast-show {
                opacity: 1;
                transform: translateX(0);
            }

            .toast.toast-dismiss {
                opacity: 0;
                transform: translateX(100%);
            }

            .toast-content {
                display: flex;
                align-items: flex-start;
                padding: 16px;
                position: relative;
            }

            .toast-icon {
                flex-shrink: 0;
                margin-right: 12px;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .toast-message {
                flex: 1;
                min-width: 0;
            }

            .toast-title {
                font-weight: 600;
                font-size: 14px;
                margin-bottom: 4px;
                color: #1f2937;
            }

            .toast-text {
                font-size: 14px;
                color: #6b7280;
                line-height: 1.4;
            }

            .toast-dismiss {
                background: none;
                border: none;
                color: #9ca3af;
                cursor: pointer;
                font-size: 18px;
                padding: 0;
                width: 20px;
                height: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: all 0.2s ease;
                flex-shrink: 0;
                margin-left: 12px;
            }

            .toast-dismiss:hover {
                background: #f3f4f6;
                color: #374151;
            }

            .toast-progress {
                height: 3px;
                background: rgba(0, 0, 0, 0.1);
                border-radius: 0 0 8px 8px;
                overflow: hidden;
            }

            .toast-progress::before {
                content: '';
                display: block;
                height: 100%;
                width: 100%;
                background: currentColor;
                animation: toast-progress var(--duration) linear forwards;
                transform-origin: left;
            }

            @keyframes toast-progress {
                from { transform: scaleX(1); }
                to { transform: scaleX(0); }
            }

            /* Toast types */
            .toast-success {
                border-left: 4px solid #10b981;
            }

            .toast-success .toast-icon {
                color: #10b981;
            }

            .toast-success .toast-progress::before {
                background: #10b981;
            }

            .toast-error {
                border-left: 4px solid #ef4444;
            }

            .toast-error .toast-icon {
                color: #ef4444;
            }

            .toast-error .toast-progress::before {
                background: #ef4444;
            }

            .toast-warning {
                border-left: 4px solid #f59e0b;
            }

            .toast-warning .toast-icon {
                color: #f59e0b;
            }

            .toast-warning .toast-progress::before {
                background: #f59e0b;
            }

            .toast-info {
                border-left: 4px solid #3b82f6;
            }

            .toast-info .toast-icon {
                color: #3b82f6;
            }

            .toast-info .toast-progress::before {
                background: #3b82f6;
            }

            .toast-loading {
                border-left: 4px solid #8b5cf6;
            }

            .toast-loading .toast-icon {
                color: #8b5cf6;
            }

            .toast-loading .toast-progress::before {
                background: #8b5cf6;
            }

            /* Dark mode */
            .dark .toast {
                background: #1f2937;
                border-color: #374151;
                color: #f9fafb;
            }

            .dark .toast-title {
                color: #f9fafb;
            }

            .dark .toast-text {
                color: #d1d5db;
            }

            .dark .toast-dismiss {
                color: #9ca3af;
            }

            .dark .toast-dismiss:hover {
                background: #374151;
                color: #f9fafb;
            }

            /* Responsive */
            @media (max-width: 640px) {
                .toast-container.top-right,
                .toast-container.top-left,
                .toast-container.bottom-right,
                .toast-container.bottom-left {
                    left: 10px;
                    right: 10px;
                    top: 10px;
                    bottom: auto;
                }

                .toast {
                    max-width: none;
                    min-width: auto;
                    margin-bottom: 8px;
                }
            }
        `;

        const style = document.createElement('style');
        style.textContent = toastCSS;
        document.head.appendChild(style);
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.toasts.size > 0) {
                this.dismissAll();
            }
        });
    }

    // Alpine.js integration
    setupAlpineIntegration() {
        document.addEventListener('alpine:init', () => {
            Alpine.store('toast', {
                show(message, options = {}) {
                    return window.toastNotifications.show(message, options);
                },

                success(message, options = {}) {
                    return this.show(message, { ...options, type: 'success' });
                },

                error(message, options = {}) {
                    return this.show(message, { ...options, type: 'error' });
                },

                warning(message, options = {}) {
                    return this.show(message, { ...options, type: 'warning' });
                },

                info(message, options = {}) {
                    return this.show(message, { ...options, type: 'info' });
                },

                loading(message, options = {}) {
                    return this.show(message, { ...options, type: 'loading', duration: 0 });
                },

                dismiss(id) {
                    window.toastNotifications.dismiss(id);
                },

                dismissAll() {
                    window.toastNotifications.dismissAll();
                },
            });
        });
    }

    generateId() {
        return 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }
}

// Global instance
window.toastNotifications = new ToastNotifications();

// Auto-setup Alpine integration
window.toastNotifications.setupAlpineIntegration();

// Export for module usage
export default ToastNotifications;
