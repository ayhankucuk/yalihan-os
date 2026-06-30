{{-- SAB — Location Signal Card (MIE V4) --}}
{{-- Konum sinyali: Erişim → Yoğunluk → Çeşitlilik → Özet --}}

@if (isset($locationInsight) && $locationInsight && $locationInsight->data_status === 'ok')
    @php
        $signalScore = $locationInsight->location_signal_score ?? 0;
        $confidenceLabel = $locationInsight->confidence_label ?? 'VERY_LOW';
        $accessScore = $locationInsight->poi_access_score ?? 0;
        $densityScore = $locationInsight->poi_density_score ?? 0;
        $coverageScore = $locationInsight->poi_coverage_score ?? 0;
        $topGroups = $locationInsight->top_nearby_groups ?? [];
        $reasonCodes = $locationInsight->reason_codes ?? [];
        $humanSummary = $locationInsight->human_summary ?? '';

        // Sinyal seviye renkleri
        $signalColors = match (true) {
            $signalScore >= 65
                => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
            $signalScore >= 35
                => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
            default
                => 'bg-gray-100 dark:bg-slate-700/50 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-slate-600',
        };
        $signalLabel = match (true) {
            $signalScore >= 65 => 'Güçlü Konum',
            $signalScore >= 35 => 'Orta Konum',
            default => 'Zayıf Konum',
        };

        // Confidence renkleri
        $confColors = match ($confidenceLabel) {
            'HIGH'
                => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border-emerald-200 dark:border-emerald-800',
            'MEDIUM'
                => 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
            'LOW'
                => 'bg-orange-100 dark:bg-orange-900/30 text-orange-700 dark:text-orange-400 border-orange-200 dark:border-orange-800',
            default
                => 'bg-gray-100 dark:bg-slate-700/50 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-slate-600',
        };
        $confLabel = match ($confidenceLabel) {
            'HIGH' => 'Yüksek',
            'MEDIUM' => 'Orta',
            'LOW' => 'Düşük',
            default => 'Çok Düşük',
        };

        // Grup ikonları
        $groupIcons = [
            'education' => '🎓',
            'health' => '🏥',
            'shopping' => '🛍️',
            'transport' => '🚌',
            'food_social' => '🍽️',
            'green_leisure' => '🌳',
            'daily_need' => '🏦',
        ];

        // Config reason_codes
        $reasonTexts = config('location_intelligence.reason_codes', []);
    @endphp

    <section x-data="{ locationOpen: false }"
        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg overflow-hidden shadow-sm dark:shadow-none">

        {{-- Header --}}
        <div
            class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 flex justify-between items-center">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                📍 Konum Sinyali
                <span class="text-xs font-normal text-gray-500 dark:text-slate-400">MIE v4</span>
            </h3>
            <div class="flex items-center gap-2">
                {{-- Signal badge --}}
                <span class="px-2 py-0.5 text-xs font-medium rounded border {{ $signalColors }}">
                    {{ $signalLabel }} ({{ $signalScore }}/100)
                </span>
                {{-- Confidence badge --}}
                <span class="px-2 py-0.5 text-xs font-medium rounded border {{ $confColors }}">
                    Güven: {{ $confLabel }}
                </span>
            </div>
        </div>

        <div class="px-6 py-4 space-y-4">

            {{-- Summary --}}
            @if ($humanSummary)
                <p class="text-sm text-gray-700 dark:text-slate-300">{{ $humanSummary }}</p>
            @endif

            {{-- Score Bars --}}
            <div class="space-y-2">
                {{-- Access Score --}}
                <div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-slate-400 mb-1">
                        <span>Erişim</span>
                        <span>{{ $accessScore }}/40</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-1.5">
                        <div class="bg-blue-500 dark:bg-blue-400 h-1.5 rounded-full"
                            style="width: {{ min(100, ($accessScore / 40) * 100) }}%"></div>
                    </div>
                </div>

                {{-- Density Score --}}
                <div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-slate-400 mb-1">
                        <span>Yoğunluk</span>
                        <span>{{ $densityScore }}/30</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-1.5">
                        <div class="bg-violet-500 dark:bg-violet-400 h-1.5 rounded-full"
                            style="width: {{ min(100, ($densityScore / 30) * 100) }}%"></div>
                    </div>
                </div>

                {{-- Coverage Score --}}
                <div>
                    <div class="flex justify-between text-xs text-gray-500 dark:text-slate-400 mb-1">
                        <span>Çeşitlilik</span>
                        <span>{{ $coverageScore }}/30</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-1.5">
                        <div class="bg-teal-500 dark:bg-teal-400 h-1.5 rounded-full"
                            style="width: {{ min(100, ($coverageScore / 30) * 100) }}%"></div>
                    </div>
                </div>
            </div>

            {{-- Top Nearby Groups (max 5) --}}
            @if (count($topGroups) > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach (array_slice($topGroups, 0, 5) as $group)
                        @php
                            $icon = $groupIcons[$group['group']] ?? '📌';
                            $closestM = $group['closest_m'] ?? 0;
                            $distLabel =
                                $closestM < 250
                                    ? 'Çok yakın'
                                    : ($closestM < 750
                                        ? 'Yakın'
                                        : ($closestM < 1500
                                            ? 'Orta'
                                            : 'Uzak'));
                        @endphp
                        <span
                            class="inline-flex items-center gap-1 px-2 py-1 text-xs rounded bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-slate-300 border border-gray-200 dark:border-slate-700"
                            title="{{ $group['label'] }}: {{ $group['count'] }} nokta, en yakın {{ $closestM }}m">
                            {{ $icon }} {{ $group['label'] }}
                            <span class="text-gray-400 dark:text-slate-500">{{ $closestM }}m</span>
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Collapsible Details --}}
            @if (count($reasonCodes) > 0)
                <div>
                    <button type="button" @click="locationOpen = !locationOpen"
                        class="flex items-center gap-1 text-xs text-gray-500 dark:text-slate-400 hover:text-gray-700 dark:hover:text-slate-200 transition-colors">
                        <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-90': locationOpen }"
                            fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                        Detaylar
                    </button>
                    <div x-show="locationOpen" x-collapse class="mt-2 space-y-1">
                        @foreach ($reasonCodes as $code)
                            <div class="flex items-start gap-2 text-xs text-gray-600 dark:text-slate-400">
                                <span class="text-gray-400 dark:text-slate-500 mt-0.5">•</span>
                                <span>{{ $reasonTexts[$code] ?? $code }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@elseif (isset($locationInsight) && $locationInsight && $locationInsight->data_status === 'no_coordinates')
    {{-- No coordinates state --}}
    <section
        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg overflow-hidden shadow-sm dark:shadow-none">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 flex items-center gap-2">
                📍 Konum Sinyali
                <span class="text-xs font-normal text-gray-500 dark:text-slate-400">MIE v4</span>
            </h3>
        </div>
        <div class="px-6 py-4">
            <p class="text-xs text-gray-500 dark:text-slate-400">İlan koordinatı bulunmadığı için konum değerlendirmesi
                yapılamadı.</p>
        </div>
    </section>
@endif
