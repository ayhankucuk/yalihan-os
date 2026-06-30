{{--
🎨 FİYAT YÖNETİMİ - Ultra Modern Edition
Context7: %100, Tailwind CSS ONLY
--}}

<x-admin.ilanlar.components.elegant-form-wrapper sectionId="section-price" title="Fiyat Yönetimi"
    subtitle="İlanın fiyat ve para birimi bilgilerini girin" badgeNumber="5" badgeColor="orange" :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'>
                  <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\'
                        d=\'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z\' />
                </svg>'"
    glassEffect="false">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Fiyat --}}
        <x-admin.ilanlar.components.elegant-input name="fiyat" type="number" label="Fiyat" placeholder="0"
            :value="old('fiyat', $ilan->fiyat ?? '')" :required="true" :floating="true" :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                                  <path d=\'M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z\' />
                                  <path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.51-1.31c-.562-.649-1.413-1.076-2.353-1.253V5z\' clip-rule=\'evenodd\' />
                                </svg>'"
            helpText="İlanın satış veya kiralama fiyatını girin" />

        {{-- Para Birimi --}}
        <x-admin.ilanlar.components.elegant-input name="para_birimi" type="select" label="Para Birimi"
            :required="true" :floating="true" :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                                  <path fill-rule=\'evenodd\' d=\'M10 18a8 8 0 100-16 8 8 0 000 16zM7 5a1 1 0 100 2h1a2 2 0 011.732 1H7a1 1 0 100 2h2.732A2 2 0 018 11H7a1 1 0 100 2h1a2 2 0 003.464 1H13a1 1 0 100-2h-1a2 2 0 00-1.732-1H13a1 1 0 100-2h-2.732A2 2 0 0112 7h1a1 1 0 100-2h-1a2 2 0 00-3.464-1H7z\' clip-rule=\'evenodd\' />
                                </svg>'" helpText="Fiyatın para birimini seçin">
            <option value="TRY" {{ old('para_birimi', $ilan->para_birimi ?? 'TRY') == 'TRY' ? 'selected' : '' }}>₺
                Türk Lirası (TRY)</option>
            <option value="USD" {{ old('para_birimi', $ilan->para_birimi ?? '') == 'USD' ? 'selected' : '' }}>$ Dolar
                (USD)</option>
            <option value="EUR" {{ old('para_birimi', $ilan->para_birimi ?? '') == 'EUR' ? 'selected' : '' }}>€ Euro
                (EUR)</option>
            <option value="GBP" {{ old('para_birimi', $ilan->para_birimi ?? '') == 'GBP' ? 'selected' : '' }}>£
                Sterlin (GBP)</option>
        </x-admin.ilanlar.components.elegant-input>
    </div>

    {{-- AI Price Suggestion --}}
    <div
        class="mt-6 p-5 rounded-xl
                bg-gradient-to-br from-orange-50 to-amber-50
                dark:from-orange-900/20 dark:to-amber-900/20
                border border-orange-200 dark:border-orange-800">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                </svg>
                <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                    💡 AI ile pazar araştırması yapıp fiyat önerisi alabilirsiniz
                </span>
            </div>
            <button type="button" id="ai-price-suggestion"
                class="inline-flex items-center gap-2 px-4 py-2
                           bg-gradient-to-r from-orange-600 to-amber-600
                           hover:from-orange-700 hover:to-amber-700
                           text-white rounded-lg
                           shadow-md hover:shadow-lg
                           transition-all duration-300
                           hover:scale-105
                           text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Fiyat Öner
            </button>
        </div>
    </div>
</x-admin.ilanlar.components.elegant-form-wrapper>
