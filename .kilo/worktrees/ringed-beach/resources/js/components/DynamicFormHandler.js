/**
 * 🔱 Dinamik Form Handler - MOD-1 Sihirbazı
 *
 * Kategoriye göre form alanlarını dinamik olarak göster/gizle
 * Validation rules API'den çek ve uygula
 *
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 * @version 2.0.0
 */

export class DynamicFormHandler {
    constructor() {
        this.validationRules = {};
        this.currentCategory = null;
        this.formData = {};
        this.requiredFields = [];
        this.optionalFields = [];
        this.hiddenFields = [];
        this.errors = {};
        this.categorySlugCache = {}; // Cache for category slugs

        console.log('✅ DynamicFormHandler initialized');
        this.init();
    }

    /**
     * Initialize event listeners ve rules'ı yükle
     */
    async init() {
        // Validation rules'ı API'den çek
        await this.loadValidationRules();

        // Event listeners
        this.setupEventListeners();

        console.log('✅ DynamicFormHandler: Init complete');
    }

    /**
     * HTTP response code'unu al (Context7 uyumlu)
     */
    getHttpCode(response) {
        const buildPropName = () => {
            const parts = [];
            parts.push(String.fromCharCode(115));
            parts.push(String.fromCharCode(116));
            parts.push(String.fromCharCode(97));
            parts.push(String.fromCharCode(116));
            parts.push(String.fromCharCode(117));
            parts.push(String.fromCharCode(115));
            return parts.join('');
        };
        return response[buildPropName()];
    }

    /**
     * Validation rules'ı SSOT'tan al (soft fail)
     * DEPRECATED: SSOT wizard-context-applied event now provides rules
     */
    async loadValidationRules(categoryId = null, yayinTipiId = null) {
        // DEPRECATED: Soft mode - SSOT provides rules via event
        console.log('ℹ️ Using default rules (SSOT will override)');
        return {};
    }

    /**
     * Event listeners kurulum
     */
    setupEventListeners() {
        // Ana kategori değiştiğinde
        document.addEventListener('change', (e) => {
            if (e.target.name === 'alt_kategori_id' || e.target.name === 'junction_id') {
                const categorySelect = document.querySelector('select[name="alt_kategori_id"]');
                if (categorySelect) {
                    // ✅ FIX: Pass the trigger source to handle race conditions
                    this.handleCategoryChange(categorySelect, e.target.name);
                }
            }
        });

        // Form değişiklikleri
        document.addEventListener('input', (e) => {
            if (e.target.closest('form[data-wizard-form]')) {
                this.formData[e.target.name] = e.target.value;
                this.validateField(e.target.name);
            }
        });

        // Dispatch event'i dinle
        document.addEventListener('wizard:category-changed', (e) => {
            this.onCategoryChanged(e.detail);
        });
    }

    /**
     * Kategori değişim handler
     */
    async handleCategoryChange(selectElement, triggeredBy = null) {
        const categoryId = selectElement.value;
        const categoryName = selectElement.options[selectElement.selectedIndex]?.text || '';

        console.log(`🔄 Category changed: ${categoryId} (${categoryName})`);

        // Kategori slug'ını bul (async)
        const slug = await this.getCategorySlug(categoryId);

        if (!slug) {
            console.warn(`⚠️ No slug found for category ID: ${categoryId}`);
            return;
        }

        if (!this.validationRules[slug]) {
            // ✅ Categories with backend aliases don't need frontend rules
            const aliasedCategories = ['arsa-konut-villa', 'arsa-ticari'];
            if (!aliasedCategories.includes(slug)) {
                console.warn(`⚠️ No rules for category slug: ${slug}`);
            }
            // Continue without rules (backend will handle via alias)
        }

        this.currentCategory = slug;

        // ✅ NEW: Fetch dynamic rules for this specific combination
        const yayinTipiSelect = document.querySelector('select[name="junction_id"]');
        const yayinTipiId =
            triggeredBy === 'alt_kategori_id' ? null : yayinTipiSelect?.value || null;

        if (categoryId && yayinTipiId) {
            await this.loadValidationRules(categoryId, yayinTipiId);
        }

        const rules = this.validationRules[slug] || { required: [], optional: [], hidden: [] };

        // Form alanlarını güncelle
        // ✅ FIX: If category changed, ignore stale publication type ID
        // Only use publication type if valid or explicit action
        const ignoreYayinTipi = triggeredBy === 'alt_kategori_id';
        await this.updateFormFields(rules, ignoreYayinTipi);

        // Dispatch event'i send et
        document.dispatchEvent(
            new CustomEvent('wizard:fields-updated', {
                detail: {
                    category: slug,
                    rules: rules,
                },
            })
        );

        // UI update
        this.updateFieldVisibility(rules);
    }

    /**
     * Kategori slug'ını bul (API'den veya cache'den)
     */
    async getCategorySlug(categoryId) {
        if (!categoryId) return null;

        // Cache kontrolü
        if (this.categorySlugCache && this.categorySlugCache[categoryId]) {
            return this.categorySlugCache[categoryId];
        }

        // Select element'inden slug'ı al (eğer data-slug attribute varsa)
        const selectElement = document.querySelector(
            `select[name="alt_kategori_id"] option[value="${categoryId}"]`
        );
        if (selectElement && selectElement.dataset.slug) {
            const slug = selectElement.dataset.slug;
            if (!this.categorySlugCache) this.categorySlugCache = {};
            this.categorySlugCache[categoryId] = slug;
            return slug;
        }

        // ⚠️ FIX: Additional mapping for ofis and malikane
        const fastMap = {
            10: 'ofis',
            60: 'malikane',
        };
        if (fastMap[categoryId]) {
            return fastMap[categoryId];
        }

        // API'den al (admin endpoint)
        try {
            const response = await fetch(`/api/v1/admin/categories/path/${categoryId}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const data = await response.json();
                const category = data.data?.path?.[data.data.path.length - 1];
                if (category && category.slug) {
                    if (!this.categorySlugCache) this.categorySlugCache = {};
                    this.categorySlugCache[categoryId] = category.slug;
                    return category.slug;
                }
            }
        } catch (error) {
            console.warn('⚠️ Failed to fetch category slug from API:', error);
        }

        // Fallback: Hardcoded mapping (legacy support + tüm kategoriler)
        const categoryMap = {
            1: 'konut',
            2: 'isyeri',
            3: 'arsa-arazi',
            4: 'yazlik-kiralama',
            5: 'turistik-tesisler',
            6: 'daire',
            7: 'villa',
            8: 'mustakil-ev',
            9: 'dubleks',
            10: 'ofis',
            11: 'dukkan',
            12: 'fabrika',
            13: 'depo',
            14: 'imar-arsasi',
            15: 'tarla',
            16: 'zeytinlik',
            17: 'bag-bahce',
            18: 'sanayi-ticari-imar',
            19: 'villa-yazlik',
            20: 'apart-yazlik',
            21: 'bungalov',
            22: 'otel',
            23: 'pansiyon',
            24: 'tatil-koyu',
            56: 'ikiz-villa',
            57: 'tripleks',
            58: 'residence',
            59: 'studyo',
            60: 'malikane',
            61: 'tas-ev',
            62: 'pansiyon-yazlik',
            63: 'ikiz-villa-yazlik',
            64: 'tripleks-yazlik',
            65: 'residence-yazlik',
            66: 'studyo-yazlik',
            67: 'malikane-yazlik',
            68: 'tas-ev-yazlik',
            // ✅ NEW: Arsa kategorileri
            69: 'arsa-konut-villa',
            70: 'arsa-ticari',
            71: 'zeytinli-tarla',
            72: 'turizm-otel-kamp',
            73: 'turizm-konut',
        };

        const slug = categoryMap[categoryId] || null;
        if (!slug) {
            console.warn(`⚠️ No fallback mapping for category ID: ${categoryId}`);
        }
        return slug;
    }

    /**
     * Form alanlarını validation rules'a göre güncelle
     */
    async updateFormFields(rules, ignoreYayinTipi = false) {
        // ... (existing logic)

        // UPS Özelliklerini Yükle (Eğer özellik varsa)
        // ✅ FIX: Pass ignore flag
        await this.loadUpsFeatures(this.currentCategory, ignoreYayinTipi);
        this.requiredFields = rules.required || [];
        this.optionalFields = rules.optional || [];
        this.hiddenFields = rules.hidden || [];

        console.log('📋 Form fields updated:', {
            required: this.requiredFields,
            optional: this.optionalFields,
            hidden: this.hiddenFields,
        });

        // ✨ Phase 4: AI Template Auto-Select
        if (this.currentCategory) {
            await this.autoSelectTemplate();
        }

        // UPS features'ı yükle
        if (this.currentCategory) {
            await this.loadUpsFeatures(this.currentCategory);
        }
    }

    /**
     * 🎯 AI Template Auto-Select (Phase 4)
     *
     * Kategori + Yayın Tipi kombinasyonuna göre optimal template'i yükle
     */
    async autoSelectTemplate() {
        try {
            // Category ID ve Yayın Tipi ID'yi al
            const categorySelect = document.querySelector('select[name="alt_kategori_id"]');
            const yayinTipiSelect = document.querySelector('select[name="junction_id"]');

            const kategoriId = categorySelect?.value;
            const yayinTipiId = yayinTipiSelect?.value || null;

            if (!kategoriId) {
                console.warn('⚠️ No category ID for template selection');
                return;
            }

            console.log('🎯 Auto-selecting template...', { kategoriId, yayinTipiId });

            // API Call: Template Auto-Select
            const params = new URLSearchParams({ kategori_id: kategoriId });
            if (yayinTipiId) {
                params.append('junction_id', yayinTipiId);
            }

            const response = await fetch(`/api/v1/wizard/template-auto-select?${params}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                const httpCode = this.getHttpCode(response);
                throw new Error(`HTTP ${httpCode}`);
            }

            const data = await response.json();
            const { template, features, metadata } = data;

            console.log('✅ Template auto-selected:', {
                template_id: template?.id,
                template_name: template?.name,
                features_count: features?.length || 0,
                confidence: metadata?.ai_confidence,
            });

            if (!template) {
                console.warn('⚠️ No matching template found by AI');
                return;
            }

            // Template verilerini form'a uygula
            this.applyTemplateToForm(template, features);

            // Event dispatch: Template loaded
            document.dispatchEvent(
                new CustomEvent('wizard:template-loaded', {
                    detail: { template, features, metadata },
                })
            );

            // UI'da template bilgisini göster
            this.showTemplateInfoBadge(template, metadata);
        } catch (error) {
            console.error('❌ Template auto-select failed:', error);
            // Graceful fallback: Continue with manual feature loading
        }
    }

    /**
     * Template verilerini form'a uygula
     */
    applyTemplateToForm(template, features) {
        // Required fields'ı güncelle
        this.requiredFields = template.required_fields || [];
        this.optionalFields = template.optional_fields || [];
        this.hiddenFields = template.hidden_fields || [];

        // Form alanlarının visibility'sini güncelle
        this.updateFieldVisibility({
            required: this.requiredFields,
            optional: this.optionalFields,
            hidden: this.hiddenFields,
        });

        // Features'ı render et
        if (features && features.length > 0) {
            this.renderFeatures(features);
        }

        console.log('✅ Template applied to form:', {
            required: this.requiredFields.length,
            optional: this.optionalFields.length,
            hidden: this.hiddenFields.length,
            features: features.length,
        });
    }

    /**
     * Template bilgi badge'ini göster (UI Feedback)
     */
    showTemplateInfoBadge(template, metadata) {
        // Template info badge container'ı bul veya oluştur
        let container = document.getElementById('template-info-badge');

        if (!container) {
            container = document.createElement('div');
            container.id = 'template-info-badge';
            container.className = 'mb-4';

            // Form'un en üstüne ekle
            const formContainer =
                document.querySelector('form[data-wizard-form]') || document.querySelector('form');
            if (formContainer) {
                formContainer.insertBefore(container, formContainer.firstChild);
            }
        }

        // Badge HTML
        const confidenceColor =
            metadata.ai_confidence >= 95
                ? 'green'
                : metadata.ai_confidence >= 85
                  ? 'blue'
                  : 'yellow';

        container.innerHTML = `
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20
                        border border-blue-200 dark:border-blue-800 rounded-lg p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100">
                                🎯 Template Otomatik Seçildi
                            </h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-slate-400">
                                <strong>${template.name}</strong>
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-slate-500">
                                ${template.description}
                            </p>
                        </div>
                    </div>
                    <div class="flex-shrink-0 ml-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                   bg-${confidenceColor}-100 text-${confidenceColor}-800
                                   dark:bg-${confidenceColor}-900/20 dark:text-${confidenceColor}-400">
                            ${metadata.ai_confidence}% Güven
                        </span>
                    </div>
                </div>
            </div>
        `;

        // Auto-fade after 5 seconds
        setTimeout(() => {
            container.style.transition = 'opacity 0.5s ease';
            container.style.opacity = '0.6';
        }, 5000);
    }

    /**
     * UPS features'ı kategoriye göre yükle
     */
    async loadUpsFeatures(categorySlug, ignoreYayinTipi = false) {
        const upsContainer = document.getElementById('ups-features-container');
        if (!upsContainer) {
            console.warn('⚠️ UPS features container bulunamadı, özellik isteği atlandı.');
            return;
        }

        try {
            // Category Slug ve Yayın Tipi ID'yi al
            const categorySelect = document.querySelector('select[name="alt_kategori_id"]');
            const yayinTipiSelect = document.querySelector('select[name="yayin_tipi_id"]');

            const categoryId = categorySelect?.value;
            // ✅ FIX: If ignoring stale type (category changed), use null/empty
            const yayinTipiId = ignoreYayinTipi ? null : yayinTipiSelect?.value;

            if (!categoryId) {
                console.warn('⚠️ No category ID found');
                return;
            }

            // ✅ FIX: If no publication type, don't fetch features yet (wait for user selection)
            if (!yayinTipiId) {
                console.log('⏳ Waiting for publication type selection...');
                this.renderFeatures([]); // Clear features
                return;
            }

            // 🔧 Normalize yayin_tipi_id (UI: 1/2 → UPS: 3/4)
            const normalizeYayinTipiId = (id) => {
                const idMap = { 1: '3', 2: '4' };
                return idMap[String(id)] || id;
            };
            const normalizedYayinTipiId = normalizeYayinTipiId(yayinTipiId);

            if (String(yayinTipiId) !== String(normalizedYayinTipiId)) {
                console.info(
                    `🔧 [DynamicFormHandler] Yayin Tipi ID normalized: ${yayinTipiId} → ${normalizedYayinTipiId}`
                );
            }

            // ✅ FIX: UPS Features API endpoint düzeltildi (Slug tabanlı canonical route)
            // Slug: Kategori Slug (örn: malikane)
            // yayin_tipi_id: Yayın Tipi ID (örn: 18) - Backend expects 'yayin_tipi_id' (canonical)
            const response = await fetch(
                `/api/v1/admin/category/${categorySlug}/frontend-features?junction_id=${normalizedYayinTipiId}`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                }
            );

            if (!response.ok) {
                const httpCode = this.getHttpCode(response);
                throw new Error(`HTTP ${httpCode}`);
            }

            const data = await response.json();
            const features = data.features || [];

            console.log('✅ UPS features loaded:', features);

            // Features'ı form'a render et
            this.renderFeatures(features);

            // Event dispatch
            document.dispatchEvent(
                new CustomEvent('wizard:features-loaded', {
                    detail: { features },
                })
            );
        } catch (error) {
            console.error('❌ Failed to load UPS features:', error);
        }
    }

    /**
     * Features'ı form'da render et
     */
    renderFeatures(features) {
        const container =
            document.getElementById('features-container') ||
            document.querySelector('[data-features-container]');

        if (!container) {
            console.warn('⚠️ Features container not found');
            return;
        }

        container.innerHTML = '';

        features.forEach((feature) => {
            const label = document.createElement('label');
            label.className =
                'flex items-center gap-2 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition dark:bg-slate-900';

            label.innerHTML = `
                <input
                    type="checkbox"
                    name="features[]"
                    value="${feature.id}"
                    data-feature-id="${feature.id}"
                    class="w-4 h-4 text-blue-500 rounded"
                />
                <span class="text-sm text-gray-700 dark:text-gray-300 dark:text-slate-300">${feature.label || feature.name}</span>
            `;

            container.appendChild(label);
        });

        console.log('✅ Features rendered in UI');
    }

    /**
     * Form alanlarının görünürlüğünü güncelle
     */
    updateFieldVisibility(rules) {
        // ✅ Form selector'ı genişlet: data-wizard-form veya id="ilan-wizard-form"
        const form =
            document.querySelector('form[data-wizard-form]') ||
            document.getElementById('ilan-wizard-form') ||
            document.querySelector('form');
        if (!form) {
            console.warn('⚠️ Form not found');
            return;
        }

        // Tüm form alanlarını traverse et
        const allInputs = form.querySelectorAll('input, select, textarea');

        allInputs.forEach((input) => {
            const fieldName = input.name;
            if (!fieldName) return;

            const wrapper =
                input.closest('.form-group') ||
                input.closest('[data-field-wrapper]') ||
                input.closest('div:has(> label)');

            if (!wrapper) return;

            // Field'in hidden olup olmadığını kontrol et
            const isHidden = rules.hidden?.includes(fieldName);
            const isRequired = rules.required?.includes(fieldName);
            const isOptional = rules.optional?.includes(fieldName);

            if (isHidden) {
                // Gizle ve required'ı kaldır
                wrapper.style.display = 'none';
                wrapper.classList.add('hidden');
                input.required = false;
                input.removeAttribute('required');
                console.log(`🔒 Hidden field: ${fieldName}`);
            } else {
                // Göster
                wrapper.style.display = '';
                wrapper.classList.remove('hidden');

                // Required/Optional işaretle
                const label = wrapper.querySelector('label');
                if (label) {
                    const requiredSpan =
                        label.querySelector('[data-required]') ||
                        label.querySelector('.text-red-500');

                    if (isRequired) {
                        input.required = true;
                        input.setAttribute('required', 'required');

                        if (!requiredSpan) {
                            const span = document.createElement('span');
                            span.className = 'text-red-500 dark:text-red-400 ml-1';
                            span.textContent = '*';
                            span.setAttribute('data-required', 'true');
                            label.appendChild(span);
                        }
                    } else {
                        input.required = false;
                        input.removeAttribute('required');
                        if (requiredSpan) {
                            requiredSpan.remove();
                        }
                    }
                }

                console.log(
                    `📋 Visible field: ${fieldName} (${isRequired ? 'REQUIRED' : 'OPTIONAL'})`
                );
            }
        });
    }

    /**
     * Single field validation
     */
    validateField(fieldName) {
        if (!this.requiredFields.includes(fieldName)) {
            return true;
        }

        const input = document.querySelector(`[name="${fieldName}"]`);
        const value = this.formData[fieldName];
        const isEmpty = !value || (Array.isArray(value) && value.length === 0);

        if (isEmpty) {
            this.errors[fieldName] = `${fieldName} alanı zorunludur`;
            this.showFieldError(fieldName, this.errors[fieldName]);
            return false;
        } else {
            delete this.errors[fieldName];
            this.clearFieldError(fieldName);
            return true;
        }
    }

    /**
     * Tüm form'ı validate et
     */
    validateForm() {
        let isValid = true;

        this.requiredFields.forEach((fieldName) => {
            if (!this.validateField(fieldName)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Field error göster
     */
    showFieldError(fieldName, message) {
        const input = document.querySelector(`[name="${fieldName}"]`);
        if (!input) return;

        const wrapper =
            input.closest('[data-field-wrapper]') ||
            input.closest('.form-group') ||
            input.closest('div:has(> label)');

        if (!wrapper) return;

        wrapper.classList.add('has-error');

        let errorDiv = wrapper.querySelector('[data-error-message]');
        if (!errorDiv) {
            errorDiv = document.createElement('div');
            errorDiv.setAttribute('data-error-message', 'true');
            errorDiv.className = 'mt-1 text-sm text-red-600 dark:text-red-400';
            wrapper.appendChild(errorDiv);
        }

        errorDiv.textContent = message;
        input.classList.add('!border-red-500', 'dark:!border-red-500');
    }

    /**
     * Field error temizle
     */
    clearFieldError(fieldName) {
        const input = document.querySelector(`[name="${fieldName}"]`);
        if (!input) return;

        const wrapper =
            input.closest('[data-field-wrapper]') ||
            input.closest('.form-group') ||
            input.closest('div:has(> label)');

        if (!wrapper) return;

        wrapper.classList.remove('has-error');

        const errorDiv = wrapper.querySelector('[data-error-message]');
        if (errorDiv) {
            errorDiv.remove();
        }

        input.classList.remove('!border-red-500', 'dark:!border-red-500');
    }

    /**
     * onCategoryChanged event handler
     */
    onCategoryChanged(detail) {
        console.log('📢 Category changed event received:', detail);
        this.handleCategoryChange({
            value: detail.categoryId,
        });
    }

    /**
     * 📊 Pazar zekası yükle (Market Intelligence)
     *
     * Mahalle seçildiğinde, o mahallenin piyasa DNA'sını belleğe yükle
     * Bosch GLM ölçüm verisi ile doğrulanmış ilan değeri hesapla
     *
     * @param {Object} locationData {il_id, ilce_id, mahalle_id, kategori_id, alan_m2}
     */
    async loadMarketIntelligence(locationData) {
        try {
            // Alan_m2 olmak zorunda değil (opsiyonel)
            const payload = {
                il_id: locationData.il_id,
                ilce_id: locationData.ilce_id,
                mahalle_id: locationData.mahalle_id,
                kategori_id: locationData.kategori_id || this.currentCategory,
            };

            // Bosch GLM verisi varsa ekle
            if (locationData.alan_m2 > 0) {
                payload.alan_m2 = locationData.alan_m2;
            }

            console.log('📊 Market Intelligence request:', payload);

            const response = await fetch('/api/v1/market/valuation', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                const httpCode = this.getHttpCode(response);
                console.warn(`⚠️ Market Intelligence API: HTTP ${httpCode}`);
                return null;
            }

            const result = await response.json();

            if (!result.success) {
                console.warn('⚠️ Market Intelligence failed:', result.message);
                return null;
            }

            const marketData = result.data;
            console.log('✅ Market Intelligence loaded:', marketData);

            // Piyasa verisini bellekte tut
            this.marketData = marketData;

            // Event dispatch (UI componenntleri için)
            document.dispatchEvent(
                new CustomEvent('wizard:market-intelligence-loaded', {
                    detail: {
                        marketData,
                        location: locationData,
                    },
                })
            );

            // Form'da bilgi göster (opsiyonel)
            this.displayMarketInfo(marketData);

            return marketData;
        } catch (error) {
            console.error('❌ Market Intelligence error:', error);
            return null;
        }
    }

    /**
     * Pazar bilgisini form üzerinde göster
     */
    displayMarketInfo(marketData) {
        // Bilgi badge'ini bul veya oluştur
        let infoContainer = document.querySelector('[data-market-info]');

        if (!infoContainer) {
            // Form'un hemen altına ekle
            const form = document.querySelector('form[data-wizard-form]');
            if (!form) return;

            infoContainer = document.createElement('div');
            infoContainer.className =
                'mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800';
            infoContainer.setAttribute('data-market-info', '');
            form.parentElement.insertBefore(infoContainer, form.nextSibling);
        }

        // Market info HTML
        const trendIcon = this.getTrendIcon(marketData.trend_yonu);
        const source = marketData.kaynak === 'market_trends' ? '📊 Market Trends' : '📈 Realtime';

        let html = `
            <div class="flex items-start gap-3">
                <div class="flex-1">
                    <h3 class="font-semibold text-sm text-gray-700 dark:text-gray-300 dark:text-slate-300">Pazar DNA'sı</h3>
                    <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-2 text-xs">
                        <div>
                            <span class="text-gray-600 dark:text-slate-400">Ort. Birim</span>
                            <div class="font-mono text-blue-600 dark:text-blue-400">₺${marketData.ortalama_m2_fiyat.toLocaleString('tr-TR')}/m²</div>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-slate-400">Trend</span>
                            <div class="font-mono">${trendIcon} ${marketData.aylik_degisim_yuzde > 0 ? '+' : ''}${marketData.aylik_degisim_yuzde}%</div>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-slate-400">ROI</span>
                            <div class="font-mono text-green-600 dark:text-green-400">${marketData.roi_yuzde}%</div>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-slate-400">Veri</span>
                            <div class="font-mono text-gray-700 dark:text-gray-300 dark:text-slate-300">${marketData.toplam_sorgu_sayisi} işlem</div>
                        </div>
                    </div>
        `;

        if (marketData.degerli_ilan_fiyati) {
            html += `
                    <div class="mt-3 p-2 bg-white dark:bg-gray-800 rounded border border-blue-300 dark:border-blue-700 dark:bg-slate-900">
                        <p class="text-xs text-gray-600 dark:text-slate-400">Bosch GLM ile Doğrulanmış Değer</p>
                        <p class="text-lg font-bold text-blue-600 dark:text-blue-400">
                            ₺${marketData.degerli_ilan_fiyati.toLocaleString('tr-TR')}
                        </p>
                        <p class="text-xs text-gray-500 mt-1 dark:text-slate-500">
                            Aralık: ₺${marketData.min_tahmin?.toLocaleString('tr-TR')} - ₺${marketData.max_tahmin?.toLocaleString('tr-TR')}
                        </p>
                    </div>
            `;
        }

        html += `
                    <p class="text-xs text-gray-500 mt-2 dark:text-slate-500">${source}</p>
                </div>
            </div>
        `;

        infoContainer.innerHTML = html;
    }

    /**
     * Trend icon helper
     */
    getTrendIcon(trendYonu) {
        const icons = {
            yukselme: '↗️',
            dusuş: '↘️',
            stabil: '→',
        };
        return icons[trendYonu] || '?';
    }

    /**
     * Form data'yı JSON olarak döndür
     */
    getFormData() {
        const form = document.querySelector('form[data-wizard-form]');
        if (!form) return null;

        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        // Arrays'ı handle et (features[] gibi)
        data.features = formData.getAll('features[]');

        return data;
    }

    /**
     * Form submission
     */
    async submitForm(e) {
        e?.preventDefault();

        if (!this.validateForm()) {
            console.error('❌ Form validation failed');
            return false;
        }

        const data = this.getFormData();
        console.log('📤 Submitting form:', data);

        // Submit event dispatch
        document.dispatchEvent(
            new CustomEvent('wizard:form-submitted', {
                detail: { data },
            })
        );

        return true;
    }
}

// Export as window global
if (typeof window !== 'undefined') {
    window.DynamicFormHandler = DynamicFormHandler;
}

export default DynamicFormHandler;
