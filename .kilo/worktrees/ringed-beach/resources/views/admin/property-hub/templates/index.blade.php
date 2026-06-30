@extends('admin.layouts.admin')

@section('title', 'Şablonlar - Property Hub')

@section('content')
    <div x-data="templateManager()" class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                    <a href="{{ route('admin.property-hub.index') }}"
                        class="hover:text-blue-600 transition-all duration-200">Property Hub</a>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span>Şablonlar</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Şablon Yönetimi</h1>
                <p class="mt-1 text-gray-500 dark:text-gray-400">Kategori ve yayın tipine göre özellik atamalarını yönetin
                </p>
            </div>

            {{-- Live Search --}}
            <div class="w-full md:w-96">
                <div class="relative">
                    <input type="text" x-model="search" placeholder="Şablon veya kategori ara..."
                        class="w-full pl-10 pr-4 py-2 rounded-xl border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 shadow-sm transition-all duration-200 dark:shadow-none">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div
                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-4 dark:shadow-none">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                            {{ $kategoriler->count() }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Kategori</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 p-4 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                            {{ $templates->count() }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Yayın Tipi (Master)</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 p-4 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">
                            {{ $templates->sum('feature_assignments_count') }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Toplam Atama</p>
                    </div>
                </div>
            </div>

            <div
                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 p-4 dark:shadow-none dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                        <svg class="h-6 w-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div>
                        @php
                            $totalCount = $templates->count();
                            $totalAssignments = $templates->sum('feature_assignments_count');
                            $avgFeatures = $totalCount > 0 ? round($totalAssignments / $totalCount, 1) : 0;
                        @endphp
                        <p class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $avgFeatures }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Ort. Özellik/Şablon</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Master Templates List --}}
        <div class="space-y-6">
            <div
                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-8 bg-blue-500 rounded-full"></div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100">
                                    Master Şablonlar
                                </h2>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $templates->count() }} Sistem Şablonu
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($templates as $yayinTipi)
                            <div x-show="matchesTemplateSearch('{{ $yayinTipi->name ?? '' }}', '{{ $yayinTipi->slug ?? '' }}')"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                data-template-name="{{ $yayinTipi->name ?? '' }}"
                                data-template-slug="{{ $yayinTipi->slug ?? '' }}"
                                class="group relative bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-4 hover:shadow-md hover:border-blue-500 dark:hover:border-blue-400 transition-all duration-200">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 flex items-center gap-2 dark:text-slate-100">
                                            {{ $yayinTipi->name ?? 'Yayın Tipi' }}
                                            @if ($yayinTipi->feature_assignments_count == 0)
                                                <span class="relative flex h-2 w-2">
                                                    <span
                                                        class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                                    <span
                                                        class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                                </span>
                                            @endif
                                        </h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mt-1">
                                            {{ $yayinTipi->slug }}
                                        </p>
                                    </div>

                                    @php
                                        $count = $yayinTipi->feature_assignments_count;
                                        $badgeClass = match (true) {
                                            $count == 0
                                                => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400 border-red-200 dark:border-red-800',
                                            $count < 20
                                                => 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 border-orange-200 dark:border-orange-800',
                                            $count < 50
                                                => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200 dark:border-blue-800',
                                            default
                                                => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 border-green-200 dark:border-green-800',
                                        };
                                        $statusText = match (true) {
                                            $count == 0 => 'Boş',
                                            $count < 20 => 'Zayıf',
                                            $count < 50 => 'İyi',
                                            default => 'Zengin',
                                        };
                                    @endphp
                                    <div class="flex flex-col items-end gap-1">
                                        <span
                                            class="px-2.5 py-0.5 text-xs font-bold rounded-full border {{ $badgeClass }}">
                                            {{ $count }}
                                        </span>
                                        <span
                                            class="text-[10px] text-gray-400 uppercase tracking-wider font-semibold">{{ $statusText }}</span>
                                    </div>
                                </div>

                                <div class="flex gap-2">
                                    <a href="{{ route('admin.property-hub.templates.edit', ['kategori_id' => 0, 'yayin_tipi_id' => $yayinTipi->id]) }}"
                                        class="flex-1 flex items-center justify-center gap-2 py-2 bg-gray-50 hover:bg-blue-600 hover:text-white text-gray-700 rounded-lg transition-all duration-200 text-sm font-medium group-hover:shadow-sm dark:bg-slate-900 dark:text-slate-300">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        Düzenle
                                    </a>
                                    <button
                                        @click="openAiModal({{ $yayinTipi->id }}, 'Master', '{{ $yayinTipi->name }}')"
                                        aria-label="AI ile Oluştur"
                                        class="px-3 py-2 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-600 hover:text-white text-indigo-700 dark:text-indigo-400 rounded-lg transition-all duration-200 text-sm font-medium">
                                        <i class="fas fa-magic"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- AI Template Generator Modal -->
        <div x-show="isAiModalOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-900/75 backdrop-blur-sm"
                    @click="isAiModalOpen = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-slate-900 rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-200 dark:border-slate-800"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                    <!-- Modal Header -->
                    <div
                        class="px-6 py-4 border-b border-gray-200 flex items-center justify-between bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
                        <div class="flex items-center gap-3">
                            <div
                                class="p-2 bg-indigo-100 dark:bg-indigo-900/50 rounded-lg text-indigo-600 dark:text-indigo-400">
                                <i class="fas fa-robot text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-slate-100">AI ile
                                    Şablon Yapılandırıcı</h3>
                                <p
                                    class="text-[10px] text-gray-500 dark:text-gray-400 uppercase tracking-widest font-bold">
                                    <span x-text="aiSelection.category"></span> / <span x-text="aiSelection.type"></span>
                                </p>
                            </div>
                        </div>
                        <button @click="isAiModalOpen = false"
                            class="text-gray-400 hover:text-gray-500 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-0">
                        <!-- Tabs -->
                        <div class="flex border-b border-gray-200 px-6 dark:border-slate-700">
                            <button @click="activeModalTab = 'preview'"
                                :class="activeModalTab === 'preview' ?
                                    'border-indigo-500 text-indigo-600 dark:text-indigo-400' :
                                    'border-transparent text-gray-500 hover:text-gray-700'"
                                class="py-4 px-4 border-b-2 font-medium text-sm transition-all duration-200 flex items-center gap-2">
                                <i class="fas fa-eye"></i> Önizleme
                            </button>
                            <button @click="activeModalTab = 'debug'"
                                :class="activeModalTab === 'debug' ? 'border-amber-500 text-amber-600 dark:text-amber-400' :
                                    'border-transparent text-gray-500 hover:text-gray-700'"
                                class="py-4 px-4 border-b-2 font-medium text-sm transition-all duration-200 flex items-center gap-2">
                                <i class="fas fa-code"></i> Debug / JSON
                            </button>
                        </div>

                        <div class="p-6">
                            <!-- Preview Tab -->
                            <div x-show="activeModalTab === 'preview'" class="space-y-4">
                                <!-- Empty State -->
                                <div x-show="!aiResult?.zorunlu_alanlar?.length && !aiResult?.opsiyonel_alanlar?.length && !isGenerating && !aiError"
                                    class="flex flex-col items-center justify-center py-12 text-center">
                                    <div
                                        class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/30 rounded-full flex items-center justify-center text-indigo-500 mb-4">
                                        <i class="fas fa-wand-magic-sparkles text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-slate-100">
                                        Şablon Verisi Getirilmedi</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm">
                                        Bu kombinasyon için UPS standart şablonunu çözümlemek için
                                        kategori seçip butona basın.
                                    </p>
                                    <div class="w-full max-w-xs mx-auto mt-4">
                                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-300 mb-1">Alt
                                            Kategori</label>
                                        <select x-model="aiSelection.altKategoriId"
                                            class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            <option value="">Kategori seçin...</option>
                                            @foreach ($kategoriler as $kat)
                                                <option value="{{ $kat->id }}">{{ $kat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button @click="generateAiTemplate" :disabled="!aiSelection.altKategoriId"
                                        :class="!aiSelection.altKategoriId ? 'opacity-50 cursor-not-allowed' :
                                            'hover:bg-indigo-700'"
                                        class="mt-4 px-6 py-2 bg-indigo-600 text-white rounded-xl shadow-lg shadow-indigo-500/30 transition-all font-semibold flex items-center gap-2">
                                        <i class="fas fa-magic"></i>
                                        UPS Şablonunu Getir
                                    </button>
                                </div>

                                <!-- Loading State -->
                                <div x-show="isGenerating"
                                    class="flex flex-col items-center justify-center py-12 text-center">
                                    <div class="relative w-16 h-16 mb-4">
                                        <div
                                            class="absolute inset-0 rounded-full border-4 border-indigo-100 dark:border-indigo-900">
                                        </div>
                                        <div
                                            class="absolute inset-0 rounded-full border-4 border-indigo-600 border-t-transparent animate-spin">
                                        </div>
                                    </div>
                                    <p class="text-indigo-600 dark:text-indigo-400 font-bold animate-pulse">UPS STANDARDI
                                        ANALİZ EDİLİYOR...</p>
                                </div>

                                <!-- Error State -->
                                <div x-show="aiError && !isGenerating"
                                    class="flex flex-col items-center justify-center py-12 text-center">
                                    <div
                                        class="w-16 h-16 bg-red-50 dark:bg-red-900/30 rounded-full flex items-center justify-center text-red-500 mb-4">
                                        <i class="fas fa-exclamation-triangle text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white">AI İşlem Hatası</h4>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 max-w-sm mt-2" x-text="aiError">
                                    </p>
                                    <button @click="generateAiTemplate"
                                        class="mt-6 px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl shadow-lg shadow-red-500/30 transition-all font-semibold flex items-center gap-2">
                                        <i class="fas fa-redo"></i>
                                        Tekrar Dene
                                    </button>
                                </div>

                                <!-- Success State -->
                                <div x-show="(aiResult?.zorunlu_alanlar?.length || aiResult?.opsiyonel_alanlar?.length) && !isGenerating && !aiError"
                                    class="space-y-6">
                                    <!-- Info Cards -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div
                                            class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800">
                                            <p
                                                class="text-[10px] text-blue-600 dark:text-blue-400 font-bold uppercase mb-2">
                                                Zorunlu Alanlar <span
                                                    x-text="`(${(aiResult?.zorunlu_alanlar || []).length})`"
                                                    class="font-mono"></span></p>
                                            <div class="flex flex-wrap gap-1.5">
                                                <template x-for="field in (aiResult?.zorunlu_alanlar || [])"
                                                    :key="field">
                                                    <span
                                                        class="px-2 py-0.5 bg-white dark:bg-slate-900 text-blue-800 dark:text-blue-200 text-[10px] rounded-md border border-blue-200 dark:border-blue-800 shadow-sm dark:shadow-none"
                                                        x-text="field"></span>
                                                </template>
                                                <template x-if="(aiResult?.zorunlu_alanlar || []).length === 0">
                                                    <span class="text-xs text-blue-400 italic">Veri yok</span>
                                                </template>
                                            </div>
                                        </div>
                                        <div
                                            class="p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl border border-indigo-100 dark:border-indigo-800">
                                            <p
                                                class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold uppercase mb-2">
                                                Opsiyonel Alanlar <span
                                                    x-text="`(${(aiResult?.opsiyonel_alanlar || []).length})`"
                                                    class="font-mono"></span></p>
                                            <div class="flex flex-wrap gap-1.5">
                                                <template x-for="field in (aiResult?.opsiyonel_alanlar || [])"
                                                    :key="field">
                                                    <span
                                                        class="px-2 py-0.5 bg-white dark:bg-slate-900 text-indigo-800 dark:text-indigo-200 text-[10px] rounded-md border border-indigo-200 dark:border-indigo-800 shadow-sm dark:shadow-none"
                                                        x-text="field"></span>
                                                </template>
                                                <template x-if="(aiResult?.opsiyonel_alanlar || []).length === 0">
                                                    <span class="text-xs text-indigo-400 italic">Veri yok</span>
                                                </template>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Validation Rules -->
                                    <div
                                        class="bg-gray-50 dark:bg-slate-800 rounded-xl p-5 border border-gray-200 dark:border-slate-700">
                                        <div class="flex items-center gap-2 mb-4">
                                            <div class="w-1.5 h-4 bg-indigo-500 rounded-full"></div>
                                            <p class="text-xs text-gray-500 dark:text-gray-400 font-bold uppercase">
                                                Validasyon ve İş Kuralları <span
                                                    x-text="`(${Object.keys(aiResult?.validasyon_kurallari || {}).length})`"
                                                    class="font-mono"></span></p>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                                            <template x-for="(rules, field) in (aiResult?.validasyon_kurallari || {})"
                                                :key="field">
                                                <div class="flex items-start gap-2 text-sm group">
                                                    <span
                                                        class="font-mono text-[11px] text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/50 px-1.5 py-0.5 rounded"
                                                        x-text="field"></span>
                                                    <span class="text-gray-600 dark:text-gray-400 text-xs italic mt-0.5"
                                                        x-text="rules"></span>
                                                </div>
                                            </template>
                                            <template
                                                x-if="Object.keys(aiResult?.validasyon_kurallari || {}).length === 0">
                                                <span class="text-xs text-gray-400 italic col-span-2">Validasyon kuralı
                                                    bulunamadı</span>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- UI Hints -->
                                    <div
                                        class="bg-amber-50 dark:bg-amber-900/10 rounded-xl p-4 border border-amber-100 dark:border-amber-900/30">
                                        <div class="flex items-center gap-2 mb-2">
                                            <i class="fas fa-lightbulb text-amber-500"></i>
                                            <p class="text-[10px] text-amber-700 dark:text-amber-400 font-bold uppercase">
                                                Arayüz İpuçları <span
                                                    x-text="`(${Object.keys(aiResult?.ui_ipuclari || {}).length})`"
                                                    class="font-mono"></span></p>
                                        </div>
                                        <ul
                                            class="list-disc list-inside text-xs text-amber-800 dark:text-amber-300 space-y-1">
                                            <template x-for="(hint, key) in (aiResult?.ui_ipuclari || {})"
                                                :key="key">
                                                <li x-text="key + ': ' + hint"></li>
                                            </template>
                                            <template x-if="Object.keys(aiResult?.ui_ipuclari || {}).length === 0">
                                                <li class="text-amber-400 italic">İpucu bulunamadı</li>
                                            </template>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Debug Tab -->
                            <div x-show="activeModalTab === 'debug'" class="space-y-4">
                                <div class="flex items-center justify-between mb-2">
                                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono italic">Raw JSON structure
                                        for UPS Template Generator</p>
                                    <button @click="copyToClipboard"
                                        class="flex items-center gap-2 text-xs text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 font-bold">
                                        <i class="fas fa-copy"></i> JSON Kopyala
                                    </button>
                                </div>
                                <div class="relative">
                                    <pre class="w-full h-96 p-4 font-mono text-[11px] bg-gray-950 text-indigo-300 rounded-xl border border-indigo-900 shadow-inner overflow-auto scrollbar-thin scrollbar-thumb-indigo-900 scrollbar-track-transparent"
                                        x-text="JSON.stringify(aiResult, null, 2)"></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between dark:bg-slate-900 dark:border-slate-700">
                        <button @click="resetAi"
                            class="text-xs text-red-600 dark:text-red-400 hover:text-red-700 transition-colors font-bold flex items-center gap-2">
                            <i class="fas fa-trash-alt"></i> Sıfırla
                        </button>
                        <div class="flex gap-3">
                            <button @click="isAiModalOpen = false"
                                class="px-5 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-xl transition-all dark:text-slate-300">
                                Vazgeç
                            </button>
                            <button @click="saveAiTemplate" :disabled="!aiResult || isSaving"
                                :class="(!aiResult || isSaving) ? 'opacity-50 cursor-not-allowed bg-indigo-400' :
                                'hover:bg-indigo-700 shadow-indigo-500/30 bg-indigo-600 hover:-translate-y-0.5 active:translate-y-0'"
                                class="px-6 py-2 text-sm font-bold text-white rounded-xl shadow-lg transition-all flex items-center gap-2">
                                <template x-if="isSaving">
                                    <i class="fas fa-spinner animate-spin"></i>
                                </template>
                                <template x-if="!isSaving">
                                    <i class="fas fa-check-double"></i>
                                </template>
                                Şablonu Uygula
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    @push('scripts')
        <script>
            function templateManager() {
                return {
                    search: '',
                    isAiModalOpen: false,
                    isGenerating: false,
                    isSaving: false,
                    activeModalTab: 'preview',
                    aiSelection: {
                        id: null,
                        category: '',
                        type: '',
                        altKategoriId: null
                    },
                    aiResult: {
                        zorunlu_alanlar: [],
                        opsiyonel_alanlar: [],
                        validasyon_kurallari: {},
                        ui_ipuclari: {}
                    },
                    aiError: null,
                    requestInFlight: false,
                    requestId: 0,

                    init() {
                        // Telemetry: Page open
                        this.sendTelemetry('property_hub_templates_open', {
                            duration_ms: 0,
                            basarili: true
                        });
                    },

                    matchesSearch(categoryName, typeNames) {
                        if (this.search === '') return true;
                        const query = this.search.toLowerCase();
                        if (categoryName.toLowerCase().includes(query)) return true;
                        return typeNames.some(name => name.toLowerCase().includes(query));
                    },

                    matchesTemplateSearch(name, slug) {
                        if (!this.search || this.search.trim() === '') return true;
                        const query = this.search.toLowerCase().trim();
                        return (name || '').toLowerCase().includes(query) ||
                            (slug || '').toLowerCase().includes(query);
                    },

                    openAiModal(id, category, type) {
                        this.aiSelection = {
                            id,
                            category,
                            type,
                            altKategoriId: null
                        };
                        this.aiResult = {
                            zorunlu_alanlar: [],
                            opsiyonel_alanlar: [],
                            validasyon_kurallari: {},
                            ui_ipuclari: {}
                        };
                        this.aiError = null;
                        this.isAiModalOpen = true;
                        this.activeModalTab = 'preview';
                        // Telemetry: Modal open
                        this.sendTelemetry('property_hub_templates_edit_open', {
                            template_id: id,
                            template_type: type,
                            basarili: true
                        });
                    },

                    async generateAiTemplate() {
                        // Race condition guard
                        if (this.requestInFlight) {
                            if (window.toastr) toastr.warning('İşlem devam ediyor, lütfen bekleyin...');
                            return;
                        }

                        this.requestInFlight = true;
                        const currentRequestId = ++this.requestId;
                        this.isGenerating = true;
                        this.aiError = null;

                        const startTime = performance.now();

                        // Telemetry: AI start
                        this.sendTelemetry('property_hub_templates_ai_start', {
                            template_id: this.aiSelection.id,
                            basarili: true
                        });

                        try {
                            const response = await fetch(
                                `/admin/ai/property/generate-template/${this.aiSelection.id}`, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                    },
                                    body: JSON.stringify({
                                        alt_kategori_id: parseInt(this.aiSelection.altKategoriId)
                                    })
                                });

                            // Request ID check (ignore stale responses)
                            if (currentRequestId !== this.requestId) {
                                console.warn('Stale AI response ignored (race condition prevented)');
                                return;
                            }

                            const duration_ms = Math.round(performance.now() - startTime);

                            if (!response.ok) {
                                const httpDurumKodu = response.status; // Context7: Extract before telemetry
                                let err = {};
                                try {
                                    err = await response.json();
                                } catch (_parseErr) {
                                    // Server returned non-JSON (e.g. HTML 500 page)
                                    err = {
                                        message: `Sunucu hatası (HTTP ${httpDurumKodu})`
                                    };
                                }
                                this.aiError = err.message || 'Sistem hatası';
                                // Telemetry: AI fail
                                this.sendTelemetry('property_hub_templates_ai_fail', {
                                    duration_ms,
                                    http_durum_kodu: httpDurumKodu,
                                    basarili: false,
                                    hata_mesaji: this.aiError
                                });
                                throw new Error(this.aiError);
                            }

                            const result = await response.json();
                            if (result.success && result.output) {
                                this.aiResult = {
                                    zorunlu_alanlar: result.output.zorunlu_alanlar || [],
                                    opsiyonel_alanlar: result.output.opsiyonel_alanlar || [],
                                    validasyon_kurallari: result.output.validasyon_kurallari || {},
                                    ui_ipuclari: result.output.ui_ipuclari || {}
                                };
                                // Telemetry: AI success
                                this.sendTelemetry('property_hub_templates_ai_done', {
                                    duration_ms,
                                    http_durum_kodu: 200,
                                    basarili: true,
                                    field_count: (this.aiResult.zorunlu_alanlar.length + this.aiResult
                                        .opsiyonel_alanlar.length)
                                });
                                if (window.toastr) toastr.success('AI analiz tamamlandı');
                            } else {
                                this.aiError = result.message || 'Veri alınamadı';
                                if (window.toastr) toastr.warning(this.aiError);
                            }
                        } catch (error) {
                            this.aiError = error.message || 'AI şablonu üretilemedi.';
                            // Defensive: ensure aiResult is never null after error
                            if (!this.aiResult || !this.aiResult.zorunlu_alanlar) {
                                this.aiResult = {
                                    zorunlu_alanlar: [],
                                    opsiyonel_alanlar: [],
                                    validasyon_kurallari: {},
                                    ui_ipuclari: {}
                                };
                            }
                            if (window.toastr) toastr.error(this.aiError);
                        } finally {
                            this.isGenerating = false;
                            this.requestInFlight = false;
                        }
                    },

                    async saveAiTemplate() {
                        this.isSaving = true;
                        try {
                            if (window.toastr) toastr.info('Şablon mühürleniyor...');

                            const response = await fetch('/admin/property-hub/templates/ai-import', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    junction_id: this.aiSelection.id,
                                    ups_json: this.aiResult,
                                    should_seal: true
                                })
                            });

                            if (!response.ok) {
                                let err = {};
                                try {
                                    err = await response.json();
                                } catch (_parseErr) {
                                    err = {
                                        message: `Sunucu hatası (HTTP ${response.status})`
                                    };
                                }
                                throw new Error(err.message || 'Mühürleme hatası');
                            }

                            const result = await response.json();
                            if (result.success) {
                                if (window.toastr) toastr.success(result.message);
                                this.isAiModalOpen = false;
                                // Sayfayı yenileyerek yeni şablon durumunu görebiliriz
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                if (window.toastr) toastr.warning(result.message);
                            }
                        } catch (error) {
                            if (window.toastr) toastr.error(error.message || 'Kaydetme sırasında hata oluştu.');
                        } finally {
                            this.isSaving = false;
                        }
                    },

                    copyToClipboard() {
                        if (!this.aiResult) return;
                        const text = JSON.stringify(this.aiResult, null, 2);
                        navigator.clipboard.writeText(text).then(() => {
                            if (window.toastr) toastr.info('JSON verisi panoya kopyalandı.');
                        });
                    },

                    resetAi() {
                        this.aiResult = {
                            zorunlu_alanlar: [],
                            opsiyonel_alanlar: [],
                            validasyon_kurallari: {},
                            ui_ipuclari: {}
                        };
                        this.aiError = null;
                        if (window.toastr) toastr.info('Arama ve analiz verileri sıfırlandı.');
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
                                    page: 'property_hub_templates',
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
