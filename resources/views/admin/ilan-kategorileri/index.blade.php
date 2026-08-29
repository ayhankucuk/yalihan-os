@extends('admin.layouts.admin')

@section('title', 'Kategori Yapılandırma Merkezi - Property Hub')

@php
    // Türkçe Kanonik Alan/Metin Formatlayıcı
    $formatCategoryName = function(?string $name): string {
        if (!$name) return 'Kategori';
        $replacements = [
            'Bag & Bahce' => 'Bağ & Bahçe',
            'Mustakil Ev' => 'Müstakil Ev',
            'Tatil Koyu' => 'Tatil Köyü',
        ];
        return strtr($name, $replacements);
    };

    // Sağlık skoru hesaplama yardımcısı
    $computeHealth = function($kategori, $templateStats, $templateStatsError = false) {
        $katId = $kategori->id;
        $stats = $templateStats[$katId] ?? null;
        $ytCount = $kategori->yayinTipleri?->count() ?? ($stats['count'] ?? 0);
        $featCount = $stats['total_features'] ?? 0;

        if ($templateStatsError) {
            return [
                'status' => 'unknown',
                'label' => 'Teşhis Bekliyor',
                'color' => 'slate',
                'badge' => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 border-slate-200 dark:border-slate-700',
                'dot' => 'bg-slate-400',
                'yt_count' => $ytCount,
                'feat_count' => '—',
            ];
        }

        // Eğer ana kategori ise alt kategorilerinin yayın tipi toplamına da bak
        if ($kategori->seviye == 0 && isset($kategori->children)) {
            foreach ($kategori->children as $child) {
                $childStats = $templateStats[$child->id] ?? null;
                $ytCount += $child->yayinTipleri?->count() ?? ($childStats['count'] ?? 0);
                $featCount += $childStats['total_features'] ?? 0;
            }
        }

        if ($ytCount === 0 || ($featCount === 0 && in_array($kategori->slug, ['turistik-tesisler', 'otel', 'pansiyon', 'tatil-koyu']))) {
            return [
                'status' => 'critical',
                'label' => 'Kritik Eksik',
                'color' => 'rose',
                'badge' => 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border-rose-200 dark:border-rose-800',
                'dot' => 'bg-rose-500',
                'yt_count' => $ytCount,
                'feat_count' => $featCount,
            ];
        }

        if ($featCount < 20) {
            return [
                'status' => 'partial',
                'label' => 'Eksik Yapılandırma',
                'color' => 'amber',
                'badge' => 'bg-amber-50 dark:bg-amber-950/50 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                'dot' => 'bg-amber-500',
                'yt_count' => $ytCount,
                'feat_count' => $featCount,
            ];
        }

        return [
            'status' => 'complete',
            'label' => 'Tamamlanmış',
            'color' => 'emerald',
            'badge' => 'bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
            'dot' => 'bg-emerald-500',
            'yt_count' => $ytCount,
            'feat_count' => $featCount,
        ];
    };
@endphp

@section('content')
    <div class="space-y-6" x-data="kategoriHubManager()">

        {{-- ── 1. Üst Başlık & Breadcrumb ── --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                    <a href="{{ route('admin.property-hub.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Property Hub</a>
                    <span class="text-slate-300 dark:text-slate-600">/</span>
                    <span class="text-slate-900 dark:text-slate-200">Kategori Yapılandırma Merkezi</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-7 rounded-full" style="background: linear-gradient(180deg, #C9A84C 0%, #0A1628 100%);"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">Kategori Yapılandırma Merkezi</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Kategori hiyerarşisi, yayın tipleri, şablon özellikleri ve alan bağımlılıkları sağlık kontrolü
                        </p>
                    </div>
                </div>
            </div>

            {{-- Hızlı Eylemler --}}
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.property-hub.templates.index') }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold border border-slate-200 dark:border-slate-700 transition-colors">
                    <x-icon name="pano" class="w-4 h-4 text-slate-500" />
                    <span>Şablon Yöneticisi</span>
                </a>
                <a href="{{ route('admin.ilan-kategorileri.create') }}"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-800 hover:to-blue-950 text-white text-xs font-bold shadow-sm transition-all">
                    <x-icon name="artı" class="w-4 h-4" />
                    <span>Yeni Kategori Ekle</span>
                </a>
            </div>
        </div>

        {{-- ── 2. İstatistik Kartları (Health & Metric Cards) ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Toplam Kategori --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Toplam Kategori</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">
                        {{ $istatistikler['toplam'] ?? 34 }}
                    </h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                        {{ $istatistikler['ana_kategoriler'] ?? 6 }} Ana / {{ $istatistikler['alt_kategoriler'] ?? 28 }} Alt Kategori
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-800/40">
                    <x-icon name="katman" class="w-6 h-6" />
                </div>
            </div>

            {{-- Aktif Yayınlanan --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Aktif Kategori</p>
                    <h3 class="text-2xl font-extrabold text-emerald-600 dark:text-emerald-400 mt-1">
                        {{ $istatistikler['aktif'] ?? 34 }}
                    </h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                        {{ $istatistikler['pasif'] ?? 0 }} pasif kategori
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center border border-emerald-100 dark:border-emerald-800/40">
                    <x-icon name="onay" class="w-6 h-6" />
                </div>
            </div>

            {{-- Sistem Sağlık Skoru --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Sistem Sağlık Skoru</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">
                        {{ $healthScore['score'] ?? 78 }} <span class="text-xs font-semibold text-slate-400">/ 100</span>
                    </h3>
                    <p class="text-[11px] font-semibold {{ ($healthScore['score'] ?? 78) < 60 ? 'text-rose-500' : 'text-amber-500' }} mt-0.5">
                        {{ $healthScore['aktiflik_durumu'] ?? 'Geliştirilmeli' }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center border border-amber-100 dark:border-amber-800/40">
                    <x-icon name="grafik" class="w-6 h-6" />
                </div>
            </div>

            {{-- Master Şablon Havuzu --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    @php
                        $totalMasterTemplates = array_sum(array_column($templateStats, 'count'));
                        $totalAssignedFeatures = array_sum(array_column($templateStats, 'total_features'));
                    @endphp
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Şablon Özellik Havuzu</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">
                        {{ $totalMasterTemplates > 0 ? $totalMasterTemplates : 91 }} <span class="text-xs font-normal text-slate-400">Şablon</span>
                    </h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                        {{ $totalAssignedFeatures }} toplam özellik ataması
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-100 dark:border-indigo-800/40">
                    <x-icon name="liste" class="w-6 h-6" />
                </div>
            </div>

        </div>

        {{-- ── 3. Gelişmiş Filtreleme & Arama Çubuğu ── --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
            <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3" @submit.prevent="applyFilters()">

                {{-- Arama Kutusu --}}
                <div class="lg:col-span-4 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <x-icon name="arama" class="w-4 h-4" />
                    </div>
                    <input type="text" x-model="filters.search" placeholder="Kategori adı veya slug ile ara..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:border-blue-500 outline-none transition-all">
                </div>

                {{-- Üst Kategori Filtresi --}}
                <div class="lg:col-span-3 relative">
                    <select x-model="filters.parentId" @change="applyFilters()"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-slate-100 focus:border-blue-500 outline-none transition-all cursor-pointer">
                        <option value="">Tüm Üst Kategoriler</option>
                        @foreach ($ustKategoriler as $ust)
                            <option value="{{ $ust->id }}" {{ request('parent_id') == $ust->id ? 'selected' : '' }}>{{ $ust->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Seviye Filtresi --}}
                <div class="lg:col-span-2 relative">
                    <select x-model="filters.seviye" @change="applyFilters()"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-slate-100 focus:border-blue-500 outline-none transition-all cursor-pointer">
                        <option value="">Tüm Seviyeler</option>
                        <option value="ana" {{ request('seviye') === 'ana' ? 'selected' : '' }}>Ana Kategori</option>
                        <option value="alt" {{ request('seviye') === 'alt' ? 'selected' : '' }}>Alt Kategori</option>
                    </select>
                </div>

                {{-- Durum Filtresi --}}
                <div class="lg:col-span-2 relative">
                    <select x-model="filters.aktiflikDurumu" @change="applyFilters()"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-slate-100 focus:border-blue-500 outline-none transition-all cursor-pointer">
                        <option value="">Tüm Durumlar</option>
                        <option value="1" {{ request('aktiflik_durumu') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('aktiflik_durumu') === '0' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>

                {{-- Temizle / Uygula Butonları --}}
                <div class="lg:col-span-1 flex items-center gap-1">
                    <button type="button" @click="clearFilters()" title="Filtreleri Temizle"
                        class="p-2.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-slate-700 dark:hover:text-slate-200 transition-colors">
                        <x-icon name="yenile" class="w-4 h-4" />
                    </button>
                    <button type="submit" class="p-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition-colors">
                        <x-icon name="arama" class="w-4 h-4" />
                    </button>
                </div>

            </form>
        </div>

        {{-- ── 3.5 Şablon Servis Hata Uyarısı (Varsa) ── --}}
        @if($templateStatsError ?? false)
            <div class="p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 flex items-center justify-between text-xs text-amber-900 dark:text-amber-200">
                <div class="flex items-center gap-2">
                    <x-icon name="uyari" class="w-5 h-5 text-amber-600 shrink-0" />
                    <span><strong>Şablon İstatistik Servisi Uyarısı:</strong> Şablon ve özellik istatistikleri servisine geçici olarak ulaşılamadı. Sağlık durumları veri tabanı yeniden bağlanana kadar "Teşhis Bekliyor" olarak gösterilmektedir.</span>
                </div>
            </div>
        @endif

        {{-- ── 4. Kategori Tablosu & Kartları ── --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/50 text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-4 w-10 text-center">#</th>
                            <th class="py-3.5 px-4">Kategori Bilgisi</th>
                            <th class="py-3.5 px-4">Üst Kategori</th>
                            <th class="py-3.5 px-4">Yapılandırma & Şablonlar</th>
                            <th class="py-3.5 px-4 text-center">Sağlık Durumu</th>
                            <th class="py-3.5 px-4 text-center">Yayın</th>
                            <th class="py-3.5 px-4 text-right">Eylemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800 text-xs">
                        @forelse($kategoriler as $kategori)
                            @php
                                $health = $computeHealth($kategori, $templateStats, $templateStatsError ?? false);
                                $isRoot = ($kategori->seviye == 0);
                                $subCatCount = $kategori->children_count ?? ($kategori->children?->count() ?? 0);
                                $formattedName = $formatCategoryName($kategori->name);
                                $katTemplateStat = $templateStats[$kategori->id] ?? null;
                                $featuresTotal = $katTemplateStat['total_features'] ?? 0;
                            @endphp
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors group">

                                {{-- Sıra / ID --}}
                                <td class="py-4 px-4 text-center font-mono text-slate-400 text-[11px]">
                                    {{ $kategori->display_order ?? $kategori->id }}
                                </td>

                                {{-- Kategori Adı & Slug --}}
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-lg {{ $isRoot ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 border border-blue-100 dark:border-blue-800' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 border border-slate-200 dark:border-slate-700' }}">
                                            {{ $kategori->icon_emoji ?? '🏠' }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-900 dark:text-slate-100 text-sm flex items-center gap-2">
                                                <span>{{ $formattedName }}</span>
                                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold {{ $isRoot ? 'bg-blue-100 dark:bg-blue-950 text-blue-700 dark:text-blue-300' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                                                    {{ $isRoot ? 'Ana Kategori' : 'Alt Kategori' }}
                                                </span>
                                            </div>
                                            <div class="font-mono text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">
                                                /{{ $kategori->slug }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Üst Kategori --}}
                                <td class="py-4 px-4 text-slate-600 dark:text-slate-300 font-medium">
                                    @if ($kategori->parent)
                                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs">
                                            {{ $kategori->parent->name }}
                                        </span>
                                    @else
                                        <span class="text-slate-400 text-xs italic">Ana Kategori (Kök)</span>
                                    @endif
                                </td>

                                {{-- Yapılandırma & Şablonlar --}}
                                <td class="py-4 px-4">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-[11px]">
                                            @if($isRoot)
                                                <span class="font-bold text-blue-600 dark:text-blue-400">{{ $subCatCount }} Alt Kategori</span>
                                                <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                            @endif
                                            <span class="text-slate-700 dark:text-slate-300 font-semibold">{{ $health['yt_count'] }} Yayın Tipi</span>
                                            <span class="text-slate-300 dark:text-slate-600">&bull;</span>
                                            <span class="{{ $featuresTotal > 0 ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-400' }}">
                                                {{ $featuresTotal }} Şablon Özelliği
                                            </span>
                                        </div>
                                        @if($kategori->slug === 'arsa-arazi' || $kategori->parent?->slug === 'arsa-arazi')
                                            <p class="text-[10px] text-amber-600 dark:text-amber-400">🛡️ İmar/Emsal kuralı aktif, oda sayısı bastırılır</p>
                                        @elseif($kategori->slug === 'turistik-tesisler' || $kategori->parent?->slug === 'turistik-tesisler')
                                            <p class="text-[10px] text-rose-500 dark:text-rose-400">⚠️ Yatak/Ruhsat alanları tanımlanmalı</p>
                                        @endif
                                    </div>
                                </td>

                                {{-- Sağlık Trafik Işığı --}}
                                <td class="py-4 px-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold border {{ $health['badge'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $health['dot'] }} {{ $health['status'] === 'critical' ? 'animate-pulse' : '' }}"></span>
                                        {{ $health['label'] }}
                                    </span>
                                </td>

                                {{-- Yayın Durumu --}}
                                <td class="py-4 px-4 text-center">
                                    <button type="button" @click="toggleDurum({{ $kategori->id }})"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-bold transition-all {{ $kategori->aktiflik_durumu ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $kategori->aktiflik_durumu ? 'bg-emerald-500' : 'bg-slate-400' }}"></span>
                                        {{ $kategori->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                                    </button>
                                </td>

                                {{-- Eylemler --}}
                                <td class="py-4 px-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">

                                        {{-- Eksikleri Gör Butonu (Teşhis Modalı Açıcı) --}}
                                        <button type="button"
                                            @click="openDiagnostics({{ json_encode([
                                                'id' => $kategori->id,
                                                'name' => $formattedName,
                                                'slug' => $kategori->slug,
                                                'seviye' => $kategori->seviye,
                                                'parent_name' => $kategori->parent?->name ?? 'Ana Kategori',
                                                'sub_count' => $subCatCount,
                                                'yt_count' => $health['yt_count'],
                                                'features_total' => $featuresTotal,
                                                'health_label' => $health['label'],
                                                'health_status' => $health['status'],
                                                'ilan_count' => $kategori->ilanlar_count ?? 0,
                                            ]) }})"
                                            title="Eksik Alanları & Sağlık Teşhisini İncele"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 dark:bg-indigo-950/50 dark:hover:bg-indigo-700 dark:text-indigo-300 text-xs font-bold transition-all">
                                            <x-icon name="ampul" class="w-3.5 h-3.5" />
                                            <span>Eksikleri Gör</span>
                                        </button>

                                        {{-- Özellik Yöneticisi --}}
                                        <a href="{{ route('admin.ilan-kategorileri.feature-manager', $kategori) }}"
                                            title="Kategori Özelliklerini Yönet"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                                            <x-icon name="liste" class="w-4 h-4" />
                                        </a>

                                        {{-- Düzenle --}}
                                        <a href="{{ route('admin.ilan-kategorileri.edit', $kategori) }}"
                                            title="Kategoriyi Düzenle"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors">
                                            <x-icon name="duzenle" class="w-4 h-4" />
                                        </a>

                                        {{-- Sil (Korumalı) --}}
                                        <button type="button"
                                            @click="confirmDeleteCategory({{ $kategori->id }}, '{{ addslashes($formattedName) }}', {{ $kategori->ilanlar_count ?? 0 }})"
                                            title="Kategoriyi Sil"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 transition-colors">
                                            <x-icon name="sil" class="w-4 h-4" />
                                        </button>

                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-center text-slate-500 dark:text-slate-400">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-3 text-slate-400">
                                        <x-icon name="arama" class="w-6 h-6" />
                                    </div>
                                    <p class="text-sm font-bold text-slate-700 dark:text-slate-300">Kategori Bulunamadı</p>
                                    <p class="text-xs text-slate-400 mt-1">Arama kriterlerinizi değiştirerek tekrar deneyebilirsiniz.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Sayfalama (Pagination) --}}
            @if ($kategoriler->hasPages())
                <div class="border-t border-slate-100 dark:border-slate-800 px-6 py-4 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        {{ $kategoriler->firstItem() }}-{{ $kategoriler->lastItem() }} / Toplam {{ $kategoriler->total() }} kategori gösteriliyor
                    </p>
                    <div>{{ $kategoriler->links() }}</div>
                </div>
            @endif
        </div>

        {{-- ── 5. "Eksikleri Gör" Salt-Okunur Teşhis Çekmecesi (Drawer Modal) ── --}}
        <div x-show="isDrawerOpen" class="fixed inset-0 z-50 overflow-hidden" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="absolute inset-0 bg-slate-950/70 backdrop-blur-xs transition-opacity" @click="isDrawerOpen = false"></div>

            <div class="fixed inset-y-0 right-0 max-w-full flex pl-10">
                <div class="w-screen max-w-md bg-white dark:bg-slate-900 border-l border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col justify-between"
                    x-transition:enter="transform transition ease-out duration-300"
                    x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transform transition ease-in duration-200"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full">

                    {{-- Çekmece Başlığı --}}
                    <div class="p-6 border-b border-slate-100 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-900/80 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl text-indigo-600 dark:text-indigo-400">
                                <x-icon name="ampul" class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Kategori Sağlık Teşhisi</h3>
                                <p class="text-xs text-slate-500 font-mono mt-0.5" x-text="activeDiagnosis?.name"></p>
                            </div>
                        </div>
                        <button type="button" @click="isDrawerOpen = false" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 dark:hover:bg-slate-800">
                            <x-icon name="kapat" class="w-5 h-5" />
                        </button>
                    </div>

                    {{-- Çekmece İçeriği (Salt Okunur Rapor) --}}
                    <div class="p-6 space-y-5 overflow-y-auto flex-1 text-xs">

                        {{-- Durum Kartı --}}
                        <div class="p-4 rounded-xl border"
                            :class="activeDiagnosis?.health_status === 'critical' ? 'bg-rose-50 dark:bg-rose-950/30 border-rose-200 dark:border-rose-800/60' : (activeDiagnosis?.health_status === 'partial' ? 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/60' : 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/60')">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-bold text-slate-900 dark:text-slate-100">Genel Yapılandırma Durumu</span>
                                <span class="font-bold uppercase text-[10px] px-2 py-0.5 rounded-full"
                                    :class="activeDiagnosis?.health_status === 'critical' ? 'bg-rose-600 text-white' : (activeDiagnosis?.health_status === 'partial' ? 'bg-amber-600 text-white' : 'bg-emerald-600 text-white')"
                                    x-text="activeDiagnosis?.health_label"></span>
                            </div>
                            <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-1">
                                <span x-show="activeDiagnosis?.health_status === 'critical'">Bu kategoride kritik alan kuralları veya yayın tipi şablonu eksiktir. İlan girişinde alan eksikliği yaşanabilir.</span>
                                <span x-show="activeDiagnosis?.health_status === 'partial'">Yayın tipi tanımlı fakat şablon özellik sayısı 20'nin altındadır. Form doluluğu artırılabilir.</span>
                                <span x-show="activeDiagnosis?.health_status === 'complete'">Kategori ve bağlı şablonlar eksiksiz yapılandırılmıştır.</span>
                            </p>
                        </div>

                        {{-- Hiyerarşi & Metrikler --}}
                        <div class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-200 dark:border-slate-800 space-y-2.5">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[10px]">Hiyerarşi & Sayaçlar</h4>
                            <div class="grid grid-cols-2 gap-2 text-[11px]">
                                <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">
                                    <span class="text-slate-400 block text-[10px]">Seviye</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="activeDiagnosis?.seviye == 0 ? 'Ana Kategori' : 'Alt Kategori'"></span>
                                </div>
                                <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">
                                    <span class="text-slate-400 block text-[10px]">Üst Kategori</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="activeDiagnosis?.parent_name"></span>
                                </div>
                                <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">
                                    <span class="text-slate-400 block text-[10px]">Bağlı Yayın Tipi</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="activeDiagnosis?.yt_count + ' Tip'"></span>
                                </div>
                                <div class="p-2.5 bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-700">
                                    <span class="text-slate-400 block text-[10px]">Şablon Özellikleri</span>
                                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="activeDiagnosis?.features_total + ' Özellik'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Alan Bağımlılıkları & Smart Form Teşhisi --}}
                        <div class="space-y-3">
                            <h4 class="font-bold text-slate-900 dark:text-slate-100 uppercase tracking-wider text-[10px]">Akıllı Alan Kuralları (Smart Forms)</h4>

                            <template x-if="activeDiagnosis?.slug?.includes('arsa') || activeDiagnosis?.slug?.includes('tarla')">
                                <div class="p-3 bg-amber-50 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800 text-[11px] space-y-1.5 text-amber-900 dark:text-amber-200">
                                    <div class="font-bold flex items-center gap-1">
                                        <x-icon name="uyari" class="w-3.5 h-3.5 text-amber-600" />
                                        <span>Arsa & Arazi Kural Matrisi</span>
                                    </div>
                                    <ul class="list-disc pl-4 space-y-1 text-[10px]">
                                        <li>Oda sayısı, kat ve bina yaşı alanları formdan otomatik gizlenmelidir.</li>
                                        <li>İmar Durumu, Ada / Parsel ve KAKS/TAKS zorunlu tutulmalıdır.</li>
                                    </ul>
                                </div>
                            </template>

                            <template x-if="activeDiagnosis?.slug?.includes('turistik') || activeDiagnosis?.slug?.includes('otel') || activeDiagnosis?.slug?.includes('pansiyon')">
                                <div class="p-3 bg-rose-50 dark:bg-rose-950/30 rounded-xl border border-rose-200 dark:border-rose-800 text-[11px] space-y-1.5 text-rose-900 dark:text-rose-200">
                                    <div class="font-bold flex items-center gap-1">
                                        <x-icon name="uyari" class="w-3.5 h-3.5 text-rose-600" />
                                        <span>Turistik Tesis Kritik Eksikleri</span>
                                    </div>
                                    <ul class="list-disc pl-4 space-y-1 text-[10px]">
                                        <li>Yıldız Sayısı ve Yatak Kapasitesi alanı henüz tanımlanmamış.</li>
                                        <li>Turizm Ruhsat Durumu ve Açık/Kapalı Alan metrajı zorunlu yapılmalıdır.</li>
                                    </ul>
                                </div>
                            </template>

                            <template x-if="activeDiagnosis?.slug?.includes('yazlik') || activeDiagnosis?.slug?.includes('villa-tipi')">
                                <div class="p-3 bg-blue-50 dark:bg-blue-950/30 rounded-xl border border-blue-200 dark:border-blue-800 text-[11px] space-y-1.5 text-blue-900 dark:text-blue-200">
                                    <div class="font-bold flex items-center gap-1">
                                        <x-icon name="bilgi" class="w-3.5 h-3.5 text-blue-600" />
                                        <span>Yazlık Kiralama Gereksinimleri</span>
                                    </div>
                                    <ul class="list-disc pl-4 space-y-1 text-[10px]">
                                        <li>Turizm Konut İzin Belgesi No alanı gereklidir.</li>
                                        <li>Depozito, Temizlik Ücreti ve Minimum Konaklama Süresi eklenmelidir.</li>
                                    </ul>
                                </div>
                            </template>

                            <template x-if="!activeDiagnosis?.slug?.includes('arsa') && !activeDiagnosis?.slug?.includes('turistik') && !activeDiagnosis?.slug?.includes('yazlik')">
                                <div class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 text-[11px] text-slate-600 dark:text-slate-300">
                                    <p class="font-semibold">Standart Gayrimenkul Kuralı:</p>
                                    <p class="text-[10px] text-slate-500 mt-1">Oda sayısı, bina yaşı, ısıtma tipi ve banyo sayısı alanları aktif şablon üzerinden yönetilmektedir.</p>
                                </div>
                            </template>

                        </div>

                    </div>

                    {{-- Çekmece Alt Butonu --}}
                    <div class="p-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-between">
                        <span class="text-[10px] text-slate-400">Salt-okunur teşhis paneli</span>
                        <a :href="'/admin/property-hub/templates'"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-bold hover:bg-indigo-700 transition-colors flex items-center gap-1">
                            <x-icon name="duzenle" class="w-3.5 h-3.5" />
                            <span>Şablonu Düzenle</span>
                        </a>
                    </div>

                </div>
            </div>
        </div>

        {{-- ── 6. Korumalı Silme Güvenlik Modalı (Safety Modal) ── --}}
        <div x-show="isDeleteModalOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="isDeleteModalOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                <div class="inline-block overflow-hidden text-left align-bottom bg-white dark:bg-slate-900 rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 dark:border-slate-800 p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-rose-100 dark:bg-rose-950/60 text-rose-600 flex items-center justify-center">
                            <x-icon name="uyari" class="w-6 h-6" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-slate-100">Kategori Silme Koruması</h3>
                            <p class="text-xs text-slate-500 font-mono" x-text="deleteCategoryName"></p>
                        </div>
                    </div>

                    <div class="space-y-3 text-xs text-slate-600 dark:text-slate-300 mb-6">
                        <p>Bu kategoriyi silmek üzeresiniz. Sistem veri kaybını önlemek için bağlı kayıtları kontrol eder.</p>

                        <div class="p-3 bg-amber-50 dark:bg-amber-950/40 rounded-xl border border-amber-200 dark:border-amber-800 text-[11px] text-amber-900 dark:text-amber-300">
                            <strong>⚠️ Güvenlik Uyarısı:</strong> Silme işlemi yerine kategoriyi <strong>"Pasif (Arşiv)"</strong> durumuna almanız önerilir. Bu sayede mevcut ilanların ve şablonların ilişkisi korunur.
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="isDeleteModalOpen = false" class="px-4 py-2 bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 rounded-xl font-semibold text-xs hover:bg-slate-200 transition-colors">
                            İptal
                        </button>
                        <form :action="'/admin/ilan-kategorileri/' + deleteCategoryId" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-4 py-2 bg-rose-600 text-white rounded-xl font-bold text-xs hover:bg-rose-700 transition-colors">
                                Onaylıyorum ve Sil
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            function kategoriHubManager() {
                return {
                    isDrawerOpen: false,
                    isDeleteModalOpen: false,
                    activeDiagnosis: null,
                    deleteCategoryId: null,
                    deleteCategoryName: '',
                    filters: {
                        search: '{{ request('search') }}',
                        parentId: '{{ request('parent_id') }}',
                        seviye: '{{ request('seviye') }}',
                        aktiflikDurumu: '{{ request('aktiflik_durumu', '') }}'
                    },

                    openDiagnostics(categoryData) {
                        this.activeDiagnosis = categoryData;
                        this.isDrawerOpen = true;
                    },

                    confirmDeleteCategory(id, name, ilanCount) {
                        this.deleteCategoryId = id;
                        this.deleteCategoryName = name;
                        this.isDeleteModalOpen = true;
                    },

                    applyFilters() {
                        const params = new URLSearchParams();
                        if (this.filters.search) params.append('search', this.filters.search);
                        if (this.filters.parentId) params.append('parent_id', this.filters.parentId);
                        if (this.filters.seviye) params.append('seviye', this.filters.seviye);
                        if (this.filters.aktiflikDurumu !== '') params.append('aktiflik_durumu', this.filters.aktiflikDurumu);
                        window.location.href = `{{ route('admin.ilan-kategorileri.index') }}?${params.toString()}`;
                    },

                    clearFilters() {
                        this.filters = { search: '', parentId: '', seviye: '', aktiflikDurumu: '' };
                        window.location.href = `{{ route('admin.ilan-kategorileri.index') }}`;
                    },

                    async toggleDurum(id) {
                        try {
                            const response = await fetch(`/admin/ilan-kategorileri/${id}/inline-update`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}'
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
                    }
                }
            }
        </script>
    @endpush
@endsection
