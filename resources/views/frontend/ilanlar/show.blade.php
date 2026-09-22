@extends('layouts.frontend')

@push('styles')
    <style>
        /* Property Detail Design System */
        .propertius-detail {
            --pd-navy: #0A1628;
            --pd-navy-mid: #0F1E38;
            --pd-gold: #C9A84C;
            --pd-gold-light: #D4B96A;
            --pd-gold-dim: rgba(201, 168, 76, 0.15);
            --pd-cream: #F8F6F1;
            --pd-cream-border: #E8E2D8;
            --pd-text: #191b23;
            --pd-text-muted: #737686;
        }

        /* Gallery States */
        .gallery-empty { aspect-ratio: 4/3; background: var(--pd-cream); }
        .gallery-single { aspect-ratio: 16/9; }
        .gallery-grid-2 { display: grid; grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; gap: 4px; aspect-ratio: 16/9; }
        .gallery-grid-3 { display: grid; grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; gap: 4px; aspect-ratio: 16/9; }
        .gallery-grid-many { display: grid; grid-template-columns: 2fr 1fr; grid-template-rows: 1fr 1fr; gap: 4px; aspect-ratio: 16/9; }

        /* Lightbox */
        #pd-lightbox {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(10, 22, 40, 0.96);
            align-items: center;
            justify-content: center;
        }
        #pd-lightbox.active { display: flex; }

        /* Sticky advisor panel */
        .advisor-sticky {
            position: sticky;
            top: 6rem;
        }

        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
        .print-only { display: none; }
    </style>
@endpush

@section('content')
<div class="propertius-detail min-h-screen bg-white">

    {{-- ============================================================
         GALLERY SECTION
         Primary visual asset — must be prominent on all devices
    ============================================================ --}}
    @php
        $fotograflar = $ilan->fotograflar ?? collect();
        $fotografSayisi = $fotograflar->count();
        $ilkFoto = $fotograflar->first();
        $mainImage = $ilkFoto ? \Illuminate\Support\Facades\Storage::url($ilkFoto->dosya_yolu) : null;
        $thumbnailFotos = $fotograflar->slice(1, 4);
        $allImages = $fotograflar->map(fn($f) => \Illuminate\Support\Facades\Storage::url($f->dosya_yolu));
    @endphp

    {{-- Breadcrumb --}}
    <div class="bg-[#F8F6F1] border-b border-[#E8E2D8] no-print">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6 py-3">
            <nav aria-label="Breadcrumb">
                <ol class="flex items-center gap-1.5 text-xs">
                    <li><a href="{{ route('home') }}" class="text-[#737686] hover:text-[#0A1628] transition-colors">Ana Sayfa</a></li>
                    <li><span class="text-[#C9A84C]" aria-hidden="true">›</span></li>
                    <li><a href="{{ route('ilanlar.index') }}" class="text-[#737686] hover:text-[#0A1628] transition-colors">İlanlar</a></li>
                    <li><span class="text-[#C9A84C]" aria-hidden="true">›</span></li>
                    <li>
                        @php
                            $ilceRelation = $ilan->relationLoaded('ilce') ? $ilan->getRelation('ilce') : null;
                            $ilceAdiCrumb = is_object($ilceRelation) ? ($ilceRelation->ilce_adi ?? null) : null;
                        @endphp
                        <span class="text-[#0A1628] font-medium">{{ $ilceAdiCrumb ?? 'Bodrum' }}</span>
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    {{-- Gallery --}}
    <section class="relative bg-[#0A1628] no-print" aria-label="Fotoğraf galerisi">
        @if($fotografSayisi === 0)
            {{-- Empty state --}}
            <div class="gallery-empty flex items-center justify-center">
                <div class="text-center text-white/50">
                    <x-icon name="villa" class="w-16 h-16 mx-auto mb-3 opacity-40" />
                    <p class="text-sm">Fotoğraf bulunmamaktadır</p>
                </div>
            </div>
        @elseif($fotografSayisi === 1)
            {{-- Single photo --}}
            <div class="gallery-single relative overflow-hidden cursor-zoom-in" onclick="openLightbox(0)">
                <img src="{{ $mainImage }}" alt="{{ $ilan->baslik }}" class="w-full h-full object-cover" loading="eager">
            </div>
        @elseif($fotografSayisi === 2)
            {{-- Two photos: side by side --}}
            <div class="gallery-grid-2">
                @foreach($fotograflar->take(2) as $index => $foto)
                    <div class="relative overflow-hidden cursor-zoom-in {{ $index === 0 ? 'row-span-2' : '' }}"
                         onclick="openLightbox({{ $index }})"
                         @if($index > 0) style="grid-row: span 2" @endif>
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($foto->dosya_yolu) }}"
                             alt="{{ $ilan->baslik }} - Fotoğraf {{ $index + 1 }}"
                             class="w-full h-full object-cover">
                    </div>
                @endforeach
            </div>
        @elseif($fotografSayisi <= 4)
            {{-- 3-4 photos: main left, stack right --}}
            <div class="gallery-grid-3">
                <div class="relative overflow-hidden cursor-zoom-in row-span-2" onclick="openLightbox(0)">
                    <img src="{{ $mainImage }}" alt="{{ $ilan->baslik }}" class="w-full h-full object-cover" loading="eager">
                </div>
                @foreach($thumbnailFotos->take(2) as $index => $foto)
                    <div class="relative overflow-hidden cursor-zoom-in" onclick="openLightbox({{ $index + 1 }})">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($foto->dosya_yolu) }}"
                             alt="{{ $ilan->baslik }} - Fotoğraf {{ $index + 2 }}"
                             class="w-full h-full object-cover">
                    </div>
                @endforeach
                @if($fotografSayisi === 4)
                    @php $foto4 = $fotograflar->skip(3)->first(); @endphp
                    <div class="relative overflow-hidden" onclick="openLightbox(3)">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($foto4->dosya_yolu) }}"
                             alt="{{ $ilan->baslik }} - Fotoğraf 4"
                             class="w-full h-full object-cover">
                    </div>
                @else
                    <div class="relative flex items-center justify-center bg-[#0F1E38] text-white/70" onclick="openLightbox(2)">
                        <span class="text-sm font-medium">+{{ $fotografSayisi - 3 }} fotoğraf</span>
                    </div>
                @endif
            </div>
        @else
            {{-- Many photos: main + 3 thumbnails + overlay --}}
            <div class="gallery-grid-many">
                <div class="relative overflow-hidden cursor-zoom-in row-span-2" onclick="openLightbox(0)">
                    <img src="{{ $mainImage }}" alt="{{ $ilan->baslik }}" class="w-full h-full object-cover" loading="eager">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent"></div>
                </div>
                @foreach($thumbnailFotos->take(3) as $index => $foto)
                    <div class="relative overflow-hidden cursor-zoom-in" onclick="openLightbox({{ $index + 1 }})">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($foto->dosya_yolu) }}"
                             alt="{{ $ilan->baslik }} - Fotoğraf {{ $index + 2 }}"
                             class="w-full h-full object-cover">
                        @if($index === 2)
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                                <span class="text-white font-semibold">+{{ $fotografSayisi - 4 }}</span>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Gallery overlay actions --}}
        @if($fotografSayisi > 1)
            <div class="absolute top-4 right-4 flex gap-2">
                <button onclick="openLightbox(0)"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-white/90 hover:bg-white text-[#0A1628] text-xs font-semibold rounded-lg shadow-sm transition-colors backdrop-blur-sm">
                    <x-icon name="goster" class="w-4 h-4" />
                    <span>Tüm Fotoğraflar ({{ $fotografSayisi }})</span>
                </button>
            </div>
        @endif
    </section>

    {{-- ============================================================
         MAIN CONTENT: DESKTOP 2-COLUMN LAYOUT
    ============================================================ --}}
    <div class="max-w-[1440px] mx-auto px-4 sm:px-6 py-8 lg:py-10">

        {{-- Property Header: Title + Key Facts (Above Fold) --}}
        @php
            $ilRelation = $ilan->relationLoaded('il') ? $ilan->getRelation('il') : null;
            $ilceRelation = $ilan->relationLoaded('ilce') ? $ilan->getRelation('ilce') : null;
            $mahRelation = $ilan->relationLoaded('mahalle') ? $ilan->getRelation('mahalle') : null;
            $ilAdi = is_object($ilRelation) ? ($ilRelation->il_adi ?? null) : null;
            $ilceAdi = is_object($ilceRelation) ? ($ilceRelation->ilce_adi ?? null) : null;
            $mahalleAdi = is_object($mahRelation) ? ($mahRelation->mahalle_adi ?? null) : null;

            $konumParcalari = array_filter([$ilAdi, $ilceAdi, $mahalleAdi], fn($v) => $v && strtolower($v) !== 'belirtilmemiş');
            $konumStr = implode(' · ', $konumParcalari);

            // Fiyat
            $fiyat = $ilan->fiyat ?? 0;
            $paraBirimi = strtoupper($ilan->para_birimi ?? 'TRY');
            $eurKur = (float) config('exchange.eur_try', 38.5);
            $usdKur = (float) config('exchange.usd_try', 36.8);
            $paraSembol = match($paraBirimi) { 'EUR' => '€', 'USD' => '$', 'GBP' => '£', default => '₺' };
            $fiyatEUR = $paraBirimi === 'TRY' && $fiyat > 0 ? round($fiyat / $eurKur) : null;
            $fiyatUSD = $paraBirimi === 'TRY' && $fiyat > 0 ? round($fiyat / $usdKur) : null;

            // Yayin tipi
            $yayinTipiRel = $ilan->relationLoaded('yayinTipi') ? $ilan->getRelation('yayinTipi') : null;
            $yayinTipiStr = is_object($yayinTipiRel) ? ($yayinTipiRel->yayin_tipi ?? '') : '';
            $isKiralik = strtolower($yayinTipiStr) === 'kiralık' || strtolower($yayinTipiStr) === 'kiralik';
        @endphp

        <div class="lg:flex lg:gap-10 xl:gap-12">

            {{-- LEFT: Main Content Column --}}
            <div class="flex-1 min-w-0">

                {{-- Property Title & Location --}}
                <header class="mb-6">
                    {{-- Location + Type badge --}}
                    <div class="flex items-center gap-3 mb-3">
                        @if($konumStr)
                            <span class="flex items-center gap-1.5 text-sm text-[#737686]">
                                <x-icon name="konum" class="w-4 h-4 text-[#C9A84C]" />
                                <span>{{ $konumStr }}</span>
                            </span>
                        @endif
                        <span class="px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wide
                            {{ $isKiralik ? 'bg-[#B45309]/10 text-[#B45309]' : 'bg-[#15803D]/10 text-[#15803D]' }}">
                            {{ $isKiralik ? 'Kiralık' : 'Satılık' }}
                        </span>
                    </div>

                    {{-- Title --}}
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-[#0A1628] leading-tight tracking-tight mb-2">
                        {{ $ilan->baslik }}
                    </h1>

                    {{-- Reference --}}
                    <p class="text-xs text-[#737686] font-mono">
                        İlan No: YLH-{{ str_pad($ilan->id, 4, '0', STR_PAD_LEFT) }}
                    </p>
                </header>

                {{-- Key Property Facts Strip --}}
                <div class="flex flex-wrap gap-4 sm:gap-6 py-4 border-y border-[#E8E2D8] mb-8">
                    @if($ilan->oda_sayisi)
                        <div class="flex items-center gap-2">
                            <x-icon name="ev" class="w-5 h-5 text-[#C9A84C]" />
                            <span class="text-sm font-semibold text-[#191b23]">{{ $ilan->oda_sayisi }} Oda</span>
                        </div>
                    @endif
                    @if($ilan->net_m2 || $ilan->brut_m2)
                        @php $alan = $ilan->net_m2 ?? $ilan->brut_m2; @endphp
                        <div class="flex items-center gap-2">
                            <x-icon name="m2" class="w-5 h-5 text-[#C9A84C]" />
                            <span class="text-sm font-semibold text-[#191b23]">{{ number_format($alan, 0) }} m²</span>
                        </div>
                    @endif
                    @if($ilan->banyo_sayisi)
                        <div class="flex items-center gap-2">
                            <x-icon name="banyo" class="w-5 h-5 text-[#C9A84C]" />
                            <span class="text-sm font-semibold text-[#191b23]">{{ $ilan->banyo_sayisi }} Banyo</span>
                        </div>
                    @endif
                    @if($ilan->bulundugu_kat !== null)
                        <div class="flex items-center gap-2">
                            <x-icon name="kat" class="w-5 h-5 text-[#C9A84C]" />
                            <span class="text-sm font-semibold text-[#191b23]">Kat: {{ $ilan->bulundugu_kat }}</span>
                        </div>
                    @endif
                    @if($ilan->bina_yasi !== null)
                        <div class="flex items-center gap-2">
                            <x-icon name="bina" class="w-5 h-5 text-[#C9A84C]" />
                            <span class="text-sm font-semibold text-[#191b23]">{{ $ilan->bina_yasi }} Yaşında</span>
                        </div>
                    @endif
                </div>

                {{-- Price Display (Prominent) --}}
                <div class="mb-8 no-print">
                    <div class="flex items-baseline gap-3">
                        @if($fiyat > 0)
                            <span class="text-3xl sm:text-4xl font-extrabold text-[#0A1628]">
                                {{ number_format($fiyat, 0, ',', '.') }}
                            </span>
                            <span class="text-lg font-semibold text-[#737686]">{{ $paraSembol }}</span>
                            @if($fiyatEUR)
                                <span class="text-sm text-[#737686]">(≈ {{ number_format($fiyatEUR, 0, ',', '.') }} €)</span>
                            @endif
                            @if($fiyatUSD)
                                <span class="text-sm text-[#737686]">(≈ {{ number_format($fiyatUSD, 0, ',', '.') }} $)</span>
                            @endif
                        @else
                            <span class="text-2xl font-semibold text-[#737686]">Fiyat Sorunuz</span>
                        @endif
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="flex flex-wrap gap-3 mb-8 no-print">
                    <button onclick="saveIlan({{ $ilan->id }})"
                            class="flex items-center gap-2 px-4 py-2 border border-[#E8E2D8] rounded-lg text-sm font-medium text-[#191b23] hover:bg-[#F8F6F1] hover:border-[#C9A84C] transition-colors"
                            aria-label="İlanı kaydet">
                        <x-icon name="kaydet" class="w-4 h-4" />
                        <span>Kaydet</span>
                    </button>
                    <button onclick="shareIlan()"
                            class="flex items-center gap-2 px-4 py-2 border border-[#E8E2D8] rounded-lg text-sm font-medium text-[#191b23] hover:bg-[#F8F6F1] hover:border-[#C9A84C] transition-colors"
                            aria-label="İlanı paylaş">
                        <x-icon name="paylas" class="w-4 h-4" />
                        <span>Paylaş</span>
                    </button>
                    <button onclick="window.print()"
                            class="flex items-center gap-2 px-4 py-2 border border-[#E8E2D8] rounded-lg text-sm font-medium text-[#191b23] hover:bg-[#F8F6F1] hover:border-[#C9A84C] transition-colors"
                            aria-label="Yazdır">
                        <x-icon name="yazdir" class="w-4 h-4" />
                        <span>Yazdır</span>
                    </button>
                </div>

                {{-- Description Section --}}
                <section class="mb-10" aria-labelledby="desc-heading">
                    <h2 id="desc-heading" class="text-lg font-bold text-[#0A1628] mb-4">İlan Açıklaması</h2>
                    <div class="prose prose-sm max-w-none text-[#434655] leading-relaxed">
                        {!! nl2br(e($ilan->aciklama ?? 'Bu gayrimenkul için açıklama bulunmamaktadır.')) !!}
                    </div>
                </section>

                {{-- Property Details Grid --}}
                @php
                    $details = [];
                    if ($ilan->brut_m2) $details['Brüt Alan'] = number_format($ilan->brut_m2, 0) . ' m²';
                    if ($ilan->net_m2) $details['Net Alan'] = number_format($ilan->net_m2, 0) . ' m²';
                    if ($ilan->oda_sayisi) $details['Oda Sayısı'] = $ilan->oda_sayisi . ' Oda';
                    if ($ilan->salon_sayisi) $details['Salon'] = $ilan->salon_sayisi;
                    if ($ilan->banyo_sayisi) $details['Banyo'] = $ilan->banyo_sayisi;
                    if ($ilan->balkon_sayisi) $details['Balkon'] = $ilan->balkon_sayisi;
                    if ($ilan->bulundugu_kat !== null) $details['Bulunduğu Kat'] = $ilan->bulundugu_kat;
                    if ($ilan->toplam_kat) $details['Toplam Kat'] = $ilan->toplam_kat;
                    if ($ilan->bina_yasi !== null) $details['Bina Yaşı'] = $ilan->bina_yasi . ' yıl';
                    if ($ilan->isitma_tipi) $details['Isıtma'] = $ilan->isitma_tipi;
                    if ($ilan->kullanim_durumu) $details['Kullanım'] = $ilan->kullanim_durumu;
                    if ($ilan->site_icerisinde !== null) $details['Site İçi'] = $ilan->site_icerisinde ? 'Evet' : 'Hayır';
                    if ($ilan->esyali) $details['Eşyalı'] = $ilan->esyali ? 'Evet' : 'Hayır';
                    if ($ilan->krediye_uygun !== null) $details['Krediye Uygun'] = $ilan->krediye_uygun ? 'Evet' : 'Hayır';
                    if ($ilan->tapu_durumu) $details['Tapu'] = $ilan->tapu_durumu;
                @endphp

                @if(count($details) > 0)
                <section class="mb-10" aria-labelledby="details-heading">
                    <h2 id="details-heading" class="text-lg font-bold text-[#0A1628] mb-4">Gayrimenkul Detayları</h2>
                    <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3">
                        @foreach($details as $label => $value)
                            <div class="border-b border-[#E8E2D8] pb-2">
                                <dt class="text-xs font-medium text-[#737686] uppercase tracking-wide mb-0.5">{{ $label }}</dt>
                                <dd class="text-sm font-semibold text-[#191b23]">{{ $value }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
                @endif

                {{-- Features / Özellikler --}}
                @php
                    $ozellikler = $ilan->ozellikler ?? collect();
                    $activeOzellikler = $ozellikler->filter(fn($o) => $o->pivot->deger ?? false);
                @endphp
                @if($activeOzellikler->count() > 0)
                <section class="mb-10" aria-labelledby="features-heading">
                    <h2 id="features-heading" class="text-lg font-bold text-[#0A1628] mb-4">Özellikler</h2>
                    <ul class="grid grid-cols-2 sm:grid-cols-3 gap-3" role="list">
                        @foreach($activeOzellikler->take(12) as $ozellik)
                            <li class="flex items-center gap-2 text-sm text-[#434655]">
                                <x-icon name="tik" class="w-4 h-4 text-[#15803D] flex-shrink-0" />
                                <span>{{ $ozellik->ozellik_adi ?? $ozellik->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
                @endif

                {{-- Map / Location --}}
                @php
                    $lat = $ilan->lat ?? $ilan->latitude;
                    $lng = $ilan->lng ?? $ilan->longitude;
                    $hasCoords = $lat !== null && $lng !== null;
                @endphp
                @if($hasCoords)
                <section class="mb-10" aria-labelledby="map-heading">
                    <h2 id="map-heading" class="text-lg font-bold text-[#0A1628] mb-4">Konum</h2>
                    <div class="rounded-xl overflow-hidden border border-[#E8E2D8] h-64 sm:h-80">
                        <div id="property-map" data-lat="{{ round((float)$lat, 2) }}" data-lng="{{ round((float)$lng, 2) }}" class="w-full h-full bg-[#F8F6F1] flex items-center justify-center text-[#737686]">
                            <span class="text-sm">Harita yükleniyor...</span>
                        </div>
                    </div>
                    @if($konumStr)
                        <p class="mt-2 text-sm text-[#737686]">{{ $konumStr }}</p>
                    @endif
                </section>
                @endif

            </div>

            {{-- RIGHT: Advisor Sidebar (Desktop) --}}
            <aside class="w-full lg:w-80 xl:w-96 flex-shrink-0 mt-8 lg:mt-0 no-print" aria-label="Danışman bilgileri">
                <div class="advisor-sticky">

                    {{-- Advisor Card --}}
                    @php
                        $danisman = $ilan->relationLoaded('danisman') ? $ilan->getRelation('danisman') : null;
                        $danismanPhoto = $danisman?->profile_photo_url;
                        $danismanName = $danisman?->name;
                    @endphp

                    @if($danisman && $danismanName)
                    <div class="bg-white rounded-2xl border border-[#E8E2D8] shadow-[0_4px_24px_rgba(10,22,40,0.06)] p-6 mb-4">
                        <h3 class="text-xs font-semibold text-[#737686] uppercase tracking-wide mb-4">Bu İlan Hakkında</h3>

                        {{-- Advisor Info --}}
                        <div class="flex items-center gap-4 mb-5">
                            @if($danismanPhoto)
                                <img src="{{ $danismanPhoto }}"
                                     alt="{{ $danismanName }}"
                                     class="w-14 h-14 rounded-full object-cover border-2 border-[#E8E2D8]">
                            @else
                                <div class="w-14 h-14 rounded-full bg-[#0A1628] flex items-center justify-center">
                                    <span class="text-white font-bold text-lg">{{ substr($danismanName, 0, 1) }}</span>
                                </div>
                            @endif
                            <div>
                                <p class="font-bold text-[#0A1628]">{{ $danismanName }}</p>
                                <p class="text-xs text-[#737686]">Yalıhan Emlak Danışmanı</p>
                            </div>
                        </div>

                        {{-- CTA Buttons --}}
                        <div class="space-y-3">
                            <a href="{{ route('ilanlar.show', $ilan->id) }}#contact-form"
                               class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-[#0A1628] hover:bg-[#0F1E38] text-white text-sm font-semibold rounded-xl transition-colors">
                                <x-icon name="bilgi" class="w-4 h-4" />
                                <span>Bu Ev Hakkında Bilgi Alın</span>
                            </a>

                            @if($danisman?->telefon)
                            <a href="tel:{{ $danisman->telefon }}"
                               class="w-full flex items-center justify-center gap-2 px-4 py-3 border-2 border-[#0A1628] text-[#0A1628] hover:bg-[#0A1628] hover:text-white text-sm font-semibold rounded-xl transition-colors">
                                <x-icon name="telefon" class="w-4 h-4" />
                                <span>Danışmanı Ara</span>
                            </a>
                            @endif

                            @if($danisman?->whatsapp_numara)
                            <a href="https://wa.me/{{ ltrim($danisman->whatsapp_numara, '+0') }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-[#15803D] hover:bg-[#166534] text-white text-sm font-semibold rounded-xl transition-colors">
                                <x-icon name="whatsapp" class="w-4 h-4" />
                                <span>WhatsApp</span>
                            </a>
                            @endif
                        </div>
                    </div>
                    @else
                    {{-- No advisor --}}
                    <div class="bg-[#F8F6F1] rounded-2xl border border-[#E8E2D8] p-6 mb-4">
                        <h3 class="text-sm font-semibold text-[#0A1628] mb-2">Bilgi Alın</h3>
                        <p class="text-xs text-[#737686] mb-4">Bu ilan hakkında detaylı bilgi için bizimle iletişime geçin.</p>
                        <a href="{{ route('home') }}#contact"
                           class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-[#0A1628] hover:bg-[#0F1E38] text-white text-sm font-semibold rounded-xl transition-colors">
                            <x-icon name="bilgi" class="w-4 h-4" />
                            <span>İletişime Geçin</span>
                        </a>
                    </div>
                    @endif

                    {{-- Quick Contact Form --}}
                    <div id="contact-form" class="bg-white rounded-2xl border border-[#E8E2D8] shadow-[0_4px_24px_rgba(10,22,40,0.06)] p-6">
                        <h3 class="text-sm font-bold text-[#0A1628] mb-4">Hızlı Bilgi Formu</h3>
                        <form action="{{ route('frontend.forms.contact.submit') }}" method="POST" class="space-y-3">
                            @csrf
                            <input type="hidden" name="ilan_id" value="{{ $ilan->id }}">
                            <div>
                                <label for="contact-name" class="block text-xs font-medium text-[#737686] mb-1">Adınız</label>
                                <input type="text" id="contact-name" name="name" required
                                       class="w-full px-3 py-2.5 border border-[#E8E2D8] rounded-lg text-sm font-semibold text-[#191b23] focus:outline-none focus:ring-2 focus:ring-[#C9A84C] focus:border-transparent"
                                       placeholder="Adınız">
                            </div>
                            <div>
                                <label for="contact-phone" class="block text-xs font-medium text-[#737686] mb-1">Telefon</label>
                                <input type="tel" id="contact-phone" name="phone" required
                                       class="w-full px-3 py-2.5 border border-[#E8E2D8] rounded-lg text-sm font-semibold text-[#191b23] focus:outline-none focus:ring-2 focus:ring-[#C9A84C] focus:border-transparent"
                                       placeholder="05XX XXX XX XX">
                            </div>
                            <div>
                                <label for="contact-message" class="block text-xs font-medium text-[#737686] mb-1">Mesajınız</label>
                                <textarea id="contact-message" name="message" rows="3"
                                          class="w-full px-3 py-2.5 border border-[#E8E2D8] rounded-lg text-sm font-semibold text-[#191b23] focus:outline-none focus:ring-2 focus:ring-[#C9A84C] focus:border-transparent resize-none"
                                          placeholder="Bu gayrimenkul hakkında bilgi almak istiyorum...">{{ $ilan->baslik }} hakkında bilgi almak istiyorum.</textarea>
                            </div>
                            <button type="submit"
                                    class="w-full px-4 py-3 bg-[#C9A84C] hover:bg-[#D4B96A] text-[#0A1628] text-sm font-bold rounded-xl transition-colors">
                                Bilgi İste
                            </button>
                            <p class="text-xs text-[#737686] text-center">
                                Bilgileriniz yalnızca bu talebe yanıt vermek için kullanılır.
                            </p>
                        </form>
                    </div>

                </div>
            </aside>
        </div>
    </div>

    {{-- ============================================================
         RELATED LISTINGS
    ============================================================ --}}
    @if(isset($similar) && $similar->count() > 0)
    <section class="bg-[#F8F6F1] border-t border-[#E8E2D8] py-12 no-print" aria-labelledby="similar-heading">
        <div class="max-w-[1440px] mx-auto px-4 sm:px-6">
            <h2 id="similar-heading" class="text-xl font-bold text-[#0A1628] mb-6">Benzer Gayrimenkuller</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach($similar->take(4) as $benzer)
                    @php
                        $benzerFoto = $benzer->fotograflar?->sortBy('display_order')->first()?->dosya_yolu;
                        $benzerImg = $benzerFoto ? \Illuminate\Support\Facades\Storage::url($benzerFoto) : null;
                        $benzerIlce = is_object($benzer->getRelation('ilce') ?? null) ? $benzer->getRelation('ilce')->ilce_adi : null;
                    @endphp
                    <a href="{{ route('ilanlar.show', $benzer->id) }}"
                       class="bg-white rounded-xl overflow-hidden shadow-[0_4px_20px_-2px_rgba(10,22,40,0.06)] hover:shadow-[0_12px_32px_-4px_rgba(10,22,40,0.12)] transition-all duration-300 group border border-[#E8E2D8] block">
                        <div class="aspect-[4/3] overflow-hidden bg-slate-100">
                            @if($benzerImg)
                                <img src="{{ $benzerImg }}" alt="{{ $benzer->baslik }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                            @else
                                <x-property-placeholder icon="villa" />
                            @endif
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-[#0A1628] text-sm mb-1 line-clamp-2 group-hover:text-[#C9A84C] transition-colors">
                                {{ $benzer->baslik }}
                            </h3>
                            <p class="text-xs text-[#737686] mb-2">
                                {{ $benzerIlce ?? 'Bodrum' }}
                                @if($benzer->oda_sayisi) · {{ $benzer->oda_sayisi }} Oda @endif
                            </p>
                            <p class="font-bold text-[#0A1628]">
                                @if($benzer->fiyat)
                                    {{ number_format($benzer->fiyat, 0, ',', '.') }} {{ $benzer->para_birimi ?? '₺' }}
                                @else
                                    Fiyat Sorunuz
                                @endif
                            </p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
    @endif

</div>

{{-- ============================================================
     LIGHTBOX
============================================================ --}}
<div id="pd-lightbox" role="dialog" aria-modal="true" aria-label="Fotoğraf galerisi">
    <button onclick="closeLightbox()" class="absolute top-4 right-4 z-10 p-2 text-white/80 hover:text-white transition-colors" aria-label="Kapat">
        <x-icon name="kapat" class="w-8 h-8" />
    </button>
    <button onclick="lbPrev()" class="absolute left-4 top-1/2 -translate-y-1/2 p-2 text-white/80 hover:text-white transition-colors" aria-label="Önceki">
        <x-icon name="sol-chevron" class="w-8 h-8" />
    </button>
    <button onclick="lbNext()" class="absolute right-4 top-1/2 -translate-y-1/2 p-2 text-white/80 hover:text-white transition-colors" aria-label="Sonraki">
        <x-icon name="sag-chevron" class="w-8 h-8" />
    </button>
    <img id="pd-lb-img" src="" alt="" class="max-w-[90vw] max-h-[85vh] object-contain">
    <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-white text-sm font-medium">
        <span id="pd-lb-counter">1 / 1</span>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // ── Lightbox ──────────────────────────────────────────
    const _lb_imgs = @json($allImages);
    let _lb_idx = 0;

    function openLightbox(idx) {
        if (!_lb_imgs.length || idx < 0) return;
        _lb_idx = idx;
        const lb = document.getElementById('pd-lightbox');
        const img = document.getElementById('pd-lb-img');
        img.src = _lb_imgs[_lb_idx];
        img.alt = '{{ $ilan->baslik }} - Fotoğraf ' + (_lb_idx + 1);
        document.getElementById('pd-lb-counter').textContent = (_lb_idx + 1) + ' / ' + _lb_imgs.length;
        lb.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeLightbox() {
        document.getElementById('pd-lightbox').classList.remove('active');
        document.body.style.overflow = '';
    }

    function lbPrev() {
        _lb_idx = (_lb_idx - 1 + _lb_imgs.length) % _lb_imgs.length;
        openLightbox(_lb_idx);
    }

    function lbNext() {
        _lb_idx = (_lb_idx + 1) % _lb_imgs.length;
        openLightbox(_lb_idx);
    }

    document.addEventListener('keydown', function(e) {
        const lb = document.getElementById('pd-lightbox');
        if (!lb.classList.contains('active')) return;
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') lbPrev();
        if (e.key === 'ArrowRight') lbNext();
    });

    // ── Share ────────────────────────────────────────────
    function shareIlan() {
        const url = window.location.href;
        const title = {{ json_encode($ilan->baslik) }};
        if (navigator.share) {
            navigator.share({ title: title, url: url }).catch(function() {});
        } else {
            navigator.clipboard.writeText(url).then(function() {
                var btn = document.querySelector('[onclick="shareIlan()"]');
                var orig = btn.innerHTML;
                btn.innerHTML = '<x-icon name="tik" class="w-4 h-4" /> Kopyalandı!';
                btn.style.background = 'rgba(21, 128, 61, 0.1)';
                setTimeout(function() { btn.innerHTML = orig; btn.style.background = ''; }, 2000);
            });
        }
    }

    // ── Save (placeholder) ─────────────────────────────────
    function saveIlan(id) {
        // Placeholder — integrate with FavoriService if available
        var btn = document.querySelector('[onclick="saveIlan(' + id + ')"]');
        btn.innerHTML = '<x-icon name="tik" class="w-4 h-4" /> Kaydedildi!';
        btn.style.background = 'rgba(21, 128, 61, 0.1)';
        setTimeout(function() {
            btn.innerHTML = '<x-icon name="kaydet" class="w-4 h-4" /> Kaydet';
            btn.style.background = '';
        }, 2000);
    }

    // ── Simple Map Init ─────────────────────────────────
    (function() {
        var mapEl = document.getElementById('property-map');
        if (!mapEl) return;
        var lat = parseFloat(mapEl.dataset.lat);
        var lng = parseFloat(mapEl.dataset.lng);
        if (isNaN(lat) || isNaN(lng)) return;

        // OpenStreetMap embed as fallback (no API key required)
        var zoom = 14;
        mapEl.innerHTML = '<iframe width="100%" height="100%" style="border:0" loading="lazy" allowfullscreen src="https://www.openstreetmap.org/export/embed.html?bbox=' + (lng - 0.01) + ',' + (lat - 0.01) + ',' + (lng + 0.01) + ',' + (lat + 0.01) + '&layer=mapnik&marker=' + lat + ',' + lng + '"></iframe>';
    })();
</script>
@endpush
