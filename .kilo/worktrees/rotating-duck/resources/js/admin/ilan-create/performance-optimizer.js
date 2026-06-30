// Yalıhan Bekçi - Performance Optimizer
// Advanced performance optimization system

class PerformanceOptimizer {
    constructor() {
        this.cache = new Map();
        this.observers = new Map();
        this.performanceMetrics = {
            loadTimes: [],
            renderTimes: [],
            apiResponseTimes: [],
        };
        this.init();
    }

    init() {
        this.setupPerformanceMonitoring();
        this.setupCriticalCSS();
        this.setupImageOptimization();
        this.setupAPICaching();
        this.setupBundleOptimization();
    }

    // ⚡ Component lazy loading (Sadece aktif step yükle)
    setupStepBasedLoading() {
        const stepObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const stepElement = entry.target;
                        const stepNumber = parseInt(stepElement.dataset.step);
                        this.loadStepComponents(stepNumber);
                    }
                });
            },
            {
                threshold: 0.1,
                rootMargin: '50px',
            }
        );

        // Observe all step elements
        document.querySelectorAll('[data-step]').forEach((step) => {
            stepObserver.observe(step);
        });
    }

    async loadStepComponents(stepNumber) {
        const stepComponents = {
            1: ['ai-content'],
            2: ['location-map'],
            3: ['price-calculator'],
            4: ['feature-selector'],
            5: ['feature-selector'],
            6: ['photo-upload', 'image-analyzer'],
            7: ['contact-form'],
            8: ['seo-optimizer'],
            9: ['publishing-settings'],
            10: ['marketing-options'],
        };

        const componentsToLoad = stepComponents[stepNumber] || [];

        for (const componentName of componentsToLoad) {
            if (!this.cache.has(componentName)) {
                await this.preloadComponent(componentName);
            }
        }
    }

    async preloadComponent(componentName) {
        const startTime = performance.now();

        try {
            const component = await window.lazyLoader.loadComponent(componentName);
            this.cache.set(componentName, component);

            const loadTime = performance.now() - startTime;
            this.performanceMetrics.loadTimes.push({
                component: componentName,
                loadTime,
                timestamp: Date.now(),
            });

            console.log(`✅ Component ${componentName} loaded in ${loadTime.toFixed(2)}ms`);
        } catch (error) {
            console.error(`❌ Failed to load component ${componentName}:`, error);
        }
    }

    // 📦 Bundle optimization (Critical CSS inline)
    setupCriticalCSS() {
        const criticalCSS = `
            /* Critical CSS for ilan-create */
            .form-step { opacity: 0; transform: translateX(20px); transition: all 0.3s ease; }
            .form-step.active { opacity: 1; transform: translateX(0); }
            .skeleton-loader { background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%); background-size: 200% 100%; animation: loading 1.5s infinite; }
            @keyframes loading { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
            .progress-bar { height: 4px; background: #e5e7eb; border-radius: 2px; overflow: hidden; }
            .progress-fill { height: 100%; background: linear-gradient(90deg, #3b82f6, #1d4ed8); transition: width 0.3s ease; }
        `;

        // Inject critical CSS
        const style = document.createElement('style');
        style.textContent = criticalCSS;
        document.head.appendChild(style);
    }

    // 🗜️ Image compression (Auto WebP conversion)
    setupImageOptimization() {
        // WebP support detection
        const webpSupported = this.detectWebPSupport();

        // Image optimization service
        this.imageOptimizer = {
            webpSupported,
            quality: 85,
            maxWidth: 1920,
            maxHeight: 1080,

            optimizeImage: async (file) => {
                return new Promise((resolve) => {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const img = new Image();

                    img.onload = () => {
                        // Calculate new dimensions
                        const { width, height } = this.calculateDimensions(
                            img.width,
                            img.height,
                            this.imageOptimizer.maxWidth,
                            this.imageOptimizer.maxHeight
                        );

                        canvas.width = width;
                        canvas.height = height;

                        // Draw and compress
                        ctx.drawImage(img, 0, 0, width, height);

                        // Convert to WebP if supported
                        const format = this.imageOptimizer.webpSupported
                            ? 'image/webp'
                            : 'image/jpeg';
                        const quality = this.imageOptimizer.quality / 100;

                        canvas.toBlob(resolve, format, quality);
                    };

                    img.src = URL.createObjectURL(file);
                });
            },

            createThumbnail: async (file, size = 200) => {
                return new Promise((resolve) => {
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const img = new Image();

                    img.onload = () => {
                        canvas.width = size;
                        canvas.height = size;

                        ctx.drawImage(img, 0, 0, size, size);
                        canvas.toBlob(resolve, 'image/jpeg', 0.8);
                    };

                    img.src = URL.createObjectURL(file);
                });
            },
        };
    }

    detectWebPSupport() {
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    }

    calculateDimensions(originalWidth, originalHeight, maxWidth, maxHeight) {
        let width = originalWidth;
        let height = originalHeight;

        if (width > maxWidth) {
            height = (height * maxWidth) / width;
            width = maxWidth;
        }

        if (height > maxHeight) {
            width = (width * maxHeight) / height;
            height = maxHeight;
        }

        return { width: Math.round(width), height: Math.round(height) };
    }

    // 🚀 API caching (Redis ile kategori/lokasyon cache)
    setupAPICaching() {
        this.apiCache = {
            // In-memory cache (Redis fallback)
            cache: new Map(),
            ttl: {
                categories: 3600000, // 1 hour
                locations: 1800000, // 30 minutes
                features: 7200000, // 2 hours
                ai_suggestions: 300000, // 5 minutes
            },

            async get(key) {
                const cached = this.cache.get(key);
                if (cached && Date.now() - cached.timestamp < this.ttl[key.split(':')[0]]) {
                    return cached.data;
                }
                return null;
            },

            async set(key, data, customTTL = null) {
                const ttl = customTTL || this.ttl[key.split(':')[0]] || 300000;
                this.cache.set(key, {
                    data,
                    timestamp: Date.now(),
                    ttl,
                });
            },

            async fetchWithCache(url, options = {}) {
                const cacheKey = `api:${url}:${JSON.stringify(options)}`;

                // Try cache first
                const cached = await this.get(cacheKey);
                if (cached) {
                    return cached;
                }

                // Fetch from API
                const startTime = performance.now();
                try {
                    const response = await fetch(url, options);
                    const data = await response.json();

                    const responseTime = performance.now() - startTime;
                    this.performanceMetrics.apiResponseTimes.push({
                        url,
                        responseTime,
                        timestamp: Date.now(),
                    });

                    // Cache the result
                    await this.set(cacheKey, data);

                    return data;
                } catch (error) {
                    console.error('API fetch failed:', error);
                    throw error;
                }
            },
        };
    }

    // 📊 Performance monitoring (User timing API)
    setupPerformanceMonitoring() {
        // Mark performance milestones
        this.mark = (name) => {
            performance.mark(name);
            console.log(`📊 Performance mark: ${name}`);
        };

        // Measure performance
        this.measure = (name, startMark, endMark) => {
            performance.measure(name, startMark, endMark);
            const measure = performance.getEntriesByName(name)[0];
            console.log(`⏱️ Performance measure: ${name} - ${measure.duration.toFixed(2)}ms`);
            return measure.duration;
        };

        // Long task monitoring
        if ('PerformanceObserver' in window) {
            const observer = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (entry.duration > 50) {
                        console.warn(`⚠️ Long task detected: ${entry.duration.toFixed(2)}ms`);
                        this.performanceMetrics.longTasks = this.performanceMetrics.longTasks || [];
                        this.performanceMetrics.longTasks.push({
                            duration: entry.duration,
                            timestamp: Date.now(),
                        });
                    }
                });
            });

            observer.observe({ entryTypes: ['longtask'] });
        }

        // Core Web Vitals monitoring
        this.monitorWebVitals();
    }

    monitorWebVitals() {
        // Largest Contentful Paint (LCP)
        if ('PerformanceObserver' in window) {
            const lcpObserver = new PerformanceObserver((list) => {
                const entries = list.getEntries();
                const lastEntry = entries[entries.length - 1];
                console.log(`🎯 LCP: ${lastEntry.startTime.toFixed(2)}ms`);
            });
            lcpObserver.observe({ entryTypes: ['largest-contentful-paint'] });

            // First Input Delay (FID)
            const fidObserver = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    console.log(`⚡ FID: ${entry.processingStart - entry.startTime}ms`);
                });
            });
            fidObserver.observe({ entryTypes: ['first-input'] });

            // Cumulative Layout Shift (CLS)
            let clsValue = 0;
            const clsObserver = new PerformanceObserver((list) => {
                list.getEntries().forEach((entry) => {
                    if (!entry.hadRecentInput) {
                        clsValue += entry.value;
                    }
                });
                console.log(`📐 CLS: ${clsValue.toFixed(4)}`);
            });
            clsObserver.observe({ entryTypes: ['layout-shift'] });
        }
    }

    // Bundle optimization utilities
    setupBundleOptimization() {
        // Dynamic import with prefetching
        this.prefetchComponent = (componentName) => {
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = `/js/components/${componentName}.js`;
            document.head.appendChild(link);
        };

        // Resource hints
        this.addResourceHints = () => {
            const hints = [
                { rel: 'dns-prefetch', href: 'https://api.openai.com' },
                { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
            ];

            hints.forEach((hint) => {
                const link = document.createElement('link');
                Object.assign(link, hint);
                document.head.appendChild(link);
            });
        };

        // Code splitting utilities
        this.loadChunk = async (chunkName) => {
            try {
                const module = await import(`/js/chunks/${chunkName}.js`);
                return module.default;
            } catch (error) {
                console.error(`Failed to load chunk ${chunkName}:`, error);
                return null;
            }
        };
    }

    // Performance analytics
    getPerformanceReport() {
        const report = {
            timestamp: Date.now(),
            loadTimes: this.performanceMetrics.loadTimes,
            apiResponseTimes: this.performanceMetrics.apiResponseTimes,
            longTasks: this.performanceMetrics.longTasks || [],
            memoryUsage: this.getMemoryUsage(),
            cacheStats: this.getCacheStats(),
        };

        return report;
    }

    getMemoryUsage() {
        if ('memory' in performance) {
            return {
                usedJSHeapSize: performance.memory.usedJSHeapSize,
                totalJSHeapSize: performance.memory.totalJSHeapSize,
                jsHeapSizeLimit: performance.memory.jsHeapSizeLimit,
            };
        }
        return null;
    }

    getCacheStats() {
        return {
            size: this.cache.size,
            apiCacheSize: this.apiCache.cache.size,
            hitRate: this.calculateCacheHitRate(),
        };
    }

    calculateCacheHitRate() {
        // Simple cache hit rate calculation
        const totalRequests = this.performanceMetrics.apiResponseTimes.length;
        const cacheHits = totalRequests - this.performanceMetrics.apiResponseTimes.length;
        return totalRequests > 0 ? (cacheHits / totalRequests) * 100 : 0;
    }

    // Cleanup and optimization
    cleanup() {
        // Clear expired cache entries
        const now = Date.now();
        for (const [key, value] of this.cache.entries()) {
            if (now - value.timestamp > value.ttl) {
                this.cache.delete(key);
            }
        }

        // Clear old performance metrics
        const cutoffTime = now - 24 * 60 * 60 * 1000; // 24 hours
        this.performanceMetrics.loadTimes = this.performanceMetrics.loadTimes.filter(
            (metric) => metric.timestamp > cutoffTime
        );
        this.performanceMetrics.apiResponseTimes = this.performanceMetrics.apiResponseTimes.filter(
            (metric) => metric.timestamp > cutoffTime
        );
    }

    // Auto cleanup every hour
    startAutoCleanup() {
        setInterval(() => {
            this.cleanup();
        }, 3600000); // 1 hour
    }
}

// Global instance
window.performanceOptimizer = new PerformanceOptimizer();

// Export for module usage
export default PerformanceOptimizer;
