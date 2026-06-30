{{-- Section 8: Anahtar Yönetimi (Basit - CREATE için) --}}
@php
    // Context7: Arsa kategorisi için Anahtar Bilgileri gösterilmez
    $anaKategoriSlug = $ilan->anaKategori->slug ?? '';
    $isArsa = $anaKategoriSlug === 'arsa' || str_contains(strtolower($anaKategoriSlug ?? ''), 'arsa');
@endphp

@if (!$isArsa)
    <div class="rounded-xl bg-gray-50 p-6 shadow-lg dark:bg-slate-900">
        @if (!($wizardMode ?? false))
            <h2 class="mb-6 flex items-center text-xl font-bold text-gray-800 dark:text-slate-200">
                <span
                    class="mr-3 flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-r from-amber-500 to-amber-600 font-bold text-white shadow-lg">8</span>
                <svg class="mr-2 h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                <span class="bg-gradient-to-r from-amber-600 to-amber-700 bg-clip-text text-transparent">🔑 Anahtar
                    Bilgileri</span>
            </h2>
        @endif

        <div class="space-y-6">
            {{-- Anahtar Durumu (Basitleştirilmiş) --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="mb-6">
                    <label for="anahtar_durumu"
                        class="mb-2 block text-sm font-medium text-gray-900 dark:text-slate-100">
                        <span class="mb-2-text block text-sm font-medium text-gray-900 dark:text-slate-100">Anahtar
                            Durumu *</span>
                    </label>
                    <select name="anahtar_durumu" id="anahtar_durumu"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-gray-900 transition-all duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:focus:ring-blue-400">
                        <option value="">Seçin...</option>
                        <option value="ofiste" {{ old('anahtar_durumu') == 'ofiste' ? 'selected' : '' }}>🏢 Ofiste
                        </option>
                        <option value="sahibinde" {{ old('anahtar_durumu') == 'sahibinde' ? 'selected' : '' }}>👤
                            Sahibinde</option>
                        <option value="emniyette" {{ old('anahtar_durumu') == 'emniyette' ? 'selected' : '' }}>🔐
                            Emniyette</option>
                        <option value="noterde" {{ old('anahtar_durumu') == 'noterde' ? 'selected' : '' }}>⚖️ Noterde
                        </option>
                        <option value="diger" {{ old('anahtar_durumu') == 'diger' ? 'selected' : '' }}>📦 Diğer
                        </option>
                    </select>
                    @error('anahtar_durumu')
                        <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-6">
                    <label for="anahtar_sayisi"
                        class="mb-2 block text-sm font-medium text-gray-900 dark:text-slate-100">
                        <span class="mb-2-text block text-sm font-medium text-gray-900 dark:text-slate-100">Anahtar
                            Sayısı</span>
                    </label>
                    <input type="number" name="anahtar_sayisi" id="anahtar_sayisi"
                        value="{{ old('anahtar_sayisi', 1) }}" min="0" max="20"
                        class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-2.5 text-gray-900 transition-all duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:focus:ring-blue-400"
                        placeholder="Kaç adet anahtar?">
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Varsayılan: 1 adet
                    </p>
                </div>
            </div>

            {{-- Detaylı Bilgiler Edit'te --}}
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-info-circle mt-1 text-blue-500"></i>
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Detaylı Anahtar Yönetimi</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">
                            Anahtar teslim bilgileri, fotoğraflar ve detaylı takip için ilan düzenleme sayfasını
                            kullanabilirsiniz.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endif
