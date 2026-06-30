/**
 * Step 5: Price & Investment Module
 *
 * @module wizard/steps/step5-price
 * @version 3.0.0
 * @since 2026-01-28
 * @context7 Modüler Wizard Mimarisi - SEALED
 *
 * Handles price input, currency, and investment calculations (ROI, yield).
 */

import { WizardEventBus, WizardEventTypes } from '../core/wizard-events.js';
import { WizardState } from '../core/wizard-state.js';

/**
 * Configuration
 */
const CONFIG = {
    currencies: ['TRY', 'USD', 'EUR', 'GBP'],
    defaultCurrency: 'TRY',
    currencySymbols: {
        TRY: '₺',
        USD: '$',
        EUR: '€',
        GBP: '£',
    },
};

/**
 * PriceManager - Step 5 Controller
 */
class PriceManagerClass {
    constructor() {
        /** @private */
        this._initialized = false;
    }

    /**
     * Initialize price module
     */
    init() {
        if (this._initialized) return;

        this._setupPriceFormatting();
        this._setupCurrencyChange();
        this._setupCalculators();

        this._initialized = true;
        console.log('[PriceManager] Initialized');
    }

    /**
     * Setup price input formatting
     * @private
     */
    _setupPriceFormatting() {
        const priceInputs = document.querySelectorAll(
            '[data-format="price"], #fiyat, #gunluk_fiyat, #haftalik_fiyat, #aylik_fiyat, #sezonluk_fiyat'
        );

        priceInputs.forEach((input) => {
            input.addEventListener('input', (e) => {
                const formatted = this.formatPrice(e.target.value);
                e.target.value = formatted;
            });

            input.addEventListener('blur', (e) => {
                const formatted = this.formatPrice(e.target.value);
                e.target.value = formatted;

                // Update calculations
                this.updateCalculations();
            });
        });
    }

    /**
     * Setup currency change handler
     * @private
     */
    _setupCurrencyChange() {
        const currencySelect = document.getElementById('para_birimi');
        if (!currencySelect) return;

        currencySelect.addEventListener('change', () => {
            const currency = currencySelect.value;
            WizardState.updateFormField('para_birimi', currency);
            this._updateCurrencySymbols(currency);
        });
    }

    /**
     * Update currency symbols in UI
     * @private
     */
    _updateCurrencySymbols(currency) {
        const symbol = CONFIG.currencySymbols[currency] || '₺';
        document.querySelectorAll('.currency-symbol').forEach((el) => {
            el.textContent = symbol;
        });
    }

    /**
     * Setup investment calculators
     * @private
     */
    _setupCalculators() {
        // Listen for field changes that affect calculations
        const calcFields = [
            'fiyat',
            'satis_fiyati',
            'gunluk_fiyat',
            'haftalik_fiyat',
            'aylik_fiyat',
            'sezonluk_fiyat',
            'aylik_kira',
            'kira_bedeli',
            'alan_m2',
            'taks',
            'kaks',
        ];

        calcFields.forEach((fieldName) => {
            const field =
                document.getElementById(fieldName) ||
                document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                field.addEventListener('input', () => this.updateCalculations());
                field.addEventListener('change', () => this.updateCalculations());
            }
        });
    }

    /**
     * Format price with thousand separators
     * @param {string|number} value
     * @returns {string}
     */
    formatPrice(value) {
        // Remove non-numeric except comma
        let cleaned = String(value).replace(/[^\d,]/g, '');

        // Handle decimal comma
        const parts = cleaned.split(',');
        let intPart = parts[0].replace(/\D/g, '');
        const decPart = parts[1]?.substring(0, 2);

        // Add thousand separators
        intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

        if (decPart !== undefined) {
            return `${intPart},${decPart}`;
        }

        return intPart;
    }

    /**
     * Parse formatted price to number
     * @param {string} formatted
     * @returns {number}
     */
    parsePrice(formatted) {
        if (!formatted) return 0;
        const cleaned = String(formatted).replace(/\./g, '').replace(',', '.');
        const num = parseFloat(cleaned);
        return isNaN(num) ? 0 : num;
    }

    /**
     * Get field value as number
     * @private
     */
    _getFieldValue(selector) {
        const el =
            document.getElementById(selector) || document.querySelector(`[name="${selector}"]`);
        if (!el) return null;
        return this.parsePrice(el.value);
    }

    /**
     * Update all investment calculations
     */
    updateCalculations() {
        this.updateSeasonalROI();
        this.updateCapitalReturn();
        this.updateLandCalculator();
        this.updateCortexSummary();
    }

    /**
     * Calculate and display seasonal ROI
     */
    updateSeasonalROI() {
        const container = document.getElementById('seasonal-roi-panel');
        if (!container) return;

        const dailyPrice =
            this._getFieldValue('gunluk_fiyat') || this._getFieldValue('field_dep_gunluk_fiyat');
        const seasonalPrice =
            this._getFieldValue('sezonluk_fiyat') ||
            this._getFieldValue('field_dep_sezonluk_fiyat');
        const salePrice =
            this._getFieldValue('satis_fiyati') ||
            this._getFieldValue('fiyat') ||
            this._getFieldValue('field_dep_satis_fiyati');

        // Occupancy estimation
        const occEl = document.getElementById('sp_occ');
        const daysEl = document.getElementById('sp_days');
        const occupancy = occEl ? parseFloat(occEl.value) || 70 : 70;
        const seasonDays = daysEl ? parseFloat(daysEl.value) || 120 : 120;

        let annualRevenue = 0;
        let roi = null;
        let yearsToRecover = null;

        if (dailyPrice && dailyPrice > 0) {
            const occupiedDays = seasonDays * (occupancy / 100);
            annualRevenue = dailyPrice * occupiedDays;
        } else if (seasonalPrice && seasonalPrice > 0) {
            annualRevenue = seasonalPrice;
        }

        if (annualRevenue > 0 && salePrice > 0) {
            roi = (annualRevenue / salePrice) * 100;
            yearsToRecover = salePrice / annualRevenue;
        }

        // Update UI
        this._updateElement(
            'sp_revenue',
            annualRevenue ? this._formatCurrency(annualRevenue) : '-'
        );
        this._updateElement('sp_roi', roi ? `${yearsToRecover.toFixed(1)} yıl` : '-');
        this._updateElement('sp_yield', roi ? `${roi.toFixed(1)}%` : '-');

        // Badge
        let badge = '-';
        if (yearsToRecover !== null) {
            if (yearsToRecover <= 10) badge = 'A+ (Mükemmel)';
            else if (yearsToRecover <= 15) badge = 'A (İyi)';
            else if (yearsToRecover <= 20) badge = 'B (Orta)';
            else badge = 'C (Uzun vadeli)';
        }
        this._updateElement('sp_badge', badge);
    }

    /**
     * Calculate and display capital return (rent vs sale)
     */
    updateCapitalReturn() {
        const rent = this._getFieldValue('aylik_kira') || this._getFieldValue('kira_bedeli');
        const sale = this._getFieldValue('satis_fiyati') || this._getFieldValue('fiyat');

        let months = null;
        let years = null;
        let yieldPercent = null;
        let tag = '-';

        if (rent && sale && rent > 0 && sale > 0) {
            months = sale / rent;
            years = months / 12;
            yieldPercent = ((rent * 12) / sale) * 100;

            const m = Math.round(months);
            if (m >= 180 && m <= 220) tag = 'Hızlı Geri Dönüş';
            else if (m >= 240) tag = 'Standart Yatırım';
            else if (m < 180) tag = 'Premium Yatırım';
        }

        this._updateElement('cr_months', months ? `${Math.round(months)} ay` : '-');
        this._updateElement('cr_years', years ? `${years.toFixed(1)} yıl` : '-');
        this._updateElement('cr_yield', yieldPercent ? `${yieldPercent.toFixed(1)}%` : '-');
        this._updateElement('cr_tag', tag);
        this._updateElement('cr_rent', rent ? this._formatCurrency(rent) : '-');
        this._updateElement('cr_sale', sale ? this._formatCurrency(sale) : '-');
    }

    /**
     * Calculate and display land calculator (arsa)
     */
    updateLandCalculator() {
        const m2 = this._getFieldValue('alan_m2');
        const price = this._getFieldValue('fiyat');
        const taks = this._getFieldValue('taks');
        const kaks = this._getFieldValue('kaks');

        let pricePerM2 = null;
        let taksArea = null;
        let kaksArea = null;
        let score = null;

        if (m2 && m2 > 0 && price && price > 0) {
            pricePerM2 = price / m2;
        }

        if (m2 && taks) {
            taksArea = m2 * taks;
        }

        if (m2 && kaks) {
            kaksArea = m2 * kaks;
        }

        // Calculate investment score
        if (pricePerM2) {
            const invPriceFactor = Math.max(0, Math.min(10, 100000 / pricePerM2));
            const imarFactor = Math.min(10, (taks || 0) * 10 * 0.5 + (kaks || 0) * 10 * 0.5);
            score = Math.max(1, Math.min(10, invPriceFactor * 0.4 + imarFactor * 0.6));
        }

        this._updateElement(
            'lc_price_m2',
            pricePerM2 ? this._formatCurrency(Math.round(pricePerM2)) : '-'
        );
        this._updateElement('lc_taks_area', taksArea ? `${Math.round(taksArea)} m²` : '-');
        this._updateElement('lc_kaks_area', kaksArea ? `${Math.round(kaksArea)} m²` : '-');
        this._updateElement('lc_score', score ? `${score.toFixed(1)}/10` : '-');
    }

    /**
     * Update Cortex investment summary
     */
    updateCortexSummary() {
        const roiText = document.getElementById('sp_roi')?.textContent || '-';
        const yieldText = document.getElementById('cr_yield')?.textContent || '-';
        const pm2Text = document.getElementById('lc_price_m2')?.textContent || '-';

        this._updateElement('cx_roi', roiText);
        this._updateElement('cx_yield', yieldText);
        this._updateElement('cx_pm2', pm2Text);

        // Compute badge
        const badge = this._computeCortexBadge(roiText, yieldText, pm2Text);
        this._updateElement('cx_badge', badge);

        // Store result for quality gate
        window.ilanWizardQualityResult = {
            recommendation: badge === 'A+' || badge === 'A' ? 'allow' : 'warn',
            badge,
        };
    }

    /**
     * Compute Cortex investment badge
     * @private
     */
    _computeCortexBadge(roiText, yieldText, pm2Text) {
        const numYears = (() => {
            const m = String(roiText || '').match(/([\d.]+)\s*yıl/);
            return m ? parseFloat(m[1]) : null;
        })();

        const yieldVal = (() => {
            const m = String(yieldText || '').match(/([\d.]+)%/);
            return m ? parseFloat(m[1]) : null;
        })();

        const pm2Val = (() => {
            const val = pm2Text ? pm2Text.replace(/[^\d]/g, '') : '';
            const n = parseFloat(val);
            return isNaN(n) ? null : n;
        })();

        if (numYears !== null && numYears <= 10) return 'A+';
        if (yieldVal !== null && yieldVal >= 6) return 'A+';
        if (numYears !== null && numYears <= 15) return 'A';
        if (yieldVal !== null && yieldVal >= 4) return 'A';
        if (pm2Val !== null && pm2Val <= 10000) return 'A';
        return 'B';
    }

    /**
     * Update element text content
     * @private
     */
    _updateElement(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    /**
     * Format currency
     * @private
     */
    _formatCurrency(value) {
        return new Intl.NumberFormat('tr-TR').format(value);
    }

    /**
     * Get current price
     * @returns {number}
     */
    getPrice() {
        return this._getFieldValue('fiyat') || 0;
    }

    /**
     * Get currency
     * @returns {string}
     */
    getCurrency() {
        const select = document.getElementById('para_birimi');
        return select?.value || CONFIG.defaultCurrency;
    }

    /**
     * Set price programmatically
     * @param {number} price
     */
    setPrice(price) {
        const input = document.getElementById('fiyat');
        if (input) {
            input.value = this.formatPrice(price);
            WizardState.updateFormField('fiyat', price);
            this.updateCalculations();
        }
    }
}

// Singleton instance
export const PriceManager = new PriceManagerClass();

// Global export
if (typeof window !== 'undefined') {
    window.YalihanWizard = window.YalihanWizard || {};
    window.YalihanWizard.price = PriceManager;
}

export default PriceManager;
