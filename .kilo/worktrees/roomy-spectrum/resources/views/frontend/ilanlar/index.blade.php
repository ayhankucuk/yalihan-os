@extends('layouts.frontend')

@php
    // Dynamic Page Title Logic
    $yayinTipi = request('yayin_tipi', '');
    $kategoriId = request('kategori', '');
    $kategoriSlug = request('kategori_slug', '');

    $title = 'Gayrimenkul Portföyü';
    $subtitle = 'Dünyanın en çok tercih edilen lokasyonlarındaki özel mülkleri keşfedin.';

    if ($yayinTipi === 'satilik') {
        $title = 'Satılık Konut Portföyü';
        $subtitle = 'Satın alabileceğiniz özel portföyümüzü keşfedin.';
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

    // Fallback for "Satılık Konut Portföyü" as requested in UI if no params
    if (!$yayinTipi && !$kategoriId && !$kategoriSlug) {
        $title = 'Satılık Konut Portföyü';
        $subtitle = "Bodrum Yarımadası'nın en gözde lokasyonlarındaki seçkin mülkleri keşfedin.";
    }
@endphp

@section('title', $title . ' — ' . config('app.name'))

@push('styles')
<style>
    .custom-checkbox {
        appearance: none;
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid #e2e8f0;
        border-radius: 4px;
        outline: none;
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .custom-checkbox:checked {
        border-color: var(--primary);
        background-color: var(--primary);
    }
    .custom-checkbox:checked::after {
        content: '';
        position: absolute;
        top: 40%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(45deg);
        width: 0.35rem;
        height: 0.6rem;
        border: solid white;
        border-width: 0 2px 2px 0;
    }

    .pill-btn {
        transition: all 0.2s;
    }
    .pill-btn:hover {
        background-color: #f1f5f9;
    }
    .pill-btn input:checked + span {
        background-color: var(--primary);
        color: white;
        border-color: var(--primary);
    }
    .pill-btn input:not(:checked) + span {
        background-color: white;
        color: #475569;
        border-color: #e2e8f0;
    }

    .ilan-card-lux {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s;
    }
    .ilan-card-lux:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .ilan-image-wrapper {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
    }
    .ilan-image-wrapper img {
        transition: transform 0.7s;
    }
    .ilan-card-lux:hover .ilan-image-wrapper img {
        transform: scale(1.05);
    }

    .view-btn {
        display: flex; align-items: center; justify-content: center;
        width: 40px; height: 40px; border-radius: 8px;
        transition: all 0.15s; cursor: pointer; border: none;
    }
    .view-btn.active { background: #e0e7ff; color: var(--primary); }
    .view-btn:not(.active) { background: transparent; color: #64748b; }
    .view-btn:not(.active):hover { background: #f1f5f9; color: #1e293b; }
</style>
@endpush

@section('content')

{{-- ── Page Header — Kurumsal Banner ── --}}
<div style="background:#0F2A5C; padding-top:7rem; padding-bottom:2.5rem;">
    <div class="max-w-[1400px] mx-auto px-6">
        {{-- Breadcrumb --}}
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;font-size:0.75rem;font-weight:500;color:rgba(255,255,255,0.55);">
            <a href="{{ route('home') }}" style="color:rgba(255,255,255,0.55);text-decoration:none;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.55)'">Ana Sayfa</a>
            <span>›</span>
            @if($secilenKat ?? null)
                <a href="{{ route('ilanlar.index') }}" style="color:rgba(255,255,255,0.55);text-decoration:none;">İlanlar</a>
                <span>›</span>
                <span style="color:#fff;">{{ $secilenKat->name }}</span>
            @else
                <span style="color:#fff;">İlanlar</span>
            @endif
        </div>
        {{-- Başlık --}}
        <h1 style="font-size:clamp(1.75rem,3vw,2.5rem);font-weight:800;color:#fff;margin-bottom:0.5rem;letter-spacing:-0.01em;">
            {{ $title }}
        </h1>
        <p style="font-size:0.95rem;color:rgba(255,255,255,0.65);max-width:40rem;">
            {{ $subtitle }}
        </p>
    </div>
</div>

<div class="min-h-screen pb-20" style="background:#f3f4f6;" x-data="{ viewMode: 'grid' }">
    <div style="height:2rem;"></div>

    <!-- Main Content -->
    <div class="max-w-[1400px] mx-auto px-6 flex flex-col lg:flex-row gap-8">

        <!-- Sidebar Filters -->
        <aside class="w-full lg:w-80 flex-shrink-0">
            <!-- Filter Form — Accordion Sidebar -->
            <form action="{{ route('ilanlar.index') }}" method="GET" id="filter-form"
                  class="bg-white rounded-2xl overflow-hidden mb-6 sticky top-28"
                  style="border:1px solid #dde1f0; box-shadow:0 4px 24px rgba(0,26,110,0.08), 0 1px 4px rgba(0,26,110,0.04);">

                @if(request('yayin_tipi'))
                    <input type="hidden" name="yayin_tipi" value="{{ request('yayin_tipi') }}">
                @endif

                {{-- Scrollable content --}}
                <div class="p-5 overflow-y-auto custom-scrollbar" style="max-height:calc(100vh - 170px);">

                    {{-- Header --}}
                    <div class="flex items-center justify-between mb-5">
                        <h3 class="text-base font-bold text-slate-900">Filtrele</h3>
                        @if(request()->hasAny(['search','kategori','kategori_slug','il','ilce','mahalle','min_fiyat','max_fiyat','min_m2','max_m2','oda_sayisi','havuz_var','imar_durumu','mulk_tipi','akilli_ev','guvenlik','otopark','spor_salonu']))
                            <a href="{{ route('ilanlar.index') }}" class="text-xs font-semibold text-primary hover:text-blue-700 transition-colors">Sıfırla</a>
                        @endif
                    </div>

                    {{-- ─── KONUM: 3-level accordion checkbox ─── --}}
                    @php
                        $selectedIlIds   = array_values(array_filter(array_map('intval', (array)request('il',      []))));
                        $selectedIlceIds = array_values(array_filter(array_map('intval', (array)request('ilce',    []))));
                        $selectedMahIds  = array_values(array_filter(array_map('intval', (array)request('mahalle', []))));

                        // Auto-expand ills/ilces that have selected sub-items
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
                        $expandedIlIds   = array_values(array_unique($expandedIlIds));
                        $expandedIlceIds = array_values(array_unique($expandedIlceIds));
                    @endphp

                    <div class="mb-5"
                         x-data="{
                             openIls:   {{ json_encode($expandedIlIds) }},
                             openIlces: {{ json_encode($expandedIlceIds) }},
                             toggleIl(id)   { const i=this.openIls.indexOf(id);   i===-1?this.openIls.push(id):this.openIls.splice(i,1); },
                             toggleIlce(id) { const i=this.openIlces.indexOf(id); i===-1?this.openIlces.push(id):this.openIlces.splice(i,1); }
                         }">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3">KONUM</h4>

                        {{-- Search --}}
                        <div class="relative mb-3">
                            <x-icon name="arama" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                            <input type="text" name="search" value="{{ request('search') }}"
                                   placeholder="İl, İlçe, Mahalle..."
                                   class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-9 pr-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>

                        {{-- Tree --}}
                        <div class="space-y-0.5 max-h-60 overflow-y-auto pr-0.5 custom-scrollbar">
                            @forelse($iller as $il)
                            <div>
                                {{-- İl row --}}
                                <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer select-none"
                                     @click="toggleIl({{ (int)$il->id }})">
                                    <input type="checkbox" name="il[]" value="{{ $il->id }}"
                                           {{ in_array($il->id, $selectedIlIds) ? 'checked' : '' }}
                                           class="custom-checkbox flex-shrink-0" @click.stop>
                                    <span class="text-sm font-semibold text-slate-700 flex-1">{{ $il->il_adi }}</span>
                                    <span class="text-[11px] text-slate-400 font-medium tabular-nums">{{ $il->ilan_sayisi }}</span>
                                    @if($il->ilceler->isNotEmpty())
                                    @php $ilId = (int)$il->id; @endphp
                                    <svg x-bind:class="openIls.includes({{ $ilId }}) ? 'rotate-180' : ''"
                                         class="w-4 h-4 text-slate-400 flex-shrink-0 transition-transform duration-150"
                                         xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                                    </svg>
                                    @endif
                                </div>

                                {{-- İlçeler --}}
                                @if($il->ilceler->isNotEmpty())
                                <div x-show="openIls.includes({{ (int)$il->id }})" x-cloak
                                     x-transition:enter="transition-all duration-150 ease-out"
                                     x-transition:enter-start="opacity-0 -translate-y-1"
                                     x-transition:enter-end="opacity-100 translate-y-0"
                                     class="pl-5 mt-0.5 space-y-0.5">
                                    @foreach($il->ilceler as $ilce)
                                    <div>
                                        {{-- İlçe row --}}
                                        @php $ilceId = (int)$ilce->id; $ilceHasMah = $ilce->mahalleler->isNotEmpty(); @endphp
                                        <div class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer select-none"
                                             @click="{{ $ilceHasMah ? 'toggleIlce('.$ilceId.')' : '' }}">
                                            <input type="checkbox" name="ilce[]" value="{{ $ilceId }}"
                                                   {{ in_array($ilceId, $selectedIlceIds) ? 'checked' : '' }}
                                                   class="custom-checkbox flex-shrink-0" @click.stop>
                                            <span class="text-sm font-medium text-slate-600 flex-1">{{ $ilce->ilce_adi }}</span>
                                            <span class="text-[11px] text-slate-400 tabular-nums">{{ $ilce->ilan_sayisi }}</span>
                                            @if($ilceHasMah)
                                            <svg x-bind:class="openIlces.includes({{ $ilceId }}) ? 'rotate-180' : ''"
                                                 class="w-3.5 h-3.5 text-slate-400 flex-shrink-0 transition-transform duration-150"
                                                 xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/>
                                            </svg>
                                            @endif
                                        </div>

                                        {{-- Mahalleler --}}
                                        @if($ilce->mahalleler->isNotEmpty())
                                        <div x-show="openIlces.includes({{ (int)$ilce->id }})" x-cloak
                                             x-transition:enter="transition-all duration-150 ease-out"
                                             x-transition:enter-start="opacity-0"
                                             x-transition:enter-end="opacity-100"
                                             class="pl-5 mt-0.5 space-y-0.5">
                                            @foreach($ilce->mahalleler as $mah)
                                            <label class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-50 cursor-pointer group">
                                                <input type="checkbox" name="mahalle[]" value="{{ $mah->id }}"
                                                       {{ in_array($mah->id, $selectedMahIds) ? 'checked' : '' }}
                                                       class="custom-checkbox flex-shrink-0">
                                                <span class="text-[13px] text-slate-600 flex-1 group-hover:text-primary transition-colors">{{ $mah->mahalle_adi }}</span>
                                                <span class="text-[11px] text-slate-400 tabular-nums">{{ $mah->ilan_sayisi }}</span>
                                            </label>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @empty
                                <p class="text-xs text-slate-400 px-2 py-3">Konum verisi bulunamadı.</p>
                            @endforelse
                        </div>
                    </div>

                    {{-- ─── KATEGORİ — radio, auto-submit ─── --}}
                    <div class="mb-5 pt-4 border-t border-slate-100">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3">KATEGORİ</h4>
                        <div class="space-y-2">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="radio" name="kategori_slug" value=""
                                       {{ !$kategoriSlug ? 'checked' : '' }}
                                       class="w-4 h-4 cursor-pointer" style="accent-color:#2563EB"
                                       onchange="document.getElementById('filter-form').submit()">
                                <span class="text-sm font-medium text-slate-700 group-hover:text-primary flex-1">Tümü</span>
                            </label>
                            @foreach($kategoriler as $kat)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="radio" name="kategori_slug" value="{{ $kat->slug }}"
                                       {{ $kategoriSlug === $kat->slug ? 'checked' : '' }}
                                       class="w-4 h-4 cursor-pointer" style="accent-color:#2563EB"
                                       onchange="document.getElementById('filter-form').submit()">
                                <span class="text-sm font-medium text-slate-700 group-hover:text-primary flex-1">{{ $kat->name }}</span>
                                @if(($kat->ilan_sayisi ?? 0) > 0)
                                    <span class="text-[11px] text-slate-400 tabular-nums">{{ $kat->ilan_sayisi }}</span>
                                @endif
                            </label>
                            @endforeach
                        </div>
                    </div>

                    {{-- ─── FİYAT — her kategori ─── --}}
                    <div class="mb-5 pt-4 border-t border-slate-100">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3">FİYAT ARALIĞI</h4>
                        <div class="flex gap-2">
                            <input type="number" name="min_fiyat" placeholder="Min €" value="{{ request('min_fiyat') }}"
                                   class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <input type="number" name="max_fiyat" placeholder="Max €" value="{{ request('max_fiyat') }}"
                                   class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                    </div>

                    {{-- ─── ARSA-SPECIFIC ─── --}}
                    @if($kategoriSlug === 'arsa-arazi')

                    <div class="mb-5 pt-4 border-t border-slate-100">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3">ARSA TİPİ</h4>
                        @php $selectedMulk = (array)request('mulk_tipi', []); @endphp
                        <div class="space-y-2">
                            @foreach(['İmarlı Arsa','Villa İmarlı','Tarla','Zeytinlik','Muhtelif Arsa'] as $tip)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="mulk_tipi[]" value="{{ $tip }}"
                                       {{ in_array($tip, $selectedMulk) ? 'checked' : '' }} class="custom-checkbox">
                                <span class="text-sm font-medium text-slate-700 group-hover:text-primary transition-colors">{{ $tip }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-5 pt-4 border-t border-slate-100">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3">İMAR DURUMU</h4>
                        <div class="relative">
                            <select name="imar_durumu" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 appearance-none outline-none focus:border-primary focus:ring-1 focus:ring-primary cursor-pointer">
                                <option value="">Tümü</option>
                                @foreach(['Konut','Ticari','Turizm'] as $imar)
                                <option value="{{ $imar }}" {{ request('imar_durumu') == $imar ? 'selected' : '' }}>{{ $imar }}</option>
                                @endforeach
                                <option value="Tarım" {{ request('imar_durumu') == 'Tarım' ? 'selected' : '' }}>Tarım / Zeytin</option>
                            </select>
                            <x-icon name="asagi-chevron" class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                        </div>
                    </div>

                    <div class="mb-5 pt-4 border-t border-slate-100">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3">ALAN (m²)</h4>
                        <div class="flex gap-2">
                            <input type="number" name="min_m2" placeholder="Min" value="{{ request('min_m2') }}"
                                   class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <input type="number" name="max_m2" placeholder="Max" value="{{ request('max_m2') }}"
                                   class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                    </div>

                    @else {{-- Konut / Yazlık / Genel --}}

                    <div class="mb-5 pt-4 border-t border-slate-100">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3">ODA SAYISI</h4>
                        <div class="flex flex-wrap gap-1.5">
                            @php $selectedOdalar = (array)request('oda_sayisi', []); @endphp
                            @foreach(['Stüdyo','1+1','2+1','3+1','4+'] as $oda)
                            <label class="pill-btn cursor-pointer">
                                <input type="checkbox" name="oda_sayisi[]" value="{{ $oda }}"
                                       {{ in_array($oda, $selectedOdalar) ? 'checked' : '' }} class="hidden">
                                <span class="inline-block px-2.5 py-1 text-[12px] font-semibold rounded-lg border">{{ $oda }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="mb-5 pt-4 border-t border-slate-100">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3">ALAN (m²)</h4>
                        <div class="flex gap-2">
                            <input type="number" name="min_m2" placeholder="Min" value="{{ request('min_m2') }}"
                                   class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <input type="number" name="max_m2" placeholder="Max" value="{{ request('max_m2') }}"
                                   class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                    </div>

                    <div class="mb-5 pt-4 border-t border-slate-100">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-3">ÖZELLİKLER</h4>
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
                                <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">{{ $ozel['label'] }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    @endif

                </div>{{-- /scrollable --}}

                {{-- Sticky submit --}}
                <div class="px-5 py-4 border-t border-slate-100 bg-white">
                    <button type="submit"
                            class="w-full bg-primary text-white font-bold py-3 rounded-xl hover:bg-blue-700 transition-all shadow-md shadow-primary/20 text-sm tracking-wide">
                        Filtreleri Uygula
                    </button>
                </div>

            </form>

            <!-- Özel Talep Card -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6">
                <h3 class="text-lg font-bold text-primary mb-2">Yardım İster misiniz?</h3>
                <p class="text-sm text-slate-600 mb-6 leading-relaxed">Uzman danışmanlarımız size özel mülk araştırması için 7/24 hizmetinizdedir.</p>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 text-primary font-bold text-sm hover:text-blue-800 transition-colors">
                    Uzmanla Görüşün <x-icon name="sag-ok" class="w-4 h-4" />
                </a>
            </div>

        </aside>

        <!-- Right Area (Listings) -->
        <div class="flex-1">

            <!-- Toolbar -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200 mb-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="text-sm text-slate-600 font-medium px-2">
                    <span class="font-bold text-slate-900">{{ number_format($ilanlar->total() ?? 0, 0, ',', '.') }}</span> seçkin mülk listeleniyor
                </div>

                <div class="flex items-center gap-4">
                    <!-- Layout Toggle -->
                    <div class="flex bg-slate-50 rounded-lg p-1 border border-slate-200">
                        <button type="button" @click="viewMode = 'grid'" :class="viewMode === 'grid' ? 'active' : ''" class="view-btn">
                            <x-icon name="oda" class="w-5 h-5" />
                        </button>
                        <button type="button" @click="viewMode = 'list'" :class="viewMode === 'list' ? 'active' : ''" class="view-btn">
                            <x-icon name="liste" class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Sort -->
                    <div class="flex items-center gap-2 border-l border-slate-200 pl-4">
                        <span class="text-sm font-semibold text-slate-500">Sırala:</span>
                        <div class="relative">
                            <select name="sort_by" class="bg-transparent text-sm font-bold text-slate-900 outline-none appearance-none pr-5 cursor-pointer">
                                <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>En Yeni</option>
                                <option value="fiyat_asc" {{ request('sort_by') == 'fiyat_asc' ? 'selected' : '' }}>Fiyat: Artan</option>
                                <option value="fiyat_desc" {{ request('sort_by') == 'fiyat_desc' ? 'selected' : '' }}>Fiyat: Azalan</option>
                            </select>
                            <x-icon name="asagi-chevron" class="absolute right-0 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid -->
            <div :class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 gap-8' : 'flex flex-col gap-8'">
                @forelse($ilanlar as $index => $ilan)
                    @php
                        $foto = $ilan->fotograflar?->sortBy('id')->first() ?? null;
                        $fotoUrl = $foto ? ($foto->thumbnail_url ?? '/storage/' . $foto->dosya_yolu) : null;

                        $ilAdi      = $ilan->il?->il_adi ?? $ilan->il?->name ?? '';
                        $ilceAdi    = $ilan->ilce?->ilce_adi ?? $ilan->ilce?->name ?? '';
                        $mahalleAdi = ($ilan->mahalle?->mahalle_adi && strtolower($ilan->mahalle->mahalle_adi) !== 'belirtilmemiş') ? $ilan->mahalle->mahalle_adi : null;
                        $lokasyon = collect([$mahalleAdi, $ilceAdi])->filter()->implode(', ');
                        if (empty($lokasyon)) $lokasyon = $ilAdi ?: 'Bodrum, Muğla';

                        $fiyat = $ilan->fiyat;
                        $kur = $ilan->para_birimi ?? 'USD';
                        $symbol = $kur == 'USD' ? '$' : ($kur == 'EUR' ? '€' : ($kur == 'GBP' ? '£' : '₺'));

                        $isArsaItem = $kategoriSlug === 'arsa-arazi' || str_contains($ilan->altKategori?->slug ?? '', 'arsa') || str_contains($ilan->anaKategori?->slug ?? '', 'arsa');

                        if ($isArsaItem) {
                            $badge1 = mb_strtoupper($ilan->altKategori?->name ?? 'İMARLI ARSA', 'UTF-8');
                            $badge2 = $index % 3 == 0 ? 'DENİZ SIFIR' : ($index % 4 == 0 ? 'FIRSAT İLAN' : '');
                            $badgeColor = 'bg-primary';
                            $badge2Color = 'bg-status-sale text-white';
                        } else {
                            $tipStr = strtolower($ilan->yayinTipi?->yayin_tipi ?? '');
                            $badge1 = $tipStr === 'kiralik' ? 'KİRALIK' : 'SATILIK';
                            $badgeColor = $tipStr === 'kiralik' ? 'bg-status-rent' : 'bg-status-sale';
                            $badge2 = ($ilan->created_at && $ilan->created_at->gt(now()->subDays(14))) ? 'YENİ' : '';
                            $badge2Color = 'bg-white/90 text-slate-800';
                        }
                    @endphp

                    <a href="{{ route('ilanlar.show', $ilan->id) }}" class="ilan-card-lux group bg-white rounded-2xl p-4" :class="viewMode === 'list' ? 'flex flex-col md:flex-row gap-6' : 'flex flex-col'" style="border:1px solid #dde1f0; box-shadow:0 2px 12px rgba(0,26,110,0.06);">

                        <!-- Image Container -->
                        <div class="ilan-image-wrapper bg-slate-100" :class="viewMode === 'list' ? 'w-full md:w-2/5 h-64 md:h-auto' : 'w-full h-64 mb-5'">
                            @if($foto)
                                <img src="{{ $fotoUrl }}" alt="{{ $ilan->baslik }}" class="w-full h-full object-cover">
                            @else
                                <x-property-placeholder :icon="$isArsaItem ? 'landscape' : 'villa'" />
                            @endif

                            <!-- Badges Top Left -->
                            <div class="absolute top-4 left-4 flex gap-2">
                                <span class="{{ $badgeColor }} text-white text-[10px] font-bold px-2.5 py-1 rounded-full tracking-wider">{{ $badge1 }}</span>
                                @if($badge2)
                                    <span class="{{ $badge2Color }} text-[10px] font-bold px-2.5 py-1 rounded-full tracking-wider shadow-sm">{{ $badge2 }}</span>
                                @endif
                            </div>

                            <!-- Favorite Top Right -->
                            <div class="absolute top-4 right-4">
                                 <button type="button" class="w-8 h-8 rounded-full bg-slate-900/40 backdrop-blur-md text-white flex items-center justify-center hover:bg-white hover:text-rose-500 transition-colors">
                                     <x-icon name="kaydet" class="w-4 h-4" />
                                 </button>
                             </div>

                            <!-- Price Bottom Right -->
                            @if($fiyat)
                            <div class="absolute bottom-4 right-4">
                                <div class="bg-primary text-white px-4 py-2 rounded-lg font-bold text-lg shadow-lg flex items-baseline gap-1">
                                    {{ $symbol }}{{ number_format($fiyat, 0, ',', '.') }}
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Details -->
                        <div class="flex-1 flex flex-col justify-between" :class="viewMode === 'list' ? 'py-2' : ''">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 mb-2 line-clamp-1 group-hover:text-primary transition-colors">{{ $ilan->baslik }}</h2>
                                <div class="flex items-center gap-1.5 text-sm text-slate-500 mb-6">
                                     <x-icon name="konum" class="w-4 h-4 text-slate-400" />
                                     <span>{{ $lokasyon }}</span>
                                 </div>
                            </div>

                            <!-- Amenities Footer -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-4 mt-auto">
                                @if($isArsaItem)
                                    <div class="flex flex-col gap-1 w-1/2">
                                         <span class="text-slate-400 text-[10px] uppercase font-bold tracking-widest">Arsa Alanı</span>
                                         <div class="flex items-center gap-2 text-[15px] font-bold text-slate-900">
                                             <x-icon name="alan" class="w-5 h-5 text-primary" />
                                             <span>{{ $ilan->net_m2 ? number_format($ilan->net_m2, 0, ',', '.') . ' m²' : '—' }}</span>
                                         </div>
                                     </div>
                                     <div class="flex flex-col gap-1 w-1/2">
                                         <span class="text-slate-400 text-[10px] uppercase font-bold tracking-widest">İmar Durumu</span>
                                         <div class="flex items-center gap-2 text-[15px] font-bold text-slate-900">
                                             <x-icon name="bina" class="w-5 h-5 text-primary" />
                                             <span>{{ $ilan->imar_durumu ?? '—' }}</span>
                                         </div>
                                     </div>
                                @else
                                    <div class="flex items-center gap-6">
                                         <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                             <x-icon name="yatak" class="w-4 h-4 text-slate-400" />
                                             <span>{{ $ilan->oda_sayisi ?? '—' }} Oda</span>
                                         </div>
                                         <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                             <x-icon name="banyo" class="w-4 h-4 text-slate-400" />
                                             <span>{{ $ilan->banyo_sayisi ?? '—' }} Banyo</span>
                                         </div>
                                         <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                             <x-icon name="alan" class="w-4 h-4 text-slate-400" />
                                             <span>{{ $ilan->net_m2 ? number_format($ilan->net_m2, 0, ',', '.') . ' m²' : '—' }}</span>
                                         </div>
                                     </div>
                                @endif
                            </div>
                        </div>

                    </a>
                @empty
                    <div class="col-span-full py-20 text-center bg-white rounded-2xl border border-slate-200">
                        <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                            <x-icon name="arama" class="w-8 h-8" />
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">İlan Bulunamadı</h3>
                        <p class="text-slate-500 mb-6">Arama kriterlerinize uygun gayrimenkul bulunamadı.</p>
                        <a href="{{ route('ilanlar.index') }}" class="inline-block bg-primary text-white font-semibold px-6 py-2.5 rounded-lg hover:bg-blue-700 transition-colors">
                            Filtreleri Temizle
                        </a>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($ilanlar instanceof \Illuminate\Pagination\LengthAwarePaginator && $ilanlar->hasPages())
            <div class="mt-12 flex justify-center gap-2">
                @if ($ilanlar->onFirstPage())
                    <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 bg-white"><x-icon name="sol-chevron" class="w-4 h-4" /></span>
                @else
                    <a href="{{ $ilanlar->previousPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 bg-white hover:border-primary hover:text-primary transition-colors"><x-icon name="sol-chevron" class="w-4 h-4" /></a>
                @endif

                @foreach ($ilanlar->getUrlRange(max(1, $ilanlar->currentPage() - 2), min($ilanlar->lastPage(), $ilanlar->currentPage() + 2)) as $page => $url)
                    @if ($page == $ilanlar->currentPage())
                        <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-primary bg-primary text-white font-bold text-sm">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 bg-white hover:border-primary hover:text-primary font-medium text-sm transition-colors">{{ $page }}</a>
                    @endif
                @endforeach

                @if ($ilanlar->currentPage() < $ilanlar->lastPage() - 2)
                    <span class="w-10 h-10 flex items-center justify-center text-slate-500">...</span>
                    <a href="{{ $ilanlar->url($ilanlar->lastPage()) }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 bg-white hover:border-primary hover:text-primary font-medium text-sm transition-colors">{{ $ilanlar->lastPage() }}</a>
                @endif

                @if ($ilanlar->hasMorePages())
                    <a href="{{ $ilanlar->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 bg-white hover:border-primary hover:text-primary transition-colors"><x-icon name="sag-chevron" class="w-4 h-4" /></a>
                @else
                    <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 bg-white"><x-icon name="sag-chevron" class="w-4 h-4" /></span>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>
@endsection
