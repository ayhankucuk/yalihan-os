/**
 * ⚠️ DEPRECATED - 3 Ocak 2026
 * 
 * POI Widget & GeoJSON Uploader Components
 * 
 * KULLANILMIYOR!
 * 
 * Sebep:
 *  - PoiSealedBadges.js bunu tamamen değiştirdi
 *  - Sealed fields + POI rendering yeni sistemde
 * 
 * Yeni System:
 *  - resources/js/components/PoiSealedBadges.js
 * 
 * Bu dosya backward compatibility için tutulmaktadır.
 */

console.error(`
❌ ERROR: poi-widget.js is DEPRECATED
════════════════════════════════════════════════════
This file is no longer maintained and should not be used.

Old System (DEPRECATED):
  - poiWidgetStep2()
  - renderPoiWidgets()

New System (USE THESE):
  - PoiSealedBadges (sealed POI badges)

Migration: See docs/WIZARD_CODE_HYGIENE_AUDIT.md
════════════════════════════════════════════════════
`);

/**
 * POI Widget & GeoJSON Uploader Components
 * Extracted from step-2-details.blade.php
 * 
 * ⚠️ DEPRECATED - Use PoiSealedBadges instead
 */

(function() {
    'use strict';

    // ✅ Window'a attach et (ReferenceError önleme)
    if (typeof window.poiWidgetStep2 === 'undefined') {
        window.poiWidgetStep2 = function() {
        let poiAbortController = null;
        let poiDebounceTimeout = null;

        return {
            pois: [],
            selectedCategories: [],
            poiProfile: 'residential',
            radius: 2000, // dynamic based on profile
            loading: false,
            error: null,
            availableCategories: [{
                    type: 'ulasim',
                    label: '🚌 Ulaşım',
                    icon: 'bus'
                },
                {
                    type: 'okul',
                    label: '🏫 Eğitim',
                    icon: 'school'
                },
                {
                    type: 'hastane',
                    label: '🏥 Sağlık',
                    icon: 'hospital'
                },
                {
                    type: 'market',
                    label: '🛒 Alışveriş',
                    icon: 'shopping-cart'
                },
                {
                    type: 'sahil',
                    label: '🏖️ Sahil & Deniz',
                    icon: 'beach'
                },
                {
                    type: 'park',
                    label: '🌳 Park & Yeşil Alan',
                    icon: 'tree'
                },
            ],

            get filteredPOIs() {
                if (this.selectedCategories.length === 0) {
                    return this.pois;
                }
                return this.pois.filter(poi => this.selectedCategories.includes(poi.type));
            },

            getCategoryCount(type) {
                return this.pois.filter(poi => poi.type === type).length;
            },

            init() {
                // Initialization with async/await pattern (no race conditions)
                // Wait max 3 seconds for Step 1 map to initialize
                const startTime = Date.now();
                const timeout = 3000; // 3 seconds max

                const checkAndLoad = async () => {
                    const elapsed = Date.now() - startTime;
                    if (elapsed > timeout) {
                        console.warn('⏱️ POI: Step 1 map timeout (3s), proceeding anyway');
                        return;
                    }

                    if (!window.wizardMap) {
                        console.warn('⏳ POI: wizardMap not ready, retrying in 300ms...');
                        await new Promise(resolve => setTimeout(resolve, 300));
                        return checkAndLoad();
                    }

                    // ✅ FIX: Use global wizardMarker instead of wizardMap.mainMarker
                    const marker = window.wizardMarker;
                    if (!marker) {
                        console.warn('⏳ POI: wizardMarker not ready, retrying in 300ms...');
                        await new Promise(resolve => setTimeout(resolve, 300));
                        return checkAndLoad();
                    }

                    // Both ready - proceed with loading
                    const latlng = marker.getLatLng();
                    await this.loadPOIs(latlng.lat, latlng.lng);
                };

                checkAndLoad();
            },

            async loadPOIs(lat, lng) {
                if (!lat || !lng) return;

                // Cancel previous request
                if (poiAbortController) poiAbortController.abort();
                poiAbortController = new AbortController();

                // Clear debounce timeout
                clearTimeout(poiDebounceTimeout);

                // Set loading state
                this.loading = true;
                this.error = null;

                // Debounce: wait 1000ms before making request
                poiDebounceTimeout = setTimeout(async () => {
                    try {
                        const types = this.selectedCategories.length > 0 ? this.selectedCategories
                            .join(',') : '';
                        const url = new URL('/api/v1/environment/pois', window.location.origin);
                        url.searchParams.append('lat', lat);
                        url.searchParams.append('lng', lng);
                        url.searchParams.append('radius', this.radius);
                        if (types) url.searchParams.append('types', types);

                        const response = await fetch(url, {
                            signal: poiAbortController.signal
                        });

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        const data = await response.json();

                        if (data.success && data.data && data.data.pois) {
                            this.pois = data.data.pois;
                        } else {
                            this.error = 'POI verileri alınamadı';
                        }
                    } catch (err) {
                        if (err.name !== 'AbortError') {
                            this.error = err.message;
                            console.error('POI load error:', err);
                        }
                    } finally {
                        this.loading = false;
                    }
                }, 1000);
            },

            toggleCategory(type) {
                const index = this.selectedCategories.indexOf(type);
                if (index > -1) {
                    this.selectedCategories.splice(index, 1);
                } else {
                    this.selectedCategories.push(type);
                }
                // Reload with new filter
                if (window.wizardMap && window.wizardMap.mainMarker) {
                    const latlng = window.wizardMap.mainMarker.getLatLng();
                    this.loadPOIs(latlng.lat, latlng.lng);
                }
            },

            setRadius(newRadius) {
                this.radius = newRadius;
                if (window.wizardMap && window.wizardMap.mainMarker) {
                    const latlng = window.wizardMap.mainMarker.getLatLng();
                    this.loadPOIs(latlng.lat, latlng.lng);
                }
            }
        };
    };
    }

    // geojsonUploaderStep2 definition
    window.geojsonUploaderStep2 = function() {
        return {
            fileName: '',
            fileSize: '',

            handleFileSelect(event) {
                const file = event.target.files[0];
                if (!file) return;

                this.fileName = file.name;
                this.fileSize = (file.size / 1024).toFixed(2) + ' KB';

                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const geojson = JSON.parse(e.target.result);
                        // Handle GeoJSON...
                        console.log('GeoJSON loaded:', geojson);

                        // Emit event for map to handle
                        window.dispatchEvent(new CustomEvent('geojson-loaded', {
                            detail: { geojson: geojson }
                        }));

                    } catch (error) {
                        console.error('Invalid GeoJSON:', error);
                        // Optionally set an error state if bound to Alpine component
                    }
                };
                reader.readAsText(file);
            }
        };
    };

})();
