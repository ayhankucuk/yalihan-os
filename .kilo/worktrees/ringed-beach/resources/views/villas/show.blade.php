@extends('layouts.frontend')

@section('title', $villa->baslik . ' - Yalıhan Emlak')
@section('description', Str::limit(strip_tags($villa->aciklama), 160))

@push('meta')
{{-- Open Graph / Facebook --}}
<meta property="og:type" content="website">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:title" content="{{ $villa->baslik }} - Yalıhan Emlak">
<meta property="og:description" content="{{ Str::limit(strip_tags($villa->aciklama), 160) }}">
@if($villa->featuredPhoto)
<meta property="og:image" content="{{ asset($villa->featuredPhoto->getImageUrl()) }}">
<meta property="og:image:width" content="{{ $villa->featuredPhoto->width ?? 1200 }}">
<meta property="og:image:height" content="{{ $villa->featuredPhoto->height ?? 630 }}">
@endif
<meta property="og:site_name" content="Yalıhan Emlak">
<meta property="og:locale" content="tr_TR">

{{-- Twitter --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="{{ url()->current() }}">
<meta name="twitter:title" content="{{ $villa->baslik }} - Yalıhan Emlak">
<meta name="twitter:description" content="{{ Str::limit(strip_tags($villa->aciklama), 160) }}">
@if($villa->featuredPhoto)
<meta name="twitter:image" content="{{ asset($villa->featuredPhoto->getImageUrl()) }}">
@endif

{{-- Additional Meta --}}
<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
<meta name="author" content="Yalıhan Emlak">
<link rel="canonical" href="{{ url()->current() }}">

{{-- Structured Data (JSON-LD) --}}
<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "Product",
  "name": "{{ $villa->baslik }}",
  "description": "{{ Str::limit(strip_tags($villa->aciklama), 500) }}",
  @if($villa->featuredPhoto)
  "image": "{{ asset($villa->featuredPhoto->getImageUrl()) }}",
  @endif
  "brand": {
    "@type": "Brand",
    "name": "Yalıhan Emlak"
  },
  "offers": {
    "@type": "Offer",
    "url": "{{ url()->current() }}",
    "priceCurrency": "TRY",
    "price": "{{ $villa->gunluk_fiyat ?? 0 }}",
    "priceSpecification": {
      "@type": "UnitPriceSpecification",
      "price": "{{ $villa->gunluk_fiyat ?? 0 }}",
      "priceCurrency": "TRY",
      "unitText": "NIGHT"
    },
    "availability": "https://schema.org/InStock",
    "seller": {
      "@type": "Organization",
      "name": "Yalıhan Emlak"
    }
  },
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "{{ $villa->ilce->ilce_adi ?? '' }}",
    "addressRegion": "{{ $villa->il->il_adi ?? '' }}",
    "addressCountry": "TR"
  }
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Place",
  "name": "{{ $villa->baslik }}",
  "description": "{{ Str::limit(strip_tags($villa->aciklama), 500) }}",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "{{ $villa->adres }}",
    "addressLocality": "{{ $villa->ilce->ilce_adi ?? '' }}",
    "addressRegion": "{{ $villa->il->il_adi ?? '' }}",
    "postalCode": "",
    "addressCountry": "TR"
  },
  @if($villa->maksimum_misafir)
  "maximumAttendeeCapacity": {{ $villa->maksimum_misafir }},
  @endif
  "amenityFeature": [
    @foreach($villa->features as $index => $feature)
    {
      "@type": "LocationFeatureSpecification",
      "name": "{{ $feature->label }}"
    }{{ $loop->last ? '' : ',' }}
    @endforeach
  ]
}
</script>

<script type="application/ld+json">
{
  "@context": "https://schema.org/",
  "@type": "BreadcrumbList",
  "itemListElement": [{
    "@type": "ListItem",
    "position": 1,
    "name": "Ana Sayfa",
    "item": "{{ url('/') }}"
  },{
    "@type": "ListItem",
    "position": 2,
    "name": "Yazlık Kirala",
    "item": "{{ route('villas.index') }}"
  },{
    "@type": "ListItem",
    "position": 3,
    "name": "{{ $villa->baslik }}"
  }]
}
</script>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-slate-900">

    {{-- Hero Banner --}}
    <section class="relative overflow-hidden" style="background: linear-gradient(135deg, #0A1628 0%, #162040 60%, #1a2a50 100%); min-height: 320px;">
        {{-- Gold accent line --}}
        <div class="absolute top-0 left-0 right-0 h-1" style="background: linear-gradient(90deg, transparent, #C9A84C, transparent);"></div>

        {{-- Background texture --}}
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: repeating-linear-gradient(45deg, #C9A84C 0, #C9A84C 1px, transparent 0, transparent 50%); background-size: 20px 20px;"></div>
        </div>

        <div class="relative z-10 container mx-auto px-4 lg:px-8 py-16 lg:py-20 flex flex-col justify-center" style="min-height: 320px;">
            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm mb-6" style="color: #C9A84C; opacity: 0.8;">
                <a href="/" class="hover:opacity-100 transition-opacity" style="color: #C9A84C;">Ana Sayfa</a>
                <span class="material-symbols-outlined" style="font-size:14px; color: #C9A84C;">chevron_right</span>
                <a href="{{ route('villas.index') }}" class="hover:opacity-100 transition-opacity" style="color: #C9A84C;">Yazlık Kirala</a>
                <span class="material-symbols-outlined" style="font-size:14px; color: #C9A84C;">chevron_right</span>
                <span style="color: rgba(249,246,241,0.7);">{{ Str::limit($villa->baslik, 50) }}</span>
            </nav>

            {{-- Title --}}
            <h1 class="text-3xl lg:text-5xl font-bold mb-4" style="color: #F8F6F1; line-height: 1.15;">
                {{ $villa->baslik }}
            </h1>

            {{-- Location + quick stats --}}
            <div class="flex flex-wrap items-center gap-6 mt-4">
                <div class="flex items-center gap-2" style="color: rgba(249,246,241,0.8);">
                    <span class="material-symbols-outlined" style="font-size:18px; color: #C9A84C;">location_on</span>
                    <span>{{ $villa->il->il_adi ?? 'Muğla' }}, {{ $villa->ilce->ilce_adi ?? 'Bodrum' }}</span>
                </div>
                @if($villa->maksimum_misafir)
                <div class="flex items-center gap-2" style="color: rgba(249,246,241,0.8);">
                    <span class="material-symbols-outlined" style="font-size:18px; color: #C9A84C;">group</span>
                    <span>{{ $villa->maksimum_misafir }} Kişi</span>
                </div>
                @endif
                @if($villa->oda_sayisi)
                <div class="flex items-center gap-2" style="color: rgba(249,246,241,0.8);">
                    <span class="material-symbols-outlined" style="font-size:18px; color: #C9A84C;">bed</span>
                    <span>{{ $villa->oda_sayisi }} Oda</span>
                </div>
                @endif
                @if($villa->gunluk_fiyat)
                <div class="flex items-center gap-2 ml-auto">
                    <span style="color: rgba(249,246,241,0.6); font-size:0.9rem;">Gecelik</span>
                    <span class="text-2xl font-bold" style="color: #C9A84C;">₺{{ number_format($villa->gunluk_fiyat) }}</span>
                </div>
                @endif
            </div>
        </div>

        {{-- Bottom wave --}}
        <div class="absolute bottom-0 left-0 right-0" style="height:40px; overflow:hidden;">
            <svg viewBox="0 0 1200 40" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" style="width:100%;height:100%;">
                <path d="M0,20 C300,40 900,0 1200,20 L1200,40 L0,40 Z" fill="#f9fafb"/>
            </svg>
        </div>
    </section>

    {{-- Photo Gallery Section --}}
    <section class="relative bg-white dark:bg-slate-900">
        @include('villas.components.photo-gallery', ['villa' => $villa])
    </section>

    {{-- Main Content --}}
    <section class="py-10">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="grid lg:grid-cols-3 gap-8">
                {{-- Left Content (2/3) --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Quick Stats Card --}}
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6 lg:p-8">
                        {{-- Quick Stats --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @if($villa->maksimum_misafir)
                            <div class="text-center p-4 rounded-xl" style="background: rgba(10,22,40,0.05);">
                                <span class="material-symbols-outlined mb-1" style="font-size:28px; color: #0A1628;">group</span>
                                <div class="text-lg font-bold" style="color: #0A1628;">{{ $villa->maksimum_misafir }}</div>
                                <div class="text-xs text-gray-500 mt-1">Kişi</div>
                            </div>
                            @endif
                            @if($villa->oda_sayisi)
                            <div class="text-center p-4 rounded-xl" style="background: rgba(201,168,76,0.08);">
                                <span class="material-symbols-outlined mb-1" style="font-size:28px; color: #C9A84C;">bed</span>
                                <div class="text-lg font-bold" style="color: #0A1628;">{{ $villa->oda_sayisi }}</div>
                                <div class="text-xs text-gray-500 mt-1">Oda</div>
                            </div>
                            @endif
                            @if($villa->banyo_sayisi)
                            <div class="text-center p-4 rounded-xl" style="background: rgba(10,22,40,0.05);">
                                <span class="material-symbols-outlined mb-1" style="font-size:28px; color: #0A1628;">bathtub</span>
                                <div class="text-lg font-bold" style="color: #0A1628;">{{ $villa->banyo_sayisi }}</div>
                                <div class="text-xs text-gray-500 mt-1">Banyo</div>
                            </div>
                            @endif
                            @if($villa->brut_metrekare)
                            <div class="text-center p-4 rounded-xl" style="background: rgba(201,168,76,0.08);">
                                <span class="material-symbols-outlined mb-1" style="font-size:28px; color: #C9A84C;">straighten</span>
                                <div class="text-lg font-bold" style="color: #0A1628;">{{ number_format($villa->brut_metrekare) }}</div>
                                <div class="text-xs text-gray-500 mt-1">m²</div>
                            </div>
                            @endif
                        </div>
                        {{-- Meta --}}
                        <div class="flex flex-wrap gap-4 mt-6 pt-6 border-t border-gray-100 dark:border-slate-700">
                            <span class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <span class="material-symbols-outlined mr-1.5" style="font-size:16px">visibility</span>
                                {{ number_format($villa->view_count) }} görüntüleme
                            </span>
                            <span class="flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <span class="material-symbols-outlined mr-1.5" style="font-size:16px">calendar_today</span>
                                {{ $villa->created_at->diffForHumans() }}
                            </span>
                        </div>
                    </div>

                    {{-- Description --}}
                    @if($villa->aciklama)
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6 lg:p-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
                            <span class="material-symbols-outlined" style="color: #C9A84C;">info</span>
                            Açıklama
                        </h2>
                        <div class="prose prose-lg dark:prose-invert max-w-none text-gray-700 dark:text-slate-200 dark:text-slate-300">
                            {!! nl2br(e($villa->aciklama)) !!}
                        </div>
                    </div>
                    @endif

                    {{-- Bedroom Layout --}}
                    @if($villa->bedroom_layout)
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6 lg:p-8">
                        @include('villas.components.bedroom-layout-display', ['bedrooms' => json_decode($villa->bedroom_layout, true)])
                    </div>
                    @endif

                    {{-- Features/Amenities --}}
                    @if($villa->features && $villa->features->count() > 0)
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6 lg:p-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2 dark:text-slate-100">
                            <span class="material-symbols-outlined" style="color: #C9A84C;">check_circle</span>
                            Özellikler ve Donanımlar
                        </h2>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($villa->features as $feature)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg">
                                <span class="material-symbols-outlined" style="font-size:20px; color: #C9A84C;">check</span>
                                <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $feature->label }}</span>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Location Map --}}
                    @if($villa->adres)
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6 lg:p-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
                            <span class="material-symbols-outlined" style="color: #C9A84C;">map</span>
                            Konum
                        </h2>
                        <p class="text-gray-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                            <span class="material-symbols-outlined" style="font-size:18px; color: #0A1628;">location_on</span>
                            {{ $villa->adres }}@if($villa->ilce), {{ $villa->ilce->ilce_adi }}@endif@if($villa->il), {{ $villa->il->il_adi }}@endif
                        </p>
                        @php
                            $mapQuery = urlencode(implode(', ', array_filter([
                                $villa->adres,
                                optional($villa->ilce)->ilce_adi,
                                optional($villa->il)->il_adi,
                                'Türkiye'
                            ])));
                        @endphp
                        <div class="rounded-xl overflow-hidden border border-gray-200 dark:border-slate-700" style="height:320px;">
                            <iframe
                                width="100%"
                                height="100%"
                                frameborder="0"
                                scrolling="no"
                                marginheight="0"
                                marginwidth="0"
                                loading="lazy"
                                src="https://www.openstreetmap.org/export/embed.html?bbox=27.0,36.9,27.7,37.2&layer=mapnik&marker={{ $mapQuery }}"
                                title="Villa konumu"
                            ></iframe>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 text-right">
                            <a href="https://www.openstreetmap.org/search?query={{ $mapQuery }}" target="_blank" rel="noopener" class="hover:underline">Büyük haritada aç</a>
                        </p>
                    </div>
                    @endif
                </div>

                {{-- Right Sidebar (1/3) - Booking Card --}}
                <div class="lg:col-span-1">
                    <div class="sticky top-24 space-y-6">
                        {{-- Booking Form Card --}}
                        @include('villas.components.booking-form', ['villa' => $villa, 'pricing' => $pricing])

                        {{-- Availability Calendar --}}
                        @if($availabilityCalendar)
                        <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6">
                            @include('villas.components.availability-calendar', ['calendar' => $availabilityCalendar])
                        </div>
                        @endif

                        {{-- Contact Info --}}
                        <div class="rounded-2xl shadow-lg p-6 text-white" style="background: linear-gradient(135deg, #0A1628 0%, #162040 100%); border-top: 3px solid #C9A84C;">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="material-symbols-outlined" style="color: #C9A84C; font-size:22px;">support_agent</span>
                                <h3 class="text-xl font-bold" style="color: #F8F6F1;">Sorularınız mı var?</h3>
                            </div>
                            <p class="text-sm mb-5" style="color: rgba(248,246,241,0.7);">Rezervasyon ve villa hakkında detaylı bilgi almak için bize ulaşın.</p>
                            <a href="tel:+905332090302" class="flex items-center justify-center w-full py-3 px-4 rounded-lg font-semibold transition-all hover:opacity-90" style="background: #C9A84C; color: #0A1628;">
                                <span class="material-symbols-outlined mr-2" style="font-size:18px">call</span>
                                0533 209 03 02
                            </a>
                            @if(\Illuminate\Support\Facades\Route::has('contact'))
                            <a href="{{ route('contact') }}" class="flex items-center justify-center w-full py-2.5 px-4 rounded-lg font-medium mt-3 transition-all border" style="border-color: rgba(201,168,76,0.4); color: #C9A84C; hover:background: rgba(201,168,76,0.1);">
                                <span class="material-symbols-outlined mr-2" style="font-size:18px">mail</span>
                                Mesaj Gönder
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- QR Code and Navigation --}}
            <div class="mt-12">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {{-- QR Code --}}
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">QR Kod</h3>
                        <x-qr-code-display :ilan="$villa" :size="'medium'" :showLabel="true" :showDownload="true" />
                    </div>

                    {{-- Navigation --}}
                    <div>
                        <x-listing-navigation :ilan="$villa" :mode="'default'" :showSimilar="false" />
                    </div>
                </div>
            </div>

            {{-- Similar Villas --}}
            @if($similarVillas && $similarVillas->count() > 0)
            <div class="mt-16">
                @include('villas.components.similar-villas', ['villas' => $similarVillas])
            </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
<script>
// Share functionality
function shareVilla() {
    if (navigator.share) {
        navigator.share({
            title: '{{ $villa->baslik }}',
            text: 'Bu villaya göz atın!',
            url: window.location.href
        });
    } else {
        // Fallback: Copy to clipboard
        navigator.clipboard.writeText(window.location.href);
        window.toast?.('Link kopyalandı!', 'success');
    }
}
</script>
@endpush
