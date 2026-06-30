@extends('admin.layouts.admin')

@section('title', 'Yeni Config Seçeneği Ekle')

@section('content')
    <div class="space-y-6">
        {{-- Header --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.config-options.index') }}"
                        class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                            Yeni Config Seçeneği Ekle
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Kategori ve Yayın Tipi bazlı config seçeneği oluşturun
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('admin.config-options.store') }}" method="POST" id="config-option-form" class="space-y-6">
            @csrf

            {{-- Temel Bilgiler --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Temel Bilgiler</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Option Key --}}
                    <div>
                        <label for="option_key" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Option Key <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="option_key" id="option_key" value="{{ old('option_key') }}" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="örn: oda_sayisi_options">
                        @error('option_key')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Option Type --}}
                    <div>
                        <label for="option_type" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Option Type <span class="text-red-500">*</span>
                        </label>
                        <select name="option_type" id="option_type" required
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">Seçin...</option>
                            <option value="simple" {{ old('option_type') == 'simple' ? 'selected' : '' }}>
                                Simple (Basit Array)
                            </option>
                            <option value="associative" {{ old('option_type') == 'associative' ? 'selected' : '' }}>
                                Associative (Key-Value)
                            </option>
                            <option value="object_array" {{ old('option_type') == 'object_array' ? 'selected' : '' }}>
                                Object Array (Obje Dizisi)
                            </option>
                            <option value="nested" {{ old('option_type') == 'nested' ? 'selected' : '' }}>
                                Nested (İç İçe Yapı)
                            </option>
                        </select>
                        @error('option_type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Kategori --}}
                    <div>
                        <label for="kategori_id" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Kategori
                        </label>
                        <select name="kategori_id" id="kategori_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">Genel (Tüm Kategoriler)</option>
                            @foreach ($kategoriler as $kategori)
                                <option value="{{ $kategori->id }}"
                                    {{ old('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                    {{ $kategori->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('kategori_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Yayın Tipi --}}
                    <div>
                        <label for="yayin_tipi_id" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Yayın Tipi
                        </label>
                        <select name="junction_id" id="junction_id"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="">Tüm Yayın Tipleri</option>
                            @foreach ($yayinTipleri as $yayinTipi)
                                <option value="{{ $yayinTipi->id }}"
                                    {{ old('yayin_tipi_id') == $yayinTipi->id ? 'selected' : '' }}>
                                    {{ $yayinTipi->kategori->name ?? '' }} - {{ $yayinTipi->yayin_tipi }}
                                </option>
                            @endforeach
                        </select>
                        @error('yayin_tipi_id')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Label --}}
                    <div>
                        <label for="label" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Label
                        </label>
                        <input type="text" name="label" id="label" value="{{ old('label') }}"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="Admin panelinde görünecek isim">
                        @error('label')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Icon --}}
                    <div>
                        <label for="icon" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Icon
                        </label>
                        <input type="text" name="icon" id="icon" value="{{ old('icon') }}"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="Emoji veya FontAwesome icon">
                        @error('icon')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Aktiflik Durumu --}}
                    <div>
                        <label for="aktiflik_durumu" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Aktiflik Durumu
                        </label>
                        <select name="aktiflik_durumu" id="aktiflik_durumu"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                            <option value="1" {{ old('aktiflik_durumu', '1') == '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ old('aktiflik_durumu') == '0' ? 'selected' : '' }}>Pasif</option>
                        </select>
                        @error('aktiflik_durumu')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Display Order --}}
                    <div>
                        <label for="display_order" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Sıralama
                        </label>
                        <input type="number" name="display_order" id="display_order" value="{{ old('display_order', 0) }}"
                            min="0"
                            class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                        @error('display_order')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Description --}}
                <div class="mt-6">
                    <label for="description" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Açıklama
                    </label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                        placeholder="Config seçeneği açıklaması">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Option Value --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Seçenek Değerleri</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Önce Option Type seçin, ardından uygun formatta veri girin. JSON formatı otomatik olarak
                    oluşturulacaktır.
                </p>

                {{-- Dinamik Form Container --}}
                <div id="option-value-container" class="space-y-4">
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            ⚠️ Lütfen önce Option Type seçin
                        </p>
                    </div>
                </div>

                {{-- Hidden Input for JSON --}}
                <input type="hidden" name="option_value" id="option_value_json" value="[]">

                @error('option_value')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('admin.config-options.index') }}"
                    class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-200">
                    İptal
                </a>
                <button type="submit"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                    Kaydet
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script src="{{ asset('js/admin/config-options-form-builder.js') }}"></script>
        <script>
            window.formBuilder = new ConfigOptionsFormBuilder('option-value-container', 'option_value_json');

            // Option type değiştiğinde formu oluştur
            document.getElementById('option_type').addEventListener('change', function() {
                if (this.value) {
                    window.formBuilder.renderForm(this.value);
                } else {
                    document.getElementById('option-value-container').innerHTML =
                        '<div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg"><p class="text-sm text-blue-800 dark:text-blue-200">⚠️ Lütfen önce Option Type seçin</p></div>';
                }
            });

            // Form submit edilmeden önce hidden input'u güncelle
            document.getElementById('config-option-form').addEventListener('submit', function(e) {
                if (window.formBuilder) {
                    window.formBuilder.updateHiddenInput();
                }
            });
        </script>
    @endpush
@endsection
