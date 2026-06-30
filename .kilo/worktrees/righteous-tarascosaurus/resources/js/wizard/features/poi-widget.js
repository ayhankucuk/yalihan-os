/**
 * POI Widget - Feature Module
 *
 * @module wizard/features/poi-widget
 * @version 3.0.0
 * @since 2026-01-28
 * @context7 Modüler Wizard Mimarisi - SEALED
 *
 * Displays nearby Points of Interest (POI) based on selected location.
 */

import { WizardEventBus, WizardEventTypes } from '../core/wizard-events.js';
import { WizardState } from '../core/wizard-state.js';

/**
 * Configuration
 */
const CONFIG = {
    endpoint: '/api/v1/poi/nearby',
    defaultRadius: 2000, // meters
    maxResults: 20,
    categories: [
        { id: 'beach', name: 'Plaj', icon: '🏖️' },
        { id: 'restaurant', name: 'Restoran', icon: '🍽️' },
        { id: 'market', name: 'Market', icon: '🛒' },
        { id: 'pharmacy', name: 'Eczane', icon: '💊' },
        { id: 'hospital', name: 'Hastane', icon: '🏥' },
        { id: 'school', name: 'Okul', icon: '🏫' },
        { id: 'bank', name: 'Banka', icon: '🏦' },
        { id: 'gas_station', name: 'Benzinlik', icon: '⛽' },
        { id: 'mosque', name: 'Cami', icon: '🕌' },
        { id: 'park', name: 'Park', icon: '🌳' },
        { id: 'shopping', name: 'AVM', icon: '🛍️' },
        { id: 'airport', name: 'Havalimanı', icon: '✈️' },
    ],
};

/**
 * POIWidget - Feature Controller
 */
class POIWidgetClass {
    constructor() {
        /** @private */
        this._pois = [];

        /** @private */
        this._selectedCategories = [];

        /** @private */
        this._radius = CONFIG.defaultRadius;

        /** @private */
        this._loading = false;

        /** @private */
        this._initialized = false;

        /** @private */
        this._lastCoordinates = null;
    }

    /**
     * Initialize POI widget
     */
    init() {
        if (this._initialized) return;

        this._setupEventListeners();

        this._initialized = true;
        console.log('[POIWidget] Initialized');
    }

    /**
     * Setup event listeners
     * @private
     */
    _setupEventListeners() {
        // Listen for coordinate updates
        WizardEventBus.on(WizardEventTypes.COORDINATES_UPDATED, (data) => {
            this.loadPOIs(data.lat, data.lng);
        });

        WizardEventBus.on(WizardEventTypes.MAP_MARKER_MOVED, (data) => {
            this.loadPOIs(data.lat, data.lng);
        });

        // Also listen for DOM events (backward compatibility)
        document.addEventListener('wizard-map-marker-moved', (e) => {
            const { lat, lng } = e.detail;
            this.loadPOIs(lat, lng);
        });
    }

    /**
     * Load POIs for coordinates
     * @param {number} lat
     * @param {number} lng
     */
    async loadPOIs(lat, lng) {
        if (!lat || !lng) return;

        // Debounce - don't reload if same location
        const coordKey = `${lat.toFixed(4)},${lng.toFixed(4)}`;
        if (this._lastCoordinates === coordKey) return;
        this._lastCoordinates = coordKey;

        this._loading = true;
        this._updateUI();

        try {
            const params = new URLSearchParams({
                lat: lat.toString(),
                lng: lng.toString(),
                radius: this._radius.toString(),
                limit: CONFIG.maxResults.toString(),
            });

            const response = await fetch(`${CONFIG.endpoint}?${params}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                throw new Error('POI fetch failed');
            }

            const result = await response.json();

            if (result.success && Array.isArray(result.data)) {
                this._pois = result.data;
            } else if (Array.isArray(result.pois)) {
                this._pois = result.pois;
            } else {
                this._pois = [];
            }

            // Update state
            WizardState.set('pois', this._pois);
        } catch (error) {
            console.error('[POIWidget] Load error:', error);
            this._pois = [];
        } finally {
            this._loading = false;
            this._updateUI();
        }
    }

    /**
     * Filter POIs by category
     * @param {string} category
     */
    toggleCategory(category) {
        const index = this._selectedCategories.indexOf(category);
        if (index === -1) {
            this._selectedCategories.push(category);
        } else {
            this._selectedCategories.splice(index, 1);
        }
        this._updateUI();
    }

    /**
     * Set radius
     * @param {number} radius - in meters
     */
    setRadius(radius) {
        this._radius = radius;
        // Reload if we have coordinates
        const location = WizardState.get('location');
        if (location.lat && location.lng) {
            this._lastCoordinates = null; // Force reload
            this.loadPOIs(location.lat, location.lng);
        }
    }

    /**
     * Get filtered POIs
     * @returns {Array}
     */
    getFilteredPOIs() {
        if (this._selectedCategories.length === 0) {
            return this._pois;
        }
        return this._pois.filter((poi) =>
            this._selectedCategories.includes(poi.type || poi.category)
        );
    }

    /**
     * Get POIs by category
     * @param {string} category
     * @returns {Array}
     */
    getPOIsByCategory(category) {
        return this._pois.filter((poi) => (poi.type || poi.category) === category);
    }

    /**
     * Get category count
     * @param {string} category
     * @returns {number}
     */
    getCategoryCount(category) {
        return this.getPOIsByCategory(category).length;
    }

    /**
     * Format distance
     * @param {number} distance - in meters
     * @returns {string}
     */
    formatDistance(distance) {
        if (!distance) return '-';
        if (distance < 1000) {
            return `${Math.round(distance)} m`;
        }
        return `${(distance / 1000).toFixed(1)} km`;
    }

    /**
     * Get marketing badge for POI
     * @param {Object} poi
     * @returns {string}
     */
    getMarketingBadge(poi) {
        const distance = poi.distance || 0;
        const type = poi.type || poi.category;

        if (type === 'beach' && distance < 500) return '🏖️ Sahile Yürüme Mesafesinde';
        if (type === 'beach' && distance < 1000) return '🏖️ Sahile Yakın';
        if (type === 'airport' && distance < 10000) return '✈️ Havalimanına Yakın';
        if (type === 'shopping' && distance < 1000) return '🛍️ AVM Yakınında';
        if (type === 'restaurant' && distance < 300) return '🍽️ Restoranlara Yürüme Mesafesi';

        return '';
    }

    /**
     * Get all POIs
     * @returns {Array}
     */
    getAllPOIs() {
        return [...this._pois];
    }

    /**
     * Get POI data for form submission
     * @returns {Array}
     */
    getPOIJsonData() {
        return this._pois.map((poi) => ({
            name: poi.name,
            type: poi.type || poi.category,
            distance: poi.distance,
            lat: poi.lat,
            lng: poi.lng,
        }));
    }

    /**
     * Get POI metadata for structured data
     * @returns {Object}
     */
    getPOIMetadata() {
        const categories = {};
        this._pois.forEach((poi) => {
            const type = poi.type || poi.category;
            if (!categories[type]) {
                categories[type] = { count: 0, nearest: null };
            }
            categories[type].count++;
            if (!categories[type].nearest || poi.distance < categories[type].nearest.distance) {
                categories[type].nearest = poi;
            }
        });
        return categories;
    }

    /**
     * Is loading
     * @returns {boolean}
     */
    isLoading() {
        return this._loading;
    }

    /**
     * Get available categories
     * @returns {Array}
     */
    getCategories() {
        return CONFIG.categories;
    }

    /**
     * Update UI
     * @private
     */
    _updateUI() {
        const container = document.getElementById('poi-widget');
        if (!container) return;

        // Update loading state
        if (this._loading) {
            container.classList.add('opacity-50');
        } else {
            container.classList.remove('opacity-50');
        }

        // Update POI list
        const listContainer = container.querySelector('.poi-list');
        if (listContainer) {
            const filteredPOIs = this.getFilteredPOIs();

            if (filteredPOIs.length === 0) {
                listContainer.innerHTML = `
                    <p class="text-sm text-gray-500 text-center py-4 dark:text-slate-500">
                        ${this._loading ? 'Yükleniyor...' : 'Bu bölgede POI bulunamadı'}
                    </p>
                `;
            } else {
                listContainer.innerHTML = filteredPOIs
                    .slice(0, 10)
                    .map((poi) => {
                        const categoryInfo = CONFIG.categories.find(
                            (c) => c.id === (poi.type || poi.category)
                        );
                        const icon = categoryInfo?.icon || '📍';
                        const badge = this.getMarketingBadge(poi);

                        return `
                        <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-colors dark:hover:bg-slate-800">
                            <span class="text-xl">${icon}</span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate dark:text-slate-100">
                                    ${poi.name}
                                </p>
                                ${badge ? `<p class="text-xs text-blue-500">${badge}</p>` : ''}
                            </div>
                            <span class="text-xs text-gray-500 whitespace-nowrap dark:text-slate-500">
                                ${this.formatDistance(poi.distance)}
                            </span>
                        </div>
                    `;
                    })
                    .join('');
            }
        }

        // Update category filters
        const filtersContainer = container.querySelector('.poi-filters');
        if (filtersContainer) {
            filtersContainer.innerHTML = CONFIG.categories
                .map((cat) => {
                    const count = this.getCategoryCount(cat.id);
                    const isSelected = this._selectedCategories.includes(cat.id);

                    return `
                    <button type="button"
                        onclick="YalihanWizard.poi.toggleCategory('${cat.id}')"
                        class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded-full transition-colors
                            ${
                                isSelected
                                    ? 'bg-blue-500 text-white'
                                    : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600 dark:text-slate-300'
                            }
                            ${count === 0 ? 'opacity-50' : ''}">
                        <span>${cat.icon}</span>
                        <span>${cat.name}</span>
                        ${count > 0 ? `<span class="ml-1">(${count})</span>` : ''}
                    </button>
                `;
                })
                .join('');
        }
    }
}

// Singleton instance
export const POIWidget = new POIWidgetClass();

// Global export
if (typeof window !== 'undefined') {
    window.YalihanWizard = window.YalihanWizard || {};
    window.YalihanWizard.poi = POIWidget;

    // Backward compatibility
    window.poiWidgetStep2 = function () {
        return {
            pois: POIWidget.getAllPOIs(),
            selectedCategories: [],
            radius: CONFIG.defaultRadius,
            loading: POIWidget.isLoading(),
            error: null,
            availableCategories: POIWidget.getCategories(),
            init() {
                POIWidget.init();
            },
            get filteredPOIs() {
                return POIWidget.getFilteredPOIs();
            },
            loadPOIs(lat, lng) {
                return POIWidget.loadPOIs(lat, lng);
            },
            toggleCategory(cat) {
                POIWidget.toggleCategory(cat);
            },
            getCategoryCount(cat) {
                return POIWidget.getCategoryCount(cat);
            },
        };
    };

    window.poiSelector = function () {
        return {
            pois: POIWidget.getAllPOIs(),
            selectedPois: [],
            loading: POIWidget.isLoading(),
            init() {
                POIWidget.init();
            },
            loadPOIs(lat, lng) {
                return POIWidget.loadPOIs(lat, lng);
            },
            getPoisByCategory(cat) {
                return POIWidget.getPOIsByCategory(cat);
            },
            formatDistance(d) {
                return POIWidget.formatDistance(d);
            },
            getMarketingBadge(poi) {
                return POIWidget.getMarketingBadge(poi);
            },
            getPoiJsonData() {
                return POIWidget.getPOIJsonData();
            },
            getPoiMetadata() {
                return POIWidget.getPOIMetadata();
            },
        };
    };
}

export default POIWidget;
