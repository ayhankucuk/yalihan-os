// Yalıhan Bekçi - Mobile-First Responsive System
// 320px → 1920px responsive design improvements

class MobileFirstResponsive {
    constructor() {
        this.breakpoints = {
            xs: 320,
            sm: 640,
            md: 768,
            lg: 1024,
            xl: 1280,
            '2xl': 1536,
        };
        this.currentBreakpoint = null;
        this.touchTargets = [];
        this.viewportHeight = window.innerHeight;
        this.init();
    }

    init() {
        this.detectBreakpoint();
        this.setupResponsiveHandlers();
        this.optimizeTouchTargets();
        this.setupProgressiveLoading();
        this.setupViewportHandling();
        this.injectResponsiveCSS();
        this.setupOrientationHandling();
    }

    // 📱 Mobile-first approach (320px → 1920px)
    detectBreakpoint() {
        const width = window.innerWidth;

        if (width >= this.breakpoints['2xl']) {
            this.currentBreakpoint = '2xl';
        } else if (width >= this.breakpoints.xl) {
            this.currentBreakpoint = 'xl';
        } else if (width >= this.breakpoints.lg) {
            this.currentBreakpoint = 'lg';
        } else if (width >= this.breakpoints.md) {
            this.currentBreakpoint = 'md';
        } else if (width >= this.breakpoints.sm) {
            this.currentBreakpoint = 'sm';
        } else {
            this.currentBreakpoint = 'xs';
        }

        document.documentElement.setAttribute('data-breakpoint', this.currentBreakpoint);
        this.dispatchBreakpointChange();
    }

    setupResponsiveHandlers() {
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                this.handleResize();
            }, 250);
        });

        // Listen for breakpoint changes
        this.addBreakpointChangeListener();
    }

    handleResize() {
        const newBreakpoint = this.detectBreakpoint();
        const newViewportHeight = window.innerHeight;

        // Handle breakpoint changes
        if (newBreakpoint !== this.currentBreakpoint) {
            this.handleBreakpointChange(newBreakpoint);
        }

        // Handle viewport height changes (mobile keyboard)
        if (Math.abs(newViewportHeight - this.viewportHeight) > 100) {
            this.handleViewportHeightChange(newViewportHeight);
        }

        this.viewportHeight = newViewportHeight;
    }

    handleBreakpointChange(newBreakpoint) {
        const oldBreakpoint = this.currentBreakpoint;
        this.currentBreakpoint = newBreakpoint;

        // Update touch targets for new breakpoint
        this.optimizeTouchTargets();

        // Update layout for new breakpoint
        this.updateLayoutForBreakpoint(newBreakpoint);

        // Dispatch custom event
        this.dispatchBreakpointChange(oldBreakpoint, newBreakpoint);
    }

    handleViewportHeightChange(newHeight) {
        // Handle mobile keyboard appearance
        const isKeyboardVisible = newHeight < this.viewportHeight * 0.75;

        if (isKeyboardVisible) {
            this.handleKeyboardAppearance();
        } else {
            this.handleKeyboardDismissal();
        }
    }

    handleKeyboardAppearance() {
        // Adjust layout for mobile keyboard
        document.body.classList.add('keyboard-visible');

        // Scroll focused element into view
        const focusedElement = document.activeElement;
        if (focusedElement && this.isMobile()) {
            setTimeout(() => {
                focusedElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
            }, 300);
        }
    }

    handleKeyboardDismissal() {
        document.body.classList.remove('keyboard-visible');
    }

    // 🖱️ Touch-friendly elements (Min 44px tap targets)
    optimizeTouchTargets() {
        this.touchTargets = document.querySelectorAll(
            'button, a, input[type="checkbox"], input[type="radio"], [role="button"], [tabindex="0"]'
        );

        this.touchTargets.forEach((element) => {
            this.optimizeTouchTarget(element);
        });
    }

    optimizeTouchTarget(element) {
        if (this.isMobile()) {
            const rect = element.getBoundingClientRect();
            const minSize = 44; // Minimum touch target size

            // Add touch-friendly class if element is too small
            if (rect.width < minSize || rect.height < minSize) {
                element.classList.add('touch-target-optimized');

                // Add padding to ensure minimum size
                const currentPadding = window.getComputedStyle(element).padding;
                const neededPadding = Math.max(
                    0,
                    (minSize - Math.min(rect.width, rect.height)) / 2
                );

                if (neededPadding > 0) {
                    element.style.setProperty('--touch-padding', `${neededPadding}px`);
                }
            }
        } else {
            element.classList.remove('touch-target-optimized');
        }
    }

    // ⚡ Progressive loading (Above-fold öncelik)
    setupProgressiveLoading() {
        this.setupIntersectionObserver();
        this.setupLazyLoading();
        this.setupCriticalResourceHints();
    }

    setupIntersectionObserver() {
        const observerOptions = {
            root: null,
            rootMargin: '50px 0px',
            threshold: 0.1,
        };

        this.intersectionObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    this.loadBelowFoldContent(entry.target);
                    this.intersectionObserver.unobserve(entry.target);
                }
            });
        }, observerOptions);

        // Observe below-fold elements
        this.observeBelowFoldElements();
    }

    observeBelowFoldElements() {
        const belowFoldElements = document.querySelectorAll('[data-below-fold]');
        belowFoldElements.forEach((element) => {
            this.intersectionObserver.observe(element);
        });
    }

    loadBelowFoldContent(element) {
        // Load images
        const images = element.querySelectorAll('img[data-src]');
        images.forEach((img) => {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        });

        // Load components
        const components = element.querySelectorAll('[data-lazy-component]');
        components.forEach((component) => {
            const componentName = component.dataset.lazyComponent;
            if (window.lazyLoader) {
                window.lazyLoader.loadComponent(componentName, component);
            }
        });

        // Load external content
        const externalContent = element.querySelectorAll('[data-external-src]');
        externalContent.forEach((content) => {
            this.loadExternalContent(content);
        });
    }

    async loadExternalContent(element) {
        try {
            const response = await fetch(element.dataset.externalSrc);
            const html = await response.text();
            element.innerHTML = html;
            element.removeAttribute('data-external-src');
        } catch (error) {
            console.error('Failed to load external content:', error);
        }
    }

    setupLazyLoading() {
        // Lazy load images
        const lazyImages = document.querySelectorAll('img[data-src]');

        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });

            lazyImages.forEach((img) => imageObserver.observe(img));
        } else {
            // Fallback for older browsers
            lazyImages.forEach((img) => {
                img.src = img.dataset.src;
            });
        }
    }

    setupCriticalResourceHints() {
        // Critical resources are already included in the main bundle
        // No need for additional preloading as they're loaded with the page
        console.log('✅ Critical resources handled by main bundle');
    }

    // 🔄 Offline capability (Service Worker)
    setupServiceWorker() {
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker
                    .register('/sw.js')
                    .then((registration) => {
                        console.log('SW registered:', registration);
                        this.setupOfflineHandling();
                    })
                    .catch((registrationError) => {
                        console.log('SW registration failed:', registrationError);
                    });
            });
        }
    }

    setupOfflineHandling() {
        // Handle offline events
        window.addEventListener('online', () => {
            this.handleOnline();
        });

        window.addEventListener('offline', () => {
            this.handleOffline();
        });

        // Check initial connection status
        if (!navigator.onLine) {
            this.handleOffline();
        }
    }

    handleOnline() {
        document.body.classList.remove('offline');

        // Show online notification
        if (window.toastNotifications) {
            window.toastNotifications.success('İnternet bağlantısı geri geldi');
        }

        // Sync offline data
        this.syncOfflineData();
    }

    handleOffline() {
        document.body.classList.add('offline');

        // Show offline notification
        if (window.toastNotifications) {
            window.toastNotifications.warning('İnternet bağlantısı yok - Offline mod');
        }

        // Enable offline features
        this.enableOfflineFeatures();
    }

    syncOfflineData() {
        // Sync any data that was stored offline
        const offlineData = localStorage.getItem('offline-data');
        if (offlineData) {
            try {
                const data = JSON.parse(offlineData);
                // Process offline data
                console.log('Syncing offline data:', data);
                localStorage.removeItem('offline-data');
            } catch (error) {
                console.error('Failed to sync offline data:', error);
            }
        }
    }

    enableOfflineFeatures() {
        // Enable offline functionality
        document.querySelectorAll('[data-offline]').forEach((element) => {
            element.classList.add('offline-status');
        });
    }

    setupViewportHandling() {
        // Handle viewport units on mobile
        this.setupViewportUnits();

        // Handle safe area insets
        this.setupSafeAreaInsets();

        // Handle orientation changes
        this.setupOrientationHandling();
    }

    setupViewportUnits() {
        // Fix viewport units on mobile
        const setViewportHeight = () => {
            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);
        };

        setViewportHeight();
        window.addEventListener('resize', setViewportHeight);
    }

    setupSafeAreaInsets() {
        // Handle safe area insets for devices with notches
        const setSafeAreaInsets = () => {
            const style = getComputedStyle(document.documentElement);
            const safeAreaTop = style.getPropertyValue('env(safe-area-inset-top)');
            const safeAreaBottom = style.getPropertyValue('env(safe-area-inset-bottom)');

            document.documentElement.style.setProperty('--safe-area-top', safeAreaTop);
            document.documentElement.style.setProperty('--safe-area-bottom', safeAreaBottom);
        };

        setSafeAreaInsets();
    }

    setupOrientationHandling() {
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.handleOrientationChange();
            }, 500);
        });
    }

    handleOrientationChange() {
        // Recalculate layout after orientation change
        this.detectBreakpoint();
        this.optimizeTouchTargets();
        this.updateLayoutForBreakpoint(this.currentBreakpoint);

        // Dispatch orientation change event
        window.dispatchEvent(
            new CustomEvent('orientationchange', {
                detail: {
                    orientation: this.getOrientation(),
                    breakpoint: this.currentBreakpoint,
                },
            })
        );
    }

    updateLayoutForBreakpoint(breakpoint) {
        const body = document.body;

        // Remove old breakpoint classes
        body.classList.remove(
            'breakpoint-xs',
            'breakpoint-sm',
            'breakpoint-md',
            'breakpoint-lg',
            'breakpoint-xl',
            'breakpoint-2xl'
        );

        // Add new breakpoint class
        body.classList.add(`breakpoint-${breakpoint}`);

        // Update layout based on breakpoint
        switch (breakpoint) {
            case 'xs':
            case 'sm':
                this.optimizeForMobile();
                break;
            case 'md':
                this.optimizeForTablet();
                break;
            case 'lg':
            case 'xl':
            case '2xl':
                this.optimizeForDesktop();
                break;
        }
    }

    optimizeForMobile() {
        // Mobile-specific optimizations
        document.body.classList.add('mobile-optimized');

        // Enable touch gestures
        if (window.touchGestures) {
            document.querySelectorAll('[data-swipe-status]').forEach((element) => {
                window.touchGestures.enableSwipeNavigation(element, {
                    next: true,
                    prev: true,
                });
            });
        }

        // Optimize forms for mobile
        this.optimizeFormsForMobile();
    }

    optimizeForTablet() {
        // Tablet-specific optimizations
        document.body.classList.add('tablet-optimized');

        // Adjust grid layouts
        this.adjustGridLayouts();
    }

    optimizeForDesktop() {
        // Desktop-specific optimizations
        document.body.classList.add('desktop-optimized');

        // Enable hover effects
        this.enableHoverEffects();
    }

    optimizeFormsForMobile() {
        // Set appropriate input types for mobile keyboards
        document.querySelectorAll('input[type="email"]').forEach((input) => {
            input.setAttribute('autocomplete', 'email');
        });

        document.querySelectorAll('input[type="tel"]').forEach((input) => {
            input.setAttribute('autocomplete', 'tel');
        });

        // Add input mode attributes
        document.querySelectorAll('input[type="number"]').forEach((input) => {
            input.setAttribute('inputmode', 'numeric');
        });
    }

    adjustGridLayouts() {
        // Adjust grid layouts for tablet
        document.querySelectorAll('.grid-responsive').forEach((grid) => {
            const columns = this.getOptimalColumns('tablet');
            grid.style.gridTemplateColumns = `repeat(${columns}, 1fr)`;
        });
    }

    enableHoverEffects() {
        // Enable hover effects on desktop
        document.querySelectorAll('[data-hover-effect]').forEach((element) => {
            element.classList.add('hover-status');
        });
    }

    getOptimalColumns(device) {
        const columnMap = {
            mobile: 1,
            tablet: 2,
            desktop: 3,
        };
        return columnMap[device] || 1;
    }

    // Utility methods
    isMobile() {
        return this.currentBreakpoint === 'xs' || this.currentBreakpoint === 'sm';
    }

    isTablet() {
        return this.currentBreakpoint === 'md';
    }

    isDesktop() {
        return ['lg', 'xl', '2xl'].includes(this.currentBreakpoint);
    }

    getOrientation() {
        return window.innerHeight > window.innerWidth ? 'portrait' : 'landscape';
    }

    dispatchBreakpointChange(oldBreakpoint = null, newBreakpoint = null) {
        window.dispatchEvent(
            new CustomEvent('breakpoint-change', {
                detail: {
                    oldBreakpoint,
                    newBreakpoint: newBreakpoint || this.currentBreakpoint,
                    isMobile: this.isMobile(),
                    isTablet: this.isTablet(),
                    isDesktop: this.isDesktop(),
                },
            })
        );
    }

    addBreakpointChangeListener() {
        window.addEventListener('breakpoint-change', (event) => {
            const { oldBreakpoint, newBreakpoint } = event.detail;
            console.log(`Breakpoint changed: ${oldBreakpoint} → ${newBreakpoint}`);
        });
    }

    injectResponsiveCSS() {
        const responsiveCSS = `
            /* Mobile-First Responsive Styles */

            /* Touch Target Optimization */
            .touch-target-optimized {
                min-width: 44px;
                min-height: 44px;
                padding: var(--touch-padding, 0);
                display: flex;
                align-items: center;
                justify-content: center;
            }

            /* Viewport Height Fix */
            .vh-fix {
                height: calc(var(--vh, 1vh) * 100);
            }

            /* Safe Area Insets */
            .safe-area-top {
                padding-top: var(--safe-area-top, 0);
            }

            .safe-area-bottom {
                padding-bottom: var(--safe-area-bottom, 0);
            }

            /* Mobile Optimizations */
            .mobile-optimized {
                -webkit-text-size-adjust: 100%;
                -webkit-tap-highlight-color: transparent;
            }

            .mobile-optimized input,
            .mobile-optimized textarea,
            .mobile-optimized select {
                font-size: 16px; /* Prevents zoom on iOS */
            }

            /* Touch-friendly scrolling */
            .mobile-optimized {
                -webkit-overflow-scrolling: touch;
            }

            /* Grid Responsive */
            .grid-responsive {
                display: grid;
                gap: var(--neo-spacing-md);
                transition: grid-template-columns var(--neo-animation-base) var(--neo-ease-in-out);
            }

            /* Hover Effects */
            .hover-status:hover {
                transform: translateY(-2px);
                box-shadow: var(--neo-shadow-lg);
            }

            /* Offline Styles */
            .offline {
                opacity: 0.8;
            }

            .offline-status {
                background: var(--neo-color-warning-50);
                border: 1px dashed var(--neo-color-warning-300);
            }

            /* Keyboard Visible */
            .keyboard-visible {
                padding-bottom: env(keyboard-inset-height, 0);
            }

            /* Lazy Loading */
            img.lazy {
                opacity: 0;
                transition: opacity var(--neo-animation-base) var(--neo-ease-in-out);
            }

            img.loaded {
                opacity: 1;
            }

            /* Breakpoint Classes */
            .breakpoint-xs .hide-xs { display: none !important; }
            .breakpoint-sm .hide-sm { display: none !important; }
            .breakpoint-md .hide-md { display: none !important; }
            .breakpoint-lg .hide-lg { display: none !important; }
            .breakpoint-xl .hide-xl { display: none !important; }
            .breakpoint-2xl .hide-2xl { display: none !important; }

            .breakpoint-xs .show-xs { display: block !important; }
            .breakpoint-sm .show-sm { display: block !important; }
            .breakpoint-md .show-md { display: block !important; }
            .breakpoint-lg .show-lg { display: block !important; }
            .breakpoint-xl .show-xl { display: block !important; }
            .breakpoint-2xl .show-2xl { display: block !important; }

            /* Responsive Utilities */
            .mobile-only {
                display: block;
            }

            .tablet-up {
                display: none;
            }

            .desktop-up {
                display: none;
            }

            @media (min-width: 768px) {
                .mobile-only { display: none; }
                .tablet-up { display: block; }
            }

            @media (min-width: 1024px) {
                .tablet-up { display: none; }
                .desktop-up { display: block; }
            }

            /* Orientation Styles */
            @media (orientation: portrait) {
                .portrait-only { display: block; }
                .landscape-only { display: none; }
            }

            @media (orientation: landscape) {
                .portrait-only { display: none; }
                .landscape-only { display: block; }
            }

            /* Print Styles */
            @media print {
                .no-print { display: none !important; }
                .print-only { display: block !important; }
            }

            /* High DPI Displays */
            @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
                .high-dpi {
                    image-rendering: -webkit-optimize-contrast;
                    image-rendering: crisp-edges;
                }
            }

            /* Reduced Motion */
            @media (prefers-reduced-motion: reduce) {
                * {
                    animation-duration: 0.01ms !important;
                    animation-iteration-count: 1 !important;
                    transition-duration: 0.01ms !important;
                }
            }

            /* Dark Mode */
            @media (prefers-color-scheme: dark) {
                .auto-dark {
                    color-scheme: dark;
                }
            }

            /* High Contrast */
            @media (prefers-contrast: high) {
                .high-contrast {
                    filter: contrast(150%);
                }
            }
        `;

        const style = document.createElement('style');
        style.textContent = responsiveCSS;
        document.head.appendChild(style);
    }

    // Public API
    getCurrentBreakpoint() {
        return this.currentBreakpoint;
    }

    isBreakpoint(breakpoint) {
        return this.currentBreakpoint === breakpoint;
    }

    isBreakpointUp(breakpoint) {
        const breakpointOrder = ['xs', 'sm', 'md', 'lg', 'xl', '2xl'];
        const currentIndex = breakpointOrder.indexOf(this.currentBreakpoint);
        const targetIndex = breakpointOrder.indexOf(breakpoint);
        return currentIndex >= targetIndex;
    }

    isBreakpointDown(breakpoint) {
        const breakpointOrder = ['xs', 'sm', 'md', 'lg', 'xl', '2xl'];
        const currentIndex = breakpointOrder.indexOf(this.currentBreakpoint);
        const targetIndex = breakpointOrder.indexOf(breakpoint);
        return currentIndex <= targetIndex;
    }
}

// Global instance
window.mobileFirstResponsive = new MobileFirstResponsive();

// Export for module usage
export default MobileFirstResponsive;
