/**
 * AI Title Generator - Feature Module
 *
 * @module wizard/features/ai-title-generator
 * @version 3.0.0
 * @since 2026-01-28
 * @context7 Modüler Wizard Mimarisi - SEALED
 *
 * AI-powered listing title generation with SEO scoring.
 */

import { WizardEventBus, WizardEventTypes } from '../core/wizard-events.js';
import { WizardState } from '../core/wizard-state.js';

/**
 * Configuration
 */
const CONFIG = {
    endpoint: '/api/v1/cortex/title/generate',
    maxTitles: 5,
    maxTitleLength: 100,
    minTitleLength: 20,
    historyKey: 'aiTitleHistory',
    maxHistory: 10,
};

/**
 * SEO scoring weights
 */
const SEO_WEIGHTS = {
    hasLocation: 15,
    hasCategory: 15,
    hasKeyFeature: 15,
    hasPrice: 10,
    optimalLength: 15,
    hasEmoji: 5,
    hasPowerWord: 10,
    noRepetition: 15,
};

/**
 * Power words for Turkish real estate
 */
const POWER_WORDS = [
    'muhteşem',
    'eşsiz',
    'lüks',
    'özel',
    'benzersiz',
    'premium',
    'fırsat',
    'acil',
    'yatırım',
    'kazançlı',
    'değerli',
    'deniz manzaralı',
    'havuzlu',
    'merkezi',
    'yeni',
    'bakımlı',
];

/**
 * AITitleGenerator - Feature Controller
 */
class AITitleGeneratorClass {
    constructor() {
        /** @private */
        this._loading = false;

        /** @private */
        this._titles = [];

        /** @private */
        this._selectedTitle = '';

        /** @private */
        this._history = [];

        /** @private */
        this._initialized = false;
    }

    /**
     * Initialize title generator
     */
    init() {
        if (this._initialized) return;

        this._loadHistory();
        this._setupListeners();

        this._initialized = true;
        console.log('[AITitleGenerator] Initialized');
    }

    /**
     * Setup event listeners
     * @private
     */
    _setupListeners() {
        const titleInput = document.getElementById('baslik');
        if (titleInput) {
            titleInput.addEventListener('input', () => {
                this._selectedTitle = titleInput.value;
                this.updateSEOScore();
            });
        }
    }

    /**
     * Load title history from localStorage
     * @private
     */
    _loadHistory() {
        try {
            const saved = localStorage.getItem(CONFIG.historyKey);
            if (saved) {
                this._history = JSON.parse(saved);
            }
        } catch (error) {
            console.error('[AITitleGenerator] Load history error:', error);
        }
    }

    /**
     * Save title history
     * @private
     */
    _saveHistory() {
        try {
            localStorage.setItem(
                CONFIG.historyKey,
                JSON.stringify(this._history.slice(0, CONFIG.maxHistory))
            );
        } catch (error) {
            console.error('[AITitleGenerator] Save history error:', error);
        }
    }

    /**
     * Check if can generate titles
     * @returns {boolean}
     */
    canGenerate() {
        const kategori =
            WizardState.get('altKategoriId') || document.getElementById('alt_kategori_id')?.value;
        const yayinTipi =
            WizardState.get('junctionId') || document.getElementById('junction_id')?.value;
        return !!(kategori && yayinTipi);
    }

    /**
     * Generate AI titles
     * @returns {Promise<string[]>}
     */
    async generateTitles() {
        if (!this.canGenerate()) {
            this._showNotification('Önce kategori ve yayın tipi seçin', 'warning');
            return [];
        }

        if (this._loading) return [];

        this._loading = true;
        this._updateUI();

        try {
            const context = this._gatherContext();

            const response = await fetch(CONFIG.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.content || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify(context),
            });

            const result = await response.json();

            if (result.success && Array.isArray(result.data?.titles)) {
                this._titles = result.data.titles.slice(0, CONFIG.maxTitles);
            } else if (Array.isArray(result.titles)) {
                this._titles = result.titles.slice(0, CONFIG.maxTitles);
            } else {
                // Fallback: generate local titles
                this._titles = this._generateLocalTitles(context);
            }

            WizardEventBus.emit(WizardEventTypes.AI_TITLE_GENERATED, {
                titles: this._titles,
                context,
            });

            return this._titles;
        } catch (error) {
            console.error('[AITitleGenerator] Generate error:', error);
            // Fallback to local generation
            const context = this._gatherContext();
            this._titles = this._generateLocalTitles(context);
            return this._titles;
        } finally {
            this._loading = false;
            this._updateUI();
        }
    }

    /**
     * Gather context for title generation
     * @private
     */
    _gatherContext() {
        const form = document.getElementById('ilan-wizard-form');

        return {
            kategori_id: document.getElementById('alt_kategori_id')?.value,
            junction_id: document.getElementById('junction_id')?.value,
            kategori_slug: WizardState.get('kategoriSlug'),
            yayin_tipi_slug: WizardState.get('yayinTipiSlug'),
            il: document.getElementById('il_id')?.selectedOptions[0]?.text,
            ilce: document.getElementById('ilce_id')?.selectedOptions[0]?.text,
            mahalle: document.getElementById('mahalle_id')?.selectedOptions[0]?.text,
            fiyat: document.getElementById('fiyat')?.value,
            alan_m2: form?.querySelector('[name="alan_m2"]')?.value,
            oda_sayisi: form?.querySelector('[name="oda_sayisi"]')?.value,
            features: this._gatherFeatures(),
        };
    }

    /**
     * Gather selected features
     * @private
     */
    _gatherFeatures() {
        const features = [];
        document
            .querySelectorAll('[data-feature-checkbox]:checked, [name^="feature_"]:checked')
            .forEach((cb) => {
                const label = cb.closest('label')?.textContent?.trim() || cb.dataset.featureName;
                if (label) features.push(label);
            });
        return features;
    }

    /**
     * Generate titles locally (fallback)
     * @private
     */
    _generateLocalTitles(context) {
        const templates = [
            `${context.ilce || 'Bodrum'}'da ${context.kategori_slug || 'Satılık Daire'}`,
            `${context.mahalle || ''} ${context.alan_m2 ? context.alan_m2 + ' m²' : ''} ${context.kategori_slug || 'Ev'}`.trim(),
            `Yatırımlık ${context.kategori_slug || 'Gayrimenkul'} - ${context.ilce || 'Merkez'}`,
            `${context.oda_sayisi ? context.oda_sayisi + '+1' : ''} ${context.kategori_slug || ''} ${context.ilce || 'Bodrum'}'da`.trim(),
            `Fırsat! ${context.mahalle || context.ilce || ''} ${context.kategori_slug || 'Ev'}`.trim(),
        ];

        return templates.filter((t) => t.length >= CONFIG.minTitleLength).slice(0, 3);
    }

    /**
     * Select a title
     * @param {string} title
     */
    selectTitle(title) {
        this._selectedTitle = title;

        const titleInput = document.getElementById('baslik');
        if (titleInput) {
            titleInput.value = title;
            titleInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // Add to history
        if (!this._history.includes(title)) {
            this._history.unshift(title);
            this._saveHistory();
        }

        WizardState.updateFormField('baslik', title);
        this.updateSEOScore();

        this._showNotification('Başlık seçildi', 'success');
    }

    /**
     * Calculate and update SEO score
     */
    updateSEOScore() {
        const title = this._selectedTitle || document.getElementById('baslik')?.value || '';
        const score = this.calculateSEOScore(title);

        // Update UI
        const scoreEl = document.getElementById('seo-score');
        const barEl = document.getElementById('seo-bar');
        const labelEl = document.getElementById('seo-label');

        if (scoreEl) scoreEl.textContent = score;

        if (barEl) {
            barEl.style.width = `${score}%`;
            barEl.className = barEl.className.replace(/bg-\w+-\d+/g, '');
            if (score >= 80) barEl.classList.add('bg-green-500');
            else if (score >= 60) barEl.classList.add('bg-yellow-500');
            else barEl.classList.add('bg-red-500');
        }

        if (labelEl) {
            if (score >= 80) labelEl.textContent = 'Mükemmel';
            else if (score >= 60) labelEl.textContent = 'İyi';
            else if (score >= 40) labelEl.textContent = 'Orta';
            else labelEl.textContent = 'Düşük';
        }

        return score;
    }

    /**
     * Calculate SEO score for a title
     * @param {string} title
     * @returns {number} 0-100
     */
    calculateSEOScore(title) {
        if (!title || title.length < 5) return 0;

        let score = 0;
        const titleLower = title.toLowerCase();

        // Check location
        const location = document
            .getElementById('ilce_id')
            ?.selectedOptions[0]?.text?.toLowerCase();
        if (location && titleLower.includes(location)) {
            score += SEO_WEIGHTS.hasLocation;
        }

        // Check category
        const kategori = WizardState.get('kategoriSlug')?.toLowerCase();
        if (
            kategori &&
            (titleLower.includes('satılık') ||
                titleLower.includes('kiralık') ||
                titleLower.includes('daire') ||
                titleLower.includes('villa') ||
                titleLower.includes('arsa'))
        ) {
            score += SEO_WEIGHTS.hasCategory;
        }

        // Check key features
        const hasFeature =
            titleLower.includes('havuz') ||
            titleLower.includes('manzara') ||
            titleLower.includes('deniz') ||
            titleLower.includes('merkez');
        if (hasFeature) {
            score += SEO_WEIGHTS.hasKeyFeature;
        }

        // Check optimal length (50-80 chars ideal)
        if (title.length >= 50 && title.length <= 80) {
            score += SEO_WEIGHTS.optimalLength;
        } else if (title.length >= 30 && title.length <= 100) {
            score += SEO_WEIGHTS.optimalLength * 0.5;
        }

        // Check for power words
        const hasPowerWord = POWER_WORDS.some((word) => titleLower.includes(word));
        if (hasPowerWord) {
            score += SEO_WEIGHTS.hasPowerWord;
        }

        // Check for no repetition (words shouldn't repeat)
        const words = title.toLowerCase().split(/\s+/);
        const uniqueWords = new Set(words);
        if (uniqueWords.size === words.length) {
            score += SEO_WEIGHTS.noRepetition;
        } else {
            score += SEO_WEIGHTS.noRepetition * (uniqueWords.size / words.length);
        }

        // Emoji bonus (light usage)
        const hasEmoji = /[\u{1F300}-\u{1F9FF}]/u.test(title);
        if (hasEmoji) {
            score += SEO_WEIGHTS.hasEmoji;
        }

        return Math.min(100, Math.round(score));
    }

    /**
     * Get generated titles
     * @returns {string[]}
     */
    getTitles() {
        return [...this._titles];
    }

    /**
     * Get title history
     * @returns {string[]}
     */
    getHistory() {
        return [...this._history];
    }

    /**
     * Check if loading
     * @returns {boolean}
     */
    isLoading() {
        return this._loading;
    }

    /**
     * Update UI state
     * @private
     */
    _updateUI() {
        const btn = document.getElementById('ai-title-btn');
        if (btn) {
            btn.disabled = this._loading;
            btn.innerHTML = this._loading
                ? '<span class="animate-spin">⏳</span> Oluşturuluyor...'
                : '🤖 AI Başlık Öner';
        }

        // Update titles container
        const container = document.getElementById('ai-titles-container');
        if (container && this._titles.length > 0) {
            container.innerHTML = this._titles
                .map(
                    (title, i) => `
                <button type="button"
                    onclick="YalihanWizard.aiTitle.selectTitle('${title.replace(/'/g,"\\'")}')"
                    class="w-full text-left px-4 py-2 rounded-lg border border-gray-200 hover:bg-blue-50 hover:border-blue-300 transition-all duration-200 dark:border-slate-700">
                    <span class="text-gray-900 dark:text-white dark:text-slate-100">${title}</span>
                    <span class="text-xs text-gray-500 ml-2 dark:text-slate-500">SEO: ${this.calculateSEOScore(title)}%</span>
                </button>
            `
                )
                .join('');
            container.classList.remove('hidden');
        }
    }

    /**
     * Show notification
     * @private
     */
    _showNotification(message, type = 'info') {
        if (window.showNotification) {
            window.showNotification(message, type);
        }
    }
}

// Singleton instance
export const AITitleGenerator = new AITitleGeneratorClass();

// Global export
if (typeof window !== 'undefined') {
    window.YalihanWizard = window.YalihanWizard || {};
    window.YalihanWizard.aiTitle = AITitleGenerator;

    // Backward compatibility
    window.aiTitleGenerator = function () {
        return {
            loading: AITitleGenerator.isLoading(),
            aiTitles: AITitleGenerator.getTitles(),
            showAiTitles: AITitleGenerator.getTitles().length > 0,
            selectedTitle: '',
            seoScore: 0,
            pastTitles: AITitleGenerator.getHistory(),
            showSuggestions: false,
            suggestions: [],
            init() {
                AITitleGenerator.init();
                this.selectedTitle = document.getElementById('baslik')?.value || '';
                this.updateSEOScore();
            },
            get canGenerate() {
                return AITitleGenerator.canGenerate();
            },
            updateSEOScore() {
                this.seoScore = AITitleGenerator.updateSEOScore();
            },
            async generateTitles() {
                const titles = await AITitleGenerator.generateTitles();
                this.aiTitles = titles;
                this.showAiTitles = titles.length > 0;
            },
            selectTitle(title) {
                AITitleGenerator.selectTitle(title);
                this.selectedTitle = title;
                this.updateSEOScore();
            },
            saveTitleHistory() {},
            logTelemetry() {},
        };
    };
}

export default AITitleGenerator;
