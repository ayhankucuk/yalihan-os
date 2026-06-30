{{--
🎨 ANAHTAR YÖNETİMİ - Ultra Modern Edition
Context7: %100, Tailwind CSS ONLY
--}}

<x-admin.ilanlar.components.elegant-form-wrapper sectionId="section-key" title="Anahtar Yönetimi"
    subtitle="İlanla ilgili anahtar bilgilerini girin" badgeNumber="9" badgeColor="cyan" :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'>
                  <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\'
                        d=\'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z\' />
                </svg>'"
    glassEffect="false" class="kategori-specific-section" data-show-for-categories="konut" style="display: none;">

    <div class="space-y-6">
        {{-- Anahtar Durumu --}}
        <div>
            <label class="mb-3 block text-sm font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">
                Anahtar Durumu
            </label>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                {{-- Ofiste --}}
                <label
                    class="relative flex cursor-pointer items-center justify-center rounded-xl border-2 p-4 transition-all duration-300 hover:border-gray-400 has-[:checked]:scale-105 has-[:checked]:border-cyan-500 has-[:not(:checked)]:border-gray-300 has-[:checked]:bg-cyan-50 has-[:checked]:shadow-lg has-[:checked]:shadow-cyan-500/20 dark:hover:border-gray-500 dark:has-[:not(:checked)]:border-gray-600 dark:has-[:checked]:bg-cyan-900/20">
                    <input type="radio" name="anahtar_durumu" value="ofiste"
                        {{ old('anahtar_durumu', $ilan->anahtar_durumu ?? '') == 'ofiste' ? 'checked' : '' }}
                        class="sr-only">
                    <span class="flex flex-col items-center gap-2">
                        <svg class="h-6 w-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                        <span
                            class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Ofiste</span>
                    </span>
                </label>

                {{-- Mal Sahibinde --}}
                <label
                    class="relative flex cursor-pointer items-center justify-center rounded-xl border-2 p-4 transition-all duration-300 hover:border-gray-400 has-[:checked]:scale-105 has-[:checked]:border-cyan-500 has-[:not(:checked)]:border-gray-300 has-[:checked]:bg-cyan-50 has-[:checked]:shadow-lg has-[:checked]:shadow-cyan-500/20 dark:hover:border-gray-500 dark:has-[:not(:checked)]:border-gray-600 dark:has-[:checked]:bg-cyan-900/20">
                    <input type="radio" name="anahtar_durumu" value="mal_sahibinde"
                        {{ old('anahtar_durumu', $ilan->anahtar_durumu ?? '') == 'mal_sahibinde' ? 'checked' : '' }}
                        class="sr-only">
                    <span class="flex flex-col items-center gap-2">
                        <svg class="h-6 w-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Mal
                            Sahibinde</span>
                    </span>
                </label>

                {{-- Kapıcıda --}}
                <label
                    class="relative flex cursor-pointer items-center justify-center rounded-xl border-2 p-4 transition-all duration-300 hover:border-gray-400 has-[:checked]:scale-105 has-[:checked]:border-cyan-500 has-[:not(:checked)]:border-gray-300 has-[:checked]:bg-cyan-50 has-[:checked]:shadow-lg has-[:checked]:shadow-cyan-500/20 dark:hover:border-gray-500 dark:has-[:not(:checked)]:border-gray-600 dark:has-[:checked]:bg-cyan-900/20">
                    <input type="radio" name="anahtar_durumu" value="kapicida"
                        {{ old('anahtar_durumu', $ilan->anahtar_durumu ?? '') == 'kapicida' ? 'checked' : '' }}
                        class="sr-only">
                    <span class="flex flex-col items-center gap-2">
                        <svg class="h-6 w-6 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z" />
                        </svg>
                        <span
                            class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Kapıcıda</span>
                    </span>
                </label>
            </div>
        </div>

        {{-- Anahtar Notu --}}
        <x-admin.ilanlar.components.elegant-input name="anahtar_notu" type="textarea" label="Anahtar Notu"
            placeholder="Anahtarla ilgili özel notlar, ulaşım bilgileri vb..." :value="old('anahtar_notu', $ilan->anahtar_notu ?? '')" :required="false"
            rows="4" :floating="true" :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                                  <path fill-rule=\'evenodd\' d=\'M18 13V5a2 2 0 00-2-2H4a2 2 0 00-2 2v8a2 2 0 002 2h3l3 3 3-3h3a2 2 0 002-2zM5 7a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1zm1 3a1 1 0 100 2h3a1 1 0 100-2H6z\' clip-rule=\'evenodd\' />
                                </svg>'" helpText="Anahtara ulaşmak için gereken bilgiler" />

        {{-- Info Box --}}
        <div
            class="rounded-xl border border-cyan-200 bg-gradient-to-br from-cyan-50 to-blue-50 p-5 dark:border-cyan-800 dark:from-cyan-900/20 dark:to-blue-900/20">
            <div class="flex items-start gap-3">
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-cyan-600 dark:text-cyan-400" fill="none"
                    stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                    <p class="mb-1 font-semibold">💡 Anahtar Yönetimi İpuçları:</p>
                    <ul class="list-inside list-disc space-y-1 text-xs">
                        <li>Anahtarın nerede olduğunu net belirtin</li>
                        <li>İletişim kişisi ve telefon numarası ekleyin</li>
                        <li>Görüşme saatleri varsa belirtin</li>
                        <li>Güvenlik kodu veya kapı numarası gibi detayları paylaşın</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-admin.ilanlar.components.elegant-form-wrapper>
