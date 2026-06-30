@props(['cortexHealth' => [], 'cortexAnalysis' => [], 'ilan'])
{{--
    Cortex AI Analiz Kartı — Versiyon B: Donut + Metrikler
    FA=0 | material-symbols=0 | x-icon kullanılıyor
    overall_health büyük SVG donut, altında 3 chip metrik
--}}
@php
    $marketScore  = (int)($cortexHealth['scores']['market']['score']  ?? 50);
    $qualityScore = (int)($cortexHealth['scores']['quality']['score'] ?? 50);
    $roiScore     = (int)($cortexAnalysis['roi_analizi']['roi_score'] ?? round(($marketScore + $qualityScore) / 2));
    $overall      = (int)($cortexHealth['overall_health'] ?? round(($marketScore + $qualityScore + $roiScore) / 3));
    $roiYears     = $cortexAnalysis['roi_analizi']['payback_period_years'] ?? 0;
    $isHighYield  = $cortexAnalysis['roi_analizi']['is_high_yield'] ?? false;

    // SVG donut hesaplama (r=36, circumference ≈ 226.2)
    $radius        = 36;
    $circumference = round(2 * M_PI * $radius, 1); // 226.2
    $dashOffset    = round($circumference * (1 - $overall / 100), 1);

    // Renk
    $ringColor = $overall >= 70 ? '#22c55e' : ($overall >= 45 ? '#f59e0b' : '#ef4444');
    $bgGrad    = $overall >= 70
        ? 'linear-gradient(135deg,#0a1628 0%,#0d2a18 100%)'
        : ($overall >= 45 ? 'linear-gradient(135deg,#0a1628 0%,#1a1400 100%)'
                          : 'linear-gradient(135deg,#0a1628 0%,#1a0a0a 100%)');

    $label = $overall >= 70 ? 'Güçlü Fırsat' : ($overall >= 45 ? 'Dengeli' : 'Temkinli');
@endphp

<div style="margin-top:2.5rem;border-radius:1.25rem;overflow:hidden;background:{{ $bgGrad }};border:1px solid rgba(201,168,76,0.18);">

    {{-- Başlık --}}
    <div style="display:flex;align-items:center;gap:0.5rem;padding:1.125rem 1.5rem 0.625rem;">
        <x-icon name="robot" class="w-4 h-4 flex-shrink-0" style="color:#C9A84C;" />
        <p style="font-family:'Manrope',sans-serif;font-size:0.7rem;font-weight:800;color:rgba(201,168,76,0.9);text-transform:uppercase;letter-spacing:0.1em;">Cortex AI · Analiz</p>
    </div>

    {{-- Donut + Skor --}}
    <div style="display:flex;align-items:center;justify-content:center;padding:0.75rem 1.5rem 0.5rem;gap:1.5rem;">

        {{-- SVG Donut --}}
        <div style="position:relative;width:96px;height:96px;flex-shrink:0;">
            <svg width="96" height="96" viewBox="0 0 96 96" style="transform:rotate(-90deg);" aria-hidden="true">
                {{-- Track --}}
                <circle cx="48" cy="48" r="{{ $radius }}"
                        fill="none"
                        stroke="rgba(255,255,255,0.06)"
                        stroke-width="10" />
                {{-- Progress --}}
                <circle cx="48" cy="48" r="{{ $radius }}"
                        fill="none"
                        stroke="{{ $ringColor }}"
                        stroke-width="10"
                        stroke-linecap="round"
                        stroke-dasharray="{{ $circumference }}"
                        stroke-dashoffset="{{ $dashOffset }}"
                        style="transition:stroke-dashoffset 1.2s ease;" />
            </svg>
            {{-- Merkez metin --}}
            <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                <p style="font-family:'Manrope',sans-serif;font-size:1.5rem;font-weight:900;color:#fff;line-height:1;">{{ $overall }}</p>
                <p style="font-family:'Inter',sans-serif;font-size:0.5rem;font-weight:700;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:0.08em;">puan</p>
            </div>
        </div>

        {{-- Sağ: etiket + açıklama --}}
        <div style="flex:1;min-width:0;">
            <span style="display:inline-block;font-family:'Inter',sans-serif;font-size:0.6rem;font-weight:800;text-transform:uppercase;letter-spacing:0.08em;color:#0A1628;background:{{ $ringColor }};padding:0.2rem 0.6rem;border-radius:9999px;margin-bottom:0.5rem;">
                {{ $label }}
            </span>
            <p style="font-family:'Inter',sans-serif;font-size:0.72rem;color:rgba(255,255,255,0.5);line-height:1.55;">
                Piyasa, kalite ve getiri verileri birleştirilerek hesaplanmış bütünleşik skor.
            </p>
        </div>

    </div>

    {{-- 3 Chip Metrik --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:0.5rem;padding:0.75rem 1.5rem;">
        @foreach([
            ['label' => 'Piyasa',   'val' => $marketScore,  'suffix' => '%', 'icon' => 'grafik'],
            ['label' => 'Kalite',   'val' => $qualityScore, 'suffix' => '%', 'icon' => 'yildiz'],
            ['label' => 'ROI',      'val' => $roiYears > 0 ? $roiYears.'y' : $roiScore.'%', 'suffix' => '', 'icon' => 'para'],
        ] as $chip)
        @php
            $chipVal = is_numeric($chip['val']) ? (int)$chip['val'] : $chip['val'];
            $chipColor = (is_int($chipVal) && $chipVal >= 70) ? '#22c55e'
                       : ((is_int($chipVal) && $chipVal >= 45) ? '#f59e0b' : '#C9A84C');
        @endphp
        <div style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);border-radius:0.625rem;padding:0.6rem 0.5rem;text-align:center;">
            <x-icon name="{{ $chip['icon'] }}" class="w-3.5 h-3.5 mx-auto mb-1" style="color:{{ $chipColor }};" />
            <p style="font-family:'Manrope',sans-serif;font-size:0.85rem;font-weight:800;color:#fff;line-height:1;">{{ $chip['val'] }}{{ $chip['suffix'] }}</p>
            <p style="font-family:'Inter',sans-serif;font-size:0.55rem;font-weight:600;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:0.06em;margin-top:0.2rem;">{{ $chip['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Hızlı amortisman etiketi --}}
    @if($isHighYield)
    <div style="margin:0 1.5rem 0.75rem;padding:0.5rem 0.75rem;background:rgba(99,102,241,0.15);border:1px solid rgba(99,102,241,0.3);border-radius:0.5rem;display:flex;align-items:center;gap:0.5rem;">
        <x-icon name="flas" class="w-3.5 h-3.5 flex-shrink-0" style="color:#818cf8;" />
        <p style="font-family:'Inter',sans-serif;font-size:0.68rem;font-weight:600;color:#a5b4fc;">Yüksek getiri potansiyeli tespit edildi</p>
    </div>
    @endif

    {{-- CTA çifti --}}
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.625rem;padding:0 1.5rem 1.5rem;">
        <a href="#iletisim"
           style="display:flex;align-items:center;justify-content:center;gap:0.4rem;padding:0.625rem;border:1px solid rgba(201,168,76,0.35);color:#C9A84C;border-radius:0.75rem;font-family:'Inter',sans-serif;font-size:0.72rem;font-weight:700;text-decoration:none;transition:background 0.2s;"
           onmouseover="this.style.background='rgba(201,168,76,0.1)'"
           onmouseout="this.style.background='transparent'">
            <x-icon name="telefon" class="w-3 h-3" />
            Danışman
        </a>
        <a href="{{ route('ai.explore', ['id' => $ilan->id]) }}"
           style="display:flex;align-items:center;justify-content:center;gap:0.4rem;padding:0.625rem;background:#C9A84C;color:#0A1628;border-radius:0.75rem;font-family:'Inter',sans-serif;font-size:0.72rem;font-weight:800;text-decoration:none;transition:opacity 0.2s;"
           onmouseover="this.style.opacity='0.88'"
           onmouseout="this.style.opacity='1'">
            <x-icon name="dis-baglanti" class="w-3 h-3" />
            Tam Rapor
        </a>
    </div>

</div>
