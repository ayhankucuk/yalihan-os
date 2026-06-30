// Yalıhan Bekçi - Unified Search Engine
// Context7 Live Search Enhancement with Elasticsearch

class UnifiedSearchEngine {
    constructor() {
        this.searchTypes = ['ilanlar', 'kategoriler', 'kisiler', 'lokasyonlar'];
        this.debounceTime = 300;
        this.elasticSearch = true;
        this.searchHistory = [];
        this.searchSuggestions = new Map();
        this.searchCache = new Map();
        this.searchTimeout = null;
        this.init();
    }

    init() {
        this.setupSearchInterface();
        this.setupSearchHistory();
        this.setupAutoComplete();
        this.setupFacetedSearch();
        this.injectSearchCSS();
    }

    // Context7 Live Search Enhancement
    setupSearchInterface() {
        // Create unified search container
        const searchContainer = document.createElement('div');
        searchContainer.className = 'unified-search-container';
        searchContainer.innerHTML = `
            <div class="search-wrapper">
                <div class="search-input-container">
                    <input type="text"
                           class="unified-search-input"
                           placeholder="İlan, kategori, kişi veya lokasyon ara..."
                           autocomplete="off"
                           spellcheck="false">
                    <div class="search-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="search-clear" style="display: none;">
                        <i class="fas fa-times"></i>
                    </div>
                </div>

                <div class="search-filters">
                    <div class="filter-group">
                        <label class="filter-label">Arama Türü:</label>
                        <div class="filter-buttons">
                            <button class="filter-btn active" data-type="all">Tümü</button>
                            <button class="filter-btn" data-type="ilanlar">İlanlar</button>
                            <button class="filter-btn" data-type="kategoriler">Kategoriler</button>
                            <button class="filter-btn" data-type="kisiler">Kişiler</button>
                            <button class="filter-btn" data-type="lokasyonlar">Lokasyonlar</button>
                        </div>
                    </div>

                    <div class="filter-group">
                        <label class="filter-label">Filtreler:</label>
                        <select class="search-filter-select" data-filter="status">
                            <option value="">Tüm Durumlar</option>
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                            <option value="draft">Taslak</option>
                        </select>
                        <select class="search-filter-select" data-filter="category">
                            <option value="">Tüm Kategoriler</option>
                        </select>
                        <select class="search-filter-select" data-filter="location">
                            <option value="">Tüm Lokasyonlar</option>
                        </select>
                    </div>
                </div>

                <div class="search-suggestions" style="display: none;">
                    <!-- Auto-complete suggestions will be populated here -->
                </div>
            </div>

            <div class="search-results" style="display: none;">
                <div class="results-header">
                    <div class="results-count">
                        <span class="count-number">0</span> sonuç bulundu
                    </div>
                    <div class="results-actions">
                        <button class="results-action-btn" data-action="export">
                            <i class="fas fa-download mr-1"></i>Dışa Aktar
                        </button>
                        <button class="results-action-btn" data-action="save-search">
                            <i class="fas fa-save mr-1"></i>Aramayı Kaydet
                        </button>
                    </div>
                </div>

                <div class="results-content">
                    <div class="results-tabs">
                        <button class="results-tab active" data-tab="ilanlar">
                            İlanlar <span class="tab-count">0</span>
                        </button>
                        <button class="results-tab" data-tab="kategoriler">
                            Kategoriler <span class="tab-count">0</span>
                        </button>
                        <button class="results-tab" data-tab="kisiler">
                            Kişiler <span class="tab-count">0</span>
                        </button>
                        <button class="results-tab" data-tab="lokasyonlar">
                            Lokasyonlar <span class="tab-count">0</span>
                        </button>
                    </div>

                    <div class="results-panels">
                        <div class="results-panel active" data-panel="ilanlar">
                            <!-- İlan results -->
                        </div>
                        <div class="results-panel" data-panel="kategoriler">
                            <!-- Kategori results -->
                        </div>
                        <div class="results-panel" data-panel="kisiler">
                            <!-- Kişi results -->
                        </div>
                        <div class="results-panel" data-panel="lokasyonlar">
                            <!-- Lokasyon results -->
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert search container into page
        const targetElement = document.querySelector('.search-container') || document.body;
        targetElement.appendChild(searchContainer);

        this.setupSearchEvents();
    }

    setupSearchEvents() {
        const searchInput = document.querySelector('.unified-search-input');
        const searchClear = document.querySelector('.search-clear');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const resultTabs = document.querySelectorAll('.results-tab');

        // Search input events
        searchInput.addEventListener('input', (e) => {
            this.handleSearchInput(e.target.value);
        });

        searchInput.addEventListener('keydown', (e) => {
            this.handleSearchKeydown(e);
        });

        searchInput.addEventListener('focus', () => {
            this.showSuggestions();
        });

        searchInput.addEventListener('blur', () => {
            setTimeout(() => this.hideSuggestions(), 200);
        });

        // Clear button
        searchClear.addEventListener('click', () => {
            this.clearSearch();
        });

        // Filter buttons
        filterButtons.forEach((btn) => {
            btn.addEventListener('click', (e) => {
                this.handleFilterChange(e.target.dataset.type);
            });
        });

        // Result tabs
        resultTabs.forEach((tab) => {
            tab.addEventListener('click', (e) => {
                this.handleTabChange(e.target.dataset.tab);
            });
        });

        // Filter selects
        document.querySelectorAll('.search-filter-select').forEach((select) => {
            select.addEventListener('change', () => {
                this.performSearch();
            });
        });
    }

    handleSearchInput(query) {
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        // Show/hide clear button
        const clearBtn = document.querySelector('.search-clear');
        clearBtn.style.display = query.length > 0 ? 'block' : 'none';

        if (query.length === 0) {
            this.hideResults();
            return;
        }

        // Debounced search
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, this.debounceTime);

        // Auto-complete suggestions
        if (query.length >= 2) {
            this.generateSuggestions(query);
        }
    }

    handleSearchKeydown(e) {
        const suggestions = document.querySelector('.search-suggestions');
        const activeSuggestion = suggestions.querySelector('.suggestion-item.active');

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.navigateSuggestions('down');
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.navigateSuggestions('up');
                break;
            case 'Enter':
                e.preventDefault();
                if (activeSuggestion) {
                    this.selectSuggestion(activeSuggestion);
                } else {
                    this.performSearch();
                }
                break;
            case 'Escape':
                this.hideSuggestions();
                this.hideResults();
                break;
        }
    }

    async performSearch(query = null) {
        const searchInput = document.querySelector('.unified-search-input');
        const searchQuery = query || searchInput.value;

        if (!searchQuery.trim()) {
            this.hideResults();
            return;
        }

        // Show loading state
        this.showLoadingState();

        try {
            // Get current filters
            const filters = this.getCurrentFilters();

            // Perform search
            const results = await this.search(searchQuery, filters);

            // Display results
            this.displayResults(results, searchQuery);

            // Update search history
            this.updateSearchHistory(searchQuery);
        } catch (error) {
            console.error('Search failed:', error);
            this.showSearchError(error);
        } finally {
            this.hideLoadingState();
        }
    }

    async search(query, filters = {}) {
        // Check cache first
        const cacheKey = this.generateCacheKey(query, filters);
        if (this.searchCache.has(cacheKey)) {
            return this.searchCache.get(cacheKey);
        }

        const searchParams = {
            query: query.trim(),
            filters: filters,
            types: this.getActiveSearchTypes(),
            limit: 50,
            offset: 0,
        };

        try {
            const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.searchUnified
                ? window.APIConfig.admin.searchUnified
                : '/api/admin/search/unified';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(searchParams),
            });

            if (!response.ok) {
                throw new Error(`Search failed: ${response.status}`);
            }

            const results = await response.json();

            // Cache results
            this.searchCache.set(cacheKey, results);

            return results;
        } catch (error) {
            console.error('Search API error:', error);

            // Fallback to local search
            return this.performLocalSearch(query, filters);
        }
    }

    performLocalSearch(query, filters) {
        // Fallback local search implementation
        const results = {
            total: 0,
            ilanlar: { items: [], total: 0 },
            kategoriler: { items: [], total: 0 },
            kisiler: { items: [], total: 0 },
            lokasyonlar: { items: [], total: 0 },
        };

        // This would be replaced with actual local search logic
        console.log('Performing local search fallback');

        return results;
    }

    displayResults(results, query) {
        const resultsContainer = document.querySelector('.search-results');
        const resultsCount = document.querySelector('.count-number');

        // Update total count
        resultsCount.textContent = results.total || 0;

        // Update tab counts
        Object.keys(results).forEach((type) => {
            if (type !== 'total') {
                const tab = document.querySelector(`[data-tab="${type}"] .tab-count`);
                if (tab) {
                    tab.textContent = results[type]?.total || 0;
                }
            }
        });

        // Display results for each type
        Object.keys(results).forEach((type) => {
            if (type !== 'total') {
                this.displayTypeResults(type, results[type]?.items || []);
            }
        });

        // Show results container
        resultsContainer.style.display = 'block';

        // Highlight search terms
        this.highlightSearchTerms(query);
    }

    displayTypeResults(type, items) {
        const panel = document.querySelector(`[data-panel="${type}"]`);
        if (!panel) return;

        if (items.length === 0) {
            panel.innerHTML = `
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <p>Bu kategoride sonuç bulunamadı</p>
                </div>
            `;
            return;
        }

        const itemsHTML = items.map((item) => this.createResultItemHTML(type, item)).join('');
        panel.innerHTML = `<div class="results-list">${itemsHTML}</div>`;
    }

    createResultItemHTML(type, item) {
        const templates = {
            ilanlar: `
                <div class="result-item ilan-item" data-id="${item.id}">
                    <div class="item-image">
                        <img src="${item.image || '/images/no-image.jpg'}" alt="${item.title}">
                    </div>
                    <div class="item-content">
                        <h3 class="item-title">${item.title}</h3>
                        <div class="item-meta">
                            <span class="item-price">${this.formatPrice(item.price)}</span>
                            <span class="item-location">${item.location}</span>
                            <span class="item-category">${item.category}</span>
                        </div>
                        <div class="item-description">${item.description}</div>
                        <div class="item-actions">
                            <button class="action-btn" data-action="view">Görüntüle</button>
                            <button class="action-btn" data-action="edit">Düzenle</button>
                        </div>
                    </div>
                </div>
            `,
            kategoriler: `
                <div class="result-item kategori-item" data-id="${item.id}">
                    <div class="item-icon">
                        <i class="${item.icon || 'fas fa-folder'}"></i>
                    </div>
                    <div class="item-content">
                        <h3 class="item-title">${item.name}</h3>
                        <div class="item-description">${item.description}</div>
                        <div class="item-meta">
                            <span class="item-count">${item.count || 0} ilan</span>
                            <span class="item-status status-${item.aktiflik_durumu || item.state || item.status || 'unknown'}">${item.aktiflik_durumu || item.state || item.status || 'Bilinmiyor'}</span>
                        </div>
                    </div>
                </div>
            `,
            kisiler: `
                <div class="result-item kisi-item" data-id="${item.id}">
                    <div class="item-avatar">
                        <img src="${item.avatar || '/images/default-avatar.jpg'}" alt="${item.name}">
                    </div>
                    <div class="item-content">
                        <h3 class="item-title">${item.name}</h3>
                        <div class="item-meta">
                            <span class="item-email">${item.email}</span>
                            <span class="item-phone">${item.phone}</span>
                        </div>
                        <div class="item-description">${item.description}</div>
                    </div>
                </div>
            `,
            lokasyonlar: `
                <div class="result-item lokasyon-item" data-id="${item.id}">
                    <div class="item-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="item-content">
                        <h3 class="item-title">${item.name}</h3>
                        <div class="item-description">${item.description}</div>
                        <div class="item-meta">
                            <span class="item-count">${item.count || 0} ilan</span>
                            <span class="item-type">${item.type}</span>
                        </div>
                    </div>
                </div>
            `,
        };

        return (
            templates[type] ||
            `
            <div class="result-item" data-id="${item.id}">
                <div class="item-content">
                    <h3 class="item-title">${item.title || item.name}</h3>
                    <div class="item-description">${item.description}</div>
                </div>
            </div>
        `
        );
    }

    generateSuggestions(query) {
        const suggestions = [];

        // Generate suggestions based on search history
        this.searchHistory.forEach((historyItem) => {
            if (historyItem.toLowerCase().includes(query.toLowerCase())) {
                suggestions.push({
                    text: historyItem,
                    type: 'history',
                    icon: 'fas fa-history',
                });
            }
        });

        // Generate category suggestions
        this.searchTypes.forEach((type) => {
            if (type.toLowerCase().includes(query.toLowerCase())) {
                suggestions.push({
                    text: `${type} kategorisinde ara`,
                    type: 'category',
                    icon: 'fas fa-folder',
                });
            }
        });

        // Display suggestions
        this.displaySuggestions(suggestions.slice(0, 5));
    }

    displaySuggestions(suggestions) {
        const suggestionsContainer = document.querySelector('.search-suggestions');

        if (suggestions.length === 0) {
            suggestionsContainer.style.display = 'none';
            return;
        }

        const suggestionsHTML = suggestions
            .map(
                (suggestion, index) => `
            <div class="suggestion-item ${index === 0 ? 'active' : ''}" data-text="${suggestion.text}">
                <i class="${suggestion.icon}"></i>
                <span>${suggestion.text}</span>
                <span class="suggestion-type">${suggestion.type}</span>
            </div>
        `
            )
            .join('');

        suggestionsContainer.innerHTML = suggestionsHTML;
        suggestionsContainer.style.display = 'block';

        // Add click handlers
        suggestionsContainer.querySelectorAll('.suggestion-item').forEach((item) => {
            item.addEventListener('click', () => {
                this.selectSuggestion(item);
            });
        });
    }

    selectSuggestion(suggestionItem) {
        const text = suggestionItem.dataset.text;
        const searchInput = document.querySelector('.unified-search-input');

        searchInput.value = text;
        this.hideSuggestions();
        this.performSearch();
    }

    navigateSuggestions(direction) {
        const suggestions = document.querySelectorAll('.suggestion-item');
        const activeIndex = Array.from(suggestions).findIndex((s) =>
            s.classList.contains('active')
        );

        suggestions.forEach((s) => s.classList.remove('active'));

        let newIndex = activeIndex;
        if (direction === 'down') {
            newIndex = activeIndex < suggestions.length - 1 ? activeIndex + 1 : 0;
        } else {
            newIndex = activeIndex > 0 ? activeIndex - 1 : suggestions.length - 1;
        }

        if (suggestions[newIndex]) {
            suggestions[newIndex].classList.add('active');
        }
    }

    showSuggestions() {
        const searchInput = document.querySelector('.unified-search-input');
        if (searchInput.value.length >= 2) {
            this.generateSuggestions(searchInput.value);
        }
    }

    hideSuggestions() {
        const suggestionsContainer = document.querySelector('.search-suggestions');
        suggestionsContainer.style.display = 'none';
    }

    hideResults() {
        const resultsContainer = document.querySelector('.search-results');
        resultsContainer.style.display = 'none';
    }

    clearSearch() {
        const searchInput = document.querySelector('.unified-search-input');
        searchInput.value = '';
        searchInput.focus();

        document.querySelector('.search-clear').style.display = 'none';
        this.hideSuggestions();
        this.hideResults();
    }

    showLoadingState() {
        const searchInput = document.querySelector('.unified-search-input');
        searchInput.classList.add('loading');

        const searchIcon = document.querySelector('.search-icon i');
        searchIcon.className = 'fas fa-spinner fa-spin';
    }

    hideLoadingState() {
        const searchInput = document.querySelector('.unified-search-input');
        searchInput.classList.remove('loading');

        const searchIcon = document.querySelector('.search-icon i');
        searchIcon.className = 'fas fa-search';
    }

    showSearchError(error) {
        if (window.toastNotifications) {
            window.toastNotifications.error('Arama sırasında hata oluştu:' + error.message);
        }
    }

    // Utility methods
    getCurrentFilters() {
        const filters = {};

        document.querySelectorAll('.search-filter-select').forEach((select) => {
            const filterName = select.dataset.filter;
            const filterValue = select.value;

            if (filterValue) {
                filters[filterName] = filterValue;
            }
        });

        return filters;
    }

    getActiveSearchTypes() {
        const activeFilter = document.querySelector('.filter-btn.active');
        const filterType = activeFilter?.dataset.type;

        if (filterType === 'all') {
            return this.searchTypes;
        } else {
            return [filterType];
        }
    }

    handleFilterChange(type) {
        document.querySelectorAll('.filter-btn').forEach((btn) => {
            btn.classList.remove('active');
        });

        document.querySelector(`[data-type="${type}"]`).classList.add('active');
        this.performSearch();
    }

    handleTabChange(tabType) {
        document.querySelectorAll('.results-tab').forEach((tab) => {
            tab.classList.remove('active');
        });

        document.querySelectorAll('.results-panel').forEach((panel) => {
            panel.classList.remove('active');
        });

        document.querySelector(`[data-tab="${tabType}"]`).classList.add('active');
        document.querySelector(`[data-panel="${tabType}"]`).classList.add('active');
    }

    updateSearchHistory(query) {
        if (!this.searchHistory.includes(query)) {
            this.searchHistory.unshift(query);
            this.searchHistory = this.searchHistory.slice(0, 10); // Keep last 10 searches
        }
    }

    generateCacheKey(query, filters) {
        return `${query}_${JSON.stringify(filters)}`;
    }

    highlightSearchTerms(query) {
        const terms = query.toLowerCase().split('');
        const resultItems = document.querySelectorAll(
            '.result-item .item-title, .result-item .item-description'
        );

        resultItems.forEach((item) => {
            let html = item.innerHTML;
            terms.forEach((term) => {
                if (term.length > 2) {
                    const regex = new RegExp(`(${term})`, 'gi');
                    html = html.replace(regex, '<mark>$1</mark>');
                }
            });
            item.innerHTML = html;
        });
    }

    formatPrice(price) {
        if (!price) return 'Fiyat Yok';
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY',
        }).format(price);
    }

    setupSearchHistory() {
        // Load search history from localStorage
        const saved = localStorage.getItem('yalihan-search-history');
        if (saved) {
            this.searchHistory = JSON.parse(saved);
        }

        // Save search history on page unload
        window.addEventListener('beforeunload', () => {
            localStorage.setItem('yalihan-search-history', JSON.stringify(this.searchHistory));
        });
    }

    setupAutoComplete() {
        // Setup auto-complete for common terms
        const commonTerms = [
            'satılık daire',
            'kiralık daire',
            'satılık villa',
            'kiralık villa',
            'satılık arsa',
            'kiralık ofis',
            'satılık dükkan',
            'kiralık dükkan',
        ];

        this.searchSuggestions.set('common', commonTerms);
    }

    setupFacetedSearch() {
        // Setup faceted search filters
        this.loadFilterOptions();
    }

    async loadFilterOptions() {
        try {
            // Load category options
            const categorySelect = document.querySelector('[data-filter="category"]');
            if (categorySelect) {
                const urlC = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.categories
                    ? window.APIConfig.admin.categories
                    : '/api/admin/categories';
                const response = await fetch(urlC);
                const categories = await response.json();

                categories.forEach((category) => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    categorySelect.appendChild(option);
                });
            }

            // Load location options
            const locationSelect = document.querySelector('[data-filter="location"]');
            if (locationSelect) {
                const urlL = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.locations
                    ? window.APIConfig.admin.locations
                    : '/api/admin/locations';
                const response = await fetch(urlL);
                const locations = await response.json();

                locations.forEach((location) => {
                    const option = document.createElement('option');
                    option.value = location.id;
                    option.textContent = location.name;
                    locationSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Failed to load filter options:', error);
        }
    }

    injectSearchCSS() {
        const searchCSS = `
            /* Unified Search Engine Styles */
            .unified-search-container {
                position: relative;
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }

            .search-wrapper {
                position: relative;
            }

            .search-input-container {
                position: relative;
                margin-bottom: 20px;
            }

            .unified-search-input {
                width: 100%;
                padding: 16px 50px 16px 50px;
                border: 2px solid #e5e7eb;
                border-radius: 12px;
                font-size: 16px;
                background: #f9fafb;
                transition: all 0.3s ease;
            }

            .unified-search-input:focus {
                outline: none;
                border-color: #3b82f6;
                background: white;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }

            .unified-search-input.loading {
                border-color: #10b981;
            }

            .search-icon {
                position: absolute;
                left: 16px;
                top: 50%;
                transform: translateY(-50%);
                color: #6b7280;
                pointer-events: none;
            }

            .search-clear {
                position: absolute;
                right: 16px;
                top: 50%;
                transform: translateY(-50%);
                color: #6b7280;
                cursor: pointer;
                padding: 4px;
                border-radius: 4px;
                transition: all 0.2s ease;
            }

            .search-clear:hover {
                color: #374151;
                background: #f3f4f6;
            }

            .search-filters {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
                flex-wrap: wrap;
            }

            .filter-group {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }

            .filter-label {
                font-size: 14px;
                font-weight: 600;
                color: #374151;
            }

            .filter-buttons {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            .filter-btn {
                padding: 8px 16px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                background: white;
                color: #374151;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .filter-btn:hover {
                background: #f9fafb;
                border-color: #9ca3af;
            }

            .filter-btn.active {
                background: #3b82f6;
                border-color: #3b82f6;
                color: white;
            }

            .search-filter-select {
                padding: 8px 12px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                background: white;
                font-size: 14px;
                min-width: 150px;
            }

            .search-suggestions {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
                z-index: 1000;
                max-height: 300px;
                overflow-y: auto;
            }

            .suggestion-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 16px;
                cursor: pointer;
                transition: background 0.2s ease;
            }

            .suggestion-item:hover,
            .suggestion-item.active {
                background: #f3f4f6;
            }

            .suggestion-item i {
                width: 16px;
                color: #6b7280;
            }

            .suggestion-item span:first-of-type {
                flex: 1;
                color: #374151;
            }

            .suggestion-type {
                font-size: 12px;
                color: #9ca3af;
                text-transform: uppercase;
            }

            .search-results {
                margin-top: 20px;
                border-top: 1px solid #e5e7eb;
                padding-top: 20px;
            }

            .results-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .results-count {
                font-size: 16px;
                color: #374151;
            }

            .count-number {
                font-weight: 600;
                color: #3b82f6;
            }

            .results-actions {
                display: flex;
                gap: 8px;
            }

            .results-action-btn {
                padding: 8px 16px;
                border: 1px solid #d1d5db;
                border-radius: 6px;
                background: white;
                color: #374151;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .results-action-btn:hover {
                background: #f9fafb;
                border-color: #9ca3af;
            }

            .results-tabs {
                display: flex;
                gap: 4px;
                margin-bottom: 20px;
                border-bottom: 1px solid #e5e7eb;
            }

            .results-tab {
                padding: 12px 20px;
                border: none;
                background: none;
                color: #6b7280;
                font-size: 14px;
                cursor: pointer;
                border-bottom: 2px solid transparent;
                transition: all 0.2s ease;
            }

            .results-tab:hover {
                color: #374151;
            }

            .results-tab.active {
                color: #3b82f6;
                border-bottom-color: #3b82f6;
            }

            .tab-count {
                background: #e5e7eb;
                color: #6b7280;
                padding: 2px 6px;
                border-radius: 10px;
                font-size: 12px;
                margin-left: 8px;
            }

            .results-tab.active .tab-count {
                background: #3b82f6;
                color: white;
            }

            .results-panel {
                display: none;
            }

            .results-panel.active {
                display: block;
            }

            .results-list {
                display: grid;
                gap: 16px;
            }

            .result-item {
                display: flex;
                gap: 16px;
                padding: 16px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                background: white;
                transition: all 0.2s ease;
            }

            .result-item:hover {
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                transform: translateY(-1px);
            }

            .item-image,
            .item-icon,
            .item-avatar {
                flex-shrink: 0;
                width: 60px;
                height: 60px;
                border-radius: 8px;
                overflow: hidden;
                background: #f3f4f6;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .item-image img,
            .item-avatar img {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .item-icon i {
                font-size: 24px;
                color: #6b7280;
            }

            .item-content {
                flex: 1;
                min-width: 0;
            }

            .item-title {
                margin: 0 0 8px 0;
                font-size: 16px;
                font-weight: 600;
                color: #1f2937;
                line-height: 1.4;
            }

            .item-description {
                margin: 8px 0;
                font-size: 14px;
                color: #6b7280;
                line-height: 1.4;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .item-meta {
                display: flex;
                gap: 16px;
                margin: 8px 0;
                font-size: 12px;
                color: #9ca3af;
                flex-wrap: wrap;
            }

            .item-price {
                font-weight: 600;
                color: #059669;
            }

            .item-actions {
                display: flex;
                gap: 8px;
                margin-top: 12px;
            }

            .action-btn {
                padding: 6px 12px;
                border: 1px solid #d1d5db;
                border-radius: 4px;
                background: white;
                color: #374151;
                font-size: 12px;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .action-btn:hover {
                background: #f9fafb;
                border-color: #9ca3af;
            }

            .no-results {
                text-align: center;
                padding: 40px 20px;
                color: #6b7280;
            }

            .no-results i {
                font-size: 48px;
                margin-bottom: 16px;
                color: #d1d5db;
            }

            mark {
                background: #fef3c7;
                color: #92400e;
                padding: 2px 4px;
                border-radius: 3px;
            }

            /* Dark mode */
            .dark .unified-search-container {
                background: #1f2937;
                border-color: #374151;
            }

            .dark .unified-search-input {
                background: #111827;
                border-color: #374151;
                color: #f9fafb;
            }

            .dark .unified-search-input:focus {
                background: #1f2937;
                border-color: #60a5fa;
            }

            .dark .search-suggestions,
            .dark .result-item {
                background: #1f2937;
                border-color: #374151;
            }

            .dark .suggestion-item:hover,
            .dark .suggestion-item.active {
                background: #374151;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .unified-search-container {
                    padding: 16px;
                    margin: 16px;
                }

                .search-filters {
                    flex-direction: column;
                    gap: 16px;
                }

                .filter-buttons {
                    justify-content: center;
                }

                .results-header {
                    flex-direction: column;
                    gap: 16px;
                    align-items: stretch;
                }

                .results-tabs {
                    overflow-x: auto;
                    flex-wrap: nowrap;
                }

                .result-item {
                    flex-direction: column;
                    text-align: center;
                }

                .item-meta {
                    justify-content: center;
                }
            }
        `;

        const style = document.createElement('style');
        style.textContent = searchCSS;
        document.head.appendChild(style);
    }
}

// Global instance
window.unifiedSearchEngine = new UnifiedSearchEngine();

// Export for module usage
export default UnifiedSearchEngine;
