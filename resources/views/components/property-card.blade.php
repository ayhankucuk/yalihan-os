@props(['ilan' => null])

@php
    $foto = $ilan?->fotograflar?->sortBy('display_order')->first() ?? $ilan?->fotograflar?->sortBy('id')->first();
    $tipStr = strtolower($ilan?->yayinTipi?->yayin_tipi ?? '');
    $paraBirimi = match(strtoupper($ilan?->para_birimi ?? 'TRY')) {
        'EUR' => '€',
        'USD' => '$',
        'GBP' => '£',
        default => '₺',
    };
    $ilceAdi = $ilan?->ilce?->ilce_adi ?? $ilan?->il?->il_adi ?? 'Bodrum';
@endphp

<a href="{{ $ilan ? route('ilanlar.show', $ilan->id) : '#' }}" class="bg-white rounded-xl overflow-hidden shadow-[0_4px_20px_-2px_rgba(10,22,40,0.06)] hover:shadow-[0_12px_32px_-4px_rgba(10,22,40,0.12)] transition-all duration-300 group block border border-[#E8E2D8]">
    {{-- 4:3 Aspect Ratio Master Container --}}
    <div class="relative aspect-[4/3] overflow-hidden bg-slate-100">
        @if($foto)
            <img alt="{{ $ilan?->baslik }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="{{ Storage::url($foto->dosya_yolu) }}" loading="lazy">
        @else
            <x-property-placeholder icon="villa" />
        @endif

        {{-- Status Badge --}}
        <div class="absolute top-3 left-3">
            @if($tipStr === 'kiralik')
                <span class="bg-[#B45309] text-white px-3 py-1 rounded-full text-[11px] font-semibold tracking-wide uppercase shadow-sm">Kiralık</span>
            @else
                <span class="bg-[#15803D] text-white px-3 py-1 rounded-full text-[11px] font-semibold tracking-wide uppercase shadow-sm">Satılık</span>
            @endif
        </div>

        {{-- Price Badge --}}
        @if($ilan?->fiyat)
            <div class="absolute bottom-3 right-3 bg-[#0A1628]/90 text-white backdrop-blur-md px-3.5 py-1.5 rounded-lg font-bold text-sm tracking-tight border border-[#C9A84C]/30 shadow-md">
                {{ number_format($ilan->fiyat, 0, ',', '.') }} {{ $paraBirimi }}
            </div>
        @endif
    </div>

    {{-- Body Content --}}
    <div class="p-5 flex flex-col justify-between">
        <div>
            <h3 class="font-sans text-base font-semibold text-[#0A1628] mb-1.5 line-clamp-1 group-hover:text-[#C9A84C] transition-colors">
                {{ $ilan?->baslik ?: 'İlan #'.($ilan?->id ?? '') }}
            </h3>
            <p class="text-[#6B7280] flex items-center gap-1.5 mb-4 text-xs font-medium">
                <x-icon name="konum" class="w-3.5 h-3.5 text-[#C9A84C] flex-shrink-0" />
                <span>{{ $ilceAdi }}</span>
            </p>
        </div>

        {{-- Metrics Footer --}}
        <div class="flex items-center justify-between pt-3 border-t border-[#E8E2D8] text-xs text-[#434655] font-meta">
            @if($ilan?->oda_sayisi)
                <div class="flex items-center gap-1">
                    <x-icon name="ev" class="w-3.5 h-3.5 text-[#6B7280]" />
                    <span>{{ $ilan->oda_sayisi }} Oda</span>
                </div>
            @endif
            @if($ilan?->net_m2)
                <div class="flex items-center gap-1">
                    <x-icon name="m2" class="w-3.5 h-3.5 text-[#6B7280]" />
                    <span>{{ number_format($ilan->net_m2, 0) }} m²</span>
                </div>
            @endif
            <div class="text-[#0A1628] font-semibold hover:underline flex items-center gap-0.5">
                <span>Detay</span>
                <x-icon name="sag-chevron" class="w-3 h-3 text-[#C9A84C]" />
            </div>
        </div>
    </div>
</a>

