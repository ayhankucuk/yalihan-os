/**
 * Context7 Location Service
 *
 * Location-based services for real estate listings
 * Version: 2.0.0 - Context7 Smart İlan Standard
 */

class Context7LocationService {
    constructor() {
        this.config = {
            apiBase: (window.APIConfig && window.APIConfig.apiV1Prefix) ? `${window.APIConfig.apiV1Prefix}/location` : '/api/v1/location',
            defaultCenter: { lat: 39.9334, lng: 32.8597 }, // Ankara
            defaultZoom: 10,
            mapOptions: {
                zoom: 10,
                mapTypeId: 'roadmap',
                streetViewControl: false,
                fullscreenControl: true,
                mapTypeControl: true,
            },
        };

        this.map = null;
        this.marker = null;
        this.geocoder = null;
        this.places = null;
        this.currentLocation = null;

        this.init();
    }

    /**
     * Initialize location service
     */
    init() {
        console.log('📍 Context7 Location Service initializing...');
        this.setupGeolocation();
        console.log('✅ SAB Location Service initialized');
    }

    /**
     * Initialize Google Maps
     */
    initMap(containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.warn(`Map container ${containerId} not found`);
            return;
        }

        // Wait for Google Maps API to load
        if (typeof google === 'undefined') {
            console.warn('Google Maps API not loaded');
            return;
        }

        try {
            this.map = new google.maps.Map(container, {
                center: this.config.defaultCenter,
                ...this.config.mapOptions,
            });

            this.geocoder = new google.maps.Geocoder();
            this.places = new google.maps.places.PlacesService(this.map);

            this.setupMapEvents();
            console.log('🗺️ Google Map initialized');
        } catch (error) {
            console.error('Map initialization error:', error);
            this.showMapFallback(container);
        }
    }

    /**
     * Setup map event listeners
     */
    setupMapEvents() {
        if (!this.map) return;

        // Click to place marker
        this.map.addListener('click', (event) => {
            this.placeMarker(event.latLng);
            this.updateLocationInputs(event.latLng.lat(), event.latLng.lng());
            this.reverseGeocode(event.latLng);
        });

        // Search box integration
        const searchBox = document.getElementById('location-search');
        if (searchBox) {
            this.setupLocationSearch(searchBox);
        }
    }

    /**
     * Setup location search
     */
    setupLocationSearch(searchBox) {
        if (!google || !google.maps.places) return;

        const searchInput = new google.maps.places.SearchBox(searchBox);

        // Bias the SearchBox results towards current map's viewport
        this.map.addListener('bounds_changed', () => {
            searchInput.setBounds(this.map.getBounds());
        });

        searchInput.addListener('places_changed', () => {
            const places = searchInput.getPlaces();

            if (places.length === 0) return;

            // Clear existing marker
            if (this.marker) {
                this.marker.setMap(null);
            }

            // For each place, get the icon, name and location
            const bounds = new google.maps.LatLngBounds();

            places.forEach((place) => {
                if (!place.geometry || !place.geometry.location) return;

                // Place marker
                this.placeMarker(place.geometry.location);

                // Update inputs
                this.updateLocationInputs(
                    place.geometry.location.lat(),
                    place.geometry.location.lng()
                );

                if (place.geometry.viewport) {
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
            });

            this.map.fitBounds(bounds);
        });
    }

    /**
     * Place or update marker on map
     */
    placeMarker(location) {
        if (!this.map) return;

        if (this.marker) {
            this.marker.setPosition(location);
        } else {
            this.marker = new google.maps.Marker({
                position: location,
                map: this.map,
                draggable: true,
                title: 'İlan Konumu',
            });

            // Make marker draggable
            this.marker.addListener('dragend', (event) => {
                this.updateLocationInputs(event.latLng.lat(), event.latLng.lng());
                this.reverseGeocode(event.latLng);
            });
        }

        this.map.setCenter(location);
    }

    /**
     * Get current location
     */
    getCurrentLocation() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation is not supported'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const location = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude,
                    };

                    this.currentLocation = location;

                    if (this.map) {
                        const latLng = new google.maps.LatLng(location.lat, location.lng);
                        this.placeMarker(latLng);
                        this.updateLocationInputs(location.lat, location.lng);
                        this.reverseGeocode(latLng);
                    }

                    resolve(location);
                },
                (error) => {
                    console.error('Geolocation error:', error);
                    reject(error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000, // 5 minutes
                }
            );
        });
    }

    /**
     * Reverse geocode coordinates to address
     */
    reverseGeocode(latLng) {
        if (!this.geocoder) return;

        this.geocoder.geocode({ location: latLng }, (results, durum) => {
            if (durum === 'OK' && results[0]) {
                this.updateAddressFields(results[0]);
            }
        });
    }

    /**
     * Geocode address to coordinates
     */
    geocodeAddress(address) {
        return new Promise((resolve, reject) => {
            if (!this.geocoder) {
                reject(new Error('Geocoder not initialized'));
                return;
            }

            this.geocoder.geocode({ address }, (results, durum) => {
                if (durum === 'OK' && results[0]) {
                    const location = results[0].geometry.location;

                    if (this.map) {
                        this.placeMarker(location);
                        this.map.setCenter(location);
                    }

                    this.updateLocationInputs(location.lat(), location.lng());
                    resolve({ lat: location.lat(), lng: location.lng() });
                } else {
                    reject(new Error(`Geocoding failed: ${durum}`));
                }
            });
        });
    }

    /**
     * Update location input fields
     */
    updateLocationInputs(lat, lng) {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');

        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);

        // Trigger change events
        if (latInput) latInput.dispatchEvent(new Event('change'));
        if (lngInput) lngInput.dispatchEvent(new Event('change'));
    }

    /**
     * Update address fields from geocoding result
     */
    updateAddressFields(result) {
        const components = this.parseAddressComponents(result.address_components);

        // Update address field
        const addressField = document.getElementById('detayli_adres');
        if (addressField && !addressField.value) {
            addressField.value = result.formatted_address;
        }

        // Try to match with Turkish location selects
        this.updateLocationSelects(components);
    }

    /**
     * Parse address components
     */
    parseAddressComponents(components) {
        const parsed = {};

        components.forEach((component) => {
            const types = component.types;

            if (types.includes('administrative_area_level_1')) {
                parsed.il = component.long_name;
            } else if (types.includes('administrative_area_level_2')) {
                parsed.ilçe = component.long_name;
            } else if (
                types.includes('administrative_area_level_3') ||
                types.includes('sublocality')
            ) {
                parsed.mahalle = component.long_name;
            } else if (types.includes('street_number')) {
                parsed.sokak_no = component.long_name;
            } else if (types.includes('route')) {
                parsed.sokak = component.long_name;
            }
        });

        return parsed;
    }

    /**
     * Update location select dropdowns
     */
    async updateLocationSelects(components) {
        if (components.il) {
            await this.selectLocationByName('il', components.il);
        }

        if (components.ilçe) {
            await this.selectLocationByName('ilce', components.ilçe);
        }

        if (components.mahalle) {
            await this.selectLocationByName('mahalle', components.mahalle);
        }
    }

    /**
     * Select location dropdown by name
     */
    async selectLocationByName(type, name) {
        const select = document.getElementById(type);
        if (!select) return;

        // Find option by name
        const options = Array.from(select.options);
        const matchingOption = options.find(
            (option) =>
                option.text.toLowerCase().includes(name.toLowerCase()) ||
                name.toLowerCase().includes(option.text.toLowerCase())
        );

        if (matchingOption) {
            select.value = matchingOption.value;
            select.dispatchEvent(new Event('change'));
        }
    }

    /**
     * Setup geolocation features
     */
    setupGeolocation() {
        // Add get current location button handler
        const getCurrentLocationBtn = document.getElementById('get-current-location');
        if (getCurrentLocationBtn) {
            getCurrentLocationBtn.addEventListener('click', async () => {
                try {
                    getCurrentLocationBtn.disabled = true;
                    getCurrentLocationBtn.innerHTML =
                        '<i class="fas fa-spinner fa-spin mr-1"></i> Konum alınıyor...';

                    await this.getCurrentLocation();

                    if (window.showNotification) {
                        window.showNotification('Mevcut konum alındı', 'success');
                    }
                } catch (error) {
                    console.error('Current location error:', error);

                    if (window.showNotification) {
                        window.showNotification('Konum alınamadı: ' + error.message, 'error');
                    }
                } finally {
                    getCurrentLocationBtn.disabled = false;
                    getCurrentLocationBtn.innerHTML =
                        '<i class="fas fa-crosshairs mr-1"></i> Konumumu Al';
                }
            });
        }
    }

    /**
     * Show map fallback when Google Maps fails
     */
    showMapFallback(container) {
        container.innerHTML = `
            <div class="flex items-center justify-center h-full bg-gray-100 text-gray-500 dark:bg-slate-800 dark:text-slate-500">
                <div class="text-center">
                    <i class="fas fa-map-marked-alt text-4xl mb-4"></i>
                    <p class="text-lg font-medium">Harita yüklenemedi</p>
                    <p class="text-sm">Konum bilgilerini manuel olarak girebilirsiniz</p>
                </div>
            </div>
        `;
    }

    /**
     * Load districts for selected province
     */
    async loadIlceler(ilId) {
        if (!ilId) return [];

        try {
            const response = await fetch(`${this.config.apiBase}/ilceler/${ilId}`);
            const data = await response.json();

            return data.success ? data.data : [];
        } catch (error) {
            console.error('İlçe loading error:', error);
            return [];
        }
    }

    /**
     * Load neighborhoods for selected district
     */
    async loadMahalleler(ilceId) {
        if (!ilceId) return [];

        try {
            const response = await fetch(`${this.config.apiBase}/mahalleler/${ilceId}`);
            const data = await response.json();

            return data.success ? data.data : [];
        } catch (error) {
            console.error('Mahalle loading error:', error);
            return [];
        }
    }

    /**
     * Get location data
     */
    getLocationData() {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const addressInput = document.getElementById('detayli_adres');
        const ilSelect = document.getElementById('il');
        const ilceSelect = document.getElementById('ilce');
        const mahalleSelect = document.getElementById('mahalle');

        return {
            latitude: latInput?.value ? parseFloat(latInput.value) : null,
            longitude: lngInput?.value ? parseFloat(lngInput.value) : null,
            address: addressInput?.value || '',
            il_id: ilSelect?.value || null,
            ilce_id: ilceSelect?.value || null,
            mahalle_id: mahalleSelect?.value || null,
            il_name: ilSelect?.selectedOptions[0]?.text || '',
            ilce_name: ilceSelect?.selectedOptions[0]?.text || '',
            mahalle_name: mahalleSelect?.selectedOptions[0]?.text || '',
        };
    }

    /**
     * Calculate distance between two points
     */
    calculateDistance(lat1, lng1, lat2, lng2) {
        const R = 6371; // Earth's radius in kilometers
        const dLat = ((lat2 - lat1) * Math.PI) / 180;
        const dLng = ((lng2 - lng1) * Math.PI) / 180;

        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos((lat1 * Math.PI) / 180) *
                Math.cos((lat2 * Math.PI) / 180) *
                Math.sin(dLng / 2) *
                Math.sin(dLng / 2);

        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        const distance = R * c;

        return distance; // Distance in kilometers
    }

    /**
     * Find nearby points of interest
     */
    findNearbyPOIs(location, radius = 1000) {
        return new Promise((resolve, reject) => {
            if (!this.places || !location) {
                reject(new Error('Places service not initialized or location not provided'));
                return;
            }

            const request = {
                location: location,
                radius: radius,
                type: ['hospital', 'school', 'shopping_mall', 'bank', 'gas_station'],
            };

            this.places.nearbySearch(request, (results, durum) => {
                if (durum === google.maps.places.PlacesServiceStatus.OK) {
                    resolve(results || []);
                } else {
                    reject(new Error(`Places search failed: ${durum}`));
                }
            });
        });
    }

    /**
     * Clear location data
     */
    clearLocation() {
        // Clear inputs
        const inputs = ['latitude', 'longitude', 'detayli_adres'];
        inputs.forEach((id) => {
            const input = document.getElementById(id);
            if (input) input.value = '';
        });

        // Clear selects
        const selects = ['il', 'ilce', 'mahalle'];
        selects.forEach((id) => {
            const select = document.getElementById(id);
            if (select) select.value = '';
        });

        // Clear map
        if (this.marker) {
            this.marker.setMap(null);
            this.marker = null;
        }

        if (this.map) {
            this.map.setCenter(this.config.defaultCenter);
            this.map.setZoom(this.config.defaultZoom);
        }

        this.currentLocation = null;

        console.log('📍 Location data cleared');
    }
}
