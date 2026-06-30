/**
 * 🏗️ MODERN PRICE SYSTEM - NEO DESIGN INTEGRATION
 * Fiyat yönetimi ve kategori workflow entegrasyonu
 * Tarih: 19 Ekim 2025
 * Context7 Compliant & Neo Design System
 */

class ModernPriceSystem {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`[ModernPriceSystem] Container with id "${containerId}" not found`);
            return;
        }

        this.options = {
            // ✅ API Endpoints (Merkezi config'den alınacak)
            apiBase: window.APIConfig?.apiPrefix || '/api',
            currencyRatesEndpoint: window.APIConfig?.currency?.rates || '/api/currency/rates',
            priceValidationEndpoint: '/prices/validate', // TODO: Merkezi config'e ekle

            // Supported Currencies
            currencies: {
                TRY: { symbol: '₺', name: 'Türk Lirası', code: 'TRY' },
                USD: { symbol: '$', name: 'Amerikan Doları', code: 'USD' },
                EUR: { symbol: '€', name: 'Euro', code: 'EUR' },
                GBP: { symbol: '£', name: 'İngiliz Sterlini', code: 'GBP' },
            },

            // Price Types
            priceTypes: {
                satilik: {
                    name: 'Satılık',
                    icon: '💰',
                    requiresMainPrice: true,
                },
                kiralik: {
                    name: 'Kiralık',
                    icon: '🔑',
                    requiresMainPrice: true,
                },
                'gunluk-kiralik': {
                    name: 'Günlük Kiralık',
                    icon: '📅',
                    requiresDailyPrice: true,
                },
                'sezonluk-kiralik': {
                    name: 'Sezonluk Kiralık',
                    icon: '🌞',
                    requiresSeasonalPrice: true,
                },
                'devren-satilik': {
                    name: 'Devren Satılık',
                    icon: '🔄',
                    requiresMainPrice: true,
                },
                'devren-kiralik': {
                    name: 'Devren Kiralık',
                    icon: '🔄',
                    requiresMainPrice: true,
                },
            },

            // Default Settings
            defaultCurrency: 'TRY',
            enableAIPrediction: true,
            enableMarketAnalysis: true,
            enablePriceHistory: true,
            autoSave: true,

            ...options,
        };

        this.state = {
            // Main Price Data
            mainPrice: 0,
            mainCurrency: this.options.defaultCurrency,

            // Additional Prices
            dailyPrice: 0,
            seasonalPrice: 0,
            startingPrice: 0,

            // Property Info
            squareMeters: 0,
            pricePerSqm: 0,

            // Exchange Rates
            exchangeRates: {},
            lastRateUpdate: null,

            // Price Type (from category workflow)
            selectedPriceType: null,

            // Converted Prices
            convertedPrices: {},

            // AI Suggestions
            aiSuggestions: [],
            marketAnalysis: null,

            // Validation
            isValid: false,
            errors: [],

            // State
            isLoading: false,
            isDirty: false,
        };

        this.cache = {
            priceHistory: [],
            marketData: null,
            similarProperties: [],
        };

        this.init();
    }

    init() {
        this.render();
        this.bindEvents();
        this.loadExchangeRates();
        this.loadPriceHistory();
        console.log('[ModernPriceSystem] Initialized successfully');
    }

    render() {
        this.container.innerHTML = `
            <div class="modern-price-system neo-card">
                <!-- Header - Yalıhan Bekçi Standardı -->
                <div class="price-system-header mb-6">
                    <h3 class="text-xl font-bold flex items-center" style="color: var(--text-primary);">
                        <div class="price-icon mr-3">
                            <svg class="w-6 h-6" style="color: var(--success);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                            </svg>
                        </div>
                        <span>💰 Yalıhan Fiyat Sistemi</span>
                        <div class="ml-auto price-status-indicator">
                            <!-- Status indicator will be here -->
                        </div>
                    </h3>
                    <p class="text-sm mt-1" style="color: var(--text-secondary);">
                        Emlak fiyatlandırma ve para birimi yönetimi - Yalıhan Bekçi Uyumlu
                    </p>
                </div>

                <!-- Main Price Section -->
                ${this.renderMainPriceSection()}

                <!-- Additional Prices -->
                ${this.renderAdditionalPricesSection()}

                <!-- Currency Conversion -->
                ${this.renderCurrencyConversionSection()}

                <!-- Price Analysis -->
                ${this.renderPriceAnalysisSection()}

                <!-- AI Suggestions -->
                ${this.renderAISuggestionsSection()}

                <!-- Price History -->
                ${this.renderPriceHistorySection()}

                <!-- Actions -->
                <div class="price-actions mt-8 flex justify-between items-center">
                    <div class="flex space-x-3">
                        <button class="neo-btn neo-neo-btn neo-btn-secondary" data-action="calculate-suggestions">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            AI Önerileri
                        </button>
                        <button class="neo-btn neo-btn-accent" data-action="refresh-rates">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                            Kurları Güncelle
                        </button>
                    </div>

                    <div class="flex space-x-3">
                        <button class="neo-btn neo-btn-outline" data-action="reset-prices">
                            Sıfırla
                        </button>
                        <button class="neo-btn neo-neo-btn neo-btn-primary" data-action="save-prices">
                            <span class="button-text">Fiyatları Kaydet</span>
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.cacheElements();
    }

    renderMainPriceSection() {
        return `
            <div class="main-price-section mb-6">
                <div class="neo-glass-effect p-6 rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-semibold" style="color: var(--text-primary);">
                            💰 Ana Fiyat Belirleme
                        </h4>
                        <div class="price-type-badge" style="display: none;">
                            <!-- Price type badge will be here -->
                        </div>
                    </div>

                    <div class="main-price-input-group grid grid-cols-1 lg:grid-cols-3 gap-4">
                        <!-- Price Input -->
                        <div class="price-input-container lg:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Fiyat
                            </label>
                            <div class="relative">
                                <input type="text"
                                       class="main-price-input neo-input pl-10 pr-20 text-lg font-semibold"
                                       placeholder="0"
                                       data-price-input="main">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="currency-symbol text-gray-500 font-medium"></span>
                                </div>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <span class="price-words text-xs text-gray-400"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Currency Selector -->
                        <div class="currency-selector-container">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Para Birimi
                            </label>
                            <select class="currency-selector neo-select" data-currency-select="main">
                                ${Object.entries(this.options.currencies)
                                    .map(
                                        ([code, currency]) => `
                                    <option value="${code}" ${
                                        code === this.state.mainCurrency ? 'selected' : ''
                                    }>
                                        ${currency.symbol} ${currency.name}
                                    </option>
                                `
                                    )
                                    .join('')}
                            </select>
                        </div>
                    </div>

                    <!-- Square Meter Calculation -->
                    <div class="sqm-calculation mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Metrekare (m²)
                                </label>
                                <input type="number"
                                       class="square-meters-input neo-input"
                                       placeholder="0"
                                       min="1"
                                       data-sqm-input>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    M² Başı Fiyat
                                </label>
                                <div class="price-per-sqm-display neo-input bg-gray-100 dark:bg-gray-700 flex items-center">
                                    <span class="price-per-sqm-value font-semibold">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderAdditionalPricesSection() {
        return `
            <div class="additional-prices-section mb-6" style="display: none;">
                <div class="neo-glass-effect p-6 rounded-xl">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-4">Ek Fiyat Seçenekleri</h4>

                    <div class="additional-prices-grid space-y-4">
                        <!-- Daily Price -->
                        <div class="daily-price-group" style="display: none;">
                            <div class="flex items-center justify-between mb-3">
                                <label class="flex items-center">
                                    <input type="checkbox" class="neo-checkbox mr-2" data-enable="daily-price">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        📅 Günlük Fiyat
                                    </span>
                                </label>
                            </div>
                            <div class="daily-price-input-container" style="display: none;">
                                <input type="text"
                                       class="daily-price-input neo-input"
                                       placeholder="Günlük fiyat"
                                       data-price-input="daily">
                            </div>
                        </div>

                        <!-- Seasonal Price -->
                        <div class="seasonal-price-group" style="display: none;">
                            <div class="flex items-center justify-between mb-3">
                                <label class="flex items-center">
                                    <input type="checkbox" class="neo-checkbox mr-2" data-enable="seasonal-price">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        🌞 Sezonluk Fiyat
                                    </span>
                                </label>
                            </div>
                            <div class="seasonal-price-input-container" style="display: none;">
                                <input type="text"
                                       class="seasonal-price-input neo-input"
                                       placeholder="Sezonluk fiyat"
                                       data-price-input="seasonal">
                            </div>
                        </div>

                        <!-- Starting Price -->
                        <div class="starting-price-group">
                            <div class="flex items-center justify-between mb-3">
                                <label class="flex items-center">
                                    <input type="checkbox" class="neo-checkbox mr-2" data-enable="starting-price">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        🎯 Başlangıç Fiyatı
                                    </span>
                                </label>
                            </div>
                            <div class="starting-price-input-container" style="display: none;">
                                <input type="text"
                                       class="starting-price-input neo-input"
                                       placeholder="Başlangıç fiyatı"
                                       data-price-input="starting">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderCurrencyConversionSection() {
        return `
            <div class="currency-conversion-section mb-6">
                <div class="neo-glass-effect p-6 rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white">Döviz Çevirimi</h4>
                        <div class="last-update-info text-xs text-gray-500">
                            <span class="last-update-text">Yükleniyor...</span>
                        </div>
                    </div>

                    <div class="converted-prices-grid grid grid-cols-2 lg:grid-cols-4 gap-4">
                        ${Object.entries(this.options.currencies)
                            .map(
                                ([code, currency]) => `
                            <div class="converted-price-item ${
                                code === this.state.mainCurrency ? 'opacity-50' : ''
                            }"
                                 data-currency="${code}">
                                <div class="text-center p-4 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-700 rounded-lg">
                                    <div class="currency-symbol text-2xl mb-2">${
                                        currency.symbol
                                    }</div>
                                    <div class="currency-code text-xs text-gray-500 mb-1">${code}</div>
                                    <div class="converted-amount font-bold text-gray-900 dark:text-white">
                                        <span class="amount">-</span>
                                    </div>
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

    renderPriceAnalysisSection() {
        return `
            <div class="price-analysis-section mb-6" style="display: none;">
                <div class="neo-glass-effect p-6 rounded-xl">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 00-2-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4"/>
                        </svg>
                        Piyasa Analizi
                    </h4>

                    <div class="analysis-content">
                        <div class="analysis-loading text-center py-8" style="display: none;">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
                            <p class="text-gray-500">Piyasa verisi analiz ediliyor...</p>
                        </div>

                        <div class="analysis-results" style="display: none;">
                            <!-- Analysis results will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderAISuggestionsSection() {
        return `
            <div class="ai-suggestions-section mb-6" style="display: none;">
                <div class="neo-glass-effect p-6 rounded-xl">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        AI Fiyat Önerileri
                    </h4>

                    <div class="suggestions-content">
                        <div class="suggestions-loading text-center py-8" style="display: none;">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600 mx-auto mb-4"></div>
                            <p class="text-gray-500">AI önerileri hesaplanıyor...</p>
                        </div>

                        <div class="suggestions-list space-y-3" style="display: none;">
                            <!-- AI suggestions will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderPriceHistorySection() {
        return `
            <div class="price-history-section mb-6" style="display: none;">
                <div class="neo-glass-effect p-6 rounded-xl">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                        Fiyat Geçmişi
                    </h4>

                    <div class="price-history-chart">
                        <!-- Price history chart will be here -->
                    </div>
                </div>
            </div>
        `;
    }

    cacheElements() {
        this.elements = {
            // Price Inputs
            mainPriceInput: this.container.querySelector('.main-price-input'),
            dailyPriceInput: this.container.querySelector('.daily-price-input'),
            seasonalPriceInput: this.container.querySelector('.seasonal-price-input'),
            startingPriceInput: this.container.querySelector('.starting-price-input'),

            // Currency
            currencySelector: this.container.querySelector('.currency-selector'),
            currencySymbol: this.container.querySelector('.currency-symbol'),

            // Square meters
            squareMetersInput: this.container.querySelector('.square-meters-input'),
            pricePerSqmValue: this.container.querySelector('.price-per-sqm-value'),

            // Converted prices
            convertedPricesGrid: this.container.querySelector('.converted-prices-grid'),

            // Additional price toggles
            dailyPriceToggle: this.container.querySelector('[data-enable="daily-price"]'),
            seasonalPriceToggle: this.container.querySelector('[data-enable="seasonal-price"]'),
            startingPriceToggle: this.container.querySelector('[data-enable="starting-price"]'),

            // Sections
            additionalPricesSection: this.container.querySelector('.additional-prices-section'),
            priceAnalysisSection: this.container.querySelector('.price-analysis-section'),
            aiSuggestionsSection: this.container.querySelector('.ai-suggestions-section'),
            priceHistorySection: this.container.querySelector('.price-history-section'),

            // Status
            priceStatusIndicator: this.container.querySelector('.price-status-indicator'),
            lastUpdateText: this.container.querySelector('.last-update-text'),
            priceWords: this.container.querySelector('.price-words'),
        };
    }

    bindEvents() {
        // Main price input
        if (this.elements.mainPriceInput) {
            this.elements.mainPriceInput.addEventListener('input', (e) => {
                this.handlePriceInput(e.target.value, 'main');
            });
            this.elements.mainPriceInput.addEventListener('blur', () => {
                this.formatPriceInput('main');
            });
        }

        // Currency selector
        if (this.elements.currencySelector) {
            this.elements.currencySelector.addEventListener('change', (e) => {
                this.handleCurrencyChange(e.target.value);
            });
        }

        // Square meters input
        if (this.elements.squareMetersInput) {
            this.elements.squareMetersInput.addEventListener('input', (e) => {
                this.handleSquareMetersChange(parseFloat(e.target.value) || 0);
            });
        }

        // Additional price toggles
        ['daily-price', 'seasonal-price', 'starting-price'].forEach((type) => {
            const toggle = this.container.querySelector(`[data-enable="${type}"]`);
            if (toggle) {
                toggle.addEventListener('change', (e) => {
                    this.toggleAdditionalPrice(type, e.target.checked);
                });
            }
        });

        // Action buttons
        this.container.addEventListener('click', (e) => {
            const action = e.target.closest('[data-action]')?.dataset.action;
            if (action) {
                this.handleAction(action, e);
            }
        });
    }

    // Price Input Handling
    handlePriceInput(value, type = 'main') {
        const numericValue = this.parsePrice(value);

        switch (type) {
            case 'main':
                this.state.mainPrice = numericValue;
                this.updatePriceWords();
                break;
            case 'daily':
                this.state.dailyPrice = numericValue;
                break;
            case 'seasonal':
                this.state.seasonalPrice = numericValue;
                break;
            case 'starting':
                this.state.startingPrice = numericValue;
                break;
        }

        this.updateConvertedPrices();
        this.updatePricePerSqm();
        this.validatePrices();
        this.markDirty();
    }

    parsePrice(value) {
        // Remove all non-numeric characters except decimal separators
        const cleaned = value.toString().replace(/[^\d.,]/g, '');

        // Handle Turkish decimal separator
        const normalized = cleaned.replace(',', '.');

        const parsed = parseFloat(normalized);
        return isNaN(parsed) ? 0 : parsed;
    }

    formatPrice(amount, currency = this.state.mainCurrency) {
        if (!amount || amount === 0) return '0';

        const currencyConfig = this.options.currencies[currency];
        if (!currencyConfig) return amount.toString();

        // Format number with Turkish locale
        const formatted = new Intl.NumberFormat('tr-TR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);

        return `${currencyConfig.symbol} ${formatted}`;
    }

    formatPriceInput(type = 'main') {
        let value, input;

        switch (type) {
            case 'main':
                value = this.state.mainPrice;
                input = this.elements.mainPriceInput;
                break;
            case 'daily':
                value = this.state.dailyPrice;
                input = this.elements.dailyPriceInput;
                break;
            case 'seasonal':
                value = this.state.seasonalPrice;
                input = this.elements.seasonalPriceInput;
                break;
            case 'starting':
                value = this.state.startingPrice;
                input = this.elements.startingPriceInput;
                break;
        }

        if (input && value > 0) {
            input.value = new Intl.NumberFormat('tr-TR').format(value);
        }
    }

    // Currency Handling
    handleCurrencyChange(newCurrency) {
        this.state.mainCurrency = newCurrency;
        this.updateCurrencySymbol();
        this.updateConvertedPrices();
        this.markDirty();
    }

    updateCurrencySymbol() {
        const currency = this.options.currencies[this.state.mainCurrency];
        if (this.elements.currencySymbol && currency) {
            this.elements.currencySymbol.textContent = currency.symbol;
        }
    }

    // Exchange Rates
    async loadExchangeRates() {
        this.state.isLoading = true;

        try {
            // ✅ Merkezi API Config kullan (hardcoded fallback YOK)
            if (!window.APIConfig?.currency?.rates) {
                console.error('❌ APIConfig.currency.rates tanımlı değil! api-config.js yüklü mü kontrol edin.');
                return;
            }

            const response = await fetch(window.APIConfig.currency.rates);
            const data = await response.json();

            if (data.success) {
                this.state.exchangeRates = data.rates;
                this.state.lastRateUpdate = new Date();
                this.updateLastUpdateDisplay();
                this.updateConvertedPrices();
            } else {
                // Use fallback rates
                this.state.exchangeRates = {
                    TRY: 1,
                    USD: 34.5,
                    EUR: 37.2,
                    GBP: 43.8,
                };
                console.warn('[ModernPriceSystem] Using fallback exchange rates');
            }
        } catch (error) {
            console.error('[ModernPriceSystem] Failed to load exchange rates:', error);
            // Use fallback rates
            this.state.exchangeRates = {
                TRY: 1,
                USD: 34.5,
                EUR: 37.2,
                GBP: 43.8,
            };
        } finally {
            this.state.isLoading = false;
        }
    }

    updateConvertedPrices() {
        if (!this.state.mainPrice || Object.keys(this.state.exchangeRates).length === 0) {
            return;
        }

        // Convert main price to TRY first
        const priceInTRY =
            this.state.mainCurrency === 'TRY'
                ? this.state.mainPrice
                : this.state.mainPrice * this.state.exchangeRates[this.state.mainCurrency];

        // Convert to all currencies
        Object.keys(this.options.currencies).forEach((currency) => {
            if (currency === this.state.mainCurrency) {
                this.state.convertedPrices[currency] = this.state.mainPrice;
            } else if (currency === 'TRY') {
                this.state.convertedPrices[currency] = priceInTRY;
            } else {
                this.state.convertedPrices[currency] =
                    priceInTRY / this.state.exchangeRates[currency];
            }
        });

        this.updateConvertedPricesDisplay();
    }

    updateConvertedPricesDisplay() {
        Object.entries(this.state.convertedPrices).forEach(([currency, amount]) => {
            const element = this.container.querySelector(`[data-currency="${currency}"] .amount`);
            if (element) {
                element.textContent = this.formatConvertedPrice(amount, currency);
            }
        });
    }

    formatConvertedPrice(amount, currency) {
        if (!amount || amount === 0) return '-';

        return new Intl.NumberFormat('tr-TR', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(Math.round(amount));
    }

    updateLastUpdateDisplay() {
        if (this.elements.lastUpdateText && this.state.lastRateUpdate) {
            this.elements.lastUpdateText.textContent = `Son güncelleme: ${this.state.lastRateUpdate.toLocaleTimeString(
                'tr-TR'
            )}`;
        }
    }

    // Square Meters Calculation
    handleSquareMetersChange(sqm) {
        this.state.squareMeters = sqm;
        this.updatePricePerSqm();
        this.markDirty();
    }

    updatePricePerSqm() {
        if (this.state.squareMeters > 0 && this.state.mainPrice > 0) {
            this.state.pricePerSqm = this.state.mainPrice / this.state.squareMeters;

            if (this.elements.pricePerSqmValue) {
                this.elements.pricePerSqmValue.textContent = this.formatPrice(
                    this.state.pricePerSqm,
                    this.state.mainCurrency
                );
            }
        } else {
            this.state.pricePerSqm = 0;
            if (this.elements.pricePerSqmValue) {
                this.elements.pricePerSqmValue.textContent = '-';
            }
        }
    }

    // Price Words
    updatePriceWords() {
        if (this.elements.priceWords && this.state.mainPrice > 0) {
            this.elements.priceWords.textContent = this.numberToWords(this.state.mainPrice);
        }
    }

    numberToWords(num) {
        if (num === 0) return 'sıfır';

        const units = ['', 'bin', 'milyon', 'milyar'];
        const words = [];
        let unitIndex = 0;

        while (num > 0) {
            const chunk = num % 1000;
            if (chunk !== 0) {
                words.unshift(`${chunk} ${units[unitIndex]}`);
            }
            num = Math.floor(num / 1000);
            unitIndex++;
        }

        return words.join(' ').trim();
    }

    // Additional Prices
    toggleAdditionalPrice(type, status) {
        const container = this.container.querySelector(
            `.${type.replace('-', '-')}-input-container`
        );
        if (container) {
            container.style.display = status ? 'block' : 'none';
        }

        if (!status) {
            // Reset the price value
            switch (type) {
                case 'daily-price':
                    this.state.dailyPrice = 0;
                    break;
                case 'seasonal-price':
                    this.state.seasonalPrice = 0;
                    break;
                case 'starting-price':
                    this.state.startingPrice = 0;
                    break;
            }
        }

        this.markDirty();
    }

    // Price Type Integration (from category workflow)
    setPriceType(priceType) {
        this.state.selectedPriceType = priceType;
        const typeConfig = this.options.priceTypes[priceType];

        if (!typeConfig) return;

        // Show/hide additional price sections based on price type
        if (typeConfig.requiresDailyPrice) {
            this.container.querySelector('.daily-price-group').style.display = 'block';
        }
        if (typeConfig.requiresSeasonalPrice) {
            this.container.querySelector('.seasonal-price-group').style.display = 'block';
        }

        // Show additional prices section if any additional price is needed
        if (typeConfig.requiresDailyPrice || typeConfig.requiresSeasonalPrice) {
            this.elements.additionalPricesSection.style.display = 'block';
        }

        // Update price type badge
        const badge = this.container.querySelector('.price-type-badge');
        if (badge) {
            badge.innerHTML = `
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                    ${typeConfig.icon} ${typeConfig.name}
                </span>
            `;
            badge.style.display = 'block';
        }
    }

    // Actions
    handleAction(action, event) {
        switch (action) {
            case 'calculate-suggestions':
                this.calculateAISuggestions();
                break;
            case 'refresh-rates':
                this.loadExchangeRates();
                break;
            case 'reset-prices':
                this.resetPrices();
                break;
            case 'save-prices':
                this.savePrices();
                break;
        }
    }

    async calculateAISuggestions() {
        this.elements.aiSuggestionsSection.style.display = 'block';
        const loadingEl = this.container.querySelector('.suggestions-loading');
        const listEl = this.container.querySelector('.suggestions-list');

        if (loadingEl) loadingEl.style.display = 'block';
        if (listEl) listEl.style.display = 'none';

        try {
            // Simulate AI suggestions (in real app, this would be an API call)
            await new Promise((resolve) => setTimeout(resolve, 2000));

            const suggestions = this.generateMockAISuggestions();
            this.displayAISuggestions(suggestions);
        } catch (error) {
            console.error('[ModernPriceSystem] Failed to calculate AI suggestions:', error);
        } finally {
            if (loadingEl) loadingEl.style.display = 'none';
            if (listEl) listEl.style.display = 'block';
        }
    }

    generateMockAISuggestions() {
        const basePrice = this.state.mainPrice || 1000000;
        return [
            {
                type: 'market-average',
                title: 'Piyasa Ortalaması',
                description: 'Benzer emlakların ortalama fiyatı',
                price: Math.round(basePrice * 0.95),
                confidence: 85,
                reasoning: 'Aynı bölgedeki benzer emlaklar analiz edildi',
            },
            {
                type: 'optimistic',
                title: 'İyimser Değerleme',
                description: 'En yüksek satış potansiyeli',
                price: Math.round(basePrice * 1.1),
                confidence: 70,
                reasoning: 'Premium özellikler ve lokasyon avantajı',
            },
            {
                type: 'quick-sale',
                title: 'Hızlı Satış',
                description: 'Kısa sürede satış için',
                price: Math.round(basePrice * 0.85),
                confidence: 90,
                reasoning: 'Piyasa ortalamasının altında cazip fiyat',
            },
        ];
    }

    displayAISuggestions(suggestions) {
        const listEl = this.container.querySelector('.suggestions-list');
        if (!listEl) return;

        listEl.innerHTML = suggestions
            .map(
                (suggestion) => `
            <div class="suggestion-item neo-glass-effect p-4 rounded-lg">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <h5 class="font-semibold text-gray-900 dark:text-white">${
                            suggestion.title
                        }</h5>
                        <p class="text-sm text-gray-500 dark:text-gray-400">${
                            suggestion.description
                        }</p>
                    </div>
                    <div class="text-right">
                        <div class="text-lg font-bold text-green-600">
                            ${this.formatPrice(suggestion.price, this.state.mainCurrency)}
                        </div>
                        <div class="text-xs text-gray-500">%${suggestion.confidence} güven</div>
                    </div>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400 mb-3">${suggestion.reasoning}</p>
                <button class="neo-btn neo-btn-sm neo-neo-btn neo-btn-primary"
                        onclick="modernPriceSystem.applySuggestion(${suggestion.price})">
                    Bu Fiyatı Uygula
                </button>
            </div>
        `
            )
            .join('');
    }

    applySuggestion(price) {
        this.state.mainPrice = price;
        this.elements.mainPriceInput.value = new Intl.NumberFormat('tr-TR').format(price);
        this.updateConvertedPrices();
        this.updatePricePerSqm();
        this.updatePriceWords();
        this.markDirty();

        this.showNotification('Önerilen fiyat uygulandı', 'success');
    }

    // Validation
    validatePrices() {
        this.state.errors = [];

        if (!this.state.mainPrice || this.state.mainPrice <= 0) {
            this.state.errors.push('Ana fiyat gereklidir');
        }

        // Currency-specific validations
        const maxPrices = {
            TRY: 1000000000, // 1 Billion TL
            USD: 100000000, // 100 Million USD
            EUR: 100000000, // 100 Million EUR
            GBP: 100000000, // 100 Million GBP
        };

        if (this.state.mainPrice > maxPrices[this.state.mainCurrency]) {
            this.state.errors.push(
                `Fiyat çok yüksek! Maksimum: ${this.formatPrice(
                    maxPrices[this.state.mainCurrency],
                    this.state.mainCurrency
                )}`
            );
        }

        this.state.isValid = this.state.errors.length === 0;
        this.updateValidationStatus();

        return this.state.isValid;
    }

    updateValidationStatus() {
        if (this.elements.priceStatusIndicator) {
            if (this.state.isValid) {
                this.elements.priceStatusIndicator.innerHTML = `
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Geçerli
                    </span>
                `;
            } else {
                this.elements.priceStatusIndicator.innerHTML = `
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Hatalı
                    </span>
                `;
            }
        }
    }

    // Data Management
    resetPrices() {
        this.state.mainPrice = 0;
        this.state.dailyPrice = 0;
        this.state.seasonalPrice = 0;
        this.state.startingPrice = 0;
        this.state.squareMeters = 0;
        this.state.pricePerSqm = 0;

        // Reset inputs
        if (this.elements.mainPriceInput) this.elements.mainPriceInput.value = '';
        if (this.elements.squareMetersInput) this.elements.squareMetersInput.value = '';

        // Reset additional price toggles
        ['daily-price', 'seasonal-price', 'starting-price'].forEach((type) => {
            const toggle = this.container.querySelector(`[data-enable="${type}"]`);
            if (toggle) toggle.checked = false;
            this.toggleAdditionalPrice(type, false);
        });

        this.updateConvertedPrices();
        this.updatePricePerSqm();
        this.updatePriceWords();
        this.validatePrices();
        this.markClean();

        this.showNotification('Fiyatlar sıfırlandı', 'info');
    }

    async savePrices() {
        if (!this.validatePrices()) {
            this.showNotification('Lütfen geçerli fiyat bilgileri girin', 'error');
            return;
        }

        try {
            const priceData = this.getPriceData();

            // Emit custom event
            this.container.dispatchEvent(
                new CustomEvent('prices-saved', {
                    detail: priceData,
                })
            );

            // Save to localStorage as backup
            localStorage.setItem('price_system_data', JSON.stringify(priceData));

            this.markClean();
            this.showNotification('Fiyatlar kaydedildi', 'success');

            console.log('[ModernPriceSystem] Prices saved:', priceData);
        } catch (error) {
            console.error('[ModernPriceSystem] Failed to save prices:', error);
            this.showNotification('Fiyatlar kaydedilemedi', 'error');
        }
    }

    getPriceData() {
        return {
            main: {
                price: this.state.mainPrice,
                currency: this.state.mainCurrency,
                formatted: this.formatPrice(this.state.mainPrice, this.state.mainCurrency),
            },
            additional: {
                daily: this.state.dailyPrice,
                seasonal: this.state.seasonalPrice,
                starting: this.state.startingPrice,
            },
            property: {
                squareMeters: this.state.squareMeters,
                pricePerSqm: this.state.pricePerSqm,
            },
            converted: this.state.convertedPrices,
            metadata: {
                priceType: this.state.selectedPriceType,
                lastUpdate: new Date().toISOString(),
                exchangeRates: this.state.exchangeRates,
            },
        };
    }

    loadPriceHistory() {
        // Load from localStorage or API
        const saved = localStorage.getItem('price_system_data');
        if (saved) {
            try {
                const data = JSON.parse(saved);
                // Restore state from saved data
                this.restoreFromData(data);
            } catch (error) {
                console.warn('[ModernPriceSystem] Failed to load saved data:', error);
            }
        }
    }

    restoreFromData(data) {
        if (data.main) {
            this.state.mainPrice = data.main.price || 0;
            this.state.mainCurrency = data.main.currency || this.options.defaultCurrency;
        }

        if (data.additional) {
            this.state.dailyPrice = data.additional.daily || 0;
            this.state.seasonalPrice = data.additional.seasonal || 0;
            this.state.startingPrice = data.additional.starting || 0;
        }

        if (data.property) {
            this.state.squareMeters = data.property.squareMeters || 0;
        }

        // Update UI
        this.updateAllDisplays();
    }

    updateAllDisplays() {
        if (this.elements.mainPriceInput && this.state.mainPrice > 0) {
            this.elements.mainPriceInput.value = new Intl.NumberFormat('tr-TR').format(
                this.state.mainPrice
            );
        }

        if (this.elements.squareMetersInput && this.state.squareMeters > 0) {
            this.elements.squareMetersInput.value = this.state.squareMeters;
        }

        if (this.elements.currencySelector) {
            this.elements.currencySelector.value = this.state.mainCurrency;
        }

        this.updateCurrencySymbol();
        this.updateConvertedPrices();
        this.updatePricePerSqm();
        this.updatePriceWords();
        this.validatePrices();
    }

    // State Management
    markDirty() {
        this.state.isDirty = true;
        if (this.options.autoSave) {
            clearTimeout(this.autoSaveTimeout);
            this.autoSaveTimeout = setTimeout(() => {
                this.savePrices();
            }, 5000); // Auto-save after 5 seconds
        }
    }

    markClean() {
        this.state.isDirty = false;
        clearTimeout(this.autoSaveTimeout);
    }

    // Notifications
    showNotification(message, type = 'info') {
        // Create toast notification
        const toast = document.createElement('div');
        toast.className = `neo-toast neo-toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.remove();
        }, 3000);
    }

    // Public API
    getState() {
        return { ...this.state };
    }

    setState(newState) {
        this.state = { ...this.state, ...newState };
        this.updateAllDisplays();
    }

    isValid() {
        return this.validatePrices();
    }

    isDirty() {
        return this.state.isDirty;
    }

    // Integration with Category Workflow
    static integrateWithCategoryWorkflow(categoryWorkflow, priceSystem) {
        // Listen for workflow completion
        categoryWorkflow.container.addEventListener('workflow-complete', (e) => {
            const { publication_type_id } = e.detail;

            // Map publication type to price type
            const priceTypeMapping = {
                1: 'satilik',
                2: 'kiralik',
                3: 'gunluk-kiralik',
                4: 'sezonluk-kiralik',
                5: 'devren-satilik',
                6: 'devren-kiralik',
            };

            const priceType = priceTypeMapping[publication_type_id];
            if (priceType) {
                priceSystem.setPriceType(priceType);
            }
        });
    }
}

// CSS for price system - Yalıhan Bekçi Uyumlu
const priceSystemCSS = `
    .neo-toast {
        position: fixed;
        top: 1rem;
        right: 1rem;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        color: white;
        z-index: 1000;
        animation: slideIn 0.3s ease-out;
        border: 1px solid rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
    }

    .neo-toast-success {
        background: var(--success);
        border-color: var(--success);
    }
    .neo-toast-error {
        background: var(--error);
        border-color: var(--error);
    }
    .neo-toast-info {
        background: var(--accent-primary);
        border-color: var(--accent-primary);
    }
    .neo-toast-warning {
        background: var(--warning);
        border-color: var(--warning);
    }

    .converted-price-item.opacity-50 {
        opacity: 0.5;
    }

    .main-price-input {
        font-size: 1.125rem;
        font-weight: 600;
        background: var(--bg-primary) !important;
        color: var(--text-primary) !important;
        border-color: var(--border-primary) !important;
    }

    .suggestion-item {
        transition: all 0.3s ease;
        background: var(--bg-primary);
        border: 1px solid var(--border-primary);
    }

    .suggestion-item:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px var(--shadow);
        border-color: var(--accent-primary);
    }

    /* Yalıhan Price System Specific Styles */
    .price-system-header {
        border-bottom: 1px solid var(--border-primary);
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }

    .currency-symbol {
        color: var(--accent-primary);
        font-weight: 600;
    }

    .price-words {
        color: var(--text-tertiary);
        font-style: italic;
    }

    .converted-price-item {
        background: var(--bg-tertiary);
        border: 1px solid var(--border-secondary);
        transition: all 0.3s ease;
    }

    .converted-price-item:hover {
        border-color: var(--accent-primary);
        transform: translateY(-2px);
    }

    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    /* Dark mode specific overrides */
    [data-theme="dark"] .main-price-input:focus {
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.2);
    }

    [data-theme="dark"] .suggestion-item {
        background: var(--bg-secondary);
    }
`;

// Inject CSS
if (!document.getElementById('modern-price-system-css')) {
    const style = document.createElement('style');
    style.id = 'modern-price-system-css';
    style.textContent = priceSystemCSS;
    document.head.appendChild(style);
}

// Global instance
window.ModernPriceSystem = ModernPriceSystem;

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ModernPriceSystem;
}
