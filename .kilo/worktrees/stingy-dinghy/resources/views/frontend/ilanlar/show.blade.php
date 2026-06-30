@extends('layouts.frontend')

{{-- title ve meta_description controller'daki $seo array'ından layout tarafından otomatik render edilir --}}


@push('styles')
    <style>
        .premium-glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .dark .premium-glass {
            background: rgba(31, 41, 55, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .text-balanced {
            text-wrap: balance;
        }

        .hero-gradient {
            background: linear-gradient(to bottom, transparent 60%, rgba(0, 0, 0, 0.8) 100%);
        }
    </style>
@endpush

@section('content')
    <div class="min-h-screen bg-white dark:bg-gray-950 dark:bg-slate-900">

        <!-- Hero Section: Premium Gallery -->
        @php
            // Fotoğraflar
            $tumFotograflar = $ilan->fotograflar ?? collect();
            $ilkFoto   = $tumFotograflar->first();
            $mainImage = $ilkFoto ? \Illuminate\Support\Facades\Storage::url($ilkFoto->dosya_yolu) : null;
            $fotografSayisi = $tumFotograflar->count();

            // Konum — güvenli erişim ('il' hem fillable string hem relation)
            $ilRelation   = $ilan->relationLoaded('il')     ? $ilan->getRelation('il')     : null;
            $ilceRelation = $ilan->relationLoaded('ilce')   ? $ilan->getRelation('ilce')   : null;
            $mahRelation  = $ilan->relationLoaded('mahalle')? $ilan->getRelation('mahalle'): null;
            $ilAdi      = is_object($ilRelation)   ? ($ilRelation->il_adi       ?? null) : null;
            $ilceAdi    = is_object($ilceRelation) ? ($ilceRelation->ilce_adi   ?? null) : null;
            $mahalleAdi = is_object($mahRelation)  ? ($mahRelation->mahalle_adi ?? null) : null;
            $konumParcalari = array_filter([$ilAdi, $ilceAdi, $mahalleAdi], fn($v) => $v && strtolower($v) !== 'belirtilmemiş' && strtolower($v) !== 'belirtilmemis');

            // Referans kodu
            $refKodu = 'YLH-' . str_pad($ilan->id, 4, '0', STR_PAD_LEFT);

            // Para birimi dönüşümü (yaklaşık kur)
            $fiyatTRY = $ilan->fiyat ?? 0;
            $paraBirimi = strtoupper($ilan->para_birimi ?? 'TRY');
            $eurKur = 38.5; // yaklaşık
            $usdKur = 36.8; // yaklaşık
            $fiyatEUR = $paraBirimi === 'TRY' && $fiyatTRY > 0 ? round($fiyatTRY / $eurKur) : null;
            $fiyatUSD = $paraBirimi === 'TRY' && $fiyatTRY > 0 ? round($fiyatTRY / $usdKur) : null;
        @endphp
        <div class="relative w-full overflow-hidden group"
             style="height:65vh; min-height:480px;">
            @if($mainImage)
                <img src="{{ $mainImage }}"
                    class="hero-main-img w-full h-full object-cover transition-transform duration-1000 group-hover:scale-105"
                    alt="{{ $ilan->baslik }}"
                    style="cursor:{{ $fotografSayisi > 0 ? 'zoom-in' : 'default' }};"
                    onclick="{{ $fotografSayisi > 0 ? 'openLightbox(0)' : '' }}">
                <div class="absolute inset-0 hero-gradient"></div>
                @if($fotografSayisi > 1)
                    <button onclick="openLightbox(0)"
                            style="position:absolute;bottom:1rem;right:1rem;z-index:5;background:rgba(0,0,0,0.55);backdrop-filter:blur(6px);color:#fff;border:1px solid rgba(255,255,255,0.3);border-radius:0.5rem;padding:0.5rem 0.875rem;font-family:'Inter',sans-serif;font-size:0.75rem;font-weight:600;display:flex;align-items:center;gap:0.4rem;cursor:pointer;transition:background 0.2s;"
                            onmouseover="this.style.background='rgba(0,0,0,0.75)'"
                            onmouseout="this.style.background='rgba(0,0,0,0.55)'"
                            aria-label="Tüm fotoğrafları gör">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        {{ $fotografSayisi }} Fotoğraf
                    </button>
                @endif
            @else
                {{-- Fotoğraf yoksa: kurumsal koyu arka plan --}}
                <div class="absolute inset-0" style="background:#0F2A5C;"></div>
                <div class="absolute inset-0" style="background:linear-gradient(to top,rgba(0,0,0,0.4) 0%,transparent 60%);"></div>
                {{-- Merkez ikon --}}
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none" style="margin-bottom:80px;">
                    <div style="opacity:0.07;">
                        <svg width="180" height="180" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="0.5" aria-hidden="true">
                            <rect x="2" y="3" width="20" height="14" rx="1"/>
                            <path d="M8 21h8M12 17v4"/>
                            <path d="M2 7h20M2 11h20"/>
                            <path d="M7 3v14M12 3v14M17 3v14"/>
                        </svg>
                    </div>
                </div>
            @endif

            <!-- Üst bar: Geri + REF + Favori + Paylaş -->
            <div class="absolute top-8 left-0 right-0 z-10">
                <div class="max-w-7xl mx-auto px-6 flex justify-between items-center">
                    <a href="{{ url()->previous() === url()->current() ? route('ilanlar.index') : url()->previous() }}"
                       style="display:flex;align-items:center;gap:0.5rem;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);color:#fff;padding:0.5rem 1rem;border-radius:9999px;font-size:0.8rem;font-weight:600;text-decoration:none;transition:background 0.2s;"
                       onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
                        İlanlara Dön
                    </a>
                    <div style="display:flex;align-items:center;gap:0.5rem;">
                        {{-- REF kodu --}}
                        <span style="background:rgba(0,0,0,0.35);backdrop-filter:blur(6px);border:1px solid rgba(255,255,255,0.15);color:rgba(255,255,255,0.85);padding:0.4rem 0.75rem;border-radius:9999px;font-family:'Inter',sans-serif;font-size:0.7rem;font-weight:700;letter-spacing:0.06em;">
                            {{ $refKodu }}
                        </span>
                        {{-- Paylaş --}}
                        <button onclick="shareIlan()" aria-label="Paylaş"
                                style="display:flex;align-items:center;gap:0.4rem;background:rgba(255,255,255,0.15);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,0.2);color:#fff;padding:0.5rem 0.875rem;border-radius:9999px;font-size:0.8rem;font-weight:600;cursor:pointer;transition:background 0.2s;"
                                onmouseover="this.style.background='rgba(255,255,255,0.25)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.15)'">
                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                            Paylaş
                        </button>
                        @include('components.favori-toggle', ['ilan' => $ilan])
                    </div>
                </div>
            </div>

            <!-- Başlık overlay -->
            <div class="absolute bottom-10 left-0 right-0">
                <div class="max-w-7xl mx-auto px-6">
                    <div class="max-w-3xl">
                        {{-- Breadcrumb --}}
                        @if(count($konumParcalari) > 0)
                            <div style="display:flex;align-items:center;gap:0.4rem;margin-bottom:0.75rem;font-family:'Inter',sans-serif;font-size:0.75rem;color:rgba(255,255,255,0.65);">
                                <a href="{{ route('home') }}" style="color:rgba(255,255,255,0.65);text-decoration:none;">Ana Sayfa</a>
                                <span>›</span>
                                <a href="{{ route('ilanlar.index') }}" style="color:rgba(255,255,255,0.65);text-decoration:none;">İlanlar</a>
                                @foreach($konumParcalari as $parca)
                                    <span>›</span>
                                    <span style="color:rgba(255,255,255,0.9);">{{ $parca }}</span>
                                @endforeach
                            </div>
                        @endif

                        {{-- Kategori + tip badge --}}
                        <div style="display:flex;gap:0.5rem;margin-bottom:1rem;">
                            @if($ilan->yayinTipi?->yayin_tipi)
                                <span style="padding:0.2rem 0.75rem;background:#004ac6;color:#fff;font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;border-radius:0.25rem;">
                                    {{ strtoupper($ilan->yayinTipi->yayin_tipi) }}
                                </span>
                            @endif
                            @if($ilan->altKategori?->name ?? $ilan->anaKategori?->name)
                                <span style="padding:0.2rem 0.75rem;background:rgba(255,255,255,0.15);color:#fff;font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:700;letter-spacing:0.06em;text-transform:uppercase;border-radius:0.25rem;border:1px solid rgba(255,255,255,0.25);backdrop-filter:blur(4px);">
                                    {{ strtoupper($ilan->altKategori?->name ?? $ilan->anaKategori?->name) }}
                                </span>
                            @endif
                        </div>

                        <h1 style="font-family:'Manrope',sans-serif;font-size:clamp(1.75rem,4vw,3.25rem);font-weight:800;color:#fff;line-height:1.2;letter-spacing:-0.02em;margin-bottom:0.75rem;">
                            {{ $ilan->baslik }}
                        </h1>

                        @if(count($konumParcalari) > 0)
                            <div style="display:flex;align-items:center;gap:0.4rem;color:rgba(255,255,255,0.8);font-size:0.9rem;font-family:'Inter',sans-serif;">
                                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#60a5fa" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/><circle cx="12" cy="9" r="2.5"/></svg>
                                {{ implode(' / ', $konumParcalari) }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Thumbnail Şeridi (fotoğraf varsa) ── --}}
        @if($fotografSayisi > 1)
        <div style="background:#0f172a;padding:0.5rem 0;overflow-x:auto;scrollbar-width:thin;scrollbar-color:rgba(255,255,255,0.2) transparent;"
             x-data="{ aktif: 0 }">
            <div style="display:flex;gap:0.5rem;max-width:1280px;margin:0 auto;padding:0 1.5rem;align-items:center;">
                @foreach($tumFotograflar as $i => $foto)
                    <button type="button"
                            onclick="document.querySelector('.hero-main-img').src='{{ \Illuminate\Support\Facades\Storage::url($foto->dosya_yolu) }}'; document.querySelectorAll('.thumb-btn').forEach(b=>b.style.borderColor='transparent'); this.style.borderColor='#004ac6';"
                            class="thumb-btn"
                            style="flex-shrink:0;width:72px;height:52px;border-radius:6px;overflow:hidden;border:2px solid {{ $i === 0 ? '#004ac6' : 'transparent' }};padding:0;cursor:pointer;transition:border-color 0.2s;"
                            aria-label="Fotoğraf {{ $i+1 }}">
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($foto->dosya_yolu) }}"
                             style="width:100%;height:100%;object-fit:cover;" loading="lazy" alt="">
                    </button>
                @endforeach
                <span style="flex-shrink:0;font-family:'Inter',sans-serif;font-size:0.7rem;color:rgba(255,255,255,0.5);margin-left:0.5rem;white-space:nowrap;">
                    {{ $fotografSayisi }} fotoğraf
                </span>
            </div>
        </div>
        @endif

        {{-- ── Sticky Fiyat Çubuğu ── --}}
        <div class="dark:bg-slate-900/95 dark:border-slate-800" style="position:sticky;top:72px;z-index:40;width:100%;background:rgba(255,255,255,0.97);backdrop-filter:blur(12px);border-bottom:1px solid #e2e8f0;box-shadow:0 2px 12px rgba(0,26,110,0.06);">
            <div style="max-width:1280px;margin:0 auto;padding:0 1.5rem;height:72px;display:flex;align-items:center;justify-content:space-between;gap:1rem;">

                {{-- Sol: Fiyat + m² fiyatı + döviz --}}
                <div style="display:flex;align-items:baseline;gap:1rem;flex-wrap:wrap;">
                    @if($ilan->fiyat_gosterim_metni)
                        <span class="dark:text-white" style="font-family:'Manrope',sans-serif;font-size:1.75rem;font-weight:900;color:#191b23;letter-spacing:-0.02em;">
                            {{ $ilan->fiyat_gosterim_metni }}
                        </span>
                    @elseif($fiyatTRY > 0)
                        <span class="dark:text-white" style="font-family:'Manrope',sans-serif;font-size:1.75rem;font-weight:900;color:#191b23;letter-spacing:-0.02em;">
                            {{ number_format($fiyatTRY, 0, ',', '.') }} {{ $paraBirimi }}
                        </span>
                    @endif

                    @if($ilan->net_m2 && $fiyatTRY > 0)
                        <span style="font-family:'Inter',sans-serif;font-size:0.8rem;color:#737686;font-weight:500;">
                            {{ number_format($fiyatTRY / $ilan->net_m2, 0, ',', '.') }} ₺/m²
                        </span>
                    @endif

                    @if($fiyatEUR)
                        <span style="font-family:'Inter',sans-serif;font-size:0.8rem;color:#004ac6;font-weight:600;background:#eff6ff;padding:0.2rem 0.6rem;border-radius:9999px;border:1px solid #dbeafe;">
                            ≈ {{ number_format($fiyatEUR, 0, ',', '.') }} €
                        </span>
                    @endif
                    @if($fiyatUSD)
                        <span style="font-family:'Inter',sans-serif;font-size:0.8rem;color:#065f46;font-weight:600;background:#ecfdf5;padding:0.2rem 0.6rem;border-radius:9999px;border:1px solid #d1fae5;">
                            ≈ {{ number_format($fiyatUSD, 0, ',', '.') }} $
                        </span>
                    @endif
                </div>

                {{-- Sağ: Cortex badge + CTA --}}
                <div style="display:flex;align-items:center;gap:0.75rem;flex-shrink:0;">
                    @if(($cortexHealth['overall_health'] ?? 0) >= 80)
                        <span style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#065f46;background:#ecfdf5;border:1px solid #6ee7b7;padding:0.25rem 0.75rem;border-radius:9999px;">
                            Yüksek Talep
                        </span>
                    @endif
                    @if($cortexAnalysis['roi_analizi']['is_high_yield'] ?? false)
                        <span style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#3730a3;background:#eef2ff;border:1px solid #a5b4fc;padding:0.25rem 0.75rem;border-radius:9999px;">
                            Hızlı Amortisman
                        </span>
                    @endif
                    <a href="#iletisim"
                       style="padding:0.625rem 1.75rem;background:#004ac6;color:#fff;border-radius:0.625rem;font-family:'Inter',sans-serif;font-weight:700;font-size:0.875rem;text-decoration:none;white-space:nowrap;transition:background 0.2s;"
                       onmouseover="this.style.background='#2563eb'"
                       onmouseout="this.style.background='#004ac6'">
                        İletişime Geç
                    </a>
                </div>
            </div>
        </div>

        <main class="max-w-7xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">

                <!-- Left Content -->
                <div class="lg:col-span-8 space-y-16">

                    <!-- Temel Bilgiler — kategori bazlı -->
                    @php
                        $katSlug = strtolower($ilan->anaKategori?->slug ?? '');
                        $isArsaDetay = str_contains($katSlug, 'arsa') || str_contains($katSlug, 'arazi');
                        $arsaD = $ilan->arsaDetail ?? null;
                    @endphp
                    <section>
                        <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 mb-8 border-b border-gray-100 pb-4">
                            Temel Bilgiler</h2>

                        @if($isArsaDetay)
                            {{-- ARSA görünümü --}}
                            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:1.5rem 2rem;">
                                <div>
                                    <p style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9ca3af;margin-bottom:0.375rem;">Arsa Alanı</p>
                                    @php $arsaAlani = $ilan->net_m2 ?? $arsaD?->arsa_alani_m2 ?? null; @endphp
                                    @if($arsaAlani)
                                        <p style="font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:800;color:#191b23;">{{ number_format($arsaAlani, 0) }} m²</p>
                                    @else
                                        <p style="font-family:'Inter',sans-serif;font-size:0.875rem;font-weight:500;color:#9ca3af;font-style:italic;">Belirtilmemiş</p>
                                    @endif
                                </div>
                                <div>
                                    <p style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9ca3af;margin-bottom:0.375rem;">İmar Durumu</p>
                                    @php $imarDurumu = $arsaD?->imar_durumu ?? $ilan->imar_durumu ?? null; @endphp
                                    @if($imarDurumu)
                                        <p style="font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:800;color:#191b23;">{{ $imarDurumu }}</p>
                                    @else
                                        <p style="font-family:'Inter',sans-serif;font-size:0.875rem;font-weight:500;color:#9ca3af;font-style:italic;">Bilgi alın</p>
                                    @endif
                                </div>
                                <div>
                                    <p style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9ca3af;margin-bottom:0.375rem;">Ada / Parsel</p>
                                    @php $adaParsel = $ilan->ada_parsel ?? (($ilan->ada_no && $ilan->parsel_no) ? $ilan->ada_no.'/'.$ilan->parsel_no : null); @endphp
                                    @if($adaParsel)
                                        <p style="font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:800;color:#191b23;">{{ $adaParsel }}</p>
                                    @else
                                        <p style="font-family:'Inter',sans-serif;font-size:0.875rem;font-weight:500;color:#9ca3af;font-style:italic;">Belirtilmemiş</p>
                                    @endif
                                </div>
                                <div>
                                    <p style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9ca3af;margin-bottom:0.375rem;">TAKS / KAKS</p>
                                    @if($arsaD?->taks || $arsaD?->kaks)
                                        <p style="font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:800;color:#191b23;">{{ $arsaD->taks ?? '—' }} / {{ $arsaD->kaks ?? '—' }}</p>
                                    @else
                                        <p style="font-family:'Inter',sans-serif;font-size:0.875rem;font-weight:500;color:#9ca3af;font-style:italic;">Bilgi alın</p>
                                    @endif
                                </div>
                                @if($ilan->yola_cephe || $ilan->altyapi_elektrik || $ilan->altyapi_su)
                                <div style="grid-column:1/-1;">
                                    <p style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#9ca3af;margin-bottom:0.75rem;">Altyapı</p>
                                    <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                                        @foreach(['yola_cephe'=>'Yola Cephe','altyapi_elektrik'=>'Elektrik','altyapi_su'=>'Su','altyapi_dogalgaz'=>'Doğalgaz'] as $alan => $etiket)
                                            @if(isset($ilan->$alan))
                                                <span style="display:flex;align-items:center;gap:0.4rem;font-family:'Inter',sans-serif;font-size:0.8rem;font-weight:600;padding:0.375rem 0.875rem;border-radius:9999px;{{ $ilan->$alan ? 'background:#ecfdf5;color:#065f46;border:1px solid #6ee7b7;' : 'background:#fef2f2;color:#991b1b;border:1px solid #fca5a5;' }}">
                                                    @if($ilan->$alan)
                                                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                    @else
                                                        <svg width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                    @endif
                                                    {{ $etiket }}
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                            </div>
                        @else
                            {{-- KONUT / VİLLA görünümü --}}
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-y-10 gap-x-8">
                                <div class="space-y-1">
                                    <p class="text-xs text-gray-400 uppercase">Alan</p>
                                    <p class="text-xl font-bold text-gray-900">{{ ($ilan->brut_m2 ?? $ilan->net_m2) ? number_format($ilan->brut_m2 ?? $ilan->net_m2, 0).' m²' : '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-gray-400 uppercase">Yerleşim</p>
                                    <p class="text-xl font-bold text-gray-900">{{ $ilan->oda_sayisi ? $ilan->oda_sayisi.' Oda' : '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-gray-400 uppercase">Banyo</p>
                                    <p class="text-xl font-bold text-gray-900">{{ $ilan->banyo_sayisi ?? '—' }}</p>
                                </div>
                                <div class="space-y-1">
                                    <p class="text-xs text-gray-400 uppercase">Durum</p>
                                    <p class="text-xl font-bold text-gray-900">{{ $ilan->bina_yasi == 0 ? 'Yeni' : ($ilan->bina_yasi ? $ilan->bina_yasi.' Yaş' : '—') }}</p>
                                </div>
                            </div>
                        @endif
                    </section>

                    <!-- Investment Insights: Cortex ROI -->
                    <section>
                        <h2
                            class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 mb-8 border-b border-gray-100 dark:border-slate-800 pb-4">
                            Yatırım Analizi</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div
                                class="p-6 rounded-2xl bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-slate-800 flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:#dbeafe;color:#004ac6;flex-shrink:0;">
                                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="22 7 13.5 15.5 8.5 10.5 2 17"/><polyline points="16 7 22 7 22 13"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Amortisman
                                        Süresi</p>
                                    <p class="text-2xl font-black text-gray-900 dark:text-white dark:text-slate-100">
                                        {{ $cortexAnalysis['roi_analizi']['payback_period_years'] ?? '-' }} Yıl</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        {{ $cortexAnalysis['roi_analizi']['market_comparison'] ?? 'Bölge ortalamasında.' }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="p-6 rounded-2xl bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-slate-800 flex items-start gap-4">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background:#e0e7ff;color:#4f46e5;flex-shrink:0;">
                                    <svg width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2zm0 0V9a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v10m-6 0a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2m0 0V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2z"/></svg>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-400 uppercase font-bold tracking-wider mb-1">Piyasa
                                        Skorlaması</p>
                                    <p class="text-2xl font-black text-gray-900 dark:text-white dark:text-slate-100">
                                        %{{ $cortexHealth['overall_health'] ?? 0 }}</p>
                                    <p class="text-xs text-gray-500 mt-1">İlan veri kalitesi ve fiyat rekabeti analizi.</p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Description: Clean Typography -->
                    <section>
                        <h2
                            class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 mb-8 border-b border-gray-100 dark:border-slate-800 pb-4">
                            Açıklama</h2>
                        <div
                            class="prose prose-xl prose-gray dark:prose-invert max-w-none leading-relaxed text-gray-600 dark:text-slate-200">
                            {!! nl2br(e($ilan->aciklama)) !!}
                        </div>
                    </section>

                    <!-- Sunulan Özellikler — DB features + boolean alanlar -->
                    @php
                        // Boolean alanlardan özellik listesi oluştur
                        $booleanOzellikler = array_filter([
                            $ilan->havuz_var         ? 'Özel Havuz'              : null,
                            $ilan->havuz_isitmali    ? 'Isıtmalı Havuz'          : null,
                            $ilan->bahce_var         ? 'Özel Bahçe'              : null,
                            $ilan->bahce_masasi_var  ? 'Bahçe Oturma Alanı'      : null,
                            $ilan->barbeku_var       ? 'Barbekü'                 : null,
                            $ilan->sezlong_var       ? 'Şezlong Alanı'           : null,
                            $ilan->deniz_manzarali   ? 'Deniz Manzaralı'         : null,
                            $ilan->doga_manzarali    ? 'Doğa Manzaralı'          : null,
                            $ilan->dag_manzarali     ? 'Dağ Manzaralı'           : null,
                            $ilan->esyali ?? false   ? 'Eşyalı'                 : null,
                            $ilan->isitma_var        ? 'Isıtma Sistemi'          : null,
                            $ilan->mutfak_tam_donanmli ? 'Tam Donanımlı Mutfak'  : null,
                            $ilan->mutfak_bulasik_makinesi ? 'Bulaşık Makinesi'  : null,
                            $ilan->evcil_hayvan_uygun ? 'Evcil Hayvan Dostu'     : null,
                            // Arsa tipinde altyapı Temel Bilgiler'de gösterilir, burada tekrar etme
                            !$isArsaDetay && $ilan->altyapi_elektrik ? 'Elektrik Altyapısı' : null,
                            !$isArsaDetay && $ilan->altyapi_su       ? 'Su Altyapısı'       : null,
                            !$isArsaDetay && $ilan->altyapi_dogalgaz ? 'Doğalgaz Altyapısı' : null,
                        ]);
                        $dbOzellikler = $ilan->features->pluck('name')->toArray();
                        $tumOzellikler = array_merge($dbOzellikler, array_values($booleanOzellikler));
                        $tumOzellikler = array_unique($tumOzellikler);
                    @endphp

                    @if(count($tumOzellikler) > 0)
                        <section>
                            <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 mb-6 border-b border-gray-100 pb-4">
                                Sunulan Özellikler</h2>
                            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:0.875rem;">
                                @foreach($tumOzellikler as $ozellik)
                                    <div style="display:flex;align-items:center;gap:0.75rem;padding:0.625rem 0.75rem;background:#f8fafc;border-radius:0.5rem;border:1px solid #e2e8f0;">
                                        <div style="width:28px;height:28px;border-radius:50%;background:#dbeafe;color:#004ac6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        </div>
                                        <span style="font-family:'Inter',sans-serif;font-size:0.85rem;font-weight:500;color:#374151;">{{ $ozellik }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <!-- Location: Interactive Map -->
                    <section>
                        <h2
                            class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 mb-8 border-b border-gray-100 dark:border-slate-800 pb-4">
                            Konum Analizi</h2>
                        <div class="rounded-3xl overflow-hidden shadow-2xl border border-gray-100 dark:border-slate-800">
                            @php
                                $lat = $ilan->lat ?? 37.034407;
                                $lng = $ilan->lng ?? 27.43054;
                            @endphp
                            <x-yaliihan.map-component :center="['lat' => $lat, 'lng' => $lng]" :markers="[['title' => $ilan->baslik, 'lat' => $lat, 'lng' => $lng]]" height="500px" />
                        </div>
                    </section>

                    {{-- ── YouTube Video ── --}}
                    @if($ilan->youtube_video_url)
                        @php
                            // YouTube URL'den video ID çıkar
                            preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|watch\?v=|watch\?.+&v=))([^&\n?#]+)/', $ilan->youtube_video_url, $ytMatch);
                            $ytId = $ytMatch[1] ?? null;
                        @endphp
                        @if($ytId)
                        <section>
                            <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 mb-6 border-b border-gray-100 pb-4">
                                Video Tur</h2>
                            <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:1rem;box-shadow:0 8px 32px rgba(0,26,110,0.10);">
                                <iframe
                                    src="https://www.youtube.com/embed/{{ $ytId }}?rel=0&modestbranding=1"
                                    style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                    title="Video Tur — {{ $ilan->baslik }}"
                                    loading="lazy">
                                </iframe>
                            </div>
                        </section>
                        @endif
                    @endif

                    {{-- ── Sanal Tur ── --}}
                    @if($ilan->sanal_tur_url)
                        <section>
                            <h2 class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 mb-6 border-b border-gray-100 pb-4">
                                360° Sanal Tur</h2>
                            <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:1rem;box-shadow:0 8px 32px rgba(0,26,110,0.10);background:#0f172a;">
                                <iframe
                                    src="{{ $ilan->sanal_tur_url }}"
                                    style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;"
                                    allowfullscreen
                                    title="Sanal Tur — {{ $ilan->baslik }}"
                                    loading="lazy">
                                </iframe>
                            </div>
                            <a href="{{ $ilan->sanal_tur_url }}" target="_blank" rel="noopener"
                               style="display:inline-flex;align-items:center;gap:0.4rem;margin-top:0.75rem;font-family:'Inter',sans-serif;font-size:0.8rem;font-weight:600;color:#004ac6;text-decoration:none;">
                                <svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                Tam Ekranda Aç
                            </a>
                        </section>
                    @endif

                </div>

                <!-- Right Sidebar -->
                <div class="lg:col-span-4 space-y-10">

                    <!-- Agent Card: Glassmorphism -->
                    <div id="iletisim" class="premium-glass p-8 rounded-[32px] shadow-2xl sticky top-48">
                        @php
                            $danisman = $ilan->danisman_id ? $ilan->danisman : null;
                            $danismanAd = $danisman?->name ?? 'Yalıhan Emlak';
                            $adParcalari = explode(' ', trim($danismanAd));
                            $initials = strtoupper(substr($adParcalari[0] ?? '', 0, 1) . substr(end($adParcalari) ?? '', 0, 1));
                            $telefon = $danisman?->telefon ?? config('app.contact_phone', '+902521234567');
                            $waNo = preg_replace('/\D/', '', $danisman?->whatsapp_numara ?? $danisman?->telefon ?? '902521234567');
                            $waNo = str_starts_with($waNo, '90') ? $waNo : '90'.$waNo;
                        @endphp

                        <div style="text-align:center;margin-bottom:1.5rem;">
                            {{-- Avatar: initials fallback --}}
                            <div style="width:80px;height:80px;border-radius:50%;margin:0 auto 1rem;border:3px solid #dde1f0;overflow:hidden;background:linear-gradient(135deg,#004ac6,#2563eb);display:flex;align-items:center;justify-content:center;">
                                @if($danisman?->profile_photo_url ?? false)
                                    <img src="{{ $danisman->profile_photo_url }}" style="width:100%;height:100%;object-fit:cover;" alt="{{ $danismanAd }}">
                                @else
                                    <span style="font-family:'Manrope',sans-serif;font-size:1.4rem;font-weight:800;color:#fff;letter-spacing:-0.02em;">{{ $initials }}</span>
                                @endif
                            </div>
                            <h3 style="font-family:'Manrope',sans-serif;font-size:1.2rem;font-weight:800;color:#191b23;">{{ $danismanAd }}</h3>
                            <p style="font-family:'Inter',sans-serif;font-size:0.7rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#004ac6;margin-top:0.25rem;">
                                {{ $danisman?->baslik ?? 'Portföy Uzmanı' }}
                            </p>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:0.75rem;">
                            <a href="tel:{{ $telefon }}"
                               style="display:flex;align-items:center;justify-content:center;gap:0.6rem;width:100%;padding:0.875rem;background:#191b23;color:#fff;border-radius:0.875rem;font-family:'Inter',sans-serif;font-weight:700;font-size:0.9rem;text-decoration:none;transition:background 0.2s;"
                               onmouseover="this.style.background='#004ac6'"
                               onmouseout="this.style.background='#191b23'">
                                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.63 1.18h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.73a16 16 0 0 0 5.35 5.35l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21.9 16z"/></svg>
                                Hemen Ara
                            </a>
                            <a href="https://wa.me/{{ $waNo }}?text={{ urlencode($ilan->baslik . ' hakkında bilgi almak istiyorum.') }}"
                               target="_blank" rel="noopener"
                               style="display:flex;align-items:center;justify-content:center;gap:0.6rem;width:100%;padding:0.875rem;border:2px solid #16a34a;color:#16a34a;border-radius:0.875rem;font-family:'Inter',sans-serif;font-weight:700;font-size:0.9rem;text-decoration:none;transition:background 0.2s;"
                               onmouseover="this.style.background='#f0fdf4'"
                               onmouseout="this.style.background='transparent'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                                WhatsApp Mesajı
                            </a>
                        </div>

                        {{-- ── Cortex AI Kart Versiyonları (Toggle) ── --}}
                        <div x-data="{ ver: 'c' }" x-cloak>

                            {{-- Versiyon seçici --}}
                            <div style="display:flex;align-items:center;gap:0.375rem;margin-top:2.5rem;margin-bottom:-1.5rem;position:relative;z-index:1;">
                                <span style="font-family:'Inter',sans-serif;font-size:0.6rem;font-weight:700;color:rgba(10,22,40,0.35);text-transform:uppercase;letter-spacing:0.08em;margin-right:0.25rem;">Tasarım:</span>
                                @foreach(['a' => 'Kompakt', 'b' => 'Donut', 'c' => 'Premium'] as $key => $lbl)
                                <button @click="ver = '{{ $key }}'"
                                        :class="ver === '{{ $key }}' ? 'bg-slate-800 text-white' : 'bg-slate-100 text-slate-500 hover:bg-slate-200'"
                                        style="font-family:'Inter',sans-serif;font-size:0.6rem;font-weight:700;padding:0.25rem 0.625rem;border-radius:9999px;border:none;cursor:pointer;transition:all 0.15s;text-transform:uppercase;letter-spacing:0.05em;">
                                    {{ $key === 'a' ? 'A' : ($key === 'b' ? 'B' : 'C') }} · {{ $lbl }}
                                </button>
                                @endforeach
                            </div>

                            <div x-show="ver === 'a'" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                <x-ilan.cortex-card-a :cortex-health="$cortexHealth" :cortex-analysis="$cortexAnalysis" :ilan="$ilan" />
                            </div>
                            <div x-show="ver === 'b'" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                <x-ilan.cortex-card-b :cortex-health="$cortexHealth" :cortex-analysis="$cortexAnalysis" :ilan="$ilan" />
                            </div>
                            <div x-show="ver === 'c'" x-transition:enter="transition-opacity duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                                <x-ilan.cortex-card-c :cortex-health="$cortexHealth" :cortex-analysis="$cortexAnalysis" :ilan="$ilan" />
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </main>

        {{-- ── Danışmanın Diğer İlanları ── --}}
        @if(isset($danismanDigerIlanlar) && $danismanDigerIlanlar->count() > 0)
            @php
                $danismanAdi = $ilan->danisman?->name ?? 'Danışman';
            @endphp
            <section style="background:#fff;padding:3.5rem 0;border-top:1px solid #e2e8f0;">
                <div style="max-width:1280px;margin:0 auto;padding:0 1.5rem;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:1.75rem;flex-wrap:wrap;gap:1rem;">
                        <div>
                            <p style="font-family:'Inter',sans-serif;font-size:0.7rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#004ac6;margin-bottom:0.35rem;">Aynı Danışmandan</p>
                            <h2 style="font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:800;color:#191b23;">{{ $danismanAdi }}'ın Diğer İlanları</h2>
                        </div>
                        <a href="{{ route('danisman.ilanlar', $ilan->danisman_id) }}"
                           style="font-family:'Inter',sans-serif;font-size:0.85rem;font-weight:600;color:#004ac6;text-decoration:none;">
                            Tüm İlanlarını Gör →
                        </a>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:1rem;">
                        @foreach($danismanDigerIlanlar as $dilan)
                            @php
                                $dilanFoto = $dilan->fotograflar->first();
                                $dilanImg  = $dilanFoto ? \Illuminate\Support\Facades\Storage::url($dilanFoto->dosya_yolu) : null;
                                $dilanIlce = is_object($dilan->relationLoaded('ilce') ? $dilan->getRelation('ilce') : null) ? $dilan->getRelation('ilce')->ilce_adi : null;
                            @endphp
                            <a href="{{ route('ilanlar.show', $dilan->id) }}"
                               style="background:#f8fafc;border-radius:0.75rem;overflow:hidden;border:1px solid #e2e8f0;text-decoration:none;color:inherit;display:flex;align-items:center;gap:0.875rem;padding:0.75rem;transition:box-shadow 0.2s,border-color 0.2s;"
                               onmouseover="this.style.boxShadow='0 4px 16px rgba(0,74,198,0.10)';this.style.borderColor='#bfdbfe'"
                               onmouseout="this.style.boxShadow='none';this.style.borderColor='#e2e8f0'">
                                {{-- Küçük fotoğraf --}}
                                <div style="width:72px;height:58px;border-radius:0.5rem;overflow:hidden;flex-shrink:0;background:linear-gradient(135deg,#dbeafe,#ededf9);">
                                    @if($dilanImg)
                                        <img src="{{ $dilanImg }}" style="width:100%;height:100%;object-fit:cover;" loading="lazy" alt="">
                                    @else
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                            <x-icon name="ev" class="w-7 h-7" style="color:#94a3b8;" />
                                        </div>
                                    @endif
                                </div>
                                {{-- Bilgi --}}
                                <div style="min-width:0;flex:1;">
                                    <p style="font-family:'Manrope',sans-serif;font-size:0.8rem;font-weight:700;color:#191b23;margin-bottom:0.2rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $dilan->baslik ?: 'İlan #'.$dilan->id }}</p>
                                    <p style="font-family:'Inter',sans-serif;font-size:0.7rem;color:#737686;">{{ $dilanIlce ?? 'Bodrum' }}</p>
                                    @if($dilan->fiyat)
                                        <p style="font-family:'Manrope',sans-serif;font-size:0.85rem;font-weight:800;color:#004ac6;margin-top:0.2rem;">{{ number_format($dilan->fiyat,0,',','.') }} {{ $dilan->para_birimi ?? '₺' }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        {{-- ── Benzer Mülkler ── --}}
        {{-- ── Lightbox overlay ── --}}
        @if($fotografSayisi > 0)
        <div id="yh-lightbox" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.93);align-items:center;justify-content:center;"
             onclick="if(event.target===this)closeLightbox()">
            <button onclick="closeLightbox()" aria-label="Kapat"
                    style="position:absolute;top:1.25rem;right:1.25rem;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);color:#fff;border-radius:50%;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:1.25rem;z-index:10;">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <button onclick="lbPrev()" aria-label="Önceki"
                    style="position:absolute;left:1rem;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);color:#fff;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;transition:background 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.12)'">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <img id="yh-lb-img" src="" alt="Fotoğraf"
                 style="max-width:90vw;max-height:88vh;object-fit:contain;border-radius:0.5rem;box-shadow:0 25px 60px rgba(0,0,0,0.6);">
            <button onclick="lbNext()" aria-label="Sonraki"
                    style="position:absolute;right:1rem;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);color:#fff;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;cursor:pointer;z-index:10;transition:background 0.2s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.25)'" onmouseout="this.style.background='rgba(255,255,255,0.12)'">
                <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </button>
            <span id="yh-lb-counter" style="position:absolute;bottom:1.25rem;left:50%;transform:translateX(-50%);font-family:'Inter',sans-serif;font-size:0.8rem;color:rgba(255,255,255,0.6);font-weight:500;"></span>
        </div>
        @endif

        @if(isset($similar) && $similar->count() > 0)
            <section style="background:#f3f4f6;padding:4rem 0;">
                <div style="max-width:1280px;margin:0 auto;padding:0 1.5rem;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
                        <div>
                            <p style="font-family:'Inter',sans-serif;font-size:0.7rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#004ac6;margin-bottom:0.4rem;">Seçkilerimiz</p>
                            <h2 style="font-family:'Manrope',sans-serif;font-size:1.75rem;font-weight:800;color:#191b23;letter-spacing:-0.01em;">Benzer Mülkler</h2>
                        </div>
                        <a href="{{ route('ilanlar.index') }}" style="font-family:'Inter',sans-serif;font-size:0.85rem;font-weight:600;color:#004ac6;text-decoration:none;">
                            Tüm İlanları Gör →
                        </a>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.25rem;">
                        @foreach($similar as $benzer)
                            @php
                                $benzerFoto = $benzer->fotograflar?->first();
                                $benzerImg = $benzerFoto ? \Illuminate\Support\Facades\Storage::url($benzerFoto->dosya_yolu) : null;
                                $benzerIlce = is_object($benzer->getRelation('ilce') ?? null) ? $benzer->getRelation('ilce')->ilce_adi : null;
                            @endphp
                            <a href="{{ route('ilanlar.show', $benzer->id) }}"
                               style="background:#fff;border-radius:0.75rem;overflow:hidden;border:1px solid #dde1f0;text-decoration:none;color:inherit;display:block;transition:box-shadow 0.3s,transform 0.3s;box-shadow:0 2px 8px rgba(0,26,110,0.05);"
                               onmouseover="this.style.boxShadow='0 12px 32px rgba(0,74,198,0.12)';this.style.transform='translateY(-3px)'"
                               onmouseout="this.style.boxShadow='0 2px 8px rgba(0,26,110,0.05)';this.style.transform='none'">
                                <div style="height:180px;overflow:hidden;background:linear-gradient(135deg,#dbeafe,#ededf9);">
                                    @if($benzerImg)
                                        <img src="{{ $benzerImg }}" style="width:100%;height:100%;object-fit:cover;" loading="lazy" alt="{{ $benzer->baslik }}">
                                    @else
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                                            <x-icon name="ev" class="w-12 h-12" style="color:#94a3b8;" />
                                        </div>
                                    @endif
                                </div>
                                <div style="padding:1rem 1.25rem;">
                                    <h3 style="font-family:'Manrope',sans-serif;font-size:0.95rem;font-weight:700;color:#191b23;margin-bottom:0.25rem;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $benzer->baslik }}</h3>
                                    <p style="font-family:'Inter',sans-serif;font-size:0.78rem;color:#737686;margin-bottom:0.625rem;">
                                        {{ $benzerIlce ?? 'Bodrum' }}
                                        @if($benzer->oda_sayisi) · {{ $benzer->oda_sayisi }} Oda @endif
                                    </p>
                                    <p style="font-family:'Manrope',sans-serif;font-size:1.1rem;font-weight:800;color:#004ac6;">
                                        {{ $benzer->fiyat ? number_format($benzer->fiyat, 0, ',', '.') . ' ' . ($benzer->para_birimi ?? '₺') : 'Fiyat Sorunuz' }}
                                    </p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>

@push('scripts')
<script>
// ── Lightbox ──────────────────────────────────────────
const _lb_imgs = @json($tumFotograflar->map(fn($f) => \Illuminate\Support\Facades\Storage::url($f->dosya_yolu)));
let _lb_idx = 0;

function openLightbox(idx) {
    if (!_lb_imgs.length) return;
    _lb_idx = idx;
    const lb = document.getElementById('yh-lightbox');
    document.getElementById('yh-lb-img').src = _lb_imgs[_lb_idx];
    document.getElementById('yh-lb-counter').textContent = (_lb_idx + 1) + ' / ' + _lb_imgs.length;
    lb.style.display = 'flex';
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    document.getElementById('yh-lightbox').style.display = 'none';
    document.body.style.overflow = '';
}
function lbPrev() { _lb_idx = (_lb_idx - 1 + _lb_imgs.length) % _lb_imgs.length; openLightbox(_lb_idx); }
function lbNext() { _lb_idx = (_lb_idx + 1) % _lb_imgs.length; openLightbox(_lb_idx); }
document.addEventListener('keydown', e => {
    if (document.getElementById('yh-lightbox').style.display === 'flex') {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') lbPrev();
        if (e.key === 'ArrowRight') lbNext();
    }
});

// ── Paylaş ────────────────────────────────────────────
function shareIlan() {
    const url = window.location.href;
    const title = {{ json_encode($ilan->baslik) }};
    if (navigator.share) {
        navigator.share({ title: title, url: url }).catch(() => {});
    } else {
        navigator.clipboard.writeText(url).then(function() {
            const btn = document.querySelector('[onclick="shareIlan()"]');
            const orig = btn.innerHTML;
            btn.innerHTML = '<svg width="13" height="13" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Kopyalandı!';
            btn.style.background = 'rgba(16,185,129,0.2)';
            setTimeout(() => { btn.innerHTML = orig; btn.style.background = 'rgba(255,255,255,0.15)'; }, 2000);
        });
    }
}
</script>
@endpush
@endsection
