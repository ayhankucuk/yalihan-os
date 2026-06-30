{{--
    Trust Breakdown v2 — Karar Dağılımı (Trust Engine)
    Composite skorun bileşen bazlı şeffaf açıklaması.
    Veri göstermek değil — ikna etmek.

    @props: $data (array) — ActionModeDTO::toTrustBreakdown() çıktısı
--}}

@props(['data' => []])

@php
    $score = $data['composite_score'] ?? 0;
    $decisionLabel = $data['decision_label'] ?? 'Bilinmiyor';
    $confidenceLabel = $data['confidence_label'] ?? 'UNKNOWN';
    $confidenceNarrative = $data['confidence_narrative'] ?? '';
    $decisionNarrative = $data['decision_narrative'] ?? '';
    $summary = $data['summary'] ?? '';
    $components = $data['components'] ?? [];
    $riskNotes = $data['risk_notes'] ?? [];
    $strongestSignal = $data['strongest_signal'] ?? null;
    $weakestSignal = $data['weakest_signal'] ?? null;

    // Total contribution for bar width calculation
    $totalContribution = collect($components)->sum('contribution');
    $totalContribution = max($totalContribution, 1);

    // Decision color
    $decisionColor = match ($decisionLabel) {
        'Yüksek Potansiyel'
            => 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 border-emerald-200 dark:border-emerald-700',
        'Dengeli'
            => 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 border-blue-200 dark:border-blue-700',
        'Riskli'
            => 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 border-amber-200 dark:border-amber-700',
        'Düşük Potansiyel'
            => 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30 border-rose-200 dark:border-rose-700',
        default
            => 'text-slate-600 dark:text-slate-400 bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700',
    };

    // Confidence badge color
    $confColor = match ($confidenceLabel) {
        'HIGH' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
        'MEDIUM' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
        'LOW' => 'bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300',
        default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
    };

    $confLabel = match ($confidenceLabel) {
        'HIGH' => 'YÜKSEK',
        'MEDIUM' => 'ORTA',
        'LOW' => 'DÜŞÜK',
        default => 'BELİRSİZ',
    };

    // Score pill color
    $scorePillColor = match (true) {
        $score >= 80 => 'bg-emerald-600 dark:bg-emerald-500',
        $score >= 60 => 'bg-blue-600 dark:bg-blue-500',
        $score >= 40 => 'bg-amber-600 dark:bg-amber-500',
        default => 'bg-rose-600 dark:bg-rose-500',
    };

    // Component Tailwind color map
    $colorMap = [
        'emerald' => [
            'bg' => 'bg-emerald-500 dark:bg-emerald-400',
            'dot' => 'bg-emerald-500',
            'card_border' => 'border-emerald-200 dark:border-emerald-800',
            'card_bg' => 'bg-emerald-50/50 dark:bg-emerald-900/20',
            'text' => 'text-emerald-700 dark:text-emerald-300',
        ],
        'blue' => [
            'bg' => 'bg-blue-500 dark:bg-blue-400',
            'dot' => 'bg-blue-500',
            'card_border' => 'border-blue-200 dark:border-blue-800',
            'card_bg' => 'bg-blue-50/50 dark:bg-blue-900/20',
            'text' => 'text-blue-700 dark:text-blue-300',
        ],
        'amber' => [
            'bg' => 'bg-amber-500 dark:bg-amber-400',
            'dot' => 'bg-amber-500',
            'card_border' => 'border-amber-200 dark:border-amber-800',
            'card_bg' => 'bg-amber-50/50 dark:bg-amber-900/20',
            'text' => 'text-amber-700 dark:text-amber-300',
        ],
        'violet' => [
            'bg' => 'bg-violet-500 dark:bg-violet-400',
            'dot' => 'bg-violet-500',
            'card_border' => 'border-violet-200 dark:border-violet-800',
            'card_bg' => 'bg-violet-50/50 dark:bg-violet-900/20',
            'text' => 'text-violet-700 dark:text-violet-300',
        ],
    ];

    // Impact styling
    $impactStyle = function ($impact) {
        return match ($impact) {
            'supports' => [
                'label' => 'Destekliyor',
                'icon' => '↑',
                'class' => 'text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30',
            ],
            'weakens' => [
                'label' => 'Zayıflatıyor',
                'icon' => '↓',
                'class' => 'text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/30',
            ],
            default => [
                'label' => 'Nötr',
                'icon' => '→',
                'class' => 'text-slate-500 dark:text-slate-400 bg-slate-50 dark:bg-slate-800',
            ],
        };
    };

    $compConfColor = function ($conf) {
        return match ($conf) {
            'HIGH' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300',
            'MEDIUM' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',
            'LOW' => 'bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300',
            default => 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
        };
    };

    $compConfTurkish = function ($conf) {
        return match ($conf) {
            'HIGH' => 'YÜKSEK',
            'MEDIUM' => 'ORTA',
            'LOW' => 'DÜŞÜK',
            default => 'BELİRSİZ',
        };
    };
@endphp

<div
    class="rounded-3xl border border-slate-200/70 dark:border-slate-700/70 bg-white/90 dark:bg-slate-900/80 shadow-sm p-5 lg:p-6 space-y-5">

    {{-- ═══════ A. COMPOSITE HEADER ═══════ --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        {{-- Sol: Başlık --}}
        <div>
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">Karar Dağılımı</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $summary }}</p>
        </div>

        {{-- Orta: Score Pill --}}
        <div class="flex items-center gap-3 order-first sm:order-none">
            <span
                class="{{ $scorePillColor }} text-white text-lg font-bold rounded-full w-14 h-14 flex items-center justify-center shadow-sm">
                {{ $score }}
            </span>
            <span class="text-xs text-slate-400 dark:text-slate-500 font-medium">/100</span>
        </div>

        {{-- Sağ: Badges --}}
        <div class="flex items-center gap-2 flex-wrap">
            <span class="px-3 py-1 rounded-full text-xs font-semibold border {{ $decisionColor }}">
                {{ $decisionLabel }}
            </span>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide {{ $confColor }}">
                {{ $confLabel }}
            </span>
        </div>
    </div>

    {{-- ═══════ A2. DECISION NARRATIVE ═══════ --}}
    @if ($decisionNarrative)
        <div
            class="rounded-xl border border-slate-200/60 dark:border-slate-700/60 bg-slate-50/80 dark:bg-slate-800/50 px-4 py-3">
            <p class="text-xs leading-relaxed text-slate-700 dark:text-slate-300">
                <span class="font-semibold text-slate-900 dark:text-white">Neden bu karar?</span>
                {{ $decisionNarrative }}
            </p>
            @if ($confidenceNarrative)
                <p class="text-[10px] text-slate-500 dark:text-slate-400 mt-1.5">
                    Güven notu: {{ $confidenceNarrative }}
                </p>
            @endif
        </div>
    @endif

    {{-- ═══════ B. CONTRIBUTION BAR ═══════ --}}
    <div class="space-y-2">
        {{-- Stacked Bar --}}
        <div class="h-4 rounded-full overflow-hidden bg-slate-100 dark:bg-slate-800 flex"
            title="Composite Score: {{ $score }}">
            @foreach ($components as $comp)
                @php
                    $c = $comp['color'] ?? 'slate';
                    $colors = $colorMap[$c] ?? $colorMap['blue'];
                    $widthPercent =
                        $totalContribution > 0 ? round(($comp['contribution'] / $totalContribution) * 100, 1) : 25;
                @endphp
                <div class="{{ $colors['bg'] }} transition-all duration-500" style="width: {{ $widthPercent }}%"
                    title="{{ $comp['label'] }}: {{ $comp['raw_score'] }} × %{{ $comp['weight'] }} = {{ $comp['contribution'] }} katkı">
                </div>
            @endforeach
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap gap-x-5 gap-y-1">
            @foreach ($components as $comp)
                @php
                    $c = $comp['color'] ?? 'slate';
                    $colors = $colorMap[$c] ?? $colorMap['blue'];
                @endphp
                <div class="flex items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                    <span class="w-2.5 h-2.5 rounded-full {{ $colors['dot'] }} flex-shrink-0"></span>
                    <span>{{ $comp['label'] }}</span>
                    <span class="font-semibold text-slate-800 dark:text-slate-200">{{ $comp['contribution'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Signal Indicators --}}
        @if ($strongestSignal || $weakestSignal)
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px]">
                @if ($strongestSignal)
                    <span class="text-emerald-600 dark:text-emerald-400">
                        <span class="font-semibold">↑ En güçlü:</span> {{ $strongestSignal }}
                    </span>
                @endif
                @if ($weakestSignal)
                    <span class="text-rose-600 dark:text-rose-400">
                        <span class="font-semibold">↓ En zayıf:</span> {{ $weakestSignal }}
                    </span>
                @endif
            </div>
        @endif
    </div>

    {{-- ═══════ C. COMPONENT CARDS ═══════ --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        @foreach ($components as $comp)
            @php
                $c = $comp['color'] ?? 'slate';
                $colors = $colorMap[$c] ?? $colorMap['blue'];
                $compConf = $comp['confidence'] ?? 'UNKNOWN';
            @endphp
            @php
                $impact = $comp['impact'] ?? 'neutral';
                $impactData = $impactStyle($impact);
            @endphp
            <div class="rounded-2xl border {{ $colors['card_border'] }} {{ $colors['card_bg'] }} p-4 space-y-2.5">
                {{-- Card Header --}}
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold {{ $colors['text'] }}">{{ $comp['label'] }}</span>
                    <div class="flex items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $impactData['class'] }}">
                            {{ $impactData['icon'] }} {{ $impactData['label'] }}
                        </span>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold tracking-wide {{ $compConfColor($compConf) }}">
                            {{ $compConfTurkish($compConf) }}
                        </span>
                    </div>
                </div>

                {{-- Score --}}
                <div class="flex items-baseline gap-1">
                    <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $comp['raw_score'] }}</span>
                    <span class="text-xs text-slate-400 dark:text-slate-500">/100</span>
                </div>

                {{-- Weight & Contribution --}}
                <div class="flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                    <span>Ağırlık: %{{ $comp['weight'] }}</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">Katkı:
                        {{ $comp['contribution'] }}</span>
                </div>

                {{-- Reason --}}
                <p class="text-xs text-slate-600 dark:text-slate-400 leading-relaxed">{{ $comp['reason'] ?? '-' }}</p>

                {{-- Source Hint --}}
                <p class="text-[10px] text-slate-400 dark:text-slate-500 italic">{{ $comp['source_hint'] ?? '' }}</p>
            </div>
        @endforeach
    </div>

    {{-- ═══════ D. TRUST FOOTER ═══════ --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4 pt-3 border-t border-slate-100 dark:border-slate-800">
        {{-- Sol: Data Quality --}}
        <div class="space-y-1.5">
            <h4 class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Veri Kaynağı /
                Güven Seviyesi</h4>
            <ul class="space-y-1">
                @foreach ($components as $comp)
                    <li class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                        <span
                            class="w-1.5 h-1.5 rounded-full {{ $colorMap[$comp['color'] ?? 'slate']['dot'] ?? 'bg-slate-400' }}"></span>
                        <span class="font-medium text-slate-600 dark:text-slate-300">{{ $comp['label'] }}</span>
                        <span>→</span>
                        <span>{{ $comp['source_hint'] ?? 'Bilinmiyor' }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Sağ: Risk Disclosure --}}
        <div class="space-y-1.5">
            <h4 class="text-xs font-semibold text-slate-700 dark:text-slate-300 uppercase tracking-wider">Dikkat
                Edilmesi Gerekenler</h4>
            @if (count($riskNotes) > 0)
                <ul class="space-y-1">
                    @foreach ($riskNotes as $note)
                        <li class="flex items-start gap-2 text-xs text-slate-500 dark:text-slate-400">
                            <svg class="w-3.5 h-3.5 text-amber-500 dark:text-amber-400 mt-0.5 flex-shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                            <span>{{ $note }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-xs text-slate-400 dark:text-slate-500">Belirgin risk faktörü tespit edilmedi.</p>
            @endif
        </div>
    </div>

    {{-- Trust Micro --}}
    <p class="text-[10px] text-slate-400 dark:text-slate-500 text-center pt-1">
        Bu skor deterministik kurallar ve ölçülebilir sinyaller üzerinden hesaplanmıştır. AI sadece yorum katmanında
        kullanılır.
    </p>
</div>
