/**
 * Smart Suggestions System - Context7 Standard
 *
 * 🎯 Amaç:
 * - AI-powered başlık, açıklama ve fiyat önerileri
 * - Kategori ve konum bazlı öneriler
 * - Real-time suggestion updates
 *
 * @version 1.0.0
 * @author Context7 Team
 */

class SmartSuggestionsSystem {
    constructor() {
        this.aiService = null;
        this.cache = new Map();
        this.isLoading = false;
        this.currentCategory = null;
        this.currentLocation = null;

        this.init();
    }

    init() {
        console.log('🤖 Smart Suggestions System initialized');

        // AI butonlarını dinle
        this.setupAIButtons();

        // Form değişikliklerini dinle
        this.setupFormListeners();

        // AI service'i initialize et
        this.initializeAIService();
    }

    setupAIButtons() {
        // Başlık AI butonu
        const titleButton = document.getElementById('ai-title-suggestion');
        if (titleButton) {
            titleButton.addEventListener('click', () => {
                this.generateTitleSuggestion();
            });
        }

        // Açıklama AI butonu
        const descriptionButton = document.getElementById('ai-description-suggestion');
        if (descriptionButton) {
            descriptionButton.addEventListener('click', () => {
                this.generateDescriptionSuggestion();
            });
        }

        // Fiyat AI butonu
        const priceButton = document.getElementById('ai-price-suggestion');
        if (priceButton) {
            priceButton.addEventListener('click', () => {
                this.generatePriceSuggestion();
            });
        }
    }

    setupFormListeners() {
        // Kategori değişikliğini dinle
        const categorySelect = document.querySelector('select[name="kategori_id"]');
        if (categorySelect) {
            categorySelect.addEventListener('change', (e) => {
                this.currentCategory = e.target.value;
                this.cache.clear(); // Category değişince cache'i temizle
            });
        }

        // Lokasyon değişikliklerini dinle
        const ilSelect = document.querySelector('select[name="il_id"]');
        const ilceSelect = document.querySelector('select[name="ilce_id"]');

        if (ilSelect) {
            ilSelect.addEventListener('change', (e) => {
                this.updateLocation();
            });
        }

        if (ilceSelect) {
            ilceSelect.addEventListener('change', (e) => {
                this.updateLocation();
            });
        }
    }

    initializeAIService() {
        // AI Service proxy oluştur
        this.aiService = {
            analyze: async (data, context) => {
                const urlAnalyze = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.aiAnalyzeProperty
                    ? window.APIConfig.admin.aiAnalyzeProperty
                    : '/api/admin/ai/analyze-property';
                return await this.makeAIRequest(urlAnalyze, {
                    data,
                    context,
                });
            },

            suggest: async (context, type) => {
                const urlSuggest = window.APIConfig && window.APIConfig.ai && window.APIConfig.ai.suggest
                    ? window.APIConfig.ai.suggest
                    : '/api/v1/ai/suggest';
                return await this.makeAIRequest(urlSuggest, {
                    context,
                    type,
                });
            },

            generate: async (prompt, options) => {
                const urlGenerate = window.APIConfig && window.APIConfig.ai && window.APIConfig.ai.generate
                    ? window.APIConfig.ai.generate
                    : '/api/v1/ai/generate';
                return await this.makeAIRequest(urlGenerate, {
                    prompt,
                    options,
                });
            },
        };
    }

    async makeAIRequest(endpoint, payload) {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content'),
                },
                body: JSON.stringify(payload),
            });

            if (!response.ok) {
                throw new Error(`AI API error: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('AI Request error:', error);
            return { success: false, error: error.message };
        }
    }

    async generateTitleSuggestion() {
        if (this.isLoading) return;

        const formData = this.collectFormData();

        if (!formData.kategori_id) {
            this.showError('Lütfen önce kategori seçin');
            return;
        }

        this.setLoadingState('title', true);

        try {
            const cacheKey = `title_${formData.kategori_id}_${formData.il_id}_${formData.metrekare}`;

            if (this.cache.has(cacheKey)) {
                this.applyTitleSuggestion(this.cache.get(cacheKey));
                return;
            }

            const context = {
                category: formData.kategori_id,
                type: formData.ilan_tipi,
                location: {
                    il_id: formData.il_id,
                    ilce_id: formData.ilce_id,
                },
                properties: {
                    metrekare: formData.metrekare,
                    oda_sayisi: formData.oda_sayisi,
                    fiyat: formData.fiyat,
                },
            };

            const result = await this.aiService.suggest(context, 'title');

            if (result.success && result.suggestions) {
                this.cache.set(cacheKey, result.suggestions);
                this.applyTitleSuggestion(result.suggestions);
            } else {
                throw new Error(result.error || 'AI title generation failed');
            }
        } catch (error) {
            console.error('Title suggestion error:', error);
            this.showError('Başlık önerisi alınamadı: ' + error.message);
        } finally {
            this.setLoadingState('title', false);
        }
    }

    async generateDescriptionSuggestion() {
        if (this.isLoading) return;

        const formData = this.collectFormData();

        if (!formData.baslik || !formData.kategori_id) {
            this.showError('Lütfen önce başlık ve kategori girin');
            return;
        }

        this.setLoadingState('description', true);

        try {
            const cacheKey = `desc_${formData.kategori_id}_${formData.baslik}_${formData.metrekare}`;

            if (this.cache.has(cacheKey)) {
                this.applyDescriptionSuggestion(this.cache.get(cacheKey));
                return;
            }

            const context = {
                title: formData.baslik,
                category: formData.kategori_id,
                type: formData.ilan_tipi,
                location: {
                    il_id: formData.il_id,
                    ilce_id: formData.ilce_id,
                    mahalle_id: formData.mahalle_id,
                    adres: formData.adres,
                },
                properties: {
                    metrekare: formData.metrekare,
                    oda_sayisi: formData.oda_sayisi,
                    kat_numarasi: formData.kat_numarasi,
                    fiyat: formData.fiyat,
                },
            };

            const result = await this.aiService.generate(
                `${formData.baslik} için detaylı emlak açıklaması oluştur`,
                { context, length: 'medium', style: 'professional' }
            );

            if (result.success && result.content) {
                this.cache.set(cacheKey, result.content);
                this.applyDescriptionSuggestion(result.content);
            } else {
                throw new Error(result.error || 'AI description generation failed');
            }
        } catch (error) {
            console.error('Description suggestion error:', error);
            this.showError('Açıklama önerisi alınamadı: ' + error.message);
        } finally {
            this.setLoadingState('description', false);
        }
    }

    async generatePriceSuggestion() {
        if (this.isLoading) return;

        const formData = this.collectFormData();

        if (!formData.kategori_id || !formData.metrekare) {
            this.showError('Lütfen kategori ve metrekare bilgisi girin');
            return;
        }

        this.setLoadingState('price', true);

        try {
            const cacheKey = `price_${formData.kategori_id}_${formData.il_id}_${formData.metrekare}`;

            if (this.cache.has(cacheKey)) {
                this.applyPriceSuggestion(this.cache.get(cacheKey));
                return;
            }

            const context = {
                category: formData.kategori_id,
                type: formData.ilan_tipi,
                location: {
                    il_id: formData.il_id,
                    ilce_id: formData.ilce_id,
                },
                properties: {
                    metrekare: formData.metrekare,
                    oda_sayisi: formData.oda_sayisi,
                    kat_numarasi: formData.kat_numarasi,
                },
            };

            const result = await this.aiService.analyze(context, {
                type: 'price_analysis',
            });

            if (result.success && result.analysis) {
                this.cache.set(cacheKey, result.analysis);
                this.applyPriceSuggestion(result.analysis);
            } else {
                throw new Error(result.error || 'AI price analysis failed');
            }
        } catch (error) {
            console.error('Price suggestion error:', error);
            this.showError('Fiyat önerisi alınamadı: ' + error.message);
        } finally {
            this.setLoadingState('price', false);
        }
    }

    applyTitleSuggestion(suggestions) {
        if (!Array.isArray(suggestions) || suggestions.length === 0) return;

        const titleInput = document.querySelector('input[name="baslik"]');
        if (!titleInput) return;

        // En yüksek confidence'a sahip öneriyi al
        const bestSuggestion = suggestions.reduce((best, current) =>
            (current.confidence || 0) > (best.confidence || 0) ? current : best
        );

        titleInput.value = bestSuggestion.content || bestSuggestion.title;
        this.showSuccess('✨ Başlık önerisi uygulandı');

        // Trigger change event
        titleInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    applyDescriptionSuggestion(content) {
        const descriptionTextarea = document.querySelector('textarea[name="aciklama"]');
        if (!descriptionTextarea) return;

        descriptionTextarea.value = content;
        this.showSuccess('✨ Açıklama önerisi uygulandı');

        // Trigger change event
        descriptionTextarea.dispatchEvent(new Event('change', { bubbles: true }));
    }

    applyPriceSuggestion(analysis) {
        const priceInput = document.querySelector('input[name="fiyat"]');
        if (!priceInput) return;

        if (analysis.suggested_price) {
            priceInput.value = analysis.suggested_price;
            this.showSuccess(`✨ Fiyat önerisi: ${this.formatPrice(analysis.suggested_price)} TL`);

            // Trigger change event
            priceInput.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Fiyat analizi göster
        if (analysis.analysis) {
            this.showPriceAnalysis(analysis.analysis);
        }
    }

    collectFormData() {
        const form = document.querySelector('#stable-create-form');
        const formData = {};

        if (form) {
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach((input) => {
                if (input.name && input.value) {
                    formData[input.name] = input.value;
                }
            });
        }

        return formData;
    }

    updateLocation() {
        const ilSelect = document.querySelector('select[name="il_id"]');
        const ilceSelect = document.querySelector('select[name="ilce_id"]');

        this.currentLocation = {
            il_id: ilSelect?.value,
            ilce_id: ilceSelect?.value,
        };

        // Lokasyon değişince price cache'ini temizle
        for (const [key] of this.cache) {
            if (key.startsWith('price_')) {
                this.cache.delete(key);
            }
        }
    }

    setLoadingState(type, loading) {
        const button = document.getElementById(`ai-${type}-suggestion`);
        if (!button) return;

        if (loading) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Oluşturuluyor...';
            this.isLoading = true;
        } else {
            button.disabled = false;
            button.innerHTML = this.getButtonHTML(type);
            this.isLoading = false;
        }
    }

    getButtonHTML(type) {
        const buttonConfigs = {
            title: '<i class="fas fa-magic mr-2"></i>AI Başlık',
            description: '<i class="fas fa-brain mr-2"></i>AI Açıklama',
            price: '<i class="fas fa-chart-line mr-2"></i>AI Fiyat',
        };

        return buttonConfigs[type] || '<i class="fas fa-robot mr-2"></i>AI Öneri';
    }

    showSuccess(message) {
        // Success notification
        this.showNotification(message, 'success');
    }

    showError(message) {
        // Error notification
        this.showNotification(message, 'error');
    }

    showNotification(message, type = 'info') {
        // Simple notification system
        const notification = document.createElement('div');
        notification.className = `ai-notification ai-notification-${type}`;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 9999;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        `;

        const colors = {
            success: '#10B981',
            error: '#EF4444',
            info: '#3B82F6',
        };

        notification.style.backgroundColor = colors[type] || colors.info;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 4000);
    }

    showPriceAnalysis(analysis) {
        // Show price analysis in a modal or sidebar
        console.log('Price Analysis:', analysis);
    }

    formatPrice(price) {
        return new Intl.NumberFormat('tr-TR').format(price);
    }

    // Public methods
    refresh() {
        this.cache.clear();
        console.log('🔄 Smart Suggestions cache cleared');
    }

    getCache() {
        return Array.from(this.cache.entries());
    }
}

// Global instance
window.smartSuggestionsSystem = null;

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('stable-create-form')) {
        window.smartSuggestionsSystem = new SmartSuggestionsSystem();
    }
});

// Export for external use
window.SmartSuggestionsSystem = SmartSuggestionsSystem;
