@props([
    'locales' => config('localization.supported_locales', []),
    'currentLocale' => app()->getLocale(),
    'currencies' => config('currency.supported', []),
    'currentCurrency' => session('currency', config('currency.default', 'TRY')),
])

<div style="background:linear-gradient(90deg,#0A1628 0%,#0d2044 60%,#0A1628 100%);position:fixed;top:0;left:0;right:0;z-index:60;">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2 flex items-center justify-between text-white">
        <div class="flex items-center gap-2 text-sm">
            <span class="material-symbols-outlined" style="font-size:16px">language</span>
            <span class="font-medium" style="color:#C9A84C">Global Portfolio</span>
        </div>

        <div class="flex items-center gap-3 text-sm" data-preference-switcher>
            <div class="flex items-center gap-1">
                <span class="uppercase opacity-75">Dil</span>
                <div class="inline-flex rounded-full bg-white/10 p-1 dark:bg-slate-900/10 dark:bg-slate-800/40" role="group" aria-label="Dil tercihleri">
                    @foreach($locales as $code => $locale)
                        @php
                            $isActive = $currentLocale === $code;
                        @endphp
                        <button
                            type="button"
                            data-preference-locale="{{ $code }}"
                            class="px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80 {{ $isActive ? 'shadow-lg' : 'hover:bg-white/20 text-white/90' }}" style="{{ $isActive ? 'background:#C9A84C;color:#0A1628;' : '' }}"
                            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
                        >
                            {{ strtoupper($code) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex items-center gap-1">
                <span class="uppercase opacity-75">Para Birimi</span>
                <div class="inline-flex rounded-full bg-white/10 p-1 dark:bg-slate-900/10 dark:bg-slate-800/40" role="group" aria-label="Para birimi tercihleri">
                    @foreach($currencies as $code => $currency)
                        @php
                            $isActiveCurrency = strtoupper($currentCurrency) === strtoupper($code);
                        @endphp
                        <button
                            type="button"
                            data-preference-currency="{{ strtoupper($code) }}"
                            class="px-3 py-1 rounded-full text-xs font-semibold transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80 {{ $isActiveCurrency ? 'shadow-lg' : 'hover:bg-white/20 text-white/90' }}" style="{{ $isActiveCurrency ? 'background:#C9A84C;color:#0A1628;' : '' }}"
                            aria-pressed="{{ $isActiveCurrency ? 'true' : 'false' }}"
                        >
                            {{ strtoupper($code) }}
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="sm:hidden flex items-center gap-2">
                <label for="mobile-currency-switcher" class="uppercase opacity-75 text-xs">Para Birimi</label>
                <div class="relative">
                    <select id="mobile-currency-switcher" data-preference-currency-select class="appearance-none pl-3 pr-8 py-1.5 rounded-full text-xs font-semibold bg-white/90 text-blue-700 dark:text-blue-900 shadow focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80 dark:shadow-none dark:bg-slate-900/90">
                        @foreach($currencies as $code => $currency)
                            <option value="{{ strtoupper($code) }}" @selected(strtoupper($currentCurrency) === strtoupper($code))>
                                {{ strtoupper($code) }}
                            </option>
                        @endforeach
                    </select>
                    <span class="material-symbols-outlined absolute right-2 top-1/2 -translate-y-1/2 text-blue-600" style="font-size:12px">expand_more</span>
                </div>
            </div>
        </div>
    </div>
</div>

