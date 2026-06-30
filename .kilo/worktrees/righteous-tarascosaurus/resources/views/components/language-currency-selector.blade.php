{{-- ========================================
     LANGUAGE & CURRENCY SELECTOR
     EmlakJet mantığına uygun dil ve para birimi seçimi
     ======================================== --}}

<div class="flex items-center space-x-4" x-data="languageCurrencySelector()">
    {{-- Language Selector --}}
    <div class="relative">
        <button @click="toggleLanguageMenu()" type="button"
            class="flex items-center space-x-2 text-sm text-gray-700 hover:text-blue-600 transition-colors dark:text-slate-300">
            <span x-text="currentLanguage.flag"></span>
            <span x-text="currentLanguage.name"></span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        {{-- Language Dropdown --}}
        <div x-show="showLanguageMenu" @click.away="showLanguageMenu = false"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50 dark:bg-slate-900 dark:border-slate-700">
            <div class="py-1">
                <template x-for="lang in languages" :key="lang.code">
                    <button @click="selectLanguage(lang)"
                        class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 flex items-center space-x-3"
                        :class="{ 'bg-blue-50 text-blue-600': currentLanguage.code === lang.code }">
                        <span x-text="lang.flag"></span>
                        <span x-text="lang.name"></span>
                        <span x-show="currentLanguage.code === lang.code" class="text-blue-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>
                </template>
            </div>
        </div>
    </div>

    {{-- Currency Selector --}}
    <div class="relative">
        <button @click="toggleCurrencyMenu()" type="button"
            class="flex items-center space-x-2 text-sm text-gray-700 hover:text-blue-600 transition-colors dark:text-slate-300">
            <span x-text="currentCurrency.symbol"></span>
            <span x-text="currentCurrency.code"></span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        {{-- Currency Dropdown --}}
        <div x-show="showCurrencyMenu" @click.away="showCurrencyMenu = false"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-50 dark:bg-slate-900 dark:border-slate-700">
            <div class="py-1">
                <template x-for="currency in currencies" :key="currency.code">
                    <button @click="selectCurrency(currency)"
                        class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 flex items-center justify-between"
                        :class="{ 'bg-blue-50 text-blue-600': currentCurrency.code === currency.code }">
                        <div class="flex items-center space-x-3">
                            <span x-text="currency.symbol"></span>
                            <span x-text="currency.name"></span>
                        </div>
                        <span x-show="currentCurrency.code === currency.code" class="text-blue-600">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>

<script>
    function languageCurrencySelector() {
        return {
            showLanguageMenu: false,
            showCurrencyMenu: false,
            currentLanguage: {
                code: 'tr',
                name: 'Türkçe',
                flag: '🇹🇷'
            },
            currentCurrency: {
                code: 'TRY',
                name: 'Türk Lirası',
                symbol: '₺'
            },

            languages: [{
                    code: 'tr',
                    name: 'Türkçe',
                    flag: '🇹🇷'
                },
                {
                    code: 'en',
                    name: 'English',
                    flag: '🇬🇧'
                },
                {
                    code: 'de',
                    name: 'Deutsch',
                    flag: '🇩🇪'
                },
                {
                    code: 'ru',
                    name: 'Русский',
                    flag: '🇷🇺'
                }
            ],

            currencies: [{
                    code: 'TRY',
                    name: 'Türk Lirası',
                    symbol: '₺'
                },
                {
                    code: 'USD',
                    name: 'US Dollar',
                    symbol: '$'
                },
                {
                    code: 'EUR',
                    name: 'Euro',
                    symbol: '€'
                },
                {
                    code: 'GBP',
                    name: 'British Pound',
                    symbol: '£'
                }
            ],

            toggleLanguageMenu() {
                this.showLanguageMenu = !this.showLanguageMenu;
                this.showCurrencyMenu = false;
            },

            toggleCurrencyMenu() {
                this.showCurrencyMenu = !this.showCurrencyMenu;
                this.showLanguageMenu = false;
            },

            selectLanguage(lang) {
                this.currentLanguage = lang;
                this.showLanguageMenu = false;
                this.changeLanguage(lang.code);
            },

            selectCurrency(currency) {
                this.currentCurrency = currency;
                this.showCurrencyMenu = false;
                this.changeCurrency(currency.code);
            },

            changeLanguage(langCode) {
                // Dil değişikliği için API çağrısı
                fetch('/api/change-language', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            language: langCode
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Sayfayı yenile veya içeriği güncelle
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Dil değişikliği hatası:', error);
                    });
            },

            changeCurrency(currencyCode) {
                // Para birimi değişikliği için API çağrısı
                fetch('/api/change-currency', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            currency: currencyCode
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Fiyatları yeni para biriminde güncelle
                            this.updatePrices(currencyCode);
                        }
                    })
                    .catch(error => {
                        console.error('Para birimi değişikliği hatası:', error);
                    });
            },

            updatePrices(currencyCode) {
                // Sayfadaki tüm fiyatları yeni para biriminde güncelle
                const priceElements = document.querySelectorAll('[data-price]');
                priceElements.forEach(element => {
                    const originalPrice = element.getAttribute('data-price');
                    const convertedPrice = this.convertPrice(originalPrice, currencyCode);
                    element.textContent = convertedPrice;
                });
            },

            convertPrice(price, targetCurrency) {
                // Basit dönüşüm (gerçek uygulamada API'den güncel kurlar alınır)
                const rates = {
                    'TRY': 1,
                    'USD': 0.037,
                    'EUR': 0.034,
                    'GBP': 0.029
                };

                const converted = parseFloat(price) * rates[targetCurrency];
                return new Intl.NumberFormat('tr-TR', {
                    style: 'currency',
                    currency: targetCurrency
                }).format(converted);
            },

            init() {
                // LocalStorage'dan kayıtlı seçimleri yükle
                const savedLang = localStorage.getItem('selectedLanguage');
                const savedCurrency = localStorage.getItem('selectedCurrency');

                if (savedLang) {
                    const lang = this.languages.find(l => l.code === savedLang);
                    if (lang) this.currentLanguage = lang;
                }

                if (savedCurrency) {
                    const currency = this.currencies.find(c => c.code === savedCurrency);
                    if (currency) this.currentCurrency = currency;
                }
            }
        }
    }
</script>
