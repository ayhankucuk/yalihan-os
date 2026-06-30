// Yalıhan Emlak Performance Optimizer
console.log('Performance Optimizer: Loading...');

// Check if PerformanceOptimizer already exists
if (typeof PerformanceOptimizer === 'undefined') {
    // Performance monitoring
    class PerformanceOptimizer {
        constructor() {
            this.metrics = {};
            this.init();
        }

        init() {
            this.measurePageLoad();
            this.measureResourceTiming();
            this.measureUserInteraction();
            this.optimizeImages();
            this.preloadCriticalResources();
        }

        measurePageLoad() {
            if ('performance' in window) {
                window.addEventListener('load', () => {
                    const perfData = performance.getEntriesByType('navigation')[0];

                    this.metrics.pageLoad = {
                        domContentLoaded:
                            perfData.domContentLoadedEventEnd - perfData.domContentLoadedEventStart,
                        loadComplete: perfData.loadEventEnd - perfData.loadEventStart,
                        totalTime: perfData.loadEventEnd - perfData.navigationStart,
                    };

                    console.log('Performance Metrics:', this.metrics);

                    // Send to analytics if available
                    this.sendToAnalytics();
                });
            }
        }

        measureResourceTiming() {
            if ('performance' in window) {
                const resources = performance.getEntriesByType('resource');

                resources.forEach((resource) => {
                    if (resource.duration > 1000) {
                        // Resources taking more than 1 second
                        console.warn(
                            'Slow resource detected:',
                            resource.name,
                            resource.duration + 'ms'
                        );
                    }
                });
            }
        }

        measureUserInteraction() {
            // Measure First Input Delay (FID)
            if ('PerformanceObserver' in window) {
                const observer = new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (entry.processingStart - entry.startTime > 100) {
                            console.warn(
                                'High input delay detected:',
                                entry.processingStart - entry.startTime + 'ms'
                            );
                        }
                    }
                });

                observer.observe({ entryTypes: ['first-input'] });
            }
        }

        optimizeImages() {
            // Lazy loading for images
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.classList.remove('lazy');
                                observer.unobserve(img);
                            }
                        }
                    });
                });

                // Observe all lazy images
                document.querySelectorAll('img[data-src]').forEach((img) => {
                    imageObserver.observe(img);
                });
            }
        }

        preloadCriticalResources() {
            // Preload critical CSS
            const criticalCSS = document.createElement('link');
            criticalCSS.rel = 'preload';
            criticalCSS.href = '/css/unified-design-system.css';
            criticalCSS.as = 'style';
            criticalCSS.onload = () => {
                criticalCSS.rel = 'stylesheet';
            };
            document.head.appendChild(criticalCSS);

            // Preload critical JavaScript - only if not already loaded
            if (!document.querySelector('script[src*="app-B3jJP77I.js"]')) {
                const criticalJS = document.createElement('link');
                criticalJS.rel = 'preload';
                criticalJS.href = '/build/assets/app-B3jJP77I.js';
                criticalJS.as = 'script';
                criticalJS.onload = () => {
                    const script = document.createElement('script');
                    script.src = criticalJS.href;
                    script.defer = true;
                    document.head.appendChild(script);
                };
                document.head.appendChild(criticalJS);
            }
        }

        sendToAnalytics() {
            // Send performance data to analytics
            if (typeof gtag !== 'undefined') {
                gtag('event', 'page_load_time', {
                    custom_parameter_1: this.metrics.pageLoad.totalTime,
                    custom_parameter_2: this.metrics.pageLoad.domContentLoaded,
                });
            }
        }
    }

    // Initialize performance optimizer
    document.addEventListener('DOMContentLoaded', () => {
        new PerformanceOptimizer();
    });

    // Service Worker performance monitoring
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data.type === 'PERFORMANCE_METRICS') {
                console.log('Service Worker Performance:', event.data.metrics);
            }
        });
    }

    console.log('Performance Optimizer: Loaded successfully');
}
