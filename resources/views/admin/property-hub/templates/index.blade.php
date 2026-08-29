@extends('admin.layouts.admin')

@section('title', 'Şablon Yönetimi - Property Hub')

@php
    // Türkçe Gösterim Formatlayıcı (Veritabanı slug'larına dokunulmaz)
    $formatTurkishLabel = function(?string $text): string {
        if (!$text) return 'Şablon';
        $replacements = [
            'Satilik' => 'Satılık',
            'Kiralik' => 'Kiralık',
            'Gunluk' => 'Günlük',
            'Haftalik' => 'Haftalık',
            'Aylik' => 'Aylık',
            'Sezonluk' => 'Sezonluk',
            'Kat-karsiligi' => 'Kat Karşılığı',
            'Kat Karsiligi' => 'Kat Karşılığı',
            'Devren Satilik' => 'Devren Satılık',
            'Devren Kiralik' => 'Devren Kiralık',
            'Bag & Bahce' => 'Bağ & Bahçe',
        ];
        return strtr($text, $replacements);
    };

    $targetFeatureBaseline = 35; // Standart tam şablon özellik hedefi
    $emptyCount = $templates->where('feature_assignments_count', 0)->count();
    $partialCount = $templates->whereBetween('feature_assignments_count', [1, 19])->count();
    $healthyCount = $templates->where('feature_assignments_count', '>=', 20)->count();
@endphp

@section('content')
    <div x-data="templateManager()" class="space-y-6">

        {{-- ── 1. Üst Başlık & Breadcrumb ── --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-6 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm">
            <div>
                <div class="flex items-center gap-2 text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5 uppercase tracking-wider">
                    <a href="{{ route('admin.property-hub.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Property Hub</a>
                    <span class="text-slate-300 dark:text-slate-600">/</span>
                    <span class="text-slate-900 dark:text-slate-200">Şablon Yönetimi</span>
                </div>
                <div class="flex items-center gap-3">
                    <div class="w-2.5 h-7 rounded-full" style="background: linear-gradient(180deg, #C9A84C 0%, #0A1628 100%);"></div>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">Master Şablon Yöneticisi</h1>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                            Kategori ve yayın tipine göre özellik atamaları, doluluk oranları ve UPS standartları
                        </p>
                    </div>
                </div>
            </div>

            {{-- Hızlı Durum Rozetleri --}}
            <div class="flex flex-wrap items-center gap-2">
                @if($emptyCount > 0)
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800/60 text-rose-700 dark:text-rose-400 text-xs font-bold">
                        <x-icon name="uyari" class="w-4 h-4 text-rose-500" />
                        <span>{{ $emptyCount }} Eksik Yapılandırma</span>
                    </div>
                @endif
                <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs font-semibold border border-slate-200 dark:border-slate-700">
                    <x-icon name="pano" class="w-4 h-4 text-slate-500" />
                    <span>{{ $templates->count() }} Toplam Şablon</span>
                </div>
            </div>
        </div>

        {{-- ── 2. İstatistik Kartları (Quick Stats) ── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            {{-- Kategori Kartı --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ana Kategori</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">{{ $kategoriler->count() }}</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Sistem ana kategorisi</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center border border-blue-100 dark:border-blue-800/40">
                    <x-icon name="katman" class="w-6 h-6" />
                </div>
            </div>

            {{-- Master Şablon Kartı --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Yayın Tipi Şablonu</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">{{ $templates->count() }}</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Master yayın şablonu</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center border border-indigo-100 dark:border-indigo-800/40">
                    <x-icon name="liste" class="w-6 h-6" />
                </div>
            </div>

            {{-- Toplam Atama Kartı --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Toplam Özellik Ataması</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">{{ $templates->sum('feature_assignments_count') }}</h3>
                    <p class="text-[11px] text-slate-400 dark:text-slate-500 mt-0.5">Aktif eşleştirme kaydı</p>
                </div>
                <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center border border-amber-100 dark:border-amber-800/40">
                    <x-icon name="etiket" class="w-6 h-6" />
                </div>
            </div>

            {{-- Ortalama Doluluk & Sağlık Skoru --}}
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-5 border border-slate-200 dark:border-slate-800 shadow-sm flex items-center justify-between">
                <div>
                    @php
                        $totalCount = $templates->count();
                        $totalAssignments = $templates->sum('feature_assignments_count');
                        $avgFeatures = $totalCount > 0 ? round($totalAssignments / $totalCount, 1) : 0;
                        $healthPercent = min(100, round(($avgFeatures / $targetFeatureBaseline) * 100));
                    @endphp
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Ort. Özellik / Doluluk</p>
                    <h3 class="text-2xl font-extrabold text-slate-900 dark:text-slate-100 mt-1">{{ $avgFeatures }} <span class="text-xs font-semibold text-slate-400">/ {{ $targetFeatureBaseline }}</span></h3>
                    <p class="text-[11px] font-semibold {{ $healthPercent < 20 ? 'text-rose-500' : ($healthPercent < 60 ? 'text-amber-500' : 'text-emerald-500') }} mt-0.5">
                        Genel Doluluk Skoru: %{{ $healthPercent }}
                    </p>
                </div>
                <div class="w-12 h-12 rounded-xl {{ $healthPercent < 20 ? 'bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 border-rose-100' : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 border-emerald-100' }} flex items-center justify-center border">
                    <x-icon name="grafik" class="w-6 h-6" />
                </div>
            </div>

        </div>

        {{-- ── 3. Gelişmiş Filtreleme & Arama Çubuğu ── --}}
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 shadow-sm space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">

                {{-- Arama Kutusu --}}
                <div class="md:col-span-5 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <x-icon name="arama" class="w-4 h-4" />
                    </div>
                    <input type="text" x-model="search" placeholder="Şablon adı veya slug ile filtrele..."
                        class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:bg-white dark:focus:bg-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none transition-all">
                </div>

                {{-- Kategori Filtresi --}}
                <div class="md:col-span-3 relative">
                    <select x-model="categoryFilter"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-slate-100 focus:border-blue-500 outline-none transition-all cursor-pointer">
                        <option value="">Tüm Kategoriler ({{ $kategoriler->count() }})</option>
                        @foreach ($kategoriler as $kat)
                            <option value="{{ $kat->id }}">{{ $kat->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Yayın Tipi Filtresi --}}
                <div class="md:col-span-2 relative">
                    <select x-model="typeFilter"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-slate-100 focus:border-blue-500 outline-none transition-all cursor-pointer">
                        <option value="">Tüm Tipler</option>
                        <option value="satilik">Satılık</option>
                        <option value="kiralik">Kiralık</option>
                        <option value="gunluk">Günlük</option>
                        <option value="sezonluk">Sezonluk</option>
                        <option value="haftalik">Haftalık</option>
                        <option value="kat-karsiligi">Kat Karşılığı</option>
                    </select>
                </div>

                {{-- Durum Filtresi --}}
                <div class="md:col-span-2 relative">
                    <select x-model="statusFilter"
                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-sm text-slate-900 dark:text-slate-100 focus:border-blue-500 outline-none transition-all cursor-pointer">
                        <option value="all">Tüm Durumlar</option>
                        <option value="missing">⚠️ Eksik (0 Özellik)</option>
                        <option value="partial">⏳ Kısmi (1-19 Özellik)</option>
                        <option value="complete">✅ Tam (20+ Özellik)</option>
                    </select>
                </div>

            </div>

            {{-- Hızlı Durum Sekmeleri (Tabs) --}}
            <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
                <div class="flex items-center gap-1.5">
                    <button type="button" @click="statusFilter = 'all'"
                        :class="statusFilter === 'all' ? 'bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 font-bold' : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 hover:bg-slate-200'"
                        class="px-3 py-1.5 rounded-lg transition-colors">
                        Tümü ({{ $templates->count() }})
                    </button>
                    <button type="button" @click="statusFilter = 'missing'"
                        :class="statusFilter === 'missing' ? 'bg-rose-600 text-white font-bold' : 'bg-rose-50 dark:bg-rose-950/30 text-rose-700 dark:text-rose-400 hover:bg-rose-100 border border-rose-200/60 dark:border-rose-800/40'"
                        class="px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                        Eksik Yapılandırma ({{ $emptyCount }})
                    </button>
                    <button type="button" @click="statusFilter = 'partial'"
                        :class="statusFilter === 'partial' ? 'bg-amber-600 text-white font-bold' : 'bg-amber-50 dark:bg-amber-950/30 text-amber-700 dark:text-amber-400 hover:bg-amber-100 border border-amber-200/60 dark:border-amber-800/40'"
                        class="px-3 py-1.5 rounded-lg transition-colors">
                        Kısmi ({{ $partialCount }})
                    </button>
                    <button type="button" @click="statusFilter = 'complete'"
                        :class="statusFilter === 'complete' ? 'bg-emerald-600 text-white font-bold' : 'bg-emerald-50 dark:bg-emerald-950/30 text-emerald-700 dark:text-emerald-400 hover:bg-emerald-100 border border-emerald-200/60 dark:border-emerald-800/40'"
                        class="px-3 py-1.5 rounded-lg transition-colors">
                        Tamamlanmış ({{ $healthyCount }})
                    </button>
                </div>

                <div class="text-slate-400 text-[11px]">
                    <span x-text="filteredCount"></span> şablon listeleniyor
                </div>
            </div>
        </div>

        {{-- ── 4. Master Şablon Kartları Izgarası (Grid) ── --}}
        <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($templates as $yayinTipi)
                    @php
                        $count = $yayinTipi->feature_assignments_count;
                        $formattedName = $formatTurkishLabel($yayinTipi->name);
                        $categoryName = $yayinTipi->kategori?->name ?? 'Genel Kategori';
                        $kategoriIdVal = $yayinTipi->kategori_id ?? 0;
                        $percent = min(100, round(($count / $targetFeatureBaseline) * 100));

                        $isMissing = ($count == 0);
                        $isPartial = ($count > 0 && $count < 20);
                        $isHealthy = ($count >= 20);

                        $editUrl = route('admin.property-hub.templates.edit', [
                            'kategori_id' => $kategoriIdVal,
                            'yayin_tipi_id' => $yayinTipi->id
                        ]);
                        $showUrl = route('admin.property-hub.templates.show', $yayinTipi->id);
                    @endphp

                    <div x-show="matchesFilters('{{ addslashes($yayinTipi->name ?? '') }}', '{{ $yayinTipi->slug ?? '' }}', '{{ $kategoriIdVal }}', {{ $count }})"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                        data-template-id="{{ $yayinTipi->id }}"
                        class="group relative bg-white dark:bg-slate-900 border {{ $isMissing ? 'border-rose-200 dark:border-rose-900/50 bg-rose-50/10' : 'border-slate-200 dark:border-slate-800' }} rounded-2xl p-5 hover:shadow-lg hover:border-blue-500 dark:hover:border-blue-400 transition-all duration-300 flex flex-col justify-between">

                        {{-- Kart Üst Bilgisi --}}
                        <div>
                            {{-- Kategori & Tip Rozeti --}}
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <span class="px-2.5 py-1 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-[11px] font-semibold border border-slate-200 dark:border-slate-700">
                                    {{ $categoryName }}
                                </span>

                                @if($isMissing)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 dark:bg-rose-950/60 text-rose-700 dark:text-rose-300 border border-rose-200 dark:border-rose-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 animate-pulse"></span>
                                        Eksik Yapılandırma
                                    </span>
                                @elseif($isPartial)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        Geliştirilmeli
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        <x-icon name="onay" class="w-3 h-3 text-emerald-600" />
                                        Mühürlü & Aktif
                                    </span>
                                @endif
                            </div>

                            {{-- Şablon Başlığı & Slug --}}
                            <div class="mb-4">
                                <h3 class="font-bold text-slate-900 dark:text-slate-100 text-base group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    {{ $formattedName }}
                                </h3>
                                <p class="text-xs font-mono text-slate-400 dark:text-slate-500 mt-0.5">
                                    {{ $yayinTipi->slug }}
                                </p>
                            </div>

                            {{-- Doluluk Oranı & İlerleme Çubuğu --}}
                            <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-3 border border-slate-100 dark:border-slate-800 mb-4">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="font-semibold text-slate-600 dark:text-slate-300">Özellik Doluluğu</span>
                                    <span class="font-bold {{ $isMissing ? 'text-rose-600 dark:text-rose-400' : ($isPartial ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400') }}">
                                        {{ $count }} / {{ $targetFeatureBaseline }} (%{{ $percent }})
                                    </span>
                                </div>
                                <div class="w-full h-2 rounded-full bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-500 {{ $isMissing ? 'bg-rose-500' : ($isPartial ? 'bg-amber-500' : 'bg-emerald-500') }}"
                                         style="width: {{ max(4, $percent) }}%;"></div>
                                </div>
                                @if($isMissing)
                                    <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-2 font-medium flex items-center gap-1">
                                        <x-icon name="uyari" class="w-3.5 h-3.5 shrink-0" />
                                        Bu şablona henüz hiçbir özellik atanmamış.
                                    </p>
                                @endif
                            </div>
                        </div>

                        {{-- Kart Eylem Butonları --}}
                        <div class="pt-2 flex items-center gap-2 border-t border-slate-100 dark:border-slate-800">

                            {{-- Düzenle / Eksikleri Tamamla Butonu --}}
                            <a href="{{ $editUrl }}"
                               class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl text-xs font-bold transition-all {{ $isMissing ? 'bg-gradient-to-r from-blue-700 to-blue-900 text-white shadow-sm hover:from-blue-800 hover:to-blue-950' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-800 dark:text-slate-200' }}">
                                <x-icon name="duzenle" class="w-4 h-4" />
                                <span>{{ $isMissing ? 'Eksik Alanları Tamamla' : 'Şablonu Düzenle' }}</span>
                            </a>

                            {{-- AI ile Kontrollü Yapılandırma Butonu --}}
                            <button type="button"
                                @click="openAiModal({{ $yayinTipi->id }}, '{{ addslashes($categoryName) }}', '{{ addslashes($formattedName) }}', '{{ $kategoriIdVal }}')"
                                title="AI ile UPS Şablonu Çözümle & Mühürle"
                                class="px-3 py-2.5 bg-indigo-50 hover:bg-indigo-600 hover:text-white text-indigo-700 dark:bg-indigo-950/50 dark:hover:bg-indigo-700 dark:text-indigo-300 rounded-xl transition-all text-xs font-bold flex items-center gap-1">
                                <x-icon name="ai" class="w-4 h-4" />
                                <span class="hidden sm:inline">AI</span>
                            </button>

                            {{-- İncele / Önizleme Butonu --}}
                            <a href="{{ $showUrl }}"
                               title="Şablon Detayını İncele"
                               class="p-2.5 bg-slate-50 hover:bg-slate-200 dark:bg-slate-800/80 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 rounded-xl transition-colors">
                                <x-icon name="goster" class="w-4 h-4" />
                            </a>

                        </div>

                    </div>
                @endforeach
            </div>

            {{-- Arama / Filtre Boş Durumu (Empty State) --}}
            <div x-show="filteredCount === 0" class="py-16 text-center bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-8" x-cloak>
                <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4 text-slate-400">
                    <x-icon name="arama" class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">Eşleşen Şablon Bulunamadı</h3>
                <p class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1 mb-4">
                    Arama kriterlerinize uygun şablon bulunamadı. Filtreleri temizleyerek tekrar deneyebilirsiniz.
                </p>
                <button type="button" @click="resetFilters" class="px-4 py-2 bg-slate-900 text-white dark:bg-slate-100 dark:text-slate-900 rounded-xl text-xs font-bold hover:opacity-90 transition-opacity">
                    Filtreleri Sıfırla
                </button>
            </div>
        </div>

        {{-- ── 5. AI Şablon Yapılandırıcı Modal (Kontrollü Akış: Önizleme → İnsan Onayı → Kaydet → Mühürle) ── --}}
        <div x-show="isAiModalOpen" class="fixed inset-0 z-50 overflow-y-auto" x-cloak
            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">

            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-slate-950/80 backdrop-blur-sm" @click="isAiModalOpen = false"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block overflow-hidden text-left align-bottom transition-all transform bg-white dark:bg-slate-900 rounded-2xl shadow-2xl sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-slate-200 dark:border-slate-800"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100">

                    {{-- Modal Başlığı --}}
                    <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/80 dark:bg-slate-900/80">
                        <div class="flex items-center gap-3">
                            <div class="p-2.5 bg-indigo-100 dark:bg-indigo-900/40 rounded-xl text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800/50">
                                <x-icon name="ai" class="w-6 h-6" />
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 dark:text-slate-100">AI UPS Şablon Yapılandırıcı</h3>
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    <span x-text="aiSelection.category"></span> &rsaquo; <span x-text="aiSelection.type"></span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="isAiModalOpen = false" class="p-2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
                            <x-icon name="kapat" class="w-5 h-5" />
                        </button>
                    </div>

                    {{-- Kontrollü Akış Adımları (Step Tracker) --}}
                    <div class="px-6 py-3 bg-slate-100/60 dark:bg-slate-800/40 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs font-semibold">
                        <div class="flex items-center gap-2" :class="aiStep >= 1 ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-400'">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[11px]" :class="aiStep >= 1 ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-300'">1</span>
                            <span>Kategori & Analiz</span>
                        </div>
                        <span class="text-slate-300 dark:text-slate-600">&rarr;</span>
                        <div class="flex items-center gap-2" :class="aiStep >= 2 ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-400'">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[11px]" :class="aiStep >= 2 ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-300'">2</span>
                            <span>İnceleme & İnsan Onayı</span>
                        </div>
                        <span class="text-slate-300 dark:text-slate-600">&rarr;</span>
                        <div class="flex items-center gap-2" :class="aiStep >= 3 ? 'text-indigo-600 dark:text-indigo-400 font-bold' : 'text-slate-400'">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center border text-[11px]" :class="aiStep >= 3 ? 'bg-indigo-600 text-white border-indigo-600' : 'border-slate-300'">3</span>
                            <span>Kaydet & Mühürle</span>
                        </div>
                    </div>

                    {{-- Modal Gövdesi --}}
                    <div class="p-6">

                        {{-- Sekmeler: Önizleme / Debug --}}
                        <div class="flex border-b border-slate-200 dark:border-slate-800 mb-5">
                            <button type="button" @click="activeModalTab = 'preview'"
                                :class="activeModalTab === 'preview' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="py-2.5 px-4 border-b-2 text-xs transition-all flex items-center gap-1.5">
                                <x-icon name="goster" class="w-4 h-4" />
                                <span>Şablon Önizleme & Onay</span>
                            </button>
                            <button type="button" @click="activeModalTab = 'debug'"
                                :class="activeModalTab === 'debug' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400 font-bold' : 'border-transparent text-slate-500 hover:text-slate-700'"
                                class="py-2.5 px-4 border-b-2 text-xs transition-all flex items-center gap-1.5">
                                <x-icon name="pano" class="w-4 h-4" />
                                <span>UPS JSON Çıktısı</span>
                            </button>
                        </div>

                        {{-- Önizleme Sekmesi --}}
                        <div x-show="activeModalTab === 'preview'" class="space-y-4">

                            {{-- Başlangıç / Veri Yok Durumu --}}
                            <div x-show="!aiResult?.zorunlu_alanlar?.length && !aiResult?.opsiyonel_alanlar?.length && !isGenerating && !aiError"
                                class="flex flex-col items-center justify-center py-10 text-center">
                                <div class="w-16 h-16 bg-indigo-50 dark:bg-indigo-900/30 rounded-2xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-4 border border-indigo-100 dark:border-indigo-800">
                                    <x-icon name="ai" class="w-8 h-8" />
                                </div>
                                <h4 class="text-base font-bold text-slate-900 dark:text-slate-100">UPS Standart Şablonunu Başlatın</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mt-1 mb-4">
                                    Yalıhan Emlak UPS standart şablon yapısını çözümlemek için alt kategori seçin ve analizi başlatın.
                                </p>
                                <div class="w-full max-w-xs mx-auto">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1.5 text-left">Alt Kategori Seçimi</label>
                                    <select x-model="aiSelection.altKategoriId"
                                        class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 dark:text-slate-100 p-2.5 text-xs focus:ring-1 focus:ring-indigo-500 outline-none">
                                        <option value="">Kategori seçin...</option>
                                        @foreach ($kategoriler as $kat)
                                            <option value="{{ $kat->id }}">{{ $kat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" @click="generateAiTemplate" :disabled="!aiSelection.altKategoriId"
                                    :class="!aiSelection.altKategoriId ? 'opacity-50 cursor-not-allowed' : 'hover:bg-indigo-700 shadow-md'"
                                    class="mt-4 px-6 py-2.5 bg-indigo-600 text-white rounded-xl font-bold text-xs transition-all flex items-center gap-2">
                                    <x-icon name="flas" class="w-4 h-4" />
                                    <span>UPS Şablonunu Analiz Et</span>
                                </button>
                            </div>

                            {{-- Yükleniyor Durumu --}}
                            <div x-show="isGenerating" class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="w-12 h-12 rounded-full border-4 border-indigo-200 border-t-indigo-600 animate-spin mb-4"></div>
                                <p class="text-sm font-bold text-indigo-600 dark:text-indigo-400 animate-pulse">UPS Standartları ve Kuralları Çözümleniyor...</p>
                            </div>

                            {{-- Hata Durumu --}}
                            <div x-show="aiError && !isGenerating" class="flex flex-col items-center justify-center py-8 text-center bg-rose-50 dark:bg-rose-950/20 rounded-xl p-6 border border-rose-200 dark:border-rose-800">
                                <x-icon name="uyari" class="w-8 h-8 text-rose-500 mb-2" />
                                <h4 class="text-sm font-bold text-rose-800 dark:text-rose-300">İşlem Başarısız</h4>
                                <p class="text-xs text-rose-600 dark:text-rose-400 mt-1 mb-4" x-text="aiError"></p>
                                <button type="button" @click="generateAiTemplate" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-bold hover:bg-rose-700 transition-colors">
                                    Tekrar Dene
                                </button>
                            </div>

                            {{-- Başarılı Sonuç / İnceleme Alanı (Human-in-the-loop) --}}
                            <div x-show="(aiResult?.zorunlu_alanlar?.length || aiResult?.opsiyonel_alanlar?.length) && !isGenerating && !aiError" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                                    {{-- Zorunlu Alanlar --}}
                                    <div class="p-4 bg-blue-50/70 dark:bg-blue-950/30 rounded-xl border border-blue-100 dark:border-blue-800/60">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-bold text-blue-900 dark:text-blue-300 uppercase">Zorunlu Alanlar</span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold bg-blue-600 text-white rounded-full" x-text="(aiResult?.zorunlu_alanlar || []).length"></span>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5 max-h-48 overflow-y-auto">
                                            <template x-for="field in (aiResult?.zorunlu_alanlar || [])" :key="field">
                                                <span class="px-2 py-1 bg-white dark:bg-slate-900 text-blue-900 dark:text-blue-200 text-xs rounded-lg border border-blue-200 dark:border-blue-800 shadow-2xs font-mono font-medium" x-text="field"></span>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Opsiyonel Alanlar --}}
                                    <div class="p-4 bg-indigo-50/70 dark:bg-indigo-950/30 rounded-xl border border-indigo-100 dark:border-indigo-800/60">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-xs font-bold text-indigo-900 dark:text-indigo-300 uppercase">Opsiyonel Alanlar</span>
                                            <span class="px-2 py-0.5 text-[10px] font-bold bg-indigo-600 text-white rounded-full" x-text="(aiResult?.opsiyonel_alanlar || []).length"></span>
                                        </div>
                                        <div class="flex flex-wrap gap-1.5 max-h-48 overflow-y-auto">
                                            <template x-for="field in (aiResult?.opsiyonel_alanlar || [])" :key="field">
                                                <span class="px-2 py-1 bg-white dark:bg-slate-900 text-indigo-900 dark:text-indigo-200 text-xs rounded-lg border border-indigo-200 dark:border-indigo-800 shadow-2xs font-mono font-medium" x-text="field"></span>
                                            </template>
                                        </div>
                                    </div>

                                </div>

                                {{-- Validasyon Kuralları --}}
                                <div class="bg-slate-50 dark:bg-slate-800/60 rounded-xl p-4 border border-slate-200 dark:border-slate-700">
                                    <h5 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase mb-3 flex items-center gap-1.5">
                                        <x-icon name="kalkan" class="w-4 h-4 text-indigo-500" />
                                        Validasyon ve İş Kuralları
                                    </h5>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-xs">
                                        <template x-for="(rules, field) in (aiResult?.validasyon_kurallari || {})" :key="field">
                                            <div class="flex items-start gap-2 bg-white dark:bg-slate-900 p-2 rounded-lg border border-slate-200 dark:border-slate-800">
                                                <span class="font-mono text-indigo-600 dark:text-indigo-400 font-bold" x-text="field"></span>
                                                <span class="text-slate-500 dark:text-slate-400 italic" x-text="rules"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                        </div>

                        {{-- JSON Debug Sekmesi --}}
                        <div x-show="activeModalTab === 'debug'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-mono text-slate-400">UPS Template Payload (Read-only)</span>
                                <button type="button" @click="copyToClipboard" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
                                    <x-icon name="kopyala" class="w-3.5 h-3.5" />
                                    <span>JSON Kopyala</span>
                                </button>
                            </div>
                            <pre class="w-full h-80 p-4 font-mono text-xs bg-slate-950 text-indigo-300 rounded-xl border border-slate-800 overflow-auto"
                                 x-text="JSON.stringify(aiResult, null, 2)"></pre>
                        </div>

                    </div>

                    {{-- Modal Alt Eylemler --}}
                    <div class="px-6 py-4 bg-slate-50 dark:bg-slate-900/90 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <button type="button" @click="resetAi" class="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1">
                            <x-icon name="sil" class="w-4 h-4" />
                            <span>Sıfırla</span>
                        </button>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="isAiModalOpen = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors">
                                İptal
                            </button>
                            <button type="button" @click="saveAiTemplate" :disabled="!aiResult || isSaving"
                                :class="(!aiResult || isSaving) ? 'opacity-50 cursor-not-allowed bg-indigo-400' : 'bg-indigo-600 hover:bg-indigo-700 shadow-md'"
                                class="px-5 py-2.5 rounded-xl text-xs font-bold text-white transition-all flex items-center gap-2">
                                <span x-show="isSaving" class="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
                                <x-icon x-show="!isSaving" name="onay-daire" class="w-4 h-4" />
                                <span>Şablonu Mühürle & Ata</span>
                            </button>
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
                    categoryFilter: '',
                    typeFilter: '',
                    statusFilter: 'all',
                    isAiModalOpen: false,
                    isGenerating: false,
                    isSaving: false,
                    aiStep: 1,
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
                        this.sendTelemetry('property_hub_templates_open', {
                            duration_ms: 0,
                            basarili: true
                        });
                    },

                    get filteredCount() {
                        const cards = document.querySelectorAll('[data-template-id]');
                        let visible = 0;
                        cards.forEach(card => {
                            if (card.style.display !== 'none') visible++;
                        });
                        return visible;
                    },

                    matchesFilters(name, slug, categoryId, count) {
                        // 1. Text Search
                        if (this.search && this.search.trim() !== '') {
                            const query = this.search.toLowerCase().trim();
                            const matchName = (name || '').toLowerCase().includes(query);
                            const matchSlug = (slug || '').toLowerCase().includes(query);
                            if (!matchName && !matchSlug) return false;
                        }

                        // 2. Category Filter
                        if (this.categoryFilter && this.categoryFilter !== '') {
                            if (String(categoryId) !== String(this.categoryFilter)) return false;
                        }

                        // 3. Type Filter
                        if (this.typeFilter && this.typeFilter !== '') {
                            const typeSlug = (slug || '').toLowerCase();
                            const typeName = (name || '').toLowerCase();
                            const filterVal = this.typeFilter.toLowerCase();
                            if (!typeSlug.includes(filterVal) && !typeName.includes(filterVal)) return false;
                        }

                        // 4. Status Filter
                        if (this.statusFilter === 'missing' && count > 0) return false;
                        if (this.statusFilter === 'partial' && (count === 0 || count >= 20)) return false;
                        if (this.statusFilter === 'complete' && count < 20) return false;

                        return true;
                    },

                    resetFilters() {
                        this.search = '';
                        this.categoryFilter = '';
                        this.typeFilter = '';
                        this.statusFilter = 'all';
                    },

                    openAiModal(id, category, type, kategoriId) {
                        this.aiSelection = {
                            id,
                            category,
                            type,
                            altKategoriId: (kategoriId && kategoriId !== '0') ? kategoriId : null
                        };
                        this.aiResult = {
                            zorunlu_alanlar: [],
                            opsiyonel_alanlar: [],
                            validasyon_kurallari: {},
                            ui_ipuclari: {}
                        };
                        this.aiError = null;
                        this.aiStep = 1;
                        this.isAiModalOpen = true;
                        this.activeModalTab = 'preview';

                        this.sendTelemetry('property_hub_templates_edit_open', {
                            template_id: id,
                            template_type: type,
                            basarili: true
                        });
                    },

                    async generateAiTemplate() {
                        if (this.requestInFlight) return;

                        this.requestInFlight = true;
                        const currentRequestId = ++this.requestId;
                        this.isGenerating = true;
                        this.aiError = null;
                        const startTime = performance.now();

                        try {
                            const response = await fetch(`/admin/ai/property/generate-template/${this.aiSelection.id}`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                },
                                body: JSON.stringify({
                                    alt_kategori_id: parseInt(this.aiSelection.altKategoriId)
                                })
                            });

                            if (currentRequestId !== this.requestId) return;
                            const duration_ms = Math.round(performance.now() - startTime);

                            if (!response.ok) {
                                let err = {};
                                try { err = await response.json(); } catch (_e) { err = { message: `Sunucu hatası (${response.status})` }; }
                                this.aiError = err.message || 'Sistem hatası';
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
                                this.aiStep = 2;
                                if (window.toastr) toastr.success('UPS Şablon analizi tamamlandı. İnceleyip onaylayabilirsiniz.');
                            } else {
                                this.aiError = result.message || 'Veri alınamadı';
                            }
                        } catch (error) {
                            this.aiError = error.message || 'AI şablonu üretilemedi.';
                        } finally {
                            this.isGenerating = false;
                            this.requestInFlight = false;
                        }
                    },

                    async saveAiTemplate() {
                        this.isSaving = true;
                        try {
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
                                try { err = await response.json(); } catch (_e) { err = { message: `Kayıt hatası (${response.status})` }; }
                                throw new Error(err.message || 'Mühürleme hatası');
                            }

                            const result = await response.json();
                            if (result.success) {
                                if (window.toastr) toastr.success(result.message || 'Şablon başarıyla mühürlendi.');
                                this.aiStep = 3;
                                this.isAiModalOpen = false;
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
                        navigator.clipboard.writeText(JSON.stringify(this.aiResult, null, 2)).then(() => {
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
                        this.aiStep = 1;
                    },

                    sendTelemetry(event, extra = {}) {
                        if (!window.location.pathname.includes('/admin/')) return;
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
