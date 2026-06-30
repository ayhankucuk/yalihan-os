@extends('layouts.frontend')

@section('title', 'Yurt Dışı Gayrimenkul Yatırımları - Yalıhan Emlak')

@push('styles')
<style>
    .glass-effect-white {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }
    .hero-gradient-overlay {
        background: linear-gradient(to right, rgba(10, 22, 40, 0.8) 0%, rgba(10, 22, 40, 0.4) 50%, rgba(10, 22, 40, 0.1) 100%);
    }
    .custom-checkbox {
        appearance: none;
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid #e2e8f0;
        border-radius: 50%;
        outline: none;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
    }
    .custom-checkbox:checked {
        border-color: var(--primary);
        background-color: transparent;
    }
    .custom-checkbox:checked::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 0.6rem;
        height: 0.6rem;
        background-color: var(--primary);
        border-radius: 50%;
    }
    .currency-btn {
        transition: all 0.2s;
    }
    .currency-btn.active {
        background-color: #f1f5f9;
        border-color: var(--primary);
        color: var(--primary);
        font-weight: 700;
    }
</style>
@endpush

@section('content')
<div class="bg-surface-container-lowest min-h-screen pt-20" x-data="{
    currency: '{{ $currency ?? 'EUR' }}'
}">
    
    <!-- Hero Section -->
    <div class="relative h-[400px] md:h-[500px] flex items-center overflow-hidden">
        <div class="absolute inset-0 z-0">
            <img alt="International Real Estate Skyscrapers" class="w-full h-full object-cover" src="https://images.unsplash.com/photo-1512453979798-5ea266f8880c?ixlib=rb-4.0.3&auto=format&fit=crop&w=2070&q=80">
            <div class="absolute inset-0 hero-gradient-overlay"></div>
        </div>
        
        <div class="relative z-10 max-w-7xl mx-auto px-6 md:px-12 w-full">
            <div class="max-w-2xl">
                <span class="inline-block bg-primary text-white text-[10px] font-bold px-3 py-1 uppercase tracking-widest rounded mb-4">
                    KÜRESEL FIRSATLAR
                </span>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-black text-white leading-tight mb-4 font-display-hero">
                    Yurt Dışı Gayrimenkul Yatırımları
                </h1>
                <p class="text-white/90 text-body-lg mb-8 max-w-xl">
                    Yalıhan güvencesiyle dünya başkentlerinde kazançlı yatırım fırsatları, oturum izinleri ve yüksek kira getirili portföyler sizi bekliyor.
                </p>
                
                <div class="flex flex-wrap gap-4">
                    <a href="#portfolio" class="bg-primary text-white font-bold py-3.5 px-8 rounded-lg flex items-center gap-2 hover:bg-blue-700 transition-all shadow-lg shadow-primary/30">
                        <span class="material-symbols-outlined text-xl">check_circle</span> Portföyü İncele
                    </a>
                    <a href="#" class="glass-effect-white text-on-surface font-bold py-3.5 px-8 rounded-lg flex items-center gap-2 hover:bg-white transition-all shadow-lg">
                        <span class="material-symbols-outlined text-xl">picture_as_pdf</span> Yatırım Rehberini İndir
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="portfolio" class="max-w-7xl mx-auto px-6 md:px-12 py-12">
        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Sidebar / Filters -->
            <aside class="w-full lg:w-72 flex-shrink-0">
                <form action="{{ route('ilanlar.international') }}" method="GET" class="bg-surface-container-low rounded-2xl p-6 border border-outline-variant/30 sticky top-28">
                    <div class="flex items-center gap-2 mb-8 text-on-surface">
                        <span class="material-symbols-outlined">filter_list</span>
                        <h3 class="font-headline-sm text-headline-sm">Filtreler</h3>
                    </div>
                    
                    <!-- Ülke Seçimi -->
                    <div class="mb-8">
                        <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-4">ÜLKE SEÇİMİ</h4>
                        <div class="space-y-3">
                            @foreach($filters['countries'] ?? [] as $country)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="country[]" value="{{ $country['id'] }}" 
                                        {{ in_array($country['id'], (array)($selectedFilters['country'] ?? [])) ? 'checked' : '' }}
                                        class="custom-checkbox">
                                    <span class="text-body-sm text-on-surface group-hover:text-primary transition-colors">{{ $country['name'] }}</span>
                                </label>
                            @endforeach
                            <!-- Default list if no data -->
                            @if(empty($filters['countries']))
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" class="custom-checkbox">
                                    <span class="text-body-sm text-on-surface group-hover:text-primary transition-colors">Birleşik Krallık</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" checked class="custom-checkbox">
                                    <span class="text-body-sm text-on-surface group-hover:text-primary transition-colors">Yunanistan</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" class="custom-checkbox">
                                    <span class="text-body-sm text-on-surface group-hover:text-primary transition-colors">Portekiz</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" class="custom-checkbox">
                                    <span class="text-body-sm text-on-surface group-hover:text-primary transition-colors">Birleşik Arap Emirlikleri</span>
                                </label>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Yatırım Tipi -->
                    <div class="mb-8">
                        <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-4">YATIRIM TİPİ</h4>
                        <div class="relative">
                            <select name="property_type" class="w-full bg-white border border-outline-variant/30 rounded-lg px-4 py-2.5 text-body-sm text-on-surface appearance-none outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all">
                                <option value="">Tüm Tipler</option>
                                @foreach($filters['types'] ?? [] as $type)
                                    <option value="{{ $type['value'] }}" {{ ($selectedFilters['property_type'] ?? '') == $type['value'] ? 'selected' : '' }}>{{ $type['label'] }}</option>
                                @endforeach
                            </select>
                            <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-outline pointer-events-none text-lg">expand_more</span>
                        </div>
                    </div>
                    
                    <!-- Fiyat Aralığı -->
                    <div class="mb-6">
                        <h4 class="text-xs font-bold text-on-surface-variant uppercase tracking-wider mb-4">FİYAT ARALIĞI</h4>
                        <div class="flex items-center gap-2 mb-4">
                            <input type="number" name="min_price" placeholder="Min" value="{{ $selectedFilters['min_price'] ?? '' }}" class="w-1/2 bg-white border border-outline-variant/30 rounded-lg px-3 py-2.5 text-body-sm text-on-surface outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-center">
                            <input type="number" name="max_price" placeholder="Max" value="{{ $selectedFilters['max_price'] ?? '' }}" class="w-1/2 bg-white border border-outline-variant/30 rounded-lg px-3 py-2.5 text-body-sm text-on-surface outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all text-center">
                        </div>
                        
                        <!-- Döviz Seçimi -->
                        <div class="flex border border-outline-variant/30 rounded-lg overflow-hidden bg-white">
                            <button type="button" @click="currency = 'EUR'" :class="currency === 'EUR' ? 'active' : ''" class="currency-btn flex-1 py-2 text-[11px] font-semibold text-outline hover:bg-slate-50 border-r border-outline-variant/30">EUR</button>
                            <button type="button" @click="currency = 'USD'" :class="currency === 'USD' ? 'active' : ''" class="currency-btn flex-1 py-2 text-[11px] font-semibold text-outline hover:bg-slate-50 border-r border-outline-variant/30">USD</button>
                            <button type="button" @click="currency = 'GBP'" :class="currency === 'GBP' ? 'active' : ''" class="currency-btn flex-1 py-2 text-[11px] font-semibold text-outline hover:bg-slate-50">GBP</button>
                        </div>
                        <input type="hidden" name="currency" x-bind:value="currency">
                    </div>
                    
                    <button type="submit" class="w-full bg-primary text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition-all shadow-md active:scale-[0.98]">
                        Sonuçları Listele
                    </button>
                </form>
            </aside>
            
            <!-- Main Listings Area -->
            <div class="flex-1">
                
                <!-- Top Filter Tabs -->
                <div class="flex flex-wrap gap-3 mb-8">
                    <a href="{{ request()->fullUrlWithQuery(['type' => 'golden-visa']) }}" class="flex items-center gap-2 px-5 py-2 rounded-full font-bold text-sm transition-all shadow-sm {{ ($selectedFilters['type'] ?? 'golden-visa') === 'golden-visa' ? 'bg-primary text-white shadow-primary/20' : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px]">verified</span> Golden Visa
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['type' => 'high-yield']) }}" class="flex items-center gap-2 px-5 py-2 rounded-full font-bold text-sm transition-all shadow-sm {{ ($selectedFilters['type'] ?? '') === 'high-yield' ? 'bg-primary text-white shadow-primary/20' : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px]">trending_up</span> High Yield Investment
                    </a>
                    <a href="{{ request()->fullUrlWithQuery(['type' => 'lifestyle']) }}" class="flex items-center gap-2 px-5 py-2 rounded-full font-bold text-sm transition-all shadow-sm {{ ($selectedFilters['type'] ?? '') === 'lifestyle' ? 'bg-primary text-white shadow-primary/20' : 'bg-surface-container-low text-on-surface-variant hover:bg-surface-container-high' }}">
                        <span class="material-symbols-outlined text-[18px]">home_work</span> Lifestyle / Second Home
                    </a>
                </div>
                
                <!-- Title & Sorting -->
                <div class="flex flex-col sm:flex-row sm:items-end justify-between border-b border-outline-variant/30 pb-4 mb-6 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-on-surface mb-1">Global Portföy</h2>
                        <p class="text-body-sm text-on-surface-variant">{{ $stats['total'] ?? 0 }} ilan bulundu</p>
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-on-surface-variant uppercase tracking-wider">SIRALAMA:</span>
                        <div class="relative">
                            <select class="bg-transparent text-primary font-bold text-sm outline-none appearance-none pr-6 cursor-pointer">
                                <option>En Yeni İlanlar</option>
                                <option>Fiyat: Düşükten Yükseğe</option>
                                <option>Fiyat: Yüksekten Düşüğe</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-0 top-1/2 -translate-y-1/2 text-primary pointer-events-none text-base">expand_more</span>
                        </div>
                    </div>
                </div>
                
                <!-- Property Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($featured as $index => $ilan)
                        @php 
                            $foto = $ilan->fotograflar?->sortBy('display_order')->first();
                            
                            // Mocking badge data based on index for visual parity with design,
                            // since the database may not have these exact classifications yet.
                            $badgeType = ['golden_visa', 'high_yield', 'stable_asset'][$index % 3];
                        @endphp
                        <article class="bg-white rounded-2xl overflow-hidden border border-outline-variant/30 hover:shadow-xl transition-all duration-300 group flex flex-col">
                            <div class="relative h-56 overflow-hidden bg-slate-100">
                                @if($foto)
                                    <img alt="{{ $ilan->baslik }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" src="{{ Storage::url($foto->dosya_yolu) }}">
                                @else
                                    <img alt="Property" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" src="https://images.unsplash.com/photo-1512917774080-9991f1c4c750?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80">
                                @endif
                                
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                                
                                <div class="absolute top-4 left-4 flex gap-2">
                                    @if($badgeType === 'golden_visa')
                                        <span class="bg-primary text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">GOLDEN VISA</span>
                                        <span class="bg-status-sale text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">FOR SALE</span>
                                    @elseif($badgeType === 'high_yield')
                                        <span class="bg-slate-800 text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">HIGH YIELD</span>
                                    @else
                                        <span class="bg-slate-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">STABLE ASSET</span>
                                        <span class="bg-status-sale text-white text-[10px] font-bold px-2.5 py-1 rounded-full uppercase">FOR SALE</span>
                                    @endif
                                </div>
                                
                                <div class="absolute top-4 right-4">
                                    <button class="w-8 h-8 rounded-full bg-white text-primary flex items-center justify-center shadow-md hover:bg-slate-50">
                                        <span class="material-symbols-outlined text-[18px]">favorite_border</span>
                                    </button>
                                </div>
                                
                                <div class="absolute bottom-3 left-4">
                                    <span class="bg-white/90 backdrop-blur-sm text-on-surface text-xs font-semibold px-2.5 py-1 rounded flex items-center gap-1 shadow-sm">
                                        <span class="material-symbols-outlined text-[14px] text-outline">location_on</span>
                                        {{ $ilan->ilce?->ilce_adi ?? 'Merkez' }}, {{ $ilan->il?->il_adi ?? ($ilan->ulke_adi ?? 'Avrupa') }}
                                    </span>
                                </div>
                            </div>
                            
                            <div class="p-5 flex-1 flex flex-col">
                                <div class="flex justify-between items-start mb-4 gap-4">
                                    <h3 class="font-headline-sm text-lg font-bold text-on-surface line-clamp-2">{{ $ilan->baslik }}</h3>
                                    <div class="text-right flex-shrink-0">
                                        <div class="text-xl font-black text-primary">{{ $ilan->converted_price ?? number_format($ilan->fiyat, 0, ',', '.') . ' ' . $ilan->para_birimi }}</div>
                                        <div class="text-[10px] text-on-surface-variant font-semibold">
                                            @if($badgeType === 'golden_visa') Starting Price @elseif($badgeType === 'high_yield') Total Price @else Asset Value @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-2 gap-y-3 gap-x-2 text-xs text-on-surface-variant mb-6 flex-1">
                                    @if($badgeType === 'golden_visa')
                                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-outline text-[16px]">account_balance</span> Golden Visa Eligible</div>
                                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-outline text-[16px]">trending_up</span> 5.2% Annual Yield</div>
                                    @elseif($badgeType === 'high_yield')
                                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-outline text-[16px]">percent</span> 8% Rental Yield</div>
                                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-outline text-[16px]">verified_user</span> 0% Income Tax</div>
                                    @else
                                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-outline text-[16px]">security</span> Capital Preservation</div>
                                        <div class="flex items-center gap-2"><span class="material-symbols-outlined text-outline text-[16px]">bed</span> Short-term Let Opt.</div>
                                    @endif
                                </div>
                                
                                <a href="{{ route('ilanlar.show', $ilan->id) }}" class="w-full py-2.5 rounded-lg border-2 border-primary/20 text-primary font-bold text-center text-sm hover:border-primary hover:bg-primary/5 transition-all">
                                    Detaylı Bilgi
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full py-12 text-center text-on-surface-variant">
                            Belirtilen kriterlere uygun ilan bulunamadı. Lütfen filtreleri değiştirerek tekrar deneyin.
                        </div>
                    @endforelse
                </div>
                
                <!-- Pagination -->
                @if($featured instanceof \Illuminate\Pagination\LengthAwarePaginator && $featured->hasPages())
                <div class="mt-12 flex justify-center gap-2">
                    @if ($featured->onFirstPage())
                        <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant/30 text-outline opacity-50"><span class="material-symbols-outlined text-sm">chevron_left</span></span>
                    @else
                        <a href="{{ $featured->previousPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant/30 text-on-surface hover:border-primary hover:text-primary transition-all"><span class="material-symbols-outlined text-sm">chevron_left</span></a>
                    @endif
                    
                    @foreach ($featured->getUrlRange(max(1, $featured->currentPage() - 1), min($featured->lastPage(), $featured->currentPage() + 1)) as $page => $url)
                        <a href="{{ $url }}" class="w-10 h-10 flex items-center justify-center rounded-lg border {{ $page == $featured->currentPage() ? 'bg-primary text-white border-primary' : 'border-outline-variant/30 text-on-surface hover:border-primary hover:text-primary' }} font-bold text-sm transition-all">{{ $page }}</a>
                    @endforeach
                    
                    @if ($featured->hasMorePages())
                        <a href="{{ $featured->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant/30 text-on-surface hover:border-primary hover:text-primary transition-all"><span class="material-symbols-outlined text-sm">chevron_right</span></a>
                    @else
                        <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-outline-variant/30 text-outline opacity-50"><span class="material-symbols-outlined text-sm">chevron_right</span></span>
                    @endif
                </div>
                @endif
                
            </div>
        </div>
    </div>
    
    <!-- Stats Footer -->
    <div class="bg-surface-container-high py-12 mt-12 border-t border-outline-variant/20">
        <div class="max-w-7xl mx-auto px-6 md:px-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl lg:text-4xl font-black text-primary mb-2">15+</div>
                    <div class="text-sm font-semibold text-on-surface-variant">Ülke Ağı</div>
                </div>
                <div>
                    <div class="text-3xl lg:text-4xl font-black text-primary mb-2">500+</div>
                    <div class="text-sm font-semibold text-on-surface-variant">Başarılı İşlem</div>
                </div>
                <div>
                    <div class="text-3xl lg:text-4xl font-black text-primary mb-2">24/7</div>
                    <div class="text-sm font-semibold text-on-surface-variant">Hukuki Destek</div>
                </div>
                <div>
                    <div class="text-3xl lg:text-4xl font-black text-primary mb-2">%100</div>
                    <div class="text-sm font-semibold text-on-surface-variant">Gizlilik Politikası</div>
                </div>
            </div>
        </div>
    </div>
    
</div>
@endsection
