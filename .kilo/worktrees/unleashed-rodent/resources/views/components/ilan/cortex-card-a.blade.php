@props(['cortexHealth' => [], 'cortexAnalysis' => [], 'ilan'])
{{--
    Cortex AI Analiz Kartı — Versiyon A: Compact Skor Kartı
    FA=0 | material-symbols=0 | x-icon kullanılıyor
    Arka plan: Navy (#0A1628) + Gold accent
    3 progress bar + özet metin + tek CTA
--}}
@php
    $marketScore  = (int)($cortexHealth['scores']['market']['score']  ?? 50);
    $qualityScore = (int)($cortexHealth['scores']['quality']['score'] ?? 50);
    $roiScore     = (int)($cortexAnalysis['roi_analizi']['roi_score'] ?? ($marketScore + $qualityScore) / 2);
    $overallScore = (int)($cortexHealth['overall_health'] ?? round(($marketScore + $qualityScore + $roiScore) / 3));
    $roiYears     = $cortexAnalysis['roi_analizi']['payback_period_years'] ?? 0;

    // Özet metin
    if ($marketScore >= 80) {
        $ozet = 'Piyasa verileri bu mülkün rekabetçi fiyatlandırıldığını gösteriyor.';
    } elseif ($marketScore <= 40) {
        $ozet = 'Üst segment konumlandırma; lüks detayları ile öne çıkıyor.';
    } else {
        $ozet = 'Bölge ortalamasıyla uyumlu dengeli bir fiyatlandırma.';
    }
    if ($roiYears > 0) {
        $ozet .= " Tahmini amortisman: {$roiYears} yıl.";
    }

    // Skor rengi
    $scoreColor = $overallScore >= 70 ? '#22c55e' : ($overallScore >= 45 ? '#f59e0b' : '#ef4444');
@endphp

<div style="margin-top:2.5rem;border-radius:1.25rem;overflow:hidden;background:#0A1628;border:1px solid rgba(201,168,76,0.2);">

    {{-- Üst şerit --}}
    <div style="padding:1.25rem 1.5rem 0.75rem;border-bottom:1px solid rgba(255,255,255,0.07);">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;">
            <div style="display:flex;align-items:center;gap:0.625rem;">
                <div style="width:34px;height:34px;border-radius:50%;background:rgba(201,168,76,0.15);border:1px solid rgba(201,168,76,0.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <x-icon name="robot" class="w-4 h-4" style="color:#C9A84C;" />
                </div>
                <div>
                    <p style="font-family:'Manrope',sans-serif;font-size:0.75rem;font-weight:800;color:#fff;letter-spacing:0.01em;">Cortex Analizi</p>
                    <p style="font-family:'Inter',sans-serif;font-size:0.6rem;font-weight:600;color:rgba(201,168,76,0.7);text-transform:uppercase;letter-spacing:0.08em;">AI Destekli</p>
                </div>
            </div>
            {{-- Genel skor rozeti --}}
            <div style="text-align:center;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:0.625rem;padding:0.375rem 0.75rem;">
                <p style="font-family:'Manrope',sans-serif;font-size:1.375rem;font-weight:900;color:{{ $scoreColor }};line-height:1;">{{ $overallScore }}</p>
                <p style="font-family:'Inter',sans-serif;font-size:0.55rem;font-weight:700;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.06em;">/ 100</p>
            </div>
        </div>
    </div>

    {{-- Progress bar'lar --}}
    <div style="padding:1.125rem 1.5rem;display:flex;flex-direction:column;gap:0.875rem;">

        @foreach([
            ['label' => 'Piyasa Uyumu',  'score' => $marketScore,  'icon' => 'grafik'],
            ['label' => 'Kalite Skoru',   'score' => $qualityScore, 'icon' => 'yildiz'],
            ['label' => 'ROI Potansiyeli','score' => $roiScore,     'icon' => 'para'],
        ] as $metrik)
        @php
            $barColor = $metrik['score'] >= 70 ? '#22c55e' : ($metrik['score'] >= 45 ? '#f59e0b' : '#ef4444');
        @endphp
        <div>
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem;">
                <div style="display:flex;align-items:center;gap:0.4rem;">
                    <x-icon name="{{ $metrik['icon'] }}" class="w-3 h-3" style="color:rgba(201,168,76,0.7);" />
                    <span style="font-family:'Inter',sans-serif;font-size:0.7rem;font-weight:600;color:rgba(255,255,255,0.65);">{{ $metrik['label'] }}</span>
                </div>
                <span style="font-family:'Manrope',sans-serif;font-size:0.75rem;font-weight:800;color:{{ $barColor }};">{{ $metrik['score'] }}%</span>
            </div>
            <div style="height:5px;background:rgba(255,255,255,0.08);border-radius:9999px;overflow:hidden;">
                <div style="height:100%;width:{{ $metrik['score'] }}%;background:{{ $barColor }};border-radius:9999px;transition:width 1s ease;"></div>
            </div>
        </div>
        @endforeach

    </div>

    {{-- Özet metin --}}
    <div style="padding:0 1.5rem 1.125rem;">
        <p style="font-family:'Inter',sans-serif;font-size:0.75rem;color:rgba(255,255,255,0.55);line-height:1.6;">
            "{{ $ozet }}"
        </p>
    </div>

    {{-- CTA --}}
    <div style="padding:0 1.5rem 1.5rem;">
        <a href="{{ route('ai.explore', ['id' => $ilan->id]) }}"
           style="display:flex;align-items:center;justify-content:center;gap:0.5rem;width:100%;padding:0.75rem;background:linear-gradient(135deg,#C9A84C,#e0b85a);color:#0A1628;border-radius:0.75rem;font-family:'Inter',sans-serif;font-size:0.8rem;font-weight:800;text-decoration:none;letter-spacing:0.02em;transition:opacity 0.2s;"
           onmouseover="this.style.opacity='0.88'"
           onmouseout="this.style.opacity='1'">
            <x-icon name="dis-baglanti" class="w-3.5 h-3.5" />
            Detaylı AI Raporu
        </a>
    </div>

</div>
