@props([
    'currentLanguage' => 'tr',
    'currentCurrency' => 'TRY',
    'showLanguage' => true,
    'showCurrency' => true,
    'class' => '',
])

@php
    $languages = [
        'tr' => ['name' => 'Türkçe', 'flag' => '🇹🇷'],
        'en' => ['name' => 'English', 'flag' => '🇺🇸'],
    ];

    $currencies = [
        'TRY' => ['name' => 'Türk Lirası', 'symbol' => '₺'],
        'USD' => ['name' => 'US Dollar', 'symbol' => '$'],
        'EUR' => ['name' => 'Euro', 'symbol' => '€'],
        'GBP' => ['name' => 'British Pound', 'symbol' => '£'],
    ];
@endphp

<div class="language-currency-selector {{ $class }}" x-data="languageCurrencySelector()">
    <!-- Language Selector -->
    @if ($showLanguage)
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-orange-600 dark:text-slate-300">
                <span class="text-lg">{{ $languages[$currentLanguage]['flag'] }}</span>
                <span class="hidden sm:inline">{{ $languages[$currentLanguage]['name'] }}</span>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="open" @click.away="open = false"
                class="absolute right-0 z-50 mt-2 w-48 rounded-lg border border-gray-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                @foreach ($languages as $code => $lang)
                    <button @click="changeLanguage('{{ $code }}'); open = false"
                        class="{{ $code === $currentLanguage ? 'bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300' }} flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-slate-800">
                        <span class="text-lg">{{ $lang['flag'] }}</span>
                        <span class="font-medium">{{ $lang['name'] }}</span>
                        @if ($code === $currentLanguage)
                            <svg class="ml-auto h-4 w-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Currency Selector -->
    @if ($showCurrency)
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                class="flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 hover:text-orange-600 dark:text-slate-300">
                <span class="text-lg font-bold">{{ $currencies[$currentCurrency]['symbol'] }}</span>
                <span class="hidden sm:inline">{{ $currentCurrency }}</span>
                <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': open }" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>

            <div x-show="open" @click.away="open = false"
                class="absolute right-0 z-50 mt-2 w-48 rounded-lg border border-gray-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900"
                x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                @foreach ($currencies as $code => $currency)
                    <button @click="changeCurrency('{{ $code }}'); open = false"
                        class="{{ $code === $currentCurrency ? 'bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400' : 'text-gray-700 dark:text-gray-300' }} flex w-full items-center gap-3 px-4 py-3 text-left transition-colors hover:bg-gray-50 dark:hover:bg-slate-800">
                        <span class="text-lg font-bold">{{ $currency['symbol'] }}</span>
                        <div class="flex-1">
                            <div class="font-medium">{{ $code }}</div>
                            <div class="text-xs text-gray-500">{{ $currency['name'] }}</div>
                        </div>
                        @if ($code === $currentCurrency)
                            <svg class="h-4 w-4 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    @endif
</div>

<script>
    function languageCurrencySelector() {
        return {
            currentLanguage: '{{ $currentLanguage }}',
            currentCurrency: '{{ $currentCurrency }}',

            init() {
                // Load saved preferences
                this.loadPreferences();
            },

            changeLanguage(lang) {
                this.currentLanguage = lang;
                this.savePreferences();
                this.updatePageLanguage(lang);
                this.showNotification('Dil değiştirildi: ' + this.getLanguageName(lang));
            },

            changeCurrency(currency) {
                this.currentCurrency = currency;
                this.savePreferences();
                this.updatePageCurrency(currency);
                this.showNotification('Para birimi değiştirildi: ' + currency);
            },

            updatePageLanguage(lang) {
                // Update page content based on language
                const elements = document.querySelectorAll('[data-lang]');
                elements.forEach(el => {
                    const text = el.getAttribute(`data-lang-${lang}`);
                    if (text) {
                        el.textContent = text;
                    }
                });

                // Update meta language
                document.documentElement.lang = lang;

                // Trigger custom event
                window.dispatchEvent(new CustomEvent('languageChanged', {
                    detail: {
                        language: lang
                    }
                }));
            },

            updatePageCurrency(currency) {
                // Update price displays
                const priceElements = document.querySelectorAll('[data-price]');
                priceElements.forEach(el => {
                    const price = parseFloat(el.getAttribute('data-price'));
                    const convertedPrice = this.convertCurrency(price, currency);
                    el.textContent = this.formatPrice(convertedPrice, currency);
                });

                // Trigger custom event
                window.dispatchEvent(new CustomEvent('currencyChanged', {
                    detail: {
                        currency: currency
                    }
                }));
            },

            convertCurrency(amount, toCurrency) {
                // Simple conversion rates (in real app, this would be from API)
                const rates = {
                    'TRY': 1,
                    'USD': 0.033,
                    'EUR': 0.030,
                    'GBP': 0.026
                };

                return amount * (rates[toCurrency] || 1);
            },

            formatPrice(amount, currency) {
                const symbols = {
                    'TRY': '₺',
                    'USD': '$',
                    'EUR': '€',
                    'GBP': '£'
                };

                return symbols[currency] + amount.toLocaleString('en-US', {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                });
            },

            getLanguageName(lang) {
                const names = {
                    'tr': 'Türkçe',
                    'en': 'English'
                };
                return names[lang] || lang;
            },

            savePreferences() {
                localStorage.setItem('yalihan_language', this.currentLanguage);
                localStorage.setItem('yalihan_currency', this.currentCurrency);
            },

            loadPreferences() {
                const savedLang = localStorage.getItem('yalihan_language');
                const savedCurrency = localStorage.getItem('yalihan_currency');

                if (savedLang) this.currentLanguage = savedLang;
                if (savedCurrency) this.currentCurrency = savedCurrency;
            },

            showNotification(message) {
                // Create toast notification
                const toast = document.createElement('div');
                toast.className =
                    'fixed top-4 right-4 bg-white rounded-lg p-4 shadow-lg border-l-4 border-orange-500 z-50 transform translate-x-full transition-transform duration-300 dark:bg-slate-900';
                toast.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="text-2xl">🌐</span>
                    <span class="font-medium">${message}</span>
                </div>
            `;

                document.body.appendChild(toast);

                setTimeout(() => toast.classList.remove('translate-x-full'), 100);
                setTimeout(() => {
                    toast.classList.add('translate-x-full');
                    setTimeout(() => document.body.removeChild(toast), 300);
                }, 3000);
            }
        }
    }
</script>
