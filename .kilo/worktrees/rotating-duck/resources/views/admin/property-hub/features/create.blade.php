@extends('admin.layouts.app')

@section('title', 'Yeni Özellik Oluştur - Property Hub')

@section('content')
    <div x-data="featureForm()" class="max-w-5xl mx-auto space-y-8 pb-12">
        {{-- Header --}}
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('admin.property-hub.index') }}"
                    class="hover:text-blue-600 transition-all duration-200">Property Hub</a>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('admin.property-hub.features.index') }}"
                    class="hover:text-blue-600 transition-all duration-200">Özellikler</a>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>Yeni Özellik</span>
            </div>
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight dark:text-slate-100">Yeni Özellik Tasarla</h1>
                    <p class="mt-2 text-gray-500 dark:text-gray-400 text-lg">
                        Sistem genelinde kullanılacak yeni bir özellik tanımlayın.
                    </p>
                </div>
                <div class="hidden md:block">
                    <div class="h-12 w-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form --}}
        <form action="{{ route('admin.property-hub.features.store') }}" method="POST" class="space-y-8">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Left Column: Main Info --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Card: Basic Information --}}
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 p-6 sm:p-8 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2 dark:text-slate-100">
                            <svg class="h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Temel Bilgiler
                        </h2>

                        <div class="space-y-6">
                            {{-- Name & Slug --}}
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                        Özellik Adı <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="name" id="name" x-model="name" @input="generateSlug()"
                                        value="{{ old('name') }}"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 py-3"
                                        placeholder="Örn: Oda Sayısı" required>
                                    @error('name')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                        Slug (Otomatik)
                                    </label>
                                    <div class="relative">
                                        <input type="text" name="slug" id="slug" x-model="slug" value="{{ old('slug') }}"
                                            class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700/50 dark:text-gray-400 bg-gray-50 text-gray-500 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 py-3 pl-10 dark:bg-slate-900"
                                            placeholder="oda-sayisi" readonly>
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400">#</span>
                                        </div>
                                    </div>
                                    @error('slug')
                                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            {{-- Description --}}
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                    Açıklama
                                </label>
                                <textarea name="description" id="description" rows="3"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                                    placeholder="Bu özellik hakkında kısa bir açıklama veya yardım metni...">{{ old('description') }}</textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Card: Data Type Selection --}}
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 p-6 sm:p-8 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2 dark:text-slate-100">
                            <svg class="h-5 w-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                            Veri Tipi
                        </h2>

                        <input type="hidden" name="type" x-model="type">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Text Type -->
                            <div @click="type = 'text'"
                                :class="type === 'text' ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-gray-200 dark:border-gray-700' dark:border-slate-700"
                                class="cursor-pointer rounded-xl border p-4 transition-all duration-200 relative group">
                                <div class="flex items-start gap-4">
                                    <div :class="type === 'text' ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                                         class="p-2 rounded-lg transition-colors duration-200">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Metin (Text)</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Kısa yazı girişleri için.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Number Type -->
                            <div @click="type = 'number'"
                                :class="type === 'number' ? 'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-gray-200 dark:border-gray-700' dark:border-slate-700"
                                class="cursor-pointer rounded-xl border p-4 transition-all duration-200 relative group">
                                <div class="flex items-start gap-4">
                                    <div :class="type === 'number' ? 'bg-purple-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                                         class="p-2 rounded-lg transition-colors duration-200">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Sayı (Number)</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Metrekare, fiyat, adet vb.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Boolean Type -->
                            <div @click="type = 'boolean'"
                                :class="type === 'boolean' ? 'ring-2 ring-green-500 bg-green-50 dark:bg-green-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-gray-200 dark:border-gray-700' dark:border-slate-700"
                                class="cursor-pointer rounded-xl border p-4 transition-all duration-200 relative group">
                                <div class="flex items-start gap-4">
                                    <div :class="type === 'boolean' ? 'bg-green-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                                         class="p-2 rounded-lg transition-colors duration-200">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Evet / Hayır</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Var/Yok durumları için.</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Select Type -->
                            <div @click="type = 'select'"
                                :class="type === 'select' ? 'ring-2 ring-amber-500 bg-amber-50 dark:bg-amber-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700/50 border-gray-200 dark:border-gray-700' dark:border-slate-700"
                                class="cursor-pointer rounded-xl border p-4 transition-all duration-200 relative group">
                                <div class="flex items-start gap-4">
                                    <div :class="type === 'select' ? 'bg-amber-500 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400'"
                                         class="p-2 rounded-lg transition-colors duration-200">
                                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">Seçim Listesi</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Ön tanımlı seçenekler.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Options for Select type --}}
                        <div x-show="type === 'select'" x-transition class="mt-8 space-y-4 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                    Seçenekler
                                </label>
                                <span class="text-xs text-gray-500">Sürükleyip bırakarak sıralayabilirsiniz</span>
                            </div>

                            <div class="space-y-3">
                                <template x-for="(option, index) in options" :key="index">
                                    <div class="flex items-center gap-3 group">
                                        <div class="text-gray-400 cursor-move hover:text-gray-600 dark:hover:text-gray-300">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                            </svg>
                                        </div>
                                        <input type="text" x-model="options[index]" :name="'options[' + index + ']'"
                                            class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200"
                                            placeholder="Seçenek değeri (örn: Doğalgaz)">
                                        <button type="button" @click="removeOption(index)"
                                            class="p-2 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-all duration-200 opacity-0 group-hover:opacity-100">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>

                            <button type="button" @click="addOption()"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-600 dark:text-gray-400 hover:border-blue-500 hover:text-blue-500 dark:hover:border-blue-400 dark:hover:text-blue-400 transition-all duration-200">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Yeni Seçenek Ekle
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Settings --}}
                <div class="space-y-6">
                    {{-- Card: Categorization --}}
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Kategorizasyon</h2>

                        <div class="space-y-4">
                            <div>
                                <label for="feature_category_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                    Özellik Kategorisi
                                </label>
                                <select name="feature_category_id" id="feature_category_id"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                                    <option value="">Kategori Seçin</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ old('feature_category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="unit" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                    Birim (Opsiyonel)
                                </label>
                                <div class="relative">
                                    <input type="text" name="unit" id="unit" value="{{ old('unit') }}"
                                        class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 pr-10"
                                        placeholder="m²">
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <span class="text-gray-400 text-xs">UNIT</span>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="display_order" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                    Sıralama
                                </label>
                                <input type="number" name="display_order" id="display_order" value="{{ old('display_order', 0) }}"
                                    class="w-full rounded-xl border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="space-y-3">
                         <button type="submit"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white text-lg font-semibold rounded-xl hover:from-blue-700 hover:to-blue-800 shadow-lg shadow-blue-500/20 transition-all duration-200 transform hover:scale-[1.02]">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Özelliği Oluştur
                        </button>

                        <a href="{{ route('admin.property-hub.features.index') }}"
                            class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 border border-gray-200 dark:border-slate-800 font-medium rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 dark:text-slate-300 dark:border-slate-700">
                            İptal Et
                        </a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function featureForm() {
            return {
                name: '{{ old('name') }}',
                slug: '{{ old('slug') }}',
                type: '{{ old('type', 'text') }}',
                options: {!! json_encode(old('options', [''])) !!},

                generateSlug() {
                    if (!this.slug || this.slug === this.slugify(this.name.slice(0, -1))) {
                        this.slug = this.slugify(this.name);
                    }
                },

                slugify(text) {
                    const turkishMap = {
                        'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u',
                        'Ç': 'c', 'Ğ': 'g', 'İ': 'i', 'Ö': 'o', 'Ş': 's', 'Ü': 'u'
                    };
                    return text
                        .split('')
                        .map(char => turkishMap[char] || char)
                        .join('')
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/(^-|-$)/g, '');
                },

                addOption() {
                    this.options.push('');
                },

                removeOption(index) {
                    this.options.splice(index, 1);
                }
            };
        }
    </script>
@endsection
