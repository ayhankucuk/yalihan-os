/**
 * Dynamic Features Loader - Context7 Standard
 *
 * 🎯 Hedefler:
 * - Dynamic feature loading based on category
 * - Real-time feature updates
 * - Performance optimization
 *
 * @version 1.0.0
 * @author Context7 Team
 */

class DynamicFeaturesLoader {
    constructor() {
        this.featuresCache = new Map();
        this.currentCategory = null;
        this.currentSubCategory = null;
        this.currentYayinTipi = null;

        this.init();
    }

    /**
     * Initialize the loader
     */
    init() {
        this.setupEventListeners();
        this.loadInitialFeatures();
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Ana kategori değişikliği
        document.addEventListener('change', (e) => {
            if (e.target.id === 'ana_kategori_id') {
                this.handleAnaKategoriChange(e.target.value);
            }
        });

        // Alt kategori değişikliği
        document.addEventListener('change', (e) => {
            if (e.target.id === 'alt_kategori_id') {
                this.handleAltKategoriChange(e.target.value);
            }
        });

        // Yayın tipi değişikliği
        document.addEventListener('change', (e) => {
            if (e.target.id === 'junction_id') {
                this.handleYayinTipiChange(e.target.value);
            }
        });
    }

    /**
     * Handle ana kategori change
     */
    async handleAnaKategoriChange(kategoriId) {
        if (!kategoriId) {
            this.clearAltKategoriler();
            this.clearYayinTipleri();
            this.clearFeatures();
            return;
        }

        this.currentCategory = kategoriId;
        await this.loadAltKategoriler(kategoriId);
        this.clearYayinTipleri();
        this.clearFeatures();
    }

    /**
     * Handle alt kategori change
     */
    async handleAltKategoriChange(altKategoriId) {
        if (!altKategoriId) {
            this.clearYayinTipleri();
            this.clearFeatures();
            return;
        }

        this.currentSubCategory = altKategoriId;
        await this.loadYayinTipleri(altKategoriId);
        this.clearFeatures();
    }

    /**
     * Handle yayın tipi change
     */
    async handleYayinTipiChange(yayinTipiId) {
        if (!yayinTipiId) {
            this.clearFeatures();
            return;
        }

        this.currentYayinTipi = yayinTipiId;
        await this.loadFeatures();
    }

    /**
     * Load alt kategoriler
     */
    async loadAltKategoriler(anaKategoriId) {
        try {
            const response = await fetch(
                `/admin/ilan-kategorileri/${anaKategoriId}/alt-kategoriler`
            );
            const data = await response.json();

            const select = document.getElementById('alt_kategori_id');
            if (select) {
                select.innerHTML = '<option value="">Alt kategori seçiniz</option>';

                data.forEach((kategori) => {
                    const option = document.createElement('option');
                    option.value = kategori.id;
                    option.textContent = kategori.name;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Alt kategoriler yüklenirken hata:', error);
        }
    }

    /**
     * Load yayın tipleri
     */
    async loadYayinTipleri(altKategoriId) {
        try {
            const response = await fetch(`/admin/yayin-tipleri/${altKategoriId}/tipler`);
            const data = await response.json();

            const select = document.getElementById('junction_id');
            if (select) {
                select.innerHTML = '<option value="">Yayın tipi seçiniz</option>';

                data.forEach((tip) => {
                    const option = document.createElement('option');
                    option.value = tip.id;
                    option.textContent = tip.name;
                    select.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Yayın tipleri yüklenirken hata:', error);
        }
    }

    /**
     * Load features based on current selection
     */
    async loadFeatures() {
        if (!this.currentSubCategory || !this.currentYayinTipi) {
            return;
        }

        const cacheKey = `${this.currentSubCategory}-${this.currentYayinTipi}`;

        // Check cache first
        if (this.featuresCache.has(cacheKey)) {
            this.renderFeatures(this.featuresCache.get(cacheKey));
            return;
        }

        try {
            const response = await fetch('/admin/ozellikler/load', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content'),
                },
                body: JSON.stringify({
                    alt_kategori_id: this.currentSubCategory,
                    junction_id: this.currentYayinTipi,
                }),
            });

            const data = await response.json();

            if (data.success) {
                // Cache the features
                this.featuresCache.set(cacheKey, data.features);
                this.renderFeatures(data.features);
            }
        } catch (error) {
            console.error('Özellikler yüklenirken hata:', error);
        }
    }

    /**
     * Render features
     */
    renderFeatures(features) {
        const container = document.getElementById('features-container');
        if (!container) return;

        container.innerHTML = '';

        if (!features || features.length === 0) {
            container.innerHTML =
                '<p class="text-gray-500 text-center py-4 dark:text-slate-500">Bu kategori için özel özellik bulunmamaktadır.</p>';
            return;
        }

        features.forEach((feature) => {
            const featureElement = this.createFeatureElement(feature);
            container.appendChild(featureElement);
        });
    }

    /**
     * Create feature element
     */
    createFeatureElement(feature) {
        const div = document.createElement('div');
        div.className = 'wizard-field-group';

        let inputHtml = '';

        switch (feature.input_type) {
            case 'text':
                inputHtml = `
                    <input type="text"
                           name="ozellik_${feature.id}"
                           class="wizard-field"
                           data-wizard-field
                           placeholder="${feature.placeholder || ''}"
                           data-feature-id="${feature.id}">
                `;
                break;

            case 'number':
                inputHtml = `
                    <input type="number"
                           name="ozellik_${feature.id}"
                           class="wizard-field"
                           data-wizard-field
                           placeholder="${feature.placeholder || ''}"
                           data-feature-id="${feature.id}"
                           ${feature.min ? `min="${feature.min}"` : ''}
                           ${feature.max ? `max="${feature.max}"` : ''}>
                `;
                break;

            case 'select':
                const options = feature.options ? JSON.parse(feature.options) : [];
                inputHtml = `
                    <select name="ozellik_${feature.id}"
                            class="wizard-field"
                            data-wizard-field
                            data-feature-id="${feature.id}">
                        <option value="">Seçiniz</option>
                        ${options
                            .map(
                                (option) =>
                                    `<option value="${option.value}">${option.label}</option>`
                            )
                            .join('')}
                    </select>
                `;
                break;

            case 'checkbox':
                inputHtml = `
                    <label class="flex items-center">
                        <input type="checkbox"
                               name="ozellik_${feature.id}"
                               class="wizard-field"
                               data-wizard-field
                               data-feature-id="${feature.id}">
                        <span class="ml-2 text-sm text-gray-700 dark:text-slate-300">${feature.name}</span>
                    </label>
                `;
                break;

            case 'textarea':
                inputHtml = `
                    <textarea name="ozellik_${feature.id}"
                              class="wizard-field"
                              data-wizard-field
                              rows="3"
                              placeholder="${feature.placeholder || ''}"
                              data-feature-id="${feature.id}"></textarea>
                `;
                break;

            default:
                inputHtml = `
                    <input type="text"
                           name="ozellik_${feature.id}"
                           class="wizard-field"
                           data-wizard-field
                           placeholder="${feature.placeholder || ''}"
                           data-feature-id="${feature.id}">
                `;
        }

        div.innerHTML = `
            <label class="wizard-field-label" for="ozellik_${feature.id}">
                ${feature.name}
                ${feature.is_required ? '<span class="text-red-500">*</span>' : ''}
            </label>
            ${inputHtml}
            ${
                feature.description
                    ? `<div class="wizard-field-help">${feature.description}</div>`
                    : ''
            }
        `;

        return div;
    }

    /**
     * Clear alt kategoriler
     */
    clearAltKategoriler() {
        const select = document.getElementById('alt_kategori_id');
        if (select) {
            select.innerHTML = '<option value="">Önce ana kategori seçiniz</option>';
        }
    }

    /**
     * Clear yayın tipleri
     */
    clearYayinTipleri() {
        const select = document.getElementById('junction_id');
        if (select) {
            select.innerHTML = '<option value="">Önce alt kategori seçiniz</option>';
        }
    }

    /**
     * Clear features
     */
    clearFeatures() {
        const container = document.getElementById('features-container');
        if (container) {
            container.innerHTML =
                '<p class="text-gray-500 text-center py-4 dark:text-slate-500">Kategori ve yayın tipi seçiniz.</p>';
        }
    }

    /**
     * Load initial features
     */
    loadInitialFeatures() {
        this.clearFeatures();
    }

    /**
     * Get selected features data
     */
    getSelectedFeatures() {
        const features = {};
        const featureInputs = document.querySelectorAll('[data-feature-id]');

        featureInputs.forEach((input) => {
            const featureId = input.dataset.featureId;
            const value = input.type === 'checkbox' ? input.checked : input.value;

            if (value !== '' && value !== false) {
                features[featureId] = value;
            }
        });

        return features;
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.featuresCache.clear();
    }
}

// Export for use in other modules
window.DynamicFeaturesLoader = DynamicFeaturesLoader;

// Auto-initialize - DISABLED for create-wizard (FeaturesManager handles it)
document.addEventListener('DOMContentLoaded', () => {
    // ✅ FIX: Skip create-wizard page - FeaturesManager in features-dynamic.blade.php handles features
    const isCreateWizard =
        window.location.pathname.includes('/ilanlar/create') ||
        window.location.pathname.includes('/ilanlar/wizard') ||
        document.getElementById('features-dynamic-root');

    if (isCreateWizard) {
        console.log('[DynamicFeaturesLoader] Skipped - FeaturesManager active on this page');
        return;
    }

    if (document.getElementById('features-container')) {
        window.dynamicFeaturesLoader = new DynamicFeaturesLoader();
    }
});
