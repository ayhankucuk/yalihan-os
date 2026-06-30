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
<div class="bg-slate-50 min-h-screen pt-32 pb-20" x-data="{ viewMode: 'grid' }">
    
    <!-- Header -->
    <div class="max-w-[1400px] mx-auto px-6 mb-10">
        <h1 class="text-4xl font-black text-slate-900 mb-3 font-display-hero">{{ $title }}</h1>
        <p class="text-lg text-slate-600 max-w-2xl">{{ $subtitle }}</p>
    </div>

    <!-- Main Content -->
    <div class="max-w-[1400px] mx-auto px-6 flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar Filters -->
        <aside class="w-full lg:w-80 flex-shrink-0">
            <!-- Filter Form -->
            <form action="{{ route('ilanlar.index') }}" method="GET" class="bg-white rounded-2xl p-6 border border-slate-200 mb-6 shadow-sm sticky top-28">
                
                @if(request('yayin_tipi'))
                    <input type="hidden" name="yayin_tipi" value="{{ request('yayin_tipi') }}">
                @endif
                
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-lg font-bold text-slate-900">Gelişmiş Arama</h3>
                    @if(request()->hasAny(['search', 'kategori', 'kategori_slug', 'il', 'min_fiyat', 'max_fiyat', 'oda_sayisi', 'havuz_var', 'imar_durumu']))
                        <a href="{{ route('ilanlar.index') }}" class="text-sm font-semibold text-primary hover:text-blue-700 transition-colors">Sıfırla</a>
                    @endif
                </div>
                
                @if($kategoriSlug === 'arsa-arazi')
                <!-- MÜLK TİPİ -->
                <div class="mb-6">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">MÜLK TİPİ</h4>
                    <div class="space-y-2">
                        @php $selectedMulkTipi = (array) request('mulk_tipi', []); @endphp
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="mulk_tipi[]" value="İmarlı Arsa" {{ in_array('İmarlı Arsa', $selectedMulkTipi) ? 'checked' : '' }} class="custom-checkbox">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-primary transition-colors">İmarlı Arsa</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="mulk_tipi[]" value="Tarla" {{ in_array('Tarla', $selectedMulkTipi) ? 'checked' : '' }} class="custom-checkbox">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-primary transition-colors">Tarla</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="mulk_tipi[]" value="Zeytinlik" {{ in_array('Zeytinlik', $selectedMulkTipi) ? 'checked' : '' }} class="custom-checkbox">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-primary transition-colors">Zeytinlik</span>
                        </label>
                    </div>
                </div>

                <!-- KONUM (BODRUM) -->
                <div class="mb-6">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">KONUM (BODRUM)</h4>
                    <div class="space-y-2 max-h-40 overflow-y-auto pr-2 custom-scrollbar">
                        @php $selectedMahalleler = (array) request('mahalle', []); @endphp
                        @foreach($bodrumMahalleleri ?? [] as $mahalle)
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="mahalle[]" value="{{ $mahalle->id }}" {{ in_array($mahalle->id, $selectedMahalleler) ? 'checked' : '' }} class="custom-checkbox">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-primary transition-colors">{{ $mahalle->mahalle_adi }}</span>
                        </label>
                        @endforeach
                    </div>
                    <!-- Fallback if bodrumMahalleleri is empty but we still want text search -->
                    <div class="mt-3 relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">search</span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Diğer bölgeler..." class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>

                <!-- FİYAT ARALIĞI -->
                <div class="mb-6">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">FİYAT ARALIĞI (€)</h4>
                    <div class="flex items-center gap-2">
                        <input type="number" name="min_fiyat" placeholder="Min" value="{{ request('min_fiyat') }}" class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <input type="number" name="max_fiyat" placeholder="Max" value="{{ request('max_fiyat') }}" class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>

                <!-- METREKARE ARALIĞI -->
                <div class="mb-6">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">METREKARE ARALIĞI (m²)</h4>
                    <div class="flex items-center gap-2">
                        <input type="number" name="min_m2" placeholder="Min" value="{{ request('min_m2') }}" class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        <input type="number" name="max_m2" placeholder="Max" value="{{ request('max_m2') }}" class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                    </div>
                </div>

                <!-- TEKNİK ÖZELLİKLER -->
                <div class="mb-8">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">TEKNİK ÖZELLİKLER</h4>
                    <div class="space-y-3">
                        <select name="imar_durumu" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all cursor-pointer">
                            <option value="">İmar Durumu Seçin</option>
                            <option value="Konut" {{ request('imar_durumu') == 'Konut' ? 'selected' : '' }}>Konut</option>
                            <option value="Ticari" {{ request('imar_durumu') == 'Ticari' ? 'selected' : '' }}>Ticari</option>
                            <option value="Turizm" {{ request('imar_durumu') == 'Turizm' ? 'selected' : '' }}>Turizm</option>
                            <option value="Tarım" {{ request('imar_durumu') == 'Tarım' ? 'selected' : '' }}>Tarım / Zeytin</option>
                        </select>
                        <div class="flex items-center gap-2">
                            <input type="text" name="gabari" placeholder="Gabari" value="{{ request('gabari') }}" class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                            <input type="text" name="taks_kaks" placeholder="TAKS/KAKS" value="{{ request('taks_kaks') }}" class="w-1/2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                        </div>
                    </div>
                </div>

                @else
                
                <!-- Konum (Arama / İl-İlçe) -->
                <div class="mb-6">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">KONUM</h4>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px] pointer-events-none">location_on</span>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="İl, ilçe veya mahalle" class="w-full bg-white border border-slate-200 rounded-lg pl-10 pr-4 py-2.5 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                    </div>
                </div>
                
                <!-- Fiyat Aralığı -->
                <div class="mb-6">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">FİYAT ARALIĞI</h4>
                    <div class="flex items-center gap-3">
                        <input type="number" name="min_fiyat" placeholder="Min" value="{{ request('min_fiyat') }}" class="w-1/2 bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                        <input type="number" name="max_fiyat" placeholder="Max" value="{{ request('max_fiyat') }}" class="w-1/2 bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                    </div>
                </div>
                
                <!-- Emlak Tipi -->
                <div class="mb-6">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">EMLAK TİPİ</h4>
                    <div class="relative">
                        <select name="kategori" class="w-full bg-white border border-slate-200 rounded-lg px-4 py-2.5 text-sm font-medium text-slate-700 appearance-none outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all cursor-pointer shadow-sm">
                            <option value="">Tüm Tipler</option>
                            @foreach ($kategoriler ?? [] as $kat)
                                <option value="{{ $kat->id }}" {{ request('kategori') == $kat->id ? 'selected' : '' }}>{{ $kat->name }}</option>
                            @endforeach
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-[18px]">expand_more</span>
                    </div>
                </div>
                
                <!-- Oda Sayısı -->
                <div class="mb-6">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2">ODA SAYISI</h4>
                    <div class="flex flex-wrap gap-2">
                        @php $selectedOdalar = (array) request('oda_sayisi', []); @endphp
                        <label class="pill-btn cursor-pointer">
                            <input type="checkbox" name="oda_sayisi[]" value="Stüdyo" {{ in_array('Stüdyo', $selectedOdalar) ? 'checked' : '' }} class="hidden">
                            <span class="inline-block px-3 py-1.5 text-[13px] font-semibold rounded-lg border">Stüdyo</span>
                        </label>
                        <label class="pill-btn cursor-pointer">
                            <input type="checkbox" name="oda_sayisi[]" value="1+1" {{ in_array('1+1', $selectedOdalar) ? 'checked' : '' }} class="hidden">
                            <span class="inline-block px-3 py-1.5 text-[13px] font-semibold rounded-lg border">1+1</span>
                        </label>
                        <label class="pill-btn cursor-pointer">
                            <input type="checkbox" name="oda_sayisi[]" value="2+1" {{ in_array('2+1', $selectedOdalar) ? 'checked' : '' }} class="hidden">
                            <span class="inline-block px-3 py-1.5 text-[13px] font-semibold rounded-lg border">2+1</span>
                        </label>
                        <label class="pill-btn cursor-pointer">
                            <input type="checkbox" name="oda_sayisi[]" value="3+1" {{ in_array('3+1', $selectedOdalar) ? 'checked' : '' }} class="hidden">
                            <span class="inline-block px-3 py-1.5 text-[13px] font-semibold rounded-lg border">3+1</span>
                        </label>
                        <label class="pill-btn cursor-pointer">
                            <input type="checkbox" name="oda_sayisi[]" value="4+" {{ in_array('4+', $selectedOdalar) ? 'checked' : '' }} class="hidden">
                            <span class="inline-block px-3 py-1.5 text-[13px] font-semibold rounded-lg border">4+</span>
                        </label>
                    </div>
                </div>
                
                <!-- Özellikler -->
                <div class="mb-8">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">ÖZELLİKLER</h4>
                    <div class="space-y-3">
                        @php $selectedOzel = request('havuz_var') ? true : false; @endphp
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" name="havuz_var" value="1" {{ $selectedOzel ? 'checked' : '' }} class="custom-checkbox">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">Havuz</span>
                        </label>
                        <!-- Other features can be added here statically or dynamically -->
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" class="custom-checkbox">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">Akıllı Ev Sistemi</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" class="custom-checkbox">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">Güvenlik</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" class="custom-checkbox">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">Otopark</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="checkbox" class="custom-checkbox">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">Spor Salonu</span>
                        </label>
                    </div>
                </div>
                @endif
                
                <button type="submit" class="w-full bg-primary text-white font-bold py-3.5 rounded-xl hover:bg-blue-700 transition-all shadow-md shadow-primary/20">
                    Filtreleri Uygula
                </button>
            </form>
            
            <!-- Özel Talep Card -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6">
                <h3 class="text-lg font-bold text-primary mb-2">Need Help?</h3>
                <p class="text-sm text-slate-600 mb-6 leading-relaxed">Our dedicated concierges are available 24/7 for tailored property searches.</p>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 text-primary font-bold text-sm hover:text-blue-800 transition-colors">
                    Talk to an Expert <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
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
                            <span class="material-symbols-outlined text-xl">grid_view</span>
                        </button>
                        <button type="button" @click="viewMode = 'list'" :class="viewMode === 'list' ? 'active' : ''" class="view-btn">
                            <span class="material-symbols-outlined text-xl">view_list</span>
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
                            <span class="material-symbols-outlined absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-base">expand_more</span>
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
                        
                        $ilAdi   = $ilan->il?->il_adi ?? $ilan->il?->name ?? '';
                        $ilceAdi = $ilan->ilce?->ilce_adi ?? $ilan->ilce?->name ?? '';
                        $lokasyon = collect([$ilceAdi, $ilAdi])->filter()->implode(', ');
                        if (empty($lokasyon)) $lokasyon = 'Bodrum, Muğla';
                        
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
                    
                    <a href="{{ route('ilanlar.show', $ilan->id) }}" class="ilan-card-lux group bg-white rounded-2xl p-4 border border-slate-200" :class="viewMode === 'list' ? 'flex flex-col md:flex-row gap-6' : 'flex flex-col'">
                        
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
                                    <span class="material-symbols-outlined text-[18px]">favorite_border</span>
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
                                    <span class="material-symbols-outlined text-[16px]">location_on</span>
                                    <span>{{ $lokasyon }}</span>
                                </div>
                            </div>
                            
                            <!-- Amenities Footer -->
                            <div class="flex items-center justify-between border-t border-slate-100 pt-4 mt-auto">
                                @if($isArsaItem)
                                    <div class="flex flex-col gap-1 w-1/2">
                                        <span class="text-slate-400 text-[10px] uppercase font-bold tracking-widest">Arsa Alanı</span>
                                        <div class="flex items-center gap-2 text-[15px] font-bold text-slate-900">
                                            <span class="material-symbols-outlined text-primary text-[20px]">square_foot</span>
                                            <span>{{ number_format($ilan->net_m2 ?? 5200, 0, ',', '.') }} m²</span>
                                        </div>
                                    </div>
                                    <div class="flex flex-col gap-1 w-1/2">
                                        <span class="text-slate-400 text-[10px] uppercase font-bold tracking-widest">İmar Durumu</span>
                                        <div class="flex items-center gap-2 text-[15px] font-bold text-slate-900">
                                            <span class="material-symbols-outlined text-primary text-[20px]">domain</span>
                                            <span>{{ $ilan->imar_durumu ?? 'Konut / %15' }}</span>
                                        </div>
                                    </div>
                                @else
                                    <div class="flex items-center gap-6">
                                        <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                            <span class="material-symbols-outlined text-slate-400 text-[18px]">bed</span>
                                            <span>{{ $ilan->oda_sayisi ?? '—' }} Oda</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                            <span class="material-symbols-outlined text-slate-400 text-[18px]">bathtub</span>
                                            <span>{{ $ilan->banyo_sayisi ?? '—' }} Banyo</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                            <span class="material-symbols-outlined text-slate-400 text-[18px]">square_foot</span>
                                            <span>{{ number_format($ilan->net_m2 ?? 5200, 0, ',', '.') }} m²</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                    </a>
                @empty
                    <div class="col-span-full py-20 text-center bg-white rounded-2xl border border-slate-200">
                        <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-3xl">search_off</span>
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
                    <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 bg-white"><span class="material-symbols-outlined text-sm">chevron_left</span></span>
                @else
                    <a href="{{ $ilanlar->previousPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 bg-white hover:border-primary hover:text-primary transition-colors"><span class="material-symbols-outlined text-sm">chevron_left</span></a>
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
                    <a href="{{ $ilanlar->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 bg-white hover:border-primary hover:text-primary transition-colors"><span class="material-symbols-outlined text-sm">chevron_right</span></a>
                @else
                    <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 bg-white"><span class="material-symbols-outlined text-sm">chevron_right</span></span>
                @endif
            </div>
            @endif
            
        </div>
    </div>
</div>
@endsection
