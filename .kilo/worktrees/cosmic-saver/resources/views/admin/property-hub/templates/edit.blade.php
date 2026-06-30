@extends('admin.layouts.app')

@section('title', 'Şablon Düzenle - Property Hub')

@section('content')
    <div x-data="templateEditor()" class="space-y-6">
        {{-- Header --}}
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                <a href="{{ route('admin.property-hub.index') }}"
                    class="hover:text-blue-600 transition-all duration-200">Property Hub</a>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <a href="{{ route('admin.property-hub.templates.index') }}"
                    class="hover:text-blue-600 transition-all duration-200">Şablonlar</a>
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span>Düzenle</span>
            </div>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                        {{ $kategori->name }} - {{ $yayinTipi->name ?? 'Yayın Tipi' }}
                    </h1>
                    <p class="mt-1 text-gray-500 dark:text-gray-400">
                        Bu şablona atanmış özellikleri yönetin
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-full text-sm">
                        {{ $assignments->count() }} Özellik Atanmış
                    </span>
                </div>
            </div>
        </div>

        {{-- 📑 TAB NAVIGATION --}}
        <div class="mb-6">
            {{-- Context7: Active Categories Visibility --}}
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-4">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-1">
                            <i class="fas fa-eye text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                                Bu Yayın Tipi Şu Alt Kategorilerde Aktif:
                            </h3>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if (isset($activeSubCategories) && $activeSubCategories->count() > 0)
                                    @foreach ($activeSubCategories as $subCat)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-200 border border-blue-200 dark:border-blue-700">
                                            {{ $subCat->name }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-sm text-gray-500 dark:text-gray-400 italic">
                                        Hiçbir kategoride aktif değil.
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div>
                        @if ($kategori->id > 0)
                            <a href="{{ route('admin.property_types.show', $kategori->id) }}"
                                class="text-xs font-medium text-blue-700 dark:text-blue-300 hover:text-blue-900 dark:hover:text-blue-100 underline">
                                <i class="fas fa-external-link-alt mr-1"></i>
                                Yöneticiye Git
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button @click="activeDashboardTab = 'global'"
                    :class="activeDashboardTab === 'global' ? 'border-blue-500 text-blue-600 dark:text-blue-400' :
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm">
                    <svg class="-ml-0.5 mr-2 h-5 w-5"
                        :class="activeDashboardTab === 'global' ? 'text-blue-500 dark:text-blue-400' :
                            'text-gray-400 group-hover:text-gray-500 dark:text-gray-500'"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Genel Şablon
                </button>

                <button @click="activeDashboardTab = 'category'"
                    :class="activeDashboardTab === 'category' ? 'border-purple-500 text-purple-600 dark:text-purple-400' :
                        'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm">
                    <svg class="-ml-0.5 mr-2 h-5 w-5"
                        :class="activeDashboardTab === 'category' ? 'text-purple-500 dark:text-purple-400' :
                            'text-gray-400 group-hover:text-gray-500 dark:text-gray-500'"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                    </svg>
                    Kategoriye Özel
                </button>
            </nav>
        </div>

        <div x-show="activeDashboardTab === 'global'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Assigned Features --}}
            <div class="lg:col-span-2 space-y-4">
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Atanmış
                                Özellikler</h2>
                            <div class="flex items-center gap-2">
                                <button @click="openAiModal()"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition-all duration-200">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    Cortex AI Önerileri
                                </button>
                                <button type="button" id="open-ai-design-btn"
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                                    data-kategori-id="{{ $kategori->id }}" data-yayin-tipi-id="{{ $yayinTipi->id }}"
                                    data-scope="{{ $scope ?? 'master' }}">
                                    <span>🧪</span>
                                    <span>AI ile Tasarla</span>
                                </button>
                                <button @click="startPipeline('template_suggest')" :disabled="pipelineRunning"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-emerald-600 text-white text-sm rounded-lg hover:bg-emerald-700 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        :class="pipelineRunning ? 'animate-spin' : ''">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                    </svg>
                                    <span x-text="pipelineRunning ? 'Pipeline Çalışıyor...' : 'Pipeline AI'"></span>
                                </button>
                                <button @click="showAddModal = true"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-all duration-200">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Özellik Ekle
                                </button>
                            </div>
                        </div>
                    </div>

                    @if ($assignments->count() > 0)
                        <div class="divide-y divide-gray-200 dark:divide-gray-700" x-ref="sortableList">
                            @foreach ($assignments as $assignment)
                                <div class="px-6 py-4 flex items-center gap-4 group hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-all duration-200"
                                    data-id="{{ $assignment->id }}">
                                    {{-- Drag Handle --}}
                                    <div class="cursor-move text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 8h16M4 16h16" />
                                        </svg>
                                    </div>

                                    {{-- Feature Info --}}
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-gray-900 dark:text-slate-100">
                                                {{ $assignment->feature->name ?? '—' }}
                                            </span>
                                            @if ($assignment->feature?->category)
                                                <span
                                                    class="px-2 py-0.5 text-xs font-semibold bg-gray-100 dark:bg-slate-900 text-gray-600 dark:text-gray-400 rounded-full border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                                    {{ $assignment->feature->category->name }}
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $assignment->feature->slug ?? '—' }}
                                            @if ($assignment->group_name)
                                                <span class="text-blue-600 dark:text-blue-400">•
                                                    {{ $assignment->group_name }}</span>
                                            @endif
                                        </p>
                                    </div>

                                    {{-- Feature Type Badge --}}
                                    <div>
                                        @php
                                            $typeColors = [
                                                'boolean' =>
                                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                'text' =>
                                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                'number' =>
                                                    'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                                'select' =>
                                                    'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                            ];
                                            $typeColor =
                                                $typeColors[$assignment->feature->type ?? 'text'] ??
                                                'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 py-1 text-xs rounded {{ $typeColor }}">
                                            {{ ucfirst($assignment->feature->type ?? 'text') }}
                                        </span>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="opacity-0 group-hover:opacity-100 transition-all duration-200">
                                        <button @click="removeFeature({{ $assignment->id }})"
                                            class="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-all duration-200">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12">
                            <div class="p-4 bg-gray-100 dark:bg-gray-700 rounded-full mb-4 dark:bg-slate-900">
                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-1 dark:text-slate-100">Henüz
                                özellik atanmamış</h3>
                            <p class="text-gray-500 dark:text-gray-400 mb-4">Bu şablona özellik ekleyin</p>
                            <button @click="showAddModal = true"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Özellik Ekle
                            </button>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-4">
                {{-- Template Info --}}
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4 dark:text-slate-100">Şablon
                        Bilgileri</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Kategori</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                {{ $kategori->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Yayın Tipi</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                {{ $yayinTipi->name ?? '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs text-gray-500 dark:text-gray-400">Atanmış Özellik</dt>
                            <dd class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                {{ $assignments->count() }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Master Templates --}}
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4 dark:text-slate-100">Master Şablon
                        Uygula</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
                        Önceden tanımlanmış standart bir seti bu kategoriye uygula.
                    </p>
                    <div class="space-y-2">
                        @foreach ($masterTemplates as $master)
                            <button @click="applyMasterTemplate({{ $master->id }})"
                                class="w-full inline-flex items-center justify-between px-4 py-2 text-sm border border-gray-200 dark:border-slate-800 rounded-lg hover:border-blue-500 hover:text-blue-600 transition-all duration-200 dark:border-slate-700">
                                <span>{{ $master->name }}</span>
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4 dark:text-slate-100">Hızlı İşlemler
                    </h3>
                    <div class="space-y-2">
                        <button @click="showAddModal = true"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Özellik Ekle
                        </button>
                        <a href="{{ route('admin.property-hub.templates.index') }}"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200 dark:text-slate-300">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Geri Dön
                        </a>
                    </div>
                </div>

                {{-- Available Features Count --}}
                <div
                    class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-sm p-6 text-white dark:shadow-none">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-gray-100 dark:bg-slate-900 rounded-lg">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ $availableFeatures->count() }}</p>
                            <p class="text-sm text-blue-100">Eklenebilir Özellik</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Category Specific Tab Content --}}
        <div x-show="activeDashboardTab === 'category'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Left Sidebar: Category Selection --}}
            <div class="lg:col-span-4 space-y-4">
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-4">
                    <h3 class="font-semibold text-gray-900 dark:text-slate-100 mb-3">Kategori Seçimi</h3>

                    {{-- Main Category Select --}}
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Ana Kategori</label>
                        <select x-model="pivotSelectedMainCategory"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="">Seçiniz...</option>
                            <template x-for="cat in pivotCategories" :key="cat.id">
                                <option :value="cat.id" x-text="cat.name"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Sub Categories List --}}
                    <div x-show="pivotSelectedMainCategory" class="space-y-1 max-h-[60vh] overflow-y-auto">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Alt
                            Kategoriler</label>
                        <template
                            x-for="cat in pivotCategories.find(c => c.id == pivotSelectedMainCategory)?.alt_kategoriler || []"
                            :key="cat.id">
                            <button @click="pivotSelectedSubCategory = cat.id"
                                :class="pivotSelectedSubCategory == cat.id ?
                                    'bg-purple-50 border-purple-200 text-purple-700 dark:bg-purple-900/20 dark:border-purple-800 dark:text-purple-300' :
                                    'hover:bg-gray-50 dark:hover:bg-gray-800 border-transparent text-gray-600 dark:text-gray-400'"
                                class="w-full text-left px-3 py-2 rounded-lg border text-sm transition-colors flex items-center justify-between">
                                <span x-text="cat.name"></span>
                                <svg x-show="pivotSelectedSubCategory == cat.id" class="h-4 w-4 text-purple-600"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Right Content: Features --}}
            <div class="lg:col-span-8 space-y-4">
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 min-h-[400px]">

                    {{-- Header --}}
                    <div
                        class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
                                <span
                                    x-text="pivotSelectedSubCategory ? (pivotCategories.find(c => c.id == pivotSelectedMainCategory)?.alt_kategoriler.find(s => s.id == pivotSelectedSubCategory)?.name || 'Kategori') : 'Kategori Seçin'"></span>
                            </h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bu alt kategoriye özel özellikler</p>
                        </div>
                        <button x-show="pivotSelectedSubCategory" @click="showAddModal = true"
                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition-all duration-200">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Özellik Ekle
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="p-6">
                        <div x-show="!pivotSelectedSubCategory"
                            class="text-center py-12 text-gray-500 dark:text-gray-400">
                            <svg class="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            Özellikleri yönetmek için soldan bir alt kategori seçin.
                        </div>

                        <div x-show="pivotSelectedSubCategory && pivotLoading" class="flex justify-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-purple-600"></div>
                        </div>

                        <div x-show="pivotSelectedSubCategory && !pivotLoading">
                            <template x-if="pivotAssignments.length === 0">
                                <div
                                    class="text-center py-8 text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800/50 rounded-lg">
                                    Bu kategoriye özel atanmış ekstra özellik yok.
                                    <br>Genel şablon özellikleri geçerlidir.
                                </div>
                            </template>

                            <div class="space-y-2">
                                <template x-for="assignment in pivotAssignments" :key="assignment.id">
                                    <div
                                        class="px-4 py-3 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 rounded-lg flex items-center justify-between group hover:shadow-sm transition-all">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="p-2 bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 rounded-lg">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-medium text-gray-900 dark:text-slate-100"
                                                    x-text="assignment.feature.name"></h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400"
                                                    x-text="assignment.feature.slug"></p>
                                            </div>
                                        </div>

                                        <button @click="removePivotFeature(assignment.feature_id)"
                                            class="text-gray-400 hover:text-red-500 transition-colors p-2">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- AI Suggestions Modal --}}
            <template x-teleport="body">
                <div x-show="showAiModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="showAiModal = false"></div>

                        <div
                            class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                            <div
                                class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-purple-50 dark:bg-purple-900/20 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-purple-100 dark:bg-purple-800 rounded-lg">
                                            <svg class="h-6 w-6 text-purple-600 dark:text-purple-300" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
                                                Cortex AI
                                                Önerileri</h2>
                                            <p class="text-sm text-purple-600 dark:text-purple-400">Yapay zeka destekli
                                                özellik önerileri</p>
                                        </div>
                                    </div>
                                    <button @click="showAiModal = false"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="flex-1 overflow-y-auto p-6" id="ai-modal-content">
                                <!-- Loading State -->
                                <div x-show="loadingSuggestions" class="flex flex-col items-center justify-center py-12">
                                    <div
                                        class="animate-spin rounded-full h-12 w-12 border-4 border-purple-500 border-t-transparent mb-4">
                                    </div>
                                    <p class="text-purple-600 dark:text-purple-400 font-medium">Öneriler hazırlanıyor...
                                    </p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Sektörel veriler analiz ediliyor
                                    </p>
                                </div>

                                <!-- Content -->
                                <div x-show="!loadingSuggestions">
                                    <template x-if="suggestions.length === 0">
                                        <div class="text-center py-12">
                                            <div
                                                class="inline-flex p-4 bg-gray-100 dark:bg-gray-700 rounded-full mb-4 dark:bg-slate-900">
                                                <svg class="h-8 w-8 text-gray-400" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                            <h3 class="text-lg font-medium text-gray-900 mb-1 dark:text-slate-100">
                                                Öneri Bulunamadı</h3>
                                            <p class="text-gray-500 dark:text-gray-400">Şu an için uygun bir öneri
                                                bulunmuyor.
                                            </p>
                                        </div>
                                    </template>

                                    <template x-if="suggestions.length > 0">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <template x-for="suggestion in suggestions" :key="suggestion.feature_id">
                                                <div :class="{
                                                    'ring-2 ring-purple-500 bg-purple-50 dark:bg-purple-900/10': selectedSuggestions
                                                        .includes(suggestion.feature_id)
                                                }"
                                                    @click="toggleSuggestion(suggestion.feature_id)"
                                                    class="cursor-pointer group relative p-4 bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 hover:shadow-md transition-all duration-200 dark:border-slate-700">

                                                    <div class="absolute top-4 right-4">
                                                        <div :class="selectedSuggestions.includes(suggestion.feature_id) ?
                                                            'bg-purple-500 border-purple-500' :
                                                            'bg-white dark:bg-slate-900 border-gray-300 dark:border-gray-600'"
                                                            class="h-6 w-6 rounded-full border-2 flex items-center justify-center transition-colors duration-200">
                                                            <svg x-show="selectedSuggestions.includes(suggestion.feature_id)"
                                                                class="h-4 w-4 text-white" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                        </div>
                                                    </div>

                                                    <div class="pr-8">
                                                        <div class="flex items-center gap-2 mb-2">
                                                            <span class="px-2 py-0.5 rounded text-xs font-semibold"
                                                                :class="{
                                                                    'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400': suggestion
                                                                        .score >= 80,
                                                                    'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400': suggestion
                                                                        .score >= 50 && suggestion.score < 80,
                                                                    'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': suggestion
                                                                        .score < 50
                                                                }">
                                                                %<span x-text="suggestion.score"></span> Güven
                                                            </span>
                                                            <span class="text-xs text-gray-500 dark:text-gray-400"
                                                                x-text="suggestion.category_name"></span>
                                                        </div>
                                                        <h3 class="text-base font-medium text-gray-900 mb-1 dark:text-slate-100"
                                                            x-text="suggestion.feature_name"></h3>
                                                        <p class="text-sm text-gray-500 dark:text-gray-400 line-clamp-2"
                                                            x-text="suggestion.reason"></p>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div
                                class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-slate-800 flex justify-end gap-3 dark:bg-slate-900 dark:border-slate-700">
                                <button @click="showAiModal = false"
                                    class="px-4 py-2 text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200 dark:text-slate-300">
                                    Vazgeç
                                </button>
                                <button @click="applySuggestions()" :disabled="selectedSuggestions.length === 0"
                                    class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                    <span x-text="selectedSuggestions.length"></span> Özellik Ekle
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- Add Feature Modal --}}
            <template x-teleport="body">
                <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-black/50 dark:bg-black/70" @click="showAddModal = false"></div>

                        <div
                            class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
                                        <span
                                            x-text="activeDashboardTab === 'category' ? 'Kategoriye Özellik Ekle' : 'Özellik Ekle'"></span>
                                    </h2>
                                    <button @click="showAddModal = false"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="mt-2">
                                    <input type="text" x-model="featureSearch" placeholder="Özellik ara..."
                                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                                </div>
                            </div>

                            <div class="p-6 overflow-y-auto max-h-[60vh]">
                                @if ($availableFeatures->count() > 0)
                                    <div class="space-y-2">
                                        @foreach ($availableFeatures->groupBy(fn($f) => $f->category?->name ?? 'Genel') as $categoryName => $features)
                                            <div class="mb-4">
                                                <h4
                                                    class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                                                    {{ $categoryName }}
                                                </h4>
                                                <div class="space-y-1">
                                                    @foreach ($features as $feature)
                                                        <button
                                                            @click="activeDashboardTab === 'category' ? addPivotFeature({{ $feature->id }}) : addFeature({{ $feature->id }})"
                                                            x-show="'{{ strtolower($feature->name) }}'.includes(featureSearch.toLowerCase()) || featureSearch === ''"
                                                            :class="{
                                                                'opacity-50 cursor-not-allowed': activeDashboardTab === 'category' &&
                                                                    pivotAssignments.some(a => a.feature_id ==
                                                                        {{ $feature->id }})
                                                            }"
                                                            :disabled="activeDashboardTab === 'category' && pivotAssignments.some(
                                                                a => a.feature_id == {{ $feature->id }})"
                                                            class="w-full flex items-center gap-3 px-4 py-3 text-left rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-all duration-200">
                                                            <div class="flex-1">
                                                                <span
                                                                    class="font-medium text-gray-900 dark:text-slate-100">{{ $feature->name }}</span>
                                                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                                                    {{ $feature->slug }}</p>
                                                            </div>
                                                            @php
                                                                $typeColors = [
                                                                    'boolean' =>
                                                                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                                                    'text' =>
                                                                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                                                    'number' =>
                                                                        'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400',
                                                                    'select' =>
                                                                        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400',
                                                                ];
                                                                $typeColor =
                                                                    $typeColors[$feature->type ?? 'text'] ??
                                                                    'bg-gray-100 text-gray-800';
                                                            @endphp
                                                            <span class="px-2 py-1 text-xs rounded {{ $typeColor }}">
                                                                {{ ucfirst($feature->type ?? 'text') }}
                                                            </span>
                                                            <svg class="h-5 w-5 text-gray-400" fill="none"
                                                                viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M12 4v16m8-8H4" />

                                                            </svg>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <p class="text-gray-500 dark:text-gray-400">Tüm özellikler zaten atanmış</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            {{-- AI ile Tasarla -- Vanilla JS Modal --}}
            @include('admin.property-hub.templates._ai-design-modal')

            {{-- Pipeline AI Progress Modal --}}
            <template x-teleport="body">
                <div x-show="showPipelineModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto"
                    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                    <div class="flex items-center justify-center min-h-screen px-4">
                        <div class="fixed inset-0 bg-black/50 dark:bg-black/70"
                            @click="!pipelineRunning && (showPipelineModal = false)"></div>

                        <div
                            class="relative bg-white dark:bg-slate-900 rounded-xl shadow-xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                            {{-- Header --}}
                            <div
                                class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-emerald-50 dark:bg-emerald-900/20">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-emerald-100 dark:bg-emerald-800 rounded-lg">
                                            <svg class="h-6 w-6 text-emerald-600 dark:text-emerald-300" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
                                                Pipeline AI Önerileri
                                            </h2>
                                            <p class="text-sm text-emerald-600 dark:text-emerald-400">
                                                Governance onaylı akıllı öneri motoru
                                            </p>
                                        </div>
                                    </div>
                                    <button @click="showPipelineModal = false" x-show="!pipelineRunning"
                                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- Pipeline Progress --}}
                            <div class="flex-1 overflow-y-auto p-6">
                                {{-- Step Progress --}}
                                <div x-show="pipelineRunning || pipelineSteps.length > 0" class="mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Pipeline
                                            İlerlemesi</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400"
                                            x-text="pipelineCompletedSteps + '/' + pipelineTotalSteps + ' adım'"></span>
                                    </div>

                                    {{-- Progress Bar --}}
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 mb-4">
                                        <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500"
                                            :style="'width: ' + (pipelineTotalSteps > 0 ? Math.round((pipelineCompletedSteps /
                                                pipelineTotalSteps) * 100) : 0) + '%'">
                                        </div>
                                    </div>

                                    {{-- Step List --}}
                                    <div class="space-y-2">
                                        <template x-for="step in pipelineMainSteps" :key="step.adim_adi">
                                            <div class="flex items-center gap-3 px-3 py-2 rounded-lg"
                                                :class="{
                                                    'bg-emerald-50 dark:bg-emerald-900/20': step
                                                        .adim_durumu === 'completed',
                                                    'bg-blue-50 dark:bg-blue-900/20 animate-pulse': step
                                                        .adim_durumu === 'running',
                                                    'bg-red-50 dark:bg-red-900/20': step.adim_durumu === 'failed',
                                                    'bg-gray-50 dark:bg-gray-800': step.adim_durumu === 'pending',
                                                }">
                                                {{-- Icon --}}
                                                <div class="flex-shrink-0">
                                                    <template x-if="step.adim_durumu === 'completed'">
                                                        <svg class="h-5 w-5 text-emerald-500" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </template>
                                                    <template x-if="step.adim_durumu === 'running'">
                                                        <div
                                                            class="animate-spin rounded-full h-5 w-5 border-2 border-blue-500 border-t-transparent">
                                                        </div>
                                                    </template>
                                                    <template x-if="step.adim_durumu === 'failed'">
                                                        <svg class="h-5 w-5 text-red-500" fill="none"
                                                            viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                        </svg>
                                                    </template>
                                                    <template x-if="step.adim_durumu === 'pending'">
                                                        <div
                                                            class="h-5 w-5 rounded-full border-2 border-gray-300 dark:border-gray-600">
                                                        </div>
                                                    </template>
                                                </div>
                                                {{-- Name --}}
                                                <span class="text-sm font-medium"
                                                    :class="{
                                                        'text-emerald-700 dark:text-emerald-400': step
                                                            .adim_durumu === 'completed',
                                                        'text-blue-700 dark:text-blue-400': step
                                                            .adim_durumu === 'running',
                                                        'text-red-700 dark:text-red-400': step
                                                            .adim_durumu === 'failed',
                                                        'text-gray-500 dark:text-gray-400': step
                                                            .adim_durumu === 'pending',
                                                    }"
                                                    x-text="pipelineStepLabel(step.adim_adi)"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- Decision Result --}}
                                <div x-show="pipelineDecision" class="mb-6">
                                    <div class="rounded-lg p-4 border"
                                        :class="{
                                            'bg-emerald-50 border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800': pipelineDecision === 'proceed',
                                            'bg-amber-50 border-amber-200 dark:bg-amber-900/20 dark:border-amber-800': pipelineDecision === 'proceed_with_caution',
                                            'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800': pipelineDecision === 'block',
                                        }">
                                        <div class="flex items-center gap-2 mb-1">
                                            <template x-if="pipelineDecision === 'proceed'">
                                                <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </template>
                                            <template x-if="pipelineDecision === 'proceed_with_caution'">
                                                <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                                </svg>
                                            </template>
                                            <template x-if="pipelineDecision === 'block'">
                                                <svg class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            </template>
                                            <span class="font-semibold text-sm"
                                                :class="{
                                                    'text-emerald-800 dark:text-emerald-300': pipelineDecision === 'proceed',
                                                    'text-amber-800 dark:text-amber-300': pipelineDecision === 'proceed_with_caution',
                                                    'text-red-800 dark:text-red-300': pipelineDecision === 'block',
                                                }"
                                                x-text="pipelineDecisionLabel(pipelineDecision)"></span>
                                        </div>
                                        <p class="text-xs text-gray-600 dark:text-gray-400" x-show="pipelineReason"
                                            x-text="pipelineReason"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-500 mt-1" x-show="pipelineDuration"
                                            x-text="'Süre: ' + pipelineDuration + 'ms'"></p>
                                    </div>
                                </div>

                                {{-- Final Output / Suggestions --}}
                                <div x-show="pipelineOutput && pipelineDecision !== 'block'">
                                    <h3 class="text-base font-semibold text-gray-900 dark:text-slate-100 mb-3">Önerilen
                                        Değişiklikler</h3>

                                    <template x-if="pipelineOutput && pipelineOutput.suggestions">
                                        <div class="space-y-3">
                                            <template x-for="(suggestion, index) in pipelineOutput.suggestions"
                                                :key="index">
                                                <div
                                                    class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                                                    <div class="flex-shrink-0 mt-0.5">
                                                        <input type="checkbox" :value="index"
                                                            x-model="selectedPipelineSuggestions"
                                                            class="rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 dark:border-gray-600 dark:bg-gray-700">
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 dark:text-slate-100"
                                                            x-text="suggestion.title || suggestion.name"></p>
                                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                                                            x-text="suggestion.reason || suggestion.description"></p>
                                                    </div>
                                                    <span class="flex-shrink-0 text-xs px-2 py-0.5 rounded-full"
                                                        :class="{
                                                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': (
                                                                suggestion.confidence || 0) >= 80,
                                                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': (
                                                                suggestion.confidence || 0) >= 50 && (suggestion
                                                                .confidence || 0) < 80,
                                                            'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': (
                                                                suggestion.confidence || 0) < 50,
                                                        }"
                                                        x-text="'%' + (suggestion.confidence || '-')"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Raw output fallback --}}
                                    <template x-if="pipelineOutput && !pipelineOutput.suggestions">
                                        <div
                                            class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                            <pre class="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap"
                                                x-text="JSON.stringify(pipelineOutput, null, 2)"></pre>
                                        </div>
                                    </template>
                                </div>

                                {{-- Blocked state --}}
                                <div x-show="pipelineDecision === 'block'" class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-red-400" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-slate-100">Pipeline
                                        Durduruldu</h3>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Governance katmanı bu öneriyi
                                        riskli buldu.</p>
                                    <p class="mt-1 text-xs text-red-600 dark:text-red-400" x-text="pipelineReason"></p>
                                </div>

                                {{-- Empty / waiting state --}}
                                <div x-show="!pipelineRunning && !pipelineDecision && pipelineSteps.length === 0"
                                    class="text-center py-12">
                                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Pipeline başlatılıyor...</p>
                                </div>
                            </div>

                            {{-- Footer --}}
                            <div class="px-6 py-4 bg-gray-50 dark:bg-slate-900 border-t border-gray-200 dark:border-slate-700 flex justify-end gap-3"
                                x-show="!pipelineRunning">
                                <button @click="showPipelineModal = false"
                                    class="px-4 py-2 text-gray-700 dark:text-slate-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200">
                                    Kapat
                                </button>
                                <button @click="applyPipelineSuggestions()"
                                    x-show="pipelineOutput && pipelineDecision !== 'block' && pipelineOutput.suggestions"
                                    :disabled="selectedPipelineSuggestions.length === 0"
                                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2">
                                    <span x-text="selectedPipelineSuggestions.length"></span> Öneriyi Uygula
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    @push('scripts')
        <script>
            function templateEditor() {
                return {
                    showAddModal: false,
                    showAiModal: false,
                    featureSearch: '',
                    kategoriId: {{ $kategori->id }},
                    yayinTipiId: {{ $yayinTipi->id }},
                    suggestions: [],
                    selectedSuggestions: [],
                    loadingSuggestions: false,
                    // Pipeline AI state
                    showPipelineModal: false,
                    pipelineRunning: false,
                    pipelineRunUuid: null,
                    pipelineSteps: [],
                    pipelineCompletedSteps: 0,
                    pipelineTotalSteps: 6,
                    pipelineDecision: null,
                    pipelineReason: null,
                    pipelineOutput: null,
                    pipelineDuration: null,
                    pipelinePollTimer: null,
                    selectedPipelineSuggestions: [],

                    // Pivot / Category Specific Logic
                    activeDashboardTab: 'global',
                    pivotCategories: @json($allCategories),
                    pivotSelectedMainCategory: null,
                    pivotSelectedSubCategory: null,
                    pivotAssignments: [],
                    pivotLoading: false,
                    pivotSearch: '',
                    pivotSubCategories: [],

                    init() {
                        this.$watch('pivotSelectedMainCategory', (value) => {
                            if (value) {
                                // Filter subcategories locally? Or assuming pivotCategories structure...
                                // structure is IlanKategori model.
                                // Actually, $allCategories is `IlanKategori::where('seviye', 0)->get()`.
                                // I need subcategories.
                                // Let's check IlanKategori model. Usually it has `children` or `altKategoriler`.
                                // If I used `with('altKategoriler')` in controller it would be better.
                            }
                            this.pivotSelectedSubCategory = null;
                            this.pivotAssignments = [];
                        });

                        this.$watch('pivotSelectedSubCategory', (value) => {
                            if (value) {
                                this.loadPivotAssignments(value);
                            } else {
                                this.pivotAssignments = [];
                            }
                        });
                    },

                    async loadPivotAssignments(subCategoryId) {
                        this.pivotLoading = true;
                        try {
                            const url =
                                '{{ route('admin.property-hub.templates.pivot-assignments') }}';
                            const params = new URLSearchParams({
                                yayin_tipi_id: {{ $yayinTipi->id }},
                                alt_kategori_id: subCategoryId
                            });

                            const response = await fetch(`${url}?${params.toString()}`);
                            const data = await response.json();

                            if (data.success) {
                                this.pivotAssignments = data.data;
                            } else {
                                this.pivotAssignments = [];
                                console.error('Pivot yükleme hatası:', data.message);
                            }
                        } catch (error) {
                            console.error('Pivot yükleme hatası:', error);
                            this.pivotAssignments = [];
                        } finally {
                            this.pivotLoading = false;
                        }
                    },

                    async savePivotAssignmentsLocal(newFeatureIds) {
                        this.pivotLoading = true;
                        try {
                            const url =
                                '{{ route('admin.property-hub.templates.save-pivot-assignments') }}';

                            const response = await fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    yayin_tipi_id: {{ $yayinTipi->id }},
                                    alt_kategori_id: this.pivotSelectedSubCategory,
                                    feature_ids: newFeatureIds
                                })
                            });

                            const data = await response.json();

                            if (data.success) {
                                // Reload to ensure sync
                                this.loadPivotAssignments(this.pivotSelectedSubCategory);
                            } else {
                                alert('Hata: ' + data.message);
                            }
                        } catch (error) {
                            console.error('Kaydetme hatası:', error);
                            alert('Bir hata oluştu.');
                        } finally {
                            this.pivotLoading = false;
                        }
                    },

                    addPivotFeature(featureId) {
                        if (!this.pivotSelectedSubCategory) {
                            alert('Lütfen önce bir alt kategori seçin.');
                            return;
                        }

                        const currentIds = this.pivotAssignments.map(a => a.feature_id);
                        if (currentIds.includes(featureId)) {
                            alert('Bu özellik zaten ekli.');
                            return;
                        }

                        const newIds = [...currentIds, featureId];
                        this.savePivotAssignmentsLocal(newIds);
                    },

                    removePivotFeature(featureId) {
                        if (!confirm('Bu özelliği bu kategoriden kaldırmak istediğinize emin misiniz?')) return;

                        const currentIds = this.pivotAssignments.map(a => a.feature_id);
                        const newIds = currentIds.filter(id => id !== featureId);
                        this.savePivotAssignmentsLocal(newIds);
                    },

                    async applyMasterTemplate(templateId) {
                        if (!confirm(
                                'Bu master şablondaki özellikler mevcut şablonunuza eklenecektir. Devam edilsin mi?')) {
                            return;
                        }

                        try {
                            const response = await fetch('{{ route('admin.property-hub.templates.apply-master') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    master_template_id: templateId,
                                    yayin_tipi_id: this.yayinTipiId
                                })
                            });
                            const data = await response.json();
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert('Hata: ' + data.message);
                            }
                        } catch (error) {
                            console.error('Master Template Error:', error);
                            alert('Şablon uygulanırken bir hata oluştu.');
                        }
                    },

                    async openAiModal() {
                        this.showAiModal = true;
                        if (this.suggestions.length === 0) {
                            await this.fetchSuggestions();
                        }
                    },

                    async fetchSuggestions() {
                        this.loadingSuggestions = true;
                        try {
                            const params = new URLSearchParams({
                                kategori_id: this.kategoriId,
                                yayin_tipi_id: this.yayinTipiId
                            });
                            const response = await fetch('{{ route('admin.property-hub.suggestions.get') }}?' + params
                                .toString());
                            const data = await response.json();

                            if (data.success) {
                                this.suggestions = data.data;
                            } else {
                                alert('Öneriler alınırken bir hata oluştu: ' + (data.message || ''));
                            }
                        } catch (error) {
                            console.error('AI Error:', error);
                            alert('Bağlantı hatası oluştu');
                        } finally {
                            this.loadingSuggestions = false;
                        }
                    },

                    toggleSuggestion(featureId) {
                        if (this.selectedSuggestions.includes(featureId)) {
                            this.selectedSuggestions = this.selectedSuggestions.filter(id => id !== featureId);
                        } else {
                            this.selectedSuggestions.push(featureId);
                        }
                    },

                    async applySuggestions() {
                        if (this.selectedSuggestions.length === 0) return;

                        try {
                            const response = await fetch('{{ route('admin.property-hub.templates.bulk-assign') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    yayin_tipi_id: this.yayinTipiId,
                                    feature_ids: this.selectedSuggestions
                                })
                            });

                            const data = await response.json();

                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || 'Bir hata oluştu');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Bir hata oluştu');
                        }
                    },

                    async addFeature(featureId) {
                        try {
                            const response = await fetch('{{ route('admin.property-hub.templates.assign') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    yayin_tipi_id: this.yayinTipiId,
                                    feature_id: featureId
                                })
                            });

                            const data = await response.json();

                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || 'Bir hata oluştu');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Bir hata oluştu');
                        }
                    },

                    async removeFeature(assignmentId) {
                        if (!confirm('Bu özelliği şablondan kaldırmak istediğinize emin misiniz?')) {
                            return;
                        }

                        try {
                            const response = await fetch('{{ route('admin.property-hub.templates.unassign') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    assignment_id: assignmentId
                                })
                            });

                            const data = await response.json();

                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || 'Bir hata oluştu');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            alert('Bir hata oluştu');
                        }
                    },

                    // ─── Pipeline AI Methods ─────────────────────────

                    async startPipeline(pipelineType) {
                        if (this.pipelineRunning) return;

                        this.pipelineRunning = true;
                        this.showPipelineModal = true;
                        this.pipelineSteps = [];
                        this.pipelineDecision = null;
                        this.pipelineReason = null;
                        this.pipelineOutput = null;
                        this.pipelineDuration = null;
                        this.pipelineCompletedSteps = 0;
                        this.selectedPipelineSuggestions = [];

                        const tStart = performance.now();

                        try {
                            const response = await fetch('{{ route('admin.property-hub.templates.ai-pipeline.start') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    kategori_id: this.kategoriId,
                                    yayin_tipi_id: this.yayinTipiId,
                                    pipeline_type: pipelineType,
                                    description: null,
                                })
                            });

                            const data = await response.json();

                            if (data.success && data.run_uuid) {
                                this.pipelineRunUuid = data.run_uuid;
                                this.pollPipeline();
                            } else {
                                this.pipelineRunning = false;
                                alert('Pipeline başlatılamadı: ' + (data.message || 'Bilinmeyen hata'));
                            }

                            this.sendTelemetry('template_pipeline_start', {
                                duration_ms: Math.round(performance.now() - tStart),
                                basarili: !!data.success,
                                pipeline_type: pipelineType,
                            });
                        } catch (error) {
                            this.pipelineRunning = false;
                            console.error('Pipeline start error:', error);
                            alert('Pipeline başlatılırken bağlantı hatası oluştu');
                            this.sendTelemetry('template_pipeline_start', {
                                duration_ms: Math.round(performance.now() - tStart),
                                basarili: false,
                                hata_mesaji: error.message,
                            });
                        }
                    },

                    pollPipeline() {
                        if (!this.pipelineRunUuid) return;

                        const pollUrl =
                            '{{ route('admin.property-hub.templates.ai-pipeline.poll', ['runUuid' => '__UUID__']) }}'
                            .replace('__UUID__', this.pipelineRunUuid);

                        const doPoll = async () => {
                            try {
                                const response = await fetch(pollUrl);
                                const data = await response.json();

                                if (!data.success) {
                                    this.stopPolling();
                                    this.pipelineRunning = false;
                                    return;
                                }

                                // Update progress
                                this.pipelineSteps = data.steps || [];
                                this.pipelineCompletedSteps = data.completed_steps || 0;
                                this.pipelineTotalSteps = data.total_steps || 6;

                                // Terminal state
                                if (data.is_terminal) {
                                    this.stopPolling();
                                    this.pipelineRunning = false;
                                    this.pipelineDecision = data.karar_aksiyonu;
                                    this.pipelineReason = data.karar_gerekcesi;
                                    this.pipelineOutput = data.final_output;
                                    this.pipelineDuration = data.duration_ms;

                                    this.sendTelemetry('template_pipeline_complete', {
                                        duration_ms: data.duration_ms || 0,
                                        basarili: data.karar_aksiyonu !== 'block',
                                        karar: data.karar_aksiyonu,
                                    });
                                }
                            } catch (error) {
                                console.error('Pipeline poll error:', error);
                                // Don't stop polling on network glitch, retry
                            }
                        };

                        // Initial poll immediately
                        doPoll();

                        // Then poll every 2 seconds
                        this.pipelinePollTimer = setInterval(doPoll, 2000);
                    },

                    stopPolling() {
                        if (this.pipelinePollTimer) {
                            clearInterval(this.pipelinePollTimer);
                            this.pipelinePollTimer = null;
                        }
                    },

                    get pipelineMainSteps() {
                        // Filter out shard sub-steps, show only main pipeline steps
                        return this.pipelineSteps.filter(s => !s.shard_key);
                    },

                    pipelineStepLabel(stepName) {
                        const labels = {
                            'normalize': 'Girdi Doğrulama',
                            'audit': 'Analiz & Denetim',
                            'fix': 'Öneri Üretimi',
                            'execution': 'Uygulama Planı',
                            'verification': 'Doğrulama',
                            'govern': 'Governance Kararı',
                        };
                        return labels[stepName] || stepName;
                    },

                    pipelineDecisionLabel(decision) {
                        const labels = {
                            'proceed': 'Onaylandı — Güvenli',
                            'proceed_with_caution': 'Dikkatli Devam — Uyarılar Var',
                            'block': 'Reddedildi — Riskli',
                        };
                        return labels[decision] || decision;
                    },

                    async applyPipelineSuggestions() {
                        if (!this.pipelineOutput?.suggestions || this.selectedPipelineSuggestions.length === 0) return;

                        const featureIds = this.selectedPipelineSuggestions
                            .map(idx => this.pipelineOutput.suggestions[idx]?.feature_id)
                            .filter(Boolean);

                        if (featureIds.length === 0) {
                            alert('Seçili önerilerde uygulanabilir özellik bulunamadı.');
                            return;
                        }

                        try {
                            const response = await fetch('{{ route('admin.property-hub.templates.bulk-assign') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    yayin_tipi_id: this.yayinTipiId,
                                    feature_ids: featureIds
                                })
                            });

                            const data = await response.json();
                            if (data.success) {
                                window.location.reload();
                            } else {
                                alert(data.message || 'Uygulama sırasında hata oluştu.');
                            }
                        } catch (error) {
                            console.error('Apply pipeline suggestions error:', error);
                            alert('Bağlantı hatası oluştu');
                        }
                    },

                    sendTelemetry(event, extra = {}) {
                        if (!window.location.pathname.includes('/admin/')) return;

                        // TODO(P2-FE-04): centralize telemetry endpoint into APIConfig
                        fetch('/admin/telemetry', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify({
                                event: event,
                                trace_id: crypto.randomUUID?.() ?? `fe-${Date.now()}`,
                                basarili: extra.basarili ?? true,
                                http_durum_kodu: extra.http_durum_kodu ?? 200,
                                duration_ms: extra.duration_ms ?? 0,
                                context: {
                                    ...extra,
                                    page: 'property_hub_template_edit',
                                    kategori_id: this.kategoriId,
                                    yayin_tipi_id: this.yayinTipiId,
                                    timestamp: new Date().toISOString()
                                }
                            })
                        }).catch(() => {});
                    }
                }
            }
        </script>
    @endpush
@endsection
