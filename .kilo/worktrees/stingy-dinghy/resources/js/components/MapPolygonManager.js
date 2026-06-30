/**
 * 🗺️ MapPolygonManager — Arsa/Arazi Polygon Drawing & GeoJSON Upload
 *
 * Leaflet.Draw entegrasyonu:
 * - Polygon çizimi (arsa/arazi ilanları)
 * - GeoJSON dosya yükleme
 * - Otomatik alan hesaplama (m²)
 * - Centroid çıkarma (MIE için lat/lng)
 * - POI görselleştirme (yakındaki noktalar)
 *
 * Context7: lat/lng (enlem/boylam YASAK)
 */

export default class MapPolygonManager {
    constructor(map, options = {}) {
        this.map = map;
        this.drawnItems = new L.FeatureGroup();
        this.poiLayer = new L.FeatureGroup();
        this.drawControl = null;
        this.currentPolygon = null;
        this.onGeometryChange = options.onGeometryChange || (() => {});
        this.onAreaCalculated = options.onAreaCalculated || (() => {});

        this.map.addLayer(this.drawnItems);
        this.map.addLayer(this.poiLayer);
    }

    /**
     * Polygon çizim modunu aktifleştir
     */
    enableDrawing() {
        if (this.drawControl) return;

        // L.Draw CDN'den yükleniyor — hazır değilse bekle
        if (
            typeof L === 'undefined' ||
            typeof L.Control === 'undefined' ||
            typeof L.Control.Draw === 'undefined'
        ) {
            console.log('⏳ Leaflet.Draw yüklenmesi bekleniyor...');
            this._drawRetries = (this._drawRetries || 0) + 1;
            if (this._drawRetries < 20) {
                setTimeout(() => this.enableDrawing(), 300);
            } else {
                console.error('❌ Leaflet.Draw 6 saniye içinde yüklenemedi');
            }
            return;
        }
        this._drawRetries = 0;

        this.drawControl = new L.Control.Draw({
            position: 'topright',
            draw: {
                polygon: {
                    allowIntersection: false,
                    showArea: true,
                    shapeOptions: {
                        color: '#f59e0b',
                        fillColor: '#f59e0b',
                        fillOpacity: 0.2,
                        weight: 3,
                    },
                    metric: true,
                },
                polyline: false,
                circle: false,
                rectangle: false,
                marker: false,
                circlemarker: false,
            },
            edit: {
                featureGroup: this.drawnItems,
                remove: true,
                edit: true,
            },
        });

        this.map.addControl(this.drawControl);

        // Draw events
        this.map.on(L.Draw.Event.CREATED, (e) => {
            this._onPolygonCreated(e.layer);
        });

        this.map.on(L.Draw.Event.EDITED, () => {
            this._onPolygonEdited();
        });

        this.map.on(L.Draw.Event.DELETED, () => {
            this._onPolygonDeleted();
        });
    }

    /**
     * Polygon çizim modunu devre dışı bırak
     */
    disableDrawing() {
        if (this.drawControl) {
            this.map.removeControl(this.drawControl);
            this.drawControl = null;
        }
        this.clearPolygon();
    }

    /**
     * GeoJSON dosyasından polygon yükle
     */
    loadGeoJSON(geojsonData) {
        try {
            const data = typeof geojsonData === 'string' ? JSON.parse(geojsonData) : geojsonData;

            this.clearPolygon();

            const geoLayer = L.geoJSON(data, {
                style: {
                    color: '#f59e0b',
                    fillColor: '#f59e0b',
                    fillOpacity: 0.2,
                    weight: 3,
                },
            });

            geoLayer.eachLayer((layer) => {
                this.drawnItems.addLayer(layer);
                this.currentPolygon = layer;
            });

            // Fit bounds to polygon
            this.map.fitBounds(this.drawnItems.getBounds(), { padding: [50, 50] });

            // Calculate area and centroid
            const geojson = this._getGeoJSON();
            const area = this._calculateArea();
            const centroid = this._calculateCentroid(geojson);

            this.onGeometryChange(geojson, centroid);
            this.onAreaCalculated(area);

            return { geojson, area, centroid };
        } catch (error) {
            console.error('GeoJSON parse hatası:', error);
            return null;
        }
    }

    /**
     * Mevcut polygon'u GeoJSON olarak al
     */
    getGeoJSON() {
        return this._getGeoJSON();
    }

    /**
     * Polygon'u temizle
     */
    clearPolygon() {
        this.drawnItems.clearLayers();
        this.currentPolygon = null;
    }

    /**
     * POI noktalarını haritada göster
     */
    showPois(pois) {
        this.poiLayer.clearLayers();

        const poiIcons = {
            education: { emoji: '🏫', color: '#10b981' },
            health: { emoji: '🏥', color: '#ef4444' },
            food_social: { emoji: '🍽️', color: '#8b5cf6' },
            shopping: { emoji: '🛒', color: '#f59e0b' },
            transport: { emoji: '🚌', color: '#3b82f6' },
            green_leisure: { emoji: '🌳', color: '#22c55e' },
            daily_need: { emoji: '🏪', color: '#6366f1' },
        };

        pois.forEach((poi) => {
            const config = poiIcons[poi.poi_kategorisi] ||
                poiIcons[poi.kategori] || { emoji: '📍', color: '#6b7280' };

            const icon = L.divIcon({
                className: 'poi-marker',
                html: `<div style="
                    background: ${config.color};
                    width: 28px; height: 28px;
                    border-radius: 50%;
                    border: 2px solid white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 14px;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
                    cursor: pointer;
                ">${config.emoji}</div>`,
                iconSize: [28, 28],
                iconAnchor: [14, 14],
            });

            const marker = L.marker([poi.lat, poi.lng], { icon });
            marker.bindPopup(`
                <div class="p-2 text-sm">
                    <strong>${poi.poi_adi || poi.name}</strong><br>
                    <span class="text-gray-500">${poi.poi_kategorisi || poi.kategori}</span>
                    ${poi.mesafe_m ? `<br><span class="text-blue-600">${poi.mesafe_m}m</span>` : ''}
                </div>
            `);

            this.poiLayer.addLayer(marker);
        });
    }

    /**
     * POI noktalarını temizle
     */
    clearPois() {
        this.poiLayer.clearLayers();
    }

    /**
     * Radius circle çiz (belirtilen koordinat etrafında)
     */
    showRadius(lat, lng, radiusMeters = 2000) {
        L.circle([lat, lng], {
            radius: radiusMeters,
            color: '#3b82f6',
            fillColor: '#3b82f6',
            fillOpacity: 0.05,
            weight: 1,
            dashArray: '5, 10',
        }).addTo(this.poiLayer);
    }

    // --- Private Methods ---

    _onPolygonCreated(layer) {
        this.clearPolygon();
        this.drawnItems.addLayer(layer);
        this.currentPolygon = layer;

        const geojson = this._getGeoJSON();
        const area = this._calculateArea();
        const centroid = this._calculateCentroid(geojson);

        this.onGeometryChange(geojson, centroid);
        this.onAreaCalculated(area);
    }

    _onPolygonEdited() {
        if (!this.currentPolygon) return;

        const geojson = this._getGeoJSON();
        const area = this._calculateArea();
        const centroid = this._calculateCentroid(geojson);

        this.onGeometryChange(geojson, centroid);
        this.onAreaCalculated(area);
    }

    _onPolygonDeleted() {
        this.currentPolygon = null;
        this.onGeometryChange(null, null);
        this.onAreaCalculated(0);
    }

    _getGeoJSON() {
        if (!this.currentPolygon) return null;

        const geojson = this.currentPolygon.toGeoJSON();
        return geojson.geometry || geojson;
    }

    _calculateArea() {
        if (!this.currentPolygon) return 0;

        const geojson = this.currentPolygon.toGeoJSON();
        const coords = geojson.geometry?.coordinates?.[0] || geojson.coordinates?.[0];

        if (!coords || coords.length < 3) return 0;

        // Shoelace formula for area in square meters (approximate)
        let area = 0;
        const n = coords.length;

        for (let i = 0; i < n - 1; i++) {
            const [lng1, lat1] = coords[i];
            const [lng2, lat2] = coords[i + 1];

            // Convert to meters (approximate at ~37° latitude for Bodrum)
            const x1 = lng1 * 111320 * Math.cos((lat1 * Math.PI) / 180);
            const y1 = lat1 * 110540;
            const x2 = lng2 * 111320 * Math.cos((lat2 * Math.PI) / 180);
            const y2 = lat2 * 110540;

            area += x1 * y2 - x2 * y1;
        }

        return Math.abs(area / 2);
    }

    _calculateCentroid(geojson) {
        if (!geojson) return null;

        const coords = geojson.coordinates?.[0];
        if (!coords || coords.length < 3) return null;

        let latSum = 0;
        let lngSum = 0;
        const count = coords.length;

        for (const [lng, lat] of coords) {
            latSum += lat;
            lngSum += lng;
        }

        return {
            lat: latSum / count,
            lng: lngSum / count,
        };
    }
}

// Global export
window.MapPolygonManager = MapPolygonManager;
