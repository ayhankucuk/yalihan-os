<div class="bg-white dark:bg-slate-900 shadow-sm rounded-xl p-6 mt-6 border border-blue-100 dark:border-blue-900 dark:shadow-none">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center dark:text-slate-100">
            <span class="mr-2">🧠</span> Cortex Akıllı Öneriler
        </h3>
        <span
            class="text-xs font-medium px-2.5 py-0.5 rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
            v1.0 Beta
        </span>
    </div>

    <div class="space-y-4">
        @forelse($matches as $match)
            <div
                class="flex items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-transparent hover:border-blue-300 transition-all duration-200 dark:bg-slate-900">
                <div
                    class="flex-shrink-0 w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold text-sm">
                    {{ $match['total_score'] }}
                </div>
                <div class="ml-4 flex-1">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                        {{ $match['ilan']->baslik }}
                    </h4>
                    <div class="flex items-center mt-1 text-xs text-gray-500 dark:text-gray-400 space-x-3">
                        <span>💰 {{ number_format($match['ilan']->fiyat, 0, ',', '.') }}
                            {{ $match['ilan']->para_birimi }}</span>
                        <span>📍 {{ $match['breakdown']['distance_km'] }} km</span>
                        <span>🏠 {{ $match['ilan']->oda_sayisi ?? '—' }} Oda</span>
                    </div>
                </div>
                <div>
                    <a href="/admin/ilanlar/{{ $match['ilan']->id }}"
                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                        İncele →
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400 italic">Uygun eşleşme bulunamadı.</p>
            </div>
        @endforelse
    </div>
</div>
