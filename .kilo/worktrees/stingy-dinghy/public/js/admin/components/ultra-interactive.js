/**
 * 🎮 ULTRA INTERACTIVE ELEMENTS
 * Advanced interactive UI components with drag-drop, hover effects, and animations
 *
 * Features:
 * - Drag & Drop with visual feedback
 * - Advanced hover effects with 3D transforms
 * - Smooth micro-interactions
 * - Touch gesture support
 * - Accessibility features
 * - Performance optimized
 * - Mobile-first responsive
 *
 * @version 3.0 - Ultra Modern
 * @author EmlakPro Team
 */

console.log('🎮 Ultra Interactive Elements v3.0 Loading...');

class UltraInteractive {
    constructor(options = {}) {
        this.options = {
            // Drag & Drop
            enableDragDrop: true,
            dragThreshold: 5,

            // Hover Effects
            enableHoverEffects: true,
            hoverScale: 1.05,
            hoverRotation: 2,

            // Touch Gestures
            enableTouch: true,
            swipeThreshold: 50,

            // Performance
            useTransform3d: true,
            useWillChange: true,

            // Accessibility
            respectReducedMotion: true,
            enableKeyboardNav: true,

            ...options,
        };

        this.activeElements = new Map();
        this.dragState = null;
        this.touchState = null;

        this.init();
    }

    init() {
        this.setupGlobalStyles();
        this.setupEventListeners();
        this.detectCapabilities();

        console.log('✨ Ultra Interactive Elements initialized!');
    }

    setupGlobalStyles() {
        const styleId = 'ultra-interactive-styles';
        if (document.getElementById(styleId)) return;

        const style = document.createElement('style');
        style.id = styleId;
        style.textContent = this.getInteractiveStyles();
        document.head.appendChild(style);
    }

    detectCapabilities() {
        // Check for reduced motion preference
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            this.options.respectReducedMotion = true;
        }

        // Check for touch support
        this.hasTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

        // Check for 3D transform support
        this.has3DTransforms = this.check3DSupport();
    }

    check3DSupport() {
        const el = document.createElement('div');
        const transforms = [
            'transform',
            'WebkitTransform',
            'MozTransform',
            'msTransform',
            'OTransform',
        ];

        for (const transform of transforms) {
            if (el.style[transform] !== undefined) {
                el.style[transform] = 'translate3d(1px,1px,1px)';
                return el.style[transform].includes('3d');
            }
        }
        return false;
    }

    setupEventListeners() {
        // Global drag events
        document.addEventListener('dragover', this.handleGlobalDragOver.bind(this));
        document.addEventListener('drop', this.handleGlobalDrop.bind(this));

        // Touch events for mobile
        if (this.hasTouch) {
            document.addEventListener('touchstart', this.handleGlobalTouchStart.bind(this), {
                passive: false,
            });
            document.addEventListener('touchmove', this.handleGlobalTouchMove.bind(this), {
                passive: false,
            });
            document.addEventListener('touchend', this.handleGlobalTouchEnd.bind(this));
        }

        // Keyboard navigation
        if (this.options.enableKeyboardNav) {
            document.addEventListener('keydown', this.handleKeyboardNav.bind(this));
        }
    }

    // 🎯 Element Registration
    register(element, options = {}) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }

        if (!element) return;

        const elementOptions = {
            ...this.options,
            ...options,
            id: this.generateId(),
        };

        this.activeElements.set(element, elementOptions);
        this.setupElement(element, elementOptions);

        return elementOptions.id;
    }

    unregister(element) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }

        if (!element || !this.activeElements.has(element)) return;

        this.cleanupElement(element);
        this.activeElements.delete(element);
    }

    setupElement(element, options) {
        // Add base classes
        element.classList.add('ultra-interactive');

        if (options.draggable) {
            this.setupDraggable(element, options);
        }

        if (options.droppable) {
            this.setupDroppable(element, options);
        }

        if (options.hoverable !== false && this.options.enableHoverEffects) {
            this.setupHoverable(element, options);
        }

        if (options.swipeable && this.hasTouch) {
            this.setupSwipeable(element, options);
        }

        if (options.clickable) {
            this.setupClickable(element, options);
        }

        // Setup accessibility
        this.setupAccessibility(element, options);
    }

    // 🖱️ Drag & Drop System
    setupDraggable(element, options) {
        element.classList.add('ultra-draggable');
        element.draggable = true;

        element.addEventListener('dragstart', (e) => this.handleDragStart(e, element, options));
        element.addEventListener('drag', (e) => this.handleDrag(e, element, options));
        element.addEventListener('dragend', (e) => this.handleDragEnd(e, element, options));

        // Touch drag support
        if (this.hasTouch) {
            element.addEventListener('touchstart', (e) =>
                this.handleTouchDragStart(e, element, options)
            );
        }
    }

    setupDroppable(element, options) {
        element.classList.add('ultra-droppable');

        element.addEventListener('dragenter', (e) => this.handleDragEnter(e, element, options));
        element.addEventListener('dragover', (e) => this.handleDragOver(e, element, options));
        element.addEventListener('dragleave', (e) => this.handleDragLeave(e, element, options));
        element.addEventListener('drop', (e) => this.handleDrop(e, element, options));
    }

    handleDragStart(e, element, options) {
        this.dragState = {
            element,
            options,
            startX: e.clientX,
            startY: e.clientY,
            data: options.dragData || {},
        };

        // Set drag data
        if (e.dataTransfer) {
            e.dataTransfer.effectAllowed = options.dragEffect || 'move';
            e.dataTransfer.setData('text/plain', JSON.stringify(this.dragState.data));

            // Custom drag image
            if (options.dragImage) {
                e.dataTransfer.setDragImage(options.dragImage, 0, 0);
            }
        }

        // Add dragging class
        element.classList.add('ultra-dragging');

        // Create drag ghost
        this.createDragGhost(element, options);

        // Trigger callback
        if (options.onDragStart) {
            options.onDragStart(e, element, this.dragState);
        }

        this.dispatchEvent('dragstart', {
            element,
            options,
            dragState: this.dragState,
        });
    }

    handleDrag(e, element, options) {
        if (!this.dragState) return;

        // Update drag ghost position
        this.updateDragGhost(e.clientX, e.clientY);

        if (options.onDrag) {
            options.onDrag(e, element, this.dragState);
        }
    }

    handleDragEnd(e, element, options) {
        if (!this.dragState) return;

        element.classList.remove('ultra-dragging');
        this.removeDragGhost();

        if (options.onDragEnd) {
            options.onDragEnd(e, element, this.dragState);
        }

        this.dispatchEvent('dragend', {
            element,
            options,
            dragState: this.dragState,
        });
        this.dragState = null;
    }

    handleDragEnter(e, element, options) {
        e.preventDefault();
        element.classList.add('ultra-drag-over');

        if (options.onDragEnter) {
            options.onDragEnter(e, element);
        }
    }

    handleDragOver(e, element, options) {
        e.preventDefault();

        if (options.onDragOver) {
            options.onDragOver(e, element);
        }
    }

    handleDragLeave(e, element, options) {
        // Only remove if actually leaving the element
        if (!element.contains(e.relatedTarget)) {
            element.classList.remove('ultra-drag-over');

            if (options.onDragLeave) {
                options.onDragLeave(e, element);
            }
        }
    }

    handleDrop(e, element, options) {
        e.preventDefault();
        element.classList.remove('ultra-drag-over');

        try {
            const data = JSON.parse(e.dataTransfer.getData('text/plain'));

            if (options.onDrop) {
                options.onDrop(e, element, data);
            }

            this.dispatchEvent('drop', { element, options, data });
        } catch (error) {
            console.warn('Invalid drop data:', error);
        }
    }

    // 👆 Touch Drag Support
    handleTouchDragStart(e, element, options) {
        if (!options.draggable) return;

        const touch = e.touches[0];
        this.touchState = {
            element,
            options,
            startX: touch.clientX,
            startY: touch.clientY,
            currentX: touch.clientX,
            currentY: touch.clientY,
            isDragging: false,
        };

        // Prevent default to avoid scrolling
        e.preventDefault();
    }

    handleGlobalTouchMove(e) {
        if (!this.touchState) return;

        const touch = e.touches[0];
        const deltaX = touch.clientX - this.touchState.startX;
        const deltaY = touch.clientY - this.touchState.startY;
        const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

        if (!this.touchState.isDragging && distance > this.options.dragThreshold) {
            this.touchState.isDragging = true;
            this.touchState.element.classList.add('ultra-touch-dragging');

            // Create touch drag visual
            this.createTouchDragVisual(this.touchState.element);
        }

        if (this.touchState.isDragging) {
            this.touchState.currentX = touch.clientX;
            this.touchState.currentY = touch.clientY;

            // Update visual position
            this.updateTouchDragVisual(touch.clientX, touch.clientY);

            e.preventDefault();
        }
    }

    handleGlobalTouchEnd(e) {
        if (!this.touchState) return;

        if (this.touchState.isDragging) {
            this.touchState.element.classList.remove('ultra-touch-dragging');
            this.removeTouchDragVisual();

            // Find drop target
            const dropTarget = this.findDropTarget(
                this.touchState.currentX,
                this.touchState.currentY
            );

            if (dropTarget) {
                const dropOptions = this.activeElements.get(dropTarget);
                if (dropOptions && dropOptions.onDrop) {
                    dropOptions.onDrop(e, dropTarget, this.touchState.options.dragData || {});
                }
            }
        }

        this.touchState = null;
    }

    // 🎨 Hover Effects
    setupHoverable(element, options) {
        element.classList.add('ultra-hoverable');

        element.addEventListener('mouseenter', (e) => this.handleMouseEnter(e, element, options));
        element.addEventListener('mouseleave', (e) => this.handleMouseLeave(e, element, options));
        element.addEventListener('mousemove', (e) => this.handleMouseMove(e, element, options));
    }

    handleMouseEnter(e, element, options) {
        if (this.options.respectReducedMotion && this.prefersReducedMotion()) return;

        element.classList.add('ultra-hovered');

        // Apply hover transform
        const scale = options.hoverScale || this.options.hoverScale;
        const rotation = options.hoverRotation || this.options.hoverRotation;

        this.applyTransform(element, {
            scale,
            rotateZ: rotation,
        });

        if (options.onHover) {
            options.onHover(e, element);
        }

        this.dispatchEvent('hover', { element, options });
    }

    handleMouseLeave(e, element, options) {
        element.classList.remove('ultra-hovered');

        // Reset transform
        this.applyTransform(element, {
            scale: 1,
            rotateZ: 0,
            rotateX: 0,
            rotateY: 0,
        });

        if (options.onHoverEnd) {
            options.onHoverEnd(e, element);
        }
    }

    handleMouseMove(e, element, options) {
        if (!element.classList.contains('ultra-hovered')) return;
        if (this.options.respectReducedMotion && this.prefersReducedMotion()) return;

        // 3D tilt effect based on mouse position
        if (options.tiltEffect !== false) {
            const rect = element.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;

            const deltaX = (e.clientX - centerX) / (rect.width / 2);
            const deltaY = (e.clientY - centerY) / (rect.height / 2);

            const tiltX = deltaY * (options.tiltIntensity || 10);
            const tiltY = -deltaX * (options.tiltIntensity || 10);

            this.applyTransform(element, {
                scale: options.hoverScale || this.options.hoverScale,
                rotateX: tiltX,
                rotateY: tiltY,
            });
        }
    }

    // 👆 Swipe Gestures
    setupSwipeable(element, options) {
        element.classList.add('ultra-swipeable');

        let swipeState = null;

        element.addEventListener('touchstart', (e) => {
            const touch = e.touches[0];
            swipeState = {
                startX: touch.clientX,
                startY: touch.clientY,
                startTime: Date.now(),
            };
        });

        element.addEventListener('touchmove', (e) => {
            if (!swipeState) return;

            const touch = e.touches[0];
            const deltaX = touch.clientX - swipeState.startX;
            const deltaY = touch.clientY - swipeState.startY;

            // Visual feedback for swipe
            if (Math.abs(deltaX) > 10 || Math.abs(deltaY) > 10) {
                element.style.transform = `translateX(${
                    deltaX * 0.1
                }px) translateY(${deltaY * 0.1}px)`;
            }
        });

        element.addEventListener('touchend', (e) => {
            if (!swipeState) return;

            const touch = e.changedTouches[0];
            const deltaX = touch.clientX - swipeState.startX;
            const deltaY = touch.clientY - swipeState.startY;
            const deltaTime = Date.now() - swipeState.startTime;

            // Reset visual
            element.style.transform = '';

            // Detect swipe
            const velocity = Math.sqrt(deltaX * deltaX + deltaY * deltaY) / deltaTime;

            if (
                velocity > 0.5 &&
                (Math.abs(deltaX) > this.options.swipeThreshold ||
                    Math.abs(deltaY) > this.options.swipeThreshold)
            ) {
                const direction = this.getSwipeDirection(deltaX, deltaY);

                if (options.onSwipe) {
                    options.onSwipe(direction, { deltaX, deltaY, velocity }, element);
                }

                this.dispatchEvent('swipe', {
                    element,
                    direction,
                    delta: { deltaX, deltaY },
                    velocity,
                });
            }

            swipeState = null;
        });
    }

    getSwipeDirection(deltaX, deltaY) {
        const absDeltaX = Math.abs(deltaX);
        const absDeltaY = Math.abs(deltaY);

        if (absDeltaX > absDeltaY) {
            return deltaX > 0 ? 'right' : 'left';
        } else {
            return deltaY > 0 ? 'down' : 'up';
        }
    }

    // 🖱️ Click Effects
    setupClickable(element, options) {
        element.classList.add('ultra-clickable');

        element.addEventListener('click', (e) => {
            this.createRippleEffect(e, element, options);

            if (options.onClick) {
                options.onClick(e, element);
            }
        });

        // Keyboard activation
        element.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.createRippleEffect(e, element, options);

                if (options.onClick) {
                    options.onClick(e, element);
                }
            }
        });
    }

    createRippleEffect(e, element, options) {
        if (this.options.respectReducedMotion && this.prefersReducedMotion()) return;

        const ripple = document.createElement('div');
        ripple.className = 'ultra-ripple';

        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        ripple.style.width = ripple.style.height = size + 'px';
        ripple.style.left = x + 'px';
        ripple.style.top = y + 'px';

        element.appendChild(ripple);

        // Remove after animation
        setTimeout(() => {
            if (ripple.parentNode) {
                ripple.parentNode.removeChild(ripple);
            }
        }, 600);
    }

    // 🛠️ Utility Methods
    applyTransform(element, transforms) {
        const transformString = Object.entries(transforms)
            .map(([key, value]) => {
                if (key.includes('rotate')) {
                    return `${key}(${value}deg)`;
                } else if (key === 'scale') {
                    return `scale(${value})`;
                } else {
                    return `${key}(${value}px)`;
                }
            })
            .join(' ');

        if (this.options.useTransform3d && this.has3DTransforms) {
            element.style.transform = transformString + ' translateZ(0)';
        } else {
            element.style.transform = transformString;
        }

        if (this.options.useWillChange) {
            element.style.willChange = 'transform';
        }
    }

    createDragGhost(element, options) {
        const ghost = element.cloneNode(true);
        ghost.className = 'ultra-drag-ghost';
        ghost.style.position = 'fixed';
        ghost.style.pointerEvents = 'none';
        ghost.style.zIndex = '10000';
        ghost.style.opacity = '0.8';
        ghost.style.transform = 'scale(0.9)';

        document.body.appendChild(ghost);
        this.dragGhost = ghost;
    }

    updateDragGhost(x, y) {
        if (this.dragGhost) {
            this.dragGhost.style.left = x - 20 + 'px';
            this.dragGhost.style.top = y - 20 + 'px';
        }
    }

    removeDragGhost() {
        if (this.dragGhost) {
            this.dragGhost.remove();
            this.dragGhost = null;
        }
    }

    createTouchDragVisual(element) {
        const visual = element.cloneNode(true);
        visual.className = 'ultra-touch-drag-visual';
        visual.style.position = 'fixed';
        visual.style.pointerEvents = 'none';
        visual.style.zIndex = '10000';
        visual.style.opacity = '0.7';
        visual.style.transform = 'scale(0.8)';

        document.body.appendChild(visual);
        this.touchDragVisual = visual;
    }

    updateTouchDragVisual(x, y) {
        if (this.touchDragVisual) {
            this.touchDragVisual.style.left = x - 30 + 'px';
            this.touchDragVisual.style.top = y - 30 + 'px';
        }
    }

    removeTouchDragVisual() {
        if (this.touchDragVisual) {
            this.touchDragVisual.remove();
            this.touchDragVisual = null;
        }
    }

    findDropTarget(x, y) {
        const elements = document.elementsFromPoint(x, y);
        for (const element of elements) {
            if (this.activeElements.has(element)) {
                const options = this.activeElements.get(element);
                if (options.droppable) {
                    return element;
                }
            }
        }
        return null;
    }

    prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    setupAccessibility(element, options) {
        if (options.draggable || options.clickable) {
            if (!element.hasAttribute('tabindex')) {
                element.setAttribute('tabindex', '0');
            }

            if (!element.hasAttribute('role')) {
                element.setAttribute('role', 'button');
            }
        }

        if (options.ariaLabel) {
            element.setAttribute('aria-label', options.ariaLabel);
        }
    }

    cleanupElement(element) {
        element.classList.remove(
            'ultra-interactive',
            'ultra-draggable',
            'ultra-droppable',
            'ultra-hoverable',
            'ultra-swipeable',
            'ultra-clickable'
        );

        element.style.transform = '';
        element.style.willChange = '';
    }

    handleKeyboardNav(e) {
        // Implementation for keyboard navigation
        // Arrow keys, tab, enter, space, etc.
    }

    handleGlobalDragOver(e) {
        e.preventDefault();
    }

    handleGlobalDrop(e) {
        e.preventDefault();
    }

    handleGlobalTouchStart(e) {
        // Global touch handling if needed
    }

    // 🎪 Events
    dispatchEvent(type, detail) {
        const event = new CustomEvent(`interactive:${type}`, { detail });
        document.dispatchEvent(event);
    }

    generateId() {
        return `interactive_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    getInteractiveStyles() {
        return `
            .ultra-interactive {
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                position: relative;
                overflow: hidden;
            }

            .ultra-draggable {
                cursor: grab;
                user-select: none;
            }

            .ultra-draggable:active,
            .ultra-dragging {
                cursor: grabbing;
            }

            .ultra-droppable {
                position: relative;
            }

            .ultra-drag-over {
                background-color: rgba(102, 126, 234, 0.1) !important;
                border: 2px dashed #667eea !important;
                transform: scale(1.02);
            }

            .ultra-hoverable {
                cursor: pointer;
            }

            .ultra-hovered {
                z-index: 10;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            }

            .ultra-clickable {
                cursor: pointer;
                position: relative;
                overflow: hidden;
            }

            .ultra-swipeable {
                touch-action: pan-y;
            }

            .ultra-ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(102, 126, 234, 0.3);
                pointer-events: none;
                transform: scale(0);
                animation: ultra-ripple-animation 0.6s ease-out;
            }

            @keyframes ultra-ripple-animation {
                to {
                    transform: scale(2);
                    opacity: 0;
                }
            }

            .ultra-drag-ghost,
            .ultra-touch-drag-visual {
                transition: none;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                border-radius: 8px;
            }

            .ultra-dragging {
                opacity: 0.5;
                transform: scale(0.95);
            }

            .ultra-touch-dragging {
                opacity: 0.7;
                transform: scale(1.05);
            }

            /* Reduced Motion */
            @media (prefers-reduced-motion: reduce) {
                .ultra-interactive {
                    transition: none;
                }

                .ultra-ripple {
                    animation: none;
                    opacity: 0;
                }

                .ultra-hovered {
                    transform: none !important;
                }
            }

            /* Focus Styles */
            .ultra-interactive:focus {
                outline: 2px solid #667eea;
                outline-offset: 2px;
            }

            /* Mobile Touch Feedback */
            @media (hover: none) {
                .ultra-hoverable:active {
                    transform: scale(0.98);
                    background-color: rgba(0, 0, 0, 0.05);
                }
            }
        `;
    }
}

// 🌟 Global Instance
window.UltraInteractive = UltraInteractive;
window.interactive = new UltraInteractive();

// Convenience methods
window.makeDraggable = (element, options) =>
    window.interactive.register(element, { draggable: true, ...options });
window.makeDroppable = (element, options) =>
    window.interactive.register(element, { droppable: true, ...options });
window.makeHoverable = (element, options) =>
    window.interactive.register(element, { hoverable: true, ...options });
window.makeSwipeable = (element, options) =>
    window.interactive.register(element, { swipeable: true, ...options });
window.makeClickable = (element, options) =>
    window.interactive.register(element, { clickable: true, ...options });

// Alpine.js integration
if (window.Alpine) {
    window.Alpine.magic('interactive', () => window.interactive);

    // Alpine directives
    ['draggable', 'droppable', 'hoverable', 'swipeable', 'clickable'].forEach((type) => {
        window.Alpine.directive(type, (el, { expression }, { evaluate, cleanup }) => {
            const options = evaluate(expression) || {};
            const id = window.interactive.register(el, {
                [type]: true,
                ...options,
            });

            cleanup(() => {
                window.interactive.unregister(el);
            });
        });
    });
}

console.log('🎮 Ultra Interactive Elements ready!');
