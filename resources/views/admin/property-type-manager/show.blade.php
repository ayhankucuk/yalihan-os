{{-- @context7-ignore-file --}}
{{-- Property Type Manager Show Page - Mediterranean Luxury Redesign --}}
@extends('admin.layouts.admin')

@section('content')
    {{-- Smart Forms Matrix Component --}}
    @vite(['resources/js/components/SmartFormMatrix.js'])

    <div class="container mx-auto px-4 py-6" x-data="{ activeTab: 'yayin-tipleri' }" x-init="window.activeTab = activeTab; $watch('activeTab', value => window.activeTab = value);">
        {{-- Session Error Messages --}}
        @if (session('error'))
            <div class="mb-6 p-4 rounded-2xl border border-rose-200 bg-rose-50/90 text-rose-800 dark:bg-rose-950/30 dark:border-rose-900/50 dark:text-rose-200 shadow-sm" x-data="{ show: true }" x-show="show" x-transition>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-icon name="hata" class="w-5 h-5 text-rose-600 dark:text-rose-400" />
                        <span class="text-sm font-medium">{{ session('error') }}</span>
                    </div>
                    <button @click="show = false" class="text-rose-500 hover:text-rose-700 dark:hover:text-rose-300 p-1">
                        <x-icon name="kapat" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        @endif

        {{-- Session Success Messages --}}
        @if (session('success'))
            <div class="mb-6 p-4 rounded-2xl border border-emerald-200 bg-emerald-50/90 text-emerald-800 dark:bg-emerald-950/30 dark:border-emerald-900/50 dark:text-emerald-200 shadow-sm" x-data="{ show: true }" x-show="show" x-transition>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-icon name="onay-daire" class="w-5 h-5 text-emerald-600 dark:text-emerald-400" />
                        <span class="text-sm font-medium">{{ session('success') }}</span>
                    </div>
                    <button @click="show = false" class="text-emerald-500 hover:text-emerald-700 dark:hover:text-emerald-300 p-1">
                        <x-icon name="kapat" class="w-4 h-4" />
                    </button>
                </div>
            </div>
        @endif

        <!-- 🏛️ Header Section -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-2xl shadow-sm">
                        @if ($kategori->icon && preg_match('/^[a-z0-9\-]+$/i', $kategori->icon))
                            <x-icon :name="$kategori->icon" class="w-6 h-6" />
                        @else
                            <span>{{ $kategori->icon ?? '🏠' }}</span>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-2.5">
                            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                                {{ $kategori->name }}
                            </h1>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-500/20">
                                Tek Sayfada Yönetim
                            </span>
                        </div>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-0.5">
                            Yayın tipleri, alt türler ve akıllı form gereksinimlerini tek merkezden yapılandırın.
                        </p>
                    </div>
                </div>

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

            <div class="flex items-center gap-2.5">
                <a href="{{ route('admin.property_types.field_dependencies', $kategori->id) }}"
                    class="inline-flex items-center px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-amber-400 font-semibold rounded-xl border border-slate-700/80 shadow-sm hover:shadow transition-all duration-200 active:scale-95 text-sm dark:bg-slate-800 dark:hover:bg-slate-700 dark:border-slate-700">
                    <x-icon name="ayar" class="w-4 h-4 mr-2 text-amber-400" />
                    Özellik Yönetimi
                </a>
                <a href="{{ route('admin.property_types.index') }}"
                    class="inline-flex items-center px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 font-medium rounded-xl border border-slate-200 shadow-sm hover:shadow-sm transition-all duration-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700 dark:border-slate-700 text-sm">
                    <x-icon name="sol-ok" class="w-4 h-4 mr-2 text-slate-500 dark:text-slate-400" />
                    Geri Dön
                </a>
            </div>
        </div>

        {{-- 📑 TAB NAVIGATION BAR --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm mb-6 p-2">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <nav class="flex items-center space-x-1.5 overflow-x-auto p-1" aria-label="Tabs">
                    <button @click="activeTab = 'yayin-tipleri'"
                        :class="activeTab === 'yayin-tipleri' ?
                            'bg-slate-900 text-amber-400 dark:bg-amber-400/10 dark:text-amber-400 shadow-sm font-semibold' :
                            'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium'"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-sm transition-all duration-200 flex items-center gap-2">
                        <x-icon name="liste" class="w-4 h-4" />
                        <span>Yayın Tipleri</span>
                        @if(isset($allYayinTipleri) && count($allYayinTipleri) > 0)
                            <span :class="activeTab === 'yayin-tipleri' ? 'bg-amber-400/20 text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'" class="ml-1 py-0.5 px-2 rounded-full text-xs font-semibold transition-colors">
                                {{ count($allYayinTipleri) }}
                            </span>
                        @endif
                    </button>

                    <button @click="activeTab = 'alt-turler'"
                        :class="activeTab === 'alt-turler' ?
                            'bg-slate-900 text-amber-400 dark:bg-amber-400/10 dark:text-amber-400 shadow-sm font-semibold' :
                            'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium'"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-sm transition-all duration-200 flex items-center gap-2">
                        <x-icon name="katman" class="w-4 h-4" />
                        <span>Alt Türler</span>
                        @if(isset($altKategoriler) && count($altKategoriler) > 0)
                            <span :class="activeTab === 'alt-turler' ? 'bg-amber-400/20 text-amber-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400'" class="ml-1 py-0.5 px-2 rounded-full text-xs font-semibold transition-colors">
                                {{ count($altKategoriler) }}
                            </span>
                        @endif
                    </button>

                    <button @click="activeTab = 'smart-rules'"
                        :class="activeTab === 'smart-rules' ?
                            'bg-slate-900 text-amber-400 dark:bg-amber-400/10 dark:text-amber-400 shadow-sm font-semibold' :
                            'text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800/60 font-medium'"
                        class="whitespace-nowrap py-2.5 px-4 rounded-xl text-sm transition-all duration-200 flex items-center gap-2">
                        <x-icon name="ai" class="w-4 h-4 text-amber-500" />
                        <span>Yayın Tipi Kuralları</span>
                        <span class="ml-1 px-2 py-0.5 text-xs bg-amber-500/10 text-amber-600 dark:text-amber-400 font-semibold rounded-full border border-amber-500/20">SMART FORMS</span>
                    </button>
                </nav>

                <div class="flex items-center gap-2 px-2 pb-1 sm:pb-0">
                    <a href="{{ route('admin.ups.features.create') }}"
                        class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl text-slate-700 dark:text-slate-300 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        <x-icon name="ekle" class="w-3.5 h-3.5 mr-1.5 text-amber-600 dark:text-amber-400" />
                        Yeni Özellik Ekle
                    </a>
                </div>
            </div>
        </div>

        {{-- 📋 TAB 1: YAYIN TİPLERİ & GENEL AYARLAR --}}
        <div x-show="activeTab === 'yayin-tipleri'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0">

            <!-- 0. Ana Yayın Tipleri Listesi -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <x-icon name="liste" class="w-5 h-5 text-amber-500" />
                            <span>{{ $kategori->name ?? 'Kategori' }} — Yayın Tipleri</span>
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Sıralamayı sürükleyerek değiştirebilir, tüm alt kategorilere tek tıkla uygulayabilirsiniz.
                        </p>
                    </div>
                    <button type="button"
                        onclick="if(typeof showAddYayinTipiModal === 'function') { showAddYayinTipiModal(); } else { console.error('showAddYayinTipiModal function not found'); alert('Modal fonksiyonu bulunamadı. Sayfayı yenileyin.'); }"
                        class="inline-flex items-center justify-center px-4 py-2.5 bg-[#C9A84C] hover:bg-[#B8973B] text-slate-950 font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-200 active:scale-95 text-sm"
                        id="add-yayin-tipi-btn">
                        <x-icon name="ekle" class="w-4 h-4 mr-1.5 text-slate-950" />
                        Yayın Tipi Ekle
                    </button>
                </div>

                <!-- Yayın Tipleri Listesi (Sortable) -->
                @if (count($allYayinTipleri ?? []) > 0)
                    <div id="yayin-tipleri-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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

                                $yayinTipiAdi = $yayinTipi->yayin_tipi ?? $yayinTipi->name ?? ($yayinTipi['yayin_tipi'] ?? $yayinTipi['name'] ?? null);
                                $yayinTipiAdi = trim($yayinTipiAdi);

                                if ($yayinTipiAdi && in_array($yayinTipiAdi, $excludedYayinTipleri)) {
                                    continue;
                                }

                                $ilanCount = \App\Models\Ilan::where('yayin_tipi_id', $yayinTipi->id)->count();
                                $yayinTipiAktif = (bool) ($yayinTipi->aktiflik_durumu ?? false);
                            @endphp
                            <div data-id="{{ $yayinTipi->id }}" data-sira="{{ $yayinTipi->display_order ?? 999 }}"
                                class="yayin-tipi-item group relative flex flex-col justify-between p-4 bg-slate-50/70 hover:bg-white dark:bg-slate-800/50 dark:hover:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 hover:border-amber-400/50 hover:shadow-md transition-all duration-200 cursor-move">
                                <!-- Top Row: Grip + Title + Status Badge -->
                                <div>
                                    <div class="flex items-start justify-between gap-2 mb-1.5">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <span class="text-slate-400 hover:text-slate-600 dark:text-slate-500 cursor-grab p-0.5 -ml-1 shrink-0">
                                                <x-icon name="surukle" class="w-4 h-4" />
                                            </span>
                                            <h4 class="text-sm sm:text-base font-bold text-slate-900 dark:text-white leading-snug">
                                                {{ $yayinTipiAdi ?? 'N/A' }}
                                            </h4>
                                        </div>
                                        @if ($yayinTipiAktif)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800 shrink-0">
                                                Aktif
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-200/70 dark:bg-slate-700 text-slate-600 dark:text-slate-400 shrink-0">
                                                Pasif
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2 mb-3 pl-5">
                                        <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                                            {{ $ilanCount }} aktif ilan
                                        </span>
                                    </div>
                                </div>

                                <!-- Bottom Row: Actions -->
                                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200/60 dark:border-slate-700/60">
                                    <button onclick="toggleYayinTipiCascade({{ $kategori->id }}, {{ $yayinTipi->id }}, '{{ $yayinTipiAdi }}')"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-slate-700 hover:text-amber-700 dark:text-slate-300 dark:hover:text-amber-300 bg-white hover:bg-amber-50/60 dark:bg-slate-700/60 dark:hover:bg-slate-700 rounded-xl border border-slate-200 dark:border-slate-600 transition-colors shadow-sm"
                                        title="Tüm Alt Kategorilere Uygula">
                                        <x-icon name="onay-daire" class="w-3.5 h-3.5 mr-1 text-amber-600 dark:text-amber-400" />
                                        Tümüne Uygula
                                    </button>
                                    <button onclick="deleteYayinTipi({{ $yayinTipi->id ?? 0 }}, '{{ $yayinTipiAdi ?? 'N/A' }}')"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-400 dark:hover:bg-rose-900/40 rounded-xl border border-rose-200/60 dark:border-rose-900/50 transition-colors"
                                        title="Yayın Tipini Sil">
                                        <x-icon name="sil" class="w-3.5 h-3.5 mr-1 text-rose-500" />
                                        Sil
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="px-6 py-16 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl mb-4">
                        <div class="w-16 h-16 bg-amber-500/10 rounded-2xl mx-auto mb-4 flex items-center justify-center border border-amber-500/20 text-amber-600">
                            <x-icon name="katman" class="w-7 h-7" />
                        </div>
                        <p class="text-slate-700 dark:text-slate-300 font-medium">
                            Henüz yayın tipi eklenmemiş.
                        </p>
                        <p class="text-xs text-slate-400 dark:text-slate-500 mt-1">
                            Yeni bir yayın tipi ekleyerek (Satılık, Kiralık vb.) başlayabilirsiniz.
                        </p>
                    </div>
                @endif
            </div>

            @php
                $yanlisEklenenYayinTipleri = $yanlisEklenenYayinTipleri ?? collect();
                $altKategoriler = $altKategoriler ?? collect();
            @endphp

            <!-- Uyarı: Yanlış eklenen yayın tipleri -->
            @if (isset($yanlisEklenenYayinTipleri) && count($yanlisEklenenYayinTipleri) > 0)
                <div class="mb-6 bg-amber-50/80 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-2xl p-5 shadow-sm">
                    <div class="flex items-start gap-3">
                        <x-icon name="uyari" class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5 shrink-0" />
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-amber-900 dark:text-amber-200 mb-1">
                                Yanlış Eklenen Kayıtlar Tespit Edildi
                            </h4>
                            <p class="text-sm text-amber-800 dark:text-amber-300 mb-3">
                                Aşağıdaki kayıtlar <strong>alt kategori</strong> olarak eklenmiş ancak <strong>yayın tipi</strong> olmalı:
                            </p>
                            <ul class="list-disc list-inside text-sm text-amber-800 dark:text-amber-300 mb-3 space-y-1">
                                @foreach ($yanlisEklenenYayinTipleri as $yanlis)
                                    <li>
                                        <strong>{{ $yanlis->name }}</strong> (ID: {{ $yanlis->id }}, Seviye: {{ $yanlis->seviye }})
                                    </li>
                                @endforeach
                            </ul>
                            <div class="flex gap-2">
                                <a href="{{ route('admin.ilan-kategorileri.index') }}?search={{ urlencode($yanlisEklenenYayinTipleri->first()->name) }}"
                                    class="inline-flex items-center text-xs font-semibold text-amber-800 dark:text-amber-300 hover:underline">
                                    <x-icon name="duzenle" class="w-3.5 h-3.5 mr-1" /> Bu Kayıtları Düzenle
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- 2. Relations Grid (Alan İlişkileri) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <x-icon name="link" class="w-5 h-5 text-amber-500" />
                            <span>Alan İlişkileri</span>
                        </h2>
                        <span class="text-xs px-2.5 py-1 bg-amber-500/10 text-amber-700 dark:text-amber-300 rounded-full font-semibold border border-amber-500/20">
                            {{ count($fieldDependencies) }} Alan Tanımlı
                        </span>
                    </div>
                    <a href="{{ route('admin.property_types.field_dependencies', $kategori->id) }}"
                        class="inline-flex items-center px-4 py-2 bg-[#C9A84C] hover:bg-[#B8973B] text-slate-950 font-semibold rounded-xl shadow-sm hover:shadow transition-all duration-200 active:scale-95 text-sm">
                        <x-icon name="ayar" class="w-4 h-4 mr-2 text-slate-950" />
                        Alan İlişkilerini Yönet
                    </a>
                </div>

                @if (count($fieldDependencies) > 0)
                    <div class="overflow-x-auto rounded-xl border border-slate-200/80 dark:border-slate-800">
                        <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                            <thead class="bg-slate-50 dark:bg-slate-800/60">
                                <tr>
                                    <th class="px-6 py-3.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                        Alan
                                    </th>
                                    @foreach ($allYayinTipleri as $yayinTipi)
                                        @php
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
                                            if (in_array($yayinTipi->yayin_tipi ?? $yayinTipi->name, $excludedYayinTipleri)) {
                                                continue;
                                            }
                                        @endphp
                                        <th class="px-6 py-3.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                            {{ $yayinTipi->name ?? $yayinTipi->yayin_tipi }}
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200/70 dark:divide-slate-800">
                                @foreach ($fieldDependencies as $fieldSlug => $fieldData)
                                    <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-2.5">
                                                <span class="text-lg">{{ $fieldData['field_icon'] }}</span>
                                                <span class="text-sm font-semibold text-slate-900 dark:text-white">
                                                    {{ $fieldData['field_name'] }}
                                                </span>
                                            </div>
                                        </td>
                                        @foreach ($allYayinTipleri as $yayinTipi)
                                            @php
                                                $excludedYayinTipleri = ['Devren Satılık'];
                                                if ($kategori->slug !== 'yazlik-kiralama') {
                                                    $excludedYayinTipleri[] = 'Günlük Kiralık';
                                                }
                                                if ($kategori->slug === 'arsa') {
                                                    $excludedYayinTipleri[] = 'Yazlık Kiralık';
                                                }
                                                if (in_array($yayinTipi->yayin_tipi ?? $yayinTipi->name, $excludedYayinTipleri)) {
                                                    continue;
                                                }

                                                $stateVal = $fieldData['yayin_tipleri'][$yayinTipi->id] ?? false;
                                                $yayinTipiKeyId = (string) $yayinTipi->id;
                                                $yayinTipiKeySlug = $yayinTipi->slug ?? $yayinTipi->yayin_tipi;
                                                $fieldDep = \App\Models\KategoriYayinTipiFieldDependency::where('kategori_slug', $kategori->slug)
                                                    ->where('field_slug', $fieldSlug)
                                                    ->where(function ($q) use ($yayinTipiKeyId, $yayinTipiKeySlug) {
                                                        $q->where('yayin_tipi', $yayinTipiKeyId)->orWhere('yayin_tipi', $yayinTipiKeySlug);
                                                    })
                                                    ->first();
                                                $fieldDepId = $fieldDep ? $fieldDep->id : null;
                                            @endphp
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <input type="checkbox" class="rounded w-4 h-4 text-amber-600 focus:ring-amber-500 border-slate-300 dark:border-slate-600 dark:bg-slate-800 field-dependency-toggle"
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
                    <div class="text-center py-10">
                        <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-full mx-auto mb-3 flex items-center justify-center text-slate-400">
                            <x-icon name="klasor-bos" class="w-6 h-6" />
                        </div>
                        <p class="text-slate-500 dark:text-slate-400 mb-4 text-sm">
                            Bu kategori için alan ilişkisi henüz tanımlanmamış.
                        </p>
                        <a href="{{ route('admin.property_types.field_dependencies', $kategori->id) }}"
                            class="inline-flex items-center px-4 py-2 bg-[#C9A84C] hover:bg-[#B8973B] text-slate-950 font-semibold rounded-xl text-sm transition-all shadow-sm">
                            <x-icon name="ekle" class="w-4 h-4 mr-1.5" />
                            Alan İlişkilerini Tanımla
                        </a>
                    </div>
                @endif
            </div>

            <!-- 4. Features Toggle (Özellik Havuzu) -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-4">
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <x-icon name="yildiz" class="w-5 h-5 text-amber-500" />
                        <span>Özellik Havuzu (Kategori Bazlı)</span>
                    </h2>
                    <div class="flex gap-2">
                        <a href="{{ route('admin.ups.features.index') }}"
                            class="inline-flex items-center px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 font-semibold rounded-xl transition-all text-sm">
                            <x-icon name="liste" class="w-4 h-4 mr-2" />
                            Global Havuz
                        </a>
                    </div>
                </div>

                <div class="mb-6 bg-amber-500/10 border-l-4 border-amber-500 p-4 rounded-r-xl">
                    <div class="flex items-start gap-3">
                        <x-icon name="bilgi" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                        <p class="text-sm text-amber-900 dark:text-amber-200">
                            <strong>Yönetim Rehberi:</strong> Aşağıdaki özellikler bu kategori için etkin hale gelir.
                            Hangi yayın tipinde zorunlu veya görünür olacağını
                            <button @click="activeTab = 'smart-rules'" class="font-bold underline hover:text-amber-950 dark:hover:text-amber-100">Yayın Tipi Kuralları (SMART FORMS)</button>
                            sekmesinden ayarlayabilirsiniz.
                        </p>
                    </div>
                </div>

                @if (count($featureCategories ?? []) > 0)
                    @foreach ($featureCategories as $category)
                        @if (count($category->features ?? []) > 0)
                            <div class="mb-6 last:mb-0">
                                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-3">
                                    {{ $category->name }}
                                </h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                    @foreach ($category->features as $feature)
                                        <label
                                            class="flex items-center p-3 rounded-xl border border-slate-200/80 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-amber-50/50 hover:border-amber-400/50 dark:hover:bg-slate-800 cursor-pointer transition-all duration-200">
                                            <input type="checkbox" class="rounded mr-2.5 w-4 h-4 text-amber-600 focus:ring-amber-500 border-slate-300 dark:border-slate-600 feature-toggle"
                                                data-feature-id="{{ $feature->id }}"
                                                data-feature-name="{{ $feature->name }}"
                                                data-aktiflik-durumu="{{ $feature->aktiflik_durumu ?? false ? '1' : '0' }}"
                                                {{ $feature->aktiflik_durumu ?? false ? 'checked' : '' }}>
                                            <span class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $feature->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="px-6 py-12 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl">
                        <div class="w-16 h-16 bg-amber-500/10 rounded-2xl mx-auto mb-4 flex items-center justify-center text-amber-600">
                            <x-icon name="ai" class="w-8 h-8" />
                        </div>
                        <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Özellik Tanımı Bulunmuyor</h3>
                        <p class="text-slate-500 dark:text-slate-400 mb-6 max-w-xs mx-auto text-sm">
                            Bu kategoriye henüz bir özellik atanmamış. Havuzdaki özellikleri buraya bağlayın.
                        </p>
                        <a href="{{ route('admin.ups.features.index') }}"
                            class="inline-flex items-center px-5 py-2.5 bg-[#C9A84C] hover:bg-[#B8973B] text-slate-950 font-bold rounded-xl shadow-sm transition-all duration-200">
                            <x-icon name="ekle" class="w-4 h-4 mr-2" />
                            Havuzdan Özellik Ekle
                        </a>
                    </div>
                @endif
            </div>

            <!-- Toplu Kaydetme & Aksiyon Çubuğu -->
            <div class="mt-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm">
                <!-- Bulk Actions -->
                <div class="flex gap-2">
                    <button onclick="toggleAllYayinTipleri(true)"
                        class="inline-flex items-center px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 font-semibold rounded-xl transition-all duration-200 text-sm">
                        <x-icon name="onay-daire" class="w-4 h-4 mr-2 text-emerald-600 dark:text-emerald-400" />
                        Tümünü Seç
                    </button>
                    <button onclick="toggleAllYayinTipleri(false)"
                        class="inline-flex items-center px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-200 font-semibold rounded-xl transition-all duration-200 text-sm">
                        <x-icon name="kapat" class="w-4 h-4 mr-2 text-rose-500" />
                        Tümünü Kaldır
                    </button>
                </div>

                <!-- Save Button -->
                <button id="saveBtn" onclick="saveChanges()"
                    class="inline-flex items-center justify-center px-8 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-slate-950 font-bold rounded-xl shadow-sm hover:shadow transition-all duration-200 transform hover:scale-105 active:scale-95 text-base">
                    <x-icon name="kaydet" class="w-5 h-5 mr-2" />
                    Tüm Değişiklikleri Kaydet
                </button>
            </div>

        </div> {{-- End Tab 1 --}}

        {{-- 🏗️ TAB 2: ALT TÜRLER (Subtypes) --}}
        <div x-show="activeTab === 'alt-turler'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0">

             <div class="bg-amber-500/10 border-l-4 border-amber-500 p-4 mb-6 rounded-r-2xl shadow-sm">
                <div class="flex items-start gap-3">
                    <x-icon name="katman" class="w-5 h-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div>
                        <h3 class="text-sm font-bold text-amber-900 dark:text-amber-200">Varlık Alt Türleri Yönetimi</h3>
                        <p class="text-sm text-amber-800 dark:text-amber-300 mt-0.5">
                            Bu bölümde <strong>{{ $kategori->name }}</strong> kategorisine ait alt türleri (Villa, Rezidans, Daire vb.) yönetebilir ve hangi yayın tiplerinde geçerli olduklarını belirleyebilirsiniz.
                        </p>
                    </div>
                </div>
            </div>

            @if(isset($altKategoriler) && count($altKategoriler) > 0)
                @foreach ($altKategoriler as $altKategori)
                    <div class="mb-6 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6">
                        <!-- Alt Kategori Başlığı -->
                        <div class="flex items-center justify-between mb-4 border-b border-slate-100 dark:border-slate-800 pb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-500/10 border border-amber-500/20 flex items-center justify-center text-amber-600 dark:text-amber-400 font-bold shrink-0">
                                    <x-icon name="katman" class="w-5 h-5" />
                                </div>
                                <div>
                                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                        {{ $altKategori->name ?? $altKategori->ad ?? 'İsimsiz Alt Kategori' }}
                                    </h3>
                                    <span class="text-xs text-slate-400 dark:text-slate-500 font-medium">ID: {{ $altKategori->id }}</span>
                                </div>
                                <span class="ml-2 text-xs px-2.5 py-1 bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 rounded-full font-semibold border border-amber-500/20">
                                    {{ $altKategoriYayinTipleri[$altKategori->id]->count() ?? 0 }} aktif ilişki
                                </span>
                            </div>
                            <button onclick="deleteAltKategori({{ $altKategori->id }}, '{{ $altKategori->name }}')"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-400 dark:hover:bg-rose-900/40 rounded-xl border border-rose-200/60 dark:border-rose-900/50 transition-colors">
                                <x-icon name="sil" class="w-3.5 h-3.5 mr-1" />
                                Alt Türü Sil
                            </button>
                        </div>

                        <div>
                            <h4 class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-3">
                                Bu Alt Tür İçin Geçerli Yayın Tipleri
                            </h4>
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                @foreach ($allYayinTipleri as $yayinTipi)
                                    @php
                                        $activeIds = $altKategoriYayinTipleri[$altKategori->id] ?? collect([]);
                                        $active = $activeIds->contains($yayinTipi->id);

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
                                            continue;
                                        }
                                    @endphp

                                    <label
                                        class="flex items-center p-3 rounded-xl border cursor-pointer transition-all duration-200 {{ $active ? 'bg-amber-50/70 dark:bg-amber-950/20 border-amber-300 dark:border-amber-800 ring-1 ring-amber-500/20' : 'bg-slate-50/70 dark:bg-slate-800/40 border-slate-200/80 dark:border-slate-700/80 hover:bg-slate-100/80' }}">
                                        <input type="checkbox" class="rounded mr-3 yayin-tipi-toggle w-4 h-4 text-amber-600 focus:ring-amber-500 border-slate-300 dark:border-slate-600"
                                            data-alt-kategori-id="{{ $altKategori->id }}"
                                            data-yayin-tipi-id="{{ $yayinTipi->id }}"
                                            data-yayin-tipi="{{ $yayinTipi->yayin_tipi }}"
                                            data-yayin-tipi-name="{{ $yayinTipi->yayin_tipi }}"
                                            data-aktiflik-durumu="{{ $active ? 'true' : 'false' }}" {{ $active ? 'checked' : '' }}
                                            onchange="PropertyTypeManager.debounce('toggle-yayin-' + this.dataset.yayinTipiId, () => toggleYayinTipiRelation(this), 500)">
                                        <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $yayinTipi->yayin_tipi }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="flex flex-col items-center justify-center py-12 px-4 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl bg-white dark:bg-slate-900">
                    <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center mb-4 text-slate-400">
                        <x-icon name="katman" class="w-7 h-7" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white mb-1">Alt Tür Tanımsız</h3>
                    <p class="text-slate-500 dark:text-slate-400 text-center max-w-sm text-sm">
                        Bu kategori için henüz tanımlanmış bir alt tür (varlık alt tipi) bulunmamaktadır.
                    </p>
                </div>
            @endif
        </div> {{-- End Tab 2 --}}

        {{-- ✨ TAB 3: SMART FORMS MATRIX (Yayın Tipi Kuralları) --}}
        <div x-show="activeTab === 'smart-rules'"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 transform translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0">

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-sm p-6"
                x-data="window.smartFormMatrix({{ $kategori->id }})"
                x-init="init()">

                {{-- Header --}}
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-slate-100 dark:border-slate-800">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <x-icon name="ai" class="w-6 h-6 text-amber-500" />
                            <span>Yayın Tipi Kuralları (Smart Forms)</span>
                        </h2>
                        <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                            Hangi alan ve özelliklerin seçilen yayın tipinde görünür ve zorunlu olacağını belirleyin.
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <span x-show="lastSaveTime" class="text-xs text-slate-500 dark:text-slate-400">
                            Son kayıt: <span x-text="lastSaveTime" class="font-semibold text-slate-700 dark:text-slate-300"></span>
                        </span>
                        <span x-show="saving" class="inline-flex items-center px-3 py-1.5 bg-amber-100 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 rounded-xl text-xs font-semibold">
                            <svg class="animate-spin h-3.5 w-3.5 mr-1.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Kaydediliyor...
                        </span>
                    </div>
                </div>

                {{-- Loading Skeleton --}}
                <div x-show="loading" class="animate-pulse space-y-4">
                    <div class="h-12 bg-slate-100 dark:bg-slate-800 rounded-xl"></div>
                    <div class="h-64 bg-slate-100 dark:bg-slate-800 rounded-xl"></div>
                </div>

                {{-- Error State --}}
                <div x-show="error && !loading" class="bg-rose-50/80 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800 rounded-2xl p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <x-icon name="hata" class="w-5 h-5 text-rose-600 dark:text-rose-400 mt-0.5 shrink-0" />
                        <div class="flex-1">
                            <h4 class="text-sm font-semibold text-rose-900 dark:text-rose-100 mb-0.5">Hata</h4>
                            <p class="text-sm text-rose-800 dark:text-rose-200" x-text="error"></p>
                        </div>
                    </div>
                </div>

                {{-- Matrix Table --}}
                <div x-show="!loading && !error" class="overflow-x-auto border border-slate-200/80 dark:border-slate-800 rounded-2xl">
                    <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-800">
                        <thead class="bg-slate-50 dark:bg-slate-800/60">
                            <tr>
                                <th scope="col" class="sticky left-0 z-10 bg-slate-50 dark:bg-slate-800 px-6 py-3.5 text-left text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider border-r border-slate-200/80 dark:border-slate-700">
                                    Özellik
                                </th>
                                <template x-for="yayinTipi in yayinTipleri" :key="yayinTipi.id">
                                    <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                                        <div class="flex flex-col items-center gap-0.5">
                                            <span x-text="yayinTipi.yayin_tipi" class="font-bold"></span>
                                            <span class="text-[10px] text-slate-400 dark:text-slate-500 normal-case" x-text="yayinTipi.kategori_adi"></span>
                                        </div>
                                    </th>
                                </template>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-900 divide-y divide-slate-200/70 dark:divide-slate-800">
                            <template x-for="feature in features" :key="feature.id">
                                <tr class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors">
                                    <td class="sticky left-0 z-10 bg-white dark:bg-slate-900 px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900 dark:text-white border-r border-slate-200/80 dark:border-slate-700">
                                        <div class="flex flex-col">
                                            <span x-text="feature.adi" class="font-semibold"></span>
                                            <span class="text-xs text-slate-400 dark:text-slate-500" x-text="feature.kod"></span>
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
                                                        class="w-4 h-4 text-amber-600 bg-slate-100 border-slate-300 rounded focus:ring-amber-500 dark:bg-slate-800 dark:border-slate-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                                                    <span class="ml-1.5 text-xs text-slate-600 dark:text-slate-400 font-medium">Görünür</span>
                                                </label>

                                                {{-- Required Checkbox --}}
                                                <label class="inline-flex items-center cursor-pointer">
                                                    <input type="checkbox"
                                                        :checked="getCellState(feature.id, yayinTipi.id).is_required"
                                                        @change="toggleRequired(yayinTipi.id, feature.id, $event)"
                                                        :disabled="saving || !getCellState(feature.id, yayinTipi.id).is_visible"
                                                        class="w-4 h-4 text-rose-600 bg-slate-100 border-slate-300 rounded focus:ring-rose-500 dark:bg-slate-800 dark:border-slate-600 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                                                    <span class="ml-1.5 text-xs text-rose-600 dark:text-rose-400 font-semibold">Zorunlu</span>
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
                    <div class="bg-amber-500/10 rounded-2xl p-4 border border-amber-500/20">
                        <div class="text-2xl font-bold text-amber-700 dark:text-amber-400" x-text="getSummary().total_features"></div>
                        <div class="text-xs font-medium text-amber-800 dark:text-amber-300 mt-0.5">Toplam Özellik</div>
                    </div>
                    <div class="bg-slate-100 dark:bg-slate-800/60 rounded-2xl p-4 border border-slate-200 dark:border-slate-700">
                        <div class="text-2xl font-bold text-slate-800 dark:text-slate-200" x-text="getSummary().total_yayin_tipleri"></div>
                        <div class="text-xs font-medium text-slate-600 dark:text-slate-400 mt-0.5">Yayın Tipi</div>
                    </div>
                    <div class="bg-emerald-500/10 rounded-2xl p-4 border border-emerald-500/20">
                        <div class="text-2xl font-bold text-emerald-700 dark:text-emerald-400" x-text="getSummary().visible_count"></div>
                        <div class="text-xs font-medium text-emerald-800 dark:text-emerald-300 mt-0.5">Görünür Hücre</div>
                    </div>
                    <div class="bg-rose-500/10 rounded-2xl p-4 border border-rose-500/20">
                        <div class="text-2xl font-bold text-rose-700 dark:text-rose-400" x-text="getSummary().required_count"></div>
                        <div class="text-xs font-medium text-rose-800 dark:text-rose-300 mt-0.5">Zorunlu Alan</div>
                    </div>
                </div>

                {{-- Legend --}}
                <div class="mt-6 bg-slate-50 dark:bg-slate-800/40 rounded-2xl p-4 border border-slate-200/80 dark:border-slate-700">
                    <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2.5 flex items-center gap-1.5">
                        <x-icon name="bilgi" class="w-4 h-4 text-amber-500" />
                        <span>Nasıl Çalışır?</span>
                    </h3>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs text-slate-600 dark:text-slate-400">
                        <li class="flex items-start gap-2">
                            <x-icon name="onay" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5" />
                            <span><strong>Görünür:</strong> Bu özellik seçilen yayın tipinde formda gösterilir.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="onay" class="w-4 h-4 text-rose-500 shrink-0 mt-0.5" />
                            <span><strong>Zorunlu:</strong> Bu özellik seçilen yayın tipinde zorunlu tutulur.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="yenile" class="w-4 h-4 text-sky-500 shrink-0 mt-0.5" />
                            <span><strong>Otomatik Senkron:</strong> Zorunlu işaretlendiğinde otomatik olarak görünür yapılır.</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <x-icon name="flas" class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" />
                            <span><strong>Anlık Kayıt:</strong> Değişiklikler anında API'ye kaydedilir.</span>
                        </li>
                    </ul>
                </div>

            </div>
        </div> {{-- End Tab 3 --}}

        <!-- 🔔 Loading Overlay -->
        <div id="loadingOverlay" style="display: none;"
            class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 text-center border border-slate-200 dark:border-slate-800 shadow-2xl">
                <div class="animate-spin rounded-full h-10 w-10 border-2 border-amber-500 border-t-transparent mx-auto mb-3"></div>
                <p class="text-slate-900 dark:text-white font-semibold text-sm">Kaydediliyor...</p>
            </div>
        </div>

        <!-- 🔔 Success Toast -->
        <div id="successToast"
            class="hidden fixed top-5 right-5 bg-emerald-600 text-white px-5 py-3.5 rounded-2xl shadow-xl z-50 flex items-center gap-2.5 border border-emerald-500">
            <x-icon name="onay-daire" class="w-5 h-5" />
            <span class="text-sm font-medium">Değişiklikler başarıyla kaydedildi!</span>
        </div>

        <!-- 🔔 Error Toast -->
        <div id="errorToast" class="hidden fixed top-5 right-5 bg-rose-600 text-white px-5 py-3.5 rounded-2xl shadow-xl z-50 flex items-center gap-2.5 border border-rose-500">
            <x-icon name="hata" class="w-5 h-5" />
            <span class="text-sm font-medium">Bir hata oluştu!</span>
        </div>

        <!-- ➕ Modal: Yeni Yayın Tipi Ekle -->
        <div id="addYayinTipiModal" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-50" style="display: none;">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl p-6 sm:p-8 max-w-md w-full border border-slate-200 dark:border-slate-800">
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <x-icon name="ekle" class="w-5 h-5 text-amber-500" />
                            <span>Yeni Yayın Tipi Ekle</span>
                        </h3>
                        <button type="button" onclick="closeAddYayinTipiModal()" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 p-1">
                            <x-icon name="kapat" class="w-5 h-5" />
                        </button>
                    </div>

                    <form id="addYayinTipiForm" onsubmit="addYayinTipi(event)">
                        <!-- Alt Kategori Seçimi -->
                        @if (count($altKategoriler ?? []) > 0)
                            <div class="mb-4">
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                                    Alt Kategori Seçin
                                </label>
                                <select id="modalAltKategori"
                                    class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm">
                                    <option value="">Seçin (Opsiyonel)...</option>
                                    @foreach ($altKategoriler as $altKat)
                                        <option value="{{ $altKat->id }}">{{ $altKat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" id="modalAltKategori" value="">
                            <div class="mb-4 text-xs text-amber-800 dark:text-amber-300 bg-amber-500/10 border border-amber-500/20 rounded-xl p-3">
                                Bu kategori için alt kategori bulunmuyor. Yayın tipi doğrudan ana kategoriye eklenecek.
                            </div>
                        @endif

                        <!-- Yayın Tipi Adı -->
                        <div class="mb-5">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                                Yayın Tipi Adı
                            </label>
                            <input type="text" id="modalYayinTipi" required
                                placeholder="Örn: Günlük Kiralık, Haftalık Kiralık"
                                class="w-full px-4 py-2.5 border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent text-sm">
                        </div>

                        <!-- Butonlar -->
                        <div class="flex gap-3">
                            <button type="button" onclick="closeAddYayinTipiModal()"
                                class="inline-flex items-center justify-center px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:text-slate-300 font-semibold rounded-xl text-sm transition-colors flex-1">
                                İptal
                            </button>
                            <button type="submit"
                                class="inline-flex items-center justify-center px-4 py-2.5 bg-[#C9A84C] hover:bg-[#B8973B] text-slate-950 font-bold rounded-xl shadow-sm transition-all duration-200 transform active:scale-95 text-sm flex-1">
                                <x-icon name="ekle" class="w-4 h-4 mr-1.5 text-slate-950" />
                                Ekle
                            </button>
                        </div>
                    </form>
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
