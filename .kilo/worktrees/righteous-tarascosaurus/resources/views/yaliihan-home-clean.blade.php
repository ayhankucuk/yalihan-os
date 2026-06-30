@extends('layouts.frontend')

@section('title', $seo['title'] ?? 'Yalıhan Emlak — Bodrum\'da Lüks Gayrimenkul')

@push('styles')
<style>
    .glass-effect {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }
    .hero-gradient {
        background: linear-gradient(105deg, rgba(10, 22, 45, 0.78) 0%, rgba(10, 22, 45, 0.55) 45%, rgba(10, 22, 45, 0.30) 100%);
    }
    
    /* Tailwind's active:scale won't work perfectly on custom tabs without this */
    .search-tab-btn {
        transition: all 0.3s ease;
    }
    .search-tab-btn.active {
        background-color: white;
        color: var(--primary);
    }
    .search-tab-btn:not(.active) {
        background-color: rgba(255, 255, 255, 0.4);
        color: white;
    }
    .search-tab-btn:not(.active):hover {
        background-color: rgba(255, 255, 255, 0.6);
    }
</style>
@endpush

@section('content')
<!-- Hero Section with Search -->
<section class="relative h-[90vh] min-h-[700px] flex items-center pt-20" x-data="{ activeTab: 'konut' }">
    <div class="absolute inset-0 z-0">
        <img alt="Bodrum lüks gayrimenkul" class="w-full h-full object-cover" src="{{ asset('assets/img/hero_luxury.jpg') }}">
        <div class="absolute inset-0 hero-gradient"></div>
    </div>
    
    <div class="relative z-10 max-w-7xl mx-auto px-6 md:px-12 w-full">
        <div class="max-w-3xl">
            <h1 class="font-display-hero text-display-hero text-white mb-6">Bodrum Yarımadası'nda Seçkin Bir Yaşam.</h1>
            <p class="text-white/90 text-body-lg mb-12 max-w-xl">Disiplinli yatırımcılar için özel olarak hazırlanmış lüks konut ve ticari mülk portföyümüze erişin.</p>
        </div>
        
        <!-- Advanced Search Bar -->
        <div class="max-w-5xl">
            <div class="flex gap-1 mb-0 px-2">
                <button @click="activeTab = 'konut'" :class="activeTab === 'konut' ? 'bg-white text-primary' : 'bg-white/40 text-white hover:bg-white/60'" class="search-tab-btn px-8 py-3 font-semibold text-body-sm rounded-t-xl border-t border-l border-r border-outline-variant/30 relative z-10 transition-all">Konut</button>
                <button @click="activeTab = 'arsa'" :class="activeTab === 'arsa' ? 'bg-white text-primary' : 'bg-white/40 text-white hover:bg-white/60'" class="search-tab-btn px-8 py-3 font-semibold text-body-sm rounded-t-xl border-t border-l border-r border-outline-variant/20 transition-all">Arsa</button>
                <button @click="activeTab = 'yazlik-kiralama'" :class="activeTab === 'yazlik-kiralama' ? 'bg-white text-primary' : 'bg-white/40 text-white hover:bg-white/60'" class="search-tab-btn px-8 py-3 font-semibold text-body-sm rounded-t-xl border-t border-l border-r border-outline-variant/20 whitespace-nowrap transition-all">Yazlık Kiralık</button>
                <button @click="activeTab = 'yurt-disi'" :class="activeTab === 'yurt-disi' ? 'bg-white text-primary' : 'bg-white/40 text-white hover:bg-white/60'" class="search-tab-btn px-8 py-3 font-semibold text-body-sm rounded-t-xl border-t border-l border-r border-outline-variant/20 whitespace-nowrap transition-all">Yurtdışı Gayrimenkul</button>
            </div>
            
            <div class="glass-effect rounded-xl rounded-tl-none shadow-2xl p-4 md:p-6">
                <form method="GET" action="{{ route('ilanlar.index') }}">
                    <input type="hidden" name="kategori_slug" x-bind:value="activeTab">
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">location_on</span>
                            <select name="ilce" class="w-full pl-10 pr-4 py-3 bg-white border border-outline-variant/30 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all outline-none text-body-sm appearance-none">
                                <option value="">Lokasyon Seçin</option>
                                @foreach($locations as $loc)
                                    <option value="{{ $loc['value'] }}">{{ $loc['label'] }}</option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                        </div>
                        
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">home</span>
                            <select name="kategori" class="w-full pl-10 pr-4 py-3 bg-white border border-outline-variant/30 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all outline-none text-body-sm appearance-none">
                                <option value="">Mülk Tipi</option>
                                @foreach($propertyTypes as $type)
                                    <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                        </div>
                        
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">payments</span>
                            <select name="max_fiyat" class="w-full pl-10 pr-4 py-3 bg-white border border-outline-variant/30 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent transition-all outline-none text-body-sm appearance-none">
                                <option value="">Fiyat Aralığı</option>
                                <option value="5000000">5M ₺'ye kadar</option>
                                <option value="10000000">10M ₺'ye kadar</option>
                                <option value="25000000">25M ₺'ye kadar</option>
                                <option value="50000000">50M ₺'ye kadar</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none">expand_more</span>
                        </div>
                        
                        <button type="submit" class="bg-primary text-white font-bold py-3 rounded-lg flex items-center justify-center gap-2 hover:bg-blue-700 transition-all active:scale-[0.98]">
                            <span class="material-symbols-outlined">search</span> Gayrimenkul Ara
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Güven Şeridi -->
<section class="bg-white border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-6 md:px-12 py-10 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
        <div>
            <p class="text-4xl font-extrabold text-primary">{{ $stats['active_listings'] ?? 0 }}+</p>
            <p class="text-sm text-on-surface-variant mt-1 uppercase tracking-wide">Aktif İlan</p>
        </div>
        <div>
            <p class="text-4xl font-extrabold text-primary">{{ $stats['experience_years'] ?? 20 }}+</p>
            <p class="text-sm text-on-surface-variant mt-1 uppercase tracking-wide">Yıllık Deneyim</p>
        </div>
        <div>
            <p class="text-4xl font-extrabold text-primary">{{ number_format($stats['happy_customers'] ?? 1500, 0, ',', '.') }}+</p>
            <p class="text-sm text-on-surface-variant mt-1 uppercase tracking-wide">Mutlu Müşteri</p>
        </div>
        <div>
            <p class="text-4xl font-extrabold text-primary">6</p>
            <p class="text-sm text-on-surface-variant mt-1 uppercase tracking-wide">Bölge</p>
        </div>
    </div>
</section>

<!-- Popüler Bölgeler -->
@if($populerMahalleler->isNotEmpty() || !empty($bolgeCounts))
<section class="py-section-gap bg-surface-container-low">
    <div class="max-w-7xl mx-auto px-6 md:px-12">
        <div class="text-center mb-16">
            <span class="text-primary font-label-caps text-label-caps mb-2 block uppercase tracking-widest">KEŞFEDİN</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Popüler Bölgeler</h2>
            <p class="text-body-md text-on-surface-variant mt-4">Bodrum Yarımadası’nın en gözde lokasyonlarında portföyümüz sizleri bekliyor.</p>
        </div>
        
        @php
            // İlçe düzeyi sayılar (bolgeCounts) önceliklidir; yoksa mahalle verisine düşeriz.
            $bolgeler = collect($bolgeCounts ?? [])
                ->filter(fn ($adet) => $adet > 0)
                ->sortDesc()
                ->map(fn ($adet, $ad) => ['ad' => $ad, 'adet' => $adet]);

            if ($bolgeler->isEmpty()) {
                $bolgeler = $populerMahalleler->map(fn ($mah) => ['ad' => $mah->mahalle_adi, 'adet' => $mah->ilan_sayisi]);
            }
        @endphp

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            @foreach($bolgeler as $bolge)
                <a href="{{ route('ilanlar.index', ['ilce' => $bolge['ad']]) }}"
                   class="relative h-44 rounded-2xl overflow-hidden group block"
                   style="background: linear-gradient(150deg, var(--primary) 0%, var(--primary-container) 60%, #3b82f6 100%);">
                    <div class="absolute inset-0" aria-hidden="true"
                         style="background-image: linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px); background-size: 26px 26px;"></div>
                    <div class="absolute inset-0 p-5 flex flex-col justify-between text-white">
                        <span class="material-symbols-outlined opacity-80 transition-transform duration-500 group-hover:scale-110">location_on</span>
                        <div>
                            <h4 class="text-base font-bold leading-tight">{{ $bolge['ad'] }}</h4>
                            <p class="text-xs mt-0.5" style="opacity:.8;">{{ $bolge['adet'] }} İlan</p>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Öne Çıkan İlanlar -->
<section class="py-section-gap max-w-7xl mx-auto px-6 md:px-12">
    <div class="flex justify-between items-end mb-12">
        <div>
            <span class="text-primary font-label-caps text-label-caps mb-2 block uppercase">ÖZEL SEÇKİ</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Öne Çıkan İlanlar</h2>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('ilanlar.index') }}" class="px-6 py-2.5 rounded-full font-semibold text-body-sm transition-all bg-primary text-white">Hepsi</a>
            @foreach(array_slice($propertyTypes, 0, 2) as $type)
                <a href="{{ route('ilanlar.index', ['kategori' => $type['value']]) }}" class="px-6 py-2.5 rounded-full font-semibold text-body-sm transition-all bg-surface-container text-on-surface-variant hover:bg-surface-container-high">{{ $type['label'] }}</a>
            @endforeach
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-grid-gutter">
        @forelse($featuredProperties as $ilan)
            @php 
                $foto = $ilan->fotograflar?->sortBy('id')->first(); 
                $tipStr = strtolower($ilan->yayinTipi?->yayin_tipi ?? '');
            @endphp
            <a href="{{ route('ilanlar.show', $ilan->id) }}" class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500 group block">
                <div class="relative h-64 overflow-hidden bg-surface-container-high">
                    @if($foto)
                        <img alt="{{ $ilan->baslik }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" src="{{ Storage::url($foto->dosya_yolu) }}">
                    @else
                        <x-property-placeholder icon="villa" />
                    @endif
                    
                    <div class="absolute top-4 left-4">
                        @if($tipStr === 'kiralik')
                            <span class="bg-status-rent text-white px-3 py-1 rounded-full text-[10px] uppercase font-bold">Kiralık</span>
                        @else
                            <span class="bg-status-sale text-white px-3 py-1 rounded-full text-[10px] uppercase font-bold">Satılık</span>
                        @endif
                    </div>
                    
                    @if($ilan->fiyat)
                        <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg font-bold text-primary">
                            {{ number_format($ilan->fiyat, 0, ',', '.') }} ₺
                        </div>
                    @endif
                </div>
                
                <div class="p-6">
                    <h3 class="font-headline-sm text-headline-sm mb-2 line-clamp-1">{{ $ilan->baslik ?: 'İlan #'.$ilan->id }}</h3>
                    <p class="text-on-surface-variant flex items-center gap-1 mb-4 text-sm">
                        <span class="material-symbols-outlined text-sm">location_on</span>
                        {{ $ilan->ilce?->ilce_adi ?? $ilan->il?->il_adi ?? 'Bodrum' }}
                    </p>
                    
                    <div class="flex justify-between py-4 border-t border-slate-100 text-on-surface-variant text-sm">
                        @if($ilan->oda_sayisi)
                            <div class="flex items-center gap-1"><span class="material-symbols-outlined text-outline text-sm">bed</span><span>{{ $ilan->oda_sayisi }} Oda</span></div>
                        @endif
                        @if($ilan->net_m2)
                            <div class="flex items-center gap-1"><span class="material-symbols-outlined text-outline text-sm">square_foot</span><span>{{ number_format($ilan->net_m2, 0) }} m²</span></div>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12 text-on-surface-variant">
                Henüz öne çıkan ilan bulunmuyor.
            </div>
        @endforelse
    </div>
</section>

<!-- Satılık Konutlar -->
@if($satılıkKonut->isNotEmpty())
<section class="py-section-gap bg-surface-muted">
    <div class="max-w-7xl mx-auto px-6 md:px-12">
        <div class="flex justify-between items-end mb-12">
            <div>
                <span class="text-primary font-label-caps text-label-caps mb-2 block uppercase tracking-widest">SATILIK</span>
                <h2 class="font-headline-lg text-headline-lg text-on-surface">Satılık Konutlar</h2>
            </div>
            <a href="{{ route('ilanlar.index', ['kategori_slug' => 'konut']) }}" class="hidden md:inline-flex items-center gap-1 text-primary font-semibold hover:gap-2 transition-all">
                Tümü <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-grid-gutter">
            @foreach($satılıkKonut as $ilan)
                <x-property-card :ilan="$ilan" />
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Yazlık Kiralık -->
@if($yazlıkIlanları->isNotEmpty())
<section class="py-section-gap max-w-7xl mx-auto px-6 md:px-12">
    <div class="flex justify-between items-end mb-12">
        <div>
            <span class="text-primary font-label-caps text-label-caps mb-2 block uppercase tracking-widest">TATİL & KİRALIK</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Yazlık Kiralıklar</h2>
        </div>
        <a href="{{ route('ilanlar.index', ['kategori_slug' => 'yazlik-kiralama']) }}" class="hidden md:inline-flex items-center gap-1 text-primary font-semibold hover:gap-2 transition-all">
            Tümü <span class="material-symbols-outlined text-base">arrow_forward</span>
        </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-grid-gutter">
        @foreach($yazlıkIlanları as $ilan)
            <x-property-card :ilan="$ilan" />
        @endforeach
    </div>
</section>
@endif

<!-- Yatırım Fırsatı Arsalar -->
@if($arsaIlanları->isNotEmpty())
<section class="py-section-gap bg-surface-muted">
    <div class="max-w-7xl mx-auto px-6 md:px-12">
        <div class="mb-12">
            <span class="text-primary font-label-caps text-label-caps mb-2 block uppercase tracking-widest">YATIRIM FIRSATI</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Arsalar</h2>
            <p class="text-body-md text-on-surface-variant mt-2">Bodrum Yarımadası’nda yatırım potansiyeli yüksek, imarlı ve deniz manzaralı seçkin arsa portföyümüz.</p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-grid-gutter">
            @foreach($arsaIlanları as $ilan)
                @php $foto = $ilan->fotograflar?->sortBy('id')->first(); @endphp
                <a href="{{ route('ilanlar.show', $ilan->id) }}" class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-500 group block">
                    <div class="relative h-64 overflow-hidden bg-surface-container-high">
                        @if($foto)
                            <img alt="{{ $ilan->baslik }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" src="{{ Storage::url($foto->dosya_yolu) }}">
                        @else
                            <x-property-placeholder icon="landscape" />
                        @endif
                        
                        <div class="absolute top-4 left-4 flex gap-2">
                            <span class="bg-primary text-white px-3 py-1 rounded-full text-[10px] font-bold uppercase">Arsa</span>
                        </div>
                        
                        @if($ilan->fiyat)
                            <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg font-bold text-primary">
                                {{ number_format($ilan->fiyat, 0, ',', '.') }} ₺
                            </div>
                        @endif
                    </div>
                    
                    <div class="p-6">
                        <h3 class="font-headline-sm text-headline-sm mb-2 leading-tight line-clamp-2">{{ $ilan->baslik ?: 'Arsa #'.$ilan->id }}</h3>
                        <p class="text-on-surface-variant flex items-center gap-1 mb-4 text-sm">
                            <span class="material-symbols-outlined text-sm">location_on</span>
                            {{ $ilan->ilce?->ilce_adi ?? 'Bodrum' }}
                        </p>
                        <div class="flex items-center gap-2 py-4 border-t border-slate-100">
                            <span class="material-symbols-outlined text-outline">straighten</span>
                            <span class="text-meta-data text-on-surface-variant">Yatırım Potansiyeli</span>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Global Yatırım & Yurt Dışı Portföy -->
<section class="py-section-gap max-w-7xl mx-auto px-6 md:px-12 bg-white">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-20 items-center">
        <div>
            <span class="text-primary font-label-caps text-label-caps mb-4 block uppercase tracking-widest">KÜRESEL VİZYON</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface mb-6 leading-tight">Global Yatırım & Yurt Dışı Portföy</h2>
            <p class="text-body-lg text-on-surface-variant mb-8">Yalıhan Emlak olarak, yerel pazarın ötesine geçerek dünyaca ünlü metropollerde ve tatil bölgelerinde seçkin yatırım fırsatları sunuyoruz. Döviz bazlı getiri sağlayan yurt dışı gayrimenkul çözümlerimizle portföyünüzü çeşitlendirin.</p>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-blue-50 flex items-center justify-center rounded-lg text-primary flex-shrink-0">
                        <span class="material-symbols-outlined text-xl">public</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-on-surface mb-1">Global Ağ</h4>
                        <p class="text-body-sm text-on-surface-variant">Dünya çapında ortaklıklar.</p>
                    </div>
                </div>
                <div class="flex gap-4">
                    <div class="w-10 h-10 bg-blue-50 flex items-center justify-center rounded-lg text-primary flex-shrink-0">
                        <span class="material-symbols-outlined text-xl">currency_exchange</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-on-surface mb-1">Döviz Getirisi</h4>
                        <p class="text-body-sm text-on-surface-variant">Yüksek kira getirili yatırımlar.</p>
                    </div>
                </div>
            </div>
            
            <a href="{{ route('ilanlar.international') }}" class="inline-flex bg-primary text-white font-bold px-8 py-3.5 rounded-xl hover:bg-blue-700 transition-all items-center gap-2">
                Tüm Yurt Dışı İlanları <span class="material-symbols-outlined">arrow_forward</span>
            </a>
        </div>
        
        <div class="relative">
            <div class="aspect-[4/3] rounded-3xl overflow-hidden shadow-2xl relative z-10">
                <x-property-placeholder icon="villa" />
            </div>
            <div class="absolute -top-10 -right-10 w-48 h-48 bg-primary/10 rounded-full -z-10 blur-2xl"></div>
        </div>
    </div>
</section>

<!-- Yurt Dışı / Uluslararası İlanlar -->
@if($yurtDışıIlanları->isNotEmpty())
<section class="py-section-gap bg-surface-muted">
    <div class="max-w-7xl mx-auto px-6 md:px-12">
        <div class="flex justify-between items-end mb-12">
            <div>
                <span class="text-primary font-label-caps text-label-caps mb-2 block uppercase tracking-widest">ULUSLARARASI</span>
                <h2 class="font-headline-lg text-headline-lg text-on-surface">Yurt Dışı Gayrimenkuller</h2>
            </div>
            <a href="{{ route('ilanlar.international') }}" class="hidden md:inline-flex items-center gap-1 text-primary font-semibold hover:gap-2 transition-all">
                Tümü <span class="material-symbols-outlined text-base">arrow_forward</span>
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-grid-gutter">
            @foreach($yurtDışıIlanları as $ilan)
                <x-property-card :ilan="$ilan" />
            @endforeach
        </div>
    </div>
</section>
@endif

<!-- Müşteri Yorumları -->
<section class="py-section-gap bg-surface-container-low">
    <div class="max-w-7xl mx-auto px-6 md:px-12">
        <div class="text-center mb-16">
            <span class="text-primary font-label-caps text-label-caps mb-2 block uppercase tracking-widest">REFERANSLARIMIZ</span>
            <h2 class="font-headline-lg text-headline-lg text-on-surface">Müşteri Yorumları</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl shadow-sm">
                <div class="w-20 h-20 rounded-full mb-4 flex items-center justify-center text-white text-xl font-bold shadow-md" style="background: linear-gradient(135deg, var(--primary), #3b82f6);">AY</div>
                <h4 class="font-bold text-on-surface">Ahmet Y.</h4>
                <p class="text-xs text-primary mb-3 uppercase font-semibold">Lüks Konut Alıcısı</p>
                <p class="text-body-sm text-on-surface-variant italic">"Hayalimdeki evi bulmak çok kolaydı. Profesyonel yaklaşımları için teşekkürler."</p>
            </div>
            
            <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl shadow-sm">
                <div class="w-20 h-20 rounded-full mb-4 flex items-center justify-center text-white text-xl font-bold shadow-md" style="background: linear-gradient(135deg, var(--primary), #3b82f6);">ED</div>
                <h4 class="font-bold text-on-surface">Elif D.</h4>
                <p class="text-xs text-primary mb-3 uppercase font-semibold">Gayrimenkul Yatırımcısı</p>
                <p class="text-body-sm text-on-surface-variant italic">"Yatırım portföyümü genişletirken verdikleri stratejik danışmanlık paha biçilemezdi."</p>
            </div>
            
            <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl shadow-sm">
                <div class="w-20 h-20 rounded-full mb-4 flex items-center justify-center text-white text-xl font-bold shadow-md" style="background: linear-gradient(135deg, var(--primary), #3b82f6);">CK</div>
                <h4 class="font-bold text-on-surface">Caner K.</h4>
                <p class="text-xs text-primary mb-3 uppercase font-semibold">Emlak Satıcısı</p>
                <p class="text-body-sm text-on-surface-variant italic">"Süreç boyunca her adımda şeffaf ve yardımcı oldular. Güvenle tavsiye ediyorum."</p>
            </div>
            
            <div class="flex flex-col items-center text-center p-6 bg-white rounded-2xl shadow-sm">
                <div class="w-20 h-20 rounded-full mb-4 flex items-center justify-center text-white text-xl font-bold shadow-md" style="background: linear-gradient(135deg, var(--primary), #3b82f6);">MA</div>
                <h4 class="font-bold text-on-surface">Merve A.</h4>
                <p class="text-xs text-primary mb-3 uppercase font-semibold">Proje Geliştirici</p>
                <p class="text-body-sm text-on-surface-variant italic">"Bölgeye olan hakimiyetleri ve etik değerleri bizi çok etkiledi. Harika bir iş ortağı."</p>
            </div>
        </div>
    </div>
</section>

<!-- Kurumsal Vizyon -->
<section class="py-section-gap bg-white">
    <div class="max-w-7xl mx-auto px-6 md:px-12">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div class="order-2 lg:order-1 relative">
                <div class="aspect-video rounded-3xl overflow-hidden shadow-2xl">
                    <x-property-placeholder icon="landscape" />
                </div>
                <div class="absolute -bottom-6 -right-6 bg-primary p-8 rounded-2xl text-white shadow-xl hidden md:block">
                    <p class="text-4xl font-extrabold mb-1">{{ $stats['experience_years'] ?? '20' }}+</p>
                    <p class="text-xs uppercase font-bold tracking-widest">Yıllık Deneyim</p>
                </div>
            </div>
            
            <div class="order-1 lg:order-2">
                <span class="text-primary font-label-caps text-label-caps mb-4 block uppercase tracking-widest">MİRASIMIZ</span>
                <h2 class="font-headline-lg text-headline-lg text-on-surface mb-6">Kurumsal Güven ve Küresel Vizyon</h2>
                <p class="text-body-lg text-on-surface-variant mb-8">Gayrimenkul sektöründe yılların birikimiyle, şeffaf iş süreçlerimiz ve etik değerlerimizle her gün daha da ileriye taşıdığımız mirasımızla hizmetinizdeyiz.</p>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">verified</span>
                        <span class="font-semibold text-on-surface">Hukuki Güvence & Tam Şeffaflık</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">analytics</span>
                        <span class="font-semibold text-on-surface">Veriye Dayalı Yatırım Danışmanlığı</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">support_agent</span>
                        <span class="font-semibold text-on-surface">7/24 Profesyonel Destek Ekibi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Blog & Haberler -->
<section class="py-section-gap bg-surface-muted">
    <div class="max-w-7xl mx-auto px-6 md:px-12">
        <div class="flex flex-col md:flex-row justify-between items-end mb-12 gap-4">
            <div class="max-w-2xl">
                <span class="text-primary font-label-caps text-label-caps mb-2 block uppercase tracking-widest">GÜNCEL KALIN</span>
                <h2 class="font-headline-lg text-headline-lg text-on-surface">Blog & Haberler</h2>
                <p class="text-body-md text-on-surface-variant mt-2">Gayrimenkul dünyasındaki en son trendler ve yatırım rehberleri.</p>
            </div>
            <a href="#" class="text-primary font-bold flex items-center gap-2 hover:underline">Tüm Yazılar <span class="material-symbols-outlined text-sm">arrow_forward</span></a>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-grid-gutter">
            <!-- Blog Card 1 -->
            <article class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col h-full">
                <div class="relative aspect-video overflow-hidden">
                    <x-property-placeholder icon="villa" class="group-hover:scale-105 transition-transform duration-500" />
                </div>
                <div class="p-6 flex flex-col flex-grow">
                    <time class="text-xs text-on-surface-variant mb-3 block font-medium">12 Mayıs 2026</time>
                    <h3 class="font-headline-sm text-headline-sm mb-4 group-hover:text-primary transition-colors">2024 Gayrimenkul Trendleri: Lüksün Yeni Tanımı</h3>
                    <p class="text-body-sm text-on-surface-variant line-clamp-3 mb-6">Yeni yılda gayrimenkul piyasasını şekillendirecek teknolojik entegrasyonlar ve değişen yatırımcı alışkanlıkları.</p>
                    <div class="mt-auto">
                        <button class="text-primary text-body-sm font-bold flex items-center gap-2">Devamını Oku <span class="material-symbols-outlined text-xs">chevron_right</span></button>
                    </div>
                </div>
            </article>
            
            <!-- Blog Card 2 -->
            <article class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col h-full">
                <div class="relative aspect-video overflow-hidden">
                    <x-property-placeholder icon="landscape" class="group-hover:scale-105 transition-transform duration-500" />
                </div>
                <div class="p-6 flex flex-col flex-grow">
                    <time class="text-xs text-on-surface-variant mb-3 block font-medium">28 Nisan 2026</time>
                    <h3 class="font-headline-sm text-headline-sm mb-4 group-hover:text-primary transition-colors">Sürdürülebilir Mimari: Yarının Yaşam Alanları</h3>
                    <p class="text-body-sm text-on-surface-variant line-clamp-3 mb-6">Doğa ile bütünleşen tasarımlar ve enerji verimliliği yüksek binaların yatırım potansiyeli.</p>
                    <div class="mt-auto">
                        <button class="text-primary text-body-sm font-bold flex items-center gap-2">Devamını Oku <span class="material-symbols-outlined text-xs">chevron_right</span></button>
                    </div>
                </div>
            </article>
            
            <!-- Blog Card 3 -->
            <article class="bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 group flex flex-col h-full">
                <div class="relative aspect-video overflow-hidden">
                    <x-property-placeholder icon="villa" class="group-hover:scale-105 transition-transform duration-500" />
                </div>
                <div class="p-6 flex flex-col flex-grow">
                    <time class="text-xs text-on-surface-variant mb-3 block font-medium">15 Nisan 2026</time>
                    <h3 class="font-headline-sm text-headline-sm mb-4 group-hover:text-primary transition-colors">Yurt Dışı Gayrimenkul Yatırımında Dikkat Edilmesi Gerekenler</h3>
                    <p class="text-body-sm text-on-surface-variant line-clamp-3 mb-6">Uluslararası pazarlarda güvenli ve yüksek getirili yatırım stratejileri.</p>
                    <div class="mt-auto">
                        <button class="text-primary text-body-sm font-bold flex items-center gap-2">Devamını Oku <span class="material-symbols-outlined text-xs">chevron_right</span></button>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>
@endsection
