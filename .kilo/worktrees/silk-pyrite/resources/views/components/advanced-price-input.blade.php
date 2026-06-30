{{-- Gelişmiş Fiyat Girişi Component --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 dark:bg-slate-900 dark:border-slate-700 dark:shadow-none" x-data="advancedPriceInput()">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Fiyat Bilgileri</h3>
        <div class="text-sm text-gray-500">
            <span x-show="isValidPrice" class="text-green-600">✓ Geçerli</span>
            <span x-show="!isValidPrice" class="text-red-600">⚠ Kontrol Et</span>
        </div>
    </div>

    {{-- Ana Fiyat Girişi --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                Ana Fiyat <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <input type="text" x-model="mainPrice" @input="formatMainPrice()"
                    class="w-full px-4 py-2.5 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="0">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <select x-model="mainCurrency" @change="updateAllPrices()"
                        class="text-sm border-0 bg-transparent focus:outline-none">
                        <option value="TL">₺</option>
                        <option value="USD">$</option>
                        <option value="EUR">€</option>
                        <option value="GBP">£</option>
                    </select>
                </div>
            </div>
            <div class="mt-1 text-sm text-gray-500">
                <span x-text="formattedMainPrice"></span>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
                Para Birimi Dönüşümü
            </label>
            <div class="bg-gray-50 rounded-lg p-3 dark:bg-slate-900">
                <div class="text-sm text-gray-600 mb-2">Diğer Para Birimleri:</div>
                <div class="space-y-1 text-xs">
                    <div x-show="mainCurrency !== 'TL'" class="flex justify-between">
                        <span>₺ (TL):</span>
                        <span class="font-medium" x-text="convertedPrices.TL"></span>
                    </div>
                    <div x-show="mainCurrency !== 'USD'" class="flex justify-between">
                        <span>$ (USD):</span>
                        <span class="font-medium" x-text="convertedPrices.USD"></span>
                    </div>
                    <div x-show="mainCurrency !== 'EUR'" class="flex justify-between">
                        <span>€ (EUR):</span>
                        <span class="font-medium" x-text="convertedPrices.EUR"></span>
                    </div>
                    <div x-show="mainCurrency !== 'GBP'" class="flex justify-between">
                        <span>£ (GBP):</span>
                        <span class="font-medium" x-text="convertedPrices.GBP"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Ek Fiyat Seçenekleri --}}
    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
            Ek Fiyat Seçenekleri
        </label>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="flex items-center">
                    <input type="checkbox" x-model="showRentalPrice" class="mr-2">
                    <span class="text-sm text-gray-700 dark:text-slate-300">Kiralık Fiyatı</span>
                </label>
                <div x-show="showRentalPrice" class="mt-2">
                    <input type="text" x-model="rentalPrice" @input="formatRentalPrice()"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Aylık kiralık fiyatı">
                    <div class="mt-1 text-xs text-gray-500">
                        <span x-text="formattedRentalPrice"></span>
                    </div>
                </div>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" x-model="showDailyPrice" class="mr-2">
                    <span class="text-sm text-gray-700 dark:text-slate-300">Günlük Fiyat</span>
                </label>
                <div x-show="showDailyPrice" class="mt-2">
                    <input type="text" x-model="dailyPrice" @input="formatDailyPrice()"
                        class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        placeholder="Günlük fiyat">
                    <div class="mt-1 text-xs text-gray-500">
                        <span x-text="formattedDailyPrice"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Fiyat Geçmişi --}}
    <div x-show="priceHistory.length > 0" class="mb-6">
        <h4 class="text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Fiyat Geçmişi</h4>
        <div class="bg-gray-50 rounded-lg p-3 max-h-32 overflow-y-auto dark:bg-slate-900">
            <div class="space-y-1 text-xs">
                <template x-for="(price, index) in priceHistory" :key="index">
                    <div class="flex justify-between items-center">
                        <span x-text="price.date"></span>
                        <span class="font-medium" x-text="price.formatted"></span>
                        <button type="button" @click="restorePrice(index)" class="text-blue-600 hover:text-blue-800">
                            <span class="material-symbols-outlined text-xs">undo</span>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Fiyat Önerileri --}}
    <div x-show="suggestions.length > 0" class="mb-6">
        <h4 class="text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">Fiyat Önerileri</h4>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <div class="space-y-2">
                <template x-for="(suggestion, index) in suggestions" :key="index">
                    <div class="flex justify-between items-center">
                        <span class="text-sm" x-text="suggestion.label"></span>
                        <div class="flex items-center space-x-2">
                            <span class="font-medium" x-text="suggestion.price"></span>
                            <button type="button" @click="applySuggestion(suggestion)"
                                class="px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700">
                                Uygula
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Hata Mesajları --}}
    <div x-show="errors.length > 0" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
        <h4 class="text-sm font-medium text-red-800 mb-2">⚠ Hatalar:</h4>
        <ul class="text-sm text-red-700 space-y-1">
            <template x-for="error in errors">
                <li x-text="error"></li>
            </template>
        </ul>
    </div>

    {{-- Hidden Inputs for Form Submission --}}
    <input type="hidden" name="fiyat" x-bind:value="cleanMainPrice">
    <input type="hidden" name="para_birimi" x-bind:value="mainCurrency">
    <input type="hidden" name="kiralik_fiyat" x-bind:value="cleanRentalPrice">
    <input type="hidden" name="gunluk_fiyat" x-bind:value="cleanDailyPrice">
</div>

<script>
    function advancedPriceInput() {
        return {
            // Ana fiyat verileri
            mainPrice: '',
            mainCurrency: 'TL',
            formattedMainPrice: '',
            cleanMainPrice: 0,

            // Ek fiyat seçenekleri
            showRentalPrice: false,
            showDailyPrice: false,
            rentalPrice: '',
            dailyPrice: '',
            formattedRentalPrice: '',
            formattedDailyPrice: '',
            cleanRentalPrice: 0,
            cleanDailyPrice: 0,

            // Dönüşüm verileri
            convertedPrices: {
                TL: '',
                USD: '',
                EUR: '',
                GBP: ''
            },

            // Fiyat geçmişi
            priceHistory: [],

            // Öneriler
            suggestions: [],

            // Kontroller
            errors: [],
            isValidPrice: true,

            // Döviz kurları (örnek - gerçek uygulamada API'den alınmalı)
            exchangeRates: {
                TL: 1,
                USD: 34.5,
                EUR: 37.2,
                GBP: 43.8
            },

            init() {
                this.loadExistingPrices();
                this.updateAllPrices();
                this.generateSuggestions();
            },

            loadExistingPrices() {
                // Mevcut fiyat değerlerini yükle
                const priceInput = document.querySelector('input[name="fiyat"]');
                if (priceInput && priceInput.value) {
                    this.mainPrice = this.formatNumber(parseFloat(priceInput.value));
                    this.cleanMainPrice = parseFloat(priceInput.value);
                }

                const currencySelect = document.querySelector('select[name="para_birimi"]');
                if (currencySelect && currencySelect.value) {
                    this.mainCurrency = currencySelect.value;
                }

                const rentalInput = document.querySelector('input[name="kiralik_fiyat"]');
                if (rentalInput && rentalInput.value) {
                    this.rentalPrice = this.formatNumber(parseFloat(rentalInput.value));
                    this.cleanRentalPrice = parseFloat(rentalInput.value);
                    this.showRentalPrice = true;
                }

                const dailyInput = document.querySelector('input[name="gunluk_fiyat"]');
                if (dailyInput && dailyInput.value) {
                    this.dailyPrice = this.formatNumber(parseFloat(dailyInput.value));
                    this.cleanDailyPrice = parseFloat(dailyInput.value);
                    this.showDailyPrice = true;
                }
            },

            formatMainPrice() {
                const cleanPrice = this.cleanNumber(this.mainPrice);
                this.cleanMainPrice = cleanPrice;
                this.formattedMainPrice = this.formatNumber(cleanPrice);
                this.updateAllPrices();
                this.validatePrice();
            },

            formatRentalPrice() {
                const cleanPrice = this.cleanNumber(this.rentalPrice);
                this.cleanRentalPrice = cleanPrice;
                this.formattedRentalPrice = this.formatNumber(cleanPrice);
            },

            formatDailyPrice() {
                const cleanPrice = this.cleanNumber(this.dailyPrice);
                this.cleanDailyPrice = cleanPrice;
                this.formattedDailyPrice = this.formatNumber(cleanPrice);
            },

            updateAllPrices() {
                const mainPrice = this.cleanMainPrice;
                if (mainPrice > 0) {
                    // Ana para biriminden diğerlerine dönüştür
                    Object.keys(this.exchangeRates).forEach(currency => {
                        if (currency !== this.mainCurrency) {
                            const rate = this.exchangeRates[currency] / this.exchangeRates[this.mainCurrency];
                            const converted = mainPrice * rate;
                            this.convertedPrices[currency] = this.formatNumber(converted);
                        } else {
                            this.convertedPrices[currency] = this.formatNumber(mainPrice);
                        }
                    });
                }
            },

            generateSuggestions() {
                // Basit fiyat önerileri (gerçek uygulamada AI/analiz ile yapılmalı)
                const mainPrice = this.cleanMainPrice;
                if (mainPrice > 0) {
                    this.suggestions = [{
                            label: 'Pazarlık payı ile (%10 düşük)',
                            price: this.formatNumber(mainPrice * 0.9),
                            value: mainPrice * 0.9
                        },
                        {
                            label: 'Piyasa ortalaması',
                            price: this.formatNumber(mainPrice * 1.05),
                            value: mainPrice * 1.05
                        },
                        {
                            label: 'Premium fiyat (%15 yüksek)',
                            price: this.formatNumber(mainPrice * 1.15),
                            value: mainPrice * 1.15
                        }
                    ];
                }
            },

            applySuggestion(suggestion) {
                this.mainPrice = this.formatNumber(suggestion.value);
                this.cleanMainPrice = suggestion.value;
                this.formattedMainPrice = suggestion.price;
                this.updateAllPrices();
                this.addToHistory();
            },

            addToHistory() {
                const now = new Date();
                const historyItem = {
                    date: now.toLocaleString('tr-TR'),
                    formatted: this.formattedMainPrice + ' ' + this.mainCurrency,
                    value: this.cleanMainPrice
                };

                this.priceHistory.unshift(historyItem);

                // Maksimum 5 kayıt tut
                if (this.priceHistory.length > 5) {
                    this.priceHistory = this.priceHistory.slice(0, 5);
                }
            },

            restorePrice(index) {
                const historyItem = this.priceHistory[index];
                this.mainPrice = this.formatNumber(historyItem.value);
                this.cleanMainPrice = historyItem.value;
                this.formattedMainPrice = historyItem.formatted;
                this.updateAllPrices();
            },

            validatePrice() {
                this.errors = [];

                if (this.cleanMainPrice <= 0) {
                    this.errors.push('Ana fiyat 0\'dan büyük olmalıdır');
                }

                if (this.cleanMainPrice > 1000000000) {
                    this.errors.push('Fiyat çok yüksek görünüyor, lütfen kontrol edin');
                }

                if (this.showRentalPrice && this.cleanRentalPrice <= 0) {
                    this.errors.push('Kiralık fiyatı 0\'dan büyük olmalıdır');
                }

                if (this.showDailyPrice && this.cleanDailyPrice <= 0) {
                    this.errors.push('Günlük fiyat 0\'dan büyük olmalıdır');
                }

                this.isValidPrice = this.errors.length === 0;
            },

            formatNumber(num) {
                if (!num || isNaN(num)) return '0';
                return new Intl.NumberFormat('tr-TR').format(parseFloat(num));
            },

            cleanNumber(str) {
                if (!str) return 0;
                return parseFloat(str.toString().replace(/[^\d.,]/g, '').replace(',', '.')) || 0;
            }
        }
    }
</script>
