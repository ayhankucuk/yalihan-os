/**
 * Context7 Features System - İzolasyon ve Standartlaştırma
 *
 * Bu sistem, özellik (features) yükleme işlemlerini standartlaştırır ve izole eder.
 *
 * Amaç: API endpoint'leri her dosyada tekrar tekrar yazmayı önlemek
 * Kullanım: Tüm ilan formlarında bu sistemi kullan
 *
 * @version 1.0.0
 * @date 2025-10-24
 * @context7 100%
 */

class Context7FeaturesSystem {
    constructor(config = {}) {
        this.config = {
            baseUrl: (window.APIConfig && window.APIConfig.apiV1Prefix) ? `${window.APIConfig.apiV1Prefix}/admin` : '/api/v1/admin',
            timeout: 10000,
            retryAttempts: 2,
            debug: false,
            ...config,
        };

        this.cache = new Map();
        this.loading = new Set();

        this.log('✅ Context7 Features System initialized');
    }

    /**
     * Kategori için özellikleri yükle
     * @param {number} categoryId - Kategori ID
     * @returns {Promise<Array>} Özellik kategorileri
     */
    async loadFeaturesForCategory(categoryId) {
        if (!categoryId) {
            this.log('⚠️ Kategori ID boş');
            return [];
        }

        // Cache kontrolü
        const cacheKey = `features_${categoryId}`;
        if (this.cache.has(cacheKey)) {
            this.log(`📦 Cache'den yüklendi: ${categoryId}`);
            return this.cache.get(cacheKey);
        }

        // Duplicate request önleme
        if (this.loading.has(categoryId)) {
            this.log(`⏳ Zaten yükleniyor: ${categoryId}`);
            await this.waitForLoad(categoryId);
            return this.cache.get(cacheKey) || [];
        }

        this.loading.add(categoryId);

        try {
            this.log(`🔧 Özellik yükleme başlatıldı: ${categoryId}`);

            const isSlug =
                typeof categoryId === 'string' && categoryId !== '' && !/^\d+$/.test(categoryId);
            const url = isSlug
                ? `${this.config.baseUrl}/features/category/${encodeURIComponent(categoryId)}`
                : `${this.config.baseUrl}/features/category/${categoryId}`;

            const response = await this.fetchWithTimeout(url, {
                credentials: 'same-origin',
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            const features =
                (Array.isArray(data?.data?.features) && data.data.features) ||
                (Array.isArray(data?.features) && data.features) ||
                (Array.isArray(data?.data) && data.data) ||
                data.featureCategories ||
                [];

            // Cache'e kaydet
            this.cache.set(cacheKey, features);

            this.log(`✅ ${features.length} özellik kategorisi yüklendi`);

            return features;
        } catch (error) {
            console.error('❌ Özellik yükleme hatası:', error);
            window.toast?.error('Özellikler yüklenirken hata oluştu');
            return [];
        } finally {
            this.loading.delete(categoryId);
        }
    }

    /**
     * Alt kategorileri yükle
     * @param {number} parentId - Ana kategori ID
     * @returns {Promise<Array>} Alt kategoriler
     */
    async loadSubcategories(parentId) {
        if (!parentId) return [];

        const cacheKey = `subcategories_${parentId}`;
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const url = `${this.config.baseUrl}/categories/${parentId}/subcategories`;
            const response = await this.fetchWithTimeout(url);

            if (!response.ok) {
                throw new Error('Alt kategoriler yüklenemedi');
            }

            const data = await response.json();
            const subcategories = data.data || data.subcategories || [];

            this.cache.set(cacheKey, subcategories);
            this.log(`✅ ${subcategories.length} alt kategori yüklendi`);

            return subcategories;
        } catch (error) {
            console.error('❌ Alt kategori yükleme hatası:', error);
            window.toast?.error('Alt kategoriler yüklenemedi');
            return [];
        }
    }

    /**
     * Yayın tiplerini yükle
     * @param {number} categoryId - Kategori ID
     * @returns {Promise<Array>} Yayın tipleri
     */
    async loadPublicationTypes(categoryId) {
        if (!categoryId) return [];

        const cacheKey = `publication_types_${categoryId}`;
        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const url = `${this.config.baseUrl}/categories/publication-types/${categoryId}`;
            const response = await this.fetchWithTimeout(url);

            if (!response.ok) {
                throw new Error('Yayın tipleri yüklenemedi');
            }

            const data = await response.json();
            const types = data.data || data.publicationTypes || [];

            this.cache.set(cacheKey, types);
            this.log(`✅ ${types.length} yayın tipi yüklendi`);

            return types;
        } catch (error) {
            console.error('❌ Yayın tipi yükleme hatası:', error);
            window.toast?.error('Yayın tipleri yüklenemedi');
            return [];
        }
    }

    /**
     * Timeout ile fetch
     * @private
     */
    async fetchWithTimeout(url, options = {}) {
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), this.config.timeout);

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    ...options.headers,
                },
            });
            return response;
        } finally {
            clearTimeout(timeout);
        }
    }

    /**
     * Yükleme tamamlanmasını bekle
     * @private
     */
    async waitForLoad(categoryId) {
        return new Promise((resolve) => {
            const checkInterval = setInterval(() => {
                if (!this.loading.has(categoryId)) {
                    clearInterval(checkInterval);
                    resolve();
                }
            }, 100);
        });
    }

    /**
     * Cache temizle
     */
    clearCache() {
        this.cache.clear();
        this.log('🗑️ Cache temizlendi');
    }

    /**
     * Debug log
     * @private
     */
    log(message) {
        if (this.config.debug || window.context7Debug) {
            console.log(`[Context7 Features] ${message}`);
        }
    }

    /**
     * Birleşik özellik yükleyici (applies_to + kategori slug + yayın tipi)
     * field-dependencies-dynamic tarafından kullanılır
     */
    async loadFeatures(appliesTo, categorySlug = null, yayinTipiId = null) {
        const key = `unified_${appliesTo || ''}_${categorySlug || ''}_${yayinTipiId || ''}`;
        if (this.cache.has(key)) return this.cache.get(key);

        const base = (window.APIConfig && window.APIConfig.features && window.APIConfig.features.categories)
            ? window.APIConfig.features.categories
            : '/api/v1/admin/features/categories';
        const url = new URL(base, window.location.origin);
        if (appliesTo) url.searchParams.set('applies_to', appliesTo);
        if (categorySlug) url.searchParams.set('category', categorySlug);
        if (yayinTipiId) url.searchParams.set('yayin_tipi', yayinTipiId);

        const response = await this.fetchWithTimeout(url.toString(), { credentials: 'same-origin' });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const data = await response.json();
        const list = (Array.isArray(data?.data) && data.data) || (Array.isArray(data?.data?.data) && data.data.data) || [];
        this.cache.set(key, list);
        return list;
    }
}

// Global instance
window.Context7FeaturesSystem = Context7FeaturesSystem;

// Auto-initialize
if (!window.featuresSystem) {
    window.featuresSystem = new Context7FeaturesSystem({
        debug: true, // Development mode
    });
    console.log('✅ Context7 Features System ready');
}

// Alpine.js helper (backward compatibility)
window.loadFeaturesForCategory = function (categoryId) {
    return window.featuresSystem.loadFeaturesForCategory(categoryId);
};

window.loadPublicationTypes = function (categoryId) {
    return window.featuresSystem.loadPublicationTypes(categoryId);
};

window.loadSubcategories = function (parentId) {
    return window.featuresSystem.loadSubcategories(parentId);
};

// Yardımcı: tüm cache'i temizle
window.featuresInvalidateAll = function () {
    if (window.featuresSystem && typeof window.featuresSystem.clearCache === 'function') {
        window.featuresSystem.clearCache();
    }
};
