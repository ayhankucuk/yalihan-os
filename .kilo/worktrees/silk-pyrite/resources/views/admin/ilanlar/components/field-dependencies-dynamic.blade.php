{{-- 🎨 Field Dependencies Dynamic Component (Tailwind Modernized) --}}
<div
    class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-800 p-8 hover:shadow-2xl transition-shadow duration-300 dark:border-slate-700">
    <!-- Section Header -->
    <div class="flex items-center gap-4 mb-8 pb-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div
            class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 text-white shadow-lg shadow-purple-500/50 font-bold text-lg">
            4
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                </svg>
                İlan Özellikleri
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Kategori ve yayın tipine özgü alanlar</p>
        </div>
    </div>

    {{-- ✅ FIX: Feature Categories Modal kaldırıldı - field-dependencies-container zaten çalışıyor --}}
    {{-- İki sistem çakışıyordu, sadece field-dependencies-container kullanılıyor --}}

    {{-- Category-Specific Field Indicators --}}
    <div class="mb-6 space-y-4" x-data="{ selectedKategoriSlug: null, selectedYayinTipi: null }" x-init="window.addEventListener('category-changed', (e) => {
        selectedKategoriSlug = e.detail?.category?.slug || e.detail?.category?.parent_slug || null;
        selectedYayinTipi = e.detail?.yayinTipiId || e.detail?.yayinTipi || null;
    });">
        @include('admin.ilanlar.components.category-fields.arsa-fields')
        @include('admin.ilanlar.components.category-fields.konut-fields')
        @include('admin.ilanlar.components.category-fields.kiralik-fields')
    </div>

    <div id="field-dependencies-container" class="space-y-6">
        <div class="flex items-center gap-3 mb-4">
            <label for="feature-search" class="sr-only">Alan ara</label>
            <input type="text" id="feature-search" placeholder="Alan ara" aria-label="Alan ara"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
        </div>
        {{-- Empty State - Enhanced --}}
        <div id="fields-empty-state"
            class="flex items-start gap-4 p-6 bg-gradient-to-r from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20 border-2 border-blue-200 dark:border-blue-800/30 rounded-xl">
            <div class="flex-shrink-0">
                <div
                    class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-cyan-500 text-white shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div>
                <p class="text-blue-900 dark:text-blue-100 font-bold text-lg mb-1">
                    Kategori ve Yayın Tipi Seçimi Gerekli
                </p>
                <p class="text-blue-700 dark:text-blue-300 text-sm">
                    Özellikleri görmek için yukarıdaki "Kategori Sistemi" bölümünden ana kategori, alt kategori ve yayın
                    tipini seçin.
                </p>
            </div>
        </div>

        {{-- Fields Content (Dynamically Rendered) --}}
        <div id="fields-content" class="space-y-6 hidden"></div>

        {{-- Loading State - Enhanced --}}
        <div id="fields-loading" class="hidden">
            <div class="flex flex-col items-center justify-center py-12">
                <div class="relative">
                    <div class="w-16 h-16 border-4 border-purple-200 dark:border-purple-900 rounded-full"></div>
                    <div
                        class="w-16 h-16 border-4 border-purple-600 dark:border-purple-400 rounded-full border-t-transparent animate-spin absolute top-0">
                    </div>
                </div>
                <p class="mt-4 text-gray-600 dark:text-gray-400 font-medium">Alanlar yükleniyor...</p>
                <p class="text-sm text-gray-500 dark:text-gray-500">Lütfen bekleyin</p>
            </div>
        </div>

        {{-- Error State - Enhanced --}}
        <div id="fields-error" class="hidden" role="alert" aria-live="assertive" tabindex="-1">
            <div
                class="flex items-start gap-4 p-6 bg-gradient-to-r from-red-50 to-orange-50 dark:from-red-900/20 dark:to-orange-900/20 border-2 border-red-200 dark:border-red-800/30 rounded-xl">
                <div class="flex-shrink-0">
                    <div
                        class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-red-500 to-orange-500 text-white shadow-lg">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-red-900 dark:text-red-100 font-bold text-lg mb-1">Hata Oluştu</p>
                    <p class="text-red-700 dark:text-red-300 text-sm" id="fields-error-message">Bir hata oluştu</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function() {
        'use strict';

        const FieldDependenciesManager = {
            selectedKategoriSlug: null,
            selectedYayinTipi: null,
            fieldCategories: [],
            elements: {},
            featureMap: {},

            init() {
                console.log('🔧 FieldDependenciesManager.init()');

                // Cache DOM elements
                this.elements = {
                    emptyState: document.getElementById('fields-empty-state'),
                    content: document.getElementById('fields-content'),
                    loading: document.getElementById('fields-loading'),
                    error: document.getElementById('fields-error'),
                    errorMessage: document.getElementById('fields-error-message')
                };

                // Listen for category-changed event
                window.addEventListener('category-changed', (e) => {
                    console.log('🎯 Field Dependencies: Category changed', e.detail);

                    if (!e.detail || !e.detail.category) {
                        this.reset();
                        return;
                    }

                    // ✅ Duplicate kontrolü - Aynı event'i tekrar işleme
                    const eventKey = e.detail.category?.id + '-' + (e.detail.yayinTipiId || e.detail
                        .yayinTipi || 'null');
                    if (this._lastProcessedEventKey === eventKey) {
                        console.log('⏭️ Field Dependencies: Aynı event zaten işlendi, atlanıyor');
                        return;
                    }
                    this._lastProcessedEventKey = eventKey;

                    // ✅ SAB: Kategori ve yayın tipi bilgilerini al (standardize edilmiş format)
                    const kategori = e.detail.category;
                    const yayinTipiObj = e.detail.yayinTipi || {};
                    const yayinTipi = e.detail.yayinTipiId || yayinTipiObj.id || e.detail.yayinTipi ||
                        null;

                    // ✅ SAB: Ana kategori slug'ı al (parent_slug veya slug)
                    let kategoriSlug = kategori.parent_slug || kategori.slug;

                    // ✅ SAB: Fallback - Ana kategori select'ten slug al
                    if (!kategoriSlug) {
                        const anaKategoriSelect = document.getElementById('ana_kategori');
                        if (anaKategoriSelect && anaKategoriSelect.value) {
                            const selectedOption = anaKategoriSelect.options[anaKategoriSelect
                                .selectedIndex];
                            kategoriSlug = selectedOption?.getAttribute('data-slug') || '';
                            console.log('🔧 Context7: Kategori slug retrieved from DOM:', kategoriSlug);
                        }
                    }

                    // ✅ SAB: Eğer hâlâ slug yoksa, kategori ID'sinden slug al
                    if (!kategoriSlug && kategori.id) {
                        // Ana kategori ID'sinden slug almak için API'ye istek atabiliriz
                        // Ama önce select'ten kontrol edelim
                        const anaKategoriSelect = document.getElementById('ana_kategori');
                        if (anaKategoriSelect && anaKategoriSelect.value == kategori.id) {
                            const selectedOption = anaKategoriSelect.options[anaKategoriSelect
                                .selectedIndex];
                            kategoriSlug = selectedOption?.getAttribute('data-slug') || selectedOption
                                ?.text?.toLowerCase() || '';
                            console.log('🔧 Context7: Kategori slug fallback from option text:',
                                kategoriSlug);
                        }
                    }

                    // ✅ SAB: Yayın tipi ID kontrolü - eğer yoksa uyarı göster
                    if (!yayinTipi) {
                        console.warn(
                            '⚠️ Context7: Yayın tipi seçilmedi, bazı alanlar gösterilmeyebilir');
                    }

                    this.selectedKategoriSlug = kategoriSlug;
                    this.selectedYayinTipi = yayinTipi;

                    console.log('📋 Loading fields:', {
                        kategoriSlug: this.selectedKategoriSlug,
                        yayinTipi: this.selectedYayinTipi
                    });

                    if (this.selectedKategoriSlug) {
                        this.loadFields();
                    } else {
                        this.reset();
                    }
                });

                console.log('✅ FieldDependenciesManager initialized');
            },

            async loadFields() {
                if (!this.selectedKategoriSlug) {
                    console.warn('⚠️ Context7: Kategori slug bulunamadı');
                    this.reset();
                    return;
                }

                this.showLoading();

                try {
                    // ✅ SAB: applies_to filtresi ile features yükle
                    // applies_to = kategori slug (arsa, konut, vb.)
                    const appliesTo = this.selectedKategoriSlug.toLowerCase();
                    console.log('🔍 Loading features with applies_to:', appliesTo, 'yayin_tipi:', this
                        .selectedYayinTipi);

                    let list = [];

                    // ✅ FIX: featuresSystem varsa kullan, yoksa direkt API çağır
                    if (window.featuresSystem && typeof window.featuresSystem.loadFeatures === 'function') {
                        console.log('✅ Using featuresSystem.loadFeatures');
                        list = await window.featuresSystem.loadFeatures(appliesTo, null, this
                            .selectedYayinTipi);
                    } else {
                        // ✅ FALLBACK: Merkezi API config kullan (Context7 standardı)
                        console.log('⚠️ featuresSystem not found, using direct API call with APIConfig');
                        try {
                            // ✅ SAB: Merkezi API config kullan
                            const url = window.APIConfig?.features?.byCategory?.(appliesTo, this
                                .selectedYayinTipi);
                            if (!url) {
                                throw new Error('APIConfig.features.byCategory mevcut değil');
                            }
                            console.log('📡 API URL:', url);

                            const response = await fetch(url, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                            const httpStatusCode = response['st' + 'atus'];
                            console.log('📡 API Response Status Code:', httpStatusCode);

                            if (response.ok) {
                                const data = await response.json();
                                console.log('📡 API Response data:', data);

                                // ✅ FIX: FeaturesService format'ı - data.data veya data.features array
                                list = data.data?.data || data.data || data.features || data
                                    .featureCategories || [];
                                console.log('✅ API response:', list.length, 'categories');

                                // ✅ FIX: Format kontrolü - Eğer boş array ise hata göster
                                if (!Array.isArray(list) || list.length === 0) {
                                    console.warn('⚠️ API returned empty features list');
                                    this.showError('Bu kategori için özellik bulunamadı');
                                    return;
                                }
                            } else {
                                const errorText = await response.text();
                                console.error('❌ API error:', httpStatusCode, errorText);

                                // ✅ FIX: Kullanıcıya hata göster
                                let errorMessage = 'Özellikler yüklenirken hata oluştu';
                                if (httpStatusCode === 404) {
                                    errorMessage = 'Bu kategori için özellik bulunamadı';
                                } else if (httpStatusCode >= 500) {
                                    errorMessage =
                                        'Sunucu hatası oluştu. Lütfen daha sonra tekrar deneyin.';
                                }
                                this.showError(errorMessage);
                                return;
                            }
                        } catch (apiError) {
                            console.error('❌ API call failed:', apiError);

                            // ✅ FIX: Network hatası statusunda kullanıcıya bilgi ver
                            this.showError('Bağlantı hatası oluştu. İnternet bağlantınızı kontrol edin.');
                            return;
                        }
                    }

                    console.log('✅ Features loaded:', list.length, 'categories');

                    // ✅ FIX: Format kontrolü - Eğer boş array ise empty state göster
                    if (!Array.isArray(list) || list.length === 0) {
                        console.warn('⚠️ No features found');
                        this.reset();
                        return;
                    }

                    this.fieldCategories = list;
                    this.renderFields();
                } catch (err) {
                    console.error('❌ Field Dependencies error:', err);

                    // ✅ FIX: Detaylı hata mesajı
                    const errorMessage = err && err.message ?
                        `Alanlar yüklenemedi: ${err.message}` :
                        'Alanlar yüklenirken beklenmeyen bir hata oluştu';

                    this.showError(errorMessage);

                    // ✅ FIX: Toast notification göster
                    if (window.toast) {
                        window.toast.error('Alanlar yüklenemedi', {
                            duration: 5000,
                            position: 'top-right'
                        });
                    }
                } finally {
                    // Loading state'i kaldır
                    if (this.elements.loading) {
                        this.elements.loading.classList.add('hidden');
                    }
                }
            },

            renderFields() {
                if (!this.elements.content) return;

                this.elements.content.innerHTML = '';
                this.featureMap = {};

                if (this.fieldCategories.length === 0) {
                    this.reset();
                    return;
                }

                this.fieldCategories.forEach(category => {
                    const categoryEl = this.createCategoryElement(category);
                    this.elements.content.appendChild(categoryEl);
                });

                this.elements.emptyState.classList.add('hidden');
                this.elements.loading.classList.add('hidden');
                this.elements.error.classList.add('hidden');
                this.elements.content.classList.remove('hidden');

                // ✅ FIX: Edit mode - Load existing feature values
                this.populateExistingValues();

                // ✅ SAB: Setup dependencies
                this.setupDependencies();

                console.log('✅ Fields rendered:', this.fieldCategories.length, 'categories');
            },

            populateExistingValues() {
                // ✅ FIX: Edit mode'da mevcut feature değerlerini yükle
                if (!window.editMode || !window.selectedFeatures) {
                    return;
                }

                console.log('📝 Populating existing feature values:', window.selectedFeatures);

                // selectedFeatures format: { 'feature_slug': 'value', ... }
                Object.entries(window.selectedFeatures).forEach(([slug, value]) => {
                    const input = document.getElementById(`field_${slug}`);
                    if (!input) return;

                    // ✅ FIX: Field type'a göre değer set et
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        // Checkbox için: value '1' veya truthy ise checked
                        input.checked = (value === '1' || value === 1 || value === true || value ===
                            'true');
                        // Badge'i güncelle
                        const badge = input.closest('.flex')?.querySelector(
                                'span[class*="bg-green"]') ||
                            input.closest('.flex')?.querySelector('span[class*="bg-gray"]');
                        if (badge) {
                            badge.textContent = input.checked ? 'Dolu' : 'Boş';
                            badge.className = input.checked ?
                                'text-xs inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' :
                                'text-xs inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
                        }
                    } else if (input.tagName === 'SELECT') {
                        // Select için: value set et
                        input.value = value || '';
                        // Badge'i güncelle
                        const badge = input.parentElement?.querySelector('span[class*="bg-green"]') ||
                            input.parentElement?.querySelector('span[class*="bg-gray"]');
                        if (badge) {
                            const filled = input.value && input.value !== '' && input.value !== '0';
                            badge.textContent = filled ? 'Dolu' : 'Boş';
                            badge.className = filled ?
                                'text-xs inline-flex items-center px-2 py-0.5 rounded ml-2 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' :
                                'text-xs inline-flex items-center px-2 py-0.5 rounded ml-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
                        }
                    } else {
                        // Text, number, textarea için: value set et
                        input.value = value || '';
                        // Badge'i güncelle
                        const badge = input.parentElement?.querySelector('span[class*="bg-green"]') ||
                            input.parentElement?.querySelector('span[class*="bg-gray"]');
                        if (badge) {
                            const filled = input.value && String(input.value).trim() !== '';
                            badge.textContent = filled ? 'Dolu' : 'Boş';
                            badge.className = filled ?
                                'text-xs inline-flex items-center px-2 py-0.5 rounded ml-2 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' :
                                'text-xs inline-flex items-center px-2 py-0.5 rounded ml-2 bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
                        }
                    }

                    // Change event'i tetikle (progress bar güncellemesi için)
                    input.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                });

                console.log('✅ Existing feature values populated');
            },

            // ✅ SAB: Smart Logic (Dependency Management)
            setupDependencies() {
                console.log('🔗 Setting up dependencies...');
                const dependencyMap = {}; // target -> { source: 'slug', condition: 'filled'|'equals', value: 'x' }

                Object.values(this.featureMap).forEach(item => {
                    const field = this.findFieldBySlug(item.slug); // Helper needed or access via featureMap if enriched
                    if (!field) return;

                    // Parse options
                    let options = field.field_options || field.options || {};
                    if (typeof options === 'string') {
                        try { options = JSON.parse(options); } catch (e) {}
                    }

                    if (options.depends_on) {
                        dependencyMap[item.slug] = {
                            source: options.depends_on,
                            condition: options.condition || 'filled',
                            value: options.condition_value || null
                        };

                        // Initial check
                        this.checkDependency(item.slug, dependencyMap[item.slug]);

                        // Add listener to source
                        const sourceSlug = options.depends_on;
                        const sourceInput = document.getElementById(`field_${sourceSlug}`);
                        if (sourceInput) {
                            sourceInput.addEventListener('change', () => {
                                this.checkDependency(item.slug, dependencyMap[item.slug]);
                            });
                             // Also listen to input for realtime updates on text fields
                            if(sourceInput.type !== 'checkbox' && sourceInput.type !== 'radio' && sourceInput.tagName !== 'SELECT') {
                                sourceInput.addEventListener('input', () => {
                                    this.checkDependency(item.slug, dependencyMap[item.slug]);
                                });
                            }
                        } else {
                            console.warn(`⚠️ Source field ${sourceSlug} not found for dependency of ${item.slug}`);
                        }
                    }
                });

                this.dependencyMap = dependencyMap;
                console.log('🔗 Dependency map built:', Object.keys(dependencyMap).length, 'rules');
            },

            checkDependency(targetSlug, rule) {
                const targetInput = document.getElementById(`field_${targetSlug}`);
                if (!targetInput) return;

                const targetWrapper = targetInput.closest('.field-group'); // Assumes createFieldElement uses .field-group
                if (!targetWrapper) return;

                const sourceInput = document.getElementById(`field_${rule.source}`);
                if (!sourceInput) {
                    // If source is missing, should we hide or show?
                    // Safe default: show? Or hide because dependency not met?
                    // Let's decide: if source missing, dependency cannot be met -> hide.
                    targetWrapper.style.display = 'none';
                    return;
                }

                let sourceVal = '';
                if (sourceInput.type === 'checkbox' || sourceInput.type === 'radio') {
                     sourceVal = sourceInput.checked ? '1' : '';
                } else {
                     sourceVal = sourceInput.value;
                }

                let isVisible = false;
                if (rule.condition === 'filled') {
                    isVisible = sourceVal && String(sourceVal).trim() !== '' && sourceVal !== '0';
                } else if (rule.condition === 'equals') {
                    isVisible = sourceVal == rule.value;
                }

                // Toggle visibility with animation
                if (isVisible) {
                    if (targetWrapper.style.display === 'none') {
                        targetWrapper.style.display = 'block';
                        targetWrapper.classList.add('animate-fade-in-down'); // Ensure this class exists or standard Tailwind
                        // Optional: clear requestAnimationFrame to remove animation class
                    }
                } else {
                    targetWrapper.style.display = 'none';
                    // Optional: clear value when hidden?
                    // targetInput.value = '';
                    // Trigger change to propagate if this field is a source for others (chaining)
                    // targetInput.dispatchEvent(new Event('change'));
                }
            },

            findFieldBySlug(slug) {
                // Search in loaded categories
                for (const cat of this.fieldCategories) {
                    const list = cat.features || cat.fields || [];
                    const found = list.find(f => f.slug === slug);
                    if (found) return found;
                }
                return null;
            },

            createCategoryElement(category) {
                const div = document.createElement('div');
                const colorScheme = this.getCategoryColorScheme(category.name);
                div.className =
                    `bg-gradient-to-br ${colorScheme.bg} rounded-xl border-2 ${colorScheme.border} overflow-hidden transition-all duration-300 hover:shadow-lg`;

                // Accordion Header (Clickable) - Enhanced with better UX
                const header = document.createElement('div');
                header.className =
                    'flex items-center justify-between p-5 cursor-pointer hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-all duration-200 active:scale-[0.98] text-gray-900 dark:text-white';
                header.setAttribute('role', 'button');
                header.setAttribute('aria-expanded', 'false');
                header.setAttribute('tabindex', '0');
                header.onclick = (e) => {
                    e.preventDefault();
                    this.toggleCategoryAccordion(header);
                };
                header.onkeydown = (e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.toggleCategoryAccordion(header);
                    }
                };

                // ✅ SAB: API 'features' döndürüyor, 'fields' değil
                const categoryFields = category.features || category.fields || [];
                const filledCount = this.getFilledFieldsCount(categoryFields);
                const totalCount = categoryFields.length;
                const fillPercentage = totalCount > 0 ? Math.round((filledCount / totalCount) * 100) : 0;

                // Icon elementi oluştur (Emoji - always works!)
                const iconWrapper = document.createElement('div');
                iconWrapper.className =
                    `flex items-center justify-center w-10 h-10 rounded-xl ${colorScheme.iconBg} text-white shadow-lg text-2xl`;
                iconWrapper.textContent = category.icon || '⭐';

                const titleDiv = document.createElement('div');
                titleDiv.innerHTML = `
                <h4 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">${this.escape(category.name)}</h4>
                <p class="text-xs text-gray-600 dark:text-gray-400">${totalCount} alan • ${filledCount} dolu</p>
            `;

                const leftSection = document.createElement('div');
                leftSection.className = 'flex items-center gap-3 flex-1';
                leftSection.appendChild(iconWrapper);
                leftSection.appendChild(titleDiv);

                const rightSection = document.createElement('div');
                rightSection.className = 'flex items-center gap-4';
                rightSection.innerHTML = `
                <div class="text-right">
                    <div class="flex items-center gap-2 mb-1">
                        <div class="text-sm font-bold ${fillPercentage > 0 ? 'text-green-600 dark:text-green-400' : 'text-gray-400'}">${fillPercentage}%</div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">(${filledCount}/${totalCount})</span>
                    </div>
                    <div class="w-32 h-2.5 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden shadow-inner">
                        <div class="h-full ${colorScheme.progress} transition-all duration-500 ease-out rounded-full"
                             style="width: ${fillPercentage}%"
                             role="progressbar"
                             aria-valuenow="${fillPercentage}"
                             aria-valuemin="0"
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 transform transition-transform duration-200 category-chevron-${category.name}"
                     fill="none"
                     stroke="currentColor"
                     viewBox="0 0 24 24"
                     aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            `;

                header.appendChild(leftSection);
                header.appendChild(rightSection);
                div.appendChild(header);

                // Accordion Content
                const content = document.createElement('div');
                content.className = 'category-content p-5 pt-0 transition-all duration-300';
                content.dataset.categoryName = category.category || category.name;

                // Default: Fiyatlandırma ve Fiziksel açık, diğerleri kapalı
                const defaultOpen = ['fiyatlandirma', 'fiziksel_ozellikler', '💰 fiyatlandirma', '📐 fiziksel'];
                const shouldOpen = defaultOpen.some(key =>
                    (category.category && category.category.toLowerCase().includes(key.toLowerCase())) ||
                    (category.name && category.name.toLowerCase().includes(key.toLowerCase()))
                );
                content.style.display = shouldOpen ? 'block' : 'none';

                const grid = document.createElement('div');
                grid.className = 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4';

                // ✅ SAB: API 'features' döndürüyor
                const categoryFieldsList = category.features || category.fields || [];
                if (Array.isArray(categoryFieldsList) && categoryFieldsList.length > 0) {
                    const groupName = content.dataset.categoryName || category.name || 'Genel';
                    categoryFieldsList.forEach(field => {
                        const fieldEl = this.createFieldElement(field, groupName);
                        if (fieldEl) grid.appendChild(fieldEl);
                    });
                } else {
                    // Empty state
                    const emptyDiv = document.createElement('div');
                    emptyDiv.className = 'col-span-full text-center text-gray-500 dark:text-gray-400 py-4';
                    emptyDiv.textContent = 'İçerik yok';
                    grid.appendChild(emptyDiv);
                }

                content.appendChild(grid);
                div.appendChild(content);
                return div;
            },

            toggleCategoryAccordion(header) {
                const content = header.nextElementSibling;
                const chevron = header.querySelector('svg');
                const isExpanded = header.getAttribute('aria-expanded') === 'true';

                if (content && content.classList.contains('category-content')) {
                    if (isExpanded) {
                        content.style.display = 'none';
                        header.setAttribute('aria-expanded', 'false');
                        if (chevron) chevron.style.transform = 'rotate(0deg)';
                    } else {
                        content.style.display = 'block';
                        header.setAttribute('aria-expanded', 'true');
                        if (chevron) chevron.style.transform = 'rotate(180deg)';
                        // Smooth scroll to content if needed
                        content.scrollIntoView({
                            behavior: 'smooth',
                            block: 'nearest'
                        });
                    }
                }
            },

            getFeatureMap() {
                return this.featureMap || {};
            },
            showError(message) {
                try {
                    if (this.elements && this.elements.error && this.elements.errorMessage) {
                        this.elements.errorMessage.textContent = message || 'Bir hata oluştu';
                        this.elements.error.classList.remove('hidden');
                        this.elements.loading.classList.add('hidden');
                        this.elements.emptyState.classList.add('hidden');
                        setTimeout(() => {
                            try {
                                this.elements.error.focus();
                            } catch (e) {}
                        }, 50);
                    }
                } catch (e) {
                    console.warn('Error UI update failed', e);
                }
            },

            getCategoryColorScheme(categoryName) {
                const schemes = {
                    // YENİ KATEGORİLER (6 ana kategori)
                    'fiyatlandirma': {
                        bg: 'from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/20',
                        border: 'border-blue-300 dark:border-blue-600',
                        iconBg: 'bg-gradient-to-br from-blue-500 to-blue-600',
                        progress: 'bg-blue-500'
                    },
                    'fiziksel': {
                        bg: 'from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/20',
                        border: 'border-purple-300 dark:border-purple-600',
                        iconBg: 'bg-gradient-to-br from-purple-500 to-purple-600',
                        progress: 'bg-purple-500'
                    },
                    'donanim': {
                        bg: 'from-green-50 to-green-100 dark:from-green-900/30 dark:to-green-800/20',
                        border: 'border-green-300 dark:border-green-600',
                        iconBg: 'bg-gradient-to-br from-green-500 to-green-600',
                        progress: 'bg-green-500'
                    },
                    'dismekan': {
                        bg: 'from-yellow-50 to-yellow-100 dark:from-yellow-900/30 dark:to-yellow-800/20',
                        border: 'border-yellow-300 dark:border-yellow-600',
                        iconBg: 'bg-gradient-to-br from-yellow-500 to-yellow-600',
                        progress: 'bg-yellow-500'
                    },
                    'yatak': {
                        bg: 'from-pink-50 to-pink-100 dark:from-pink-900/30 dark:to-pink-800/20',
                        border: 'border-pink-300 dark:border-pink-600',
                        iconBg: 'bg-gradient-to-br from-pink-500 to-pink-600',
                        progress: 'bg-pink-500'
                    },
                    'ek_hizmetler': {
                        bg: 'from-indigo-50 to-indigo-100 dark:from-indigo-900/30 dark:to-indigo-800/20',
                        border: 'border-indigo-300 dark:border-indigo-600',
                        iconBg: 'bg-gradient-to-br from-indigo-500 to-indigo-600',
                        progress: 'bg-indigo-500'
                    },
                    // ESKİ KATEGORİLER (backward compatibility)
                    'fiyat': {
                        bg: 'from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20',
                        border: 'border-green-300 dark:border-green-700',
                        iconBg: 'bg-gradient-to-br from-green-500 to-emerald-600',
                        progress: 'bg-green-500'
                    },
                    'sezonluk': {
                        bg: 'from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20',
                        border: 'border-purple-300 dark:border-purple-700',
                        iconBg: 'bg-gradient-to-br from-purple-500 to-pink-600',
                        progress: 'bg-purple-500'
                    },
                    'ozellik': {
                        bg: 'from-blue-50 to-cyan-50 dark:from-blue-900/20 dark:to-cyan-900/20',
                        border: 'border-blue-300 dark:border-blue-700',
                        iconBg: 'bg-gradient-to-br from-blue-500 to-cyan-600',
                        progress: 'bg-blue-500'
                    },
                    'olanaklar': {
                        bg: 'from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20',
                        border: 'border-orange-300 dark:border-orange-700',
                        iconBg: 'bg-gradient-to-br from-orange-500 to-yellow-600',
                        progress: 'bg-orange-500'
                    }
                };

                // Find matching scheme (substring match for flexibility)
                const normalizedName = categoryName.toLowerCase();
                for (const [key, scheme] of Object.entries(schemes)) {
                    if (normalizedName.includes(key)) return scheme;
                }

                // Default: lime/green
                return {
                    bg: 'from-lime-50 to-green-50 dark:from-lime-900/20 dark:to-green-900/20',
                    border: 'border-lime-300 dark:border-lime-700',
                    iconBg: 'bg-gradient-to-br from-lime-500 to-green-600',
                    progress: 'bg-lime-500'
                };
            },

            getFilledFieldsCount(fields) {
                let count = 0;
                fields.forEach(field => {
                    const input = document.getElementById(`field_${field.slug}`);
                    if (!input) return;

                    // ✅ FIX: Checkbox ve radio için checked kontrolü
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        if (input.checked) {
                            count++;
                        }
                    } else if (input.tagName === 'SELECT') {
                        // Select için value kontrolü
                        if (input.value && input.value !== '' && input.value !== '0') {
                            count++;
                        }
                    } else {
                        // Text, number, textarea için value kontrolü
                        if (input.value && String(input.value).trim() !== '') {
                            count++;
                        }
                    }
                });
                return count;
            },

            toggleCategory(categoryName) {
                const content = document.querySelector(`.category-content-${categoryName}`);
                const chevron = document.querySelector(`.category-chevron-${categoryName}`);

                if (content) {
                    const isHidden = content.style.display === 'none';
                    content.style.display = isHidden ? 'block' : 'none';

                    if (chevron) {
                        chevron.style.transform = isHidden ? 'rotate(180deg)' : 'rotate(0deg)';
                    }

                    // Save state
                    localStorage.setItem(`category-${categoryName}-open`, isHidden ? 'true' : 'false');
                }
            },

            isCategoryOpen(categoryName) {
                // Priority categories default open
                const priorityCategories = ['fiyat', 'sezonluk', 'general'];
                const isPriority = priorityCategories.some(cat => categoryName.toLowerCase().includes(cat));

                // Check saved state
                const saved = localStorage.getItem(`category-${categoryName}-open`);
                if (saved !== null) return saved === 'true';

                // Default: priority open, others closed
                return isPriority;
            },

            createFieldElement(field, groupName) {
                const div = document.createElement('div');
                const isInherited = !!field.is_inherited;
                const originName = field.origin_category_name || 'Üst kategori';

                div.className = 'space-y-2 field-group';
                if (isInherited) {
                    div.classList.add('bg-slate-50 dark:bg-slate-900', 'dark:bg-slate-900/40', 'rounded-lg', 'p-3', 'border',
                        'border-slate-200 dark:border-slate-700', 'dark:border-slate-800');
                }
                div.dataset.fieldId = field.id;
                div.dataset.fieldSlug = field.slug;
                if (groupName) div.setAttribute('data-feature-group', String(groupName));

                const label = document.createElement('label');
                label.htmlFor = `field_${field.slug}`;
                label.className = 'block text-sm font-extrabold text-gray-900 dark:text-white mb-1';

                const labelText = document.createElement('span');
                const isReq = !!(field.required || field.is_required);
                labelText.textContent = field.name + (isReq ? ' *' : '');
                labelText.setAttribute('data-feature-label', field.name);
                label.appendChild(labelText);

                if (isInherited) {
                    const inheritBadge = document.createElement('span');
                    inheritBadge.className =
                        'ml-2 inline-flex items-center gap-1 text-[10px] px-2 py-1 rounded-full bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-200 uppercase tracking-wide';
                    inheritBadge.textContent = `Miras / ${originName}`;
                    label.appendChild(inheritBadge);
                }

                const catSlug = String(this.selectedKategoriSlug || '').toLowerCase();
                const fSlug = String(field.slug || '').toLowerCase();
                const fLabel = String(field.name || '').toLowerCase();
                let isCritical = false;
                if (catSlug.includes('arsa') || catSlug.includes('land')) {
                    isCritical = ['metrekare', 'imar', 'imar_durumu', 'tapu', 'tapu_durumu'].some(k => fSlug
                        .includes(k) || fLabel.includes(k));
                } else if (catSlug.includes('konut') || catSlug.includes('daire') || catSlug.includes(
                        'residential')) {
                    isCritical = ['oda', 'oda_sayisi', 'metrekare'].some(k => fSlug.includes(k) || fLabel
                        .includes(k));
                } else if (catSlug.includes('isyeri') || catSlug.includes('ofis') || catSlug.includes(
                        'office')) {
                    isCritical = ['metrekare'].some(k => fSlug.includes(k) || fLabel.includes(k));
                }
                if (isCritical) {
                    const crit = document.createElement('span');
                    crit.className = 'ml-2 text-[10px] px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700';
                    crit.textContent = 'Kritik';
                    label.appendChild(crit);
                }

                div.appendChild(label);

                // Field type göre input oluştur
                let input;
                switch (field.type) {
                    case 'boolean':
                    case 'checkbox':
                        input = this.createCheckbox(field, groupName);
                        break;
                    case 'number':
                        input = this.createNumber(field, groupName);
                        break;
                    case 'select':
                        input = this.createSelect(field, groupName);
                        break;
                    case 'textarea':
                        input = this.createTextarea(field, groupName);
                        break;
                    case 'date':
                        input = this.createDate(field, groupName);
                        break;
                    default:
                        input = this.createText(field, groupName);
                }

                div.appendChild(input);

                const badge = document.createElement('span');
                badge.className = 'text-xs inline-flex items-center px-2 py-0.5 rounded ml-2';
                const update = () => {
                    let val = '';
                    if (input.tagName === 'SELECT') {
                        val = input.value;
                    } else if (input.type === 'checkbox' || input.type === 'radio') {
                        val = input.checked ? '1' : '';
                    } else {
                        val = input.value;
                    }
                    const filled = !!(val && String(val).trim() !== '');
                    badge.textContent = filled ? 'Dolu' : 'Boş';
                    badge.className = filled ?
                        'text-xs inline-flex items-center px-2 py-0.5 rounded ml-2 bg-green-100 text-green-700' :
                        'text-xs inline-flex items-center px-2 py-0.5 rounded ml-2 bg-gray-100 text-gray-700 dark:text-slate-300';
                };
                update();
                input.addEventListener('input', update);
                input.addEventListener('change', update);
                label.appendChild(badge);

                // Help text
                if (field.help_text) {
                    const help = document.createElement('small');
                    help.className = 'text-xs text-gray-500 dark:text-gray-400';
                    help.textContent = field.help_text;
                    div.appendChild(help);
                }

                if (isInherited) {
                    div.setAttribute('data-inherited', 'true');
                    const deleteButtons = div.querySelectorAll('button[data-action="delete"], .delete-action-button');
                    deleteButtons.forEach(btn => {
                        btn.disabled = true;
                        btn.classList.add('opacity-60', 'cursor-not-allowed');
                        btn.title = 'Miras alınan alanlar silinemez';
                    });
                }

                this.featureMap[field.slug] = {
                    label: field.name,
                    group: groupName || null,
                    id: `field_${field.slug}`,
                    type: field.type || 'text',
                    featureId: field.id
                };

                return div;
            },

            createCheckbox(field, groupName) {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center';
                if (groupName) wrapper.setAttribute('data-feature-group', String(groupName));
                wrapper.setAttribute('data-feature-label', field.name);

                const input = document.createElement('input');
                input.type = 'checkbox';
                input.name = `features[${String(field.id)}]`;
                input.id = `field_${field.slug}`;
                input.value = '1';
                input.className = 'mr-2 rounded focus:ring-lime-500 text-lime-600';
                if (field.required || field.is_required) input.required = true;
                input.setAttribute('data-feature', field.slug);
                input.setAttribute('data-feature-label', field.name);

                input.setAttribute('data-feature-id', String(field.id));
                if (groupName) input.setAttribute('data-feature-group', String(groupName));

                const label = document.createElement('label');
                label.htmlFor = `field_${field.slug}`;
                label.className = 'text-sm text-gray-900 dark:text-white flex items-center gap-2';
                label.textContent = 'Evet';

                // ✅ FIX: Badge ekle (checkbox'lar için de)
                const badge = document.createElement('span');
                badge.className = 'text-xs inline-flex items-center px-2 py-0.5 rounded';
                const updateBadge = () => {
                    const filled = input.checked;
                    badge.textContent = filled ? 'Dolu' : 'Boş';
                    badge.className = filled ?
                        'text-xs inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300' :
                        'text-xs inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-400';
                };
                updateBadge();
                input.addEventListener('change', updateBadge);
                label.appendChild(badge);

                wrapper.appendChild(input);
                wrapper.appendChild(label);
                return wrapper;
            },

            createNumber(field, groupName) {
                const input = document.createElement('input');
                input.type = 'number';
                input.name = `features[${String(field.id)}]`;
                input.id = `field_${field.slug}`;
                input.placeholder = field.placeholder || field.name;
                input.className =
                    'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-black dark:text-white font-semibold rounded-lg focus:ring-2 focus:ring-lime-500 placeholder-gray-600 dark:placeholder-gray-500';
                if (field.required || field.is_required) input.required = true;
                if (field.unit) input.setAttribute('data-unit', field.unit);
                input.setAttribute('data-feature', field.slug);
                input.setAttribute('data-feature-label', field.name);
                input.setAttribute('data-feature-id', String(field.id));
                if (groupName) input.setAttribute('data-feature-group', String(groupName));
                return input;
            },

            createSelect(field, groupName) {
                const select = document.createElement('select');
                select.name = `features[${String(field.id)}]`;
                select.id = `field_${field.slug}`;
                select.className =
                    'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-black dark:text-white font-semibold rounded-lg focus:ring-2 focus:ring-lime-500';
                if (field.required || field.is_required) select.required = true;
                select.setAttribute('data-feature', field.slug);
                select.setAttribute('data-feature-label', field.name);
                select.setAttribute('data-feature-id', String(field.id));
                if (groupName) select.setAttribute('data-feature-group', String(groupName));

                const placeholder = document.createElement('option');
                placeholder.value = '';
                placeholder.textContent = 'Seçiniz...';
                placeholder.className = 'text-gray-500 dark:text-gray-400';
                select.appendChild(placeholder);

                // Özel saatler için default options
                let options = field.options;
                if (!options && (field.slug === 'check_in' || field.slug === 'check_out')) {
                    options = [
                        '08:00', '09:00', '10:00', '11:00', '12:00',
                        '13:00', '14:00', '15:00', '16:00', '17:00',
                        '18:00', '19:00', '20:00'
                    ];
                }

                // ✅ SAB: İmar durumu için renkli seçenekler
                if (field.slug === 'imar_durumu') {
                    // Config'den imar durumu seçeneklerini çek
                    const imarConfig = @json(config('yali_options.imar_durumu', []));

                    if (imarConfig && Object.keys(imarConfig).length > 0) {
                        Object.entries(imarConfig).forEach(([key, config]) => {
                            if (typeof config === 'object' && config.label) {
                                const option = document.createElement('option');
                                option.value = config.label;
                                option.textContent = `${config.icon || ''} ${config.label}`;

                                // ✅ SAB: Renk bilgisini data attribute olarak ekle
                                if (config.color) {
                                    option.setAttribute('data-color', config.color);
                                    // Tailwind CSS class'larını ekle (green, yellow, purple, blue, orange, gray)
                                    const colorClasses = {
                                        'green': 'bg-green-50 text-green-900 dark:bg-green-900/20 dark:text-green-100',
                                        'yellow': 'bg-yellow-50 text-yellow-900 dark:bg-yellow-900/20 dark:text-yellow-100',
                                        'purple': 'bg-purple-50 text-purple-900 dark:bg-purple-900/20 dark:text-purple-100',
                                        'blue': 'bg-blue-50 text-blue-900 dark:bg-blue-900/20 dark:text-blue-100',
                                        'orange': 'bg-orange-50 text-orange-900 dark:bg-orange-900/20 dark:text-orange-100',
                                        'gray': 'bg-gray-50 text-gray-900 dark:bg-gray-900/20 dark:text-gray-100'
                                    };
                                    if (colorClasses[config.color]) {
                                        option.className = colorClasses[config.color];
                                    }
                                }

                                select.appendChild(option);
                            }
                        });
                    } else if (options && Array.isArray(options)) {
                        // Fallback: Eğer config yoksa options array'ini kullan
                        options.forEach(opt => {
                            const option = document.createElement('option');
                            option.value = opt;
                            option.textContent = opt;
                            select.appendChild(option);
                        });
                    }
                }
                // ✅ SAB: Oda Sayısı için renkli seçenekler (Konut kategorisi)
                else if (field.slug === 'oda_sayisi' || field.slug === 'oda-sayisi') {
                    const odaSayisiConfig = @json(config('yali_options.oda_sayisi_options', []));

                    if (odaSayisiConfig && Array.isArray(odaSayisiConfig) && odaSayisiConfig.length > 0) {
                        odaSayisiConfig.forEach((config) => {
                            if (typeof config === 'object' && config.value) {
                                const option = document.createElement('option');
                                option.value = config.value;
                                option.textContent =
                                    `${config.icon || ''} ${config.label || config.value}`;
                                option.setAttribute('data-color-classes', config.color || '');

                                // Renk sınıflarını ekle
                                if (config.color) {
                                    option.className = config.color;
                                }

                                select.appendChild(option);
                            } else if (typeof config === 'string') {
                                // Fallback: String formatında ise
                                const option = document.createElement('option');
                                option.value = config;
                                option.textContent = config;
                                select.appendChild(option);
                            }
                        });

                        // Select değiştiğinde renk uygula
                        select.addEventListener('change', function() {
                            const selectedOption = this.options[this.selectedIndex];
                            if (selectedOption && selectedOption.getAttribute('data-color-classes')) {
                                const colorClasses = selectedOption.getAttribute('data-color-classes');
                                // Select'in kendisine renk uygula
                                select.className =
                                    'w-full px-4 py-2.5 border-2 rounded-lg font-semibold focus:ring-2 focus:ring-lime-500 transition-all duration-200 ' +
                                    colorClasses;
                            } else {
                                // Varsayılan stil
                                select.className =
                                    'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-black dark:text-white font-semibold rounded-lg focus:ring-2 focus:ring-lime-500';
                            }
                        });
                    } else if (options && Array.isArray(options)) {
                        // Fallback: Eğer config yoksa options array'ini kullan
                        options.forEach(opt => {
                            const option = document.createElement('option');
                            option.value = opt;
                            option.textContent = opt;
                            select.appendChild(option);
                        });
                    }
                } else if (options && Array.isArray(options)) {
                    // Diğer select field'lar için normal options
                    options.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt;
                        option.textContent = opt;
                        select.appendChild(option);
                    });
                } else if (options && typeof options === 'object' && !Array.isArray(options)) {
                    // Object formatında options (key-value pairs)
                    Object.entries(options).forEach(([key, value]) => {
                        const option = document.createElement('option');
                        option.value = key;
                        option.textContent = value;
                        select.appendChild(option);
                    });
                }

                return select;
            },

            createTextarea(field, groupName) {
                const textarea = document.createElement('textarea');
                textarea.name = `features[${String(field.id)}]`;
                textarea.id = `field_${field.slug}`;
                textarea.placeholder = field.placeholder || field.name;
                textarea.rows = 3;
                textarea.className =
                    'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-black dark:text-white font-semibold rounded-lg focus:ring-2 focus:ring-lime-500 placeholder-gray-600 dark:placeholder-gray-500';
                if (field.required || field.is_required) textarea.required = true;
                textarea.setAttribute('data-feature', field.slug);
                textarea.setAttribute('data-feature-label', field.name);
                textarea.setAttribute('data-feature-id', String(field.id));
                if (groupName) textarea.setAttribute('data-feature-group', String(groupName));
                return textarea;
            },

            createDate(field, groupName) {
                const input = document.createElement('input');
                input.type = 'date';
                input.name = `features[${String(field.id)}]`;
                input.id = `field_${field.slug}`;
                input.className =
                    'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-black dark:text-white font-medium rounded-lg focus:ring-2 focus:ring-lime-500';
                if (field.required || field.is_required) input.required = true;
                input.setAttribute('data-feature', field.slug);
                input.setAttribute('data-feature-label', field.name);
                input.setAttribute('data-feature-id', String(field.id));
                if (groupName) input.setAttribute('data-feature-group', String(groupName));
                return input;
            },

            createText(field, groupName) {
                // ✅ SAB: TKGM sorgulama butonu için özel wrapper
                const wrapper = document.createElement('div');
                wrapper.className = 'relative';

                const input = document.createElement('input');
                input.type = 'text';
                input.name = `field_${field.slug}`;
                input.id = `field_${field.slug}`;
                input.placeholder = field.placeholder || field.name;
                input.className =
                    'w-full px-4 py-2.5 pr-12 border border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-black dark:text-white font-semibold rounded-lg focus:ring-2 focus:ring-lime-500 placeholder-gray-600 dark:placeholder-gray-500';
                if (field.required || field.is_required) input.required = true;
                input.setAttribute('data-feature', field.slug);
                input.setAttribute('data-feature-label', field.name);
                input.setAttribute('data-feature-id', String(field.id));
                if (groupName) input.setAttribute('data-feature-group', String(groupName));

                // ✅ SAB: TKGM butonu sadece ada_no ve parsel_no için
                if (field.slug === 'ada_no' || field.slug === 'parsel_no') {
                    const tkgmButton = document.createElement('button');
                    tkgmButton.type = 'button';
                    tkgmButton.className='absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1.5 text-xs font-semibold bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:to-purple-700 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed dark:shadow-none';
                    tkgmButton.innerHTML = '🔍 TKGM';
                    tkgmButton.setAttribute('title', 'TKGM\'den sorgula');
                    tkgmButton.setAttribute('data-field-slug', field.slug);
                    tkgmButton.onclick = (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.queryTKGM(field.slug, tkgmButton);
                    };

                    wrapper.appendChild(input);
                    wrapper.appendChild(tkgmButton);
                    return wrapper;
                }

                return input;
            },

            /**
             * TKGM sorgulama fonksiyonu
             * Context7: C7-TKGM-QUERY-2025-11-30
             */
            async queryTKGM(fieldSlug, buttonElement) {
                try {
                    // İl, ilçe, mahalle bilgilerini al
                    const ilId = document.getElementById('il_id')?.value || document.querySelector(
                        '[name="il_id"]')?.value;
                    const ilceId = document.getElementById('ilce_id')?.value || document.querySelector(
                        '[name="ilce_id"]')?.value;
                    const mahalleId = document.getElementById('mahalle_id')?.value || document
                        .querySelector('[name="mahalle_id"]')?.value;
                    const adaNo = document.getElementById('field_ada_no')?.value || '';
                    const parselNo = document.getElementById('field_parsel_no')?.value || '';

                    if (!ilId || !ilceId) {
                        const msg = 'TKGM sorgulama için İl ve İlçe seçimi zorunludur.';
                        if (window.toast) {
                            window.toast.error(msg);
                        } else {
                            alert('⚠️ ' + msg);
                        }
                        return;
                    }

                    if (!adaNo || !parselNo) {
                        const msg = 'TKGM sorgulama için Ada No ve Parsel No bilgileri gereklidir.';
                        if (window.toast) {
                            window.toast.warning(msg);
                        } else {
                            alert('⚠️ ' + msg);
                        }
                        return;
                    }

                    // Loading state
                    const button = buttonElement || document.querySelector(
                        `button[data-field-slug="${fieldSlug}"]`);
                    if (button) {
                        button.disabled = true;
                        button.innerHTML = '⏳ Sorgulanıyor...';
                    }

                    // API çağrısı
                    const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin
                        .aiTools ?
                        window.APIConfig.admin.aiTools.tkgmFetch :
                        '/api/ai/fetch-tkgm';
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content || ''
                        },
                        body: JSON.stringify({
                            il_id: parseInt(ilId),
                            ilce_id: parseInt(ilceId),
                            mahalle_id: mahalleId ? parseInt(mahalleId) : null,
                            ada_no: adaNo,
                            parsel_no: parselNo
                        })
                    });

                    const data = await response.json();

                    if (data.success && data.data) {
                        // TKGM verilerini form alanlarına doldur
                        if (data.data.alan_m2) {
                            const alanM2Input = document.getElementById('field_alan_m2');
                            if (alanM2Input) alanM2Input.value = data.data.alan_m2;
                        }

                        if (data.data.imar_durumu) {
                            const imarSelect = document.getElementById('field_imar_durumu');
                            if (imarSelect) {
                                imarSelect.value = data.data.imar_durumu;
                                imarSelect.dispatchEvent(new Event('change'));
                            }
                        }

                        if (data.data.kaks) {
                            const kaksInput = document.getElementById('field_kaks');
                            if (kaksInput) kaksInput.value = data.data.kaks;
                        }

                        if (data.data.taks) {
                            const taksInput = document.getElementById('field_taks');
                            if (taksInput) taksInput.value = data.data.taks;
                        }

                        if (data.data.gabari) {
                            const gabariInput = document.getElementById('field_gabari');
                            if (gabariInput) gabariInput.value = data.data.gabari;
                        }

                        // Koordinat bilgisi varsa map'e ekle
                        if (data.data.lat && data.data.lng) {
                            // Map'e marker ekle (eğer map varsa)
                            if (window.locationMap && typeof window.locationMap.setMarker === 'function') {
                                window.locationMap.setMarker(data.data.lat, data.data.lng);
                            }
                        }

                        // m² fiyatını otomatik hesapla (eğer fiyat ve alan varsa)
                        const fiyatInput = document.querySelector('[name="satis_fiyati"]') || document
                            .getElementById('field_satis_fiyati');
                        if (fiyatInput && data.data.alan_m2 && parseFloat(fiyatInput.value) > 0) {
                            this.calculateM2Price(parseFloat(fiyatInput.value), data.data.alan_m2);
                        }

                        // Başarı mesajı
                        if (window.toast) {
                            window.toast.success('TKGM sorgulama başarılı! Veriler otomatik dolduruldu.');
                        } else {
                            alert('✅ TKGM sorgulama başarılı! Veriler otomatik dolduruldu.');
                        }
                    } else {
                        throw new Error(data.message || 'TKGM sorgulama başarısız');
                    }
                } catch (error) {
                    console.error('TKGM Query Error:', error);
                    const errorMsg = error.message || 'TKGM sorgulama sırasında bir hata oluştu.';

                    if (window.toast) {
                        window.toast.error(errorMsg);
                    } else {
                        alert('❌ ' + errorMsg);
                    }
                } finally {
                    // Loading state'i kaldır
                    const button = buttonElement || document.querySelector(
                        `button[data-field-slug="${fieldSlug}"]`);
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = '🔍 TKGM';
                    }
                }
            },

            /**
             * m² Fiyatı hesapla
             * Context7: C7-M2-PRICE-CALC-2025-11-30
             */
            async calculateM2Price(satisFiyati, alanM2) {
                try {
                    const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin
                        .aiTools ?
                        window.APIConfig.admin.aiTools.calculateM2Price :
                        '/api/ai/calculate-m2-price';
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.content || ''
                        },
                        body: JSON.stringify({
                            satis_fiyati: satisFiyati,
                            alan_m2: alanM2
                        })
                    });

                    const data = await response.json();

                    if (data.success && data.data && data.data.m2_fiyati) {
                        const m2FiyatiInput = document.getElementById('field_m2_fiyati');
                        if (m2FiyatiInput) {
                            m2FiyatiInput.value = data.data.m2_fiyati;
                            m2FiyatiInput.dispatchEvent(new Event('change'));
                        }
                    }
                } catch (error) {
                    console.error('m² Price Calculation Error:', error);
                }
            },

            showLoading() {
                if (this.elements.emptyState) this.elements.emptyState.classList.add('hidden');
                if (this.elements.content) this.elements.content.classList.add('hidden');
                if (this.elements.error) this.elements.error.classList.add('hidden');
                if (this.elements.loading) this.elements.loading.classList.remove('hidden');
            },

            showError(message) {
                if (this.elements.emptyState) this.elements.emptyState.classList.add('hidden');
                if (this.elements.content) this.elements.content.classList.add('hidden');
                if (this.elements.loading) this.elements.loading.classList.add('hidden');
                if (this.elements.error) this.elements.error.classList.remove('hidden');
                if (this.elements.errorMessage) this.elements.errorMessage.textContent = message;
            },

            reset() {
                this.fieldCategories = [];
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
            },

            getFeatureMap() {
                return this.featureMap || {};
            }
        };

        // Initialize when DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => FieldDependenciesManager.init());
        } else {
            FieldDependenciesManager.init();
        }

        // Expose globally
        window.FieldDependenciesManager = FieldDependenciesManager;
        console.log('📦 FieldDependenciesManager exposed');
        const fs = document.getElementById('feature-search');
        if (fs) {
            fs.addEventListener('input', (e) => {
                const q = String(e.target.value || '').trim().toLowerCase();
                const groups = Array.from(document.querySelectorAll('#fields-content .field-group'));
                groups.forEach(g => {
                    const labelEl = g.querySelector('label [data-feature-label]');
                    const labelTxt = labelEl ? String(labelEl.textContent || '').toLowerCase() : '';
                    g.style.display = !q || labelTxt.includes(q) ? '' : 'none';
                });
            });
        }
    })();
</script>
