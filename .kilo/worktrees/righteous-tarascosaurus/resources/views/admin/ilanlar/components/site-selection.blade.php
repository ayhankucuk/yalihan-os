{{-- 🎨 Section 6: Site Selection (Tailwind Modernized) --}}
@php
    // Context7: Arsa kategorisi için Site Seçimi gösterilmez
    $anaKategoriSlug = $ilan->anaKategori->slug ?? '';
    $isArsa = ($anaKategoriSlug === 'arsa' || str_contains(strtolower($anaKategoriSlug ?? ''), 'arsa'));
@endphp

@if(!$isArsa)
<div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-800 p-8 hover:shadow-2xl transition-shadow duration-300 dark:border-slate-700">
    <!-- Section Header -->
    <div class="flex items-center gap-4 mb-8 pb-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 text-white shadow-lg shadow-purple-500/50 font-bold text-lg">
            6
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Site Seçimi
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Site içindeki konum bilgileri</p>
        </div>
    </div>

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="site_adi" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                    Site Adı
                </label>
                <input type="text" 
                       name="site_adi" 
                       id="site_adi"
                       value="{{ old('site_adi', $ilan->site_adi ?? '') }}"
                       class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-purple-500 dark:focus:ring-purple-400/20 focus:border-purple-500 dark:focus:border-purple-400 transition-all duration-200"
                       placeholder="Site adı giriniz">
            </div>

            <div>
                <label for="blok_no" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                    Blok No
                </label>
                <input type="text" 
                       name="blok_no" 
                       id="blok_no"
                       value="{{ old('blok_no', $ilan->blok_no ?? '') }}"
                       class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-purple-500 dark:focus:ring-purple-400/20 focus:border-purple-500 dark:focus:border-purple-400 transition-all duration-200"
                       placeholder="Blok numarası">
            </div>

            <div>
                <label for="daire_no" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                    Daire No
                </label>
                <input type="text" 
                       name="daire_no" 
                       id="daire_no"
                       value="{{ old('daire_no', $ilan->daire_no ?? '') }}"
                       class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-purple-500 dark:focus:ring-purple-400/20 focus:border-purple-500 dark:focus:border-purple-400 transition-all duration-200"
                       placeholder="Daire numarası">
            </div>

            <div>
                <label for="site_ozellikleri" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                    Site Özellikleri
                </label>
                <textarea name="site_ozellikleri" 
                          id="site_ozellikleri"
                          rows="3"
                          class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-purple-500 dark:focus:ring-purple-400/20 focus:border-purple-500 dark:focus:border-purple-400 transition-all duration-200"
                          placeholder="Site özelliklerini giriniz (havuz, güvenlik, vs.)">{{ old('site_ozellikleri', $ilan->site_ozellikleri ?? '') }}</textarea>
            </div>
        </div>

        {{-- Bilgilendirme --}}
        <div class="bg-purple-50 dark:bg-purple-900/20 border-2 border-purple-200 dark:border-purple-800 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-purple-800 dark:text-purple-200">
                    <p class="font-semibold mb-1">Site Bilgileri:</p>
                    <p class="text-purple-700 dark:text-purple-300">İlanınız bir site içindeyse site adı, blok ve daire numarasını girebilirsiniz. Bu bilgiler ilan detay sayfasında gösterilir.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

