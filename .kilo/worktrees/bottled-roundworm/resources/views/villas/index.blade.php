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
        width: 1.1rem;
        height: 1.1rem;
        border: 2px solid #cbd5e1;
        border-radius: 4px;
        outline: none;
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
        flex-shrink: 0;
        background: #fff;
    }
    .custom-checkbox:checked {
        border-color: var(--primary);
        background-color: var(--primary);
    }
    .custom-checkbox:checked::after {
        content: '';
        position: absolute;
        top: 45%;
        left: 50%;
        transform: translate(-50%, -50%) rotate(45deg);
        width: 0.3rem;
        height: 0.55rem;
        border-right: 2px solid #fff;
        border-bottom: 2px solid #fff;
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
<div class="min-h-screen" x-data="{ viewMode: 'grid' }">

    <!-- ── Page Header — Kurumsal Banner ── -->
    <div style="background:#0F2A5C; padding-top:7rem; padding-bottom:2.5rem;">
        <div class="max-w-[1400px] mx-auto px-6">
            <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:0.75rem;font-size:0.75rem;font-weight:500;color:rgba(255,255,255,0.55);">
                <a href="{{ route('home') }}" style="color:rgba(255,255,255,0.55);text-decoration:none;">Ana Sayfa</a>
                <span>›</span>
                <span style="color:#fff;">Yazlık Kiralık</span>
            </div>
            <h1 style="font-size:clamp(1.75rem,3vw,2.5rem);font-weight:800;color:#fff;margin-bottom:0.5rem;letter-spacing:-0.01em;">
                Yazlık Kiralık Portföyümüz
            </h1>
            <p style="font-size:0.95rem;color:rgba(255,255,255,0.65);max-width:40rem;">
                Ege ve Akdeniz'in en seçkin bölgelerinde özel kiralık villa portföyü.
            </p>
        </div>
    </div>

    <!-- ── İçerik ── -->
    <div class="bg-slate-50 pb-20 pt-10">

    <!-- Main Content -->
    <div class="max-w-[1400px] mx-auto px-6 flex flex-col lg:flex-row gap-8">
        
        <!-- Sidebar Filters -->
        <aside class="w-full lg:w-80 flex-shrink-0">
            <!-- Filter Form -->
            <form id="filter-form" action="{{ route('villas.index') }}" method="GET"
                  class="bg-white rounded-2xl border border-slate-200 mb-6 shadow-sm sticky top-28 flex flex-col"
                  style="max-height: calc(100vh - 8rem);">

                <!-- Header -->
                <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-slate-100 flex-shrink-0">
                    <h3 class="text-lg font-bold text-slate-900">Filtreler</h3>
                    @if(request()->hasAny(['rental_type', 'location', 'min_price', 'max_price', 'bedrooms', 'amenities', 'guests', 'sort']))
                        <a href="{{ route('villas.index') }}" class="text-sm font-semibold text-primary hover:text-blue-700 transition-colors">Sıfırla</a>
                    @endif
                </div>

                <!-- Scrollable content -->
                <div class="overflow-y-auto flex-1 divide-y divide-slate-100">

                    <!-- Kiralama Tipi -->
                    <div class="px-6 py-5">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">KİRALAMA TİPİ</h4>
                        <div class="space-y-2.5">
                            @php $selectedType = request('rental_type', ''); @endphp
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

                    <!-- Lokasyon (çoklu seçim checkbox) -->
                    <div class="px-6 py-5">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">LOKASYON (BODRUM)</h4>
                        @php $selectedLocations = (array) request('location', []); @endphp
                        <div class="space-y-2">
                            @forelse($locations ?? [] as $loc)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="location[]" value="{{ $loc->mahalle_adi }}"
                                           {{ in_array($loc->mahalle_adi, $selectedLocations) ? 'checked' : '' }}
                                           class="custom-checkbox">
                                    <span class="flex-1 text-sm font-medium text-slate-700 group-hover:text-slate-900">{{ $loc->mahalle_adi }}</span>
                                    @if(($loc->ilan_sayisi ?? 0) > 0)
                                        <span class="text-xs font-semibold text-slate-400 bg-slate-100 rounded-full px-2 py-0.5">{{ $loc->ilan_sayisi }}</span>
                                    @endif
                                </label>
                            @empty
                                {{-- Statik fallback --}}
                                @foreach(['Yalıkavak' => 'yalikavak', 'Türkbükü' => 'turkbuku', 'Gündoğan' => 'gundogan', 'Göltürkbükü' => 'golturkbuku', 'Bitez' => 'bitez', 'Ortakent' => 'ortakent'] as $bolge => $slug)
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" name="location[]" value="{{ $bolge }}"
                                               {{ in_array($bolge, $selectedLocations) ? 'checked' : '' }}
                                               class="custom-checkbox">
                                        <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">{{ $bolge }}</span>
                                    </label>
                                @endforeach
                            @endforelse
                        </div>
                    </div>

                    <!-- Fiyat Aralığı -->
                    <div class="px-6 py-5">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">FİYAT ARALIĞI (€)</h4>
                        <div class="flex items-center gap-3">
                            <input type="number" name="min_price" placeholder="Min €" value="{{ request('min_price') }}"
                                   class="w-1/2 bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                            <input type="number" name="max_price" placeholder="Max €" value="{{ request('max_price') }}"
                                   class="w-1/2 bg-white border border-slate-200 rounded-lg px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-primary focus:ring-1 focus:ring-primary transition-all shadow-sm">
                        </div>
                    </div>

                    <!-- Misafir Sayısı -->
                    <div class="px-6 py-5">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">MİSAFİR SAYISI</h4>
                        <div class="flex flex-wrap gap-2">
                            @php $selectedGuests = request('guests', ''); @endphp
                            @foreach([2 => '2 Kişi', 4 => '4 Kişi', 6 => '6 Kişi', 8 => '8+ Kişi'] as $val => $label)
                                <label class="pill-btn cursor-pointer">
                                    <input type="radio" name="guests" value="{{ $val }}" {{ $selectedGuests == $val ? 'checked' : '' }} class="hidden">
                                    <span class="inline-block px-3 py-2 text-sm font-semibold rounded-lg border">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Yatak Odası -->
                    <div class="px-6 py-5">
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
                    <div class="px-6 py-5">
                        <h4 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-4">ÖZELLİKLER</h4>
                        <div class="space-y-2.5">
                            @php $selectedAmenities = (array) request('amenities', []); @endphp
                            @foreach($popularAmenities ?? [] as $amenity)
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <input type="checkbox" name="amenities[]" value="{{ $amenity['slug'] }}"
                                           {{ in_array($amenity['slug'], $selectedAmenities) ? 'checked' : '' }}
                                           class="custom-checkbox">
                                    <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900">{{ $amenity['name'] }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <!-- Sort sync -->
                    <input type="hidden" name="sort" id="sort-hidden" value="{{ request('sort', 'popular') }}">

                </div>

                <!-- Submit — sticky at bottom -->
                <div class="px-6 pb-5 pt-4 border-t border-slate-100 flex-shrink-0">
                    <button type="submit" style="width:100%;background:linear-gradient(135deg,#0A1628,#0d2044);color:#fff;font-family:'Inter',sans-serif;font-weight:700;font-size:0.9rem;padding:0.875rem;border-radius:0.75rem;border:1px solid rgba(201,168,76,0.3);cursor:pointer;transition:all 0.2s;display:flex;align-items:center;justify-content:center;gap:0.5rem;"
                            onmouseover="this.style.background='linear-gradient(135deg,#0d2044,#004ac6)'"
                            onmouseout="this.style.background='linear-gradient(135deg,#0A1628,#0d2044)'">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        Filtreleri Uygula
                    </button>
                </div>

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
                            <select id="sort-toolbar" class="bg-transparent text-sm font-bold text-slate-900 outline-none appearance-none pr-5 cursor-pointer"
                                    onchange="document.getElementById('sort-hidden').value=this.value; document.getElementById('filter-form').submit();">
                                <option value="popular" {{ request('sort','popular') === 'popular' ? 'selected' : '' }}>Önerilen</option>
                                <option value="price_low" {{ request('sort') === 'price_low' ? 'selected' : '' }}>Fiyat: Düşükten Yükseğe</option>
                                <option value="price_high" {{ request('sort') === 'price_high' ? 'selected' : '' }}>Fiyat: Yüksekten Düşüğe</option>
                                <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>En Yeni</option>
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
                        $fotoUrl = $foto ? ($foto->thumbnail_url ?? '/storage/' . $foto->dosya_yolu) : null;
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
                            @if($fotoUrl)
                                <img src="{{ $fotoUrl }}" alt="{{ $villa->baslik }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center" style="background:linear-gradient(135deg,#0A1628 0%,#0d2044 50%,#0a2a5e 100%);">
                                    <svg width="56" height="56" fill="none" viewBox="0 0 24 24" stroke="rgba(201,168,76,0.4)" stroke-width="0.75" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline stroke-linecap="round" stroke-linejoin="round" points="9 22 9 12 15 12 15 22"/></svg>
                                </div>
                            @endif
                            
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
                                    <span>{{ $villa->yatak_odasi_sayisi ?? $villa->oda_sayisi ?? '—' }} Y.Odası</span>
                                </div>
                                <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                    <span class="material-symbols-outlined text-slate-400 text-[18px]">group</span>
                                    <span>{{ $villa->maksimum_misafir ?? $villa->max_guests ?? '—' }} Kişi</span>
                                </div>
                                <div class="flex items-center gap-2 text-[13px] font-medium text-slate-600">
                                    <span class="material-symbols-outlined text-slate-400 text-[18px]">home</span>
                                    <span>Müstakil</span>
                                </div>
                            </div>
                        </div>
                        
                    </a>
                @empty
                    <div class="col-span-full">
                        <div style="background:#fff;border-radius:1.25rem;border:1px solid #e2e8f0;padding:4rem 2rem;text-align:center;">
                            {{-- İkon --}}
                            <div style="width:72px;height:72px;border-radius:50%;margin:0 auto 1.5rem;background:linear-gradient(135deg,#0A1628,#0d2044);display:flex;align-items:center;justify-content:center;">
                                <svg width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="rgba(201,168,76,0.8)" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline stroke-linecap="round" stroke-linejoin="round" points="9 22 9 12 15 12 15 22"/></svg>
                            </div>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.35rem;font-weight:800;color:#191b23;margin-bottom:0.5rem;">Aradığınız Villa Bulunamadı</h3>
                            <p style="font-family:'Inter',sans-serif;font-size:0.9rem;color:#64748b;max-width:360px;margin:0 auto 2rem;line-height:1.6;">
                                Seçtiğiniz kriterlere uygun kiralık villa şu an portföyümüzde yok. Filtreleri sıfırlayın veya ekibimizle iletişime geçin — size özel seçenekler sunarız.
                            </p>
                            <div style="display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
                                <a href="{{ route('villas.index') }}"
                                   style="display:inline-flex;align-items:center;gap:0.4rem;background:#0A1628;color:#fff;font-family:'Inter',sans-serif;font-weight:700;font-size:0.85rem;padding:0.7rem 1.5rem;border-radius:0.625rem;text-decoration:none;transition:background 0.2s;"
                                   onmouseover="this.style.background='#004ac6'" onmouseout="this.style.background='#0A1628'">
                                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                    Filtreleri Sıfırla
                                </a>
                                <a href="{{ route('contact') }}"
                                   style="display:inline-flex;align-items:center;gap:0.4rem;background:transparent;color:#0A1628;font-family:'Inter',sans-serif;font-weight:700;font-size:0.85rem;padding:0.7rem 1.5rem;border-radius:0.625rem;text-decoration:none;border:2px solid #0A1628;transition:all 0.2s;"
                                   onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                    <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                    Danışmana Sor
                                </a>
                            </div>
                        </div>
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
    </div>{{-- bg-slate-50 --}}
</div>{{-- outer --}}
@endsection
