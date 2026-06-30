// Yalıhan Emlak Search Optimizer
console.log('Search Optimizer: Loading...');

class SearchOptimizer {
    constructor() {
        this.searchForm = null;
        this.advancedPanel = null;
        this.init();
    }

    init() {
        this.searchForm = document.querySelector('.search-form');
        this.advancedPanel = document.getElementById('advancedSearchPanel');

        if (this.searchForm) {
            this.bindEvents();
            this.initializeForm();
        }
    }

    bindEvents() {
        // Search button click
        const searchBtn = this.searchForm?.querySelector('button[onclick="performSearch()"]');
        if (searchBtn) {
            searchBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }

        // Advanced search toggle
        const advancedBtn = this.searchForm?.querySelector(
            'button[onclick="toggleAdvancedSearch()"]'
        );
        if (advancedBtn) {
            advancedBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleAdvancedSearch();
            });
        }

        // Clear button
        const clearBtn = this.searchForm?.querySelector('button[onclick="clearAdvancedSearch()"]');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearAdvancedSearch();
            });
        }

        // Form validation
        const inputs = this.searchForm?.querySelectorAll('input, select');
        inputs?.forEach((input) => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.debounceValidation(input));
        });
    }

    initializeForm() {
        // Set default values
        const locationInput = this.searchForm?.querySelector('input[placeholder*="Şehir"]');
        if (locationInput) {
            locationInput.value = 'Bodrum';
        }

        // Add form validation classes
        const inputs = this.searchForm?.querySelectorAll('input, select');
        inputs?.forEach((input) => {
            input.classList.add('form-input');
        });
    }

    performSearch() {
        const formData = this.collectFormData();

        if (this.validateForm(formData)) {
            this.showLoading();
            this.executeSearch(formData);
        } else {
            this.showToast('Lütfen gerekli alanları doldurun', 'error');
        }
    }

    collectFormData() {
        const formData = {};
        const inputs = this.searchForm?.querySelectorAll('input, select');

        inputs?.forEach((input) => {
            if (input.name || input.id) {
                const key = input.name || input.id;
                formData[key] = input.value;
            }
        });

        return formData;
    }

    validateForm(data) {
        // Basic validation
        if (!data.location && !data.property_type) {
            return false;
        }
        return true;
    }

    validateField(field) {
        const value = field.value.trim();
        const isValid = value.length > 0;

        field.classList.toggle('border-red-500', !isValid);
        field.classList.toggle('border-green-500', isValid);

        return isValid;
    }

    debounceValidation(field) {
        clearTimeout(field.validationTimeout);
        field.validationTimeout = setTimeout(() => {
            this.validateField(field);
        }, 300);
    }

    executeSearch(data) {
        // Simulate API call
        setTimeout(() => {
            this.hideLoading();
            this.showToast('Arama tamamlandı! Sonuçlar yükleniyor...', 'success');

            // Redirect to search results
            const searchParams = new URLSearchParams(data);
            window.location.href = `/yalihan/properties?${searchParams.toString()}`;
        }, 1500);
    }

    toggleAdvancedSearch() {
        const panel = this.advancedPanel;
        const icon = document.getElementById('advancedToggleIcon');

        if (panel && icon) {
            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                icon.textContent = '▲';
                panel.style.maxHeight = panel.scrollHeight + 'px';
            } else {
                panel.classList.add('hidden');
                icon.textContent = '▼';
                panel.style.maxHeight = '0px';
            }
        }
    }

    clearAdvancedSearch() {
        const panel = this.advancedPanel;
        if (panel) {
            const inputs = panel.querySelectorAll('input, select');
            inputs.forEach((input) => {
                if (input.type === 'number') {
                    input.value = '';
                } else {
                    input.selectedIndex = 0;
                }
                input.classList.remove('border-red-500', 'border-green-500');
            });
            this.showToast('Gelişmiş arama temizlendi', 'success');
        }
    }

    showLoading() {
        const searchBtn = this.searchForm?.querySelector('button[onclick="performSearch()"]');
        if (searchBtn) {
            searchBtn.disabled = true;
            searchBtn.innerHTML = '⏳ Aranıyor...';
            searchBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }

    hideLoading() {
        const searchBtn = this.searchForm?.querySelector('button[onclick="performSearch()"]');
        if (searchBtn) {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '🔍 Ara';
            searchBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }

    showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 bg-white rounded-lg p-4 shadow-lg border-l-4 ${
            type === 'success' ? 'border-green-500' : 'border-red-500'
        } z-50 transform translate-x-full transition-transform duration-300`;

        toast.innerHTML = `
            <div class="flex items-center gap-2">
                <span class="text-2xl">${type === 'success' ? '✅' : '❌'}</span>
                <span class="font-medium">${message}</span>
            </div>
        `;

        document.body.appendChild(toast);

        setTimeout(() => toast.classList.remove('translate-x-full'), 100);
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => document.body.removeChild(toast), 300);
        }, 3000);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SearchOptimizer();
});

console.log('Search Optimizer: Loaded successfully');
