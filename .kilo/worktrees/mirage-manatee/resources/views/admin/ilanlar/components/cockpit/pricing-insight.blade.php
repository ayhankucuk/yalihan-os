{{-- MIE v1 Alpha — Pricing Insight Card --}}
{{-- Deterministic pricing position card. No AI, no rand(). --}}

@if (isset($pricingInsight))
    <div
        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg overflow-hidden shadow-sm">
        <div
            class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900 flex justify-between items-center">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100">Piyasa Pozisyonu</h3>
            @php
                $badgeColors = [
                    'underpriced' =>
                        'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800',
                    'fair' =>
                        'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
                    'overpriced' =>
                        'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                    'aggressively_overpriced' =>
                        'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border-red-200 dark:border-red-800',
                    'insufficient_data' =>
                        'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-200 dark:border-gray-700',
                ];
                $posValue = $pricingInsight->pricing_position->value;
                $badgeClass = $badgeColors[$posValue] ?? $badgeColors['insufficient_data'];
            @endphp
            <span class="px-2 py-0.5 text-xs font-medium rounded border {{ $badgeClass }}">
                {{ $pricingInsight->pricing_position->icon() }} {{ $pricingInsight->pricing_position->label() }}
            </span>
        </div>

        <div class="p-6 space-y-4">
            @if ($pricingInsight->insufficient_data)
                {{-- Yetersiz veri durumu --}}
                <div
                    class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700">
                    <span class="text-gray-400 dark:text-gray-500 text-lg">📊</span>
                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $pricingInsight->reason }}</p>
                </div>
            @else
                {{-- Fiyat karşılaştırma --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">İlan Fiyatı</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format($pricingInsight->current_price, 0, ',', '.') }} ₺
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Benchmark Medyan</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white">
                            {{ number_format($pricingInsight->benchmark_price, 0, ',', '.') }} ₺
                        </p>
                    </div>
                </div>

                {{-- Skor ve Sapma --}}
                <div class="flex items-center gap-4">
                    {{-- Pricing Score Bar --}}
                    <div class="flex-1">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <span>Piyasa Uyum Skoru</span>
                            <span
                                class="font-semibold text-gray-900 dark:text-white">{{ $pricingInsight->pricing_score }}/100</span>
                        </div>
                        @php
                            $barColor = match (true) {
                                $pricingInsight->pricing_score >= 80 => 'bg-green-500',
                                $pricingInsight->pricing_score >= 60 => 'bg-blue-500',
                                $pricingInsight->pricing_score >= 40 => 'bg-amber-500',
                                default => 'bg-red-500',
                            };
                        @endphp
                        <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2">
                            <div class="{{ $barColor }} h-2 rounded-full transition-all"
                                style="width: {{ $pricingInsight->pricing_score }}%"></div>
                        </div>
                    </div>

                    {{-- Delta --}}
                    <div class="text-right shrink-0">
                        @php
                            $deltaColor = match (true) {
                                $pricingInsight->price_delta_percent > 10 => 'text-red-600 dark:text-red-400',
                                $pricingInsight->price_delta_percent < -10 => 'text-green-600 dark:text-green-400',
                                default => 'text-gray-700 dark:text-gray-300',
                            };
                            $deltaSign = $pricingInsight->price_delta_percent > 0 ? '+' : '';
                        @endphp
                        <p class="text-xs text-gray-500 dark:text-gray-400">Sapma</p>
                        <p class="text-sm font-bold {{ $deltaColor }}">
                            {{ $deltaSign }}{{ $pricingInsight->price_delta_percent }}%
                        </p>
                    </div>
                </div>

                {{-- Açıklama --}}
                <div class="p-3 bg-gray-50 dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700">
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $pricingInsight->reason }}</p>
                </div>

                {{-- Güvenilirlik Detayı (Risk 1: Explainability) --}}
                <div class="p-3 bg-gray-50 dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Güvenilirlik</span>
                        @php
                            $confColor = match ($pricingInsight->confidence_label) {
                                'HIGH' => 'text-green-600 dark:text-green-400',
                                'MODERATE' => 'text-blue-600 dark:text-blue-400',
                                'LOW' => 'text-amber-600 dark:text-amber-400',
                                default => 'text-red-600 dark:text-red-400',
                            };
                            $confBadge = match ($pricingInsight->confidence_label) {
                                'HIGH' => 'Yüksek',
                                'MODERATE' => 'Orta',
                                'LOW' => 'Düşük',
                                default => 'Çok Düşük',
                            };
                        @endphp
                        <span
                            class="text-xs font-semibold {{ $confColor }}">{{ $pricingInsight->confidence_score }}/100
                            — {{ $confBadge }}</span>
                    </div>
                    @if (!empty($pricingInsight->confidence_reason))
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $pricingInsight->confidence_reason }}
                        </p>
                    @endif
                </div>

                {{-- Meta --}}
                <div class="flex justify-between text-xs text-gray-400 dark:text-gray-500">
                    <span>{{ $pricingInsight->sample_size }} karşılaştırılabilir ilan</span>
                </div>

                {{-- Opportunity Score & Action (MIE v1.3) --}}
                <div
                    class="mt-4 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Fırsat Skoru</span>
                        @php
                            $actionBadgeColors = [
                                'BUY' =>
                                    'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border-green-200 dark:border-green-800',
                                'WAIT' =>
                                    'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 border-blue-200 dark:border-blue-800',
                                'SELL' =>
                                    'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border-amber-200 dark:border-amber-800',
                                'INSUFFICIENT_DATA' =>
                                    'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700',
                            ];
                            $actionLabels = [
                                'BUY' => 'Fırsat',
                                'WAIT' => 'Bekle',
                                'SELL' => 'Revizyon',
                                'INSUFFICIENT_DATA' => 'Yetersiz Veri',
                            ];
                            $actionClass =
                                $actionBadgeColors[$pricingInsight->opportunity_action] ??
                                $actionBadgeColors['INSUFFICIENT_DATA'];
                            $actionLabel = $actionLabels[$pricingInsight->opportunity_action] ?? 'Yetersiz Veri';
                        @endphp
                        <span class="px-2 py-0.5 text-xs font-medium rounded border {{ $actionClass }}">
                            {{ $actionLabel }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            @php
                                $oppBarColor = match (true) {
                                    $pricingInsight->opportunity_score >= 70 => 'bg-green-500',
                                    $pricingInsight->opportunity_score >= 45 => 'bg-blue-500',
                                    $pricingInsight->opportunity_score >= 20 => 'bg-amber-500',
                                    default => 'bg-gray-400',
                                };
                            @endphp
                            <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-1.5">
                                <div class="{{ $oppBarColor }} h-1.5 rounded-full transition-all"
                                    style="width: {{ $pricingInsight->opportunity_score }}%"></div>
                            </div>
                        </div>
                        <span
                            class="text-xs font-semibold text-gray-700 dark:text-gray-300 shrink-0">{{ $pricingInsight->opportunity_score }}/100</span>
                    </div>
                    @if ($pricingInsight->opportunity_reason)
                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ $pricingInsight->opportunity_reason }}</p>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endif
