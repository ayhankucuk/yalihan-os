@extends('admin.layouts.admin')

@section('title', 'Kategori Yönetimi')

@section('content')
    <div class="container mx-auto px-4 py-6" x-data="kategorilerManager()">
        <!-- Sticky Header Summary Bar -->
        <div
            class="sticky top-0 z-10 -mx-4 mb-6 flex flex-col justify-between gap-4 border-b border-gray-200 bg-slate-50/90 px-4 py-3 shadow-sm backdrop-blur-md transition-all duration-300 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900/90 dark:shadow-none sm:flex-row sm:items-center">
            <div class="flex items-center gap-6">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-gray-900 dark:text-slate-100 dark:text-white">Kategori
                        Yönetimi</h1>
                    <!-- Stats & Health Score (Phase 20) -->
                    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-4">
                        <!-- Health Score Card -->
                        <div class="group relative rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none"
                            x-data="{ showHealth: false }">
                            <div class="absolute right-0 top-0 p-4 opacity-10">
                                <svg class="h-24 w-24" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
                                </svg>
                            </div>
                            <div class="relative z-10">
                                <div class="mb-2 flex items-start justify-between">
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Sistem Sağlığı</span>
                                    <button @click="showHealth = !showHealth"
                                        class="text-gray-400 transition-colors hover:text-blue-500">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <span
                                        class="{{ $healthScore['color'] }} text-3xl font-bold">{{ $healthScore['score'] }}</span>
                                    <span class="text-sm text-gray-500">/ 100</span>
                                </div>
                                <div class="{{ $healthScore['color'] }} mt-2 text-sm font-medium">
                                    {{ $healthScore['aktiflik_durumu'] }}</div>
                            </div>

                            <!-- Health Details Popover -->
                            <div x-show="showHealth" @click.away="showHealth = false" x-transition
                                class="absolute left-0 top-full z-20 mt-2 w-full rounded-lg border border-gray-200 bg-white p-4 shadow-xl dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                <h4 class="mb-2 text-sm font-bold text-gray-900 dark:text-slate-100 dark:text-white">Gelişim
                                    Alanları</h4>
                                <ul class="space-y-2">
                                    @forelse($healthScore['deductions'] as $item)
                                        <li class="flex justify-between text-xs text-red-600 dark:text-red-400">
                                            <span>{{ $item['reason'] }}</span>
                                            <span class="font-mono">-{{ $item['points'] }}</span>
                                        </li>
                                    @empty
                                        <li class="text-xs text-green-600 dark:text-green-400">Harika! Sistem tam puan.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>

                        <!-- Enhanced Category Stats -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:col-span-3">
                            <!-- Total Categories -->
                            <div
                                class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                                <div>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Toplam
                                        Kategori</span>
                                    <div class="mt-2 text-2xl font-bold text-gray-900 dark:text-slate-100">
                                        @if ($istatistikler['toplam'] == 0)
                                            <span class="text-lg font-normal text-gray-400">Henüz Eklenmedi</span>
                                        @else
                                            {{ $istatistikler['toplam'] }}
                                        @endif
                                    </div>
                                </div>
                                @if ($istatistikler['toplam'] == 0)
                                    <a href="{{ route('admin.ilan-kategorileri.create') }}"
                                        class="mt-3 inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-700">
                                        İlk Kategoriyi Ekle →
                                    </a>
                                @endif
                            </div>

                            <!-- Active Categories -->
                            <div
                                class="flex flex-col justify-between rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:shadow-none">
                                <div>
                                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktif
                                        Yayınlanan</span>
                                    <div class="mt-2 text-2xl font-bold text-green-600 dark:text-green-400">
                                        @if ($istatistikler['aktif'] == 0 && $istatistikler['toplam'] > 0)
                                            <span class="text-lg font-normal text-yellow-500">Yayında Yok</span>
                                        @else
                                            {{ $istatistikler['aktif'] }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Actions (Safety Guardrails) -->
                            <div
                                class="flex flex-col items-start justify-center gap-3 rounded-xl border border-blue-100 bg-blue-50 p-5 dark:border-blue-800/50 dark:bg-blue-900/20">
                                <span class="text-sm font-medium text-blue-800 dark:text-blue-300">Hızlı İşlemler</span>
                                <a href="{{ route('admin.ilan-kategorileri.create') }}"
                                    class="w-full rounded-lg bg-blue-600 px-4 py-2 text-center text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 dark:shadow-none">
                                    + Yeni Kategori
                                </a>
                                <div class="flex w-full gap-2">
                                    <button disabled
                                        class="flex-1 cursor-not-allowed rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-400 dark:border-slate-700 dark:bg-slate-900"
                                        title="Gelişmiş özellik (Yakında)">
                                        Şablonlar
                                    </button>
                                    <button disabled
                                        class="flex-1 cursor-not-allowed rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-400 dark:border-slate-700 dark:bg-slate-900"
                                        title="Gelişmiş özellik (Yakında)">
                                        Paketler
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('admin.ilan-kategorileri.export') }}"
                    class="p-2 text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    title="Dışa Aktar">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </a>
                <button type="button" @click="toggleSortingMode()"
                    class="p-2 text-gray-500 transition-colors hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    :class="{ 'text-blue-600 bg-blue-50 dark:bg-blue-900/30': sortingMode }" title="Sıralama Modu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                </button>
                <div class="mx-1 h-6 w-px bg-gray-200 dark:bg-gray-700"></div>
                <a href="{{ route('admin.ilan-kategorileri.create') }}" x-show="!sortingMode"
                    class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md dark:shadow-none">
                    <svg class="mr-1.5 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Yeni Ekle
                </a>
                <div x-show="sortingMode" class="flex items-center gap-2">
                    <button @click="cancelSorting()"
                        class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400">İptal</button>
                    <button @click="saveOrders()"
                        class="rounded-lg bg-green-600 px-3 py-1.5 text-sm font-medium text-white shadow-sm hover:bg-green-700 dark:shadow-none">Kaydet</button>
                </div>
            </div>
        </div>

        <!-- Main Card -->
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-900">
            <!-- Filters -->
            <div class="border-b border-gray-200 bg-gray-50 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                <form method="GET" class="flex flex-wrap items-center gap-3" @submit.prevent="submitFilters()">
                    <div class="relative min-w-[200px] flex-1">
                        <svg class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <input type="text" name="search" placeholder="Kategori ara..."
                            class="w-full rounded-lg border border-gray-300 bg-white py-2 pl-10 pr-4 text-sm transition-all duration-200 focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900"
                            x-model="filters.search" value="{{ request('search') }}">
                    </div>

                    <select name="parent_id" x-model="filters.parentId" @change="applyFilters()"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm transition-all duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900">
                        <option value="">Tüm Üst Kategoriler</option>
                        @foreach ($ustKategoriler as $ust)
                            <option value="{{ $ust->id }}">{{ $ust->name }}</option>
                        @endforeach
                    </select>
                    <select name="seviye" x-model="filters.seviye" @change="applyFilters()"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm transition-all duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900">
                        <option value="">Tüm Seviyeler</option>
                        <option value="ana">Ana Kategori</option>
                        <option value="alt">Alt Kategori</option>
                    </select>
                    <select name="aktiflik_durumu" x-model="filters.aktiflikDurumu" @change="applyFilters()"
                        class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm transition-all duration-200 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900">
                        <option value="">Tüm Durumlar</option>
                        <option value="1">Aktif</option>
                        <option value="0">Pasif</option>
                    </select>
                    <button type="button" @click="clearFilters()"
                        class="p-2 text-gray-500 transition-all duration-200 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                        title="Temizle">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </button>
                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-blue-700">
                        Ara
                    </button>
                </form>
            </div>

            <!-- Bulk Actions Bar -->
            <div x-show="selectedItems.length > 0" x-transition
                class="flex items-center gap-4 border-b border-blue-100 bg-blue-50 px-4 py-3 dark:border-blue-800 dark:bg-blue-900/30">
                <span class="text-sm font-medium text-blue-700 dark:text-blue-300">
                    <span x-text="selectedItems.length"></span> öğe seçildi
                </span>
                <div class="flex items-center gap-2">
                    <button @click="bulkAction('activate')"
                        class="rounded-lg bg-green-100 px-3 py-1.5 text-xs font-medium text-green-700 transition-all duration-200 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-300 dark:hover:bg-green-900">
                        Aktifleştir
                    </button>
                    <button @click="bulkAction('deactivate')"
                        class="rounded-lg bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-700 transition-all duration-200 hover:bg-gray-200 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-gray-600">
                        Pasifleştir
                    </button>
                    <button @click="bulkAction('delete')"
                        class="rounded-lg bg-red-100 px-3 py-1.5 text-xs font-medium text-red-700 transition-all duration-200 hover:bg-red-200 dark:bg-red-900/50 dark:text-red-300 dark:hover:bg-red-900">
                        Sil
                    </button>
                </div>
                <button @click="clearSelection()"
                    class="ml-auto text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400">
                    Seçimi Temizle
                </button>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="w-12 px-4 py-3">
                                <input type="checkbox" @change="toggleSelectAll()" :checked="isAllSelected"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 transition-all duration-200 focus:ring-blue-500 dark:border-gray-600">
                            </th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                Kategori</th>
                            <th
                                class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                Üst Kategori</th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                Sıra
                            </th>
                            <th
                                class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                Durum</th>
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">
                                İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse($kategoriler as $kategori)
                            <tr class="transition-all duration-200 hover:bg-gray-50 dark:hover:bg-gray-700/50"
                                :class="{ 'bg-blue-50 dark:bg-blue-900/20': selectedItems.includes({{ $kategori->id }}) }">
                                <td class="px-4 py-3">
                                    <input type="checkbox" value="{{ $kategori->id }}"
                                        @change="toggleItemSelection({{ $kategori->id }})"
                                        :checked="selectedItems.includes({{ $kategori->id }})"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 transition-all duration-200 focus:ring-blue-500 dark:border-gray-600">
                                </td>
                                <td class="px-4 py-3">
                                    <div class="group flex items-center gap-3">
                                        <span
                                            class="text-2xl opacity-80 transition-opacity group-hover:opacity-100">{{ $kategori->icon_emoji }}</span>
                                        <div>
                                            <div
                                                class="text-lg font-bold tracking-tight text-gray-900 dark:text-slate-100">
                                                {{ $kategori->name }}</div>
                                            <div class="mt-0.5 flex items-center gap-2">
                                                <span
                                                    class="{{ $kategori->seviye == 0 ? 'bg-blue-50 text-blue-700 border-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800' : 'bg-gray-50 text-gray-600 border-gray-100 dark:bg-slate-900 dark:text-gray-400 dark:border-gray-700' }} rounded-md border px-2 py-0.5 text-xs font-medium"
                                                    title="{{ $kategori->seviye == 0 ? 'Bu bir Ana Kategoridir. Alt kategoriler içerebilir.' : 'Bu bir Alt Kategoridir. İlanlar bu seviyede eklenir.' }}">
                                                    {{ $kategori->seviye == 0 ? 'Ana Kategori' : 'Alt Kategori' }}
                                                </span>
                                                <span
                                                    class="font-mono text-xs tracking-wide text-gray-400 dark:text-gray-500">/{{ $kategori->slug }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-slate-200">
                                    @if ($kategori->parent)
                                        {{ $kategori->parent->name }}
                                    @else
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <div x-show="!sortingMode">
                                        <span
                                            class="font-mono text-sm text-gray-500">{{ $kategori->display_order }}</span>
                                    </div>
                                    <div x-show="sortingMode">
                                        <input type="number" value="{{ $kategori->display_order }}"
                                            @input="updateDisplayOrder({{ $kategori->id }}, $event.target.value)"
                                            class="w-20 rounded border border-gray-300 px-2 py-1 text-center text-sm focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900">
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button type="button" @click="toggleDurum({{ $kategori->id }})"
                                        class="{{ $kategori->aktiflik_durumu
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300'
                                            : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' }} inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium transition-all duration-200">
                                        <span
                                            class="{{ $kategori->aktiflik_durumu ? 'bg-green-500' : 'bg-gray-400' }} mr-1.5 h-1.5 w-1.5 rounded-full"></span>
                                        {{ $kategori->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3">
                                    <div
                                        class="flex items-center justify-end gap-1 opacity-40 transition-all duration-200 group-hover:opacity-100">
                                        <a href="{{ route('admin.ilan-kategorileri.edit', $kategori) }}"
                                            class="rounded-lg bg-transparent p-2 text-gray-500 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:text-gray-400 dark:hover:bg-blue-900/30 dark:hover:text-blue-400"
                                            title="Düzenle">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </a>
                                        <a href="{{ route('admin.ilan-kategorileri.feature-manager', $kategori) }}"
                                            class="rounded-lg bg-transparent p-2 text-gray-500 transition-colors hover:bg-indigo-50 hover:text-indigo-600 dark:text-gray-400 dark:hover:bg-indigo-900/30 dark:hover:text-indigo-400"
                                            title="Özellikler">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                            </svg>
                                        </a>
                                        <form action="{{ route('admin.ilan-kategorileri.destroy', $kategori) }}"
                                            method="POST" class="inline"
                                            onsubmit="return confirm('{{ addslashes($kategori->name) }} kategorisini silmek istediğinize emin misiniz?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-lg bg-transparent p-2 text-gray-500 transition-colors hover:bg-red-50 hover:text-red-600 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                                title="Sil">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-12 text-center">
                                    <div class="mb-2 text-gray-400 dark:text-gray-500">
                                        <svg class="mx-auto h-12 w-12" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400">Henüz kategori bulunmuyor</p>
                                    <a href="{{ route('admin.ilan-kategorileri.create') }}"
                                        class="mt-4 inline-flex items-center text-sm text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                        <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Yeni Kategori Ekle
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if ($kategoriler->hasPages())
                <div class="border-t border-gray-200 bg-gray-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-900/50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $kategoriler->firstItem() }}-{{ $kategoriler->lastItem() }} / {{ $kategoriler->total() }}
                            kayıt
                        </p>
                        <div>{{ $kategoriler->links() }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
        function kategorilerManager() {
            return {
                loading: false,
                processing: false,
                sortingMode: false,
                modifiedOrders: {},
                selectedItems: [],
                filters: {
                    search: '{{ request('search') }}',
                    parentId: '{{ request('parent_id') }}',
                    seviye: '{{ request('seviye') }}',
                    aktiflikDurumu: '{{ request('aktiflik_durumu', '') }}'
                },

                get isAllSelected() {
                    const total = {{ $kategoriler->count() }};
                    return total > 0 && this.selectedItems.length === total;
                },

                toggleSelectAll() {
                    if (this.isAllSelected) {
                        this.selectedItems = [];
                    } else {
                        this.selectedItems = [
                            @foreach ($kategoriler as $k)
                                {{ $k->id }},
                            @endforeach
                        ];
                    }
                },

                toggleItemSelection(id) {
                    const idx = this.selectedItems.indexOf(id);
                    if (idx > -1) this.selectedItems.splice(idx, 1);
                    else this.selectedItems.push(id);
                },

                clearSelection() {
                    this.selectedItems = [];
                },

                applyFilters() {
                    this.loading = true;
                    const params = new URLSearchParams();
                    if (this.filters.search) params.append('search', this.filters.search);
                    if (this.filters.parentId) params.append('parent_id', this.filters.parentId);
                    if (this.filters.seviye) params.append('seviye', this.filters.seviye);
                    if (this.filters.aktiflikDurumu !== '') params.append('aktiflik_durumu', this.filters.aktiflikDurumu);
                    window.location.href = `{{ route('admin.ilan-kategorileri.index') }}?${params.toString()}`;
                },

                clearFilters() {
                    this.filters = {
                        search: '',
                        parentId: '',
                        seviye: '',
                        aktiflikDurumu: ''
                    };
                    this.applyFilters();
                },

                submitFilters() {
                    this.applyFilters();
                },

                async bulkAction(action) {
                    if (this.selectedItems.length === 0) return;
                    if (!confirm(`Seçili ${this.selectedItems.length} öğe için ${action} işlemi yapılsın mı?`)) return;

                    this.processing = true;
                    try {
                        const response = await fetch('{{ route('admin.ilan-kategorileri.bulk.action') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                action,
                                ids: this.selectedItems
                            })
                        });
                        const res = await response.json();
                        if (res.success) window.location.reload();
                        else alert(res.message || 'Bir hata oluştu');
                    } catch (e) {
                        console.error('Hata:', e);
                    } finally {
                        this.processing = false;
                    }
                },

                async toggleDurum(id) {
                    try {
                        const response = await fetch(`/admin/ilan-kategorileri/${id}/inline-update`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                field: 'aktiflik_durumu',
                                value: 'toggle'
                            })
                        });
                        if (response.ok) window.location.reload();
                    } catch (e) {
                        console.error('Hata:', e);
                    }

                },

                toggleSortingMode() {
                    if (this.sortingMode) {
                        this.saveOrders();
                    } else {
                        this.sortingMode = true;
                    }
                },

                cancelSorting() {
                    this.sortingMode = false;
                    this.modifiedOrders = {};
                    window.location.reload();
                },

                updateDisplayOrder(id, value) {
                    this.modifiedOrders[id] = parseInt(value);
                },

                async saveOrders() {
                    if (Object.keys(this.modifiedOrders).length === 0) {
                        this.sortingMode = false;
                        return;
                    }

                    this.processing = true;
                    // Format items for backend: { items: [ { id: 1, display_order: 10 } ] }
                    const items = Object.entries(this.modifiedOrders).map(([id, seq]) => ({
                        id: parseInt(id),
                        display_order: seq
                    }));

                    try {
                        const response = await fetch('{{ route('admin.ilan-kategorileri.sirala') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                items
                            })
                        });
                        const res = await response.json();
                        if (res.success) {
                            window.location.reload();
                        } else {
                            alert(res.message || 'Bir hata oluştu');
                            this.sortingMode = false;
                        }
                    } catch (e) {
                        console.error(e);
                        alert('Bir hata oluştu');
                    }
                    this.processing = false;
                }
            }
        }
    </script>
@endsection
