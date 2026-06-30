@extends('admin.layouts.admin')

@section('title', 'Özellik Düzenle')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Success Message -->
        @if (session('success'))
            <div
                class="bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 text-green-800 dark:text-green-300 p-4 mb-6 rounded-r-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        <!-- Modern Header with Gradient -->
        <div class="bg-gradient-to-r from-blue-600 via-blue-500 to-purple-600 rounded-xl p-6 mb-6 text-white shadow-lg">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-white dark:bg-slate-900 backdrop-blur-lg rounded-lg p-3 shadow-md border border-white dark:border-slate-800 dark:shadow-none">
                        <span class="text-2xl text-white dark:text-white">✏️</span>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold mb-0.5 text-white dark:text-white">Özellik Düzenle</h1>
                        <p class="text-blue-100 dark:text-blue-100 text-xs">{{ $ozellik->name }} özelliğini düzenleyin</p>
                    </div>
                </div>
                <a href="{{ route('admin.ozellikler.index') }}"
                    class="bg-blue-500 dark:bg-blue-600 hover:bg-blue-600 dark:hover:bg-blue-700 backdrop-blur-lg text-white dark:text-white px-4 py-2.5 rounded-lg transition-all duration-200 flex items-center gap-2 shadow-md dark:shadow-none border border-blue-400 dark:border-blue-500">
                    <svg class="w-4 h-4 text-white dark:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    <span class="font-medium text-sm text-white dark:text-white">Geri Dön</span>
                </a>
            </div>
        </div>

        <form action="{{ route('admin.ozellikler.update', $ozellik) }}" method="POST" x-data="{
            isSubmitting: false,
            aktif_mi: {{ $ozellik->aktiflik_durumu ?? $ozellik->aktif_mi ? 'true' : 'false' }},
            veriTipi: '{{ $ozellik->type ?? 'text' }}',
            selectedCategory: '{{ old('feature_category_id', $ozellik->feature_category_id) }}',
            sira: '{{ old('display_order', $ozellik->display_order ?? '') }}',
            showOptions: ['select', 'checkbox', 'radio'].includes('{{ $ozellik->type ?? 'text' }}'),
            options: @json(old('field_options', $ozellik->field_options ?? [])) || [],
            getCategoryName() {
                const select = document.getElementById('feature_category_id');
                const option = select ? select.options[select.selectedIndex] : null;
                return option && option.value ? option.text : 'Kategorisiz';
            }
        }"
            @submit.prevent="if(!isSubmitting){ isSubmitting=true; $el.submit(); }"
            x-effect="showOptions = ['select','checkbox','radio'].includes(veriTipi)"
            x-init="suggestCategory = function() {
                const nameInput = document.getElementById('name');
                const name = nameInput.value;
                if (!name) {
                    alert('Lütfen önce bir özellik adı girin.');
                    nameInput.focus();
                    return;
                }

                this.aiSuggesting = true;
                fetch('{{ route('admin.ozellikler.suggest-category') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        name: name
                    })
                })
                .then(res => res.json())
                .then(data => {
                    this.aiSuggesting = false;
                    if (data.success) {
                        if (data.suggested_category_id) {
                            window.dispatchEvent(new CustomEvent('category-suggested', {
                                detail: {
                                    id: data.suggested_category_id,
                                    name: data.suggested_category_name
                                }
                            }));
                        } else if (data.is_new_category) {
                            alert('AI bu özellik için yeni bir kategori öneriyor: ' + data.suggested_category_name);
                        }
                    } else {
                        alert('Hata: ' + (data.error || 'Öneri alınamadı.'));
                    }
                })
                .catch(err => {
                    this.aiSuggesting = false;
                    console.error('AI Suggestion Error:', err);
                    alert('AI servisine ulaşılamadı.');
                });
            };
            aiSuggesting = false;">
            @csrf
            @method('PUT')

            <!-- Temel Bilgiler -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 mb-6 overflow-hidden hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div
                    class="bg-gradient-to-r from-gray-50 to-blue-50 dark:from-gray-800 dark:to-blue-900/20 px-4 py-3 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                        <span class="text-xl mr-2">📝</span>
                        <span>Temel Bilgiler</span>
                    </h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Özellik Adı -->
                        <div x-data="{ localName: '{{ old('name', $ozellik->name) }}' }">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Özellik Adı <span class="text-red-500">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input type="text" id="name" name="name" required autofocus x-model="localName"
                                    class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100"
                                    placeholder="Örn: Balkon, Asansör, Güvenlik" value="{{ old('name', $ozellik->name) }}">

                                <button type="button" @click="suggestCategory()"
                                    class="inline-flex items-center gap-2 px-3 py-2 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/40 transition-all duration-200"
                                    :disabled="aiSuggesting || !localName">
                                    <svg x-show="!aiSuggesting" class="w-4 h-4" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <svg x-show="aiSuggesting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                            stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                        </path>
                                    </svg>
                                    <span class="text-xs font-bold" x-text="aiSuggesting ? '...' : 'AI Önerisi'"></span>
                                </button>
                            </div>
                            @error('name')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Kategori (Arama Özellikli) -->
                        @php
                            $oldCategoryId = old('feature_category_id', $ozellik->feature_category_id);
                            $selectedCategory = $oldCategoryId ? $kategoriler->firstWhere('id', $oldCategoryId) : null;
                            $selectedCategoryName = $selectedCategory ? $selectedCategory->name : '';
                        @endphp
                        <div class="space-y-2 relative" x-data="{
                            searchQuery: '{{ $selectedCategoryName }}',
                            selectedCategory: {{ $oldCategoryId ?: 'null' }},
                            selectedCategoryName: '{{ $selectedCategoryName }}',
                            showDropdown: false,
                            allCategories: @js($kategoriler ?? []),
                            filteredCategories: @js($kategoriler ?? []),
                            init() {
                                if (this.selectedCategory) {
                                    this.searchQuery = this.selectedCategoryName;
                                }

                                // AI Önerisi Dinleyici
                                window.addEventListener('category-suggested', (e) => {
                                    const category = this.allCategories.find(c => c.id == e.detail.id);
                                    if (category) {
                                        this.selectCategory(category);
                                        // Visual feedback
                                        this.$el.classList.add('ring-2', 'ring-purple-400');
                                        setTimeout(() => this.$el.classList.remove('ring-2', 'ring-purple-400'), 2000);
                                    }
                                });
                            },
                            filterCategories() {
                                if (!this.searchQuery.trim()) {
                                    this.filteredCategories = this.allCategories;
                                    return;
                                }
                                const query = this.searchQuery.toLowerCase();
                                this.filteredCategories = this.allCategories.filter(cat =>
                                    cat.name.toLowerCase().includes(query) ||
                                    (cat.slug && cat.slug.toLowerCase().includes(query))
                                );
                            },
                            selectCategory(category) {
                                this.selectedCategory = category.id;
                                this.selectedCategoryName = category.name;
                                this.searchQuery = category.name;
                                this.showDropdown = false;
                            },
                            clearSelection() {
                                this.selectedCategory = null;
                                this.selectedCategoryName = '';
                                this.searchQuery = '';
                                this.showDropdown = false;
                            }
                        }" @click.away="showDropdown = false">
                            <label for="category_search"
                                class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Kategori <span class="text-red-500">*</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(Arayarak seçin)</span>
                            </label>

                            <!-- Hidden input for form submission -->
                            <input type="hidden" name="feature_category_id" :value="selectedCategory" required>

                            <!-- Search Input -->
                            <div class="relative">
                                <input type="text" id="category_search" x-model="searchQuery"
                                    @input="filterCategories(); showDropdown = true" @focus="showDropdown = true"
                                    placeholder="Kategori ara veya seçin..." autocomplete="off" required
                                    class="w-full px-4 py-2.5 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200">

                                <!-- Search Icon -->
                                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>

                                <!-- Clear Button (when selected) -->
                                <button type="button" x-show="selectedCategory" @click="clearSelection()"
                                    class="absolute right-10 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Dropdown Results -->
                            <div x-show="showDropdown && filteredCategories.length > 0"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform scale-95"
                                x-transition:enter-end="opacity-100 transform scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 transform scale-100"
                                x-transition:leave-end="opacity-0 transform scale-95"
                                class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-lg max-h-60 overflow-y-auto dark:border-slate-700">
                                <template x-for="category in filteredCategories" :key="category.id">
                                    <div @click="selectCategory(category)"
                                        :class="selectedCategory == category.id ? 'bg-blue-50 dark:bg-blue-900/20' :
                                            'hover:bg-gray-50 dark:hover:bg-gray-700'"
                                        class="px-4 py-2.5 cursor-pointer transition-colors duration-150 border-b border-gray-100 dark:border-slate-800 last:border-b-0">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100"
                                                x-text="category.name"></span>
                                            <span x-show="selectedCategory == category.id"
                                                class="text-blue-600 dark:text-blue-400">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                        clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        </div>
                                        <span x-show="category.slug"
                                            class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 block"
                                            x-text="category.slug"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- No Results Message -->
                            <div x-show="showDropdown && searchQuery.trim() && filteredCategories.length === 0"
                                x-transition
                                class="absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-lg p-4 dark:border-slate-700">
                                <p class="text-sm text-gray-500 dark:text-gray-400 text-center">
                                    "<span x-text="searchQuery"></span>" için sonuç bulunamadı
                                </p>
                            </div>

                            <!-- Selected Category Display -->
                            <div x-show="selectedCategory && !showDropdown" class="mt-2">
                                <div
                                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                                    <span class="text-sm font-medium text-blue-700 dark:text-blue-300"
                                        x-text="selectedCategoryName"></span>
                                    <button type="button" @click="clearSelection()"
                                        class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            @error('feature_category_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Sıra -->
                        <div>
                            <label for="sira" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Sıra
                            </label>
                            <input type="number" id="sira" name="display_order" min="0" x-model="sira"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100"
                                placeholder="Otomatik" value="{{ old('display_order', $ozellik->display_order ?? '') }}">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Boş bırakılırsa otomatik sıralanır</p>
                            @error('display_order')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Modern Status Toggle -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Durum
                            </label>
                            <div class="flex items-center space-x-3">
                                <!-- Toggle Switch -->
                                <div class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors duration-200 cursor-pointer"
                                    :class="aktif_mi ? 'bg-blue-600 dark:bg-blue-500' : 'bg-gray-300 dark:bg-gray-600'"
                                    @click="aktif_mi = !aktif_mi">
                                    <span
                                        class="inline-block h-4 w-4 transform rounded-full bg-white dark:bg-gray-200 transition-transform duration-200 dark:bg-slate-900"
                                        :class="aktif_mi ? 'translate-x-6' : 'translate-x-1'"></span>
                                </div>
                                <input type="hidden" name="aktif_mi" :value="aktif_mi ? '1' : '0">
                                <span class="text-sm font-medium"
                                    :class="aktif_mi ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400'">
                                    <span x-text="aktif_mi ? 'Aktif' : 'Pasif'"></span>
                                </span>
                            </div>
                            @error('aktif_mi')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Veri Tipi ve Seçenekler -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
                        <!-- Veri Tipi -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Veri Tipi <span class="text-red-500">*</span>
                            </label>
                            <select style="color-scheme: light dark;" id="type" name="type" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100"
                                x-model="veriTipi">
                                <option value="">Veri Tipi Seçin</option>
                                <option value="text"
                                    {{ old('type', $ozellik->type ?? 'text') == 'text' ? 'selected' : '' }}>
                                    Metin
                                    (Text)</option>
                                <option value="number"
                                    {{ old('type', $ozellik->type ?? 'text') == 'number' ? 'selected' : '' }}>
                                    Sayı (Number)</option>
                                <option value="select"
                                    {{ old('type', $ozellik->type ?? 'text') == 'select' ? 'selected' : '' }}>
                                    Seçim (Select)</option>
                                <option value="boolean"
                                    {{ old('type', $ozellik->type ?? 'text') == 'boolean' ? 'selected' : '' }}>
                                    Evet/Hayır (Boolean)</option>
                                <option value="checkbox"
                                    {{ old('type', $ozellik->type ?? 'text') == 'checkbox' ? 'selected' : '' }}>
                                    Checkbox</option>
                                <option value="radio"
                                    {{ old('type', $ozellik->type ?? 'text') == 'radio' ? 'selected' : '' }}>
                                    Radio</option>
                                <option value="textarea"
                                    {{ old('type', $ozellik->type ?? 'text') == 'textarea' ? 'selected' : '' }}>
                                    Textarea</option>
                            </select>
                            @error('type')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Birim -->
                        <div>
                            <label for="unit" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Birim
                            </label>
                            <input type="text" id="unit" name="unit"
                                value="{{ old('unit', $ozellik->unit ?? '') }}"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100"
                                placeholder="Örn: m², adet, TL, gün">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sadece sayısal alanlar için</p>
                            @error('unit')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Veri Seçenekleri (Select/Multiselect için) -->
                    <div x-show="showOptions" x-transition class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Veri Seçenekleri <span class="text-red-500">*</span>
                        </label>
                        <div>
                            <template x-for="(option, index) in options" :key="index">
                                <div class="flex items-center space-x-2 mb-2">
                                    <input type="text" x-model="option.value"
                                        :name="'field_options[' + index + '][value]'" placeholder="Seçenek değeri"
                                        class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100">
                                    <input type="text" x-model="option.label"
                                        :name="'field_options[' + index + '][label]'" placeholder="Seçenek etiketi"
                                        class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:text-slate-100">
                                    <button type="button" @click="options.splice(index, 1)"
                                        class="px-4 py-2.5 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md active:scale-95 dark:shadow-none">
                                        ❌
                                    </button>
                                </div>
                            </template>
                            <button type="button" @click="options.push({value: '', label: ''})"
                                class="px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-all duration-200 shadow-sm hover:shadow-md active:scale-95 dark:shadow-none">
                                ➕ Seçenek Ekle
                            </button>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Sadece Select/Multiselect için gerekli</p>
                    </div>

                    <!-- Zorunlu Alan -->
                    <div class="mt-6">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" name="is_required" value="1"
                                {{ old('is_required', $ozellik->is_required ?? false) ? 'checked' : '' }}
                                class="w-4 h-4 text-blue-600 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 bg-white dark:bg-slate-900 transition-all duration-200">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Bu özellik zorunlu alan
                                olsun</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Sadece sayısal alanlar için</span>
                        </label>
                    </div>

                    <!-- Açıklama -->
                    <div class="mt-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Açıklama
                        </label>
                        <textarea id="description" name="description" rows="4"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 resize-none dark:text-slate-100"
                            placeholder="Özellik hakkında detaylı açıklama...">{{ old('description', $ozellik->description ?? '') }}</textarea>
                        @error('description')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Özellik Bilgileri (Real-time Updates) -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 mb-6 overflow-hidden hover:shadow-md transition-shadow duration-200 dark:shadow-none dark:border-slate-700">
                <div
                    class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 px-4 py-3 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
                        <span class="text-xl mr-2">ℹ️</span>
                        <span>Özellik Bilgileri</span>
                        <span class="text-xs font-normal text-gray-600 dark:text-gray-400 ml-2">Güncel özellik
                            detayları</span>
                    </h2>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 dark:bg-slate-900">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Kategori</h3>
                            <p class="text-gray-600 dark:text-gray-400" x-text="getCategoryName()"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 dark:bg-slate-900">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Durum</h3>
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium transition-colors duration-200"
                                :class="aktif_mi ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' :
                                    'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'">
                                <span class="w-2 h-2 rounded-full mr-1"
                                    :class="aktif_mi ? 'bg-green-400 animate-pulse' : 'bg-red-400'"></span>
                                <span x-text="aktif_mi ? 'Aktif' : 'Pasif'"></span>
                            </span>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 dark:bg-slate-900">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Sıra</h3>
                            <p class="text-gray-600 dark:text-gray-400" x-text="sira || 'Otomatik'"></p>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 dark:bg-slate-900">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Oluşturulma</h3>
                            <p class="text-gray-600 dark:text-gray-400">{{ $ozellik->created_at->format('d.m.Y H:i') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modern Form Actions -->
            <div
                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-4 sticky bottom-4 dark:shadow-none dark:border-slate-700">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <!-- Status Badge -->
                        <div class="flex items-center gap-2">
                            <div class="flex items-center gap-1.5">
                                <div class="w-2 h-2 rounded-full animate-pulse"
                                    :class="aktif_mi ? 'bg-green-500' : 'bg-red-500'"></div>
                                <span class="text-xs font-semibold"
                                    :class="aktif_mi ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400'"
                                    x-text="aktif_mi ? 'Aktif' : 'Pasif'"></span>
                            </div>
                            <div class="h-4 w-px bg-gray-300 dark:bg-gray-600"></div>
                            <span class="text-xs text-gray-600 dark:text-gray-400 font-medium">{{ $ozellik->name }}
                                düzenleniyor</span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2">
                        <a href="{{ route('admin.ozellikler.index') }}"
                            class="px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-900 dark:text-white rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 text-center font-medium text-sm shadow-sm hover:shadow-md active:scale-95 dark:shadow-none dark:text-slate-100">
                            İptal
                        </a>
                        <button type="submit" :disabled="isSubmitting" id="ozellik-edit-submit-btn"
                            class="px-6 py-2.5 bg-gradient-to-r from-blue-600 via-blue-500 to-purple-600 text-white rounded-lg hover:from-blue-700 hover:via-blue-600 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg font-semibold text-sm active:scale-95 disabled:hover:scale-100 dark:shadow-none">
                            <svg id="ozellik-edit-submit-icon" x-show="!isSubmitting" class="w-4 h-4" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                            <svg id="ozellik-edit-submit-spinner" x-show="isSubmitting" class="w-4 h-4 animate-spin"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-show="!isSubmitting">💾 Güncelle</span>
                            <span x-show="isSubmitting">Kaydediliyor...</span>
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // ✅ SAB: Keyboard shortcut (Cmd/Ctrl + S)
        document.addEventListener('keydown', function(e) {
            if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                document.querySelector('form').submit();
            }
        });
    </script>
@endpush
