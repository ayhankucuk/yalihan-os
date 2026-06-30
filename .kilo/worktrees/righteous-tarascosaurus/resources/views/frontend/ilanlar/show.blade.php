@extends('layouts.frontend')

@section('title', $ilan->baslik . ' - Yalıhan Premium')
@section('meta_description', Str::limit(strip_tags($ilan->aciklama), 160))

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
        <div class="relative h-[65vh] md:h-[75vh] w-full overflow-hidden bg-gray-900 group">
            @php
                // $mainImage controller'dan gelir (kapak_fotografi öncelikli).
                // Gelmemişse fallback.
                $mainImage ??= ($ilan->fotograflar->first()
                    ? \Illuminate\Support\Facades\Storage::url($ilan->fotograflar->first()->dosya_yolu)
                    : asset('images/default-property.jpg'));
            @endphp
            <img src="{{ $mainImage }}"
                class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-105"
                alt="{{ $ilan->baslik }}"
                loading="eager"
                decoding="async"
                fetchpriority="high">
            <div class="absolute inset-0 hero-gradient"></div>

            <!-- Floating Navigation Overlay -->
            <div class="absolute top-8 left-0 right-0 z-10">
                <div class="max-w-7xl mx-auto px-6 flex justify-between items-center text-white">
                    <a href="{{ route('ilanlar.index') }}"
                        class="flex items-center gap-2 premium-glass px-4 py-2 rounded-full text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white transition-all">
                        <x-icon name="sol-ok" class="w-4 h-4" /> İlanlara Dön
                    </a>
                    <div class="flex gap-3">
                        @include('components.favori-toggle', ['ilan' => $ilan])
                    </div>
                </div>
            </div>

            <!-- Title Overlay -->
            <div class="absolute bottom-12 left-0 right-0">
                <div class="max-w-7xl mx-auto px-6">
                    <div class="max-w-3xl">
                        <div class="flex gap-2 mb-4">
                            <span
                                class="px-3 py-1 bg-[var(--gold)] text-[var(--navy)] text-xs font-bold uppercase tracking-widest rounded">{{ $ilan->yayinTipi->yayin_tipi ?? 'İlan' }}</span>
                            <span
                                class="px-3 py-1 bg-slate-100/20 dark:bg-gray-800/60 backdrop-blur-md text-white dark:text-slate-200 text-xs font-bold uppercase tracking-widest rounded border border-slate-100/30 dark:border-gray-700/50">{{ $ilan->altKategori->name ?? 'Daire' }}</span>
                        </div>
                        <h1 class="text-4xl md:text-6xl font-extrabold text-white mb-4 leading-tight text-balanced">
                            {{ $ilan->baslik }}
                        </h1>
                        <div class="flex items-center text-gray-200 text-lg">
                            <x-icon name="konum" class="w-4 h-4 text-[var(--gold)] mr-2" />
                            {{ $ilan->il->il_adi ?? '' }} / {{ $ilan->ilce->ilce_adi ?? '' }} /
                            {{ $ilan->mahalle->mahalle_adi ?? '' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sticky Pricing & CTA Bar -->
        <div
            class="sticky top-20 z-40 w-full h-20 bg-white dark:bg-gray-950 backdrop-blur-md border-b border-gray-100 dark:border-slate-800 hidden md:block dark:bg-slate-900">
            <div class="max-w-7xl mx-auto px-6 h-full flex justify-between items-center">
                <div class="flex items-baseline gap-4">
                    @if ($ilan->fiyat_gosterim_metni)
                        <span class="text-3xl font-black text-gray-900 dark:text-white dark:text-slate-100">
                            {{ $ilan->fiyat_gosterim_metni }}
                        </span>
                    @endif
                    @if (($ilan->fiyat_gosterim_modu ?? 'exact') === 'exact' && $ilan->net_m2)
                        <span class="text-gray-500 dark:text-gray-400 font-medium">
                            {{ number_format($ilan->fiyat / $ilan->net_m2, 0, ',', '.') }} {{ $ilan->para_birimi }}/m²
                        </span>
                    @endif
                </div>
                <div class="flex gap-4 items-center">
                    <div class="flex gap-2 mr-4">
                        @if ($cortexHealth['overall_health'] >= 80)
                            <span
                                class="px-3 py-1 bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400 text-[10px] font-bold uppercase rounded-full border border-green-200 dark:border-green-800">Yüksek
                                Talep</span>
                        @endif
                        @if ($cortexAnalysis['roi_analizi']['is_high_yield'] ?? false)
                            <span
                                class="px-3 py-1 bg-[var(--gold-dim)] text-[var(--navy)] dark:text-[var(--gold)] text-[10px] font-bold uppercase rounded-full border border-[var(--gold)]/30">Hızlı
                                Amortisman</span>
                        @endif
                    </div>
                    <a href="#iletisim"
                        class="px-8 py-3 bg-[var(--navy)] hover:bg-[var(--navy-mid)] text-[var(--gold)] rounded-xl font-bold hover:scale-105 active:scale-95 transition-all shadow-xl">
                        İletişime Geç
                    </a>
                </div>
            </div>
        </div>

        <main class="max-w-7xl mx-auto px-6 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-16">

                <!-- Left Content -->
                <div class="lg:col-span-8 space-y-16">

                    <!-- Quick Specs: Sotheby's Grid -->
                    <section>
                        <h2
                            class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 mb-8 border-b border-gray-100 dark:border-gray-800/50 pb-4 dark:border-slate-800">
                            Temel Bilgiler</h2>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-y-10 gap-x-8">
                            <div class="space-y-1">
                                <p class="text-xs text-gray-400 dark:text-gray-500 uppercase">Alan</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $ilan->brut_m2 ?? $ilan->net_m2 }} m²</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs text-gray-400 dark:text-gray-500 uppercase">Yerleşim</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $ilan->oda_sayisi == 0 ? '-' : $ilan->oda_sayisi . ' Oda' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs text-gray-400 dark:text-gray-500 uppercase">Banyo</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $ilan->banyo_sayisi ?? '1' }}</p>
                            </div>
                            <div class="space-y-1">
                                <p class="text-xs text-gray-400 dark:text-gray-500 uppercase">Durum</p>
                                <p class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $ilan->bina_yasi == 0 ? 'Yeni' : $ilan->bina_yasi . ' Yaş' }}</p>
                            </div>
                        </div>
                    </section>

                    <!-- Investment Insights: Cortex ROI -->
                    <section>
                        <h2
                            class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 mb-8 border-b border-gray-100 dark:border-slate-800 pb-4">
                            Yatırım Analizi</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div
                                class="p-6 rounded-2xl bg-gray-50 dark:bg-slate-900 border border-gray-100 dark:border-slate-800 flex items-start gap-4">
                                <div
                                    class="w-12 h-12 rounded-xl bg-[var(--gold-dim)] flex items-center justify-center" style="color:var(--gold);"">
                                    <x-icon name="grafik" class="w-5 h-5" />
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
                                <div
                                    class="w-12 h-12 rounded-xl bg-[var(--gold-dim)] flex items-center justify-center" style="color:var(--gold);"">
                                    <x-icon name="grafik" class="w-5 h-5" />
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

                    <!-- Features: Airbnb Tile Style -->
                    @if ($ilan->features->count() > 0)
                        <section>
                            <h2
                                class="text-xs font-bold uppercase tracking-[0.2em] text-gray-400 dark:text-gray-500 mb-8 border-b border-gray-100 dark:border-slate-800 pb-4">
                                Sunulan Özellikler</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($ilan->features as $feature)
                                    <div class="flex items-center gap-4 group">
                                        <div
                                            class="w-10 h-10 rounded-full bg-gray-50 dark:bg-slate-900 flex items-center justify-center text-gray-600 dark:text-gray-400 group-hover:bg-[var(--navy)] group-hover:text-[var(--gold)] transition-all">
                                            <x-icon name="onay" class="w-4 h-4" />
                                        </div>
                                        <span
                                            class="text-lg text-gray-700 dark:text-slate-200 font-medium dark:text-slate-300">{{ $feature->name }}</span>
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

                </div>

                <!-- Right Sidebar -->
                <div class="lg:col-span-4 space-y-10">

                    <!-- Agent Card: Glassmorphism -->
                    <div id="iletisim" class="premium-glass p-8 rounded-[32px] shadow-2xl sticky top-48">
                        @if ($ilan->danisman)
                            <div class="text-center mb-8">
                                <div
                                    class="w-24 h-24 rounded-full overflow-hidden mx-auto mb-4 border-4 border-white dark:border-slate-800 shadow-lg">
                                    <img src="{{ $ilan->danisman->profile_photo_url ?? asset('images/default-agent.png') }}"
                                        class="w-full h-full object-cover"
                                        loading="lazy" decoding="async"
                                        alt="{{ $ilan->danisman->name ?? 'Danışman' }}">
                                </div>
                                <h3 class="text-2xl font-black text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $ilan->danisman->name }}</h3>
                                <p
                                    class="font-bold text-sm tracking-widest" style="color:var(--gold);" uppercase mt-1">
                                    {{ $ilan->danisman->baslik ?? 'Lüks Portföy Uzmanı' }}</p>
                            </div>
                        @endif

                        <div class="space-y-4">
                            <a href="tel:{{ $ilan->danisman->telefon ?? '+90 252 123 45 67' }}"
                                class="flex items-center justify-center gap-3 w-full py-4 bg-gray-900 border border-gray-800 dark:bg-white dark:border-gray-200 text-white dark:text-gray-900 rounded-2xl font-bold transition-transform active:scale-95">
                                <x-icon name="telefon" class="w-4 h-4" /> Hemen Ara
                            </a>
                            <a href="https://wa.me/{{ $ilan->danisman->whatsapp_numara ?? '902521234567' }}?text={{ urlencode($ilan->baslik . ' hakkında bilgi almak istiyorum.') }}"
                                class="flex items-center justify-center gap-3 w-full py-4 border-2 border-green-600 text-green-600 dark:text-green-400 rounded-2xl font-bold hover:bg-green-50 dark:hover:bg-green-900/10 transition-all">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M11.999 2C6.477 2 2 6.477 2 12c0 1.821.487 3.53 1.338 5L2 22l5.145-1.31A9.963 9.963 0 0 0 12 22c5.523 0 10-4.477 10-10S17.523 2 11.999 2z"/></svg> WhatsApp Mesajı
                            </a>
                        </div>

                        <!-- AI Suggestion Card -->
                        <div
                            class="mt-10 p-6 rounded-2xl text-white relative overflow-hidden group" style="background:linear-gradient(135deg,var(--navy),var(--navy-light));">
                            <div class="relative z-10">
                                <div class="flex items-center gap-3 mb-3">
                                    <span class="text-2xl">🤖</span>
                                    <h4 class="font-bold">Cortex Analizi</h4>
                                </div>
                                <p class="text-sm leading-relaxed mb-4 text-white/75">
                                    @php
                                        $marketScore = $cortexHealth['scores']['market']['score'] ?? 50;
                                        $qualityScore = $cortexHealth['scores']['quality']['score'] ?? 50;

                                        if ($marketScore >= 80) {
                                            $marketDesc =
                                                'Bu mülk piyasa verilerine göre oldukça rekabetçi bir fiyatla sunuluyor.';
                                        } elseif ($marketScore <= 40) {
                                            $marketDesc =
                                                'Bu mülk üst segment bir yatırım olup lüks detayları ile öne çıkıyor.';
                                        } else {
                                            $marketDesc = 'Bölge ortalaması ile tam uyumlu bir fiyatlandırma yapılmış.';
                                        }

                                        $roiYears = $cortexAnalysis['roi_analizi']['payback_period_years'] ?? 0;
                                        $roiDesc = $roiYears > 0 ? " Tahmini amortisman süresi $roiYears yıl." : '';
                                    @endphp
                                    "{{ $marketDesc }}{{ $roiDesc }}"
                                </p>
                                <a href="{{ route('ai.explore', ['id' => $ilan->id]) }}"
                                    class="inline-block text-xs font-bold border-b border-white hover:pb-1 transition-all uppercase tracking-widest">
                                    Detaylı Raporu Gör
                                </a>
                            </div>
                            {{-- decorative bolt background --}}
                            <span class="absolute bottom-[-20px] right-[-10px] w-32 h-32 text-white/10 group-hover:rotate-12 transition-transform duration-500 pointer-events-none">
                                <x-icon name="flas" class="w-full h-full" />
                            </span>
                        </div>
                    </div>

                </div>
            </div>
        </main>

        <!-- Similar Properties: Modern Gallery -->
        @if (isset($similar) && $similar->count() > 0)
            <section class="bg-gray-50 dark:bg-gray-900/50 py-24 dark:bg-slate-900">
                <div class="max-w-7xl mx-auto px-6">
                    <div class="flex justify-between items-end mb-12">
                        <div>
                            <p class="font-bold tracking-widest uppercase text-xs mb-2" style="color:var(--gold);"">
                                Seçkilerimiz</p>
                            <h2 class="text-4xl font-black text-gray-900 dark:text-white dark:text-slate-100">Benzer
                                Mülkler</h2>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                        @foreach ($similar as $benzer)
                            <a href="{{ route('ilanlar.show', $benzer->id) }}" class="group block space-y-4">
                                <div class="aspect-[4/3] rounded-3xl overflow-hidden bg-gray-200">
                                    <img src="{{ \Illuminate\Support\Facades\Storage::url($benzer->fotograflar->first()->dosya_yolu ?? '') }}"
                                        class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                                        loading="lazy" decoding="async"
                                        alt="{{ $benzer->baslik }}">
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-bold text-gray-900 dark:text-white group-hover:text-[var(--gold)] transition-colors dark:text-slate-100">
                                        {{ $benzer->baslik }}</h3>
                                    <p class="text-gray-500 text-sm">{{ $benzer->ilce->ilce_adi ?? '' }} /
                                        {{ $benzer->oda_sayisi == 0 ? '' : $benzer->oda_sayisi . ' Oda' }}</p>
                                    <p class="text-2xl font-black text-gray-900 dark:text-white mt-2 dark:text-slate-100">
                                        {{ number_format($benzer->fiyat, 0, ',', '.') }} {{ $benzer->para_birimi }}</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
{{-- ═══════════════════════════════════════════════════════════════
     JSON-LD Structured Data — RealEstateListing + BreadcrumbList
     Google Zengin Sonuç: fiyat, m², konum, danışman
     ═══════════════════════════════════════════════════════════════ --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "RealEstateListing",
    "name": "{{ addslashes($ilan->baslik) }}",
    "description": "{{ addslashes(Str::limit(strip_tags($ilan->aciklama ?? ''), 500)) }}",
    "url": "{{ route('ilanlar.show', $ilan->id) }}",
    @if($mainImage ?? false)
    "image": "{{ $mainImage }}",
    @endif
    "datePosted": "{{ $ilan->created_at?->toIso8601String() }}",
    "validThrough": "{{ $ilan->updated_at?->addMonths(3)->toIso8601String() }}",
    @if($ilan->fiyat && ($ilan->fiyat_gosterim_modu ?? 'exact') !== 'hidden')
    "offers": {
        "@type": "Offer",
        "price": "{{ $ilan->fiyat }}",
        "priceCurrency": "{{ $ilan->para_birimi ?? 'TRY' }}",
        "availability": "https://schema.org/InStock"
    },
    @endif
    "address": {
        "@type": "PostalAddress",
        "addressCountry": "TR",
        "addressRegion": "{{ $ilan->il->il_adi ?? 'Muğla' }}",
        "addressLocality": "{{ $ilan->ilce->ilce_adi ?? '' }}",
        "streetAddress": "{{ $ilan->mahalle->mahalle_adi ?? '' }}"
    },
    @if($ilan->lat && $ilan->lng)
    "geo": {
        "@type": "GeoCoordinates",
        "latitude": "{{ $ilan->lat }}",
        "longitude": "{{ $ilan->lng }}"
    },
    @endif
    @if($ilan->net_m2 || $ilan->brut_m2)
    "floorSize": {
        "@type": "QuantitativeValue",
        "value": "{{ $ilan->net_m2 ?? $ilan->brut_m2 }}",
        "unitCode": "MTK"
    },
    @endif
    @if($ilan->oda_sayisi)
    "numberOfRooms": "{{ $ilan->oda_sayisi }}",
    @endif
    "seller": {
        "@type": "RealEstateAgent",
        "name": "{{ addslashes($ilan->danisman->name ?? 'Yalıhan Emlak') }}",
        "telephone": "{{ $ilan->danisman->telefon ?? '+905332090302' }}",
        "url": "{{ url('/') }}"
    }
}
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "Ana Sayfa",
            "item": "{{ url('/') }}"
        },
        {
            "@type": "ListItem",
            "position": 2,
            "name": "İlanlar",
            "item": "{{ route('ilanlar.index') }}"
        },
        {
            "@type": "ListItem",
            "position": 3,
            "name": "{{ addslashes($ilan->baslik) }}",
            "item": "{{ route('ilanlar.show', $ilan->id) }}"
        }
    ]
}
</script>
@endpush
