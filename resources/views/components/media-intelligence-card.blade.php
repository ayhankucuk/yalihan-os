{{--
    Media Intelligence Card — Sprint 6.3

    Cockpit dashboard'da Media Health gösterir.
    Mediterranean Design System: navy, gold, cream palette.
--}}

@props([
    'ilan' => null,
    'mediaSummary' => null,
    'compact' => false,
])

@php
    $health = $mediaSummary['health'] ?? 'MISSING';
    $healthScore = $mediaSummary['health_score'] ?? 0;
    $qualityScore = $mediaSummary['quality_score'] ?? 0;
    $coverage = $mediaSummary['coverage'] ?? 0;
    $detectedRooms = $mediaSummary['detected_rooms'] ?? [];
    $missingRooms = $mediaSummary['missing_rooms'] ?? [];
    $heroImageUrl = $mediaSummary['hero_image_url'] ?? null;
    $totalPhotos = $mediaSummary['total_photos'] ?? 0;

    $healthColor = match ($health) {
        'EXCELLENT' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
        'GOOD' => 'bg-blue-100 text-blue-700 border-blue-200',
        'FAIR' => 'bg-amber-100 text-amber-700 border-amber-200',
        'POOR' => 'bg-red-100 text-red-700 border-red-200',
        default => 'bg-gray-100 text-gray-500 border-gray-200',
    };

    $healthLabel = match ($health) {
        'EXCELLENT' => 'Mükemmel',
        'GOOD' => 'İyi',
        'FAIR' => 'Orta',
        'POOR' => 'Zayıf',
        default => 'Eksik',
    };

    $scoreColor = match (true) {
        $healthScore >= 80 => 'text-emerald-600',
        $healthScore >= 60 => 'text-blue-600',
        $healthScore >= 40 => 'text-amber-600',
        default => 'text-red-500',
    };
@endphp

<div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800/50 {{ $compact ? 'p-4' : '' }}"
     x-data="mediaIntelligenceCard({{ $ilan?->id ?? 'null' }})">

    {{-- Header --}}
    <div class="mb-4 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-navy/5 dark:bg-gold/10">
                <svg class="h-4 w-4 text-navy dark:text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                Medya Zekası
            </h3>
        </div>

        @if ($health !== 'MISSING')
            <span class="rounded-full border px-2 py-0.5 text-xs font-medium {{ $healthColor }}">
                {{ $healthLabel }}
            </span>
        @endif
    </div>

    @if ($health !== 'MISSING')
        {{-- Stats Grid --}}
        <div class="grid grid-cols-3 gap-3 mb-4">

            {{-- Health Score Ring --}}
            <div class="flex flex-col items-center">
                @php
                    $radius = $compact ? 22 : 28;
                    $circumference = 2 * M_PI * $radius;
                    $dashOffset = $circumference * (1 - $healthScore / 100);
                    $ringColor = match (true) {
                        $healthScore >= 80 => '#10b981',
                        $healthScore >= 60 => '#3b82f6',
                        $healthScore >= 40 => '#f59e0b',
                        default => '#ef4444',
                    };
                @endphp
                <div class="relative">
                    <svg class="{{ $compact ? 'h-12 w-12' : 'h-16 w-16' }}" viewBox="0 0 {{ ($radius + 6) * 2 }} {{ ($radius + 6) * 2 }}">
                        <circle cx="{{ $radius + 6 }}" cy="{{ $radius + 6 }}" r="{{ $radius }}"
                            fill="none" stroke="currentColor" stroke-width="3"
                            class="text-slate-200 dark:text-slate-700"/>
                        <circle cx="{{ $radius + 6 }}" cy="{{ $radius + 6 }}" r="{{ $radius }}"
                            fill="none" stroke="{{ $ringColor }}" stroke-width="3"
                            stroke-linecap="round"
                            stroke-dasharray="{{ $circumference }}"
                            stroke-dashoffset="{{ $dashOffset }}"
                            transform="rotate(-90 {{ $radius + 6 }} {{ $radius + 6 }})"
                            class="transition-all duration-700"/>
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-lg font-bold {{ $scoreColor }}">{{ $healthScore }}</span>
                    </div>
                </div>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sağlık</span>
            </div>

            {{-- Stats --}}
            <div class="col-span-2 flex flex-col justify-center gap-2">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Kalite</span>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $qualityScore }}/100</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Kapsam</span>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ round($coverage * 100) }}%</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">Fotoğraf</span>
                    <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $totalPhotos }} adet</span>
                </div>
            </div>
        </div>

        {{-- Hero Image --}}
        @if ($heroImageUrl)
            <div class="mb-3">
                <p class="mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Kapak Fotoğrafı</p>
                <img src="{{ $heroImageUrl }}"
                     alt="Kapak fotoğrafı"
                     class="h-20 w-full rounded-lg object-cover border border-slate-200 dark:border-slate-700">
            </div>
        @endif

        {{-- Detected Rooms --}}
        @if (!empty($detectedRooms))
            <div class="mb-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                <p class="mb-1.5 text-xs font-medium text-gray-500 dark:text-gray-400">Tespit Edilen Odalar</p>
                <div class="flex flex-wrap gap-1">
                    @foreach (array_slice($detectedRooms, 0, 5) as $room)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-400">
                            {{ $room['label'] ?? $room['oda_turu'] }}
                            <span class="text-emerald-400">{{ $room['count'] }}</span>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Missing Rooms --}}
        @if (!empty($missingRooms))
            <div class="mb-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                <p class="mb-1.5 text-xs font-medium text-red-500">Eksik Odalar</p>
                <div class="flex flex-wrap gap-1">
                    @foreach (array_slice($missingRooms, 0, 4) as $turu)
                        <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs text-red-600 dark:bg-red-900/20 dark:text-red-400">
                            {{ match ($turu) {
                                'pool' => 'Havuz',
                                'view' => 'Manzara',
                                'living_room' => 'Salon',
                                'bedroom' => 'Yatak Odası',
                                'kitchen' => 'Mutfak',
                                'bathroom' => 'Banyo',
                                'terrace' => 'Teras',
                                'garden' => 'Bahçe',
                                'exterior' => 'Dış Cephe',
                                default => ucfirst($turu),
                            } }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Analyze Button --}}
        <div class="flex items-center justify-end border-t border-slate-100 pt-3 dark:border-slate-700">
            <button
                @click="analyze()"
                :disabled="loading"
                class="flex items-center gap-1 rounded-lg bg-navy px-3 py-1.5 text-xs font-medium text-white transition-all hover:bg-navy-mid disabled:opacity-50 dark:bg-gold dark:text-navy dark:hover:bg-gold-light">
                <svg x-show="loading" class="h-3 w-3 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Analiz...' : 'Yeniden Analiz'"></span>
            </button>
        </div>

    @else
        {{-- Empty State --}}
        <div class="flex flex-col items-center justify-center py-6 text-center">
            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-700">
                <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="mb-1 text-sm font-medium text-gray-600 dark:text-gray-400">Fotoğraf analizi yok</p>
            <p class="mb-3 text-xs text-gray-400">Bu ilan için henüz medya zekası üretilmedi.</p>
            <button
                @click="analyze()"
                :disabled="loading"
                class="flex items-center gap-1.5 rounded-lg bg-navy px-4 py-2 text-sm font-medium text-white transition-all hover:bg-navy-mid disabled:opacity-50 dark:bg-gold dark:text-navy">
                <svg x-show="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 0 12h4z"></path>
                </svg>
                <span x-text="loading ? 'Analiz Ediliyor...' : 'Medya Analizi Başlat'"></span>
            </button>
        </div>
    @endif
</div>

@push('scripts')
<script>
function mediaIntelligenceCard(ilanId) {
    return {
        loading: false,
        ilanId: ilanId,

        async analyze() {
            if (!this.ilanId || this.loading) return;

            this.loading = true;
            try {
                const response = await fetch('/api/media/analyze', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ ilan_id: this.ilanId }),
                });

                const data = await response.json();
                if (data.status === 'ok' || data.status === 'queued') {
                    window.location.reload();
                } else {
                    alert(data.message ?? 'Analiz başarısız.');
                }
            } catch (err) {
                console.error('Media analysis error:', err);
                alert('Bir hata oluştu.');
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endpush
