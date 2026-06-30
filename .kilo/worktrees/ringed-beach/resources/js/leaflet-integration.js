/**
 * Leaflet.js OpenStreetMap Entegrasyonu
 * Google Maps yerine açık kaynak alternatif
 * Context7 uyumlu nearby search ile
 */

class LeafletMapManager {
    constructor(containerId = 'map') {
        this.containerId = containerId;
        this.map = null;
        this.markers = [];
        this.currentLocationMarker = null;
        this.nearbyMarkers = [];

        this.init();
    }

    /**
     * Haritayı başlat
     */
    init() {
        if (!document.getElementById(this.containerId)) {
            console.error(`Map container '${this.containerId}' not found`);
            return;
        }

        // İstanbul merkezli harita
        this.map = L.map(this.containerId).setView([41.0082, 28.9784], 13);

        // OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(this.map);

        // Harita tıklama eventi
        this.map.on('click', (e) => {
            this.onMapClick(e.latlng);
        });

        // Konum butonu ekle
        this.addLocationControl();
    }

    /**
     * Harita tıklama olayı
     */
    onMapClick(latlng) {
        this.setMarker(latlng.lat, latlng.lng);
        this.updateLocationInputs(latlng.lat, latlng.lng);
        this.reverseGeocode(latlng.lat, latlng.lng);
    }

    /**
     * Marker ekle/güncelle
     */
    setMarker(lat, lng, title = 'Seçilen Konum') {
        if (this.currentLocationMarker) {
            this.map.removeLayer(this.currentLocationMarker);
        }

        this.currentLocationMarker = L.marker([lat, lng], {
            title: title,
            draggable: true,
        }).addTo(this.map);

        // Marker sürüklenme eventi
        this.currentLocationMarker.on('dragend', (e) => {
            const position = e.target.getLatLng();
            this.updateLocationInputs(position.lat, position.lng);
            this.reverseGeocode(position.lat, position.lng);
        });

        // Popup ekle
        this.currentLocationMarker
            .bindPopup(
                `
            <div class="p-2">
                <strong>${title}</strong><br>
                <small>Lat: ${lat.toFixed(6)}, Lng: ${lng.toFixed(6)}</small>
            </div>
        `
            )
            .openPopup();
    }

    /**
     * Form inputlarını güncelle
     */
    updateLocationInputs(lat, lng) {
        // ✅ SAB: lat/lng standart
        const latInput = document.getElementById('lat');
        const lngInput = document.getElementById('lng');

        if (latInput) latInput.value = lat.toFixed(6);
        if (lngInput) lngInput.value = lng.toFixed(6);

        // Context7 event trigger
        if (window.Context7) {
            window.Context7.trigger('location:updated', { lat, lng });
        }
    }

    /**
     * Reverse geocoding - koordinatlardan adres
     */
    async reverseGeocode(lat, lng) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1&accept-language=tr`
            );

            const data = await response.json();

            if (data && data.address) {
                this.updateAddressFields(data.address, data.display_name);
            }
        } catch (error) {
            console.error('Reverse geocoding error:', error);
        }
    }

    /**
     * Adres alanlarını güncelle
     */
    updateAddressFields(address, displayName) {
        // İl
        const ilSelect = document.getElementById('il_id');
        if (ilSelect && address.state) {
            const ilOption = Array.from(ilSelect.options).find((opt) =>
                opt.text.toLowerCase().includes(address.state.toLowerCase())
            );
            if (ilOption) ilSelect.value = ilOption.value;
        }

        // İlçe
        const ilceSelect = document.getElementById('ilce_id');
        if (ilceSelect && address.county) {
            // İlçe dropdown'ını güncelle
            this.updateDistrictDropdown(address.county);
        }

        // Mahalle
        const mahalleInput = document.getElementById('mahalle');
        if (mahalleInput && address.suburb) {
            mahalleInput.value = address.suburb;
        }

        // Tam adres
        const adresInput = document.getElementById('adres');
        if (adresInput) {
            adresInput.value = displayName;
        }

        // Toast notification
        if (window.showToast) {
            window.showToast('success', 'Konum bilgileri güncellendi');
        }
    }

    /**
     * Nearby places - çevredeki yerler
     */
    async findNearbyPlaces(lat, lng, radius = 1000, type = '') {
        try {
            let query = `https://nominatim.openstreetmap.org/search?format=json&lat=${lat}&lon=${lng}&zoom=16&addressdetails=1&limit=20&accept-language=tr`;

            if (type) {
                query += `&q=${encodeURIComponent(type)}`;
            }

            const response = await fetch(query);
            const places = await response.json();

            this.displayNearbyPlaces(places);
            return places;
        } catch (error) {
            console.error('Nearby places error:', error);
            return [];
        }
    }

    /**
     * Nearby places'i haritada göster
     */
    displayNearbyPlaces(places) {
        // Önceki nearby marker'ları temizle
        this.clearNearbyMarkers();

        places.forEach((place) => {
            if (place.lat && place.lon) {
                const marker = L.marker([parseFloat(place.lat), parseFloat(place.lon)], {
                    icon: this.createNearbyIcon(place.type),
                }).addTo(this.map);

                marker.bindPopup(`
                    <div class="p-2">
                        <strong>${place.display_name}</strong><br>
                        <small>${place.type || 'Yer'}</small>
                    </div>
                `);

                this.nearbyMarkers.push(marker);
            }
        });
    }

    /**
     * Özel ikon oluştur
     */
    createNearbyIcon(type) {
        let color = '#3b82f6';

        switch (type) {
            case 'hospital':
                color = '#ef4444';
                break;
            case 'school':
                color = '#10b981';
                break;
            case 'shopping':
                color = '#f59e0b';
                break;
            case 'restaurant':
                color = '#8b5cf6';
                break;
            default:
                color = '#6b7280';
        }

        return L.divIcon({
            className: 'custom-nearby-marker',
            html: `<div style="background-color: ${color}; width: 12px; height: 12px; border-radius: 50%; border: 2px solid white;"></div>`,
            iconSize: [16, 16],
            iconAnchor: [8, 8],
        });
    }

    /**
     * Nearby marker'ları temizle
     */
    clearNearbyMarkers() {
        this.nearbyMarkers.forEach((marker) => {
            this.map.removeLayer(marker);
        });
        this.nearbyMarkers = [];
    }

    /**
     * Konum kontrolü ekle
     */
    addLocationControl() {
        const locationControl = L.control({ position: 'topright' });

        locationControl.onAdd = () => {
            const div = L.DomUtil.create('div', 'leaflet-control leaflet-bar');
            div.innerHTML = `
                <a href="#" title="Konumumu Bul" style="background: white; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; text-decoration: none; color: #333;">
                    📍
                </a>
            `;

            div.onclick = (e) => {
                e.preventDefault();
                this.getCurrentLocation();
            };

            return div;
        };

        locationControl.addTo(this.map);
    }

    /**
     * Mevcut konumu al
     */
    getCurrentLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;

                    this.map.setView([lat, lng], 16);
                    this.setMarker(lat, lng, 'Mevcut Konumum');
                    this.updateLocationInputs(lat, lng);
                    this.reverseGeocode(lat, lng);
                },
                (error) => {
                    console.error('Konum alınamadı:', error);
                    if (window.showToast) {
                        window.showToast('error', 'Konum bilgisi alınamadı');
                    }
                }
            );
        }
    }

    /**
     * Adres arama
     */
    async searchAddress(query) {
        try {
            const response = await fetch(
                `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(
                    query
                )}&limit=5&accept-language=tr&countrycodes=tr`
            );

            const results = await response.json();

            if (results.length > 0) {
                const result = results[0];
                const lat = parseFloat(result.lat);
                const lng = parseFloat(result.lon);

                this.map.setView([lat, lng], 16);
                this.setMarker(lat, lng, result.display_name);
                this.updateLocationInputs(lat, lng);
            }

            return results;
        } catch (error) {
            console.error('Address search error:', error);
            return [];
        }
    }

    /**
     * İlçe dropdown güncelle
     */
    async updateDistrictDropdown(countyName) {
        // İlçe API'si Context7 uyumlu
        const ilId = document.getElementById('il_id')?.value;
        if (!ilId) return;

        try {
            const response = await fetch(`/admin/api/locations/districts/${ilId}`);
            const districts = await response.json();

            const ilceSelect = document.getElementById('ilce_id');
            if (ilceSelect && districts.length > 0) {
                ilceSelect.innerHTML = '<option value="">İlçe Seçin</option>';

                districts.forEach((district) => {
                    const option = document.createElement('option');
                    option.value = district.id;
                    option.textContent = district.name;

                    // Eşleşen ilçeyi seç
                    if (district.name.toLowerCase().includes(countyName.toLowerCase())) {
                        option.selected = true;
                    }

                    ilceSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('District update error:', error);
        }
    }
}

// Global initialization
window.LeafletMapManager = LeafletMapManager;

// DOM ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('map')) {
        window.mapManager = new LeafletMapManager('map');

        // Nearby search butonları
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('nearby-search-btn')) {
                const type = e.target.dataset.type;
                // ✅ SAB: lat/lng standart
                const lat = document.getElementById('lat')?.value;
                const lng = document.getElementById('lng')?.value;

                if (lat && lng && window.mapManager) {
                    window.mapManager.findNearbyPlaces(
                        parseFloat(lat),
                        parseFloat(lng),
                        1000,
                        type
                    );
                }
            }
        });
    }
});
