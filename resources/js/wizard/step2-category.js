/**
 * Step 2 Category Handler
 * Context7: Kategori kontrolü (Arsa/Konut)
 */

import { logger } from './step1-core.js';

/**
 * Category detection keywords
 */
const ARSA_KEYWORDS = ['arsa', 'tarla', 'arazi', 'land', 'parsel', 'ada'];
const KONUT_KEYWORDS = ['konut', 'daire', 'villa', 'residential', 'apartment', 'house'];

/**
 * Check category type (Arsa/Konut)
 * @returns {string} 'arsa', 'konut', or ''
 */
export function checkCategoryType() {
    const altKategoriSelect = document.getElementById('alt_kategori_id');
    if (!altKategoriSelect) {
        return '';
    }

    const selectedOption = altKategoriSelect.options[altKategoriSelect.selectedIndex];
    if (!selectedOption || !selectedOption.value) {
        return '';
    }

    const categoryName = selectedOption.text.toLowerCase().trim();
    const categorySlug = selectedOption.getAttribute('data-slug') || '';

    // Check for Arsa
    const isArsa = ARSA_KEYWORDS.some(
        (keyword) => categoryName.includes(keyword) || categorySlug.includes(keyword)
    );

    if (isArsa) {
        logger.log('✅ Step 2: Arsa kategorisi algılandı');
        return 'arsa';
    }

    // Check for Konut
    const isKonut = KONUT_KEYWORDS.some(
        (keyword) => categoryName.includes(keyword) || categorySlug.includes(keyword)
    );

    if (isKonut) {
        logger.log('✅ Step 2: Konut kategorisi algılandı');
        return 'konut';
    }

    // Check for Yazlik
    const isYazlik = categoryName.includes('yazlık') || categorySlug.includes('yazlik');
    if (isYazlik) {
        logger.log('✅ Step 2: Yazlık kategorisi algılandı');
        return 'yazlik';
    }

    logger.warn('⚠️ Step 2: Bilinmeyen kategori:', categoryName);
    return '';
}

/**
 * Step 2 Universal Component
 * Handles form switching and state for all Step 2 variants (Konut, Yazlik, Default)
 */
export function wizardStep2Component() {
    return {
        currentForm: 'default', // 'konut_satilik', 'yazlik', 'default'
        title: 'İlan Bilgileri',
        subtitle: 'İlanınızın başlık, fiyat ve detaylı bilgilerini girin',
        portal_ids: {
            sahibinden: '',
            emlakjet: '',
            hepsiemlak: '',
            zingat: '',
            hurriyetemlak: '',
        },

        init() {
            this.updateFormState();

            // Hydrate portal IDs from DOM (server-rendered values)
            const portals = ['sahibinden', 'emlakjet', 'hepsiemlak', 'zingat', 'hurriyetemlak'];
            portals.forEach((p) => {
                const el = document.querySelector(`input[name="${p}_id"]`);
                if (el && el.value) {
                    this.portal_ids[p] = el.value;
                }
            });

            // Listen for category changes
            window.addEventListener('category-changed', () => {
                this.updateFormState();
            });

            // Listen for wizard step changes
            document.addEventListener('wizard-step-changed', (e) => {
                if (e.detail?.step === 2) {
                    this.updateFormState();
                }
            });
        },

        updateFormState() {
            const anaSelect = document.getElementById('ana_kategori_id');
            const yayinSelect = document.getElementById('junction_id');
            const altSelect = document.getElementById('alt_kategori_id');

            if (!anaSelect || !yayinSelect) return;

            const anaOpt = anaSelect.options[anaSelect.selectedIndex];
            const yayinOpt = yayinSelect.options[yayinSelect.selectedIndex];
            const altOpt = altSelect ? altSelect.options[altSelect.selectedIndex] : null;

            if (!anaOpt || !yayinOpt || !anaOpt.value) {
                this.currentForm = 'default';
                return;
            }

            const anaSlug = (anaOpt.dataset.slug || '').toLowerCase();
            const altSlug = altOpt ? (altOpt.dataset.slug || '').toLowerCase() : '';
            const anaRootSlug = (anaOpt.dataset.rootSlug || '').toLowerCase();
            const yayinSlug = (yayinOpt.dataset.slug || '').toLowerCase();
            const yayinText = (yayinOpt.text || '').toLowerCase();

            const isSatilik = yayinSlug.includes('satilik') || yayinText.includes('satılık') || yayinText.includes('satilik');
            const isKiralik = (yayinSlug.includes('kiralik') && !yayinSlug.includes('gunluk')) || (yayinText.includes('kiralık') && !yayinText.includes('günlük'));
            const isGunluk = yayinSlug.includes('gunluk') || yayinSlug.includes('sezonluk') || yayinSlug.includes('haftalik') || yayinSlug.includes('aylik') || yayinText.includes('günlük') || yayinText.includes('sezonluk');

            const isKonut = anaSlug.includes('konut') || anaRootSlug.includes('konut') || altSlug.includes('daire') || altSlug.includes('villa');
            const isIsyeri = anaSlug.includes('isyeri') || anaRootSlug.includes('isyeri') || altSlug.includes('dukkan') || altSlug.includes('ofis');
            const isArsa = anaSlug.includes('arsa') || anaRootSlug.includes('arsa') || altSlug.includes('arsa') || altSlug.includes('arazi') || altSlug.includes('zeytinlik');

            // Detect Forms
            if (isGunluk || anaSlug.includes('yazlik') || altSlug.includes('yazlik') || altSlug.includes('villa-tipi')) {
                this.currentForm = 'gunluk_kiralik';
                this.title = 'Günlük Kiralama Detayları';
                this.subtitle = 'Tesis, fiyatlandırma ve kapasite bilgileri';
            } else if (isKonut && isSatilik) {
                this.currentForm = 'konut_satilik';
                this.title = 'Konut Satış Detayları';
                this.subtitle = 'Oda sayısı, kat durumu ve diğer özellikler';
            } else if (isKonut && isKiralik) {
                this.currentForm = 'konut_kiralik';
                this.title = 'Konut Kiralama Detayları';
                this.subtitle = 'Depozito, aidat ve kiralama koşulları';
            } else if (isIsyeri && isSatilik) {
                this.currentForm = 'isyeri_satilik';
                this.title = 'İşyeri Satış Detayları';
                this.subtitle = 'Devren/Satılık durumu, m² ve ticari özellikler';
            } else if (isIsyeri && isKiralik) {
                this.currentForm = 'isyeri_kiralik';
                this.title = 'İşyeri Kiralama Detayları';
                this.subtitle = 'Kira, devir ve ticari kullanım bilgileri';
            } else if (isArsa) {
                this.currentForm = 'arsa_satilik';
                this.title = 'Arsa Detayları';
                this.subtitle = 'İmar durumu, ada/parsel ve altyapı bilgileri';
            } else if (anaOpt.value) {
                this.currentForm = 'schema_form';
                this.title = (anaOpt.text || 'İlan') + ' Detayları';
                this.subtitle = 'Özellik ve detay bilgileri';
            } else {
                this.currentForm = 'default';
                this.title = 'İlan Bilgileri';
                this.subtitle = 'Temel ilan bilgileri';
            }

            // Sync with Central Store (Safety Check)
            if (window.Alpine && typeof window.Alpine.store === 'function') {
                try {
                    const store = window.Alpine.store('listing');
                    if (store) {
                        store.currentForm = this.currentForm;
                    }
                } catch (e) {
                    // Ignore store errors
                }
            }

            // Dispatch update (safe)
            document.dispatchEvent(
                new CustomEvent('wizard-state-updated', {
                    detail: {
                        currentForm: this.currentForm,
                        title: this.title,
                    },
                })
            );
        },
    };
}

// Global expose
// Event listener for Alpine initialization
const registerAlpineComponent = () => {
    if (typeof Alpine !== 'undefined') {
        Alpine.data('wizardStep2Component', wizardStep2Component);
    }
};

if (typeof window.Alpine !== 'undefined') {
    registerAlpineComponent();
} else {
    document.addEventListener('alpine:init', registerAlpineComponent);
}

// Global expose (fallback for non-Alpine contexts if needed)
if (typeof window !== 'undefined') {
    window.wizardStep2Component = wizardStep2Component;
    window.checkCategoryType = checkCategoryType;
}
