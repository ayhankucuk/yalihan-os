@extends('layouts.frontend')

@section('title', 'Yazlık Kiralık Portföyü — ' . config('app.name'))

@push('styles')
<style>
    .custom-radio {
        appearance: none;
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid #e2e8f0;
        border-radius: 50%;
        outline: none;
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
        flex-shrink: 0;
    }
    .custom-radio:checked {
        border-color: var(--primary);
        background-color: transparent;
    }
    .custom-radio:checked::after {
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
    
    .custom-checkbox {
        appearance: none;
        width: 1.25rem;
        height: 1.25rem;
        border: 2px solid #e2e8f0;
        border-radius: 50%;
        outline: none;
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
        flex-shrink: 0;
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
    
    .villa-card-lux {
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.3s;
    }
    .villa-card-lux:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .villa-image-wrapper {
        position: relative;
        overflow: hidden;
        border-radius: 12px;
    }
    .villa-image-wrapper img {
        transition: transform 0.7s;
    }
    .villa-card-lux:hover .villa-image-wrapper img {
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
        <h1 class="text-4xl font-black text-slate-900 mb-3 font-display-hero">Yazlık Kiralık Portföyü</h1>
        <p class="text-lg text-slate-600 max-w-2xl">Ege ve Akdeniz'in en seçkin bölgelerinde, rüya gibi bir tatil için özel portföyümüzü keşfedin.</p>
    </div>

    <!-- Main Content -->
    <div class="max-w-[1400px] mx-auto px-6 flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar Filters -->
        <aside class="w-full lg:w-80 flex-shrink-0">
            <!-- Filter Form -->
            <form action="{{ route('villas.index') }}" method="GET" class="bg-white rounded-2xl p-6 border border-slate-200 mb-6 shadow-sm sticky top-28">
                
                <div class="flex items-center justify-between mb-8">
                    <h3 class="text-lg font-bold text-slate-900">Gelişmiş Filtreler</h3>
                    @if(request()->hasAny(['rental_type', 'location', 'min_price', 'max_price', 'bedrooms', 'amenities']))
                        <a href="{{ route('villas.index') }}" class="text-sm font-semibold text-primary hover:text-blue-700 transition-colors">Sıfırla</a>
                    @endif
                </div>
                
                <!-- Kiralama Tipi -->
                <div class="mb-8">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">KİRALAMA TİPİ</h4>
                    <div class="space-y-3">
                        @php $selectedType = request('rental_type', 'aylik'); @endphp
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="radio" name="rental_type" value="sezonluk" {{ $selectedType == 'sezonluk' ? 'checked' : '' }} class="custom-radio">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">Sezonluk</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="radio" name="rental_type" value="aylik" {{ $selectedType == 'aylik' ? 'checked' : '' }} class="custom-radio">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">Aylık</span>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer group">
                            <input type="radio" name="rental_type" value="gunluk" {{ $selectedType == 'gunluk' ? 'checked' : '' }} class="custom-radio">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">Günlük</span>
                        </label>
                    </div>
                </div>
                
                <!-- Lokasyon -->
                <div class="mb-8">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">LOKASYON (BODRUM)</h4>
                    <div class="relative">
                        <select name="location" class="w-full bg-white border border-slate-200 rounded-lg px-4 py-3 text-sm font-medium text-slate-700 appearance-none outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all cursor-pointer shadow-sm">
                            <option value="">Tüm Bölgeler</option>
                            @foreach($locations ?? [] as $loc)
                                <option value="{{ $loc->ilce_adi ?? $loc->name ?? '' }}" {{ request('location') == ($loc->ilce_adi ?? $loc->name ?? '') ? 'selected' : '' }}>
                                    {{ $loc->ilce_adi ?? $loc->name ?? '' }}
                                </option>
                            @endforeach
                            @if(empty($locations))
                                <option value="Yalıkavak" {{ request('location') == 'Yalıkavak' ? 'selected' : '' }}>Yalıkavak</option>
                                <option value="Türkbükü" {{ request('location') == 'Türkbükü' ? 'selected' : '' }}>Türkbükü</option>
                                <option value="Gündoğan" {{ request('location') == 'Gündoğan' ? 'selected' : '' }}>Gündoğan</option>
                            @endif
                        </select>
                        <span class="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">expand_more</span>
                    </div>
                </div>
                
                <!-- Fiyat Aralığı -->
                <div class="mb-8">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">FİYAT ARALIĞI (€)</h4>
                    <div class="flex items-center gap-3">
                        <input type="number" name="min_price" placeholder="Min €" value="{{ request('min_price') }}" class="w-1/2 bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                        <input type="number" name="max_price" placeholder="Max €" value="{{ request('max_price') }}" class="w-1/2 bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                    </div>
                </div>
                
                <!-- Yatak Odası -->
                <div class="mb-8">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">YATAK ODASI</h4>
                    <div class="flex flex-wrap gap-2">
                        @php $selectedBedrooms = request('bedrooms', ''); @endphp
                        <label class="pill-btn cursor-pointer">
                            <input type="radio" name="bedrooms" value="0" {{ $selectedBedrooms == '0' ? 'checked' : '' }} class="hidden">
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-lg border">Stüdyo</span>
                        </label>
                        <label class="pill-btn cursor-pointer">
                            <input type="radio" name="bedrooms" value="1" {{ $selectedBedrooms == '1' ? 'checked' : '' }} class="hidden">
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-lg border">1+</span>
                        </label>
                        <label class="pill-btn cursor-pointer">
                            <input type="radio" name="bedrooms" value="2" {{ $selectedBedrooms == '2' ? 'checked' : '' }} class="hidden">
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-lg border">2+</span>
                        </label>
                        <label class="pill-btn cursor-pointer">
                            <input type="radio" name="bedrooms" value="3" {{ $selectedBedrooms == '3' ? 'checked' : '' }} class="hidden">
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-lg border">3+</span>
                        </label>
                        <label class="pill-btn cursor-pointer">
                            <input type="radio" name="bedrooms" value="4" {{ $selectedBedrooms == '4' ? 'checked' : '' }} class="hidden">
                            <span class="inline-block px-4 py-2 text-sm font-semibold rounded-lg border">4+</span>
                        </label>
                    </div>
                </div>
                
                <!-- Özellikler -->
                <div class="mb-8">
                    <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">ÖZELLİKLER</h4>
                    <div class="space-y-3">
                        @php $selectedAmenities = request('amenities', []); @endphp
                        @foreach($popularAmenities ?? [] as $amenity)
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" name="amenities[]" value="{{ $amenity['slug'] }}" {{ in_array($amenity['slug'], $selectedAmenities) ? 'checked' : '' }} class="custom-checkbox">
                                <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">{{ $amenity['name'] }}</span>
                            </label>
                        @endforeach
                        @if(empty($popularAmenities))
                            <!-- Fallback -->
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" class="custom-checkbox">
                                <span class="text-sm font-medium text-slate-700">Özel Havuz</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" class="custom-checkbox">
                                <span class="text-sm font-medium text-slate-700">Deniz Manzarası</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" class="custom-checkbox">
                                <span class="text-sm font-medium text-slate-700">Denize Sıfır</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" class="custom-checkbox">
                                <span class="text-sm font-medium text-slate-700">Bahçeli</span>
                            </label>
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <input type="checkbox" class="custom-checkbox">
                                <span class="text-sm font-medium text-slate-700">Wi-Fi</span>
                            </label>
                        @endif
                    </div>
                </div>
                
                <button type="submit" class="w-full bg-primary text-white font-bold py-3.5 rounded-xl hover:bg-blue-700 transition-all shadow-md shadow-primary/20">
                    Filtreleri Uygula
                </button>
            </form>
            
            <!-- Özel Talep Card -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6">
                <h3 class="text-lg font-bold text-primary mb-2">Özel Talep?</h3>
                <p class="text-sm text-slate-600 mb-6 leading-relaxed">Aradığınızı bulamadınız mı? Size özel kiralama seçenekleri için ekibimizle iletişime geçin.</p>
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 text-primary font-bold text-sm hover:text-blue-800 transition-colors">
                    Danışmanla Görüş <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>
            
        </aside>
        
        <!-- Right Area (Listings) -->
        <div class="flex-1">
            
            <!-- Toolbar -->
            <div class="bg-white rounded-2xl p-4 border border-slate-200 mb-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="text-sm text-slate-600 font-medium px-2">
                    <span class="font-bold text-slate-900">{{ $stats['total'] ?? $villas->total() ?? 42 }}</span> adet yazlık kiralık mülk gösteriliyor
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
                        <span class="text-sm font-semibold text-slate-500">Sıralama:</span>
                        <div class="relative">
                            <select name="sort" class="bg-transparent text-sm font-bold text-slate-900 outline-none appearance-none pr-5 cursor-pointer">
                                <option value="popular">Önerilen</option>
                                <option value="price_low">Fiyat: Düşükten Yükseğe</option>
                                <option value="price_high">Fiyat: Yüksekten Düşüğe</option>
                                <option value="newest">En Yeni</option>
                            </select>
                            <span class="material-symbols-outlined absolute right-0 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none text-base">expand_more</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Grid -->
            <div :class="viewMode === 'grid' ? 'grid grid-cols-1 md:grid-cols-2 gap-8' : 'flex flex-col gap-8'">
                @forelse($villas as $index => $villa)
                    @php
                        $foto = $villa->featuredPhoto ?? $villa->fotograflar?->sortBy('id')->first() ?? null;
                        $fotoUrl = $foto ? ($foto->thumbnail_url ?? '/storage/' . $foto->dosya_yolu) : 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
                        $ilAdi   = $villa->il?->il_adi ?? $villa->il?->name ?? '';
                        $ilceAdi = $villa->ilce?->ilce_adi ?? $villa->ilce?->name ?? '';
                        $lokasyon = collect([$ilceAdi, $ilAdi])->filter()->implode(', ');
                        if (empty($lokasyon)) $lokasyon = 'Yalıkavak, Bodrum';
                        
                        $haftalikFiyat  = $villa->haftalik_fiyat ?? null;
                        $aylikFiyat     = $villa->aylik_fiyat ?? null;
                        $sezonlukFiyat  = $villa->sezonluk_fiyat ?? null;
                        $gunlukFiyat    = $villa->gunluk_fiyat ?? $villa->fiyat ?? 12500;
                        
                        // Fallbacks for layout
                        $displayPrice = $aylikFiyat ?? $sezonlukFiyat ?? $gunlukFiyat;
                        $priceLabel = $aylikFiyat ? '/ Ay' : ($sezonlukFiyat ? '/ Sezon' : '/ Gece');
                        $badge1 = $aylikFiyat ? 'AYLIK' : ($sezonlukFiyat ? 'SEZONLUK' : 'GÜNLÜK');
                        $badge2 = ($index % 2 == 0) ? 'VİLLA' : 'YALI';
                    @endphp
                    
                    <a href="{{ route('villas.show', $villa->id) }}" class="villa-card-lux group bg-white rounded-2xl p-4 border border-slate-200" :class="viewMode === 'list' ? 'flex flex-col md:flex-row gap-6' : 'flex flex-col'">
                        
                        <!-- Image Container -->
                        <div class="villa-image-wrapper bg-slate-100" :class="viewMode === 'list' ? 'w-full md:w-2/5 h-64 md:h-auto' : 'w-full h-64 mb-5'">
                            <img src="{{ $fotoUrl }}" alt="{{ $villa->baslik }}" class="w-full h-full object-cover">
                            
                            <!-- Badges Top Left -->
                            <div class="absolute top-4 left-4 flex gap-2">
                                <span class="bg-emerald-500 text-white text-[10px] font-bold px-2.5 py-1 rounded-full tracking-wider">{{ $badge1 }}</span>
                                <span class="bg-white/90 backdrop-blur-md text-slate-800 text-[10px] font-bold px-2.5 py-1 rounded-full tracking-wider shadow-sm">{{ $badge2 }}</span>
                            </div>
                            
                            <!-- Favorite Top Right -->
                            <div class="absolute top-4 right-4">
                                <button type="button" class="w-8 h-8 rounded-full bg-white/30 backdrop-blur-md text-white flex items-center justify-center hover:bg-white hover:text-rose-500 transition-colors">
                                    <span class="material-symbols-outlined text-[20px]">favorite_border</span>
                                </button>
                            </div>
                            
                            <!-- Price Bottom Right -->
                            <div class="absolute bottom-4 right-4">
                                <div class="bg-primary text-white px-4 py-2 rounded-lg font-bold text-lg shadow-lg flex items-baseline gap-1">
                                    €{{ number_format($displayPrice, 0, ',', '.') }} <span class="text-xs font-normal text-white/80">{{ $priceLabel }}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Details -->
                        <div class="flex-1 flex flex-col justify-between" :class="viewMode === 'list' ? 'py-2' : ''">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900 mb-2 line-clamp-1 group-hover:text-primary transition-colors">{{ $villa->baslik }}</h2>
                                <div class="flex items-center gap-1.5 text-sm text-slate-500 mb-6">
                                    <span class="material-symbols-outlined text-[16px]">location_on</span>
                                    <span>{{ $lokasyon }}</span>
                                </div>
                            </div>
                            
                            <!-- Amenities Footer -->
                            <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-4 mt-auto">
                                <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                    <span class="material-symbols-outlined text-slate-400 text-[18px]">bed</span>
                                    <span>{{ $villa->oda_sayisi ?? 4 }} Y.Odası</span>
                                </div>
                                <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                    <span class="material-symbols-outlined text-slate-400 text-[18px]">group</span>
                                    <span>{{ $villa->kapasite ?? 8 }} Kişi</span>
                                </div>
                                <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                    <span class="material-symbols-outlined text-slate-400 text-[18px]">home</span>
                                    <span>Müstakil</span>
                                </div>
                            </div>
                        </div>
                        
                    </a>
                @empty
                    <div class="col-span-full py-20 text-center bg-white rounded-2xl border border-slate-200">
                        <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="material-symbols-outlined text-3xl">search_off</span>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900 mb-2">Yazlık Bulunamadı</h3>
                        <p class="text-slate-500 mb-6">Arama kriterlerinize uygun kiralık villa bulunamadı.</p>
                        <a href="{{ route('villas.index') }}" class="inline-block bg-primary text-white font-semibold px-6 py-2.5 rounded-lg hover:bg-blue-700 transition-colors">
                            Filtreleri Temizle
                        </a>
                    </div>
                @endforelse
            </div>
            
            <!-- Pagination -->
            @if($villas instanceof \Illuminate\Pagination\LengthAwarePaginator && $villas->hasPages())
            <div class="mt-12 flex justify-center gap-2">
                @if ($villas->onFirstPage())
                    <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 bg-white"><span class="material-symbols-outlined text-sm">chevron_left</span></span>
                @else
                    <a href="{{ $villas->previousPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 bg-white hover:border-primary hover:text-primary transition-colors"><span class="material-symbols-outlined text-sm">chevron_left</span></a>
                @endif
                
                @foreach ($villas->getUrlRange(max(1, $villas->currentPage() - 2), min($villas->lastPage(), $villas->currentPage() + 2)) as $page => $url)
                    @if ($page == $villas->currentPage())
                        <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-primary bg-primary text-white font-bold text-sm">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 bg-white hover:border-primary hover:text-primary font-medium text-sm transition-colors">{{ $page }}</a>
                    @endif
                @endforeach
                
                @if ($villas->currentPage() < $villas->lastPage() - 2)
                    <span class="w-10 h-10 flex items-center justify-center text-slate-500">...</span>
                    <a href="{{ $villas->url($villas->lastPage()) }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 bg-white hover:border-primary hover:text-primary font-medium text-sm transition-colors">{{ $villas->lastPage() }}</a>
                @endif
                
                @if ($villas->hasMorePages())
                    <a href="{{ $villas->nextPageUrl() }}" class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-700 bg-white hover:border-primary hover:text-primary transition-colors"><span class="material-symbols-outlined text-sm">chevron_right</span></a>
                @else
                    <span class="w-10 h-10 flex items-center justify-center rounded-lg border border-slate-200 text-slate-400 bg-white"><span class="material-symbols-outlined text-sm">chevron_right</span></span>
                @endif
            </div>
            @endif
            
        </div>
    </div>
</div>
@endsection
