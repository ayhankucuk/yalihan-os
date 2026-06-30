/**
 * Site/Apartman Seçim Bileşeni - EmlakPro
 * İlan formunda site/apartman seçimini kolaylaştıran bileşen
 */

class SiteSelector {
    constructor(options = {}) {
        this.options = {
            selectorElementId: 'site-selector',
            apiEndpoint: (window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.sites && window.APIConfig.admin.sites.search)
                ? window.APIConfig.admin.sites.search
                : '/api/admin/sites/search',
            createEndpoint: (window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.sites && window.APIConfig.admin.sites.create)
                ? window.APIConfig.admin.sites.create
                : '/api/admin/sites/create',
            ...options,
        };

        this.container = document.getElementById(this.options.selectorElementId);
        this.selectedSite = null;
        this.listeners = {
            onSelect: [],
        };

        if (this.container) {
            this.init();
        } else {
            console.warn('Site selector container bulunamadı:', this.options.selectorElementId);
        }
    }

    init() {
        this.createUI();
        this.bindEvents();
    }

    createUI() {
        // Ana site seçici konteyner
        this.container.innerHTML = `
            <div class="site-selector">
                <div class="relative">
                    <input type="text" id="site-search-input"
                        placeholder="Site/Apartman adı ara veya seç..."
                        class="w-full py-3 px-4 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white dark:shadow-none">

                    <button id="site-create-btn" type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-green-600 hover:text-green-800">
                        <i class="fas fa-plus-circle"></i>
                    </button>

                    <div id="site-search-results" class="absolute z-20 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg rounded-md border border-gray-300 dark:border-gray-600 max-h-60 overflow-y-auto hidden"></div>
                </div>

                <div id="selected-site-info" class="mt-3 p-3 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-md hidden">
                    <div class="flex items-start">
                        <i class="fas fa-building text-blue-600 dark:text-blue-400 mr-3 mt-1"></i>
                        <div class="flex-1">
                            <div id="site-name" class="font-medium text-blue-800 dark:text-blue-200"></div>
                            <div id="site-address" class="text-sm text-blue-600 dark:text-blue-300 mt-1"></div>
                        </div>
                        <button id="remove-site" class="text-red-500 hover:text-red-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <input type="hidden" id="site_id" name="site_id">
            </div>
        `;

        // Yeni site ekleme modalı
        const modalHtml = `
            <div id="site-create-modal" class="fixed inset-0 bg-black bg-opacity-75 z-50 items-center justify-center hidden">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md mx-3">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                            <i class="fas fa-building mr-2 text-blue-600"></i>
                            Yeni Site/Apartman Ekle
                        </h3>
                        <button id="close-site-modal" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="px-6 py-4">
                        <form id="site-create-form">
                            <div class="mb-4">
                                <label for="new-site-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Site/Apartman Adı <span class="text-red-500">*</span></label>
                                <input type="text" id="new-site-name" required
                                    class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white dark:shadow-none">
                            </div>

                            <div class="mb-4">
                                <label for="new-site-address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Adres</label>
                                <textarea id="new-site-address"
                                    class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white dark:shadow-none" rows="3"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="mb-4">
                                    <label for="new-site-lat" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Enlem</label>
                                    <input type="number" id="new-site-lat" step="any"
                                        class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white dark:shadow-none">
                                </div>

                                <div class="mb-4">
                                    <label for="new-site-lng" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Boylam</label>
                                    <input type="number" id="new-site-lng" step="any"
                                        class="w-full py-2 px-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 text-sm dark:text-white dark:shadow-none">
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end space-x-3">
                                <button type="button" id="cancel-site-create"
                                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                                    Vazgeç
                                </button>
                                <button type="submit"
                                    class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors">
                                    <i class="fas fa-save mr-2"></i>
                                    Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;

        // Modal'ı body'ye ekle
        const modalContainer = document.createElement('div');
        modalContainer.innerHTML = modalHtml;
        document.body.appendChild(modalContainer);
    }

    bindEvents() {
        const searchInput = document.getElementById('site-search-input');
        const searchResults = document.getElementById('site-search-results');
        const createBtn = document.getElementById('site-create-btn');
        const createModal = document.getElementById('site-create-modal');
        const closeModal = document.getElementById('close-site-modal');
        const cancelCreate = document.getElementById('cancel-site-create');
        const createForm = document.getElementById('site-create-form');
        const removeBtn = document.getElementById('remove-site');

        // Debounced search
        let timeout = null;
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(timeout);
                const query = e.target.value.trim();

                if (query.length < 2) {
                    searchResults.classList.add('hidden');
                    return;
                }

                timeout = setTimeout(() => {
                    this.searchSites(query, searchResults);
                }, 300);
            });

            // Focus/blur olayları
            searchInput.addEventListener('focus', () => {
                if (searchInput.value.trim().length >= 2) {
                    searchResults.classList.remove('hidden');
                }
            });

            // Dışarıya tıklandığında sonuçları gizle
            document.addEventListener('click', (e) => {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.classList.add('hidden');
                }
            });
        }

        // Sonuç tıklama olayı
        if (searchResults) {
            searchResults.addEventListener('click', (e) => {
                const item = e.target.closest('.site-result-item');
                if (item) {
                    const siteId = item.dataset.id;
                    const siteName = item.dataset.name;
                    const siteAddress = item.dataset.address;
                    const lat = parseFloat(item.dataset.lat);
                    const lng = parseFloat(item.dataset.lng);

                    this.selectSite({
                        id: siteId,
                        name: siteName,
                        address: siteAddress,
                        lat: lat,
                        lng: lng,
                    });

                    searchResults.classList.add('hidden');
                    if (searchInput) searchInput.value = siteName;
                }
            });
        }

        // Yeni Site ekleme butonu
        if (createBtn) {
            createBtn.addEventListener('click', () => {
                // Mevcut değerleri forma doldur
                const nameInput = document.getElementById('new-site-name');
                if (nameInput && searchInput) {
                    nameInput.value = searchInput.value;
                }

                // Modal'ı aç
                if (createModal) {
                    createModal.classList.remove('hidden');
                    createModal.classList.add('flex');
                }
            });
        }

        // Modal kapatma butonları
        if (closeModal) {
            closeModal.addEventListener('click', () => {
                if (createModal) {
                    createModal.classList.add('hidden');
                    createModal.classList.remove('flex');
                }
            });
        }

        if (cancelCreate) {
            cancelCreate.addEventListener('click', () => {
                if (createModal) {
                    createModal.classList.add('hidden');
                    createModal.classList.remove('flex');
                }
            });
        }

        // Form submit
        if (createForm) {
            createForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.createNewSite();
            });
        }

        // Seçili site temizleme
        if (removeBtn) {
            removeBtn.addEventListener('click', () => {
                this.clearSelection();
            });
        }
    }

    async searchSites(query, resultsContainer) {
        if (!resultsContainer) return;

        // Loading durumu göster
        resultsContainer.innerHTML =
            '<div class="p-3 text-center text-gray-500 dark:text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i> Aranıyor...</div>';
        resultsContainer.classList.remove('hidden');

        try {
            // API ile arama yap
            const response = await fetch(
                `${this.options.apiEndpoint}?q=${encodeURIComponent(query)}`
            );
            const data = await response.json();

            if (data.success && data.sites && data.sites.length > 0) {
                // Sonuçları göster
                resultsContainer.innerHTML = '';

                data.sites.forEach((site) => {
                    const resultItem = document.createElement('div');
                    resultItem.className =
                        'site-result-item p-3 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-200 dark:border-gray-700 last:border-b-0';
                    resultItem.dataset.id = site.id;
                    resultItem.dataset.name = site.name;
                    resultItem.dataset.address = site.address || '';
                    resultItem.dataset.lat = site.latitude || '';
                    resultItem.dataset.lng = site.longitude || '';

                    resultItem.innerHTML = `
                        <div class="flex items-start">
                            <div class="mr-3 text-blue-600 dark:text-blue-400">
                                <i class="fas fa-building"></i>
                            </div>
                            <div>
                                <div class="font-medium">${site.name}</div>
                                ${site.address ? `<div class="text-xs text-gray-500 dark:text-gray-400 mt-1">${site.address}</div>` : ''}
                            </div>
                        </div>
                    `;

                    resultsContainer.appendChild(resultItem);
                });
            } else {
                // Sonuç yok mesajı
                resultsContainer.innerHTML = `
                    <div class="p-3 text-center">
                        <div class="text-gray-500 dark:text-gray-400">Sonuç bulunamadı</div>
                        <button id="create-new-site-btn" class="mt-2 px-3 py-1 text-xs bg-green-600 text-white rounded-full hover:bg-green-700 transition-colors">
                            <i class="fas fa-plus mr-1"></i> Yeni Site Ekle
                        </button>
                    </div>
                `;

                // Yeni site ekleme butonu olayı
                const newSiteBtn = document.getElementById('create-new-site-btn');
                if (newSiteBtn) {
                    newSiteBtn.addEventListener('click', () => {
                        // Mevcut araması modalda göster
                        const nameInput = document.getElementById('new-site-name');
                        const searchInput = document.getElementById('site-search-input');
                        if (nameInput && searchInput) {
                            nameInput.value = searchInput.value;
                        }

                        // Modal'ı aç
                        const createModal = document.getElementById('site-create-modal');
                        if (createModal) {
                            createModal.classList.remove('hidden');
                            resultsContainer.classList.add('hidden');
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Site arama hatası:', error);
            resultsContainer.innerHTML =
                '<div class="p-3 text-center text-red-500 dark:text-red-400">Arama sırasında bir hata oluştu</div>';
        }
    }

    async createNewSite() {
        const nameInput = document.getElementById('new-site-name');
        const addressInput = document.getElementById('new-site-address');
        const latInput = document.getElementById('new-site-lat');
        const lngInput = document.getElementById('new-site-lng');
        const createModal = document.getElementById('site-create-modal');

        if (!nameInput || !addressInput) return;

        const siteName = nameInput.value.trim();
        if (!siteName) {
            alert('Site/Apartman adı gerekli');
            return;
        }

        try {
            // API ile yeni site oluştur
            const response = await fetch(this.options.createEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') || '',
                },
                body: JSON.stringify({
                    name: siteName,
                    address: addressInput.value.trim(),
                    latitude: latInput?.value || null,
                    longitude: lngInput?.value || null,
                }),
            });

            const data = await response.json();

            if (data.success && data.site) {
                // Başarılı oluşturma
                this.selectSite({
                    id: data.site.id,
                    name: data.site.name,
                    address: data.site.address,
                    lat: data.site.latitude,
                    lng: data.site.longitude,
                });

                // Formu temizle
                nameInput.value = '';
                addressInput.value = '';
                if (latInput) latInput.value = '';
                if (lngInput) lngInput.value = '';

                // Modal'ı kapat
                if (createModal) {
                    createModal.classList.add('hidden');
                }

                // Başarılı mesajı göster
                this.showToast('Site/Apartman başarıyla oluşturuldu', 'success');
            } else {
                // Hata mesajı
                this.showToast(data.message || 'Site oluşturulurken bir hata oluştu', 'error');
            }
        } catch (error) {
            console.error('Site oluşturma hatası:', error);
            this.showToast('Site oluşturulurken bir hata oluştu', 'error');
        }
    }

    selectSite(site) {
        if (!site || !site.id) return;

        // Site ID'sini gizli alana kaydet
        const siteIdInput = document.getElementById('site_id');
        if (siteIdInput) {
            siteIdInput.value = site.id;
        }

        // Site bilgilerini göster
        const siteInfoContainer = document.getElementById('selected-site-info');
        const siteNameElement = document.getElementById('site-name');
        const siteAddressElement = document.getElementById('site-address');

        if (siteInfoContainer && siteNameElement) {
            siteNameElement.textContent = site.name;
            if (siteAddressElement) {
                siteAddressElement.textContent = site.address || '';
            }

            siteInfoContainer.classList.remove('hidden');
        }

        // Seçilen site'ı kaydet
        this.selectedSite = site;

        // Event dinleyicileri çağır
        this.listeners.onSelect.forEach((callback) => callback(site));
    }

    clearSelection() {
        // Site ID'sini temizle
        const siteIdInput = document.getElementById('site_id');
        if (siteIdInput) {
            siteIdInput.value = '';
        }

        // Site bilgilerini gizle
        const siteInfoContainer = document.getElementById('selected-site-info');
        if (siteInfoContainer) {
            siteInfoContainer.classList.add('hidden');
        }

        // Arama alanını temizle
        const searchInput = document.getElementById('site-search-input');
        if (searchInput) {
            searchInput.value = '';
        }

        // Seçilen site'ı temizle
        this.selectedSite = null;

        // Event dinleyicileri çağır
        this.listeners.onSelect.forEach((callback) => callback(null));
    }

    onSiteSelect(callback) {
        if (typeof callback === 'function') {
            this.listeners.onSelect.push(callback);
        }
        return this;
    }

    setSelected(siteId) {
        // API ile site detaylarını al ve seç
        if (siteId) {
            fetch(`${this.options.apiEndpoint}/${siteId}`)
                .then((response) => response.json())
                .then((data) => {
                    if (data.success && data.site) {
                        this.selectSite(data.site);

                        // Arama alanını güncelle
                        const searchInput = document.getElementById('site-search-input');
                        if (searchInput) {
                            searchInput.value = data.site.name;
                        }
                    }
                })
                .catch((error) => {
                    console.error('Site detayı alınırken hata:', error);
                });
        }
    }

    showToast(message, type = 'info') {
        // Basit toast notification göster
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 z-50 px-5 py-3 rounded-md shadow-lg transition-opacity duration-300 ${type === 'success' ? 'bg-green-600 text-white' : type === 'error' ? 'bg-red-600 text-white' : 'bg-blue-600 text-white'}`;
        toast.innerHTML = message;

        document.body.appendChild(toast);

        // 3 saniye sonra kaldır
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(toast);
            }, 300);
        }, 3000);
    }
}

// Global nesne olarak tanımla
window.SiteSelector = SiteSelector;
