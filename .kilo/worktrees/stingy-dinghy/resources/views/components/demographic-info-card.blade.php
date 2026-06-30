{{-- ========================================
     DEMOGRAPHIC INFO CARD COMPONENT
     Emlak kartlarında demografik bilgi gösterimi
     ======================================== --}}

@props([
    'location' => null, // Mahalle/Belde/Köy bilgisi
    'population' => null,
    'educationLevel' => null,
    'averageAge' => null,
    'genderRatio' => null,
    'householdSize' => null,
    'employmentRate' => null,
    'compact' => false,
])

@php
    // TurkiyeAPI'den nüfus bilgisi varsa kullan
    $locationData = $location ?? null;
    $showDemographic = $population || $educationLevel || $averageAge;
@endphp

@if($showDemographic)
<div class="demographic-info-card {{ $compact ? 'compact' : '' }} mt-4 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
    <div class="flex items-center gap-2 mb-3">
        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
        </svg>
        <h4 class="text-sm font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">Demografik Bilgiler</h4>
    </div>

    <div class="grid {{ $compact ? 'grid-cols-2' : 'grid-cols-2 md:grid-cols-3' }} gap-3">
        {{-- Nüfus --}}
        @if($population)
        <div class="demographic-item group relative">
            <div class="flex items-center gap-2 p-2 rounded-lg bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors duration-200">
                <span class="text-lg">👥</span>
                <div class="flex-1 min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Nüfus</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate dark:text-slate-100">
                        {{ number_format($population, 0, ',', '.') }}
                    </div>
                </div>
            </div>
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Mahalle/Belde nüfusu
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
            </div>
        </div>
        @endif

        {{-- Eğitim Seviyesi --}}
        @if($educationLevel)
        <div class="demographic-item group relative">
            <div class="flex items-center gap-2 p-2 rounded-lg bg-green-50 dark:bg-green-900/20 hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors duration-200">
                <span class="text-lg">🎓</span>
                <div class="flex-1 min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Eğitim</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate dark:text-slate-100">
                        {{ $educationLevel }}%
                    </div>
                </div>
            </div>
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Üniversite mezun oranı
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
            </div>
        </div>
        @endif

        {{-- Ortalama Yaş --}}
        @if($averageAge)
        <div class="demographic-item group relative">
            <div class="flex items-center gap-2 p-2 rounded-lg bg-purple-50 dark:bg-purple-900/20 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors duration-200">
                <span class="text-lg">📊</span>
                <div class="flex-1 min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Yaş</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate dark:text-slate-100">
                        {{ $averageAge }}
                    </div>
                </div>
            </div>
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Ortalama yaş grubu
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
            </div>
        </div>
        @endif

        {{-- Cinsiyet Oranı --}}
        @if($genderRatio && !$compact)
        <div class="demographic-item group relative">
            <div class="flex items-center gap-2 p-2 rounded-lg bg-pink-50 dark:bg-pink-900/20 hover:bg-pink-100 dark:hover:bg-pink-900/30 transition-colors duration-200">
                <span class="text-lg">⚖️</span>
                <div class="flex-1 min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Cinsiyet</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate dark:text-slate-100">
                        {{ $genderRatio }}% K
                    </div>
                </div>
            </div>
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Kadın nüfus oranı
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
            </div>
        </div>
        @endif

        {{-- Hane Halkı Büyüklüğü --}}
        @if($householdSize && !$compact)
        <div class="demographic-item group relative">
            <div class="flex items-center gap-2 p-2 rounded-lg bg-orange-50 dark:bg-orange-900/20 hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors duration-200">
                <span class="text-lg">👨‍👩‍👧‍👦</span>
                <div class="flex-1 min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Hane</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate dark:text-slate-100">
                        {{ number_format($householdSize, 1) }} kişi
                    </div>
                </div>
            </div>
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                Ortalama hane halkı büyüklüğü
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
            </div>
        </div>
        @endif

        {{-- İstihdam Oranı --}}
        @if($employmentRate && !$compact)
        <div class="demographic-item group relative">
            <div class="flex items-center gap-2 p-2 rounded-lg bg-cyan-50 dark:bg-cyan-900/20 hover:bg-cyan-100 dark:hover:bg-cyan-900/30 transition-colors duration-200">
                <span class="text-lg">💼</span>
                <div class="flex-1 min-w-0">
                    <div class="text-xs text-gray-500 dark:text-gray-400">İstihdam</div>
                    <div class="text-sm font-semibold text-gray-900 dark:text-white truncate dark:text-slate-100">
                        {{ $employmentRate }}%
                    </div>
                </div>
            </div>
            <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-10">
                İstihdam oranı
                <div class="absolute top-full left-1/2 transform -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
            </div>
        </div>
        @endif
    </div>

    {{-- Bilgi Notu --}}
    @if(!$compact)
    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Kaynak: TÜİK & TurkiyeAPI</span>
    </div>
    @endif
</div>
@endif

<style>
.demographic-info-card.compact .demographic-item {
    font-size: 0.75rem;
}
</style>
