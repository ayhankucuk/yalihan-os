/**
 * ⚡ PERFORMANCE & LOADING UX SYSTEM - NEO DESIGN
 * Skeleton screens, lazy loading, progressive loading, smooth transitions
 * Tarih: 19 Ekim 2025
 * Context7 Compliant & Neo Design System
 */

class PerformanceLoadingSystem {
    constructor(options = {}) {
        this.options = {
            // Skeleton Configuration
            enableSkeletons: true,
            skeletonAnimationDuration: 1500,
            skeletonBaseColor: '#f3f4f6',
            skeletonHighlightColor: '#e5e7eb',

            // Lazy Loading
            enableLazyLoading: true,
            lazyLoadingThreshold: 200,
            lazyLoadingDelay: 100,

            // Progressive Loading
            enableProgressiveLoading: true,
            progressiveLoadingSteps: ['critical', 'important', 'normal', 'low'],

            // Performance Optimization
            enableBundleOptimization: true,
            imageOptimization: true,
            prefetchingEnabled: true,

            // Animation Settings
            transitionDuration: 300,
            easeFunction: 'cubic-bezier(0.4, 0, 0.2, 1)',

            ...options,
        };

        this.state = {
            isLoading: false,
            loadingProgress: 0,
            loadedResources: new Set(),
            intersectionObserver: null,
            performanceMetrics: {
                startTime: performance.now(),
                loadTime: 0,
                renderTime: 0,
                resourcesLoaded: 0,
                totalResources: 0,
            },
        };

        this.init();
    }

    init() {
        this.injectCSS();
        this.setupIntersectionObserver();
        this.setupProgressiveLoading();
        this.setupImageOptimization();
        this.setupSmoothTransitions();
        this.monitorPerformance();

        console.log('[PerformanceLoadingSystem] Initialized successfully');
    }

    injectCSS() {
        if (document.getElementById('performance-loading-css')) return;

        const css = `
            /* Skeleton Loading Animations */
            .neo-skeleton {
                background: linear-gradient(90deg,
                    ${this.options.skeletonBaseColor} 25%,
                    ${this.options.skeletonHighlightColor} 50%,
                    ${this.options.skeletonBaseColor} 75%);
                background-size: 200% 100%;
                animation: neo-skeleton-loading ${this.options.skeletonAnimationDuration}ms ease-in-out infinite;
                border-radius: 0.5rem;
                overflow: hidden;
            }

            @keyframes neo-skeleton-loading {
                0% { background-position: -200% 0; }
                100% { background-position: 200% 0; }
            }

            .neo-skeleton-text {
                height: 1rem;
                margin-bottom: 0.5rem;
            }

            .neo-skeleton-text:last-child {
                width: 75%;
                margin-bottom: 0;
            }

            .neo-skeleton-title {
                height: 1.5rem;
                width: 60%;
                margin-bottom: 1rem;
            }

            .neo-skeleton-avatar {
                width: 3rem;
                height: 3rem;
                border-radius: 50%;
            }

            .neo-skeleton-card {
                height: 12rem;
                margin-bottom: 1rem;
            }

            .neo-skeleton-button {
                height: 2.5rem;
                width: 8rem;
                border-radius: 0.375rem;
            }

            /* Lazy Loading States */
            .neo-lazy-loading {
                opacity: 0.6;
                filter: blur(2px);
                transition: all ${this.options.transitionDuration}ms ${this.options.easeFunction};
            }

            .neo-lazy-loaded {
                opacity: 1;
                filter: blur(0);
            }

            /* Progressive Loading */
            .neo-progressive-load {
                opacity: 0;
                transform: translateY(20px);
                transition: all ${this.options.transitionDuration}ms ${this.options.easeFunction};
            }

            .neo-progressive-loaded {
                opacity: 1;
                transform: translateY(0);
            }

            /* Loading Overlays */
            .neo-loading-overlay {
                position: absolute;
                inset: 0;
                background: rgba(255, 255, 255, 0.8);
                backdrop-filter: blur(4px);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 50;
                transition: opacity ${this.options.transitionDuration}ms ease-out;
            }

            .dark .neo-loading-overlay {
                background: rgba(0, 0, 0, 0.8);
            }

            .neo-loading-spinner {
                width: 2rem;
                height: 2rem;
                border: 2px solid #e5e7eb;
                border-top: 2px solid #3b82f6;
                border-radius: 50%;
                animation: neo-spin 1s linear infinite;
            }

            @keyframes neo-spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }

            /* Progress Bars */
            .neo-progress-container {
                width: 100%;
                height: 0.25rem;
                background-color: #e5e7eb;
                border-radius: 0.125rem;
                overflow: hidden;
            }

            .neo-progress-bar {
                height: 100%;
                background: linear-gradient(90deg, #3b82f6, #1d4ed8);
                border-radius: 0.125rem;
                transition: width ${this.options.transitionDuration}ms ${this.options.easeFunction};
                position: relative;
            }

            .neo-progress-bar::after {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
                animation: neo-progress-shine 2s ease-in-out infinite;
            }

            @keyframes neo-progress-shine {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }

            /* Smooth Transitions */
            .neo-smooth-transition {
                transition: all ${this.options.transitionDuration}ms ${this.options.easeFunction};
            }

            .neo-fade-in {
                animation: neo-fade-in ${this.options.transitionDuration}ms ${this.options.easeFunction} forwards;
            }

            @keyframes neo-fade-in {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .neo-slide-up {
                animation: neo-slide-up ${this.options.transitionDuration}ms ${this.options.easeFunction} forwards;
            }

            @keyframes neo-slide-up {
                from { opacity: 0; transform: translateY(20px); }
                to { opacity: 1; transform: translateY(0); }
            }

            /* Performance Optimized Image Loading */
            .neo-optimized-image {
                transition: opacity ${this.options.transitionDuration}ms ease-out;
                background-color: #f3f4f6;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24'%3E%3Cpath stroke='%23D1D5DB' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z'/%3E%3C/svg%3E");
                background-size: 2rem 2rem;
                background-repeat: no-repeat;
                background-position: center;
            }

            .neo-optimized-image.loaded {
                background-image: none;
            }

            /* Loading States for Different Components */
            .neo-card-loading {
                position: relative;
                overflow: hidden;
            }

            .neo-card-loading::before {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
                transform: translateX(-100%);
                animation: neo-card-shimmer 2s infinite;
            }

            @keyframes neo-card-shimmer {
                100% { transform: translateX(100%); }
            }

            /* Responsive Performance Adjustments */
            @media (prefers-reduced-motion: reduce) {
                .neo-skeleton,
                .neo-lazy-loading,
                .neo-progressive-load,
                .neo-smooth-transition,
                .neo-fade-in,
                .neo-slide-up {
                    animation: none !important;
                    transition: none !important;
                }
            }

            @media (max-width: 768px) {
                .neo-skeleton {
                    animation-duration: 1000ms;
                }

                .neo-smooth-transition {
                    transition-duration: 200ms;
                }
            }
        `;

        const style = document.createElement('style');
        style.id = 'performance-loading-css';
        style.textContent = css;
        document.head.appendChild(style);
    }

    setupIntersectionObserver() {
        if (!this.options.enableLazyLoading) return;

        this.state.intersectionObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        this.loadLazyElement(entry.target);
                        this.state.intersectionObserver.unobserve(entry.target);
                    }
                });
            },
            {
                rootMargin: `${this.options.lazyLoadingThreshold}px`,
                threshold: 0.1,
            }
        );

        // Observe all lazy elements
        this.observeLazyElements();
    }

    observeLazyElements() {
        const lazyElements = document.querySelectorAll('[data-lazy]');
        lazyElements.forEach((element) => {
            this.state.intersectionObserver.observe(element);
            element.classList.add('neo-lazy-loading');
        });
    }

    loadLazyElement(element) {
        setTimeout(() => {
            const lazySrc = element.dataset.lazy;
            const lazyType = element.dataset.lazyType || 'image';

            switch (lazyType) {
                case 'image':
                    this.loadLazyImage(element, lazySrc);
                    break;
                case 'content':
                    this.loadLazyContent(element, lazySrc);
                    break;
                case 'component':
                    this.loadLazyComponent(element, lazySrc);
                    break;
                default:
                    this.loadLazyImage(element, lazySrc);
            }
        }, this.options.lazyLoadingDelay);
    }

    loadLazyImage(element, src) {
        const img = new Image();
        img.onload = () => {
            element.src = src;
            element.classList.remove('neo-lazy-loading');
            element.classList.add('neo-lazy-loaded', 'neo-optimized-image', 'loaded');
            this.state.performanceMetrics.resourcesLoaded++;
        };
        img.onerror = () => {
            element.classList.remove('neo-lazy-loading');
            element.classList.add('neo-image-error');
        };
        img.src = src;
    }

    loadLazyContent(element, url) {
        fetch(url)
            .then((response) => response.text())
            .then((html) => {
                element.innerHTML = html;
                element.classList.remove('neo-lazy-loading');
                element.classList.add('neo-lazy-loaded');
                this.setupProgressiveLoading(element);
            })
            .catch((error) => {
                console.error('Lazy content loading failed:', error);
                element.innerHTML = '<p class="text-red-500">İçerik yüklenemedi</p>';
            });
    }

    loadLazyComponent(element, componentName) {
        // Dynamic component loading
        import(`/js/components/${componentName}.js`)
            .then((module) => {
                const Component = module.default;
                new Component(element);
                element.classList.remove('neo-lazy-loading');
                element.classList.add('neo-lazy-loaded');
            })
            .catch((error) => {
                console.error('Component loading failed:', error);
            });
    }

    setupProgressiveLoading(container = document) {
        if (!this.options.enableProgressiveLoading) return;

        this.options.progressiveLoadingSteps.forEach((priority, index) => {
            const elements = container.querySelectorAll(`[data-priority="${priority}"]`);

            elements.forEach((element) => {
                element.classList.add('neo-progressive-load');

                setTimeout(() => {
                    element.classList.remove('neo-progressive-load');
                    element.classList.add('neo-progressive-loaded');
                }, index * 150); // Staggered loading
            });
        });
    }

    setupImageOptimization() {
        if (!this.options.imageOptimization) return;

        const images = document.querySelectorAll('img:not([data-lazy])');
        images.forEach((img) => {
            img.classList.add('neo-optimized-image');

            if (img.complete) {
                img.classList.add('loaded');
            } else {
                img.addEventListener('load', () => {
                    img.classList.add('loaded');
                    this.state.performanceMetrics.resourcesLoaded++;
                });
            }
        });
    }

    setupSmoothTransitions() {
        // Add smooth transitions to interactive elements
        const interactiveElements = document.querySelectorAll(
            'button, a, .neo-card, [role="button"], [tabindex="0"]'
        );

        interactiveElements.forEach((element) => {
            if (!element.classList.contains('neo-smooth-transition')) {
                element.classList.add('neo-smooth-transition');
            }
        });
    }

    monitorPerformance() {
        // Performance observer for monitoring
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.entryType === 'navigation') {
                        this.state.performanceMetrics.loadTime =
                            entry.loadEventEnd - entry.loadEventStart;
                    }
                    if (entry.entryType === 'paint' && entry.name === 'first-contentful-paint') {
                        this.state.performanceMetrics.renderTime = entry.startTime;
                    }
                });
            });

            observer.observe({ entryTypes: ['navigation', 'paint'] });
        }

        // Monitor resource loading
        window.addEventListener('load', () => {
            this.state.performanceMetrics.totalResources =
                document.querySelectorAll('img, link, script').length;
            console.log('[Performance Metrics]', this.state.performanceMetrics);
        });
    }

    // Skeleton Loading Methods
    showSkeleton(element, type = 'default') {
        if (!this.options.enableSkeletons) return;

        const skeletons = {
            default: this.createDefaultSkeleton(),
            card: this.createCardSkeleton(),
            list: this.createListSkeleton(),
            table: this.createTableSkeleton(),
            form: this.createFormSkeleton(),
            dashboard: this.createDashboardSkeleton(),
        };

        const skeletonHTML = skeletons[type] || skeletons.default;
        element.innerHTML = skeletonHTML;
        element.classList.add('neo-skeleton-container');
    }

    hideSkeleton(element) {
        element.classList.remove('neo-skeleton-container');
        const skeletons = element.querySelectorAll('.neo-skeleton');
        skeletons.forEach((skeleton) => skeleton.remove());
    }

    createDefaultSkeleton() {
        return `
            <div class="space-y-4">
                <div class="neo-skeleton neo-skeleton-title"></div>
                <div class="neo-skeleton neo-skeleton-text"></div>
                <div class="neo-skeleton neo-skeleton-text"></div>
                <div class="neo-skeleton neo-skeleton-text"></div>
            </div>
        `;
    }

    createCardSkeleton() {
        return `
            <div class="neo-skeleton neo-skeleton-card mb-4"></div>
            <div class="space-y-3">
                <div class="neo-skeleton neo-skeleton-title"></div>
                <div class="neo-skeleton neo-skeleton-text"></div>
                <div class="neo-skeleton neo-skeleton-text"></div>
                <div class="flex space-x-3 mt-4">
                    <div class="neo-skeleton neo-skeleton-button"></div>
                    <div class="neo-skeleton neo-skeleton-button"></div>
                </div>
            </div>
        `;
    }

    createListSkeleton() {
        return `
            <div class="space-y-4">
                ${Array(5)
                    .fill(0)
                    .map(
                        () => `
                    <div class="flex items-center space-x-4">
                        <div class="neo-skeleton neo-skeleton-avatar"></div>
                        <div class="flex-1 space-y-2">
                            <div class="neo-skeleton neo-skeleton-text w-3/4"></div>
                            <div class="neo-skeleton neo-skeleton-text w-1/2"></div>
                        </div>
                    </div>
                `
                    )
                    .join('')}
            </div>
        `;
    }

    createTableSkeleton() {
        return `
            <div class="space-y-3">
                ${Array(6)
                    .fill(0)
                    .map(
                        () => `
                    <div class="flex space-x-4">
                        <div class="neo-skeleton neo-skeleton-text flex-1"></div>
                        <div class="neo-skeleton neo-skeleton-text flex-1"></div>
                        <div class="neo-skeleton neo-skeleton-text flex-1"></div>
                        <div class="neo-skeleton neo-skeleton-button w-20"></div>
                    </div>
                `
                    )
                    .join('')}
            </div>
        `;
    }

    createFormSkeleton() {
        return `
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <div class="neo-skeleton h-4 w-20"></div>
                        <div class="neo-skeleton h-10 w-full"></div>
                    </div>
                    <div class="space-y-2">
                        <div class="neo-skeleton h-4 w-24"></div>
                        <div class="neo-skeleton h-10 w-full"></div>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="neo-skeleton h-4 w-32"></div>
                    <div class="neo-skeleton h-24 w-full"></div>
                </div>
                <div class="flex space-x-3">
                    <div class="neo-skeleton neo-skeleton-button"></div>
                    <div class="neo-skeleton neo-skeleton-button"></div>
                </div>
            </div>
        `;
    }

    createDashboardSkeleton() {
        return `
            <div class="space-y-6">
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    ${Array(4)
                        .fill(0)
                        .map(
                            () => `
                        <div class="neo-skeleton h-24 rounded-lg"></div>
                    `
                        )
                        .join('')}
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="neo-skeleton h-64 rounded-lg"></div>
                    <div class="neo-skeleton h-64 rounded-lg"></div>
                </div>

                <!-- Table -->
                <div class="neo-skeleton h-48 rounded-lg"></div>
            </div>
        `;
    }

    // Loading Overlay Methods
    showLoading(element, message = 'Yükleniyor...') {
        const overlay = document.createElement('div');
        overlay.className = 'neo-loading-overlay';
        overlay.innerHTML = `
            <div class="text-center">
                <div class="neo-loading-spinner mx-auto mb-4"></div>
                <p class="text-gray-600 dark:text-gray-400">${message}</p>
            </div>
        `;

        element.style.position = element.style.position || 'relative';
        element.appendChild(overlay);

        return overlay;
    }

    hideLoading(element) {
        const overlay = element.querySelector('.neo-loading-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.remove(), this.options.transitionDuration);
        }
    }

    // Progress Bar Methods
    createProgressBar(container, options = {}) {
        const progressHTML = `
            <div class="neo-progress-container">
                <div class="neo-progress-bar" style="width: 0%"></div>
            </div>
        `;

        container.innerHTML = progressHTML;
        return container.querySelector('.neo-progress-bar');
    }

    updateProgress(progressBar, percentage) {
        if (progressBar) {
            progressBar.style.width = `${Math.min(100, Math.max(0, percentage))}%`;
        }
    }

    // Bundle Optimization Methods
    async loadScriptAsync(src) {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    async loadStyleAsync(href) {
        return new Promise((resolve, reject) => {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = href;
            link.onload = resolve;
            link.onerror = reject;
            document.head.appendChild(link);
        });
    }

    prefetchResource(url, type = 'script') {
        if (!this.options.prefetchingEnabled) return;

        const link = document.createElement('link');
        link.rel = 'prefetch';
        link.href = url;
        if (type === 'script') link.as = 'script';
        if (type === 'style') link.as = 'style';
        if (type === 'image') link.as = 'image';
        document.head.appendChild(link);
    }

    // Performance Monitoring
    measurePerformance(name, fn) {
        const start = performance.now();
        const result = fn();
        const end = performance.now();

        console.log(`[Performance] ${name}: ${(end - start).toFixed(2)}ms`);
        return result;
    }

    // Utility Methods
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    throttle(func, limit) {
        let inThrottle;
        return function () {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => (inThrottle = false), limit);
            }
        };
    }

    // Public APIs
    enableSkeletonLoading(status = true) {
        this.options.enableSkeletons = status;
    }

    enableLazyLoading(status = true) {
        this.options.enableLazyLoading = status;
        if (status) {
            this.observeLazyElements();
        }
    }

    getPerformanceMetrics() {
        return { ...this.state.performanceMetrics };
    }

    destroy() {
        if (this.state.intersectionObserver) {
            this.state.intersectionObserver.disconnect();
        }

        const style = document.getElementById('performance-loading-css');
        if (style) {
            style.remove();
        }
    }
}

// Global instance
window.PerformanceLoadingSystem = PerformanceLoadingSystem;

// Auto-initialize
document.addEventListener('DOMContentLoaded', function () {
    if (!window.performanceLoader) {
        window.performanceLoader = new PerformanceLoadingSystem();

        // Add utility methods to window for easy access
        window.showSkeleton = (element, type) =>
            window.performanceLoader.showSkeleton(element, type);
        window.hideSkeleton = (element) => window.performanceLoader.hideSkeleton(element);
        window.showLoading = (element, message) =>
            window.performanceLoader.showLoading(element, message);
        window.hideLoading = (element) => window.performanceLoader.hideLoading(element);
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PerformanceLoadingSystem;
}
