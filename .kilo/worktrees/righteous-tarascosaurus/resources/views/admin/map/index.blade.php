@extends('admin.layouts.admin')

@section('title', 'Gayrimenkul Haritası')

@push('styles')
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

    <style>
        #map {
            height: 70vh;
            /* Ekran yüksekliğine göre ayarlandı */
            border-radius: 0.5rem;
        }

        .map-container {
            position: relative;
        }

        .popup-img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-top-left-radius: 0.375rem;
            border-top-right-radius: 0.375rem;
        }

        .leaflet-popup-content-wrapper {
            border-radius: 0.375rem;
            /* rounded-lg */
        }

        .leaflet-popup-content {
            margin: 0;
            width: 280px !important;
        }

        .custom-div-icon {
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.4);
            transition: transform 0.2s ease;
        }

        .custom-div-icon:hover {
            transform: scale(1.2);
        }
    </style>
@endpush

@section('content')
    <div class="content-header mb-6">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-800 dark:text-slate-200">
                        <i class="fas fa-map-marked-alt mr-2 text-blue-600"></i>
                        İlan Haritası
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-2">
                        Yayındaki ilanları harita üzerinde interstatus olarak görüntüleyin.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreler ve Harita -->
    <div class="container-fluid">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
            <!-- Filtreleme Paneli -->
            <div class="lg:col-span-1 bg-gray-50 dark:bg-slate-900 rounded-lg shadow-lg p-6 h-max">
                <h3
                    class="text-lg font-semibold text-gray-900 dark:text-white border-b border-gray-200 dark:border-slate-800 pb-3 mb-4 dark:border-slate-700 dark:text-slate-100">
                    Filtreler</h3>
                <div>
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-3 dark:text-slate-100">Kategoriler</h4>
                    <div class="space-y-2">
                        @forelse($kategoriler as $kategori)
                            <div class="flex items-center">
                                <input type="checkbox" id="category-{{ $kategori->slug }}"
                                    class="category-checkbox h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    data-category-slug="{{ $kategori->slug }}" checked>
                                <label for="category-{{ $kategori->slug }}"
                                    class="ml-3 text-sm text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                                    <span class="inline-block w-3 h-3 rounded-full mr-2"
                                        style="background-color: {{ $categoryColors[$kategori->slug] ?? '#6B7280' }};"></span>
                                    {{ $kategori->name }}
                                </label>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Filtrelenecek kategori bulunamadı.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Harita Alanı -->
            <div class="lg:col-span-3">
                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg shadow-lg overflow-hidden">
                    <div id="map"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Leaflet JS & Eklentileri --}}
    <x-csp-script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" />
    <x-csp-script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js" />

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapData = @json($mapData);
            const categoryColors = @json($categoryColors);

            if (!mapData || mapData.length === 0) {
                // Harita verisi yoksa, haritayı yine de göster ama bir uyarı ekle.
                const map = L.map('map').setView([39.9334, 32.8597], 6);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                }).addTo(map);
                // Ekrana bir mesaj basılabilir.
                document.getElementById('map').innerHTML =
                    '<div class="flex items-center justify-center h-full text-gray-500">Haritada gösterilecek ilan bulunamadı.</div>';
                return;
            }

            const map = L.map('map').setView([mapData[0].lat, mapData[0].lng], 10);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            const markers = L.markerClusterGroup();
            const categoryLayers = {};

            function createCustomIcon(color) {
                return L.divIcon({
                    html: `<span style="background-color: ${color}; width: 24px; height: 24px;" class="custom-div-icon"></span>`,
                    className: ''
                });
            }

            mapData.forEach(data => {
                const categorySlug = data.category.toLowerCase().replace(/\s+/g, '-'); // Basit slugify
                const color = data.color || '#6B7280';

                const icon = createCustomIcon(color);

                const marker = L.marker([data.lat, data.lng], {
                    icon: icon,
                    categorySlug: categorySlug
                });

                const popupContent = `
                <div>
                    <img src="${data.photo || 'https://via.placeholder.com/280x150?text=Foto+Yok'}" alt="${data.title}" class="popup-img"/>
                    <div class="p-4">
                        <h4 class="font-bold text-base mb-1">${data.title}</h4>
                        <p class="text-gray-600 text-sm mb-2">${data.address}</p>
                        <p class="font-semibold text-lg text-blue-600 mb-3">${data.price}</p>
                        <a href="${data.url}" target="_blank" class="block w-full text-center bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">Detayları Gör</a>
                    </div>
                </div>
            `;

                marker.bindPopup(popupContent);

                if (!categoryLayers[categorySlug]) {
                    categoryLayers[categorySlug] = L.layerGroup();
                }
                categoryLayers[categorySlug].addLayer(marker);
            });

            Object.values(categoryLayers).forEach(layer => markers.addLayer(layer));
            map.addLayer(markers);

            // Filtreleme mantığı
            const checkboxes = document.querySelectorAll('.category-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const slug = this.dataset.categorySlug;
                    if (categoryLayers[slug]) {
                        if (this.checked) {
                            markers.addLayer(categoryLayers[slug]);
                        } else {
                            markers.removeLayer(categoryLayers[slug]);
                        }
                    }
                });
            });

            // Haritayı tüm markerları içerecek şekilde ayarla
            if (markers.getBounds().isValid()) {
                map.fitBounds(markers.getBounds().pad(0.1));
            }
        });
    </script>
@endpush
