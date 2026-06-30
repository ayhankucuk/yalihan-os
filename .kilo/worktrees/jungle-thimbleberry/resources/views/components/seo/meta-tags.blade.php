@props([
    'title' => 'Emlak Pro - Profesyonel Emlak Yönetim Sistemi',
    'description' =>
        'Modern ve kullanıcı dostu emlak yönetim sistemi. İlan yönetimi, müşteri takibi, satış analizi ve daha fazlası.',
    'keywords' => 'emlak, yönetim, sistem, ilan, müşteri, satış, analiz, rapor',
    'image' => '/images/og-image.jpg',
    'url' => null,
    'type' => 'website',
    'author' => 'Emlak Pro',
    'publishedTime' => null,
    'modifiedTime' => null,
    'section' => null,
    'tags' => [],
    'noindex' => false,
    'canonical' => null,
])

@php
    $currentUrl = $url ?? request()->url();
    $canonicalUrl = $canonical ?? $currentUrl;
    $fullImageUrl = $image ? (str_starts_with($image, 'http') ? $image : url($image)) : url('/images/og-image.jpg');
    $publishedTime = $publishedTime ?? now()->toISOString();
    $modifiedTime = $modifiedTime ?? now()->toISOString();
@endphp

<!-- Primary Meta Tags -->
<title>{{ $title }}</title>
<meta name="title" content="{{ $title }}">
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<meta name="author" content="{{ $author }}">

@if ($noindex)
    <meta name="robots" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow">
@endif

<!-- Canonical URL -->
<link rel="canonical" href="{{ $canonicalUrl }}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="{{ $type }}">
<meta property="og:url" content="{{ $currentUrl }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:image" content="{{ $fullImageUrl }}">
<meta property="og:site_name" content="Emlak Pro">
<meta property="og:locale" content="tr_TR">

@if ($publishedTime)
    <meta property="article:published_time" content="{{ $publishedTime }}">
@endif

@if ($modifiedTime)
    <meta property="article:modified_time" content="{{ $modifiedTime }}">
@endif

@if ($section)
    <meta property="article:section" content="{{ $section }}">
@endif

@if (!empty($tags))
    @foreach ($tags as $tag)
        <meta property="article:tag" content="{{ $tag }}">
    @endforeach
@endif

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="{{ $currentUrl }}">
<meta property="twitter:title" content="{{ $title }}">
<meta property="twitter:description" content="{{ $description }}">
<meta property="twitter:image" content="{{ $fullImageUrl }}">
<meta property="twitter:creator" content="@emlakpro">
<meta property="twitter:site" content="@emlakpro">

<!-- Additional Meta Tags -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#3b82f6">
<meta name="msapplication-TileColor" content="#3b82f6">
<meta name="msapplication-config" content="/browserconfig.xml">

<!-- Language -->
<meta name="language" content="Turkish">
<meta name="geo.region" content="TR">
<meta name="geo.country" content="Turkey">

<!-- Mobile App -->
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Emlak Pro">

<!-- Icons -->
<link rel="icon" type="image/x-icon" href="/favicon.ico">
<link rel="icon" type="image/png" sizes="32x32" href="/icons/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/icons/favicon-16x16.png">
<link rel="apple-touch-icon" sizes="180x180" href="/icons/apple-touch-icon.png">
<link rel="manifest" href="/manifest.json">

<!-- Preconnect to external domains -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net">

<!-- DNS Prefetch -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//fonts.gstatic.com">
<link rel="dns-prefetch" href="//cdn.jsdelivr.net">

<!-- Structured Data -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebApplication",
  "name": "Emlak Pro",
  "description": "{{ $description }}",
  "url": "{{ $currentUrl }}",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web Browser",
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "TRY"
  },
  "author": {
    "@type": "Organization",
    "name": "{{ $author }}"
  },
  "publisher": {
    "@type": "Organization",
    "name": "{{ $author }}",
    "logo": {
      "@type": "ImageObject",
      "url": "{{ url('/images/logo.png') }}"
    }
  },
  "datePublished": "{{ $publishedTime }}",
  "dateModified": "{{ $modifiedTime }}",
  "inLanguage": "tr-TR",
  "isAccessibleForFree": true,
  "browserRequirements": "Requires JavaScript. Requires HTML5.",
  "softwareVersion": "1.0.0",
  "screenshot": "{{ $fullImageUrl }}"
}
</script>

<!-- Additional SEO Meta Tags -->
<meta name="format-detection" content="telephone=no">
<meta name="format-detection" content="date=no">
<meta name="format-detection" content="address=no">
<meta name="format-detection" content="email=no">

<!-- Security -->
<meta http-equiv="X-Content-Type-Options" content="nosniff">
<meta http-equiv="X-Frame-Options" content="DENY">
<meta http-equiv="X-XSS-Protection" content="1; mode=block">
<meta http-equiv="Referrer-Policy" content="strict-origin-when-cross-origin">

<!-- Performance -->
<meta http-equiv="Cache-Control" content="public, max-age=31536000">
<meta http-equiv="Expires" content="31536000">

<!-- PWA Meta Tags -->
<meta name="application-name" content="Emlak Pro">
<meta name="apple-mobile-web-app-title" content="Emlak Pro">
<meta name="msapplication-tooltip" content="Emlak Pro Admin Panel">
<meta name="msapplication-starturl" content="/admin">
<meta name="msapplication-tap-highlight" content="no">

<!-- Additional Open Graph Tags -->
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="{{ $title }}">
<meta property="og:image:type" content="image/jpeg">

<!-- Additional Twitter Tags -->
<meta name="twitter:image:alt" content="{{ $title }}">
<meta name="twitter:domain" content="{{ parse_url($currentUrl, PHP_URL_HOST) }}">
<meta name="twitter:url" content="{{ $currentUrl }}">

<!-- Custom Meta Tags -->
<meta name="emlak-pro:version" content="1.0.0">
<meta name="emlak-pro:build" content="{{ config('app.version', '1.0.0') }}">
<meta name="emlak-pro:environment" content="{{ config('app.env', 'production') }}">
