{{-- 🎨 Section 3: Type-based Fields (Tailwind Modernized) --}}
<div class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 rounded-2xl shadow-xl border border-gray-200 dark:border-slate-800 p-8 hover:shadow-2xl transition-shadow duration-300 dark:border-slate-700">
    <!-- Section Header -->
    <div class="flex items-center gap-4 mb-8 pb-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-lg shadow-blue-500/50 font-bold text-lg">
            3
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Tip Bazlı Alanlar
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">İlan tipine özel bilgiler</p>
        </div>
    </div>

    <div class="space-y-6">
        @php
            $anaKategoriSlug = $ilan->anaKategori->slug ?? '';
            $altKategoriSlug = $ilan->altKategori->slug ?? '';
        @endphp

        {{-- Arsa Alanları --}}
        @if($anaKategoriSlug === 'arsa' || str_contains($altKategoriSlug ?? '', 'arsa'))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label for="ada_no" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Ada No
                    </label>
                    <input type="text" 
                           name="ada_no" 
                           id="ada_no"
                           value="{{ old('ada_no', $ilan->ada_no) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="parsel_no" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Parsel No
                    </label>
                    <input type="text" 
                           name="parsel_no" 
                           id="parsel_no"
                           value="{{ old('parsel_no', $ilan->parsel_no) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="imar_statusu" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        İmar Durumu
                    </label>
                    <select name="imar_statusu" 
                            id="imar_statusu"
                            class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                        <option value="">Seçiniz...</option>
                        <option value="Konut İmarlı" {{ old('imar_statusu', $ilan->imar_statusu) === 'Konut İmarlı' ? 'selected' : '' }}>Konut İmarlı</option>
                        <option value="Turizm İmarlı" {{ old('imar_statusu', $ilan->imar_statusu) === 'Turizm İmarlı' ? 'selected' : '' }}>Turizm İmarlı</option>
                        <option value="Ticari İmarlı" {{ old('imar_statusu', $ilan->imar_statusu) === 'Ticari İmarlı' ? 'selected' : '' }}>Ticari İmarlı</option>
                        <option value="İmarsız" {{ old('imar_statusu', $ilan->imar_statusu) === 'İmarsız' ? 'selected' : '' }}>İmarsız</option>
                    </select>
                </div>

                <div>
                    <label for="kaks" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        KAKS
                    </label>
                    <input type="number" 
                           name="kaks" 
                           id="kaks"
                           step="0.01"
                           value="{{ old('kaks', $ilan->kaks) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="taks" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        TAKS
                    </label>
                    <input type="number" 
                           name="taks" 
                           id="taks"
                           step="0.01"
                           value="{{ old('taks', $ilan->taks) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="gabari" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Gabari (m)
                    </label>
                    <input type="number" 
                           name="gabari" 
                           id="gabari"
                           step="0.1"
                           value="{{ old('gabari', $ilan->gabari) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>
            </div>
        @endif

        {{-- Konut Alanları --}}
        @if($anaKategoriSlug === 'konut' || str_contains($altKategoriSlug ?? '', 'villa') || str_contains($altKategoriSlug ?? '', 'daire'))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label for="oda_sayisi" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Oda Sayısı
                    </label>
                    <input type="number" 
                           name="oda_sayisi" 
                           id="oda_sayisi"
                           value="{{ old('oda_sayisi', $ilan->oda_sayisi) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="banyo_sayisi" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Banyo Sayısı
                    </label>
                    <input type="number" 
                           name="banyo_sayisi" 
                           id="banyo_sayisi"
                           value="{{ old('banyo_sayisi', $ilan->banyo_sayisi) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="bina_yasi" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Bina Yaşı
                    </label>
                    <input type="number" 
                           name="bina_yasi" 
                           id="bina_yasi"
                           value="{{ old('bina_yasi', $ilan->bina_yasi) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="kat" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Bulunduğu Kat
                    </label>
                    <input type="number" 
                           name="kat" 
                           id="kat"
                           value="{{ old('kat', $ilan->kat) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="toplam_kat" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Toplam Kat
                    </label>
                    <input type="number" 
                           name="toplam_kat" 
                           id="toplam_kat"
                           value="{{ old('toplam_kat', $ilan->toplam_kat) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="isitma" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Isıtma Tipi
                    </label>
                    <input type="text" 
                           name="isitma" 
                           id="isitma"
                           value="{{ old('isitma', $ilan->isitma) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>
            </div>
        @endif

        {{-- Yazlık Alanları --}}
        @if($anaKategoriSlug === 'yazlik' || str_contains($altKategoriSlug ?? '', 'yazlik'))
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label for="gunluk_fiyat" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Günlük Fiyat
                    </label>
                    <input type="number" 
                           name="gunluk_fiyat" 
                           id="gunluk_fiyat"
                           step="0.01"
                           value="{{ old('gunluk_fiyat', $ilan->gunluk_fiyat) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="min_konaklama" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Minimum Konaklama (Gün)
                    </label>
                    <input type="number" 
                           name="min_konaklama" 
                           id="min_konaklama"
                           value="{{ old('min_konaklama', $ilan->min_konaklama) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="max_misafir" class="block text-sm font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Maksimum Misafir
                    </label>
                    <input type="number" 
                           name="max_misafir" 
                           id="max_misafir"
                           value="{{ old('max_misafir', $ilan->max_misafir) }}"
                           class="w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-4 focus:ring-blue-500 dark:focus:ring-blue-400/20 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200">
                </div>

                <div>
                    <label for="havuz" class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" 
                               name="havuz" 
                               id="havuz"
                               value="1"
                               {{ old('havuz', $ilan->havuz) ? 'checked' : '' }}
                               class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Havuz Var</span>
                    </label>
                </div>
            </div>
        @endif

        {{-- Genel Mesaj --}}
        @if(!in_array($anaKategoriSlug, ['arsa', 'konut', 'yazlik']))
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm">Bu kategori için özel alanlar bulunmamaktadır.</p>
            </div>
        @endif
    </div>
</div>

