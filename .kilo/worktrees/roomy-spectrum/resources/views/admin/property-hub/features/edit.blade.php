@extends('admin.layouts.app')

@section('title', 'Özellik Düzenle - Property Hub')

@section('content')
    <div x-data="featureForm()" class="min-h-screen pb-20">
        {{-- Header Section --}}
        <div class="mb-8">
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
                <a href="{{ route('admin.property-hub.index') }}"
                    class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Property Hub</a>
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('admin.property-hub.features.index') }}"
                    class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Özellikler</a>
                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-gray-900 dark:text-white font-medium dark:text-slate-100">Düzenle</span>
            </div>

            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white tracking-tight leading-tight dark:text-slate-100">
                        Özellik Düzenle: <span class="text-blue-600 dark:text-blue-400">{{ $feature->name }}</span>
                    </h1>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">
                        Bu özelliğin tipini, seçeneklerini ve sistemdeki davranışını yapılandırın.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="button"
                        onclick="if(confirm('Bu özelliği silmek istediğinize emin misiniz?')) document.getElementById('delete-form').submit();"
                        class="inline-flex items-center gap-2 px-4 py-2.5 text-red-600 bg-red-50 hover:bg-red-100 hover:text-red-700 dark:bg-red-900/10 dark:text-red-400 dark:hover:bg-red-900/20 rounded-xl transition-all duration-200 font-medium">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Sil
                    </button>
                    <button type="submit" form="feature-update-form"
                        class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/30 transition-all duration-200 font-medium transform hover:-translate-y-0.5">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Değişiklikleri Kaydet
                    </button>
                </div>
            </div>
        </div>

        <form id="feature-update-form" action="{{ route('admin.property-hub.features.update', $feature) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Left Column: Main Configuration --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Card: Temel Bilgiler --}}
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden dark:shadow-none">
                        <div class="border-b border-gray-100 dark:border-slate-800 px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                                <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0" />
                                </svg>
                                Temel Yapılandırma
                            </h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- Name --}}
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                        Özellik Adı
                                    </label>
                                    <div class="relative">
                                        <input type="text" name="name" x-model="name"
                                            class="w-full pl-11 pr-4 py-3 rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 group-hover:bg-white dark:group-hover:bg-gray-800 dark:border-slate-700 dark:text-slate-100"
                                            placeholder="Örn: Isıtma Tipi" required>
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                {{-- Slug --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                        Sistem Kodu (Slug)
                                    </label>
                                    <div class="relative">
                                        <input type="text" name="slug"
                                            value="{{ old('slug', $feature->slug) }}"
                                            class="w-full pl-11 pr-4 py-3 rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 font-mono text-sm dark:border-slate-700 dark:text-slate-100"
                                            placeholder="otomatik-olusturulur">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                {{-- Category --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                        Kategori
                                    </label>
                                    <div class="relative">
                                        <select name="feature_category_id"
                                            class="w-full pl-11 pr-10 py-3 rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 appearance-none transition-all duration-200 dark:border-slate-700 dark:text-slate-100">
                                            <option value="">Kategorisiz</option>
                                            @foreach ($categories as $category)
                                                <option value="{{ $category->id }}"
                                                    {{ old('feature_category_id', $feature->feature_category_id) == $category->id ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                            </svg>
                                        </div>
                                        <div
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-400">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M8 9l4-4 4 4m0 6l-4 4-4-4" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Card: Veri Tipi ve Seçenekler --}}
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden dark:shadow-none">
                        <div
                            class="border-b border-gray-100 dark:border-slate-800 px-6 py-4 bg-gray-50/50 dark:bg-gray-800/50 flex justify-between items-center">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                                </svg>
                                Veri Modeli
                            </h3>
                        </div>
                        <div class="p-6 space-y-6">
                            {{-- Type Selection --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-3 dark:text-slate-300">
                                    Giriş Tipi
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    {{-- Type: Text --}}
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="type" value="text" x-model="type"
                                            class="sr-only peer">
                                        <div
                                            class="flex flex-col items-center justify-center p-4 rounded-xl border-2 border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-600 h-full dark:border-slate-700">
                                            <svg class="w-8 h-8 text-gray-400 peer-checked:text-blue-500 mb-2"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-slate-200 peer-checked:text-blue-600 dark:peer-checked:text-blue-400 dark:text-slate-300">Metin</span>
                                        </div>
                                    </label>

                                    {{-- Type: Number --}}
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="type" value="number" x-model="type"
                                            class="sr-only peer">
                                        <div
                                            class="flex flex-col items-center justify-center p-4 rounded-xl border-2 border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-600 h-full dark:border-slate-700">
                                            <svg class="w-8 h-8 text-gray-400 peer-checked:text-blue-500 mb-2"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                                            </svg>
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-slate-200 peer-checked:text-blue-600 dark:peer-checked:text-blue-400 dark:text-slate-300">Sayı</span>
                                        </div>
                                    </label>

                                    {{-- Type: Boolean --}}
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="type" value="boolean" x-model="type"
                                            class="sr-only peer">
                                        <div
                                            class="flex flex-col items-center justify-center p-4 rounded-xl border-2 border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-600 h-full dark:border-slate-700">
                                            <svg class="w-8 h-8 text-gray-400 peer-checked:text-blue-500 mb-2"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-slate-200 peer-checked:text-blue-600 dark:peer-checked:text-blue-400 dark:text-slate-300">Mantıksal</span>
                                        </div>
                                    </label>

                                    {{-- Type: Select --}}
                                    <label class="cursor-pointer group">
                                        <input type="radio" name="type" value="select" x-model="type"
                                            class="sr-only peer">
                                        <div
                                            class="flex flex-col items-center justify-center p-4 rounded-xl border-2 border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 transition-all duration-200 group-hover:border-gray-300 dark:group-hover:border-gray-600 h-full dark:border-slate-700">
                                            <svg class="w-8 h-8 text-gray-400 peer-checked:text-blue-500 mb-2"
                                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 6h16M4 12h16M4 18h16" />
                                            </svg>
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-slate-200 peer-checked:text-blue-600 dark:peer-checked:text-blue-400 dark:text-slate-300">Liste</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Dynamic Options --}}
                            <div x-show="type === 'select'" x-transition:enter="transition ease-out duration-300"
                                x-transition:enter-start="opacity-0 translate-y-4"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                class="bg-gray-50 dark:bg-gray-900/50 rounded-xl p-6 border border-gray-100 dark:border-slate-800 dark:bg-slate-900">
                                <div class="flex justify-between items-center mb-4">
                                    <label class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                        Liste Seçenekleri
                                    </label>
                                    <span class="text-xs text-gray-500 bg-white dark:bg-slate-900 px-2 py-1 rounded border border-gray-200 dark:border-slate-800 dark:border-slate-700" x-text="options.length + ' adet'"></span>
                                </div>
                                <div class="space-y-3">
                                    <template x-for="(option, index) in options" :key="index">
                                        <div class="flex items-center gap-3">
                                            <div class="flex-none text-gray-400 font-mono text-xs w-6 text-center" x-text="index + 1"></div>
                                            <input type="text" x-model="options[index]"
                                                :name="'options[' + index + ']'"
                                                class="flex-1 rounded-lg border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 text-sm shadow-sm dark:shadow-none dark:border-slate-700 dark:text-slate-100"
                                                placeholder="Seçenek metni girin">
                                            <button type="button" @click="removeOption(index)"
                                                class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                <button type="button" @click="addOption()"
                                    class="mt-4 w-full flex items-center justify-center gap-2 px-4 py-3 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl text-gray-500 dark:text-gray-400 hover:border-blue-500 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/10 transition-all duration-200 font-medium text-sm">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Yeni Seçenek Ekle
                                </button>
                            </div>

                            {{-- Unit Field --}}
                            <div x-show="type === 'number'" x-transition>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                    Ölçü Birimi
                                </label>
                                <div class="relative">
                                    <input type="text" name="unit" value="{{ old('unit', $feature->unit) }}"
                                        class="w-full pl-11 pr-4 py-3 rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 dark:border-slate-700 dark:text-slate-100"
                                        placeholder="Örn: m², km, adet">
                                    <div
                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Right Column: Preview & Metadata --}}
                <div class="space-y-6">

                    {{-- Live Preview Card --}}
                    <div
                        class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-2xl shadow-xl overflow-hidden text-white relative">
                        <div class="absolute top-0 right-0 p-4 opacity-10">
                            <svg class="w-32 h-32" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path fill-rule="evenodd"
                                    d="M1.323 11.447C2.811 6.976 7.028 3.75 12.001 3.75c4.97 0 9.185 3.223 10.675 7.69.12.362.12.752 0 1.113-1.487 4.471-5.705 7.697-10.677 7.697-4.97 0-9.186-3.223-10.675-7.69a1.762 1.762 0 010-1.113zM17.25 12a5.25 5.25 0 11-10.5 0 5.25 5.25 0 0110.5 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="p-6 relative z-10">
                            <h3 class="text-lg font-semibold mb-1 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                Canlı Önizleme
                            </h3>
                            <p class="text-blue-100 text-sm mb-6">İlan sihirbazında bu özellik böyle görünecek.</p>

                            <div class="bg-white dark:bg-slate-900 rounded-xl p-4 shadow-lg text-gray-900 dark:text-white dark:text-slate-100">
                                {{-- Dynamic Render --}}
                                <div class="space-y-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                        <span x-text="name || 'Özellik Adı'"></span>
                                    </label>

                                    {{-- Text Input Preview --}}
                                    <div x-show="type === 'text'">
                                        <input type="text"
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm py-2.5 dark:bg-slate-900"
                                            placeholder="Veri giriniz...">
                                    </div>

                                    {{-- Number Input Preview --}}
                                    <div x-show="type === 'number'">
                                        <div class="relative">
                                            <input type="number"
                                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm py-2.5 dark:bg-slate-900"
                                                placeholder="0">
                                            <div
                                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none text-gray-500">
                                                <span x-text="document.querySelector('[name=unit]').value || ''"></span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Boolean Toggle Preview --}}
                                    <div x-show="type === 'boolean'">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" class="sr-only peer">
                                    <div
                                        class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-600 dark:border-gray-600 peer-checked:bg-blue-600">
                                    </div>
                                            <span class="ml-3 text-sm font-medium text-gray-900 dark:text-slate-200 dark:text-white">Evet /
                                                Hayır</span>
                                        </label>
                                    </div>

                                    {{-- Select Preview --}}
                                    <div x-show="type === 'select'">
                                        <select
                                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-sm py-2.5 dark:bg-slate-900">
                                            <option>Seçiniz...</option>
                                            <template x-for="opt in options">
                                                <option x-text="opt"></option>
                                            </template>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Metadata Card --}}
                    <div
                        class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 p-6 space-y-6 dark:shadow-none">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">Durum & Ayarlar</h3>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-slate-200 dark:bg-slate-900">
                                ID: {{ $feature->id }}
                            </span>
                        </div>

                        {{-- Active Toggle --}}
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-xl dark:bg-slate-900">
                            <span class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Aktiflik Durumu</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="aktiflik_durumu" value="1"
                                    {{ old('aktiflik_durumu', $feature->aktiflik_durumu) ? 'checked' : '' }} class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:after:border-gray-600 dark:border-gray-600 peer-checked:bg-blue-600">
                                </div>
                            </label>
                        </div>

                        {{-- Display Order --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Görüntüleme Sırası
                            </label>
                            <input type="number" name="display_order"
                                value="{{ old('display_order', $feature->display_order) }}"
                                class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 dark:border-slate-700 dark:text-slate-100">
                        </div>

                        <hr class="border-gray-100 dark:border-slate-800">

                        {{-- Stats Grid --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 rounded-lg bg-blue-50 dark:bg-blue-900/10">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    {{ $feature->assignments_count ?? 0 }}</div>
                                <div class="text-xs text-blue-600/70 dark:text-blue-400/70 font-medium">İlan Ataması</div>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-purple-50 dark:bg-purple-900/10">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    {{ $feature->created_at->diffInDays(now()) }}</div>
                                <div class="text-xs text-purple-600/70 dark:text-purple-400/70 font-medium">Günlük Özellik</div>
                            </div>
                        </div>
                    </div>

                    {{-- Description --}}
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-100 dark:border-slate-800 p-6 dark:shadow-none">
                         <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Açıklama / Notlar
                        </label>
                        <textarea name="description" rows="4"
                            class="w-full rounded-xl border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200 resize-none dark:border-slate-700 dark:text-slate-100"
                            placeholder="Bu özelliğin kullanımı hakkında notlar...">{{ old('description', $feature->description) }}</textarea>
                    </div>

                </div>
            </div>
        </form>

        {{-- Hidden Delete Form --}}
        <form id="delete-form" action="{{ route('admin.property-hub.features.destroy', $feature) }}" method="POST"
            class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>

    <script>
        function featureForm() {
            return {
                name: @json(old('name', $feature->name)),
                type: '{{ old('type', $feature->type) }}',
                options: {!! json_encode(old('options', $feature->options ?? [''])) !!},

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
