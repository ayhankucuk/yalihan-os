{{-- Section 10: Dinamik Özellikler (Context7 Uyumlu + AI Powered) --}}
{{-- 🐛 DEBUG: x-show geçici olarak kaldırıldı - element her zaman görünür --}}
<div id="features-dynamic-root" class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
    <div class="mb-6 flex items-start justify-between">
        <div>
            <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200 mb-2 flex items-center">
                <span
                    class="bg-lime-100 dark:bg-lime-900 text-lime-600 dark:text-lime-400 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">9</span>
                ✨ İlan Özellikleri
            </h2>
            <p class="text-gray-600 dark:text-gray-400 text-sm">
                İlanınıza özel özellikler ekleyin. Kategori seçtikten sonra ilgili özellikler görünecek.
            </p>
        </div>

        {{-- 🤖 AI ile Tümünü Doldur Butonu --}}
        <button type="button" id="ai-suggest-all-features" style="display: none;"
            class="px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center space-x-2 dark:shadow-none"
            onclick="window.FeaturesAI && window.FeaturesAI.suggestAll(window.FeaturesAI.getFormContext())">
            <i class="fas fa-magic"></i>
            <span class="font-medium">AI ile Tümünü Doldur</span>
        </button>
    </div>

    {{-- ✅ VANILLA JS RENDER - Alpine.js nested loops KALDIRILDI --}}
    <div id="features-container" class="space-y-6">
        {{-- Kategori Seçimi Uyarısı --}}
        <div id="features-empty-state"
            class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-info-circle text-blue-600 dark:text-blue-400 mr-3"></i>
                <div>
                    <p class="text-blue-800 dark:text-blue-200 font-medium empty-title">
                        Kategori Seçimi Gerekli
                    </p>
                    <p class="text-blue-600 dark:text-blue-400 text-sm mt-1 empty-message">
                        Özellikleri görmek için önce "Kategori Sistemi" bölümünden kategori seçin.
                    </p>
                </div>
            </div>
        </div>

        {{-- Dinamik Özellik Kategorileri (Vanilla JS ile render edilecek) --}}
        <div id="features-content" class="hidden"></div>
        <div id="ups-features-container" class="hidden"></div>

        {{-- Type-based Fields Container (categories.js için) --}}
        <div id="type-based-fields-container" class="space-y-4 hidden"></div>

        {{-- Loading State --}}
        <div id="features-loading" class="text-center py-8 hidden" style="display: none;">
            <i class="fas fa-spinner fa-spin text-2xl text-gray-400 mb-2"></i>
            <p class="text-gray-500 dark:text-gray-400">Özellikler yükleniyor...</p>
        </div>

        {{-- Error State --}}
        <div id="features-error"
            class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 hidden">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 mr-3"></i>
                <p class="text-red-800 dark:text-red-200" id="features-error-message"></p>
            </div>
        </div>
    </div>
</div>

<script>
    // ✅ SSOT: Global slug cache (eliminates DOM race condition)
    window.WizardState = window.WizardState || {};
    window.WizardState.ssotSlugs = {
        category: null,
        yayin_tipi: null,
        contextKey: null
    };

    // ✅ SSOT: Cache slugs from wizard-context-applied event
    document.addEventListener('wizard-context-applied', (e) => {
        try {
            const ctx = e.detail?.context || {};
            const key = e.detail?.context_key || 'unknown';

            window.WizardState.ssotSlugs.category = ctx.category_slug || ctx.category?.slug || null;
            window.WizardState.ssotSlugs.yayin_tipi = ctx.yayin_tipi_slug || ctx.yayin_tipi?.slug || null;
            window.WizardState.ssotSlugs.contextKey = key;

            console.log('[WIZARD] phase=features action=ssot_slugs_cached key=' + key + ' category=' + window
                .WizardState.ssotSlugs.category);
        } catch (err) {
            console.warn('[WIZARD] phase=features action=ssot_cache_failed', err);
        }
    });

    // ✅ VANILLA JS FEATURES MANAGER - NO ALPINE.JS DEPENDENCY
    (function() {
        'use strict';

        const FeaturesManager = {
            selectedCategory: null,
            // ❌ REMOVED: featureCategories state (now passed as parameter to renderFeatures)
            elements: {},
            _pendingController: null,
            _loadTimeout: null,

            init() {
                console.log('🔧 FeaturesManager.init() called');

                // Cache DOM elements
                this.elements = {
                    emptyState: document.getElementById('features-empty-state'),
                    content: document.getElementById('features-content'),
                    loading: document.getElementById('features-loading'),
                    error: document.getElementById('features-error'),
                    errorMessage: document.getElementById('features-error-message')
                };



                // ✅ features-dynamic artık form sonunda - Step 2 observer gereksiz

                // ✅ FIX: Hem category-changed event'ini hem de direkt select change'i dinle
                const altSelect = document.getElementById('alt_kategori_id');

                // ✅ CRITICAL FIX: Yayın tipi selector - hem ID hem name ile dene
                const yayinTipiSelect =
                    document.querySelector('#junction_id') ||
                    document.querySelector('select[name="junction_id"]') ||
                    document.querySelector('#yayin_tipi') ||
                    document.querySelector('select[name="yayin_tipi"]');

                if (!altSelect) {
                    console.warn('⚠️ Features: alt_kategori_id select elementi bulunamadı');
                    return;
                }

                // Helper: Turkish karakterleri slug'a çevir
                const normalizeSlug = (text) => {
                    if (!text) return '';
                    return text.toString().toLowerCase()
                        .replace(/ş/g, 's').replace(/ğ/g, 'g').replace(/ü/g, 'u')
                        .replace(/ö/g, 'o').replace(/ç/g, 'c').replace(/ı/g, 'i')
                        .replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
                };

                // ✅ FIX: category-changed event'ini dinle (wizard step 2'den gelebilir)
                document.addEventListener('category-changed', (event) => {
                    console.log('🎯 Features: category-changed event received', event.detail);
                    const detail = event.detail;

                    // 🚀 SSOT SLUG FALLBACK CHAIN (eliminates race)
                    // 1) SSOT cache (best)
                    // 2) Event payload
                    // 3) Reset if all fail
                    let slug = window.WizardState?.ssotSlugs?.category || null;

                    if (!slug) {
                        // Fallback to event payload
                        if (detail.altCategory && detail.altCategory.slug) {
                            slug = detail.altCategory.slug;
                        } else if (detail.category && detail.category.slug) {
                            slug = detail.category.slug;
                        }
                    }

                    // Yayın tipi bilgisini al (SSOT önce)
                    const yayin = window.WizardState?.ssotSlugs?.yayin_tipi ||
                        detail.yayinTipiId ||
                        detail.yayinTipi?.slug ||
                        null;

                    if (slug) {
                        console.log('📌 Features: Loading from SSOT cache:', {
                            slug,
                            yayin,
                            source: window.WizardState?.ssotSlugs?.category ? 'SSOT' : 'event'
                        });
                        this.loadFeatures(slug, yayin);
                    } else {
                        console.log('⏭️ Features: Slug not available (waiting for SSOT)');
                        this.reset();
                    }
                });

                // Alt kategori değişince özellikler yükle
                altSelect.addEventListener('change', () => {
                    const selected = altSelect.options[altSelect.selectedIndex];

                    // 🚀 SSOT slug first, DOM data-slug as fallback
                    const slug = window.WizardState?.ssotSlugs?.category ||
                        selected?.getAttribute('data-slug') ||
                        '';

                    // ✅ Yayın tipi - SSOT önce
                    let yayin = window.WizardState?.ssotSlugs?.yayin_tipi ||
                        yayinTipiSelect?.value ||
                        null;

                    console.log('📌 Alt kategori değişti:', {
                        slug,
                        yayin,
                        source: window.WizardState?.ssotSlugs?.category ? 'SSOT' : 'DOM',
                        text: selected?.text
                    });

                    if (slug) {
                        this.loadFeatures(slug, yayin);
                    } else {
                        console.log('⏭️ Features: Waiting for SSOT slug');
                        this.reset();
                    }
                });

                // ✅ FIX: Yayın tipi değişince de özellikler yükle
                if (yayinTipiSelect) {
                    yayinTipiSelect.addEventListener('change', () => {
                        // Slug race condition fix: Try to resolve slug, if fail, wait briefly and try again
                        const tryLoad = (retries = 3) => {
                            const selected = altSelect.options[altSelect.selectedIndex];
                            const slug = selected?.getAttribute('data-slug') || '';
                            const yayin = yayinTipiSelect.value || null;

                            if (slug) {
                                console.log('📌 Yayın tipi değişti (slug resolved):', {
                                    slug,
                                    yayin
                                });
                                this.loadFeatures(slug, yayin);
                            } else if (retries > 0 && altSelect.value) {
                                // Subcategory selected but slug not yet in data-attribute? Wait for cascade
                                console.log(`⏳ Features: Slug not ready, retrying... (${retries})`);
                                setTimeout(() => tryLoad(retries - 1), 100);
                            } else {
                                console.log(
                                    '⏭️ Features: Slug bulunamadı, yayın tipi değişikliği atlandı.'
                                );
                            }
                        };

                        tryLoad();
                    });
                }

                // Sayfa yüklenirken seçili kategori varsa özellikler yükle
                if (altSelect.value) {
                    setTimeout(() => {
                        const selected = altSelect.options[altSelect.selectedIndex];
                        const slug = selected?.getAttribute('data-slug') || '';

                        // ✅ Yayın tipi ID'sini direkt kullan
                        let yayin = null;
                        if (yayinTipiSelect?.value) {
                            yayin = yayinTipiSelect.value;
                        }

                        if (slug) {
                            console.log('📌 Sayfa yüklemede seçili kategori var:', {
                                slug,
                                yayin
                            });
                            this.loadFeatures(slug, yayin);
                        }
                    }, 500);
                }

                console.log('✅ FeaturesManager initialized (simplified)');
            },

            // ✅ REMOVED: _setupStep2Observer - features-dynamic artık form sonunda, her zaman DOM'da

            async loadFeatures(kategoriSlug, yayinTipi = null) {
                if (!kategoriSlug) {
                    console.log('❌ loadFeatures: slug yok');
                    this.reset();
                    return;
                }

                // ✅ FIX: Debounce - Çok hızlı ardışık çağrıları önle
                if (this._loadTimeout) {
                    clearTimeout(this._loadTimeout);
                }

                this._loadTimeout = setTimeout(() => {
                    this._loadFeaturesInternal(kategoriSlug, yayinTipi);
                }, 300); // 300ms debounce
            },

            async _loadFeaturesInternal(kategoriSlug, yayinTipi = null) {
                this.showLoading();

                // ✅ GEMINI ÖNERİSİ: Elementleri her seferinde yeniden query et
                // Bu, DOM'da element kesinlikle bulunmasını sağlar
                this.elements.content = document.getElementById('features-content');
                this.elements.loading = document.getElementById('features-loading');
                this.elements.error = document.getElementById('features-error');
                this.elements.errorMessage = document.getElementById('features-error-message');
                this.elements.emptyState = document.getElementById('features-empty-state');

                // ✅ FIX: Element yoksa yeniden oluştur (başka bir script silmiş olabilir)
                if (!this.elements.content) {
                    console.warn('🔧 #features-content not found, recreating...');
                    const container = document.getElementById('features-container');
                    if (container) {
                        // Eğer container tamamen silindiyse, gerekli elementleri yeniden oluştur
                        const existingContent = container.querySelector('#features-content');
                        if (!existingContent) {
                            const contentDiv = document.createElement('div');
                            contentDiv.id = 'features-content';
                            contentDiv.className = 'space-y-6';
                            container.appendChild(contentDiv);
                            this.elements.content = contentDiv;
                            console.log('✅ #features-content recreated successfully');
                        }
                    }
                }

                console.log('🔄 Elements re-queried:', {
                    content: !!this.elements.content,
                    loading: !!this.elements.loading,
                    error: !!this.elements.error
                });

                const upsContainer = document.getElementById('ups-features-container');
                const featuresContent = this.elements.content;

                if (!upsContainer && !featuresContent) {
                    console.warn('⚠️ Features containers not found in DOM');
                }

                // ✅ Pending request'i iptal et
                if (this._pendingController) {
                    console.log('🛑 Cancelling previous request');
                    this._pendingController.abort();
                }

                const controller = new AbortController();
                this._pendingController = controller;
                const timeoutId = setTimeout(() => {
                    console.warn('⏱️ Request timeout after 10 seconds');
                    controller.abort();
                }, 10000); // ✅ FIX: 10 second timeout

                try {
                    let url;
                    // ✅ Include inactive features for admin/show all features
                    const includeInactive = window.location.pathname.includes('/admin/ilanlar') || window
                        .location.pathname.includes('/admin/ilanlarim');
                    if (window.APIConfig?.features?.byCategory) {
                        url = window.APIConfig.features.byCategory(kategoriSlug, yayinTipi);
                        console.log('🐛 DEBUG FINAL REQUEST URL:', url);
                    } else {
                        url = `/api/v1/admin/features/category/${encodeURIComponent(kategoriSlug)}`;
                        const params = new URLSearchParams();
                        if (yayinTipi) {
                            params.append('yayin_tipi', yayinTipi);
                        }
                        if (includeInactive) {
                            params.append('include_inactive', 'true');
                        }
                        if (params.toString()) {
                            url += `?${params.toString()}`;
                        }
                    }

                    console.log('🌐 API Request:', url);

                    const response = await fetch(url, {
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        signal: controller.signal
                    });

                    const payload = await response.json();

                    // ✅ CRITICAL: Production-safe debug logging
                    // Only log in localhost OR when explicitly enabled via window.__DEBUG_FEATURES
                    const DEBUG = (window.location.hostname === 'localhost' ||
                        window.location.hostname === '127.0.0.1' ||
                        window.__DEBUG_FEATURES === true);

                    if (DEBUG) console.log('✅ API Response:', payload);
                    if (DEBUG) console.log('🔍 API Response structure:', {
                        hasData: !!payload?.data,
                        dataType: typeof payload?.data,
                        isArray: Array.isArray(payload?.data),
                        dataKeys: payload?.data ? Object.keys(payload.data) : []
                    });

                    // ✅ CRITICAL: Unified payload adapter for feature_categories
                    // ONLY support new FeatureAssignment resolver format
                    let finalList = payload?.data?.feature_categories ?? [];

                    // ✅ VALIDATION: Verify response structure
                    if (!payload?.data?.feature_categories) {
                        console.error('❌ FATAL: Response missing feature_categories!', {
                            payload_keys: payload ? Object.keys(payload) : [],
                            data_keys: payload?.data ? Object.keys(payload.data) : [],
                            data_type: typeof payload?.data,
                            is_array: Array.isArray(payload?.data)
                        });
                        this.showError(
                            'Özellikler yüklenemedi: Geçersiz API yanıtı.\n' +
                            'Lütfen sayfayı yenileyin veya admin ile iletişime geçin.'
                        );
                        return;
                    }

                    // ✅ VALIDATION: Verify array type
                    if (!Array.isArray(finalList)) {
                        console.error('❌ FATAL: feature_categories is not an array!', {
                            type: typeof finalList,
                            value: finalList
                        });
                        this.showError(
                            'Özellikler yüklenemedi: Veri formatı hatalı.\n' +
                            'Lütfen sayfayı yenileyin.'
                        );
                        return;
                    }

                    // ✅ CRITICAL: Normalize required field (required vs is_required)
                    // Backend may return 'required', but some legacy code expects 'is_required'
                    finalList = (finalList || []).map(category => ({
                        ...category,
                        features: (category.features || []).map(feature => ({
                            ...feature,
                            // Ensure both keys exist for compatibility
                            is_required: (feature.is_required ?? feature.required) ?
                                true : false,
                            required: (feature.required ?? feature.is_required) ?
                                true : false
                        }))
                    }));

                    // ✅ DEBUG: Log payload structure
                    if (DEBUG) {
                        console.log('📦 Payload structure:', {
                            has_feature_categories: !!payload?.data?.feature_categories,
                            categories_count: finalList.length,
                            metadata_system: payload?.data?.metadata?.system
                        });
                    }

                    // ✅ Verify FeatureAssignment resolver
                    if (payload?.data?.metadata?.system === 'FeatureAssignment') {
                        if (DEBUG) console.log('✅ NEW: FeatureAssignment resolver confirmed');
                    } else {
                        console.warn('⚠️ Unknown resolver system:', payload?.data?.metadata?.system);
                    }

                    if (finalList.length > 0) {
                        this.renderFeatures(finalList);
                    } else {
                        console.warn('⚠️ API returned no features for category:', kategoriSlug);
                        this.showEmptyState(
                            'Kategori Özelliği Bulunmadı',
                            'Bu kategori için henüz özel bir özellik tanımlanmamış. Temel bilgilerle devam edebilirsiniz.',
                            'info'
                        );
                    }
                } catch (err) {
                    // ✅ FIX: AbortController hatası normal - ignore et
                    if (err.name === 'AbortError') {
                        console.log('⏭️ Request cancelled (normal)');
                        return;
                    }
                    console.error('❌ Features error:', err.message);
                    this.showError('Özellikler yüklenirken hata oluştu: ' + err.message);
                } finally {
                    clearTimeout(timeoutId);
                    this._pendingController = null;
                    this.elements.loading?.classList.add('hidden');
                }
            },

            // ✅ SAB: Fallback sistemi kaldırıldı - Artık sadece FeatureAssignment kullanılıyor
            // Boş durum mesajı showEmptyState() metodu ile gösteriliyor

            renderFeatures(input) {
                // ✅ FIX: Elements'i her seferinde yeniden cache'le (DOM değişmiş olabilir)
                this.elements = {
                    emptyState: document.getElementById('features-empty-state'),
                    content: document.getElementById('features-content'),
                    loading: document.getElementById('features-loading'),
                    error: document.getElementById('features-error'),
                    errorMessage: document.getElementById('features-error-message')
                };

                // ✅ NORMALIZE INPUT: Handle both array and {categories: []} format
                const categories = Array.isArray(input) ? input : input?.categories;

                if (!Array.isArray(categories)) {
                    console.error('❌ renderFeatures: Invalid input!', input);
                    this.showError('Özellikler render edilemedi: Veri formatı hatalı.');
                    return;
                }

                // ✅ FIX: Retry mekanizması
                if (!this.elements.content) {
                    const container = document.getElementById('features-container');
                    if (container) {
                        const contentDiv = document.createElement('div');
                        contentDiv.id = 'features-content';
                        // ✅ TABS SUPPORT: Remove space-y-6, we handle spacing inside tabs
                        contentDiv.className=';
                        container.appendChild(contentDiv);
                        this.elements.content = contentDiv;
                    } else if (this._renderRetryCount++ <= 5) {
                        setTimeout(() => this.renderFeatures(input), 200);
                        return;
                    } else {
                        return;
                    }
                }
                this._renderRetryCount = 0;

                this.elements.content.innerHTML = '';

                if (categories.length === 0) {
                    this.reset();
                    return;
                }

                // ✨ PREMIUM UI: TABS IMPLEMENTATION
                const tabsContainer = document.createElement('div');
                tabsContainer.className = 'features-tabs-container';

                // 1. Tab Headers Scrollable Container
                const tabHeaders = document.createElement('div');
                tabHeaders.className = 'flex overflow-x-auto gap-2 border-b border-gray-200 dark:border-gray-700 pb-0.5 mb-6 no-scrollbar';

                // 2. Tab Contents Container
                const tabContents = document.createElement('div');
                tabContents.className = 'min-h-[300px]';

                // Active Tab State tracking
                let activeTabId = null;

                categories.forEach((category, idx) => {
                    // Create unique ID for tab
                    const tabId = `tab-${category.slug || idx}`;
                    const isFirst = idx === 0;

                    if (isFirst) activeTabId = tabId;

                    // --- Tab Button ---
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = `
                        flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all duration-200 whitespace-nowrap border-b-2
                        ${isFirst
                            ? 'border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-900/10'
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800'
                        }
                    `;
                    btn.setAttribute('data-tab-target', tabId);
                    btn.innerHTML = `
                        <i class="${category.icon || 'fas fa-layer-group'} ${isFirst ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400'}"></i>
                        ${this.escape(category.name)}
                        <span class="ml-1.5 px-1.5 py-0.5 text-[10px] rounded-full ${isFirst ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'}">
                            ${category.features?.length || 0}
                        </span>
                    `;

                    // Tab Click Handler
                    btn.addEventListener('click', () => {
                        // Reset all buttons
                        tabHeaders.querySelectorAll('button').forEach(b => {
                            b.className = 'flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all duration-200 whitespace-nowrap border-b-2 border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800';
                            b.querySelector('i').className = `${category.icon || 'fas fa-layer-group'} text-gray-400`;
                            const badge = b.querySelector('span');
                            badge.className = 'ml-1.5 px-1.5 py-0.5 text-[10px] rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400';
                        });

                        // Activate this button
                        btn.className = 'flex items-center gap-2 px-4 py-2.5 text-sm font-medium rounded-t-lg transition-all duration-200 whitespace-nowrap border-b-2 border-blue-600 text-blue-600 dark:border-blue-500 dark:text-blue-400 bg-blue-50/50 dark:bg-blue-900/10';
                        btn.querySelector('i').className = `${category.icon || 'fas fa-layer-group'} text-blue-600 dark:text-blue-400`;
                        const badge = btn.querySelector('span');
                        badge.className = 'ml-1.5 px-1.5 py-0.5 text-[10px] rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300';

                        // Switch Panel
                        tabContents.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
                        document.getElementById(tabId)?.classList.remove('hidden');
                    });

                    tabHeaders.appendChild(btn);

                    // --- Tab Panel ---
                    const panel = document.createElement('div');
                    panel.id = tabId;
                    panel.className = `tab-panel ${isFirst ? '' : 'hidden'} animate-fadeIn`;

                    // Render Grid inside Panel
                    const categoryEl = this.createCategoryElement(category, true); // true = content only, no header
                    if (categoryEl) {
                        panel.appendChild(categoryEl);
                    }

                    tabContents.appendChild(panel);
                });

                tabsContainer.appendChild(tabHeaders);
                tabsContainer.appendChild(tabContents);
                this.elements.content.appendChild(tabsContainer);

                this.elements.emptyState?.classList.add('hidden');
                this.elements.loading?.classList.add('hidden');
                this.elements.error?.classList.add('hidden');
                this.elements.content.classList.remove('hidden');

                const aiSuggestAllBtn = document.getElementById('ai-suggest-all-features');
                if (aiSuggestAllBtn) aiSuggestAllBtn.classList.remove('hidden');
            },

            createCategoryElement(category) {
                const div = document.createElement('div');
                div.className =
                    'bg-gradient-to-br from-lime-50 to-green-50 dark:from-lime-900/20 dark:to-green-900/20 rounded-lg p-6';

                const header = document.createElement('h4');
                header.className =
                    'text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4 flex items-center';
                header.innerHTML = `
                    <i class="${category.icon || 'fas fa-star'} mr-2 text-lime-600"></i>
                    <span>${this.escape(category.name)}</span>
                `;
                div.appendChild(header);

                const grid = document.createElement('div');
                grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4';

                if (category.features && Array.isArray(category.features) && category.features.length > 0) {
                    category.features.forEach((feature, featIdx) => {
                        const featureEl = this.createFeatureElement(feature);
                        if (featureEl) {
                            grid.appendChild(featureEl);
                            // ✅ Only log errors, not every feature
                        } else {
                            console.error(
                                `      ❌ Feature "${feature.name || feature.slug}" element is null!`,
                                feature);
                        }
                    });
                } else {
                    console.warn(`  ⚠️ Category "${category.name}" has no features!`, {
                        category: category,
                        features: category.features,
                        isArray: Array.isArray(category.features),
                        length: category.features?.length,
                        featuresType: typeof category.features
                    });
                    // Boş kategori için placeholder göster
                    const emptyMsg = document.createElement('div');
                    emptyMsg.className =
                        'col-span-full text-sm text-gray-500 dark:text-gray-400 italic p-4 text-center';
                    emptyMsg.textContent =
                        `⚠️ Bu kategori için özellik bulunamadı (features: ${category.features ? (Array.isArray(category.features) ? 'array[' + category.features.length + ']' : typeof category.features) : 'null'})`;
                    grid.appendChild(emptyMsg);
                }

                div.appendChild(grid);
                return div;
            },

            createFeatureElement(feature) {
                if (!feature || !feature.slug) {
                    console.warn('⚠️ createFeatureElement: Invalid feature', feature);
                    return null;
                }

                // ✅ Check if feature is inactive
                const isInactive = feature.aktiflik_durumu === false || feature.aktiflik_durumu === 0;
                const disabledClass = isInactive ? 'opacity-60 cursor-not-allowed' : '';
                const disabledAttr = isInactive ? 'disabled' : '';

                const div = document.createElement('div');
                div.className = `space-y-2 feature-group ${disabledClass}`;

                // ✅ SAB: Get existing value from edit mode
                const existingValue = this.getExistingFeatureValue(feature.slug);

                if (feature.type === 'boolean') {
                    const isChecked = existingValue === '1' || existingValue === 'true' || existingValue ===
                        true;
                    const inactiveBadge = isInactive ?
                        '<span class="ml-2 px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded dark:bg-slate-900">Pasif</span>' :
                        '';
                    div.innerHTML = `
                        <label class="flex items-center justify-between ${isInactive ? '' : 'cursor-pointer'} group">
                            <div class="flex items-center">
                                <input type="checkbox"
                                    name="features[${this.escape(feature.slug)}]"
                                    value="${feature.id}"
                                    id="feature_${feature.id}"
                                    ${isChecked ? 'checked' : ''}
                                    ${disabledAttr}
                                    class="mr-3 rounded focus:ring-lime-500 text-lime-600 transition-all duration-200">
                                <span class="text-sm text-gray-900 dark:text-white dark:text-slate-100">${this.escape(feature.name)}</span>
                                ${inactiveBadge}
                            </div>
                            ${!isInactive ? `<button type="button"
                                class="ai-suggest-btn opacity-0 group-hover:opacity-100 inline-flex items-center px-2 py-1 text-xs font-medium text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded transition-all"
                                onclick="window.FeaturesAI && window.FeaturesAI.suggestSingle('${this.escape(feature.slug)}', document.getElementById('feature_${feature.id}'))"
                                title="AI ile öner">
                                <i class="fas fa-magic"></i>
                            </button>` : ''}
                        </label>
                    `;
                } else if (feature.type === 'number') {
                    div.innerHTML = `
                        <label for="feature_${feature.id}"
                            class="flex items-center justify-between text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                            <span>${this.escape(feature.name)}${isInactive ? ' <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded dark:bg-slate-900">Pasif</span>' : ''}</span>
                            ${!isInactive ? `<button type="button"
                                class="ai-suggest-btn inline-flex items-center px-2 py-1 text-xs font-medium text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded transition-all"
                                onclick="window.FeaturesAI && window.FeaturesAI.suggestSingle('${this.escape(feature.slug)}', document.getElementById('feature_${feature.id}'))"
                                title="AI ile öner">
                                <i class="fas fa-magic mr-1"></i><span class="hidden sm:inline">AI</span>
                            </button>` : ''}
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number"
                                name="features[${this.escape(feature.slug)}]"
                                id="feature_${feature.id}"
                                value="${existingValue || ''}"
                                step="${feature.unit === 'm²' ? '0.01' : '1'}"
                                min="0"
                                ${disabledAttr}
                                class="flex-1 h-11 px-4 border border-gray-300 dark:border-slate-800 bg-white dark:bg-gray-900/50 text-sm text-gray-900 dark:text-slate-100 rounded-xl focus:ring-4 focus:ring-lime-500/10 focus:border-lime-500 transition-all shadow-sm ${disabledClass} dark:shadow-none dark:bg-slate-900 dark:text-white">
                            ${feature.unit ? `<span class="text-sm text-gray-500 dark:text-gray-400">${this.escape(feature.unit)}</span>` : ''}
                        </div>
                    `;
                } else if (feature.type === 'select') {
                    const label = document.createElement('label');
                    label.htmlFor = `feature_${feature.id}`;
                    label.className =
                        'flex items-center justify-between text-sm font-medium text-gray-900 dark:text-white mb-1';

                    const labelText = document.createElement('span');
                    labelText.innerHTML = feature.name + (isInactive ?
                        ' <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded dark:bg-slate-900">Pasif</span>' :
                        '');

                    if (!isInactive) {
                        const aiBtn = document.createElement('button');
                        aiBtn.type = 'button';
                        aiBtn.className =
                            'ai-suggest-btn inline-flex items-center px-2 py-1 text-xs font-medium text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded transition-all';
                        aiBtn.title = 'AI ile öner';
                        aiBtn.innerHTML =
                            '<i class="fas fa-magic mr-1"></i><span class="hidden sm:inline">AI</span>';
                        aiBtn.onclick = () => {
                            if (window.FeaturesAI) {
                                window.FeaturesAI.suggestSingle(feature.slug, document.getElementById(
                                    `feature_${feature.id}`));
                            }
                        };
                        label.appendChild(aiBtn);
                    }

                    label.appendChild(labelText);

                    const select = document.createElement('select');
                    select.name = `features[${feature.slug}]`;
                    select.id = `feature_${feature.id}`;
                    select.disabled = isInactive;
                    select.className=`w-full h-11 px-4 border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900/50 text-sm text-gray-900 dark:text-gray-100 rounded-xl focus:ring-4 focus:ring-lime-500/10 focus:border-lime-500 transition-all shadow-sm cursor-pointer ${disabledClass} dark:shadow-none`;

                    const placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Seçiniz...';
                    select.appendChild(placeholder);

                    if (feature.options && Array.isArray(feature.options)) {
                        feature.options.forEach(opt => {
                            const option = document.createElement('option');
                            option.value = opt;
                            option.textContent = opt;
                            if (existingValue && existingValue === opt) {
                                option.selected = true;
                            }
                            select.appendChild(option);
                        });
                    }

                    div.appendChild(label);
                    div.appendChild(select);
                }

                return div;
            },

            getExistingFeatureValue(featureSlug) {
                // ✅ SAB: Get existing value from edit mode
                if (window.editMode && window.selectedFeatures && window.selectedFeatures[featureSlug]) {
                    return window.selectedFeatures[featureSlug];
                }
                return null;
            },

            showLoading() {
                // ✅ FIX: Elements'i her seferinde yeniden cache'le
                this.elements = {
                    emptyState: document.getElementById('features-empty-state'),
                    content: document.getElementById('features-content'),
                    loading: document.getElementById('features-loading'),
                    error: document.getElementById('features-error'),
                    errorMessage: document.getElementById('features-error-message')
                };

                if (this.elements.emptyState) this.elements.emptyState.classList.add('hidden');
                if (this.elements.content) this.elements.content.classList.add('hidden');
                if (this.elements.error) this.elements.error.classList.add('hidden');
                if (this.elements.loading) this.elements.loading.classList.remove('hidden');

                // ✅ TIMEOUT: 10 saniye sonra otomatik gizle (sonsuz loading'i önle)
                clearTimeout(this._loadTimeout);
                this._loadTimeout = setTimeout(() => {
                    console.warn('⚠️ Loading timeout - hiding loading state');
                    if (this.elements.loading) this.elements.loading.classList.add('hidden');
                }, 10000);
            },

            showEmptyState(title, message, type = 'info') {
                // ✅ NON-BLOCKING: Empty state shows info but doesn't disable wizard
                console.log('📭 showEmptyState (non-blocking):', {
                    title,
                    message,
                    type
                });

                this.elements = {
                    emptyState: document.getElementById('features-empty-state'),
                    content: document.getElementById('features-content'),
                    loading: document.getElementById('features-loading'),
                    error: document.getElementById('features-error'),
                    errorMessage: document.getElementById('features-error-message')
                };

                const emptyState = this.elements.emptyState;
                if (emptyState) {
                    emptyState.classList.remove('hidden', 'bg-blue-50', 'dark:bg-blue-900/20', 'bg-yellow-50',
                        'dark:bg-yellow-900/20');
                    emptyState.classList.add(type === 'warning' ? 'bg-yellow-50' : 'bg-blue-50');
                    if (type === 'warning') emptyState.classList.add('dark:bg-yellow-900/20');
                    else emptyState.classList.add('dark:bg-blue-900/20');

                    const emptyTitle = emptyState.querySelector('.empty-title');
                    const emptyMessage = emptyState.querySelector('.empty-message');
                    const icon = emptyState.querySelector('i');

                    if (emptyTitle && title) emptyTitle.textContent = title;
                    if (emptyMessage && message) emptyMessage.textContent = message;
                    if (icon) {
                        icon.className = type === 'warning' ? 'fas fa-exclamation-circle text-yellow-600 mr-3' :
                            'fas fa-info-circle text-blue-600 mr-3';
                    }

                    emptyState.classList.remove('hidden');
                }

                // 🚀 NON-BLOCKING: Keep content visible (base fields work)
                // Do NOT hide this.elements.content - wizard must function
                if (this.elements.loading) this.elements.loading.classList.add('hidden');
                if (this.elements.error) this.elements.error.classList.add('hidden');

                console.log('✅ Empty state shown (wizard navigation still enabled)');
            },

            showError(message) {
                // ✅ FIX: Elements'i her seferinde yeniden cache'le
                this.elements = {
                    emptyState: document.getElementById('features-empty-state'),
                    content: document.getElementById('features-content'),
                    loading: document.getElementById('features-loading'),
                    error: document.getElementById('features-error'),
                    errorMessage: document.getElementById('features-error-message')
                };

                if (this.elements.emptyState) this.elements.emptyState.classList.add('hidden');
                if (this.elements.content) this.elements.content.classList.add('hidden');
                if (this.elements.loading) this.elements.loading.classList.add('hidden');
                if (this.elements.error) this.elements.error.classList.remove('hidden');
                if (this.elements.errorMessage) this.elements.errorMessage.textContent = message;
            },

            reset() {
                // ✅ FIX: Elements'i her seferinde yeniden cache'le
                this.elements = {
                    emptyState: document.getElementById('features-empty-state'),
                    content: document.getElementById('features-content'),
                    loading: document.getElementById('features-loading'),
                    error: document.getElementById('features-error'),
                    errorMessage: document.getElementById('features-error-message')
                };

                this.featureCategories = [];
                if (this.elements.content) this.elements.content.innerHTML = '';
                if (this.elements.emptyState) this.elements.emptyState.classList.remove('hidden');
                if (this.elements.content) this.elements.content.classList.add('hidden');
                if (this.elements.loading) this.elements.loading.classList.add('hidden');
                if (this.elements.error) this.elements.error.classList.add('hidden');
            },

            escape(str) {
                const div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }
        };

        // Initialize when DOM ready
        console.log('🚀 Features-dynamic blade script loaded, readyState:', document.readyState);

        if (document.readyState === 'loading') {
            console.log('⏳ Waiting for DOMContentLoaded...');
            document.addEventListener('DOMContentLoaded', () => {
                console.log('✅ DOMContentLoaded fired, initializing FeaturesManager...');
                FeaturesManager.init();
            });
        } else {
            console.log('✅ DOM already ready, initializing FeaturesManager immediately...');
            FeaturesManager.init();
        }

        // Expose globally
        window.FeaturesManager = FeaturesManager;
        console.log('📦 FeaturesManager exposed to window.FeaturesManager');
    })();
</script>

{{-- 🎨 AI Features Styling --}}
<style>
    /* AI Suggested Animation */
    @keyframes ai-pulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(168, 85, 247, 0.4);
        }

        50% {
            box-shadow: 0 0 0 10px rgba(168, 85, 247, 0);
        }
    }

    .ai-suggested {
        animation: ai-pulse 1s ease-in-out 2;
        border-color: #a855f7 !important;
    }

    /* AI Loading State */
    .ai-loading {
        position: relative;
        opacity: 0.6;
        pointer-events: none;
    }

    .ai-loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 40px;
        height: 40px;
        border: 3px solid #f3f4f6;
        border-top-color: #a855f7;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        to {
            transform: translate(-50%, -50%) rotate(360deg);
        }
    }

    /* AI Button Hover Effects */
    .ai-suggest-btn {
        position: relative;
        overflow: hidden;
    }

    .ai-suggest-btn::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        border-radius: 50%;
        background: rgba(168, 85, 247, 0.1);
        transform: translate(-50%, -50%);
        transition: width 0.6s, height 0.6s;
    }

    .ai-suggest-btn:hover::before {
        width: 300px;
        height: 300px;
    }

    /* Feature Group Hover */
    .feature-group:hover {
        transform: translateY(-1px);
        transition: transform 0.2s ease;
    }
</style>
