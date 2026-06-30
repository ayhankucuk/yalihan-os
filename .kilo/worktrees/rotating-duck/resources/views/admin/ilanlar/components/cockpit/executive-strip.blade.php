{{-- SAB — Executive Strip: Tek satır karar özeti --}}
{{-- Location Score + Opportunity + Confidence + Action Label --}}

@php
    $hasExecAction = isset($actionMode) && $actionMode !== null;
    $hasExecLocation = isset($locationInsight) && $locationInsight && $locationInsight->data_status === 'ok';
    $hasExecPricing = isset($pricingInsight) && $pricingInsight && !$pricingInsight->insufficient_data;

    // Show strip only if we have at least some intelligence data
    $showStrip = $hasExecAction || $hasExecLocation || $hasExecPricing;

    if ($hasExecAction) {
        $execScore = $actionMode->composite_score;
        $execLevel = $actionMode->decision_level;
        $execLabel = $actionMode->decision_label;
        $execCta = $actionMode->cta_label;
        $execCtaAction = $actionMode->cta_action;
        $execOpportunity = $actionMode->opportunity_action;
        $execConfidence = $actionMode->confidence_score;
    } else {
        $execScore = $hasExecLocation ? $locationInsight->location_signal_score ?? 0 : 0;
        $execLevel = $execScore >= 65 ? 'hot' : ($execScore >= 35 ? 'balanced' : 'risky');
        $execLabel = $execScore >= 65 ? 'Güçlü Konum' : ($execScore >= 35 ? 'Orta Konum' : 'Zayıf Konum');
        $execCta = '—';
        $execCtaAction = 'unknown';
        $execOpportunity = $hasExecPricing ? $pricingInsight->opportunity_action ?? '—' : '—';
        $execConfidence = $hasExecPricing ? $pricingInsight->confidence_score : 0;
    }

    // Colors
    $scoreBg = match ($execLevel) {
        'hot' => 'bg-emerald-500',
        'balanced' => 'bg-blue-500',
        'risky' => 'bg-amber-500',
        default => 'bg-red-500',
    };
    $scoreRing = match ($execLevel) {
        'hot' => 'ring-emerald-500/20',
        'balanced' => 'ring-blue-500/20',
        'risky' => 'ring-amber-500/20',
        default => 'ring-red-500/20',
    };
    $labelBg = match ($execLevel) {
        'hot'
            => 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-800 dark:text-emerald-300 border-emerald-300 dark:border-emerald-700',
        'balanced'
            => 'bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 border-blue-300 dark:border-blue-700',
        'risky'
            => 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 border-amber-300 dark:border-amber-700',
        default => 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-300 dark:border-red-700',
    };

    // Opportunity color
    $oppColor = match ($execOpportunity) {
        'BUY' => 'text-emerald-600 dark:text-emerald-400',
        'WAIT' => 'text-amber-600 dark:text-amber-400',
        'SELL' => 'text-red-600 dark:text-red-400',
        default => 'text-gray-500 dark:text-slate-400',
    };
    $oppLabel = match ($execOpportunity) {
        'BUY' => 'FIRSAT',
        'WAIT' => 'İZLE',
        'SELL' => 'REVİZYON',
        'INSUFFICIENT_DATA' => 'VERİ YOK',
        default => '—',
    };

    // Confidence color + label
    $confColor = match (true) {
        $execConfidence >= 80 => 'text-emerald-600 dark:text-emerald-400',
        $execConfidence >= 50 => 'text-blue-600 dark:text-blue-400',
        $execConfidence >= 20 => 'text-amber-600 dark:text-amber-400',
        default => 'text-red-500 dark:text-red-400',
    };
    $confLabel = match (true) {
        $execConfidence >= 80 => 'YÜKSEK',
        $execConfidence >= 50 => 'ORTA',
        $execConfidence >= 20 => 'DÜŞÜK',
        default => 'ÇOK DÜŞÜK',
    };

    // Microcopy
    $microCopy = match ($execLevel) {
        'hot' => 'Konum sinyali güçlü — karar desteği mevcut',
        'balanced' => 'Dengeli fırsat — detaylı inceleme önerilir',
        'risky' => 'Temkinli yaklaş — riskler mevcut',
        'avoid' => 'Dikkatli değerlendir — belirgin riskler var',
        default => 'Veri toplanıyor',
    };
@endphp

@if ($showStrip)
    <div
        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 rounded-lg shadow-sm dark:shadow-none overflow-hidden">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between gap-6">
                {{-- Score Badge --}}
                <div class="flex items-center gap-4">
                    <div
                        class="w-14 h-14 rounded-xl {{ $scoreBg }} ring-4 {{ $scoreRing }} flex items-center justify-center text-white font-bold text-lg flex-shrink-0">
                        {{ $execScore }}
                    </div>
                    <div>
                        <div
                            class="inline-flex items-center px-3 py-1 rounded-md border text-xs font-bold {{ $labelBg }}">
                            {{ $execLabel }}
                        </div>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-1">{{ $microCopy }}</p>
                    </div>
                </div>

                {{-- Signal Gauges --}}
                <div class="hidden md:flex items-center gap-6">
                    {{-- Opportunity --}}
                    <div class="text-center">
                        <div
                            class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">
                            Fırsat</div>
                        <div class="text-sm font-bold {{ $oppColor }} mt-0.5">{{ $oppLabel }}</div>
                    </div>

                    <div class="h-8 w-px bg-gray-200 dark:bg-slate-700"></div>

                    {{-- Confidence --}}
                    <div class="text-center">
                        <div
                            class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">
                            Güven</div>
                        <div class="text-sm font-bold {{ $confColor }} mt-0.5">{{ $confLabel }}</div>
                    </div>

                    <div class="h-8 w-px bg-gray-200 dark:bg-slate-700"></div>

                    {{-- Action Recommendation --}}
                    <div class="text-center">
                        <div
                            class="text-[10px] font-semibold text-gray-400 dark:text-slate-500 uppercase tracking-wider">
                            Öneri</div>
                        @if ($hasExecAction)
                            @php
                                $ctaColor = match ($execCtaAction) {
                                    'buy' => 'bg-emerald-600 dark:bg-emerald-700 text-white',
                                    'watch' => 'bg-amber-500 dark:bg-amber-600 text-white',
                                    default => 'bg-red-500 dark:bg-red-600 text-white',
                                };
                            @endphp
                            <span
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-bold {{ $ctaColor }} mt-0.5">
                                {{ $execCta }}
                            </span>
                        @else
                            <div class="text-sm font-bold text-gray-400 dark:text-slate-500 mt-0.5">—</div>
                        @endif
                    </div>
                </div>

                {{-- MIE version badge --}}
                <div class="flex-shrink-0">
                    <span
                        class="text-[10px] px-2 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 font-semibold border border-indigo-200 dark:border-indigo-700">
                        MIE v5
                    </span>
                </div>
            </div>
        </div>

        {{-- Trust Stack Micro --}}
        <div class="px-6 py-2 border-t border-gray-100 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/30">
            <p class="text-[10px] text-gray-400 dark:text-slate-500">
                Bu değerlendirme fiyat, konum ve çevresel hizmet sinyallerine dayanır. AI açıklama üretir; nihai karar
                kullanıcıya aittir.
            </p>
        </div>
    </div>
@endif
