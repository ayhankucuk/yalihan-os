{{-- SAB — INSIGHT + ACTION MODE: Konum İstihbaratı + Karar Platformu --}}
{{-- Alan (Polygon) + Çevre (POI) + Karar (WHY this score? WHAT to do?) = Tek Ekran --}}

@php
    $hasCoords = $ilan->lat && $ilan->lng;
    $hasPolygon = $ilan->geometry_type === 'polygon' && $ilan->geometry;
    $hasLocation = $hasCoords || $hasPolygon;

    // MIE scores
    $hasMie = isset($locationInsight) && $locationInsight && $locationInsight->data_status === 'ok';
    $signalScore = $hasMie ? $locationInsight->location_signal_score ?? 0 : 0;
    $accessScore = $hasMie ? $locationInsight->poi_access_score ?? 0 : 0;
    $densityScore = $hasMie ? $locationInsight->poi_density_score ?? 0 : 0;
    $coverageScore = $hasMie ? $locationInsight->poi_coverage_score ?? 0 : 0;
    $humanSummary = $hasMie ? $locationInsight->human_summary ?? '' : '';
    $confidenceLabel = $hasMie ? $locationInsight->confidence_label ?? 'VERY_LOW' : 'VERY_LOW';
    $topGroups = $hasMie ? $locationInsight->top_nearby_groups ?? [] : [];
    $reasonCodes = $hasMie ? $locationInsight->reason_codes ?? [] : [];

    // Signal label + color
    $signalLabel = match (true) {
        $signalScore >= 65 => 'Güçlü Konum',
        $signalScore >= 35 => 'Orta Konum',
        default => 'Zayıf Konum',
    };
    $signalBg = match (true) {
        $signalScore >= 65 => 'bg-emerald-500',
        $signalScore >= 35 => 'bg-amber-500',
        default => 'bg-gray-500',
    };
    $signalRing = match (true) {
        $signalScore >= 65 => 'ring-emerald-500/20',
        $signalScore >= 35 => 'ring-amber-500/20',
        default => 'ring-gray-500/20',
    };

    // Sub-score strength levels for visual indicators
    $accessPct = min(100, ($accessScore / 40) * 100);
    $densityPct = min(100, ($densityScore / 30) * 100);
    $coveragePct = min(100, ($coverageScore / 30) * 100);

    $accessLevel = $accessPct >= 60 ? 'strong' : ($accessPct >= 30 ? 'moderate' : 'weak');
    $densityLevel = $densityPct >= 60 ? 'strong' : ($densityPct >= 30 ? 'moderate' : 'weak');
    $coverageLevel = $coveragePct >= 60 ? 'strong' : ($coveragePct >= 30 ? 'moderate' : 'weak');

    // Advisor
    $hasAdvisor = isset($advisorInsight) && $advisorInsight && !empty($advisorInsight->summary);

    // POI group icons + labels
    $groupIcons = [
        'education' => '🎓',
        'health' => '🏥',
        'shopping' => '🛍️',
        'transport' => '🚌',
        'food_social' => '🍽️',
        'green_leisure' => '🌳',
        'daily_need' => '🏦',
    ];
    $groupLabels = [
        'education' => 'Eğitim',
        'health' => 'Sağlık',
        'shopping' => 'Alışveriş',
        'transport' => 'Ulaşım',
        'food_social' => 'Yeme-İçme',
        'green_leisure' => 'Yeşil Alan',
        'daily_need' => 'Günlük İhtiyaç',
    ];

    // INSIGHT MODE: Generate strength/weakness chips from top_nearby_groups
    $insightChips = [];
    $allGroupKeys = ['education', 'health', 'shopping', 'transport', 'food_social', 'green_leisure', 'daily_need'];
    $presentGroups = collect($topGroups)->pluck('group')->toArray();

    foreach ($topGroups as $g) {
        $m = $g['closest_m'] ?? 9999;
        $label = $groupLabels[$g['group']] ?? ($g['label'] ?? $g['group']);
        $icon = $groupIcons[$g['group']] ?? '📌';
        if ($m <= 500) {
            $insightChips[] = ['type' => 'strong', 'text' => $icon . ' ' . $label . ' erişimi güçlü', 'dist' => $m];
        } elseif ($m <= 1500) {
            $insightChips[] = ['type' => 'moderate', 'text' => $icon . ' ' . $label . ' erişimi orta', 'dist' => $m];
        } else {
            $insightChips[] = ['type' => 'weak', 'text' => $icon . ' ' . $label . ' uzak mesafede', 'dist' => $m];
        }
    }
    // Missing critical groups
    $criticalGroups = ['education', 'health', 'daily_need', 'transport'];
    foreach ($criticalGroups as $cg) {
        if (!in_array($cg, $presentGroups)) {
            $label = $groupLabels[$cg] ?? $cg;
            $icon = $groupIcons[$cg] ?? '📌';
            $insightChips[] = ['type' => 'missing', 'text' => $icon . ' ' . $label . ' erişimi yok', 'dist' => null];
        }
    }

    // Sort: weak/missing first, then moderate, then strong
    $chipOrder = ['missing' => 0, 'weak' => 1, 'moderate' => 2, 'strong' => 3];
    usort($insightChips, fn($a, $b) => ($chipOrder[$a['type']] ?? 9) <=> ($chipOrder[$b['type']] ?? 9));

    // INSIGHT MODE: Build decision text from MIE data
    $strengths = array_filter($insightChips, fn($c) => $c['type'] === 'strong');
    $weaknesses = array_filter($insightChips, fn($c) => in_array($c['type'], ['weak', 'missing']));

    $decisionText = '';
    if ($hasMie) {
        $strongLabels = array_map(
            fn($c) => preg_replace('/^[^\s]+\s/', '', explode(' erişimi', $c['text'])[0] ?? ''),
            array_values($strengths),
        );
        $weakLabels = array_map(
            fn($c) => preg_replace('/^[^\s]+\s/', '', explode(' erişimi', $c['text'])[0] ?? ''),
            array_values($weaknesses),
        );

        if (count($strongLabels) > 0 && count($weakLabels) > 0) {
            $decisionText =
                'Bu lokasyon ' .
                implode(', ', array_slice($strongLabels, 0, 3)) .
                ' için güçlü, ancak ' .
                implode(', ', array_slice($weakLabels, 0, 2)) .
                ' erişimi sınırlı.';
        } elseif (count($strongLabels) > 0) {
            $decisionText =
                'Bu lokasyon ' . implode(', ', array_slice($strongLabels, 0, 3)) . ' açısından güçlü bir konumda.';
        } elseif (count($weakLabels) > 0) {
            $decisionText =
                'Bu lokasyonda ' .
                implode(', ', array_slice($weakLabels, 0, 3)) .
                ' erişimi sınırlı. Dikkatli değerlendirme önerilir.';
        }
    }

    // POI type to icon mapping for individual markers
    $poiTypeIcons = json_encode([
        'school' => '🎓',
        'university' => '🎓',
        'kindergarten' => '🎓',
        'hospital' => '🏥',
        'clinic' => '🏥',
        'pharmacy' => '🏥',
        'doctors' => '🏥',
        'supermarket' => '🛒',
        'marketplace' => '🛒',
        'mall' => '🛍️',
        'shop' => '🛍️',
        'bus_station' => '🚌',
        'bus_stop' => '🚌',
        'ferry_terminal' => '⛴️',
        'restaurant' => '🍽️',
        'cafe' => '☕',
        'fast_food' => '🍔',
        'park' => '🌳',
        'garden' => '🌳',
        'beach' => '🏖️',
        'playground' => '🌳',
        'bank' => '🏦',
        'atm' => '🏦',
        'post_office' => '📮',
        'mosque' => '🕌',
        'police' => '🚔',
        'fire_station' => '🚒',
        'fuel' => '⛽',
        'parking' => '🅿️',
    ]);

    // Group strength for JS (map glow coloring)
    $groupStrengthMap = [];
    foreach ($topGroups as $g) {
        $m = $g['closest_m'] ?? 9999;
        $groupStrengthMap[$g['group']] = $m <= 500 ? 'strong' : ($m <= 1500 ? 'moderate' : 'weak');
    }

    // ═══════════════════════════════════════════════════════
    // ACTION MODE: Decision Engine — computed by ActionModeEngine
    // View kullanımı: $actionMode (ActionModeDTO) controller'dan gelir
    // ═══════════════════════════════════════════════════════
    $hasActionMode = isset($actionMode) && $actionMode !== null;
@endphp

@if ($hasLocation)
    <section
        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg overflow-hidden shadow-sm dark:shadow-none">

        {{-- Header --}}
        <div
            class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 flex justify-between items-center">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                🗺️ Konum İstihbaratı
                <span class="text-xs font-normal text-gray-500 dark:text-slate-400">INSIGHT MODE</span>
                @if ($hasActionMode)
                    <span class="text-xs font-normal text-indigo-600 dark:text-indigo-400">+ ACTION MODE</span>
                @endif
            </h3>
            <div class="flex items-center gap-2">
                @if ($hasPolygon)
                    <span
                        class="px-2 py-0.5 text-xs font-medium rounded border bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800">Polygon</span>
                @endif
                @if ($hasMie)
                    <span
                        class="px-2 py-0.5 text-xs font-medium rounded border bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800">MIE
                        v4</span>
                @endif
            </div>
        </div>

        {{-- ═══ MAP + SCORE BREAKDOWN PANEL ═══ --}}
        <div class="flex flex-col lg:flex-row">

            {{-- Map Container (left side) --}}
            <div class="relative flex-1 min-h-[400px] lg:min-h-[500px]">
                <div id="intelligence-map" class="w-full h-full" style="min-height: 400px;"></div>

                {{-- Legend Overlay (bottom-left corner of map) --}}
                <div class="absolute bottom-3 left-3 z-[1000] pointer-events-none">
                    <div
                        class="bg-white/95 dark:bg-slate-800/95 backdrop-blur-sm rounded-lg border border-gray-200 dark:border-slate-600 px-3 py-2 pointer-events-auto">
                        <div
                            class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] text-gray-600 dark:text-slate-400">
                            @if ($hasPolygon)
                                <span class="flex items-center gap-1">
                                    <span class="w-3 h-0.5 bg-emerald-500 inline-block rounded"></span> Arsa Sınırı
                                </span>
                            @endif
                            <span class="flex items-center gap-1">
                                <span
                                    class="w-3 h-3 border border-indigo-400 rounded-full inline-block opacity-50"></span>
                                2km Alan
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-emerald-400 rounded-full inline-block"></span> Güçlü
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="w-2 h-2 bg-red-400 rounded-full inline-block"></span> Zayıf
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 1: Score Breakdown Panel (right side) --}}
            @if ($hasMie)
                <div
                    class="w-full lg:w-[280px] xl:w-[320px] border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/30 p-5 flex flex-col">

                    {{-- Main Score Ring --}}
                    <div class="flex items-center gap-3 mb-5">
                        <div
                            class="w-14 h-14 rounded-xl {{ $signalBg }} ring-4 {{ $signalRing }} flex items-center justify-center text-white font-bold text-lg">
                            {{ $signalScore }}
                        </div>
                        <div>
                            <div class="text-sm font-semibold text-gray-900 dark:text-slate-100">{{ $signalLabel }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-slate-400">Konum Skoru / 100</div>
                        </div>
                    </div>

                    {{-- Sub-Score Breakdown --}}
                    <div class="space-y-3 mb-5">
                        <div
                            class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">
                            Neden {{ $signalScore }}?</div>

                        {{-- Access Score --}}
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-medium text-gray-700 dark:text-slate-300">Erişim</span>
                                <span
                                    class="text-xs font-bold {{ $accessLevel === 'strong' ? 'text-emerald-600 dark:text-emerald-400' : ($accessLevel === 'moderate' ? 'text-amber-600 dark:text-amber-400' : 'text-red-500 dark:text-red-400') }}">{{ $accessScore }}/40</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all {{ $accessLevel === 'strong' ? 'bg-emerald-500' : ($accessLevel === 'moderate' ? 'bg-amber-500' : 'bg-red-500') }}"
                                    style="width: {{ $accessPct }}%"></div>
                            </div>
                            <div class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">
                                {{ $accessLevel === 'strong' ? 'Kritik ihtiyaçlara yakın' : ($accessLevel === 'moderate' ? 'Orta mesafede erişim' : 'Kritik hizmetlere uzak') }}
                            </div>
                        </div>

                        {{-- Density Score --}}
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-medium text-gray-700 dark:text-slate-300">Yoğunluk</span>
                                <span
                                    class="text-xs font-bold {{ $densityLevel === 'strong' ? 'text-emerald-600 dark:text-emerald-400' : ($densityLevel === 'moderate' ? 'text-amber-600 dark:text-amber-400' : 'text-red-500 dark:text-red-400') }}">{{ $densityScore }}/30</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all {{ $densityLevel === 'strong' ? 'bg-emerald-500' : ($densityLevel === 'moderate' ? 'bg-amber-500' : 'bg-red-500') }}"
                                    style="width: {{ $densityPct }}%"></div>
                            </div>
                            <div class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">
                                {{ $densityLevel === 'strong' ? 'Çevrede yoğun hizmet noktası' : ($densityLevel === 'moderate' ? 'Çevrede yeterli nokta' : 'Hizmet noktası seyrek') }}
                            </div>
                        </div>

                        {{-- Coverage Score --}}
                        <div>
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs font-medium text-gray-700 dark:text-slate-300">Çeşitlilik</span>
                                <span
                                    class="text-xs font-bold {{ $coverageLevel === 'strong' ? 'text-emerald-600 dark:text-emerald-400' : ($coverageLevel === 'moderate' ? 'text-amber-600 dark:text-amber-400' : 'text-red-500 dark:text-red-400') }}">{{ $coverageScore }}/30</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                                <div class="h-2 rounded-full transition-all {{ $coverageLevel === 'strong' ? 'bg-emerald-500' : ($coverageLevel === 'moderate' ? 'bg-amber-500' : 'bg-red-500') }}"
                                    style="width: {{ $coveragePct }}%"></div>
                            </div>
                            <div class="text-[10px] text-gray-400 dark:text-slate-500 mt-0.5">
                                {{ $coverageLevel === 'strong' ? 'Farklı hizmet kategorileri mevcut' : ($coverageLevel === 'moderate' ? 'Kısmi çeşitlilik' : 'Tek tip hizmet bölgesi') }}
                            </div>
                        </div>
                    </div>

                    {{-- STEP 4: POI Clustering Insight (Top 3 Nearest) --}}
                    @if (count($topGroups) > 0)
                        <div class="mt-auto pt-4 border-t border-gray-200 dark:border-slate-700">
                            <div
                                class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">
                                En Yakın Hizmetler</div>
                            <div class="space-y-2">
                                @foreach (array_slice($topGroups, 0, 4) as $group)
                                    @php
                                        $icon = $groupIcons[$group['group']] ?? '📌';
                                        $closestM = $group['closest_m'] ?? 0;
                                        $distColor =
                                            $closestM <= 500
                                                ? 'text-emerald-600 dark:text-emerald-400'
                                                : ($closestM <= 1500
                                                    ? 'text-amber-600 dark:text-amber-400'
                                                    : 'text-red-500 dark:text-red-400');
                                        $distBg =
                                            $closestM <= 500
                                                ? 'bg-emerald-100 dark:bg-emerald-900/30'
                                                : ($closestM <= 1500
                                                    ? 'bg-amber-100 dark:bg-amber-900/30'
                                                    : 'bg-red-100 dark:bg-red-900/30');
                                    @endphp
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="text-base">{{ $icon }}</span>
                                            <span
                                                class="text-xs text-gray-700 dark:text-slate-300">{{ $group['label'] }}</span>
                                            @if (($group['count'] ?? 0) > 1)
                                                <span
                                                    class="text-[10px] text-gray-400 dark:text-slate-500">({{ $group['count'] }})</span>
                                            @endif
                                        </div>
                                        <span
                                            class="text-xs font-semibold px-1.5 py-0.5 rounded {{ $distBg }} {{ $distColor }}">
                                            {{ $closestM < 1000 ? $closestM . 'm' : number_format($closestM / 1000, 1) . 'km' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- STEP 2: Reason Insight Chips --}}
        @if ($hasMie && count($insightChips) > 0)
            <div class="px-6 py-3 border-t border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/30">
                <div class="flex flex-wrap gap-2">
                    @foreach ($insightChips as $chip)
                        @php
                            $chipStyle = match ($chip['type']) {
                                'strong'
                                    => 'bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
                                'moderate'
                                    => 'bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                                'weak'
                                    => 'bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border-red-200 dark:border-red-800',
                                'missing'
                                    => 'bg-gray-100 dark:bg-slate-700/50 text-gray-500 dark:text-gray-400 border-gray-300 dark:border-slate-600',
                                default
                                    => 'bg-gray-100 dark:bg-slate-700/50 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-slate-600',
                            };
                            $chipIcon = match ($chip['type']) {
                                'strong' => '✓',
                                'moderate' => '~',
                                'weak' => '!',
                                'missing' => '✕',
                                default => '•',
                            };
                        @endphp
                        <span
                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full border {{ $chipStyle }}">
                            <span class="text-[10px]">{{ $chipIcon }}</span>
                            {{ $chip['text'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- STEP 5: Decision Advisor Section --}}
        @if ($decisionText || $humanSummary || $hasAdvisor)
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
                {{-- Decision Text (generated from MIE interpretation) --}}
                @if ($decisionText)
                    <div class="flex items-start gap-3 mb-3">
                        <div
                            class="w-8 h-8 rounded-lg {{ $signalBg }} flex items-center justify-center text-white text-sm flex-shrink-0 mt-0.5">
                            💡
                        </div>
                        <div>
                            <div
                                class="text-xs font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-1">
                                Karar Yardımcısı</div>
                            <p class="text-sm font-medium text-gray-800 dark:text-slate-200 leading-relaxed">
                                {{ $decisionText }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- MIE Human Summary --}}
                @if ($humanSummary && $humanSummary !== $decisionText)
                    <p
                        class="text-sm text-gray-600 dark:text-slate-400 leading-relaxed {{ $decisionText ? 'ml-11' : '' }}">
                        {{ $humanSummary }}
                    </p>
                @endif

                {{-- AI Advisor --}}
                @if ($hasAdvisor)
                    <div class="flex items-start gap-2 mt-3 {{ $decisionText ? 'ml-11' : '' }}">
                        <span class="text-base mt-0.5">🧠</span>
                        <p class="text-sm text-gray-500 dark:text-slate-400 leading-relaxed italic">
                            {{ $advisorInsight->summary }}
                        </p>
                    </div>
                @endif
            </div>
        @endif

        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- ACTION MODE v5: "Bu arsayı ne yapmalısın?" — Composite --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        @if ($hasActionMode)
            @php
                $amLevel = $actionMode->decision_level;
                $amScore = $actionMode->composite_score;

                $actionLabelBg = match ($amLevel) {
                    'hot'
                        => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
                    'balanced'
                        => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border-blue-300 dark:border-blue-700',
                    'risky'
                        => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-700',
                    default
                        => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-300 dark:border-red-700',
                };

                $ctaBg = match ($actionMode->cta_action) {
                    'buy' => 'bg-emerald-600 hover:bg-emerald-700 dark:bg-emerald-700 dark:hover:bg-emerald-600',
                    'watch' => 'bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-500',
                    default => 'bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-500',
                };

                $scoreBg = match ($amLevel) {
                    'hot' => 'bg-emerald-500',
                    'balanced' => 'bg-blue-500',
                    'risky' => 'bg-amber-500',
                    default => 'bg-red-500',
                };
                $scoreRing = match ($amLevel) {
                    'hot' => 'ring-emerald-500/20',
                    'balanced' => 'ring-blue-500/20',
                    'risky' => 'ring-amber-500/20',
                    default => 'ring-red-500/20',
                };
            @endphp

            <div
                class="px-6 py-5 border-t-2 border-indigo-200 dark:border-indigo-800 bg-gradient-to-b from-indigo-50/50 to-white dark:from-indigo-950/20 dark:to-slate-900">

                {{-- ACTION MODE Header with Composite Score --}}
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🎯</span>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-slate-100 uppercase tracking-wider">
                            Aksiyon Modu</h4>
                        <span
                            class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-semibold border border-indigo-200 dark:border-indigo-700">MIE
                            v5</span>
                    </div>
                    <div
                        class="w-11 h-11 rounded-xl {{ $scoreBg }} ring-4 {{ $scoreRing }} flex items-center justify-center text-white font-bold text-sm">
                        {{ $amScore }}
                    </div>
                </div>

                {{-- Composite Score Breakdown Mini-Bar --}}
                <div
                    class="mb-4 p-3 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <div
                        class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">
                        Bileşik Skor Dağılımı</div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        @php
                            $miniScores = [
                                [
                                    'label' => 'Konum',
                                    'score' => $actionMode->location_score,
                                    'max' => 100,
                                    'icon' => '📍',
                                    'available' => $actionMode->has_location_data,
                                ],
                                [
                                    'label' => 'Fiyat',
                                    'score' => $actionMode->pricing_score,
                                    'max' => 100,
                                    'icon' => '💰',
                                    'available' => $actionMode->has_pricing_data,
                                ],
                                [
                                    'label' => 'Talep',
                                    'score' => $actionMode->demand_score,
                                    'max' => 100,
                                    'icon' => '📊',
                                    'available' => $actionMode->has_pricing_data,
                                ],
                                [
                                    'label' => 'Fırsat',
                                    'score' => $actionMode->opportunity_score,
                                    'max' => 100,
                                    'icon' => '🎯',
                                    'available' => $actionMode->has_pricing_data,
                                ],
                            ];
                        @endphp
                        @foreach ($miniScores as $ms)
                            <div class="text-center">
                                <div class="text-base mb-1">{{ $ms['icon'] }}</div>
                                @if ($ms['available'])
                                    @php
                                        $msPct = min(100, ($ms['score'] / $ms['max']) * 100);
                                        $msColor =
                                            $msPct >= 60
                                                ? 'bg-emerald-500'
                                                : ($msPct >= 30
                                                    ? 'bg-amber-500'
                                                    : 'bg-red-500');
                                    @endphp
                                    <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-1.5 mb-1">
                                        <div class="h-1.5 rounded-full {{ $msColor }}"
                                            style="width: {{ $msPct }}%"></div>
                                    </div>
                                    <div class="text-[10px] font-bold text-gray-700 dark:text-slate-300">
                                        {{ $ms['score'] }}/{{ $ms['max'] }}</div>
                                @else
                                    <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-1.5 mb-1">
                                        <div class="h-1.5 rounded-full bg-gray-400" style="width: 0%"></div>
                                    </div>
                                    <div class="text-[10px] text-gray-400 dark:text-slate-500">Veri yok</div>
                                @endif
                                <div class="text-[10px] text-gray-500 dark:text-slate-400">{{ $ms['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- STEP 1: Decision Label --}}
                <div class="mb-4">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg border text-sm font-bold {{ $actionLabelBg }}">
                        {{ $actionMode->decision_label }}
                    </div>
                </div>

                {{-- STEP 2: Investment Angle — "Bu lokasyon kim için uygun?" --}}
                @if (count($actionMode->investment_angles) > 0)
                    <div class="mb-4">
                        <div
                            class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">
                            Bu lokasyon kim için uygun?</div>
                        <div class="grid gap-2">
                            @foreach ($actionMode->investment_angles as $angle)
                                <div
                                    class="flex items-start gap-3 p-3 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                                    <span class="text-xl flex-shrink-0">{{ $angle['icon'] }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-semibold text-gray-800 dark:text-slate-200">
                                                {{ $angle['label'] }}</span>
                                            @if (isset($angle['fit_score']))
                                                @php
                                                    $fitColor =
                                                        ($angle['fit_score'] ?? 0) >= 60
                                                            ? 'text-emerald-600 dark:text-emerald-400'
                                                            : 'text-amber-600 dark:text-amber-400';
                                                @endphp
                                                <span class="text-[10px] font-bold {{ $fitColor }}">
                                                    %{{ $angle['fit_score'] }} uyum</span>
                                            @endif
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">
                                            {{ $angle['reason'] }}</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- STEP 3 & 4: Opportunity + Risk Panels --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                    {{-- Opportunities --}}
                    <div
                        class="p-3 rounded-lg bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800">
                        <div
                            class="text-[10px] font-semibold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-2">
                            ✓ Fırsatlar</div>
                        @if (count($actionMode->opportunities) > 0)
                            <ul class="space-y-1.5">
                                @foreach (array_slice($actionMode->opportunities, 0, 5) as $opp)
                                    @php
                                        $oppDot =
                                            ($opp['strength'] ?? '') === 'strong'
                                                ? 'text-emerald-600 dark:text-emerald-400 font-bold'
                                                : 'text-emerald-500 dark:text-emerald-400';
                                    @endphp
                                    <li
                                        class="text-xs text-emerald-800 dark:text-emerald-300 flex items-start gap-1.5">
                                        <span class="{{ $oppDot }} mt-0.5 flex-shrink-0">+</span>
                                        {{ $opp['text'] }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-emerald-600/60 dark:text-emerald-400/60 italic">Belirgin fırsat
                                tespit edilemedi</p>
                        @endif
                    </div>

                    {{-- Risks --}}
                    <div class="p-3 rounded-lg bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800">
                        <div
                            class="text-[10px] font-semibold text-red-600 dark:text-red-400 uppercase tracking-wider mb-2">
                            ⚠ Riskler</div>
                        @if (count($actionMode->risks) > 0)
                            <ul class="space-y-1.5">
                                @foreach (array_slice($actionMode->risks, 0, 5) as $risk)
                                    @php
                                        $riskDot =
                                            ($risk['severity'] ?? '') === 'high'
                                                ? 'text-red-600 dark:text-red-400 font-bold'
                                                : 'text-red-500 dark:text-red-400';
                                    @endphp
                                    <li class="text-xs text-red-800 dark:text-red-300 flex items-start gap-1.5">
                                        <span class="{{ $riskDot }} mt-0.5 flex-shrink-0">−</span>
                                        {{ $risk['text'] }}
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-red-600/60 dark:text-red-400/60 italic">Risk tespit edilemedi</p>
                        @endif
                    </div>
                </div>

                {{-- STEP 6: Advisor Narrative --}}
                @if ($actionMode->advisor_narrative)
                    <div
                        class="mb-4 p-3 rounded-lg bg-slate-50 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700">
                        <div class="flex items-start gap-2">
                            <span class="text-base mt-0.5 flex-shrink-0">🧠</span>
                            <p class="text-sm text-gray-700 dark:text-slate-300 leading-relaxed">
                                {{ $actionMode->advisor_narrative }}
                            </p>
                        </div>
                    </div>
                @endif

                {{-- STEP 5: Clear CTA --}}
                <div
                    class="flex items-center justify-between p-3 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <div class="text-xs font-medium text-gray-500 dark:text-slate-400">Bu ilan için öneri:</div>
                    <div
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-white text-sm font-bold shadow-sm transition-colors {{ $ctaBg }}">
                        <span>{{ $actionMode->cta_icon }}</span>
                        {{ $actionMode->cta_label }}
                    </div>
                </div>

                {{-- ACTION PANEL: Next-Step CTAs --}}
                <div
                    class="mt-4 p-4 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <div
                        class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-3">
                        Sonraki Adımlar</div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <a href="{{ route('admin.ilanlar.show', $ilan->id) }}#pricing-insight"
                            class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-300 dark:hover:border-blue-700 transition-colors text-center group">
                            <span class="text-lg group-hover:scale-110 transition-transform">💰</span>
                            <span class="text-[11px] font-semibold text-gray-700 dark:text-slate-300">Fiyatı
                                İncele</span>
                        </a>
                        <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Takip özelliği yakında aktif olacak'}}))"
                            class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:border-amber-300 dark:hover:border-amber-700 transition-colors text-center group">
                            <span class="text-lg group-hover:scale-110 transition-transform">👁️</span>
                            <span class="text-[11px] font-semibold text-gray-700 dark:text-slate-300">Takibe Al</span>
                        </button>
                        <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Portföy özelliği yakında aktif olacak'}}))"
                            class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 hover:bg-emerald-50 dark:hover:bg-emerald-900/20 hover:border-emerald-300 dark:hover:border-emerald-700 transition-colors text-center group">
                            <span class="text-lg group-hover:scale-110 transition-transform">📁</span>
                            <span class="text-[11px] font-semibold text-gray-700 dark:text-slate-300">Portföye
                                Ekle</span>
                        </button>
                        <button type="button"
                            onclick="window.dispatchEvent(new CustomEvent('show-toast', {detail: {message: 'Danışman atama özelliği yakında aktif olacak'}}))"
                            class="flex flex-col items-center gap-1.5 px-3 py-3 rounded-lg border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50 hover:bg-purple-50 dark:hover:bg-purple-900/20 hover:border-purple-300 dark:hover:border-purple-700 transition-colors text-center group">
                            <span class="text-lg group-hover:scale-110 transition-transform">🧑‍💼</span>
                            <span class="text-[11px] font-semibold text-gray-700 dark:text-slate-300">Danışmana
                                Ata</span>
                        </button>
                    </div>
                    <p class="text-[10px] text-gray-400 dark:text-slate-500 mt-2 text-center">Otomatik işlem yapılmaz —
                        sadece yönlendirme sağlar</p>
                </div>

                {{-- Data Source Indicator --}}
                <div class="mt-3 flex items-center gap-3 text-[10px] text-gray-400 dark:text-slate-500">
                    <span>Kaynaklar:</span>
                    @if ($actionMode->has_location_data)
                        <span
                            class="px-1.5 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">📍
                            Konum</span>
                    @endif
                    @if ($actionMode->has_pricing_data)
                        <span
                            class="px-1.5 py-0.5 rounded bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">💰
                            Fiyat</span>
                        <span
                            class="px-1.5 py-0.5 rounded bg-purple-100 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400">📊
                            Talep</span>
                    @endif
                    @if (!$actionMode->has_location_data && !$actionMode->has_pricing_data)
                        <span
                            class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-700 text-gray-500 dark:text-slate-400">Yetersiz
                            veri</span>
                    @endif
                </div>
            </div>
        @endif
        {{-- ═══════════════════════════════════════════════════════ --}}
        {{-- AUDIT FOLD: İç Denetim Detayları (Collapsible)      --}}
        {{-- ═══════════════════════════════════════════════════════ --}}
        @if ($hasMie || $hasActionMode)
            <div x-data="{ auditOpen: false }" class="border-t border-gray-200 dark:border-slate-700">
                <button @click="auditOpen = !auditOpen" type="button"
                    class="w-full px-6 py-3 flex items-center justify-between bg-gray-50/50 dark:bg-slate-800/30 hover:bg-gray-100 dark:hover:bg-slate-800/50 transition-colors">
                    <div class="flex items-center gap-2">
                        <span class="text-xs">🔍</span>
                        <span
                            class="text-[11px] font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Denetim
                            Detayları</span>
                    </div>
                    <svg class="w-4 h-4 text-gray-400 dark:text-slate-500 transition-transform"
                        :class="auditOpen && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="auditOpen" x-collapse class="px-6 py-4 bg-gray-50/30 dark:bg-slate-800/20">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-xs">

                        {{-- Score Components --}}
                        @if ($hasActionMode)
                            <div>
                                <div
                                    class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">
                                    Skor Bileşenleri</div>
                                <div class="space-y-1">
                                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                                        <span>Konum Skoru</span>
                                        <span
                                            class="font-mono font-semibold">{{ $actionMode->location_score }}/100</span>
                                    </div>
                                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                                        <span>Fiyat Skoru</span>
                                        <span
                                            class="font-mono font-semibold">{{ $actionMode->pricing_score }}/100</span>
                                    </div>
                                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                                        <span>Talep Skoru</span>
                                        <span
                                            class="font-mono font-semibold">{{ $actionMode->demand_score }}/100</span>
                                    </div>
                                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                                        <span>Fırsat Skoru</span>
                                        <span
                                            class="font-mono font-semibold">{{ $actionMode->opportunity_score }}/100</span>
                                    </div>
                                    <div
                                        class="flex justify-between text-gray-700 dark:text-slate-300 font-semibold border-t border-gray-200 dark:border-slate-700 pt-1 mt-1">
                                        <span>Bileşik Skor</span>
                                        <span class="font-mono">{{ $actionMode->composite_score }}/100</span>
                                    </div>
                                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                                        <span>Güven Skoru</span>
                                        <span
                                            class="font-mono font-semibold">{{ $actionMode->confidence_score }}/100</span>
                                    </div>
                                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                                        <span>Karar Seviyesi</span>
                                        <span class="font-mono font-semibold">{{ $actionMode->decision_level }}</span>
                                    </div>
                                    <div class="flex justify-between text-gray-600 dark:text-slate-400">
                                        <span>Fiyat Pozisyonu</span>
                                        <span
                                            class="font-mono font-semibold">{{ $actionMode->pricing_position ?: '—' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- POI Groups --}}
                        @if ($hasMie && count($topGroups) > 0)
                            <div>
                                <div
                                    class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">
                                    POI Grup Detayları</div>
                                <div class="space-y-1.5">
                                    @foreach ($topGroups as $g)
                                        @php
                                            $gIcon = $groupIcons[$g['group']] ?? '📌';
                                            $gLabel = $groupLabels[$g['group']] ?? $g['group'];
                                            $gDist = $g['closest_m'] ?? 0;
                                            $gCount = $g['count'] ?? 0;
                                            $gStrength = $groupStrengthMap[$g['group']] ?? 'weak';
                                            $gColor = match ($gStrength) {
                                                'strong' => 'text-emerald-600 dark:text-emerald-400',
                                                'moderate' => 'text-amber-600 dark:text-amber-400',
                                                default => 'text-red-500 dark:text-red-400',
                                            };
                                        @endphp
                                        <div
                                            class="flex items-center justify-between text-gray-600 dark:text-slate-400">
                                            <span class="flex items-center gap-1.5">
                                                <span>{{ $gIcon }}</span>
                                                {{ $gLabel }}
                                                <span
                                                    class="text-[10px] text-gray-400 dark:text-slate-500">({{ $gCount }})</span>
                                            </span>
                                            <span class="font-mono font-semibold {{ $gColor }}">
                                                {{ $gDist < 1000 ? $gDist . 'm' : number_format($gDist / 1000, 1) . 'km' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Reason Codes + Geometry --}}
                        <div>
                            @if ($hasMie && count($reasonCodes) > 0)
                                <div
                                    class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">
                                    Reason Codes</div>
                                <div class="flex flex-wrap gap-1 mb-3">
                                    @foreach ($reasonCodes as $rc)
                                        <span
                                            class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-700 text-[10px] font-mono text-gray-600 dark:text-slate-400">{{ $rc }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div
                                class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mb-2">
                                Geometri</div>
                            <div class="space-y-1 text-gray-600 dark:text-slate-400">
                                <div class="flex justify-between">
                                    <span>Tür</span>
                                    <span class="font-mono font-semibold">{{ $ilan->geometry_type ?? 'point' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Koordinat</span>
                                    <span
                                        class="font-mono font-semibold text-[10px]">{{ $ilan->lat ? number_format($ilan->lat, 6) : '—' }},
                                        {{ $ilan->lng ? number_format($ilan->lng, 6) : '—' }}</span>
                                </div>
                                @if ($hasPolygon && $ilan->geometry)
                                    @php
                                        $coords = data_get(json_decode($ilan->geometry, true), 'coordinates.0', []);
                                        $vertexCount = count($coords);
                                    @endphp
                                    <div class="flex justify-between">
                                        <span>Köşe Sayısı</span>
                                        <span class="font-mono font-semibold">{{ $vertexCount }}</span>
                                    </div>
                                @endif
                                @if ($hasMie)
                                    <div class="flex justify-between">
                                        <span>Güven</span>
                                        <span class="font-mono font-semibold">{{ $confidenceLabel }}</span>
                                    </div>
                                @endif
                            </div>

                            <div
                                class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider mt-3 mb-1">
                                Motor</div>
                            <div class="space-y-1 text-gray-600 dark:text-slate-400">
                                <div class="flex justify-between">
                                    <span>MIE Versiyon</span>
                                    <span class="font-mono font-semibold">{{ $hasActionMode ? 'v5' : 'v4' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Konum Verisi</span>
                                    <span class="font-mono font-semibold">{{ $hasMie ? '✓' : '✕' }}</span>
                                </div>
                                @if ($hasActionMode)
                                    <div class="flex justify-between">
                                        <span>Fiyat Verisi</span>
                                        <span
                                            class="font-mono font-semibold">{{ $actionMode->has_pricing_data ? '✓' : '✕' }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <p
                        class="text-[10px] text-gray-400 dark:text-slate-500 mt-4 pt-3 border-t border-gray-200 dark:border-slate-700">
                        Bu bölüm iç denetim ve güven doğrulaması içindir. Yatırımcı sunumunda gizli kalır.
                    </p>
                </div>
            </div>
        @endif

    </section>

    {{-- Intelligence Map Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapEl = document.getElementById('intelligence-map');
            if (!mapEl || typeof L === 'undefined') return;

            const lat = {{ $ilan->lat ?? 'null' }};
            const lng = {{ $ilan->lng ?? 'null' }};
            const geojson = @json($hasPolygon ? $ilan->geometry : null);
            const poiTypeIcons = @json(json_decode($poiTypeIcons, true));
            const groupStrength = @json($groupStrengthMap);

            // Group → strength color mapping for POI glow
            const strengthColors = {
                strong: {
                    color: '#00c853',
                    glow: '0 0 8px rgba(0,200,83,0.6)'
                },
                moderate: {
                    color: '#ff9800',
                    glow: '0 0 8px rgba(255,152,0,0.5)'
                },
                weak: {
                    color: '#ef4444',
                    glow: '0 0 8px rgba(239,68,68,0.5)'
                }
            };

            // poi_turu → group mapping (simplified)
            const typeToGroup = {
                school: 'education',
                university: 'education',
                kindergarten: 'education',
                hospital: 'health',
                clinic: 'health',
                pharmacy: 'health',
                doctors: 'health',
                supermarket: 'shopping',
                marketplace: 'shopping',
                mall: 'shopping',
                shop: 'shopping',
                bus_station: 'transport',
                bus_stop: 'transport',
                ferry_terminal: 'transport',
                restaurant: 'food_social',
                cafe: 'food_social',
                fast_food: 'food_social',
                park: 'green_leisure',
                garden: 'green_leisure',
                beach: 'green_leisure',
                playground: 'green_leisure',
                bank: 'daily_need',
                atm: 'daily_need',
                post_office: 'daily_need',
                mosque: 'daily_need',
                police: 'daily_need',
                fire_station: 'daily_need',
                fuel: 'daily_need',
                parking: 'transport'
            };

            // Initialize map
            const map = L.map(mapEl, {
                zoomControl: true,
                attributionControl: false,
                scrollWheelZoom: true
            });

            // Satellite tiles
            L.tileLayer(
                'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                    maxZoom: 19
                }
            ).addTo(map);

            let center = (lat && lng) ? [lat, lng] : null;

            // ─── 1. POLYGON OVERLAY ───
            if (geojson) {
                const polyLayer = L.geoJSON(geojson, {
                    style: {
                        color: '#00c853',
                        fillColor: '#00c853',
                        fillOpacity: 0.15,
                        weight: 2,
                        dashArray: '6 4'
                    }
                }).addTo(map);

                const polyCenter = polyLayer.getBounds().getCenter();
                center = center || [polyCenter.lat, polyCenter.lng];
                map.fitBounds(polyLayer.getBounds(), {
                    padding: [80, 80]
                });
            } else if (center) {
                map.setView(center, 14);
            }

            if (!center) return;

            // ─── 2. LOCATION MARKER ───
            if (lat && lng) {
                const locIcon = L.divIcon({
                    html: '<div style="width:16px;height:16px;background:#3b82f6;border:3px solid #fff;border-radius:50%;box-shadow:0 2px 8px rgba(59,130,246,0.5);"></div>',
                    className: '',
                    iconSize: [16, 16],
                    iconAnchor: [8, 8]
                });
                L.marker([lat, lng], {
                    icon: locIcon,
                    zIndexOffset: 1000
                }).addTo(map);
            }

            // ─── 3. RADIUS CIRCLE (2km) ───
            L.circle(center, {
                radius: 2000,
                color: '#6366f1',
                fillColor: '#6366f1',
                fillOpacity: 0.04,
                weight: 1.5,
                dashArray: '6 8'
            }).addTo(map);

            // ─── 4. POI OVERLAY WITH STRENGTH GLOW ───
            if (lat && lng) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                fetch('/api/v1/location/poi-distances', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        },
                        body: JSON.stringify({
                            lat: lat,
                            lng: lng,
                            radius_km: 2
                        })
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(response) {
                        const pois = response.data?.pois || response.data || [];
                        if (!Array.isArray(pois)) return;

                        pois.forEach(function(poi) {
                            if (!poi.lat || !poi.lng) return;

                            const poiTuru = poi.poi_turu || poi.type || '';
                            const emoji = poiTypeIcons[poiTuru] || '📍';
                            const name = poi.poi_adi || poi.name || '';
                            const distM = poi.distance || Math.round((poi.distance_km || 0) * 1000);

                            // STEP 3: Determine glow color from group strength
                            const group = typeToGroup[poiTuru] || '';
                            const strength = groupStrength[group] || 'moderate';
                            const sc = strengthColors[strength] || strengthColors.moderate;

                            const icon = L.divIcon({
                                html: '<span style="font-size:18px;filter:drop-shadow(' + sc
                                    .glow + ');">' +
                                    emoji + '</span>',
                                className: '',
                                iconSize: [24, 24],
                                iconAnchor: [12, 12]
                            });

                            const strengthLabel = strength === 'strong' ? '✓ Güçlü' : (strength ===
                                'weak' ? '! Zayıf' : '~ Orta');

                            L.marker([poi.lat, poi.lng], {
                                    icon: icon
                                })
                                .bindTooltip(
                                    '<div style="min-width:120px;">' +
                                    '<strong>' + name + '</strong><br>' +
                                    '<span style="color:' + sc.color + ';font-weight:600;">' + distM +
                                    'm</span>' +
                                    ' · <span style="font-size:10px;opacity:0.8;">' + strengthLabel +
                                    '</span>' +
                                    '</div>', {
                                        direction: 'top',
                                        offset: [0, -8],
                                        className: 'poi-tooltip-insight'
                                    }
                                )
                                .addTo(map);
                        });
                    })
                    .catch(function() {});
            }
        });
    </script>

    {{-- POI Tooltip Style --}}
    <style>
        .poi-tooltip-insight {
            font-size: 12px;
            line-height: 1.4;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid rgba(0, 0, 0, 0.08);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
    </style>
@endif
