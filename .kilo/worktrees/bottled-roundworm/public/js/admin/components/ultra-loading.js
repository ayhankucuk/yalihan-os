/**
 * ⏳ ULTRA LOADING SYSTEM
 * Premium loading states with advanced animations and effects
 *
 * Features:
 * - Multiple loading animation types
 * - Context-aware loading states
 * - Progress tracking with visual feedback
 * - Skeleton loaders for content
 * - Interactive loading with cancellation
 * - Performance optimized animations
 * - Accessibility support
 * - Mobile-optimized
 *
 * @version 3.0 - Ultra Modern
 * @author EmlakPro Team
 */

console.log('⏳ Ultra Loading System v3.0 Loading...');

class UltraLoading {
    constructor(options = {}) {
        this.options = {
            // Animation types
            defaultType: 'pulse', // pulse, spinner, dots, wave, skeleton, progress

            // Timing
            minDuration: 500, // Minimum loading time for UX
            maxDuration: 30000, // Maximum before auto-timeout

            // Visual
            overlay: true,
            blur: true,
            darkMode: false,

            // Behavior
            cancellable: true,
            showProgress: true,
            showMessage: true,

            // Accessibility
            announceToScreenReader: true,

            ...options,
        };

        this.activeLoaders = new Map();
        this.globalLoader = null;

        this.init();
    }

    init() {
        this.setupGlobalStyles();
        this.setupAccessibility();

        console.log('✨ Ultra Loading System initialized!');
    }

    setupGlobalStyles() {
        const styleId = 'ultra-loading-styles';
        if (document.getElementById(styleId)) return;

        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = this.getLoadingStyles();
        document.head.appendChild(style);
    }

    setupAccessibility() {
        // Create screen reader announcement area
        if (!document.getElementById('loading-announcements')) {
            const announcer = document.createElement('div');
            announcer.id = 'loading-announcements';
            announcer.className = 'sr-only';
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            document.body.appendChild(announcer);
        }
    }

    // 🎯 Main Loading Methods
    show(options = {}) {
        const loadingOptions = {
            ...this.options,
            ...options,
            id: this.generateId(),
            startTime: Date.now(),
        };

        const loader = this.createLoader(loadingOptions);
        this.activeLoaders.set(loadingOptions.id, loader);

        // Auto-timeout
        if (loadingOptions.maxDuration > 0) {
            setTimeout(() => {
                this.hide(loadingOptions.id);
            }, loadingOptions.maxDuration);
        }

        // Announce to screen reader
        this.announceToScreenReader('Loading...');

        return loadingOptions.id;
    }

    hide(id) {
        const loader = this.activeLoaders.get(id);
        if (!loader) return;

        const { element, options } = loader;

        // Ensure minimum duration for UX
        const elapsed = Date.now() - options.startTime;
        const remaining = Math.max(0, options.minDuration - elapsed);

        setTimeout(() => {
            this.animateOut(element, () => {
                element.remove();
                this.activeLoaders.delete(id);

                // Trigger completion event
                this.dispatchEvent('complete', options);
            });
        }, remaining);
    }

    updateProgress(id, progress, message = null) {
        const loader = this.activeLoaders.get(id);
        if (!loader) return;

        const { element } = loader;
        const progressBar = element.querySelector('.ultra-loading-progress-bar');
        const messageEl = element.querySelector('.ultra-loading-message');

        if (progressBar) {
            progressBar.style.width = `${Math.min(100, Math.max(0, progress))}%`;
        }

        if (message && messageEl) {
            messageEl.textContent = message;
        }
    }

    // 🎨 Specific Loading Types
    showGlobal(message = 'Loading...', type = 'pulse') {
        if (this.globalLoader) {
            this.hideGlobal();
        }

        this.globalLoader = this.show({
            type,
            message,
            target: document.body,
            overlay: true,
            blur: true,
            global: true,
        });

        return this.globalLoader;
    }

    hideGlobal() {
        if (this.globalLoader) {
            this.hide(this.globalLoader);
            this.globalLoader = null;
        }
    }

    showButton(button, message = 'Loading...') {
        const originalContent = button.innerHTML;
        const originalDisabled = button.disabled;

        button.disabled = true;
        button.classList.add('ultra-loading-button');

        const loaderId = this.show({
            type: 'spinner',
            target: button,
            overlay: false,
            message: null,
            inline: true,
        });

        // Store original state
        const loader = this.activeLoaders.get(loaderId);
        if (loader) {
            loader.originalContent = originalContent;
            loader.originalDisabled = originalDisabled;
        }

        return loaderId;
    }

    hideButton(id) {
        const loader = this.activeLoaders.get(id);
        if (!loader) return;

        const { target, originalContent, originalDisabled } = loader;

        if (target && target.tagName === 'BUTTON') {
            target.innerHTML = originalContent || target.innerHTML;
            target.disabled = originalDisabled || false;
            target.classList.remove('ultra-loading-button');
        }

        this.hide(id);
    }

    showSkeleton(target, options = {}) {
        const skeletonOptions = {
            type: 'skeleton',
            target: typeof target === 'string' ? document.querySelector(target) : target,
            overlay: false,
            lines: 3,
            avatar: false,
            ...options,
        };

        return this.show(skeletonOptions);
    }

    showProgress(message = 'Loading...', initialProgress = 0) {
        return this.show({
            type: 'progress',
            message,
            progress: initialProgress,
            showProgress: true,
        });
    }

    // 🏗️ Loader Creation
    createLoader(options) {
        const element = document.createElement('div');
        element.className = this.getLoaderClasses(options);
        element.setAttribute('role', 'status');
        element.setAttribute('aria-live', 'polite');
        element.innerHTML = this.getLoaderHTML(options);

        // Add to target
        const target = options.target || document.body;
        target.appendChild(element);

        // Setup interactions
        this.setupLoaderInteractions(element, options);

        // Animate in
        this.animateIn(element, options);

        return { element, options, target };
    }

    getLoaderClasses(options) {
        const classes = ['ultra-loading', `ultra-loading-${options.type}`, 'ultra-loading-enter'];

        if (options.overlay) classes.push('ultra-loading-overlay');
        if (options.blur) classes.push('ultra-loading-blur');
        if (options.darkMode) classes.push('ultra-loading-dark');
        if (options.inline) classes.push('ultra-loading-inline');
        if (options.global) classes.push('ultra-loading-global');

        return classes.join(' ');
    }

    getLoaderHTML(options) {
        switch (options.type) {
            case 'spinner':
                return this.getSpinnerHTML(options);
            case 'dots':
                return this.getDotsHTML(options);
            case 'wave':
                return this.getWaveHTML(options);
            case 'skeleton':
                return this.getSkeletonHTML(options);
            case 'progress':
                return this.getProgressHTML(options);
            case 'pulse':
            default:
                return this.getPulseHTML(options);
        }
    }

    getPulseHTML(options) {
        return `
            <div class="ultra-loading-content">
                <div class="ultra-loading-pulse">
                    <div class="ultra-loading-pulse-ring"></div>
                    <div class="ultra-loading-pulse-ring"></div>
                    <div class="ultra-loading-pulse-ring"></div>
                </div>
                ${
                    options.message
                        ? `<div class="ultra-loading-message">${options.message}</div>`
                        : ''
                }
                ${options.cancellable ? '<button class="ultra-loading-cancel">✕</button>' : ''}
            </div>
        `;
    }

    getSpinnerHTML(options) {
        return `
            <div class="ultra-loading-content">
                <div class="ultra-loading-spinner">
                    <div class="ultra-loading-spinner-blade"></div>
                    <div class="ultra-loading-spinner-blade"></div>
                    <div class="ultra-loading-spinner-blade"></div>
                    <div class="ultra-loading-spinner-blade"></div>
                    <div class="ultra-loading-spinner-blade"></div>
                    <div class="ultra-loading-spinner-blade"></div>
                    <div class="ultra-loading-spinner-blade"></div>
                    <div class="ultra-loading-spinner-blade"></div>
                </div>
                ${
                    options.message
                        ? `<div class="ultra-loading-message">${options.message}</div>`
                        : ''
                }
                ${options.cancellable ? '<button class="ultra-loading-cancel">✕</button>' : ''}
            </div>
        `;
    }

    getDotsHTML(options) {
        return `
            <div class="ultra-loading-content">
                <div class="ultra-loading-dots">
                    <div class="ultra-loading-dot"></div>
                    <div class="ultra-loading-dot"></div>
                    <div class="ultra-loading-dot"></div>
                    <div class="ultra-loading-dot"></div>
                </div>
                ${
                    options.message
                        ? `<div class="ultra-loading-message">${options.message}</div>`
                        : ''
                }
                ${options.cancellable ? '<button class="ultra-loading-cancel">✕</button>' : ''}
            </div>
        `;
    }

    getWaveHTML(options) {
        return `
            <div class="ultra-loading-content">
                <div class="ultra-loading-wave">
                    <div class="ultra-loading-wave-bar"></div>
                    <div class="ultra-loading-wave-bar"></div>
                    <div class="ultra-loading-wave-bar"></div>
                    <div class="ultra-loading-wave-bar"></div>
                    <div class="ultra-loading-wave-bar"></div>
                </div>
                ${
                    options.message
                        ? `<div class="ultra-loading-message">${options.message}</div>`
                        : ''
                }
                ${options.cancellable ? '<button class="ultra-loading-cancel">✕</button>' : ''}
            </div>
        `;
    }

    getSkeletonHTML(options) {
        const lines = Array.from(
            { length: options.lines || 3 },
            (_, i) =>
                `<div class="ultra-loading-skeleton-line" style="width: ${
                    Math.random() * 30 + 70
                }%"></div>`
        ).join('');

        return `
            <div class="ultra-loading-skeleton">
                ${options.avatar ? '<div class="ultra-loading-skeleton-avatar"></div>' : ''}
                <div class="ultra-loading-skeleton-content">
                    ${lines}
                </div>
            </div>
        `;
    }

    getProgressHTML(options) {
        return `
            <div class="ultra-loading-content">
                <div class="ultra-loading-progress-container">
                    <div class="ultra-loading-progress-track">
                        <div class="ultra-loading-progress-bar" style="width: ${
                            options.progress || 0
                        }%"></div>
                    </div>
                    <div class="ultra-loading-progress-text">${options.progress || 0}%</div>
                </div>
                ${
                    options.message
                        ? `<div class="ultra-loading-message">${options.message}</div>`
                        : ''
                }
                ${options.cancellable ? '<button class="ultra-loading-cancel">✕</button>' : ''}
            </div>
        `;
    }

    // 🎭 Interactions & Animations
    setupLoaderInteractions(element, options) {
        // Cancel button
        const cancelBtn = element.querySelector('.ultra-loading-cancel');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.cancel(options.id);
            });
        }

        // Escape key to cancel
        if (options.cancellable) {
            const handleEscape = (e) => {
                if (e.key === 'Escape') {
                    this.cancel(options.id);
                    document.removeEventListener('keydown', handleEscape);
                }
            };
            document.addEventListener('keydown', handleEscape);
        }
    }

    animateIn(element, options) {
        requestAnimationFrame(() => {
            element.classList.add('ultra-loading-enter-active');

            setTimeout(() => {
                element.classList.remove('ultra-loading-enter', 'ultra-loading-enter-active');
            }, 300);
        });
    }

    animateOut(element, callback) {
        element.classList.add('ultra-loading-exit', 'ultra-loading-exit-active');

        setTimeout(() => {
            callback();
        }, 300);
    }

    // 🎯 Utility Methods
    cancel(id) {
        const loader = this.activeLoaders.get(id);
        if (!loader) return;

        this.dispatchEvent('cancel', loader.options);
        this.hide(id);
    }

    cancelAll() {
        Array.from(this.activeLoaders.keys()).forEach((id) => this.cancel(id));
    }

    isLoading(id) {
        return this.activeLoaders.has(id);
    }

    getActiveCount() {
        return this.activeLoaders.size;
    }

    // 📢 Accessibility
    announceToScreenReader(message) {
        if (!this.options.announceToScreenReader) return;

        const announcer = document.getElementById('loading-announcements');
        if (announcer) {
            announcer.textContent = message;

            setTimeout(() => {
                announcer.textContent = '';
            }, 1000);
        }
    }

    // 🎪 Events
    dispatchEvent(type, options) {
        const event = new CustomEvent(`loading:${type}`, {
            detail: options,
        });
        document.dispatchEvent(event);
    }

    // 🛠️ Utilities
    generateId() {
        return `loading_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    getLoadingStyles() {
        return `
            .ultra-loading {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10000;
                pointer-events: auto;
            }

            .ultra-loading-overlay {
                background: rgba(255, 255, 255, 0.9);
                backdrop-filter: blur(4px);
            }

            .ultra-loading-dark {
                background: rgba(0, 0, 0, 0.8);
                color: white;
            }

            .ultra-loading-blur {
                backdrop-filter: blur(8px);
            }

            .ultra-loading-inline {
                position: relative;
                display: inline-flex;
                width: auto;
                height: auto;
                background: none;
                backdrop-filter: none;
            }

            .ultra-loading-global {
                position: fixed;
                z-index: 99999;
            }

            .ultra-loading-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 16px;
                padding: 32px;
                background: rgba(255, 255, 255, 0.95);
                border-radius: 16px;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                max-width: 300px;
                text-align: center;
            }

            .ultra-loading-dark .ultra-loading-content {
                background: rgba(0, 0, 0, 0.9);
                color: white;
            }

            .ultra-loading-inline .ultra-loading-content {
                background: none;
                box-shadow: none;
                backdrop-filter: none;
                border: none;
                padding: 8px;
            }

            /* Pulse Animation */
            .ultra-loading-pulse {
                position: relative;
                width: 60px;
                height: 60px;
            }

            .ultra-loading-pulse-ring {
                position: absolute;
                border: 3px solid #667eea;
                border-radius: 50%;
                animation: ultra-pulse 1.5s ease-out infinite;
            }

            .ultra-loading-pulse-ring:nth-child(1) {
                animation-delay: 0s;
            }

            .ultra-loading-pulse-ring:nth-child(2) {
                animation-delay: 0.5s;
            }

            .ultra-loading-pulse-ring:nth-child(3) {
                animation-delay: 1s;
            }

            @keyframes ultra-pulse {
                0% {
                    top: 30px;
                    left: 30px;
                    width: 0;
                    height: 0;
                    opacity: 1;
                }
                100% {
                    top: 0;
                    left: 0;
                    width: 60px;
                    height: 60px;
                    opacity: 0;
                }
            }

            /* Spinner Animation */
            .ultra-loading-spinner {
                position: relative;
                width: 50px;
                height: 50px;
                animation: ultra-spin 1s linear infinite;
            }

            .ultra-loading-spinner-blade {
                position: absolute;
                left: 50%;
                top: 50%;
                width: 4px;
                height: 15px;
                background: #667eea;
                border-radius: 2px;
                transform-origin: 0 -20px;
                opacity: 0.8;
            }

            .ultra-loading-spinner-blade:nth-child(1) { transform: rotate(0deg) translateX(-50%); }
            .ultra-loading-spinner-blade:nth-child(2) { transform: rotate(45deg) translateX(-50%); opacity: 0.7; }
            .ultra-loading-spinner-blade:nth-child(3) { transform: rotate(90deg) translateX(-50%); opacity: 0.6; }
            .ultra-loading-spinner-blade:nth-child(4) { transform: rotate(135deg) translateX(-50%); opacity: 0.5; }
            .ultra-loading-spinner-blade:nth-child(5) { transform: rotate(180deg) translateX(-50%); opacity: 0.4; }
            .ultra-loading-spinner-blade:nth-child(6) { transform: rotate(225deg) translateX(-50%); opacity: 0.3; }
            .ultra-loading-spinner-blade:nth-child(7) { transform: rotate(270deg) translateX(-50%); opacity: 0.2; }
            .ultra-loading-spinner-blade:nth-child(8) { transform: rotate(315deg) translateX(-50%); opacity: 0.1; }

            @keyframes ultra-spin {
                to { transform: rotate(360deg); }
            }

            /* Dots Animation */
            .ultra-loading-dots {
                display: flex;
                gap: 8px;
            }

            .ultra-loading-dot {
                width: 12px;
                height: 12px;
                background: #667eea;
                border-radius: 50%;
                animation: ultra-dot-bounce 1.4s ease-in-out infinite both;
            }

            .ultra-loading-dot:nth-child(1) { animation-delay: -0.32s; }
            .ultra-loading-dot:nth-child(2) { animation-delay: -0.16s; }
            .ultra-loading-dot:nth-child(3) { animation-delay: 0s; }
            .ultra-loading-dot:nth-child(4) { animation-delay: 0.16s; }

            @keyframes ultra-dot-bounce {
                0%, 80%, 100% {
                    transform: scale(0.8);
                    opacity: 0.5;
                }
                40% {
                    transform: scale(1.2);
                    opacity: 1;
                }
            }

            /* Wave Animation */
            .ultra-loading-wave {
                display: flex;
                gap: 4px;
                align-items: end;
                height: 40px;
            }

            .ultra-loading-wave-bar {
                width: 6px;
                background: #667eea;
                border-radius: 3px;
                animation: ultra-wave 1.2s ease-in-out infinite;
            }

            .ultra-loading-wave-bar:nth-child(1) { animation-delay: 0s; }
            .ultra-loading-wave-bar:nth-child(2) { animation-delay: 0.1s; }
            .ultra-loading-wave-bar:nth-child(3) { animation-delay: 0.2s; }
            .ultra-loading-wave-bar:nth-child(4) { animation-delay: 0.3s; }
            .ultra-loading-wave-bar:nth-child(5) { animation-delay: 0.4s; }

            @keyframes ultra-wave {
                0%, 40%, 100% { height: 10px; }
                20% { height: 40px; }
            }

            /* Skeleton Animation */
            .ultra-loading-skeleton {
                display: flex;
                gap: 16px;
                width: 100%;
                max-width: 400px;
            }

            .ultra-loading-skeleton-avatar {
                width: 50px;
                height: 50px;
                border-radius: 50%;
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: ultra-skeleton-loading 1.5s infinite;
                flex-shrink: 0;
            }

            .ultra-loading-skeleton-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                gap: 12px;
            }

            .ultra-loading-skeleton-line {
                height: 16px;
                background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                background-size: 200% 100%;
                animation: ultra-skeleton-loading 1.5s infinite;
                border-radius: 8px;
            }

            @keyframes ultra-skeleton-loading {
                0% { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }

            /* Progress Animation */
            .ultra-loading-progress-container {
                display: flex;
                align-items: center;
                gap: 12px;
                width: 200px;
            }

            .ultra-loading-progress-track {
                flex: 1;
                height: 8px;
                background: rgba(0, 0, 0, 0.1);
                border-radius: 4px;
                overflow: hidden;
            }

            .ultra-loading-progress-bar {
                height: 100%;
                background: linear-gradient(90deg, #667eea, #764ba2);
                border-radius: 4px;
                transition: width 0.3s ease;
                position: relative;
                overflow: hidden;
            }

            .ultra-loading-progress-bar::after {
                content: '';
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
                animation: ultra-progress-shimmer 2s infinite;
            }

            @keyframes ultra-progress-shimmer {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }

            .ultra-loading-progress-text {
                font-size: 12px;
                font-weight: 600;
                color: #667eea;
                min-width: 35px;
                text-align: right;
            }

            /* Message */
            .ultra-loading-message {
                font-size: 14px;
                font-weight: 500;
                color: #4a5568;
                margin-top: 8px;
            }

            .ultra-loading-dark .ultra-loading-message {
                color: #e2e8f0;
            }

            /* Cancel Button */
            .ultra-loading-cancel {
                position: absolute;
                top: 8px;
                right: 8px;
                background: rgba(0, 0, 0, 0.1);
                border: none;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                cursor: pointer;
                font-size: 12px;
                color: #666;
                transition: all 0.2s ease;
            }

            .ultra-loading-cancel:hover {
                background: rgba(0, 0, 0, 0.2);
                transform: scale(1.1);
            }

            /* Entrance/Exit Animations */
            .ultra-loading-enter {
                opacity: 0;
                transform: scale(0.8);
            }

            .ultra-loading-enter-active {
                opacity: 1;
                transform: scale(1);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .ultra-loading-exit {
                opacity: 1;
                transform: scale(1);
            }

            .ultra-loading-exit-active {
                opacity: 0;
                transform: scale(0.8);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            /* Button Loading State */
            .ultra-loading-button {
                position: relative;
                pointer-events: none;
            }

            .ultra-loading-button::after {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 16px;
                height: 16px;
                border: 2px solid transparent;
                border-top: 2px solid currentColor;
                border-radius: 50%;
                animation: ultra-spin 1s linear infinite;
                transform: translate(-50%, -50%);
            }

            /* Mobile Optimizations */
            @media (max-width: 640px) {
                .ultra-loading-content {
                    padding: 24px;
                    max-width: 280px;
                }

                .ultra-loading-skeleton {
                    max-width: 300px;
                }

                .ultra-loading-progress-container {
                    width: 150px;
                }
            }

            /* Accessibility */
            .sr-only {
                position: absolute;
                width: 1px;
                height: 1px;
                padding: 0;
                margin: -1px;
                overflow: hidden;
                clip: rect(0, 0, 0, 0);
                white-space: nowrap;
                border: 0;
            }

            /* Reduced Motion */
            @media (prefers-reduced-motion: reduce) {
                .ultra-loading-pulse-ring,
                .ultra-loading-spinner,
                .ultra-loading-dot,
                .ultra-loading-wave-bar,
                .ultra-loading-skeleton-avatar,
                .ultra-loading-skeleton-line,
                .ultra-loading-progress-bar::after {
                    animation: none;
                }

                .ultra-loading-spinner {
                    border: 3px solid #667eea;
                    border-top: 3px solid transparent;
                    border-radius: 50%;
                }
            }
        `;
    }
}

// 🌟 Global Instance & Helper Functions
window.UltraLoading = UltraLoading;
window.loading = new UltraLoading();

// Convenience methods
window.showLoading = (message, type) => window.loading.show({ message, type });
window.hideLoading = (id) => window.loading.hide(id);
window.showGlobalLoading = (message, type) => window.loading.showGlobal(message, type);
window.hideGlobalLoading = () => window.loading.hideGlobal();

// Alpine.js integration
if (window.Alpine) {
    window.Alpine.magic('loading', () => window.loading);

    // Alpine directive for automatic loading states
    window.Alpine.directive('loading', (el, { expression, modifiers }, { evaluate, cleanup }) => {
        let loadingId = null;

        const startLoading = () => {
            if (loadingId) return;

            const options = evaluate(expression) || {};
            if (modifiers.includes('button')) {
                loadingId = window.loading.showButton(el, options.message);
            } else {
                loadingId = window.loading.show({
                    target: el,
                    overlay: false,
                    ...options,
                });
            }
        };

        const stopLoading = () => {
            if (loadingId) {
                if (modifiers.includes('button')) {
                    window.loading.hideButton(loadingId);
                } else {
                    window.loading.hide(loadingId);
                }
                loadingId = null;
            }
        };

        // Listen for Alpine events
        el.addEventListener('loading:start', startLoading);
        el.addEventListener('loading:stop', stopLoading);

        cleanup(() => {
            el.removeEventListener('loading:start', startLoading);
            el.removeEventListener('loading:stop', stopLoading);
            stopLoading();
        });
    });
}

console.log('⏳ Ultra Loading System ready!');
