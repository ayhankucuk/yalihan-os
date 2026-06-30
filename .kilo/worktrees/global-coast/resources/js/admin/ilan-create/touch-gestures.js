// Yalıhan Bekçi - Touch Gestures System
// Advanced touch gestures with swipe navigation

class TouchGestures {
    constructor() {
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchEndX = 0;
        this.touchEndY = 0;
        this.minSwipeDistance = 50;
        this.maxVerticalDistance = 100;
        this.gestureHandlers = new Map();
        this.isGesturing = false;
        this.init();
    }

    init() {
        this.setupTouchEvents();
        this.setupGestureRecognizers();
        this.setupSwipeNavigation();
        this.injectGestureCSS();
    }

    // 📱 Touch gestures (Swipe navigation)
    setupTouchEvents() {
        // Prevent default touch behaviors that interfere with gestures
        document.addEventListener(
            'touchstart',
            (e) => {
                // Allow gestures on specific elements
                if (e.target.closest('[data-swipe-durumu="true"]')) {
                    e.preventDefault();
                }
            },
            { passive: false }
        );

        document.addEventListener(
            'touchmove',
            (e) => {
                if (e.target.closest('[data-swipe-durumu="true"]')) {
                    e.preventDefault();
                }
            },
            { passive: false }
        );

        document.addEventListener(
            'touchend',
            (e) => {
                if (e.target.closest('[data-swipe-durumu="true"]')) {
                    e.preventDefault();
                }
            },
            { passive: false }
        );
    }

    setupGestureRecognizers() {
        // Swipe gestures
        this.addGestureHandler('swipe', {
            left: (element, event) => this.handleSwipeLeft(element, event),
            right: (element, event) => this.handleSwipeRight(element, event),
            up: (element, event) => this.handleSwipeUp(element, event),
            down: (element, event) => this.handleSwipeDown(element, event),
        });

        // Pinch gestures
        this.addGestureHandler('pinch', {
            start: (element, event) => this.handlePinchStart(element, event),
            move: (element, event) => this.handlePinchMove(element, event),
            end: (element, event) => this.handlePinchEnd(element, event),
        });

        // Long press gestures
        this.addGestureHandler('longpress', {
            start: (element, event) => this.handleLongPressStart(element, event),
            end: (element, event) => this.handleLongPressEnd(element, event),
        });

        // Double tap gestures
        this.addGestureHandler('doubletap', {
            tap: (element, event) => this.handleDoubleTap(element, event),
        });
    }

    addGestureHandler(gestureType, handlers) {
        this.gestureHandlers.set(gestureType, handlers);
    }

    // Swipe navigation for form steps
    setupSwipeNavigation() {
        document.addEventListener('touchstart', (e) => {
            const swipeElement = e.target.closest('[data-swipe-navigation="true"]');
            if (!swipeElement) return;

            this.touchStartX = e.touches[0].clientX;
            this.touchStartY = e.touches[0].clientY;
            this.isGesturing = true;

            // Add visual feedback
            swipeElement.classList.add('swipe-active');
        });

        document.addEventListener('touchmove', (e) => {
            if (!this.isGesturing) return;

            const swipeElement = e.target.closest('[data-swipe-navigation="true"]');
            if (!swipeElement) return;

            const currentX = e.touches[0].clientX;
            const currentY = e.touches[0].clientY;

            const deltaX = currentX - this.touchStartX;
            const deltaY = currentY - this.touchStartY;

            // Update swipe indicator
            this.updateSwipeIndicator(swipeElement, deltaX, deltaY);
        });

        document.addEventListener('touchend', (e) => {
            if (!this.isGesturing) return;

            const swipeElement = e.target.closest('[data-swipe-navigation="true"]');
            if (!swipeElement) return;

            this.touchEndX = e.changedTouches[0].clientX;
            this.touchEndY = e.changedTouches[0].clientY;

            this.processSwipeGesture(swipeElement);

            // Clean up
            this.isGesturing = false;
            swipeElement.classList.remove('swipe-active');
            this.clearSwipeIndicator(swipeElement);
        });
    }

    processSwipeGesture(element) {
        const deltaX = this.touchEndX - this.touchStartX;
        const deltaY = this.touchEndY - this.touchStartY;

        // Determine swipe direction
        const isHorizontalSwipe = Math.abs(deltaX) > Math.abs(deltaY);
        const isVerticalSwipe = Math.abs(deltaY) > Math.abs(deltaX);

        if (isHorizontalSwipe && Math.abs(deltaX) > this.minSwipeDistance) {
            if (deltaX > 0) {
                this.handleSwipeRight(element, { deltaX, deltaY });
            } else {
                this.handleSwipeLeft(element, { deltaX, deltaY });
            }
        } else if (isVerticalSwipe && Math.abs(deltaY) > this.minSwipeDistance) {
            if (deltaY > 0) {
                this.handleSwipeDown(element, { deltaX, deltaY });
            } else {
                this.handleSwipeUp(element, { deltaX, deltaY });
            }
        }
    }

    updateSwipeIndicator(element, deltaX, deltaY) {
        let indicator = element.querySelector('.swipe-indicator');
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.className = 'swipe-indicator';
            element.appendChild(indicator);
        }

        const isHorizontalSwipe = Math.abs(deltaX) > Math.abs(deltaY);
        const isVerticalSwipe = Math.abs(deltaY) > Math.abs(deltaX);

        if (isHorizontalSwipe) {
            indicator.style.display = 'block';
            indicator.style.left = '50%';
            indicator.style.top = '20px';
            indicator.style.transform = 'translateX(-50%)';

            if (deltaX > 0) {
                indicator.innerHTML = '<i class="fas fa-arrow-right"></i> Geri';
                indicator.className = 'swipe-indicator swipe-right';
            } else {
                indicator.innerHTML = '<i class="fas fa-arrow-left"></i> İleri';
                indicator.className = 'swipe-indicator swipe-left';
            }
        } else if (isVerticalSwipe) {
            indicator.style.display = 'block';
            indicator.style.left = '20px';
            indicator.style.top = '50%';
            indicator.style.transform = 'translateY(-50%)';

            if (deltaY > 0) {
                indicator.innerHTML = '<i class="fas fa-arrow-down"></i> Aşağı';
                indicator.className = 'swipe-indicator swipe-down';
            } else {
                indicator.innerHTML = '<i class="fas fa-arrow-up"></i> Yukarı';
                indicator.className = 'swipe-indicator swipe-up';
            }
        }
    }

    clearSwipeIndicator(element) {
        const indicator = element.querySelector('.swipe-indicator');
        if (indicator) {
            indicator.style.display = 'none';
        }
    }

    // Gesture handlers
    handleSwipeLeft(element, event) {
        const handlers = this.gestureHandlers.get('swipe');
        if (handlers && handlers.left) {
            handlers.left(element, event);
        }

        // Default behavior for form navigation
        if (element.hasAttribute('data-swipe-next')) {
            this.triggerNextStep(element);
        }

        // Dispatch custom event
        element.dispatchEvent(
            new CustomEvent('swipe-left', {
                detail: { element, event },
            })
        );
    }

    handleSwipeRight(element, event) {
        const handlers = this.gestureHandlers.get('swipe');
        if (handlers && handlers.right) {
            handlers.right(element, event);
        }

        // Default behavior for form navigation
        if (element.hasAttribute('data-swipe-prev')) {
            this.triggerPrevStep(element);
        }

        // Dispatch custom event
        element.dispatchEvent(
            new CustomEvent('swipe-right', {
                detail: { element, event },
            })
        );
    }

    handleSwipeUp(element, event) {
        const handlers = this.gestureHandlers.get('swipe');
        if (handlers && handlers.up) {
            handlers.up(element, event);
        }

        // Dispatch custom event
        element.dispatchEvent(
            new CustomEvent('swipe-up', {
                detail: { element, event },
            })
        );
    }

    handleSwipeDown(element, event) {
        const handlers = this.gestureHandlers.get('swipe');
        if (handlers && handlers.down) {
            handlers.down(element, event);
        }

        // Dispatch custom event
        element.dispatchEvent(
            new CustomEvent('swipe-down', {
                detail: { element, event },
            })
        );
    }

    // Form step navigation
    triggerNextStep(element) {
        const formState = window.Alpine?.store('ilanForm');
        if (formState && typeof formState.nextStep === 'function') {
            formState.nextStep();
        }

        // Fallback: trigger click on next button
        const nextButton =
            element.querySelector('[data-next-step]') ||
            element.querySelector('[data-action="next"]');
        if (nextButton) {
            nextButton.click();
        }
    }

    triggerPrevStep(element) {
        const formState = window.Alpine?.store('ilanForm');
        if (formState && typeof formState.prevStep === 'function') {
            formState.prevStep();
        }

        // Fallback: trigger click on prev button
        const prevButton =
            element.querySelector('[data-prev-step]') ||
            element.querySelector('[data-action="prev"]');
        if (prevButton) {
            prevButton.click();
        }
    }

    // Pinch gesture handlers
    handlePinchStart(element, event) {
        if (event.touches.length === 2) {
            const touch1 = event.touches[0];
            const touch2 = event.touches[1];

            const distance = Math.sqrt(
                Math.pow(touch2.clientX - touch1.clientX, 2) +
                    Math.pow(touch2.clientY - touch1.clientY, 2)
            );

            element.dataset.pinchStartDistance = distance;
            element.dataset.pinchStartScale = parseFloat(element.dataset.pinchScale || '1');
        }
    }

    handlePinchMove(element, event) {
        if (event.touches.length === 2) {
            const touch1 = event.touches[0];
            const touch2 = event.touches[1];

            const distance = Math.sqrt(
                Math.pow(touch2.clientX - touch1.clientX, 2) +
                    Math.pow(touch2.clientY - touch1.clientY, 2)
            );

            const startDistance = parseFloat(element.dataset.pinchStartDistance || '0');
            const startScale = parseFloat(element.dataset.pinchStartScale || '1');

            if (startDistance > 0) {
                const scale = (distance / startDistance) * startScale;
                element.dataset.pinchScale = scale;

                // Apply scaling
                element.style.transform = `scale(${scale})`;

                // Dispatch pinch event
                element.dispatchEvent(
                    new CustomEvent('pinch', {
                        detail: { scale, element, event },
                    })
                );
            }
        }
    }

    handlePinchEnd(element, event) {
        // Clean up pinch data
        delete element.dataset.pinchStartDistance;
        delete element.dataset.pinchStartScale;

        // Dispatch pinch end event
        element.dispatchEvent(
            new CustomEvent('pinch-end', {
                detail: { element, event },
            })
        );
    }

    // Long press gesture handlers
    handleLongPressStart(element, event) {
        element.dataset.longPressTimer = setTimeout(() => {
            element.classList.add('long-press-active');

            // Dispatch long press event
            element.dispatchEvent(
                new CustomEvent('long-press', {
                    detail: { element, event },
                })
            );
        }, 500); // 500ms long press threshold
    }

    handleLongPressEnd(element, event) {
        if (element.dataset.longPressTimer) {
            clearTimeout(element.dataset.longPressTimer);
            delete element.dataset.longPressTimer;
        }

        element.classList.remove('long-press-active');
    }

    // Double tap gesture handlers
    handleDoubleTap(element, event) {
        // Simple double tap detection
        const now = Date.now();
        const lastTap = parseFloat(element.dataset.lastTap || '0');

        if (now - lastTap < 300) {
            // 300ms double tap window
            element.dataset.lastTap = '0';

            // Dispatch double tap event
            element.dispatchEvent(
                new CustomEvent('double-tap', {
                    detail: { element, event },
                })
            );
        } else {
            element.dataset.lastTap = now.toString();
        }
    }

    injectGestureCSS() {
        const gestureCSS = `
            /* Touch Gesture Styles */
            [data-swipe-durumu="true"],
            [data-swipe-navigation="true"] {
                touch-action: none;
                user-select: none;
                -webkit-user-select: none;
                -moz-user-select: none;
                -ms-user-select: none;
            }

            .swipe-active {
                transition: transform 0.1s ease;
            }

            .swipe-indicator {
                position: absolute;
                z-index: 1000;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                pointer-events: none;
                display: none;
                backdrop-filter: blur(4px);
            }

            .swipe-indicator i {
                margin-right: 4px;
            }

            .swipe-left {
                background: rgba(59, 130, 246, 0.9);
            }

            .swipe-right {
                background: rgba(16, 185, 129, 0.9);
            }

            .swipe-up {
                background: rgba(245, 158, 11, 0.9);
            }

            .swipe-down {
                background: rgba(239, 68, 68, 0.9);
            }

            /* Long press feedback */
            .long-press-active {
                transform: scale(0.95);
                opacity: 0.8;
                transition: all 0.2s ease;
            }

            /* Gesture hints */
            .gesture-hint {
                position: absolute;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: rgba(0, 0, 0, 0.7);
                color: white;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 12px;
                opacity: 0;
                transition: opacity 0.3s ease;
                pointer-events: none;
            }

            .gesture-hint.show {
                opacity: 1;
            }

            /* Touch feedback */
            .touch-feedback {
                position: absolute;
                border-radius: 50%;
                background: rgba(59, 130, 246, 0.3);
                pointer-events: none;
                animation: touch-ripple 0.6s ease-out;
                z-index: 999;
            }

            @keyframes touch-ripple {
                0% {
                    transform: scale(0);
                    opacity: 1;
                }
                100% {
                    transform: scale(4);
                    opacity: 0;
                }
            }

            /* Dark mode gesture styles */
            .dark .swipe-indicator {
                background: rgba(255, 255, 255, 0.9);
                color: #1e293b;
            }

            .dark .gesture-hint {
                background: rgba(255, 255, 255, 0.9);
                color: #1e293b;
            }

            /* Responsive adjustments */
            @media (max-width: 768px) {
                .swipe-indicator {
                    font-size: 12px;
                    padding: 6px 10px;
                }

                .gesture-hint {
                    bottom: 10px;
                    font-size: 11px;
                    padding: 6px 12px;
                }
            }
        `;

        const style = document.createElement('style');
        style.textContent = gestureCSS;
        document.head.appendChild(style);
    }

    // Alpine.js integration
    setupAlpineIntegration() {
        document.addEventListener('alpine:init', () => {
            // Touch gestures store
            Alpine.store('touchGestures', {
                isSupported: this.isTouchSupported(),
                isGesturing: false,
                swipeDirection: null,

                enableSwipe(element, options = {}) {
                    element.setAttribute('data-swipe-durumu', 'true');
                    if (options.navigation) {
                        element.setAttribute('data-swipe-navigation', 'true');
                    }
                    if (options.next) {
                        element.setAttribute('data-swipe-next', 'true');
                    }
                    if (options.prev) {
                        element.setAttribute('data-swipe-prev', 'true');
                    }
                },

                disableSwipe(element) {
                    element.removeAttribute('data-swipe-durumu');
                    element.removeAttribute('data-swipe-navigation');
                    element.removeAttribute('data-swipe-next');
                    element.removeAttribute('data-swipe-prev');
                },

                showGestureHint(element, message, duration = 2000) {
                    let hint = element.querySelector('.gesture-hint');
                    if (!hint) {
                        hint = document.createElement('div');
                        hint.className = 'gesture-hint';
                        element.appendChild(hint);
                    }

                    hint.textContent = message;
                    hint.classList.add('show');

                    setTimeout(() => {
                        hint.classList.remove('show');
                    }, duration);
                },
            });

            // Touch gesture directive
            Alpine.directive('touch-gesture', (el, { expression }, { evaluateLater, effect }) => {
                const evaluate = evaluateLater(expression);
                let options = {};

                effect(() => {
                    evaluate((value) => {
                        options = value || {};
                        this.setupElementGestures(el, options);
                    });
                });
            });
        });
    }

    setupElementGestures(element, options) {
        // Enable swipe gestures
        if (options.swipe) {
            element.setAttribute('data-swipe-durumu', 'true');

            if (options.swipe.navigation) {
                element.setAttribute('data-swipe-navigation', 'true');
            }

            if (options.swipe.next) {
                element.setAttribute('data-swipe-next', 'true');
            }

            if (options.swipe.prev) {
                element.setAttribute('data-swipe-prev', 'true');
            }
        }

        // Enable pinch gestures
        if (options.pinch) {
            element.addEventListener('touchstart', (e) => this.handlePinchStart(element, e));
            element.addEventListener('touchmove', (e) => this.handlePinchMove(element, e));
            element.addEventListener('touchend', (e) => this.handlePinchEnd(element, e));
        }

        // Enable long press
        if (options.longPress) {
            element.addEventListener('touchstart', (e) => this.handleLongPressStart(element, e));
            element.addEventListener('touchend', (e) => this.handleLongPressEnd(element, e));
            element.addEventListener('touchcancel', (e) => this.handleLongPressEnd(element, e));
        }

        // Enable double tap
        if (options.doubleTap) {
            element.addEventListener('touchend', (e) => this.handleDoubleTap(element, e));
        }
    }

    isTouchSupported() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }

    // Utility methods
    createTouchRipple(x, y, element) {
        const ripple = document.createElement('div');
        ripple.className = 'touch-feedback';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';

        element.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    // Public API
    enableSwipeNavigation(element, options = {}) {
        element.setAttribute('data-swipe-navigation', 'true');

        if (options.next) {
            element.setAttribute('data-swipe-next', 'true');
        }

        if (options.prev) {
            element.setAttribute('data-swipe-prev', 'true');
        }
    }

    disableSwipeNavigation(element) {
        element.removeAttribute('data-swipe-navigation');
        element.removeAttribute('data-swipe-next');
        element.removeAttribute('data-swipe-prev');
    }

    setSwipeThreshold(distance) {
        this.minSwipeDistance = distance;
    }

    setMaxVerticalDistance(distance) {
        this.maxVerticalDistance = distance;
    }
}

// Global instance
window.touchGestures = new TouchGestures();

// Auto-setup Alpine integration
window.touchGestures.setupAlpineIntegration();

// Export for module usage
export default TouchGestures;
