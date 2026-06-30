/**
 * Advanced Site/Apartman Search System
 * Modern UI/UX Design Implementation
 * Context7 Compliant
 */

class AdvancedSiteSearch {
    constructor(options = {}) {
        this.container = options.container || '#site-search-container';
        this.apiEndpoint = options.apiEndpoint || (window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.sites && window.APIConfig.admin.sites.search ? window.APIConfig.admin.sites.search : '/api/admin/sites/search');
        this.debounceDelay = options.debounceDelay || 300;
        this.minQueryLength = options.minQueryLength || 2;
        this.maxResults = options.maxResults || 20;

        this.searchTimeout = null;
        this.currentQuery = '';
        this.selectedSite = null;
        this.isLoading = false;

        this.init();
    }

    init() {
        this.createSearchInterface();
        this.bindEvents();
        this.initializeAnimations();
    }

    createSearchInterface() {
        const container = document.querySelector(this.container);
        if (!container) return;

        container.innerHTML = `
            <div class="neo-advanced-site-search">
                <!-- Search Input with Modern Design -->
                <div class="neo-search-input-container">
                    <div class="neo-search-input-wrapper">
                        <svg class="neo-search-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                            <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <input
                            type="text"
                            id="site-search-input"
                            class="neo-search-input"
                            placeholder="Site veya apartman ara..."
                            autocomplete="off"
                        />
                        <div class="neo-loading-spinner" id="site-search-loading">
                            <div class="neo-spinner"></div>
                        </div>
                    </div>
                    <button type="button" class="neo-btn neo-neo-btn neo-btn-primary neo-add-site-btn" id="add-new-site-btn">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M12 5v14m7-7H5" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        Yeni Site Ekle
                    </button>
                </div>

                <!-- Search Results Dropdown -->
                <div class="neo-search-results" id="site-search-results">
                    <div class="neo-results-header">
                        <span class="neo-results-count" id="results-count">0 sonuç</span>
                        <div class="neo-filter-tabs">
                            <button class="neo-filter-tab active" data-filter="all">Tümü</button>
                            <button class="neo-filter-tab" data-filter="site">Site</button>
                            <button class="neo-filter-tab" data-filter="apartman">Apartman</button>
                        </div>
                    </div>
                    <div class="neo-results-list" id="results-list">
                        <!-- Results will be populated here -->
                    </div>
                    <div class="neo-no-results" id="no-results">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" class="neo-no-results-icon">
                            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                            <path d="m21 21-4.35-4.35" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <h3>Site bulunamadı</h3>
                        <p>Aradığınız site veya apartman bulunamadı. Yeni bir site eklemek ister misiniz?</p>
                        <button class="neo-btn neo-neo-btn neo-btn-primary" onclick="this.showAddSiteModal()">
                            Yeni Site Ekle
                        </button>
                    </div>
                </div>

                <!-- Selected Site Display -->
                <div class="neo-selected-site" id="selected-site" style="display: none;">
                    <div class="neo-selected-site-content">
                        <div class="neo-site-avatar">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-2" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <div class="neo-site-info">
                            <h4 class="neo-site-name"></h4>
                            <p class="neo-site-details"></p>
                        </div>
                        <button class="neo-remove-selection" onclick="this.clearSelection()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.attachStyles();
    }

    attachStyles() {
        const styles = `
            <style>
            .neo-advanced-site-search {
                position: relative;
                margin-bottom: 1.5rem;
            }

            .neo-search-input-container {
                display: flex;
                gap: 0.75rem;
                align-items: center;
                margin-bottom: 0.5rem;
            }

            .neo-search-input-wrapper {
                position: relative;
                flex: 1;
                display: flex;
                align-items: center;
            }

            .neo-search-input {
                width: 100%;
                padding: 0.875rem 1rem 0.875rem 2.75rem;
                border: 2px solid #e5e7eb;
                border-radius: 0.75rem;
                font-size: 0.875rem;
                font-weight: 500;
                background: #ffffff;
                transition: all 0.2s ease;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            .neo-search-input:focus {
                outline: none;
                border-color: #3b82f6;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
                transform: translateY(-1px);
            }

            .neo-search-icon {
                position: absolute;
                left: 0.875rem;
                color: #6b7280;
                pointer-events: none;
                z-index: 2;
            }

            .neo-loading-spinner {
                position: absolute;
                right: 0.875rem;
                display: none;
            }

            .neo-spinner {
                width: 16px;
                height: 16px;
                border: 2px solid #e5e7eb;
                border-top: 2px solid #3b82f6;
                border-radius: 50%;
                animation: neo-spin 0.8s linear infinite;
            }

            @keyframes neo-spin {
                to { transform: rotate(360deg); }
            }

            .neo-add-site-btn {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.875rem 1.25rem;
                font-size: 0.875rem;
                font-weight: 600;
                white-space: nowrap;
                border-radius: 0.75rem;
                transition: all 0.2s ease;
            }

            .neo-add-site-btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
            }

            .neo-search-results {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 0.75rem;
                box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
                z-index: 1000;
                max-height: 400px;
                overflow: hidden;
                display: none;
                animation: neo-slideDown 0.2s ease-out;
            }

            @keyframes neo-slideDown {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .neo-results-header {
                padding: 1rem;
                border-bottom: 1px solid #f3f4f6;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f9fafb;
            }

            .neo-results-count {
                font-size: 0.875rem;
                color: #6b7280;
                font-weight: 500;
            }

            .neo-filter-tabs {
                display: flex;
                gap: 0.5rem;
            }

            .neo-filter-tab {
                padding: 0.375rem 0.75rem;
                font-size: 0.8rem;
                font-weight: 500;
                border: 1px solid #e5e7eb;
                border-radius: 0.5rem;
                background: #ffffff;
                color: #6b7280;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .neo-filter-tab.active,
            .neo-filter-tab:hover {
                background: #3b82f6;
                color: #ffffff;
                border-color: #3b82f6;
            }

            .neo-results-list {
                max-height: 300px;
                overflow-y: auto;
                padding: 0.5rem 0;
            }

            .neo-site-result {
                display: flex;
                align-items: center;
                padding: 0.875rem 1rem;
                cursor: pointer;
                transition: all 0.2s ease;
                border-bottom: 1px solid #f3f4f6;
            }

            .neo-site-result:hover,
            .neo-site-result.active {
                background: #f8fafc;
                transform: translateX(4px);
            }

            .neo-site-result:last-child {
                border-bottom: none;
            }

            .neo-site-avatar {
                width: 40px;
                height: 40px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 0.75rem;
                display: flex;
                align-items: center;
                justify-content: center;
                margin-right: 0.875rem;
                color: #ffffff;
                flex-shrink: 0;
            }

            .neo-site-info {
                flex: 1;
                min-width: 0;
            }

            .neo-site-name {
                font-weight: 600;
                color: #111827;
                margin: 0 0 0.25rem 0;
                font-size: 0.9rem;
            }

            .neo-site-details {
                font-size: 0.8rem;
                color: #6b7280;
                margin: 0;
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .neo-site-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.125rem 0.5rem;
                background: #dbeafe;
                color: #1e40af;
                border-radius: 0.375rem;
                font-size: 0.7rem;
                font-weight: 500;
            }

            .neo-no-results {
                text-align: center;
                padding: 2rem 1rem;
                color: #6b7280;
            }

            .neo-no-results-icon {
                margin: 0 auto 1rem;
                color: #d1d5db;
            }

            .neo-no-results h3 {
                font-size: 1.1rem;
                font-weight: 600;
                color: #374151;
                margin: 0 0 0.5rem 0;
            }

            .neo-no-results p {
                font-size: 0.875rem;
                margin: 0 0 1.5rem 0;
                line-height: 1.5;
            }

            .neo-selected-site {
                margin-top: 0.75rem;
                background: #f0f9ff;
                border: 1px solid #bae6fd;
                border-radius: 0.75rem;
                padding: 1rem;
                animation: neo-fadeIn 0.3s ease-out;
            }

            @keyframes neo-fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }

            .neo-selected-site-content {
                display: flex;
                align-items: center;
            }

            .neo-selected-site .neo-site-avatar {
                background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
            }

            .neo-remove-selection {
                padding: 0.5rem;
                border: none;
                background: none;
                color: #6b7280;
                cursor: pointer;
                border-radius: 0.5rem;
                transition: all 0.2s ease;
                margin-left: auto;
            }

            .neo-remove-selection:hover {
                background: #fee2e2;
                color: #dc2626;
            }

            /* Responsive Design */
            @media (max-width: 768px) {
                .neo-search-input-container {
                    flex-direction: column;
                    gap: 0.5rem;
                }

                .neo-add-site-btn {
                    width: 100%;
                    justify-content: center;
                }

                .neo-results-header {
                    flex-direction: column;
                    gap: 0.75rem;
                    align-items: stretch;
                }

                .neo-filter-tabs {
                    justify-content: center;
                }
            }

            /* Loading States */
            .neo-search-input.loading {
                background-image: linear-gradient(90deg, #f3f4f6 25%, #e5e7eb 50%, #f3f4f6 75%);
                background-size: 200% 100%;
                animation: neo-shimmer 1.5s infinite;
            }

            @keyframes neo-shimmer {
                0% { background-position: -200% 0; }
                100% { background-position: 200% 0; }
            }
            </style>
        `;

        if (!document.querySelector('#neo-site-search-styles')) {
            const styleElement = document.createElement('div');
            styleElement.id = 'neo-site-search-styles';
            styleElement.innerHTML = styles;
            document.head.appendChild(styleElement);
        }
    }

    bindEvents() {
        const searchInput = document.getElementById('site-search-input');
        const addSiteBtn = document.getElementById('add-new-site-btn');
        const filterTabs = document.querySelectorAll('.neo-filter-tab');

        // Search input events
        searchInput?.addEventListener('input', (e) => this.handleSearch(e.target.value));
        searchInput?.addEventListener('focus', () => this.showResults());
        searchInput?.addEventListener('keydown', (e) => this.handleKeyNavigation(e));

        // Add site button
        addSiteBtn?.addEventListener('click', () => this.showAddSiteModal());

        // Filter tabs
        filterTabs.forEach((tab) => {
            tab.addEventListener('click', (e) => this.handleFilterChange(e.target.dataset.filter));
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.neo-advanced-site-search')) {
                this.hideResults();
            }
        });
    }

    handleSearch(query) {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        this.currentQuery = query.trim();

        if (this.currentQuery.length < this.minQueryLength) {
            this.hideResults();
            return;
        }

        this.showLoading();

        this.searchTimeout = setTimeout(() => {
            this.performSearch(this.currentQuery);
        }, this.debounceDelay);
    }

    async performSearch(query) {
        try {
            this.isLoading = true;

            // ✅ Loading Manager kullan
            if (window.LoadingManager) {
                window.LoadingManager.set('site-search', true, document.getElementById('site-search-loading'));
            }

            // ✅ API Helper kullan (merkezi yönetim)
            const endpoint = `${this.apiEndpoint}?q=${encodeURIComponent(query)}&limit=${this.maxResults}`;
            let data;
            if (window.APIHelper) {
                const result = await window.APIHelper.request(endpoint, {
                    method: 'GET',
                }, {
                    showLoading: false, // Loading Manager kullanıyoruz
                    loadingKey: 'site-search',
                });
                data = result.data || result;
            } else {
                // Fallback: Eski kod
                const response = await fetch(endpoint, {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                data = await response.json();
            }

            if (data.success) {
                this.displayResults(data.data, data.count);
            } else {
                this.showError(data.message || 'Arama sırasında hata oluştu');
            }
        } catch (error) {
            console.error('Site search error:', error);
            this.showError('Bağlantı hatası oluştu');
        } finally {
            this.isLoading = false;
            this.hideLoading();
            // ✅ Loading Manager kullan
            if (window.LoadingManager) {
                window.LoadingManager.set('site-search', false, document.getElementById('site-search-loading'));
            }
        }
    }

    displayResults(sites, count) {
        const resultsList = document.getElementById('results-list');
        const resultsCount = document.getElementById('results-count');
        const noResults = document.getElementById('no-results');

        // Update count
        resultsCount.textContent = `${count} sonuç`;

        if (sites.length === 0) {
            resultsList.style.display = 'none';
            noResults.style.display = 'block';
        } else {
            resultsList.style.display = 'block';
            noResults.style.display = 'none';

            resultsList.innerHTML = sites
                .map(
                    (site, index) => `
                <div class="neo-site-result" data-site-id="${site.id}" data-index="${index}">
                    <div class="neo-site-avatar">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-2" stroke="currentColor" stroke-width="2"/>
                        </svg>
                    </div>
                    <div class="neo-site-info">
                        <h4 class="neo-site-name">${this.highlightMatch(
                            site.ad,
                            this.currentQuery
                        )}</h4>
                        <p class="neo-site-details">
                            <span class="neo-site-badge">${site.tip || 'Site'}</span>
                            ${site.adres ? `• ${site.adres}` : ''}
                            ${site.daire_sayisi ? `• ${site.daire_sayisi} daire` : ''}
                        </p>
                    </div>
                </div>
            `
                )
                .join('');

            // Bind click events to results
            resultsList.querySelectorAll('.neo-site-result').forEach((result) => {
                result.addEventListener('click', () =>
                    this.selectSite(JSON.parse(result.dataset.siteData || '{}'))
                );
            });
        }

        this.showResults();
    }

    highlightMatch(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(
            regex,
            '<mark style="background: #fef08a; padding: 0.125rem 0.25rem; border-radius: 0.25rem;">$1</mark>'
        );
    }

    selectSite(site) {
        this.selectedSite = site;
        this.displaySelectedSite();
        this.hideResults();
        this.clearSearchInput();

        // Trigger selection event
        this.triggerSiteSelected(site);
    }

    displaySelectedSite() {
        const selectedSiteDiv = document.getElementById('selected-site');
        const siteInfo = selectedSiteDiv.querySelector('.neo-site-info');

        if (this.selectedSite && siteInfo) {
            siteInfo.querySelector('.neo-site-name').textContent = this.selectedSite.ad;
            siteInfo.querySelector('.neo-site-details').textContent = [
                this.selectedSite.tip,
                this.selectedSite.adres,
                this.selectedSite.daire_sayisi ? `${this.selectedSite.daire_sayisi} daire` : '',
            ]
                .filter(Boolean)
                .join(' • ');

            selectedSiteDiv.style.display = 'block';
        }
    }

    clearSelection() {
        this.selectedSite = null;
        document.getElementById('selected-site').style.display = 'none';
        this.triggerSiteCleared();
    }

    showResults() {
        document.getElementById('site-search-results').style.display = 'block';
    }

    hideResults() {
        document.getElementById('site-search-results').style.display = 'none';
    }

    showLoading() {
        document.getElementById('site-search-loading').style.display = 'block';
        const input = document.getElementById('site-search-input');
        input?.classList.add('loading');
    }

    hideLoading() {
        document.getElementById('site-search-loading').style.display = 'none';
        const input = document.getElementById('site-search-input');
        input?.classList.remove('loading');
    }

    clearSearchInput() {
        document.getElementById('site-search-input').value = '';
    }

    showAddSiteModal() {
        // This would trigger the existing site modal
        console.log('Opening add site modal...');
        // Integration with existing modal system
        if (window.openSiteModal) {
            window.openSiteModal();
        }
    }

    triggerSiteSelected(site) {
        // Dispatch custom event for integration
        const event = new CustomEvent('siteSelected', {
            detail: { site },
        });
        document.dispatchEvent(event);

        // Update hidden input if exists
        const hiddenInput = document.querySelector('input[name="site_id"]');
        if (hiddenInput) {
            hiddenInput.value = site.id;
        }
    }

    triggerSiteCleared() {
        const event = new CustomEvent('siteCleared');
        document.dispatchEvent(event);

        const hiddenInput = document.querySelector('input[name="site_id"]');
        if (hiddenInput) {
            hiddenInput.value = '';
        }
    }

    handleKeyNavigation(e) {
        const results = document.querySelectorAll('.neo-site-result');
        const activeResult = document.querySelector('.neo-site-result.active');
        let activeIndex = activeResult ? parseInt(activeResult.dataset.index) : -1;

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, results.length - 1);
                this.updateActiveResult(results, activeIndex);
                break;
            case 'ArrowUp':
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                this.updateActiveResult(results, activeIndex);
                break;
            case 'Enter':
                e.preventDefault();
                if (activeResult) {
                    activeResult.click();
                }
                break;
            case 'Escape':
                e.preventDefault();
                this.hideResults();
                break;
        }
    }

    updateActiveResult(results, activeIndex) {
        results.forEach((result, index) => {
            result.classList.toggle('active', index === activeIndex);
        });
    }

    handleFilterChange(filter) {
        // Update active tab
        document.querySelectorAll('.neo-filter-tab').forEach((tab) => {
            tab.classList.toggle('active', tab.dataset.filter === filter);
        });

        // Re-perform search with filter
        if (this.currentQuery.length >= this.minQueryLength) {
            this.performSearch(this.currentQuery, filter);
        }
    }

    initializeAnimations() {
        // Initialize any additional animations or interactions
        const input = document.getElementById('site-search-input');

        // Smooth focus animations
        input?.addEventListener('focus', () => {
            input.parentElement.style.transform = 'scale(1.02)';
        });

        input?.addEventListener('blur', () => {
            input.parentElement.style.transform = 'scale(1)';
        });
    }

    // Public methods for external integration
    getValue() {
        return this.selectedSite;
    }

    setValue(site) {
        this.selectedSite = site;
        this.displaySelectedSite();
    }

    clear() {
        this.clearSelection();
        this.clearSearchInput();
    }

    destroy() {
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }

        // Remove event listeners and clean up
        const container = document.querySelector(this.container);
        if (container) {
            container.innerHTML = '';
        }
    }
}

// Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Initialize site search if container exists
    if (document.querySelector('#site-search-container')) {
        window.advancedSiteSearch = new AdvancedSiteSearch({
            container: '#site-search-container',
            apiEndpoint: '/api/admin/sites/search',
        });

        // Listen for site selection events
        document.addEventListener('siteSelected', function (e) {
            console.log('Site selected:', e.detail.site);
            // Additional integration logic here
        });
    }
});

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdvancedSiteSearch;
}
