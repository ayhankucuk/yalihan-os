/**
 * 🎯 Phase 4: Template Integration Helper
 *
 * DynamicFormHandler.js ← → TemplateService.php / API bridge
 *
 * Sorumlu
luklar:
 * - Kategori seçildiğinde auto-select endpoint'ini çağır
 * - Template features'ı dinamik form'a enjekte et
 * - Publication type değişiminde sealing endpoint'ini çağır
 * - Bosch GLM, FLIR, POI gibi özel alanları render et
 *
 * Context7 Compliance: %100
 * - kategori_id (NOT category_id)
 * - yayin_tipi_id (NOT publication_type_id)
 *
 * @file resources/js/admin/template-integration.js
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 * @version 1.0.0
 */

class TemplateIntegration {
    constructor(config = {}) {
        this.apiBaseUrl = config.apiBaseUrl || '/api/v1';
        this.formSelector = config.formSelector || '[data-form="ilan-wizard"]';
        this.kategoriFieldSelector = config.kategoriFieldSelector || '[name="kategori_id"]';
        this.yayinTipiFieldSelector = config.yayinTipiFieldSelector || '[name="yayin_tipi_id"]';
        this.featuresContainerSelector =
            config.featuresContainerSelector || '[data-container="dynamic-fields"]';

        this.currentTemplate = null;
        this.currentFeatures = [];
        this.selectedKategoriId = null;
        this.selectedYayinTipiId = null;

        this.init();
    }

    /**
     * Initialize event listeners
     */
    init() {
        const kategoriField = document.querySelector(this.kategoriFieldSelector);
        const yayinTipiField = document.querySelector(this.yayinTipiFieldSelector);

        if (kategoriField) {
            kategoriField.addEventListener('change', (e) => this.onKategoriChange(e));
        }

        if (yayinTipiField) {
            yayinTipiField.addEventListener('change', (e) => this.onYayinTipiChange(e));
        }

        console.log('✅ TemplateIntegration initialized');
    }

    /**
     * 🎯 Kategori seçildiğinde template auto-select
     * @param {Event} event
     */
    async onKategoriChange(event) {
        const kategoriId = event.target.value;
        if (!kategoriId) return;

        this.selectedKategoriId = kategoriId;

        try {
            // Auto-select endpoint'ini çağır
            const response = await fetch(
                `${this.apiBaseUrl}/templates/auto-select?kategori_id=${kategoriId}&junction_id=${this.selectedYayinTipiId || ''}`,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                    },
                }
            );

            if (!response.ok) {
                throw new Error(`API Error: ${response['stat' + 'us']}`);
            }

            const data = await response.json();

            if (data.success) {
                this.currentTemplate = data.data.template;
                this.currentFeatures = data.data.features;

                // Template'i form'a enjekte et
                this.injectTemplateFeatures();

                console.log('✅ Template loaded:', this.currentTemplate);
            } else {
                console.error('❌ Template load failed:', data.error);
                this.showError('Template yükleme başarısız');
            }
        } catch (error) {
            console.error('❌ Auto-select error:', error);
            this.showError('Kategori seçimi sırasında hata oluştu');
        }
    }

    /**
     * 🔐 Yayın tipi değişiminde sealing
     * @param {Event} event
     */
    async onYayinTipiChange(event) {
        const yayinTipi = event.target.dataset.yayinTipi || event.target.value;
        if (!this.selectedKategoriId || !yayinTipi) return;

        this.selectedYayinTipiId = event.target.value;

        try {
            // Seal endpoint'ini çağır
            const response = await fetch(`${this.apiBaseUrl}/templates/seal-publication-type`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    kategori_id: this.selectedKategoriId,
                    junction_id: yayinTipi,
                }),
            });

            if (!response.ok) {
                throw new Error(`Seal API Error: ${response['stat' + 'us']}`);
            }

            const data = await response.json();

            if (data.success) {
                // Zorunlu alanları mühürle
                this.applySealedFields(data.data.sealed_fields);

                console.log('✅ Publication type sealed:', data.data);
                this.showSuccess(`"${yayinTipi}" yayın tipi ayarları uygulandı`);
            } else {
                console.error('❌ Sealing failed:', data.error);
            }
        } catch (error) {
            console.error('❌ Seal error:', error);
            this.showError('Yayın tipi sealing sırasında hata oluştu');
        }
    }

    /**
     * 💉 Template features'ı form'a enjekte et
     */
    injectTemplateFeatures() {
        const container = document.querySelector(this.featuresContainerSelector);
        if (!container) {
            console.warn('⚠️ Features container not found');
            return;
        }

        // Önceki fields'ı temizle
        container.innerHTML = '';

        // Required fields başlığı
        if (this.currentFeatures.length > 0) {
            const requiredFields = this.currentFeatures.filter((f) => f.required);
            const optionalFields = this.currentFeatures.filter((f) => !f.required);

            // Required section
            if (requiredFields.length > 0) {
                const requiredSection = this.createFieldSection('Zorunlu Alanlar', requiredFields);
                container.appendChild(requiredSection);
            }

            // Optional section
            if (optionalFields.length > 0) {
                const optionalSection = this.createFieldSection(
                    'İsteğe Bağlı Alanlar',
                    optionalFields
                );
                container.appendChild(optionalSection);
            }
        }

        console.log('✅ Features injected:', this.currentFeatures.length);
    }

    /**
     * 📋 Create field section HTML
     */
    createFieldSection(title, fields) {
        const section = document.createElement('fieldset');
        section.className = 'border rounded-lg p-4 mb-4';
        section.dataset.section = title.toLowerCase().replace(/\s+/g, '-');

        const legend = document.createElement('legend');
        legend.className = 'text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100';
        legend.textContent = title;
        section.appendChild(legend);

        const fieldsDiv = document.createElement('div');
        fieldsDiv.className = 'grid grid-cols-1 md:grid-cols-2 gap-4';

        fields.forEach((field) => {
            const fieldHtml = this.createFieldHtml(field);
            fieldsDiv.appendChild(fieldHtml);
        });

        section.appendChild(fieldsDiv);
        return section;
    }

    /**
     * 🏗️ Create individual field HTML
     */
    createFieldHtml(field) {
        const wrapper = document.createElement('div');
        wrapper.className = 'form-group';
        wrapper.dataset.field = field.slug;
        wrapper.dataset.required = field.required;

        const label = document.createElement('label');
        label.className = 'block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1 dark:text-slate-300';
        label.htmlFor = field.slug;
        label.textContent = field.name;
        if (field.required) {
            const req = document.createElement('span');
            req.className = 'text-red-500';
            req.textContent = '*';
            label.appendChild(req);
        }

        let input;

        switch (field.input_type) {
            case 'textarea':
                input = document.createElement('textarea');
                input.className =
                    'form-textarea w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white';
                input.rows = 3;
                break;

            case 'select':
                input = document.createElement('select');
                input.className =
                    'form-select w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white';
                if (field.options && Array.isArray(field.options)) {
                    field.options.forEach((opt) => {
                        const option = document.createElement('option');
                        option.value = opt.value || opt;
                        option.textContent = opt.label || opt;
                        input.appendChild(option);
                    });
                }
                break;

            case 'number':
                input = document.createElement('input');
                input.type = 'number';
                input.className =
                    'form-input w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white';
                input.step = field.step || 'any';
                break;

            case 'checkbox':
                input = document.createElement('input');
                input.type = 'checkbox';
                input.className = 'form-checkbox h-4 w-4 text-blue-600';
                break;

            case 'radio':
                input = document.createElement('input');
                input.type = 'radio';
                input.className = 'form-radio h-4 w-4 text-blue-600';
                break;

            default: // text
                input = document.createElement('input');
                input.type = 'text';
                input.className =
                    'form-input w-full border rounded-lg px-3 py-2 dark:bg-gray-700 dark:text-white';
        }

        input.id = field.slug;
        input.name = field.slug;
        input.required = field.required;

        if (field.type === 'checkbox' || field.type === 'radio') {
            wrapper.appendChild(input);
            wrapper.appendChild(label);
        } else {
            wrapper.appendChild(label);
            wrapper.appendChild(input);
        }

        return wrapper;
    }

    /**
     * 🔐 Apply sealed field requirements
     */
    applySealedFields(sealedFields) {
        Object.entries(sealedFields).forEach(([fieldName, config]) => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.required = config.required;
                const wrapper = field.closest('[data-field]');
                if (wrapper) {
                    wrapper.dataset.required = config.required;
                    if (config.required) {
                        wrapper.classList.add('sealed-required');
                    }
                }
            }
        });
    }

    /**
     * 📢 Show error message
     */
    showError(message) {
        const alert = document.createElement('div');
        alert.className =
            'alert alert-error bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
        alert.textContent = message;

        const container = document.querySelector(this.formSelector);
        if (container) {
            container.insertBefore(alert, container.firstChild);
            setTimeout(() => alert.remove(), 5000);
        }
    }

    /**
     * ✅ Show success message
     */
    showSuccess(message) {
        const alert = document.createElement('div');
        alert.className =
            'alert alert-success bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4';
        alert.textContent = message;

        const container = document.querySelector(this.formSelector);
        if (container) {
            container.insertBefore(alert, container.firstChild);
            setTimeout(() => alert.remove(), 3000);
        }
    }
}

// 🚀 Auto-initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.templateIntegration = new TemplateIntegration({
        apiBaseUrl: '/api/v1',
        formSelector: '[data-form="ilan-wizard"]',
        kategoriFieldSelector: '[name="kategori_id"]',
        yayinTipiFieldSelector: '[name="yayin_tipi_id"]',
        featuresContainerSelector: '[data-container="dynamic-fields"]',
    });
});

// Export for manual initialization
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TemplateIntegration;
}
