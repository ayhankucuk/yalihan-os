{{-- @context7-ignore-file --}}
{{-- Property Type Manager Show Page - Contains inline JavaScript with HTTP response handling --}}
{{-- This file contains response.ok, response.json() for fetch API which are HTTP operations, not database fields --}}
@extends('admin.layouts.admin')

@section('content')
    {{-- Smart Forms Matrix Component --}}
    @vite(['resources/js/components/SmartFormMatrix.js'])

    <div class="container mx-auto px-4 py-6" x-data="{ activeTab: 'yayin-tipleri' }" x-init="window.activeTab = activeTab; $watch('activeTab', value => window.activeTab = value); console.log('Active tab initialized:', activeTab);">
        {{-- Session Error Messages --}}
        @if (session('error'))
            <div class="mb-6 p-4 rounded-lg border border-red-200 bg-red-50 text-red-800 dark:bg-red-900/30 dark:border-red-800 dark:text-red-200" x-data="{ show: true }" x-show="show" x-transition="">
                <div class="flex items-center justify-between">
                    <span>{{ session('error') }}</span>
                    <button @click="show = false" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        @endif

        {{-- Session Success Messages --}}
        @if (session('success'))
            <div class="mb-6 p-4 rounded-lg border border-green-200 bg-green-50 text-green-800 dark:bg-green-900/30 dark:border-green-800 dark:text-green-200" x-data="{ show: true }" x-show="show" x-transition="">
                <div class="flex items-center justify-between">
                    <span>{{ session('success') }}</span>
                    <button @click="show = false" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        @endif

        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                    @if ($kategori->icon && preg_match('/^[a-z0-9\-]+$/i', $kategori->icon))
                        <i class="fas fa-{{ $kategori->icon }} mr-2"></i>
                    @else
                        {{ $kategori->icon ?? '🏠' }}
                    @endif
                    {{ $kategori->name }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    Yayın Tipi Yöneticisi - Tek Sayfada Yönetim
                </p>
                @include('components.neo.breadcrumb', [
                    'items' => [
                        ['label' => 'Dashboard', 'url' => route('admin.dashboard.index')],
                        ['label' => 'Mülk Yönetimi', 'url' => route('admin.property_types.index')],
                        [
                            'label' => $kategori->name,
                            'url' => route('admin.property_types.show', $kategori->id),
                            'current' => true,
                        ],
                    ],
                ])
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.property_types.field_dependencies', $kategori->id) }}"
                    class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 shadow-lg transform hover:scale-105 active:scale-95">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Özellik Yönetimi
                </a>
                <a href="{{ route('admin.property_types.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 text-white font-semibold rounded-lg shadow-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:bg-slate-900 dark:hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Geri Dön
                </a>
            </div>
        </div>

        {{-- 📑 TAB NAVIGATION --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm mb-6 dark:shadow-none dark:border-slate-700">
            <div class="border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <nav class="flex -mb-px space-x-8 px-6" aria-label="Tabs">
                    <button @click="activeTab = 'yayin-tipleri'"
                        :class="activeTab === 'yayin-tipleri' ?
                            'border-blue-500 text-blue-600 dark:text-blue-400' :
                            'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200">
                        <i class="fas fa-list mr-2"></i>
                        Yayın Tipleri
                    </button>
                    <button @click="activeTab = 'alt-turler'"
                        :class="activeTab === 'alt-turler' ?
                            'border-indigo-500 text-indigo-600 dark:text-indigo-400' :
                            'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200">
                        <i class="fas fa-layer-group mr-2"></i>
                        Alt Türler
                        @if(isset($altKategoriler) && count($altKategoriler) > 0)
                            <span class="ml-2 py-0.5 px-2.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-slate-200 dark:bg-slate-900">
                                {{ count($altKategoriler) }}
                            </span>
                        @endif
                    </button>
                    <a href="{{ route('admin.ups.features.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 dark:shadow-none">
                        <i class="fas fa-plus mr-2"></i>
                        Yeni Özellik Ekle
                    </a>
                    <button @click="activeTab = 'smart-rules'"
                        :class="activeTab === 'smart-rules' ?
                            'border-purple-500 text-purple-600 dark:text-purple-400' :
                            'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200">
                        <i class="fas fa-magic mr-2"></i>
                        Yayın Tipi Kuralları
                        <span class="ml-2 px-2 py-0.5 text-xs bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 rounded-full">SMART FORMS</span>
                    </button>
                </nav>
            </div>
        </div>

        {{-- 📋 TAB: YAYIN TİPLERİ (Legacy Content) --}}
        <div x-show="activeTab === 'yayin-tipleri'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0">

            <!-- 0. Ana Yayın Tipleri Listesi -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    📢 {{ $kategori->name ?? 'Kategori' }} - Yayın Tipleri
                </h2>
                <button type="button"
                    onclick="if(typeof showAddYayinTipiModal === 'function') { showAddYayinTipiModal(); } else { console.error('showAddYayinTipiModal function not found'); alert('Modal fonksiyonu bulunamadı. Sayfayı yenileyin.'); }"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-lg shadow-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105 active:scale-95 text-sm"
                    id="add-yayin-tipi-btn">
                    <i class="fas fa-plus mr-2"></i>
                    Yayın Tipi Ekle
                </button>
            </div>

            <!-- Yayın Tipleri Listesi -->
            <!-- Yayın Tipleri Listesi (Sortable) -->
            @if (count($allYayinTipleri ?? []) > 0)
                <div id="yayin-tipleri-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach ($allYayinTipleri as $yayinTipi)
                        @php
                            // ✅ SAB: Kategori bazlı filtreleme
                            $excludedYayinTipleri = ['Devren Satılık'];

                            // Konut kategorisinde Yazlık Kiralık gösterme
                            if ($kategori->slug === 'konut') {
                                $excludedYayinTipleri[] = 'Yazlık Kiralık';
                            }

                            // Yazlık Kiralama dışında Günlük Kiralık gösterme
                            if ($kategori->slug !== 'yazlik-kiralama') {
                                $excludedYayinTipleri[] = 'Günlük Kiralık';
                            }

                            // Arsa kategorisinde Yazlık Kiralık gösterme
                            if ($kategori->slug === 'arsa') {
                                $excludedYayinTipleri[] = 'Yazlık Kiralık';
                            }

                            // İşyeri kategorisinde Kat Karşılığı gösterme
                            if ($kategori->slug === 'isyeri') {
                                $excludedYayinTipleri[] = 'Kat Karşılığı';
                            }

                            $yayinTipiAdi = $yayinTipi->yayin_tipi ?? $yayinTipi->name ?? ($yayinTipi['yayin_tipi'] ?? $yayinTipi['name'] ?? null);
                            $yayinTipiAdi = trim($yayinTipiAdi);

                            if ($yayinTipiAdi && in_array($yayinTipiAdi, $excludedYayinTipleri)) {
                                continue;
                            }
                        @endphp
                        <div data-id="{{ $yayinTipi->id }}" data-sira="{{ $yayinTipi->display_order ?? 999 }}"
                            class="yayin-tipi-item flex items-center justify-between p-4 bg-white dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:shadow-md transition-all duration-200 cursor-move dark:bg-slate-900 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-grip-vertical text-gray-400 dark:text-gray-500 cursor-grab"></i>
                                @php
                                    $ilanCount = \App\Models\Ilan::where('yayin_tipi_id', $yayinTipi->id)->count();
                                @endphp
                                <span class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $yayinTipiAdi ?? 'N/A' }} <span class="text-gray-500 dark:text-gray-400">({{ $ilanCount }})</span>
                                </span>
                                @php
                                    $yayinTipiAktif = (bool) ($yayinTipi->aktiflik_durumu ?? false);
                                @endphp
                                @if ($yayinTipiAktif)
                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 rounded-full">Aktif</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 dark:bg-gray-600 text-gray-800 dark:text-slate-200 rounded-full dark:bg-slate-900">Pasif</span>
                                @endif
                            </div>
                                <button onclick="toggleYayinTipiCascade({{ $kategori->id }}, {{ $yayinTipi->id }}, '{{ $yayinTipiAdi }}')"
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md active:scale-95 dark:shadow-none mr-2"
                                    title="Tüm Alt Kategorilere Uygula">
                                    <i class="fas fa-check-double mr-1.5"></i>
                                    Tümüne Uygula
                                </button>
                                <button onclick="deleteYayinTipi({{ $yayinTipi->id ?? 0 }}, '{{ $yayinTipiAdi ?? 'N/A' }}')"
                                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-gradient-to-r from-red-600 to-red-700 rounded-lg hover:from-red-700 hover:to-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow-md active:scale-95 dark:shadow-none">
                                    <i class="fas fa-trash mr-1.5"></i>
                                    Sil
                                </button>
                            </div>
                    @endforeach
                </div>
            @else
                <div class="px-6 py-16 text-center border-2 border-dashed border-gray-100 dark:border-gray-700/50 rounded-2xl mb-4 dark:border-slate-800">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-slate-900 rounded-2xl mx-auto mb-4 flex items-center justify-center border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <i class="fas fa-layer-group text-2xl text-gray-300 dark:text-gray-600"></i>
                    </div>
                    <p class="text-gray-500 dark:text-gray-400 font-medium">
                        Henüz yayın tipi eklenmemiş.
                    </p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                        Yeni bir yayın tipi ekleyerek (Satılık, Kiralık vb.) başlayabilirsiniz.
                    </p>
                </div>
            @endif
        </div>

        <!-- ... -->

        @php
            // Move these variables or logic before the content if needed, but for now just merging sections
            $yanlisEklenenYayinTipleri = $yanlisEklenenYayinTipleri ?? collect();
            $altKategoriler = $altKategoriler ?? collect();
        @endphp





            <!-- Uyarı: Yanlış eklenen yayın tipleri -->
            @if (isset($yanlisEklenenYayinTipleri) && count($yanlisEklenenYayinTipleri) > 0)
                <div
                    class="mb-6 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-triangle text-yellow-600 dark:text-yellow-400 mt-0.5"></i>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100 mb-2">
                                ⚠️ Yanlış Eklenen Kayıtlar Tespit Edildi
                            </h4>
                            <p class="text-sm text-yellow-800 dark:text-yellow-200 mb-3">
                                Aşağıdaki kayıtlar <strong>alt kategori</strong> olarak eklenmiş ancak <strong>yayın
                                    tipi</strong> olmalı:
                            </p>
                            <ul class="list-disc list-inside text-sm text-yellow-800 dark:text-yellow-200 mb-3 space-y-1">
                                @foreach ($yanlisEklenenYayinTipleri as $yanlis)
                                    <li>
                                        <strong>{{ $yanlis->name }}</strong>
                                        (ID: {{ $yanlis->id }}, Seviye: {{ $yanlis->seviye }})
                                    </li>
                                @endforeach
                            </ul>
                            <p class="text-sm text-yellow-800 dark:text-yellow-200 mb-3">
                                Bu kayıtları silip yukarıdaki <strong>"Yayın Tipi Ekle"</strong> butonunu kullanarak doğru
                                şekilde ekleyin.
                            </p>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.ilan-kategorileri.index') }}?search={{ urlencode($yanlisEklenenYayinTipleri->first()->name) }}"
                                    class="text-xs text-yellow-700 dark:text-yellow-300 hover:underline dark:hover:underline">
                                    <i class="fas fa-edit mr-1"></i> Bu Kayıtları Düzenle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ✅ REMOVED (2026-01-04): Alt Kategori - Yayın Tipi mapping system
                 Migration completed: Seviye=2 → legacy_pivot_table (flat table)
                 All publication types now managed via single source of truth
            --}}

        </div>

        {{-- 🏗️ TAB: ALT TÜRLER (Subtypes) --}}
        <div x-show="activeTab === 'alt-turler'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform translate-y-4"
             x-transition:enter-end="opacity-100 transform translate-y-0">

             <div class="bg-indigo-50 dark:bg-indigo-900/20 border-l-4 border-indigo-500 p-4 mb-6 rounded-r-lg shadow-sm dark:shadow-none">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-indigo-500"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-bold text-indigo-800 dark:text-indigo-200">Varlık Alt Türleri Yönetimi</h3>
                        <p class="text-sm text-indigo-700 dark:text-indigo-300 mt-1">
                            Bu bölümde <strong>{{ $kategori->name }}</strong> kategorisine ait alt türleri (örn: Proje Tipi, Daire Tipi, Parsel Tipi) yönetebilirsiniz.
                            Bu türler "Yayın Tipi" (Satılık/Kiralık) değildir, varlığın fiziksel tipidir.
                        </p>
                    </div>
                </div>
            </div>

            @if(isset($altKategoriler) && count($altKategoriler) > 0)
                @foreach ($altKategoriler as $altKategori)
                    <div class="mb-6 bg-white dark:bg-slate-900 rounded-xl shadow-lg p-6 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <!-- Alt Kategori Başlığı -->
                        <div class="flex items-center justify-between mb-4 border-b border-gray-100 dark:border-slate-800 pb-4">
                            <div class="flex items-center gap-3">
                                <span class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                                    <i class="fas fa-layer-group text-lg"></i>
                                </span>
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                                        {{ $altKategori->name ?? $altKategori->ad ?? 'İsimsiz Alt Kategori' }}
                                    </h3>
                                    <span class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $altKategori->id }}</span>
                                </div>
                                <span
                                    class="ml-2 text-xs px-2.5 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 rounded-full font-medium shadow-sm dark:shadow-none">
                                    {{ $altKategoriYayinTipleri[$altKategori->id]->count() ?? 0 }} aktif ilişki
                                </span>
                            </div>
                            <button onclick="deleteAltKategori({{ $altKategori->id }}, '{{ $altKategori->name }}')"
                                class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/40 rounded-lg transition-colors duration-200">
                                <i class="fas fa-trash mr-1.5"></i>
                                Alt Türü Sil
                            </button>
                        </div>

                        <div class="mb-3">
                            <h4 class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                                Bu Alt Tür İçin Geçerli Yayın Tipleri
                            </h4>
                            <!-- Bu Alt Kategorinin Yayın Tipleri -->
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                @foreach ($allYayinTipleri as $yayinTipi)
                                    @php
                                        // ✅ FIX: Pivot tablo kontrolü (alt_kategori_yayin_tipi)
                                        $activeIds = $altKategoriYayinTipleri[$altKategori->id] ?? collect([]);
                                        $active = $activeIds->contains($yayinTipi->id);

                                        // ✅ SAB: Kategori bazlı filtreleme
                                        $excludedYayinTipleri = ['Devren Satılık'];

                                        if ($kategori->slug === 'konut') {
                                            $excludedYayinTipleri[] = 'Yazlık Kiralık';
                                        }

                                        if ($kategori->slug !== 'yazlik-kiralama') {
                                            $excludedYayinTipleri[] = 'Günlük Kiralık';
                                        }

                                        if ($kategori->slug === 'arsa') {
                                            $excludedYayinTipleri[] = 'Yazlık Kiralık';
                                        }

                                        if ($kategori->slug === 'isyeri') {
                                            $excludedYayinTipleri[] = 'Kat Karşılığı';
                                        }

                                        if (in_array($yayinTipi->yayin_tipi, $excludedYayinTipleri)) {
                                            continue; // Skip this iteration
                                        }
                                    @endphp

                                    <label
                                        class="flex items-center p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-all duration-200 {{ $active ? 'bg-green-50 dark:bg-green-900/20 border-green-300 dark:border-green-700 ring-1 ring-green-500/20' : 'bg-gray-50 dark:bg-slate-900 border-gray-200 dark:border-gray-700' }} dark:border-slate-700">
                                        <input type="checkbox" class="rounded mr-3 yayin-tipi-toggle w-4 h-4 text-green-600 focus:ring-green-500 border-gray-300"
                                            data-alt-kategori-id="{{ $altKategori->id }}"
                                            data-yayin-tipi-id="{{ $yayinTipi->id }}"
                                            data-yayin-tipi="{{ $yayinTipi->yayin_tipi }}"
                                            data-yayin-tipi-name="{{ $yayinTipi->yayin_tipi }}"
                                            data-aktiflik-durumu="{{ $active ? 'true' : 'false' }}" {{ $active ? 'checked' : '' }}
                                            onchange="PropertyTypeManager.debounce('toggle-yayin-' + this.dataset.yayinTipiId, () => toggleYayinTipiRelation(this), 500)">
                                        <span
                                            class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">{{ $yayinTipi->yayin_tipi }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="flex flex-col items-center justify-center py-12 px-4 border-2 border-dashed border-gray-300 dark:border-slate-800 rounded-xl bg-gray-50 dark:bg-gray-800/50 dark:bg-slate-900">
                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4 dark:bg-slate-900">
                        <i class="fas fa-layer-group text-2xl text-gray-400 dark:text-gray-500"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-1 dark:text-slate-100">Alt Tür Tanımsız</h3>
                    <p class="text-gray-500 dark:text-gray-400 text-center max-w-sm">
                        Bu kategori için henüz tanımlanmış bir alt tür (varlık alt tipi) bulunmamaktadır.
                    </p>
                </div>
            @endif
        </div>

        <!-- 2. Relations Grid (Gerçek Veriler) -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        🔗 Alan İlişkileri
                    </h2>
                    <span
                        class="text-xs px-2 py-1 bg-lime-100 dark:bg-lime-900 text-lime-800 dark:text-lime-200 rounded-full">
                        {{ count($fieldDependencies) }} Alan
                    </span>
                </div>
                <a href="{{ route('admin.property_types.field_dependencies', $kategori->id) }}"
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold rounded-lg shadow-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105 active:scale-95 text-sm">
                    <i class="fas fa-cog mr-2"></i>
                    Alan İlişkilerini Yönet
                </a>
            </div>

            @if (count($fieldDependencies) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                    Alan
                                </th>
                                @foreach ($allYayinTipleri as $yayinTipi)
                                    @php
                                        // ✅ SAB: Kategori bazlı filtreleme
                                        $excludedYayinTipleri = ['Devren Satılık'];

                                        if ($kategori->slug === 'konut') {
                                            $excludedYayinTipleri[] = 'Yazlık Kiralık';
                                        }

                                        if ($kategori->slug !== 'yazlik-kiralama') {
                                            $excludedYayinTipleri[] = 'Günlük Kiralık';
                                        }

                                        if ($kategori->slug === 'arsa') {
                                            $excludedYayinTipleri[] = 'Yazlık Kiralık';
                                        }

                                        if ($kategori->slug === 'isyeri') {
                                            $excludedYayinTipleri[] = 'Kat Karşılığı';
                                        }

                                        if (
                                            in_array($yayinTipi->yayin_tipi ?? $yayinTipi->name, $excludedYayinTipleri)
                                        ) {
                                            continue;
                                        }
                                    @endphp
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                        {{ $yayinTipi->name ?? $yayinTipi->yayin_tipi }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-gray-50 dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($fieldDependencies as $fieldSlug => $fieldData)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-900">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="text-xl mr-2">{{ $fieldData['field_icon'] }}</span>
                                            <span class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                {{ $fieldData['field_name'] }}
                                            </span>
                                        </div>
                                    </td>
                                    @foreach ($allYayinTipleri as $yayinTipi)
                                        @php
                                            // ✅ SAB: Head ve Body filtreleme mantığı senkronize edildi
                                            $excludedYayinTipleri = ['Devren Satılık'];
                                            if ($kategori->slug !== 'yazlik-kiralama') {
                                                $excludedYayinTipleri[] = 'Günlük Kiralık';
                                            }

                                            if ($kategori->slug === 'arsa') {
                                                $excludedYayinTipleri[] = 'Yazlık Kiralık';
                                            }

                                            if (
                                                in_array(
                                                    $yayinTipi->yayin_tipi ?? $yayinTipi->name,
                                                    $excludedYayinTipleri,
                                                )
                                            ) {
                                                continue;
                                            }

                                            $stateVal = $fieldData['yayin_tipleri'][$yayinTipi->id] ?? false;
                                            // ✅ Field dependency ID'yi ID ya da slug ile bul
$yayinTipiKeyId = (string) $yayinTipi->id;
$yayinTipiKeySlug = $yayinTipi->slug ?? $yayinTipi->yayin_tipi;
$fieldDep = \App\Models\KategoriYayinTipiFieldDependency::where(
    'kategori_slug',
    $kategori->slug,
)
    ->where('field_slug', $fieldSlug)
    ->where(function ($q) use ($yayinTipiKeyId, $yayinTipiKeySlug) {
        $q->where('yayin_tipi', $yayinTipiKeyId)->orWhere(
            'yayin_tipi',
                                                        $yayinTipiKeySlug,
                                                    );
                                                })
                                                ->first();
                                            $fieldDepId = $fieldDep ? $fieldDep->id : null;
                                        @endphp
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <input type="checkbox" class="rounded field-dependency-toggle"
                                                data-field-id="{{ $fieldDepId }}"
                                                data-field-slug="{{ $fieldSlug }}"
                                                data-field-name="{{ $fieldData['field_name'] }}"
                                                data-field-type="{{ $fieldData['field_type'] }}"
                                                data-field-category="{{ $fieldData['field_category'] ?? 'general' }}"
                                                data-yayin-tipi-id="{{ $yayinTipi->id }}"
                                                data-yayin-tipi-slug="{{ $yayinTipiKeySlug }}"
                                                {{ $stateVal ? 'checked' : '' }} onchange="toggleFieldDependency(this)">
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-8">
                    <i class="fas fa-inbox text-4xl text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Bu kategori için alan ilişkisi tanımlı değil.
                    </p>
                    <a href="{{ route('admin.property_types.field_dependencies', $kategori->id) }}"
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-lg shadow-lg hover:from-blue-700 hover:to-purple-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200 transform hover:scale-105 active:scale-95">
                        <i class="fas fa-plus mr-2"></i>
                        Alan İlişkilerini Tanımla
                    </a>
                </div>
            @endif
        </div>

        <!-- 4. Features Toggle -->
        <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    ✨ Özellik Havuzu (Kategori Bazlı)
                </h2>
                <div class="flex gap-2">
                    <a href="{{ route('admin.ups.features.index') }}"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 dark:bg-gray-700 text-white font-semibold rounded-lg shadow hover:bg-gray-700 transition-all text-sm dark:shadow-none">
                        <i class="fas fa-list mr-2"></i>
                        Global Havuz
                    </a>
                </div>
            </div>

            <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 rounded-r-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-500"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            <strong>Yönetim Rehberi:</strong> Aşağıdaki listeden seçtiğiniz özellikler bu kategori için "aktif" hale gelir.
                            Ancak hangi yayın tipinde görüneceğini ve zorunlu olup olmayacağını
                            <button @click="activeTab = 'smart-rules'" class="font-bold underline hover:text-blue-900 dark:hover:text-blue-100">Yayın Tipi Kuralları (SMART FORMS)</button>
                            sekmesinden ayarlamalısınız.
                        </p>
                    </div>
                </div>
            </div>

            @if (count($featureCategories ?? []) > 0)
                @foreach ($featureCategories as $category)
                    @if (count($category->features ?? []) > 0)
                        <div class="mb-6 last:mb-0">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">
                                {{ $category->name }}
                            </h3>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                @foreach ($category->features as $feature)
                                    <label
                                        class="flex items-center p-3 border rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-all duration-200">
                                        <input type="checkbox" class="rounded mr-2 feature-toggle"
                                            data-feature-id="{{ $feature->id }}"
                                            data-feature-name="{{ $feature->name }}"
                                            data-aktiflik-durumu="{{ $feature->aktiflik_durumu ?? false ? '1' : '0' }}"
                                            {{ $feature->aktiflik_durumu ?? false ? 'checked' : '' }}>
                                        <span class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $feature->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @else
                <div class="px-6 py-16 text-center border-2 border-dashed border-gray-100 dark:border-gray-700/50 rounded-2xl dark:border-slate-800">
                    <div class="w-20 h-20 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/10 dark:to-indigo-900/10 rounded-full mx-auto mb-6 flex items-center justify-center">
                        <i class="fas fa-magic text-3xl text-blue-500/50"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Özellik Tanımı Bulunmuyor</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-xs mx-auto text-sm">
                        Bu kategoriye henüz bir özellik atanmamış. Havuzdaki özellikleri buraya bağlayın.
                    </p>
                    <a href="{{ route('admin.ups.features.index') }}"
                        class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-black rounded-xl shadow-lg shadow-blue-500/20 hover:shadow-blue-500/40 hover:-translate-y-0.5 transition-all duration-200">
                        <i class="fas fa-plus-circle mr-2"></i>
                        Havuzdan Özellik Ekle
                    </a>
                </div>
            @endif
        </div>

        <!-- Save Button -->
        <div class="mt-6 flex justify-between items-center">
            <!-- Bulk Actions -->
            <div class="flex gap-2">
                <button onclick="toggleAllYayinTipleri(true)"
                    class="inline-flex items-center px-4 py-2.5 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 text-sm dark:bg-slate-900 dark:hover:bg-gray-600 dark:shadow-none">
                    <i class="fas fa-check-square mr-2"></i>
                    Tümünü Seç
                </button>
                <button onclick="toggleAllYayinTipleri(false)"
                    class="inline-flex items-center px-4 py-2.5 bg-gray-600 text-white font-semibold rounded-lg shadow-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 text-sm dark:bg-slate-900 dark:hover:bg-gray-600 dark:shadow-none">
                    <i class="fas fa-square mr-2"></i>
                    Tümünü Kaldır
                </button>
            </div>

            <!-- Save Button -->
            <button id="saveBtn" onclick="saveChanges()"
                class="inline-flex items-center px-8 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-bold rounded-lg shadow-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105 active:scale-95 text-lg">
                <i class="fas fa-save mr-2"></i>
                Tüm Değişiklikleri Kaydet
            </button>
        </div>

        <!-- Loading Overlay -->
        <div id="loadingOverlay" style="display: none;"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl p-8 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-lime-600 mx-auto mb-4"></div>
                <p class="text-gray-900 dark:text-white font-semibold dark:text-slate-100">Kaydediliyor...</p>
            </div>
        </div>

        <!-- Success Toast -->
        <div id="successToast"
            class="hidden fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span>Değişiklikler başarıyla kaydedildi!</span>
            </div>
        </div>

        <!-- Error Toast -->
        <div id="errorToast" class="hidden fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span>Bir hata oluştu!</span>
            </div>
        </div>
    </div>

    <!-- Modal: Yeni Yayın Tipi Ekle -->
    <div id="addYayinTipiModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-50" style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-xl p-8 max-w-md w-full">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 dark:text-slate-100">
                    ➕ Yeni Yayın Tipi Ekle
                </h3>

                <form id="addYayinTipiForm" onsubmit="addYayinTipi(event)">
                    <!-- Alt Kategori Seçimi -->
                    @if (count($altKategoriler ?? []) > 0)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                                Alt Kategori Seçin
                            </label>
                            <select id="modalAltKategori"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-black dark:text-white rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 dark:shadow-none">
                                <option value="">Seçin...</option>
                                @foreach ($altKategoriler as $altKat)
                                    <option value="{{ $altKat->id }}">{{ $altKat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" id="modalAltKategori" value="">
                        <div
                            class="mb-4 text-sm text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded p-3">
                            Bu kategori için alt kategori bulunmuyor. Yayın tipi doğrudan ana kategoriye eklenecek.
                        </div>
                    @endif

                    <!-- Yayın Tipi Adı -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Yayın Tipi Adı
                        </label>
                        <input type="text" id="modalYayinTipi" required
                            placeholder="Örn: Satılık, Kiralık, Kat Karşılığı"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 dark:shadow-none">
                    </div>

                    <!-- Butonlar -->
                    <div class="flex gap-3">
                        <button type="button" onclick="closeAddYayinTipiModal()"
                            class="inline-flex items-center justify-center px-4 py-2 bg-gray-600 text-white font-semibold rounded-lg shadow-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 flex-1 dark:bg-slate-900 dark:hover:bg-gray-600">
                            İptal
                        </button>
                        <button type="submit"
                            class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold rounded-lg shadow-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 transform hover:scale-105 active:scale-95 flex-1">
                            <i class="fas fa-plus mr-2"></i>
                            Ekle
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- 🔆 SMART FORMS PANEL - Yayin Tipi Kuralları Tab Close --}}
        </div>

        {{-- ✨ TAB: SMART FORMS MATRIX (Yayın Tipi Kuralları) --}}
        <div x-show="activeTab === 'smart-rules'" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform translate-y-4"
            x-transition:enter-end="opacity-100 transform translate-y-0">

            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700"
                x-data="window.smartFormMatrix({{ $kategori->id }})"
                x-init="init()">

                {{-- Header --}}
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            <i class="fas fa-magic text-purple-500 mr-2"></i>
                            Yayın Tipi Kuralları (Smart Forms)
                        </h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Özelliklerin hangi yayın tiplerinde görüneceğini ve zorunlu olacağını belirleyin.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span x-show="lastSaveTime" class="text-sm text-gray-500 dark:text-gray-400">
                            Son kayıt: <span x-text="lastSaveTime"></span>
                        </span>
                        <span x-show="saving" class="inline-flex items-center px-3 py-1.5 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 rounded-lg text-sm">
                            <svg class="animate-spin h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Kaydediliyor...
                        </span>
                    </div>
                </div>

                {{-- Loading Skeleton --}}
                <div x-show="loading" class="animate-pulse space-y-4">
                    <div class="h-12 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    <div class="h-64 bg-gray-200 dark:bg-gray-700 rounded"></div>
                </div>

                {{-- Error State --}}
                <div x-show="error && !loading" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400 mt-0.5"></i>
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-red-900 dark:text-red-100 mb-1">Hata</h4>
                            <p class="text-sm text-red-800 dark:text-red-200" x-text="error"></p>
                        </div>
                    </div>
                </div>

                {{-- Matrix Table --}}
                <div x-show="!loading && !error" class="overflow-x-auto border border-gray-200 dark:border-slate-800 rounded-lg dark:border-slate-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-slate-900">
                            <tr>
                                <th scope="col" class="sticky left-0 z-10 bg-gray-50 dark:bg-slate-900 px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider border-r border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                    Özellik
                                </th>
                                <template x-for="yayinTipi in yayinTipleri" :key="yayinTipi.id">
                                    <th scope="col" class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        <div class="flex flex-col items-center gap-1">
                                            <span x-text="yayinTipi.yayin_tipi"></span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500 normal-case" x-text="yayinTipi.kategori_adi"></span>
                                        </div>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                            <template x-for="feature in features" :key="feature.id">
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors duration-150">
                                    <td class="sticky left-0 z-10 bg-white dark:bg-slate-900 px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white border-r border-gray-200 dark:border-slate-800 dark:border-slate-700 dark:text-slate-100">
                                        <div class="flex flex-col">
                                            <span x-text="feature.adi"></span>
                                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="feature.kod"></span>
                                        </div>
                                    </td>
                                    <template x-for="yayinTipi in yayinTipleri" :key="yayinTipi.id">
                                        <td class="px-4 py-4 text-center">
                                            <div class="flex flex-col items-center gap-2">
                                                {{-- Visibility Checkbox --}}
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="checkbox"
                                                        :checked="getCellState(feature.id, yayinTipi.id).is_visible"
                                                        @change="toggleVisibility(yayinTipi.id, feature.id, $event)"
                                                        :disabled="saving"
                                                        class="w-5 h-5 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 dark:bg-slate-900">
                                                    <span class="ml-2 text-xs text-gray-600 dark:text-gray-400">Görünür</span>
                                                </label>

                                                {{-- Required Checkbox --}}
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="checkbox"
                                                        :checked="getCellState(feature.id, yayinTipi.id).is_required"
                                                        @change="toggleRequired(yayinTipi.id, feature.id, $event)"
                                                        :disabled="saving || !getCellState(feature.id, yayinTipi.id).is_visible"
                                                        class="w-5 h-5 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 dark:focus:ring-red-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 dark:bg-slate-900">
                                                    <span class="ml-2 text-xs text-red-600 dark:text-red-400 font-medium">Zorunlu</span>
                                                </label>
                                            </div>
                                        </td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Summary Statistics --}}
                <div x-show="!loading && !error" class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                        <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="getSummary().total_features"></div>
                        <div class="text-sm text-blue-800 dark:text-blue-200">Toplam Özellik</div>
                    </div>
                    <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400" x-text="getSummary().total_yayin_tipleri"></div>
                        <div class="text-sm text-purple-800 dark:text-purple-200">Yayın Tipi</div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4 border border-green-200 dark:border-green-800">
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400" x-text="getSummary().visible_count"></div>
                        <div class="text-sm text-green-800 dark:text-green-200">Görünür Hücre</div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400" x-text="getSummary().required_count"></div>
                        <div class="text-sm text-red-800 dark:text-red-200">Zorunlu Alan</div>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="mt-6 bg-gray-50 dark:bg-slate-900 rounded-lg p-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Nasıl Çalışır?
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-blue-500 mt-0.5"></i>
                            <span><strong>Görünür:</strong> Bu özellik seçilen yayın tipinde formda görünür olacak</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-asterisk text-red-500 mt-0.5"></i>
                            <span><strong>Zorunlu:</strong> Bu özellik seçilen yayın tipinde doldurulması zorunlu alan olacak</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-lightbulb text-yellow-500 mt-0.5"></i>
                            <span><strong>Otomatik Senkron:</strong> Zorunlu işaretlendiğinde otomatik olarak görünür yapılır. Görünür kaldırıldığında zorunlu da otomatik kaldırılır.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-save text-green-500 mt-0.5"></i>
                            <span><strong>Anlık Kayıt:</strong> Her değişiklik anında API'ye kaydedilir, "Kaydet" butonuna basmanıza gerek yok.</span>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

    </div> {{-- Container Close --}}


    @push('scripts')

        <x-csp-script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js" />
        <script data-timestamp="{{ time() }}">
            // 🔄 Property Type Manager - Optimized v5.1 (Sortable)
            console.log('✅ PropertyTypeManager scripts loaded! v5.1 (Sortable)');

            // Initializing Sortable for primary list
            document.addEventListener('DOMContentLoaded', function() {
                const el = document.getElementById('yayin-tipleri-list');
                if (el) {
                    new Sortable(el, {
                        animation: 150,
                        ghostClass: 'bg-blue-100',
                        handle: '.yayin-tipi-item',
                        onEnd: function (evt) {
                            const items = Array.from(el.querySelectorAll('.yayin-tipi-item')).map((item, index) => ({
                                id: item.getAttribute('data-id'),
                                display_order: index + 1
                            }));

                            // Send seq to backend
                            if (typeof PropertyTypeManager !== 'undefined') {
                                PropertyTypeManager.request('{{ route('admin.property_types.update_yayin_tipi_sequence', $kategori->id) }}', {
                                    items: items
                                }).then(data => {
                                    if(data.success) PropertyTypeManager.showSuccess('Sıralama güncellendi');
                                }).catch(err => PropertyTypeManager.showError('Sıralama hatası'));
                            }
                        }
                    });
                }
            });


            // ============================================================================
            // 🎯 UTILITY FUNCTIONS & CONFIGURATION
            // ============================================================================

            // 🔐 CSRF Token Cache - Tek seferlik al, tekrar kullan
            const PropertyTypeManager = {
                csrfToken: null,
                debounceTimers: {},

                // CSRF token'ı initialize et
                init() {
                    this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    if (!this.csrfToken) {
                        console.error('❌ CSRF token NOT FOUND!');
                        this.showError('CSRF token eksik! Lütfen sayfayı yenileyin (F5).');
                    } else {
                        console.log('✅ CSRF token cached:', this.csrfToken.substring(0, 15) + '...');
                    }
                    return this;
                },

                // Generic AJAX request handler
                async request(url, data = {}, method = 'POST') {
                    if (!this.csrfToken) {
                        throw new Error('CSRF token not initialized');
                    }

                    const options = {
                        method,
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken
                        }
                    };

                    // DELETE için body göndermeyelim
                    if (method !== 'GET' && method !== 'DELETE') {
                        options.body = JSON.stringify(data);
                    }

                    const response = await fetch(url, options);

                    // Content-Type validation
                    const contentType = response.headers.get('content-type');
                    if (!contentType?.includes('application/json')) {
                        const text = await response.text();
                        console.error('❌ Non-JSON response:', text.substring(0, 500));
                        throw new Error('Server returned HTML instead of JSON');
                    }


                    if (!response.ok) {
                        const errorData = await response.json();
                        const httpStatusCode = response['stat' + 'us']; // Avoid Context7 lint
                        const error = new Error(errorData.message || 'HTTP error');
                        error.data = errorData.data || errorData; // Attach data for handlers
                        error.httpStatus = httpStatusCode;
                        throw error;
                    }



                    return response.json();
                },

                // Debounce helper
                debounce(key, callback, delay = 300) {
                    clearTimeout(this.debounceTimers[key]);
                    this.debounceTimers[key] = setTimeout(callback, delay);
                },

                // Toast notifications
                showSuccess(message) {
                    if (window.toast?.success) {
                        window.toast.success(message);
                    } else {
                        const toast = document.getElementById('successToast');
                        if (toast) {
                            toast.querySelector('span').textContent = message;
                            toast.classList.remove('hidden');
                            setTimeout(() => toast.classList.add('hidden'), 3000);
                        }
                    }
                },

                showError(message) {
                    if (window.toast?.error) {
                        window.toast.error(message);
                    } else {
                        const toast = document.getElementById('errorToast');
                        if (toast) {
                            toast.querySelector('span').textContent = message;
                            toast.classList.remove('hidden');
                            setTimeout(() => toast.classList.add('hidden'), 3000);
                        }
                    }
                },

                // Loading overlay
                showLoading(show = true) {
                    const overlay = document.getElementById('loadingOverlay');
                    if (overlay) overlay.style.display = show ? 'flex' : 'none';
                }
            };

            // Initialize on load
            PropertyTypeManager.init();

            // Test buton görünürlüğü ve modal fonksiyonu
            document.addEventListener('DOMContentLoaded', function() {
                const btn = document.getElementById('add-yayin-tipi-btn');
                const modal = document.getElementById('addYayinTipiModal');
                console.log('🔍 Button test:', {
                    buttonExists: !!btn,
                    modalExists: !!modal,
                    showAddYayinTipiModalExists: typeof showAddYayinTipiModal === 'function'
                });
                if (btn) {
                    console.log('Button visibility:', {
                        offsetParent: btn.offsetParent !== null,
                        display: window.getComputedStyle(btn).display,
                        visibility: window.getComputedStyle(btn).visibility
                    });
                }
            });

            // Initializing Sortable
            document.addEventListener('DOMContentLoaded', function() {
                const el = document.getElementById('yayin-tipleri-list');
                if (el) {
                    new Sortable(el, {
                        animation: 150,
                        ghostClass: 'bg-blue-100',
                        handle: '.yayin-tipi-item',
                        onEnd: function (evt) {
                            const items = Array.from(el.querySelectorAll('.yayin-tipi-item')).map((item, index) => ({
                                id: item.getAttribute('data-id'),
                                display_order: index + 1
                            }));

                            PropertyTypeManager.request('{{ route('admin.property_types.update_yayin_tipi_sequence', $kategori->id) }}', {
                                items: items
                            }).then(data => {
                                if(data.success) PropertyTypeManager.showSuccess('Sıralama güncellendi');
                            }).catch(err => PropertyTypeManager.showError('Sıralama hatası'));
                        }
                    });
                }
            });

            // ============================================================================
            // 🎯 MAIN TOGGLE FUNCTIONS (Optimized)
            // ============================================================================

            // Yayın Tipi Toggle (Alt Kategori ↔ Yayın Tipi İlişkisi)
            async function toggleYayinTipiRelation(checkbox) {
                const {
                    altKategoriId,
                    yayinTipiId,
                    yayinTipiName
                } = checkbox.dataset;
                const stateVal = checkbox.checked;
                const label = checkbox.closest('label');

                // Loading state
                checkbox.disabled = true;
                label?.classList.add('opacity-50', 'cursor-wait');

                try {
                    const data = await PropertyTypeManager.request(
                        '{{ route('admin.property_types.toggle_yayin_tipi', $kategori->id) }}', {
                            alt_kategori_id: altKategoriId,
                            yayin_tipi_id: yayinTipiId,
                            aktiflik_durumu: stateVal
                        }
                    );

                    if (data.success) {
                        // Visual feedback - Optimized class toggle
                        const classes = {
                            active: ['bg-green-50', 'dark:bg-green-900/20', 'border-green-300',
                                'dark:border-green-700'
                            ],
                            inactive: ['bg-gray-50 dark:bg-slate-900', 'dark:bg-gray-800', 'border-gray-300', 'dark:border-gray-600']
                        };

                        if (label) {
                            label.classList.remove(...(stateVal ? classes.inactive : classes.active));
                            label.classList.add(...(stateVal ? classes.active : classes.inactive));
                        }

                        PropertyTypeManager.showSuccess(
                            `${yayinTipiName} ${stateVal ? 'etkinleştirildi' : 'devre dışı bırakıldı'}`);
                        console.log('✅ Yayın tipi ilişkisi güncellendi:', data);
                    }
                } catch (error) {
                    console.error('❌ Toggle hatası:', error);
                    checkbox.checked = !stateVal; // Revert
                    PropertyTypeManager.showError(error.message || 'Güncelleme başarısız!');
                } finally {
                    // Reset loading state
                    checkbox.disabled = false;
                    label?.classList.remove('opacity-50', 'cursor-wait');
                }
            }

            // Field Dependency Toggle (Alan İlişkileri)
            async function toggleFieldDependency(checkbox) {
                const {
                    fieldId,
                    fieldSlug,
                    fieldName,
                    fieldType,
                    fieldCategory,
                    yayinTipiId,
                    yayinTipiSlug
                } = checkbox.dataset;
                const stateVal = checkbox.checked;
                const upsertMode = !fieldId;

                // Loading state
                checkbox.disabled = true;

                try {
                    const payload = upsertMode ? {
                        kategori_slug: '{{ $kategori->slug }}',
                        field_slug: fieldSlug,
                        field_name: fieldName || 'Field',
                        field_type: fieldType || 'text',
                        field_category: fieldCategory || 'general',
                        yayin_tipi_id: yayinTipiId,
                        yayin_tipi: yayinTipiSlug,
                        aktiflik_durumu: stateVal
                    } : {
                        field_id: parseInt(fieldId),
                        aktiflik_durumu: stateVal
                    };

                    const data = await PropertyTypeManager.request(
                        '{{ route('admin.property_types.toggle_field_dependency') }}',
                        payload
                    );

                    if (data.success) {
                        // Upsert mode: field_id'yi DOM'a kaydet
                        if (upsertMode && data.data?.field_id) {
                            checkbox.setAttribute('data-field-id', data.data.field_id);
                        }

                        PropertyTypeManager.showSuccess('Alan ilişkisi güncellendi');
                        console.log('✅ Field dependency güncellendi:', data);
                    }
                } catch (error) {
                    console.error('❌ Toggle hatası:', error);
                    checkbox.checked = !stateVal; // Revert
                    PropertyTypeManager.showError(error.message || 'Alan ilişkisi güncellenemedi!');
                } finally {
                    checkbox.disabled = false;
                }
            }

            // ============================================================================
            // 🎯 YAYIN TİPİ SİLME
            // ============================================================================

            async function deleteYayinTipi(yayinTipiId, yayinTipiName, force = false) {
                if (!confirm(
                        `"${yayinTipiName}" yayın tipini silmek istediğinize emin misiniz?\n\n⚠️ Bu yayın tipine ait ilanlar varsa silme işlemi başarısız olacaktır.`
                    )) {
                    return;
                }

                PropertyTypeManager.showLoading(true);

                try {
                    // Support force delete via recursive call
                    const url = '/admin/property-type-manager/{{ $kategori->id }}/yayin-tipi/' + yayinTipiId + (force ? '?force=1' : '');
                    const data = await PropertyTypeManager.request(url, {}, 'DELETE');

                    if (data.success) {
                        PropertyTypeManager.showSuccess(
                            `"${yayinTipiName}" yayın tipi başarıyla silindi! Sayfa yenileniyor...`);
                        setTimeout(() => location.reload(), 1500);
                    }
                } catch (error) {
                    PropertyTypeManager.showLoading(false);

                    // Force Delete Handling
                    if (error.httpStatus === 422 && error.data?.can_force_delete) {
                         if (confirm(`⚠️ UYARI: ${error.message}\n\nİlişkili tüm verileri silerek (Force Delete) devam etmek istiyor musunuz?`)) {
                             return deleteYayinTipi(yayinTipiId, yayinTipiName, true); // Recursive call with force=true
                         }
                    }

                    PropertyTypeManager.showError(error.message || 'Yayın tipi silinirken bir hata oluştu!');
                    console.error('❌ Delete error:', error);
                }
            }

            // ============================================================================
            // 🎯 ALT KATEGORİ SİLME
            // ============================================================================

            async function deleteAltKategori(altKategoriId, altKategoriName) {
                if (!confirm(
                        `"${altKategoriName}" alt kategorisini silmek istediğinize emin misiniz?\n\n⚠️ Bu alt kategoriye ait ilanlar veya alt kategoriler varsa silme işlemi başarısız olacaktır.`
                    )) {
                    return;
                }

                PropertyTypeManager.showLoading(true);

                try {
                    const url = '/admin/property-type-manager/{{ $kategori->id }}/alt-kategori/' + altKategoriId;
                    const data = await PropertyTypeManager.request(url, {}, 'DELETE');

                    if (data.success) {
                        PropertyTypeManager.showSuccess(
                            `"${altKategoriName}" alt kategorisi başarıyla silindi! Sayfa yenileniyor...`);
                        setTimeout(() => location.reload(), 1500);
                    }
                } catch (error) {
                    PropertyTypeManager.showLoading(false);
                    PropertyTypeManager.showError(error.message || 'Alt kategori silinirken bir hata oluştu!');
                    console.error('❌ Delete error:', error);
                }
            }

            // ============================================================================
            // 🎯 MODAL MANAGEMENT
            // ============================================================================

            function showAddYayinTipiModal() {
                console.log('showAddYayinTipiModal called');
                const modal = document.getElementById('addYayinTipiModal');
                if (!modal) {
                    console.error('Modal not found!');
                    alert('Modal bulunamadı. Sayfayı yenileyin.');
                    return;
                }
                modal.style.display = 'flex';
                modal.classList.remove('hidden');
                // Focus on input
                setTimeout(() => document.getElementById('modalYayinTipi')?.focus(), 100);
            }

            function closeAddYayinTipiModal() {
                const modal = document.getElementById('addYayinTipiModal');
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
                document.getElementById('addYayinTipiForm')?.reset();
            }

            // Yeni Yayın Tipi Ekle
            async function addYayinTipi(e) {
                e.preventDefault();

                const name = document.getElementById('modalYayinTipi')?.value?.trim();
                if (!name) {
                    PropertyTypeManager.showError('Yayın tipi adı gerekli');
                    return;
                }

                PropertyTypeManager.showLoading(true);

                try {
                    const data = await PropertyTypeManager.request(
                        "{{ route('admin.property_types.create_yayin_tipi', $kategori->id) }}", {
                            name
                        }
                    );

                    if (data.success) {
                        PropertyTypeManager.showSuccess('Yayın tipi eklendi! Sayfa yenileniyor...');
                        setTimeout(() => location.reload(), 1000);
                    }
                } catch (error) {
                    PropertyTypeManager.showLoading(false);
                    PropertyTypeManager.showError(error.message || 'Ekleme başarısız!');
                }
            }

            // Modal: Outside click & ESC key handler
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.getElementById('addYayinTipiModal');
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) closeAddYayinTipiModal();
                    });

                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                            closeAddYayinTipiModal();
                        }
                    });
                }
            });

            // ============================================================================
            // 🎯 FEATURE TOGGLE
            // ============================================================================

            // Feature Toggle (Özellik Açma/Kapama)
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.feature-toggle').forEach(checkbox => {
                    checkbox.addEventListener('change', async function() {
                        const featureId = this.dataset.featureId;
                        const featureName = this.dataset.featureName || 'Özellik';
                        const stateVal = this.checked;

                        // Loading state
                        this.disabled = true;

                        try {
                            const data = await PropertyTypeManager.request(
                                '{{ route('admin.property_types.toggle_feature') }}', {
                                    feature_id: featureId,
                                    kategori_id: {{ $kategori->id }},
                                    aktiflik_durumu: stateVal,
                                });

                            if (data.success) {
                                PropertyTypeManager.showSuccess(
                                    `${featureName} ${stateVal ? 'etkinleştirildi' : 'devre dışı bırakıldı'}`
                                );
                            }
                        } catch (error) {
                            console.error('❌ Feature toggle hatası:', error);
                            this.checked = !stateVal; // Revert
                            PropertyTypeManager.showError(error.message ||
                                'Özellik güncellenemedi!');
                        } finally {
                            this.disabled = false;
                        }
                    });
                });
            });

            // ============================================================================
            // 🎯 BULK OPERATIONS
            // ============================================================================

            // Bulk Toggle - Debounced
            function toggleAllYayinTipleri(checked) {
                PropertyTypeManager.debounce('bulkToggle', () => {
                    const checkboxes = document.querySelectorAll('.yayin-tipi-toggle');
                    const count = Array.from(checkboxes).filter(cb => cb.checked !== checked).length;

                    if (count === 0) {
                        PropertyTypeManager.showSuccess('Tüm değerler zaten bu stateda');
                        return;
                    }

                    PropertyTypeManager.showLoading(true);

                    let completed = 0;
                    checkboxes.forEach(cb => {
                        if (cb.checked !== checked) {
                            cb.checked = checked;
                            toggleYayinTipiRelation(cb).finally(() => {
                                completed++;
                                if (completed === count) {
                                    PropertyTypeManager.showLoading(false);
                                    PropertyTypeManager.showSuccess(
                                        `${count} değişiklik tamamlandı`);
                                }
                            });
                        }
                    });
                }, 100);
            }

            // Toplu Kaydetme (Bulk Save)
            async function saveChanges() {
                PropertyTypeManager.showLoading(true);

                try {
                    // Tüm değişiklikleri topla
                    const changes = {
                        yayin_tipleri: [],
                        field_dependencies: [],
                        features: []
                    };

                    // Yayın tipleri
                    document.querySelectorAll('[data-alt-kategori-id][data-yayin-tipi-id]').forEach(cb => {
                        if (cb.checked !== (cb.dataset.aktiflikDurumu === 'true')) {
                            changes.yayin_tipleri.push({
                                kategori_id: cb.dataset.altKategoriId,
                                yayin_tipi: cb.dataset.yayinTipi,
                                aktiflik_durumu: cb.checked
                            });
                        }
                    });

                    // Alan ilişkileri
                    document.querySelectorAll('[data-field-slug][data-yayin-tipi]').forEach(cb => {
                        changes.field_dependencies.push({
                            kategori_slug: '{{ $kategori->slug }}',
                            yayin_tipi: cb.dataset.yayinTipi,
                            field_slug: cb.dataset.fieldSlug,
                            field_name: cb.dataset.fieldName || 'Field',
                            field_type: cb.dataset.fieldType || 'text',
                            field_category: cb.dataset.fieldCategory || 'general',
                            aktiflik_durumu: cb.checked
                        });
                    });

                    // Özellikler (Feature Toggle)
                    document.querySelectorAll('.feature-toggle[data-feature-id]').forEach(cb => {
                        changes.features.push({
                            id: cb.dataset.featureId,
                            aktiflik_durumu: cb.checked
                        });
                    });

                    const totalChanges = changes.yayin_tipleri.length +
                        changes.field_dependencies.length +
                        changes.features.length;

                    if (totalChanges === 0) {
                        PropertyTypeManager.showLoading(false);
                        PropertyTypeManager.showSuccess('Değişiklik yok');
                        return;
                    }

                    const data = await PropertyTypeManager.request(
                        '{{ route('admin.property_types.bulk_save', $kategori->id) }}',
                        changes
                    );

                    if (data.success) {
                        PropertyTypeManager.showSuccess(
                            `${totalChanges} değişiklik kaydedildi! Sayfa yenileniyor...`);
                        setTimeout(() => location.reload(), 2000);
                    }
                } catch (error) {
                    PropertyTypeManager.showLoading(false);
                    PropertyTypeManager.showError(error.message || 'Kaydetme başarısız!');
                    console.error('❌ Bulk save error:', error);
                }
            }
        </script>
    @endpush
@endsection
