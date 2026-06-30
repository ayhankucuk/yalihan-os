// price.js - Gelişmiş Fiyat Yönetimi Modülü

/**
 * Advanced Price Manager
 * Para birimi çevirimi, fiyat hesaplama, AI önerileri
 */
window.advancedPriceManager = function () {
    return {
        // Ana fiyat bilgileri
        mainPrice: 0,
        mainPriceInput: '', // Kullanıcı girişi için string
        mainCurrency: 'TRY',
        startingPrice: 0,
        dailyPrice: 0,
        metrekare: 0,

        // Görünürlük kontrolleri
        showStartingPrice: false,
        showDailyPrice: false,

        // Döviz kurları
        exchangeRates: {
            TRY: 1,
            USD: 34.5,
            EUR: 37.2,
            GBP: 43.8,
        },
        lastRateUpdate: new Date().toLocaleString('tr-TR'),

        // Dönüştürülmüş fiyatlar
        convertedPrices: {
            TRY: 0,
            USD: 0,
            EUR: 0,
            GBP: 0,
        },

        // AI önerileri
        aiSuggestions: [],

        /**
         * Formatlanmış ana fiyat
         */
        get mainPriceFormatted() {
            return this.formatPrice(this.mainPrice, this.mainCurrency);
        },

        /**
         * Formatlanmış başlangıç fiyatı
         */
        get startingPriceFormatted() {
            return this.formatPrice(this.startingPrice, this.mainCurrency);
        },

        /**
         * Formatlanmış günlük fiyat
         */
        get dailyPriceFormatted() {
            return this.formatPrice(this.dailyPrice, this.mainCurrency);
        },

        /**
         * Fiyat yazıyla
         */
        get mainPriceWords() {
            return this.numberToWords(this.mainPrice);
        },

        /**
         * M² başı fiyat
         */
        get pricePerSqm() {
            if (this.metrekare > 0 && this.mainPrice > 0) {
                const perSqm = this.mainPrice / this.metrekare;
                return this.formatPrice(perSqm, this.mainCurrency);
            }
            return '-';
        },

        /**
         * Tüm fiyatları güncelle
         */
        updateAllPrices() {
            this.onCurrencyChange();
        },

        /**
         * Döviz kurlarını yükle
         */
        loadExchangeRates() {
            this.fetchExchangeRates();
        },

        /**
         * M² başı fiyat hesapla
         */
        calculatePricePerSqm() {
            // Metrekare değişince otomatik hesapla
            this.onPriceChange();
        },

        /**
         * Öneri uygula
         */
        applySuggestion(suggestion) {
            if (suggestion && suggestion.value) {
                this.mainPrice = suggestion.value;
                this.onPriceChange();
                window.toast?.success('Fiyat önerisi uygulandı!');
            }
        },

        /**
         * AI önerileri yenile
         */
        refreshAISuggestions() {
            this.getAIPriceSuggestion();
        },

        /**
         * Başlangıç fiyatı formatla
         */
        formatStartingPrice() {
            // Otomatik formatlanır
        },

        /**
         * Günlük fiyat formatla
         */
        formatDailyPrice() {
            // Otomatik formatlanır
        },

        /**
         * Fiyat formatla
         */
        formatPrice(amount, currency = 'TRY') {
            const symbols = {
                TRY: '₺',
                USD: '$',
                EUR: '€',
                GBP: '£',
            };

            const formatted = new Intl.NumberFormat('tr-TR').format(amount);
            return `${formatted} ${symbols[currency] || currency}`;
        },

        /**
         * Sayıyı yazıya çevir (gelişmiş Türkçe versiyon)
         */
        numberToWords(num) {
            if (!num || num === 0) return 'sıfır';

            const ones = ['', 'bir', 'iki', 'üç', 'dört', 'beş', 'altı', 'yedi', 'sekiz', 'dokuz'];
            const tens = [
                '',
                'on',
                'yirmi',
                'otuz',
                'kırk',
                'elli',
                'altmış',
                'yetmiş',
                'seksen',
                'doksan',
            ];
            const hundreds = [
                '',
                'yüz',
                'iki yüz',
                'üç yüz',
                'dört yüz',
                'beş yüz',
                'altı yüz',
                'yedi yüz',
                'sekiz yüz',
                'dokuz yüz',
            ];

            const convertThreeDigits = (n) => {
                const result = [];
                const h = Math.floor(n / 100);
                const t = Math.floor((n % 100) / 10);
                const o = n % 10;

                if (h > 0) result.push(hundreds[h]);
                if (t > 0) result.push(tens[t]);
                if (o > 0) result.push(ones[o]);

                return result.join(' ');
            };

            // Milyar
            const billion = Math.floor(num / 1000000000);
            num = num % 1000000000;

            // Milyon
            const million = Math.floor(num / 1000000);
            num = num % 1000000;

            // Bin
            const thousand = Math.floor(num / 1000);
            num = num % 1000;

            // Yüzler
            const rest = num;

            const result = [];

            if (billion > 0) {
                result.push(convertThreeDigits(billion) + ' milyar');
            }

            if (million > 0) {
                result.push(convertThreeDigits(million) + ' milyon');
            }

            if (thousand > 0) {
                result.push(convertThreeDigits(thousand) + ' bin');
            }

            if (rest > 0) {
                result.push(convertThreeDigits(rest));
            }

            // Sonuç boşsa sıfır döndür
            if (result.length === 0) {
                return 'sıfır';
            }

            return result.join(' ').trim();
        },

        /**
         * Döviz kuru çek
         */
        async fetchExchangeRates() {
            try {
                // ✅ API Helper kullan (merkezi yönetim)
                const result = await window.APIHelper?.request('currency.rates');

                if (result.success && result.data?.rates) {
                    this.exchangeRates = result.data.rates;
                    this.lastRateUpdate = new Date().toLocaleString('tr-TR');
                    this.updateConvertedPrices();
                }
            } catch (error) {
                console.warn('Döviz kurları alınamadı, varsayılan kurlar kullanılıyor');
            }
        },

        /**
         * Fiyatları çevir
         */
        updateConvertedPrices() {
            const baseTRY =
                this.mainCurrency === 'TRY'
                    ? this.mainPrice
                    : this.mainPrice * this.exchangeRates[this.mainCurrency];

            this.convertedPrices = {
                TRY: baseTRY,
                USD: baseTRY / this.exchangeRates.USD,
                EUR: baseTRY / this.exchangeRates.EUR,
                GBP: baseTRY / this.exchangeRates.GBP,
            };
        },

        /**
         * Fiyat input değiştiğinde (450- formatını destekler)
         */
        onPriceInputChange() {
            // Kullanıcı girişini temizle ve parse et
            const input = this.mainPriceInput.toString().trim();

            // Boş giriş kontrolü
            if (!input || input === '') {
                this.mainPrice = 0;
                this.onPriceChange();
                return;
            }

            // 450- formatını handle et
            if (input.endsWith('-')) {
                // Kısa format: 450- = 450000
                const baseNumber = input.slice(0, -1);
                const num = parseInt(baseNumber);
                if (!isNaN(num)) {
                    this.mainPrice = num * 1000;
                    this.mainPriceInput = this.mainPrice.toString();
                }
            } else {
                // Normal sayı girişi - Türkçe format desteği
                // Nokta ve virgülü temizle, sadece sayıları al
                const cleanInput = input.replace(/[^\d]/g, '');

                if (cleanInput && cleanInput.length > 0) {
                    // Büyük sayıları destekle
                    const num = parseInt(cleanInput);
                    if (!isNaN(num) && num >= 0) {
                        this.mainPrice = num;
                    }
                } else {
                    this.mainPrice = 0;
                }
            }

            // Fiyat limiti kontrolü
            this.validatePriceLimit();

            this.onPriceChange();
        },

        /**
         * Fiyat limiti kontrolü (TL ve döviz bazlı)
         */
        validatePriceLimit() {
            const maxPrices = {
                TRY: 1000000000, // 1 Milyar TL
                USD: 100000000, // 100 Milyon USD
                EUR: 100000000, // 100 Milyon EUR
                GBP: 100000000, // 100 Milyon GBP
            };

            if (this.mainPrice > maxPrices[this.mainCurrency]) {
                window.toast?.warning(
                    `Fiyat çok yüksek! Maksimum: ${this.formatPrice(
                        maxPrices[this.mainCurrency],
                        this.mainCurrency
                    )}`
                );
                this.mainPrice = maxPrices[this.mainCurrency];
                this.mainPriceInput = this.mainPrice.toString();
            }
        },

        /**
         * Fiyat input blur olduğunda (formatla)
         */
        onPriceBlur() {
            // Önce mainPrice'ı güncelle
            this.onPriceInputChange();

            // Sonra formatla
            if (this.mainPrice > 0) {
                this.mainPriceInput = this.mainPrice.toLocaleString('tr-TR');
            } else {
                this.mainPriceInput = '';
            }
        },

        /**
         * Ana fiyat değiştiğinde
         */
        onPriceChange() {
            this.updateConvertedPrices();
            this.aiSuggestions = []; // AI önerilerini sıfırla
        },

        /**
         * Para birimi değiştiğinde
         */
        onCurrencyChange() {
            this.validatePriceLimit();
            this.updateConvertedPrices();
        },

        /**
         * AI fiyat önerisi al
         */
        async getAIPriceSuggestion() {
            // Fiyat olmasa bile öneri isteyebilir (lokasyon bazlı)

            try {
                window.toast?.info('AI fiyat analizi yapılıyor...');

                // Gather context data explicitly if not available in this scope
                const context = {
                    fiyat: this.mainPrice || 0,
                    para_birimi: this.mainCurrency,
                    metrekare: this.metrekare || 0,
                    // Diğer veriler IlanAIController tarafında Request'ten veya DOM'dan toplanmalı
                    // Fakat IlanAIController::suggest action='price' parametreleri request body'den bekliyor
                    // Bu yüzden DOM'dan gerekli verileri alıp gönderelim
                };

                // DOM elementlerinden ek verileri topla (Context7 JS Pattern)
                const il = document.getElementById('il_id')?.selectedOptions[0]?.text;
                const ilce = document.getElementById('ilce_id')?.selectedOptions[0]?.text;
                const mahalle = document.getElementById('mahalle_id')?.selectedOptions[0]?.text;
                const kategori = document.getElementById('ana_kategori')?.selectedOptions[0]?.text;

                const payload = {
                    action: 'price',
                    fiyat: this.mainPrice,
                    para_birimi: this.mainCurrency,
                    metrekare: this.metrekare,
                    il: il,
                    ilce: ilce,
                    mahalle: mahalle,
                    kategori: kategori,
                };

                // ✅ URL should be resolved properly. Assuming 'admin.ai.suggest' is the name.
                // If APIHelper resolves names, use name. If not, use URL.
                // We'll use the URL directly as fallback or assume APIHelper matches the route definition we saw.
                // Route definition: Route::post('/suggest', ...)->name('suggest') under prefix 'ai' -> 'admin.ai.suggest'

                const result = await window.APIHelper?.request('admin.ai.suggest', {
                    method: 'POST',
                    body: JSON.stringify(payload),
                });

                if (result.success && result.suggestions) {
                    this.aiSuggestions = result.suggestions;
                    if (window.NotificationHelper) {
                        window.NotificationHelper.success('AI önerileri hazır!');
                    } else if (window.toast) {
                        window.toast.success('AI önerileri hazır!');
                    }

                    // Show suggestions panel if hidden
                    const suggestionsPanel = document.querySelector(
                        '[x-show="aiSuggestions.length > 0"]'
                    );
                    if (suggestionsPanel) suggestionsPanel.style.display = 'block';
                } else {
                    const message = result.message || result.error || 'AI önerisi alınamadı';
                    if (window.NotificationHelper) {
                        window.NotificationHelper.error(message);
                    } else if (window.toast) {
                        window.toast.error(message);
                    }
                }
            } catch (error) {
                console.error('AI price suggestion error:', error);
                const errorMessage =
                    error instanceof window.APIError ? error.message : 'AI servisi çalışmıyor';
                if (window.NotificationHelper) {
                    window.NotificationHelper.error(errorMessage);
                } else if (window.toast) {
                    window.toast.error(errorMessage);
                }
            }
        },

        init() {
            console.log('Price manager initialized');

            // mainPriceInput'u başlat
            this.mainPriceInput = this.mainPrice > 0 ? this.mainPrice.toString() : '';

            this.fetchExchangeRates();

            // Fiyat değişikliklerini izle
            this.$watch('mainPrice', () => this.onPriceChange());
            this.$watch('mainCurrency', () => this.onCurrencyChange());
            this.$watch('metrekare', () => this.onPriceChange());

            // Listen for external trigger
            document.addEventListener('trigger-ai-price', () => {
                this.getAIPriceSuggestion();
            });
        },
    };
};

// Export
export default window.advancedPriceManager;
