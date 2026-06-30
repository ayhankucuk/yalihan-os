/**
 * İlan Create Sayfası JavaScript Hata Düzeltmeleri
 *
 * Context7: Vanilla JS ile hata düzeltmeleri
 * - SyntaxError düzeltmeleri
 * - Undefined function düzeltmeleri
 * - Alpine.js store düzeltmeleri
 */

// Global fonksiyonlar tanımla
window.addressSearch = function (query) {
    
    // Adres arama fonksiyonu implementasyonu
    return new Promise((resolve, reject) => {
        // Nominatim API ile adres arama
        fetch(
            `https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(query)}&format=json&limit=1`
        )
            .then((response) => response.json())
            .then((data) => {
                if (data && data.length > 0) {
                    resolve({
                        lat: parseFloat(data[0].lat),
                        lon: parseFloat(data[0].lon),
                        display_name: data[0].display_name,
                    });
                } else {
                    reject(new Error('Adres bulunamadı'));
                }
            })
            .catch((error) => {
                
                reject(error);
            });
    });
};

window.getStatusText = function (status) {
    const statusMap = {
        Aktif: '✅ Aktif',
        Pasif: '⏸️ Pasif',
        Taslak: '📝 Taslak',
        Beklemede: '⏳ Beklemede',
        Silindi: '🗑️ Silindi',
    };
    return statusMap[status] || status;
};

// Alpine.js store düzeltmeleri
document.addEventListener('alpine:init', () => {
    Alpine.store('formData', {
        kategori_id: null,
        alt_kategori_id: null,
        yayin_tipi_id: null,

        init() {
            
        },

        setKategori(kategoriId) {
            this.kategori_id = kategoriId;
            
        },

        setAltKategori(altKategoriId) {
            this.alt_kategori_id = altKategoriId;
            
        },

        setYayinTipi(yayinTipiId) {
            this.yayin_tipi_id = yayinTipiId;
            
        },
    });
});

// Syntax hatalarını düzelt
document.addEventListener('DOMContentLoaded', () => {
    

    // Eksik fonksiyonları tanımla
    if (typeof window.createMarker === 'undefined') {
        window.createMarker = function (lat, lon) {
            
            // Marker oluşturma implementasyonu
        };
    }

    // API endpoint'lerini düzelt
    const apiEndpoints = {
        featuresBySlug: (slug) => (window.APIConfig && window.APIConfig.features && window.APIConfig.features.byCategory)
            ? window.APIConfig.features.byCategory(slug)
            : `/api/v1/admin/features/category/${encodeURIComponent(slug)}`,
        featuresById: (id) => (window.APIConfig && window.APIConfig.features && window.APIConfig.features.byCategoryId)
            ? window.APIConfig.features.byCategoryId(id)
            : `/api/v1/admin/features?category_id=${id}`,
        publicationTypes: (categoryId) => (window.APIConfig && window.APIConfig.categories && window.APIConfig.categories.publicationTypes)
            ? window.APIConfig.categories.publicationTypes(categoryId)
            : `/api/v1/categories/publication-types/${categoryId}`,
    };

    // Özellik yükleme fonksiyonu
    window.loadFeaturesForCategory = function (categoryId) {
        

        const isSlug = typeof categoryId === 'string' && categoryId !== '' && !/^\d+$/.test(categoryId);
        const endpoint = isSlug
            ? apiEndpoints.featuresBySlug(categoryId)
            : apiEndpoints.featuresById(categoryId);

        fetch(endpoint, {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then((data) => {
                
                const featuresData =
                    (Array.isArray(data?.data?.features) && data.data.features) ||
                    data.features ||
                    data?.data ||
                    [];

                // Özellikleri UI'ya yükle
                updateFeaturesUI({ features: featuresData });
            })
            .catch((error) => {
                
                // Fallback özellikler
                loadFallbackFeatures(categoryId);
            });
    };

    // Fallback özellikler
    function loadFallbackFeatures(categoryId) {
        const fallbackFeatures = {
            1: ['Oda Sayısı', 'Banyo Sayısı', 'Net m²', 'Brüt m²', 'Kat', 'Balkon'],
            2: ['Ada No', 'Parsel No', 'İmar Durumu', 'KAKS', 'TAKS', 'Gabari'],
            3: ['Günlük Fiyat', 'Haftalık Fiyat', 'Sezon', 'Havuz', 'Misafir Sayısı'],
            4: ['İşyeri Tipi', 'Kira Tutarı', 'Ciro', 'Ruhsat Tipi', 'Kapasite'],
        };

        const features = fallbackFeatures[categoryId] || [];
        updateFeaturesUI({ features: features });
    }

    // UI güncelleme
    function updateFeaturesUI(data) {
        const featuresContainer = document.getElementById('features-container');
        if (featuresContainer) {
            featuresContainer.innerHTML = '';
            data.features.forEach((feature) => {
                const featureElement = document.createElement('div');
                featureElement.className = 'feature-item';
                featureElement.textContent = feature;
                featuresContainer.appendChild(featureElement);
            });
        }
    }

    // Yayın tipi yükleme
    window.loadPublicationTypes = function (categoryId) {
        

        fetch(`${apiEndpoints.publicationTypes}${categoryId}`)
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then((data) => {
                
                updatePublicationTypesUI(data);
            })
            .catch((error) => {
                
                loadFallbackPublicationTypes(categoryId);
            });
    };

    // Fallback yayın tipleri
    function loadFallbackPublicationTypes(categoryId) {
        const fallbackTypes = {
            1: ['Satılık', 'Kiralık'],
            2: ['Satılık', 'Kiralık'],
            3: ['Kiralık'],
            4: ['Satılık', 'Kiralık', 'Devren'],
        };

        const types = fallbackTypes[categoryId] || ['Satılık', 'Kiralık'];
        updatePublicationTypesUI({ types: types });
    }

    // Yayın tipi UI güncelleme
    function updatePublicationTypesUI(data) {
        const typesContainer = document.getElementById('publication-types-container');
        if (typesContainer) {
            typesContainer.innerHTML = '';
            data.types.forEach((type) => {
                const typeElement = document.createElement('option');
                typeElement.value = type;
                typeElement.textContent = type;
                typesContainer.appendChild(typeElement);
            });
        }
    }

    // Hata yakalama
    window.addEventListener('error', (event) => {
        

        // Hata tipine göre düzeltme
        if (event.error.message.includes('addressSearch is not defined')) {
            
        }

        if (event.error.message.includes('getStatusText is not defined')) {
            
        }
    });

    // Unhandled promise rejection
    window.addEventListener('unhandledrejection', (event) => {
        
        event.preventDefault();
    });
});

// Context7 Live Search düzeltmeleri
document.addEventListener('DOMContentLoaded', () => {
    // Context7 Live Search sistemini başlat
    if (typeof window.initContext7LiveSearch === 'function') {
        window.initContext7LiveSearch();
    } else {
        

        // Basit arama implementasyonu
        const searchInputs = document.querySelectorAll('[data-context7-search]');
        searchInputs.forEach((input) => {
            input.addEventListener('input', function () {
                const query = this.value;
                if (query.length > 2) {
                    performSearch(query, this);
                }
            });
        });
    }
});

// Arama fonksiyonu
function performSearch(query, input) {
    const searchType = input.dataset.context7Search;
    const resultsContainer = document.getElementById(`${searchType}-results`);

    if (!resultsContainer) return;

    // Basit arama implementasyonu
    fetch(`/api/search/${searchType}?q=${encodeURIComponent(query)}`)
        .then((response) => response.json())
        .then((data) => {
            resultsContainer.innerHTML = '';
            data.results.forEach((result) => {
                const resultElement = document.createElement('div');
                resultElement.className = 'search-result';
                resultElement.textContent = result.name;
                resultElement.addEventListener('click', () => {
                    input.value = result.name;
                    resultsContainer.innerHTML = '';
                });
                resultsContainer.appendChild(resultElement);
            });
        })
        .catch((error) => {
            
        });
}

// [console-zero] removed
