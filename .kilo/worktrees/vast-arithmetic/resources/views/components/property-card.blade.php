@props(['ilan'])

@php
    $foto = $ilan->fotograflar?->sortBy('id')->first();
    $tipStr = strtolower($ilan->yayinTipi?->yayin_tipi ?? '');
    $paraBirimi = match(strtoupper($ilan->para_birimi ?? 'TRY')) {
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
        default => '₺',
    };
@endphp

<a href="{{ route('ilanlar.show', $ilan->id) }}" class="bg-white rounded-xl overflow-hidden shadow-sm hover:shadow-2xl transition-all duration-500 group block">
    <div class="relative h-64 overflow-hidden bg-surface-container-high">
        @if($foto)
            <img alt="{{ $ilan->baslik }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700" src="{{ Storage::url($foto->dosya_yolu) }}">
        @else
            <x-property-placeholder icon="villa" />
        @endif

        <div class="absolute top-4 left-4">
            @if($tipStr === 'kiralik')
                <span class="bg-status-rent text-white px-3 py-1 rounded-full text-[10px] uppercase font-bold">Kiralık</span>
            @else
                <span class="bg-status-sale text-white px-3 py-1 rounded-full text-[10px] uppercase font-bold">Satılık</span>
            @endif
        </div>

        @if($ilan->fiyat)
            <div class="absolute bottom-4 right-4 bg-white/90 backdrop-blur-sm px-4 py-2 rounded-lg font-bold text-primary">
                {{ number_format($ilan->fiyat, 0, ',', '.') }} {{ $paraBirimi }}
            </div>
        @endif
    </div>

    <div class="p-6">
        <h3 class="font-headline-sm text-headline-sm mb-2 line-clamp-1">{{ $ilan->baslik ?: 'İlan #'.$ilan->id }}</h3>
        <p class="text-on-surface-variant flex items-center gap-1 mb-4 text-sm">
            <span class="material-symbols-outlined text-sm">location_on</span>
            {{ $ilan->ilce?->ilce_adi ?? $ilan->il?->il_adi ?? 'Bodrum' }}
        </p>

        <div class="flex justify-between py-4 border-t border-slate-100 text-on-surface-variant text-sm">
            @if($ilan->oda_sayisi)
                <div class="flex items-center gap-1"><span class="material-symbols-outlined text-outline text-sm">bed</span><span>{{ $ilan->oda_sayisi }} Oda</span></div>
            @endif
            @if($ilan->net_m2)
                <div class="flex items-center gap-1"><span class="material-symbols-outlined text-outline text-sm">square_foot</span><span>{{ number_format($ilan->net_m2, 0) }} m²</span></div>
            @endif
        </div>
    </div>
</a>
