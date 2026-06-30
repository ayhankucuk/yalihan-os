@props([
    'type' => 'WebApplication',
    'data' => [],
])

@php
    $structuredData = array_merge(
        [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'name' => 'Emlak Pro',
            'description' => 'Profesyonel emlak yönetim sistemi',
            'url' => request()->url(),
            'applicationCategory' => 'BusinessApplication',
            'operatingSystem' => 'Web Browser',
            'inLanguage' => 'tr-TR',
            'isAccessibleForFree' => true,
            'browserRequirements' => 'Requires JavaScript. Requires HTML5.',
            'softwareVersion' => '1.0.0',
            'datePublished' => now()->toISOString(),
            'dateModified' => now()->toISOString(),
            'author' => [
                '@type' => 'Organization',
                'name' => 'Emlak Pro',
                'url' => url('/'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Emlak Pro',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => url('/images/logo.png'),
                    'width' => 200,
                    'height' => 200,
                ],
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'TRY',
                'availability' => 'https://schema.org/InStock',
            ],
        ],
        $data,
    );
@endphp

<script type="application/ld+json">
{!! json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
</script>

<!-- BreadcrumbList -->
@if (isset($breadcrumbs) && is_array($breadcrumbs))
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    @foreach($breadcrumbs as $index => $breadcrumb)
    {
      "@type": "ListItem",
      "position": {{ $index + 1 }},
      "name": "{{ $breadcrumb['name'] }}",
      "item": "{{ $breadcrumb['url'] }}"
    }{{ $index < count($breadcrumbs) - 1 ? ',' : '' }}
    @endforeach
  ]
}
</script>
@endif

<!-- Organization -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "Emlak Pro",
  "url": "{{ url('/') }}",
  "logo": "{{ url('/images/logo.png') }}",
  "description": "Profesyonel emlak yönetim sistemi",
  "foundingDate": "2025",
  "address": {
    "@type": "PostalAddress",
    "addressCountry": "TR",
    "addressLocality": "İstanbul"
  },
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+90-212-000-0000",
    "contactType": "customer service",
    "availableLanguage": "Turkish"
  },
  "sameAs": [
    "https://twitter.com/emlakpro",
    "https://linkedin.com/company/emlakpro",
    "https://facebook.com/emlakpro"
  ]
}
</script>

<!-- WebSite -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "name": "Emlak Pro",
  "url": "{{ url('/') }}",
  "description": "Profesyonel emlak yönetim sistemi",
  "inLanguage": "tr-TR",
  "copyrightYear": "2025",
  "publisher": {
    "@type": "Organization",
    "name": "Emlak Pro"
  },
  "potentialAction": {
    "@type": "SearchAction",
    "target": {
      "@type": "EntryPoint",
      "urlTemplate": "{{ url('/admin/ilanlar?search={search_term_string}') }}"
    },
    "query-input": "required name=search_term_string"
  }
}
</script>

<!-- SoftwareApplication -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Emlak Pro",
  "description": "Modern ve kullanıcı dostu emlak yönetim sistemi",
  "url": "{{ url('/') }}",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web Browser",
  "softwareVersion": "1.0.0",
  "datePublished": "{{ now()->toISOString() }}",
  "dateModified": "{{ now()->toISOString() }}",
  "author": {
    "@type": "Organization",
    "name": "Emlak Pro"
  },
  "offers": {
    "@type": "Offer",
    "price": "0",
    "priceCurrency": "TRY",
    "availability": "https://schema.org/InStock"
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "ratingCount": "150",
    "bestRating": "5",
    "worstRating": "1"
  },
  "featureList": [
    "İlan Yönetimi",
    "Müşteri Takibi",
    "Satış Analizi",
    "Raporlama",
    "Mobil Uyumlu",
    "Gerçek Zamanlı Bildirimler"
  ],
  "screenshot": "{{ url('/images/screenshot.png') }}",
  "downloadUrl": "{{ url('/manifest.json') }}",
  "installUrl": "{{ url('/admin') }}",
  "browserRequirements": "Requires JavaScript. Requires HTML5.",
  "memoryRequirements": "512MB",
  "storageRequirements": "100MB",
  "processorRequirements": "Any modern processor",
  "permissions": "Camera, Location, Notifications"
}
</script>

<!-- FAQPage -->
@if (isset($faqs) && is_array($faqs))
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    @foreach($faqs as $index => $faq)
    {
      "@type": "Question",
      "name": "{{ $faq['question'] }}",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "{{ $faq['answer'] }}"
      }
    }{{ $index < count($faqs) - 1 ? ',' : '' }}
    @endforeach
  ]
}
</script>
@endif

<!-- LocalBusiness -->
@if (isset($localBusiness) && is_array($localBusiness))
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "{{ $localBusiness['name'] ?? 'Emlak Pro' }}",
  "description": "{{ $localBusiness['description'] ?? 'Profesyonel emlak yönetim sistemi' }}",
  "url": "{{ $localBusiness['url'] ?? url('/') }}",
  "telephone": "{{ $localBusiness['telephone'] ?? '+90-212-000-0000' }}",
  "email": "{{ $localBusiness['email'] ?? 'info@emlakpro.com' }}",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "{{ $localBusiness['streetAddress'] ?? 'Örnek Mahallesi' }}",
    "addressLocality": "{{ $localBusiness['addressLocality'] ?? 'İstanbul' }}",
    "addressRegion": "{{ $localBusiness['addressRegion'] ?? 'İstanbul' }}",
    "postalCode": "{{ $localBusiness['postalCode'] ?? '34000' }}",
    "addressCountry": "{{ $localBusiness['addressCountry'] ?? 'TR' }}"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "{{ $localBusiness['latitude'] ?? '41.0082' }}",
    "longitude": "{{ $localBusiness['longitude'] ?? '28.9784' }}"
  },
  "openingHours": "{{ $localBusiness['openingHours'] ?? 'Mo-Fr 09:00-18:00' }}",
  "priceRange": "{{ $localBusiness['priceRange'] ?? '$$' }}",
  "paymentAccepted": "{{ $localBusiness['paymentAccepted'] ?? 'Cash, Credit Card' }}",
  "currenciesAccepted": "{{ $localBusiness['currenciesAccepted'] ?? 'TRY' }}"
}
</script>
@endif

<!-- Product -->
@if (isset($product) && is_array($product))
    <script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "{{ $product['name'] }}",
  "description": "{{ $product['description'] }}",
  "image": "{{ $product['image'] }}",
  "brand": {
    "@type": "Brand",
    "name": "Emlak Pro"
  },
  "offers": {
    "@type": "Offer",
    "price": "{{ $product['price'] ?? '0' }}",
    "priceCurrency": "{{ $product['currency'] ?? 'TRY' }}",
    "availability": "https://schema.org/InStock",
    "seller": {
      "@type": "Organization",
      "name": "Emlak Pro"
    }
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "{{ $product['rating'] ?? '4.8' }}",
    "ratingCount": "{{ $product['ratingCount'] ?? '150' }}"
  }
}
</script>
@endif
