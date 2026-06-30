@props([
    'property',
    'currency' => 'USD',
    'convertedPrice' => null,
])

@php
    $photoCollection = collect(data_get($property, 'ilanFotograflari', []));
    if ($photoCollection->isEmpty()) {
        $photoCollection = collect(data_get($property, 'fotograflar', []));
    }

    $photoUrl = data_get($photoCollection->first(), 'dosya_yolu');
    
    // Currency conversion - check both prop and property attribute
    $convertedPriceData = $convertedPrice ?? data_get($property, 'converted_price');
    $currencyLabel = null;
    if ($convertedPriceData && is_array($convertedPriceData)) {
        $currencyLabel = $convertedPriceData['formatted'] ?? null;
    }
    $countryName = data_get($property, 'il.il_adi') ?? data_get($property, 'country_name', 'Konum');
    $cityName = data_get($property, 'ilce.ilce_adi') ?? data_get($property, 'city_name');
    $fullLocation = trim($countryName . ($cityName ? ' / ' . $cityName : ''));
    $bedrooms = data_get($property, 'oda_sayisi') ?? data_get($property, 'bedrooms');
    $bathrooms = data_get($property, 'banyo_sayisi') ?? data_get($property, 'bathrooms');
    $squareMeters = data_get($property, 'metrekare') ?? data_get($property, 'square_meters');
    $description = \Illuminate\Support\Str::limit(data_get($property, 'aciklama', ''), 120);
    $tags = data_get($property, 'etiketler', collect());
@endphp

<article class="group bg-white dark:bg-slate-900 rounded-3xl shadow-md border border-gray-200 dark:border-slate-800 overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl dark:shadow-none dark:border-slate-700">
    <div class="relative h-56 overflow-hidden">
        @if ($photoUrl)
            <img src="{{ Storage::url($photoUrl) }}" alt="{{ $property->baslik }}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
        @else
            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-800 dark:to-gray-700 text-gray-500 dark:text-gray-400 text-4xl">
                <i class="fas fa-home"></i>
            </div>
        @endif

        <div class="absolute top-4 left-4 flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1 rounded-full bg-white/90 dark:bg-gray-900/90 px-3 py-1 text-xs font-semibold text-gray-800 dark:text-slate-100 shadow-md dark:shadow-none dark:text-slate-200 dark:bg-slate-900/90">
                <i class="fas fa-map-marker-alt text-blue-500"></i>
                {{ $fullLocation ?: 'Konum' }}
            </span>
            @if (data_get($property, 'citizenship_eligible'))
                <span class="inline-flex items-center gap-1 rounded-full bg-green-500/90 text-white px-3 py-1 text-xs font-semibold shadow-md dark:shadow-none">
                    <i class="fas fa-passport"></i>
                    Vatandaşlık Uygun
                </span>
            @endif
        </div>

        <button type="button" class="absolute top-4 right-4 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/90 dark:bg-gray-900/90 text-gray-600 dark:text-slate-200 hover:text-red-500 hover:scale-105 transition-all duration-200 dark:bg-slate-900/90" aria-label="Favorilere ekle">
            <i class="far fa-heart"></i>
        </button>
    </div>

    <div class="p-6 space-y-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white leading-tight line-clamp-2 dark:text-slate-100">
                    {{ $property->baslik }}
                </h3>
                <p class="mt-2 text-sm text-gray-600 dark:text-slate-200 line-clamp-2">
                    {{ $description }}
                </p>
            </div>
            <div class="flex flex-col items-end gap-1 text-right">
                <span class="text-xl font-bold text-blue-600 dark:text-blue-400">
                    {{ number_format($property->fiyat, 0, ',', '.') }}
                    <span class="text-sm font-medium">{{ $property->para_birimi }}</span>
                </span>
                @if ($currencyLabel)
                    <span class="text-xs text-gray-500 dark:text-gray-400">≈ {{ $currencyLabel }} ({{ $currency }})</span>
                @endif
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-slate-200">
            @if ($bedrooms)
                <span class="inline-flex items-center gap-1"><i class="fas fa-bed"></i>{{ $bedrooms }} Oda</span>
            @endif
            @if ($bathrooms)
                <span class="inline-flex items-center gap-1"><i class="fas fa-bath"></i>{{ $bathrooms }} Banyo</span>
            @endif
            @if ($squareMeters)
                <span class="inline-flex items-center gap-1"><i class="fas fa-ruler-combined"></i>{{ $squareMeters }} m²</span>
            @endif
        </div>

        <div class="flex flex-wrap gap-2">
            @foreach (collect($tags)->take(3) as $etiket)
                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 px-3 py-1 text-xs font-semibold">
                    <i class="fas fa-tag"></i>
                    {{ $etiket->name ?? $etiket }}
                </span>
            @endforeach
        </div>

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-3 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <a href="{{ route('ilanlar.show', $property->id) }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 dark:bg-blue-500 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition-all duration-200 hover:bg-blue-700 dark:hover:bg-blue-600 hover:shadow-lg active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:shadow-none">
                Detayları Gör
                <i class="fas fa-arrow-right text-xs"></i>
            </a>

            <button type="button" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 dark:border-slate-800 px-5 py-2.5 text-sm font-semibold text-gray-700 dark:text-slate-200 transition-all duration-200 hover:bg-gray-100 dark:hover:bg-gray-800 active:scale-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:text-slate-300 dark:border-slate-700" data-ai-analyze="{{ $property->id }}">
                <i class="fas fa-robot"></i>
                AI ile Analiz Et
            </button>
        </div>
    </div>
</article>

