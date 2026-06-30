/**
 * İlan Wizard Sayfası - Merkezi JavaScript (Legacy)
 *
 * @deprecated Bu dosya geriye uyumluluk için korunuyor.
 * @see resources/js/wizard/index.js - Yeni modüler sistem (v3.0.0)
 *
 * MODÜLER MİMARİ (2026-01-28):
 * ┌─────────────────────────────────────────────────────────────────┐
 * │  resources/js/wizard/                                           │
 * │  ├── index.js              → Entry point (lazy loading)        │
 * │  ├── core/                                                      │
 * │  │   ├── wizard-events.js  → Event Bus (pub/sub)               │
 * │  │   ├── wizard-state.js   → Reactive State Manager            │
 * │  │   └── wizard-validation.js → Form Validation                │
 * │  ├── steps/                                                     │
 * │  │   ├── step3-upload.js   → Photo Upload                      │
 * │  │   ├── step4-location.js → Location & Map                    │
 * │  │   └── step5-price.js    → Price & Investment                │
 * │  └── features/                                                  │
 * │      ├── ai-title-generator.js → AI Title                      │
 * │      ├── ai-quality-check.js   → Quality Gate                  │
 * │      └── poi-widget.js         → POI Display                   │
 * └─────────────────────────────────────────────────────────────────┘
 *
 * Context7: Final Neural Handshake - Global Scope Mühürlemesi
 * ✅ REFACTOR: Tüm wizard fonksiyonları YalihanWizard namespace altında
 */

// ✅ Observability Layer - Telemetry Imports (Phase: Production Monitoring)
import { tStart, tEnd } from '../wizard/core/telemetry.js';

// ✅ REFACTOR: Merkezi Namespace - Global scope kirliliğini önler
// Bu legacy namespace, modüler sistem tarafından override edilir
window.YalihanWizard = window.YalihanWizard || {
    version: '2.0.0', // v3.0.0 modüler sistemde
    initialized: false,
    components: {},
    utils: {},
    events: {},
};

// ✅ Alpine.js Global Scope Mühürlemesi (ReferenceError önleme)
// Component fonksiyonları window nesnesine mühürleniyor
// ⚠️ REFACTOR NOTE: aiTitleGenerator tam implementasyonu create-wizard.blade.php'de
// Bu sadece fallback - blade yüklendiğinde override edilir
if (typeof window.aiTitleGenerator === 'undefined') {
    window.aiTitleGenerator = function () {
        return {
            loading: false,
            aiTitles: [],
            showAiTitles: false,
            selectedTitle: '',
            seoScore: 0,
            pastTitles: [],
            showSuggestions: false,
            suggestions: [],
            init() {
                // Değişken başlatma
                this.selectedTitle = document.getElementById('baslik')?.value || '';
                this.updateSEOScore();
            },
            // Placeholder methods - gerçek implementasyon create-wizard.blade.php @push('scripts') içinde
            get canGenerate() {
                return false;
            },
            updateSEOScore() {},
            generateTitles() {
                console.warn('aiTitleGenerator.generateTitles() henüz yüklenmedi');
            },
            selectTitle() {
                console.warn('aiTitleGenerator.selectTitle() henüz yüklenmedi');
            },
            saveTitleHistory() {},
            logTelemetry() {},
        };
    };
}

if (typeof window.intelligenceHub === 'undefined') {
    window.intelligenceHub = function () {
        return {
            loading: false,
            healthData: null,
            init() {
                // Değişken başlatma
                this.healthData = null;
            },
            // Placeholder methods - gerçek implementasyon step-3-additional.blade.php'de
            analyzeHealth() {},
            getScoreLabel() {},
            getScoreMessage() {},
        };
    };
}

if (typeof window.poiWidgetStep2 === 'undefined') {
    window.poiWidgetStep2 = function () {
        return {
            pois: [],
            selectedCategories: [],
            radius: 2000,
            loading: false,
            error: null,
            availableCategories: [],
            init() {
                // Değişken başlatma
                this.pois = [];
                this.selectedCategories = [];
                this.radius = 2000;
                this.loading = false;
                this.error = null;
                this.availableCategories = [];
            },
            get filteredPOIs() {
                if (this.selectedCategories.length === 0) {
                    return this.pois;
                }
                return this.pois.filter((poi) => this.selectedCategories.includes(poi.type));
            },
            loadPOIs() {},
            toggleCategory() {},
            getCategoryCount() {
                return 0;
            },
        };
    };
}

if (typeof window.poiSelector === 'undefined') {
    window.poiSelector = function () {
        return {
            pois: [],
            selectedPois: [],
            loading: false,
            init() {
                this.pois = [];
                this.selectedPois = [];
                this.loading = false;
            },
            loadPOIs() {},
            getPoisByCategory() {
                return [];
            },
            formatDistance() {
                return '';
            },
            getMarketingBadge() {
                return '';
            },
            getPoiJsonData() {
                return [];
            },
            getPoiMetadata() {
                return {};
            },
        };
    };
}

(function () {
    'use strict';

    if (typeof window.ilanWizard !== 'undefined') {
        return; // Zaten yüklenmiş
    }

    /**
     * 🛡️ wizardFetch — SSOT Ağ Katmanı (No Raw Fetch Policy)
     *
     * Tüm wizard HTTP çağrıları bu fonksiyon üzerinden geçer.
     * Raw fetch() kullanımı MİMARİ İHLAL — @see docs/adr/2026-02-15-no-raw-fetch-policy.md
     *
     * Sağladıkları:
     *  - APIHelper.safeFetch varsa onu kullanır (cache, logging, debounce)
     *  - Yoksa native fetch + otomatik CSRF / Accept / Content-Type
     *  - Context7 uyumlu telemetri (tStart / tEnd)
     *  - Safe JSON parse (asla throw etmez)
     *  - Standardized response: { response, data, ok }
     *
     * @param {string} url - İstek URL'i
     * @param {object} [options={}] - Fetch options (method, body, headers vb.)
     * @param {string|null} [telemetryEvent=null] - Telemetri event adı (null = ölçüm yok)
     * @param {object} [telemetryExtra={}] - Ek telemetri bağlamı (contextKey vb.)
     * @returns {Promise<{response: Response|null, data: object|null, ok: boolean}>}
     */
    async function wizardFetch(url, options = {}, telemetryEvent = null, telemetryExtra = {}) {
        const timer = telemetryEvent ? tStart(telemetryEvent) : null;

        // Default headers
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const defaultHeaders = {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        // CSRF token sadece mutasyon isteklerinde (GET/HEAD hariç)
        const method = (options.method || 'GET').toUpperCase();
        if (method !== 'GET' && method !== 'HEAD') {
            defaultHeaders['X-CSRF-TOKEN'] = csrfToken;
        }

        // Content-Type: JSON (FormData hariç — browser otomatik boundary ekler)
        if (options.body && !(options.body instanceof FormData)) {
            defaultHeaders['Content-Type'] = 'application/json';
        }

        const mergedOptions = {
            ...options,
            headers: { ...defaultHeaders, ...options.headers },
            credentials: options.credentials || 'same-origin',
        };

        try {
            // APIHelper varsa onu kullan (cache, logging, monitoring), yoksa native fetch
            const response = window.APIHelper
                ? await window.APIHelper.safeFetch(url, mergedOptions)
                : await fetch(url, mergedOptions); // @context7:exempt — wizardFetch wrapper implementasyonu

            // Safe JSON parse — response body'yi text olarak oku, sonra parse et
            let data;
            try {
                const responseText = await response.text();
                data = responseText ? JSON.parse(responseText) : {};
            } catch (_parseErr) {
                data = {
                    success: false,
                    message: `Sunucu geçersiz yanıt döndürdü (HTTP ${response.status})`,
                };
            }

            // Context7 uyumlu telemetri
            if (timer) {
                tEnd(timer, {
                    http_durum_kodu: response.status,
                    basarili: response.ok,
                    istek_url: url,
                    ...telemetryExtra,
                });
            }

            return { response, data, ok: response.ok };
        } catch (error) {
            // Network error telemetrisi
            if (timer) {
                tEnd(timer, {
                    basarili: false,
                    hata_mesaji: error.message,
                    istek_url: url,
                    ...telemetryExtra,
                });
            }

            return {
                response: null,
                data: { success: false, message: error.message || 'Ağ hatası' },
                ok: false,
            };
        }
    }

    /**
     * Singleton wizard instance - aynı objeyi döndürür
     * @type {Object|null}
     */
    let wizardInstance = null;

    /**
     * İlan Wizard sayfası için Alpine.js data fonksiyonu
     * ✅ SINGLETON PATTERN: Her çağrıda aynı objeyi döndürür
     *
     * @returns {Object} Alpine.js x-data objesi
     */
    window.ilanWizard = function () {
        // Singleton: Eğer instance varsa aynı objeyi döndür
        if (wizardInstance) {
            return wizardInstance;
        }

        wizardInstance = {
            currentStep: 1,
            totalSteps: 5,
            completedSteps: [],
            formData: {},
            healthData: null,
            // ✅ Intelligence Hub entegrasyonu

            init() {
                // ✅ Init Delay: Alpine'in script yüklenmeden önce çalışmasını engelle
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', () => {
                        setTimeout(() => this.initializeWizard(), 100);
                    });
                } else {
                    setTimeout(() => this.initializeWizard(), 100);
                }
            },

            initializeWizard() {
                this.loadDraft();
                this.setupValidation();
                this.updateRequiredFields();
                // Photo upload initialization will be done after DOM is ready
                setTimeout(() => {
                    this.initPhotoUpload();
                }, 100);

                // Merkezi konum/harita sistemi init
                this.initLocationSystem();

                // Expose collectDraftFeatures globally
                window.collectDraftFeatures = this.collectDraftFeatures.bind(this);

                // Yayın tipi governance state
                window.getSelectedYayinTipiSlug = this.getSelectedYayinTipiSlug.bind(this);
                window.getSelectedKategoriSlug = this.getSelectedKategoriSlug.bind(this);
                this.setupYayinTipiChangeListener();
                this.updateContextSlugs();

                // Step 1 zorunlu alanları için input event listener'ları ekle
                this.setupStep1FieldListeners();

                // 🔱 KRITIK #1: Kategori değişince field visibility'ı güncelle
                this.setupCategoryChangeListener();
            },

            /**
             * 🔱 Kategori değişince field visibility'ı güncelle
             */
            setupCategoryChangeListener() {
                const altKategoriSelect = document.getElementById('alt_kategori_id');
                const yayinTipiSelect = document.getElementById('junction_id');

                const updateFieldVisibility = () => {
                    this.updateFieldVisibilityByCategory();
                };

                if (altKategoriSelect) {
                    altKategoriSelect.addEventListener('change', updateFieldVisibility);
                }
                if (yayinTipiSelect) {
                    yayinTipiSelect.addEventListener('change', updateFieldVisibility);
                }
            },

            /**
             * 🛰️ Resolve Wizard Context (SSOT Resolver)
             *
             * Phase 24: Unifies template, features, and field visibility under one lifecycle.
             * Implements spam protection, in-flight dedupe, and stale response discard.
             */
            async resolveWizardContext(params = null) {
                const altKategoriSelect = document.getElementById('alt_kategori_id');
                const yayinTipiSelect = document.getElementById('junction_id');
                const anaKategoriSelect = document.getElementById('ana_kategori_id');

                let kategoriId = params?.alt_kategori_id || altKategoriSelect?.value;
                const yayinTipiId = params?.junction_id || yayinTipiSelect?.value;
                const anaKategoriId = params?.ana_kategori_id || anaKategoriSelect?.value;

                // 🔱 Leaf Category / Parent Fallback support
                if (!kategoriId && anaKategoriId && anaKategoriId > 0) {
                    kategoriId = anaKategoriId;
                }

                if (!kategoriId || !yayinTipiId) {
                    console.debug('[WIZARD] context_skip: Missing identifiers', {
                        kategoriId,
                        yayinTipiId,
                    });
                    return;
                }

                // Context key must include ALL identifying factors to prevent wrong dedupe
                const contextKey = `${anaKategoriId || 'none'}-${kategoriId}-${yayinTipiId}`;

                // 1. Min interval throttle (300ms)
                const now = Date.now();
                if (this.__lastContextFetch && now - this.__lastContextFetch < 300) {
                    console.log('[WIZARD] phase=resolution action=throttle_skip key=' + contextKey);
                    return;
                }
                this.__lastContextFetch = now;

                // 2. In-flight dedupe: reuse Promise if same key
                if (this.__inFlightContextPromise && this.__inFlightContextKey === contextKey) {
                    console.log('[WIZARD] phase=resolution action=dedupe_reuse key=' + contextKey);
                    return this.__inFlightContextPromise;
                }

                // 3. Stale response discard preparation
                this.__latestContextKey = contextKey;

                console.log('[WIZARD] phase=resolution action=fetch_starting key=' + contextKey);

                this.__inFlightContextKey = contextKey;
                this.__inFlightContextPromise = (async () => {
                    try {
                        // ✅ No Raw Fetch Policy: wizardFetch SSOT
                        const contextUrl = `/api/v1/wizard/context?alt_kategori_id=${kategoriId}&junction_id=${yayinTipiId}`;
                        const {
                            response,
                            data: result,
                            ok,
                        } = await wizardFetch(
                            contextUrl,
                            { method: 'GET' },
                            'wizard_fetch_context',
                            { contextKey }
                        );

                        if (!ok) {
                            throw new Error(`HTTP request failed: ${response?.status || 0}`);
                        }

                        // 4. Stale response discard: latestContextKey harici response apply etme
                        if (this.__latestContextKey !== contextKey) {
                            console.log('[WIZARD] phase=resolution action=discard_stale meta=', {
                                contextKey,
                                currentKey: this.__latestContextKey,
                            });
                            return;
                        }

                        // 🛠️ API Response Check (Wrapper handling)
                        // Backend might return { data: { context: ... } } or { context: ... }
                        const contextData = result.data?.context || result.context;
                        const isSuccess = result.success || result.data?.success;

                        // Fail-soft handling: backend returns success=true with context=null
                        const responseState = result.state || result.data?.state;
                        if (isSuccess && !contextData && responseState) {
                            console.info(
                                '[WIZARD] phase=resolution action=fail_soft state=' + responseState,
                                result.message || ''
                            );
                            // Don't show error — just silently skip context application
                            return;
                        }

                        if (!isSuccess || !contextData) {
                            console.error(
                                '[WIZARD] phase=resolution action=error_invalid_payload meta=',
                                result
                            );

                            const kategoriSlug =
                                (typeof this.getSelectedKategoriSlug === 'function'
                                    ? this.getSelectedKategoriSlug()
                                    : null) || kategoriId;
                            const yayinSlug =
                                (typeof this.getSelectedYayinTipiSlug === 'function'
                                    ? this.getSelectedYayinTipiSlug()
                                    : null) || yayinTipiId;

                            this.showTemplateErrorState(kategoriSlug, yayinSlug);
                            return;
                        }

                        const { template, features } = contextData;

                        // Deterministik uygulama sırası:
                        // 1) applyTemplateSSOT BEFORE other components
                        this.applyTemplateSSOT(
                            template.required,
                            template.optional,
                            template.hidden,
                            features.feature_groups,
                            template.validation_rules,
                            template.fields,
                            template.field_visibility
                        );

                        // Cache result for other components
                        this.wizardContext = contextData;

                        console.log(
                            '[WIZARD] phase=resolution action=applied key=' +
                                contextKey +
                                'templateId=' +
                                (template.id || 'none')
                        );

                        // 2) dispatch wizard-context-applied
                        document.dispatchEvent(
                            new CustomEvent('wizard-context-applied', {
                                detail: {
                                    context: result.context,
                                    context_key: contextKey,
                                    timestamp: Date.now(),
                                },
                            })
                        );

                        return result.context;
                    } catch (error) {
                        // Telemetri wizardFetch içinde otomatik kaydedilir
                        console.error('[WIZARD] phase=resolution action=fetch_failed meta=', {
                            error: error.message,
                            contextKey,
                        });
                        this.showNetworkErrorState(error, { kategoriId, yayinTipiId });
                    } finally {
                        if (this.__inFlightContextKey === contextKey) {
                            this.__inFlightContextPromise = null;
                            this.__inFlightContextKey = null;
                        }
                    }
                })();

                return this.__inFlightContextPromise;
            },

            /**
             * 🔱 Kategori ve yayın tipi seçimine göre alanları gizle/göster (LEGACY WRAPPER)
             */
            async updateFieldVisibilityByCategory() {
                await this.resolveWizardContext();
            },

            /**
             * 🎯 Template SSOT kurallarını form'a uygula
             */
            applyTemplateSSOT(
                requiredFields,
                optionalFields,
                hiddenFields,
                featureGroups,
                validation,
                fieldOverrides,
                fieldVisibility
            ) {
                const form = document.getElementById('ilan-wizard-form');
                if (!form) return;

                console.info(
                    '[WIZARD] lifecycle + applying_visibility: Syncing DOM with SSOT context'
                );

                // Step 2, 3 & 4 alanlarını kontrol et
                const relevantSteps = form.querySelectorAll('.wizard-step');

                relevantSteps.forEach((stepContainer) => {
                    // Find current step number from container x-show or class
                    let stepNum = 0;
                    const xShow = stepContainer.getAttribute('x-show');
                    if (xShow && xShow.includes('currentStep ===')) {
                        stepNum = parseInt(xShow.match(/currentStep === (\d+)/)[1]);
                    }

                    const allFields = stepContainer.querySelectorAll(
                        'input[name]:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]),' +
                            'select[name],' +
                            'textarea[name]'
                    );

                    allFields.forEach((field) => {
                        const fieldName = field.getAttribute('name');
                        const fieldGroup =
                            field.closest('.form-group') ||
                            field.closest('div[class*="space-y"]')?.closest('div') ||
                            field.parentElement;

                        if (!fieldGroup) return;

                        // 1. Identify visibility from schema
                        const isRequired = requiredFields?.includes(fieldName);
                        const isOptional = optionalFields?.includes(fieldName);
                        const isHidden = hiddenFields?.includes(fieldName);

                        // 2. Override visibility from step-specific map if exists
                        let shouldBeVisible = !isHidden;
                        if (fieldVisibility && stepNum > 0) {
                            const stepKey = `step${stepNum}`;
                            if (fieldVisibility[stepKey]) {
                                shouldBeVisible = fieldVisibility[stepKey].includes(fieldName);
                            }
                        }

                        // Apply Overrides (Label, Placeholder, Hint, Suffix)
                        if (fieldOverrides && fieldOverrides[fieldName]) {
                            const override = fieldOverrides[fieldName];
                            const label =
                                fieldGroup.querySelector('label') ||
                                fieldGroup.querySelector('.wizard-field-label');

                            if (label && override.label) {
                                const star = label.querySelector('.required-star, .text-red-500');
                                const suffix = label.querySelector('.field-suffix');
                                label.textContent = override.label + '';
                                if (star) label.appendChild(star);
                                if (suffix) label.appendChild(suffix);
                            }

                            if (override.placeholder) {
                                field.setAttribute('placeholder', override.placeholder);
                            }

                            // 🧪 Phase 18.1: AI Hints (UI Intelligence)
                            if (override.hint) {
                                let hintEl = fieldGroup.querySelector('.field-hint');
                                if (!hintEl) {
                                    hintEl = fieldGroup.querySelector('p.mt-1.text-xs');
                                    if (hintEl) hintEl.classList.add('field-hint');
                                }

                                if (!hintEl) {
                                    hintEl = document.createElement('p');
                                    hintEl.className =
                                        'mt-1 text-xs text-blue-500 dark:text-blue-400 font-medium field-hint opacity-0 translate-y-1 transition-all duration-300';
                                    field.insertAdjacentElement('afterend', hintEl);
                                    setTimeout(
                                        () => hintEl.classList.remove('opacity-0', 'translate-y-1'),
                                        10
                                    );
                                }

                                hintEl.innerHTML = `<i class="fas fa-magic mr-1"></i> ${override.hint}`;
                            }

                            // 🧪 Phase 18.1: Unit Suffixes (UI Intelligence)
                            if (override.suffix) {
                                let suffixEl = fieldGroup.querySelector('.field-suffix');
                                if (!suffixEl) {
                                    suffixEl = document.createElement('span');
                                    suffixEl.className =
                                        'ml-2 px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/30 text-[10px] font-bold text-blue-600 dark:text-blue-400 field-suffix';
                                    if (label) label.appendChild(suffixEl);
                                }
                                suffixEl.textContent = override.suffix;
                            }
                        }

                        if (!shouldBeVisible) {
                            // 🔴 GIZLE
                            fieldGroup.style.display = 'none';
                            field.removeAttribute('required');
                            field.classList.add('template-hidden');
                        } else if (isRequired) {
                            // 🟢 ZORUNLU
                            fieldGroup.style.display = '';
                            field.setAttribute('required', 'required');
                            field.classList.remove('template-hidden');

                            // Required işareti ekle
                            const label = fieldGroup.querySelector('label');
                            if (label && !label.querySelector('.required-star')) {
                                const star = document.createElement('span');
                                star.className = 'required-star text-red-500 ml-1';
                                star.textContent = '*';
                                label.appendChild(star);
                            }
                        } else if (isOptional) {
                            // 🟡 OPSİYONEL
                            fieldGroup.style.display = '';
                            field.removeAttribute('required');
                            field.classList.remove('template-hidden');

                            // Required yıldızını kaldır
                            const label = fieldGroup.querySelector('label');
                            const star = label?.querySelector('.required-star');
                            if (star) star.remove();
                        }
                    });
                });

                // Update validation rules in the component state
                if (validation && validation.rules) {
                    this.wizardRules = validation.rules;
                    this.wizardMessages = validation.messages || {};
                    console.info('[WIZARD] lifecycle + validation: Rules synced from SSOT');
                }
            },

            /**
             * 🟡 Legacy matrix fallback (template yoksa)
            /**
             * 🚨 Show Template Error State (No Fallback to Wrong Category)
             */
            showTemplateErrorState(kategoriSlug, yayinSlug) {
                const step2Container =
                    document.querySelector('[x-show*="currentStep === 2"]') ||
                    document.querySelector('.wizard-step-2') ||
                    document.getElementById('step-2-content');

                if (!step2Container) {
                    console.warn('⚠️ Step 2 container not found');
                    return;
                }

                step2Container.innerHTML = `
                    <div class="p-8 text-center bg-yellow-50 dark:bg-yellow-900/10 border-2 border-yellow-200 dark:border-yellow-800 rounded-2xl shadow-lg">
                        <div class="mb-4">
                            <svg class="w-16 h-16 mx-auto text-yellow-500 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-yellow-800 dark:text-yellow-300 mb-2">
                            ⚠️ Template Tanımlı Değil
                        </h3>
                        <p class="text-sm text-yellow-700 dark:text-yellow-400 mb-4">
                            <strong>Kategori:</strong> ${kategoriSlug}<br>
                            <strong>Yayın Tipi:</strong> ${yayinSlug}
                        </p>
                        <p class="text-xs text-yellow-600 dark:text-yellow-500">
                            Lütfen admin panelinden bu kategori kombinasyonu için template oluşturun.
                        </p>
                    </div>
                `;
            },

            /**
             * 🔴 Show Network Error State with Retry
             */
            showNetworkErrorState(error, params) {
                const step2Container =
                    document.querySelector('[x-show*="currentStep === 2"]') ||
                    document.querySelector('.wizard-step-2') ||
                    document.getElementById('step-2-content');

                if (!step2Container) {
                    console.warn('⚠️ Step 2 container not found for error state');
                    return;
                }

                const retryHandler = () => {
                    console.log('[WIZARD] phase=error_recovery action=retry_clicked meta=', params);
                    this.resolveWizardContext(params);
                };

                step2Container.innerHTML = `
                    <div class="p-8 text-center bg-red-50 dark:bg-red-900/10 border-2 border-red-200 dark:border-red-800 rounded-2xl shadow-lg">
                        <div class="mb-4">
                            <svg class="w-16 h-16 mx-auto text-red-500 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-red-800 dark:text-red-300 mb-2">
                            🔴 Bağlam Yüklenemedi
                        </h3>
                        <p class="text-sm text-red-700 dark:text-red-400 mb-4">
                            Wizard bağlamı yüklenirken bir hata oluştu.<br>
                            <span class="text-xs">${error.message || 'Network error'}</span>
                        </p>
                        <button
                            onclick="window.ilanWizardInstance?.resolveWizardContext({alt_kategori_id: ${params.kategoriId}, junction_id: ${params.yayinTipiId}})"
                            class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                            🔄 Tekrar Dene
                        </button>
                    </div>
                `;
            },

            /**
             * 🔴 Legacy Visibility (DEPRECATED - DEV ONLY)
             */

            getSelectedYayinTipiSlug() {
                const select = document.getElementById('junction_id');
                if (!select || !select.value) return null;
                const opt = select.options[select.selectedIndex];
                return opt?.getAttribute('data-slug') || null;
            },

            getSelectedKategoriSlug() {
                const select = document.getElementById('alt_kategori_id');
                if (!select || !select.value) return null;
                const opt = select.options[select.selectedIndex];
                return opt?.getAttribute('data-slug') || null;
            },

            requiresCalendarForSlug(slug) {
                if (!slug) return false;

                const normalizeToCanonical = (value) => {
                    const s = String(value).trim().toLowerCase().replace(/_/g, '-');
                    const map = {
                        satilik: 'satilik',
                        kiralik: 'kiralik',
                        devren: 'devren',
                        gunluk: 'gunluk',
                        'gunluk-kiralik': 'gunluk',
                        'gunluk-kiralama': 'gunluk',
                        haftalik: 'haftalik',
                        'haftalik-kiralik': 'haftalik',
                        'haftalik-kiralama': 'haftalik',
                        aylik: 'aylik',
                        'aylik-kiralik': 'aylik',
                        'aylik-kiralama': 'aylik',
                        sezonluk: 'sezonluk',
                        'sezonluk-kiralik': 'sezonluk',
                        'sezonluk-kiralama': 'sezonluk',
                        yazlik: 'yazlik-kiralik',
                        'yazlik-kiralik': 'yazlik-kiralik',
                    };

                    return map[s] || s;
                };

                const canonical = normalizeToCanonical(slug);
                return ['gunluk', 'haftalik', 'aylik', 'sezonluk', 'yazlik-kiralik'].includes(
                    canonical
                );
            },

            setupYayinTipiChangeListener() {
                const select = document.getElementById('junction_id');
                if (!select) return;
                select.addEventListener('change', () => {
                    this.resetGovernanceState();
                    const suppressToast =
                        (window.YALI_OPTIONS &&
                            window.YALI_OPTIONS.suppressYayinTipiChangeToast) === true;
                    if (!suppressToast) {
                        this.showNotification(
                            'Yayın tipi değişti — AI/kalite/takvim state sıfırlandı.',
                            'info'
                        );
                    }
                    // Clear visual error state on select change
                    select.classList.remove('border-red-500', 'dark:border-red-500');
                    const err = select.parentElement?.querySelector?.('.field-error');
                    if (err) err.remove();
                    this.updateContextSlugs();
                });
            },

            resetGovernanceState() {
                const mode =
                    (window.YALI_OPTIONS && window.YALI_OPTIONS.resetOnYayinTipiChange) ||
                    'minimal';
                // Always reset AI quality gate
                window.__aiQualityResult = null;
                const aiQualityComponent = document.querySelector('[x-data]')?.__x?.$data;
                if (aiQualityComponent && typeof aiQualityComponent === 'object') {
                    aiQualityComponent.overrideBlock = false;
                }
                if (mode === 'full') {
                    const aciklama = document.getElementById('aciklama');
                    if (aciklama) {
                        aciklama.value = '';
                    }
                    const takvimInputs = document.querySelectorAll('[data-calendar-selected]');
                    takvimInputs.forEach((el) => {
                        el.removeAttribute('data-calendar-selected');
                    });
                }
            },

            updateContextSlugs() {
                let ctxEl = document.getElementById('wizard-context');
                if (!ctxEl) {
                    ctxEl = document.createElement('div');
                    ctxEl.id = 'wizard-context';
                    ctxEl.style.display = 'none';
                    document.body.appendChild(ctxEl);
                }
                const kategoriSlug = this.getSelectedKategoriSlug();
                let yayinTipiSlug = this.getSelectedYayinTipiSlug();
                if (!yayinTipiSlug) {
                    const select = document.getElementById('junction_id');
                    if (select && select.options && select.options.length > 0) {
                        const opt = Array.from(select.options).find(
                            (o) => (o.getAttribute('data-slug') || '').toLowerCase() === 'sezonluk'
                        );
                        if (opt) {
                            select.value = opt.value;
                            select.dispatchEvent(new window.Event('change', { bubbles: true }));
                            yayinTipiSlug = this.getSelectedYayinTipiSlug();
                        }
                    }
                }
                if (kategoriSlug) ctxEl.setAttribute('data-kategori-slug', kategoriSlug);
                if (yayinTipiSlug) ctxEl.setAttribute('data-yayin-tipi-slug', yayinTipiSlug);
            },

            initLocationSystem() {
                const startStep1Map = () => {
                    if (window.initWizardMap) {
                        window.initWizardMap();
                    }
                };

                if ('IntersectionObserver' in window) {
                    const observer = new window.IntersectionObserver(
                        (entries) => {
                            entries.forEach((entry) => {
                                if (!entry.isIntersecting) return;

                                if (entry.target.id === 'wizard-map') {
                                    startStep1Map();
                                }
                            });
                        },
                        {
                            rootMargin: '100px',
                            threshold: 0.01,
                        }
                    );

                    const step1El = document.getElementById('wizard-map');

                    if (step1El) observer.observe(step1El);
                } else {
                    setTimeout(startStep1Map, 500);
                }

                document.addEventListener('wizard-step-changed', (e) => {
                    if (!e.detail) return;

                    if (e.detail.step === 1 && window.wizardMap) {
                        setTimeout(() => {
                            window.wizardMap.invalidateSize();
                        }, 200);
                    }
                });
            },

            goToStep(step) {
                if (step < this.currentStep || this.completedSteps.includes(step - 1)) {
                    this.currentStep = step;
                    this.updateRequiredFields();
                    this.scrollToTop();
                    // Step 2'ye geçildiğinde kategori kontrolünü tetikle
                    if (step === 2) {
                        setTimeout(() => {
                            document.dispatchEvent(
                                new window.CustomEvent('wizard-step-changed', {
                                    detail: {
                                        step: 2,
                                    },
                                })
                            );

                            // ✅ Step 2'ye geçildiğinde kategori bilgisini kontrol et ve category-changed event'ini tekrar dispatch et
                            this.triggerCategoryChangedIfNeeded();
                        }, 100);
                    }
                }
            },

            getPriceDisplayMode() {
                const modeField =
                    document.getElementById('fiyat_gosterim_modu') ||
                    document.querySelector('[name="fiyat_gosterim_modu"], #price_display_mode, [name="price_display_mode"]');

                return modeField?.value || 'exact';
            },

            isPriceRequiredByMode() {
                return this.getPriceDisplayMode() === 'exact';
            },

            nextStep() {
                // ✅ YENİ YAPI: Step 1 - Sadece Kategori Validasyonu
                if (this.currentStep === 1) {
                    if (!this.validateStep(1)) {
                        return false;
                    }
                }

                // ✅ YENİ YAPI: Step 2 - Başlık, Fiyat, Açıklama
                if (this.currentStep === 2) {
                    const baslik = document.getElementById('baslik')?.value?.trim();
                    const fiyat = document.getElementById('fiyat')?.value?.trim();
                    const fiyatRequired = this.isPriceRequiredByMode();

                    const errors = [];

                    if (!baslik) {
                        errors.push('Başlık');
                        const baslikEl = document.getElementById('baslik');
                        if (baslikEl) this.showFieldError(baslikEl, 'Başlık zorunludur');
                    }

                    if (fiyatRequired && (!fiyat || fiyat === '0')) {
                        errors.push('Fiyat');
                        const fiyatEl = document.getElementById('fiyat');
                        if (fiyatEl) this.showFieldError(fiyatEl, 'Fiyat zorunludur');
                    } else {
                        const fiyatEl = document.getElementById('fiyat');
                        if (fiyatEl) this.hideFieldError(fiyatEl);
                    }

                    // Backend contract parity: aciklama nullable (no runtime min-length gate)
                    const aciklamaEl = document.getElementById('aciklama');
                    if (aciklamaEl) this.hideFieldError(aciklamaEl);

                    if (errors.length > 0) {
                        this.showNotification(
                            `Lütfen zorunlu alanları doldurun: ${errors.join(', ')}`,
                            'error'
                        );
                        return false;
                    }
                }

                // ✅ YENİ YAPI: Step 3 - Fotoğraf (Opsiyonel, sadece uyarı)
                if (this.currentStep === 3) {
                    // Not: Bu alanda Alpine.js photoWizardStep2() component'i kullanılıyor.
                    // photos array'i ana wizard state'inde olduğu için oradan kontrol edilebilir.

                    if (this.photos?.length === 0) {
                        // Fotoğraf yoksa uyarı ver ama engelleme
                        this.showNotification(
                            '💡 İpucu: En az 3 fotoğraf eklemeniz önerilir. Devam edebilirsiniz.',
                            'warning'
                        );
                    }
                }

                // ✅ YENİ YAPI: Step 4 - İl, İlçe, Mahalle
                if (this.currentStep === 4) {
                    const il = document.getElementById('il_id')?.value;
                    const ilce = document.getElementById('ilce_id')?.value;
                    const mahalle = document.getElementById('mahalle_id')?.value;

                    const errors = [];

                    if (!il) {
                        errors.push('İl');
                        const ilEl = document.getElementById('il_id');
                        if (ilEl) this.showFieldError(ilEl, 'İl seçilmelidir');
                    }

                    if (!ilce) {
                        errors.push('İlçe');
                        const ilceEl = document.getElementById('ilce_id');
                        if (ilceEl) this.showFieldError(ilceEl, 'İlçe seçilmelidir');
                    }

                    if (!mahalle) {
                        errors.push('Mahalle');
                        const mahalleEl = document.getElementById('mahalle_id');
                        if (mahalleEl) this.showFieldError(mahalleEl, 'Mahalle seçilmelidir');
                    }

                    if (errors.length > 0) {
                        this.showNotification(
                            `Lütfen zorunlu alanları doldurun: ${errors.join(', ')}`,
                            'error'
                        );
                        return false;
                    }
                }

                // Diğer step'ler için mevcut validasyon
                if (this.validateStep(this.currentStep)) {
                    if (!this.completedSteps.includes(this.currentStep)) {
                        this.completedSteps.push(this.currentStep);
                    }
                    if (this.currentStep < this.totalSteps) {
                        this.currentStep++;
                        this.updateRequiredFields();
                        this.scrollToTop();
                        // Step 2'ye geçildiğinde kategori kontrolünü tetikle
                        if (this.currentStep === 2) {
                            setTimeout(() => {
                                document.dispatchEvent(
                                    new window.CustomEvent('wizard-step-changed', {
                                        detail: {
                                            step: 2,
                                        },
                                    })
                                );

                                // ✅ Step 2'ye geçildiğinde kategori bilgisini kontrol et ve category-changed event'ini tekrar dispatch et
                                this.triggerCategoryChangedIfNeeded();
                            }, 100);
                        }
                    }
                }
            },

            /**
             * ✅ YENİ YAPI: Step 1 için validasyon - Sadece Kategori
             * Zorunlu alanlar: ana_kategori_id, alt_kategori_id, yayin_tipi_id
             */
            validateStep1() {
                const requiredFields = [
                    { id: 'ana_kategori_id', name: 'Ana Kategori' },
                    { id: 'alt_kategori_id', name: 'Alt Kategori' },
                    { id: 'junction_id', name: 'Yayın Tipi' },
                ];

                let isValid = true;
                const emptyFields = [];

                requiredFields.forEach((field) => {
                    const element = document.getElementById(field.id);
                    if (!element) {
                        // Element bulunamadı, validasyonu atla (muhtemelen DOM henüz hazır değil)
                        return;
                    }

                    // Select için özel kontrol
                    if (element.tagName === 'SELECT') {
                        if (!element.value || element.value === '' || element.disabled) {
                            isValid = false;
                            emptyFields.push(field.name);
                            this.showFieldError(element, `${field.name} seçilmelidir`);
                        } else {
                            this.hideFieldError(element);
                        }
                    }
                    // Input için kontrol (fiyat için özel kontrol)
                    else if (element.tagName === 'INPUT') {
                        const value = element.value;

                        // Fiyat için özel kontrol (formatlanmış değer kontrolü)
                        if (field.id === 'fiyat') {
                            const rawValue = value.replace(/\./g, '').replace(/,/g, '');
                            if (
                                !rawValue ||
                                rawValue.trim() === '' ||
                                isNaN(rawValue) ||
                                parseFloat(rawValue) <= 0
                            ) {
                                isValid = false;
                                emptyFields.push(field.name);
                                this.showFieldError(element, 'Geçerli bir fiyat giriniz');
                            } else {
                                this.hideFieldError(element);
                            }
                        } else {
                            // Diğer input alanları için normal kontrol
                            if (!value || value.trim() === '') {
                                isValid = false;
                                emptyFields.push(field.name);
                                this.showFieldError(element, `${field.name} alanı zorunludur`);
                            } else {
                                this.hideFieldError(element);
                            }
                        }
                    }
                });

                if (!isValid) {
                    const errorMessage =
                        emptyFields.length > 0
                            ? `Lütfen zorunlu alanları doldurun: ${emptyFields.join(', ')}`
                            : 'Lütfen zorunlu alanları doldurun';
                    this.showNotification(errorMessage, 'error');

                    // İlk boş alana scroll yap
                    const firstEmptyField = requiredFields.find((field) => {
                        const el = document.getElementById(field.id);
                        return el && el.classList.contains('border-red-500');
                    });
                    if (firstEmptyField) {
                        const element = document.getElementById(firstEmptyField.id);
                        if (element) {
                            element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            element.focus();
                        }
                    }
                }

                return isValid;
            },

            /**
             * ✅ Step 2 için validasyon
             * Zorunlu alanlar: kategoriye göre değişir (Konut: oda_sayisi, brut_alan, vb.)
             * Yazlık: gunluk_fiyat, min_konaklama
             */

            prevStep() {
                if (this.currentStep > 1) {
                    this.currentStep--;
                    this.updateRequiredFields();
                    this.scrollToTop();
                }
            },

            // ✅ SAB: Sadece aktif adımdaki alanların required olmasını sağla
            updateRequiredFields() {
                const form = document.getElementById('ilan-wizard-form');
                if (!form) return;

                // Tüm required alanları bul
                const allRequiredFields = form.querySelectorAll('[required]');

                // Önce tüm required attribute'larını kaldır
                allRequiredFields.forEach((field) => {
                    field.removeAttribute('required');
                });

                // Aktif adımdaki alanları bul ve required yap
                const stepFields = this.getStepFields(this.currentStep);
                stepFields.forEach((fieldName) => {
                    const field = form.querySelector(`[name="${fieldName}"]`);
                    if (field) {
                        if (fieldName === 'aciklama') {
                            return;
                        }

                        if (fieldName === 'fiyat' && !this.isPriceRequiredByMode()) {
                            return;
                        }

                        field.setAttribute('required', 'required');
                    }
                });
            },

            validateStep(step) {
                const form = document.getElementById('ilan-wizard-form');
                const stepFields = this.getStepFields(step);

                let isValid = true;
                const errors = [];

                // 🎯 Phase 2: SSOT Validation Rules
                const templateRules = this.wizardRules || {};

                stepFields.forEach((fieldName) => {
                    const field = form.querySelector(`[name="${fieldName}"]`);
                    if (!field) return;

                    // Determine if field is actually required (Template SSOT or HTML attribute)
                    const isRequired = field.hasAttribute('required') || !!templateRules[fieldName];

                    if (isRequired) {
                        // Checkbox ve radio için özel kontrol
                        if (field.type === 'checkbox' || field.type === 'radio') {
                            const checked = form.querySelector(`[name="${fieldName}"]:checked`);
                            if (!checked) {
                                isValid = false;
                                const msg =
                                    this.wizardMessages?.[`${fieldName}.required`] ||
                                    'Bu alan zorunludur';
                                errors.push(msg);
                                this.showFieldError(field, msg);
                            } else {
                                this.hideFieldError(field);
                            }
                        } else if (field.type === 'file') {
                            if (!field.files || field.files.length === 0) {
                                if (step !== 2) {
                                    isValid = false;
                                    const msg =
                                        this.wizardMessages?.[`${fieldName}.required`] ||
                                        'Bu alan zorunludur';
                                    errors.push(msg);
                                    this.showFieldError(field, msg);
                                }
                            } else {
                                this.hideFieldError(field);
                            }
                        } else {
                            // Normal input, select, textarea
                            const value = field.value?.trim();
                            if (!value) {
                                isValid = false;
                                const msg =
                                    this.wizardMessages?.[`${fieldName}.required`] ||
                                    'Bu alan zorunludur';
                                errors.push(msg);
                                this.showFieldError(field, msg);
                            } else {
                                // Rule-based validation (min, max, numeric etc)
                                const rule = templateRules[fieldName];
                                if (rule) {
                                    if (
                                        rule.includes('numeric') &&
                                        isNaN(value.replace(/\./g, '').replace(',', '.'))
                                    ) {
                                        isValid = false;
                                        const msg =
                                            this.wizardMessages?.[`${fieldName}.numeric`] ||
                                            'Sayısal bir değer giriniz';
                                        errors.push(msg);
                                        this.showFieldError(field, msg);
                                    } else if (rule.includes('min:')) {
                                        const min = parseInt(rule.split('min:')[1]);
                                        if (value.length < min) {
                                            isValid = false;
                                            const msg =
                                                this.wizardMessages?.[`${fieldName}.min`] ||
                                                `En az ${min} karakter girmelisiniz`;
                                            errors.push(msg);
                                            this.showFieldError(field, msg);
                                        }
                                    }
                                }

                                if (isValid) this.hideFieldError(field);
                            }
                        }
                    }
                });

                if (!isValid) {
                    const errorMessage =
                        errors.length > 0
                            ? errors.join(',')
                            : 'Lütfen tüm zorunlu alanları doldurun';
                    this.showNotification(errorMessage, 'error');
                }

                return isValid;
            },

            getStepFields(step) {
                // 🎯 Phase 2: SSOT-Driven Field Mapping
                // ✅ REFACTOR: Step numaraları UI sırası ile eşleştirildi
                // UI: 1. Kategori → 2. Bilgiler → 3. Fotoğraf → 4. Adres → 5. Önizleme
                const baseStepFields = {
                    1: ['ana_kategori_id', 'alt_kategori_id', 'junction_id'],
                    2: ['baslik', 'fiyat', 'para_birimi', 'aciklama'],
                    3: ['fotograflar'],
                    4: ['il_id', 'ilce_id', 'mahalle_id', 'adres_detay', 'lat', 'lng'],
                    5: ['ilan_sahibi_id', 'yayin_durumu'],
                };

                const fields = [...(baseStepFields[step] || [])];

                // If template is loaded and we are in Step 2 (Main Info), add template fields
                if (step === 2 && this.wizardRules) {
                    const templateFields = Object.keys(this.wizardRules);
                    templateFields.forEach((f) => {
                        if (!fields.includes(f)) fields.push(f);
                    });
                }

                // Also scan for dynamically rendered required fields in Step 2 (Bilgiler)
                if (step === 2) {
                    const form = document.getElementById('ilan-wizard-form');
                    if (form) {
                        const dynamicInputs = form.querySelectorAll(
                            '[name^="ozellik_"], [name^="feature_"]'
                        );
                        dynamicInputs.forEach((input) => {
                            if (input.hasAttribute('required') && !fields.includes(input.name)) {
                                fields.push(input.name);
                            }
                        });
                    }
                }

                return fields;
            },

            showFieldError(field, message) {
                // ✅ Enhanced field error display with animation
                field.classList.add(
                    'border-red-500',
                    'dark:border-red-500',
                    'ring-2',
                    'ring-red-500',
                    'ring-opacity-50'
                );
                field.classList.remove('border-gray-300', 'dark:border-gray-600');

                let errorDiv = field.parentElement.querySelector('.field-error');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className =
                        'field-error text-sm text-red-600 dark:text-red-400 mt-1 flex items-center gap-1.5 animate-pulse';
                    field.parentElement.appendChild(errorDiv);
                }
                errorDiv.innerHTML = `
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>${message}</span>
                `;

                // Scroll to field if not visible
                const rect = field.getBoundingClientRect();
                if (rect.top < 0 || rect.bottom > window.innerHeight) {
                    field.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            },

            hideFieldError(field) {
                // ✅ Enhanced field error removal with smooth transition
                field.classList.remove(
                    'border-red-500',
                    'dark:border-red-500',
                    'ring-2',
                    'ring-red-500',
                    'ring-opacity-50'
                );
                field.classList.add('border-gray-300', 'dark:border-gray-600');

                const errorDiv = field.parentElement.querySelector('.field-error');
                if (errorDiv) {
                    errorDiv.style.transition = 'opacity 0.3s ease-out';
                    errorDiv.style.opacity = '0';
                    setTimeout(() => {
                        if (errorDiv.parentNode) {
                            errorDiv.remove();
                        }
                    }, 300);
                }
            },

            /**
             * ✅ Step 1 zorunlu alanları için input event listener'ları ekle
             * Kullanıcı veri girdiğinde kırmızı border'ı otomatik kaldır
             */
            setupStep1FieldListeners() {
                const requiredFieldIds = ['baslik', 'fiyat', 'il_id', 'ilce_id', 'alt_kategori_id'];

                requiredFieldIds.forEach((fieldId) => {
                    const element = document.getElementById(fieldId);
                    if (!element) {
                        // Element henüz DOM'da yoksa, biraz bekleyip tekrar dene
                        setTimeout(() => {
                            const retryElement = document.getElementById(fieldId);
                            if (retryElement) {
                                this.attachFieldListener(retryElement);
                            }
                        }, 500);
                        return;
                    }

                    this.attachFieldListener(element);
                });
            },

            /**
             * ✅ Tek bir alan için event listener ekle
             */
            attachFieldListener(element) {
                if (element._hasValidationListener) return;

                // Input/change event listener ekle
                const eventType = element.tagName === 'SELECT' ? 'change' : 'input';

                // Yeni listener ekle
                element.addEventListener(eventType, () => {
                    // Kullanıcı veri girdiğinde kırmızı border'ı kaldır
                    if (element.value && element.value.trim() !== '') {
                        // Fiyat için özel kontrol
                        if (element.id === 'fiyat') {
                            const rawValue = element.value.replace(/\./g, '').replace(/,/g, '');
                            if (rawValue && !isNaN(rawValue) && parseFloat(rawValue) > 0) {
                                this.hideFieldError(element);
                            }
                        } else {
                            this.hideFieldError(element);
                        }
                    }
                });

                element._hasValidationListener = true;

                // Select için disabled kontrolü (il_id seçildiğinde ilce_id aktif olur)
                if (element.id === 'il_id') {
                    element.addEventListener('change', () => {
                        // İl seçildiğinde ilçe alanını kontrol et
                        setTimeout(() => {
                            const ilceField = document.getElementById('ilce_id');
                            if (ilceField && !ilceField.disabled && ilceField.value) {
                                this.hideFieldError(ilceField);
                            }
                        }, 500); // AJAX yükleme için bekle
                    });
                }

                // Alt kategori seçildiğinde yayın tipi alanını kontrol et
                if (element.id === 'alt_kategori_id') {
                    element.addEventListener('change', () => {
                        setTimeout(() => {
                            const yayinTipiSelect = document.getElementById('junction_id');
                            if (
                                yayinTipiSelect &&
                                !yayinTipiSelect.disabled &&
                                yayinTipiSelect.value
                            ) {
                                this.hideFieldError(yayinTipiField);
                            }
                        }, 500); // AJAX yükleme için bekle
                    });
                }
            },

            /**
             * ✅ Phase H: Collect draft features from Step 2
             * Returns slug-based feature map for AI/quality/publish payloads
             */
            collectDraftFeatures() {
                const features = {};
                document.querySelectorAll('[data-feature-slug]').forEach((el) => {
                    const slug = el.dataset.featureSlug;
                    if (!slug) return;

                    let value = null;

                    if (el.type === 'checkbox') {
                        if (el.checked) value = true;
                    } else if (el.type === 'radio') {
                        if (el.checked) value = el.value;
                    } else if (el.tagName === 'SELECT') {
                        value = el.value;
                    } else {
                        value = el.value;
                    }

                    // Only include non-empty values
                    if (value !== null && value !== '' && value !== undefined) {
                        features[slug] = value;
                    }
                });
                return features;
            },

            showNotification(message, type = 'info') {
                // ✅ Modern Toast Notification System
                const toast = document.createElement('div');
                const toastId = `toast-${Date.now()}`;
                toast.id = toastId;

                // Icon mapping
                const icons = {
                    error: '❌',
                    success: '✅',
                    warning: '⚠️',
                    info: 'ℹ️',
                };

                // Color mapping
                const colors = {
                    error: 'bg-red-600 dark:bg-red-700 text-white border-red-700 dark:border-red-800',
                    success:
                        'bg-green-600 dark:bg-green-700 text-white border-green-700 dark:border-green-800',
                    warning:
                        'bg-yellow-600 dark:bg-yellow-700 text-white border-yellow-700 dark:border-yellow-800',
                    info: 'bg-blue-600 dark:bg-blue-700 text-white border-blue-700 dark:border-blue-800',
                };

                toast.className = `fixed top-4 right-4 px-6 py-4 rounded-xl shadow-2xl z-[9999]
                    transition-all duration-300 ease-in-out transform translate-x-full opacity-0
                    ${colors[type] || colors.info}
                    border-2 min-w-[300px] max-w-[500px]
                    flex items-start gap-3`;

                toast.innerHTML = `
                    <div class="flex-shrink-0 text-xl">${icons[type] || icons.info}</div>
                    <div class="flex-1">
                        <p class="text-sm font-medium leading-relaxed">${message}</p>
                    </div>
                    <button onclick="document.getElementById('${toastId}').remove()"
                        class="flex-shrink-0 text-white/80 hover:text-white transition-colors duration-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                `;

                document.body.appendChild(toast);

                // Animate in
                setTimeout(() => {
                    toast.classList.remove('translate-x-full', 'opacity-0');
                    toast.classList.add('translate-x-0', 'opacity-100');
                }, 10);

                // Auto remove after 5 seconds
                const autoRemove = setTimeout(() => {
                    toast.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.remove();
                        }
                    }, 300);
                }, 5000);

                // Remove on click
                toast.addEventListener('click', () => {
                    clearTimeout(autoRemove);
                    toast.classList.add('translate-x-full', 'opacity-0');
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.remove();
                        }
                    }, 300);
                });
            },

            scrollToTop() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth',
                });
            },

            // Step 2'ye geçildiğinde kategori bilgisini kontrol et ve category-changed event'ini tekrar dispatch et
            triggerCategoryChangedIfNeeded() {
                const altKategoriSelect = document.getElementById('alt_kategori_id');
                const yayinTipiSelect = document.getElementById('yayin_tipi_id');

                if (!altKategoriSelect || !altKategoriSelect.value) {
                    console.log(
                        '⚠️ Step 2: Kategori seçilmemiş, category-changed event dispatch edilmiyor'
                    );
                    return;
                }

                const selectedAltKategoriOption =
                    altKategoriSelect.options[altKategoriSelect.selectedIndex];
                if (!selectedAltKategoriOption || !selectedAltKategoriOption.value) {
                    console.log('⚠️ Step 2: Alt kategori seçilmemiş');
                    return;
                }

                // Ana kategori bilgisini al
                const anaKategoriSelect =
                    document.getElementById('ana_kategori_id') ||
                    document.getElementById('ana_kategori');
                if (!anaKategoriSelect || !anaKategoriSelect.value) {
                    console.log('⚠️ Step 2: Ana kategori seçilmemiş');
                    return;
                }

                const selectedAnaKategoriOption =
                    anaKategoriSelect.options[anaKategoriSelect.selectedIndex];
                // ✅ FIX: Ana kategori slug'ını al - önce data-slug, sonra text'ten slug oluştur
                let anaKategoriSlug = selectedAnaKategoriOption?.getAttribute('data-slug');
                if (!anaKategoriSlug) {
                    // Text'ten slug oluştur (örn: "Arsa" -> "arsa")
                    const anaKategoriText = selectedAnaKategoriOption?.text?.trim() || '';
                    anaKategoriSlug = anaKategoriText
                        .toLowerCase()
                        .replace(/ş/g, 's')
                        .replace(/ğ/g, 'g')
                        .replace(/ü/g, 'u')
                        .replace(/ö/g, 'o')
                        .replace(/ç/g, 'c')
                        .replace(/ı/g, 'i')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                }

                // Alt kategori slug'ını al
                let altKategoriSlug = selectedAltKategoriOption?.getAttribute('data-slug');
                if (!altKategoriSlug) {
                    const altKategoriText = selectedAltKategoriOption?.text?.trim() || '';
                    altKategoriSlug = altKategoriText
                        .toLowerCase()
                        .replace(/ş/g, 's')
                        .replace(/ğ/g, 'g')
                        .replace(/ü/g, 'u')
                        .replace(/ö/g, 'o')
                        .replace(/ç/g, 'c')
                        .replace(/ı/g, 'i')
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                }

                console.log("🔍 Step 2: Kategori slug'ları:", {
                    anaKategoriSlug,
                    altKategoriSlug,
                    anaKategoriText: selectedAnaKategoriOption?.text,
                    altKategoriText: selectedAltKategoriOption?.text,
                });

                // Yayın tipi bilgisini al
                let yayinTipiId = null;
                let yayinTipiSlug = null;
                let yayinTipiName = null;

                if (yayinTipiSelect && yayinTipiSelect.value) {
                    const selectedYayinTipiOption =
                        yayinTipiSelect.options[yayinTipiSelect.selectedIndex];
                    yayinTipiId = selectedYayinTipiOption?.value || null;
                    yayinTipiSlug = selectedYayinTipiOption?.getAttribute('data-slug') || null;
                    yayinTipiName = selectedYayinTipiOption?.text || null;
                }

                // category-changed event'ini dispatch et
                const detail = {
                    category: {
                        id: parseInt(anaKategoriSelect.value),
                        slug: anaKategoriSlug,
                        parent_slug: anaKategoriSlug,
                        name: selectedAnaKategoriOption?.text || '',
                    },
                    altCategory: {
                        id: parseInt(altKategoriSelect.value),
                        slug: altKategoriSlug,
                        name: selectedAltKategoriOption?.text || '',
                    },
                    yayinTipi: {
                        id: yayinTipiId ? parseInt(yayinTipiId) : null,
                        slug: yayinTipiSlug,
                        name: yayinTipiName,
                    },
                    // Backward compatibility
                    yayinTipiId: yayinTipiId,
                };

                console.log('✅ Step 2: category-changed event dispatch ediliyor:', detail);

                // safeDispatchCategoryChanged fonksiyonu varsa kullan, yoksa direkt dispatch et
                if (window.safeDispatchCategoryChanged) {
                    // Geçici olarak detail'i window'a kaydet ki safeDispatchCategoryChanged kullanabilsin
                    window._tempCategoryDetail = detail;
                    window.safeDispatchCategoryChanged();
                } else {
                    window.dispatchEvent(
                        new window.CustomEvent('category-changed', {
                            detail: detail,
                        })
                    );
                }

                // Step 2 loader now exclusively event-driven via wizard-context-applied
                // No manual triggering needed - SSOT handles this
                this.updateContextSlugs();
            },

            saveDraft() {
                const form = document.getElementById('ilan-wizard-form');
                if (!form) return;

                const draftData = {};

                // ✅ SAB: Tüm form alanlarını düzgün işle (FormData yerine direkt form elementlerinden)
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach((field) => {
                    const name = field.name;
                    if (!name || name === '_token' || name === 'csrf_token') return;

                    // Array checkbox'lar (site_ozellikleri[], vb.)
                    if (name.endsWith('[]') && field.type === 'checkbox') {
                        const arrayKey = name.slice(0, -2);
                        if (!draftData[arrayKey]) {
                            draftData[arrayKey] = [];
                        }
                        if (field.checked) {
                            const value = field.value || '1';
                            if (!draftData[arrayKey].includes(value)) {
                                draftData[arrayKey].push(value);
                            }
                        }
                    }
                    // Normal checkbox'lar
                    else if (field.type === 'checkbox') {
                        draftData[name] = field.checked ? field.value || '1' : '0';
                    }
                    // Radio button'lar
                    else if (field.type === 'radio') {
                        if (field.checked) {
                            draftData[name] = field.value;
                        }
                    }
                    // Multi-select
                    else if (field.tagName === 'SELECT' && field.multiple) {
                        const selectedValues = Array.from(field.selectedOptions)
                            .map((opt) => opt.value)
                            .filter((v) => v);
                        draftData[name] = selectedValues.length > 0 ? selectedValues : [];
                    }
                    // Normal input, select, textarea
                    else {
                        draftData[name] = field.value || '';
                    }
                });

                localStorage.setItem('ilan_wizard_draft', JSON.stringify(draftData));
                this.showNotification('Taslak kaydedildi', 'success');
            },

            async loadDraft() {
                const draftData = localStorage.getItem('ilan_wizard_draft');
                if (!draftData) return;

                try {
                    const data = JSON.parse(draftData);
                    const form = document.getElementById('ilan-wizard-form');
                    if (!form) return;

                    // ✅ Bug Fix: Cascade dropdown'ları sıralı olarak restore et
                    // Önce tüm non-cascade field'ları restore et
                    const cascadeFields = [
                        'ana_kategori_id',
                        'alt_kategori_id',
                        'junction_id',
                        'il_id',
                        'ilce_id',
                        'mahalle_id',
                    ];

                    Object.keys(data).forEach((key) => {
                        const value = data[key];

                        // Cascade field'ları atla (sonra ayrı işlenecek)
                        if (cascadeFields.includes(key)) {
                            return;
                        }

                        // ✅ Array field'ları (site_ozellikleri[], vb.)
                        if (Array.isArray(value)) {
                            // Array checkbox'lar için
                            const arrayCheckboxes = form.querySelectorAll(`[name="${key}[]"]`);
                            arrayCheckboxes.forEach((checkbox) => {
                                checkbox.checked = value.includes(checkbox.value);
                            });

                            // Multi-select için
                            const multiSelect = form.querySelector(`[name="${key}"]`);
                            if (multiSelect && multiSelect.multiple) {
                                Array.from(multiSelect.options).forEach((option) => {
                                    option.selected = value.includes(option.value);
                                });
                                // Change event'i tetikle
                                multiSelect.dispatchEvent(
                                    new window.Event('change', {
                                        bubbles: true,
                                    })
                                );
                            }
                        } else {
                            // Normal field'lar
                            const fields = form.querySelectorAll(`[name="${key}"]`);

                            if (fields.length === 0) return;

                            fields.forEach((field) => {
                                // Skip file inputs (cannot be programmatically set)
                                if (field.type === 'file') {
                                    return;
                                }

                                // Skip live search search fields
                                if (key.endsWith('_search')) {
                                    return;
                                }

                                if (field.type === 'checkbox') {
                                    // Checkbox için
                                    field.checked =
                                        value === field.value || value === '1' || value === true;
                                } else if (field.type === 'radio') {
                                    // Radio button için
                                    field.checked = field.value === value;
                                } else {
                                    // Input, select, textarea için
                                    try {
                                        field.value = value || '';
                                    } catch (error) {
                                        // Skip fields that cannot be set (e.g., file inputs)
                                        console.debug('Cannot set value for field:', key, error);
                                    }
                                }
                            });
                        }
                    });

                    // ✅ Bug Fix: Cascade dropdown'ları sıralı olarak restore et
                    await this.restoreCascadeDropdowns(data, form);

                    this.showNotification('Taslak geri yüklendi', 'success');
                } catch (e) {
                    console.error('Draft yüklenemedi:', e);
                    this.showNotification('Taslak yüklenirken hata oluştu', 'error');
                }
            },

            // ✅ Bug Fix: Cascade dropdown'ları sıralı restore et
            async restoreCascadeDropdowns(data, form) {
                // 1. Ana Kategori restore et
                if (data.ana_kategori_id) {
                    const anaKategoriSelect = form.querySelector('[name="ana_kategori_id"]');
                    if (anaKategoriSelect) {
                        anaKategoriSelect.value = data.ana_kategori_id;
                        anaKategoriSelect.dispatchEvent(
                            new window.Event('change', { bubbles: true })
                        );

                        // Alt kategorilerin yüklenmesini bekle
                        await this.waitForDropdownReady('[name="alt_kategori_id"]', 2000);

                        // 2. Alt Kategori restore et
                        if (data.alt_kategori_id) {
                            const altKategoriSelect = form.querySelector(
                                '[name="alt_kategori_id"]'
                            );
                            if (altKategoriSelect && !altKategoriSelect.disabled) {
                                altKategoriSelect.value = data.alt_kategori_id;
                                altKategoriSelect.dispatchEvent(
                                    new window.Event('change', { bubbles: true })
                                );

                                // Junction'ların yüklenmesini bekle
                                await this.waitForDropdownReady('[name="junction_id"]', 2000);

                                // 3. Junction restore et
                                if (data.junction_id) {
                                    const junctionSelect =
                                        form.querySelector('[name="junction_id"]');
                                    if (junctionSelect && !junctionSelect.disabled) {
                                        junctionSelect.value = data.junction_id;
                                        junctionSelect.dispatchEvent(
                                            new window.Event('change', { bubbles: true })
                                        );
                                    }
                                }
                            }
                        }
                    }
                }

                // 4. İl restore et
                if (data.il_id) {
                    const ilSelect = form.querySelector('[name="il_id"]');
                    if (ilSelect) {
                        ilSelect.value = data.il_id;
                        ilSelect.dispatchEvent(new window.Event('change', { bubbles: true }));

                        // İlçelerin yüklenmesini bekle
                        await this.waitForDropdownReady('[name="ilce_id"]', 2000);

                        // 5. İlçe restore et
                        if (data.ilce_id) {
                            const ilceSelect = form.querySelector('[name="ilce_id"]');
                            if (ilceSelect && !ilceSelect.disabled) {
                                ilceSelect.value = data.ilce_id;
                                ilceSelect.dispatchEvent(
                                    new window.Event('change', { bubbles: true })
                                );

                                // Mahallelerin yüklenmesini bekle
                                await this.waitForDropdownReady('[name="mahalle_id"]', 2000);

                                // 6. Mahalle restore et
                                if (data.mahalle_id) {
                                    const mahalleSelect = form.querySelector('[name="mahalle_id"]');
                                    if (mahalleSelect && !mahalleSelect.disabled) {
                                        mahalleSelect.value = data.mahalle_id;
                                        mahalleSelect.dispatchEvent(
                                            new window.Event('change', { bubbles: true })
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            },

            // ✅ Bug Fix: Dropdown'un hazır olmasını bekle
            async waitForDropdownReady(selector, maxWait = 2000) {
                const startTime = Date.now();
                return new Promise((resolve) => {
                    const checkInterval = setInterval(() => {
                        const select = document.querySelector(selector);
                        // Dropdown hazır: disabled değil ve option'ları var
                        if (select && !select.disabled && select.options.length > 1) {
                            clearInterval(checkInterval);
                            resolve();
                        } else if (Date.now() - startTime > maxWait) {
                            // Timeout: Devam et
                            clearInterval(checkInterval);
                            resolve();
                        }
                    }, 100);
                });
            },

            setupValidation() {
                // Real-time validation
                const form = document.getElementById('ilan-wizard-form');
                form.querySelectorAll(
                    'input[required], select[required], textarea[required]'
                ).forEach((field) => {
                    field.addEventListener('blur', () => {
                        if (field.value.trim() === '') {
                            this.showFieldError(field, 'Bu alan zorunludur');
                        } else {
                            this.hideFieldError(field);
                        }
                    });
                });
            },

            async submitForm() {
                if (!this.validateStep(3)) {
                    return;
                }

                const form = document.getElementById('ilan-wizard-form');
                const submitButton = form.querySelector('button[type="submit"]');

                // ✅ Phase D/G: Check publish gate soft-blocking
                const qualityResult = window.ilanWizardQualityResult || null;
                const aiQualityComponent = document.querySelector('[x-data]')?.__x?.$data;
                const overrideBlock = aiQualityComponent?.overrideBlock || false;

                // Block if recommendation=block and no override
                if (qualityResult?.recommendation === 'block' && !overrideBlock) {
                    this.showNotification(
                        '⚠️ Kalite kontrolü engelliyor. Lütfen "Override" checkbox\'unu işaretleyin.',
                        'error'
                    );
                    return;
                }

                // ✅ Phase V0: Require kategori/yayın tipi in UI before publish
                const kategoriSlug = this.getSelectedKategoriSlug();
                const yayinTipiSlug = this.getSelectedYayinTipiSlug();

                if (!yayinTipiSlug) {
                    this.showNotification('Yayın tipi seçmeden devam edemezsiniz.', 'error');
                    return;
                }
                if (!kategoriSlug) {
                    this.showNotification('Kategori seçmeden devam edemezsiniz.', 'error');
                    return;
                }

                // Disable submit button
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.textContent = 'Kaydediliyor...';
                }

                // Price formatting
                const fiyatInput = document.getElementById('fiyat');
                const fiyatRawInput = document.getElementById('fiyat_raw');
                if (fiyatInput && fiyatRawInput) {
                    const rawValue = fiyatRawInput.value || fiyatInput.value.replace(/\./g, '');
                    fiyatInput.value = rawValue;
                }

                const formData = new FormData(form);

                // ✅ SAB SafetyNet: Manually append critical IDs if missed by FormData
                // This ensures that even if these fields are manipulated by JS or outside the form scope, they are included.
                const criticalFields = [
                    'ilan_sahibi_id',
                    'danisman_id',
                    'ilgili_kisi_id',
                    'sahibinden_id',
                    'emlakjet_id',
                    'hepsiemlak_id',
                    'zingat_id',
                    'hurriyetemlak_id',
                ];

                criticalFields.forEach((id) => {
                    const el = document.getElementById(id);
                    if (el && el.value) {
                        // Only append if not already in formData (or overwrite to be safe? overwrite is safer)
                        formData.set(id, el.value);
                    }
                });

                try {
                    // ✅ Phase 23: Commit Draft (A5)
                    const draftId = window.autoSaveManager?.state?.draftId;
                    const commitUrl = draftId
                        ? `/admin/ilanlar/draft/${draftId}/commit`
                        : form.action;

                    // ✅ No Raw Fetch Policy: wizardFetch SSOT
                    const {
                        response: createResponse,
                        data: createData,
                        ok: createOk,
                    } = await wizardFetch(commitUrl, { method: 'POST', body: formData });

                    if (!createOk) {
                        const errorData = createData || { message: 'Bir hata oluştu' };
                        if (errorData.errors) {
                            Object.keys(errorData.errors).forEach((field) => {
                                const fieldElement = form.querySelector(`[name="${field}"]`);
                                if (fieldElement)
                                    this.showFieldError(fieldElement, errorData.errors[field][0]);
                            });
                            this.showNotification('Lütfen form hatalarını düzeltin', 'error');
                        } else {
                            this.showNotification(errorData.message || 'Bir hata oluştu', 'error');
                        }
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.textContent = '✅ Yayınla';
                        }
                        return;
                    }

                    const ilanId = createData.data?.ilan_id || createData.id;

                    // ✅ Sprint Plan A5: Set ilan_id to WizardState
                    if (!window.WizardState) window.WizardState = {};
                    window.WizardState.ilan_id = ilanId;

                    if (window.autoSaveManager) window.autoSaveManager.clearDraft();

                    // Step 2: Call Publish Gate
                    if (submitButton) submitButton.textContent = 'Yayınlanıyor...';

                    const draftFeatures = this.collectDraftFeatures
                        ? this.collectDraftFeatures()
                        : {};
                    const publishEndpoint = `/admin/ilanlar/${ilanId}/publish`;

                    // ✅ No Raw Fetch Policy: wizardFetch SSOT
                    const {
                        response: publishResponse,
                        data: publishResult,
                        ok: publishOk,
                    } = await wizardFetch(publishEndpoint, {
                        method: 'POST',
                        body: JSON.stringify({
                            override: overrideBlock,
                            draft_features: draftFeatures,
                            kategori_slug: kategoriSlug,
                            yayin_tipi_slug: yayinTipiSlug,
                            ilan: {
                                baslik: document.getElementById('baslik')?.value || '',
                                aciklama: document.getElementById('aciklama')?.value || '',
                                fiyat: document.getElementById('fiyat')?.value || '',
                                para_birimi: document.getElementById('para_birimi')?.value || 'TRY',
                                il_id: document.getElementById('il_id')?.value || '',
                                ilce_id: document.getElementById('ilce_id')?.value || '',
                                mahalle_id: document.getElementById('mahalle_id')?.value || '',
                            },
                        }),
                    });

                    if (publishOk && publishResult.success) {
                        this.showNotification('✅ İlan başarıyla yayınlandı!', 'success');
                        setTimeout(() => {
                            window.location.href =
                                publishResult.data?.redirect || `/admin/ilanlar/${ilanId}`;
                        }, 1500);
                    } else {
                        this.showNotification(
                            `⚠️ İlan taslak olarak kaydedildi: ${publishResult.message || 'Yayınlama başarısız'}`,
                            'warning'
                        );
                        setTimeout(() => {
                            window.location.href = `/admin/ilanlar/${ilanId}/edit`;
                        }, 1500);
                    }
                } catch (error) {
                    console.error('Wizard submission error:', error);
                    this.showNotification('Sistem hatası oluştu. Lütfen tekrar deneyin.', 'error');
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = '✅ Yayınla';
                    }
                }
            },

            // Photo Upload Functions
            initPhotoUpload() {
                const uploadArea = document.getElementById('photo-upload-area');
                const fileInput = document.getElementById('fotograflar');
                if (!uploadArea || !fileInput) return;

                // Drag & Drop events
                uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadArea.classList.add('drag-over');
                });

                uploadArea.addEventListener('dragleave', () => {
                    uploadArea.classList.remove('drag-over');
                });

                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.classList.remove('drag-over');

                    const files = Array.from(e.dataTransfer.files).filter((file) =>
                        file.type.startsWith('image/')
                    );

                    if (files.length > 0) {
                        this.handlePhotoFiles(files);
                    }
                });

                // Click to upload
                uploadArea.addEventListener('click', (e) => {
                    if (e.target.closest('label')) return;
                    fileInput.click();
                });
            },

            handlePhotoFiles(files) {
                const fileInput = document.getElementById('fotograflar');
                const previewContainer = document.getElementById('photo-preview');

                if (!fileInput || !previewContainer) return;

                // Mevcut dosyaları al
                const existingFiles = Array.from(fileInput.files || []);
                const newFiles = [...existingFiles, ...files];

                // DataTransfer ile yeni dosya listesini oluştur
                const dataTransfer = new window.DataTransfer();
                newFiles.forEach((file) => dataTransfer.items.add(file));
                fileInput.files = dataTransfer.files;

                // Preview göster
                this.updatePhotoPreview(newFiles);
            },

            updatePhotoPreview(files) {
                const previewContainer = document.getElementById('photo-preview');
                if (!previewContainer) return;

                previewContainer.innerHTML = '';

                files.forEach((file, index) => {
                    const reader = new window.FileReader();
                    reader.onload = (e) => {
                        const div = document.createElement('div');
                        div.className = 'relative group';
                        div.innerHTML = `
                            <img src="${e.target.result}" alt="Preview ${index + 1}"
                                class="w-full h-32 object-cover rounded-lg border border-gray-200 dark:border-slate-700">
                            <button type="button" onclick="removePhoto(${index})"
                                class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            <div class="absolute bottom-1 left-1 bg-black/50 text-white text-xs px-2 py-1 rounded">
                                ${(file.size / 1024 / 1024).toFixed(2)} MB
                            </div>
                        `;
                        previewContainer.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                });
            },
        };
    };

    // Global photo functions (for onclick handlers)
    window.removePhoto = function (index) {
        const fileInput = document.getElementById('fotograflar');
        if (!fileInput) return;

        const files = Array.from(fileInput.files);
        files.splice(index, 1);

        const dataTransfer = new window.DataTransfer();
        files.forEach((file) => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;

        // Update preview
        const wizard = document.querySelector('[x-data*="ilanWizard"]');
        if (wizard && wizard._x_dataStack && wizard._x_dataStack[0]) {
            wizard._x_dataStack[0].updatePhotoPreview(files);
        }
    };

    window.handlePhotoSelection = function (event) {
        const fileInput = event.target;
        const files = Array.from(fileInput.files || []);

        const wizard = document.querySelector('[x-data*="ilanWizard"]');
        if (wizard && wizard._x_dataStack && wizard._x_dataStack[0]) {
            wizard._x_dataStack[0].handlePhotoFiles(files);
        }
    };

    /**
     * Step 2 Dynamic Fields Loader
     * Context7: Field Dependencies API ile dinamik alanlar
     */
    class Step2DynamicFieldsLoader {
        constructor() {
            this.container = null;
            this.currentFields = [];
            this.requiredFieldSlugs = [];
        }

        init() {
            // Container'ı bul (with retry guard for reliability)
            let retries = 0;
            const findContainer = () => {
                this.container = document.getElementById('step2-dynamic-fields-container');

                if (!this.container && retries < 5) {
                    retries++;
                    console.info(`[WIZARD] UPS container beklemede (Deneme: ${retries}/5)`);
                    setTimeout(findContainer, 500);
                    return;
                }

                if (!this.container) {
                    // Create if missing but needed (Phase 24 fallback)
                    const detailsSection = document.querySelector('[x-data*="categoryType"]');
                    if (detailsSection) {
                        this.container = document.createElement('div');
                        this.container.id = 'step2-dynamic-fields-container';
                        this.container.className = 'mt-6';
                        detailsSection.parentNode.insertBefore(
                            this.container,
                            detailsSection.nextSibling
                        );
                        console.log('[WIZARD] phase=container action=created_dynamically meta=', {
                            id: 'step2-dynamic-fields-container',
                        });
                    }
                }

                if (this.container) {
                    this.setupEventListeners();
                } else {
                    console.warn('[WIZARD] UPS container bulunamadı, Step2 loader iptal edildi.');
                }
            };

            findContainer();
        }

        /**
         * Event listener'ları kur
         */
        setupEventListeners() {
            // Unify with SSOT lifecycle
            document.addEventListener('wizard-context-applied', (e) => {
                const ssot = e.detail.context;
                if (!ssot || !ssot.features) return;

                console.info(
                    '[WIZARD] context + component: Step2DynamicFieldsLoader syncing from SSOT'
                );
                this.renderFeaturesFromSSOT(ssot.features);
            });
        }

        /**
         * Render features directly from SSOT data
         */
        renderFeaturesFromSSOT(featuresSSOT) {
            if (!featuresSSOT || !featuresSSOT.feature_schema) {
                this.showEmptyState(
                    'Özellik bulunamadı',
                    'Bu kategori için özellik tanımlanmamış.'
                );
                return;
            }

            const featureGroups = featuresSSOT.feature_groups || [];
            const featureSchema = featuresSSOT.feature_schema || {};

            // Convert SSOT features to groups expected by renderFieldGroups
            const groups = featureGroups
                .map((group) => {
                    const groupFields = Object.values(featureSchema).filter((f) => {
                        const binding = featuresSSOT.ups_bindings[f.slug];
                        return (
                            binding &&
                            (binding.group === group.name || binding.group_slug === group.slug)
                        );
                    });

                    return {
                        name: group.name,
                        slug: group.slug,
                        fields: groupFields,
                    };
                })
                .filter((g) => g.fields.length > 0);

            // If no groups found but features exist, put all in a general group
            if (groups.length === 0 && Object.keys(featureSchema).length > 0) {
                groups.push({
                    name: 'Genel Özellikler',
                    slug: 'genel',
                    fields: Object.values(featureSchema),
                });
            }

            this.renderFieldGroups(groups);

            // Update required field slugs for validation
            this.requiredFieldSlugs = Object.values(featureSchema)
                .filter((f) => f.required)
                .map((f) => f.slug);

            // Notify validation logic
            document.dispatchEvent(
                new window.CustomEvent('step2-dynamic-fields-loaded', {
                    detail: { requiredFields: this.requiredFieldSlugs },
                })
            );
        }

        /**
         * Clean up container
         */
        clearFields() {
            if (this.container) {
                this.container.innerHTML = '';
            }
        }

        /**
         * Field gruplarını render et
         */
        renderFieldGroups(groups) {
            if (!this.container) return;

            // ✅ FIX: Array kontrolü ekle
            if (!Array.isArray(groups)) {
                console.error('renderFieldGroups: groups bir array değil:', groups);
                this.showError('Veri formatı hatası');
                return;
            }

            // ✅ Dedup: Aynı slug'lı alanların tekrarlı renderını engelle
            const seen = new Set();
            let dedupedGroups = groups
                .map((g) => {
                    const fields = Array.isArray(g.fields)
                        ? g.fields.filter((f) => {
                              const slug = f?.slug;
                              if (!slug) return false;
                              if (seen.has(slug)) return false;
                              seen.add(slug);
                              return true;
                          })
                        : [];
                    return { ...g, fields };
                })
                .filter((g) => g.fields.length > 0);

            // ✅ FIX: Satılık yayın tipinde yazlık kiralama alanlarını gizle
            const yayinTipiSelect = document.getElementById('junction_id');
            let yayinTipiSlug = null;
            if (yayinTipiSelect && yayinTipiSelect.value) {
                const opt = yayinTipiSelect.options[yayinTipiSelect.selectedIndex];
                yayinTipiSlug =
                    opt?.getAttribute('data-slug') || opt?.text?.trim().toLowerCase() || null;
            }

            const yazlikKiralamaFields = [
                'maksimum_misafir',
                'max_misafir',
                'min_konaklama',
                'minimum_konaklama',
                'check_in',
                'check_in_saati',
                'check_out',
                'check_out_saati',
                'rezervasyon_tipi',
                'iptal_politikasi',
                'gunluk_fiyat',
            ];

            if (yayinTipiSlug && ['satilik', 'satılık'].includes(yayinTipiSlug)) {
                dedupedGroups = dedupedGroups
                    .map((group) => {
                        const filteredFields = group.fields.filter((field) => {
                            const fieldSlug = (field.slug || '').toLowerCase();
                            return !yazlikKiralamaFields.some((ykf) => fieldSlug.includes(ykf));
                        });
                        return { ...group, fields: filteredFields };
                    })
                    .filter((g) => g.fields.length > 0);
            }

            if (!dedupedGroups || dedupedGroups.length === 0) {
                this.container.innerHTML = `
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6 text-center">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            ℹ️ Bu kategori için özel detay alanı bulunmuyor.
                        </p>
                    </div>
                `;
                return;
            }

            let html = '';

            dedupedGroups.forEach((group) => {
                html += this.renderFieldGroup(group);
            });

            this.container.innerHTML = html;

            if (
                yayinTipiSlug &&
                ['sezonluk', 'yazlik-kiralik', 'gunluk', 'haftalik', 'aylik'].includes(
                    yayinTipiSlug
                )
            ) {
                this.renderSmartPricingPanel();
                this.initSmartPricingListeners();
                this.updateSmartPricingSummary();
                this.initOccupancyDefaultAuto();
            }
            const ctxEl = document.getElementById('wizard-context');
            const kategoriSlug = ctxEl?.getAttribute('data-kategori-slug') || '';
            const k = String(kategoriSlug || '').toLowerCase();
            if (
                k.includes('ticari') ||
                k.includes('isyeri') ||
                k.includes('ofis') ||
                k.includes('dukkan') ||
                k.includes('magaza')
            ) {
                this.renderCommercialROIPanel();
                this.initCommercialROIListeners();
                this.updateCommercialROISummary();
            }
            if (k.includes('arsa') || k.includes('tarla') || k.includes('arazi')) {
                this.renderLandCalculatorPanel();
                this.initLandCalculatorListeners();
                this.updateLandCalculatorSummary();
            }
            this.renderCortexInvestmentSummaryPanel();
            this.initCortexInvestmentSummaryListeners();
            this.updateCortexInvestmentSummary();
        }

        /**
         * Tek bir field grubunu render et
         */
        renderFieldGroup(group) {
            const icon = group.icon || '📋';
            const name = group.name || 'Genel';

            let html = `
                <div class="bg-white rounded-xl border border-gray-200 shadow-lg p-6 mb-6 dark:bg-slate-900 dark:border-slate-700">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">${icon}</span>
                        </div>
                        <div>
                            <h4 class="text-base font-semibold text-gray-900 dark:text-slate-100">${name}</h4>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            `;

            group.fields.forEach((field) => {
                html += this.renderField(field);
            });

            html += `
                    </div>
                </div>
            `;

            return html;
        }

        /**
         * Tek bir field render et (FieldDependency formatına göre)
         */
        renderField(field) {
            const fieldName = field.slug;
            const fieldId = `field_dep_${field.slug}`;
            const required = field.required ? '<span class="text-red-500">*</span>' : '';
            const requiredAttr = field.required ? 'required' : '';

            let html = '<div>';

            // Label
            html += `<label for="${fieldId}" class="block text-[13px] font-bold text-gray-700 dark:text-slate-300 mb-2 uppercase tracking-tight">${field.name}${required}</label>`;

            // Field type'a göre input
            switch (field.type) {
                case 'hour-picker':
                    html += `
                        <div x-data="{
                            open: false,
                            selectedHour: '14',
                            selectedMinute: '00',
                            hours: Array.from({length: 24}, (_, i) => i.toString().padStart(2, '0')),
                            minutes: ['00', '15', '30', '45'],
                            get displayValue() { return \`\${this.selectedHour}:\${this.selectedMinute}\`; }
                        }"@click.away="open = false"class="relative">
                            <div class="relative">
                                <input type="text" id="${fieldId}" name="${fieldName}" x-model="displayValue" @click="open = !open" readonly
                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-black focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 cursor-pointer transition-all duration-300 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    ${requiredAttr}>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                            </div>
                            <div x-show="open" x-transition class="absolute z-50 mt-2 w-64 bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-gray-100 dark:border-slate-700 p-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <div class="text-[10px] font-bold text-gray-400 mb-2 text-center dark:text-slate-600">SAAT</div>
                                        <div class="grid grid-cols-4 gap-1 h-32 overflow-y-auto no-scrollbar">
                                            <template x-for="h in hours" :key="h">
                                                <button type="button" @click="selectedHour = h; if(selectedMinute) open = false"
                                                    :class="selectedHour === h ? 'bg-blue-600 text-white' : 'hover:bg-blue-50 dark:hover:bg-blue-900/40 text-gray-700' dark:text-slate-300"
                                                    class="px-1 py-1.5 text-xs rounded-lg transition-colors" x-text="h"></button>
                                            </template>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-[10px] font-bold text-gray-400 mb-2 text-center dark:text-slate-600">DAKİKA</div>
                                        <div class="flex flex-col gap-1">
                                            <template x-for="m in minutes" :key="m">
                                                <button type="button" @click="selectedMinute = m; open = false"
                                                    :class="selectedMinute === m ? 'bg-blue-600 text-white' : 'hover:bg-blue-50 dark:hover:bg-blue-900/40 text-gray-700' dark:text-slate-300"
                                                    class="px-1 py-1.5 text-xs rounded-lg transition-colors" x-text="m"></button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    break;

                case 'visual-chips':
                case 'select':
                    // Eğer select tipindeyse ama chip olması isteniyorsa (opsiyonel mantık eklenebilir)
                    // Şimdilik select'leri daha şık bir chip yapısına dönüştürelim
                    if (field.options && field.options.length <= 6) {
                        html += `<div x-data="{ selected: '' }" class="flex flex-wrap gap-2">`;
                        html += `<input type="hidden" name="${fieldName}" x-model="selected" ${requiredAttr}>`;
                        field.options.forEach((opt) => {
                            const value = typeof opt === 'object' ? opt.value : opt;
                            const label = typeof opt === 'object' ? opt.label : opt;
                            html += `
                                <button type="button" @click="selected = '${value}'"
                                    :class="selected === '${value}' ? 'bg-blue-600 text-white border-blue-600 shadow-lg scale-105' : 'bg-white dark:bg-gray-800 text-gray-700 border-gray-200 dark:border-gray-700 hover:border-blue-400 shadow-sm' dark:text-slate-300 dark:border-slate-700"
                                    class="px-4 py-2 text-sm font-bold border-2 rounded-xl transition-all duration-300 transform active:scale-95">
                                    ${label}
                                </button>
                            `;
                        });
                        html += `</div>`;
                    } else {
                        // Standart Premium Select
                        html += `<div class="relative">
                            <select id="${fieldId}" name="${fieldName}" class="appearance-none w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-black focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all duration-300 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" ${requiredAttr}>
                                <option value="">Seçin...</option>`;
                        if (field.options && Array.isArray(field.options)) {
                            field.options.forEach((opt) => {
                                const value = typeof opt === 'object' ? opt.value : opt;
                                const label = typeof opt === 'object' ? opt.label : opt;
                                html += `<option value="${value}">${label}</option>`;
                            });
                        }
                        html += `</select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-gray-400 dark:text-slate-600">
                                <i class="fas fa-chevron-down text-[10px]"></i>
                            </div>
                        </div>`;
                    }
                    break;

                case 'text':
                case 'email':
                case 'url':
                    html += `<input type="${field.type}" id="${fieldId}" name="${fieldName}" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-black focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all duration-300 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"`;
                    if (field.placeholder) html += `placeholder="${field.placeholder}"`;
                    if (requiredAttr) html += requiredAttr;
                    html += '>';
                    break;

                case 'number':
                    html += `<div class="relative">
                        <input type="number" id="${fieldId}" name="${fieldName}" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-black focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all duration-300 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"`;
                    if (field.placeholder) html += `placeholder="${field.placeholder}"`;
                    if (requiredAttr) html += requiredAttr;
                    if (field.unit) html += `data-unit="${field.unit}"`;
                    html += '>';
                    if (field.unit) {
                        html += `<div class="absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-bold text-gray-400 pointer-events-none dark:text-slate-600">${field.unit}</div>`;
                    }
                    html += '</div>';
                    break;

                case 'textarea':
                    html += `<textarea id="${fieldId}" name="${fieldName}" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-black focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all duration-300 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"`;
                    if (field.placeholder) html += `placeholder="${field.placeholder}"`;
                    if (requiredAttr) html += requiredAttr;
                    html += '></textarea>';
                    break;

                case 'checkbox':
                    html += '<div class="grid grid-cols-2 gap-3">';
                    if (field.options && Array.isArray(field.options)) {
                        field.options.forEach((opt, idx) => {
                            const value = typeof opt === 'object' ? opt.value : opt;
                            const label = typeof opt === 'object' ? opt.label : opt;
                            const checkboxId = `${fieldId}_${idx}`;
                            html += `
                                <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-gray-50 cursor-pointer transition-colors dark:border-slate-700">
                                    <input type="checkbox" id="${checkboxId}" name="${fieldName}[]" value="${value}" class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500 transition-all">
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-slate-300">${label}</span>
                                </label>
                            `;
                        });
                    }
                    html += '</div>';
                    break;

                case 'radio':
                    html += '<div class="grid grid-cols-2 gap-3">';
                    if (field.options && Array.isArray(field.options)) {
                        field.options.forEach((opt, idx) => {
                            const value = typeof opt === 'object' ? opt.value : opt;
                            const label = typeof opt === 'object' ? opt.label : opt;
                            const radioId = `${fieldId}_${idx}`;
                            html += `
                                <label class="flex items-center p-3 border border-gray-200 rounded-xl hover:bg-gray-50 cursor-pointer transition-colors dark:border-slate-700">
                                    <input type="radio" id="${radioId}" name="${fieldName}" value="${value}" class="w-4 h-4 text-blue-600 focus:ring-blue-500 transition-all"${requiredAttr}>
                                    <span class="ml-3 text-sm font-medium text-gray-700 dark:text-slate-300">${label}</span>
                                </label>
                            `;
                        });
                    }
                    html += '</div>';
                    break;

                default:
                    html += `<input type="text" id="${fieldId}" name="${fieldName}" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl bg-white text-black focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all duration-300 shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"`;
                    if (requiredAttr) html += requiredAttr;
                    html += '>';
            }

            // Help text
            if (field.help_text) {
                html += `<p class="mt-2 text-xs font-medium text-gray-500 flex items-center gap-1 dark:text-slate-500">
                    <i class="fas fa-info-circle opacity-50"></i>
                    ${field.help_text}
                </p>`;
            }

            html += '</div>';

            return html;
        }

        /**
         * ✅ Client-side seasonal (Airbnb) fallback field groups
         */
        buildSeasonalAirbnbFallbackGroups() {
            const group = {
                icon: '🛏️',
                name: 'Airbnb Özellikleri',
                fields: [
                    {
                        slug: 'gunluk_fiyat',
                        name: 'Günlük Fiyat',
                        type: 'number',
                        required: true,
                        unit: '₺',
                    },
                    {
                        slug: 'haftalik_fiyat',
                        name: 'Haftalık Fiyat',
                        type: 'number',
                        required: false,
                        unit: '₺',
                    },
                    {
                        slug: 'aylik_fiyat',
                        name: 'Aylık Fiyat',
                        type: 'number',
                        required: false,
                        unit: '₺',
                    },
                    {
                        slug: 'min_konaklama',
                        name: 'Minimum Konaklama (Gece)',
                        type: 'number',
                        required: true,
                    },
                    {
                        slug: 'max_konaklama',
                        name: 'Maksimum Konaklama (Gece)',
                        type: 'number',
                        required: false,
                    },
                    {
                        slug: 'giris_saati',
                        name: 'Giriş Saati',
                        type: 'hour-picker',
                        required: true,
                        placeholder: '14:00',
                    },
                    {
                        slug: 'cikis_saati',
                        name: 'Çıkış Saati',
                        type: 'hour-picker',
                        required: true,
                        placeholder: '12:00',
                    },
                    {
                        slug: 'temizlik_ucreti',
                        name: 'Temizlik Ücreti',
                        type: 'number',
                        required: false,
                        unit: '₺',
                    },
                    {
                        slug: 'depozito',
                        name: 'Depozito',
                        type: 'number',
                        required: false,
                        unit: '₺',
                    },
                    {
                        slug: 'iade_kurali',
                        name: 'İade Kuralı',
                        type: 'select',
                        required: true,
                        options: ['Esnek', 'Orta', 'Katı'],
                    },
                    {
                        slug: 'ev_kurallari',
                        name: 'Ev Kuralları',
                        type: 'textarea',
                        required: false,
                        placeholder: 'Sigara yok, parti yok...',
                    },
                    {
                        slug: 'misafir_kapasitesi',
                        name: 'Misafir Kapasitesi',
                        type: 'number',
                        required: true,
                    },
                    { slug: 'yatak_sayisi', name: 'Yatak Sayısı', type: 'number', required: false },
                    { slug: 'oda_sayisi', name: 'Oda Sayısı', type: 'number', required: true },
                    {
                        slug: 'evcil_hayvan',
                        name: 'Evcil Hayvan',
                        type: 'select',
                        required: false,
                        options: ['İzin Verilmez', 'Küçük ırklar', 'Tüm ırklar'],
                    },
                    {
                        slug: 'sezon_tipi',
                        name: 'Sezon Tipi',
                        type: 'select',
                        required: true,
                        options: ['Yaz', 'Kış', 'Tüm Yıl'],
                    },
                    {
                        slug: 'sezon_baslangic',
                        name: 'Sezon Başlangıç',
                        type: 'text',
                        required: false,
                        placeholder: '01-06',
                    },
                    {
                        slug: 'sezon_bitis',
                        name: 'Sezon Bitiş',
                        type: 'text',
                        required: false,
                        placeholder: '30-09',
                    },
                ],
            };
            return [group];
        }

        renderSmartPricingPanel() {
            const panelId = 'smart-pricing-panel';
            let panel = document.getElementById(panelId);
            if (!panel) {
                panel = document.createElement('div');
                panel.id = panelId;
                panel.className =
                    'bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg p-6 mb-6 dark:bg-slate-900 dark:border-slate-700';
                const title = document.createElement('div');
                title.className = 'flex items-center gap-3 mb-4';
                title.innerHTML =
                    '<div class="w-10 h-10 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center"><span class="text-2xl">💰</span></div><div><h4 class="text-base font-semibold text-gray-900 dark:text-slate-100">Akıllı Fiyat ve ROI</h4></div> dark:text-slate-100';
                const body = document.createElement('div');
                body.innerHTML =
                    '<div class="grid grid-cols-1 lg:grid-cols-3 gap-4"><div><div class="text-sm text-gray-500 dark:text-slate-500">Günlük Fiyat</div><div id="sp_daily" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-sm text-gray-500 dark:text-slate-500">Sezonluk Fiyat</div><div id="sp_seasonal" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-sm text-gray-500 dark:text-slate-500">Amortisman (tahmini)</div><div id="sp_roi" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div><div id="sp_basis" class="text-xs text-gray-500 dark:text-slate-500">-</div></div></div><div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4"><div><label class="text-sm text-gray-500 dark:text-slate-500">Doluluk Oranı</label><input id="sp_occ" type="range" min="0.6" max="0.9" step="0.01" value="0.7" class="w-full"><div class="text-xs text-gray-500 dark:text-slate-500"><span id="sp_occ_label">70%</span></div></div><div><label class="text-sm text-gray-500 dark:text-slate-500">Sezon Süresi (gün)</label><input id="sp_days" type="range" min="60" max="150" step="1" value="90" class="w-full"><div class="text-xs text-gray-500 dark:text-slate-500"><span id="sp_days_label">90 gün</span></div></div></div> dark:text-slate-100';
                panel.appendChild(title);
                panel.appendChild(body);
                this.container.parentNode.insertBefore(panel, this.container.nextSibling);
            }
        }

        initSmartPricingListeners() {
            const ids = [
                'field_dep_gunluk_fiyat',
                'field_dep_sezonluk_fiyat',
                'field_dep_satis_fiyati',
                'field_dep_sezon_baslangic',
                'field_dep_sezon_bitis',
            ];
            ids.forEach((id) => {
                const el = document.getElementById(id);
                if (el) {
                    el.addEventListener('input', () => this.updateSmartPricingSummary());
                    el.addEventListener('change', () => this.updateSmartPricingSummary());
                }
            });
            const occ = document.getElementById('sp_occ');
            const days = document.getElementById('sp_days');
            const occLabel = document.getElementById('sp_occ_label');
            const daysLabel = document.getElementById('sp_days_label');
            if (occ) {
                occ.addEventListener('input', () => {
                    if (occLabel)
                        occLabel.textContent = `${Math.round(parseFloat(occ.value) * 100)}%`;
                    this.updateSmartPricingSummary();
                });
            }
            if (days) {
                days.addEventListener('input', () => {
                    if (daysLabel) daysLabel.textContent = `${parseInt(days.value, 10)} gün`;
                    this.updateSmartPricingSummary();
                });
            }
        }

        initOccupancyDefaultAuto() {
            const applyFromBenchmark = async () => {
                const occSlider = document.getElementById('sp_occ');
                const occLabel = document.getElementById('sp_occ_label');
                if (!occSlider) return;
                const ilId = document.getElementById('il_id')?.value || null;
                const ilceId = document.getElementById('ilce_id')?.value || null;
                const kategoriSlug =
                    document.getElementById('wizard-context')?.getAttribute('data-kategori-slug') ||
                    null;
                const yayinTipiSlug =
                    document
                        .getElementById('wizard-context')
                        ?.getAttribute('data-yayin-tipi-slug') || null;
                const buildUrl = () => {
                    if (window.APIConfig?.analytics?.benchmark) {
                        return window.APIConfig.analytics.benchmark('occupancy', {
                            il_id: ilId,
                            ilce_id: ilceId,
                            kategori_slug: kategoriSlug,
                            yayin_tipi_slug: yayinTipiSlug,
                        });
                    }
                    const qs = [];
                    qs.push('metric=occupancy');
                    if (ilId) qs.push('il_id=' + encodeURIComponent(ilId));
                    if (ilceId) qs.push('ilce_id=' + encodeURIComponent(ilceId));
                    if (kategoriSlug) qs.push('kategori_slug=' + encodeURIComponent(kategoriSlug));
                    if (yayinTipiSlug)
                        qs.push('yayin_tipi_slug=' + encodeURIComponent(yayinTipiSlug));
                    return '/api/v1/analytics/benchmark?' + qs.join('&');
                };
                try {
                    // ✅ No Raw Fetch Policy: wizardFetch SSOT
                    const { data: j, ok: respOk } = await wizardFetch(buildUrl());
                    if (!respOk) return;
                    const avg = j?.data?.avg_occupancy ?? j?.avg_occupancy;
                    if (typeof avg === 'number' && avg > 0.5 && avg <= 0.95) {
                        occSlider.value = String(avg);
                        if (occLabel) occLabel.textContent = `${Math.round(avg * 100)}%`;
                        this.updateSmartPricingSummary();
                    }
                } catch {
                    // silent
                }
            };
            const ilEl = document.getElementById('il_id');
            const ilceEl = document.getElementById('ilce_id');
            if (ilEl) ilEl.addEventListener('change', applyFromBenchmark);
            if (ilceEl) ilceEl.addEventListener('change', applyFromBenchmark);
            applyFromBenchmark();
        }

        updateSmartPricingSummary() {
            const v = (name) => {
                const el =
                    document.querySelector(`[name="${name}"]`) ||
                    document.getElementById(`field_dep_${name}`);
                if (!el) return null;
                const raw = String(el.value || '')
                    .replace(/\./g, '')
                    .replace(/,/g, '.');
                const num = parseFloat(raw);
                return isNaN(num) ? null : num;
            };
            const daily = v('gunluk_fiyat');
            const seasonal = v('sezonluk_fiyat');
            const weekly = v('haftalik_fiyat');
            const monthly = v('aylik_fiyat');
            const sale = v('satis_fiyati') || v('fiyat');
            const sStart =
                document.querySelector('[name="sezon_baslangic"]')?.value ||
                document.getElementById('field_dep_sezon_baslangic')?.value ||
                '';
            const sEnd =
                document.querySelector('[name="sezon_bitis"]')?.value ||
                document.getElementById('field_dep_sezon_bitis')?.value ||
                '';
            let seasonDays = 90;
            const parseMD = (s) => {
                const m = String(s || '')
                    .trim()
                    .split(/[-/\\.]/);
                if (m.length >= 2) return { d: parseInt(m[0]), m: parseInt(m[1]) };
                return null;
            };
            const ps = parseMD(sStart);
            const pe = parseMD(sEnd);
            if (ps && pe && !isNaN(ps.d) && !isNaN(ps.m) && !isNaN(pe.d) && !isNaN(pe.m)) {
                const dm = (m, d) => m * 30 + d;
                const diff = dm(pe.m, pe.d) - dm(ps.m, ps.d);
                if (diff > 0 && diff < 366) seasonDays = diff;
            }
            const occSlider = document.getElementById('sp_occ');
            const daysSlider = document.getElementById('sp_days');
            const occ = occSlider ? parseFloat(occSlider.value || '0.7') : 0.7;
            seasonDays = daysSlider
                ? parseInt(daysSlider.value || `${seasonDays}`, 10)
                : seasonDays;
            let roiText = '-';
            if (sale && (daily || seasonal || weekly || monthly)) {
                let annualIncome = 0;
                if (seasonal) {
                    annualIncome = seasonal;
                } else if (daily) {
                    annualIncome = daily * seasonDays * occ;
                } else if (weekly) {
                    annualIncome = weekly * Math.max(1, Math.round(seasonDays / 7)) * occ;
                } else if (monthly) {
                    annualIncome = monthly * Math.max(1, Math.round(seasonDays / 30)) * occ;
                }
                if (annualIncome > 0) {
                    const years = sale / annualIncome;
                    roiText = `${years.toFixed(1)} yıl`;
                }
            }
            const setText = (id, val) => {
                const el = document.getElementById(id);
                if (el)
                    el.textContent =
                        val !== null && val !== undefined
                            ? typeof val === 'number'
                                ? new Intl.NumberFormat('tr-TR').format(val)
                                : val
                            : '-';
            };
            setText('sp_daily', daily);
            setText('sp_seasonal', seasonal);
            setText('sp_roi', roiText);
            let basis = '-';
            if (seasonal) {
                basis = 'Sezonluk fiyat üzerinden';
            } else if (daily) {
                basis = 'Günlük × gün × doluluk';
            } else if (weekly) {
                basis = 'Haftalık × hafta × doluluk';
            } else if (monthly) {
                basis = 'Aylık × ay × doluluk';
            }
            setText('sp_basis', basis);
        }

        renderCommercialROIPanel() {
            const pid = 'commercial-roi-panel';
            let el = document.getElementById(pid);
            if (!el) {
                el = document.createElement('div');
                el.id = pid;
                el.className =
                    'bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg p-6 mb-6 dark:bg-slate-900 dark:border-slate-700';
                el.innerHTML =
                    '<div class="flex items-center gap-3 mb-4"><div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center"><span class="text-2xl">🏢</span></div><div><h4 class="text-base font-semibold text-gray-900 dark:text-slate-100">Ticari ROI</h4></div></div><div class="grid grid-cols-1 lg:grid-cols-4 gap-4"><div><div class="text-sm text-gray-500 dark:text-slate-500">Aylık Kira</div><div id="cr_rent" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-sm text-gray-500 dark:text-slate-500">Satış Fiyatı</div><div id="cr_sale" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-sm text-gray-500 dark:text-slate-500">Amortisman</div><div id="cr_months" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div><div id="cr_years" class="text-xs text-gray-500 dark:text-slate-500">-</div></div><div><div class="text-sm text-gray-500 dark:text-slate-500">Yield</div><div id="cr_yield" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div><div id="cr_tag" class="text-xs text-gray-500 dark:text-slate-500">-</div></div></div> dark:text-slate-100';
                this.container.parentNode.insertBefore(el, this.container.nextSibling);
            }
        }

        initCommercialROIListeners() {
            ['kira_bedeli', 'satis_fiyati', 'fiyat'].forEach((name) => {
                const el =
                    document.querySelector(`[name="${name}"]`) ||
                    document.getElementById(`field_dep_${name}`);
                if (el) {
                    el.addEventListener('input', () => this.updateCommercialROISummary());
                    el.addEventListener('change', () => this.updateCommercialROISummary());
                }
            });
        }

        updateCommercialROISummary() {
            const num = (sel) => {
                const el =
                    document.querySelector(`[name="${sel}"]`) ||
                    document.getElementById(`field_dep_${sel}`);
                if (!el) return null;
                const raw = String(el.value || '')
                    .replace(/\./g, '')
                    .replace(/,/g, '.');
                const v = parseFloat(raw);
                return isNaN(v) ? null : v;
            };
            const rent = num('aylik_kira') || num('kira_bedeli');
            const sale = num('satis_fiyati') || num('fiyat');
            const fmt = (v) =>
                v === null || v === undefined ? '-' : new Intl.NumberFormat('tr-TR').format(v);
            const monthsEl = document.getElementById('cr_months');
            const yearsEl = document.getElementById('cr_years');
            const yieldEl = document.getElementById('cr_yield');
            const tagEl = document.getElementById('cr_tag');
            const rentEl = document.getElementById('cr_rent');
            const saleEl = document.getElementById('cr_sale');
            if (rentEl) rentEl.textContent = fmt(rent);
            if (saleEl) saleEl.textContent = fmt(sale);
            let months = null;
            let years = null;
            let yi = null;
            let tag = '-';
            if (rent && sale && rent > 0) {
                months = sale / rent;
                years = months / 12;
                yi = ((rent * 12) / sale) * 100;
                const m = Math.round(months);
                if (m >= 180 && m <= 220) tag = 'Hızlı Geri Dönüş';
                if (m >= 240) tag = 'Standart Yatırım';
            }
            if (monthsEl) monthsEl.textContent = months ? `${Math.round(months)} ay` : '-';
            if (yearsEl) yearsEl.textContent = years ? `${years.toFixed(1)} yıl` : '-';
            if (yieldEl) yieldEl.textContent = yi ? `${yi.toFixed(1)}%` : '-';
            if (tagEl) tagEl.textContent = tag;
        }

        renderLandCalculatorPanel() {
            const pid = 'land-calculator-panel';
            let el = document.getElementById(pid);
            if (!el) {
                el = document.createElement('div');
                el.id = pid;
                el.className =
                    'bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-lg p-6 mb-6 dark:bg-slate-900 dark:border-slate-700';
                el.innerHTML =
                    '<div class="flex items-center gap-3 mb-4"><div class="w-10 h-10 bg-sky-100 dark:bg-sky-900/30 rounded-lg flex items-center justify-center"><span class="text-2xl">🧮</span></div><div><h4 class="text-base font-semibold text-gray-900 dark:text-slate-100">Arsa m² Hesaplayıcı</h4></div></div><div class="grid grid-cols-1 lg:grid-cols-4 gap-4"><div><div class="text-sm text-gray-500 dark:text-slate-500">₺/m²</div><div id="lc_price_m2" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-sm text-gray-500 dark:text-slate-500">Zemin Oturumu</div><div id="lc_taks_area" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-sm text-gray-500 dark:text-slate-500">Toplam İnşaat Alanı</div><div id="lc_kaks_area" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-sm text-gray-500 dark:text-slate-500">Potansiyel Skoru</div><div id="lc_score" class="text-lg font-semibold text-gray-900 dark:text-slate-100">-</div></div></div> dark:text-slate-100';
                this.container.parentNode.insertBefore(el, this.container.nextSibling);
            }
        }

        initLandCalculatorListeners() {
            ['alan_m2', 'fiyat', 'taks', 'kaks'].forEach((name) => {
                const el =
                    document.querySelector(`[name="${name}"]`) ||
                    document.getElementById(`field_dep_${name}`);
                if (el) {
                    el.addEventListener('input', () => this.updateLandCalculatorSummary());
                    el.addEventListener('change', () => this.updateLandCalculatorSummary());
                }
            });
        }

        updateLandCalculatorSummary() {
            const val = (sel) => {
                const el =
                    document.querySelector(`[name="${sel}"]`) ||
                    document.getElementById(`field_dep_${sel}`);
                if (!el) return null;
                const raw = String(el.value || '')
                    .replace(/\./g, '')
                    .replace(/,/g, '.');
                const v = parseFloat(raw);
                return isNaN(v) ? null : v;
            };
            const m2 = val('alan_m2');
            const price = val('fiyat');
            const taks = val('taks');
            const kaks = val('kaks');
            const fmt = (v) =>
                v === null || v === undefined ? '-' : new Intl.NumberFormat('tr-TR').format(v);
            const pm2El = document.getElementById('lc_price_m2');
            const taksEl = document.getElementById('lc_taks_area');
            const kaksEl = document.getElementById('lc_kaks_area');
            const scoreEl = document.getElementById('lc_score');
            let pm2 = null;
            let taksArea = null;
            let kaksArea = null;
            let score = null;
            if (m2 && price && m2 > 0) pm2 = price / m2;
            if (m2 && taks) taksArea = m2 * taks;
            if (m2 && kaks) kaksArea = m2 * kaks;
            if (pm2) {
                const invPriceFactor = Math.max(0, Math.min(10, 100000 / pm2));
                const imarFactor = Math.min(10, (taks || 0) * 10 * 0.5 + (kaks || 0) * 10 * 0.5);
                score = Math.max(1, Math.min(10, invPriceFactor * 0.4 + imarFactor * 0.6));
            }
            if (pm2El) pm2El.textContent = pm2 ? fmt(Math.round(pm2)) : '-';
            if (taksEl) taksEl.textContent = taksArea ? fmt(Math.round(taksArea)) : '-';
            if (kaksEl) kaksEl.textContent = kaksArea ? fmt(Math.round(kaksArea)) : '-';
            if (scoreEl) scoreEl.textContent = score ? `${score.toFixed(1)}/10` : '-';
            this.updateCortexInvestmentSummary();
        }

        renderCortexInvestmentSummaryPanel() {
            const pid = 'cortex-summary-panel';
            let el = document.getElementById(pid);
            if (!el) {
                el = document.createElement('div');
                el.id = pid;
                el.className =
                    'bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-xl p-6 mb-6';
                el.innerHTML =
                    '<div class="flex items-center gap-3 mb-2"><div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900/40 rounded-lg flex items-center justify-center"><span class="text-2xl">🧠</span></div><div><h4 class="text-base font-semibold text-gray-900 dark:text-slate-100">Cortex Yatırım Özeti</h4></div></div><div class="grid grid-cols-1 lg:grid-cols-5 gap-4"><div><div class="text-xs text-gray-600 dark:text-slate-400">Sezonluk ROI</div><div id="cx_roi" class="text-sm font-semibold text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-xs text-gray-600 dark:text-slate-400">Ticari Yield</div><div id="cx_yield" class="text-sm font-semibold text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-xs text-gray-600 dark:text-slate-400">Arsa ₺/m²</div><div id="cx_pm2" class="text-sm font-semibold text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-xs text-gray-600 dark:text-slate-400">Bölge Kıyaslaması</div><div id="cx_bench" class="text-xs font-medium text-gray-900 dark:text-slate-100">-</div></div><div><div class="text-xs text-gray-600 dark:text-slate-400">Rozet</div><div id="cx_badge" class="text-sm font-semibold text-gray-900 dark:text-slate-100">-</div></div></div> dark:text-slate-100';
                this.container.parentNode.insertBefore(el, this.container);
            }
        }

        initCortexInvestmentSummaryListeners() {
            [
                'sp_occ',
                'sp_days',
                'field_dep_gunluk_fiyat',
                'field_dep_sezonluk_fiyat',
                'field_dep_satis_fiyati',
                'field_dep_sezon_baslangic',
                'field_dep_sezon_bitis',
                'aylik_kira',
                'kira_bedeli',
                'fiyat',
                'alan_m2',
                'taks',
                'kaks',
            ].forEach((id) => {
                const el = document.getElementById(id) || document.querySelector(`[name="${id}"]`);
                if (el) {
                    el.addEventListener('input', () => this.updateCortexInvestmentSummary());
                    el.addEventListener('change', () => this.updateCortexInvestmentSummary());
                }
            });
        }

        updateCortexInvestmentSummary() {
            const t = (id) => document.getElementById(id)?.textContent || '-';
            const roiTxt = t('sp_roi');
            const yieldTxt = t('cr_yield');
            const pm2Txt = t('lc_price_m2');
            const set = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.textContent = val || '-';
            };
            set('cx_roi', roiTxt);
            set('cx_yield', yieldTxt);
            set('cx_pm2', pm2Txt);
            const badge = this.computeCortexBadge(roiTxt, yieldTxt, pm2Txt);
            set('cx_badge', badge);
            window.ilanWizardQualityResult = {
                recommendation: badge === 'A+' || badge === 'A' ? 'allow' : 'warn',
                badge,
            };
            this.updateBenchmarkRow();
        }

        computeCortexBadge(roiTxt, yieldTxt, pm2Txt) {
            const numYears = (() => {
                const m = String(roiTxt || '').match(/([\d.]+)\s*yıl/);
                return m ? parseFloat(m[1]) : null;
            })();
            const yieldVal = (() => {
                const m = String(yieldTxt || '').match(/([\d.]+)%/);
                return m ? parseFloat(m[1]) : null;
            })();
            const pm2Val = (() => {
                const val = pm2Txt ? pm2Txt.replace(/\./g, '').replace(/,/g, '.') : '';
                const n = parseFloat(val);
                return isNaN(n) ? null : n;
            })();
            if (numYears !== null && numYears <= 10) return 'A+';
            if (yieldVal !== null && yieldVal >= 6) return 'A+';
            if (numYears !== null && numYears <= 15) return 'A';
            if (yieldVal !== null && yieldVal >= 4) return 'A';
            if (pm2Val !== null && pm2Val <= 10000) return 'A';
            return 'B';
        }

        updateBenchmarkRow() {
            const bEl = document.getElementById('cx_bench');
            if (!bEl) return;
            const ilId = document.getElementById('il_id')?.value || null;
            const ilceId = document.getElementById('ilce_id')?.value || null;
            const kategoriSlug =
                document.getElementById('wizard-context')?.getAttribute('data-kategori-slug') ||
                null;
            const yayinTipiSlug =
                document.getElementById('wizard-context')?.getAttribute('data-yayin-tipi-slug') ||
                null;
            const buildUrl = (metric) => {
                if (window.APIConfig?.analytics?.benchmark) {
                    return window.APIConfig.analytics.benchmark(metric, {
                        il_id: ilId,
                        ilce_id: ilceId,
                        kategori_slug: kategoriSlug,
                        yayin_tipi_slug: yayinTipiSlug,
                    });
                }
                const qs = [];
                qs.push(`metric=${encodeURIComponent(metric)}`);
                if (ilId) qs.push(`il_id=${encodeURIComponent(ilId)}`);
                if (ilceId) qs.push(`ilce_id=${encodeURIComponent(ilceId)}`);
                if (kategoriSlug) qs.push(`kategori_slug=${encodeURIComponent(kategoriSlug)}`);
                if (yayinTipiSlug) qs.push(`yayin_tipi_slug=${encodeURIComponent(yayinTipiSlug)}`);
                return `/api/v1/analytics/benchmark?${qs.join('&')}`;
            };
            const fmtPct = (d) => `${d > 0 ? '+' : ''}${Math.round(d)}%`;
            const tryUpdate = async () => {
                try {
                    // ✅ No Raw Fetch Policy: wizardFetch SSOT
                    const { data: pm2Data, ok: pm2Ok } = await wizardFetch(buildUrl('price_m2'));
                    const { data: amoData, ok: amoOk } = await wizardFetch(
                        buildUrl('amortization')
                    );
                    let text = '';
                    if (pm2Ok) {
                        const j = pm2Data;
                        const avg = j?.data?.avg ?? j?.avg;
                        const pm2Text = document.getElementById('cx_pm2')?.textContent || '';
                        const pm2Val = parseFloat((pm2Text || '').replace(/[^\d.]/g, ''));
                        if (avg && pm2Val) {
                            const diff = ((pm2Val - avg) / avg) * 100;
                            const arrow = diff > 0 ? '↑' : diff < 0 ? '↓' : '→';
                            text += `₺/m² bölge ort.: ${Math.round(avg)} (${arrow} ${fmtPct(diff)})`;
                        }
                    }
                    if (amoOk) {
                        const j2 = amoData;
                        const avgMonths = j2?.data?.avg_months ?? j2?.avg_months;
                        const monthsText = document.getElementById('cr_months')?.textContent || '';
                        const mMatch = monthsText.match(/(\d+)\s*ay/);
                        const mVal = mMatch ? parseInt(mMatch[1], 10) : null;
                        if (avgMonths && mVal) {
                            const diff2 = mVal - avgMonths;
                            text +=
                                (text ? '•' : '') +
                                `Amortisman bölge aralığı ~${avgMonths} ay (${diff2 >= 0 ? '+' : ''}${diff2} ay)`;
                        }
                    }
                    bEl.textContent = text || 'Veri bekleniyor';
                } catch {
                    bEl.textContent = 'Veri bekleniyor';
                }
            };
            tryUpdate();
        }

        showLoading(url = '', kategoriSlug = '', yayinTipi = '') {
            if (!this.container) return;
            this.container.innerHTML = `
                <div class="bg-white rounded-xl border border-gray-200 shadow-lg p-8 text-center dark:bg-slate-900 dark:border-slate-700">
                    <div class="flex items-center justify-center gap-3">
                        <svg class="animate-spin h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-gray-700 dark:text-slate-300">Detay alanları yükleniyor...</span>
                    </div>
                    <div class="mt-4 text-xs text-gray-500 dark:text-slate-500">
                        <p><strong>Slug:</strong> ${kategoriSlug || '-'}</p>
                        <p><strong>Yayın Tipi:</strong> ${yayinTipi || '-'}</p>
                        <p class="break-all"><strong>Endpoint:</strong> ${url || '-'}</p>
                    </div>
                </div>
            `;
        }

        showError(message) {
            if (!this.container) return;
            this.container.innerHTML = `
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-6 text-center">
                    <p class="text-sm text-red-700 dark:text-red-300">❌ ${message}</p>
                </div>
            `;
        }

        clearFields() {
            if (!this.container) return;
            this.container.innerHTML = '';
            this.requiredFieldSlugs = [];
        }

        /**
         * Required field slugs'ı döndür (validation için)
         */
        getRequiredFieldSlugs() {
            return this.requiredFieldSlugs;
        }
    }

    /**
     * ✅ AI Quality Check Alpine Component
     */
    window.aiQualityCheck = function () {
        return {
            loading: false,
            result: null,

            // Throttle/Backoff state
            backoffUntil: 0,

            async runQualityCheck() {
                // ✅ SSOT Backoff Check
                if (Date.now() < this.backoffUntil) {
                    console.log(
                        `⏸️ Quality Check suspended until ${new Date(this.backoffUntil).toLocaleTimeString()}`
                    );
                    return;
                }

                this.loading = true;
                this.result = null;

                try {
                    const form = document.getElementById('ilan-wizard-form');
                    const formData = new FormData(form);

                    const baslik = formData.get('baslik');
                    const aciklama = formData.get('aciklama');

                    // ✅ Draft Mode Guard: ID yoksa kalite kontrolü yapma (400 spam önleme)
                    const ilanId =
                        window?.WizardState?.ilan_id ||
                        formData.get('ilan_id') ||
                        document.querySelector('input[name="ilan_id"]')?.value;

                    if (!ilanId) {
                        console.log('ℹ️ Quality Check: Skipping (draft mode, no ilan_id)');
                        this.loading = false;
                        return;
                    }

                    if (!baslik || !aciklama) {
                        window.showToast?.(
                            'Başlık ve açıklama alanları analize başlamak için gereklidir.',
                            'warning'
                        );
                        this.loading = false;
                        return;
                    }

                    const url =
                        window.APIConfig &&
                        window.APIConfig.admin &&
                        window.APIConfig.admin.aiQualityCheck
                            ? window.APIConfig.admin.aiQualityCheck
                            : '/api/v1/admin/ai/quality-check';

                    // ✅ No Raw Fetch Policy: wizardFetch SSOT
                    const {
                        response: qcResponse,
                        data: qcData,
                        ok: qcOk,
                    } = await wizardFetch(
                        url,
                        {
                            method: 'POST',
                            body: JSON.stringify({
                                baslik: baslik,
                                aciklama: aciklama,
                                fiyat: formData.get('fiyat'),
                                para_birimi: formData.get('para_birimi'),
                                il_id: formData.get('il_id'),
                                ilce_id: formData.get('ilce_id'),
                                mahalle_id: formData.get('mahalle_id'),
                                ilan_id: ilanId,
                            }),
                        },
                        'ai_quality_check'
                    );

                    // ✅ SSOT: 400 handling BEFORE any throw (never let it reach catch)
                    if (qcResponse?.status === 400) {
                        console.warn(
                            '⚠️ Quality Check: 400 Bad Request (suspended for 24h)',
                            qcData?.message || ''
                        );

                        // Set 24h backoff (SSOT)
                        this.backoffUntil = Date.now() + 24 * 60 * 60 * 1000;

                        // Store in WizardState for persistence
                        if (!window.WizardState) window.WizardState = {};
                        window.WizardState.aiQualityBackoff = this.backoffUntil;

                        this.loading = false;
                        return; // ✅ CRITICAL: return, don't throw
                    }

                    // ✅ Other non-OK responses (5xx, network errors)
                    if (!qcOk) {
                        console.warn(
                            'AI quality-check failed:',
                            qcResponse?.status,
                            qcData?.message
                        );
                        throw new Error(`API Error (${qcResponse?.status || 0})`);
                    }

                    const data = qcData || {};

                    if (data.success) {
                        this.result = data.data || data;
                        window.__aiQualityResult = this.result;
                    } else {
                        throw new Error(data.message || 'Kalite kontrolü başarısız.');
                    }
                } catch (error) {
                    console.error('AI Quality Error:', error);

                    // ✅ SSOT: Catch NEVER overrides 24h backoff
                    const has24hBackoff = this.backoffUntil > Date.now() + 1 * 60 * 60 * 1000; // >1h means it's the 24h one

                    if (!has24hBackoff) {
                        // Only apply short backoff for transient errors (network, 5xx)
                        this.backoffUntil = Date.now() + 10000; // 10 seconds
                        console.warn('⏸️ Quality Check suspended for 10s (transient error)');
                    } else {
                        console.log('ℹ️ Quality Check: 24h backoff already active, not overriding');
                    }

                    window.showToast?.(error.message, 'error');
                } finally {
                    this.loading = false;
                }
            },
        };
    };

    /**
     * ⛔ SSOT GUARD: Cascade dropdown fonksiyonları (loadAltKategoriler, loadYayinTipleri,
     * loadIlceler, loadMahalleler) SADECE step1-cascade.js içinde tanımlıdır.
     * Bu dosyada bu fonksiyonları yeniden tanımlamak YASAKTIR.
     * @see resources/js/wizard/step1-cascade.js (TEK KAYNAK)
     */

    // Harita-dropdown senkronizasyonu: Mahalle seçildiğinde haritaya flyTo
    // ✅ Harita-dropdown senkronizasyonu: Mahalle seçildiğinde haritaya flyTo
    window.updateMapFromLocation = async function () {
        const mahalleId = document.getElementById('mahalle_id')?.value;
        if (!mahalleId) return;

        try {
            // ✅ No Raw Fetch Policy: wizardFetch SSOT
            const { data, ok } = await wizardFetch(
                `/api/v1/location/neighborhood/${mahalleId}/coordinates`
            );
            if (!ok) return;

            if (data.success && (data.lat || data.data?.lat) && (data.lng || data.data?.lng)) {
                const lat = parseFloat(data.lat || data.data.lat);
                const lng = parseFloat(data.lng || data.data.lng);

                // Haritayı mahalle koordinatlarına uçur
                if (window.wizardMap && !isNaN(lat) && !isNaN(lng)) {
                    window.wizardMap.flyTo([lat, lng], 16, {
                        duration: 1.5,
                    });

                    // Marker'ı güncelle
                    if (window.wizardMarker) {
                        window.wizardMarker.setLatLng([lat, lng]);
                    } else if (window.wizardMap && typeof L !== 'undefined') {
                        window.wizardMarker = L.marker([lat, lng], {
                            draggable: true,
                        }).addTo(window.wizardMap);
                    }

                    // Koordinatları kaydet - Context7: lat/lng canonical
                    const latInput =
                        document.querySelector('[name="lat"]') ||
                        document.querySelector('[name="enlem"]'); // backward compat
                    const lngInput =
                        document.querySelector('[name="lng"]') ||
                        document.querySelector('[name="boylam"]'); // backward compat
                    if (latInput) latInput.value = lat;
                    if (lngInput) lngInput.value = lng;

                    // ✅ Koordinat display'leri güncelle
                    const latDisplay = document.getElementById('lat-display');
                    const lngDisplay = document.getElementById('lng-display');
                    if (latDisplay) latDisplay.textContent = lat.toFixed(6);
                    if (lngDisplay) lngDisplay.textContent = lng.toFixed(6);

                    // ✅ POI widget'ı güncelle
                    document.dispatchEvent(
                        new CustomEvent('wizard-map-marker-moved', {
                            detail: { lat, lng },
                        })
                    );
                } else if (window.locationWizard && window.locationWizard().map) {
                    // Alternatif: locationWizard component'i kullan
                    const locationWizard = window.locationWizard();
                    if (locationWizard.flyToLocation) {
                        locationWizard.flyToLocation(lat, lng, 16);
                    }
                }
            }
        } catch (error) {
            console.error('Mahalle koordinat hatası:', error);
        }
    };

    // ✅ Safe Map Helpers
    function _toNumber(v) {
        if (v === null || v === undefined) return null;
        const n = typeof v === 'number' ? v : parseFloat(String(v).replace(',', '.'));
        return Number.isFinite(n) ? n : null;
    }

    function _isValidLatLng(lat, lng) {
        return (
            Number.isFinite(lat) &&
            Number.isFinite(lng) &&
            Math.abs(lat) <= 90 &&
            Math.abs(lng) <= 180
        );
    }

    function _safeFlyTo(map, lat, lng, zoom = 11) {
        // Bodrum fallback
        const fallback = { lat: 37.0344, lng: 27.4305, zoom: 11 };

        const L1 = _toNumber(lat);
        const L2 = _toNumber(lng);

        if (!_isValidLatLng(L1, L2)) {
            console.warn('[Map] Invalid coords, fallback:', { lat, lng });
            return map.flyTo([fallback.lat, fallback.lng], fallback.zoom, { animate: false });
        }

        return map.flyTo([L1, L2], zoom, { animate: false });
    }

    // ✅ İl seçildiğinde haritayı güncelle
    window.updateMapFromIl = async function (ilId) {
        if (!ilId) return;

        // İl koordinatları (Bodrum için özel)
        const ilCoordinates = {
            48: [37.0344, 27.4305], // Muğla (Bodrum)
            // Diğer iller için koordinatlar eklenebilir
        };

        const coords = ilCoordinates[ilId];

        if (window.wizardMap) {
            if (coords && Array.isArray(coords)) {
                _safeFlyTo(window.wizardMap, coords[0], coords[1], 11);
            }
        }
    };

    // ✅ İlçe seçildiğinde haritayı güncelle
    window.updateMapFromIlce = async function (ilceId) {
        if (!ilceId) return;

        try {
            // ✅ No Raw Fetch Policy: wizardFetch SSOT
            const { data, ok } = await wizardFetch(
                `/api/v1/location/district/${ilceId}/coordinates`
            );
            if (!ok) return;

            if (data.success && (data.lat || data.data?.lat) && (data.lng || data.data?.lng)) {
                const lat = data.lat || data.data.lat;
                const lng = data.lng || data.data.lng;

                if (window.wizardMap) {
                    _safeFlyTo(window.wizardMap, lat, lng, 13);
                }
            }
        } catch (error) {
            console.error('İlçe koordinat hatası:', error);
        }
    };

    // Helper for safe category changed dispatch
    window.safeDispatchCategoryChanged = function () {
        // ✅ Use temporary detail if available (from triggerCategoryChangedIfNeeded)
        const detail = window._tempCategoryDetail || null;

        if (
            window.IlanCreateCategories &&
            typeof window.IlanCreateCategories.dispatchCategoryChanged === 'function'
        ) {
            window.IlanCreateCategories.dispatchCategoryChanged();
        } else {
            // Fallback: manually dispatch event with detail
            const event = new CustomEvent('category-changed', {
                detail: detail,
                bubbles: true,
            });
            window.dispatchEvent(event);

            // Clean up temporary detail
            if (window._tempCategoryDetail) {
                delete window._tempCategoryDetail;
            }
        }
    };

    // Global instance oluştur
    window.step2DynamicFieldsLoader = new Step2DynamicFieldsLoader();

    // Keep fiyat required state aligned with backend contract on all listing forms.
    function syncPriceRequiredByMode() {
        const modeField =
            document.getElementById('fiyat_gosterim_modu') ||
            document.querySelector('[name="fiyat_gosterim_modu"], #price_display_mode, [name="price_display_mode"]');
        const fiyatField =
            document.getElementById('fiyat') || document.querySelector('input[name="fiyat"]');

        if (!modeField || !fiyatField) return;

        if ((modeField.value || 'exact') === 'exact') {
            fiyatField.setAttribute('required', 'required');
        } else {
            fiyatField.removeAttribute('required');
        }
    }

    function queuePriceRequiredSync() {
        syncPriceRequiredByMode();
        window.requestAnimationFrame(() => {
            syncPriceRequiredByMode();
        });
        setTimeout(() => {
            syncPriceRequiredByMode();
        }, 0);
    }

    document.addEventListener('change', (event) => {
        const target = event.target;
        if (!(target instanceof Element)) return;

        if (
            target.matches(
                '#fiyat_gosterim_modu, [name="fiyat_gosterim_modu"], #price_display_mode, [name="price_display_mode"]'
            )
        ) {
            queuePriceRequiredSync();
        }
    });

    document.addEventListener(
        'click',
        (event) => {
            const target = event.target;
            if (!(target instanceof Element)) return;

            if (target.matches('button[type="submit"], input[type="submit"]')) {
                queuePriceRequiredSync();
            }
        },
        true
    );

    // DOM ready olduğunda init et
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.step2DynamicFieldsLoader.init();
            queuePriceRequiredSync();
        });
    } else {
        window.step2DynamicFieldsLoader.init();
        queuePriceRequiredSync();
    }
})();

// 🧠 Cortex Dynamic Wizard Component
const cortexDynamicWizardData = () => ({
    // State
    kategoriSelected: false,
    selectedKategoriName: '',
    selectedKategoriId: null,
    requiredFields: [],
    recommendedFields: [],
    optionalFields: [],
    fieldValues: {},

    // Quality Metrics
    qualityScore: 0,
    completedRequired: 0,
    completedRecommended: 0,
    completedOptional: 0,
    qualityBreakdown: {
        blueprint: { label: 'Nexus Alanları', score: 0 },
        visuals: { label: 'Fotoğraflar', score: 0 },
        content: { label: 'Açıklama', score: 0 },
        total: { label: 'Genel Skor', score: 0 },
    },
    suggestions: [],
    missingCritical: [],

    // Computed
    get fieldSummary() {
        const total =
            this.requiredFields.length + this.recommendedFields.length + this.optionalFields.length;
        return `${total} alan mevcut: ${this.requiredFields.length} zorunlu, ${this.recommendedFields.length} önerilen`;
    },

    get requiredFieldsHtml() {
        return this.generateFieldsHtml(this.requiredFields, 'required');
    },
    get recommendedFieldsHtml() {
        return this.generateFieldsHtml(this.recommendedFields, 'recommended');
    },
    get optionalFieldsHtml() {
        return this.generateFieldsHtml(this.optionalFields, 'optional');
    },

    // Initialize
    init() {
        // Listen for wizard-context-applied from SSOT resolver
        window.addEventListener('wizard-context-applied', (event) => {
            const ssot = event.detail.context;
            if (!ssot) return;

            console.info('[WIZARD] context + component: CortexDynamicWizard syncing from SSOT');

            this.selectedKategoriId = ssot.category.id;
            this.selectedKategoriName = ssot.category.name;
            this.kategoriSelected = true;

            // Map features from SSOT feature_schema
            const featureSchema = ssot.features.feature_schema || {};
            const features = Object.values(featureSchema);

            // Categorize fields using SSOT required flag
            this.requiredFields = features.filter((f) => f.required);
            this.recommendedFields = []; // Can be extended if SSOT provides recommendation flags
            this.optionalFields = features.filter((f) => !f.required);

            // Trigger real-time analysis
            this.calculateQualityScore();
            this.generateSuggestions();
        });

        // ✅ FIX: Also listen for category-changed event (fallback for timing issues)
        document.addEventListener('category-changed', (event) => {
            const detail = event.detail;
            if (!detail || !detail.category) return;

            console.info(
                '[WIZARD] context + component: CortexDynamicWizard syncing from category-changed'
            );

            this.selectedKategoriId = detail.category.id || detail.altCategory?.id;
            this.selectedKategoriName =
                detail.category.name || detail.altCategory?.name || 'Seçili Kategori';
            this.kategoriSelected = true;
        });
    },

    // Load fields from SSOT (Internal mapping, no fetch)
    loadFieldsFromSSOT(features) {
        if (!features) return;

        const featureSchema = features.feature_schema || {};
        const featuresList = Object.values(featureSchema);

        this.requiredFields = featuresList.filter((f) => f.required);
        this.optionalFields = featuresList.filter((f) => !f.required);
        this.recommendedFields = [];

        this.calculateQualityScore();
        this.generateSuggestions();
    },

    // Generate HTML for fields
    generateFieldsHtml(fields, priority) {
        if (!fields || fields.length === 0) return '';

        return fields
            .map((field) => {
                const icon = this.getIconForField(field.slug);
                const inputHtml = this.generateInputHtml(field, priority);

                return `
                    <div class="field-group" data-field="${field.slug}" data-priority="${priority}">
                        <label class="block text-sm font-semibold text-gray-900 mb-2 dark:text-slate-100">
                            <i class="${icon} text-gray-600 mr-2 dark:text-slate-400"></i>
                            ${field.name}
                            ${field.required ? '<span class="text-red-600 dark:text-red-400">*</span>' : ''}
                            ${field.unit ? `<span class="text-gray-500 text-xs ml-1 dark:text-slate-500">(${field.unit})</span>` : ''}
                        </label>
                        ${inputHtml}
                        ${field.help ? `<p class="text-xs text-gray-600 mt-1 dark:text-slate-400">${field.help}</p>` : ''}
                    </div>
                `;
            })
            .join('');
    },

    // Generate input HTML based on field type
    generateInputHtml(field, priority) {
        const baseClasses =
            'w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white';
        const name = `features[${field.slug}]`;
        const onInput = `@input="fieldValues['${field.slug}'] = $event.target.value; calculateQualityScore()"`;

        switch (field.type) {
            case 'boolean':
                return `
                        <div class="flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-300 dark:bg-slate-900 dark:border-slate-600">
                            <input type="checkbox" name="${name}" id="field_${field.slug}" class="w-5 h-5 text-blue-600 dark:text-blue-500 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400" ${onInput}>
                            <label for="field_${field.slug}" class="text-sm text-gray-700 dark:text-slate-300 cursor-pointer">${field.name} mevcut</label>
                        </div>`;
            case 'number':
            case 'decimal':
                return `<input type="number" name="${name}" class="${baseClasses}" placeholder="${field.name} girin" ${field.type === 'decimal' ? 'step="0.01"' : ''} ${onInput}>`;
            case 'select':
                const options = field.options || [];
                return `
                        <select name="${name}" class="${baseClasses}" ${onInput}>
                            <option value="">Seçiniz...</option>
                            ${options.map((opt) => `<option value="${opt}">${opt}</option>`).join('')}
                        </select>`;
            case 'textarea':
                return `<textarea name="${name}" class="${baseClasses}" rows="3" placeholder="${field.name} detaylarını girin" ${onInput}></textarea>`;
            default:
                return `<input type="text" name="${name}" class="${baseClasses}" placeholder="${field.name} girin" ${onInput}>`;
        }
    },

    // Calculate real-time quality score (Cortex algorithm)
    async calculateQualityScore() {
        this.completedRequired = this.countCompletedFields(this.requiredFields);
        this.completedRecommended = this.countCompletedFields(this.recommendedFields);
        this.completedOptional = this.countCompletedFields(this.optionalFields);

        const allFields = [
            ...this.requiredFields,
            ...this.recommendedFields,
            ...this.optionalFields,
        ];
        const filledFields = {};
        allFields.forEach((field) => {
            if (!field || !field.id || !field.slug) return;
            const value = this.fieldValues[field.slug];
            if (value !== undefined && value !== null && value.toString().trim() !== '') {
                filledFields[field.id] = value;
            }
        });

        try {
            // ✅ No Raw Fetch Policy: wizardFetch SSOT
            const { data: payload, ok: cortexOk } = await wizardFetch(
                '/api/v1/cortex/analyze-quality',
                {
                    method: 'POST',
                    body: JSON.stringify({
                        category_id: this.selectedKategoriId,
                        filled_fields: filledFields,
                        photo_count: window.CORTEX_PHOTO_COUNT || 0,
                        description: document.getElementById('aciklama')?.value || '',
                    }),
                },
                'cortex_analyze_quality'
            );

            if (!cortexOk) {
                console.warn('Cortex analyze-quality failed');
                return;
            }

            const data = payload.data || {};

            this.qualityScore = data.score ?? 0;
            const metrics = data.metrics || {};

            this.qualityBreakdown.blueprint.score = metrics.blueprint ?? 0;
            this.qualityBreakdown.visuals.score = metrics.visuals ?? 0;
            this.qualityBreakdown.content.score = metrics.content ?? 0;
            this.qualityBreakdown.total.score = this.qualityScore;

            this.missingCritical = data.missing_critical || [];

            window.dispatchEvent(
                new CustomEvent('cortex-score-updated', {
                    detail: {
                        score: this.qualityScore,
                        metrics,
                        missingCritical: this.missingCritical,
                    },
                })
            );

            this.generateSuggestions();
        } catch (error) {
            console.error('Cortex Wizard: analyze-quality error', error);
        }
    },

    // Count completed fields
    countCompletedFields(fields) {
        return fields.filter((field) => {
            const value = this.fieldValues[field.slug];
            return value && value.toString().trim() !== '';
        }).length;
    },

    // Generate AI suggestions
    generateSuggestions() {
        this.suggestions = [];
        if (this.missingCritical && this.missingCritical.length > 0) {
            this.suggestions.push('Kritik alanları doldurun:' + this.missingCritical.join(','));
        }
        if (this.qualityScore < 70)
            this.suggestions.push('Daha fazla özellik ekleyerek görünürlüğünüzü artırabilirsiniz.');
        if (this.qualityScore >= 90)
            this.suggestions.push('🎉 Mükemmel! İlanınız yüksek kaliteli veri ile yayına hazır.');
    },

    // Get score color
    getScoreColor() {
        if (this.qualityScore < 40) return 'linear-gradient(90deg, #dc2626, #ef4444)';
        if (this.qualityScore < 70) return 'linear-gradient(90deg, #f97316, #fb923c)';
        if (this.qualityScore < 90) return 'linear-gradient(90deg, #3b82f6, #60a5fa)';
        return 'linear-gradient(90deg, #10b981, #34d399)';
    },

    // Get icon for field
    getIconForField(slug) {
        const iconMap = {
            havuz: 'fas fa-swimming-pool',
            denize_mesafe: 'fas fa-water',
            oda_sayisi: 'fas fa-bed',
            banyo_sayisi: 'fas fa-bath',
            kat_sayisi: 'fas fa-building',
            alan: 'fas fa-ruler-combined',
            fiyat: 'fas fa-dollar-sign',
            imar_durumu: 'fas fa-file-contract',
            kaks: 'fas fa-calculator',
            default: 'fas fa-tag',
        };
        for (const [key, icon] of Object.entries(iconMap)) {
            if (slug.includes(key)) return icon;
        }
        return iconMap.default;
    },
});

if (window.Alpine) {
    Alpine.data('cortexDynamicWizard', cortexDynamicWizardData);
} else {
    document.addEventListener('alpine:init', () => {
        Alpine.data('cortexDynamicWizard', cortexDynamicWizardData);
    });
}

window.dispatchEvent(new CustomEvent('wizard:ready'));

/**
 * Yayın tipi seçildiğinde category-changed event'i dispatch et
 * Context7: Global scope mühürlemesi
 */
if (typeof window.dispatchCategoryChangedEvent === 'undefined') {
    window.dispatchCategoryChangedEvent = function () {
        const yayinTipiSelect = document.getElementById('yayin_tipi_id');
        const altKategoriSelect = document.getElementById('alt_kategori_id');
        const anaKategoriSelect = document.getElementById('ana_kategori_id');

        if (!yayinTipiSelect || !yayinTipiSelect.value) {
            return;
        }

        const yayinTipiId = yayinTipiSelect.value;
        const yayinTipiName = yayinTipiSelect.options[yayinTipiSelect.selectedIndex]?.text;
        const altKategoriId = altKategoriSelect?.value;
        const altKategoriSlug =
            altKategoriSelect?.options[altKategoriSelect.selectedIndex]?.dataset?.slug;
        const anaKategoriId = anaKategoriSelect?.value;
        const anaKategoriSlug =
            anaKategoriSelect?.options[anaKategoriSelect.selectedIndex]?.dataset?.slug;

        const kategoriId = altKategoriId || anaKategoriId;
        const kategoriSlug = altKategoriSlug || anaKategoriSlug;

        const event = new CustomEvent('category-changed', {
            detail: {
                // Backward compatibility - kategoriId ve kategoriName
                kategoriId: kategoriId ? parseInt(kategoriId) : null,
                kategoriName:
                    altKategoriSelect?.options[altKategoriSelect.selectedIndex]?.text ||
                    anaKategoriSelect?.options[anaKategoriSelect.selectedIndex]?.text ||
                    '',
                // New structure
                category: {
                    id: kategoriId ? parseInt(kategoriId) : null,
                    slug: kategoriSlug,
                    parent_slug: anaKategoriSlug,
                    name:
                        altKategoriSelect?.options[altKategoriSelect.selectedIndex]?.text ||
                        anaKategoriSelect?.options[anaKategoriSelect.selectedIndex]?.text ||
                        '',
                },
                yayinTipi: yayinTipiName,
                yayinTipiId: yayinTipiId ? parseInt(yayinTipiId) : null,
            },
            bubbles: true,
        });

        document.dispatchEvent(event);
        console.log('✅ category-changed event dispatched', event.detail);
    };
}

// ✅ REFACTOR: Namespace'e fonksiyonları kaydet (backward compatibility için window.* da korunuyor)
window.YalihanWizard.components = {
    aiTitleGenerator: window.aiTitleGenerator,
    intelligenceHub: window.intelligenceHub,
    poiWidgetStep2: window.poiWidgetStep2,
    poiSelector: window.poiSelector,
    ilanWizard: window.ilanWizard,
};

window.YalihanWizard.utils = {
    dispatchCategoryChangedEvent: window.dispatchCategoryChangedEvent,
    safeDispatchCategoryChanged: window.safeDispatchCategoryChanged,
};

window.YalihanWizard.initialized = true;
console.log('✅ YalihanWizard namespace initialized v' + window.YalihanWizard.version);
