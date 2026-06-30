/**
 * 🚀 EmlakPro Quick Search - Ultra Hızlı Arama Sistemi
 *
 * Özellikler:
 * - 300ms debounce ile real-time arama
 * - Cache destekli hızlı sonuçlar
 * - Keyboard navigation (↑↓ arrows)
 * - Click to navigate
 * - Modern UI with icons
 * - Multiple input support
 * - Relevance scoring
 */

class QuickSearch {
    constructor(options = {}) {
        this.debounceDelay = 300; // ms
        this.minSearchLength = 2;
        this.maxSuggestions = 10;
        this.isLoading = false;
        this.currentIndex = -1;

        // API endpoint
        this.apiUrl = options.apiUrl || (window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.searchUnified
            ? window.APIConfig.admin.searchUnified
            : '/api/admin/search/unified');

        // Initialize all inputs with data-quick-search
        this.initializeInputs();

        // Global search stats
        this.loadSearchStats();

        console.log('🚀 QuickSearch initialized for multiple inputs');
    }

    initializeInputs() {
        const inputs = document.querySelectorAll('[data-quick-search]');

        inputs.forEach((input, index) => {
            const instance = {
                element: input,
                suggestions: [],
                isLoading: false,
                searchType: input.dataset.searchType || 'all',
                dropdown: null,
                currentIndex: -1,
            };

            // Create dropdown container
            this.createDropdown(instance);

            // Add event listeners
            this.addEventListeners(instance);

            // Store instance
            input.quickSearchInstance = instance;
        });
    }

    createDropdown(instance) {
        // Remove existing dropdown if any
        if (instance.dropdown) {
            instance.dropdown.remove();
        }

        // Create dropdown container
        instance.dropdown = document.createElement('div');
        instance.dropdown.className = 'quick-search-dropdown';
        instance.dropdown.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            max-height: 400px;
            overflow-y: auto;
            display: none;
        `;

        // Insert after input
        instance.element.parentNode.style.position = 'relative';
        instance.element.parentNode.insertBefore(instance.dropdown, instance.element.nextSibling);
    }

    addEventListeners(instance) {
        const input = instance.element;

        // Input event with debounce
        let debounceTimer;
        input.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                this.performSearch(instance, e.target.value);
            }, this.debounceDelay);
        });

        // Focus event
        input.addEventListener('focus', () => {
            if (input.value.length >= this.minSearchLength) {
                this.showDropdown(instance);
            }
        });

        // Blur event (with delay to allow clicks)
        input.addEventListener('blur', () => {
            setTimeout(() => {
                this.hideDropdown(instance);
            }, 200);
        });

        // Keyboard navigation
        input.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(instance, e);
        });

        // Click outside to close
        document.addEventListener('click', (e) => {
            if (!input.contains(e.target) && !instance.dropdown.contains(e.target)) {
                this.hideDropdown(instance);
            }
        });
    }

    async performSearch(instance, query) {
        if (instance.isLoading) return;

        // Clear previous suggestions
        instance.suggestions = [];
        instance.currentIndex = -1;

        // Minimum length check
        if (query.length < this.minSearchLength) {
            this.hideDropdown(instance);
            return;
        }

        instance.isLoading = true;
        this.showLoadingState(instance);

        try {
            // Build API URL
            const params = new URLSearchParams({
                q: query,
                type: instance.searchType,
                limit: this.maxSuggestions,
            });

            const response = await fetch(`${this.apiUrl}?${params}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                instance.suggestions = data.data || [];
                this.renderSuggestions(instance);
                this.showDropdown(instance);
                console.log(`✅ Quick search results: ${instance.suggestions.length} items`);
            } else {
                console.error('Quick search error:', data.error);
                this.hideDropdown(instance);
            }
        } catch (error) {
            console.error('Quick search request failed:', error);
            this.hideDropdown(instance);
        } finally {
            instance.isLoading = false;
        }
    }

    renderSuggestions(instance) {
        const dropdown = instance.dropdown;

        if (instance.suggestions.length === 0) {
            dropdown.innerHTML = `
                <div class="p-4 text-center text-gray-500">
                    <svg class="w-6 h-6 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Sonuç bulunamadı
                </div>
            `;
            return;
        }

        let html = '<div class="py-2">';

        // Group by type
        const grouped = this.groupByType(instance.suggestions);

        Object.keys(grouped).forEach((type) => {
            const items = grouped[type];
            const typeLabel = this.getTypeLabel(type);

            html += `
                <div class="px-3 py-2 text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-100">
                    ${typeLabel} (${items.length})
                </div>
            `;

            items.forEach((item, index) => {
                const isSelected = index === instance.currentIndex;
                const icon = this.getIcon(item.icon);

                html += `
                    <div class="quick-search-item ${
                        isSelected ? 'bg-blue-50 border-blue-200' : 'hover:bg-gray-50'
                    }"
                         data-index="${index}"
                         data-type="${item.type}"
                         data-url="${item.url || '#'}"
                         style="cursor: pointer; padding: 12px 16px; border-left: 3px solid transparent; ${
                             isSelected ? 'border-left-color: #3b82f6;' : ''
                         }">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 text-gray-400">
                                ${icon}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-gray-900 truncate dark:text-slate-100">
                                    ${this.highlightQuery(item.title, instance.element.value)}
                                </div>
                                <div class="text-sm text-gray-500 truncate">
                                    ${item.subtitle}
                                </div>
                                ${
                                    item.price
                                        ? `<div class="text-xs text-green-600 font-medium">${item.price}</div>`
                                        : ''
                                }
                            </div>
                            ${
                                item.relevance
                                    ? `<div class="text-xs text-gray-400">${item.relevance}%</div>`
                                    : ''
                            }
                        </div>
                    </div>
                `;
            });
        });

        html += '</div>';
        dropdown.innerHTML = html;

        // Add click handlers
        dropdown.querySelectorAll('.quick-search-item').forEach((item) => {
            item.addEventListener('click', () => {
                this.selectItem(instance, parseInt(item.dataset.index));
            });
        });
    }

    groupByType(suggestions) {
        return suggestions.reduce((groups, item) => {
            const type = item.type;
            if (!groups[type]) {
                groups[type] = [];
            }
            groups[type].push(item);
            return groups;
        }, {});
    }

    getTypeLabel(type) {
        const labels = {
            ilan: 'İlanlar',
            kisi: 'Kişiler',
            danisman: 'Danışmanlar',
            building: 'Site/Apartman',
        };
        return labels[type] || type;
    }

    getIcon(iconName) {
        const icons = {
            home: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>',
            user: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
            'user-tie':
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>',
            building:
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>',
        };
        return icons[iconName] || icons['user'];
    }

    highlightQuery(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>');
    }

    handleKeyboardNavigation(instance, event) {
        const suggestions = instance.suggestions;
        const totalItems = suggestions.length;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                instance.currentIndex = Math.min(instance.currentIndex + 1, totalItems - 1);
                this.updateSelection(instance);
                break;

            case 'ArrowUp':
                event.preventDefault();
                instance.currentIndex = Math.max(instance.currentIndex - 1, -1);
                this.updateSelection(instance);
                break;

            case 'Enter':
                event.preventDefault();
                if (instance.currentIndex >= 0 && instance.currentIndex < totalItems) {
                    this.selectItem(instance, instance.currentIndex);
                }
                break;

            case 'Escape':
                this.hideDropdown(instance);
                instance.element.blur();
                break;
        }
    }

    updateSelection(instance) {
        const items = instance.dropdown.querySelectorAll('.quick-search-item');

        items.forEach((item, index) => {
            const isSelected = index === instance.currentIndex;
            item.classList.toggle('bg-blue-50', isSelected);
            item.classList.toggle('border-blue-200', isSelected);
            item.style.borderLeftColor = isSelected ? '#3b82f6' : 'transparent';
        });
    }

    selectItem(instance, index) {
        const item = instance.suggestions[index];
        if (!item) return;

        // Update input value
        instance.element.value = item.title;

        // Navigate to URL if available
        if (item.url && item.url !== '#') {
            window.location.href = item.url;
        } else if (item.search_query) {
            // For search queries, trigger a new search
            instance.element.value = item.search_query;
            this.performSearch(instance, item.search_query);
        }

        // Hide dropdown
        this.hideDropdown(instance);
    }

    showDropdown(instance) {
        if (instance.dropdown) {
            instance.dropdown.style.display = 'block';
        }
    }

    hideDropdown(instance) {
        if (instance.dropdown) {
            instance.dropdown.style.display = 'none';
        }
    }

    showLoadingState(instance) {
        const dropdown = instance.dropdown;
        dropdown.innerHTML = `
            <div class="p-4 text-center">
                <div class="inline-flex items-center space-x-2">
                    <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm text-gray-600">Aranıyor...</span>
                </div>
            </div>
        `;
        this.showDropdown(instance);
    }

    async loadSearchStats() {
        try {
            const response = await fetch('/admin/api/search-stats');
            const data = await response.json();

            if (data.success) {
                this.searchStats = data.stats;
                console.log('📊 Search stats loaded:', this.searchStats);
            }
        } catch (error) {
            console.error('Failed to load search stats:', error);
        }
    }

    // Public method to clear cache
    async clearCache() {
        try {
            const response = await fetch('/admin/api/search-clear-cache', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'application/json',
                },
            });

            const data = await response.json();

            if (data.success) {
                console.log('✅ Search cache cleared');
                return true;
            }
        } catch (error) {
            console.error('Failed to clear cache:', error);
        }
        return false;
    }
}

// Global instance
window.quickSearch = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    window.quickSearch = new QuickSearch();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = QuickSearch;
}
