/**
 * AI Integration Module for Wizard
 * Context7: AI-powered title generation, description, and SEO scoring
 */

export class AIIntegration {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        this.apiBaseUrl = '/api/v1/admin/ai';
    }

    /**
     * Generate AI-powered titles
     * @param {Object} context - Context data (category, location, etc.)
     * @returns {Promise<Array>} Array of title suggestions
     */
    async generateTitles(context) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/generate-title`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    kategori: context.kategori,
                    yayin_tipi: context.yayin_tipi,
                    il: context.il,
                    ilce: context.ilce,
                    mahalle: context.mahalle,
                    ozellikler: context.ozellikler || [],
                }),
            });

            const data = await response.json();

            if (data.success && data.titles) {
                return data.titles;
            }

            throw new Error(data.message || 'Başlık oluşturulamadı');
        } catch (error) {
            console.error('AI Title Generation Error:', error);
            throw error;
        }
    }

    /**
     * Generate AI-powered description
     * @param {Object} context - Context data
     * @returns {Promise<string>} Generated description
     */
    async generateDescription(context) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/generate-description`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    baslik: context.baslik,
                    kategori: context.kategori,
                    il: context.il,
                    ilce: context.ilce,
                    mahalle: context.mahalle,
                    fiyat: context.fiyat,
                    alan_m2: context.alan_m2,
                }),
            });

            const data = await response.json();

            if (data.success && data.description) {
                return data.description;
            }

            // Fallback: Template-based description
            return this.generateFallbackDescription(context);
        } catch (error) {
            console.error('AI Description Generation Error:', error);
            return this.generateFallbackDescription(context);
        }
    }

    /**
     * Fallback description generator (template-based)
     * @param {Object} context - Context data
     * @returns {string} Template description
     */
    generateFallbackDescription(context) {
        const parts = [];

        parts.push(
            `${context.kategori || 'Gayrimenkul'} kategorisinde, ${context.il || ''} ${context.ilce || ''}${context.mahalle ? ' ' + context.mahalle : ''} bölgesinde ${context.yayin_tipi || 'satılık'} ${context.baslik || 'ilan'}.`
        );

        if (context.alan_m2) {
            parts.push(`\nAlan: ${context.alan_m2} m²`);
        }

        if (context.fiyat) {
            const formatted = parseInt(context.fiyat).toLocaleString('tr-TR');
            parts.push(`Fiyat: ${formatted} TL`);
        }

        parts.push('\n\nDetaylı bilgi ve görüşme için iletişime geçiniz.');

        return parts.join('\n');
    }

    /**
     * Calculate SEO score for title
     * @param {string} title - Title text
     * @returns {number} SEO score (0-100)
     */
    calculateSEOScore(title) {
        if (!title) return 0;

        let score = 0;
        const length = title.length;

        // Length check (50-70 optimal)
        if (length >= 50 && length <= 70) {
            score += 40;
        } else if (length >= 40 && length <= 80) {
            score += 25;
        } else if (length >= 30 && length <= 90) {
            score += 15;
        }

        // Contains location words
        const locationWords = ['bodrum', 'yalıkavak', 'türkbükü', 'gümbet', 'bitez'];
        if (locationWords.some((word) => title.toLowerCase().includes(word))) {
            score += 20;
        }

        // Contains property type
        const propertyTypes = ['villa', 'daire', 'apart', 'residence', 'müstakil', 'dubleks'];
        if (propertyTypes.some((type) => title.toLowerCase().includes(type))) {
            score += 20;
        }

        // Contains descriptive words
        const descriptiveWords = ['lüks', 'deniz manzaralı', 'havuzlu', 'bahçeli', 'yeni'];
        const descriptiveCount = descriptiveWords.filter((word) =>
            title.toLowerCase().includes(word)
        ).length;
        score += Math.min(descriptiveCount * 5, 20);

        return Math.min(score, 100);
    }

    /**
     * Seal all fields (auto-fill with AI)
     * @param {Object} context - Form context
     * @returns {Promise<Object>} Sealed fields
     * @todo Backend route /api/v1/wizard/seal-all-fields not yet implemented
     */
    async sealAllFields(context) {
        console.warn('sealAllFields: Backend endpoint not yet implemented');
        return {};
    }
}

// Export singleton instance
export const aiIntegration = new AIIntegration();

// Global window exposure for Blade templates
if (typeof window !== 'undefined') {
    window.AIIntegration = AIIntegration;
    window.aiIntegration = aiIntegration;
}
