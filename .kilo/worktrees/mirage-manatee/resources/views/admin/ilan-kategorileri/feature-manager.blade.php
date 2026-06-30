@extends('admin.layouts.admin')

@section('title', 'Kategori Özellikleri Yöneticisi: ' . $kategori->name)
@section('meta_description', 'İlan kategorisinin özelliklerini yönetin, düzenleyin ve öncelikleri belirleyin.')
@section('meta_keywords', 'kategori, özellik, yönetim, kategorileri')

@section('content')
    <div class="container mx-auto px-4 py-6" x-data="featureManager()">
        <!-- Breadcrumb -->
        <div class="mb-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="mb-2 text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                        Kategori Özellikleri: <span class="text-blue-600 dark:text-blue-400">{{ $kategori->name }}</span>
                    </h1>
                </div>
                <div>
                    <a href="{{ route('admin.ilan-kategorileri.index') }}"
                        class="inline-flex items-center rounded-lg bg-gray-200 px-4 py-2 text-gray-800 transition-colors hover:bg-gray-300 dark:bg-gray-700 dark:text-slate-200 dark:hover:bg-gray-600">
                        <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Geri Dön
                    </a>
                </div>
            </div>

            <!-- Kategori Bilgisi ve Hiyerarşi -->
            <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-3">
                <!-- Kategori Kartı -->
                <div
                    class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-slate-700 dark:bg-slate-900 dark:shadow-none md:col-span-1">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100">Kategori Bilgisi</h3>

                    <div class="space-y-3 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Ad:</span>
                            <span class="block font-medium text-gray-900 dark:text-slate-100">{{ $kategori->name }}</span>
                        </div>

                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Seviye:</span>
                            <span class="block font-medium text-gray-900 dark:text-slate-100">
                                <span class="inline-block rounded px-2 py-1 text-xs font-semibold"
                                    :style="{ 'background-color': getSeviyeColor('{{ $kategori->seviye }}') }"
                                    class="text-white">
                                    {{ \App\Models\IlanKategori::getSeviyeAciklamalari()[$kategori->seviye] ?? 'Bilinmeyen' }}
                                </span>
                            </span>
                        </div>

                        @if ($kategori->parent)
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Üst Kategori:</span>
                                <a href="{{ route('admin.ilan-kategorileri.feature-manager', $kategori->parent_id) }}"
                                    class="block font-medium text-blue-600 hover:underline dark:text-blue-400">
                                    {{ $kategori->parent->name }}
                                </a>
                            </div>
                        @endif

                        <div>
                            <span class="text-gray-500 dark:text-gray-400">Durum:</span>
                            <span class="block font-medium text-gray-900 dark:text-slate-100">
                                @php $kategoriAktifDurumu = $kategori->aktiflik_durumu ?? 0; @endphp
                                <span
                                    class="{{ $kategoriAktifDurumu ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' }} inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium">
                                    {{ $kategoriAktifDurumu ? 'Aktif' : 'Pasif' }}
                                </span>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Hiyerarşi Gösterimi -->
                <div
                    class="rounded-lg border border-gray-200 bg-white p-6 shadow dark:border-slate-700 dark:bg-slate-900 dark:shadow-none md:col-span-2">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100">Kategori Hiyerarşisi</h3>

                    <div class="space-y-2">
                        @php
                            $path = [];
                            $current = $kategori;
                            while ($current) {
                                array_unshift($path, $current);
                                $current = $current->parent;
                            }
                        @endphp

                        @foreach ($path as $index => $cat)
                            <div class="flex items-center">
                                <div class="flex-1">
                                    <div class="inline-block rounded-lg px-3 py-2"
                                        :style="{ 'background-color': getSeviyeColorLight('{{ optional($cat)->seviye }}') }"
                                        class="text-sm font-medium">
                                        {{ optional($cat)->name }}
                                    </div>
                                </div>

                                @if ($index < count($path) - 1)
                                    <svg class="mx-2 h-5 w-5 text-gray-400 dark:text-gray-600" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                @endif

                                @if (optional($cat)->id !== optional($kategori)->id)
                                    <a href="{{ route('admin.ilan-kategorileri.feature-manager', optional($cat)->id) }}"
                                        class="ml-2 text-xs font-medium text-blue-600 hover:underline dark:text-blue-400">
                                        Yönet
                                    </a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- 🤖 AI Suggestions Panel (Collapsible) --}}
            @include('admin.ilan-kategorileri.feature-manager-ai')

            {{-- 🔍 Search & Filter Bar --}}
            <div
                class="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <div
                    class="flex flex-col space-y-3 md:flex-row md:items-center md:justify-between md:space-x-4 md:space-y-0">
                    <div class="flex-1">
                        <label for="search" class="sr-only">Özellik Ara</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input type="text" x-model="searchQuery" @input="filterFeatures()" id="search"
                                class="block w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-3 text-gray-900 placeholder-gray-400 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:bg-slate-900 dark:text-slate-100 sm:text-sm"
                                placeholder="Özellik ara... (isim, tür, öncelik)">
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button @click="loadAISuggestions()" :disabled="loadingAI"
                            class="inline-flex items-center rounded-lg border border-transparent bg-gradient-to-r from-purple-600 to-blue-600 px-4 py-2 text-sm font-medium text-white transition-all hover:from-purple-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50">
                            <svg class="mr-2 h-4 w-4" :class="{ 'animate-spin': loadingAI }" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                            </svg>
                            <span x-text="loadingAI ? 'Yükleniyor...' : 'AI Önerileri'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Özellik Ekleme Modalı (Premium Redesign) -->
            <div x-show="showAddForm" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 pb-10" x-transition:enter-end="opacity-100 pb-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" style="display: none;"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-gray-900/40 p-4 backdrop-blur-sm"
                aria-labelledby="modal-title" role="dialog" aria-modal="true" @keydown.escape.window="showAddForm = false">

                <div class="relative w-full max-w-lg transform overflow-hidden rounded-3xl border border-white/20 bg-white shadow-2xl transition-all dark:border-gray-700/50 dark:bg-slate-900"
                    @click.away="showAddForm = false">

                    {{-- Premium Header Gradient --}}
                    <div
                        class="relative flex h-24 items-center overflow-hidden bg-gradient-to-r from-blue-600 to-indigo-600 px-8 dark:from-blue-500 dark:to-indigo-500">
                        <div class="absolute -right-8 -top-8 h-32 w-32 rounded-full bg-white blur-2xl dark:bg-slate-900">
                        </div>
                        <div class="absolute -bottom-4 -left-4 h-24 w-24 rounded-full bg-blue-400 blur-xl dark:bg-blue-900">
                        </div>

                        <div class="relative flex items-center gap-4">
                            <div
                                class="flex h-12 w-12 items-center justify-center rounded-2xl border border-white bg-white text-white shadow-inner backdrop-blur-md dark:border-slate-800 dark:bg-slate-900">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-xl font-black tracking-tight text-white" id="modal-title">Yeni Özellik
                                    Ekle</h3>
                                <p
                                    class="text-xs font-medium uppercase tracking-widest text-blue-100 opacity-80 dark:text-blue-300">
                                    {{ $kategori->name }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-8">
                        <form id="assignFeatureForm"
                            action="{{ route('admin.property_types.assign_feature', ['propertyTypeId' => $kategori->id]) }}"
                            method="POST" @submit.prevent="submitFeature">
                            @csrf

                            {{-- Feature Selection --}}
                            <div class="mb-8">
                                <label for="feature_id"
                                    class="mb-3 block text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Özellik
                                    Seçin</label>
                                <div class="group relative">
                                    <select name="feature_id" id="feature_id"
                                        class="block w-full cursor-pointer appearance-none rounded-2xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-bold text-gray-900 transition-all focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10 group-hover:border-gray-200 dark:border-slate-800 dark:bg-gray-800/50 dark:bg-slate-900 dark:text-slate-100 dark:group-hover:border-gray-600"
                                        :disabled="isSubmitting" required>
                                        <option value="">Lütfen seçim yapın...</option>
                                        @foreach ($availableFeatures as $f)
                                            <option value="{{ $f->id }}">{{ $f->name }}
                                                ({{ $f->type }})</option>
                                        @endforeach
                                    </select>
                                    <div
                                        class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                                <p x-show="errors.feature_id" x-text="errors.feature_id ? errors.feature_id[0] : ''"
                                    class="mt-2 text-sm font-bold text-red-500"></p>
                            </div>

                            {{-- Visual Toggles --}}
                            <div class="mb-8 grid grid-cols-2 gap-6">
                                <div class="relative">
                                    <label
                                        class="mb-3 block text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Zorunluluk</label>
                                    <label class="group flex cursor-pointer items-center">
                                        <input id="is_required" name="is_required" type="checkbox" value="1"
                                            :disabled="isSubmitting" class="peer sr-only">
                                        <div
                                            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3 text-gray-500 transition-all group-hover:border-gray-200 peer-checked:border-red-400 peer-checked:bg-red-50 peer-checked:text-red-600 dark:border-slate-800 dark:bg-gray-800/50 dark:bg-slate-900 dark:group-hover:border-gray-600 dark:peer-checked:bg-red-900/20">
                                            <div class="h-2 w-2 rounded-full bg-gray-300 peer-checked:bg-red-500"></div>
                                            <span class="text-sm font-black">Zorunlu</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="relative">
                                    <label
                                        class="mb-3 block text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Görünürlük</label>
                                    <label class="group flex cursor-pointer items-center">
                                        <input id="is_visible" name="is_visible" type="checkbox" value="1" checked
                                            :disabled="isSubmitting" class="peer sr-only">
                                        <div
                                            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-gray-100 bg-gray-50 px-4 py-3 text-gray-500 transition-all group-hover:border-gray-200 peer-checked:border-green-400 peer-checked:bg-green-50 peer-checked:text-green-600 dark:border-slate-800 dark:bg-gray-800/50 dark:bg-slate-900 dark:group-hover:border-gray-600 dark:peer-checked:bg-green-900/20">
                                            <div class="h-2 w-2 rounded-full bg-gray-300 peer-checked:bg-green-500"></div>
                                            <span class="text-sm font-black">Görünür</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            {{-- Display Order --}}
                            <div class="mb-10">
                                <label for="display_order"
                                    class="mb-3 block text-xs font-black uppercase tracking-widest text-gray-400 dark:text-gray-500">Görüntüleme
                                    Sırası</label>
                                <div class="flex items-center gap-4">
                                    <input type="number" name="display_order" id="display_order"
                                        :disabled="isSubmitting"
                                        class="w-32 rounded-2xl border-2 border-gray-100 bg-gray-50 px-5 py-4 font-black text-gray-900 transition-all focus:border-blue-500 focus:outline-none focus:ring-4 focus:ring-blue-500/10 dark:border-slate-800 dark:bg-gray-800/50 dark:bg-slate-900 dark:text-slate-100"
                                        value="0">
                                    <p class="mt-1 text-xs font-medium italic text-gray-400">Daha düşük rakamlar üstte
                                        listelenir.</p>
                                </div>
                            </div>
                        </form>
                    </div>

                    {{-- Actions Container --}}
                    <div
                        class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50 p-8 dark:border-slate-800 dark:bg-slate-900 sm:flex-row-reverse">
                        <button type="submit" form="assignFeatureForm" :disabled="isSubmitting"
                            class="inline-flex flex-1 transform items-center justify-center rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-4 font-black text-white shadow-lg shadow-blue-500/30 transition-all hover:scale-[1.02] hover:shadow-blue-600/40 active:scale-95 disabled:opacity-50">
                            <svg x-show="isSubmitting" class="-ml-1 mr-3 h-5 w-5 animate-spin text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            <span x-text="isSubmitting ? 'İşleniyor...' : 'Özelliği Ata'">Özelliği Ata</span>
                        </button>
                        <button type="button" @click="showAddForm = false" :disabled="isSubmitting"
                            class="transform rounded-2xl border-2 border-gray-100 bg-white px-8 py-4 font-black text-gray-600 transition-all hover:bg-gray-50 active:scale-95 disabled:opacity-50 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-gray-700">
                            İptal
                        </button>
                    </div>
                </div>
            </div>

            <!-- Özellikler Tablosu -->
            <div
                class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-slate-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-slate-100">Atanan Özellikler</h2>
                    <button @click="toggleAddFeature()"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800">
                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Özellik Ekle
                    </button>
                </div>

                @if (isset($features) && $features->isNotEmpty())
                    <div class="divide-y divide-gray-200 dark:divide-gray-800">
                        @foreach ($features->groupBy('priority') as $priority => $priorityFeatures)
                            <div class="bg-gray-50 px-6 py-3 dark:bg-slate-900">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-slate-100">
                                    @if ($priority === 'required')
                                        🔴 Zorunlu Özellikler ({{ $priorityFeatures->count() }})
                                    @elseif($priority === 'recommended')
                                        🟡 Önerilen Özellikler ({{ $priorityFeatures->count() }})
                                    @else
                                        🟢 İsteğe Bağlı Özellikler ({{ $priorityFeatures->count() }})
                                    @endif
                                </h4>
                            </div>

                            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                                @foreach ($priorityFeatures as $assignment)
                                    <div class="px-6 py-4 transition-colors hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h5 class="font-medium text-gray-900 dark:text-slate-100">
                                                    {{ $assignment->feature->name ?? 'Özellik Bulunamadı' }}
                                                </h5>
                                                @if ($assignment->feature && $assignment->feature->description)
                                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                        {{ $assignment->feature->description }}</p>
                                                @endif
                                                <div
                                                    class="mt-2 flex items-center space-x-4 text-xs text-gray-600 dark:text-gray-400">
                                                    @if ($assignment->feature)
                                                        <span>Tür: <span
                                                                class="font-medium">{{ ucfirst($assignment->feature->type) }}</span></span>
                                                        @if ($assignment->feature->options)
                                                            <span>Seçenekler: <span
                                                                    class="font-medium">{{ count(json_decode($assignment->feature->options, true) ?? []) }}</span></span>
                                                        @endif
                                                    @endif
                                                    <span>Öncelik: <span
                                                            class="font-medium">{{ ucfirst($assignment->priority ?? 'optional') }}</span></span>
                                                </div>
                                            </div>

                                            <div class="ml-4 flex items-center space-x-2">
                                                <button @click="editFeature({{ $assignment->id }})"
                                                    class="rounded p-2 text-blue-600 transition-colors hover:bg-blue-50 hover:text-blue-700 dark:text-blue-400 dark:hover:bg-blue-900/20 dark:hover:text-blue-300"
                                                    title="Düzenle">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </button>

                                                <button @click="deleteFeature({{ $assignment->id }})"
                                                    class="rounded p-2 text-red-600 transition-colors hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-900/20 dark:hover:text-red-300"
                                                    title="Sil">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="relative overflow-hidden px-6 py-20 text-center">
                        {{-- Decorative background elements --}}
                        <div
                            class="absolute -left-24 -top-24 h-64 w-64 rounded-full bg-blue-500/5 blur-3xl dark:bg-blue-500/10">
                        </div>
                        <div
                            class="absolute -bottom-24 -right-24 h-64 w-64 rounded-full bg-purple-500/5 blur-3xl dark:bg-purple-500/10">
                        </div>

                        <div class="relative">
                            <div
                                class="mx-auto mb-6 flex h-24 w-24 transform items-center justify-center rounded-3xl border border-gray-200/50 bg-gradient-to-br from-gray-50 to-gray-100 shadow-inner transition-transform duration-500 hover:scale-110 dark:border-gray-700/50 dark:from-gray-800 dark:to-gray-900">
                                <svg class="h-12 w-12 text-blue-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                </svg>
                            </div>

                            <h3 class="mb-3 text-2xl font-black tracking-tight text-gray-900 dark:text-slate-100">Henüz
                                Özellik Tanımlanmamış</h3>
                            <p class="mx-auto mb-10 max-w-sm text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                                Bu kategoriye ait ilanlar şu an boş görünüyor. Hemen ilk özelliği ekleyerek ilan deneyimini
                                başlatabilir veya AI önerilerinden faydalanabilirsiniz.
                            </p>

                            <div class="flex flex-col items-center justify-center gap-4 sm:flex-row">
                                <button @click="toggleAddFeature()"
                                    class="group relative inline-flex items-center rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-4 font-black text-white shadow-xl shadow-blue-500/25 transition-all duration-300 hover:-translate-y-1 hover:shadow-blue-500/40">
                                    <svg class="mr-3 h-5 w-5 transition-transform duration-300 group-hover:rotate-90"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    İlk Özelliği Ekle
                                </button>

                                <button @click="loadAISuggestions()"
                                    class="inline-flex items-center rounded-2xl border-2 border-gray-100 bg-white px-8 py-4 font-bold text-gray-700 shadow-sm transition-all duration-300 hover:border-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:shadow-none dark:hover:border-blue-500">
                                    <i class="fas fa-magic mr-3 text-purple-500"></i>
                                    AI Önerilerini Gör
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Template Inheritance Bilgisi -->
            <div class="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800/30 dark:bg-blue-900/20">
                <div class="flex items-start">
                    <svg class="mr-3 mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-11-1a1 1 0 11-2 0 1 1 0 012 0zM8 7a1 1 0 000 2h6a1 1 0 000-2H8zm2 3a1 1 0 11-2 0 1 1 0 012 0zm2 0a1 1 0 100-2 1 1 0 000 2z"
                            clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1">
                        <h4 class="mb-1 font-semibold text-blue-900 dark:text-blue-100">Şablon Mirasları</h4>
                        <p class="text-sm text-blue-800 dark:text-blue-200">
                            @if ($hasCustomTemplate)
                                ✅ Bu kategorinin <strong>özel şablonu vardır</strong>. Özellikleri bu kategoriye özgü olarak
                                tanımlanmıştır.
                            @else
                                ⚠️ Bu kategorinin <strong>özel şablonu yoktur</strong>. Alt kategoriler listelendiğinde,
                                <strong>üst kategorisinin özelliklerini miras alır</strong>.
                                @if ($kategori->parent && !$kategori->parent->hasCustomTemplate())
                                    <br><br>
                                    <span class="font-bold text-red-600 dark:text-red-400">DİKKAT:</span> Üst kategori
                                    (<strong>{{ $kategori->parent->name }}</strong>) de herhangi bir özelliğe sahip değil.
                                    Bu kategori şu an tamamen özellik listesiz görünecek. Özel özellikler tanımlamak için
                                    yukarıdaki "Özellik Ekle" butonunu kullanın.
                                @else
                                    Özel özellikler tanımlamak için yukarıdaki "Özellik Ekle" butonunu kullanın.
                                @endif
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alpine.js Component -->
        <script>
            function featureManager() {
                return {
                    showAddForm: false,
                    showEditForm: false,
                    editingFeature: null,
                    isSubmitting: false,
                    errors: {},

                    // 🤖 AI Suggestions
                    showAISuggestions: false,
                    loadingAI: false,
                    aiSuggestions: [],

                    // 🔍 Search & Filter
                    searchQuery: '',
                    filteredAssignments: [],

                    getSeviyeColor(seviye) {
                        const colors = {
                            '0': '#3b82f6', // blue
                            '1': '#f59e0b', // amber
                            '2': '#10b981' // green
                        };
                        return colors[seviye] || '#6b7280';
                    },

                    getSeviyeColorLight(seviye) {
                        const colors = {
                            '0': '#dbeafe', // light blue
                            '1': '#fef3c7', // light amber
                            '2': '#d1fae5' // light green
                        };
                        return colors[seviye] || '#f3f4f6';
                    },

                    toggleAddFeature() {
                        this.showAddForm = !this.showAddForm;
                        this.errors = {};
                    },

                    // 🤖 Load AI Suggestions
                    async loadAISuggestions() {
                        if (this.loadingAI) return;

                        this.loadingAI = true;
                        this.showAISuggestions = true;

                        try {
                            const response = await fetch(
                                '/admin/ilan-kategorileri/{{ $kategori->id }}/ai-feature-suggestions', {
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json'
                                    }
                                });

                            const data = await response.json();

                            if (response.ok && data.success) {
                                this.aiSuggestions = data.suggestions || [];
                                if (this.aiSuggestions.length === 0) {
                                    this.showNotification('Bu kategori için AI önerisi bulunamadı', 'info');
                                } else {
                                    this.showNotification(`${this.aiSuggestions.length} AI önerisi yüklendi!`, 'success');
                                }
                            } else {
                                this.showNotification(data.message || 'AI önerileri yüklenemedi', 'error');
                                this.aiSuggestions = [];
                            }
                        } catch (error) {
                            console.error('AI Suggestions Error:', error);
                            this.showNotification('AI servisi şu an kullanılamıyor', 'error');
                            this.aiSuggestions = [];
                        } finally {
                            this.loadingAI = false;
                        }
                    },

                    // ⚡ Quick Add from AI Suggestions
                    async quickAddFeature(featureId) {
                        if (this.isSubmitting) return;

                        this.isSubmitting = true;
                        const formData = new FormData();
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                        formData.append('feature_id', featureId);
                        formData.append('is_visible', '1');
                        formData.append('display_order', '0');

                        try {
                            const response = await fetch(
                                '{{ route('admin.property_types.assign_feature', ['propertyTypeId' => $kategori->id]) }}', {
                                    method: 'POST',
                                    body: formData,
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'Accept': 'application/json'
                                    }
                                });

                            const data = await response.json();

                            if (response.ok) {
                                this.showNotification('Özellik başarıyla eklendi!', 'success');
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                this.showNotification(data.message || 'Ekleme başarısız', 'error');
                            }
                        } catch (error) {
                            console.error('Quick Add Error:', error);
                            this.showNotification('Bir hata oluştu', 'error');
                        } finally {
                            this.isSubmitting = false;
                        }
                    },

                    // 🔍 Filter Features
                    filterFeatures() {
                        // This would filter the display - for now just a placeholder
                        // In production, you'd filter the DOM elements based on searchQuery
                        console.log('Filtering features:', this.searchQuery);
                    },

                    // ✅ AJAX Form Submission
                    async submitFeature(event) {
                        event.preventDefault();

                        if (this.isSubmitting) return;

                        this.isSubmitting = true;
                        this.errors = {};

                        const formData = new FormData(event.target);

                        try {
                            const response = await fetch(event.target.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });

                            const data = await response.json();

                            if (response.ok) {
                                this.showNotification('Özellik başarıyla eklendi!', 'success');
                                this.showAddForm = false;
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                if (data.errors) {
                                    this.errors = data.errors;
                                }
                                this.showNotification(data.message || 'Bir hata oluştu!', 'error');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            this.showNotification('Sunucu hatası oluştu!', 'error');
                        } finally {
                            this.isSubmitting = false;
                        }
                    },

                    // ✅ Edit Feature Assignment
                    async editFeature(assignmentId) {
                        try {
                            const response = await fetch(
                            `/admin/property-type-manager/feature-assignment/${assignmentId}`, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });

                            if (response.ok) {
                                const data = await response.json();
                                this.editingFeature = data;
                                this.showEditForm = true;
                            } else {
                                this.showNotification('Özellik bilgileri yüklenemedi!', 'error');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            this.showNotification('Bir hata oluştu!', 'error');
                        }
                    },

                    // ✅ Delete Feature Assignment
                    async deleteFeature(assignmentId) {
                        if (!confirm('Bu özellik atamasını silmek istediğinize emin misiniz?')) {
                            return;
                        }

                        try {
                            const response = await fetch(
                                `/admin/property-type-manager/property-type/{{ $kategori->id }}/unassign-feature`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        assignment_id: assignmentId
                                    })
                                });

                            const data = await response.json();

                            if (response.ok) {
                                this.showNotification('Özellik başarıyla kaldırıldı!', 'success');
                                setTimeout(() => window.location.reload(), 1000);
                            } else {
                                this.showNotification(data.message || 'Silme işlemi başarısız!', 'error');
                            }
                        } catch (error) {
                            console.error('Error:', error);
                            this.showNotification('Bir hata oluştu!', 'error');
                        }
                    },

                    // ✅ Notification Helper
                    showNotification(message, type = 'info') {
                        // Create notification element
                        const notification = document.createElement('div');
                        notification.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg transform transition-all duration-300 ${
                            type === 'success' ? 'bg-green-500 text-white' :
                            type === 'error' ? 'bg-red-500 text-white' :
                            'bg-blue-500 text-white'
                        }`;
                        notification.textContent = message;

                        document.body.appendChild(notification);

                        // Auto remove after 3 seconds
                        setTimeout(() => {
                            notification.style.opacity = '0';
                            setTimeout(() => notification.remove(), 300);
                        }, 3000);
                    }
                }
            }
        </script>
    @endsection
