/**
 * 🔱 MOD-1: İlan Sihirbazı - Dinamik Form Handler
 *
 * Features:
 * - Kategori → UPS Özellikler (Features) dinamik binding
 * - Yayın Tipi → Field visibility toggle (Satılık/Kiralık)
 * - Real-time form validation
 * - Context7 compliant field names (yayin_durumu, etc)
 *
 * Integration:
 * - WizardCategorySync.js ile event-based sinkronizasyon
 * - Location → Form koordinat binding
 * - POI data → Sealed badges generation
 *
 * @author GitHub Copilot
 * @date 3 Ocak 2026
 */

export class WizardFormHandler {
    constructor(formElement = null) {
        this.form = formElement || document.querySelector('form[data-wizard-form]');
        this.formData = new FormData();
        this.rules = {};
        this.sealedFields = {};

        this.init();
    }

    /**
     * İnişiyalisasyon: Event listeners ekle
     */
    async init() {
        if (!this.form) {
            console.warn('❌ Form element not found');
            return;
        }

        // SSOT Mode: Validation rules from wizard-context-applied event
        document.addEventListener('wizard-context-applied', (e) => {
            try {
                const detail = e && e.detail ? e.detail : {};
                const ctx = detail.context || {};
                const key = detail.context_key || 'unknown';

                const rules =
                    ctx.template && ctx.template.validation_rules
                        ? ctx.template.validation_rules
                        : {};
                const visibility =
                    ctx.template && ctx.template.field_visibility
                        ? ctx.template.field_visibility
                        : {};

                // Apply SSOT rules
                this.applyRulesFromSSOT(rules, visibility);

                console.log('[WIZARD] phase=validation action=ssot_applied key=' + key);
            } catch (err) {
                console.warn('[WIZARD] phase=validation action=ssot_apply_failed', err);
            }
        });

        // Initialize with soft defaults (SSOT will override)
        this.rules = {};
        console.log('[WIZARD] phase=validation action=soft_mode_init');

        // Kategori değişimi listener
        document.addEventListener('wizard:category-changed', (e) => {
            this.onCategoryChanged(e.detail);
        });

        // Yayın tipi değişimi listener
        document.addEventListener('wizard:publication-type-changed', (e) => {
            this.onPublicationTypeChanged(e.detail);
        });

        // Koordinat sealing listener (harita tarafından)
        document.addEventListener('wizard:coordinates-sealed', (e) => {
            this.onCoordinatesSealed(e.detail);
        });

        // POI badges listener
        document.addEventListener('wizard:poi-badges-ready', (e) => {
            this.onPoiBadgesReady(e.detail);
        });

        console.log('✅ WizardFormHandler initialized');
    }

    /**
     * Validation rules'i sunucudan yükle
     * @returns {Object}
     */
    async loadValidationRules() {
        try {
            const response = await fetch('/api/v1/wizard/validation-rules', {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            });

            if (!response.ok) throw new Error('Failed to load rules');
            const data = await response.json();
            return data.rules || {};
        } catch (error) {
            console.error('❌ Failed to load validation rules:', error);
            return {};
        }
    }

    /**
     * 1️⃣ KATEGORİ DEĞİŞİMİ
     * - UPS özelliklerini yükle
     * - Form alanlarını dinamik render et
     * - Required field'ları işaretle
     */
    async onCategoryChanged({ kategoriSlug, kategoriId, features = [] }) {
        console.log('📌 Category changed:', { kategoriSlug, kategoriId });

        // Mevcut UPS özelliklerini temizle
        this.clearFeaturesPanel();

        // Kategoriye ait validation rules'ı al
        const categoryRules = this.rules[kategoriSlug] || {};
        const { required = [], optional = [], hidden = [] } = categoryRules;

        // Required fields'ları form'da işaretле
        this.updateRequiredFields(required);

        // UPS features'larını dinamik render et
        if (features.length > 0) {
            this.renderUpsFeatures(features, kategoriSlug);
        } else {
            // Sunucudan features'ları yükle
            await this.loadCategoryFeatures(kategoriId);
        }

        // Hidden fields'ları gizle
        this.toggleHiddenFields(hidden);

        console.log('✅ Category form updated:', {
            required: required.length,
            optional: optional.length,
            hidden: hidden.length,
            features: features.length,
        });
    }

    /**
     * 2️⃣ YAYIN TİPİ DEĞİŞİMİ (Satılık/Kiralık)
     * - Fiyat, aidat, depozito vs. alanları göster/gizle
     * - Validasyon kurallarını güncelle
     */
    onPublicationTypeChanged({ yayinTipiSlug, yayinTipiId }) {
        console.log('📌 Publication type changed:', { yayinTipiSlug });

        const fieldToggleMap = {
            satilik: {
                show: ['fiyat', 'para_birimi'],
                hide: ['kira_ucreti', 'kaution'],
            },
            kiralik: {
                show: ['kira_ucreti', 'kaution', 'aidat'],
                hide: ['fiyat'],
            },
            takas: {
                show: ['takas_aciklamasi'],
                hide: ['fiyat', 'kira_ucreti', 'kaution'],
            },
        };

        const toggleConfig = fieldToggleMap[yayinTipiSlug] || {};
        const { show = [], hide = [] } = toggleConfig;

        // Show fields
        show.forEach((fieldName) => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.closest('.form-group')?.classList.remove('hidden');
                field.disabled = false;
            }
        });

        // Hide fields
        hide.forEach((fieldName) => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.closest('.form-group')?.classList.add('hidden');
                field.disabled = true;
                field.value = '';
            }
        });

        console.log('✅ Publication type fields toggled:', {
            show: show.length,
            hide: hide.length,
        });
    }

    /**
     * 3️⃣ KOORDİNAT MÜHÜRLEMESİ
     * - Harita'dan gelen lat/lng'yi form hidden input'a mühürle
     * - Eğer cihaz verisi varsa (sealed), koordinat değişimini kilitle
     */
    onCoordinatesSealed({ lat, lng, address, sealed = false, source = 'map' }) {
        console.log('📌 Coordinates sealed:', { lat, lng, address, sealed, source });

        // Hidden input'ları doldur
        const latInput = this.form.querySelector('[name="lat"]') || this.createHiddenInput('lat');
        const lngInput = this.form.querySelector('[name="lng"]') || this.createHiddenInput('lng');
        const adresInput = this.form.querySelector('[name="adres"]');
        const koordinatMuhurleriInput =
            this.form.querySelector('[name="koordinat_muhurleri"]') ||
            this.createHiddenInput('koordinat_muhurleri');

        latInput.value = lat;
        lngInput.value = lng;
        if (adresInput) adresInput.value = address || '';

        // Mühürleme bilgisini kaydet
        this.sealedFields['lat'] = {
            value: lat,
            source,
            sealed,
            timestamp: new Date().toISOString(),
        };
        this.sealedFields['lng'] = {
            value: lng,
            source,
            sealed,
            timestamp: new Date().toISOString(),
        };

        koordinatMuhurleriInput.value = JSON.stringify(this.sealedFields);

        // Eğer sealed (mühürlü) ise, input'ları disable et
        if (sealed) {
            latInput.disabled = true;
            lngInput.disabled = true;
            this.showSealedBadge('📍 Koordinatlar Mühürlü (Cihaz Onaylı)', 'success');
        }

        console.log('✅ Coordinates saved to form (sealed=' + sealed + ')');
    }

    /**
     * 4️⃣ POI MÜHÜRLÜ ETİKETLER
     * - PoiService'ten gelen mesafeleri görsel badges olarak göster
     * - Harita altına ekle
     */
    onPoiBadgesReady({ pois, nearbyPois, totalCount }) {
        console.log('📌 POI badges ready:', { totalCount });

        const badgesContainer =
            document.getElementById('poi-sealed-badges') || this.createBadgesContainer();

        // Clear existing badges
        badgesContainer.innerHTML = '';

        if (nearbyPois && nearbyPois.length > 0) {
            // Top 5 POI badges
            const topPois = nearbyPois.slice(0, 5);

            topPois.forEach((poi) => {
                const badge = this.createPoiBadge(poi);
                badgesContainer.appendChild(badge);
            });

            // Show summary
            const summary = document.createElement('div');
            summary.className = 'text-xs text-gray-500 dark:text-gray-400 mt-2';
            summary.innerHTML = `<strong>${totalCount}</strong> POI bulundu`;
            badgesContainer.appendChild(summary);

            badgesContainer.classList.remove('hidden');
        }

        console.log('✅ POI badges rendered');
    }

    /**
     * 🎯 YARDIMCI METODLAR
     */

    /**
     * Required fields'ları form'da kırmızı yıldız ile işaretле
     */
    updateRequiredFields(requiredFields) {
        // Tüm required işaretlerini temizle
        this.form.querySelectorAll('[data-required-badge]').forEach((el) => {
            el.remove();
        });

        // Yeni required fields'ları işaretле
        requiredFields.forEach((fieldName) => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (!field) return;

            const label = field.closest('.form-group')?.querySelector('label');
            if (label && !label.querySelector('[data-required-badge]')) {
                const badge = document.createElement('span');
                badge.className = 'text-red-500 font-bold ml-1';
                badge.textContent = '*';
                badge.setAttribute('data-required-badge', fieldName);
                label.appendChild(badge);
            }

            // Add form validation
            field.setAttribute('required', 'required');
        });
    }

    /**
     * Hidden fields'ları göster/gizle
     */
    toggleHiddenFields(hiddenFields) {
        hiddenFields.forEach((fieldName) => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.closest('.form-group')?.classList.add('hidden');
                field.disabled = true;
            }
        });
    }

    /**
     * Kategori özelliklerini sunucudan yükle
     */
    async loadCategoryFeatures(kategoriId) {
        try {
            const response = await fetch(`/api/v1/ups/features/by-category/${kategoriId}`);
            if (!response.ok) throw new Error('Failed to load features');

            const data = await response.json();
            this.renderUpsFeatures(data.features || [], data.kategori_slug);
        } catch (error) {
            console.error('❌ Failed to load category features:', error);
        }
    }

    /**
     * UPS özelliklerini dinamik render et
     */
    renderUpsFeatures(features, kategoriSlug) {
        const container =
            document.getElementById('features-container') || this.createFeaturesContainer();

        container.innerHTML = '';

        if (features.length === 0) {
            container.innerHTML =
                '<p class="text-gray-500 dark:text-slate-500">Bu kategori için özellik tanımlanmamış</p>';
            return;
        }

        features.forEach((feature) => {
            const checkbox = document.createElement('label');
            checkbox.className =
                'flex items-center gap-2 p-2 rounded hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-all';

            const input = document.createElement('input');
            input.type = 'checkbox';
            input.name = `ozellik_${feature.id}`;
            input.value = feature.id;
            input.className = 'rounded border-gray-300 text-blue-600 focus:ring-blue-500';

            const label = document.createElement('span');
            label.className = 'text-sm text-gray-700 dark:text-gray-300 dark:text-slate-300';
            label.textContent = feature.name;

            const icon = document.createElement('span');
            icon.textContent = feature.icon || '✓';
            icon.className = 'mr-auto text-lg';

            checkbox.appendChild(input);
            checkbox.appendChild(icon);
            checkbox.appendChild(label);
            container.appendChild(checkbox);
        });
    }

    /**
     * POI badge HTML oluştur
     */
    createPoiBadge(poi) {
        const badge = document.createElement('div');
        badge.className =
            'inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gradient-to-r from-blue-100 to-blue-50 dark:from-blue-900/30 dark:to-blue-800/20 border border-blue-200 dark:border-blue-700 text-sm font-medium text-blue-900 dark:text-blue-300 mr-2 mb-2';

        const icon = document.createElement('span');
        icon.className = 'text-base';
        icon.textContent = this.getPoiIcon(poi.type) || '📍';

        const text = document.createElement('span');
        text.textContent = `${poi.name} ${this.formatDistance(poi.distance_m)}`;

        badge.appendChild(icon);
        badge.appendChild(text);

        return badge;
    }

    /**
     * POI ikonu al (type'a göre)
     */
    getPoiIcon(type) {
        const iconMap = {
            school: '📚',
            hospital: '🏥',
            market: '🛒',
            restaurant: '🍽️',
            park: '🌳',
            metro: '🚇',
            bus: '🚌',
            bank: '🏦',
            pharmacy: '💊',
            gym: '💪',
            parking: '🅿️',
        };
        return iconMap[type] || '📍';
    }

    /**
     * Mesafe format et (m → km)
     */
    formatDistance(meters) {
        if (meters < 1000) return `${Math.round(meters)}m`;
        return `${(meters / 1000).toFixed(1)}km`;
    }

    /**
     * Mühürlü badge göster
     */
    showSealedBadge(text, type = 'info') {
        const alertEl = document.getElementById('form-sealed-alert') || this.createSealedAlert();
        alertEl.textContent = text;
        alertEl.className = `p-3 rounded mb-4 ${
            type === 'success'
                ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
                : type === 'error'
                  ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300'
                  : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
        }`;
        alertEl.classList.remove('hidden');
    }

    /**
     * Helper: Hidden input oluştur
     */
    createHiddenInput(name) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        this.form.appendChild(input);
        return input;
    }

    /**
     * Helper: Features container oluştur
     */
    createFeaturesContainer() {
        const container = document.createElement('div');
        container.id = 'features-container';
        container.className = 'grid grid-cols-2 md:grid-cols-3 gap-2';
        this.form.appendChild(container);
        return container;
    }

    /**
     * Helper: POI badges container oluştur
     */
    createBadgesContainer() {
        const container = document.createElement('div');
        container.id = 'poi-sealed-badges';
        container.className = 'flex flex-wrap gap-2 mt-3';

        const mapContainer = document.getElementById('wizard-map');
        if (mapContainer?.parentElement) {
            mapContainer.parentElement.appendChild(container);
        }

        return container;
    }

    /**
     * Helper: Sealed alert oluştur
     */
    createSealedAlert() {
        const alert = document.createElement('div');
        alert.id = 'form-sealed-alert';
        alert.className = 'hidden';
        this.form?.prepend(alert);
        return alert;
    }

    /**
     * Features panelini temizle
     */
    clearFeaturesPanel() {
        const container = document.getElementById('features-container');
        if (container) {
            container.innerHTML = '';
        }
    }

    /**
     * Form verilerini al (sealed fields dahil)
     */
    getFormData() {
        const data = new FormData(this.form);
        const obj = Object.fromEntries(data.entries());

        // Sealed fields'ları ekle
        obj.koordinat_muhurleri = this.sealedFields;

        return obj;
    }
}

// Global initialization
if (typeof window !== 'undefined') {
    window.WizardFormHandler = WizardFormHandler;
}

export default WizardFormHandler;
