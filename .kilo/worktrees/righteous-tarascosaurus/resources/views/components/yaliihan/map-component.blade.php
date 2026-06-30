@props([
    'center' => ['lat' => 37.034407, 'lng' => 27.430540], // Default: Bodrum
    'zoom' => 15,
    'markers' => [],
    'height' => '400px',
    'showTraffic' => false, // Google Maps legacy (kept for API compat)
    'showTransit' => false,
    'showBicycling' => false,
    'poiProfile' => 'default'
])

<div {{ $attributes->merge(['class' => 'w-full rounded-2xl overflow-hidden bg-gray-100 dark:bg-gray-800 shadow-md border border-gray-200 dark:border-gray-700 dark:shadow-none']) }}>
    <!-- Map Container -->
    <div id="property-map" style="height: {{ $height }}; z-index: 1;"></div>

    <!-- POI Controls (Optional Overlay) -->
    <div class="px-4 py-3 bg-white dark:bg-slate-900 border-t border-gray-200 dark:border-slate-800 flex justify-between items-center dark:border-slate-700">
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Yakın Çevre</span>
        </div>
        <div id="radius-controls" class="flex gap-2">
             <button type="button" data-radius="500"
                    class="radius-btn px-3 py-1 text-xs font-medium rounded-full border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors dark:border-slate-700">
                500m
            </button>
            <button type="button" data-radius="1000"
                    class="radius-btn px-3 py-1 text-xs font-medium rounded-full border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors dark:border-slate-700">
                1km
            </button>
            <button type="button" data-radius="2000"
                    class="radius-btn px-3 py-1 text-xs font-medium rounded-full border border-blue-500 bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:border-blue-400 dark:text-blue-300 transition-colors">
                2km
            </button>
        </div>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<style>
    .leaflet-container {
        font-family: inherit;
    }
    .custom-marker-icon {
        background: transparent;
        border: none;
    }
</style>
@endpush

@push('scripts')
<x-csp-script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" />
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Map
        const map = L.map('property-map').setView([{{ $center['lat'] }}, {{ $center['lng'] }}], {{ $zoom }});

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
        }).addTo(map);

        // Add Main Maker
        const mainMarkerConfig = @json($markers[0] ?? ['title' => 'Konum']);

        const mainIcon = L.divIcon({
            className: 'custom-marker-icon',
            html: `<div class="w-10 h-10 bg-blue-600 rounded-full border-4 border-white shadow-lg flex items-center justify-center text-white text-xl">
                    <i class="fas fa-home"></i>
                   </div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        const marker = L.marker([{{ $center['lat'] }}, {{ $center['lng'] }}], { icon: mainIcon }).addTo(map);
        marker.bindPopup(`<b>${mainMarkerConfig.title}</b>`).openPopup();

        // POI Logic (Similar to existing show.blade.php but modular)
        const radiusBtns = document.querySelectorAll('.radius-btn');
        let currentRadius = 2000;
        let poiCircle = L.circle([{{ $center['lat'] }}, {{ $center['lng'] }}], {
            color: '#3b82f6',
            fillColor: '#3b82f6',
            fillOpacity: 0.1,
            radius: 2000
        }).addTo(map);

        radiusBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                // Update active state
                radiusBtns.forEach(b => {
                    b.classList.remove('border-blue-500', 'bg-blue-50', 'text-blue-600', 'dark:bg-blue-900/30', 'dark:border-blue-400', 'dark:text-blue-300');
                    b.classList.add('border-gray-200 dark:border-slate-700', 'dark:border-gray-600');
                });
                e.target.classList.remove('border-gray-200 dark:border-slate-700', 'dark:border-gray-600');
                e.target.classList.add('border-blue-500', 'bg-blue-50', 'text-blue-600', 'dark:bg-blue-900/30', 'dark:border-blue-400', 'dark:text-blue-300');

                // Update map
                const r = parseInt(e.target.dataset.radius);
                currentRadius = r;
                poiCircle.setRadius(r);
                map.fitBounds(poiCircle.getBounds());

                // Trigger event for other components (e.g. POI list)
                const event = new CustomEvent('poi-radius-changed', { detail: { radius: r } });
                document.dispatchEvent(event);
            });
        });
    });
</script>
@endpush
