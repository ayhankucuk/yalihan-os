/**
 * 🏗️ MODERN CATEGORY WORKFLOW SYSTEM - NEO DESIGN
 * Site/Apartman → Kategori → Alt Kategori → Yayın Tipi akış tasarımı
 * Tarih: 19 Ekim 2025
 * Context7 Compliant & Neo Design System
 */

class ModernCategoryWorkflow {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`[ModernCategoryWorkflow] Container with id "${containerId}" not found`);
            return;
        }

        this.options = {
            // SSOT API Endpoints (P0: Fixed)
            apiBase: '/api/v1',
            sitesEndpoint: '/sites/search',
            categoriesEndpoint: '/categories',
            subcategoriesEndpoint: '/categories/sub',
            publicationTypesEndpoint: '/categories/publication-types',

            // UI Options
            enableAnimations: true,
            showProgressSteps: true,
            enableSmartSuggestions: true,
            compactMode: false,

            // Validation
            requireAllSteps: true,
            validateOnChange: true,

            ...options,
        };

        this.state = {
            currentStep: 1,
            selectedSite: null,
            selectedCategory: null,
            selectedSubcategory: null,
            selectedPublicationType: null,
            isLoading: false,
            validationErrors: [],
        };

        this.cache = {
            sites: [],
            categories: [],
            subcategories: {},
            publicationTypes: {},
        };

        this.init();
    }

    init() {
        this.render();
        this.bindEvents();
        this.loadInitialData();
        console.log('[ModernCategoryWorkflow] Initialized successfully');
    }

    render() {
        this.container.innerHTML = `
            <div class="modern-category-workflow neo-card">
                <!-- Progress Steps -->
                ${this.options.showProgressSteps ? this.renderProgressSteps() : ''}

                <!-- Main Content -->
                <div class="workflow-content space-y-8">
                    ${this.renderSiteSelection()}
                    ${this.renderCategorySelection()}
                    ${this.renderSubcategorySelection()}
                    ${this.renderPublicationTypeSelection()}
                    ${this.renderSmartSuggestions()}
                </div>

                <!-- Action Buttons -->
                <div class="workflow-actions mt-8 flex justify-between items-center">
                    <button class="neo-btn neo-neo-btn neo-btn-secondary" data-action="reset">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Sıfırla
                    </button>

                    <div class="flex space-x-3">
                        <button class="neo-btn neo-btn-accent" data-action="save-draft" style="display: none;">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Taslak Kaydet
                        </button>
                        <button class="neo-btn neo-neo-btn neo-btn-primary" data-action="continue" disabled>
                            <span class="button-text">Devam Et</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.cacheElements();
    }

    renderProgressSteps() {
        return `
            <div class="workflow-progress mb-8">
                <div class="progress-container neo-glass-effect p-6 rounded-xl">
                    <div class="progress-steps flex items-center justify-between">
                        <div class="step ${this.state.currentStep >= 1 ? 'active' : ''} ${
                            this.state.selectedSite ? 'completed' : ''
                        }" data-step="1">
                            <div class="step-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="step-content">
                                <div class="step-title">Site/Apartman</div>
                                <div class="step-description">Emlak lokasyonu</div>
                            </div>
                        </div>

                        <div class="step-connector ${
                            this.state.currentStep >= 2 ? 'active' : ''
                        }"></div>

                        <div class="step ${this.state.currentStep >= 2 ? 'active' : ''} ${
                            this.state.selectedCategory ? 'completed' : ''
                        }" data-step="2">
                            <div class="step-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                </svg>
                            </div>
                            <div class="step-content">
                                <div class="step-title">Ana Kategori</div>
                                <div class="step-description">Emlak türü</div>
                            </div>
                        </div>

                        <div class="step-connector ${
                            this.state.currentStep >= 3 ? 'active' : ''
                        }"></div>

                        <div class="step ${this.state.currentStep >= 3 ? 'active' : ''} ${
                            this.state.selectedSubcategory ? 'completed' : ''
                        }" data-step="3">
                            <div class="step-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                </svg>
                            </div>
                            <div class="step-content">
                                <div class="step-title">Alt Kategori</div>
                                <div class="step-description">Detay türü</div>
                            </div>
                        </div>

                        <div class="step-connector ${
                            this.state.currentStep >= 4 ? 'active' : ''
                        }"></div>

                        <div class="step ${this.state.currentStep >= 4 ? 'active' : ''} ${
                            this.state.selectedPublicationType ? 'completed' : ''
                        }" data-step="4">
                            <div class="step-icon">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                                </svg>
                            </div>
                            <div class="step-content">
                                <div class="step-title">Yayın Tipi</div>
                                <div class="step-description">Satılık/Kiralık</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderSiteSelection() {
        return `
            <div class="workflow-step-container ${
                this.state.currentStep < 1 ? 'disabled' : ''
            }" data-step="site">
                <div class="step-header mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <div class="step-number">1</div>
                        <span>Site/Apartman Seçimi</span>
                        ${
                            this.state.selectedSite
                                ? '<svg class="w-5 h-5 ml-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>'
                                : ''
                        }
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">İlanınızın bulunduğu site veya apartmanı seçin</p>
                </div>

                <div class="site-selection-area">
                    <!-- Search Input -->
                    <div class="search-container mb-4">
                        <div class="relative">
                            <input type="text"
                                   class="site-search-input neo-input pl-10 pr-4"
                                   placeholder="Site veya apartman ara..."
                                   data-search="sites">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                        </div>
                    </div>

                    <!-- Site Results -->
                    <div class="site-results" style="display: none;">
                        <div class="results-header mb-3">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Arama Sonuçları</span>
                            <span class="results-count text-xs text-gray-500 ml-2"></span>
                        </div>
                        <div class="results-list space-y-2 max-h-64 overflow-y-auto">
                            <!-- Results will be populated here -->
                        </div>
                    </div>

                    <!-- Selected Site Display -->
                    <div class="selected-site" style="display: none;">
                        <div class="selected-item-card neo-glass-effect p-4 rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="site-icon">
                                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <div class="site-name font-semibold text-gray-900 dark:text-white"></div>
                                        <div class="site-address text-sm text-gray-500 dark:text-gray-400"></div>
                                    </div>
                                </div>
                                <button class="change-site-btn neo-btn neo-btn-sm neo-neo-btn neo-btn-secondary">
                                    Değiştir
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Add New Site Option -->
                    <div class="add-new-site mt-4">
                        <button class="add-site-btn neo-btn neo-btn-outline w-full justify-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Yeni Site/Apartman Ekle
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    renderCategorySelection() {
        return `
            <div class="workflow-step-container ${
                this.state.currentStep < 2 ? 'disabled' : ''
            }" data-step="category">
                <div class="step-header mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <div class="step-number">2</div>
                        <span>Ana Kategori</span>
                        ${
                            this.state.selectedCategory
                                ? '<svg class="w-5 h-5 ml-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>'
                                : ''
                        }
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Emlak türünü belirleyin</p>
                </div>

                <div class="category-selection-area">
                    <div class="categories-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Categories will be populated here -->
                    </div>
                </div>
            </div>
        `;
    }

    renderSubcategorySelection() {
        return `
            <div class="workflow-step-container ${
                this.state.currentStep < 3 ? 'disabled' : ''
            }" data-step="subcategory">
                <div class="step-header mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <div class="step-number">3</div>
                        <span>Alt Kategori</span>
                        ${
                            this.state.selectedSubcategory
                                ? '<svg class="w-5 h-5 ml-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>'
                                : ''
                        }
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Emlak detay türünü seçin</p>
                </div>

                <div class="subcategory-selection-area">
                    <div class="subcategories-grid grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <!-- Subcategories will be populated here -->
                    </div>
                </div>
            </div>
        `;
    }

    renderPublicationTypeSelection() {
        return `
            <div class="workflow-step-container ${
                this.state.currentStep < 4 ? 'disabled' : ''
            }" data-step="publication">
                <div class="step-header mb-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <div class="step-number">4</div>
                        <span>Yayın Tipi</span>
                        ${
                            this.state.selectedPublicationType
                                ? '<svg class="w-5 h-5 ml-2 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>'
                                : ''
                        }
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">İlan tipini belirleyin</p>
                </div>

                <div class="publication-type-selection-area">
                    <div class="publication-types-grid grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Publication types will be populated here -->
                    </div>
                </div>
            </div>
        `;
    }

    renderSmartSuggestions() {
        if (!this.options.enableSmartSuggestions) return '';

        return `
            <div class="smart-suggestions-container" style="display: none;">
                <div class="suggestions-header mb-4">
                    <h4 class="text-md font-semibold text-gray-900 dark:text-white flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        Akıllı Öneriler
                    </h4>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Size uygun kategoriler öneriyoruz</p>
                </div>

                <div class="suggestions-list">
                    <!-- Smart suggestions will be populated here -->
                </div>
            </div>
        `;
    }

    cacheElements() {
        // Search inputs
        this.elements = {
            siteSearchInput: this.container.querySelector('.site-search-input'),
            siteResults: this.container.querySelector('.site-results'),
            selectedSite: this.container.querySelector('.selected-site'),
            categoriesGrid: this.container.querySelector('.categories-grid'),
            subcategoriesGrid: this.container.querySelector('.subcategories-grid'),
            publicationTypesGrid: this.container.querySelector('.publication-types-grid'),
            smartSuggestions: this.container.querySelector('.smart-suggestions-container'),
            continueBtn: this.container.querySelector('[data-action="continue"]'),
            saveDraftBtn: this.container.querySelector('[data-action="save-draft"]'),
            resetBtn: this.container.querySelector('[data-action="reset"]'),
        };
    }

    bindEvents() {
        // Site search
        if (this.elements.siteSearchInput) {
            this.elements.siteSearchInput.addEventListener(
                'input',
                this.debounce((e) => this.handleSiteSearch(e.target.value), 300)
            );
        }

        // Button actions
        this.container.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]')?.dataset.action;
            if (action) {
                this.handleAction(action, e);
            }

            // Category selections
            const categoryCard = e.target.closest('[data-category]');
            if (categoryCard) {
                this.selectCategory(categoryCard.dataset.category);
            }

            const subcategoryCard = e.target.closest('[data-subcategory]');
            if (subcategoryCard) {
                this.selectSubcategory(subcategoryCard.dataset.subcategory);
            }

            const publicationCard = e.target.closest('[data-publication-type]');
            if (publicationCard) {
                this.selectPublicationType(publicationCard.dataset.publicationType);
            }
        });
    }

    async loadInitialData() {
        try {
            this.state.isLoading = true;
            await this.loadCategories();
            this.state.isLoading = false;
        } catch (error) {
            console.error('[ModernCategoryWorkflow] Failed to load initial data:', error);
            this.state.isLoading = false;
        }
    }

    async handleSiteSearch(query) {
        if (query.length < 2) {
            this.elements.siteResults.style.display = 'none';
            return;
        }

        try {
            const url =
                window.APIConfig &&
                window.APIConfig.admin &&
                window.APIConfig.admin.sites &&
                window.APIConfig.admin.sites.search
                    ? `${window.APIConfig.admin.sites.search}?q=${encodeURIComponent(query)}`
                    : `${this.options.apiBase}${this.options.sitesEndpoint}?q=${encodeURIComponent(query)}`;
            const response = await fetch(url);
            const data = await response.json();

            this.displaySiteResults(data.data || []);
        } catch (error) {
            console.error('[ModernCategoryWorkflow] Site search failed:', error);
        }
    }

    displaySiteResults(sites) {
        const resultsList = this.elements.siteResults.querySelector('.results-list');
        const resultsCount = this.elements.siteResults.querySelector('.results-count');

        if (sites.length === 0) {
            resultsList.innerHTML = `
                <div class="no-results text-center py-8">
                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">Site bulunamadı</p>
                    <button class="neo-btn neo-btn-sm neo-neo-btn neo-btn-primary mt-3" data-action="add-new-site">
                        Yeni Site Ekle
                    </button>
                </div>
            `;
        } else {
            resultsList.innerHTML = sites
                .map(
                    (site) => `
                <div class="site-result-item neo-glass-effect p-3 rounded-lg cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                     data-site-id="${site.id}">
                    <div class="flex items-center space-x-3">
                        <div class="site-type-icon">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        </div>
                        <div class="flex-1">
                            <div class="site-name font-medium text-gray-900 dark:text-white">${
                                site.name
                            }</div>
                            <div class="site-address text-sm text-gray-500 dark:text-gray-400">${
                                site.address
                            }</div>
                        </div>
                        <div class="site-meta text-xs text-gray-400">
                            <div>${site.unit_count || 0} ünite</div>
                            <div>${site.available_units || 0} müsait</div>
                        </div>
                    </div>
                </div>
            `
                )
                .join('');
        }

        resultsCount.textContent = `(${sites.length} sonuç)`;
        this.elements.siteResults.style.display = 'block';

        // Bind site selection events
        resultsList.querySelectorAll('.site-result-item').forEach((item) => {
            item.addEventListener('click', () => {
                const siteId = item.dataset.siteId;
                const siteData = sites.find((s) => s.id == siteId);
                this.selectSite(siteData);
            });
        });
    }

    selectSite(siteData) {
        this.state.selectedSite = siteData;

        // Update UI
        const siteNameEl = this.elements.selectedSite.querySelector('.site-name');
        const siteAddressEl = this.elements.selectedSite.querySelector('.site-address');

        if (siteNameEl) siteNameEl.textContent = siteData.name;
        if (siteAddressEl) siteAddressEl.textContent = siteData.address;

        // Show selected site, hide results
        this.elements.selectedSite.style.display = 'block';
        this.elements.siteResults.style.display = 'none';
        this.elements.siteSearchInput.value = '';

        // Advance to next step
        this.advanceToStep(2);
        this.updateProgressSteps();
        this.validateForm();
    }

    async loadCategories() {
        try {
            const url =
                window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.categories
                    ? window.APIConfig.admin.categories
                    : `${this.options.apiBase}${this.options.categoriesEndpoint}`;
            const response = await fetch(url);
            const data = await response.json();

            this.cache.categories = data.data || [];
            this.displayCategories();
        } catch (error) {
            console.error('[ModernCategoryWorkflow] Failed to load categories:', error);
        }
    }

    displayCategories() {
        const categoriesHTML = this.cache.categories
            .map(
                (category) => `
            <div class="category-card neo-glass-effect p-4 rounded-lg cursor-pointer hover:shadow-lg transition-all ${
                this.state.selectedCategory?.id === category.id
                    ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : ''
            }"
                 data-category="${category.id}">
                <div class="category-icon text-2xl mb-2">${this.getCategoryIcon(
                    category.slug
                )}</div>
                <div class="category-name font-semibold text-gray-900 dark:text-white mb-1">${
                    category.name
                }</div>
                <div class="category-description text-sm text-gray-500 dark:text-gray-400">${
                    category.description || ''
                }</div>
                <div class="category-stats text-xs text-gray-400 mt-2">
                    ${category.subcategories_count || 0} alt kategori
                </div>
            </div>
        `
            )
            .join('');

        this.elements.categoriesGrid.innerHTML = categoriesHTML;
    }

    getCategoryIcon(slug) {
        const icons = {
            konut: '🏠',
            isyeri: '🏢',
            arsa: '🌍',
            yazlik: '🏖️',
            turistik: '🏨',
        };
        return icons[slug] || '🏗️';
    }

    async selectCategory(categoryId) {
        const category = this.cache.categories.find((c) => c.id == categoryId);
        if (!category) return;

        this.state.selectedCategory = category;
        this.displayCategories(); // Refresh to show selection

        // Load subcategories
        await this.loadSubcategories(categoryId);

        // Advance to next step
        this.advanceToStep(3);
        this.updateProgressSteps();
        this.validateForm();
    }

    async loadSubcategories(categoryId) {
        try {
            const url =
                window.APIConfig &&
                window.APIConfig.categories &&
                window.APIConfig.categories.subcategories
                    ? window.APIConfig.categories.subcategories(categoryId)
                    : `${this.options.apiBase}${this.options.subcategoriesEndpoint}/${categoryId}`;
            const response = await fetch(url);
            const data = await response.json();

            // SSOT Response: data.subcategories (not data.data)
            this.cache.subcategories[categoryId] =
                data.data?.subcategories || data.subcategories || [];
            this.displaySubcategories(categoryId);
        } catch (error) {
            console.error('[ModernCategoryWorkflow] Failed to load subcategories:', error);
        }
    }

    displaySubcategories(categoryId) {
        const subcategories = this.cache.subcategories[categoryId] || [];

        const subcategoriesHTML = subcategories
            .map(
                (subcategory) => `
            <div class="subcategory-card neo-glass-effect p-3 rounded-lg cursor-pointer hover:shadow-md transition-all ${
                this.state.selectedSubcategory?.id === subcategory.id
                    ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : ''
            }"
                 data-subcategory="${subcategory.id}">
                <div class="subcategory-name font-medium text-gray-900 dark:text-white">${
                    subcategory.name
                }</div>
                <div class="subcategory-description text-sm text-gray-500 dark:text-gray-400 mt-1">${
                    subcategory.description || ''
                }</div>
            </div>
        `
            )
            .join('');

        this.elements.subcategoriesGrid.innerHTML = subcategoriesHTML;
    }

    async selectSubcategory(subcategoryId) {
        const subcategory = Object.values(this.cache.subcategories)
            .flat()
            .find((s) => s.id == subcategoryId);

        if (!subcategory) return;

        this.state.selectedSubcategory = subcategory;
        this.displaySubcategories(this.state.selectedCategory.id); // Refresh to show selection

        // Load publication types
        await this.loadPublicationTypes(subcategoryId);

        // Advance to next step
        this.advanceToStep(4);
        this.updateProgressSteps();
        this.validateForm();
    }

    async loadPublicationTypes(subcategoryId) {
        try {
            const url =
                window.APIConfig &&
                window.APIConfig.categories &&
                window.APIConfig.categories.publicationTypes
                    ? window.APIConfig.categories.publicationTypes(
                          this.state.selectedCategory?.id || subcategoryId
                      )
                    : `${this.options.apiBase}${this.options.publicationTypesEndpoint}/${subcategoryId}`;
            const response = await fetch(url);
            const data = await response.json();

            // SSOT Response: data.types or data.yayinTipleri
            this.cache.publicationTypes[subcategoryId] =
                data.data?.types || data.types || data.data || [];
            this.displayPublicationTypes(subcategoryId);
        } catch (error) {
            console.error('[ModernCategoryWorkflow] Failed to load publication types:', error);
        }
    }

    displayPublicationTypes(subcategoryId) {
        const publicationTypes = this.cache.publicationTypes[subcategoryId] || [];

        const publicationTypesHTML = publicationTypes
            .map(
                (type) => `
            <div class="publication-type-card neo-glass-effect p-4 rounded-lg cursor-pointer hover:shadow-md transition-all text-center ${
                this.state.selectedPublicationType?.id === type.id
                    ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20'
                    : ''
            }"
                 data-publication-type="${type.id}">
                <div class="publication-icon text-2xl mb-2">${this.getPublicationIcon(
                    type.slug
                )}</div>
                <div class="publication-name font-semibold text-gray-900 dark:text-white">${
                    type.name
                }</div>
                <div class="publication-description text-xs text-gray-500 dark:text-gray-400 mt-1">${
                    type.description || ''
                }</div>
            </div>
        `
            )
            .join('');

        this.elements.publicationTypesGrid.innerHTML = publicationTypesHTML;
    }

    getPublicationIcon(slug) {
        const icons = {
            satilik: '💰',
            kiralik: '🔑',
            'gunluk-kiralik': '📅',
            'sezonluk-kiralik': '🌞',
            'devren-satilik': '🔄',
            'devren-kiralik': '🔄',
        };
        return icons[slug] || '📋';
    }

    selectPublicationType(typeId) {
        const publicationType = Object.values(this.cache.publicationTypes)
            .flat()
            .find((t) => t.id == typeId);

        if (!publicationType) return;

        this.state.selectedPublicationType = publicationType;
        this.displayPublicationTypes(this.state.selectedSubcategory.id); // Refresh to show selection

        this.updateProgressSteps();
        this.validateForm();

        // Show smart suggestions if status
        if (this.options.enableSmartSuggestions) {
            this.showSmartSuggestions();
        }
    }

    advanceToStep(stepNumber) {
        this.state.currentStep = Math.max(this.state.currentStep, stepNumber);

        // Enable step containers
        const stepContainers = this.container.querySelectorAll('.workflow-step-container');
        stepContainers.forEach((container, index) => {
            if (index < stepNumber) {
                container.classList.remove('disabled');
            }
        });
    }

    updateProgressSteps() {
        const steps = this.container.querySelectorAll('.step');
        steps.forEach((step, index) => {
            const stepNumber = index + 1;

            if (stepNumber <= this.state.currentStep) {
                step.classList.add('active');
            }

            // Mark as completed based on selections
            if (
                (stepNumber === 1 && this.state.selectedSite) ||
                (stepNumber === 2 && this.state.selectedCategory) ||
                (stepNumber === 3 && this.state.selectedSubcategory) ||
                (stepNumber === 4 && this.state.selectedPublicationType)
            ) {
                step.classList.add('completed');
            }
        });

        // Update connectors
        const connectors = this.container.querySelectorAll('.step-connector');
        connectors.forEach((connector, index) => {
            if (index + 1 < this.state.currentStep) {
                connector.classList.add('active');
            }
        });
    }

    validateForm() {
        const isValid =
            this.state.selectedSite &&
            this.state.selectedCategory &&
            this.state.selectedSubcategory &&
            this.state.selectedPublicationType;

        this.elements.continueBtn.disabled = !isValid;

        if (isValid) {
            this.elements.saveDraftBtn.style.display = 'inline-flex';
        }

        return isValid;
    }

    handleAction(action, event) {
        switch (action) {
            case 'reset':
                this.resetWorkflow();
                break;
            case 'save-draft':
                this.saveDraft();
                break;
            case 'continue':
                this.continueWorkflow();
                break;
            case 'add-new-site':
                this.showAddSiteModal();
                break;
        }
    }

    resetWorkflow() {
        this.state = {
            currentStep: 1,
            selectedSite: null,
            selectedCategory: null,
            selectedSubcategory: null,
            selectedPublicationType: null,
            isLoading: false,
            validationErrors: [],
        };

        // Reset UI
        this.elements.selectedSite.style.display = 'none';
        this.elements.siteResults.style.display = 'none';
        this.elements.siteSearchInput.value = '';
        this.elements.saveDraftBtn.style.display = 'none';

        this.updateProgressSteps();
        this.validateForm();
        this.displayCategories();
    }

    saveDraft() {
        const draftData = {
            site: this.state.selectedSite,
            category: this.state.selectedCategory,
            subcategory: this.state.selectedSubcategory,
            publicationType: this.state.selectedPublicationType,
            timestamp: new Date().toISOString(),
        };

        localStorage.setItem('category_workflow_draft', JSON.stringify(draftData));

        // Show success message
        this.showNotification('Taslak kaydedildi', 'success');
    }

    continueWorkflow() {
        const workflowData = {
            site_id: this.state.selectedSite?.id,
            category_id: this.state.selectedCategory?.id,
            subcategory_id: this.state.selectedSubcategory?.id,
            publication_type_id: this.state.selectedPublicationType?.id,
        };

        // Emit custom event
        this.container.dispatchEvent(
            new CustomEvent('workflow-complete', {
                detail: workflowData,
            })
        );

        console.log('[ModernCategoryWorkflow] Workflow completed:', workflowData);
    }

    showSmartSuggestions() {
        // AI-powered suggestions based on selections
        // This would integrate with your AI system
        this.elements.smartSuggestions.style.display = 'block';
    }

    showNotification(message, type = 'info') {
        // Simple toast notification
        const toast = document.createElement('div');
        toast.className = `neo-toast neo-toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.remove(), 3000);
    }

    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Public API
    getSelections() {
        return {
            site: this.state.selectedSite,
            category: this.state.selectedCategory,
            subcategory: this.state.selectedSubcategory,
            publicationType: this.state.selectedPublicationType,
        };
    }

    loadDraft() {
        const draft = localStorage.getItem('category_workflow_draft');
        if (draft) {
            const draftData = JSON.parse(draft);
            // Restore selections
            this.state.selectedSite = draftData.site;
            this.state.selectedCategory = draftData.category;
            this.state.selectedSubcategory = draftData.subcategory;
            this.state.selectedPublicationType = draftData.publicationType;

            // Update UI accordingly
            this.updateProgressSteps();
            this.validateForm();
        }
    }
}

// CSS Injection for better styling
const workflowCSS = `
    .workflow-step-container.disabled {
        opacity: 0.5;
        pointer-events: none;
    }

    .step-number {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2rem;
        height: 2rem;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        color: white;
        border-radius: 50%;
        font-weight: bold;
        margin-right: 0.75rem;
        font-size: 0.875rem;
    }

    .step {
        display: flex;
        align-items: center;
        opacity: 0.5;
        transition: all 0.3s ease;
    }

    .step.active {
        opacity: 1;
    }

    .step.completed .step-icon {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
    }

    .step-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        background: #f3f4f6;
        border-radius: 50%;
        margin-right: 1rem;
        transition: all 0.3s ease;
    }

    .step-connector {
        flex: 1;
        height: 2px;
        background: #e5e7eb;
        margin: 0 1rem;
        transition: all 0.3s ease;
    }

    .step-connector.active {
        background: linear-gradient(90deg, #3b82f6, #1d4ed8);
    }

    .category-card:hover,
    .subcategory-card:hover,
    .publication-type-card:hover {
        transform: translateY(-2px);
    }

    .neo-toast {
        position: fixed;
        top: 1rem;
        right: 1rem;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        color: white;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
    }

    .neo-toast-success { background: #10b981; }
    .neo-toast-error { background: #ef4444; }
    .neo-toast-info { background: #3b82f6; }

    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;

// Inject CSS
if (!document.getElementById('modern-category-workflow-css')) {
    const style = document.createElement('style');
    style.id = 'modern-category-workflow-css';
    style.textContent = workflowCSS;
    document.head.appendChild(style);
}

// Global instance
window.ModernCategoryWorkflow = ModernCategoryWorkflow;

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModernCategoryWorkflow;
}
