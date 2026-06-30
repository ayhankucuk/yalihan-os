@extends('admin.layouts.admin')

@section('title', 'Özellik Düzenle')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-8">
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('admin.property_types.index') }}"
                            class="text-gray-700 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 dark:text-slate-300">
                            Property Type Manager
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <a href="{{ route('admin.ups.features.index') }}"
                                class="ml-1 text-gray-700 dark:text-slate-200 hover:text-blue-600 dark:text-slate-300">Özellikler</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-gray-500 dark:text-gray-400">{{ $feature->name }} Düzenle</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3 dark:text-slate-100">
                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Özellik Düzenle: {{ $feature->name }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                "{{ $feature->slug }}" özelliğini güncelleyin
            </p>
        </div>

        <!-- Form -->
        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <form action="{{ route('ups.features.update', $feature->id) }}" method="POST" class="p-6">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="mb-6 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <div>
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Lütfen hataları düzeltin:
                                </h3>
                                <ul class="mt-2 text-sm text-red-700 dark:text-red-300 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Özellik Adı <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name', $feature->name) }}" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100"
                            placeholder="Örn: Oda Sayısı">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Slug (Readonly in Edit) -->
                    <div>
                        <label for="slug" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Slug (Değiştirilemez)
                        </label>
                        <input type="text" id="slug" value="{{ $feature->slug }}" disabled
                            class="w-full px-4 py-2.5 border border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 text-gray-500 dark:text-gray-400 rounded-lg cursor-not-allowed dark:border-slate-700">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Slug değeri veri bütünlüğü için düzenlenemez.
                        </p>
                    </div>

                    <!-- Type -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Tip <span class="text-red-500">*</span>
                        </label>
                        <select id="type" name="type" required
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100"
                            style="color-scheme: light dark;">
                            <option value="">Seçin</option>
                            @foreach(['text', 'number', 'boolean', 'date', 'select', 'multiselect'] as $type)
                                <option value="{{ $type }}" {{ old('type', $feature->type) === $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Unit -->
                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Birim
                        </label>
                        <input type="text" id="unit" name="unit" value="{{ old('unit', $feature->unit) }}"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100"
                            placeholder="Örn: m², TL, adet">
                        @error('unit')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Options (for select/multiselect) -->
                    <div id="options-container" class="{{ in_array($feature->type, ['select', 'multiselect']) ? '' : 'hidden' }}">
                        <label for="options_text" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Seçenekler (Her satıra bir seçenek)
                        </label>
                        <textarea id="options_text" name="options_text" rows="4"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100"
                            placeholder="Seçenek 1&#10;Seçenek 2&#10;Seçenek 3">{{ old('options_text', is_array($feature->options) ? implode("\n", $feature->options) : '') }}</textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Select veya Multiselect tipi için her satıra bir seçenek yazın
                        </p>
                    </div>
                </div>

                <!-- Description -->
                <div class="mt-6">
                    <label for="description" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                        Açıklama
                    </label>
                    <textarea id="description" name="description" rows="3"
                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100"
                        placeholder="Özellik hakkında açıklama...">{{ old('description', $feature->description) }}</textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Checkboxes -->
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" name="is_required" value="1"
                            {{ old('is_required', $feature->is_required) ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring-2 focus:ring-blue-200 focus:ring-opacity-50 transition-all duration-200 cursor-pointer group-hover:border-blue-400 dark:shadow-none">
                        <span
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200 dark:text-slate-100">
                            Zorunlu Alan
                        </span>
                    </label>

                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" name="is_filterable" value="1"
                            {{ old('is_filterable', $feature->is_filterable) ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring-2 focus:ring-blue-200 focus:ring-opacity-50 transition-all duration-200 cursor-pointer group-hover:border-blue-400 dark:shadow-none">
                        <span
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200 dark:text-slate-100">
                            Filtrelenebilir
                        </span>
                    </label>

                    <label class="flex items-center cursor-pointer group">
                        <input type="checkbox" name="is_searchable" value="1"
                            {{ old('is_searchable', $feature->is_searchable) ? 'checked' : '' }}
                            class="w-4 h-4 rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring-2 focus:ring-blue-200 focus:ring-opacity-50 transition-all duration-200 cursor-pointer group-hover:border-blue-400 dark:shadow-none">
                        <span
                            class="ml-2 text-sm font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200 dark:text-slate-100">
                            Aranabilir
                        </span>
                    </label>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex items-center justify-end gap-3 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <a href="{{ route('admin.ups.features.index') }}"
                        class="px-6 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-all duration-200 dark:text-slate-100">
                        İptal
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105 active:scale-95 dark:shadow-none">
                        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Show/hide options field based on type
        document.getElementById('type').addEventListener('change', function(e) {
            const optionsContainer = document.getElementById('options-container');
            if (e.target.value === 'select' || e.target.value === 'multiselect') {
                optionsContainer.classList.remove('hidden');
            } else {
                optionsContainer.classList.add('hidden');
            }
        });

        // Convert options textarea to JSON array on submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const type = document.getElementById('type').value;
            const optionsInput = document.getElementById('options_text');

            if ((type === 'select' || type === 'multiselect') && optionsInput.value) {
                const lines = optionsInput.value.split('\n').filter(line => line.trim());
                if (lines.length > 0) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'options';
                    hiddenInput.value = JSON.stringify(lines);
                    this.appendChild(hiddenInput);
                }
            }
        });
    </script>
@endsection
