/**
 * Context7 Live Search System
 *
 * Bu dosya Context7 standartlarına uygun canlı arama sistemini yönetir.
 * Kişi, danışman ve site/apartman aramaları için birleşik arayüz sağlar.
 *
 * @version 2.0.0
 * @since 2025-10-05
 * @author Context7 System
 */

// Prevent multiple class declarations
if (typeof window.Context7LiveSearch === 'undefined') {
    window.Context7LiveSearch = class Context7LiveSearch {
        constructor(options = {}) {
            this.defaultOptions = {
                debounceDelay: 300,
                minQueryLength: 2,
                maxResults: 20,
                apiBaseUrl:
                    window.APIConfig &&
                    window.APIConfig.liveSearch &&
                    window.APIConfig.liveSearch.unified
                        ? window.APIConfig.liveSearch.unified
                        : '/api/v1/search/unified',
                animationDuration: 200,
                showSearchHints: true,
                enableKeyboardNavigation: true,
                context7Compliant: true,
            };

            this.options = { ...this.defaultOptions, ...options };
            this.searchCache = new Map();
            this.activeInstances = new Map();
            this.debounceTimers = new Map();

            this.initializeSystem();
        }

        /**
         * Sistem başlatma
         */
        initializeSystem() {
            this.setupGlobalEventListeners();
            this.initializeSearchComponents();
            console.log('🔍 Context7 Live Search System initialized');
        }

        /**
         * Global event listener'ları kur
         */
        setupGlobalEventListeners() {
            // ESC tuşu ile dropdown'ları kapat
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.hideAllDropdowns();
                }
            });

            // Sayfa dışına tıklama ile dropdown'ları kapat
            document.addEventListener('click', (e) => {
                if (!e.target.closest('.context7-live-search')) {
                    this.hideAllDropdowns();
                }
            });
        }

        /**
         * Mevcut arama bileşenlerini başlat
         */
        initializeSearchComponents() {
            // ✅ Eski format: data-context7-search
            document.querySelectorAll('[data-context7-search="kisiler"]').forEach((element) => {
                this.initializeSearchInstance(element, 'kisiler');
            });
            document.querySelectorAll('[data-context7-search="danismanlar"]').forEach((element) => {
                this.initializeSearchInstance(element, 'danismanlar');
            });
            document.querySelectorAll('[data-context7-search="sites"]').forEach((element) => {
                this.initializeSearchInstance(element, 'sites');
            });
            document.querySelectorAll('[data-context7-search="unified"]').forEach((element) => {
                this.initializeSearchInstance(element, 'unified');
            });

            // ✅ Yeni format: data-search-type (wizard uyumluluğu)
            document.querySelectorAll('[data-search-type]').forEach((element) => {
                const searchType = element.dataset.searchType;
                if (searchType && !element.dataset.context7Search) {
                    this.initializeSearchInstance(element, searchType);
                }
            });
        }

        /**
         * Arama instance'ı başlat
         */
        initializeSearchInstance(element, searchType) {
            if (element.dataset.c7Initialized === 'true') {
                return;
            }
            element.dataset.c7Initialized = 'true';

            const instanceId = this.generateInstanceId();
            const input = element.tagName === 'INPUT' ? element : element.querySelector('input[type="text"]');
            const hiddenInput = element.querySelector('input[type="hidden"]') ||
                (element.tagName === 'INPUT' && element.parentNode ? element.parentNode.querySelector('input[type="hidden"]') : null);
            const existingResults = element.querySelector('.context7-search-results');

            const instance = {
                id: instanceId,
                element: element,
                input: input,
                searchType: searchType,
                isLoading: false,
                currentQuery: '',
                currentResults: [],
                selectedIndex: -1,
                dropdown: null,
                existingResults: existingResults,
                hiddenInput: hiddenInput,
                selectedValue: null,
                config: this.extractConfig(element),
            };

            this.activeInstances.set(instanceId, instance);
            this.setupInstanceEventListeners(instance);
            this.createDropdown(instance);

            return instanceId;
        }

        /**
         * Instance event listener'ları kur
         */
        setupInstanceEventListeners(instance) {
            const targetInput = instance.input || instance.element;

            // Input event'leri
            targetInput.addEventListener('input', (e) => {
                this.handleInput(instance, e.target.value);
            });

            targetInput.addEventListener('keydown', (e) => {
                this.handleKeyDown(instance, e);
            });

            targetInput.addEventListener('focus', () => {
                if (targetInput.value && targetInput.value.trim().length >= this.options.minQueryLength) {
                    this.handleInput(instance, targetInput.value);
                } else if (instance.currentResults.length > 0) {
                    this.showDropdown(instance);
                }
            });

            targetInput.addEventListener('blur', (e) => {
                // Dropdown'a tıklama kontrolü için gecikme
                setTimeout(() => {
                    if (!e.relatedTarget || (!e.relatedTarget.closest('.context7-search-dropdown') && !e.relatedTarget.closest('.context7-search-results'))) {
                        this.hideDropdown(instance);
                    }
                }, 200);
            });
        }

        /**
         * Input değişikliği işle
         */
        handleInput(instance, query) {
            instance.currentQuery = query.trim();
            instance.selectedIndex = -1;

            if (instance.currentQuery.length < this.options.minQueryLength) {
                this.hideDropdown(instance);
                return;
            }

            this.debounceSearch(instance);
        }

        /**
         * Debounce ile arama yap
         */
        debounceSearch(instance) {
            clearTimeout(this.debounceTimers.get(instance.id));

            const timer = setTimeout(() => {
                this.performSearch(instance);
            }, this.options.debounceDelay);

            this.debounceTimers.set(instance.id, timer);
        }

        /**
         * Arama gerçekleştir
         */
        async performSearch(instance) {
            if (instance.isLoading) return;

            instance.isLoading = true;
            this.updateLoadingState(instance, true);

            try {
                const apiUrl = this.buildApiUrl(instance);
                console.log('🔍 Context7 Live Search fetching:', apiUrl);
                const response = await fetch(apiUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    console.error('Context7 HTTP Error:', response.status, response.statusText);
                    instance.currentResults = [];
                    this.renderResults(instance);
                    this.showDropdown(instance);
                    return;
                }

                const data = await response.json();
                console.log('📊 Context7 Live Search raw response:', data);

                const results = this.processResults(data, instance);
                instance.currentResults = Array.isArray(results) ? results : [];
                console.log('🎨 Rendering results count:', instance.currentResults.length, 'for instance:', instance.id, 'type:', instance.searchType, 'items:', instance.currentResults);
                this.renderResults(instance);
                this.showDropdown(instance);
            } catch (error) {
                console.error('Context7 Live Search Request Failed:', error);
                instance.currentResults = [];
                this.renderResults(instance);
                this.showDropdown(instance);
            } finally {
                instance.isLoading = false;
                this.updateLoadingState(instance, false);
            }
        }

        /**
         * API URL oluştur
         */
        buildApiUrl(instance) {
            // ✅ Custom endpoint (wizard uyumluluğu)
            if (instance.config.endpoint) {
                const params = new URLSearchParams({
                    q: instance.currentQuery,
                    limit: instance.config.maxResults || this.options.maxResults,
                });
                return `${instance.config.endpoint}${instance.config.endpoint.includes('?') ? '&' : '?'}${params.toString()}`;
            }

            // ✅ Context7: Merkezi API config kullan (api-config.js)
            const endpointMap = window.APIConfig?.liveSearch || {
                kisiler: '/api/v1/kisiler/search',
                users: '/api/v1/users/search',
                sites: '/api/v1/sites/search',
                unified: '/api/v1/search/unified',
            };

            const baseUrl = endpointMap[instance.searchType] || endpointMap.kisiler;
            const params = new URLSearchParams({
                q: instance.currentQuery,
                limit: instance.config.maxResults || this.options.maxResults,
            });

            // Context7 uyumlu ek parametreler
            if (instance.config.filters) {
                Object.entries(instance.config.filters).forEach(([key, value]) => {
                    if (value !== null && value !== undefined) {
                        params.append(key, value);
                    }
                });
            }

            return `${baseUrl}?${params.toString()}`;
        }

        /**
         * Sonuçları işle
         */
        processResults(data, instance) {
            if (!data) {
                console.warn('⚠️ [processResults] Data is null or undefined');
                return [];
            }

            // If string (e.g. unparsed JSON string)
            if (typeof data === 'string') {
                try {
                    data = JSON.parse(data);
                } catch (e) {
                    console.error('❌ [processResults] Failed to parse data string:', e);
                    return [];
                }
            }

            console.log('🔍 [processResults input]', {
                type: typeof data,
                isArray: Array.isArray(data),
                hasData: data && typeof data === 'object' ? 'data' in data : false,
                isDataArray: data && typeof data === 'object' ? Array.isArray(data.data) : false,
                raw: data,
            });

            try {
                if (Array.isArray(data)) {
                    console.log('✅ [processResults] Returning direct array, length:', data.length);
                    return data;
                }

                if (instance && instance.searchType === 'unified') {
                    const results = data.results || data.data || {};
                    const processed = this.processUnifiedResults(results);
                    return Array.isArray(processed) ? processed : [];
                }

                let items = [];
                if (Array.isArray(data.data)) {
                    items = data.data;
                } else if (data.data && Array.isArray(data.data.data)) {
                    items = data.data.data;
                } else if (data.data && Array.isArray(data.data.items)) {
                    items = data.data.items;
                } else if (Array.isArray(data.results)) {
                    items = data.results;
                } else if (Array.isArray(data.kisiler)) {
                    items = data.kisiler;
                } else if (Array.isArray(data.users)) {
                    items = data.users;
                } else if (data.data && typeof data.data === 'object') {
                    items = Object.values(data.data).filter(item => typeof item === 'object' && item !== null);
                } else if (typeof data === 'object' && data !== null) {
                    for (const key of Object.keys(data)) {
                        if (Array.isArray(data[key]) && key !== 'meta') {
                            items = data[key];
                            break;
                        }
                    }
                }

                console.log('✅ [processResults] Normalized items count:', items.length, items);
                return Array.isArray(items) ? items : [];
            } catch (error) {
                console.error('❌ [processResults error]:', error);
                return [];
            }
        }

        /**
         * Birleşik arama sonuçlarını işle
         */
        processUnifiedResults(results) {
            const processedResults = [];

            Object.entries(results).forEach(([type, typeResults]) => {
                if (typeResults.data && typeResults.data.length > 0) {
                    typeResults.data.forEach((item) => {
                        processedResults.push({
                            ...item,
                            resultType: type,
                            displayText: this.getDisplayText(item, type),
                        });
                    });
                }
            });

            return processedResults;
        }

        /**
         * Görüntüleme metni al
         */
        getDisplayText(item, type) {
            if (!item) return '';
            if (item.text) return item.text;
            if (item.tam_ad) return item.tam_ad + (item.telefon ? ' - ' + item.telefon : '');
            if (item.ad && item.soyad) return item.ad + ' ' + item.soyad + (item.telefon ? ' - ' + item.telefon : '');
            if (item.display_text) return item.display_text;
            if (item.name) return item.name + (item.email ? ' - ' + item.email : '');
            return String(item.id || '');
        }

        /**
         * Sonuçları render et
         */
        renderResults(instance) {
            // ✅ Defensive: ensure currentResults is an array
            const results = Array.isArray(instance.currentResults) ? instance.currentResults : [];

            // Case A: Container has existing .context7-search-results element (Wizard UI)
            if (instance.existingResults) {
                if (results.length === 0) {
                    instance.existingResults.innerHTML = `
                        <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                            <p class="text-sm">Sonuç bulunamadı</p>
                        </div>
                    `;
                    this.showDropdown(instance);
                    return;
                }

                let html = results
                    .map((result, index) => {
                        const safeText = this.getDisplayText(result, result.resultType);
                        const subtitle = result.kisi_tipi
                            ? `📋 ${result.kisi_tipi}`
                            : (result.email || result.eposta || result.telefon ? (result.email || result.eposta || result.telefon) : '');
                        return `
                            <div class="context7-result-item px-4 py-3 hover:bg-gray-100 dark:hover:bg-slate-800 cursor-pointer border-b border-gray-100 dark:border-slate-800 last:border-b-0 transition-colors duration-150" data-index="${index}">
                                <div class="font-medium text-gray-900 dark:text-gray-100 text-sm">${safeText}</div>
                                ${subtitle ? `<div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">${subtitle}</div>` : ''}
                            </div>
                        `;
                    })
                    .join('');

                instance.existingResults.innerHTML = html;
                this.showDropdown(instance);
                return;
            }

            // Case B: Fallback dropdown container
            const dropdown = instance.dropdown;
            const resultsContainer = dropdown?.querySelector('.results-container');

            if (!resultsContainer) return;

            if (results.length === 0) {
                resultsContainer.innerHTML = this.createNoResultsHTML(instance);
                return;
            }

            let html = results
                .map((result, index) => {
                    return this.createResultItemHTML(result, index, instance);
                })
                .join('');

            // Site arama için "Yeni Site Ekle" butonu ekle
            if (instance.searchType === 'sites') {
                html += this.createAddSiteButtonHTML();
            }

            resultsContainer.innerHTML = html;
        }

        /**
         * Sonuç öğesi HTML'i oluştur
         */
        createResultItemHTML(result, index, instance) {
            const isSelected = index === instance.selectedIndex;
            const selectedClass = isSelected ? 'selected' : '';

            let resultTypeBadge = '';
            if (instance.searchType === 'unified' && result.resultType) {
                const typeLabels = {
                    kisiler: '👤 Kişi',
                    danismanlar: '👨‍💼 Danışman',
                    sites: '🏢 Site',
                };
                resultTypeBadge = `<span class="result-type-badge">${
                    typeLabels[result.resultType] || result.resultType
                }</span>`;
            }

            let searchHint = '';
            if (this.options.showSearchHints && result.search_hint) {
                searchHint = `<div class="search-hint">${(result.search_hint || '').replace(/[<>&"']/g, (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#039;' })[c])}</div>`;
            }

            const safeDisplayText = this.getDisplayText(result, result.resultType).replace(
                /[<>&"']/g,
                (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#039;' })[c]
            );

            return `
            <div class="result-item ${selectedClass}" data-index="${index}" data-value='${JSON.stringify(
                result
            )}'>
                <div class="result-content">
                    <div class="result-main">
                        ${resultTypeBadge}
                        <span class="result-text">${safeDisplayText}</span>
                    </div>
                    ${searchHint}
                </div>
                <div class="result-actions">
                    <button type="button" class="select-btn" title="Seç">✓</button>
                </div>
            </div>
        `;
        }

        /**
         * Sonuç bulunamadı HTML'i oluştur
         */
        createNoResultsHTML(instance) {
            if (instance.searchType === 'sites') {
                return `
                <div class="no-results">
                    <div class="no-results-icon">🏢</div>
                    <div class="no-results-text">Site bulunamadı</div>
                    <div class="no-results-hint">Farklı anahtar kelimeler deneyin veya yeni site ekleyin</div>
                    <button type="button" class="add-new-btn" data-action="add-site">
                        <span class="add-icon">+</span>
                        Yeni Site Ekle
                    </button>
                </div>
            `;
            } else {
                return `
                <div class="no-results">
                    <div class="no-results-icon">🔍</div>
                    <div class="no-results-text">Sonuç bulunamadı</div>
                    <div class="no-results-hint">Farklı anahtar kelimeler deneyin</div>
                </div>
            `;
            }
        }

        /**
         * "Yeni Site Ekle" butonu HTML'i oluştur
         */
        createAddSiteButtonHTML() {
            return `
            <div class="result-item add-new-item" data-action="add-site">
                <div class="result-content">
                    <div class="result-main">
                        <span class="add-icon">+</span>
                        <span class="result-text">Yeni Site Ekle</span>
                    </div>
                    <div class="search-hint">Aradığınız site bulunamadı mı? Yeni site ekleyin</div>
                </div>
            </div>
        `;
        }

        /**
         * Dropdown oluştur
         */
        createDropdown(instance) {
            // Case A: If container has existing .context7-search-results, listen to clicks on it
            if (instance.existingResults) {
                instance.existingResults.addEventListener('click', (e) => {
                    const resultItem = e.target.closest('.context7-result-item') || e.target.closest('.result-item');
                    if (resultItem && resultItem.dataset.index !== undefined) {
                        const index = parseInt(resultItem.dataset.index);
                        this.selectResult(instance, index);
                    }
                });
                return;
            }

            // Case B: Create new dropdown
            const dropdown = document.createElement('div');
            dropdown.className = 'context7-search-dropdown';
            dropdown.innerHTML = `
            <div class="dropdown-header">
                <span class="search-type-label">${this.getSearchTypeLabel(
                    instance.searchType
                )}</span>
                <span class="results-count"></span>
            </div>
            <div class="results-container"></div>
            <div class="dropdown-footer">
                <div class="search-tips">
                    <span class="tip">↑↓ Navigate</span>
                    <span class="tip">Enter Select</span>
                    <span class="tip">Esc Close</span>
                </div>
            </div>
        `;

            // Dropdown'ı context7-live-search container'ının içine yerleştir
            const container = instance.element.closest('.context7-live-search');
            if (container) {
                container.appendChild(dropdown);
            } else {
                instance.element.parentNode.appendChild(dropdown);
            }
            instance.dropdown = dropdown;

            // Dropdown event listener'ları
            dropdown.addEventListener('click', (e) => {
                const resultItem = e.target.closest('.result-item');
                if (resultItem) {
                    // "Yeni Site Ekle" butonu kontrolü
                    if (resultItem.dataset.action === 'add-site') {
                        e.preventDefault();
                        this.showAddSiteModal(instance.element);
                        return;
                    }

                    const index = parseInt(resultItem.dataset.index);
                    this.selectResult(instance, index);
                }

                // "Yeni Site Ekle" butonu kontrolü (no-results içinde)
                const addBtn = e.target.closest('.add-new-btn');
                if (addBtn && addBtn.dataset.action === 'add-site') {
                    e.preventDefault();
                    this.showAddSiteModal(container);
                }
            });

            // Hidden input oluştur eğer henüz yoksa
            if (!instance.hiddenInput) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = instance.config.hiddenInputName || ((instance.input ? instance.input.name : instance.element.name) || 'search') + '_id';
                instance.element.parentNode.appendChild(hiddenInput);
                instance.hiddenInput = hiddenInput;
            }
        }

        /**
         * Arama tipi etiketi al
         */
        getSearchTypeLabel(searchType) {
            const labels = {
                kisiler: '👤 Kişi Arama',
                danismanlar: '👨‍💼 Danışman Arama',
                users: '👨‍💼 Danışman Arama',
                sites: '🏢 Site/Apartman Arama',
                unified: '🔍 Birleşik Arama',
            };
            return labels[searchType] || 'Arama';
        }

        /**
         * Klavye olaylarını işle
         */
        handleKeyDown(instance, event) {
            if (!this.options.enableKeyboardNavigation) return;

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.navigateResults(instance, 1);
                    break;
                case 'ArrowUp':
                    event.preventDefault();
                    this.navigateResults(instance, -1);
                    break;
                case 'Enter':
                    event.preventDefault();
                    if (instance.selectedIndex >= 0) {
                        this.selectResult(instance, instance.selectedIndex);
                    }
                    break;
                case 'Escape':
                    this.hideDropdown(instance);
                    break;
            }
        }

        /**
         * Sonuçlar arasında gezin
         */
        navigateResults(instance, direction) {
            const maxIndex = instance.currentResults.length - 1;
            instance.selectedIndex += direction;

            if (instance.selectedIndex < 0) {
                instance.selectedIndex = maxIndex;
            } else if (instance.selectedIndex > maxIndex) {
                instance.selectedIndex = -1;
            }

            this.updateSelection(instance);
        }

        /**
         * Seçimi güncelle
         */
        updateSelection(instance) {
            const container = instance.existingResults || instance.dropdown;
            if (!container) return;
            const items = container.querySelectorAll('.context7-result-item, .result-item');

            items.forEach((item, index) => {
                item.classList.toggle('selected', index === instance.selectedIndex);
                if (index === instance.selectedIndex) {
                    item.classList.add('bg-purple-50', 'dark:bg-purple-900/30');
                } else {
                    item.classList.remove('bg-purple-50', 'dark:bg-purple-900/30');
                }
            });
        }

        /**
         * Sonucu seç
         */
        selectResult(instance, index) {
            const result = instance.currentResults[index];
            if (!result) return;

            const displayText = this.getDisplayText(result, result.resultType);

            // Input değerini güncelle
            const targetInput = instance.input || instance.element;
            if (targetInput) {
                targetInput.value = displayText;
                targetInput.dispatchEvent(new Event('input', { bubbles: true }));
                targetInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Hidden input değerini güncelle
            if (instance.hiddenInput) {
                instance.hiddenInput.value = result.id;
                instance.hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                instance.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            // Global state güncelle
            if (instance.searchType === 'kisi' || instance.element?.id?.includes('ilan_sahibi') || instance.hiddenInput?.id === 'ilan_sahibi_id') {
                window.context7SelectedOwner = displayText;
            } else if (instance.element?.id?.includes('ilgili_kisi') || instance.hiddenInput?.id === 'ilgili_kisi_id') {
                window.context7SelectedContact = displayText;
            } else if (instance.searchType === 'user' || instance.element?.id?.includes('danisman') || instance.hiddenInput?.id === 'danisman_id') {
                window.context7SelectedAdvisor = displayText;
            }

            // Seçilen değeri sakla
            instance.selectedValue = result;

            // Dropdown'ı gizle
            this.hideDropdown(instance);

            // Custom event tetikle
            this.triggerSelectionEvent(instance, result);

            if (typeof window.updateStep5Preview === 'function') {
                window.updateStep5Preview();
            }
        }

        /**
         * Seçim event'i tetikle
         */
        triggerSelectionEvent(instance, result) {
            const event = new CustomEvent('context7:search:selected', {
                bubbles: true,
                cancelable: true,
                detail: {
                    instance: instance,
                    result: result,
                    searchType: instance.searchType,
                },
            });

            const target = instance.input || instance.element;
            if (target) target.dispatchEvent(event);
            window.dispatchEvent(event);
            document.dispatchEvent(event);
        }

        /**
         * Dropdown'ı göster
         */
        showDropdown(instance) {
            if (instance.existingResults) {
                instance.existingResults.classList.remove('hidden');
                instance.existingResults.style.display = 'block';
                return;
            }

            if (!instance.dropdown || instance.currentResults.length === 0) return;

            instance.dropdown.classList.add('active');

            // Dropdown pozisyonunu ayarla
            this.positionDropdown(instance);
        }

        /**
         * Dropdown'ı gizle
         */
        hideDropdown(instance) {
            if (instance.existingResults) {
                instance.existingResults.classList.add('hidden');
                instance.existingResults.style.display = 'none';
            }
            if (instance.dropdown) {
                instance.dropdown.classList.remove('active');
            }
            instance.selectedIndex = -1;
        }

        /**
         * Tüm dropdown'ları gizle
         */
        hideAllDropdowns() {
            this.activeInstances.forEach((instance) => {
                this.hideDropdown(instance);
            });
        }

        /**
         * Dropdown pozisyonunu ayarla
         */
        positionDropdown(instance) {
            const input = instance.element;
            const dropdown = instance.dropdown;
            const container = instance.element.closest('.context7-live-search');

            if (!container) return;

            const inputRect = input.getBoundingClientRect();
            const containerRect = container.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            const dropdownHeight = 300; // Tahmini yükseklik

            // Dropdown'ı container'a göre konumlandır
            dropdown.style.position = 'absolute';
            dropdown.style.top = '100%'; // Container'ın altında
            dropdown.style.left = '0';
            dropdown.style.right = '0';
            dropdown.style.width = '100%';

            // Viewport sınırlarını kontrol et
            const spaceBelow = viewportHeight - inputRect.bottom;
            const spaceAbove = inputRect.top;

            if (spaceBelow < dropdownHeight && spaceAbove > dropdownHeight) {
                // Dropdown'ı yukarıda göster
                dropdown.style.top = 'auto';
                dropdown.style.bottom = '100%';
            }
        }

        /**
         * Yükleme durumunu güncelle
         */
        updateLoadingState(instance, isLoading) {
            const input = instance.element;

            if (isLoading) {
                input.classList.add('loading');
                input.setAttribute('data-loading', 'true');
            } else {
                input.classList.remove('loading');
                input.removeAttribute('data-loading');
            }
        }

        /**
         * Instance ID oluştur
         */
        generateInstanceId() {
            return 'context7-search-' + Math.random().toString(36).substr(2, 9);
        }

        /**
         * Element'ten config çıkar
         */
        extractConfig(element) {
            const config = {
                maxResults: 10,
                hiddenInputName: null,
                endpoint: null,
            };

            // ✅ Data attribute'leri oku (context7 standardı)
            Object.keys(element.dataset).forEach((key) => {
                if (key.startsWith('context7')) {
                    const configKey = key.replace('context7', '').toLowerCase();
                    config[configKey] = element.dataset[key];
                }
            });

            // ✅ Wizard uyumluluğu: data-endpoint, data-max-results, vb.
            if (element.dataset.endpoint) {
                config.endpoint = element.dataset.endpoint;
            }
            if (element.dataset.maxResults) {
                config.maxResults = parseInt(element.dataset.maxResults) || 10;
            }
            if (element.dataset.placeholder) {
                config.placeholder = element.dataset.placeholder;
            }

            return config;
        }

        /**
         * Yeni arama instance'ı ekle
         */
        addSearchInstance(element, searchType, config = {}) {
            element.setAttribute('data-context7-search', searchType);

            // Config'i data attribute'lara yaz
            Object.entries(config).forEach(([key, value]) => {
                element.dataset[`context7${key.charAt(0).toUpperCase() + key.slice(1)}`] = value;
            });

            return this.initializeSearchInstance(element, searchType);
        }

        /**
         * Instance'ı kaldır
         */
        removeSearchInstance(instanceId) {
            const instance = this.activeInstances.get(instanceId);
            if (instance) {
                if (instance.dropdown) {
                    instance.dropdown.remove();
                }
                if (instance.hiddenInput) {
                    instance.hiddenInput.remove();
                }
                this.activeInstances.delete(instanceId);
            }
        }

        /**
         * Sistem durumunu al
         */
        getSystemStatus() {
            return {
                activeInstances: this.activeInstances.size,
                searchCache: this.searchCache.size,
                options: this.options,
                context7Compliant: true,
            };
        }

        /**
         * "Yeni Site Ekle" modal'ını göster
         */
        showAddSiteModal(container) {
            // Modal HTML'i oluştur
            const modalHTML = `
            <div id="addSiteModal" class="context7-modal">
                <div class="context7-modal-overlay"></div>
                <div class="context7-modal-content">
                    <div class="context7-modal-header">
                        <h3>Yeni Site Ekle</h3>
                        <button type="button" class="context7-modal-close">&times;</button>
                    </div>
                    <div class="context7-modal-body">
                        <form id="addSiteForm">
                            <div class="context7-form-group">
                                <label for="siteName">Site Adı *</label>
                                <input type="text" id="siteName" name="name" required
                                       placeholder="Örn: Bahçeşehir Sitesi" class="context7-input">
                            </div>
                            <div class="context7-form-group">
                                <label for="siteAddress">Adres</label>
                                <input type="text" id="siteAddress" name="address"
                                       placeholder="Örn: Bahçeşehir Mahallesi, Başakşehir/İstanbul" class="context7-input">
                            </div>
                            <div class="context7-form-group">
                                <label for="siteDescription">Açıklama</label>
                                <textarea id="siteDescription" name="description"
                                          placeholder="Site hakkında kısa açıklama..." class="context7-textarea"></textarea>
                            </div>
                            <div class="context7-form-group">
                                <label for="siteIl">İl</label>
                                <select id="siteIl" name="il_id" class="context7-select">
                                    <option value="">İl Seçin</option>
                                </select>
                            </div>
                            <div class="context7-form-group">
                                <label for="siteIlce">İlçe</label>
                                <select id="siteIlce" name="ilce_id" class="context7-select">
                                    <option value="">İlçe Seçin</option>
                                </select>
                            </div>
                            <div class="context7-form-group">
                                <label for="siteMahalle">Mahalle</label>
                                <select id="siteMahalle" name="mahalle_id" class="context7-select">
                                    <option value="">Mahalle Seçin</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="context7-modal-footer">
                        <button type="button" class="context7-btn context7-neo-btn neo-btn-secondary" onclick="this.closest('.context7-modal').remove()">
                            İptal
                        </button>
                        <button type="button" class="context7-btn context7-neo-btn neo-btn-primary" onclick="window.Context7LiveSearch.createSite()">
                            Site Ekle
                        </button>
                    </div>
                </div>
            </div>
        `;

            // Modal'ı DOM'a ekle
            document.body.insertAdjacentHTML('beforeend', modalHTML);

            // Modal event listener'ları
            const modal = document.getElementById('addSiteModal');
            const closeBtn = modal.querySelector('.context7-modal-close');
            const overlay = modal.querySelector('.context7-modal-overlay');

            closeBtn.addEventListener('click', () => modal.remove());
            overlay.addEventListener('click', () => modal.remove());

            // İlleri yükle
            this.loadIller();

            // İl değişikliği
            document.getElementById('siteIl').addEventListener('change', (e) => {
                this.loadIlceler(e.target.value);
            });

            // İlçe değişikliği
            document.getElementById('siteIlce').addEventListener('change', (e) => {
                this.loadMahalleler(e.target.value);
            });

            // Modal'ı göster
            modal.style.display = 'flex';
        }

        /**
         * İlleri yükle
         */
        async loadIller() {
            try {
                const response = await fetch(
                    window.APIConfig &&
                        window.APIConfig.location &&
                        window.APIConfig.location.provinces
                        ? window.APIConfig.location.provinces
                        : '/api/location/provinces'
                );
                const data = await response.json();

                const select = document.getElementById('siteIl');
                select.innerHTML = '<option value="">İl Seçin</option>';

                data.forEach((il) => {
                    const option = document.createElement('option');
                    option.value = il.id;
                    option.textContent = il.name;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('İller yüklenirken hata:', error);
            }
        }

        /**
         * İlçeleri yükle
         */
        async loadIlceler(ilId) {
            if (!ilId) {
                document.getElementById('siteIlce').innerHTML =
                    '<option value="">İlçe Seçin</option>';
                return;
            }

            try {
                const url = window.APIConfig
                    ? window.APIConfig.location.districts(ilId)
                    : `/api/v1/location/districts/${ilId}`;
                const response = await fetch(url);
                const data = await response.json();

                const select = document.getElementById('siteIlce');
                select.innerHTML = '<option value="">İlçe Seçin</option>';

                data.forEach((ilce) => {
                    const option = document.createElement('option');
                    option.value = ilce.id;
                    option.textContent = ilce.name;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('İlçeler yüklenirken hata:', error);
            }
        }

        /**
         * Mahalleleri yükle
         */
        async loadMahalleler(ilceId) {
            if (!ilceId) {
                document.getElementById('siteMahalle').innerHTML =
                    '<option value="">Mahalle Seçin</option>';
                return;
            }

            try {
                const url2 = window.APIConfig
                    ? window.APIConfig.location.neighborhoods(ilceId)
                    : `/api/v1/location/neighborhoods/${ilceId}`;
                const response = await fetch(url2);
                const data = await response.json();

                const select = document.getElementById('siteMahalle');
                select.innerHTML = '<option value="">Mahalle Seçin</option>';

                data.forEach((mahalle) => {
                    const option = document.createElement('option');
                    option.value = mahalle.id;
                    option.textContent = mahalle.name;
                    select.appendChild(option);
                });
            } catch (error) {
                console.error('Mahalleler yüklenirken hata:', error);
            }
        }

        /**
         * Yeni site oluştur
         * PHASE 2.1: AJAX + Toast modernization
         */
        async createSite() {
            const form = document.getElementById('addSiteForm');
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());

            // Boş değerleri temizle
            Object.keys(data).forEach((key) => {
                if (data[key] === '') {
                    delete data[key];
                }
            });

            // Validation
            if (!data.name || !data.il_id || !data.ilce_id) {
                window.toast?.error('Lütfen zorunlu alanları doldurun') ||
                    this.showNotification('Lütfen zorunlu alanları doldurun', 'error');
                return;
            }

            try {
                // PHASE 2.1: AjaxHelper kullan (eğer varsa)
                const result = window.AjaxHelper
                    ? await window.AjaxHelper.post(window.APIConfig.admin.sites.create, data)
                    : await this.legacyAjaxPost(window.APIConfig.admin.sites.create, data);

                if (result.success) {
                    // PHASE 2.1: Toast notification (modern!)
                    if (window.toast) {
                        window.toast.success('Site başarıyla eklendi!');
                    } else {
                        this.showNotification('Site başarıyla eklendi!', 'success');
                    }

                    // Modal'ı kapat
                    document.getElementById('addSiteModal')?.remove();

                    // Arama alanını güncelle
                    this.updateSearchWithNewSite(result.data);

                    // PHASE 2.1: Smooth scroll + highlight (eğer UIHelpers varsa)
                    if (window.smoothScroll && result.data.id) {
                        setTimeout(() => {
                            window.smoothScroll(`site-${result.data.id}`);
                        }, 100);
                    }
                } else {
                    window.toast?.error(result.message) ||
                        this.showNotification('Site eklenirken hata: ' + result.message, 'error');
                }
            } catch (error) {
                console.error('Site oluşturma hatası:', error);
                window.toast?.error('Site eklenirken hata oluştu') ||
                    this.showNotification('Site eklenirken hata oluştu', 'error');
            }
        }

        /**
         * Legacy AJAX post (fallback)
         */
        async legacyAjaxPost(url, data) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content'),
                },
                body: JSON.stringify(data),
            });
            return await response.json();
        }

        /**
         * Arama alanını yeni site ile güncelle
         */
        updateSearchWithNewSite(siteData) {
            // Tüm arama instance'larını bul ve güncelle
            this.activeInstances.forEach((instance, instanceId) => {
                if (instance.searchType === 'sites') {
                    // Input değerini güncelle
                    instance.element.value = siteData.display;

                    // Hidden input'u güncelle
                    const hiddenInput = document.querySelector(
                        `[name="${instance.config.hiddenInputName}"]`
                    );
                    if (hiddenInput) {
                        hiddenInput.value = siteData.id;
                    }

                    // Dropdown'ı kapat
                    this.hideDropdown(instance);
                }
            });
        }

        /**
         * Bildirim göster
         */
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `context7-notification context7-notification-${type}`;
            notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button type="button" class="notification-close">&times;</button>
            </div>
        `;

            document.body.appendChild(notification);

            // Otomatik kapatma
            setTimeout(() => {
                notification.remove();
            }, 5000);

            // Manuel kapatma
            notification.querySelector('.notification-close').addEventListener('click', () => {
                notification.remove();
            });
        }
    };

    // Global instance oluştur
    if (!window.context7LiveSearchInstance) {
        window.context7LiveSearchInstance = new window.Context7LiveSearch();
    }
}

// Otomatik başlatma ve dinamik bileşen tarayıcı
const bootstrapContext7LiveSearch = () => {
    if (window.context7LiveSearchInstance) {
        window.context7LiveSearchInstance.initializeSearchComponents();
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrapContext7LiveSearch);
} else {
    bootstrapContext7LiveSearch();
}

window.addEventListener('load', bootstrapContext7LiveSearch);
document.addEventListener('wizard:ready', bootstrapContext7LiveSearch);
document.addEventListener('ilan-wizard-ready', bootstrapContext7LiveSearch);

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = window.Context7LiveSearch;
}
