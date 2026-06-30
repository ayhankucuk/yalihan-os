/**
 * LocationManager - Singleton Modern Konum Yönetim Sistemi
 * 
 * @context7 YALIHAN_BEKCI_APPROVED
 * - jQuery/Select2 bağımlılığı YOK - Pure Vanilla JS
 * - Singleton pattern ile tek instance
 * - Memory leak önleme ve proper cleanup
 * - Debounce utilities entegrasyonu
 * - Backend geo proxy kullanımı (rate limit + cache)
 * - Leaflet.js + OpenStreetMap standardı
 * 
 * Sürüm: 4.0 - Modern Refactor (Aralık 2025)
 * Önceki: LocationMapHelper.js (jQuery/Select2 bağımlı)
 */

import { debounce, throttle } from '/resources/js/utils/debounce.js';

class LocationManager {
    /**
     * Singleton instance
     * @private
     */
    static #instance = null;

    /**
     * Aktif location helper'lar
     * @private
     */
    #helpers = new Map();

    /**
     * Cleanup fonksiyonları
     * @private
     */
    #cleanupCallbacks = new Set();

    /**
     * Constructor - Private, sadece getInstance ile erişim
     * @private
     */
    constructor() {
        if (LocationManager.#instance) {
            return LocationManager.#instance;
        }

        console.log('✅ LocationManager singleton başlatıldı');
        this.#setupGlobalCleanup();
        
        LocationManager.#instance = this;
    }

    /**
     * Singleton instance getter
     * @returns {LocationManager}
     */
    static getInstance() {
        if (!LocationManager.#instance) {
            LocationManager.#instance = new LocationManager();
        }
        return LocationManager.#instance;
    }

    /**
     * Location helper oluştur veya getir
     * @param {Object} options - Helper ayarları
     * @returns {LocationHelper}
     */
    createHelper(options) {
        const { uniqueId } = options;
        
        if (!uniqueId) {
            throw new Error('uniqueId parametresi zorunludur');
        }

        // Mevcut helper varsa kaldır
        if (this.#helpers.has(uniqueId)) {
            this.destroyHelper(uniqueId);
        }

        // Yeni helper oluştur
        const helper = new LocationHelper(options);
        this.#helpers.set(uniqueId, helper);
        
        console.log(`✅ LocationHelper oluşturuldu: ${uniqueId}`);
        return helper;
    }

    /**
     * Helper'ı temizle ve kaldır
     * @param {string} uniqueId - Helper ID
     */
    destroyHelper(uniqueId) {
        const helper = this.#helpers.get(uniqueId);
        if (helper) {
            helper.cleanup();
            this.#helpers.delete(uniqueId);
            console.log(`🗑️ LocationHelper temizlendi: ${uniqueId}`);
        }
    }

    /**
     * Tüm helper'ları temizle
     */
    destroyAll() {
        this.#helpers.forEach((helper, id) => {
            this.destroyHelper(id);
        });
        console.log('🗑️ Tüm LocationHelper\'lar temizlendi');
    }

    /**
     * Global cleanup setup - Memory leak önleme
     * @private
     */
    #setupGlobalCleanup() {
        // Page unload'da cleanup
        window.addEventListener('beforeunload', () => {
            this.destroyAll();
            this.#cleanupCallbacks.forEach(callback => callback());
        });

        // Turbolinks/SPA destekli cleanup
        if (typeof Turbolinks !== 'undefined') {
            document.addEventListener('turbolinks:before-cache', () => {
                this.destroyAll();
            });
        }
    }

    /**
     * Cleanup callback ekle
     * @param {Function} callback - Cleanup fonksiyonu
     */
    addCleanupCallback(callback) {
        this.#cleanupCallbacks.add(callback);
    }

    /**
     * Helper al
     * @param {string} uniqueId - Helper ID
     * @returns {LocationHelper|null}
     */
    getHelper(uniqueId) {
        return this.#helpers.get(uniqueId) || null;
    }
}

/**
 * LocationHelper - Tekil konum yönetimi
 * @private - Sadece LocationManager tarafından kullanılır
 */
class LocationHelper {
    constructor(options) {
        // Defaults
        this.options = {
            uniqueId: 'loc_default',
            initialLat: 38.4237,
            initialLng: 27.1428,
            initialZoom: 6,
            language: 'tr',
            showMap: true,
            enableSearch: true,
            enableReset: true,
            ...options
        };

        // Element refs
        this.elements = this.#findElements();
        
        // Map instances
        this.map = null;
        this.marker = null;
        this.geocoderControl = null;

        // Event listeners store (cleanup için)
        this.#listeners = [];

        // Debounced functions
        this.#setupDebouncedFunctions();

        // Initialize
        this.#init();
    }

    /**
     * Event listeners store
     * @private
     */
    #listeners = [];

    /**
     * Debounced functions
     * @private
     */
    #debouncedSearch = null;
    #debouncedReverseGeocode = null;

    /**
     * Element'leri bul
     * @private
     */
    #findElements() {
        const { uniqueId } = this.options;
        
        const findElement = (suffix) => {
            const specificId = `${uniqueId}_${suffix}`;
            return document.getElementById(specificId) || document.getElementById(suffix);
        };

        return {
            container: document.getElementById(uniqueId),
            mapContainer: findElement('map'),
            ulkeSelect: findElement('ulke_select'),
            ilSelect: findElement('il_select'),
            ilceSelect: findElement('ilce_select'),
            mahalleSelect: findElement('mahalle_select'),
            latitudeInput: findElement('latitude'),
            longitudeInput: findElement('longitude'),
            searchButton: findElement('map_search'),
            resetButton: findElement('map_reset'),
        };
    }

    /**
     * Debounced fonksiyonları setup et
     * @private
     */
    #setupDebouncedFunctions() {
        // Adres arama - 500ms debounce
        this.#debouncedSearch = debounce((query) => {
            this.#performSearch(query);
        }, 500);

        // Reverse geocoding - 300ms debounce
        this.#debouncedReverseGeocode = debounce((lat, lng) => {
            this.#performReverseGeocode(lat, lng);
        }, 300);
    }

    /**
     * Initialize
     * @private
     */
    #init() {
        if (this.options.showMap && this.elements.mapContainer) {
            this.#initMap();
        }
        this.#attachEventListeners();
        this.#setInitialValues();
    }

    /**
     * Harita başlat
     * @private
     */
    #initMap() {
        try {
            const { initialLat, initialLng, initialZoom } = this.options;

            this.map = L.map(this.elements.mapContainer).setView(
                [initialLat, initialLng],
                initialZoom
            );

            // Tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                maxZoom: 19,
            }).addTo(this.map);

            // Geocoder control
            this.geocoderControl = L.Control.geocoder().addTo(this.map);

            // Initial marker
            if (this.elements.latitudeInput.value && this.elements.longitudeInput.value) {
                this.addMarker(
                    parseFloat(this.elements.latitudeInput.value),
                    parseFloat(this.elements.longitudeInput.value)
                );
            }

            // Map click event
            this.map.on('click', (e) => {
                this.addMarker(e.latlng.lat, e.latlng.lng);
                this.#debouncedReverseGeocode(e.latlng.lat, e.latlng.lng);
            });

            console.log('✅ Harita başlatıldı:', this.options.uniqueId);
        } catch (error) {
            console.error('❌ Harita başlatma hatası:', error);
        }
    }

    /**
     * Marker ekle
     * @param {number} lat - Latitude
     * @param {number} lng - Longitude
     */
    addMarker(lat, lng) {
        // Remove previous marker
        if (this.marker) {
            this.map.removeLayer(this.marker);
        }

        // Add new marker
        this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);

        // Update inputs
        this.elements.latitudeInput.value = lat.toFixed(6);
        this.elements.longitudeInput.value = lng.toFixed(6);

        // Dragend event
        this.marker.on('dragend', (e) => {
            const pos = e.target.getLatLng();
            this.elements.latitudeInput.value = pos.lat.toFixed(6);
            this.elements.longitudeInput.value = pos.lng.toFixed(6);
            this.#debouncedReverseGeocode(pos.lat, pos.lng);
        });
    }

    /**
     * Reverse geocode - Backend proxy ile
     * @private
     */
    async #performReverseGeocode(lat, lng) {
        try {
            const endpoint = window.APIConfig?.geo?.reverseGeocode || '/api/v1/geo/reverse-geocode';
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.#getCsrfToken(),
                },
                body: JSON.stringify({ lat, lng }),
            });

            if (!response.ok) throw new Error('API hatası');

            const result = await response.json();
            
            if (!result.success || !result.data) {
                console.warn('Konum bilgisi alınamadı:', result.message);
                return;
            }

            this.#updateLocationSelects(result.data);
            
        } catch (error) {
            console.error('Reverse geocode hatası:', error);
            this.#showToast('Konum bilgisi alınamadı', 'error');
        }
    }

    /**
     * Location select'leri güncelle - Pure Vanilla JS
     * @private
     */
    #updateLocationSelects(data) {
        // İl güncelle
        if (data.il && this.elements.ilSelect) {
            const ilText = data.il.toUpperCase();
            const options = Array.from(this.elements.ilSelect.options);
            
            const matchedOption = options.find(opt => 
                opt.text.toUpperCase().includes(ilText)
            );
            
            if (matchedOption) {
                this.elements.ilSelect.value = matchedOption.value;
                this.#triggerChange(this.elements.ilSelect);
            }
        }

        // İlçe ve mahalle için benzer güncellemeler yapılabilir
        console.log('📍 Konum güncellendi:', data);
    }

    /**
     * Change event tetikle - Vanilla JS
     * @private
     */
    #triggerChange(element) {
        if (!element) return;
        
        const event = new Event('change', { bubbles: true });
        element.dispatchEvent(event);
    }

    /**
     * Adres arama
     * @private
     */
    async #performSearch(query) {
        if (!query || query.length < 3) return;

        try {
            const endpoint = window.APIConfig?.geo?.geocode || '/api/v1/geo/geocode';
            
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.#getCsrfToken(),
                },
                body: JSON.stringify({ query }),
            });

            if (!response.ok) throw new Error('API hatası');

            const result = await response.json();
            
            if (result.success && result.data) {
                this.#handleSearchResults(result.data);
            }
            
        } catch (error) {
            console.error('Adres arama hatası:', error);
            this.#showToast('Adres aranamadı', 'error');
        }
    }

    /**
     * Arama sonuçlarını işle
     * @private
     */
    #handleSearchResults(results) {
        if (!results || results.length === 0) return;

        const firstResult = results[0];
        
        if (firstResult.lat && firstResult.lng) {
            this.map.setView([firstResult.lat, firstResult.lng], 15);
            this.addMarker(firstResult.lat, firstResult.lng);
            this.#updateLocationSelects(firstResult);
        }
    }

    /**
     * Event listeners ekle
     * @private
     */
    #attachEventListeners() {
        // İl değişimi
        if (this.elements.ilSelect) {
            this.#addEventListener(this.elements.ilSelect, 'change', () => {
                const ilId = this.elements.ilSelect.value;
                if (ilId) this.#fetchIlceler(ilId);
            });
        }

        // İlçe değişimi
        if (this.elements.ilceSelect) {
            this.#addEventListener(this.elements.ilceSelect, 'change', () => {
                const ilceId = this.elements.ilceSelect.value;
                if (ilceId) this.#fetchMahalleler(ilceId);
            });
        }

        // Search button
        if (this.elements.searchButton && this.options.enableSearch) {
            this.#addEventListener(this.elements.searchButton, 'click', () => {
                this.#openSearchModal();
            });
        }

        // Reset button
        if (this.elements.resetButton && this.options.enableReset) {
            this.#addEventListener(this.elements.resetButton, 'click', () => {
                this.resetMap();
            });
        }
    }

    /**
     * Event listener ekle (cleanup için kaydet)
     * @private
     */
    #addEventListener(element, event, handler) {
        if (!element) return;
        
        element.addEventListener(event, handler);
        this.#listeners.push({ element, event, handler });
    }

    /**
     * İlçeleri getir - Vanilla JS
     * @private
     */
    async #fetchIlceler(ilId) {
        if (!ilId) return;

        this.#resetSelect(this.elements.ilceSelect);
        this.#resetSelect(this.elements.mahalleSelect);

        try {
            const response = await fetch(`/api/location/ilceler?il_id=${ilId}`, {
                headers: { 'X-CSRF-TOKEN': this.#getCsrfToken() }
            });
            
            const result = await response.json();
            
            if (result.status === 'success' && result.data?.length > 0) {
                this.elements.ilceSelect.disabled = false;
                
                result.data.forEach(ilce => {
                    const option = new Option(ilce.ilce_adi, ilce.id);
                    this.elements.ilceSelect.appendChild(option);
                });

                this.#triggerChange(this.elements.ilceSelect);
            }
        } catch (error) {
            console.error('İlçe getirme hatası:', error);
        }
    }

    /**
     * Mahalleleri getir - Vanilla JS
     * @private
     */
    async #fetchMahalleler(ilceId) {
        if (!ilceId) return;

        this.#resetSelect(this.elements.mahalleSelect);

        try {
            const response = await fetch(`/api/location/mahalleler?ilce_id=${ilceId}`, {
                headers: { 'X-CSRF-TOKEN': this.#getCsrfToken() }
            });
            
            const result = await response.json();
            
            if (result.status === 'success' && result.data?.length > 0) {
                this.elements.mahalleSelect.disabled = false;
                
                result.data.forEach(mahalle => {
                    const option = new Option(mahalle.mahalle_adi, mahalle.id);
                    this.elements.mahalleSelect.appendChild(option);
                });

                this.#triggerChange(this.elements.mahalleSelect);
            }
        } catch (error) {
            console.error('Mahalle getirme hatası:', error);
        }
    }

    /**
     * Select sıfırla - Vanilla JS
     * @private
     */
    #resetSelect(select) {
        if (!select) return;

        // İlk option hariç tümünü kaldır
        Array.from(select.options).slice(1).forEach(opt => opt.remove());
        select.disabled = true;
        this.#triggerChange(select);
    }

    /**
     * Haritayı sıfırla
     */
    resetMap() {
        if (!this.map) return;

        const { initialLat, initialLng, initialZoom } = this.options;

        this.map.setView([initialLat, initialLng], initialZoom);

        if (this.marker) {
            this.map.removeLayer(this.marker);
            this.marker = null;
        }

        this.elements.latitudeInput.value = initialLat;
        this.elements.longitudeInput.value = initialLng;

        if (this.elements.ulkeSelect) {
            this.elements.ulkeSelect.value = 'TR';
            this.#triggerChange(this.elements.ulkeSelect);
        }

        this.#resetSelect(this.elements.ilSelect);
        this.#resetSelect(this.elements.ilceSelect);
        this.#resetSelect(this.elements.mahalleSelect);
    }

    /**
     * Search modal aç
     * @private
     */
    #openSearchModal() {
        const query = prompt(
            this.options.language === 'tr' 
                ? 'Adres aramak için bir yer adı girin:' 
                : 'Enter a place name to search:'
        );
        
        if (query) {
            this.#debouncedSearch(query);
        }
    }

    /**
     * Initial values set
     * @private
     */
    #setInitialValues() {
        if (this.elements.ilSelect?.value) {
            this.#fetchIlceler(this.elements.ilSelect.value);
        }
    }

    /**
     * CSRF token al
     * @private
     */
    #getCsrfToken() {
        return window.__csrfToken || 
               document.querySelector('meta[name="csrf-token"]')?.content || 
               '';
    }

    /**
     * Toast göster
     * @private
     */
    #showToast(message, type = 'info') {
        if (window.showToast) {
            window.showToast(message, type);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    /**
     * Cleanup - Memory leak önleme
     */
    cleanup() {
        // Event listeners temizle
        this.#listeners.forEach(({ element, event, handler }) => {
            element.removeEventListener(event, handler);
        });
        this.#listeners = [];

        // Debounced functions cancel
        if (this.#debouncedSearch?.cancel) this.#debouncedSearch.cancel();
        if (this.#debouncedReverseGeocode?.cancel) this.#debouncedReverseGeocode.cancel();

        // Map temizle
        if (this.map) {
            this.map.remove();
            this.map = null;
        }

        console.log('🗑️ LocationHelper cleanup tamamlandı');
    }
}

// Export singleton instance
export default LocationManager.getInstance();

// Global access (backward compatibility)
if (typeof window !== 'undefined') {
    window.LocationManager = LocationManager.getInstance();
}
