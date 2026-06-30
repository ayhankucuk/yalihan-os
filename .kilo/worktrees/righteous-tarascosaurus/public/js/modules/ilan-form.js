/**
 * İlan Form JavaScript - EmlakPro
 * ---
 * Bu modül, ilan ekleme ve düzenleme sayfalarında kullanılan ortak
 * JavaScript fonksiyonlarını içerir. Kod, camelCase isimlendirme
 * standardına ve EmlakPro projesi global kurallarına uygun olarak yazılmıştır.
 */

(function () {
    'use strict';

    // DOM yüklendiğinde çalışacak kod
    document.addEventListener('DOMContentLoaded', function () {
        setupCsrfToken();
        initSelect2();
        setupLocationSelectors();
        setupFiyatFormatting();
        setupPhotoUpload();
        // Initialize map if container exists
        if (document.getElementById('map-container')) {
            initMap();
        } else {
            console.log('ℹ️ Map container not found, skipping map initialization');
        }
        setupFormValidation();
    });

    /**
     * CSRF token ayarları
     */
    function setupCsrfToken() {
        let token = document.head.querySelector('meta[name="csrf-token"]');
        if (token) {
            window.__csrfToken = token.content;
        }
    }

    /**
     * Select2 kütüphanesini başlat
     */
    function initSelect2() {
        // Temel select2 konfigürasyonu
        if (typeof $.fn !== 'undefined' && $.fn.select2) {
        window.$('.select2-basic').select2({
            theme: 'classic',
            language: 'tr',
            width: '100%',
            placeholder: 'Seçiniz...',
            allowClear: true,
        });

        // Danışman seçimi için özel select2
        window.$('#danisman_id').select2({
            theme: 'classic',
            language: 'tr',
            width: '100%',
            placeholder: 'Danışman Seçin',
            allowClear: true,
        });

        // Proje seçimi için özel select2
        window.$('#proje_id').select2({
            theme: 'classic',
            language: 'tr',
            width: '100%',
            placeholder: 'Proje Seçin',
            allowClear: true,
        });
        }
    }

    /**
     * İl, İlçe, Mahalle seçicilerini ayarla
     */
    function setupLocationSelectors() {
        const ilSelect = document.getElementById('il_select');
        const ilceSelect = document.getElementById('ilce_select');
        const mahalleSelect = document.getElementById('mahalle_select');

        // İl değişiminde ilçeleri getir
        ilSelect && ilSelect.addEventListener('change', function () {
            const selectedIl = ilSelect.value;
            ilceSelect.innerHTML = '<option value="">-- İlçe Seçin --</option>';
            ilceSelect.disabled = true;
            mahalleSelect.innerHTML = '<option value="">-- Önce ilçe seçin --</option>';
            mahalleSelect.disabled = true;

            if (selectedIl) {
                const params = new URLSearchParams({ il: selectedIl });
                const headers = {};
                if (window.__csrfToken) headers['X-CSRF-TOKEN'] = window.__csrfToken;
                fetch(`/api/location/ilceler?${params.toString()}`, { method: 'GET', headers })
                    .then((res) => res.json())
                    .then(function (response) {
                        if (response.data && response.data.length > 0) {
                            const selectedIlceId = ilceSelect.dataset.selected;
                            response.data.forEach(function (ilce) {
                                const opt = new Option(ilce.ilce_adi, ilce.ilce_adi);
                                if (selectedIlceId && String(selectedIlceId) == String(ilce.id)) {
                                    opt.selected = true;
                                }
                                ilceSelect.append(opt);
                            });
                            ilceSelect.disabled = false;
                            triggerChanged(ilceSelect)
                        }
                    })
                    .catch(function (err) {
                        console.error('İlçe verileri alınırken hata oluştu:', err);
                    });
            }
        });

        // İlçe değişiminde mahalleleri getir
        ilceSelect && ilceSelect.addEventListener('change', function () {
            const selectedIlce = ilceSelect.value;
            const selectedIl = ilSelect ? ilSelect.value : '';
            mahalleSelect.innerHTML = '<option value="">-- Mahalle Seçin --</option>';
            mahalleSelect.disabled = true;

            if (selectedIl && selectedIlce) {
                const params = new URLSearchParams({ il: selectedIl, ilce: selectedIlce });
                const headers = {};
                if (window.__csrfToken) headers['X-CSRF-TOKEN'] = window.__csrfToken;
                fetch(`/api/location/mahalleler?${params.toString()}`, { method: 'GET', headers })
                    .then((res) => res.json())
                    .then(function (response) {
                        if (response.data && response.data.length > 0) {
                            const selectedMahalleId = mahalleSelect.dataset.selected;
                            response.data.forEach(function (mahalle) {
                                const opt = new Option(mahalle.mahalle_adi, mahalle.mahalle_adi);
                                if (selectedMahalleId && String(selectedMahalleId) == String(mahalle.id)) {
                                    opt.selected = true;
                                }
                                mahalleSelect.append(opt);
                            });
                            mahalleSelect.disabled = false;
                            triggerChanged(mahalleSelect)
                        }
                    })
                    .catch(function (err) {
                        console.error('Mahalle verileri alınırken hata oluştu:', err);
                    });
            }
        });

        // Sayfa yüklendiğinde, eğer il seçili ise ilçeleri yükle
        if (ilSelect && ilSelect.value) { triggerChanged(ilSelect) }
    }

    /**
     * Fiyat formatlaması
     */
    function setupFiyatFormatting() {
        const fiyatDisplay = document.getElementById('fiyat_display');
        const fiyatInput = document.getElementById('fiyat');

        if (fiyatDisplay && fiyatInput) {
            // Ekranda formatlanmış gösterimi
            fiyatDisplay.addEventListener('input', function (e) {
                // Sadece sayıları al
                let value = this.value.replace(/[^\d]/g, '');

                // Boş değilse, formatla
                if (value) {
                    // Binlik ayraç ile formatla
                    value = parseInt(value, 10).toLocaleString('tr-TR');
                    this.value = value;
                }

                // Gerçek değeri hidden input'a aktar
                fiyatInput.value = this.value.replace(/[^\d]/g, '');
            });

            // Başlangıçta formatla
            if (fiyatDisplay.value) {
                const numValue = parseInt(fiyatDisplay.value.replace(/[^\d]/g, ''), 10);
                if (!isNaN(numValue)) {
                    fiyatDisplay.value = numValue.toLocaleString('tr-TR');
                }
            }
        }
    }

    /**
     * Fotoğraf yükleme işlemleri
     */
    function setupPhotoUpload() {
        const fileInput = document.getElementById('fotograflar');
        const dropzone = document.getElementById('dropzone');
        const previewGrid = document.getElementById('preview-grid');

        if (fileInput && dropzone) {
            // Sürükle-bırak olayları
            dropzone.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('border-indigo-500');
            });

            dropzone.addEventListener('dragleave', function () {
                this.classList.remove('border-indigo-500');
            });

            dropzone.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('border-indigo-500');

                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    handleFileSelect(e.dataTransfer.files);
                }
            });

            // Dosya seçme olayı
            fileInput.addEventListener('change', function (e) {
                // Önizleme alanını temizle
                if (previewGrid) {
                    previewGrid.innerHTML = '';
                }

                handleFileSelect(this.files);
            });

            // "Dosya Seç" butonuna tıklama
            dropzone.addEventListener('click', function () {
                fileInput.click();
            });
        }

        // Kapak fotoğrafı seçme (düzenleme sayfasında)
        setupCoverPhotoSelection();
    }

    /**
     * Dosya seçimi işleme
     */
    function handleFileSelect(files) {
        const previewGrid = document.getElementById('preview-grid');

        if (!previewGrid || !files.length) return;

        // Dosya boyutu kontrolü
        const maxFileSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

        for (let i = 0; i < files.length; i++) {
            const file = files[i];

            // Dosya tipi kontrolü
            if (!allowedTypes.includes(file.type)) {
                alert(
                    `"${file.name}" desteklenmeyen bir dosya formatıdır. Lütfen JPG veya PNG dosyaları yükleyin.`
                );
                continue;
            }

            // Dosya boyutu kontrolü
            if (file.size > maxFileSize) {
                alert(
                    `"${file.name}" dosyası çok büyük (${Math.round(file.size / 1024 / 1024)}MB). Maksimum dosya boyutu 5MB'dir.`
                );
                continue;
            }

            // Dosya önizleme
            const reader = new FileReader();

            reader.onload = function (e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'photo-upload__preview-item';

                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Önizleme">
                    <div class="photo-upload__actions">
                        <button type="button" class="p-1 bg-white rounded-full shadow hover:bg-gray-100 dark:bg-slate-900 dark:shadow-none" title="Kapak Fotoğrafı Yap">
                            <svg class="w-4 h-4 text-gray-700 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                            </svg>
                        </button>
                        <button type="button" class="p-1 bg-white rounded-full shadow hover:bg-red-100 dark:bg-slate-900 dark:shadow-none" title="Kaldır">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                `;

                previewGrid.appendChild(previewItem);
            };

            reader.readAsDataURL(file);
        }
    }

    /**
     * Kapak fotoğrafı seçme işlemleri (düzenleme sayfasında)
     */
    function setupCoverPhotoSelection() {
        // Mevcut kapak fotoğrafı radyo butonları
        document.addEventListener('change', function (e) {
            const target = e.target;
            if (target && target.matches('input[name="kapak_fotografi"]')) {
                document.querySelectorAll('.photo-cover-badge').forEach((el) => el.classList.add('hidden'));
                const selectedId = target.value;
                const badge = document.querySelector(`#photo-${selectedId} .photo-cover-badge`);
                badge && badge.classList.remove('hidden');
            }
        });

        // Mevcut fotoğraf silme işlemi
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.photo-delete-btn');
            if (!btn) return;
            const photoId = btn.getAttribute('data-photo-id');
            const photoItem = document.querySelector(`#photo-${photoId}`);

            if (confirm('Bu fotoğrafı silmek istediğinize emin misiniz?')) {
                // Silme işareti ekle
                photoItem && photoItem.classList.add('opacity-50');

                // Hidden input ekle
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'sil_fotograflar[]';
                input.value = photoId;
                document.querySelector('form').appendChild(input);

                // Eğer kapak fotoğrafı ise, silme işaretini kaldır
                const kapak = document.getElementById(`kapak_${photoId}`);
                if (kapak && kapak.checked) {
                    kapak.checked = false;
                    document.querySelectorAll('.photo-cover-badge').forEach((el) => el.classList.add('hidden'));
                }
            }
        });
    }

    /**
     * Harita başlatma
     */
    function initMap() {
        // Map loading göster
        const mapLoading = document.getElementById('map-loading');
        mapLoading && mapLoading.classList.remove('hidden');
        mapLoading && mapLoading.classList.add('flex');

        // Mevcut koordinatları al
        const latitudeInput = document.getElementById('latitude');
        const longitudeInput = document.getElementById('longitude');

        if (!latitudeInput || !longitudeInput) return;

        const latitude = parseFloat(latitudeInput.value) || 38.4237;
        const longitude = parseFloat(longitudeInput.value) || 27.1428;

        // Harita oluştur
        const map = L.map('map', {
            center: [latitude, longitude],
            zoom: 12,
            scrollWheelZoom: false,
        });

        // OSM katmanı ekle
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution:
                '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        }).addTo(map);

        // Marker ekle
        const marker = L.marker([latitude, longitude], {
            draggable: true,
        }).addTo(map);

        // Marker sürükleme olayları
        marker.on('dragend', function (e) {
            const position = marker.getLatLng();
            latitudeInput.value = position.lat.toFixed(6);
            longitudeInput.value = position.lng.toFixed(6);

            // Haritayı yeni konuma merkezle
            map.panTo(position);
        });

        // Map loading gizle
        mapLoading && mapLoading.classList.remove('flex');
        mapLoading && mapLoading.classList.add('hidden');

        // Adres arama butonu
        const searchBtn = document.getElementById('map-search-btn');
        searchBtn && searchBtn.addEventListener('click', function () { searchAddress(); });

        // Enter tuşu ile arama
        const searchInput = document.getElementById('map-search-input');
        searchInput && searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); searchAddress(); }
        });

        // Harita sıfırlama butonu
        const resetBtn = document.getElementById('map-reset-btn');
        resetBtn && resetBtn.addEventListener('click', function () {
            // Türkiye'nin merkezi
            const defaultLat = 38.4237;
            const defaultLng = 27.1428;

            // Konum bilgilerini güncelle
            latitudeInput.value = defaultLat;
            longitudeInput.value = defaultLng;

            // Marker ve haritayı güncelle
            marker.setLatLng([defaultLat, defaultLng]);
            map.setView([defaultLat, defaultLng], 6);
        });

        // Adres arama fonksiyonu
        function searchAddress() {
            const searchValue = document.getElementById('map-search-input')?.value;

            if (!searchValue) return;

            // Loading göster
            mapLoading && mapLoading.classList.remove('hidden');
            mapLoading && mapLoading.classList.add('flex');

            // Nominatim API ile adres ara
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchValue)}`)
                .then((res) => res.json())
                .then(function (data) {
                    if (data && data.length > 0) {
                        const location = data[0];
                        const lat = parseFloat(location.lat);
                        const lng = parseFloat(location.lon);

                        // Konum bilgilerini güncelle
                        latitudeInput.value = lat.toFixed(6);
                        longitudeInput.value = lng.toFixed(6);

                        // Marker ve haritayı güncelle
                        marker.setLatLng([lat, lng]);
                        map.setView([lat, lng], 14);
                    } else {
                        alert('Aranan adres bulunamadı.');
                    }

                    // Loading gizle
                    mapLoading && mapLoading.classList.remove('flex');
                    mapLoading && mapLoading.classList.add('hidden');
                })
                .catch(function () {
                    alert('Adres arama sırasında bir hata oluştu.');
                    // Loading gizle
                    mapLoading && mapLoading.classList.remove('flex');
                    mapLoading && mapLoading.classList.add('hidden');
                });
        }
    }

    /**
     * Form doğrulama işlemleri
     */
    function setupFormValidation() {
        document.addEventListener('submit', function (e) {
            const form = e.target;
            if (!(form instanceof HTMLFormElement)) return;
            let hasError = false;

            // Zorunlu alanların doğrulaması
            const requiredFields = [
                { id: 'baslik', name: 'Başlık' },
                { id: 'fiyat', name: 'Fiyat' },
                { id: 'kategori', name: 'Emlak Kategorisi' },
                { id: 'il_select', name: 'İl' },
                { id: 'ilce_select', name: 'İlçe' },
                { id: 'mahalle_select', name: 'Mahalle' },
                { id: 'aciklama', name: 'Açıklama' },
            ];

            // Hata mesajlarını gizle
            document.querySelectorAll('.validation-error').forEach((el) => el.classList.add('hidden'));

            // Tüm zorunlu alanları kontrol et
            requiredFields.forEach(function (field) {
                const input = document.getElementById(field.id);
                if (!input || !input.value.trim()) {
                    const errEl = document.getElementById(`${field.id}_error`);
                    errEl && errEl.classList.remove('hidden');
                    hasError = true;
                }
            });

            // Fiyat kontrolü
            const fiyatInput = document.getElementById('fiyat');
            if (fiyatInput && (!fiyatInput.value || parseInt(fiyatInput.value) <= 0)) {
                const fErr = document.getElementById('fiyat_error');
                fErr && fErr.classList.remove('hidden');
                hasError = true;
            }

            // Hata varsa formu gönderme
            if (hasError) {
                e.preventDefault();

                // Sayfayı ilk hataya kaydır
                const firstError = document.querySelector('.validation-error:not(.hidden)');
                if (firstError) {
                    try {
                        const reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        firstError.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'center' });
                    } catch {
                        firstError.scrollIntoView();
                    }
                }

                return false;
            }

            return true;
        });
    }
    function triggerChanged(el) { if (!el) return; if (typeof $.fn !== 'undefined' && $.fn.select2) { window.$(el).trigger('change.select2') } else { el.dispatchEvent(new Event('change')) } }
})();
