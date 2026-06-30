/**
 * Features AI Assistant Module
 * Context7: AI-powered feature suggestions for property listings
 *
 * SADECE İlan Create sayfası için kullanılır.
 * AIService (shared core) kullanarak özellik önerileri sunar.
 */

import { AIService } from '../services/AIService.js';

export class FeaturesAI {
    /**
     * Suggest All Features
     * Tüm özellikler için AI önerileri al
     *
     * @param {Object} context - Form context (category, area, location, etc.)
     * @returns {Promise<Object>} Feature suggestions
     */
    static async suggestAll(context) {
        try {
            // Show loading state
            this.showLoadingState();

            // ORTAK CORE kullanılıyor!
            const result = await AIService.suggestFeatureValues(context);

            if (result.success && result.data) {
                this.applysuggestions(result.data);

                // Show success toast
                if (window.toast) {
                    window.toast.success(
                        `✨ ${result.data.suggested_count || 0} özellik AI tarafından önerildi!`
                    );
                }

                return result;
            } else {
                throw new Error(result.message || 'AI önerisi alınamadı');
            }
        } catch (error) {
            console.error('FeaturesAI.suggestAll error:', error);

            // Show error toast
            if (window.toast) {
                window.toast.error(`❌ AI önerisi hatası: ${error.message}`);
            }

            return {
                success: false,
                message: error.message,
            };
        } finally {
            this.hideLoadingState();
        }
    }

    /**
     * Suggest Single Feature
     * Tek bir özellik için AI önerisi al
     *
     * @param {string} featureName - Feature name
     * @param {HTMLElement} featureElement - Feature input element
     * @param {Object} context - Form context
     * @returns {Promise<void>}
     */
    static async suggestSingle(featureName, featureElement, context = {}) {
        if (!featureElement) return;

        try {
            // Show loading on button
            const button = featureElement
                .closest('.feature-group')
                ?.querySelector('.ai-suggest-btn');
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }

            // Get form context
            const formContext = this.getFormContext();
            const fullContext = { ...formContext, ...context };

            // ORTAK CORE kullanılıyor!
            const result = await AIService.suggestSingleFeature(featureName, fullContext);

            if (result.success && result.data) {
                this.applySingleSuggestion(featureElement, result.data);

                // Show success animation
                this.showSuccessAnimation(featureElement);

                // Show toast
                if (window.toast) {
                    window.toast.success(`✅ ${featureName} için AI önerisi uygulandı!`);
                }
            } else {
                throw new Error(result.message || 'Öneri alınamadı');
            }
        } catch (error) {
            console.error('FeaturesAI.suggestSingle error:', error);

            if (window.toast) {
                window.toast.error(`❌ ${error.message}`);
            }
        } finally {
            // Reset button
            const button = featureElement
                .closest('.feature-group')
                ?.querySelector('.ai-suggest-btn');
            if (button) {
                button.disabled = false;
                button.innerHTML = '<i class="fas fa-magic"></i>';
            }
        }
    }

    /**
     * Get Smart Defaults
     * Kategori için akıllı varsayılan değerler al
     *
     * @param {number} categoryId - Category ID
     * @returns {Promise<Object>} Smart defaults
     */
    static async getSmartDefaults(categoryId) {
        if (!categoryId) return null;

        try {
            // Get location and other filters
            const filters = {
                location: document.getElementById('mahalle_id')?.value || null,
                price: document.getElementById('fiyat')?.value || null,
                area: document.getElementById('brut_m2')?.value || null,
            };

            // ORTAK CORE kullanılıyor!
            const result = await AIService.getSmartDefaults(categoryId, filters);

            if (result.success && result.data) {
                return result.data;
            }

            return null;
        } catch (error) {
            console.error('FeaturesAI.getSmartDefaults error:', error);
            return null;
        }
    }

    /**
     * Apply Suggestions
     * AI önerilerini form'a uygula
     *
     * @param {Object} suggestions - Suggestions data
     */
    static applySuggestions(suggestions) {
        if (!suggestions || typeof suggestions !== 'object') return;

        Object.keys(suggestions).forEach((featureName) => {
            const value = suggestions[featureName];
            const input = document.querySelector(`[name="features[${featureName}]"]`);

            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = Boolean(value);
                } else {
                    input.value = value;
                }

                // Trigger change event
                input.dispatchEvent(new Event('change', { bubbles: true }));

                // Visual feedback
                this.showSuccessAnimation(input);
            }
        });
    }

    /**
     * Apply Single Suggestion
     * Tek bir özellik önerisini uygula
     *
     * @param {HTMLElement} element - Input element
     * @param {*} value - Suggested value
     */
    static applySingleSuggestion(element, value) {
        if (!element) return;

        if (element.type === 'checkbox') {
            element.checked = Boolean(value);
        } else if (element.tagName === 'SELECT') {
            element.value = value;
        } else {
            element.value = value;
        }

        // Trigger change event
        element.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /**
     * Get Form Context
     * Formdan context bilgilerini al
     *
     * @returns {Object} Form context
     */
    static getFormContext() {
        return {
            category_id: document.getElementById('ana_kategori')?.value || null,
            sub_category_id: document.getElementById('alt_kategori')?.value || null,
            publication_type_id: document.getElementById('junction_id')?.value || null,
            area: document.getElementById('brut_m2')?.value || null,
            net_area: document.getElementById('net_m2')?.value || null,
            location: {
                // Context7: sehir_id → il_id (forbidden pattern)
                il_id: document.getElementById('il_id')?.value || null,
                district_id: document.getElementById('ilce_id')?.value || null,
                neighborhood_id: document.getElementById('mahalle_id')?.value || null,
            },
            price: document.getElementById('fiyat')?.value || null,
            currency: document.getElementById('para_birimi')?.value || 'TRY',
        };
    }

    /**
     * Show Loading State
     * Yükleme durumunu göster
     */
    static showLoadingState() {
        const container = document.getElementById('features-container');
        if (container) {
            container.classList.add('ai-loading');
        }

        // Disable all AI buttons
        document.querySelectorAll('.ai-suggest-btn').forEach((btn) => {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        });
    }

    /**
     * Hide Loading State
     * Yükleme durumunu gizle
     */
    static hideLoadingState() {
        const container = document.getElementById('features-container');
        if (container) {
            container.classList.remove('ai-loading');
        }

        // Enable all AI buttons
        document.querySelectorAll('.ai-suggest-btn').forEach((btn) => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-magic"></i>';
        });
    }

    /**
     * Show Success Animation
     * Başarı animasyonu göster
     *
     * @param {HTMLElement} element - Target element
     */
    static showSuccessAnimation(element) {
        if (!element) return;

        element.classList.add('ai-suggested');

        setTimeout(() => {
            element.classList.remove('ai-suggested');
        }, 2000);
    }

    /**
     * Create AI Button
     * AI öneri butonu oluştur
     *
     * @param {string} featureName - Feature name
     * @param {HTMLElement} targetElement - Target input element
     * @returns {HTMLElement} AI button
     */
    static createAIButton(featureName, targetElement) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className =
            'ai-suggest-btn inline-flex items-center px-2 py-1 text-xs font-medium text-purple-600 dark:text-purple-400 hover:text-purple-700 dark:hover:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded transition-colors';
        button.title = 'AI ile öner';
        button.innerHTML =
            '<i class="fas fa-magic mr-1"></i><span class="hidden sm:inline">AI</span>';

        button.addEventListener('click', () => {
            this.suggestSingle(featureName, targetElement);
        });

        return button;
    }

    /**
     * Initialize AI Buttons
     * Tüm özellikler için AI butonları ekle
     */
    static initializeAIButtons() {
        const featureInputs = document.querySelectorAll('[name^="features["]');

        featureInputs.forEach((input) => {
            // Feature name'i parse et
            const nameMatch = input.name.match(/features\[(.+?)\]/);
            if (!nameMatch) return;

            const featureName = nameMatch[1];

            // AI button ekle
            const button = this.createAIButton(featureName, input);

            // Button'u input'un yanına ekle
            const parent = input.parentElement;
            if (parent) {
                const wrapper = document.createElement('div');
                wrapper.className = 'flex items-center gap-2';

                parent.insertBefore(wrapper, input);
                wrapper.appendChild(input);
                wrapper.appendChild(button);
            }
        });

        console.log('✅ AI buttons initialized for features');
    }
}

export default FeaturesAI;
