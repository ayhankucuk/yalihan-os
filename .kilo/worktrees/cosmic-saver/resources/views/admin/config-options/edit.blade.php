@extends('admin.layouts.admin')

@section('title', 'Config Seçeneği Düzenle: ' . $configOption->label)

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
                            Config Düzenle: {{ $configOption->label }}
                        </h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $configOption->option_key }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <span
                        class="px-3 py-1 rounded-full text-xs font-medium {{ $configOption->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' }}">
                        {{ $configOption->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                    </span>
                    <button type="button" onclick="document.getElementById('main-submit-btn').click()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Kaydet</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('admin.config-options.update', $configOption->id) }}" method="POST" id="config-option-form"
            class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Temel Bilgiler --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Temel Bilgiler</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Option Key --}}
                    <div>
                        <label for="option_key" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Option Key <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="option_key" id="option_key"
                            value="{{ old('option_key', $configOption->option_key) }}" required
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
                            <option value="simple"
                                {{ old('option_type', $configOption->option_type) == 'simple' ? 'selected' : '' }}>
                                Simple (Basit Array)
                            </option>
                            <option value="associative"
                                {{ old('option_type', $configOption->option_type) == 'associative' ? 'selected' : '' }}>
                                Associative (Key-Value)
                            </option>
                            <option value="object_array"
                                {{ old('option_type', $configOption->option_type) == 'object_array' ? 'selected' : '' }}>
                                Object Array (Obje Dizisi)
                            </option>
                            <option value="nested"
                                {{ old('option_type', $configOption->option_type) == 'nested' ? 'selected' : '' }}>
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
                                    {{ old('kategori_id', $configOption->kategori_id) == $kategori->id ? 'selected' : '' }}>
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
                                    {{ old('yayin_tipi_id', $configOption->yayin_tipi_id) == $yayinTipi->id ? 'selected' : '' }}>
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
                        <input type="text" name="label" id="label"
                            value="{{ old('label', $configOption->label) }}"
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
                        <input type="text" name="icon" id="icon" value="{{ old('icon', $configOption->icon) }}"
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
                            <option value="1" {{ old('aktiflik_durumu', $configOption->aktiflik_durumu) ? 'selected' : '' }}>
                                Aktif</option>
                            <option value="0" {{ !old('aktiflik_durumu', $configOption->aktiflik_durumu) ? 'selected' : '' }}>
                                Pasif</option>
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
                        <input type="number" name="display_order" id="display_order"
                            value="{{ old('display_order', $configOption->display_order) }}" min="0"
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
                        placeholder="Config seçeneği açıklaması">{{ old('description', $configOption->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Option Value --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Seçenek Değerleri</h2>
                {{-- Dinamik Form Container --}}
                <div id="option-value-container" class="space-y-4">
                    {{-- JS ile doldurulacak --}}
                    <div
                        class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-slate-800 flex items-center gap-3 dark:bg-slate-900 dark:border-slate-700">
                        <div class="animate-spin rounded-full h-5 w-5 border-2 border-blue-500 border-t-transparent">
                        </div>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Yükleniyor...</span>
                    </div>
                </div>

                {{-- Hidden Input for JSON - Değer backend'den gelirken array ise JSON'a çevirmeyin, value attribute içinde json_encode kullanın --}}
                <input type="hidden" name="option_value" id="option_value_json"
                    value="{{ is_array(old('option_value', $configOption->option_value)) ? json_encode(old('option_value', $configOption->option_value)) : old('option_value', json_encode($configOption->option_value)) }}">

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
                <button type="submit" id="main-submit-btn"
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                    Güncelle
                </button>
            </div>
        </form>
    </div>

    @push('scripts')
        <script src="{{ asset('js/admin/config-options-form-builder.js') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Mevcut veriyi yükle
                const currentOptionType = '{{ $configOption->option_type }}';
                // Null safety: JSON helper/directive null values are already handled, we still use ?? [] as extra safety
                const currentOptionValue = @json($configOption->option_value ?? []);

                // Form Builder'ı güvenli başlat
                if (typeof ConfigOptionsFormBuilder !== 'undefined') {
                    window.formBuilder = new ConfigOptionsFormBuilder('option-value-container', 'option_value_json');
                    window.formBuilder.init(currentOptionType, currentOptionValue);
                } else {
                    console.error('ConfigOptionsFormBuilder yüklenemedi!');
                }

                // Option type değiştiğinde formu yeniden oluştur
                const typeSelect = document.getElementById('option_type');
                if (typeSelect) {
                    typeSelect.addEventListener('change', function() {
                        if (window.formBuilder) {
                            window.formBuilder.renderForm(this.value);
                        }
                    });
                }

                // Form submit edilmeden önce hidden input'u güncelle
                const form = document.getElementById('config-option-form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        // Form builder varsa hidden input'u güncelle
                        if (window.formBuilder) {
                            window.formBuilder.updateHiddenInput();
                        }
                        // Standart submit işlemine izin ver (e.preventDefault YOK)
                    });
                }
            });
        </script>
    @endpush
@endsection
