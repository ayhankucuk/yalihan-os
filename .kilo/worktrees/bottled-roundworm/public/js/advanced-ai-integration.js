/**
 * 🚀 Advanced AI Integration for Property Creation
 *
 * Enterprise seviye AI özellikleri:
 * - GPT-4 entegrasyonu
 * - Market analysis
 * - Price suggestions
 * - SEO optimization
 * - Multi-language support
 * - A/B testing
 */

class AdvancedAIIntegration {
    constructor() {
        this.apiBaseUrl = '/api/advanced-ai';
        this.isGenerating = false;
        this.currentRequest = null;
        this.cache = new Map();
        this.observers = new Map();

        this.init();
    }

    init() {
        this.bindEvents();
        this.setupPerformanceMonitoring();
        this.initializeCache();
    }

    /**
     * Event binding
     */
    bindEvents() {
        // AI öneri butonu
        const aiSuggestBtn = document.getElementById('ai-suggest-btn');
        if (aiSuggestBtn) {
            aiSuggestBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.generateAdvancedContent();
            });
        }

        // Fiyat analizi butonu
        const priceAnalysisBtn = document.getElementById('price-analysis-btn');
        if (priceAnalysisBtn) {
            priceAnalysisBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.generatePriceAnalysis();
            });
        }

        // Pazar analizi butonu
        const marketAnalysisBtn = document.getElementById('market-analysis-btn');
        if (marketAnalysisBtn) {
            marketAnalysisBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.generateMarketAnalysis();
            });
        }

        // SEO anahtar kelimeler butonu
        const seoKeywordsBtn = document.getElementById('seo-keywords-btn');
        if (seoKeywordsBtn) {
            seoKeywordsBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.generateSEOKeywords();
            });
        }

        // Form değişikliklerini izle
        this.observeFormChanges();
    }

    /**
     * Gelişmiş AI içerik üretimi
     */
    async generateAdvancedContent() {
        if (this.isGenerating) {
            this.showNotification('AI içerik üretimi devam ediyor...', 'info');
            return;
        }

        try {
            this.isGenerating = true;
            this.updateUI('generating');

            const formData = this.collectFormData();
            const aiOptions = this.getAIOptions();

            // Cache kontrolü
            const cacheKey = this.getCacheKey(formData, aiOptions);
            if (this.cache.has(cacheKey)) {
                this.showCachedResults(this.cache.get(cacheKey));
                return;
            }

            // API çağrısı
            const response = await this.makeAPICall('/generate-content', {
                method: 'POST',
                body: JSON.stringify({
                    ...formData,
                    ...aiOptions,
                }),
            });

            if (!response.success) {
                throw new Error(response.message || 'AI içerik üretimi başarısız');
            }

            // Sonuçları göster
            this.showAdvancedResults(response.data);

            // Cache'e kaydet
            this.cache.set(cacheKey, response.data);

            // Analytics
            this.trackEvent('ai_content_generated', {
                tone: aiOptions.tone,
                variant_count: aiOptions.variant_count,
                success: true,
            });
        } catch (error) {
            console.error('AI content generation error:', error);
            this.showNotification(
                'AI içerik üretimi sırasında hata oluştu: ' + error.message,
                'error'
            );

            // Fallback içerik göster
            this.showFallbackContent();

            // Analytics
            this.trackEvent('ai_content_generated', {
                success: false,
                error: error.message,
            });
        } finally {
            this.isGenerating = false;
            this.updateUI('idle');
        }
    }

    /**
     * Fiyat analizi
     */
    async generatePriceAnalysis() {
        try {
            this.updateUI('analyzing_price');

            const formData = this.collectFormData();
            const response = await this.makeAPICall('/price-analysis', {
                method: 'POST',
                body: JSON.stringify(formData),
            });

            if (!response.success) {
                throw new Error(response.message || 'Fiyat analizi başarısız');
            }

            this.showPriceAnalysis(response.data);
        } catch (error) {
            console.error('Price analysis error:', error);
            this.showNotification('Fiyat analizi sırasında hata oluştu: ' + error.message, 'error');
        } finally {
            this.updateUI('idle');
        }
    }

    /**
     * Pazar analizi
     */
    async generateMarketAnalysis() {
        try {
            this.updateUI('analyzing_market');

            const formData = this.collectFormData();
            const response = await this.makeAPICall('/market-analysis', {
                method: 'POST',
                body: JSON.stringify(formData),
            });

            if (!response.success) {
                throw new Error(response.message || 'Pazar analizi başarısız');
            }

            this.showMarketAnalysis(response.data);
        } catch (error) {
            console.error('Market analysis error:', error);
            this.showNotification('Pazar analizi sırasında hata oluştu: ' + error.message, 'error');
        } finally {
            this.updateUI('idle');
        }
    }

    /**
     * SEO anahtar kelimeler
     */
    async generateSEOKeywords() {
        try {
            this.updateUI('generating_seo');

            const formData = this.collectFormData();
            const response = await this.makeAPICall('/seo-keywords', {
                method: 'POST',
                body: JSON.stringify(formData),
            });

            if (!response.success) {
                throw new Error(response.message || 'SEO anahtar kelimeler oluşturulamadı');
            }

            this.showSEOKeywords(response.data);
        } catch (error) {
            console.error('SEO keywords error:', error);
            this.showNotification(
                'SEO anahtar kelimeler oluşturulurken hata oluştu: ' + error.message,
                'error'
            );
        } finally {
            this.updateUI('idle');
        }
    }

    /**
     * Form verilerini topla
     */
    collectFormData() {
        try {
            const form = document.querySelector('form');
            if (!form) {
                console.warn('Form element not found');
                return this.getDefaultFormData();
            }

            const formData = new FormData(form);
            const data = {};

            for (let [key, value] of formData.entries()) {
                if (data[key]) {
                    if (Array.isArray(data[key])) {
                        data[key].push(value);
                    } else {
                        data[key] = [data[key], value];
                    }
                } else {
                    data[key] = value;
                }
            }

            // Lokasyon bilgilerini hazırla - Context7 Live Search uyumlu
            const ilSelect = document.getElementById('il_id');
            const ilceSelect = document.getElementById('ilce_id');
            const mahalleSelect = document.getElementById('mahalle_id');

            // Select dropdown için güvenli kontrol
            if (ilSelect && ilSelect.value) {
                // Select option'dan il adını al
                const selectedOption = ilSelect.querySelector(`option[value="${ilSelect.value}"]`);
                if (selectedOption) {
                    data.il = selectedOption.textContent.trim();
                }
            }

            // Eğer il hala boşsa, default değer ata
            if (!data.il || data.il.trim() === '') {
                data.il = 'İstanbul';
            }

            if (ilceSelect && ilceSelect.value) {
                // Select option'dan ilçe adını al
                const selectedOption = ilceSelect.querySelector(
                    `option[value="${ilceSelect.value}"]`
                );
                if (selectedOption) {
                    data.ilce = selectedOption.textContent.trim();
                }
            } else {
                data.ilce = '';
            }

            if (mahalleSelect && mahalleSelect.value) {
                // Select option'dan mahalle adını al
                const selectedOption = mahalleSelect.querySelector(
                    `option[value="${mahalleSelect.value}"]`
                );
                if (selectedOption) {
                    data.mahalle = selectedOption.textContent.trim();
                }
            } else {
                data.mahalle = '';
            }

            // Kategori bilgilerini hazırla - Güvenli kontrol
            const anaKategoriSelect = document.querySelector('[name="ana_kategori_id"]');
            const altKategoriSelect = document.querySelector('[name="alt_kategori_id"]');

            if (
                anaKategoriSelect &&
                anaKategoriSelect.selectedOptions &&
                anaKategoriSelect.selectedOptions.length > 0
            ) {
                data.kategori = anaKategoriSelect.selectedOptions[0].textContent.trim();
            } else if (anaKategoriSelect && anaKategoriSelect.value) {
                // Fallback: value'dan kategori adını al
                const option = anaKategoriSelect.querySelector(
                    `option[value="${anaKategoriSelect.value}"]`
                );
                if (option) {
                    data.kategori = option.textContent.trim();
                }
            } else {
                // Default fallback
                data.kategori = data.ana_kategori_id || 'Emlak';
            }

            // Eğer kategori hala boşsa, default değer ata
            if (!data.kategori || data.kategori.trim() === '') {
                data.kategori = 'Emlak';
            }

            if (
                altKategoriSelect &&
                altKategoriSelect.selectedOptions &&
                altKategoriSelect.selectedOptions.length > 0
            ) {
                data.alt_kategori = altKategoriSelect.selectedOptions[0].textContent.trim();
            } else if (altKategoriSelect && altKategoriSelect.value) {
                const option = altKategoriSelect.querySelector(
                    `option[value="${altKategoriSelect.value}"]`
                );
                if (option) {
                    data.alt_kategori = option.textContent.trim();
                }
            } else {
                data.alt_kategori = data.alt_kategori_id || '';
            }

            return data;
        } catch (error) {
            console.error('Error collecting form data:', error);
            return this.getDefaultFormData();
        }
    }

    /**
     * Default form data fallback
     */
    getDefaultFormData() {
        return {
            baslik: '',
            kategori: 'Emlak',
            il: 'İstanbul',
            ilce: '',
            mahalle: '',
            fiyat: 500000,
            metrekare: 100,
            oda_sayisi: '2+1',
            banyo_sayisi: 1,
            balkon_var: false,
            asansor_var: false,
            kat_no: 1,
            toplam_kat: 5,
            ozellikler: [],
        };
    }

    /**
     * AI seçeneklerini al
     */
    getAIOptions() {
        try {
            return {
                ai_tone: document.getElementById('ai-tone')?.value || 'seo',
                ai_variant_count: parseInt(document.getElementById('ai-variant-count')?.value) || 3,
                ai_ab_test: document.getElementById('ai-ab-test')?.checked || false,
                ai_languages: Array.from(
                    document.querySelectorAll('[name="ai_languages[]"]:checked')
                ).map((el) => el.value),
                include_market_analysis: true,
                include_seo_keywords: true,
                include_price_analysis: true,
            };
        } catch (error) {
            console.error('Error getting AI options:', error);
            return {
                ai_tone: 'seo',
                ai_variant_count: 3,
                ai_ab_test: false,
                ai_languages: ['TR'],
                include_market_analysis: true,
                include_seo_keywords: true,
                include_price_analysis: true,
            };
        }
    }

    /**
     * API çağrısı yap
     */
    async makeAPICall(endpoint, options = {}) {
        const url = `${this.apiBaseUrl}${endpoint}`;

        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                    document.querySelector('input[name="_token"]')?.value,
            },
            credentials: 'same-origin', // CSRF token için
        };

        const requestOptions = { ...defaultOptions, ...options };

        // İptal edilebilir istek
        if (this.currentRequest) {
            this.currentRequest.abort();
        }

        this.currentRequest = new AbortController();
        requestOptions.signal = this.currentRequest.signal;

        const startTime = performance.now();
        const response = await fetch(url, requestOptions);
        const endTime = performance.now();

        // Performance tracking
        this.trackPerformance(endpoint, endTime - startTime);

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        return await response.json();
    }

    /**
     * Gelişmiş sonuçları göster
     */
    showAdvancedResults(data) {
        if (!data.success) {
            this.showNotification(data.message || 'AI içerik üretimi başarısız', 'error');
            return;
        }

        // Modal oluştur veya güncelle
        let modal = document.getElementById('advanced-ai-results-modal');
        if (!modal) {
            modal = this.createResultsModal();
        }

        // İçeriği güncelle
        this.updateResultsModal(modal, data);

        // Modal'ı göster
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Analytics
        this.trackEvent('ai_results_viewed', {
            variant_count: data.variants?.length || 0,
            has_market_analysis: !!data.market_analysis,
            has_price_analysis: !!data.price_analysis,
            has_seo_keywords: !!data.seo_keywords,
        });
    }

    /**
     * Sonuç modal'ı oluştur
     */
    createResultsModal() {
        const modal = document.createElement('div');
        modal.id = 'advanced-ai-results-modal';
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 hidden';

        modal.innerHTML = `
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden dark:bg-slate-900">
                    <div class="flex items-center justify-between p-6 border-b">
                        <h2 class="text-2xl font-bold text-gray-800">🤖 AI İçerik Önerileri</h2>
                        <button type="button" id="close-ai-results" class="text-gray-500 hover:text-gray-700 text-2xl">
                            ×
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]" id="ai-results-content">
                        <!-- İçerik buraya yüklenecek -->
                    </div>

                    <div class="flex justify-end space-x-4 p-6 border-t bg-gray-50 dark:bg-slate-900">
                        <button type="button" id="regenerate-ai-content" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors dark:text-slate-300">
                            🔄 Yeniden Üret
                        </button>
                        <button type="button" id="close-ai-results-btn" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Kapat
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Event listeners
        modal.querySelector('#close-ai-results').addEventListener('click', () => {
            this.closeResultsModal();
        });

        modal.querySelector('#close-ai-results-btn').addEventListener('click', () => {
            this.closeResultsModal();
        });

        modal.querySelector('#regenerate-ai-content').addEventListener('click', () => {
            this.regenerateContent();
        });

        // ESC tuşu ile kapat
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                this.closeResultsModal();
            }
        });

        return modal;
    }

    /**
     * Sonuç modal'ını güncelle
     */
    updateResultsModal(modal, data) {
        const content = modal.querySelector('#ai-results-content');

        let html = '';

        // Başlık ve açıklama varyantları
        if (data.variants && data.variants.length > 0) {
            html += `
                <div class="mb-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">📝 İçerik Varyantları</h3>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            `;

            data.variants.forEach((variant, index) => {
                html += `
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 dark:bg-slate-900 dark:border-slate-700">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-blue-600">Varyant ${variant.id}</span>
                            <span class="text-sm text-gray-500">SEO: ${variant.seo_score}/100</span>
                        </div>

                        <h4 class="font-semibold text-gray-800 mb-2">${variant.title}</h4>
                        <p class="text-gray-600 text-sm mb-3">${variant.description}</p>

                        <div class="flex space-x-2">
                            <button type="button" class="px-3 py-1 bg-green-600 text-white text-sm rounded hover:bg-green-700 transition-colors"
                                    onclick="window.advancedAI.useVariant(${index})">
                                ✅ Kullan
                            </button>
                            <button type="button" class="px-3 py-1 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 transition-colors"
                                    onclick="window.advancedAI.editVariant(${index})">
                                ✏️ Düzenle
                            </button>
                        </div>
                    </div>
                `;
            });

            html += '</div></div>';
        }

        // Pazar analizi
        if (data.market_analysis) {
            html += `
                <div class="mb-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">📊 Pazar Analizi</h3>
                    <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Lokasyon Skoru</h4>
                                <div class="text-2xl font-bold text-blue-600">
                                    ${data.market_analysis.location_analysis?.score || 'N/A'}/100
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Pazar Trendi</h4>
                                <div class="text-lg font-semibold text-gray-700 dark:text-slate-300">
                                    ${data.market_analysis.market_trends?.trend || 'Stabil'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // Fiyat analizi
        if (data.price_analysis) {
            html += `
                <div class="mb-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">💰 Fiyat Analizi</h3>
                    <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Mevcut Fiyat</h4>
                                <div class="text-xl font-bold text-gray-700 dark:text-slate-300">
                                    ${new Intl.NumberFormat('tr-TR').format(
                                        data.price_analysis.current_price || 0
                                    )} TL
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Önerilen Fiyat</h4>
                                <div class="text-xl font-bold text-green-600">
                                    ${new Intl.NumberFormat('tr-TR').format(
                                        data.price_analysis.suggested_price || 0
                                    )} TL
                                </div>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-800 mb-2">Güven Skoru</h4>
                                <div class="text-xl font-bold text-blue-600">
                                    ${data.price_analysis.confidence || 0}%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        // SEO anahtar kelimeler
        if (data.seo_keywords) {
            html += `
                <div class="mb-8">
                    <h3 class="text-xl font-semibold text-gray-800 mb-4">🔍 SEO Anahtar Kelimeler</h3>
                    <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                        <div class="mb-4">
                            <span class="text-sm font-medium text-gray-600">SEO Skoru: </span>
                            <span class="text-lg font-bold text-purple-600">${
                                data.seo_keywords.seo_score || 0
                            }/100</span>
                        </div>

                        <div class="space-y-3">
                            ${Object.entries(data.seo_keywords)
                                .filter(([key]) => key !== 'seo_score')
                                .map(
                                    ([category, keywords]) => `
                                <div>
                                    <h4 class="font-medium text-gray-800 mb-1">${this.getCategoryLabel(
                                        category
                                    )}</h4>
                                    <div class="flex flex-wrap gap-2">
                                        ${keywords
                                            .map(
                                                (keyword) => `
                                            <span class="px-2 py-1 bg-purple-100 text-purple-800 text-sm rounded">${keyword}</span>
                                        `
                                            )
                                            .join('')}
                                    </div>
                                </div>
                            `
                                )
                                .join('')}
                        </div>
                    </div>
                </div>
            `;
        }

        content.innerHTML = html;
    }

    /**
     * Varyant kullan
     */
    useVariant(index) {
        const modal = document.getElementById('advanced-ai-results-modal');
        const variants = this.getCurrentVariants();

        if (variants && variants[index]) {
            const variant = variants[index];

            // Form alanlarını güncelle
            const baslikInput = document.querySelector('[name="baslik"]');
            const aciklamaInput = document.querySelector('[name="aciklama"]');

            if (baslikInput) baslikInput.value = variant.title;
            if (aciklamaInput) aciklamaInput.value = variant.description;

            // Bildirim göster
            this.showNotification('İçerik başarıyla uygulandı!', 'success');

            // Modal'ı kapat
            this.closeResultsModal();

            // Analytics
            this.trackEvent('ai_variant_used', {
                variant_id: variant.id,
                seo_score: variant.seo_score,
                tone: variant.tone,
            });
        }
    }

    /**
     * Varyant düzenle
     */
    editVariant(index) {
        const variants = this.getCurrentVariants();

        if (variants && variants[index]) {
            const variant = variants[index];

            // Düzenleme modal'ı göster
            this.showEditModal(variant, index);
        }
    }

    /**
     * Yardımcı metodlar
     */
    getCategoryLabel(category) {
        const labels = {
            location: 'Lokasyon',
            property_type: 'Emlak Tipi',
            features: 'Özellikler',
            long_tail: 'Uzun Kuyruk',
            trending: 'Trend',
        };
        return labels[category] || category;
    }

    getCurrentVariants() {
        // Modal'dan varyantları al
        const modal = document.getElementById('advanced-ai-results-modal');
        if (modal) {
            // Bu basit implementasyon - gerçekte state management gerekli
            return this.lastGeneratedVariants;
        }
        return null;
    }

    closeResultsModal() {
        const modal = document.getElementById('advanced-ai-results-modal');
        if (modal) {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }

    regenerateContent() {
        this.closeResultsModal();
        setTimeout(() => {
            this.generateAdvancedContent();
        }, 300);
    }

    updateUI(state) {
        const aiBtn = document.getElementById('ai-suggest-btn');
        if (!aiBtn) return;

        switch (state) {
            case 'generating':
                aiBtn.disabled = true;
                aiBtn.innerHTML = '🤖 AI Üretiyor...';
                break;
            case 'analyzing_price':
                aiBtn.innerHTML = '💰 Fiyat Analizi...';
                break;
            case 'analyzing_market':
                aiBtn.innerHTML = '📊 Pazar Analizi...';
                break;
            case 'generating_seo':
                aiBtn.innerHTML = '🔍 SEO Üretiyor...';
                break;
            case 'idle':
            default:
                aiBtn.disabled = false;
                aiBtn.innerHTML = '🤖 AI Öneri Oluştur';
                break;
        }
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 transition-all duration-300 ${
            type === 'success'
                ? 'bg-green-500 text-white'
                : type === 'error'
                  ? 'bg-red-500 text-white'
                  : type === 'warning'
                    ? 'bg-yellow-500 text-white'
                    : 'bg-blue-500 text-white'
        }`;

        notification.textContent = message;
        document.body.appendChild(notification);

        // Animasyon
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, 100);

        // Otomatik kaldır
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }

    showCachedResults(data) {
        this.showNotification('Önbellekten sonuçlar yüklendi', 'info');
        this.showAdvancedResults(data);
    }

    showFallbackContent() {
        this.showNotification('Basit içerik önerileri gösteriliyor', 'warning');
        // Fallback içerik göster
    }

    getCacheKey(formData, aiOptions) {
        return `ai_content_${btoa(JSON.stringify({ formData, aiOptions })).substring(0, 32)}`;
    }

    initializeCache() {
        // Cache temizleme (5 dakikada bir)
        setInterval(() => {
            if (this.cache.size > 50) {
                const keys = Array.from(this.cache.keys());
                keys.slice(0, 10).forEach((key) => this.cache.delete(key));
            }
        }, 300000);
    }

    observeFormChanges() {
        const form = document.querySelector('form');
        if (form) {
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                        // Form değişikliklerini izle
                        this.onFormChange();
                    }
                });
            });

            observer.observe(form, {
                attributes: true,
                attributeFilter: ['value', 'checked', 'selected'],
            });
        }
    }

    onFormChange() {
        // Form değişikliklerinde cache'i temizle
        this.cache.clear();
    }

    setupPerformanceMonitoring() {
        // Performance monitoring
        if ('performance' in window) {
            window.addEventListener('load', () => {
                setTimeout(() => {
                    const perfData = performance.getEntriesByType('navigation')[0];
                    if (perfData && perfData.loadEventEnd - perfData.loadEventStart > 3000) {
                        console.warn(
                            'Page load time exceeded 3 seconds:',
                            perfData.loadEventEnd - perfData.loadEventStart + 'ms'
                        );
                    }
                }, 0);
            });
        }
    }

    trackPerformance(endpoint, duration) {
        console.log(`API ${endpoint} took ${duration.toFixed(2)}ms`);

        // Analytics'e gönder
        if (window.gtag) {
            window.gtag('event', 'api_performance', {
                endpoint: endpoint,
                duration: Math.round(duration),
            });
        }
    }

    trackEvent(eventName, parameters = {}) {
        console.log(`Event: ${eventName}`, parameters);

        // Analytics'e gönder
        if (window.gtag) {
            window.gtag('event', eventName, parameters);
        }
    }
}

// Global instance
window.advancedAI = new AdvancedAIIntegration();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdvancedAIIntegration;
}
