@extends('layouts.frontend')

@php
    // Dynamic Page Title Logic
    $yayinTipi = request('yayin_tipi', '');
    $kategoriId = request('kategori', '');
    $kategoriSlug = request('kategori_slug', '');

    $title = 'Gayrimenkul Portföyü';
    $subtitle = 'Bodrum Yarımadası\'nın en gözde lokasyonlarındaki özel mülkleri keşfedin.';

    if ($yayinTipi === 'satilik') {
        $title = 'Satılık Konut Portföyü';
        $subtitle = 'Satın alabileceğiniz seçkin portföyümüzü keşfedin.';
    } elseif ($yayinTipi === 'kiralik') {
        $title = 'Kiralık Konut Portföyü';
        $subtitle = 'Kiralayabileceğiniz özel portföyümüzü keşfedin.';
    }

    if ($kategoriSlug === 'arsa-arazi') {
        $title = ($yayinTipi === 'satilik' ? 'Satılık ' : ($yayinTipi === 'kiralik' ? 'Kiralık ' : '')) . 'Arsa Portföyü';
        $subtitle = 'Yatırıma uygun eşsiz arazi ve arsaları keşfedin.';
    } elseif ($kategoriId) {
        $secilenKatIsim = $kategoriler->firstWhere('id', $kategoriId)?->name;
        if ($secilenKatIsim) {
            $title = ($yayinTipi === 'satilik' ? 'Satılık ' : ($yayinTipi === 'kiralik' ? 'Kiralık ' : '')) . $secilenKatIsim . ' Portföyü';
        }
    }

    if (!$yayinTipi && !$kategoriId && !$kategoriSlug) {
        $title = 'Satılık Konut Portföyü';
        $subtitle = "Bodrum Yarımadası'nın en gözde lokasyonlarındaki seçkin mülkleri keşfedin.";
    }

    // Applied Filter Detection
    $hasActiveFilters = request()->hasAny([
        'search', 'kategori_slug', 'il', 'ilce', 'mahalle',
        'min_fiyat', 'max_fiyat', 'min_m2', 'max_m2',
        'oda_sayisi', 'havuz_var', 'imar_durumu', 'mulk_tipi',
        'akilli_ev', 'guvenlik', 'otopark', 'spor_salonu'
    ]);
@endphp

@section('title', $title . ' — ' . config('app.name'))

@section('content')

{{-- ── Brand Navy Banner Header ── --}}
<div class="bg-[#0A1628] pt-28 pb-10 border-b border-[#C9A84C]/20">
    <div class="max-w-[1400px] mx-auto px-6">
        {{-- Breadcrumb --}}
        <div class="flex items-center gap-2 mb-3 text-xs font-medium text-white/60">
            <a href="{{ route('home') }}" class="hover:text-white transition-colors">Ana Sayfa</a>
            <span>›</span>
            @if($secilenKat ?? null)
                <a href="{{ route('ilanlar.index') }}" class="hover:text-white transition-colors">İlanlar</a>
                <span>›</span>
                <span class="text-[#C9A84C] font-semibold">{{ $secilenKat->name }}</span>
            @else
                <span class="text-[#C9A84C] font-semibold">İlanlar</span>
            @endif
        </div>
        {{-- Title --}}
        <h1 class="text-3xl sm:text-4xl font-extrabold text-white mb-2 tracking-tight">
            {{ $title }}
        </h1>
        <p class="text-sm sm:text-base text-[#F8F6F1]/70 max-w-2xl">
            {{ $subtitle }}
        </p>
    </div>
</div>

<div class="min-h-screen pb-20 bg-[#F8F6F1]" x-data="{ viewMode: 'grid', mobileFilterOpen: false }">
    <div class="h-6"></div>

    <!-- Main Content -->
    <div class="max-w-[1400px] mx-auto px-6 flex flex-col lg:flex-row gap-8">

        <!-- Mobile Filter Button (<1024px) -->
        <div class="lg:hidden">
            <button @click="mobileFilterOpen = !mobileFilterOpen"
                    type="button"
                    class="w-full bg-[#0A1628] text-white font-semibold py-3 px-4 rounded-xl flex items-center justify-center gap-2 text-sm shadow-md">
                <x-icon name="filtrele" class="w-5 h-5 text-[#C9A84C]" />
                <span>Filtreleri Göster / Gizle</span>
                @if($hasActiveFilters)
                    <span class="w-2.5 h-2.5 rounded-full bg-[#C9A84C] ml-1"></span>
                @endif
            </button>
        </div>

        <!-- Sidebar Filters -->
        <aside class="w-full lg:w-80 flex-shrink-0"
               :class="mobileFilterOpen ? 'block' : 'hidden lg:block'">

            <!-- Filter Form — Accordion Sidebar -->
            <form action="{{ route('ilanlar.index') }}" method="GET" id="filter-form"
                  class="bg-white rounded-2xl overflow-hidden mb-6 sticky top-28 border border-[#E8E2D8] shadow-[0_4px_24px_rgba(10,22,40,0.06)]">

                @if(request('yayin_tipi'))
                    <input type="hidden" name="yayin_tipi" value="{{ request('yayin_tipi') }}">
                @endif

                {{-- Scrollable content --}}
                <div class="p-5 overflow-y-auto custom-scrollbar" style="max-height:calc(100vh - 170px);">

                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-base font-bold text-[#0A1628] flex items-center gap-2">
                            <x-icon name="filtrele" class="w-4 h-4 text-[#C9A84C]" />
                            Filtrele
                        </h3>
                        @if($hasActiveFilters)
                            <a href="{{ route('ilanlar.index') }}" class="text-xs font-semibold text-[#C9A84C] hover:underline transition-colors">Sıfırla</a>
                        @endif
                    </div>

                    {{-- ─── Extracted Location Filter Component ─── --}}
                    @php
                        $selectedIlIds   = array_values(array_filter(array_map('intval', (array)request('il',      []))));
                        $selectedIlceIds = array_values(array_filter(array_map('intval', (array)request('ilce',    []))));
                        $selectedMahIds  = array_values(array_filter(array_map('intval', (array)request('mahalle', []))));

                        $expandedIlIds   = $selectedIlIds;
                        $expandedIlceIds = $selectedIlceIds;
                        foreach ($iller as $_il) {
                            $ilceIds = $_il->ilceler->pluck('id')->toArray();
                            if (array_intersect($selectedIlceIds, $ilceIds)) {
                                $expandedIlIds[] = $_il->id;
                            }
                            foreach ($_il->ilceler as $_ilce) {
                                $mahIds = $_ilce->mahalleler->pluck('id')->toArray();
                                if (array_intersect($selectedMahIds, $mahIds)) {
                                    $expandedIlIds[]   = $_il->id;
                                    $expandedIlceIds[] = $_ilce->id;
                                }
                            }
                        }
                    @endphp

                    <x-frontend.location-filter-tree
                        :iller="$iller"
                        :selected-il-ids="$selectedIlIds"
                        :selected-ilce-ids="$selectedIlceIds"
                        :selected-mah-ids="$selectedMahIds"
                        :expanded-il-ids="$expandedIlIds"
                        :expanded-ilce-ids="$expandedIlceIds" />

                    {{-- ─── KATEGORİ — radio, auto-submit ─── --}}
                    <div class="mb-5 pt-4 border-t border-[#E8E2D8]">
                        <h4 class="text-[11px] font-bold text-[#6B7280] uppercase tracking-wider mb-3">KATEGORİ</h4>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="radio" name="kategori_slug" value=""
                                       {{ !$kategoriSlug ? 'checked' : '' }}
                                       class="w-4 h-4 cursor-pointer" style="accent-color:#0A1628"
                                       onchange="document.getElementById('filter-form').submit()">
                                <span class="text-sm font-medium text-[#0A1628] group-hover:text-[#C9A84C] flex-1">Tümü</span>
                            </label>
                            @foreach($kategoriler as $kat)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="radio" name="kategori_slug" value="{{ $kat->slug }}"
                                       {{ $kategoriSlug === $kat->slug ? 'checked' : '' }}
                                       class="w-4 h-4 cursor-pointer" style="accent-color:#0A1628"
                                       onchange="document.getElementById('filter-form').submit()">
                                <span class="text-sm font-medium text-[#0A1628] group-hover:text-[#C9A84C] flex-1">{{ $kat->name }}</span>
                                @if(($kat->ilan_sayisi ?? 0) > 0)
                                    <span class="text-[11px] text-[#6B7280] tabular-nums">{{ $kat->ilan_sayisi }}</span>
                                @endif
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- ─── FİYAT ARALIĞI ─── --}}
                    <div class="mb-5 pt-4 border-t border-[#E8E2D8]">
                        <h4 class="text-[11px] font-bold text-[#6B7280] uppercase tracking-wider mb-3">FİYAT ARALIĞI</h4>
                        <div class="flex gap-2">
                            <input type="number" name="min_fiyat" placeholder="Min €" value="{{ request('min_fiyat') }}"
                                   class="w-1/2 bg-[#F8F6F1] border border-[#E8E2D8] rounded-lg px-3 py-2 text-sm text-[#0A1628] outline-none focus:border-[#C9A84C] focus:ring-1 focus:ring-[#C9A84C]/20 transition-all h-[44px]">
                            <input type="number" name="max_fiyat" placeholder="Max €" value="{{ request('max_fiyat') }}"
                                   class="w-1/2 bg-[#F8F6F1] border border-[#E8E2D8] rounded-lg px-3 py-2 text-sm text-[#0A1628] outline-none focus:border-[#C9A84C] focus:ring-1 focus:ring-[#C9A84C]/20 transition-all h-[44px]">
                        </div>
                    </div>

                    {{-- ─── ARSA-SPECIFIC / KONUT SPECIFIC ─── --}}
                    @if($kategoriSlug === 'arsa-arazi')

                    <div class="mb-5 pt-4 border-t border-[#E8E2D8]">
                        <h4 class="text-[11px] font-bold text-[#6B7280] uppercase tracking-wider mb-3">ARSA TİPİ</h4>
                        @php $selectedMulk = (array)request('mulk_tipi', []); @endphp
                        <div class="space-y-2">
                            @foreach(['İmarlı Arsa','Villa İmarlı','Tarla','Zeytinlik','Muhtelif Arsa'] as $tip)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="mulk_tipi[]" value="{{ $tip }}"
                                       {{ in_array($tip, $selectedMulk) ? 'checked' : '' }} class="custom-checkbox">
                                <span class="text-sm font-medium text-[#0A1628] group-hover:text-[#C9A84C] transition-colors">{{ $tip }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-5 pt-4 border-t border-[#E8E2D8]">
                        <h4 class="text-[11px] font-bold text-[#6B7280] uppercase tracking-wider mb-3">İMAR DURUMU</h4>
                        <div class="relative">
                            <select name="imar_durumu" class="w-full bg-[#F8F6F1] border border-[#E8E2D8] rounded-lg px-3 py-2.5 text-sm font-medium text-[#0A1628] appearance-none outline-none focus:border-[#C9A84C] focus:ring-1 focus:ring-[#C9A84C] cursor-pointer h-[44px]">
                                <option value="">Tümü</option>
                                @foreach(['Konut','Ticari','Turizm'] as $imar)
                                <option value="{{ $imar }}" {{ request('imar_durumu') == $imar ? 'selected' : '' }}>{{ $imar }}</option>
                                @endforeach
                                <option value="Tarım" {{ request('imar_durumu') == 'Tarım' ? 'selected' : '' }}>Tarım / Zeytin</option>
                            </select>
                            <x-icon name="asagi-chevron" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-[#6B7280] pointer-events-none" />
                        </div>
                    </div>

                    <div class="mb-5 pt-4 border-t border-[#E8E2D8]">
                        <h4 class="text-[11px] font-bold text-[#6B7280] uppercase tracking-wider mb-3">ALAN (m²)</h4>
                        <div class="flex gap-2">
                            <input type="number" name="min_m2" placeholder="Min" value="{{ request('min_m2') }}"
                                   class="w-1/2 bg-[#F8F6F1] border border-[#E8E2D8] rounded-lg px-3 py-2 text-sm text-[#0A1628] outline-none focus:border-[#C9A84C] focus:ring-1 focus:ring-[#C9A84C]/20 transition-all h-[44px]">
                            <input type="number" name="max_m2" placeholder="Max" value="{{ request('max_m2') }}"
                                   class="w-1/2 bg-[#F8F6F1] border border-[#E8E2D8] rounded-lg px-3 py-2 text-sm text-[#0A1628] outline-none focus:border-[#C9A84C] focus:ring-1 focus:ring-[#C9A84C]/20 transition-all h-[44px]">
                        </div>
                    </div>

                    @else {{-- Konut / Yazlık / Genel --}}

                    <div class="mb-5 pt-4 border-t border-[#E8E2D8]">
                        <h4 class="text-[11px] font-bold text-[#6B7280] uppercase tracking-wider mb-3">ODA SAYISI</h4>
                        <div class="flex flex-wrap gap-1.5">
                            @php $selectedOdalar = (array)request('oda_sayisi', []); @endphp
                            @foreach(['Stüdyo','1+1','2+1','3+1','4+'] as $oda)
                            <label class="pill-btn cursor-pointer">
                                <input type="checkbox" name="oda_sayisi[]" value="{{ $oda }}"
                                       {{ in_array($oda, $selectedOdalar) ? 'checked' : '' }} class="hidden">
                                <span class="inline-block px-3 py-1.5 text-xs font-semibold rounded-lg border h-[36px] flex items-center justify-center">{{ $oda }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-5 pt-4 border-t border-[#E8E2D8]">
                        <h4 class="text-[11px] font-bold text-[#6B7280] uppercase tracking-wider mb-3">ALAN (m²)</h4>
                        <div class="flex gap-2">
                            <input type="number" name="min_m2" placeholder="Min" value="{{ request('min_m2') }}"
                                   class="w-1/2 bg-[#F8F6F1] border border-[#E8E2D8] rounded-lg px-3 py-2 text-sm text-[#0A1628] outline-none focus:border-[#C9A84C] focus:ring-1 focus:ring-[#C9A84C]/20 transition-all h-[44px]">
                            <input type="number" name="max_m2" placeholder="Max" value="{{ request('max_m2') }}"
                                   class="w-1/2 bg-[#F8F6F1] border border-[#E8E2D8] rounded-lg px-3 py-2 text-sm text-[#0A1628] outline-none focus:border-[#C9A84C] focus:ring-1 focus:ring-[#C9A84C]/20 transition-all h-[44px]">
                        </div>
                    </div>

                    <div class="mb-5 pt-4 border-t border-[#E8E2D8]">
                        <h4 class="text-[11px] font-bold text-[#6B7280] uppercase tracking-wider mb-3">ÖZELLİKLER</h4>
                        <div class="space-y-2">
                            @foreach([
                                ['name'=>'havuz_var',   'label'=>'Havuz'],
                                ['name'=>'akilli_ev',   'label'=>'Akıllı Ev Sistemi'],
                                ['name'=>'guvenlik',    'label'=>'Güvenlik / Site'],
                                ['name'=>'otopark',     'label'=>'Otopark'],
                                ['name'=>'spor_salonu', 'label'=>'Spor Salonu'],
                            ] as $ozel)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="{{ $ozel['name'] }}" value="1"
                                       {{ request($ozel['name']) ? 'checked' : '' }} class="custom-checkbox">
                                <span class="text-sm font-medium text-[#0A1628] group-hover:text-[#C9A84C] transition-colors">{{ $ozel['label'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    @endif

                </div>{{-- /scrollable --}}

                {{-- Sticky submit --}}
                <div class="px-5 py-4 border-t border-[#E8E2D8] bg-white">
                    <button type="submit"
                            class="w-full bg-[#0A1628] text-white font-bold py-3 rounded-xl hover:bg-[#162240] transition-all shadow-md text-sm tracking-wide h-[52px] flex items-center justify-center gap-2">
                        <x-icon name="arama" class="w-4 h-4 text-[#C9A84C]" />
                        <span>Filtreleri Uygula</span>
                    </button>
                </div>

            </form>

            <!-- Özel Talep Card -->
            <div class="bg-[#0A1628] text-white rounded-2xl p-6 border border-[#C9A84C]/30 shadow-md">
                <h3 class="text-base font-bold text-[#C9A84C] mb-2 flex items-center gap-2">
                    <x-icon name="mühür" class="w-4 h-4 text-[#C9A84C]" />
                    Özel Mülk Danışmanlığı
                </h3>
                <p class="text-xs text-[#F8F6F1]/80 mb-5 leading-relaxed">Aradığınız özellikteki mülkü bulamadınız mı? Bodrum uzmanlarımız size özel gizli portföy araştırması yapar.</p>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 text-white font-bold text-xs hover:text-[#C9A84C] transition-colors">
                    <span>Uzman Danışmanla Görüşün</span>
                    <x-icon name="sag-ok" class="w-3.5 h-3.5 text-[#C9A84C]" />
                </a>
            </div>

        </aside>

        <!-- Right Area (Listings) -->
        <div class="flex-1">

            {{-- ─── Applied Filter Chips (PILOT_001) ─── --}}
            @if($hasActiveFilters)
                @php
                    // Collect chips with removal URLs
                    $chips = [];

                    // Location: İl chips
                    $selectedIlIds = array_values(array_filter(array_map('intval', (array)request('il', []))));
                    foreach ($selectedIlIds as $ilId) {
                        $il = $iller->firstWhere('id', $ilId);
                        if ($il) {
                            $newIl = array_values(array_filter($selectedIlIds, fn($id) => $id !== $ilId));
                            $url = $newIl
                                ? route('ilanlar.index', array_merge(request()->except(['il']), ['il' => $newIl]))
                                : route('ilanlar.index', request()->except(['il']));
                            $chips[] = ['label' => $il->name, 'url' => $url];
                        }
                    }

                    // Location: İlçe chips
                    $selectedIlceIds = array_values(array_filter(array_map('intval', (array)request('ilce', []))));
                    foreach ($selectedIlceIds as $ilceId) {
                        foreach ($iller as $il) {
                            $ilce = $il->ilceler->firstWhere('id', $ilceId);
                            if ($ilce) {
                                $newIlce = array_values(array_filter($selectedIlceIds, fn($id) => $id !== $ilceId));
                                $url = $newIlce
                                    ? route('ilanlar.index', array_merge(request()->except(['ilce']), ['ilce' => $newIlce]))
                                    : route('ilanlar.index', request()->except(['ilce']));
                                $chips[] = ['label' => $ilce->name, 'url' => $url];
                            }
                        }
                    }

                    // Location: Mahalle chips
                    $selectedMahIds = array_values(array_filter(array_map('intval', (array)request('mahalle', []))));
                    foreach ($selectedMahIds as $mahId) {
                        foreach ($iller as $il) {
                            foreach ($il->ilceler as $ilce) {
                                $mah = $ilce->mahalleler->firstWhere('id', $mahId);
                                if ($mah) {
                                    $newMah = array_values(array_filter($selectedMahIds, fn($id) => $id !== $mahId));
                                    $url = $newMah
                                        ? route('ilanlar.index', array_merge(request()->except(['mahalle']), ['mahalle' => $newMah]))
                                        : route('ilanlar.index', request()->except(['mahalle']));
                                    $chips[] = ['label' => $mah->name, 'url' => $url];
                                }
                            }
                        }
                    }

                    // Kategori chip
                    if ($kategoriSlug) {
                        $katName = $kategoriler->firstWhere('slug', $kategoriSlug)?->name ?? ucfirst(str_replace('-', ' ', $kategoriSlug));
                        $chips[] = ['label' => $katName, 'url' => route('ilanlar.index', request()->except(['kategori_slug']))];
                    }

                    // Mülk tipi chips
                    $selectedMulk = (array)request('mulk_tipi', []);
                    foreach ($selectedMulk as $tip) {
                        $newMulk = array_values(array_filter($selectedMulk, fn($t) => $t !== $tip));
                        $url = $newMulk
                            ? route('ilanlar.index', array_merge(request()->except(['mulk_tipi']), ['mulk_tipi' => $newMulk]))
                            : route('ilanlar.index', request()->except(['mulk_tipi']));
                        $chips[] = ['label' => $tip, 'url' => $url];
                    }

                    // İmar durumu chip
                    if (request('imar_durumu')) {
                        $chips[] = ['label' => request('imar_durumu'), 'url' => route('ilanlar.index', request()->except(['imar_durumu']))];
                    }

                    // Oda sayısı chips
                    $selectedOdalar = (array)request('oda_sayisi', []);
                    foreach ($selectedOdalar as $oda) {
                        $newOda = array_values(array_filter($selectedOdalar, fn($o) => $o !== $oda));
                        $url = $newOda
                            ? route('ilanlar.index', array_merge(request()->except(['oda_sayisi']), ['oda_sayisi' => $newOda]))
                            : route('ilanlar.index', request()->except(['oda_sayisi']));
                        $chips[] = ['label' => $oda, 'url' => $url];
                    }

                    // Fiyat aralığı chip
                    if (request('min_fiyat') || request('max_fiyat')) {
                        $fiyatLabel = '₺' . (request('min_fiyat') ? number_format(request('min_fiyat'), 0, ',', '.') : '0') . ' – ₺' . (request('max_fiyat') ? number_format(request('max_fiyat'), 0, ',', '.') : '∞');
                        $chips[] = ['label' => $fiyatLabel, 'url' => route('ilanlar.index', request()->except(['min_fiyat', 'max_fiyat']))];
                    }

                    // Alan chip
                    if (request('min_m2') || request('max_m2')) {
                        $alanLabel = (request('min_m2') ?: '0') . '–' . (request('max_m2') ?: '∞') . ' m²';
                        $chips[] = ['label' => $alanLabel, 'url' => route('ilanlar.index', request()->except(['min_m2', 'max_m2']))];
                    }

                    // Özellik chips
                    $ozellikMap = ['havuz_var' => 'Havuz', 'akilli_ev' => 'Akıllı Ev', 'guvenlik' => 'Güvenlik', 'otopark' => 'Otopark', 'spor_salonu' => 'Spor Salonu'];
                    foreach ($ozellikMap as $key => $label) {
                        if (request($key)) {
                            $chips[] = ['label' => $label, 'url' => route('ilanlar.index', request()->except([$key]))];
                        }
                    }
                @endphp

                @if(count($chips) > 0)
                <div class="bg-white rounded-2xl px-4 py-3 border border-[#E8E2D8] mb-6 shadow-sm flex flex-wrap items-center gap-2">
                    <!-- Filter chips -->
                    @foreach($chips as $chip)
                        <a href="{{ $chip['url'] }}"
                           class="inline-flex items-center gap-1.5 pl-3 pr-1.5 py-1.5 rounded-full text-xs font-medium bg-neutral-100 text-neutral-700 hover:bg-neutral-200 transition-colors group">
                            <span>{{ $chip['label'] }}</span>
                            <span class="w-4 h-4 flex items-center justify-center rounded-full bg-neutral-300/60 group-hover:bg-neutral-400/60 transition-colors text-neutral-600 text-[10px] font-bold leading-none">×</span>
                        </a>
                    @endforeach
                    <!-- Clear all -->
                    <a href="{{ route('ilanlar.index') }}"
                       class="ml-auto text-xs font-semibold text-[#C9A84C] hover:underline whitespace-nowrap">
                        Tümünü Temizle
                    </a>
                </div>
                @endif
            @endif

            <!-- Toolbar -->
            <div class="bg-white rounded-2xl p-4 border border-[#E8E2D8] mb-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="text-sm text-[#434655] font-medium px-2">
                    <span class="font-bold text-[#0A1628] text-base">{{ number_format($ilanlar->total() ?? 0, 0, ',', '.') }}</span> mülk listeleniyor
                </div>

                <div class="flex items-center gap-4">
                    <!-- Layout Toggle -->
                    <div class="flex bg-[#F8F6F1] rounded-lg p-1 border border-[#E8E2D8]">
                        <button type="button" @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'active' : ''" class="view-btn" aria-label="Izgara Görünümü">
                            <x-icon name="ev" class="w-4 h-4" />
                        </button>
                        <button type="button" @click="viewMode = 'list'" :class="viewMode === 'list' ? 'active' : ''" class="view-btn" aria-label="Liste Görünümü">
                            <x-icon name="menu" class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Sort -->
                    <div class="flex items-center gap-2 border-l border-[#E8E2D8] pl-4">
                        <span class="text-xs font-semibold text-[#6B7280]">Sırala:</span>
                        <div class="relative">
                            <select name="sort_by"
                                    onchange="document.getElementById('filter-form').elements['sort_by']?.remove(); const h=document.createElement('input'); h.type='hidden'; h.name='sort_by'; h.value=this.value; document.getElementById('filter-form').appendChild(h); document.getElementById('filter-form').submit();"
                                    class="bg-transparent text-xs font-bold text-[#0A1628] outline-none appearance-none pr-5 cursor-pointer">
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>En Yeni</option>
                                <option value="fiyat_asc" {{ request('sort_by') == 'fiyat_asc' ? 'selected' : '' }}>Fiyat: Artan</option>
                                <option value="fiyat_desc" {{ request('sort_by') == 'fiyat_desc' ? 'selected' : '' }}>Fiyat: Azalan</option>
                            </select>
                            <x-icon name="asagi-chevron" class="absolute right-0 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-[#6B7280] pointer-events-none" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Property Grid (Canonical <x-property-card /> component) -->
            <div :class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 xl:grid-cols-2 gap-6' : 'flex flex-col gap-6'">
                @forelse($ilanlar as $ilan)
                    <x-property-card :ilan="$ilan" />
                @empty
                    <div class="col-span-full py-16 px-6 text-center bg-white rounded-2xl border border-[#E8E2D8] shadow-sm">
                        <div class="w-16 h-16 bg-[#F8F6F1] text-[#0A1628] rounded-full flex items-center justify-center mx-auto mb-4 border border-[#E8E2D8]">
                            <x-icon name="arama" class="w-7 h-7 text-[#C9A84C]" />
                        </div>
                        <h3 class="text-lg font-bold text-[#0A1628] mb-2">Bu kriterlerde aktif portföy bulunamadı</h3>
                        <p class="text-sm text-[#6B7280] max-w-md mx-auto mb-6">Arama kriterlerinizi genişleterek veya konum filtresini temizleyerek diğer mülkleri inceleyebilirsiniz.</p>
                        <a href="{{ route('ilanlar.index') }}" class="inline-flex items-center gap-2 bg-[#0A1628] text-white font-semibold px-6 py-3 rounded-xl hover:bg-[#162240] transition-colors text-sm shadow-md">
                            <x-icon name="yenile" class="w-4 h-4 text-[#C9A84C]" />
                            <span>Tüm Filtreleri Temizle</span>
                        </a>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($ilanlar instanceof \Illuminate\Pagination\LengthAwarePaginator && $ilanlar->hasPages())
            <div class="mt-12 flex justify-center gap-2">
                @if ($ilanlar->onFirstPage())
                    <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-[#E8E2D8] text-[#6B7280] bg-white"><x-icon name="sol-chevron" class="w-4 h-4" /></span>
                @else
                    <a href="{{ $ilanlar->previousPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-[#E8E2D8] text-[#0A1628] bg-white hover:border-[#C9A84C] hover:text-[#C9A84C] transition-colors"><x-icon name="sol-chevron" class="w-4 h-4" /></a>
                @endif

                @foreach ($ilanlar->getUrlRange(max(1, $ilanlar->currentPage() - 2), min($ilanlar->lastPage(), $ilanlar->currentPage() + 2)) as $page => $url)
                    @if ($page == $ilanlar->currentPage())
                        <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-[#0A1628] bg-[#0A1628] text-white font-bold text-sm">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-[#E8E2D8] text-[#0A1628] bg-white hover:border-[#C9A84C] hover:text-[#C9A84C] font-medium text-sm transition-colors">{{ $page }}</a>
                    @endif
                @endforeach

                @if ($ilanlar->currentPage() < $ilanlar->lastPage() - 2)
                    <span class="w-10 h-10 flex items-center justify-center text-[#6B7280]">...</span>
                    <a href="{{ $ilanlar->url($ilanlar->lastPage()) }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-[#E8E2D8] text-[#0A1628] bg-white hover:border-[#C9A84C] hover:text-[#C9A84C] font-medium text-sm transition-colors">{{ $ilanlar->lastPage() }}</a>
                @endif

                @if ($ilanlar->hasMorePages())
                    <a href="{{ $ilanlar->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-[#E8E2D8] text-[#0A1628] bg-white hover:border-[#C9A84C] hover:text-[#C9A84C] transition-colors"><x-icon name="sag-chevron" class="w-4 h-4" /></a>
                @else
                    <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-[#E8E2D8] text-[#6B7280] bg-white"><x-icon name="sag-chevron" class="w-4 h-4" /></span>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
