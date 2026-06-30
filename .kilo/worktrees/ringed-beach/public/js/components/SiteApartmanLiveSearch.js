// Site/Apartman Live Search Component
// Context7 Standard: C7-SITE-LIVE-SEARCH-2025-10-17
// Alpine.js komponenti - Neo Design System uyumlu

function siteApartmanLiveSearch() {
    return {
        // State
        query: '',
        results: [],
        selectedSite: null,
        loading: false,
        showResults: false,
        debounceTimer: null,

        // Configuration
        minQueryLength: 2,
        searchDelay: 300, // ms
        maxResults: 10,

        // Lifecycle
        init() {
            this.$watch('query', (value) => {
                this.handleQueryChange(value);
            });

            // Click outside to close results
            document.addEventListener('click', (e) => {
                if (!this.$el.contains(e.target)) {
                    this.showResults = false;
                }
            });
        },

        // Methods
        handleQueryChange(query) {
            clearTimeout(this.debounceTimer);

            if (query.length < this.minQueryLength) {
                this.results = [];
                this.showResults = false;
                return;
            }

            this.debounceTimer = setTimeout(() => {
                this.search(query);
            }, this.searchDelay);
        },

        async search(query) {
            if (this.loading) return;

            this.loading = true;

            try {
                const response = await fetch(
                    `/api/sites/search?search_term=${encodeURIComponent(
                        query
                    )}&limit=${this.maxResults}`,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    }
                );

                const data = await response.json();

                if (data.success) {
                    this.results = data.data || [];
                    this.showResults = this.results.length > 0;
                } else {
                    console.error('Site search error:', data.message);
                    this.results = [];
                    this.showResults = false;
                }
            } catch (error) {
                console.error('Site search fetch error:', error);
                this.results = [];
                this.showResults = false;
            } finally {
                this.loading = false;
            }
        },

        selectSite(site) {
            this.selectedSite = site;
            this.query = site.name;
            this.showResults = false;

            // Emit custom event for parent components
            this.$dispatch('site-selected', { site: site });

            // Update form fields if they exist
            this.updateFormFields(site);
        },

        updateFormFields(site) {
            // Update site name field
            const siteNameField = document.querySelector('input[name="site_adi"]');
            if (siteNameField) {
                siteNameField.value = site.name;
            }

            // Update site ID field (hidden)
            const siteIdField = document.querySelector('input[name="site_id"]');
            if (siteIdField) {
                siteIdField.value = site.id;
            }

            // Update address field if available
            const addressField = document.querySelector('input[name="site_adresi"]');
            if (addressField && site.adres) {
                addressField.value = site.adres;
            }

            // Update apartment count field
            const daireCountField = document.querySelector('input[name="toplam_daire_sayisi"]');
            if (daireCountField && site.daire_sayisi) {
                daireCountField.value = site.daire_sayisi;
            }
        },

        clearSelection() {
            this.selectedSite = null;
            this.query = '';
            this.results = [];
            this.showResults = false;

            // Clear form fields
            this.clearFormFields();

            // Emit clear event
            this.$dispatch('site-cleared');
        },

        clearFormFields() {
            const fields = ['site_adi', 'site_id', 'site_adresi', 'toplam_daire_sayisi'];
            fields.forEach((fieldName) => {
                const field = document.querySelector(`input[name="${fieldName}"]`);
                if (field) {
                    field.value = '';
                }
            });
        },

        highlightMatch(text, query) {
            if (!query || !text) return text;

            const regex = new RegExp(`(${query})`, 'gi');
            return text.replace(
                regex,
                '<mark class="bg-yellow-200 text-yellow-900 px-1 rounded">$1</mark>'
            );
        },

        formatSiteDisplay(site) {
            let display = site.name;
            if (site.adres) {
                display += ` - ${site.adres}`;
            }
            if (site.daire_sayisi) {
                display += ` (${site.daire_sayisi} daire)`;
            }
            return display;
        },

        // Keyboard navigation
        handleKeydown(event) {
            if (!this.showResults || this.results.length === 0) return;

            const activeIndex = this.results.findIndex((result) => result.active);

            switch (event.key) {
                case 'ArrowDown':
                    event.preventDefault();
                    this.setActiveResult((activeIndex + 1) % this.results.length);
                    break;

                case 'ArrowUp':
                    event.preventDefault();
                    const newIndex = activeIndex - 1;
                    this.setActiveResult(newIndex < 0 ? this.results.length - 1 : newIndex);
                    break;

                case 'Enter':
                    event.preventDefault();
                    const activeResult = this.results.find((result) => result.active);
                    if (activeResult) {
                        this.selectSite(activeResult);
                    }
                    break;

                case 'Escape':
                    this.showResults = false;
                    break;
            }
        },

        setActiveResult(index) {
            this.results.forEach((result, i) => {
                result.active = i === index;
            });
        },

        // Utility methods
        isEmpty() {
            return !this.selectedSite && !this.query;
        },

        hasResults() {
            return this.results.length > 0;
        },

        isSelected(site) {
            return this.selectedSite && this.selectedSite.id === site.id;
        },

        getStatusClass() {
            if (this.loading) return 'border-blue-300';
            if (this.selectedSite) return 'border-green-300';
            if (this.query && !this.hasResults()) return 'border-red-300';
            return 'border-gray-300';
        },

        getStatusIcon() {
            if (this.loading) return 'fas fa-spinner fa-spin';
            if (this.selectedSite) return 'fas fa-check text-green-500';
            if (this.query && !this.hasResults()) return 'fas fa-exclamation-triangle text-red-500';
            return 'fas fa-building text-gray-400';
        },
    };
}
