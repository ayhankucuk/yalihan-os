{{--
    Location Intelligence Card — Sprint 6.2

    Dashboard'da Location Score + alt skorlar + POI grupları gösterir.
    Mediterranean Design System: navy, gold, cream palette.

    Props:
    - ilan: Ilan model instance
    - compact: bool (default: false) — dar görünüm
--}}

@props([
    'ilan' => null,
    'compact' => false,
])

@php
    $score = $ilan?->location_score;
    $confidence = $ilan?->location_score_confidence ?? 'VERY_LOW';
    $locationData = $ilan?->location_data;
    $analyzedAt = $ilan?->location_analyzed_at;
    $topGroups = $locationData['top_groups'] ?? [];
    $aiSummary = $locationData['ai_summary'] ?? null;
    $accessScore = $locationData['sub_scores']['poi_access_score'] ?? 0;
    $densityScore = $locationData['sub_scores']['poi_density_score'] ?? 0;
    $coverageScore = $locationData['sub_scores']['poi_coverage_score'] ?? 0;
    $geocodeSource = $locationData['geocode_source'] ?? 'none';

    $scoreColor = match (true) {
        $score >= 65 => 'text-green-600',
        $score >= 35 => 'text-amber-600',
        default => 'text-red-500',
    };

    $confidenceColor = match ($confidence) {
        'HIGH' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        'MEDIUM' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        'LOW' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        default => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
    };

    $confidenceLabel = match ($confidence) {
        'HIGH' => 'Yüksek',
        'MEDIUM' => 'Orta',
        'LOW' => 'Düşük',
        default => 'Çok Düşük',
    };

    $hasScore = $score !== null;
@endphp

{{-- Score Ring SVG helpers --}}
@php
    $radius = $compact ? 28 : 38;
    $circumference = 2 * M_PI * $radius;
    $dashOffset = $hasScore ? $circumference * (1 - $score / 100) : $circumference;
    $ringColor = match (true) {
        $score >= 65 => '#22c55e',  // green
        $score >= 35 => '#f59e0b',  // amber
        default => '#ef4444',       // red
    };
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800/50 {{ $compact ? 'p-4' : '' }}"
     x-data="locationIntelligenceCard({{ $ilan?->id ?? 'null' }})">

    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-navy/5 dark:bg-gold/10">
                <svg class="h-4 w-4 text-navy dark:text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                Konum Zekası
            </h3>
        </div>

        @if ($hasScore)
            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $confidenceColor }}">
                {{ $confidenceLabel }}
            </span>
        @endif
    </div>

    @if ($hasScore)
        {{-- Score + Sub-scores --}}
        <div class="{{ $compact ? 'flex items-center gap-4' : 'grid grid-cols-2 gap-4' }}">

            {{-- Score Ring --}}
            <div class="{{ $compact ? '' : 'flex flex-col items-center' }}">
                <div class="relative">
                    <svg class="{{ $compact ? 'h-16 w-16' : 'h-20 w-20' }}" viewBox="0 0 {{ ($radius + 6) * 2 }} {{ ($radius + 6) * 2 }}">
                        {{-- Background circle --}}
                        <circle
                            cx="{{ $radius + 6 }}"
                            cy="{{ $radius + 6 }}"
                            r="{{ $radius }}"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="4"
                            class="text-slate-200 dark:text-slate-700"
                        />
                        {{-- Score arc --}}
                        <circle
                            cx="{{ $radius + 6 }}"
                            cy="{{ $radius + 6 }}"
                            r="{{ $radius }}"
                            fill="none"
                            stroke="{{ $ringColor }}"
                            stroke-width="4"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $circumference }}"
                            stroke-dashoffset="{{ $dashOffset }}"
                            transform="rotate(-90 {{ $radius + 6 }} {{ $radius + 6 }})"
                            class="transition-all duration-700"
                        />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-xl font-bold {{ $scoreColor }}">{{ $score ?? '—' }}</span>
                        <span class="text-xs text-gray-400">/100</span>
                    </div>
                </div>
            </div>

            {{-- Sub-scores (only if not compact) --}}
            @unless ($compact)
                <div class="space-y-2.5">
                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Erişim</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $accessScore }}/40</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                            <div class="h-full rounded-full bg-blue-500 transition-all"
                                 style="width: {{ $accessScore / 40 * 100 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Yoğunluk</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $densityScore }}/30</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                            <div class="h-full rounded-full bg-purple-500 transition-all"
                                 style="width: {{ $densityScore / 30 * 100 }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs">
                            <span class="text-gray-500 dark:text-gray-400">Çeşitlilik</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">{{ $coverageScore }}/30</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                            <div class="h-full rounded-full bg-gold transition-all"
                                 style="width: {{ $coverageScore / 30 * 100 }}%"></div>
                        </div>
                    </div>
                </div>
            @endunless
        </div>

        {{-- Top Groups --}}
        @if (!empty($topGroups) && !$compact)
            <div class="mt-4 border-t border-slate-100 pt-3 dark:border-slate-700">
                <p class="mb-2 text-xs font-medium text-gray-500 dark:text-gray-400">Yakın Çevre</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach (array_slice($topGroups, 0, 4) as $group)
                        <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-slate-700 dark:text-gray-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-gold"></span>
                            {{ $group['label'] ?? $group['group'] ?? '' }}
                            @if (isset($group['closest_m']))
                                <span class="text-gray-400">{{ $group['closest_m'] < 1000 ? $group['closest_m'] . 'm' : round($group['closest_m'] / 1000, 1) . 'km' }}</span>
                            @endif
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- AI Summary --}}
        @if ($aiSummary && !$compact)
            <div class="mt-3 rounded-lg bg-navy/5 p-3 dark:bg-gold/5">
                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                    {{ Str::limit($aiSummary, 120) }}
                </p>
            </div>
        @endif

        {{-- Meta + Actions --}}
        <div class="mt-3 flex items-center justify-between">
            <div class="flex items-center gap-2 text-xs text-gray-400">
                @if ($geocodeSource !== 'none')
                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-xs dark:bg-slate-700">
                        {{ match ($geocodeSource) { 'nominatim' => 'OSM', 'adres_db' => 'AdresDB', 'manual' => 'Manuel', default => $geocodeSource } }}
                    </span>
                @endif
                @if ($analyzedAt)
                    <span>{{ $analyzedAt->diffForHumans() }}</span>
                @endif
            </div>

            <button
                @click="analyze()"
                :disabled="loading"
                class="flex items-center gap-1 rounded-lg bg-navy px-3 py-1.5 text-xs font-medium text-white transition-all hover:bg-navy-mid disabled:opacity-50 dark:bg-gold dark:text-navy dark:hover:bg-gold-light">
                <svg x-show="loading" class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Analiz...' : 'Yeniden Analiz'"></span>
            </button>
        </div>

    @else
        {{-- Empty state --}}
        <div class="flex flex-col items-center justify-center py-6 text-center">
            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
            </div>
            <p class="mb-1 text-sm font-medium text-gray-600 dark:text-gray-400">Konum analizi yapılmadı</p>
            <p class="mb-3 text-xs text-gray-400">Bu ilan için henüz konum zekası üretilmedi.</p>
            <button
                @click="analyze()"
                :disabled="loading"
                class="flex items-center gap-1.5 rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition-all hover:bg-navy-mid disabled:opacity-50 dark:bg-gold dark:text-navy">
                <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Analiz Ediliyor...' : 'Konum Analizi Başlat'"></span>
            </button>
        </div>
    @endif
</div>

@push('scripts')
<script>
function locationIntelligenceCard(ilanId) {
    return {
        loading: false,
        ilanId: ilanId,

        async analyze() {
            if (!this.ilanId || this.loading) return;

            this.loading = true;
            try {
                const response = await fetch('/api/location/analyze', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        ilan_id: this.ilanId,
                        include_ai_summary: false,
                    }),
                });

                const data = await response.json();
                if (data.status === 'ok') {
                    // Reload page to show updated score
                    window.location.reload();
                } else {
                    alert(data.message ?? 'Analiz başarısız.');
                }
            } catch (err) {
                console.error('Location analysis error:', err);
                alert('Bir hata oluştu.');
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
