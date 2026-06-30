/**
 * Context7 AI Service
 *
 * AI-powered real estate listing creation and optimization
 * Version: 2.0.0 - Context7 Smart İlan Standard
 */

class Context7AIService {
    constructor() {
        this.config = {
            apiBase: (window.APIConfig && window.APIConfig.ai) ? window.APIConfig.ai.analyze.replace('/analyze','') : '/api/v1/ai',
            providers: ['openai', 'deepseek', 'gemini', 'claude'],
            defaultProvider: 'deepseek',
            maxRetries: 3,
            timeout: 30000,
        };

        this.cache = new Map();
        this.init();
    }

    /**
     * Initialize AI service
     */
    init() {
        console.log('🤖 Context7 AI Service initializing...');
        this.setupErrorHandling();
        console.log('✅ SAB AI Service initialized');
    }

    /**
     * Setup error handling
     */
    setupErrorHandling() {
        window.addEventListener('unhandledrejection', (event) => {
            if (event.reason && event.reason.service === 'Context7AI') {
                console.error('Context7 AI Service Error:', event.reason);
                this.handleAIError(event.reason);
            }
        });
    }

    /**
     * Analyze basic listing information
     */
    async analyzeBasicInfo(formData) {
        const cacheKey = `basic_analysis_${this.hashFormData(formData)}`;

        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const response = await this.makeAIRequest('/analyze/basic', {
                formData,
                analysis_type: 'basic_info',
            });

            const result = {
                success: true,
                suggestions: this.generateBasicSuggestions(formData, response),
                confidence: response.confidence || 85,
            };

            this.cache.set(cacheKey, result);
            return result;
        } catch (error) {
            console.error('AI Basic Analysis Error:', error);
            return this.getFallbackBasicSuggestions(formData);
        }
    }

    /**
     * Get AI feature suggestions
     */
    async getFeatureSuggestions(categoryId, location) {
        const cacheKey = `features_${categoryId}_${location?.latitude || 'no_loc'}`;

        if (this.cache.has(cacheKey)) {
            return this.cache.get(cacheKey);
        }

        try {
            const response = await this.makeAIRequest('/suggest/features', {
                category_id: categoryId,
                location: location,
                suggestion_type: 'features',
            });

            const result = {
                success: true,
                suggestions: this.formatFeatureSuggestions(response.features || []),
                confidence: response.confidence || 80,
            };

            this.cache.set(cacheKey, result);
            return result;
        } catch (error) {
            console.error('AI Feature Suggestions Error:', error);
            return this.getFallbackFeatureSuggestions(categoryId);
        }
    }

    /**
     * Optimize listing price
     */
    async getPriceOptimization(formData) {
        try {
            const response = await this.makeAIRequest('/optimize/price', {
                formData,
                market_analysis: true,
                optimization_type: 'price',
            });

            return {
                success: true,
                suggestions: this.formatPriceSuggestions(response),
                analysis: response.analysis || {},
                confidence: response.confidence || 75,
            };
        } catch (error) {
            console.error('AI Price Optimization Error:', error);
            return this.getFallbackPriceSuggestions(formData);
        }
    }

    /**
     * Generate listing description
     */
    async generateDescription(formData) {
        try {
            const response = await this.makeAIRequest('/generate/description', {
                formData,
                language: 'tr',
                style: 'professional',
                generation_type: 'description',
            });

            return {
                success: true,
                description: response.description || this.generateFallbackDescription(formData),
                alternatives: response.alternatives || [],
                confidence: response.confidence || 70,
            };
        } catch (error) {
            console.error('AI Description Generation Error:', error);
            return {
                success: true,
                description: this.generateFallbackDescription(formData),
                alternatives: [],
                confidence: 60,
            };
        }
    }

    /**
     * Analyze uploaded images
     */
    async analyzeImages(images) {
        try {
            const formData = new FormData();

            if (images instanceof FormData) {
                // Images already in FormData
                for (const [key, value] of images.entries()) {
                    formData.append(key, value);
                }
            } else if (Array.isArray(images)) {
                // Array of image files
                images.forEach((image, index) => {
                    formData.append(`images[${index}]`, image);
                });
            }

            const response = await this.makeAIRequest('/analyze/images', formData, {
                method: 'POST',
                headers: {
                    // Don't set Content-Type, let browser set it for FormData
                },
            });

            return {
                success: true,
                analysis: this.formatImageAnalysis(response.analysis || []),
                suggestions: response.suggestions || [],
                confidence: response.confidence || 65,
            };
        } catch (error) {
            console.error('AI Image Analysis Error:', error);
            return this.getFallbackImageAnalysis();
        }
    }

    /**
     * Make AI API request
     */
    async makeAIRequest(endpoint, data, options = {}) {
        const url = `${this.config.apiBase}${endpoint}`;

        const defaultOptions = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                Accept: 'application/json',
            },
            body: data instanceof FormData ? data : JSON.stringify(data),
            timeout: this.config.timeout,
        };

        const requestOptions = { ...defaultOptions, ...options };

        // Remove Content-Type for FormData
        if (data instanceof FormData && requestOptions.headers['Content-Type']) {
            delete requestOptions.headers['Content-Type'];
        }

        for (let attempt = 1; attempt <= this.config.maxRetries; attempt++) {
            try {
                const response = await fetch(url, requestOptions);

                if (!response.ok) {
                    throw new Error(`AI API Error: ${response.status} ${response.statusText}`);
                }

                const result = await response.json();
                return result;
            } catch (error) {
                console.warn(`AI Request attempt ${attempt} failed:`, error);

                if (attempt === this.config.maxRetries) {
                    throw error;
                }

                // Wait before retry
                await new Promise((resolve) => setTimeout(resolve, 1000 * attempt));
            }
        }
    }

    /**
     * Generate basic suggestions fallback
     */
    getFallbackBasicSuggestions(formData) {
        const suggestions = [];

        // Title suggestions
        if (!formData.baslik || formData.baslik.length < 10) {
            suggestions.push({
                id: 'title_too_short',
                title: 'Başlık Optimizasyonu',
                content: 'Başlığınız çok kısa. En az 10 karakter olmalı ve özellikleri içermeli.',
                confidence: 90,
                action: 'optimize_title',
            });
        }

        // Price suggestions
        if (formData.fiyat && !formData.para_birimi) {
            suggestions.push({
                id: 'currency_missing',
                title: 'Para Birimi',
                content: 'Para birimi seçmeyi unutmayın.',
                confidence: 95,
                action: 'set_currency',
            });
        }

        // Description suggestions
        if (!formData.aciklama || formData.aciklama.length < 50) {
            suggestions.push({
                id: 'description_too_short',
                title: 'Açıklama Geliştirme',
                content: 'Detaylı açıklama eklemeniz ilanınızın görünürlüğünü artırır.',
                confidence: 85,
                action: 'expand_description',
            });
        }

        return {
            success: true,
            suggestions,
            confidence: 70,
        };
    }

    /**
     * Generate feature suggestions fallback
     */
    getFallbackFeatureSuggestions(categoryId) {
        const commonFeatures = {
            1: [
                // Konut
                { id: 'balkon', name: 'Balkon', confidence: 80 },
                { id: 'asansor', name: 'Asansör', confidence: 75 },
                { id: 'otopark', name: 'Otopark', confidence: 85 },
            ],
            2: [
                // İşyeri
                { id: 'klima', name: 'Klima', confidence: 90 },
                { id: 'guvenlik', name: 'Güvenlik', confidence: 85 },
                { id: 'otopark', name: 'Otopark', confidence: 80 },
            ],
            3: [
                // Arsa
                { id: 'elektrik', name: 'Elektrik Altyapısı', confidence: 95 },
                { id: 'su', name: 'Su Altyapısı', confidence: 90 },
                { id: 'yol', name: 'Yola Cephe', confidence: 85 },
            ],
        };

        const features = commonFeatures[categoryId] || [];

        return {
            success: true,
            suggestions: features.map((feature) => ({
                id: `feature_${feature.id}`,
                title: `${feature.name} Önerisi`,
                content: `Bu kategori için ${feature.name} özelliği önerilir.`,
                confidence: feature.confidence,
                action: 'add_feature',
                featureId: feature.id,
            })),
            confidence: 70,
        };
    }

    /**
     * Generate price suggestions fallback
     */
    getFallbackPriceSuggestions(formData) {
        const suggestions = [];
        const price = parseFloat(formData.fiyat);

        if (price) {
            // Price format suggestion
            if (price > 1000000) {
                suggestions.push({
                    id: 'price_format',
                    title: 'Fiyat Formatı',
                    content: 'Büyük tutarlar için "1.2M ₺" gibi kısa format kullanılabilir.',
                    confidence: 60,
                });
            }

            // Price range suggestion
            suggestions.push({
                id: 'price_market',
                title: 'Piyasa Analizi',
                content: 'Fiyatınız piyasa ortalamasına uygun görünüyor.',
                confidence: 50,
            });
        }

        return {
            success: true,
            suggestions,
            confidence: 50,
        };
    }

    /**
     * Generate fallback description
     */
    generateFallbackDescription(formData) {
        let description = '';

        if (formData.baslik) {
            description += `${formData.baslik}\n\n`;
        }

        description += 'Bu güzel emlak sizleri bekliyor. ';

        if (formData.fiyat && formData.para_birimi) {
            description += `Fiyat: ${formData.fiyat} ${formData.para_birimi}. `;
        }

        description += 'Detaylı bilgi için iletişime geçiniz.';

        return description;
    }

    /**
     * Format image analysis fallback
     */
    getFallbackImageAnalysis() {
        return {
            success: true,
            analysis: [
                {
                    id: 'fallback_analysis',
                    title: 'Fotoğraf Analizi',
                    content:
                        'Fotoğraflarınız yüklendi. Daha iyi sonuçlar için AI analizi kullanılabilir.',
                    confidence: 40,
                },
            ],
            suggestions: [],
            confidence: 40,
        };
    }

    /**
     * Format basic suggestions
     */
    generateBasicSuggestions(formData, response) {
        // Process AI response and format suggestions
        const suggestions = response.suggestions || [];

        return suggestions.map((suggestion) => ({
            id: suggestion.id || Math.random().toString(36).substr(2, 9),
            title: suggestion.title || 'AI Önerisi',
            content: suggestion.content || suggestion.message,
            confidence: suggestion.confidence || 70,
            action: suggestion.action,
        }));
    }

    /**
     * Format feature suggestions
     */
    formatFeatureSuggestions(features) {
        return features.map((feature) => ({
            id: `feature_${feature.id}`,
            title: `${feature.name} Önerisi`,
            content: feature.description || `${feature.name} özelliği önerilir.`,
            confidence: feature.confidence || 70,
            action: 'add_feature',
            featureId: feature.id,
        }));
    }

    /**
     * Format price suggestions
     */
    formatPriceSuggestions(response) {
        return (response.suggestions || []).map((suggestion) => ({
            id: suggestion.id || Math.random().toString(36).substr(2, 9),
            title: suggestion.title || 'Fiyat Önerisi',
            content: suggestion.content,
            confidence: suggestion.confidence || 70,
            action: suggestion.action,
            value: suggestion.value,
        }));
    }

    /**
     * Format image analysis
     */
    formatImageAnalysis(analysis) {
        return analysis.map((item) => ({
            id: item.id || Math.random().toString(36).substr(2, 9),
            title: item.title || 'Görsel Analizi',
            content: item.content || item.description,
            confidence: item.confidence || 65,
            imageIndex: item.imageIndex,
            suggestions: item.suggestions || [],
        }));
    }

    /**
     * Hash form data for caching
     */
    hashFormData(formData) {
        const str = JSON.stringify(formData);
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = (hash << 5) - hash + char;
            hash = hash & hash; // 32bit integer
        }
        return hash.toString(36);
    }

    /**
     * Handle AI errors
     */
    handleAIError(error) {
        console.error('Context7 AI Error:', error);

        // Show user-friendly error message
        if (window.showNotification) {
            window.showNotification(
                'AI servisi geçici olarak kullanılamıyor. Varsayılan öneriler gösteriliyor.',
                'warning'
            );
        }
    }

    /**
     * Clear cache
     */
    clearCache() {
        this.cache.clear();
        console.log('🧹 Context7 AI Service cache cleared');
    }
}
