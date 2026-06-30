/**
 * 📍 Gelişmiş Lokasyon Yöneticisi (LocationManager)
 * Context7 Kural #75: Modern lokasyon ve harita sistemi
 *
 * Bu modül şunları sağlar:
 * - İl/İlçe/Mahalle cascade dropdown'ları
 * - Google Maps entegrasyonu
 * - Geocoding ve reverse geocoding
 * - Adres doğrulama
 * - Yakındaki konumları bulma
 */

class LocationManager {
    constructor(options = {}) {
        this.options = {
            // Container elements
            provinceSelect: options.provinceSelect || '#il_id',
            districtSelect: options.districtSelect || '#ilce_id',
            neighborhoodSelect: options.neighborhoodSelect || '#mahalle_id',
            mapContainer: options.mapContainer || '#location_map',

            // Google Maps
            googleMapsKey: options.googleMapsKey || null,
            defaultCenter: options.defaultCenter || {
                lat: 37.0662,
                lng: 27.4278,
            }, // Bodrum
            defaultZoom: options.defaultZoom || 10,

            // Callbacks
            onLocationChange: options.onLocationChange || null,
            onMapClick: options.onMapClick || null,
            onAddressFound: options.onAddressFound || null,

            // Features
            enableGeocoding: options.enableGeocoding !== false,
            enableReverseGeocoding: options.enableReverseGeocoding !== false,
            enableNearbySearch: options.enableNearbySearch !== false,
            enableAddressValidation: options.enableAddressValidation !== false,

            // Context7 settings
            cacheEnabled: options.cacheEnabled !== false,
            cacheTTL: options.cacheTTL || 300000, // 5 minutes
            apiBaseUrl: options.apiBaseUrl || '/api/location',

            ...options,
        };

        // State management
        this.state = {
            selectedProvince: null,
            selectedDistrict: null,
            selectedNeighborhood: null,
            currentLocation: null,
            isLoading: false,
            cache: new Map(),
        };

        // DOM elements
        this.elements = {};

        // Google Maps instances
        this.map = null;
        this.marker = null;
        this.geocoder = null;

        this.init();
    }

    /**
     * 🔧 Initialize LocationManager
     */
    async init() {
        try {
            this.bindElements();
            this.bindEvents();
            await this.loadInitialData();

            if (this.options.enableGeocoding && this.options.googleMapsKey) {
                await this.initializeGoogleMaps();
            }

            this.log('LocationManager initialized successfully (Context7 uyumlu)');
        } catch (error) {
            this.logError('LocationManager initialization failed', error);
        }
    }

    /**
     * 🎯 Bind DOM elements
     */
    bindElements() {
        this.elements = {
            provinceSelect: document.querySelector(this.options.provinceSelect),
            districtSelect: document.querySelector(this.options.districtSelect),
            neighborhoodSelect: document.querySelector(this.options.neighborhoodSelect),
            mapContainer: document.querySelector(this.options.mapContainer),
        };

        // Validate required elements
        if (!this.elements.provinceSelect) {
            throw new Error('Province select element not found');
        }
    }

    /**
     * 📡 Bind event listeners
     */
    bindEvents() {
        // Province change
        this.elements.provinceSelect?.addEventListener('change', (e) => {
            this.handleProvinceChange(e.target.value);
        });

        // District change
        this.elements.districtSelect?.addEventListener('change', (e) => {
            this.handleDistrictChange(e.target.value);
        });

        // Neighborhood change
        this.elements.neighborhoodSelect?.addEventListener('change', (e) => {
            this.handleNeighborhoodChange(e.target.value);
        });
    }

    /**
     * 📊 Load initial data (provinces)
     */
    async loadInitialData() {
        try {
            this.setLoading(true);
            const provinces = await this.fetchProvinces();
            this.populateSelect(this.elements.provinceSelect, provinces, 'İl Seçin...');
            this.log(`${provinces.length} province loaded`);
        } catch (error) {
            this.logError('Failed to load provinces', error);
            this.showError('İller yüklenemedi');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * 🌐 Initialize Google Maps
     */
    async initializeGoogleMaps() {
        if (!this.elements.mapContainer || !window.google) {
            this.log('Google Maps not available or container not found');
            return;
        }

        try {
            this.map = new google.maps.Map(this.elements.mapContainer, {
                center: this.options.defaultCenter,
                zoom: this.options.defaultZoom,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                mapTypeControl: true,
                streetViewControl: true,
                fullscreenControl: true,
            });

            this.geocoder = new google.maps.Geocoder();

            // Map click handler
            this.map.addListener('click', (event) => {
                this.handleMapClick(event.latLng);
            });

            this.log('Google Maps initialized successfully');
        } catch (error) {
            this.logError('Google Maps initialization failed', error);
        }
    }

    /**
     * 🏙️ Handle province change
     */
    async handleProvinceChange(provinceId) {
        if (!provinceId) {
            this.clearSelect(this.elements.districtSelect, 'Önce İl Seçin...');
            this.clearSelect(this.elements.neighborhoodSelect, 'Önce İlçe Seçin...');
            return;
        }

        try {
            this.setLoading(true);
            this.state.selectedProvince = provinceId;

            const districts = await this.fetchDistricts(provinceId);
            this.populateSelect(this.elements.districtSelect, districts, 'İlçe Seçin...');
            this.clearSelect(this.elements.neighborhoodSelect, 'Önce İlçe Seçin...');

            this.triggerLocationChange();
            this.log(`${districts.length} districts loaded for province ${provinceId}`);
        } catch (error) {
            this.logError('Failed to load districts', error);
            this.showError('İlçeler yüklenemedi');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * 🏘️ Handle district change
     */
    async handleDistrictChange(districtId) {
        if (!districtId) {
            this.clearSelect(this.elements.neighborhoodSelect, 'Önce İlçe Seçin...');
            return;
        }

        try {
            this.setLoading(true);
            this.state.selectedDistrict = districtId;

            const neighborhoods = await this.fetchNeighborhoods(districtId);
            this.populateSelect(
                this.elements.neighborhoodSelect,
                neighborhoods,
                'Mahalle Seçin...'
            );

            this.triggerLocationChange();
            this.log(`${neighborhoods.length} neighborhoods loaded for district ${districtId}`);
        } catch (error) {
            this.logError('Failed to load neighborhoods', error);
            this.showError('Mahalleler yüklenemedi');
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * 🏠 Handle neighborhood change
     */
    handleNeighborhoodChange(neighborhoodId) {
        this.state.selectedNeighborhood = neighborhoodId;
        this.triggerLocationChange();

        if (neighborhoodId) {
            this.log(`Neighborhood ${neighborhoodId} selected`);
        }
    }

    /**
     * 🗺️ Handle map click
     */
    async handleMapClick(latLng) {
        if (!this.options.enableReverseGeocoding) return;

        try {
            this.setLoading(true);

            // Update marker
            if (this.marker) {
                this.marker.setMap(null);
            }

            this.marker = new google.maps.Marker({
                position: latLng,
                map: this.map,
                draggable: true,
                title: 'Seçilen Konum',
            });

            // Reverse geocode
            const address = await this.reverseGeocode(latLng.lat(), latLng.lng());

            if (address && this.options.onAddressFound) {
                this.options.onAddressFound(address);
            }

            if (this.options.onMapClick) {
                this.options.onMapClick({
                    latitude: latLng.lat(),
                    longitude: latLng.lng(),
                    address: address,
                });
            }

            this.log('Map clicked, reverse geocoding completed');
        } catch (error) {
            this.logError('Map click handling failed', error);
        } finally {
            this.setLoading(false);
        }
    }

    /**
     * 📡 API Methods
     */

    async fetchProvinces() {
        const cacheKey = 'provinces';

        if (this.options.cacheEnabled && this.state.cache.has(cacheKey)) {
            return this.state.cache.get(cacheKey);
        }

        const response = await fetch(`${this.options.apiBaseUrl}/iller`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to fetch provinces');
        }

        const data = result.iller || result.data || [];

        if (this.options.cacheEnabled) {
            this.state.cache.set(cacheKey, data);
            setTimeout(() => this.state.cache.delete(cacheKey), this.options.cacheTTL);
        }

        return data;
    }

    async fetchDistricts(provinceId) {
        const cacheKey = `districts_${provinceId}`;

        if (this.options.cacheEnabled && this.state.cache.has(cacheKey)) {
            return this.state.cache.get(cacheKey);
        }

        const response = await fetch(`${this.options.apiBaseUrl}/districts/${provinceId}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to fetch districts');
        }

        const data = result.data || [];

        if (this.options.cacheEnabled) {
            this.state.cache.set(cacheKey, data);
            setTimeout(() => this.state.cache.delete(cacheKey), this.options.cacheTTL);
        }

        return data;
    }

    async fetchNeighborhoods(districtId) {
        const cacheKey = `neighborhoods_${districtId}`;

        if (this.options.cacheEnabled && this.state.cache.has(cacheKey)) {
            return this.state.cache.get(cacheKey);
        }

        const response = await fetch(`${this.options.apiBaseUrl}/neighborhoods/${districtId}`);
        const result = await response.json();

        if (!result.success) {
            throw new Error(result.message || 'Failed to fetch neighborhoods');
        }

        const data = result.data || [];

        if (this.options.cacheEnabled) {
            this.state.cache.set(cacheKey, data);
            setTimeout(() => this.state.cache.delete(cacheKey), this.options.cacheTTL);
        }

        return data;
    }

    /**
     * 🌐 Geocoding - Address to Coordinates
     */
    async geocode(address) {
        if (!this.options.enableGeocoding) {
            throw new Error('Geocoding is disabled');
        }

        try {
            const response = await fetch(`${this.options.apiBaseUrl}/geocode`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ address }),
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Geocoding failed');
            }

            return result.data;
        } catch (error) {
            this.logError('Geocoding failed', error);
            throw error;
        }
    }

    /**
     * 🗺️ Reverse Geocoding - Coordinates to Address
     */
    async reverseGeocode(latitude, longitude) {
        if (!this.options.enableReverseGeocoding) {
            throw new Error('Reverse geocoding is disabled');
        }

        try {
            const response = await fetch(`${this.options.apiBaseUrl}/reverse-geocode`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ latitude, longitude }),
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Reverse geocoding failed');
            }

            return result.data;
        } catch (error) {
            this.logError('Reverse geocoding failed', error);
            throw error;
        }
    }

    /**
     * 🔍 Find nearby locations
     */
    async findNearby(latitude, longitude, radius = 5) {
        if (!this.options.enableNearbySearch) {
            throw new Error('Nearby search is disabled');
        }

        try {
            const response = await fetch(
                `${this.options.apiBaseUrl}/nearby/${latitude}/${longitude}/${radius}`
            );
            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Nearby search failed');
            }

            return result.data;
        } catch (error) {
            this.logError('Nearby search failed', error);
            throw error;
        }
    }

    /**
     * ✅ Validate address
     */
    async validateAddress(addressData) {
        if (!this.options.enableAddressValidation) {
            throw new Error('Address validation is disabled');
        }

        try {
            const response = await fetch(`${this.options.apiBaseUrl}/validate-address`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify(addressData),
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'Address validation failed');
            }

            return result.data;
        } catch (error) {
            this.logError('Address validation failed', error);
            throw error;
        }
    }

    /**
     * 🛠️ Utility Methods
     */

    populateSelect(selectElement, data, placeholder = 'Seçin...') {
        if (!selectElement) return;

        selectElement.innerHTML = `<option value="">${placeholder}</option>`;

        data.forEach((item) => {
            const option = document.createElement('option');
            option.value = item.id;
            option.textContent = item.name || item.il || item.ilce || item.mahalle;
            selectElement.appendChild(option);
        });

        selectElement.disabled = false;
    }

    clearSelect(selectElement, placeholder = 'Seçin...') {
        if (!selectElement) return;

        selectElement.innerHTML = `<option value="">${placeholder}</option>`;
        selectElement.disabled = true;
    }

    setLoading(isLoading) {
        this.state.isLoading = isLoading;

        // Visual loading indicator
        document.querySelectorAll('.location-loading').forEach((el) => {
            el.style.display = isLoading ? 'block' : 'none';
        });
    }

    triggerLocationChange() {
        if (this.options.onLocationChange) {
            this.options.onLocationChange({
                province: this.state.selectedProvince,
                district: this.state.selectedDistrict,
                neighborhood: this.state.selectedNeighborhood,
            });
        }
    }

    showError(message) {
        if (window.toast && window.toast.error) {
            window.toast.error(message);
        } else {
            console.error('LocationManager Error:', message);
        }
    }

    log(message) {
        console.log(`[LocationManager] ${message}`);
    }

    logError(message, error) {
        console.error(`[LocationManager] ${message}:`, error);
    }

    /**
     * 🗑️ Cleanup
     */
    destroy() {
        // Remove event listeners
        this.elements.provinceSelect?.removeEventListener('change', this.handleProvinceChange);
        this.elements.districtSelect?.removeEventListener('change', this.handleDistrictChange);
        this.elements.neighborhoodSelect?.removeEventListener(
            'change',
            this.handleNeighborhoodChange
        );

        // Clear cache
        this.state.cache.clear();

        // Remove Google Maps instances
        if (this.marker) {
            this.marker.setMap(null);
            this.marker = null;
        }

        if (this.map) {
            this.map = null;
        }

        this.log('LocationManager destroyed');
    }
}

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LocationManager;
}

// Global availability
window.LocationManager = LocationManager;

export default LocationManager;
