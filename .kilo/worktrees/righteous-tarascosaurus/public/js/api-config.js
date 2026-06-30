/**
 * API Endpoint Configuration
 *
 * Context7 Standard: C7-API-CONFIG-JS-2025-12-03
 *
 * Merkezi API endpoint yönetimi için JavaScript config dosyası.
 * Tüm endpoint'ler buradan alınır, hardcoded endpoint'ler yasaktır.
 *
 * @version 1.0.0
 * @since 2025-12-03
 */

/* global window */

// Prevent multiple declarations
if (typeof window.APIConfig === 'undefined') {
    window.APIConfig = {
        /**
         * Base URLs
         */
        baseUrl: window.location.origin,
        apiPrefix: '/api',
        apiV1Prefix: '/api/v1',

        /**
         * Location API Endpoints
         */
        location: {
            provinces: '/api/v1/location/provinces',
            districts: (id) => `/api/v1/location/districts/${id}`,
            neighborhoods: (id) => `/api/v1/location/neighborhoods/${id}`,
            geocode: '/api/v1/location/geocode',
            reverseGeocode: '/api/v1/location/reverse-geocode',
            nearby: (lat, lng, radius = 1000) => `/api/v1/location/nearby/${lat}/${lng}/${radius}`,
            neighborhoodCoordinates: (id) => `/api/v1/location/neighborhood/${id}/coordinates`,
        },

        /**
         * Geo Proxy API Endpoints (CORS-free Nominatim/Overpass)
         * Context7: Hardcoded Nominatim yasak - Backend proxy kullan
         * Yalıhan Bekçi: Rate limit, cache ve error handling
         */
        geo: {
            geocode: '/api/v1/geo/geocode',
            reverseGeocode: '/api/v1/geo/reverse-geocode',
            nearby: '/api/v1/geo/nearby',
        },

        /**
         * Categories API Endpoints
         */
        categories: {
            subcategories: (parentId) => `/api/v1/categories/sub/${parentId}`,
            publicationTypes: (categoryId) => `/api/v1/categories/publication-types/${categoryId}`,
            fields: (categoryId, publicationTypeId = null) => {
                if (publicationTypeId) {
                    return `/api/v1/categories/fields/${categoryId}/${publicationTypeId}`;
                }
                return `/api/v1/categories/fields/${categoryId}`;
            },
            detail: (id) => `/api/v1/categories/${id}`,
        },

        /**
         * Live Search API Endpoints
         */
        liveSearch: {
            kisiler: '/api/v1/kisiler/search',
            danismanlar: '/api/v1/users/search',
            sites: '/api/v1/sites/search',
            unified: '/api/v1/search/unified',
        },

        /**
         * TKGM API Endpoints
         */
        tkgm: {
            parselSorgu: '/api/v1/tkgm/parsel-sorgu',
            yatirimAnalizi: '/api/v1/tkgm/yatirim-analizi',
            health: '/api/v1/tkgm/health',
        },

        /**
         * Properties API Endpoints
         */
        properties: {
            tkgmLookup: '/api/v1/properties/tkgm-lookup',
            calculate: '/api/v1/properties/calculate',
        },

        /**
         * Environment API Endpoints
         */
        environment: {
            analyze: '/api/v1/environment/analyze',
            category: (category) => `/api/v1/environment/category/${category}`,
            valuePrediction: '/api/v1/environment/value-prediction',
            pois: (lat, lng, radius = 2000, types = null) => {
                let url = `/api/v1/environment/pois?lat=${lat}&lng=${lng}&radius=${radius}`;
                if (types) {
                    url += `&types=${Array.isArray(types) ? types.join(',') : types}`;
                }
                return url;
            },
        },

        /**
         * AI API Endpoints
         */
        ai: {
            analyze: '/api/v1/ai/analyze',
            suggest: '/api/v1/ai/suggest',
            generate: '/api/v1/ai/generate',
            health: '/api/v1/ai/health',
            durum: '/api/v1/ai/durum',
            startVideoRender: (ilanId) => `/api/v1/ai/start-video-render/${ilanId}`,
            videoDurumu: (ilanId) => `/api/v1/ai/video-durumu/${ilanId}`,
        },

        /**
         * Admin API Endpoints
         */
        admin: {
            // ✅ Phase B/G: Standard AI endpoints
            aiTitle: '/admin/ilanlar/ai/title',
            aiDescription: '/admin/ilanlar/ai/description',
            aiQualityCheck: '/admin/ilanlar/ai/quality-check', // Phase C

            // Legacy endpoints (deprecated, use ai* instead)
            generateAiTitle: '/admin/ilanlar/generate-ai-title',
            generateAiDescription: '/admin/ilanlar/generate-ai-description',
            generateAiCopy: (ilanId) => `/admin/ilanlar/${ilanId}/ai-copy`,

            convertPriceToText: '/admin/ilanlar/convert-price-to-text',
            liveSearch: '/admin/ilanlar/live-search',
            analytics: (range = '30d') => `/api/admin/analytics?range=${range}`,
            aiSmartDefaults: (query) => `/api/admin/ai/smart-defaults${query ? `?${query}` : ''}`,
            aiSuggestFeature: '/api/admin/ai/suggest-feature',
            aiTools: {
                tkgmFetch: '/api/ai/fetch-tkgm',
                calculateM2Price: '/api/ai/calculate-m2-price',
            },
            aiSettings: {
                testProvider: '/admin/ai-settings/test-provider',
                saglayiciDurumu: '/admin/ai-settings/saglayici-durumu',
            },
            aiAssist: {
                priceSuggest: '/admin/ai-assist/price-suggest',
                seoOptimize: '/api/admin/ai-assist/seo-optimize',
                autoCategorize: '/api/admin/ai-assist/auto-categorize',
                descriptionGenerate: '/api/admin/ai-assist/description-generate',
                imageAnalyze: '/api/admin/ai-assist/image-analyze',
            },
            aiAnalyzeProperty: '/api/admin/ai/analyze-property',
            searchUnified: '/api/admin/search/unified',
            categories: '/api/admin/categories',
            locations: '/api/admin/locations',
            events: {
                list: (ilanId) => `/api/admin/ilanlar/${ilanId}/events`,
                create: '/api/admin/events',
                update: (id) => `/api/admin/events/${id}`,
                delete: (id) => `/api/admin/events/${id}`,
                definitions: '/api/admin/events/definitions',
            },
            photos: {
                upload: '/api/admin/photos/upload',
                delete: (photoId) => `/api/photos/${photoId}`,
                bulkDelete: '/api/photos/bulk-delete',
                listByListing: (listingId) => `/api/listings/${listingId}/photos`,
            },
            sites: {
                create: '/api/admin/sites/create',
                search: '/api/admin/sites/search',
            },
            /**
             * Calculator API Endpoints
             * Context7: Smart Calculator Service
             */
            calculator: {
                types: '/api/v1/admin/calculator/types',
                calculate: '/api/v1/admin/calculator/calculate',
                history: '/api/v1/admin/calculator/history',
                favorites: '/api/v1/admin/calculator/favorites',
                taxRates: '/api/v1/admin/calculator/tax-rates',
                commissionRates: '/api/v1/admin/calculator/commission-rates',
            },
            /**
             * Consultants API Endpoints
             */
            consultants: {
                dashboard: '/api/v1/admin/consultants/dashboard',
                quickAdd: '/api/v1/admin/consultants/quick-add',
            },
            /**
             * Person API Endpoints
             */
            person: {
                detail: (id) => `/api/kisiler/${id}`,
                quickAdd: '/api/v1/admin/person/quick-add',
                search: '/api/v1/admin/person/search',
            },
            /**
             * Owner Portal API Endpoints
             */
            owner: {
                dashboard: '/api/v1/admin/owner/dashboard',
            },
            /**
             * Environment Analysis API Endpoints
             */
            environment: {
                analyze: '/api/v1/admin/environment/analyze',
            },
            /**
             * Config Options API Endpoints
             * Context7: Database-driven config options management
             */
            configOptions: {
                /**
                 * Get single config option
                 * @param {string} optionKey - Option key (e.g., 'oda_sayisi_options')
                 * @param {number|null} [kategoriId] - Optional category ID
                 * @param {number|null} [yayinTipiId] - Optional publication type ID
                 * @returns {string} API endpoint URL with query params
                 */
                get: (optionKey, kategoriId = null, yayinTipiId = null) => {
                    let url = `/api/v1/admin/config-options/get?option_key=${encodeURIComponent(optionKey)}`;
                    if (kategoriId) url += `&kategori_id=${kategoriId}`;
                    if (yayinTipiId) url += `&yayin_tipi_id=${yayinTipiId}`;
                    return url;
                },
                /**
                 * Get multiple config options
                 * @param {string[]} optionKeys - Array of option keys
                 * @param {number|null} [kategoriId] - Optional category ID
                 * @param {number|null} [yayinTipiId] - Optional publication type ID
                 * @returns {string} API endpoint URL
                 */
                getMultiple: '/api/v1/admin/config-options/get-multiple',
                /**
                 * Update config option
                 * @param {number} id - Config option ID
                 * @returns {string} API endpoint URL
                 */
                update: (id) => `/admin/config-options/${id}`,
                /**
                 * Store config option
                 * @returns {string} API endpoint URL
                 */
                store: '/admin/config-options',
                /**
                 * Duplicate config option
                 * @param {number} id - Config option ID
                 * @returns {string} API endpoint URL
                 */
                duplicate: (id) => `/admin/config-options/${id}/duplicate`,
                /**
                 * Delete config option
                 * @param {number} id - Config option ID
                 * @returns {string} API endpoint URL
                 */
                destroy: (id) => `/admin/config-options/${id}`,
            },
            /**
             * Property Type Manager API Endpoints
             * Context7: Property type and field dependencies management
             */
            propertyTypeManager: {
                toggleYayinTipi: (kategoriId) =>
                    `/admin/property-type-manager/${kategoriId}/toggle-yayin-tipi`,
                bulkSave: (kategoriId) => `/admin/property-type-manager/${kategoriId}/bulk-save`,
                destroyYayinTipi: (kategoriId, yayinTipiId) =>
                    `/admin/property-type-manager/${kategoriId}/yayin-tipi/${yayinTipiId}`,
                destroyAltKategori: (kategoriId, altKategoriId) =>
                    `/admin/property-type-manager/${kategoriId}/alt-kategori/${altKategoriId}`,
                fieldDependencies: (kategoriId) =>
                    `/admin/property-type-manager/${kategoriId}/field-dependencies`,
                storeFieldDependency: (kategoriId) =>
                    `/admin/property-type-manager/${kategoriId}/field-dependencies`,
                updateFieldDependency: (kategoriId, fieldId) =>
                    `/admin/property-type-manager/${kategoriId}/field-dependencies/${fieldId}`,
                destroyFieldDependency: (kategoriId, fieldId) =>
                    `/admin/property-type-manager/${kategoriId}/field-dependencies/${fieldId}`,
                toggleFieldDependency: '/admin/property-type-manager/toggle-field-dependency',
                updateFieldSequence: '/admin/property-type-manager/update-field-sequence',
                toggleFeature: '/admin/property-type-manager/toggle-feature',
                createYayinTipi: (kategoriId) =>
                    `/admin/property-type-manager/${kategoriId}/yayin-tipi`,
                ensureAllYayinTipleri: '/admin/property-type-manager/ensure-all-yayin-tipleri',
                assignFeature: (propertyTypeId) =>
                    `/admin/property-type-manager/property-type/${propertyTypeId}/assign-feature`,
                unassignFeature: (propertyTypeId) =>
                    `/admin/property-type-manager/property-type/${propertyTypeId}/unassign-feature`,
                syncFeatures: (propertyTypeId) =>
                    `/admin/property-type-manager/property-type/${propertyTypeId}/sync-features`,
                toggleFeatureAssignment: '/admin/property-type-manager/toggle-feature-assignment',
                updateFeatureAssignment: (assignmentId) =>
                    `/admin/property-type-manager/feature-assignment/${assignmentId}`,
            },
            /**
             * Features Management API Endpoints
             * Context7: Feature display sequencing and status management
             */
            featuresManagement: {
                base: '/admin/features-management',
                featuresDisplaySequence: '/admin/property-type-manager/update-field-sequence',
                assignmentsDisplaySequence: '/admin/property-type-manager/update-field-sequence',
                toggleFeatureDurum: (featureId) =>
                    `/admin/features-management/features/${featureId}/toggle-durum`,
                bulkToggle: '/admin/features-management/features/bulk-toggle',
                passive: '/admin/features-management/passive',
                configOptions: '/admin/features-management/config-options',
                updateConfigOptions: '/admin/features-management/config-options',
            },
            portals: {
                durum: (ilanId) => `/api/ilanlar/${ilanId}/portals/durum`,
                sync: (ilanId, portal) => `/api/ilanlar/${ilanId}/portals/sync/${portal}`,
                syncAll: (ilanId) => `/api/ilanlar/${ilanId}/portals/sync-all`,
                remove: (ilanId, portal) => `/api/ilanlar/${ilanId}/portals/${portal}`,
                pricing: (ilanId) => `/api/ilanlar/${ilanId}/portals/pricing`,
                schedule: (ilanId) => `/api/ilanlar/${ilanId}/portals/schedule`,
                masterDurum: (ilanId) => `/api/ilanlar/${ilanId}/portals/master-durum`,
                list: '/api/portals',
            },
            ilanlar: {
                kanban: '/api/admin/ilanlar/kanban',
                search: '/api/ilanlar/search',
                draft: {
                    get: '/api/admin/ilanlar/draft',
                    create: '/api/admin/ilanlar/draft',
                },
                create: '/admin/ilanlar',
                publication: {
                    expiryDurum: (ilanId) => `/api/ilanlar/${ilanId}/publication/expiry-durum`,
                    renew: (ilanId) => `/api/ilanlar/${ilanId}/publication/renew`,
                    autoRenewal: (ilanId) => `/api/ilanlar/${ilanId}/publication/auto-renewal`,
                    scheduleReminders: (ilanId) =>
                        `/api/ilanlar/${ilanId}/publication/schedule-reminders`,
                },
            },
        },

        yazlikKiralama: {
            takvim: (ilanId) => `/api/v1/yazlik-kiralama/takvim/${ilanId}`,
            fiyatHesapla: '/api/v1/yazlik-kiralama/fiyat-hesapla',
            musaitlikKontrol: '/api/v1/yazlik-kiralama/musaitlik-kontrol',
            rezervasyon: '/api/v1/yazlik-kiralama/rezervasyon',
            fiyatlandirmaList: (ilanId) => `/api/v1/yazlik-kiralama/fiyatlandirma/${ilanId}`,
            fiyatlandirmaCreate: '/api/v1/yazlik-kiralama/fiyatlandirma',
            fiyatlandirmaUpdate: (id) => `/api/v1/yazlik-kiralama/fiyatlandirma/${id}`,
            fiyatlandirmaDelete: (id) => `/api/v1/yazlik-kiralama/fiyatlandirma/${id}`,
        },

        /**
         * Features API Endpoints
         * Context7: Category-based features loading
         */
        features: {
            /**
             * Get features by category slug (respects feature flag)
             * ✅ CRITICAL: Switches between FeatureAssignment resolver and legacy based on flag
             * @param {string} categorySlug - Category slug (arsa, konut, yazlik, etc.)
             * @param {string|null} [yayinTipiSlug] - Optional publication type slug (gunluk|haftalik|aylik|sezonluk)
             * @returns {string} API endpoint URL
             */
            byCategory: (categorySlug, yayinTipiSlug = null) => {
                // ✅ CRITICAL: Check feature flag for resolver selection
                const useAssignmentResolver = window.YALI_FEATURES_USE_ASSIGNMENT_RESOLVER ?? false;

                if (useAssignmentResolver) {
                    // NEW: FeatureAssignment-based resolver
                    let url = `/api/v1/admin/category/${encodeURIComponent(categorySlug)}/frontend-features`;
                    if (yayinTipiSlug) {
                        url += `?yayin_tipi=${encodeURIComponent(yayinTipiSlug)}`;
                    }
                    return url;
                } else {
                    // LEGACY: Old resolver for rollback safety
                    let url = `/api/v1/admin/features/category/${encodeURIComponent(categorySlug)}`;
                    if (yayinTipiSlug) {
                        url += `?yayin_tipi=${encodeURIComponent(yayinTipiSlug)}`;
                    }
                    return url;
                }
            },
            /**
             * Legacy: Get features by category slug using /features/category endpoint
             * Kept for backward compatibility and potential rollback
             * @param {string} categorySlug
             * @param {string|number|null} [yayinTipi]
             * @returns {string}
             */
            byCategoryLegacy: (categorySlug, yayinTipi = null) => {
                let url = `/api/v1/admin/features/category/${encodeURIComponent(categorySlug)}`;
                if (yayinTipi) {
                    url += `?yayin_tipi=${encodeURIComponent(yayinTipi)}`;
                }
                return url;
            },
            /**
             * Get features by category ID (legacy support)
             * @param {number} categoryId - Category ID
             * @returns {string} API endpoint URL
             */
            byCategoryId: (categoryId) => `/api/v1/admin/features/category/${categoryId}`,
            /**
             * Get all feature categories
             * @returns {string} API endpoint URL
             */
            categories: '/api/v1/admin/features/categories',
            validationHints: '/api/v1/features/validation-hints',
        },

        /**
         * Field Dependencies API Endpoints
         * Context7: Dynamic fields based on category and publication type
         */
        fieldDependencies: {
            /**
             * Get field dependencies for a category and publication type
             * @param {string} kategoriSlug - Category slug
             * @param {string|number} [yayinTipi] - Optional publication type ID or slug
             * @returns {string} API endpoint URL with query params
             */
            index: (kategoriSlug, yayinTipi = null) => {
                let url = `/api/v1/admin/field-dependencies?kategori_slug=${encodeURIComponent(kategoriSlug)}`;
                if (yayinTipi) {
                    url += `&yayin_tipi=${encodeURIComponent(yayinTipi)}`;
                }
                return url;
            },
            /**
             * Get field dependencies by category ID
             * @param {number} kategoriId - Category ID
             * @returns {string} API endpoint URL
             */
            byCategory: (kategoriId) => `/api/v1/admin/field-dependencies/category/${kategoriId}`,
        },

        static: {
            wizardRequiredMatrix: () => '/js/wizard-required-matrix.json',
        },

        currency: {
            rates: '/api/currency/rates',
            convert: '/api/currency/convert',
        },

        smartIlan: {
            searchPersons: '/api/smart-ilan/search/persons',
            ai: {
                basicAnalysis: '/api/smart-ilan/ai/basic-analysis',
                featureSuggestions: '/api/smart-ilan/ai/feature-suggestions',
                priceOptimization: '/api/smart-ilan/ai/price-optimization',
                generateDescription: '/api/smart-ilan/ai/generate-description',
                analyzeImages: '/api/smart-ilan/ai/analyze-images',
            },
        },
        analytics: {
            /**
             * Benchmark endpoint builder
             * @param {string} metric - e.g. 'price_m2' | 'amortization' | 'occupancy'
             * @param {Object} params - { ilce_id?, il_id?, kategori_slug?, yayin_tipi_slug? }
             * @returns {string}
             */
            benchmark(metric, params = {}) {
                const qs = [];
                if (metric) qs.push(`metric=${encodeURIComponent(metric)}`);
                if (params.ilce_id) qs.push(`ilce_id=${encodeURIComponent(params.ilce_id)}`);
                if (params.il_id) qs.push(`il_id=${encodeURIComponent(params.il_id)}`);
                if (params.kategori_slug)
                    qs.push(`kategori_slug=${encodeURIComponent(params.kategori_slug)}`);
                if (params.yayin_tipi_slug)
                    qs.push(`yayin_tipi_slug=${encodeURIComponent(params.yayin_tipi_slug)}`);
                const suffix = qs.length ? `?${qs.join('&')}` : '';
                return `/api/v1/analytics/benchmark${suffix}`;
            },
            performance: '/api/analytics/performance',
        },

        /**
         * Yalihan Cortex API Endpoints
         */
        cortex: {
            analyze: (id) => `/api/admin/cortex/analyze/${id}`,
            video: (id) => `/api/admin/cortex/video/${id}`,
            photos: (id) => `/api/admin/cortex/photos/${id}`,
        },

        pwa: {
            pushSubscription: '/api/push-subscription',
        },

        /**
         * Market Analysis API (TKGM Learning Engine)
         */
        marketAnalysis: {
            predictPrice: '/api/v1/market-analysis/predict-price',
            analysis: (ilId, ilceId = null) => {
                const suffix = ilceId ? `/${ilceId}` : '';
                return `/api/v1/market-analysis/${ilId}${suffix}`;
            },
            hotspots: (ilId) => `/api/v1/market-analysis/hotspots/${ilId}`,
            stats: '/api/v1/market-analysis/stats',
        },

        /**
         * Wizard API (Phase 6: UI/UX Smart Features)
         * Context7: Merkezi API endpoint yönetimi
         * Performance: 3ms eager loading (Phase 5)
         * AI: SmartFieldGenerationService entegrasyonu
         */
        wizard: {
            /**
             * Get features grouped by ui_group for wizard
             * @param {number} categoryId - Category ID
             * @param {number} yayinTipiId - Publication Type ID
             * @returns {string} API endpoint URL with query params
             */
            features: (categoryId, yayinTipiId) => {
                return `/api/v1/wizard/features?category_id=${categoryId}&yayin_tipi_id=${yayinTipiId}`;
            },
            /**
             * AI-powered feature suggestions based on description text
             * @returns {string} API endpoint URL (POST request)
             */
            suggest: '/api/v1/wizard/suggest',
        },

        valuation: {
            base: '/api/valuation',
        },

        /**
         * Helper: Replace parameters in endpoint
         */
        replaceParams: function (endpoint, params = {}) {
            let url = endpoint;
            for (const [key, value] of Object.entries(params)) {
                url = url.replace(`{${key}}`, value);
                url = url.replace(`{${key}?}`, value);
            }
            // Remove optional parameters that weren't replaced
            url = url.replace(/\{[^}]+\?\}/g, '');
            return url;
        },

        /**
         * Helper: Get full URL
         */
        getUrl: function (endpoint, params = {}) {
            const url =
                typeof endpoint === 'function'
                    ? endpoint(...Object.values(params))
                    : this.replaceParams(endpoint, params);
            return `${this.baseUrl}${url}`;
        },
    };
}
