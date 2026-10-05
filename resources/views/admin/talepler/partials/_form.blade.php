@csrf
<div class="space-y-8">

    {{-- Hata Mesajları --}}
    @if ($errors->any())
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg" role="alert">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-semibold">Form Hataları!</p>
            </div>
            <ul class="mt-2 list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Temel Bilgiler --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl border border-blue-200 shadow-sm dark:shadow-none">
        <div class="p-6">
            <h2 class="text-xl font-bold text-blue-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                👤 Temel Bilgiler
            </h2>
            {{-- Başlık — canonical controller contract: 'baslik' is required|string|max:255 --}}
            <div class="form-field mb-6">
                <label for="baslik" class="admin-label">
                    Talep Başlığı <span class="text-red-500">*</span>
                </label>
                <input type="text" id="baslik" name="baslik"
                    class="admin-input transition-all duration-200"
                    value="{{ old('baslik', $talep->baslik ?? '') }}"
                    placeholder="Örn: 3+1 Daire Aranıyor - Merkez"
                    maxlength="255"
                    required>
                @error('baslik')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="form-field">
                    <label for="kisi_id" class="admin-label">
                        Müşteri <span class="text-red-500">*</span>
                    </label>
                    <select style="color-scheme: light dark;" id="kisi_id" name="kisi_id"
                        class="admin-input transition-all duration-200" required>
                        <option value="">Müşteri Seçin</option>
                        @foreach ($kisiler as $kisi)
                            <option value="{{ $kisi->id }}" @selected(old('kisi_id', $talep->kisi_id ?? '') == $kisi->id)>
                                {{ $kisi->tam_ad }} ({{ $kisi->telefon }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="tip" class="admin-label">
                        Talep Tipi <span class="text-red-500">*</span>
                    </label>
                    <select style="color-scheme: light dark;" id="tip" name="tip"
                        class="admin-input transition-all duration-200" required>
                        @foreach ($talepTipleri as $tipItem)
                            <option value="{{ $tipItem }}" @selected(old('tip', $talep->talep_tipi ?? '') == $tipItem)>{{ $tipItem }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="alt_kategori_id" class="admin-label">
                        Emlak Kategorisi <span class="text-red-500">*</span>
                    </label>
                    <select style="color-scheme: light dark;" id="alt_kategori_id" name="alt_kategori_id"
                        class="admin-input transition-all duration-200" required>
                        <option value="">Kategori Seçin</option>
                        @foreach ($kategoriler as $kategori)
                            <option value="{{ $kategori->id }}" @selected(old('alt_kategori_id', $talep->alt_kategori_id ?? '') == $kategori->id)>{{ $kategori->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Adres Bilgileri --}}
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200 shadow-sm dark:shadow-none">
        <div class="p-6">
            <h2 class="text-xl font-bold text-green-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                📍 Konum Bilgileri
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="form-field">
                    <label for="il_id" class="admin-label">
                        İl <span class="text-red-500">*</span>
                    </label>
                    <select style="color-scheme: light dark;" id="il_id" name="il_id"
                        x-model="form.il_id"
                        @change="form.ilce_id = ''; form.mahalle_id = ''; fetchIlceler()"
                        class="admin-input transition-all duration-200" required>
                        <option value="">İl Seçin</option>
                        @foreach ($iller as $il)
                            <option value="{{ $il->id }}" @selected(old('il_id', $talep->il_id ?? '') == $il->id)>
                                {{ $il->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field">
                    <label for="ilce_id" class="admin-label">
                        İlçe <span class="text-red-500">*</span>
                    </label>
                    <select style="color-scheme: light dark;" id="ilce_id" name="ilce_id"
                        x-model="form.ilce_id"
                        @change="form.mahalle_id = ''; fetchMahalleler()"
                        :disabled="!form.il_id"
                        class="admin-input transition-all duration-200" required>
                        <option value="">İlçe Seçin</option>
                        <template x-if="ilceler.length > 0">
                            <option value="" disabled>-- İlçe Seçin --</option>
                        </template>
                        <template x-for="ilce in ilceler" :key="ilce.id">
                            <option :value="ilce.id" x-text="ilce.name"
                                :selected="ilce.id == {{ old('ilce_id', $talep->ilce_id ?? 'null') }}">
                            </option>
                        </template>
                        @if (old('ilce_id', $talep->ilce_id ?? null))
                            <option value="{{ old('ilce_id', $talep->ilce_id) }}" selected>
                                {{ \App\Models\Ilce::find(old('ilce_id', $talep->ilce_id))?->name ?? 'Seçili İlçe' }}
                            </option>
                        @endif
                    </select>
                </div>
                <div class="form-field">
                    <label for="mahalle_id" class="admin-label">
                        Mahalle
                    </label>
                    <select style="color-scheme: light dark;" id="mahalle_id" name="mahalle_id"
                        x-model="form.mahalle_id"
                        :disabled="!form.ilce_id"
                        class="admin-input transition-all duration-200">
                        <option value="">Mahalle Seçin (Opsiyonel)</option>
                        <template x-if="mahalleler.length > 0">
                            <option value="" disabled>-- Mahalle Seçin --</option>
                        </template>
                        <template x-for="mahalle in mahalleler" :key="mahalle.id">
                            <option :value="mahalle.id" x-text="mahalle.name"
                                :selected="mahalle.id == {{ old('mahalle_id', $talep->mahalle_id ?? 'null') }}">
                            </option>
                        </template>
                        @if (old('mahalle_id', $talep->mahalle_id ?? null))
                            <option value="{{ old('mahalle_id', $talep->mahalle_id) }}" selected>
                                {{ \App\Models\Mahalle::find(old('mahalle_id', $talep->mahalle_id))?->name ?? 'Seçili Mahalle' }}
                            </option>
                        @endif
                    </select>
                </div>
            </div>

            {{-- Konum Bilgi Kartı --}}
            <div x-show="form.ilce_id" class="mt-4">
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                Seçili Konum:
                                <span x-text="selectedLocation" class="font-semibold">@if ($talep->il_id) {{ $talep->il?->name ?? '' }} @if ($talep->ilce_id), {{ $talep->ilce?->name ?? '' }} @endif @if ($talep->mahalle_id), {{ $talep->mahalle?->name ?? '' }} @endif @endif</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kriterler --}}
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-200 shadow-sm dark:shadow-none">
        <div class="p-6">
            <h2 class="text-xl font-bold text-purple-800 mb-6 flex items-center">
                <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                🎯 Emlak Kriterleri
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                {{-- Oda Sayısı --}}
                <div class="form-field">
                    <label for="min_oda_sayisi" class="admin-label">
                        Min Oda Sayısı
                    </label>
                    <input type="number" id="min_oda_sayisi" name="min_oda_sayisi"
                        x-model="form.min_oda_sayisi"
                        class="admin-input transition-all duration-200"
                        value="{{ old('min_oda_sayisi', $talep->min_oda_sayisi ?? '') }}"
                        min="1" max="10"
                        placeholder="örn: 3">
                </div>

                {{-- Max Oda Sayısı --}}
                <div class="form-field">
                    <label for="max_oda_sayisi" class="admin-label">
                        Max Oda Sayısı
                    </label>
                    <input type="number" id="max_oda_sayisi" name="max_oda_sayisi"
                        x-model="form.max_oda_sayisi"
                        class="admin-input transition-all duration-200"
                        value="{{ old('max_oda_sayisi', $talep->max_oda_sayisi ?? '') }}"
                        min="1" max="10"
                        placeholder="örn: 5">
                </div>

                {{-- Min Alan --}}
                <div class="form-field">
                    <label for="min_metrekare" class="admin-label">
                        Min Alan (m²)
                    </label>
                    <input type="number" id="min_metrekare" name="min_metrekare"
                        x-model="form.min_metrekare"
                        class="admin-input transition-all duration-200"
                        value="{{ old('min_metrekare', $talep->min_metrekare ?? '') }}"
                        min="0" max="10000"
                        placeholder="örn: 100">
                </div>

                {{-- Max Alan --}}
                <div class="form-field">
                    <label for="max_metrekare" class="admin-label">
                        Max Alan (m²)
                    </label>
                    <input type="number" id="max_metrekare" name="max_metrekare"
                        x-model="form.max_metrekare"
                        class="admin-input transition-all duration-200"
                        value="{{ old('max_metrekare', $talep->max_metrekare ?? '') }}"
                        min="0" max="10000"
                        placeholder="örn: 200">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                {{-- Min Fiyat --}}
                <div class="form-field">
                    <label for="min_fiyat" class="admin-label">
                        Min Fiyat (₺)
                    </label>
                    <input type="number" id="min_fiyat" name="min_fiyat"
                        x-model="form.min_fiyat"
                        class="admin-input transition-all duration-200"
                        value="{{ old('min_fiyat', $talep->min_fiyat ?? '') }}"
                        min="0" step="1000"
                        placeholder="örn: 1000000">
                </div>

                {{-- Max Fiyat --}}
                <div class="form-field">
                    <label for="max_fiyat" class="admin-label">
                        Max Fiyat (₺)
                    </label>
                    <input type="number" id="max_fiyat" name="max_fiyat"
                        x-model="form.max_fiyat"
                        class="admin-input transition-all duration-200"
                        value="{{ old('max_fiyat', $talep->max_fiyat ?? '') }}"
                        min="0" step="1000"
                        placeholder="örn: 3000000">
                </div>
            </div>

            {{-- Parsel Durumu --}}
            <div class="grid grid-cols-1 md:grid-cols-1 gap-6 mt-6">
                <div class="form-field">
                    <div class="flex items-center justify-between">
                        <label for="parsel_durumu" class="admin-label">
                            Parsel Durumu
                        </label>
                        <span x-show="form.parsel_durumu" class="text-xs text-green-600 dark:text-green-400">
                            ✓ Seçildi
                        </span>
                    </div>
                    <select style="color-scheme: light dark;" id="parsel_durumu" name="parsel_durumu"
                        x-model="form.parsel_durumu"
                        class="admin-input transition-all duration-200">
                        <option value="">Parsel Durumu Seçin (Opsiyonel)</option>
                        <option value="b Blok" @selected(old('parsel_durumu', $talep->parsel_durumu ?? '') == 'b Blok')>B Blok</option>
                        <option value="b+1 Blok" @selected(old('parsel_durumu', $talep->parsel_durumu ?? '') == 'b+1 Blok')>B+1 Blok</option>
                        <option value="A Blok" @selected(old('parsel_durumu', $talep->parsel_durumu ?? '') == 'A Blok')>A Blok</option>
                        <option value="A+1 Blok" @selected(old('parsel_durumu', $talep->parsel_durumu ?? '') == 'A+1 Blok')>A+1 Blok</option>
                    </select>
                </div>
            </div>

            {{-- Kriter Bilgi Kartı --}}
            <div class="mt-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg p-4">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-purple-800 dark:text-purple-300">
                            Kriter Özeti:
                            <span x-text="kriterOzeti" class="font-semibold">
                                {{ $talep->min_oda_sayisi ?? '—' }} - {{ $talep->max_oda_sayisi ?? '—' }} oda,
                                {{ $talep->min_alan ?? '—' }} - {{ $talep->max_alan ?? '—' }} m²,
                                ₺{{ $talep->min_fiyat ? number_format($talep->min_fiyat, 0, ',', '.') : '—' }} - ₺{{ $talep->max_fiyat ? number_format($talep->max_fiyat, 0, ',', '.') : '—' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Notlar --}}
    <div class="bg-gradient-to-r from-amber-50 to-orange-50 rounded-xl border border-amber-200 shadow-sm dark:shadow-none">
        <div class="p-6">
            <h2 class="text-xl font-bold text-amber-800 mb-4 flex items-center">
                <svg class="w-6 h-6 mr-3 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                📝 Notlar
            </h2>
            <textarea id="notlar" name="notlar"
                x-model="form.notlar"
                class="admin-input transition-all duration-200"
                rows="4"
                placeholder="Müşteri ile ilgili özel notlar, tercihler veya detaylar...">{{ old('notlar', $talep->notlar ?? '') }}</textarea>
        </div>
    </div>

    {{-- Danışman Atama --}}
    @can('assign-talep')
        <div class="bg-gradient-to-r from-gray-50 to-slate-50 rounded-xl border border-gray-200 shadow-sm dark:shadow-none">
            <div class="p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <svg class="w-6 h-6 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Danışman Atama
                </h2>
                <div class="form-field">
                    <label for="danisman_id" class="admin-label">
                        Sorumlu Danışman
                    </label>
                    <select style="color-scheme: light dark;" id="danisman_id" name="danisman_id"
                        class="admin-input transition-all duration-200">
                        <option value="">Danışman Seçin</option>
                        @foreach ($danismanlar as $danisman)
                            <option value="{{ $danisman->id }}"
                                @selected(old('danisman_id', $talep->danisman_id ?? '') == $danisman->id)>
                                {{ $danisman->name }} ({{ $danisman->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    @endcan

    {{-- Gönderim --}}
    <div class="flex justify-end gap-4">
        <a href="{{ route('admin.talepler.index') }}"
            class="inline-flex items-center justify-center gap-2 px-6 py-3
                  bg-gray-200 text-gray-700 font-medium rounded-lg shadow-sm
                  hover:bg-gray-300 hover:scale-105 hover:shadow-md
                  active:scale-95
                  focus:ring-2 focus:ring-gray-400 focus:ring-offset-2
                  transition-all duration-200 ease-in-out
                  dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M6 18L18 6M6 6l12 12" />
            </svg>
            İptal
        </a>
        <button type="submit"
            class="inline-flex items-center justify-center gap-2 px-6 py-3
                   bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800
                   text-white font-semibold rounded-lg shadow-md hover:shadow-lg
                   focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                   transition-all duration-200 transform hover:scale-105 active:scale-95">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M5 13l4 4L19 7" />
            </svg>
            <span id="talep-form-submit-text">{{ $submitText ?? 'Kaydet' }}</span>
        </button>
    </div>
</div>

@push('styles')
    <style>
        /* Form field standards */
        .form-field {
            @apply space-y-2;
        }

        .admin-label {
            @apply block text-sm font-medium text-gray-900 dark:text-white mb-1;
        }

        .admin-input,
        .admin-input,
        .admin-input {
            @apply w-full px-4 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg;
            @apply bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white;
            @apply focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-900;
            @apply focus:outline-none transition-all duration-200;
            @apply placeholder-gray-500 dark:placeholder-gray-400 resize-vertical;
        }

        .admin-input:hover {
            @apply border-gray-400 dark:border-gray-500;
        }

        .admin-input:disabled {
            @apply bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 cursor-not-allowed;
        }

        /* Button standards */
        .inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2.inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg {
            @apply inline-flex items-center px-6 py-3;
            @apply bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800;
            @apply text-white font-semibold rounded-lg shadow-md hover:shadow-lg;
            @apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
            @apply transition-all duration-200 transform hover:scale-105 active:scale-95;
        }

        .inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2.inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 transition-all duration-200 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700 {
            @apply inline-flex items-center px-6 py-2.5;
            @apply bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800;
            @apply text-white font-semibold rounded-lg shadow-md hover:shadow-lg;
            @apply focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
            @apply transition-all duration-200 transform hover:scale-105 active:scale-95;
        }

        /* Touch Target Optimization */
        .touch-target-optimized {
            @apply min-h-[44px] min-w-[44px];
        }
    </style>
@endpush

@push('scripts')
    <!-- Legacy Select2 Manager -->
    <script src="{{ asset('js/admin/select2-legacy-manager.js') }}"></script>

    <script>
        // LEGACY_SELECT2 - 2025-01-30 - Modern form integration pending
        $(document).ready(function() {
            // Legacy mode enable et
            document.body.classList.add('legacy-select2');

            // Select2 Legacy Manager otomatik başlatılacak
            console.log('🔧 Legacy Select2 form yüklendi - Legacy Manager enable');
        });

        // Eski addressSelector kodu kaldırıldı - Artık unified component kullanılıyor
    </script>
@endpush
