{{--
🎨 YAYIN DURUMU - Ultra Modern Edition
Context7: %100, Tailwind CSS ONLY
--}}

<x-admin.ilanlar.components.elegant-form-wrapper sectionId="section-yayin-durumu" title="Yayın Durumu"
    subtitle="İlanın yayın durumunu ve portal bilgilerini belirleyin" badgeNumber="10" badgeColor="red" :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'>
                  <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\'
                        d=\'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\' />
                </svg>'"
    glassEffect="true">

    {{-- Status Seçimi --}}
    <div class="mb-6">
        <label class="mb-3 block text-sm font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">
            Yayın Durumu
            <span class="text-red-500">*</span>
        </label>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            {{-- Aktif --}}
            <label
                class="relative flex cursor-pointer items-center justify-center rounded-xl border-2 p-4 transition-all duration-300 hover:border-gray-400 has-[:checked]:scale-105 has-[:checked]:border-green-500 has-[:not(:checked)]:border-gray-300 has-[:checked]:bg-green-50 has-[:checked]:shadow-lg has-[:checked]:shadow-green-500/20 dark:hover:border-gray-500 dark:has-[:not(:checked)]:border-gray-600 dark:has-[:checked]:bg-green-900/20">
                {{-- Context7: Accessor üzerinden yayın_durumu erişimi --}}
                <input type="radio" name="yayin_durumu" value="Aktif"
                    {{ old('yayin_durumu', $ilan->yayin_durumu ?? 'Taslak') == 'Aktif' ? 'checked' : '' }} required
                    class="sr-only">
                <span class="flex flex-col items-center gap-2">
                    <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Aktif</span>
                </span>
            </label>

            {{-- Taslak --}}
            <label
                class="relative flex cursor-pointer items-center justify-center rounded-xl border-2 p-4 transition-all duration-300 hover:border-gray-400 has-[:checked]:scale-105 has-[:checked]:border-yellow-500 has-[:not(:checked)]:border-gray-300 has-[:checked]:bg-yellow-50 has-[:checked]:shadow-lg has-[:checked]:shadow-yellow-500/20 dark:hover:border-gray-500 dark:has-[:not(:checked)]:border-gray-600 dark:has-[:checked]:bg-yellow-900/20">
                <input type="radio" name="yayin_durumu" value="Taslak"
                    {{ old('yayin_durumu', $ilan->yayin_durumu ?? 'Taslak') == 'Taslak' ? 'checked' : '' }} required
                    class="sr-only">
                <span class="flex flex-col items-center gap-2">
                    <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path
                            d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                    </svg>
                    <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Taslak</span>
                </span>
            </label>

            {{-- Beklemede --}}
            <label
                class="relative flex cursor-pointer items-center justify-center rounded-xl border-2 p-4 transition-all duration-300 hover:border-gray-400 has-[:checked]:scale-105 has-[:checked]:border-blue-500 has-[:not(:checked)]:border-gray-300 has-[:checked]:bg-blue-50 has-[:checked]:shadow-lg has-[:checked]:shadow-blue-500/20 dark:hover:border-gray-500 dark:has-[:not(:checked)]:border-gray-600 dark:has-[:checked]:bg-blue-900/20">
                <input type="radio" name="yayin_durumu" value="Beklemede"
                    {{ old('yayin_durumu', $ilan->yayin_durumu ?? '') == 'Beklemede' ? 'checked' : '' }} required
                    class="sr-only">
                <span class="flex flex-col items-center gap-2">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                            clip-rule="evenodd" />
                    </svg>
                    <span
                        class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Beklemede</span>
                </span>
            </label>

            {{-- Pasif --}}
            <label
                class="relative flex cursor-pointer items-center justify-center rounded-xl border-2 p-4 transition-all duration-300 hover:border-gray-400 has-[:checked]:scale-105 has-[:checked]:border-gray-500 has-[:not(:checked)]:border-gray-300 has-[:checked]:bg-gray-50 has-[:checked]:shadow-lg has-[:checked]:shadow-gray-500/20 dark:hover:border-gray-500 dark:has-[:not(:checked)]:border-gray-600 dark:has-[:checked]:bg-gray-800">
                <input type="radio" name="yayin_durumu" value="Pasif"
                    {{ old('yayin_durumu', $ilan->yayin_durumu ?? '') == 'Pasif' ? 'checked' : '' }} required
                    class="sr-only">
                <span class="flex flex-col items-center gap-2">
                    <svg class="h-6 w-6 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z"
                            clip-rule="evenodd" />
                    </svg>
                    <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Pasif</span>
                </span>
            </label>
        </div>
    </div>

    {{-- Form Actions --}}
    <div class="mt-8 border-t border-gray-200 pt-6 dark:border-slate-700 dark:border-slate-800">
        <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
            <div class="text-sm text-gray-600 dark:text-gray-400">
                💾 Form otomatik olarak kaydediliyor
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.ilanlar.index') }}"
                    class="rounded-xl border-2 border-gray-300 px-5 py-2.5 font-semibold text-gray-700 transition-all duration-300 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800">
                    İptal
                </a>

                <button type="submit" name="submit_action" value="draft"
                    class="rounded-xl bg-yellow-600 px-5 py-2.5 font-semibold text-white shadow-md transition-all duration-300 hover:scale-105 hover:bg-yellow-700 hover:shadow-lg">
                    💾 Taslak Kaydet
                </button>

                <button type="submit" name="submit_action" value="publish"
                    class="rounded-xl bg-gradient-to-r from-green-600 to-emerald-600 px-6 py-2.5 font-bold text-white shadow-lg shadow-green-500/30 transition-all duration-300 hover:scale-105 hover:from-green-700 hover:to-emerald-700 hover:shadow-xl hover:shadow-green-500/40">
                    ✨ Kaydet ve Yayınla
                </button>
            </div>
        </div>
    </div>
</x-admin.ilanlar.components.elegant-form-wrapper>
