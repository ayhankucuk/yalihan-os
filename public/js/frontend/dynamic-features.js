/**
 * Frontend Dinamik Özellikler Sistemi
 * İlan detay sayfalarında kategori özelliklerini gösterir
 */

class FrontendDynamicFeatures {
    constructor(options = {}) {
        this.options = {
            container: '#ilan-ozellikleri',
            apiBase: '/admin/api/kategori-ozellik',
            ...options,
        };

        this.features = [];
        this.init();
    }

    init() {
        console.log('🏠 Frontend dinamik özellik sistemi başlatılıyor...');
        this.bindEvents();
    }

    bindEvents() {
        // Sayfa yüklendiğinde özellikleri yükle
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.loadFeatures());
        } else {
            this.loadFeatures();
        }
    }

    /**
     * İlan özelliklerini yükle
     */
    async loadFeatures() {
        const ilanId = this.getIlanId();
        if (!ilanId) return;

        try {
            const response = await fetch(`${this.options.apiBase}/ilan-features?ilan_id=${ilanId}`);
            const data = await response.json();

            if (data.success) {
                this.features = data.data;
                this.renderFeatures();
            }
        } catch (error) {
            console.error('Özellikler yüklenirken hata:', error);
        }
    }

    /**
     * İlan ID'sini al
     */
    getIlanId() {
        // URL'den ilan ID'sini çıkar
        const urlParams = new URLSearchParams(window.location.search);
        const ilanId = urlParams.get('ilan_id') || urlParams.get('id');

        if (ilanId) return ilanId;

        // Meta tag'den al
        const metaTag = document.querySelector('meta[name="ilan-id"]');
        if (metaTag) return metaTag.getAttribute('content');

        // Data attribute'dan al
        const container = document.querySelector(this.options.container);
        if (container) return container.dataset.ilanId;

        return null;
    }

    /**
     * Özellikleri render et
     */
    renderFeatures() {
        const container = document.querySelector(this.options.container);
        if (!container || !this.features.ozellikler) return;

        let html = '';

        // Kategori bilgisi
        if (this.features.kategori) {
            html += `
                <div class="ilan-kategori-info mb-6 p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-200">
                    <div class="flex items-center">
                        ${
                            this.features.kategori.icon_class
                                ? `<i class="${this.features.kategori.icon_class} text-2xl mr-3 text-blue-600"></i>`
                                : ''
                        }
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">${
                                this.features.kategori.name
                            }</h3>
                            ${
                                this.features.yayin_tipi
                                    ? `<p class="text-sm text-gray-600">${this.features.yayin_tipi.name}</p>`
                                    : ''
                            }
                        </div>
                    </div>
                </div>
            `;
        }

        // Özellikler
        Object.entries(this.features.ozellikler).forEach(([kategori, ozellikler]) => {
            const safeKategori = kategori.replace(
                /[<>&"']/g,
                (c) => ({ '<': '&lt;', '>': '&gt;', '&': '&amp;', '"': '&quot;', "'": '&#039;' })[c]
            );
            html += `
                <div class="ozellik-kategori mb-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200 dark:border-slate-700">
                        ${safeKategori}
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        ${ozellikler.map((ozellik) => this.renderFeatureCard(ozellik)).join('')}
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    /**
     * Tek özellik kartını render et
     */
    renderFeatureCard(ozellik) {
        const esc = (s) =>
            s == null
                ? ''
                : String(s).replace(
                      /[<>&"']/g,
                      (c) =>
                          ({
                              '<': '&lt;',
                              '>': '&gt;',
                              '&': '&amp;',
                              '"': '&quot;',
                              "'": '&#039;',
                          })[c]
                  );
        const icon = esc(ozellik.icon || '🏷️');
        const deger = Array.isArray(ozellik.deger)
            ? esc(ozellik.deger.join(', '))
            : esc(ozellik.deger);
        const birim = ozellik.birim
            ? ` <span class="text-gray-500">(${esc(ozellik.birim)})</span>`
            : '';

        return `
            <div class="ozellik-kart bg-white p-4 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow dark:bg-slate-900 dark:border-slate-700 dark:shadow-none">
                <div class="flex items-start">
                    <div class="text-2xl mr-3 text-blue-600">${icon}</div>
                    <div class="flex-1">
                        <h5 class="font-medium text-gray-800 mb-1">${esc(ozellik.ad)}</h5>
                        <p class="text-gray-600 text-sm">
                            ${deger}${birim}
                        </p>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Arama sayfasında kategori özelliklerini göster
     */
    async showCategoryFeatures(kategoriId, yayinTipiId = null) {
        try {
            const response = await fetch(
                `${this.options.apiBase}/frontend-features?kategori_id=${kategoriId}${
                    yayinTipiId ? `&yayin_tipi_id=${yayinTipiId}` : ''
                }`
            );
            const data = await response.json();

            if (data.success) {
                this.renderSearchFeatures(data.data);
            }
        } catch (error) {
            console.error('Arama özellikleri yüklenirken hata:', error);
        }
    }

    /**
     * Arama sayfasında özellikleri render et
     */
    renderSearchFeatures(data) {
        const container = document.querySelector('#arama-ozellikleri');
        if (!container) return;

        let html = '';

        // Kategori özellikleri
        if (data.kategori_ozellikleri && data.kategori_ozellikleri.length > 0) {
            html += `
                <div class="ozellik-grup mb-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">${
                        data.kategori.name
                    } Özellikleri</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        ${data.kategori_ozellikleri
                            .map((ozellik) => this.renderSearchFeature(ozellik))
                            .join('')}
                    </div>
                </div>
            `;
        }

        // Yayın tipi özellikleri
        if (data.yayin_tipi_ozellikleri && data.yayin_tipi_ozellikleri.length > 0) {
            html += `
                <div class="ozellik-grup mb-6">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Yayın Tipi Özellikleri</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        ${data.yayin_tipi_ozellikleri
                            .map((ozellik) => this.renderSearchFeature(ozellik))
                            .join('')}
                    </div>
                </div>
            `;
        }

        container.innerHTML = html;
    }

    /**
     * Arama sayfasında tek özelliği render et
     */
    renderSearchFeature(ozellik) {
        const icon = ozellik.icon || '🏷️';
        const zorunlu = ozellik.zorunlu ? '<span class="text-red-500 ml-1">*</span>' : '';

        return `
            <div class="ozellik-item bg-gray-50 p-3 rounded-lg border border-gray-200 dark:bg-slate-900 dark:border-slate-700">
                <div class="flex items-center">
                    <div class="text-xl mr-2 text-blue-600">${icon}</div>
                    <div class="flex-1">
                        <label class="text-sm font-medium text-gray-700 dark:text-slate-300">
                            ${ozellik.ad}${zorunlu}
                        </label>
                        ${
                            ozellik.aciklama
                                ? `<p class="text-xs text-gray-500 mt-1">${ozellik.aciklama}</p>`
                                : ''
                        }
                    </div>
                </div>
            </div>
        `;
    }
}

// Global instance oluştur
window.frontendDynamicFeatures = new FrontendDynamicFeatures();

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FrontendDynamicFeatures;
}
