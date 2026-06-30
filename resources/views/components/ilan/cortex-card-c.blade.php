@props(['cortexHealth' => [], 'cortexAnalysis' => [], 'ilan'])
{{--
    Cortex AI Analiz Kartı — Versiyon C: Premium Glassmorphism
    FA=0 | material-symbols=0 | x-icon kullanılıyor
    backdrop-blur cam efekti, gold border, fiyat/m² bölge karşılaştırması, trend ok
--}}
@php
    $marketScore  = (int)($cortexHealth['scores']['market']['score']  ?? 50);
    $qualityScore = (int)($cortexHealth['scores']['quality']['score'] ?? 50);
    $roiScore     = (int)($cortexAnalysis['roi_analizi']['roi_score'] ?? round(($marketScore + $qualityScore) / 2));
    $overall      = (int)($cortexHealth['overall_health'] ?? round(($marketScore + $qualityScore + $roiScore) / 3));
    $roiYears     = $cortexAnalysis['roi_analizi']['payback_period_years'] ?? 0;
    $isHighYield  = $cortexAnalysis['roi_analizi']['is_high_yield'] ?? false;
    $isHighDemand = ($overall >= 80);

    // Fiyat/m² hesabı
    $fiyat       = $ilan->fiyat ?? 0;
    $netM2       = $ilan->net_m2 ?? 0;
    $fiyatPerM2  = ($fiyat > 0 && $netM2 > 0) ? (int)round($fiyat / $netM2) : 0;

    // Bölge ortalaması (cortex verisinden ya da fallback)
    $bolgeOrt    = (int)($cortexAnalysis['piyasa_analizi']['bolge_ort_m2'] ?? 0);
    $hasBolge    = $bolgeOrt > 0 && $fiyatPerM2 > 0;
    $farkYuzde   = $hasBolge ? round((($fiyatPerM2 - $bolgeOrt) / $bolgeOrt) * 100) : 0;
    $trend       = $farkYuzde <= -5 ? 'down' : ($farkYuzde >= 5 ? 'up' : 'flat');
    $trendLabel  = $trend === 'down' ? 'Bölge ortalamasının altında' : ($trend === 'up' ? 'Bölge ortalamasının üstünde' : 'Bölge ortalamasında');
    $trendColor  = $trend === 'down' ? '#22c55e' : ($trend === 'up' ? '#f59e0b' : '#60a5fa');

    // Genel label
    $overallLabel = $overall >= 70 ? 'Güçlü Fırsat' : ($overall >= 45 ? 'Dengeli Portföy' : 'Temkinli Yaklaş');
    $labelBg      = $overall >= 70 ? 'rgba(34,197,94,0.15)' : ($overall >= 45 ? 'rgba(245,158,11,0.15)' : 'rgba(239,68,68,0.15)');
    $labelColor   = $overall >= 70 ? '#4ade80' : ($overall >= 45 ? '#fbbf24' : '#f87171');
@endphp

{{-- Glassmorphism container --}}
<div style="margin-top:2.5rem;border-radius:1.375rem;overflow:hidden;background:rgba(10,22,40,0.82);backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);border:1px solid rgba(201,168,76,0.28);box-shadow:0 8px 32px rgba(10,22,40,0.35),inset 0 1px 0 rgba(255,255,255,0.07);">

    {{-- Altın gradient üst bant --}}
    <div style="height:3px;background:linear-gradient(90deg,rgba(201,168,76,0),#C9A84C 40%,rgba(201,168,76,0.4) 100%);"></div>

    {{-- Başlık bölgesi --}}
    <div style="padding:1.25rem 1.5rem 0.875rem;display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
        <div style="display:flex;align-items:center;gap:0.625rem;">
            <div style="width:38px;height:38px;border-radius:0.625rem;background:rgba(201,168,76,0.12);border:1px solid rgba(201,168,76,0.25);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <x-icon name="robot" class="w-5 h-5" style="color:#C9A84C;" />
            </div>
            <div>
                <p style="font-family:'Manrope',sans-serif;font-size:0.8rem;font-weight:800;color:#fff;letter-spacing:-0.01em;">Cortex AI Analizi</p>
                <p style="font-family:'Inter',sans-serif;font-size:0.58rem;font-weight:600;color:rgba(201,168,76,0.6);text-transform:uppercase;letter-spacing:0.09em;">Yalıhan · Gayrimenkul Zekâsı</p>
            </div>
        </div>
        {{-- Overall badge --}}
        <div style="background:{{ $labelBg }};border:1px solid {{ $labelColor }}33;border-radius:0.5rem;padding:0.3rem 0.625rem;text-align:center;flex-shrink:0;">
            <p style="font-family:'Manrope',sans-serif;font-size:1.125rem;font-weight:900;color:{{ $labelColor }};line-height:1.1;">{{ $overall }}</p>
            <p style="font-family:'Inter',sans-serif;font-size:0.5rem;font-weight:700;color:{{ $labelColor }}99;text-transform:uppercase;letter-spacing:0.07em;">puan</p>
        </div>
    </div>

    {{-- Durum rozeti --}}
    <div style="padding:0 1.5rem 0.875rem;">
        <span style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.25rem 0.75rem;background:{{ $labelBg }};border:1px solid {{ $labelColor }}55;border-radius:9999px;">
            <span style="width:6px;height:6px;border-radius:50%;background:{{ $labelColor }};flex-shrink:0;"></span>
            <span style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:700;color:{{ $labelColor }};text-transform:uppercase;letter-spacing:0.06em;">{{ $overallLabel }}</span>
        </span>
    </div>

    {{-- Divider --}}
    <div style="height:1px;background:rgba(255,255,255,0.06);margin:0 1.5rem;"></div>

    {{-- Metrik grid --}}
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1px;background:rgba(255,255,255,0.06);margin:0.875rem 0;">
        @foreach([
            ['label' => 'Piyasa',  'val' => $marketScore,  'unit' => '%', 'icon' => 'grafik'],
            ['label' => 'Kalite',  'val' => $qualityScore, 'unit' => '%', 'icon' => 'yildiz'],
            ['label' => 'ROI',     'val' => $roiScore,     'unit' => '%', 'icon' => 'para'],
        ] as $m)
        @php $mc = $m['val'] >= 70 ? '#4ade80' : ($m['val'] >= 45 ? '#fbbf24' : '#f87171'); @endphp
        <div style="background:rgba(10,22,40,0.6);padding:0.875rem 0.625rem;text-align:center;">
            <x-icon name="{{ $m['icon'] }}" class="w-4 h-4 mx-auto mb-1" style="color:{{ $mc }};" />
            <p style="font-family:'Manrope',sans-serif;font-size:1rem;font-weight:900;color:#fff;line-height:1.1;">{{ $m['val'] }}<span style="font-size:0.6rem;font-weight:600;color:rgba(255,255,255,0.4);">{{ $m['unit'] }}</span></p>
            <p style="font-family:'Inter',sans-serif;font-size:0.55rem;font-weight:600;color:rgba(255,255,255,0.35);text-transform:uppercase;letter-spacing:0.07em;margin-top:0.25rem;">{{ $m['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Fiyat/m² karşılaştırma --}}
    @if($fiyatPerM2 > 0)
    <div style="margin:0 1.5rem 0.875rem;padding:0.75rem 1rem;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:0.75rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;flex-wrap:wrap;">
            <div>
                <p style="font-family:'Inter',sans-serif;font-size:0.6rem;font-weight:700;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.2rem;">Bu Mülk · m² Fiyatı</p>
                <p style="font-family:'Manrope',sans-serif;font-size:1rem;font-weight:900;color:#fff;">{{ number_format($fiyatPerM2, 0, ',', '.') }} <span style="font-size:0.65rem;font-weight:600;color:rgba(255,255,255,0.45);">₺/m²</span></p>
            </div>
            @if($hasBolge)
            <div style="text-align:right;">
                <p style="font-family:'Inter',sans-serif;font-size:0.6rem;font-weight:700;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.2rem;">Bölge Ortalaması</p>
                <p style="font-family:'Manrope',sans-serif;font-size:0.875rem;font-weight:700;color:rgba(255,255,255,0.55);">{{ number_format($bolgeOrt, 0, ',', '.') }} ₺/m²</p>
            </div>
            @endif
        </div>
        @if($hasBolge)
        <div style="margin-top:0.625rem;display:flex;align-items:center;gap:0.375rem;">
            {{-- Trend ok --}}
            @if($trend === 'down')
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m-7 7l7 7 7-7"/></svg>
            @elseif($trend === 'up')
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#f59e0b" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7l-7-7-7 7"/></svg>
            @else
                <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#60a5fa" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
            @endif
            <p style="font-family:'Inter',sans-serif;font-size:0.68rem;font-weight:600;color:{{ $trendColor }};">
                {{ $trendLabel }}
                @if($farkYuzde !== 0)
                    ({{ $farkYuzde > 0 ? '+' : '' }}{{ $farkYuzde }}%)
                @endif
            </p>
        </div>
        @endif
    </div>
    @endif

    {{-- Amortisman & Yüksek Getiri --}}
    @if($roiYears > 0 || $isHighYield || $isHighDemand)
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;padding:0 1.5rem 0.875rem;">
        @if($roiYears > 0)
        <div style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.3rem 0.625rem;background:rgba(99,102,241,0.12);border:1px solid rgba(99,102,241,0.25);border-radius:9999px;">
            <x-icon name="saat" class="w-3 h-3 flex-shrink-0" style="color:#818cf8;" />
            <span style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:600;color:#a5b4fc;">{{ $roiYears }} yıl amortisman</span>
        </div>
        @endif
        @if($isHighYield)
        <div style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.3rem 0.625rem;background:rgba(245,158,11,0.12);border:1px solid rgba(245,158,11,0.25);border-radius:9999px;">
            <x-icon name="flas" class="w-3 h-3 flex-shrink-0" style="color:#fbbf24;" />
            <span style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:600;color:#fcd34d;">Hızlı amortisman</span>
        </div>
        @endif
        @if($isHighDemand)
        <div style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.3rem 0.625rem;background:rgba(34,197,94,0.12);border:1px solid rgba(34,197,94,0.25);border-radius:9999px;">
            <x-icon name="yildiz" class="w-3 h-3 flex-shrink-0" style="color:#4ade80;" />
            <span style="font-family:'Inter',sans-serif;font-size:0.65rem;font-weight:600;color:#86efac;">Yüksek talep</span>
        </div>
        @endif
    </div>
    @endif

    {{-- Divider --}}
    <div style="height:1px;background:rgba(255,255,255,0.06);margin:0 1.5rem;"></div>

    {{-- CTA --}}
    <div style="padding:1rem 1.5rem 1.375rem;">
        <a href="{{ route('ai.explore', ['id' => $ilan->id]) }}"
           style="display:flex;align-items:center;justify-content:center;gap:0.5rem;width:100%;padding:0.8rem;background:linear-gradient(135deg,#C9A84C 0%,#e0c068 50%,#C9A84C 100%);background-size:200% 100%;color:#0A1628;border-radius:0.875rem;font-family:'Inter',sans-serif;font-size:0.8rem;font-weight:800;text-decoration:none;letter-spacing:0.02em;transition:background-position 0.4s ease,box-shadow 0.2s;"
           onmouseover="this.style.backgroundPosition='right center';this.style.boxShadow='0 4px 20px rgba(201,168,76,0.35)'"
           onmouseout="this.style.backgroundPosition='left center';this.style.boxShadow='none'">
            <x-icon name="dis-baglanti" class="w-3.5 h-3.5" />
            Tam AI Analiz Raporunu Gör
        </a>
        <p style="text-align:center;margin-top:0.625rem;font-family:'Inter',sans-serif;font-size:0.6rem;color:rgba(255,255,255,0.25);">
            Cortex · Gerçek zamanlı piyasa verisi
        </p>
    </div>

</div>
