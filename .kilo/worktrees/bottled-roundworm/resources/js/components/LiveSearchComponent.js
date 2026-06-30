/**
 * Live Search Component - Alpine.js
 *
 * Merkezi live search component'i - Tüm formlarda kullanılabilir
 *
 * Kullanım:
 * <div x-data="liveSearch('kisiler', 'kisi_id')">
 *     <input x-model="searchQuery" @input.debounce.300ms="search()">
 * </div>
 */

export function liveSearch(searchType, fieldName, options = {}) {
    return {
        // Configuration
        searchType: searchType, // 'kisiler', 'ilanlar', 'danismanlar'
        fieldName: fieldName,
        options: options, // Tüm options'ı sakla (genişletilebilirlik için)
        minQueryLength: options.minQueryLength || 2,
        debounceDelay: options.debounceDelay || 300,
        maxResults: options.maxResults || 10,
        showNoResults: options.showNoResults !== false, // Default: true
        showLoadingIndicator: options.showLoadingIndicator !== false, // Default: true
        enableKeyboardNavigation: options.enableKeyboardNavigation !== false, // Default: true
        noResultsText: options.noResultsText || 'Sonuç bulunamadı', // Direct access for Alpine.js
        cacheTTL: options.cacheTTL || 5 * 60 * 1000, // 5 dakika (ms)
        maxCacheSize: options.maxCacheSize || 100, // Maksimum cache boyutu
        enableCacheCleanup: options.enableCacheCleanup !== false, // Default: true

        // State
        searchQuery: '',
        results: [],
        showDropdown: false,
        loading: false,
        selectedItem: null,
        error: null,
        highlightedIndex: -1, // Keyboard navigation için
        searchCache: new Map(), // Performance cache
        abortController: null, // Request cancellation için

        // ✅ API Endpoints (Context7: Merkezi config'den al - hardcoded fallback YOK)
        get apiEndpoint() {
            if (!window.APIConfig?.liveSearch) {
                console.error('❌ APIConfig.liveSearch tanımlı değil! api-config.js yüklü mü kontrol edin.');
                return null;
            }

            const endpoints = window.APIConfig.liveSearch;
            return endpoints[this.searchType] || endpoints.kisiler;
        },

        // Lifecycle
        init() {
            // URL parametrelerinden değer al (sayfa yenilendiğinde)
            const urlParams = new URLSearchParams(window.location.search);
            const searchParam = urlParams.get(this.fieldName + '_search');
            if (searchParam && searchParam.trim().length >= this.minQueryLength) {
                this.searchQuery = decodeURIComponent(searchParam);
                // Otomatik arama yap
                setTimeout(() => {
                    this.performSearch(this.searchQuery);
                }, 500);
            }

            // Periodic cache cleanup (Performance: Memory management)
            if (this.options?.enableCacheCleanup !== false) {
                setInterval(() => {
                    this.cleanupCache();
                }, 60000); // Her 1 dakikada bir
            }
        },

        // Search method
        async search() {
            const query = this.searchQuery.trim();

            // Minimum query length kontrolü
            if (query.length < this.minQueryLength) {
                this.results = [];
                this.showDropdown = false;
                return;
            }

            // Debounce
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(() => {
                this.performSearch(query);
            }, this.debounceDelay);
        },

        // Perform actual search
        async performSearch(query) {
            if (this.loading) return;

            // Önceki request'i iptal et (Performance: Duplicate request önleme)
            if (this.abortController) {
                this.abortController.abort();
            }

            // Cache kontrolü (Performance optimizasyonu)
            const cacheKey = `${this.searchType}:${query}`;
            if (this.searchCache.has(cacheKey)) {
                const cached = this.searchCache.get(cacheKey);
                // Cache TTL kontrolü
                const now = Date.now();
                if (now - cached.timestamp < this.cacheTTL) {
                    this.results = cached.results;
                    this.showDropdown = cached.results.length > 0;
                    this.highlightedIndex = -1;

                    // Custom onAfterSearch hook
                    if (this.options?.onAfterSearch) {
                        this.options.onAfterSearch(this.results, this.searchType);
                    }
                    return;
                } else {
                    // Cache süresi dolmuş, sil
                    this.searchCache.delete(cacheKey);
                }
            }

            // Custom onBeforeSearch hook
            if (this.options?.onBeforeSearch) {
                const result = await this.options.onBeforeSearch(query, this.searchType);
                if (result === false) return; // Hook aramayı engelledi
            }

            this.loading = true;
            this.error = null;
            this.highlightedIndex = -1; // Reset highlight

            // Yeni AbortController oluştur (Performance: Request cancellation)
            this.abortController = new AbortController();

            try {
                // Build API URL
                const url = this.buildApiUrl(query);

                if (!url) {
                    throw new Error('API endpoint tanımlı değil');
                }

                // ✅ API Helper kullan (merkezi yönetim)
                const response = await window.APIHelper?.safeFetch(url, {
                    signal: this.abortController.signal,
                    method: 'GET',
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }

                const result = await window.APIHelper?.handleResponse(response);

                if (result.success && result.data) {
                    // API response formatı: { success: true, data: { data: [...], count: ... } }
                    const responseData = result.data;

                    // Unified search için özel format
                    if (this.searchType === 'unified') {
                        // Unified search response: { ilanlar: {items: []}, kisiler: {items: []}, ... }
                        const allItems = [];
                        if (responseData.ilanlar?.items) {
                            responseData.ilanlar.items.forEach((item) => {
                                allItems.push({ ...item, source: 'ilanlar' });
                            });
                        }
                        if (responseData.kisiler?.items) {
                            responseData.kisiler.items.forEach((item) => {
                                allItems.push({ ...item, source: 'kisiler' });
                            });
                        }
                        if (responseData.danismanlar?.items) {
                            responseData.danismanlar.items.forEach((item) => {
                                allItems.push({ ...item, source: 'danismanlar' });
                            });
                        }
                        this.results = allItems;
                    } else {
                        // Normal search response
                        const items =
                            responseData.data || (Array.isArray(responseData) ? responseData : []);
                        this.results = Array.isArray(items) ? items : [];
                    }

                    this.showDropdown = this.results.length > 0;
                    this.highlightedIndex = -1; // Reset highlight

                    // Cache'e kaydet (Performance optimizasyonu)
                    this.searchCache.set(cacheKey, {
                        results: this.results,
                        timestamp: Date.now(),
                    });

                    // Cache temizleme (Performance: Memory management)
                    this.cleanupCache();

                    // Custom onAfterSearch hook
                    if (this.options?.onAfterSearch) {
                        this.options.onAfterSearch(this.results, this.searchType);
                    }
                } else {
                    this.results = [];
                    this.showDropdown = false;
                    this.error = result.message || 'Arama başarısız';

                    // Custom onError hook
                    if (this.options?.onError) {
                        this.options.onError(this.error, this.searchType);
                    }
                }
            } catch (error) {
                // AbortController hatası değilse logla (Performance: Gereksiz log önleme)
                if (error.name !== 'AbortError') {
                    console.error(`${this.searchType} search error:`, error);
                    this.results = [];
                    this.showDropdown = false;
                    this.error = 'Arama sırasında hata oluştu';

                    // Custom onError hook
                    if (this.options?.onError) {
                        this.options.onError(error, this.searchType);
                    }
                }
                // AbortError durumunda sessizce çık (kullanıcı yeni arama yapıyor)
            } finally {
                this.loading = false;
                this.abortController = null; // Cleanup
            }
        },

        // Build API URL
        buildApiUrl(query) {
            const url = new URL(this.apiEndpoint, window.location.origin);

            // Search type'a göre parametre adı
            const paramMap = {
                kisiler: 'q',
                ilanlar: 'q',
                danismanlar: 'q',
                unified: 'q',
            };

            const paramName = paramMap[this.searchType] || 'q';
            url.searchParams.set(paramName, query);
            url.searchParams.set('limit', this.maxResults);

            // API Filters desteği
            if (this.options?.filters && typeof this.options.filters === 'object') {
                Object.entries(this.options.filters).forEach(([key, value]) => {
                    if (value !== null && value !== undefined && value !== '') {
                        url.searchParams.set(key, value);
                    }
                });
            }

            return url.toString();
        },

        // Select item
        selectItem(item) {
            if (!item || !item.id) return;

            // Custom onSelect hook
            if (this.options?.onSelect) {
                const result = this.options.onSelect(item, this.searchType);
                if (result === false) return; // Hook seçimi engelledi
            }

            this.selectedItem = item;
            this.searchQuery = this.getDisplayText(item);
            this.showDropdown = false;
            this.results = [];

            // Update hidden field
            const hiddenInput = document.querySelector(`input[name="${this.fieldName}"]`);
            if (hiddenInput) {
                hiddenInput.value = item.id;
            }

            // Trigger custom event
            this.$dispatch('live-search-selected', {
                type: this.searchType,
                item: item,
                fieldName: this.fieldName,
            });
        },

        // Get display text (Plugin sistemi - genişletilebilir)
        getDisplayText(item) {
            // Plugin registry - Yeni arama tipleri için buraya ekle
            const displayTextPlugins = {
                kisiler: (item) => `${item.ad || ''} ${item.soyad || ''}`.trim(),
                ilanlar: (item) => item.baslik || `İlan #${item.id}`,
                danismanlar: (item) => item.name || item.email || `Danışman #${item.id}`,
                unified: (item) => {
                    // Unified search için source'a göre format
                    if (item.source === 'kisiler') {
                        return `${item.ad || ''} ${item.soyad || ''}`.trim();
                    } else if (item.source === 'ilanlar') {
                        return item.baslik || `İlan #${item.id}`;
                    } else if (item.source === 'danismanlar') {
                        return item.name || item.email || `Danışman #${item.id}`;
                    }
                    return item.name || item.display_text || item.title || `Item #${item.id}`;
                },
            };

            // Custom formatter varsa kullan
            if (this.options?.customDisplayFormatter) {
                return this.options.customDisplayFormatter(item, this.searchType);
            }

            // Plugin'den al veya default
            const plugin = displayTextPlugins[this.searchType];
            return plugin ? plugin(item) : item.name || item.display_text || `Item #${item.id}`;
        },

        // Get CSRF token
        getCsrfToken() {
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        },

        // Close dropdown
        closeDropdown() {
            setTimeout(() => {
                this.showDropdown = false;
                this.highlightedIndex = -1;
            }, 200);
        },

        // Cache temizleme (Performance: Memory management)
        cleanupCache() {
            const now = Date.now();
            const maxCacheSize = this.options?.maxCacheSize || 100;

            // TTL dolmuş cache'leri temizle
            for (const [key, value] of this.searchCache.entries()) {
                if (now - value.timestamp >= this.cacheTTL) {
                    this.searchCache.delete(key);
                }
            }

            // Cache size limiti kontrolü (LRU: En eski olanları sil)
            if (this.searchCache.size > maxCacheSize) {
                // En eski cache'leri bul ve sil
                const entries = Array.from(this.searchCache.entries()).sort(
                    (a, b) => a[1].timestamp - b[1].timestamp
                );
                const toDelete = entries.slice(0, this.searchCache.size - maxCacheSize);
                toDelete.forEach(([key]) => this.searchCache.delete(key));
            }
        },

        // Keyboard Navigation
        navigateUp() {
            if (!this.enableKeyboardNavigation || !this.showDropdown || this.results.length === 0)
                return;
            this.highlightedIndex =
                this.highlightedIndex > 0 ? this.highlightedIndex - 1 : this.results.length - 1;
            this.scrollToHighlighted();
        },

        navigateDown() {
            if (!this.enableKeyboardNavigation || !this.showDropdown || this.results.length === 0)
                return;
            this.highlightedIndex =
                this.highlightedIndex < this.results.length - 1 ? this.highlightedIndex + 1 : 0;
            this.scrollToHighlighted();
        },

        selectHighlighted() {
            if (
                !this.enableKeyboardNavigation ||
                this.highlightedIndex < 0 ||
                this.highlightedIndex >= this.results.length
            )
                return;
            this.selectItem(this.results[this.highlightedIndex]);
        },

        scrollToHighlighted() {
            // Dropdown içinde highlighted item'ı görünür yap
            this.$nextTick(() => {
                const dropdown = document
                    .querySelector(`[x-data*="${this.fieldName}"]`)
                    ?.querySelector('[x-show*="showDropdown"]');
                if (dropdown) {
                    const highlightedItem = dropdown.children[this.highlightedIndex];
                    if (highlightedItem) {
                        highlightedItem.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    }
                }
            });
        },

        handleKeydown(event) {
            if (!this.enableKeyboardNavigation) return;

            switch (event.key) {
                case 'ArrowUp':
                    event.preventDefault();
                    this.navigateUp();
                    break;
                case 'ArrowDown':
                    event.preventDefault();
                    this.navigateDown();
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (this.highlightedIndex >= 0 && this.highlightedIndex < this.results.length) {
                        this.selectHighlighted();
                    }
                    break;
                case 'Escape':
                    event.preventDefault();
                    this.closeDropdown();
                    break;
            }
        },
    };
}

// Global export
if (typeof window !== 'undefined') {
    window.liveSearch = liveSearch;
}
