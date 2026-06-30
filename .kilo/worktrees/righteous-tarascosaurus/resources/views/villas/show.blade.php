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
    {{-- Photo Gallery Section --}}
    <section class="relative bg-white dark:bg-slate-900">
        @include('villas.components.photo-gallery', ['villa' => $villa])
    </section>

    {{-- Main Content --}}
    <section class="public-section">
        <div class="container mx-auto px-4 lg:px-8">
            <div class="grid lg:grid-cols-3 gap-8 -mt-12 relative z-10">
                {{-- Left Content (2/3) --}}
                <div class="lg:col-span-2 space-y-6">
                    {{-- Title & Quick Info Card --}}
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6 lg:p-8">
                        {{-- Breadcrumb --}}
                        <nav class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-4">
                            <a href="/" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Ana Sayfa</a>
                            <i class="fas fa-chevron-right text-xs"></i>
                            <a href="{{ route('villas.index') }}" class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors">Yazlık Kirala</a>
                            <i class="fas fa-chevron-right text-xs"></i>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $villa->baslik }}</span>
                        </nav>

                        {{-- Title --}}
                        <h1 class="text-3xl lg:text-4xl font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                            {{ $villa->baslik }}
                        </h1>

                        {{-- Location & Stats --}}
                        <div class="flex flex-wrap items-center gap-4 mb-6">
                            <div class="flex items-center text-gray-600 dark:text-gray-400">
                                <i class="fas fa-map-marker-alt mr-2 text-blue-600 dark:text-blue-400"></i>
                                <span>{{ $villa->il->il_adi }}, {{ $villa->ilce->ilce_adi }}</span>
                            </div>
                            <div class="flex items-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-eye mr-2"></i>
                                <span>{{ number_format($villa->view_count) }} görüntüleme</span>
                            </div>
                            <div class="flex items-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-calendar mr-2"></i>
                                <span>{{ $villa->created_at->diffForHumans() }}</span>
                            </div>
                        </div>

                        {{-- Quick Stats --}}
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            @if($villa->maksimum_misafir)
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $villa->maksimum_misafir }} Kişi</div>
                            </div>
                            @endif

                            @if($villa->oda_sayisi)
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                                    <i class="fas fa-bed"></i>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $villa->oda_sayisi }} Oda</div>
                            </div>
                            @endif

                            @if($villa->banyo_sayisi)
                            <div class="text-center">
                                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                                    <i class="fas fa-bath"></i>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ $villa->banyo_sayisi }} Banyo</div>
                            </div>
                            @endif

                            @if($villa->brut_metrekare)
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                    <i class="fas fa-ruler-combined"></i>
                                </div>
                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ number_format($villa->brut_metrekare) }} m²</div>
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Description --}}
                    @if($villa->aciklama)
                    <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg p-6 lg:p-8">
                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
                            <i class="fas fa-info-circle text-blue-600 dark:text-blue-400"></i>
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
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                            Özellikler ve Donanımlar
                        </h2>
                        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($villa->features as $feature)
                            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-900 rounded-lg">
                                <i class="fas fa-check text-green-500 text-lg"></i>
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
                            <i class="fas fa-map-marked-alt text-red-600 dark:text-red-400"></i>
                            Konum
                        </h2>
                        <p class="text-gray-700 dark:text-slate-200 mb-4 dark:text-slate-300">
                            <i class="fas fa-map-marker-alt text-blue-600 mr-2"></i>
                            {{ $villa->adres }}
                        </p>
                        <div class="aspect-video bg-gray-100 dark:bg-slate-900 rounded-lg flex items-center justify-center">
                            <div class="text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-map text-4xl mb-2"></i>
                                <p>Harita entegrasyonu (Google Maps API)</p>
                            </div>
                        </div>
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
                        <div class="bg-gradient-to-br from-blue-600 to-purple-600 rounded-2xl shadow-lg p-6 text-white">
                            <h3 class="text-xl font-bold mb-4">Sorularınız mı var?</h3>
                            <p class="text-blue-100 mb-4 text-sm">Rezervasyon ve villa hakkında detaylı bilgi almak için bize ulaşın.</p>
                            <a href="tel:+905332090302" class="block w-full bg-white text-blue-600 text-center py-3 px-4 rounded-lg font-semibold hover:bg-blue-50 transition-colors dark:bg-slate-900">
                                <i class="fas fa-phone mr-2"></i>
                                0533 209 03 02
                            </a>
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
