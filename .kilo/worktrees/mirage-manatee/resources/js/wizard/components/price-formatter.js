/**
 * Price Formatter Module
 * Context7: Turkish locale formatting with thousand separators and text conversion
 */

export class PriceFormatter {
    constructor() {
        this.currencySymbols = {
            TRY: '₺',
            USD: '$',
            EUR: '€',
            GBP: '£',
        };

        this.currencyNames = {
            TRY: 'Türk Lirası',
            USD: 'Amerikan Doları',
            EUR: 'Euro',
            GBP: 'İngiliz Sterlini',
        };
    }

    /**
     * Format number with thousand separators (Turkish locale)
     * @param {string|number} value - Raw value
     * @returns {string} Formatted value (e.g., "27.000.000")
     */
    formatWithSeparators(value) {
        // Remove all non-digit characters
        const cleaned = String(value).replace(/[^\d]/g, '');

        if (!cleaned) return '';

        // Convert to integer and format with Turkish locale
        return parseInt(cleaned).toLocaleString('tr-TR');
    }

    /**
     * Get raw value (remove separators)
     * @param {string} formattedValue - Formatted value with separators
     * @returns {number} Raw numeric value
     */
    getRawValue(formattedValue) {
        const cleaned = String(formattedValue).replace(/\./g, '');
        return parseInt(cleaned) || 0;
    }

    /**
     * Convert price to text (Turkish)
     * @param {number} price - Numeric price
     * @param {string} currency - Currency code
     * @returns {string} Price in words
     */
    convertToText(price, currency = 'TRY') {
        if (!price || price <= 0) return '';

        const currencyName = this.currencyNames[currency] || 'Türk Lirası';

        // Millions
        if (price >= 1000000) {
            const milyon = Math.floor(price / 1000000);
            const kalan = price % 1000000;

            if (kalan > 0) {
                return `${milyon} Milyon ${kalan.toLocaleString('tr-TR')} ${currencyName}`;
            }
            return `${milyon} Milyon ${currencyName}`;
        }

        // Thousands
        if (price >= 1000) {
            const bin = Math.floor(price / 1000);
            const kalan = price % 1000;

            if (kalan > 0) {
                return `${bin} Bin ${kalan.toLocaleString('tr-TR')} ${currencyName}`;
            }
            return `${bin} Bin ${currencyName}`;
        }

        return `${price.toLocaleString('tr-TR')} ${currencyName}`;
    }

    /**
     * Calculate unit price per m²
     * @param {number} totalPrice - Total price
     * @param {number} area - Area in m²
     * @returns {number} Unit price per m²
     */
    calculateUnitPrice(totalPrice, area) {
        if (!area || area <= 0) return 0;
        return totalPrice / area;
    }

    /**
     * Format unit price with currency
     * @param {number} unitPrice - Unit price per m²
     * @param {string} currency - Currency code
     * @returns {string} Formatted unit price
     */
    formatUnitPrice(unitPrice, currency = 'TRY') {
        if (!unitPrice || unitPrice <= 0) return '';

        const formatted = new Intl.NumberFormat('tr-TR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(unitPrice);

        const symbol = this.currencySymbols[currency] || '₺';
        return `${formatted} ${symbol}`;
    }

    /**
     * Get currency symbol
     * @param {string} currency - Currency code
     * @returns {string} Currency symbol
     */
    getCurrencySymbol(currency = 'TRY') {
        return this.currencySymbols[currency] || '₺';
    }

    /**
     * Attach to input element with auto-formatting
     * @param {HTMLInputElement} input - Input element
     * @param {HTMLInputElement} hiddenInput - Hidden input for raw value
     * @param {Function} onUpdate - Callback after update
     */
    attachToInput(input, hiddenInput, onUpdate = null) {
        if (!input) return;

        input.addEventListener('input', (e) => {
            const formatted = this.formatWithSeparators(e.target.value);
            input.value = formatted;

            if (hiddenInput) {
                hiddenInput.value = this.getRawValue(formatted);
            }

            if (onUpdate && typeof onUpdate === 'function') {
                onUpdate(this.getRawValue(formatted));
            }
        });
    }
}

// Export singleton instance
export const priceFormatter = new PriceFormatter();

// Global window exposure for Blade templates
if (typeof window !== 'undefined') {
    window.PriceFormatter = PriceFormatter;
    window.priceFormatter = priceFormatter;
}
