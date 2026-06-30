/**
 * 🍞 ULTRA TOAST NOTIFICATION SYSTEM
 * Advanced toast notifications with animations, sounds, and interactions
 *
 * Features:
 * - Multiple toast types with custom styling
 * - Smooth animations with CSS transforms
 * - Sound effects (optional)
 * - Progress bars for timed toasts
 * - Interactive actions (undo, retry, etc.)
 * - Queue management with priorities
 * - Accessibility support
 * - Mobile-optimized
 * - Auto-positioning
 *
 * @version 3.0 - Ultra Modern
 * @author EmlakPro Team
 */

console.log('🍞 Ultra Toast System v3.0 Loading...');

class UltraToast {
    constructor(options = {}) {
        this.options = {
            // Default settings
            position: 'top-right', // top-left, top-right, bottom-left, bottom-right, top-center, bottom-center
            duration: 4000,
            maxToasts: 5,
            enableSounds: true,
            enableAnimations: true,
            enableProgress: true,
            enableActions: true,
            pauseOnHover: true,
            closeOnClick: true,
            rtl: false,

            // Theme
            theme: 'modern', // modern, minimal, glassmorphism

            // Accessibility
            announceToScreenReader: true,

            ...options,
        };

        this.toasts = new Map();
        this.queue = [];
        this.container = null;
        this.soundContext = null;

        this.init();
    }

    init() {
        this.createContainer();
        this.setupSounds();
        this.setupGlobalStyles();
        this.setupAccessibility();

        console.log('✨ Ultra Toast System initialized!');
    }

    createContainer() {
        // Remove existing container
        const existing = document.querySelector('.ultra-toast-container');
        if (existing) existing.remove();

        // Create new container
        this.container = document.createElement('div');
        this.container.className = `ultra-toast-container ultra-toast-${this.options.position}`;
        this.container.setAttribute('role', 'region');
        this.container.setAttribute('aria-label', 'Notifications');
        this.container.setAttribute('aria-live', 'polite');

        document.body.appendChild(this.container);
    }

    setupSounds() {
        if (!this.options.enableSounds) return;

        try {
            this.soundContext = new (window.AudioContext || window.webkitAudioContext)();
        } catch (e) {
            console.warn('Audio context not supported');
            this.options.enableSounds = false;
        }
    }

    setupGlobalStyles() {
        const styleId = 'ultra-toast-styles';
        if (document.getElementById(styleId)) return;

        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = this.getToastStyles();
        document.head.appendChild(style);
    }

    setupAccessibility() {
        // Create screen reader announcement area
        if (!document.getElementById('toast-announcements')) {
            const announcer = document.createElement('div');
            announcer.id = 'toast-announcements';
            announcer.className = 'sr-only';
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            document.body.appendChild(announcer);
        }
    }

    // 🎨 Toast Creation Methods
    success(message, options = {}) {
        return this.show(message, { type: 'success', ...options });
    }

    error(message, options = {}) {
        return this.show(message, {
            type: 'error',
            duration: 6000,
            ...options,
        });
    }

    warning(message, options = {}) {
        return this.show(message, { type: 'warning', ...options });
    }

    info(message, options = {}) {
        return this.show(message, { type: 'info', ...options });
    }

    loading(message, options = {}) {
        return this.show(message, {
            type: 'loading',
            duration: 0, // Persist until manually closed
            showProgress: false,
            ...options,
        });
    }

    custom(message, options = {}) {
        return this.show(message, { type: 'custom', ...options });
    }

    // 🚀 Main Show Method
    show(message, options = {}) {
        const toastOptions = {
            ...this.options,
            ...options,
            id: this.generateId(),
            message,
            timestamp: Date.now(),
        };

        // Check queue limit
        if (this.toasts.size >= this.options.maxToasts) {
            this.queue.push(toastOptions);
            return toastOptions.id;
        }

        this.createToast(toastOptions);
        return toastOptions.id;
    }

    createToast(options) {
        const toast = document.createElement('div');
        toast.className = this.getToastClasses(options);
        toast.setAttribute('role', 'alert');
        toast.setAttribute('data-toast-id', options.id);

        toast.innerHTML = this.getToastHTML(options);

        // Add to container
        this.container.appendChild(toast);
        this.toasts.set(options.id, { element: toast, options });

        // Setup interactions
        this.setupToastInteractions(toast, options);

        // Animate in
        this.animateToastIn(toast, options);

        // Play sound
        this.playSound(options.type);

        // Setup auto-close
        if (options.duration > 0) {
            this.setupAutoClose(toast, options);
        }

        // Announce to screen reader
        this.announceToScreenReader(options.message);

        // Trigger custom event
        this.dispatchToastEvent('show', options);
    }

    getToastClasses(options) {
        const classes = [
            'ultra-toast',
            `ultra-toast-${options.type}`,
            `ultra-toast-theme-${options.theme}`,
            'ultra-toast-enter',
        ];

        if (options.icon) classes.push('ultra-toast-with-icon');
        if (options.actions) classes.push('ultra-toast-with-actions');
        if (options.showProgress !== false && options.duration > 0) {
            classes.push('ultra-toast-with-progress');
        }

        return classes.join(' ');
    }

    getToastHTML(options) {
        const icon = this.getToastIcon(options.type, options.icon);
        const actions = this.getToastActions(options.actions);
        const progress = this.getToastProgress(options);
        const closeButton = this.getCloseButton();

        return `
            <div class="ultra-toast-content">
                ${icon}
                <div class="ultra-toast-body">
                    <div class="ultra-toast-message">${options.message}</div>
                    ${
                        options.description
                            ? `<div class="ultra-toast-description">${options.description}</div>`
                            : ''
                    }
                    ${actions}
                </div>
                ${closeButton}
            </div>
            ${progress}
        `;
    }

    getToastIcon(type, customIcon) {
        if (customIcon) {
            return `<div class="ultra-toast-icon">${customIcon}</div>`;
        }

        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️',
            loading: '⏳',
        };

        const icon = icons[type] || '📢';
        return `<div class="ultra-toast-icon">${icon}</div>`;
    }

    getToastActions(actions) {
        if (!actions || !Array.isArray(actions)) return '';

        const actionButtons = actions
            .map((action) => {
                return `
                <button
                    class="ultra-toast-action ${action.style || 'primary'}"
                    data-action="${action.key}"
                    ${action.disabled ? 'disabled' : ''}
                >
                    ${action.label}
                </button>
            `;
            })
            .join('');

        return `<div class="ultra-toast-actions">${actionButtons}</div>`;
    }

    getToastProgress(options) {
        if (options.showProgress === false || options.duration <= 0) return '';

        return `
            <div class="ultra-toast-progress">
                <div class="ultra-toast-progress-bar" style="animation-duration: ${options.duration}ms;"></div>
            </div>
        `;
    }

    getCloseButton() {
        return `
            <button class="ultra-toast-close" aria-label="Close notification">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 7.293l2.146-2.147a.5.5 0 01.708.708L8.707 8l2.147 2.146a.5.5 0 01-.708.708L8 8.707l-2.146 2.147a.5.5 0 01-.708-.708L7.293 8 5.146 5.854a.5.5 0 01.708-.708L8 7.293z"/>
                </svg>
            </button>
        `;
    }

    // 🎭 Interactions & Animations
    setupToastInteractions(toast, options) {
        // Close button
        const closeBtn = toast.querySelector('.ultra-toast-close');
        closeBtn?.addEventListener('click', () => this.close(options.id));

        // Click to close
        if (options.closeOnClick !== false) {
            toast.addEventListener('click', (e) => {
                if (!e.target.closest('.ultra-toast-action, .ultra-toast-close')) {
                    this.close(options.id);
                }
            });
        }

        // Action buttons
        const actionBtns = toast.querySelectorAll('.ultra-toast-action');
        actionBtns.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                const actionKey = e.target.dataset.action;
                this.handleAction(options.id, actionKey, options);
            });
        });

        // Pause on hover
        if (options.pauseOnHover !== false && options.duration > 0) {
            let pauseTimeout;

            toast.addEventListener('mouseenter', () => {
                const progressBar = toast.querySelector('.ultra-toast-progress-bar');
                if (progressBar) {
                    progressBar.style.animationPlayState = 'paused';
                }
                clearTimeout(pauseTimeout);
            });

            toast.addEventListener('mouseleave', () => {
                const progressBar = toast.querySelector('.ultra-toast-progress-bar');
                if (progressBar) {
                    progressBar.style.animationPlayState = 'running';
                }
            });
        }

        // Swipe to dismiss (mobile)
        this.setupSwipeGesture(toast, options);
    }

    setupSwipeGesture(toast, options) {
        let startX = 0;
        let currentX = 0;
        let isDragging = false;

        toast.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isDragging = true;
            toast.style.transition = 'none';
        });

        toast.addEventListener('touchmove', (e) => {
            if (!isDragging) return;

            currentX = e.touches[0].clientX;
            const deltaX = currentX - startX;

            toast.style.transform = `translateX(${deltaX}px)`;
            toast.style.opacity = Math.max(0.3, 1 - Math.abs(deltaX) / 200);
        });

        toast.addEventListener('touchend', () => {
            if (!isDragging) return;

            const deltaX = currentX - startX;
            isDragging = false;

            if (Math.abs(deltaX) > 100) {
                this.close(options.id);
            } else {
                toast.style.transition = 'all 0.3s ease';
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            }
        });
    }

    animateToastIn(toast, options) {
        if (!options.enableAnimations) {
            toast.classList.remove('ultra-toast-enter');
            return;
        }

        requestAnimationFrame(() => {
            toast.classList.add('ultra-toast-enter-active');

            setTimeout(() => {
                toast.classList.remove('ultra-toast-enter', 'ultra-toast-enter-active');
            }, 300);
        });
    }

    animateToastOut(toast, callback) {
        if (!this.options.enableAnimations) {
            callback();
            return;
        }

        toast.classList.add('ultra-toast-exit', 'ultra-toast-exit-active');

        setTimeout(() => {
            callback();
        }, 300);
    }

    // ⏰ Auto-close Management
    setupAutoClose(toast, options) {
        const timeout = setTimeout(() => {
            this.close(options.id);
        }, options.duration);

        // Store timeout for potential cancellation
        const toastData = this.toasts.get(options.id);
        if (toastData) {
            toastData.timeout = timeout;
        }
    }

    // 🎵 Sound Effects
    playSound(type) {
        if (!this.options.enableSounds || !this.soundContext) return;

        const frequencies = {
            success: [523.25, 659.25, 783.99], // C5, E5, G5
            error: [329.63, 293.66], // E4, D4
            warning: [440, 554.37], // A4, C#5
            info: [523.25, 659.25], // C5, E5
            loading: [440], // A4
        };

        const freq = frequencies[type] || frequencies.info;
        this.playTone(freq, 0.1, 0.2);
    }

    playTone(frequencies, volume, duration) {
        try {
            const oscillators = frequencies.map((freq) => {
                const oscillator = this.soundContext.createOscillator();
                const gainNode = this.soundContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(this.soundContext.destination);

                oscillator.frequency.setValueAtTime(freq, this.soundContext.currentTime);
                oscillator.type = 'sine';

                gainNode.gain.setValueAtTime(0, this.soundContext.currentTime);
                gainNode.gain.linearRampToValueAtTime(volume, this.soundContext.currentTime + 0.01);
                gainNode.gain.exponentialRampToValueAtTime(
                    0.01,
                    this.soundContext.currentTime + duration
                );

                oscillator.start(this.soundContext.currentTime);
                oscillator.stop(this.soundContext.currentTime + duration);

                return oscillator;
            });
        } catch (e) {
            console.warn('Could not play sound:', e);
        }
    }

    // 🎯 Toast Management
    close(id) {
        const toastData = this.toasts.get(id);
        if (!toastData) return;

        const { element, options } = toastData;

        // Clear timeout
        if (toastData.timeout) {
            clearTimeout(toastData.timeout);
        }

        // Animate out
        this.animateToastOut(element, () => {
            element.remove();
            this.toasts.delete(id);

            // Process queue
            this.processQueue();

            // Trigger event
            this.dispatchToastEvent('close', options);
        });
    }

    closeAll() {
        Array.from(this.toasts.keys()).forEach((id) => this.close(id));
    }

    update(id, newMessage, newOptions = {}) {
        const toastData = this.toasts.get(id);
        if (!toastData) return;

        const { element, options } = toastData;
        const updatedOptions = {
            ...options,
            ...newOptions,
            message: newMessage,
        };

        // Update content
        const messageEl = element.querySelector('.ultra-toast-message');
        if (messageEl) {
            messageEl.textContent = newMessage;
        }

        // Update type classes if changed
        if (newOptions.type && newOptions.type !== options.type) {
            element.className = this.getToastClasses(updatedOptions);
        }

        // Store updated options
        toastData.options = updatedOptions;

        // Trigger event
        this.dispatchToastEvent('update', updatedOptions);
    }

    processQueue() {
        if (this.queue.length === 0 || this.toasts.size >= this.options.maxToasts) {
            return;
        }

        const nextToast = this.queue.shift();
        this.createToast(nextToast);
    }

    handleAction(toastId, actionKey, options) {
        const action = options.actions?.find((a) => a.key === actionKey);
        if (!action) return;

        // Execute action callback
        if (action.callback) {
            action.callback(toastId, actionKey);
        }

        // Close toast if specified
        if (action.closeOnClick !== false) {
            this.close(toastId);
        }

        // Trigger event
        this.dispatchToastEvent('action', { ...options, actionKey });
    }

    // 📢 Accessibility
    announceToScreenReader(message) {
        if (!this.options.announceToScreenReader) return;

        const announcer = document.getElementById('toast-announcements');
        if (announcer) {
            announcer.textContent = message;

            // Clear after announcement
            setTimeout(() => {
                announcer.textContent = '';
            }, 1000);
        }
    }

    // 🎪 Events
    dispatchToastEvent(type, options) {
        const event = new CustomEvent(`toast:${type}`, {
            detail: options,
        });
        document.dispatchEvent(event);
    }

    // 🛠️ Utilities
    generateId() {
        return `toast_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    getToastStyles() {
        return `
            .ultra-toast-container {
                position: fixed;
                z-index: 10000;
                pointer-events: none;
                max-width: 420px;
                width: 100%;
            }

            .ultra-toast-container.ultra-toast-top-right {
                top: 20px;
                right: 20px;
            }

            .ultra-toast-container.ultra-toast-top-left {
                top: 20px;
                left: 20px;
            }

            .ultra-toast-container.ultra-toast-bottom-right {
                bottom: 20px;
                right: 20px;
            }

            .ultra-toast-container.ultra-toast-bottom-left {
                bottom: 20px;
                left: 20px;
            }

            .ultra-toast-container.ultra-toast-top-center {
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
            }

            .ultra-toast-container.ultra-toast-bottom-center {
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
            }

            .ultra-toast {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 12px;
                padding: 16px;
                margin-bottom: 12px;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.2);
                pointer-events: auto;
                position: relative;
                overflow: hidden;
                max-width: 100%;
                word-wrap: break-word;
            }

            .ultra-toast-enter {
                opacity: 0;
                transform: translateX(100%) scale(0.8);
            }

            .ultra-toast-enter-active {
                opacity: 1;
                transform: translateX(0) scale(1);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .ultra-toast-exit {
                opacity: 1;
                transform: translateX(0) scale(1);
            }

            .ultra-toast-exit-active {
                opacity: 0;
                transform: translateX(100%) scale(0.8);
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }

            .ultra-toast-content {
                display: flex;
                align-items: flex-start;
                gap: 12px;
            }

            .ultra-toast-icon {
                font-size: 20px;
                flex-shrink: 0;
                margin-top: 2px;
            }

            .ultra-toast-body {
                flex: 1;
                min-width: 0;
            }

            .ultra-toast-message {
                font-weight: 600;
                color: #2d3748;
                font-size: 14px;
                line-height: 1.4;
                margin-bottom: 4px;
            }

            .ultra-toast-description {
                font-size: 13px;
                color: #718096;
                line-height: 1.3;
                margin-bottom: 8px;
            }

            .ultra-toast-actions {
                display: flex;
                gap: 8px;
                margin-top: 8px;
            }

            .ultra-toast-action {
                padding: 6px 12px;
                border: none;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .ultra-toast-action.primary {
                background: #667eea;
                color: white;
            }

            .ultra-toast-action.secondary {
                background: rgba(0, 0, 0, 0.05);
                color: #4a5568;
            }

            .ultra-toast-close {
                background: none;
                border: none;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                color: #a0aec0;
                transition: all 0.2s ease;
                flex-shrink: 0;
            }

            .ultra-toast-close:hover {
                background: rgba(0, 0, 0, 0.05);
                color: #718096;
            }

            .ultra-toast-progress {
                position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                height: 3px;
                background: rgba(0, 0, 0, 0.1);
            }

            .ultra-toast-progress-bar {
                height: 100%;
                background: currentColor;
                animation: toast-progress linear;
                transform-origin: left;
            }

            @keyframes toast-progress {
                from { transform: scaleX(1); }
                to { transform: scaleX(0); }
            }

            .ultra-toast-success {
                border-left: 4px solid #48bb78;
            }

            .ultra-toast-error {
                border-left: 4px solid #f56565;
            }

            .ultra-toast-warning {
                border-left: 4px solid #ed8936;
            }

            .ultra-toast-info {
                border-left: 4px solid #4299e1;
            }

            .ultra-toast-loading .ultra-toast-icon {
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                to { transform: rotate(360deg); }
            }

            @media (max-width: 640px) {
                .ultra-toast-container {
                    left: 12px !important;
                    right: 12px !important;
                    max-width: none;
                    transform: none !important;
                }

                .ultra-toast {
                    margin-bottom: 8px;
                }
            }

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
        `;
    }
}

// 🌟 Global Instance
window.UltraToast = UltraToast;

// Create default instance
window.toast = new UltraToast();

// Convenience methods
window.showToast = (message, type = 'info', options = {}) => {
    return window.toast[type]
        ? window.toast[type](message, options)
        : window.toast.show(message, { type, ...options });
};

// Alpine.js integration
if (window.Alpine) {
    window.Alpine.magic('toast', () => window.toast);
}

console.log('🍞 Ultra Toast System ready!');
