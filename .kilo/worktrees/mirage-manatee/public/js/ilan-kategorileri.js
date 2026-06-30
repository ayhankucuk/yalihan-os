/**
 * İlan Kategorileri için Cascading Dropdown Etkileşimleri
 *
 * Bu script, ilan kategorileri için 3 seviyeli (Ana Kategori -> Alt Kategori -> Yayın Tipi)
 * cascading dropdown sistemini kontrol eder.
 */

class IlanKategoriManager {
    constructor() {
        // DOM Elementleri
        this.anaKategoriSelect = document.getElementById('ana_kategori_id');
        this.altKategoriSelect = document.getElementById('alt_kategori_id');
        this.yayinTipiSelect = document.getElementById('yayin_tipi_id');
        this.altKategoriContainer = document.querySelector('.alt-kategori-container');
        this.yayinTipiContainer = document.querySelector('.yayin-tipi-container');
        this.kategoriForm = document.getElementById('kategoriForm');

        // Event Listener'ları ekle
        this.setupEventListeners();
    }

    /**
     * Event listener'ları ayarla
     */
    setupEventListeners() {
        // Ana kategori değiştiğinde
        if (this.anaKategoriSelect) {
            this.anaKategoriSelect.addEventListener('change', () => this.onAnaKategoriChange());
        }

        // Alt kategori değiştiğinde
        if (this.altKategoriSelect) {
            this.altKategoriSelect.addEventListener('change', () => this.onAltKategoriChange());
        }

        // Form gönderildiğinde doğrulama yap
        if (this.kategoriForm) {
            this.kategoriForm.addEventListener('submit', (e) => this.validateForm(e));
        }
    }

    /**
     * Ana kategori değiştiğinde alt kategorileri yükle
     */
    onAnaKategoriChange() {
        const anaKategoriId = this.anaKategoriSelect.value;

        // Ana kategori seçili değilse alt kategori ve yayın tipi alanlarını gizle
        if (!anaKategoriId) {
            this.hideAltKategori();
            this.hideYayinTipi();
            return;
        }

        // Alt kategorileri getir
        this.fetchAltKategoriler(anaKategoriId);
    }

    /**
     * Alt kategori değiştiğinde yayın tiplerini yükle
     */
    onAltKategoriChange() {
        const altKategoriId = this.altKategoriSelect.value;

        // Alt kategori seçili değilse yayın tipi alanını gizle
        if (!altKategoriId) {
            this.hideYayinTipi();
            return;
        }

        // Yayın tiplerini getir
        this.fetchYayinTipleri(altKategoriId);
    }

    /**
     * AJAX ile alt kategorileri getir (SSOT Endpoint)
     */
    fetchAltKategoriler(anaKategoriId) {
        // Alt kategori container'ı göster
        this.showAltKategori();

        // Yükleniyor göstergesi ekle
        this.setLoading(this.altKategoriSelect);

        // Yayın tipi container'ı gizle
        this.hideYayinTipi();

        // SSOT Endpoint: /api/v1/categories/sub/{parentId}
        fetch(`/api/v1/categories/sub/${anaKategoriId}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                this.populateAltKategoriler(data);
                this.clearLoading(this.altKategoriSelect);
            })
            .catch((error) => {
                console.error('Alt kategoriler yüklenirken hata oluştu:', error);
                this.clearLoading(this.altKategoriSelect);
            });
    }

    /**
     * AJAX ile yayın tiplerini getir (SSOT Endpoint)
     */
    fetchYayinTipleri(altKategoriId) {
        // Yayın tipi container'ı göster
        this.showYayinTipi();

        // Yükleniyor göstergesi ekle
        this.setLoading(this.yayinTipiSelect);

        // SSOT Endpoint: /api/v1/categories/publication-types/{categoryId}
        fetch(`/api/v1/categories/publication-types/${altKategoriId}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                this.populateYayinTipleri(data);
                this.clearLoading(this.yayinTipiSelect);
            })
            .catch((error) => {
                console.error('Yayın tipleri yüklenirken hata oluştu:', error);
                this.clearLoading(this.yayinTipiSelect);
            });
    }

    /**
     * Alt kategorileri dropdown'da göster (SSOT Response Format)
     */
    populateAltKategoriler(data) {
        // Dropdown'ı temizle
        this.altKategoriSelect.innerHTML = '<option value="">Alt Kategori Seçiniz</option>';

        // SSOT Response: data.subcategories array
        const subcategories = data?.data?.subcategories || data?.subcategories || [];

        // Gelen verileri ekle
        if (subcategories && subcategories.length > 0) {
            subcategories.forEach((kategori) => {
                const option = document.createElement('option');
                option.value = kategori.id;
                option.textContent = kategori.name || kategori.ad;
                this.altKategoriSelect.appendChild(option);
            });
        } else {
            // Hiç alt kategori yoksa bilgi mesajı göster
            const option = document.createElement('option');
            option.disabled = true;
            option.textContent = 'Bu kategoride alt kategori bulunmuyor';
            this.altKategoriSelect.appendChild(option);
        }
    }

    /**
     * Yayın tiplerini dropdown'da göster (SSOT Response Format)
     */
    populateYayinTipleri(data) {
        // Dropdown'ı temizle
        this.yayinTipiSelect.innerHTML = '<option value="">Yayın Tipi Seçiniz</option>';

        // SSOT Response: data.types array
        const types = data?.data?.types || data?.types || [];

        // Gelen verileri ekle
        if (types && types.length > 0) {
            types.forEach((yayinTipi) => {
                const option = document.createElement('option');
                option.value = yayinTipi.id;
                option.textContent = yayinTipi.name;
                this.yayinTipiSelect.appendChild(option);
            });
        } else {
            // Hiç yayın tipi yoksa bilgi mesajı göster
            const option = document.createElement('option');
            option.disabled = true;
            option.textContent = 'Bu kategoride yayın tipi bulunmuyor';
            this.yayinTipiSelect.appendChild(option);
        }
    }

    /**
     * Alt kategori alanını göster
     */
    showAltKategori() {
        if (this.altKategoriContainer) {
            this.altKategoriContainer.style.display = 'block';
        }
    }

    /**
     * Alt kategori alanını gizle
     */
    hideAltKategori() {
        if (this.altKategoriContainer) {
            this.altKategoriContainer.style.display = 'none';
        }
    }

    /**
     * Yayın tipi alanını göster
     */
    showYayinTipi() {
        if (this.yayinTipiContainer) {
            this.yayinTipiContainer.style.display = 'block';
        }
    }

    /**
     * Yayın tipi alanını gizle
     */
    hideYayinTipi() {
        if (this.yayinTipiContainer) {
            this.yayinTipiContainer.style.display = 'none';
        }
    }

    /**
     * Yükleniyor göstergesi ekle
     */
    setLoading(selectElement) {
        selectElement.disabled = true;
        const originalText = selectElement.querySelector('option:first-child').textContent;
        selectElement.querySelector('option:first-child').textContent = 'Yükleniyor...';

        // Değişiklik yapmak için orijinal metni saklayalım
        selectElement.dataset.originalText = originalText;
    }

    /**
     * Yükleniyor göstergesini kaldır
     */
    clearLoading(selectElement) {
        selectElement.disabled = false;
        if (selectElement.dataset.originalText) {
            selectElement.querySelector('option:first-child').textContent =
                selectElement.dataset.originalText;
            delete selectElement.dataset.originalText;
        }
    }

    /**
     * Form gönderilmeden önce doğrulama yap
     */
    validateForm(e) {
        let isValid = true;
        let errorMessage = '';

        // Ana kategori kontrolü
        if (!this.anaKategoriSelect.value) {
            isValid = false;
            errorMessage = 'Lütfen bir ana kategori seçin.';
        }
        // Alt kategori gösteriliyorsa kontrol et
        else if (
            this.altKategoriContainer.style.display !== 'none' &&
            !this.altKategoriSelect.value
        ) {
            isValid = false;
            errorMessage = 'Lütfen bir alt kategori seçin.';
        }
        // Yayın tipi gösteriliyorsa kontrol et
        else if (this.yayinTipiContainer.style.display !== 'none' && !this.yayinTipiSelect.value) {
            isValid = false;
            errorMessage = 'Lütfen bir yayın tipi seçin.';
        }

        // Geçersizse form gönderimini engelle ve hata mesajı göster
        if (!isValid) {
            e.preventDefault();
            this.showError(errorMessage);
        }
    }

    /**
     * Hata mesajı göster
     */
    showError(message) {
        // Eski hata mesajlarını temizle
        const existingErrors = document.querySelectorAll('.ilan-kategori-error');
        existingErrors.forEach((error) => error.remove());

        // Yeni hata mesajını oluştur
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-500 mt-2 ilan-kategori-error';
        errorDiv.textContent = message;

        // Formu bul ve altına mesajı ekle
        if (this.kategoriForm) {
            this.kategoriForm.appendChild(errorDiv);

            // 5 saniye sonra hata mesajını kaldır
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }
    }
}

// Sayfa yüklendiğinde işlemleri başlat
document.addEventListener('DOMContentLoaded', () => {
    new IlanKategoriManager();
});
