/**
 * Step 4: Location Module
 *
 * @module wizard/steps/step4-location
 * @version 3.0.0
 * @since 2026-01-28
 * @context7 Modüler Wizard Mimarisi - SEALED
 * @context7-compliance lat/lng canonical (NOT enlem/boylam)
 *
 * Handles location selection, map interaction, and coordinate management.
 */

import { WizardEventBus, WizardEventTypes } from '../core/wizard-events.js';
import { WizardState } from '../core/wizard-state.js';

/**
 * Configuration
 */
const CONFIG = {
    defaultCenter: { lat: 37.0344, lng: 27.4305 }, // Bodrum
    defaultZoom: 11,
    markerZoom: 16,
    tileLayer: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    attribution: '© OpenStreetMap contributors',
    coordinatesApiBase: '/api/v1/location',
};

/**
 * LocationManager - Step 4 Controller
 */
class LocationManagerClass {
    constructor() {
        /** @private */
        this._map = null;

        /** @private */
        this._marker = null;

        /** @private */
        this._initialized = false;

        /** @private */
        this._currentLocation = {
            ilId: null,
            ilceId: null,
            mahalleId: null,
            lat: null,
            lng: null,
        };
    }

    /**
     * Initialize location system
     */
    init() {
        if (this._initialized) return;

        // Setup cascade dropdowns
        this._setupCascadeDropdowns();

        // Initialize map when visible
        this._setupMapObserver();

        this._initialized = true;
        console.log('[LocationManager] Initialized');
    }

    /**
     * Setup IntersectionObserver for lazy map loading
     * @private
     */
    _setupMapObserver() {
        const mapContainer = document.getElementById('wizard-map');
        if (!mapContainer) return;

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.initMap();
                            observer.unobserve(entry.target);
                        }
                    });
                },
                { threshold: 0.1 }
            );
            observer.observe(mapContainer);
        } else {
            // Fallback: init immediately
            this.initMap();
        }
    }

    /**
     * Initialize Leaflet map
     */
    initMap() {
        if (this._map) return;

        const mapContainer = document.getElementById('wizard-map');
        if (!mapContainer) return;

        // Check if Leaflet is available
        if (typeof L === 'undefined') {
            console.warn('[LocationManager] Leaflet not loaded');
            return;
        }

        const center = this._getInitialCenter();

        this._map = L.map('wizard-map', {
            center: [center.lat, center.lng],
            zoom: CONFIG.defaultZoom,
            scrollWheelZoom: true,
            zoomControl: true,
        });

        L.tileLayer(CONFIG.tileLayer, {
            attribution: CONFIG.attribution,
            maxZoom: 19,
        }).addTo(this._map);

        // Add marker
        this._marker = L.marker([center.lat, center.lng], {
            draggable: true,
        }).addTo(this._map);

        // Marker drag event
        this._marker.on('dragend', (e) => {
            const { lat, lng } = e.target.getLatLng();
            this._updateCoordinates(lat, lng);
        });

        // Map click event
        this._map.on('click', (e) => {
            const { lat, lng } = e.latlng;
            this._marker.setLatLng([lat, lng]);
            this._updateCoordinates(lat, lng);
        });

        // Store references globally for backward compatibility
        window.wizardMap = this._map;
        window.wizardMarker = this._marker;

        console.log('[LocationManager] Map initialized');
    }

    /**
     * Get initial center from state or default
     * @private
     */
    _getInitialCenter() {
        const state = WizardState.state.location;
        if (state.lat && state.lng) {
            return { lat: state.lat, lng: state.lng };
        }
        return CONFIG.defaultCenter;
    }

    /**
     * Update coordinates in state and UI
     * @private
     */
    _updateCoordinates(lat, lng) {
        // Validate
        if (!this._isValidLatLng(lat, lng)) {
            console.warn('[LocationManager] Invalid coordinates:', { lat, lng });
            return;
        }

        this._currentLocation.lat = lat;
        this._currentLocation.lng = lng;

        // Update state
        WizardState.batch({
            'location.lat': lat,
            'location.lng': lng,
        });

        // Update form inputs - Context7: lat/lng canonical
        const latInput =
            document.querySelector('[name="lat"]') || document.querySelector('[name="enlem"]');
        const lngInput =
            document.querySelector('[name="lng"]') || document.querySelector('[name="boylam"]');

        if (latInput) latInput.value = lat;
        if (lngInput) lngInput.value = lng;

        // Update display
        const latDisplay = document.getElementById('lat-display');
        const lngDisplay = document.getElementById('lng-display');

        if (latDisplay) latDisplay.textContent = lat.toFixed(6);
        if (lngDisplay) lngDisplay.textContent = lng.toFixed(6);

        // Emit event
        WizardEventBus.emit(WizardEventTypes.COORDINATES_UPDATED, { lat, lng });
        WizardEventBus.emit(WizardEventTypes.MAP_MARKER_MOVED, { lat, lng });
    }

    /**
     * Validate lat/lng
     * @private
     */
    _isValidLatLng(lat, lng) {
        return (
            Number.isFinite(lat) &&
            Number.isFinite(lng) &&
            Math.abs(lat) <= 90 &&
            Math.abs(lng) <= 180
        );
    }

    /**
     * Setup cascade dropdowns (İl -> İlçe -> Mahalle)
     * @private
     */
    _setupCascadeDropdowns() {
        const ilSelect = document.getElementById('il_id');
        const ilceSelect = document.getElementById('ilce_id');
        const mahalleSelect = document.getElementById('mahalle_id');

        if (ilSelect) {
            ilSelect.addEventListener('change', () => {
                const ilId = ilSelect.value;
                if (ilId) {
                    this._loadIlceler(ilId);
                    this._flyToIl(ilId);
                } else {
                    this._clearSelect(ilceSelect, 'Önce İl Seçin');
                    this._clearSelect(mahalleSelect, 'Önce İlçe Seçin');
                }
            });
        }

        if (ilceSelect) {
            ilceSelect.addEventListener('change', () => {
                const ilceId = ilceSelect.value;
                if (ilceId) {
                    this._loadMahalleler(ilceId);
                    this._flyToIlce(ilceId);
                } else {
                    this._clearSelect(mahalleSelect, 'Önce İlçe Seçin');
                }
            });
        }

        if (mahalleSelect) {
            mahalleSelect.addEventListener('change', () => {
                const mahalleId = mahalleSelect.value;
                if (mahalleId) {
                    this._flyToMahalle(mahalleId);
                }
            });
        }
    }

    /**
     * Load ilçeler for il
     * @private
     */
    async _loadIlceler(ilId) {
        const ilceSelect = document.getElementById('ilce_id');
        const mahalleSelect = document.getElementById('mahalle_id');

        if (!ilceSelect) return;

        ilceSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        ilceSelect.disabled = true;
        this._clearSelect(mahalleSelect, 'Önce İlçe Seçin');

        try {
            const response = await fetch(`${CONFIG.coordinatesApiBase}/districts/${ilId}`);
            const data = await response.json();

            ilceSelect.innerHTML = '<option value="">İlçe Seçin</option>';

            const districts = data.data || data.districts || data;
            if (Array.isArray(districts)) {
                districts.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name || item.ad;
                    if (item.lat && item.lng) {
                        option.dataset.lat = item.lat;
                        option.dataset.lng = item.lng;
                    }
                    ilceSelect.appendChild(option);
                });
            }

            ilceSelect.disabled = false;
        } catch (error) {
            console.error('[LocationManager] Load ilceler error:', error);
            ilceSelect.innerHTML = '<option value="">Hata!</option>';
            ilceSelect.disabled = false;
        }
    }

    /**
     * Load mahalleler for ilce
     * @private
     */
    async _loadMahalleler(ilceId) {
        const mahalleSelect = document.getElementById('mahalle_id');
        if (!mahalleSelect) return;

        mahalleSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        mahalleSelect.disabled = true;

        try {
            const response = await fetch(`${CONFIG.coordinatesApiBase}/neighborhoods/${ilceId}`);
            const data = await response.json();

            mahalleSelect.innerHTML = '<option value="">Mahalle Seçin</option>';

            const neighborhoods = data.data || data.neighborhoods || data;
            if (Array.isArray(neighborhoods)) {
                neighborhoods.forEach((item) => {
                    const option = document.createElement('option');
                    option.value = item.id;
                    option.textContent = item.name || item.ad;
                    // Context7: lat/lng canonical
                    const lat = item.lat ?? item.enlem;
                    const lng = item.lng ?? item.boylam;
                    if (lat && lng) {
                        option.dataset.lat = lat;
                        option.dataset.lng = lng;
                    }
                    mahalleSelect.appendChild(option);
                });
            }

            mahalleSelect.disabled = false;
        } catch (error) {
            console.error('[LocationManager] Load mahalleler error:', error);
            mahalleSelect.innerHTML = '<option value="">Hata!</option>';
            mahalleSelect.disabled = false;
        }
    }

    /**
     * Clear select and set placeholder
     * @private
     */
    _clearSelect(select, placeholder) {
        if (!select) return;
        select.innerHTML = `<option value="">${placeholder}</option>`;
        select.disabled = true;
    }

    /**
     * Fly to il coordinates
     * @private
     */
    async _flyToIl(ilId) {
        // Known il coordinates
        const ilCoordinates = {
            48: { lat: 37.0344, lng: 27.4305 }, // Muğla
            6: { lat: 39.9334, lng: 32.8597 }, // Ankara
            34: { lat: 41.0082, lng: 28.9784 }, // İstanbul
            35: { lat: 38.4192, lng: 27.1287 }, // İzmir
        };

        const coords = ilCoordinates[ilId];
        if (coords) {
            this.flyTo(coords.lat, coords.lng, CONFIG.defaultZoom);
        }
    }

    /**
     * Fly to ilce coordinates
     * @private
     */
    async _flyToIlce(ilceId) {
        try {
            const response = await fetch(
                `${CONFIG.coordinatesApiBase}/district/${ilceId}/coordinates`
            );
            const data = await response.json();

            // Context7: lat/lng priority
            const lat = data.lat ?? data.data?.lat ?? data.enlem ?? data.data?.enlem;
            const lng = data.lng ?? data.data?.lng ?? data.boylam ?? data.data?.boylam;

            if (lat && lng) {
                this.flyTo(parseFloat(lat), parseFloat(lng), 13);
            }
        } catch (error) {
            console.error('[LocationManager] Fly to ilce error:', error);
        }
    }

    /**
     * Fly to mahalle coordinates
     * @private
     */
    async _flyToMahalle(mahalleId) {
        // First check option data attributes
        const mahalleSelect = document.getElementById('mahalle_id');
        const selectedOption = mahalleSelect?.options[mahalleSelect.selectedIndex];

        if (selectedOption?.dataset.lat && selectedOption?.dataset.lng) {
            const lat = parseFloat(selectedOption.dataset.lat);
            const lng = parseFloat(selectedOption.dataset.lng);
            this.flyTo(lat, lng, CONFIG.markerZoom);
            this._updateCoordinates(lat, lng);
            return;
        }

        // Fallback to API
        try {
            const response = await fetch(
                `${CONFIG.coordinatesApiBase}/neighborhood/${mahalleId}/coordinates`
            );
            const data = await response.json();

            // Context7: lat/lng priority
            const lat = data.lat ?? data.data?.lat ?? data.enlem ?? data.data?.enlem;
            const lng = data.lng ?? data.data?.lng ?? data.boylam ?? data.data?.boylam;

            if (lat && lng) {
                this.flyTo(parseFloat(lat), parseFloat(lng), CONFIG.markerZoom);
                this._updateCoordinates(parseFloat(lat), parseFloat(lng));
            }
        } catch (error) {
            console.error('[LocationManager] Fly to mahalle error:', error);
        }
    }

    /**
     * Fly map to coordinates
     * @param {number} lat
     * @param {number} lng
     * @param {number} zoom
     */
    flyTo(lat, lng, zoom = CONFIG.markerZoom) {
        if (!this._map) return;

        if (!this._isValidLatLng(lat, lng)) {
            console.warn('[LocationManager] Invalid flyTo coordinates:', { lat, lng });
            // Fallback to default
            lat = CONFIG.defaultCenter.lat;
            lng = CONFIG.defaultCenter.lng;
            zoom = CONFIG.defaultZoom;
        }

        this._map.flyTo([lat, lng], zoom, { duration: 1.5 });

        if (this._marker) {
            this._marker.setLatLng([lat, lng]);
        }

        this._updateCoordinates(lat, lng);
    }

    /**
     * Set location from coordinates
     * @param {number} lat
     * @param {number} lng
     */
    setLocation(lat, lng) {
        this._updateCoordinates(lat, lng);

        if (this._map) {
            this._map.setView([lat, lng], CONFIG.markerZoom);
            if (this._marker) {
                this._marker.setLatLng([lat, lng]);
            }
        }
    }

    /**
     * Get current location
     * @returns {Object}
     */
    getLocation() {
        return { ...this._currentLocation };
    }

    /**
     * Get coordinates
     * @returns {{ lat: number, lng: number } | null}
     */
    getCoordinates() {
        if (this._currentLocation.lat && this._currentLocation.lng) {
            return {
                lat: this._currentLocation.lat,
                lng: this._currentLocation.lng,
            };
        }
        return null;
    }

    /**
     * Refresh map (call after container becomes visible)
     */
    refreshMap() {
        if (this._map) {
            setTimeout(() => {
                this._map.invalidateSize();
            }, 100);
        }
    }

    /**
     * Destroy map
     */
    destroy() {
        if (this._map) {
            this._map.remove();
            this._map = null;
            this._marker = null;
        }
        this._initialized = false;
    }
}

// Singleton instance
export const LocationManager = new LocationManagerClass();

// Global export
if (typeof window !== 'undefined') {
    window.YalihanWizard = window.YalihanWizard || {};
    window.YalihanWizard.location = LocationManager;

    // Backward compatibility
    window.initWizardMap = () => LocationManager.initMap();
    window.updateMapFromLocation = async () => {
        const mahalleId = document.getElementById('mahalle_id')?.value;
        if (mahalleId) {
            await LocationManager._flyToMahalle(mahalleId);
        }
    };
    window.updateMapFromIl = (ilId) => LocationManager._flyToIl(ilId);
    window.updateMapFromIlce = (ilceId) => LocationManager._flyToIlce(ilceId);
}

export default LocationManager;
