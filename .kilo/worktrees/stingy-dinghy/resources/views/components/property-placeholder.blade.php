@props(['icon' => 'villa'])

{{-- Premium markalı görsel placeholder — fotoğraf yokken kurumsal görünüm sağlar.
     Dış servis bağımlılığı yok; saf CSS gradient + inline SVG (SAB: Unsplash deprecated kuralı). --}}
<div {{ $attributes->merge(['class' => 'w-full h-full relative overflow-hidden']) }}
     style="background: linear-gradient(135deg, var(--primary, #004ac6) 0%, var(--primary-container, #2563eb) 60%, #3b82f6 100%);">

    {{-- İnce dekoratif çizgi dokusu --}}
    <div class="absolute inset-0" aria-hidden="true"
         style="background-image: linear-gradient(rgba(255,255,255,0.06) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.06) 1px, transparent 1px); background-size: 28px 28px;"></div>

    <div class="absolute inset-0 flex flex-col items-center justify-center text-white select-none">
        @if($icon === 'landscape')
            <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" style="opacity:.9" aria-hidden="true">
                <path d="M3 18l5-7 4 5 3-4 6 6"/><path d="M3 21h18"/><circle cx="7" cy="7" r="2"/>
            </svg>
        @else
            <svg width="46" height="46" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" style="opacity:.9" aria-hidden="true">
                <path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>
            </svg>
        @endif
        <span class="mt-3 text-[10px] font-semibold uppercase" style="letter-spacing:.28em; opacity:.78;">Yalıhan Emlak</span>
    </div>
</div>
