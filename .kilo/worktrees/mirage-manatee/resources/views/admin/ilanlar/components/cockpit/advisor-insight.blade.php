{{-- SAB — Advisor Insight Card (V3 Harden) --}}
{{-- Satışa Hazır UX: Badge → Summary → Action → Confidence → Why → Details → Risk --}}
{{-- V3: Kullanıcı yanlış anlama senaryoları proaktif engellenmiştir --}}

@if (isset($advisorInsight) && isset($pricingInsight))
    @php
        // ── Badge Renk Haritaları ──
        $opportunityAction = $pricingInsight->opportunity_action ?? 'INSUFFICIENT_DATA';
        $opportunityColors = [
            'BUY' =>
                'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
            'WAIT' =>
                'bg-gray-100 dark:bg-slate-700/50 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-slate-600',
            'SELL' =>
                'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
            'INSUFFICIENT_DATA' =>
                'bg-gray-100 dark:bg-slate-700/50 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-slate-600',
        ];
        $opportunityLabels = [
            'BUY' => 'Fırsat',
            'WAIT' => 'Bekle',
            'SELL' => 'Gözden Geçir',
            'INSUFFICIENT_DATA' => 'Veri Yetersiz',
        ];
        // S1 + S3: Badge micro-copy — her badge açıklama ile gelir
        $opportunityMicro = [
            'BUY' => 'benchmark\'a göre avantajlı konum',
            'WAIT' => 'aktif izleme önerilir',
            'SELL' => 'fiyat benchmark üzerinde',
            'INSUFFICIENT_DATA' => 'yeterli karşılaştırma verisi yok',
        ];
        $opportunityClass = $opportunityColors[$opportunityAction] ?? $opportunityColors['INSUFFICIENT_DATA'];
        $opportunityLabel = $opportunityLabels[$opportunityAction] ?? 'Veri Yetersiz';
        $opportunityMicroText = $opportunityMicro[$opportunityAction] ?? '';

        // Priority — Advisor urgency'den türet
$urgency = $advisorInsight->urgency ?? 'LOW';
$priorityColors = [
    'HIGH' => 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
    'MEDIUM' =>
        'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
    'LOW' =>
        'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
];
$priorityLabels = [
    'HIGH' => 'Yüksek Öncelik',
    'MEDIUM' => 'Orta Öncelik',
    'LOW' => 'Düşük Öncelik',
];
$priorityClass = $priorityColors[$urgency] ?? $priorityColors['LOW'];
$priorityLabel = $priorityLabels[$urgency] ?? 'Düşük Öncelik';

// Confidence
$confidenceLabel = $pricingInsight->confidence_label ?? 'VERY_LOW';
$confidenceScore = $pricingInsight->confidence_score ?? 0;
$confidenceReason = $pricingInsight->confidence_reason ?? '';
$confidenceColors = [
    'HIGH' =>
        'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
    'MEDIUM' =>
        'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
    'LOW' =>
        'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
    'VERY_LOW' =>
        'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
];
$confidenceTr = [
    'HIGH' => 'Yüksek Güven',
    'MEDIUM' => 'Orta Güven',
    'LOW' => 'Düşük Güven',
    'VERY_LOW' => 'Çok Düşük',
];
// S2: Confidence kısa etiket (veri güçlü / orta / zayıf / çok zayıf, kesinlik içermez)
$confidenceQuality = [
    'HIGH' => 'veri güçlü',
    'MEDIUM' => 'veri orta',
    'LOW' => 'veri zayıf',
    'VERY_LOW' => 'veri çok zayıf',
];
$confidenceClass = $confidenceColors[$confidenceLabel] ?? $confidenceColors['VERY_LOW'];
$confidenceTrLabel = $confidenceTr[$confidenceLabel] ?? 'Çok Düşük';
$confidenceQualityText = $confidenceQuality[$confidenceLabel] ?? 'veri çok zayıf';

// Pricing Position
$pricingPosition = $pricingInsight->pricing_position;
$positionLabel = $pricingPosition->label();

// Demand
$demandLabel = $pricingInsight->demand_label ?? 'WEAK';
$demandTr = [
    'HOT' => 'Güçlü',
    'ACTIVE' => 'Aktif',
    'SLOW' => 'Yavaş',
    'WEAK' => 'Zayıf',
];
$demandTrLabel = $demandTr[$demandLabel] ?? 'Zayıf';

// Days on market
$daysOnMarket = null;
if (isset($ilan) && $ilan->yayin_tarihi) {
    $daysOnMarket = now()->diffInDays($ilan->yayin_tarihi);
}

// Risk determination
$hasRisk = !empty($advisorInsight->risk_note);
$isLowConfidence = in_array($confidenceLabel, ['LOW', 'VERY_LOW']);

// S5: Reasoning — sadece sayısal veri içeren satırlar gösterilir (max 3)
$allReasoningLines = array_filter(
    array_map('trim', preg_split('/[\n•\-]/', $advisorInsight->reasoning ?? '')),
    fn($l) => strlen($l) > 5,
);
$reasoningLines = array_slice(
    array_values(array_filter($allReasoningLines, fn($l) => preg_match('/\d/', $l))),
    0,
    3,
);

// S8: CTA context — delta bilgisi
$deltaPercent = abs($pricingInsight->price_delta_percent ?? 0);
$deltaDirection = ($pricingInsight->price_delta_percent ?? 0) < 0 ? 'düşük' : 'yüksek';

// Format price
$formatPrice = fn($v) => $v ? '₺' . number_format($v, 0, ',', '.') : '—';
    @endphp

    <div
        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg overflow-hidden shadow-sm">

        {{-- ─── 1. HEADER + BADGE STRIP ─── --}}
        <div class="px-5 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 mb-3">Advisor Insight</h3>
            <div class="flex flex-wrap gap-2">
                {{-- Opportunity --}}
                <span
                    class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded border {{ $opportunityClass }}">
                    {{ $opportunityLabel }}
                </span>
                {{-- Priority --}}
                <span
                    class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded border {{ $priorityClass }}">
                    {{ $priorityLabel }}
                </span>
                {{-- Confidence --}}
                <span
                    class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded border {{ $confidenceClass }}">
                    {{ $confidenceTrLabel }}
                </span>
            </div>
            {{-- S1/S3: Badge altı micro-copy --}}
            @if ($opportunityMicroText)
                <p class="mt-2 text-[10px] text-gray-500 dark:text-gray-400">
                    {{ strtoupper($opportunityAction) }} — {{ $opportunityMicroText }}
                </p>
            @endif
        </div>

        <div class="p-5 space-y-4">

            {{-- ─── 2. ANA BAŞLIK — 1 SATIRLIK YORUM ─── --}}
            <p class="text-sm font-medium text-gray-900 dark:text-white leading-relaxed">
                {{ Str::limit($advisorInsight->summary, 140) }}
            </p>

            {{-- ─── 3. ÖNERİLEN AKSİYON BLOĞU ─── --}}
            <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                <p class="text-xs font-medium text-blue-600 dark:text-blue-400 mb-1">Önerilen Aksiyon</p>
                <p class="text-sm font-medium text-blue-900 dark:text-blue-200">
                    {{ $advisorInsight->recommended_action }}
                </p>
            </div>

            {{-- S8: CTA Butonlar — context zorunlu --}}
            <div class="flex flex-wrap gap-2">
                @if (in_array($opportunityAction, ['SELL']) ||
                        in_array($pricingPosition->value, ['overpriced', 'aggressively_overpriced']))
                    <a href="{{ route('admin.ilanlar.edit', $pricingInsight->ilan_id) }}#fiyat"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800 hover:bg-amber-200 dark:hover:bg-amber-900/50 transition-colors">
                        Fiyatı İncele
                        <span class="text-[10px] opacity-75">(benchmark'a göre %{{ $deltaPercent }}
                            {{ $deltaDirection }})</span>
                    </a>
                @endif
                @if ($opportunityAction === 'BUY')
                    <a href="{{ route('admin.ilanlar.show', $pricingInsight->ilan_id) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors">
                        Takibe Al
                        @if ($daysOnMarket !== null)
                            <span class="text-[10px] opacity-75">({{ $daysOnMarket }} gündür yayında)</span>
                        @endif
                    </a>
                @endif
                @if ($opportunityAction === 'WAIT')
                    <a href="{{ route('admin.ilanlar.show', $pricingInsight->ilan_id) }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-slate-700 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors">
                        İzlemeye Al
                        <span class="text-[10px] opacity-75">(önümüzdeki 7 gün kritik)</span>
                    </a>
                @endif
                <a href="{{ route('admin.ilanlar.show', $pricingInsight->ilan_id) }}"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 dark:bg-slate-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-slate-700 hover:bg-gray-200 dark:hover:bg-slate-700 transition-colors">
                    Detaya Git
                </a>
            </div>

            {{-- ─── 4. GÜVEN YAPISI (S6: Confidence yukarı taşındı) ─── --}}
            <div class="p-3 bg-gray-50 dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Güven Düzeyi</p>
                <div class="flex items-center gap-3">
                    {{-- S2: Format → "78/100 — HIGH (veri güçlü, kesinlik içermez)" --}}
                    <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $confidenceScore }}<span
                            class="text-xs font-normal text-gray-400 dark:text-gray-500">/100</span></span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">—</span>
                    <span
                        class="inline-flex items-center px-2 py-0.5 text-xs font-semibold rounded border {{ $confidenceClass }}">
                        {{ $confidenceTrLabel }}
                    </span>
                    <span class="text-[10px] text-gray-400 dark:text-gray-500">({{ $confidenceQualityText }}, kesinlik
                        içermez)</span>
                </div>
                @if ($confidenceReason)
                    <p class="mt-1.5 text-xs text-gray-600 dark:text-gray-400">{{ $confidenceReason }}</p>
                @endif
                {{-- S2: Güven disclaimer —zorunlu --}}
                <p class="mt-1.5 text-[10px] text-gray-400 dark:text-gray-500">Güven düzeyi veri kalitesini ifade eder,
                    sonucu belirlemez.</p>
            </div>

            {{-- ─── 5. NEDEN BLOĞU (S5: sadece sayısal veri içeren satırlar) ─── --}}
            @if (count($reasoningLines) > 0)
                <div class="p-3 bg-gray-50 dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Bu öneri neden verildi?</p>
                    <ul class="space-y-1.5">
                        @foreach ($reasoningLines as $line)
                            <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <span class="shrink-0 mt-1.5 w-1 h-1 rounded-full bg-gray-400 dark:bg-gray-500"></span>
                                {{ $line }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- ─── 6. ALT VERİ PANELİ (Collapsible) ─── --}}
            <div x-data="{ open: false }">
                <button @click="open = !open" type="button"
                    class="flex items-center gap-1 text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-90': open }" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                    </svg>
                    Detaylar
                </button>
                <div x-show="open" x-collapse class="mt-2">
                    <div class="border border-gray-200 dark:border-slate-700 rounded-lg overflow-hidden">
                        <table class="w-full text-xs">
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-700">
                                <tr class="bg-white dark:bg-slate-900">
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Fiyat Pozisyonu</td>
                                    <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">
                                        {{ $positionLabel }}</td>
                                </tr>
                                <tr class="bg-gray-50 dark:bg-slate-800">
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Fiyat Skoru</td>
                                    <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">
                                        {{ $pricingInsight->pricing_score }}</td>
                                </tr>
                                <tr class="bg-white dark:bg-slate-900">
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Talep</td>
                                    <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">
                                        {{ $demandTrLabel }}</td>
                                </tr>
                                <tr class="bg-gray-50 dark:bg-slate-800">
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Opportunity</td>
                                    <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">
                                        {{ $opportunityLabel }}</td>
                                </tr>
                                @if ($daysOnMarket !== null)
                                    <tr class="bg-white dark:bg-slate-900">
                                        <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Gün Sayısı</td>
                                        <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">
                                            {{ $daysOnMarket }}</td>
                                    </tr>
                                @endif
                                <tr
                                    class="{{ $daysOnMarket !== null ? 'bg-gray-50 dark:bg-slate-800' : 'bg-white dark:bg-slate-900' }}">
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Fiyat</td>
                                    <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">
                                        {{ $formatPrice($pricingInsight->current_price) }}</td>
                                </tr>
                                <tr
                                    class="{{ $daysOnMarket !== null ? 'bg-white dark:bg-slate-900' : 'bg-gray-50 dark:bg-slate-800' }}">
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Benchmark</td>
                                    <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">
                                        {{ $formatPrice($pricingInsight->benchmark_price) }}</td>
                                </tr>
                                @php
                                    $dtSign = ($pricingInsight->price_delta_percent ?? 0) > 0 ? '+' : '';
                                @endphp
                                <tr
                                    class="{{ $daysOnMarket !== null ? 'bg-gray-50 dark:bg-slate-800' : 'bg-white dark:bg-slate-900' }}">
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Sapma</td>
                                    <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">
                                        {{ $dtSign }}{{ $pricingInsight->price_delta_percent ?? '—' }}%</td>
                                </tr>
                                <tr
                                    class="{{ $daysOnMarket !== null ? 'bg-white dark:bg-slate-900' : 'bg-gray-50 dark:bg-slate-800' }}">
                                    <td class="px-3 py-2 text-gray-500 dark:text-gray-400">Emsal Sayısı</td>
                                    <td class="px-3 py-2 text-right font-medium text-gray-900 dark:text-white">
                                        {{ $pricingInsight->sample_size }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ─── 7. RİSK NOTU BLOĞU (S7: her zaman görünür) ─── --}}
            <div
                class="p-3 {{ $hasRisk || $isLowConfidence ? 'bg-amber-50 dark:bg-amber-900/15 border-amber-200 dark:border-amber-800/60' : 'bg-gray-50 dark:bg-slate-800 border-gray-200 dark:border-slate-700' }} rounded-lg border">
                <p
                    class="text-xs font-medium {{ $hasRisk || $isLowConfidence ? 'text-amber-700 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400' }} mb-1.5">
                    Dikkat Edilmesi Gerekenler
                </p>
                <ul class="space-y-1">
                    @if ($hasRisk)
                        <li class="flex items-start gap-2 text-xs text-amber-700 dark:text-amber-300">
                            <span class="shrink-0 mt-1 w-1 h-1 rounded-full bg-amber-400 dark:bg-amber-500"></span>
                            {{ $advisorInsight->risk_note }}
                        </li>
                    @endif
                    @if ($isLowConfidence)
                        <li class="flex items-start gap-2 text-xs text-amber-700 dark:text-amber-300">
                            <span class="shrink-0 mt-1 w-1 h-1 rounded-full bg-amber-400 dark:bg-amber-500"></span>
                            Güven düzeyi düşük, sonuç sınırlı veriyle üretildi
                        </li>
                    @endif
                    {{-- S1: Piyasa davranış disclaimer --}}
                    <li
                        class="flex items-start gap-2 text-xs {{ $hasRisk || $isLowConfidence ? 'text-amber-600 dark:text-amber-400' : 'text-gray-500 dark:text-gray-400' }}">
                        <span
                            class="shrink-0 mt-1 w-1 h-1 rounded-full {{ $hasRisk || $isLowConfidence ? 'bg-amber-400 dark:bg-amber-500' : 'bg-gray-300 dark:bg-gray-600' }}"></span>
                        Bu sonuç piyasa davranışını belirlemez.
                    </li>
                    @if (!$hasRisk && !$isLowConfidence)
                        {{-- S7: Risk yoksa bile bilgi ver --}}
                        <li class="flex items-start gap-2 text-xs text-gray-500 dark:text-gray-400">
                            <span class="shrink-0 mt-1 w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                            Ek risk sinyali tespit edilmedi.
                        </li>
                    @endif
                </ul>
            </div>

            {{-- ─── TRUST STACK (S4 + S9) ─── --}}
            <div class="pt-3 border-t border-gray-100 dark:border-slate-700 space-y-1">
                <p class="text-[10px] text-gray-400 dark:text-gray-500">Bu öneri fiyat, talep ve karşılaştırmalı analiz
                    sinyallerine dayanır.</p>
                <p class="text-[10px] text-gray-400 dark:text-gray-500">Nihai karar kullanıcıya aittir.</p>
                <p class="text-[10px] text-gray-400 dark:text-gray-500">Bu sistem öneri üretir, sonuçları izleyerek
                    gelişir.</p>
                @if ($isLowConfidence)
                    <p class="text-[10px] text-amber-500 dark:text-amber-400">Düşük güven durumlarında sistem yalnız
                        inceleme önerir.</p>
                @endif
            </div>
        </div>
    </div>
@endif
