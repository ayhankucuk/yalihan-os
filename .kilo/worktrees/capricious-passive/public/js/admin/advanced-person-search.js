/**
 * Advanced Person Search System
 * Modern typeahead search with filtering, sorting, and modal integration
 * Context7 compliant - Person management system
 */

class AdvancedPersonSearch {
    constructor(containerId = 'person-search-container') {
        this.containerId = containerId;
        this.persons = [];
        this.filteredPersons = [];
        this.selectedPerson = null;
        this.searchTerm = '';
        this.isLoading = false;
        this.debounceTimer = null;
        this.maxResults = 20;
        this.minSearchLength = 2;

        this.init();
    }

    init() {
        this.createHTML();
        this.bindEvents();
        this.loadPersons();
        console.log('✅ Advanced Person Search initialized');
    }

    createHTML() {
        const container = document.getElementById(this.containerId);
        if (!container) {
            console.error('Person search container not found:', this.containerId);
            return;
        }

        container.innerHTML = `
            <div class="advanced-person-search">
                <!-- Search Input -->
                <div class="relative">
                    <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                        <i class="fas fa-user text-sm"></i>
                    </div>
                    <input
                        type="text"
                        id="person-search-input"
                        name="person_search"
                        class="w-full pl-10 pr-12 py-3 border border-gray-300 dark:border-gray-600 rounded-lg
                               focus:ring-2 focus:ring-blue-500 focus:border-transparent
                               bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100
                               placeholder-gray-500 dark:placeholder-gray-400"
                        placeholder="Kişi ara... (ad, soyad, telefon, email)"
                        autocomplete="off"
                    />
                    <div class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <button type="button" id="clear-search" class="text-gray-400 hover:text-gray-600 hidden">
                            <i class="fas fa-times"></i>
                        </button>
                        <div id="search-loading" class="hidden">
                            <i class="fas fa-spinner fa-spin text-blue-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Selected Person Display -->
                <div id="selected-person-display" class="hidden mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-semibold">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900 dark:text-gray-100" id="selected-person-name"></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400" id="selected-person-contact"></div>
                            </div>
                        </div>
                        <button type="button" id="remove-selected-person" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Search Results Dropdown -->
                <div id="search-results" class="hidden absolute z-50 w-full mt-1 bg-white dark:bg-gray-800
                                                 border border-gray-200 dark:border-gray-600 rounded-lg shadow-lg max-h-96 overflow-y-auto">
                    <!-- Results will be populated here -->
                </div>

                <!-- Quick Actions -->
                <div class="mt-3 flex items-center justify-between">
                    <div class="text-sm text-gray-500 dark:text-gray-400" id="search-status">
                        Kişi aramak için yazmaya başlayın
                    </div>
                    <button type="button" id="add-new-person"
                            class="px-3 py-1 text-sm bg-blue-100 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300
                                   rounded-md hover:bg-blue-200 dark:hover:bg-blue-900/40 transition-colors">
                        <i class="fas fa-plus mr-1"></i>
                        Yeni Kişi Ekle
                    </button>
                </div>

                <!-- Hidden inputs for form submission -->
                <input type="hidden" id="selected_person_id" name="ilan_sahibi_id" value="" />
                <input type="hidden" id="selected_person_data" name="person_data" value="" />
            </div>
        `;
    }

    bindEvents() {
        const searchInput = document.getElementById('person-search-input');
        const clearButton = document.getElementById('clear-search');
        const removeButton = document.getElementById('remove-selected-person');
        const addNewButton = document.getElementById('add-new-person');

        if (searchInput) {
            // Search input events
            searchInput.addEventListener('input', (e) => this.onSearchInput(e));
            searchInput.addEventListener('focus', (e) => this.onSearchFocus(e));
            searchInput.addEventListener('blur', (e) => this.onSearchBlur(e));
            searchInput.addEventListener('keydown', (e) => this.onSearchKeydown(e));
        }

        if (clearButton) {
            clearButton.addEventListener('click', () => this.clearSearch());
        }

        if (removeButton) {
            removeButton.addEventListener('click', () => this.clearSelection());
        }

        if (addNewButton) {
            addNewButton.addEventListener('click', () => this.openAddPersonModal());
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.advanced-person-search')) {
                this.hideResults();
            }
        });
    }

    async loadPersons() {
        try {
            this.setLoading(true);
            const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.person && window.APIConfig.admin.person.search
                ? window.APIConfig.admin.person.search
                : '/api/admin/persons/search';
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content'),
                },
            });

            if (response.ok) {
                const data = await response.json();
                this.persons = data.data || data.persons || [];
                this.updateSearchStatus(`${this.persons.length} kişi yüklendi`);
            } else {
                throw new Error('Kişiler yüklenemedi');
            }
        } catch (error) {
            console.error('Load persons error:', error);
            this.updateSearchStatus('Kişiler yüklenirken hata oluştu');
        } finally {
            this.setLoading(false);
        }
    }

    onSearchInput(e) {
        const value = e.target.value.trim();
        this.searchTerm = value;

        // Show/hide clear button
        const clearButton = document.getElementById('clear-search');
        if (clearButton) {
            clearButton.classList.toggle('hidden', value.length === 0);
        }

        // Debounced search
        clearTimeout(this.debounceTimer);
        this.debounceTimer = setTimeout(() => {
            this.performSearch(value);
        }, 300);
    }

    onSearchFocus(e) {
        if (this.searchTerm.length >= this.minSearchLength) {
            this.showResults();
        }
    }

    onSearchBlur(e) {
        // Delay hiding to allow clicking on results
        setTimeout(() => {
            if (!document.querySelector('.advanced-person-search:hover')) {
                this.hideResults();
            }
        }, 150);
    }

    onSearchKeydown(e) {
        const resultsContainer = document.getElementById('search-results');
        const items = resultsContainer?.querySelectorAll('.search-result-item');

        if (!items || items.length === 0) return;

        const currentActive = resultsContainer.querySelector('.result-active');
        let currentIndex = -1;

        if (currentActive) {
            currentIndex = Array.from(items).indexOf(currentActive);
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                const nextIndex = Math.min(currentIndex + 1, items.length - 1);
                this.setActiveResult(items, nextIndex);
                break;

            case 'ArrowUp':
                e.preventDefault();
                const prevIndex = Math.max(currentIndex - 1, 0);
                this.setActiveResult(items, prevIndex);
                break;

            case 'Enter':
                e.preventDefault();
                if (currentActive) {
                    const personId = currentActive.dataset.personId;
                    this.selectPerson(personId);
                }
                break;

            case 'Escape':
                this.hideResults();
                break;
        }
    }

    setActiveResult(items, index) {
        items.forEach((item, i) => {
            item.classList.toggle('result-active', i === index);
        });
    }

    async performSearch(term) {
        if (term.length < this.minSearchLength) {
            this.hideResults();
            this.updateSearchStatus('En az 2 karakter girin');
            return;
        }

        this.setLoading(true);
        this.updateSearchStatus('Aranıyor...');

        try {
            // Client-side filtering for better performance
            this.filteredPersons = this.persons
                .filter((person) => {
                    const searchStr = term.toLowerCase();
                    return (
                        person.ad?.toLowerCase().includes(searchStr) ||
                        person.soyad?.toLowerCase().includes(searchStr) ||
                        person.telefon?.includes(searchStr) ||
                        person.email?.toLowerCase().includes(searchStr) ||
                        `${person.ad} ${person.soyad}`.toLowerCase().includes(searchStr)
                    );
                })
                .slice(0, this.maxResults);

            this.renderResults();
            this.showResults();
            this.updateSearchStatus(`${this.filteredPersons.length} kişi bulundu`);
        } catch (error) {
            console.error('Search error:', error);
            this.updateSearchStatus('Arama hatası');
        } finally {
            this.setLoading(false);
        }
    }

    renderResults() {
        const resultsContainer = document.getElementById('search-results');
        if (!resultsContainer) return;

        if (this.filteredPersons.length === 0) {
            resultsContainer.innerHTML = `
                <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-search mb-2 text-2xl"></i>
                    <div>Kişi bulunamadı</div>
                    <button type="button" onclick="advancedPersonSearch.openAddPersonModal()"
                            class="mt-2 text-blue-600 hover:text-blue-800 text-sm">
                        <i class="fas fa-plus mr-1"></i>
                        Yeni kişi ekle
                    </button>
                </div>
            `;
            return;
        }

        resultsContainer.innerHTML = this.filteredPersons
            .map(
                (person) => `
            <div class="search-result-item p-3 hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-100 dark:border-gray-600 cursor-pointer"
                 data-person-id="${person.id}"
                 onclick="advancedPersonSearch.selectPerson(${person.id})">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-semibold">
                        ${this.getPersonInitials(person)}
                    </div>
                    <div class="flex-1">
                        <div class="font-medium text-gray-900 dark:text-gray-100">
                            ${person.ad} ${person.soyad}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            ${person.telefon || 'Telefon yok'} • ${person.email || 'Email yok'}
                        </div>
                        ${
                            person.sehir
                                ? `<div class="text-xs text-gray-500">${
                                      person.sehir
                                  }, ${person.ulke || 'Türkiye'}</div>`
                                : ''
                        }
                    </div>
                    <div class="text-gray-400">
                        <i class="fas fa-chevron-right text-xs"></i>
                    </div>
                </div>
            </div>
        `
            )
            .join('');
    }

    selectPerson(personId) {
        const person = this.persons.find((p) => p.id == personId);
        if (!person) return;

        this.selectedPerson = person;

        // Update hidden inputs
        document.getElementById('selected_person_id').value = person.id;
        document.getElementById('selected_person_data').value = JSON.stringify(person);

        // Clear search input
        document.getElementById('person-search-input').value = '';
        this.searchTerm = '';

        // Show selected person
        this.showSelectedPerson();
        this.hideResults();
        this.updateSearchStatus('Kişi seçildi');

        // Trigger change event for form validation
        this.triggerChangeEvent();

        console.log('Person selected:', person);
    }

    showSelectedPerson() {
        const displayContainer = document.getElementById('selected-person-display');
        const nameElement = document.getElementById('selected-person-name');
        const contactElement = document.getElementById('selected-person-contact');

        if (displayContainer && nameElement && contactElement && this.selectedPerson) {
            nameElement.textContent = `${this.selectedPerson.ad} ${this.selectedPerson.soyad}`;
            contactElement.textContent = `${
                this.selectedPerson.telefon || 'Telefon yok'
            } • ${this.selectedPerson.email || 'Email yok'}`;

            displayContainer.classList.remove('hidden');
        }
    }

    clearSelection() {
        this.selectedPerson = null;

        // Clear hidden inputs
        document.getElementById('selected_person_id').value = '';
        document.getElementById('selected_person_data').value = '';

        // Hide selected person display
        document.getElementById('selected-person-display').classList.add('hidden');

        this.updateSearchStatus('Kişi seçimi temizlendi');
        this.triggerChangeEvent();
    }

    clearSearch() {
        document.getElementById('person-search-input').value = '';
        this.searchTerm = '';
        this.hideResults();
        document.getElementById('clear-search').classList.add('hidden');
        this.updateSearchStatus('Arama temizlendi');
    }

    showResults() {
        document.getElementById('search-results').classList.remove('hidden');
    }

    hideResults() {
        document.getElementById('search-results').classList.add('hidden');
    }

    setLoading(loading) {
        this.isLoading = loading;
        const loadingIcon = document.getElementById('search-loading');
        if (loadingIcon) {
            loadingIcon.classList.toggle('hidden', !loading);
        }
    }

    updateSearchStatus(message) {
        const statusElement = document.getElementById('search-status');
        if (statusElement) {
            statusElement.textContent = message;
        }
    }

    getPersonInitials(person) {
        const first = person.ad?.charAt(0)?.toUpperCase() || '';
        const last = person.soyad?.charAt(0)?.toUpperCase() || '';
        return first + last || 'KS';
    }

    triggerChangeEvent() {
        const event = new Event('change', { bubbles: true });
        document.getElementById('selected_person_id').dispatchEvent(event);
    }

    openAddPersonModal() {
        // Integration with existing person modal
        if (window.initializePersonModal) {
            window.initializePersonModal();
        }

        // Show modal
        const modal = document.getElementById('add-person-modal');
        if (modal) {
            modal.classList.remove('hidden');
        }

        console.log('Add person modal opened');
    }

    // Public API
    getSelectedPerson() {
        return this.selectedPerson;
    }

    setSelectedPerson(person) {
        this.selectPerson(person.id);
    }

    refresh() {
        this.loadPersons();
    }
}

// Initialize when DOM is ready
let advancedPersonSearch;

document.addEventListener('DOMContentLoaded', function () {
    // Initialize if container exists
    if (document.getElementById('person-search-container')) {
        advancedPersonSearch = new AdvancedPersonSearch();
    }
});

// Global export
window.AdvancedPersonSearch = AdvancedPersonSearch;
window.advancedPersonSearch = advancedPersonSearch;
