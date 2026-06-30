/**
 * Performance Optimization Manager
 * Handles lazy loading, image optimization, and performance monitoring
 */

class PerformanceManager {
    constructor() {
        this.observers = new Map();
        this.performanceMetrics = {};
        this.isInitialized = false;

        this.init();
    }

    init() {
        if (this.isInitialized) return;

        this.setupLazyLoading();
        this.setupImageOptimization();
        this.setupPerformanceMonitoring();
        this.setupResourceHints();
        this.setupCriticalCSS();
        this.setupCodeSplitting();

        this.isInitialized = true;
        console.log('Performance Manager initialized');
    }

    setupLazyLoading() {
        // Intersection Observer for lazy loading
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(
                (entries, observer) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            this.loadImage(img);
                            observer.unobserve(img);
                        }
                    });
                },
                {
                    rootMargin: '50px 0px',
                    threshold: 0.01,
                }
            );

            // Observe all images with data-src
            document.querySelectorAll('img[data-src]').forEach((img) => {
                imageObserver.observe(img);
            });

            this.observers.set('images', imageObserver);
        }

        // Lazy load components
        this.setupComponentLazyLoading();
    }

    setupImageOptimization() {
        // WebP support detection
        this.detectWebPSupport().then((supportsWebP) => {
            if (supportsWebP) {
                document.documentElement.classList.add('webp');
            } else {
                document.documentElement.classList.add('no-webp');
            }
        });

        // Responsive images
        this.setupResponsiveImages();
    }

    setupPerformanceMonitoring() {
        // Core Web Vitals monitoring
        this.monitorCoreWebVitals();

        // Resource timing
        this.monitorResourceTiming();

        // User timing
        this.monitorUserTiming();

        // Memory usage
        this.monitorMemoryUsage();
    }

    setupResourceHints() {
        // Preload critical resources
        this.preloadCriticalResources();

        // Prefetch likely next pages
        this.prefetchNextPages();

        // DNS prefetch for external domains
        this.dnsPrefetch();
    }

    setupCriticalCSS() {
        // Inline critical CSS
        this.inlineCriticalCSS();

        // Load non-critical CSS asynchronously
        this.loadNonCriticalCSS();
    }

    setupCodeSplitting() {
        // Dynamic imports for non-critical features
        this.setupDynamicImports();

        // Route-based code splitting
        this.setupRouteBasedSplitting();
    }

    loadImage(img) {
        const src = img.dataset.src;
        if (src) {
            img.src = src;
            img.classList.remove('lazy');
            img.classList.add('loaded');

            // Remove data-src to prevent reloading
            delete img.dataset.src;
        }
    }

    setupComponentLazyLoading() {
        // Lazy load components based on viewport
        const componentObserver = new IntersectionObserver(
            (entries, observer) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const component = entry.target;
                        this.loadComponent(component);
                        observer.unobserve(component);
                    }
                });
            },
            {
                rootMargin: '100px 0px',
                threshold: 0.1,
            }
        );

        document.querySelectorAll('[data-component]').forEach((component) => {
            componentObserver.observe(component);
        });

        this.observers.set('components', componentObserver);
    }

    async loadComponent(component) {
        const componentName = component.dataset.component;

        try {
            // Dynamic import based on component name
            const module = await import(`./components/${componentName}.js`);
            if (module.default) {
                module.default(component);
            }
        } catch (error) {
            console.warn(`Failed to load component ${componentName}:`, error);
        }
    }

    async detectWebPSupport() {
        return new Promise((resolve) => {
            const webP = new Image();
            webP.onload = webP.onerror = () => {
                resolve(webP.height === 2);
            };
            webP.src =
                'data:image/webp;base64,UklGRjoAAABXRUJQVlA4IC4AAACyAgCdASoCAAIALmk0mk0iIiIiIgBoSygABc6WWgAA/veff/0PP8bA//LwYAAA';
        });
    }

    setupResponsiveImages() {
        // Generate responsive image sources
        document.querySelectorAll('img[data-responsive]').forEach((img) => {
            const src = img.src || img.dataset.src;
            if (src) {
                this.generateResponsiveSources(img, src);
            }
        });
    }

    generateResponsiveSources(img, src) {
        const sizes = [320, 640, 768, 1024, 1280, 1920];
        const srcset = sizes
            .map((size) => {
                const webpSrc = this.getOptimizedImageUrl(src, size, 'webp');
                const fallbackSrc = this.getOptimizedImageUrl(src, size, 'jpg');
                return `${webpSrc} ${size}w, ${fallbackSrc} ${size}w`;
            })
            .join(', ');

        img.srcset = srcset;
        img.sizes = '(max-width: 768px) 100vw, (max-width: 1024px) 50vw, 33vw';
    }

    getOptimizedImageUrl(src, width, format) {
        // This would integrate with your image optimization service
        const baseUrl = src.replace(/\.(jpg|jpeg|png|gif)$/, '');
        return `${baseUrl}_${width}w.${format}`;
    }

    monitorCoreWebVitals() {
        // Largest Contentful Paint (LCP)
        new PerformanceObserver((list) => {
            const entries = list.getEntries();
            const lastEntry = entries[entries.length - 1];
            this.performanceMetrics.lcp = lastEntry.startTime;
            this.reportMetric('lcp', lastEntry.startTime);
        }).observe({ entryTypes: ['largest-contentful-paint'] });

        // First Input Delay (FID)
        new PerformanceObserver((list) => {
            const entries = list.getEntries();
            entries.forEach((entry) => {
                this.performanceMetrics.fid = entry.processingStart - entry.startTime;
                this.reportMetric('fid', entry.processingStart - entry.startTime);
            });
        }).observe({ entryTypes: ['first-input'] });

        // Cumulative Layout Shift (CLS)
        let clsValue = 0;
        new PerformanceObserver((list) => {
            const entries = list.getEntries();
            entries.forEach((entry) => {
                if (!entry.hadRecentInput) {
                    clsValue += entry.value;
                }
            });
            this.performanceMetrics.cls = clsValue;
            this.reportMetric('cls', clsValue);
        }).observe({ entryTypes: ['layout-shift'] });
    }

    monitorResourceTiming() {
        // Monitor resource loading times
        window.addEventListener('load', () => {
            const resources = performance.getEntriesByType('resource');
            const resourceMetrics = {
                totalResources: resources.length,
                totalSize: 0,
                loadTime: 0,
            };

            resources.forEach((resource) => {
                resourceMetrics.totalSize += resource.transferSize || 0;
                resourceMetrics.loadTime += resource.duration || 0;
            });

            this.performanceMetrics.resources = resourceMetrics;
            this.reportMetric('resources', resourceMetrics);
        });
    }

    monitorUserTiming() {
        // Custom timing marks
        performance.mark('performance-manager-start');

        window.addEventListener('load', () => {
            performance.mark('performance-manager-end');
            performance.measure(
                'performance-manager-duration',
                'performance-manager-start',
                'performance-manager-end'
            );

            const measure = performance.getEntriesByName('performance-manager-duration')[0];
            this.performanceMetrics.userTiming = measure.duration;
            this.reportMetric('user-timing', measure.duration);
        });
    }

    monitorMemoryUsage() {
        if ('memory' in performance) {
            const memory = performance.memory;
            this.performanceMetrics.memory = {
                used: memory.usedJSHeapSize,
                total: memory.totalJSHeapSize,
                limit: memory.jsHeapSizeLimit,
            };
            this.reportMetric('memory', this.performanceMetrics.memory);
        }
    }

    preloadCriticalResources() {
        // Critical CSS already loaded via Vite - skip preload to avoid 404
        // Using Laravel asset() helper ensures proper path resolution

        // Preload critical fonts
        const criticalFont = document.createElement('link');
        criticalFont.rel = 'preload';
        criticalFont.href = '/fonts/inter-var.woff2';
        criticalFont.as = 'font';
        criticalFont.type = 'font/woff2';
        criticalFont.crossOrigin = 'anonymous';
        document.head.appendChild(criticalFont);
    }

    prefetchNextPages() {
        // Prefetch likely next pages based on current page
        const currentPath = window.location.pathname;
        const prefetchPages = this.getPrefetchPages(currentPath);

        prefetchPages.forEach((page) => {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = page;
            document.head.appendChild(link);
        });
    }

    getPrefetchPages(currentPath) {
        const prefetchMap = {
            '/admin': ['/admin/ilanlar', '/admin/musteriler', '/admin/satislar'],
            '/admin/ilanlar': ['/admin/ilanlar/create', '/admin/ilanlar/1'],
            '/admin/musteriler': ['/admin/musteriler/create'],
            '/admin/satislar': ['/admin/satislar/create'],
        };

        return prefetchMap[currentPath] || [];
    }

    dnsPrefetch() {
        const externalDomains = ['fonts.googleapis.com', 'fonts.gstatic.com', 'cdn.jsdelivr.net'];

        externalDomains.forEach((domain) => {
            const link = document.createElement('link');
            link.rel = 'dns-prefetch';
            link.href = `//${domain}`;
            document.head.appendChild(link);
        });
    }

    inlineCriticalCSS() {
        // This would be handled by your build process
        // For now, we'll just ensure critical CSS is loaded
        const criticalCSS = document.createElement('style');
        criticalCSS.textContent = `
            /* Critical CSS would be inlined here */
            .context7-card { display: block; }
            .context7-btn { display: inline-flex; }
        `;
        document.head.appendChild(criticalCSS);
    }

    loadNonCriticalCSS() {
        // Load non-critical CSS asynchronously
        const nonCriticalCSS = document.createElement('link');
        nonCriticalCSS.rel = 'preload';
        nonCriticalCSS.href = '/css/non-critical.css';
        nonCriticalCSS.as = 'style';
        nonCriticalCSS.onload = () => {
            nonCriticalCSS.rel = 'stylesheet';
        };
        document.head.appendChild(nonCriticalCSS);
    }

    setupDynamicImports() {
        // Dynamic imports for non-critical features
        this.dynamicImports = {
            chart: () => import('./charts.js'),
            editor: () => import('./editor.js'),
            calendar: () => import('./calendar.js'),
        };
    }

    setupRouteBasedSplitting() {
        // Route-based code splitting
        const routes = {
            '/admin/analitik': () => import('./pages/analitik.js'),
            '/admin/raporlar': () => import('./pages/raporlar.js'),
            '/admin/ayarlar': () => import('./pages/ayarlar.js'),
        };

        const currentRoute = window.location.pathname;
        if (routes[currentRoute]) {
            routes[currentRoute]().catch((error) => {
                console.warn(`Failed to load route ${currentRoute}:`, error);
            });
        }
    }

    reportMetric(name, value) {
        // Send metrics to analytics service
        if (typeof gtag !== 'undefined') {
            gtag('event', 'performance_metric', {
                metric_name: name,
                metric_value: value,
            });
        }

        // Send to custom analytics endpoint
        this.sendToAnalytics(name, value);
    }

    async sendToAnalytics(name, value) {
        try {
            const url = window.APIConfig && window.APIConfig.analytics && window.APIConfig.analytics.performance
                ? window.APIConfig.analytics.performance
                : '/api/analytics/performance';
            await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content'),
                },
                body: JSON.stringify({
                    metric: name,
                    value: value,
                    timestamp: Date.now(),
                    url: window.location.href,
                    userAgent: navigator.userAgent,
                }),
            });
        } catch (error) {
            console.warn('Failed to send performance metric:', error);
        }
    }

    // Public methods
    getPerformanceMetrics() {
        return this.performanceMetrics;
    }

    optimizeImages() {
        // Optimize all images on the page
        document.querySelectorAll('img').forEach((img) => {
            this.optimizeImage(img);
        });
    }

    optimizeImage(img) {
        // Add loading="lazy" if not present
        if (!img.hasAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }

        // Add decoding="async" for better performance
        if (!img.hasAttribute('decoding')) {
            img.setAttribute('decoding', 'async');
        }
    }

    cleanup() {
        // Clean up observers
        this.observers.forEach((observer) => {
            observer.disconnect();
        });
        this.observers.clear();
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.performanceManager = new PerformanceManager();
});

// Export for use in other scripts
window.PerformanceManager = PerformanceManager;
