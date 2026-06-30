/**
 * AI Quality Check - Feature Module
 *
 * @module wizard/features/ai-quality-check
 * @version 3.0.0
 * @since 2026-01-28
 * @context7 Modüler Wizard Mimarisi - SEALED
 *
 * AI-powered listing quality assessment with publish gate.
 */

import { WizardEventBus, WizardEventTypes } from '../core/wizard-events.js';
import { WizardState } from '../core/wizard-state.js';

/**
 * Configuration
 */
const CONFIG = {
    endpoint: '/api/v1/cortex/quality/analyze',
    minScore: 60, // Minimum score to publish
    criticalFields: ['baslik', 'aciklama', 'fiyat', 'fotograflar'],
};

/**
 * Quality criteria and weights
 */
const QUALITY_CRITERIA = {
    title: {
        weight: 15,
        checks: [
            { name: 'length', min: 20, max: 100, message: 'Başlık 20-100 karakter olmalı' },
            { name: 'hasLocation', message: 'Başlıkta konum bilgisi olmalı' },
            { name: 'noSpam', message: 'Spam kelimeler kullanılmamalı' },
        ],
    },
    description: {
        weight: 25,
        checks: [
            { name: 'length', min: 100, max: 5000, message: 'Açıklama en az 100 karakter olmalı' },
            { name: 'hasFeatures', message: 'Açıklama özellik içermeli' },
            { name: 'grammar', message: 'Dilbilgisi hataları düzeltilmeli' },
        ],
    },
    photos: {
        weight: 20,
        checks: [
            { name: 'count', min: 3, max: 20, message: 'En az 3 fotoğraf yükleyin' },
            { name: 'quality', message: 'Yüksek kalite fotoğraflar kullanın' },
            { name: 'variety', message: 'Farklı açılardan fotoğraf ekleyin' },
        ],
    },
    price: {
        weight: 15,
        checks: [
            { name: 'valid', message: 'Geçerli bir fiyat girin' },
            { name: 'market', message: 'Piyasa değerine uygun olmalı' },
        ],
    },
    location: {
        weight: 15,
        checks: [
            { name: 'complete', message: 'Konum bilgileri eksiksiz olmalı' },
            { name: 'coordinates', message: 'Harita üzerinde konum seçin' },
        ],
    },
    details: {
        weight: 10,
        checks: [
            { name: 'requiredFields', message: 'Zorunlu alanları doldurun' },
            { name: 'features', message: 'Özellikler seçin' },
        ],
    },
};

/**
 * Spam words to detect
 */
const SPAM_WORDS = [
    'acil',
    'fırsat',
    'kaçmaz',
    'son şans',
    'hemen ara',
    'müthiş',
    'inanılmaz',
    '!!!',
    '???',
    'BÜYÜK HARF',
    'ACİL SATILIK',
];

/**
 * AIQualityCheck - Feature Controller
 */
class AIQualityCheckClass {
    constructor() {
        /** @private */
        this._loading = false;

        /** @private */
        this._result = null;

        /** @private */
        this._overrideBlock = false;

        /** @private */
        this._initialized = false;
    }

    /**
     * Initialize quality check
     */
    init() {
        if (this._initialized) return;

        this._initialized = true;
        console.log('[AIQualityCheck] Initialized');
    }

    /**
     * Run full quality check
     * @returns {Promise<Object>}
     */
    async runCheck() {
        if (this._loading) return this._result;

        this._loading = true;
        this._updateUI();

        try {
            // First run local checks
            const localResult = this._runLocalChecks();

            // Then call AI endpoint for advanced analysis
            const aiResult = await this._runAICheck();

            // Merge results
            this._result = this._mergeResults(localResult, aiResult);

            // Store in state
            WizardState.set('aiQualityResult', this._result);
            window.ilanWizardQualityResult = this._result;

            // Emit event
            WizardEventBus.emit(WizardEventTypes.AI_QUALITY_CHECKED, {
                result: this._result,
            });

            return this._result;
        } catch (error) {
            console.error('[AIQualityCheck] Check error:', error);
            // Fall back to local only
            this._result = this._runLocalChecks();
            return this._result;
        } finally {
            this._loading = false;
            this._updateUI();
        }
    }

    /**
     * Run local quality checks
     * @private
     */
    _runLocalChecks() {
        const scores = {};
        const issues = [];
        const suggestions = [];

        // Title checks
        const title = document.getElementById('baslik')?.value || '';
        scores.title = this._checkTitle(title, issues, suggestions);

        // Description checks
        const description = document.getElementById('aciklama')?.value || '';
        scores.description = this._checkDescription(description, issues, suggestions);

        // Photo checks
        const photoCount = WizardState.get('photos')?.length || 0;
        scores.photos = this._checkPhotos(photoCount, issues, suggestions);

        // Price checks
        const price = document.getElementById('fiyat')?.value;
        scores.price = this._checkPrice(price, issues, suggestions);

        // Location checks
        scores.location = this._checkLocation(issues, suggestions);

        // Details checks
        scores.details = this._checkDetails(issues, suggestions);

        // Calculate total score
        let totalScore = 0;
        let totalWeight = 0;

        Object.entries(scores).forEach(([key, score]) => {
            const weight = QUALITY_CRITERIA[key]?.weight || 10;
            totalScore += score * weight;
            totalWeight += weight;
        });

        const overallScore = Math.round(totalScore / totalWeight);

        return {
            score: overallScore,
            scores,
            issues,
            suggestions,
            canPublish: overallScore >= CONFIG.minScore,
            recommendation: overallScore >= 80 ? 'allow' : overallScore >= 60 ? 'warn' : 'block',
            badge: overallScore >= 80 ? 'A' : overallScore >= 60 ? 'B' : 'C',
        };
    }

    /**
     * Check title quality
     * @private
     */
    _checkTitle(title, issues, suggestions) {
        let score = 100;

        if (!title || title.length < 20) {
            score -= 40;
            issues.push('Başlık çok kısa (min 20 karakter)');
            suggestions.push('Konum ve özellik bilgisi ekleyin');
        } else if (title.length > 100) {
            score -= 20;
            issues.push('Başlık çok uzun (max 100 karakter)');
        }

        // Check for spam
        const titleLower = title.toLowerCase();
        const hasSpam = SPAM_WORDS.some((word) => titleLower.includes(word.toLowerCase()));
        if (hasSpam) {
            score -= 30;
            issues.push('Başlıkta spam kelimeler var');
            suggestions.push('Profesyonel bir dil kullanın');
        }

        // Check for ALL CAPS
        if (title === title.toUpperCase() && title.length > 5) {
            score -= 20;
            issues.push('Başlık tamamen büyük harfle yazılmış');
        }

        return Math.max(0, score);
    }

    /**
     * Check description quality
     * @private
     */
    _checkDescription(description, issues, suggestions) {
        let score = 100;

        if (!description || description.length < 100) {
            score -= 50;
            issues.push('Açıklama çok kısa (min 100 karakter)');
            suggestions.push('Detaylı bir açıklama yazın');
        } else if (description.length < 300) {
            score -= 20;
            suggestions.push('Daha detaylı bir açıklama ilanınızı ön plana çıkarır');
        }

        // Check for feature keywords
        const featureKeywords = [
            'oda',
            'banyo',
            'mutfak',
            'salon',
            'balkon',
            'otopark',
            'havuz',
            'manzara',
        ];
        const hasFeatures = featureKeywords.some((kw) => description.toLowerCase().includes(kw));
        if (!hasFeatures && description.length > 100) {
            score -= 15;
            suggestions.push('Özellik bilgilerini açıklamaya ekleyin');
        }

        return Math.max(0, score);
    }

    /**
     * Check photos quality
     * @private
     */
    _checkPhotos(count, issues, suggestions) {
        let score = 100;

        if (count === 0) {
            score = 0;
            issues.push('Fotoğraf yüklenmemiş');
            suggestions.push('En az 3 fotoğraf yükleyin');
        } else if (count < 3) {
            score -= 40;
            issues.push(`Sadece ${count} fotoğraf var (min 3 önerilir)`);
            suggestions.push('Daha fazla fotoğraf ekleyin');
        } else if (count < 5) {
            score -= 15;
            suggestions.push('5+ fotoğraf ile daha fazla ilgi çekersiniz');
        }

        return Math.max(0, score);
    }

    /**
     * Check price quality
     * @private
     */
    _checkPrice(price, issues, suggestions) {
        let score = 100;

        if (!price) {
            score = 0;
            issues.push('Fiyat girilmemiş');
        } else {
            const numPrice = parseFloat(String(price).replace(/\./g, '').replace(',', '.'));
            if (isNaN(numPrice) || numPrice <= 0) {
                score = 0;
                issues.push('Geçersiz fiyat');
            }
        }

        return Math.max(0, score);
    }

    /**
     * Check location quality
     * @private
     */
    _checkLocation(issues, suggestions) {
        let score = 100;

        const il = document.getElementById('il_id')?.value;
        const ilce = document.getElementById('ilce_id')?.value;
        const mahalle = document.getElementById('mahalle_id')?.value;
        const lat = WizardState.get('location.lat');
        const lng = WizardState.get('location.lng');

        if (!il) {
            score -= 40;
            issues.push('İl seçilmemiş');
        }

        if (!ilce) {
            score -= 30;
            issues.push('İlçe seçilmemiş');
        }

        if (!mahalle) {
            score -= 15;
            suggestions.push('Mahalle seçimi ilanınızı daha görünür kılar');
        }

        if (!lat || !lng) {
            score -= 15;
            suggestions.push('Haritadan konum seçin');
        }

        return Math.max(0, score);
    }

    /**
     * Check details quality
     * @private
     */
    _checkDetails(issues, suggestions) {
        let score = 100;

        // Check for selected features
        const featureCheckboxes = document.querySelectorAll(
            '[data-feature-checkbox]:checked, [name^="feature_"]:checked'
        );
        if (featureCheckboxes.length === 0) {
            score -= 30;
            suggestions.push('Özellik seçimi yapın');
        } else if (featureCheckboxes.length < 5) {
            score -= 10;
            suggestions.push('Daha fazla özellik seçerek ilanınızı zenginleştirin');
        }

        return Math.max(0, score);
    }

    /**
     * Run AI-powered quality check
     * @private
     */
    async _runAICheck() {
        try {
            const formData = this._gatherFormData();

            const response = await fetch(CONFIG.endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.content || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify(formData),
            });

            if (!response.ok) {
                throw new Error('AI check failed');
            }

            return await response.json();
        } catch (error) {
            console.warn('[AIQualityCheck] AI check unavailable:', error);
            return null;
        }
    }

    /**
     * Gather form data for AI check
     * @private
     */
    _gatherFormData() {
        return {
            baslik: document.getElementById('baslik')?.value,
            aciklama: document.getElementById('aciklama')?.value,
            fiyat: document.getElementById('fiyat')?.value,
            kategori_id: document.getElementById('alt_kategori_id')?.value,
            junction_id: document.getElementById('junction_id')?.value,
            photo_count: WizardState.get('photos')?.length || 0,
        };
    }

    /**
     * Merge local and AI results
     * @private
     */
    _mergeResults(local, ai) {
        if (!ai || !ai.success) {
            return local;
        }

        // Merge AI suggestions
        const aiSuggestions = ai.data?.suggestions || [];

        return {
            ...local,
            aiScore: ai.data?.score,
            aiSuggestions,
            suggestions: [...local.suggestions, ...aiSuggestions],
        };
    }

    /**
     * Check if can publish
     * @returns {boolean}
     */
    canPublish() {
        if (this._overrideBlock) return true;
        if (!this._result) return false;
        return this._result.canPublish;
    }

    /**
     * Override publish block
     * @param {boolean} override
     */
    setOverride(override) {
        this._overrideBlock = override;
        WizardState.set('aiOverrideBlock', override);
    }

    /**
     * Get result
     * @returns {Object|null}
     */
    getResult() {
        return this._result;
    }

    /**
     * Get score
     * @returns {number}
     */
    getScore() {
        return this._result?.score || 0;
    }

    /**
     * Get issues
     * @returns {string[]}
     */
    getIssues() {
        return this._result?.issues || [];
    }

    /**
     * Get suggestions
     * @returns {string[]}
     */
    getSuggestions() {
        return this._result?.suggestions || [];
    }

    /**
     * Is loading
     * @returns {boolean}
     */
    isLoading() {
        return this._loading;
    }

    /**
     * Update UI
     * @private
     */
    _updateUI() {
        const container = document.getElementById('quality-check-panel');
        if (!container || !this._result) return;

        const { score, issues, suggestions, badge } = this._result;

        // Update score display
        const scoreEl = container.querySelector('.quality-score');
        if (scoreEl) {
            scoreEl.textContent = score;
            scoreEl.className = scoreEl.className.replace(/text-\w+-\d+/g, '');
            if (score >= 80) scoreEl.classList.add('text-green-500');
            else if (score >= 60) scoreEl.classList.add('text-yellow-500');
            else scoreEl.classList.add('text-red-500');
        }

        // Update badge
        const badgeEl = container.querySelector('.quality-badge');
        if (badgeEl) {
            badgeEl.textContent = badge;
        }

        // Update issues list
        const issuesEl = container.querySelector('.quality-issues');
        if (issuesEl) {
            issuesEl.innerHTML = issues
                .map(
                    (issue) => `
                <li class="text-sm text-red-500 flex items-start gap-2">
                    <span>⚠️</span>
                    <span>${issue}</span>
                </li>
            `
                )
                .join('');
        }

        // Update suggestions list
        const suggestionsEl = container.querySelector('.quality-suggestions');
        if (suggestionsEl) {
            suggestionsEl.innerHTML = suggestions
                .map(
                    (sug) => `
                <li class="text-sm text-blue-500 flex items-start gap-2">
                    <span>💡</span>
                    <span>${sug}</span>
                </li>
            `
                )
                .join('');
        }
    }
}

// Singleton instance
export const AIQualityCheck = new AIQualityCheckClass();

// Global export
if (typeof window !== 'undefined') {
    window.YalihanWizard = window.YalihanWizard || {};
    window.YalihanWizard.quality = AIQualityCheck;

    // Backward compatibility
    window.aiQualityCheck = function () {
        return {
            loading: AIQualityCheck.isLoading(),
            result: AIQualityCheck.getResult(),
            overrideBlock: false,
            init() {
                AIQualityCheck.init();
            },
            async runCheck() {
                return await AIQualityCheck.runCheck();
            },
            canPublish() {
                return AIQualityCheck.canPublish();
            },
            getScore() {
                return AIQualityCheck.getScore();
            },
            getIssues() {
                return AIQualityCheck.getIssues();
            },
            getSuggestions() {
                return AIQualityCheck.getSuggestions();
            },
        };
    };
}

export default AIQualityCheck;
