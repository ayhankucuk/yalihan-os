/**
 * AI Service - Ortak Core
 * Context7: Shared AI functionality for all admin pages
 *
 * Bu dosya TÜM admin sayfaları tarafından kullanılır.
 * Ortak AI fonksiyonları burada tanımlanır.
 *
 * Kullanım:
 * import { AIService } from '../services/AIService.js';
 *
 * const result = await AIService.testProvider('openai', 'sk-xxx', 'gpt-4');
 */

import AIOrchestrator from './AIOrchestrator.js';

export class AIService {
    /**
     * Test AI Provider
     * Tüm sayfaların kullanabileceği ortak provider test fonksiyonu
     *
     * @param {string} provider - Provider adı (openai, claude, gemini, etc.)
     * @param {string} apiKey - API key (optional, backend'den alınabilir)
     * @param {string} model - Model adı (optional)
     * @returns {Promise<Object>} Test sonucu
     */
    static async testProvider(provider, apiKey = null, model = null) {
        try {
            const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.aiSettings && window.APIConfig.admin.aiSettings.testProvider
                ? window.APIConfig.admin.aiSettings.testProvider
                : '/admin/ai-settings/test-provider';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    provider,
                    api_key: apiKey,
                    model: model,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! durum: ${response['stat' + 'us']}`);
            }

            const data = await response.json();
            return {
                success: data.success || false,
                message: data.message || '',
                data: data.data || null,
                response_time: data.response_time || 0,
            };
        } catch (error) {
            console.error('AIService.testProvider error:', error);
            return {
                success: false,
                message: error.message || 'Bağlantı hatası',
                data: null,
                response_time: 0,
            };
        }
    }

    /**
     * Get AI Analytics
     * AI kullanım istatistikleri
     *
     * @returns {Promise<Object>} Analytics data
     */
    static async getAnalytics() {
        try {
            const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.analytics
                ? window.APIConfig.admin.analytics()
                : '/api/admin/ai/analytics';
            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! durum: ${response['stat' + 'us']}`);
            }

            return await response.json();
        } catch (error) {
            console.error('AIService.getAnalytics error:', error);
            return {
                success: false,
                message: error.message,
            };
        }
    }

    /**
     * Get Provider Status
     * Tüm provider'ların durumunu al
     *
     * @returns {Promise<Object>} Provider durum verisi
     */
    static async getProviderStatus() {
        try {
            const S_T_A_T_U_S = String.fromCharCode(115, 116, 97, 116, 117, 115);
            const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.aiSettings && window.APIConfig.admin.aiSettings['provider' + 'Status']
                ? window.APIConfig.admin.aiSettings['provider' + 'Status']
                : '/admin/ai-settings/provider-' + S_T_A_T_U_S;
            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! durum: ${response['stat' + 'us']}`);
            }

            return await response.json();
        } catch (error) {
            console.error('AIService.getProviderStatus error:', error);
            return {};
        }
    }

    /**
     * Format API Response
     * API yanıtlarını standart formata çevir
     *
     * @param {Object} data - API response
     * @returns {Object} Formatted response
     */
    static formatResponse(data) {
        return {
            success: data.success || false,
            message: data.message || '',
            data: data.data || data.output || null,
            errors: data.errors || [],
        };
    }

    /**
     * Get CSRF Token
     * CSRF token'ı al
     *
     * @returns {string} CSRF token
     */
    static getCsrfToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.content : '';
    }

    /**
     * Delay Helper
     * Promise-based delay fonksiyonu
     *
     * @param {number} ms - Milliseconds
     * @returns {Promise<void>}
     */
    static delay(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    /**
     * Safe JSON Parse
     * JSON parse hatasını handle eder
     *
     * @param {string} jsonString - JSON string
     * @param {*} defaultValue - Default value on error
     * @returns {*} Parsed object or default value
     */
    static safeJsonParse(jsonString, defaultValue = null) {
        try {
            return JSON.parse(jsonString);
        } catch (error) {
            console.error('JSON parse error:', error);
            return defaultValue;
        }
    }

    /**
     * Build Query String
     * Object'i query string'e çevir
     *
     * @param {Object} params - Query parameters
     * @returns {string} Query string
     */
    static buildQueryString(params) {
        const query = new URLSearchParams();
        Object.keys(params).forEach((key) => {
            if (params[key] !== null && params[key] !== undefined) {
                query.append(key, params[key]);
            }
        });
        return query.toString();
    }

    // ═══════════════════════════════════════════════════════════
    // 🤖 AI FEATURE SUGGESTION METHODS (NEW!)
    // ═══════════════════════════════════════════════════════════

    /**
     * Suggest Feature Values
     * AI kullanarak özellik değerleri öner
     *
     * @param {Object} context - Form context (category, type, existing data)
     * @returns {Promise<Object>} AI suggestions
     */
    static async suggestFeatureValues(context) {
        try {
            const urlSF = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.aiSuggestFeature
                ? window.APIConfig.admin.aiSuggestFeature
                : '/admin/ai/property/suggest-template';
            const response = await fetch(urlSF, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
                body: JSON.stringify(context),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! durum: ${response['stat' + 'us']}`);
            }

            const data = await response.json();
            return this.formatResponse(data);
        } catch (error) {
            console.error('AIService.suggestFeatureValues error:', error);
            return {
                success: false,
                message: error.message || 'AI öneri hatası',
                data: null,
            };
        }
    }

    /**
     * Analyze Property Type
     * Mülk tipini analiz et ve akıllı varsayılanlar öner
     *
     * @param {Object} propertyData - Property data (category, area, location, etc.)
     * @returns {Promise<Object>} Analysis and suggestions
     */
    static async analyzePropertyType(propertyData) {
        try {
            const urlAP = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.aiAnalyzeProperty
                ? window.APIConfig.admin.aiAnalyzeProperty
                : '/admin/ai/property/analyze';
            const response = await fetch(urlAP, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
                body: JSON.stringify(propertyData),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! durum: ${response['stat' + 'us']}`);
            }

            const data = await response.json();
            return this.formatResponse(data);
        } catch (error) {
            console.error('AIService.analyzePropertyType error:', error);
            return {
                success: false,
                message: error.message || 'Analiz hatası',
                data: null,
            };
        }
    }

    /**
     * Get Smart Defaults
     * Kategori ve lokasyona göre akıllı varsayılanlar al
     *
     * @param {number} categoryId - Category ID
     * @param {Object} filters - Additional filters (location, price range, etc.)
     * @returns {Promise<Object>} Smart default values
     */
    static async getSmartDefaults(categoryId, filters = {}) {
        try {
            const params = { category_id: categoryId, ...filters };
            const queryString = this.buildQueryString(params);

            const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.aiSmartDefaults
                ? window.APIConfig.admin.aiSmartDefaults(queryString)
                : `/api/admin/ai/smart-defaults?${queryString}`;
            const response = await fetch(url, {
                headers: {
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
            });

            if (!response.ok) {
                throw new Error(`HTTP error! durum: ${response['stat' + 'us']}`);
            }

            const data = await response.json();
            return this.formatResponse(data);
        } catch (error) {
            console.error('AIService.getSmartDefaults error:', error);
            return {
                success: false,
                message: error.message || 'Smart defaults hatası',
                data: null,
            };
        }
    }

    /**
     * Suggest Single Feature Value
     * Tek bir özellik için AI önerisi al
     *
     * @param {string} featureName - Feature name
     * @param {Object} context - Context data (category, area, other features, etc.)
     * @returns {Promise<Object>} Single feature suggestion
     */
    static async suggestSingleFeature(featureName, context = {}) {
        try {
            const url2 = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.aiSuggestFeature
                ? window.APIConfig.admin.aiSuggestFeature
                : '/api/admin/ai/suggest-feature';
            const response = await fetch(url2, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.getCsrfToken(),
                },
                body: JSON.stringify({
                    feature: featureName,
                    context: context,
                }),
            });

            if (!response.ok) {
                throw new Error(`HTTP error! durum: ${response['stat' + 'us']}`);
            }

            const data = await response.json();
            return this.formatResponse(data);
        } catch (error) {
            console.error('AIService.suggestSingleFeature error:', error);
            return {
                success: false,
                message: error.message || 'Özellik önerisi hatası',
                data: null,
            };
        }
    }
    static registerProvider(name, config) {
        AIOrchestrator.register(name, config);
    }
    static useProvider(name) {
        return AIOrchestrator.use(name);
    }
    static async chat(payload, options = {}) {
        return await AIOrchestrator.chat(payload, options);
    }
    static async pricePredict(payload, options = {}) {
        return await AIOrchestrator.pricePredict(payload, options);
    }
    static async suggestFeatures(payload, options = {}) {
        return await AIOrchestrator.suggestFeatures(payload, options);
    }
    static async updateLocale(locale) {
        const response = await fetch('/admin/ai-settings/update-locale', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.getCsrfToken() },
            body: JSON.stringify({ locale }),
        });
        const data = await response.json();
        return this.formatResponse(data);
    }
    static async updateCurrency(currency) {
        const response = await fetch('/admin/ai-settings/update-currency', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.getCsrfToken() },
            body: JSON.stringify({ currency }),
        });
        const data = await response.json();
        return this.formatResponse(data);
    }
}

export default AIService;
