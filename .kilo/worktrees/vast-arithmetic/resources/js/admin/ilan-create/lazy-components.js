// Yalıhan Bekçi - AI Enhanced Lazy Loading Components
// Component bazlı lazy loading sistemi

class LazyComponentLoader {
    constructor() {
        this.loadedComponents = new Map();
        this.loadingComponents = new Set();
        this.componentCache = new Map();
    }

    // Component registry
    componentRegistry = {
        'location-map': () => import('./components/LocationMap.js'),
        'ai-content': () => import('./components/AIContent.js'),
        'photo-upload': () => import('./components/PhotoUpload.js'),
        'feature-selector': () => import('./components/FeatureSelector.js'),
        'price-calculator': () => import('./components/PriceCalculator.js'),
        'seo-optimizer': () => import('./components/SEOOptimizer.js'),
        'image-analyzer': () => import('./components/ImageAnalyzer.js'),
        'contact-form': () => import('./components/ContactForm.js'),
        'publishing-settings': () => import('./components/PublishingSettings.js'),
        'marketing-options': () => import('./components/MarketingOptions.js'),
    };

    /**
     * Lazy load component
     */
    async loadComponent(componentName, container, options = {}) {
        // Check if already loaded
        if (this.loadedComponents.has(componentName)) {
            return this.loadedComponents.get(componentName);
        }

        // Check if currently loading
        if (this.loadingComponents.has(componentName)) {
            return this.waitForComponent(componentName);
        }

        try {
            this.loadingComponents.add(componentName);

            // Show loading indicator
            this.showLoadingIndicator(container);

            // Load component
            const componentLoader = this.componentRegistry[componentName];
            if (!componentLoader) {
                throw new Error(`Component ${componentName} not found in registry`);
            }

            const componentModule = await componentLoader();
            const component = componentModule.default || componentModule;

            // Initialize component
            const initializedComponent = await this.initializeComponent(
                component,
                container,
                options
            );

            // Cache component
            this.componentCache.set(componentName, initializedComponent);
            this.loadedComponents.set(componentName, initializedComponent);

            // Hide loading indicator
            this.hideLoadingIndicator(container);

            return initializedComponent;
        } catch (error) {
            console.error(`Failed to load component ${componentName}:`, error);
            this.showErrorIndicator(container, error);
            throw error;
        } finally {
            this.loadingComponents.delete(componentName);
        }
    }

    /**
     * Wait for component to finish loading
     */
    async waitForComponent(componentName, timeout = 10000) {
        const startTime = Date.now();

        while (this.loadingComponents.has(componentName)) {
            if (Date.now() - startTime > timeout) {
                throw new Error(`Component ${componentName} loading timeout`);
            }
            await new Promise((resolve) => setTimeout(resolve, 100));
        }

        return this.loadedComponents.get(componentName);
    }

    /**
     * Initialize component with options
     */
    async initializeComponent(component, container, options) {
        if (typeof component === 'function') {
            return component(container, options);
        } else if (component && typeof component.init === 'function') {
            return component.init(container, options);
        } else {
            return component;
        }
    }

    /**
     * Preload components
     */
    async preloadComponents(componentNames) {
        const promises = componentNames.map((name) => this.loadComponent(name, null));
        return Promise.allSettled(promises);
    }

    /**
     * Unload component
     */
    unloadComponent(componentName) {
        const component = this.loadedComponents.get(componentName);
        if (component && typeof component.destroy === 'function') {
            component.destroy();
        }

        this.loadedComponents.delete(componentName);
        this.componentCache.delete(componentName);
    }

    /**
     * Show loading indicator
     */
    showLoadingIndicator(container) {
        if (!container) return;

        const loadingHTML = `
            <div class="lazy-loading-indicator">
                <div class="flex items-center justify-center p-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-3 text-gray-600 dark:text-slate-400">Yükleniyor...</span>
                </div>
            </div>
        `;

        container.innerHTML = loadingHTML;
    }

    /**
     * Hide loading indicator
     */
    hideLoadingIndicator(container) {
        if (!container) return;

        const indicator = container.querySelector('.lazy-loading-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    /**
     * Show error indicator
     */
    showErrorIndicator(container, error) {
        if (!container) return;

        const errorHTML = `
            <div class="lazy-loading-error">
                <div class="flex items-center justify-center p-8 text-red-600">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span>Bileşen yüklenirken hata oluştu: ${error.message}</span>
                </div>
            </div>
        `;

        container.innerHTML = errorHTML;
    }

    /**
     * Get component status
     */
    getComponentStatus(componentName) {
        if (this.loadedComponents.has(componentName)) {
            return 'loaded';
        } else if (this.loadingComponents.has(componentName)) {
            return 'loading';
        } else {
            return 'not-loaded';
        }
    }

    /**
     * Get all loaded components
     */
    getLoadedComponents() {
        return Array.from(this.loadedComponents.keys());
    }

    /**
     * Clear all components
     */
    clearAll() {
        this.loadedComponents.forEach((component, name) => {
            this.unloadComponent(name);
        });
    }
}

// Global instance
window.lazyLoader = new LazyComponentLoader();

// Alpine.js integration
document.addEventListener('alpine:init', () => {
    Alpine.directive('lazy-component', (el, { expression }, { evaluateLater, effect }) => {
        const evaluate = evaluateLater(expression);
        let componentName = '';
        let componentInstance = null;

        effect(() => {
            evaluate((value) => {
                if (typeof value === 'string') {
                    componentName = value;
                } else if (typeof value === 'object') {
                    componentName = value.name;
                }

                if (componentName) {
                    loadComponentIntoElement(el, componentName, value);
                }
            });
        });

        async function loadComponentIntoElement(element, name, options = {}) {
            try {
                componentInstance = await window.lazyLoader.loadComponent(name, element, options);

                // Dispatch loaded event
                element.dispatchEvent(
                    new CustomEvent('component-loaded', {
                        detail: {
                            componentName: name,
                            component: componentInstance,
                        },
                    })
                );
            } catch (error) {
                console.error(`Failed to load component ${name}:`, error);

                // Dispatch error event
                element.dispatchEvent(
                    new CustomEvent('component-error', {
                        detail: { componentName: name, error },
                    })
                );
            }
        }
    });

    // Alpine.js store for lazy loading
    Alpine.store('lazyLoading', {
        loadedComponents: new Set(),
        loadingComponents: new Set(),

        isLoaded(componentName) {
            return this.loadedComponents.has(componentName);
        },

        isLoading(componentName) {
            return this.loadingComponents.has(componentName);
        },

        async loadComponent(componentName, options = {}) {
            if (this.isLoaded(componentName)) {
                return window.lazyLoader.loadedComponents.get(componentName);
            }

            this.loadingComponents.add(componentName);

            try {
                const component = await window.lazyLoader.loadComponent(
                    componentName,
                    null,
                    options
                );
                this.loadedComponents.add(componentName);
                return component;
            } finally {
                this.loadingComponents.delete(componentName);
            }
        },
    });
});

// Component definitions
export const LazyComponents = {
    // Location Map Component
    LocationMap: async (container, options) => {
        const mapHTML = `
            <div class="location-map-container">
                <div id="map" class="w-full h-64 rounded-lg border"></div>
                <div class="mt-4">
                    <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 dark:shadow-none" onclick="initMap()">
                        <i class="fas fa-map-marker-alt mr-2"></i>
                        Haritayı Başlat
                    </button>
                </div>
            </div>
        `;

        if (container) {
            container.innerHTML = mapHTML;
        }

        return {
            initMap: () => {
                // Google Maps initialization
                console.log('Initializing map...');
            },
            destroy: () => {
                console.log('Destroying map...');
            },
        };
    },

    // AI Content Component
    AIContent: async (container, options) => {
        const aiHTML = `
            <div class="ai-content-container">
                <div class="ai-header flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">
                        <i class="fas fa-brain mr-2 text-blue-600"></i>
                        AI İçerik Asistanı
                    </h3>
                    <div class="ai-status">
                        <span class="text-sm text-gray-600 dark:text-slate-400">Hazır</span>
                    </div>
                </div>

                <div class="ai-actions grid grid-cols-2 gap-4">
                    <button type="button" class="ai-action-btn" data-action="generate-description">
                        <i class="fas fa-edit mr-2"></i>
                        Açıklama Üret
                    </button>
                    <button type="button" class="ai-action-btn" data-action="optimize-seo">
                        <i class="fas fa-search mr-2"></i>
                        SEO Optimize Et
                    </button>
                    <button type="button" class="ai-action-btn" data-action="suggest-title">
                        <i class="fas fa-heading mr-2"></i>
                        Başlık Öner
                    </button>
                    <button type="button" class="ai-action-btn" data-action="analyze-content">
                        <i class="fas fa-chart-line mr-2"></i>
                        İçerik Analizi
                    </button>
                </div>

                <div class="ai-results mt-4 hidden">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h4 class="font-semibold text-blue-800 mb-2">AI Önerileri</h4>
                        <div class="ai-result-content"></div>
                    </div>
                </div>
            </div>
        `;

        if (container) {
            container.innerHTML = aiHTML;
        }

        return {
            generateDescription: async () => {
                console.log('Generating description...');
            },
            optimizeSEO: async () => {
                console.log('Optimizing SEO...');
            },
            destroy: () => {
                console.log('Destroying AI content component...');
            },
        };
    },

    // Photo Upload Component
    PhotoUpload: async (container, options) => {
        const uploadHTML = `
            <div class="photo-upload-container">
                <div class="upload-area border-2 border-dashed border-gray-300 rounded-lg p-8 text-center dark:border-slate-600">
                    <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-4 dark:text-slate-600"></i>
                    <p class="text-gray-600 mb-4 dark:text-slate-400">Görselleri buraya sürükleyin veya tıklayın</p>
                    <input type="file" multiple accept="image/*" class="hidden" id="photo-upload">
                    <button type="button" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg shadow-md hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:outline-none transition-all duration-200 dark:shadow-none" onclick="document.getElementById('photo-upload').click()">
                        Görsel Seç
                    </button>
                </div>

                <div class="uploaded-photos mt-4 grid grid-cols-4 gap-4">
                    <!-- Uploaded photos will be displayed here -->
                </div>

                <div class="ai-image-analysis mt-4 hidden">
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <h4 class="font-semibold text-green-800 mb-2">
                            <i class="fas fa-robot mr-2"></i>
                            AI Görsel Analizi
                        </h4>
                        <div class="analysis-results"></div>
                    </div>
                </div>
            </div>
        `;

        if (container) {
            container.innerHTML = uploadHTML;
        }

        return {
            uploadPhotos: (files) => {
                console.log('Uploading photos:', files);
            },
            analyzeImages: async () => {
                console.log('Analyzing images...');
            },
            destroy: () => {
                console.log('Destroying photo upload component...');
            },
        };
    },
};

export default LazyComponentLoader;
