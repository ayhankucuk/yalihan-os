@extends('admin.layouts.admin')

@section('title', 'Yeni Özellik Ekle')

@section('content')
    <!-- Context7: Temiz Özellik Create Form -->
    <div class="container mx-auto" x-data="ozellikForm()">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni Özellik Ekle</h1>
            <a href="{{ route('admin.ozellikler.index') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 transition-all duration-200 shadow-sm dark:shadow-blue-900/10 dark:text-slate-300">
                ← Geri Dön
            </a>
        </div>

        <!-- Success Message -->
        @if (session('success'))
            <div
                class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-blue-800 dark:bg-blue-900 dark:border-blue-800 dark:text-blue-200 mb-6">
                {{ session('success') }}
            </div>
        @endif

        <!-- Form Card -->
        <div
            class="rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm dark:shadow-none hover:shadow-md transition-all duration-300 p-6 dark:border-slate-700">
            <form method="POST" action="{{ route('admin.ozellikler.store') }}" @submit="loading = true">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Özellik Adı -->
                    <div class="space-y-2 relative" x-data="{ localName: '' }">
                        <label for="name"
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Özellik Adı
                            *</label>
                        <div class="flex gap-2">
                            <input type="text" id="name" name="name" x-model="localName"
                                class="flex-1 px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                                value="{{ old('name') }}" required maxlength="255"
                                placeholder="Örn: Havuz, Asansör, Güvenlik">

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
                            <div class="text-red-600 dark:text-red-400 font-medium text-xs">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Özellik Tipi -->
                    <div class="space-y-2 relative">
                        <label for="type"
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Özellik Tipi
                            *</label>
                        <select style="color-scheme: light dark;" id="type" name="type"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                            required>
                            <option value="">Seçiniz</option>
                            <option value="boolean" {{ old('type') == 'boolean' ? 'selected' : '' }}>Evet/Hayır</option>
                            <option value="text" {{ old('type') == 'text' ? 'selected' : '' }}>Metin</option>
                            <option value="number" {{ old('type') == 'number' ? 'selected' : '' }}>Sayı</option>
                            <option value="select" {{ old('type') == 'select' ? 'selected' : '' }}>Seçenekli</option>
                        </select>
                        @error('type')
                            <div class="text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Kategori (Arama Özellikli) -->
                    @php
                        $oldCategoryId = old('feature_category_id');
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
                            Kategori
                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">(Arayarak seçin)</span>
                        </label>

                        <!-- Hidden input for form submission -->
                        <input type="hidden" name="feature_category_id" :value="selectedCategory">

                        <!-- Search Input -->
                        <div class="relative">
                            <input type="text" id="category_search" x-model="searchQuery"
                                @input="filterCategories(); showDropdown = true" @focus="showDropdown = true"
                                placeholder="Kategori ara veya seçin..." autocomplete="off"
                                class="w-full px-4 py-2.5 pr-10 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200">

                            <!-- Search Icon -->
                            <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>

                            <!-- Clear Button (when selected) -->
                            <button type="button" x-show="selectedCategory" @click="clearSelection()"
                                class="absolute right-10 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200">
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
                        <div x-show="showDropdown && searchQuery.trim() && filteredCategories.length === 0" x-transition
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
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200 transition-colors duration-200">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        @error('feature_category_id')
                            <div class="text-red-600 dark:text-red-400 text-sm mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Sıra -->
                    <div class="space-y-2 relative">
                        <label for="sira"
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Sıra</label>
                        <input type="number" id="sira" name="display_order"
                            class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                            value="{{ old('display_order', 0) }}" min="0">
                        @error('display_order')
                            <div class="text-red-600 dark:text-red-400">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Durum -->
                <div class="space-y-2 relative mt-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Durum</label>
                    <div class="flex items-center space-x-4">
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="aktif_mi" value="1"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 dark:bg-slate-900"
                                {{ old('aktif_mi', 1) == 1 ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-900 dark:text-white dark:text-slate-100">Aktif</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="radio" name="aktif_mi" value="0"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 dark:bg-slate-900"
                                {{ old('aktif_mi') == 0 ? 'checked' : '' }}>
                            <span class="ml-2 text-gray-900 dark:text-white dark:text-slate-100">Pasif</span>
                        </label>
                    </div>
                    @error('aktif_mi')
                        <div class="text-red-600 dark:text-red-400">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <a href="{{ route('admin.ozellikler.index') }}"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-slate-800 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 hover:scale-105 dark:hover:scale-105 active:scale-95 dark:active:scale-95 focus:ring-2 focus:ring-gray-500 dark:focus:ring-gray-400 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                        İptal
                    </a>
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 dark:bg-blue-500 text-white font-bold rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 shadow-md dark:shadow-blue-900/40 hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100"
                        :disabled="loading">
                        <svg x-show="!loading" class="w-5 h-5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <svg x-show="loading" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                            </path>
                        </svg>
                        <span x-text="loading ? 'Kaydediliyor...' : 'Kaydet'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function ozellikForm() {
            return {
                loading: false,
                aiSuggesting: false,
                suggestCategory() {
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
                                // Find the category search script scope
                                // Since it's nested, we can use an event or direct access if named
                                window.dispatchEvent(new CustomEvent('category-suggested', {
                                    detail: {
                                        id: data.suggested_category_id,
                                        name: data.suggested_category_name
                                    }
                                }));
                            } else if (data.is_new_category) {
                                alert('AI bu özellik için yeni bir kategori öneriyor: "' + data.suggested_category_name + '".\nBu kategori sistemde henüz mevcut değil.');
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
                }
            }
        }
    </script>
@endsection
@push('scripts')
@endpush
