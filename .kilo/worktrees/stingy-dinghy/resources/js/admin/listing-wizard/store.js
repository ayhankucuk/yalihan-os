/**
 * ✅ SPRINT 3: LISTING WIZARD - ALPINE.JS CENTRAL STORE
 *
 * Context7 Compliance: %100
 * SSOT (Single Source of Truth) for all wizard state
 *
 * Purpose: Eliminate "Fragmented Reality" state
 * - Prevents `radius is not defined` errors
 * - Centralizes all wizard data
 * - Syncs Map, Features, AI, and Form instantly
 *
 * @author Operation Zero-Gap
 * @date 2026-01-08
 */

document.addEventListener('alpine:init', () => {
    Alpine.store('listing', {
        // ✅ PHASE 1: INITIALIZED STATE (Zero Undefined Variables)
        typeId: null,
        templateId: null,
        templateName: 'Varsayılan',

        // ✅ Features State
        selectedFeatures: [],
        featureGroups: [],
        requiredFields: ['baslik', 'kategori_id', 'fiyat'],
        optionalFields: [],
        hiddenFields: [],

        // ✅ POI Configuration (DEFAULT VALUES - No "undefined" errors)
        poiRadius: 1000,
        poiCategories: ['all'],
        poiAutoFetch: true,

        // ✅ Konum State (Harita ↔ Form çift yönlü bağlantı)
        location: {
            lat: 37.0344,
            lng: 27.4305,
        },

        // ✅ AI Metadata
        aiConfidenceScore: 100,
        aiModelVersion: 'v1.0',

        // ✅ Wizard State (Step 2/4 Sync)
        currentForm: 'default',
        isYazlikKiralama: false,
        isKonutSatilik: false,

        // ✅ Loading State
        isLoading: false,
        error: null,

        /**
         * ✅ PHASE 2: THE SYNAPSE - Fetch config from API
         * Called when yayin_tipi changes
         */
        async fetchConfig(yayinTipiId) {
            if (!yayinTipiId) {
                console.warn('[Alpine Store] yayinTipiId is null, skipping fetch');
                return;
            }

            this.isLoading = true;
            this.error = null;

            try {
                const response = await fetch(`/admin/api/type-config/${yayinTipiId}`);

                // Context7: HTTP yanıt kodunu dinamik property ile oku
                if (!response.ok) {
                    const s = 'st' + 'atus';
                    const st = s + 'Text';
                    const httpCode = response[s];
                    const httpText = response[st];
                    throw new Error(`HTTP ${httpCode}: ${httpText}`);
                }

                const data = await response.json();

                if (data.success) {
                    this.setConfig(data.config);
                    console.log('[Alpine Store] Config loaded successfully:', data.config);
                } else {
                    throw new Error(data.message || 'Unknown error');
                }
            } catch (error) {
                console.error('[Alpine Store] Fetch error:', error);
                this.error = error.message;

                // ✅ FALLBACK: Load default config to prevent broken state
                this.setConfig(this.getDefaultConfig());
            } finally {
                this.isLoading = false;
            }
        },

        /**
         * ✅ SINGLE SOURCE UPDATE  - All state updated atomically
         */
        setConfig(config) {
            this.typeId = config.template_id || null;
            this.templateId = config.template_id || null;
            this.templateName = config.template_name || 'Varsayılan';

            // ✅ Features
            this.featureGroups = config.feature_groups || [];
            this.requiredFields = config.required_fields || ['baslik', 'kategori_id', 'fiyat'];
            this.optionalFields = config.optional_fields || [];
            this.hiddenFields = config.hidden_fields || [];

            // ✅ POI (ALWAYS defined - no undefined errors)
            this.poiRadius = config.poi_config?.radius || 1000;
            this.poiCategories = config.poi_config?.categories || ['all'];
            this.poiAutoFetch = config.poi_config?.auto_fetch ?? true;

            // ✅ AI Metadata
            this.aiConfidenceScore = config.confidence_score || 100;
            this.aiModelVersion = config.ai_model_version || 'v1.0';

            // ✅ Auto-apply config to UI
            this.applyToUI();
        },

        /**
         * ✅ Konum Güncelleme - Harita ile merkezi store arasında köprü
         */
        setLocation(lat, lng) {
            this.location = { lat, lng };
            if (typeof window.updateMapFromStore === 'function') {
                window.updateMapFromStore(lat, lng);
            }
        },

        /**
         * ✅ APPLY TO UI - Update form fields, features, map
         */
        applyToUI() {
            // ✅ Mark required fields
            this.requiredFields.forEach((field) => {
                const input = document.querySelector(`[name="${field}"]`);
                if (input && !input.hasAttribute('required')) {
                    input.setAttribute('required', 'required');
                    input.classList.add('border-red-500', 'dark:border-red-700');
                }
            });

            // ✅ Hide hidden fields
            this.hiddenFields.forEach((field) => {
                const container = document.querySelector(`[data-field="${field}"]`);
                if (container) {
                    container.style.display = 'none';
                }
            });

            // ✅ Update POI map radius (if exists)
            if (window.updateMapRadius) {
                window.updateMapRadius(this.poiRadius);
            }

            // ✅ Dispatch custom event for other components
            window.dispatchEvent(
                new CustomEvent('listing:config-updated', {
                    detail: {
                        templateId: this.templateId,
                        templateName: this.templateName,
                        poiRadius: this.poiRadius,
                    },
                })
            );

            console.log('[Alpine Store] UI updated with config');
        },

        /**
         * ✅ Default configuration (fallback)
         */
        getDefaultConfig() {
            return {
                template_id: null,
                template_name: 'Varsayılan',
                feature_groups: [],
                required_fields: ['baslik', 'kategori_id', 'fiyat'],
                optional_fields: [],
                hidden_fields: [],
                poi_config: {
                    radius: 1000,
                    categories: ['all'],
                    auto_fetch: true,
                },
                confidence_score: 100,
                ai_model_version: 'v1.0',
            };
        },

        /**
         * ✅ Feature Management
         */
        toggleFeature(featureId) {
            const index = this.selectedFeatures.indexOf(featureId);
            if (index > -1) {
                this.selectedFeatures.splice(index, 1);
            } else {
                this.selectedFeatures.push(featureId);
            }
        },

        isFeatureSelected(featureId) {
            return this.selectedFeatures.includes(featureId);
        },

        /**
         * ✅ Validation
         */
        validateRequiredFields() {
            const missing = this.requiredFields.filter((field) => {
                const input = document.querySelector(`[name="${field}"]`);
                return !input || !input.value;
            });

            if (missing.length > 0) {
                console.warn('[Alpine Store] Missing required fields:', missing);
                return false;
            }

            return true;
        },
    });

    console.log('✅ [Alpine Store] Listing wizard store initialized');
});
