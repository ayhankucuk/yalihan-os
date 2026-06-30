/**
 * Unified Person Selector Component
 * Context7 standardı ile geliştirilmiş, tüm farklı person selector implementasyonlarını birleştiren unified component
 *
 * @author Context7 AI System
 * @version 1.0.0
 * @date 2025-09-22
 */

class UnifiedPersonSelector {
    constructor(config = {}) {
        console.log('[UnifiedPersonSelector] Initializing with config:', config);

        this.config = {
            type: 'owner', // 'owner' || 'advisor' || 'person'
            name: 'person_id',
            placeholder: 'Kişi ara veya seç...',
            searchDelay: 300,
            minSearchLength: 2,
            maxResults: 10,
            required: false,
            allowCreate: true,
            container: null,
            showLabel: true,
            ...config,
        };

        // Type-specific configuration
        this.setupTypeConfig();

        // State
        this.searchTimeout = null;
        this.selectedPerson = null;
        this.isLoading = false;
        this.currentResults = [];
        this.eventListeners = {};

        // Elements
        this.elements = {};

        this.init();
    }

    /**
     * Type-specific configuration setup
     */
    setupTypeConfig() {
        // ✅ Merkezi API Config kullan (hardcoded fallback YOK)
        if (!window.APIConfig?.smartIlan?.searchPersons) {
            console.error('❌ APIConfig.smartIlan.searchPersons tanımlı değil! api-config.js yüklü mü kontrol edin.');
        }
        if (!window.APIConfig?.liveSearch?.danismanlar) {
            console.error('❌ APIConfig.liveSearch.danismanlar tanımlı değil! api-config.js yüklü mü kontrol edin.');
        }
        if (!window.APIConfig?.admin?.person?.quickAdd) {
            console.error('❌ APIConfig.admin.person.quickAdd tanımlı değil! api-config.js yüklü mü kontrol edin.');
        }
        if (!window.APIConfig?.admin?.consultants?.quickAdd) {
            console.error('❌ APIConfig.admin.consultants.quickAdd tanımlı değil! api-config.js yüklü mü kontrol edin.');
        }

        const typeConfigs = {
            owner: {
                endpoint: window.APIConfig?.smartIlan?.searchPersons,
                placeholder: 'İlan sahibi ara veya seç...',
                label: 'İlan Sahibi',
                createEndpoint: window.APIConfig?.admin?.person?.quickAdd,
                requiredFields: ['ad', 'soyad'],
            },
            advisor: {
                endpoint: window.APIConfig?.liveSearch?.danismanlar,
                placeholder: 'Danışman ara veya seç...',
                label: 'Danışman',
                createEndpoint: window.APIConfig?.admin?.consultants?.quickAdd,
                requiredFields: ['name', 'email'],
            },
            person: {
                endpoint: window.APIConfig?.smartIlan?.searchPersons,
                placeholder: 'Kişi ara veya seç...',
                label: 'Kişi',
                createEndpoint: window.APIConfig?.admin?.person?.quickAdd,
                requiredFields: ['ad', 'soyad'],
            },
        };

        this.typeConfig = typeConfigs[this.config.type] || typeConfigs.person;

        // Override with custom config
        Object.keys(this.typeConfig).forEach((key) => {
            if (this.config[key] !== undefined) {
                this.typeConfig[key] = this.config[key];
            }
        });
    }

    /**
     * Initialize the component
     */
    init() {
        if (!this.findContainer()) {
            console.error('[UnifiedPersonSelector] Container not found');
            return;
        }

        this.createElements();
        this.bindEvents();
        this.loadInitialData();

        console.log('[UnifiedPersonSelector] Initialized successfully');
    }

    /**
     * Find or create container
     */
    findContainer() {
        if (this.config.container) {
            this.container =
                typeof this.config.container === 'string'
                    ? document.querySelector(this.config.container)
                    : this.config.container;
        } else {
            // Try to find by common selectors
            const selectors = [
                '#owner-selector-container',
                '#person-selector-container',
                `#${this.config.name}-container`,
                `[data-person-selector="${this.config.type}"]`,
            ];

            for (const selector of selectors) {
                this.container = document.querySelector(selector);
                if (this.container) break;
            }
        }

        return !!this.container;
    }

    /**
     * Create DOM elements
     */
    createElements() {
        const containerId = `unified-person-selector-${Math.random().toString(36).substr(2, 9)}`;

        this.container.innerHTML = `
            <div id="${containerId}" class="unified-person-selector-wrapper">
                ${
    this.config.showLabel
        ? `<label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 dark:text-slate-300">
                    ${this.typeConfig.label} ${this.config.required ? '<span class="text-red-500">*</span>' : ''}
                </label>`
        : ''
}

                <!-- Search Input -->
                <div class="relative">
                    <input type="text"
                           id="${containerId}-search"
                           class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg bg-white text-gray-900 focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100"
                           placeholder="${this.typeConfig.placeholder}"
                           autocomplete="off"
                           ${this.config.required ? 'required' : ''}>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                        <div id="${containerId}-spinner" class="hidden">
                            <svg class="animate-spin h-4 w-4 text-gray-400 dark:text-slate-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        <button type="button" id="${containerId}-clear" class="hidden text-gray-400 hover:text-gray-600 dark:text-slate-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Results Dropdown -->
                <div id="${containerId}-results" class="absolute z-50 w-full bg-white border border-gray-300 rounded-lg shadow-xl mt-1 hidden max-h-72 overflow-y-auto left-0 right-0 dark:bg-slate-900 dark:border-slate-600">
                    <div id="${containerId}-results-list"></div>
                    <div id="${containerId}-no-results" class="hidden p-4 text-center text-gray-500 dark:text-slate-500">
                        <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <div>Sonuç bulunamadı</div>
                        ${
    this.config.allowCreate
        ? `
                            <button type="button" class="neo-btn neo-btn-primary btn-sm mt-2" onclick="this.showCreateModal()">
                                Yeni ${this.typeConfig.label} Oluştur
                            </button>
                        `
        : ''
}
                    </div>
                    <div id="${containerId}-loading" class="hidden p-4 text-center">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin h-4 w-4 mr-2 text-gray-400 dark:text-slate-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Aranıyor...
                        </div>
                    </div>
                </div>

                <!-- Selected Person Display -->
                <div id="${containerId}-selected" class="hidden mt-3 p-3 bg-gray-50 rounded-md dark:bg-slate-950">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div id="${containerId}-selected-avatar" class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <span class="text-white font-semibold text-xs"></span>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100" id="${containerId}-selected-name"></div>
                                <div class="text-sm text-gray-500 dark:text-slate-500" id="${containerId}-selected-contact"></div>
                            </div>
                        </div>
                        <button type="button" id="${containerId}-remove" class="text-red-500 hover:text-red-700">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                ${
    this.config.allowCreate
        ? `
                    <!-- Create New Person Button -->
                    <div class="mt-3">
                        <button type="button" id="${containerId}-create" class="btn-outline text-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Yeni ${this.typeConfig.label} Oluştur
                        </button>
                    </div>
                `
        : ''
}

                <!-- Hidden Input for Form Submission is handled separately -->
            </div>
        `;

        // Store element references
        this.elements = {
            container: document.getElementById(containerId),
            searchInput: document.getElementById(`${containerId}-search`),
            spinner: document.getElementById(`${containerId}-spinner`),
            clearButton: document.getElementById(`${containerId}-clear`),
            results: document.getElementById(`${containerId}-results`),
            resultsList: document.getElementById(`${containerId}-results-list`),
            noResults: document.getElementById(`${containerId}-no-results`),
            loading: document.getElementById(`${containerId}-loading`),
            selected: document.getElementById(`${containerId}-selected`),
            selectedAvatar: document.getElementById(`${containerId}-selected-avatar`),
            selectedName: document.getElementById(`${containerId}-selected-name`),
            selectedContact: document.getElementById(`${containerId}-selected-contact`),
            removeButton: document.getElementById(`${containerId}-remove`),
            createButton: document.getElementById(`${containerId}-create`),
            hiddenInput:
                document.getElementById(this.config.name) || document.createElement('input'),
        };

        // If hidden input doesn't exist, create and append it
        if (!document.getElementById(this.config.name)) {
            this.elements.hiddenInput.type = 'hidden';
            this.elements.hiddenInput.name = this.config.name;
            this.elements.hiddenInput.id = this.config.name;
            this.elements.container.appendChild(this.elements.hiddenInput);
        }
    }

    /**
     * Bind event listeners
     */
    bindEvents() {
        // Search input events
        this.elements.searchInput.addEventListener('input', (e) => {
            this.handleSearch(e.target.value);
        });

        this.elements.searchInput.addEventListener('focus', () => {
            if (
                this.elements.searchInput.value.length >= this.config.minSearchLength &&
                this.currentResults.length > 0
            ) {
                this.showResults();
            }
        });

        // Clear selection
        this.elements.clearButton?.addEventListener('click', () => {
            this.clearSelection();
        });

        // Remove selection
        this.elements.removeButton?.addEventListener('click', () => {
            this.clearSelection();
        });

        // Create new person
        this.elements.createButton?.addEventListener('click', () => {
            this.showCreateModal();
        });

        // Hide results when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.elements.container.contains(e.target)) {
                this.hideResults();
            }
        });
    }

    /**
     * Handle search input
     */
    handleSearch(query) {
        clearTimeout(this.searchTimeout);

        if (query.length < this.config.minSearchLength) {
            this.hideResults();
            return;
        }

        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, this.config.searchDelay);
    }

    /**
     * Perform AJAX search
     */
    async performSearch(query) {
        this.setLoading(true);

        try {
            // ✅ API Helper kullan (merkezi yönetim)
            if (!this.typeConfig.endpoint) {
                throw new Error('Endpoint tanımlı değil');
            }

            const endpointUrl = `${this.typeConfig.endpoint}?q=${encodeURIComponent(query)}&limit=${this.config.maxResults}`;
            const response = await window.APIHelper?.safeFetch(endpointUrl, {
                method: 'GET',
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await window.APIHelper?.handleResponse(response);
            this.currentResults = data.data?.data || data.data?.results || data.data || [];
            this.displayResults();
        } catch (error) {
            console.error('[UnifiedPersonSelector] Search error:', error);
            const errorMessage = error instanceof window.APIError ? error.message : 'Arama sırasında bir hata oluştu';
            this.showError(errorMessage);
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * Display search results
     */
    displayResults() {
        if (!Array.isArray(this.currentResults) || this.currentResults.length === 0) {
            this.showNoResults();
            return;
        }

        const resultsList = this.currentResults
            .map((person) => {
                const name = this.getPersonName(person);
                const contact = this.getPersonContact(person);
                const initials = this.getInitials(name);

                return `
                <div class="result-item flex items-center justify-between p-4 hover:bg-purple-50 cursor-pointer border-b border-gray-200 last:border-b-0 transition-colors duration-200 dark:border-slate-700"
                     data-person-id="${person.id}">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-500 rounded-full flex items-center justify-center">
                            <span class="text-white font-semibold text-sm">${initials}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-medium text-gray-900 dark:text-white truncate dark:text-slate-100">${this.escapeHtml(name)}</div>
                            <div class="text-sm text-gray-500 truncate dark:text-slate-500">${this.escapeHtml(contact)}</div>
                        </div>
                    </div>
                    <div class="text-purple-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            `;
            })
            .join('');

        this.elements.resultsList.innerHTML = resultsList;
        this.showResults();

        // Bind click events to results
        this.elements.resultsList.querySelectorAll('.result-item').forEach((item) => {
            item.addEventListener('click', () => {
                const personId = item.dataset.personId;
                const person = this.currentResults.find((p) => p.id == personId);
                this.selectPerson(person);
            });
        });
    }

    /**
     * Select a person
     */
    selectPerson(person) {
        this.selectedPerson = person;
        const name = this.getPersonName(person);
        const contact = this.getPersonContact(person);
        const initials = this.getInitials(name);

        // Update UI
        this.elements.selectedName.textContent = name;
        this.elements.selectedContact.textContent = contact;
        this.elements.selectedAvatar.querySelector('span').textContent = initials;

        // Update hidden input
        this.elements.hiddenInput.value = person.id;

        // Update search input
        this.elements.searchInput.value = name;

        // Show selected person, hide results
        this.elements.selected.classList.remove('hidden');
        this.elements.clearButton?.classList.remove('hidden');
        this.hideResults();

        // Trigger events
        this.triggerEvent('personSelect', person);
        this.elements.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));

        // Form wizard integration
        if (window.ilanFormWizard?.updateFormData) {
            window.ilanFormWizard.updateFormData();
        }
    }

    /**
     * Clear selection
     */
    clearSelection() {
        this.selectedPerson = null;

        // Clear UI
        this.elements.searchInput.value = '';
        this.elements.selected.classList.add('hidden');
        this.elements.clearButton?.classList.add('hidden');

        // Clear hidden input
        this.elements.hiddenInput.value = '';

        this.hideResults();
        this.elements.searchInput.focus();

        // Trigger events
        this.triggerEvent('personClear');
    }

    /**
     * Show results dropdown
     */
    showResults() {
        this.elements.results.classList.remove('hidden');
        this.elements.noResults.classList.add('hidden');
        this.elements.loading.classList.add('hidden');
    }

    /**
     * Hide results dropdown
     */
    hideResults() {
        this.elements.results.classList.add('hidden');
    }

    /**
     * Show no results message
     */
    showNoResults() {
        this.elements.resultsList.innerHTML = '';
        this.elements.noResults.classList.remove('hidden');
        this.showResults();
    }

    /**
     * Show loading state
     */
    setLoading(loading) {
        this.isLoading = loading;

        if (loading) {
            this.elements.spinner.classList.remove('hidden');
            this.elements.clearButton?.classList.add('hidden');
            this.elements.loading.classList.remove('hidden');
            this.showResults();
        } else {
            this.elements.spinner.classList.add('hidden');
            this.elements.loading.classList.add('hidden');
            if (this.selectedPerson) {
                this.elements.clearButton?.classList.remove('hidden');
            }
        }
    }

    /**
     * Show error message
     */
    showError(message) {
        this.elements.resultsList.innerHTML = `
            <div class="p-4 text-center text-red-500">
                <svg class="mx-auto h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>${message}</div>
            </div>
        `;
        this.showResults();
    }

    /**
     * Load initial data if pre-selected
     */
    loadInitialData() {
        const hiddenValue = this.elements.hiddenInput.value;
        if (hiddenValue) {
            this.loadPersonById(hiddenValue);
        }
    }

    /**
     * Load person by ID
     */
    async loadPersonById(personId) {
        try {
            // ✅ API Helper kullan (merkezi yönetim)
            if (!this.typeConfig.endpoint) {
                return;
            }

            const endpointUrl = this.typeConfig.endpoint.replace('/search', `/${personId}`);
            const response = await window.APIHelper?.safeFetch(endpointUrl, {
                method: 'GET',
            });

            if (response.ok) {
                const data = await window.APIHelper?.handleResponse(response);
                this.selectPerson(data.data?.data || data.data);
            }
        } catch (error) {
            console.error('[UnifiedPersonSelector] Error loading person:', error);
        }
    }

    /**
     * Show create modal
     */
    showCreateModal() {
        // This would integrate with a modal system
        this.triggerEvent('createModalRequest', this.config.type);
    }

    /**
     * Get person name (handles different data structures)
     */
    getPersonName(person) {
        if (person.name) return person.name;
        if (person.ad && person.soyad) return `${person.ad} ${person.soyad}`;
        return person.ad || person.soyad || 'İsimsiz';
    }

    /**
     * Get person contact info
     */
    getPersonContact(person) {
        const contact = [];
        if (person.telefon) contact.push(person.telefon);
        if (person.email) contact.push(person.email);
        if (person.phone_number && !person.telefon) contact.push(person.phone_number);
        return contact.join('•') || 'İletişim bilgisi yok';
    }

    /**
     * Get initials from name
     */
    getInitials(name) {
        return name
            .split('')
            .map((n) => n.charAt(0))
            .join('')
            .toUpperCase()
            .substring(0, 2);
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Event system
     */
    on(eventName, callback) {
        if (!this.eventListeners[eventName]) {
            this.eventListeners[eventName] = [];
        }
        this.eventListeners[eventName].push(callback);
        return this;
    }

    off(eventName, callback) {
        if (this.eventListeners[eventName]) {
            this.eventListeners[eventName] = this.eventListeners[eventName].filter(
                (listener) => listener !== callback,
            );
        }
        return this;
    }

    triggerEvent(eventName, data) {
        if (this.eventListeners[eventName]) {
            this.eventListeners[eventName].forEach((callback) => {
                callback(data);
            });
        }
    }

    /**
     * Public API methods
     */
    getValue() {
        return this.elements.hiddenInput.value;
    }

    setValue(personId) {
        if (personId) {
            this.loadPersonById(personId);
        } else {
            this.clearSelection();
        }
    }

    getSelectedPerson() {
        return this.selectedPerson;
    }

    destroy() {
        // Cleanup
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        // Remove event listeners
        this.eventListeners = {};

        // Clear container
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = UnifiedPersonSelector;
}

// Global assignment for direct script inclusion
if (typeof window !== 'undefined') {
    window.UnifiedPersonSelector = UnifiedPersonSelector;
}
