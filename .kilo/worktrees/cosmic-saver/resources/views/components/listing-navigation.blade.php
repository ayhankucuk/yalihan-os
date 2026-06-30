{{-- ========================================
     LISTING NAVIGATION COMPONENT
     Context7: Önceki/Sonraki ilan navigasyonu
     ======================================== --}}

@props([
    'ilan' => null,
    'mode' => 'default', // default, category, location
    'showSimilar' => false,
    'similarLimit' => 4,
])

@php
    if (!$ilan) {
        return;
    }

    $navigationService = app(\App\Services\ListingNavigationService::class);

    // Check if navigation is status from settings
    if (!$navigationService->isEnabled()) {
        return;
    }

    $navigation = match($mode) {
        'category' => $navigationService->getByCategory($ilan),
        'location' => $navigationService->getByLocation($ilan),
        default => $navigationService->getNavigation($ilan)
    };

    $previous = $navigation['previous'] ?? null;
    $next = $navigation['next'] ?? null;
    $currentIndex = $navigation['current_index'] ?? null;
    $total = $navigation['total'] ?? 0;

    $similar = $showSimilar ? $navigationService->getSimilar($ilan, $similarLimit) : collect([]);
@endphp

@if($previous || $next || $showSimilar)
<div class="listing-navigation bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 p-6 dark:border-slate-700">
    {{-- Navigation Header --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">swap_horiz</span>
            <span>İlan Gezinme</span>
        </h3>
        @if($currentIndex && $total)
        <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ $currentIndex }} / {{ $total }}
        </span>
        @endif
    </div>

    {{-- Previous/Next Navigation --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        {{-- Previous Listing --}}
        @if($previous)
        <a href="{{ route('ilanlar.show', $previous->id) }}"
           class="group flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all duration-200 dark:bg-slate-900 dark:border-slate-700">
            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:bg-blue-500 dark:group-hover:bg-blue-600 transition-colors">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 group-hover:text-white transition-colors">chevron_left</span>
            </div>
            <div class="flex-1 min-w-0">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Önceki İlan</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors dark:text-slate-100">
                    {{ $previous->baslik ?? 'İlan #' . $previous->id }}
                </div>
                @if($previous->fiyat)
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    {{ number_format($previous->fiyat, 0, ',', '.') }} {{ $previous->para_birimi ?? 'TRY' }}
                </div>
                @endif
            </div>
        </a>
        @else
        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 opacity-50 cursor-not-allowed dark:bg-slate-900 dark:border-slate-700">
            <div class="flex-shrink-0 w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-400">chevron_left</span>
            </div>
            <div class="flex-1">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Önceki İlan</div>
                <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    İlan yok
                </div>
            </div>
        </div>
        @endif

        {{-- Next Listing --}}
        @if($next)
        <a href="{{ route('ilanlar.show', $next->id) }}"
           class="group flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all duration-200 dark:bg-slate-900 dark:border-slate-700">
            <div class="flex-1 min-w-0 text-right">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Sonraki İlan</div>
                <div class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors dark:text-slate-100">
                    {{ $next->baslik ?? 'İlan #' . $next->id }}
                </div>
                @if($next->fiyat)
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    {{ number_format($next->fiyat, 0, ',', '.') }} {{ $next->para_birimi ?? 'TRY' }}
                </div>
                @endif
            </div>
            <div class="flex-shrink-0 w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center group-hover:bg-blue-500 dark:group-hover:bg-blue-600 transition-colors">
                <span class="material-symbols-outlined text-blue-600 dark:text-blue-400 group-hover:text-white transition-colors">chevron_right</span>
            </div>
        </a>
        @else
        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 opacity-50 cursor-not-allowed dark:bg-slate-900 dark:border-slate-700">
            <div class="flex-1 text-right">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">Sonraki İlan</div>
                <div class="text-sm font-semibold text-gray-500 dark:text-gray-400">
                    İlan yok
                </div>
            </div>
            <div class="flex-shrink-0 w-12 h-12 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                <span class="material-symbols-outlined text-gray-400">chevron_right</span>
            </div>
        </div>
        @endif
    </div>

    {{-- Similar Listings --}}
    @if($showSimilar && $similar->isNotEmpty())
    <div class="border-t border-gray-200 dark:border-slate-800 pt-6 mt-6 dark:border-slate-700">
        <h4 class="text-md font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
            <span class="material-symbols-outlined text-blue-600 dark:text-blue-400">grid_view</span>
            <span>Benzer İlanlar</span>
        </h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($similar as $similarIlan)
            <a href="{{ route('ilanlar.show', $similarIlan->id) }}"
               class="group block p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-md transition-all duration-200 dark:bg-slate-900 dark:border-slate-700">
                @if($similarIlan->kapak_fotografi_url)
                <img src="{{ $similarIlan->kapak_fotografi_url }}"
                     alt="{{ $similarIlan->baslik }}"
                     class="w-full h-32 object-cover rounded-lg mb-2 group-hover:scale-105 transition-transform duration-200">
                @endif
                <div class="text-sm font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors dark:text-slate-100">
                    {{ $similarIlan->baslik ?? 'İlan #' . $similarIlan->id }}
                </div>
                @if($similarIlan->fiyat)
                <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                    {{ number_format($similarIlan->fiyat, 0, ',', '.') }} {{ $similarIlan->para_birimi ?? 'TRY' }}
                </div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endif
