{{-- Danışman Performance Leaderboard Widget --}}
<div class="bg-white dark:bg-slate-900 rounded-lg shadow-md border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none dark:border-slate-700">
    {{-- Header --}}
    <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-3xl">🏆</span>
                <div>
                    <h3 class="text-white font-bold text-lg">Danışman Leaderboard</h3>
                    <p class="text-orange-100 text-sm">Performance rankings ayda geçerli</p>
                </div>
            </div>
            <a href="{{ route('api.leaderboard.danismanlar') }}" 
               class="text-white hover:text-orange-100 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </a>
        </div>
    </div>

    {{-- Content --}}
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <tr>
                    <th class="px-6 py-3 text-left font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">Sıra</th>
                    <th class="px-6 py-3 text-left font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">Danışman</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">Puan</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">İlanlar</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">Bosch %</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">FLIR %</th>
                    <th class="px-6 py-3 text-center font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">Rozetler</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse ($leaderboard ?? [] as $index => $danisman)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                        {{-- Rank --}}
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center w-8 h-8 rounded-full 
                                @if($index === 0) bg-gradient-to-r from-yellow-400 to-yellow-600 text-white font-bold text-lg
                                @elseif($index === 1) bg-gradient-to-r from-gray-300 to-gray-400 text-white font-bold text-lg
                                @elseif($index === 2) bg-gradient-to-r from-amber-500 to-amber-600 text-white font-bold text-lg
                                @else bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold
                                @endif">
                                {{ $index + 1 }}
                            </div>
                        </td>

                        {{-- Danışman Adı --}}
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $danisman['ad_soyad'] ?? 'Unknown' }}
                            </div>
                        </td>

                        {{-- Puan --}}
                        <td class="px-6 py-4 text-center">
                            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100 dark:bg-blue-900/30">
                                <span class="text-2xl">{{ $danisman['score_badge'] ?? '📊' }}</span>
                                <span class="font-bold text-blue-700 dark:text-blue-300">
                                    {{ round($danisman['average_score'] ?? 0, 1) }}
                                </span>
                            </div>
                        </td>

                        {{-- İlanlar --}}
                        <td class="px-6 py-4 text-center">
                            <span class="text-gray-700 dark:text-slate-200 font-semibold dark:text-slate-300">
                                {{ $danisman['total_ilanlar'] ?? 0 }}
                            </span>
                        </td>

                        {{-- Bosch % --}}
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-xl">🔧</span>
                                <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    {{ round($danisman['bosch_usage_percent'] ?? 0, 0) }}%
                                </span>
                            </div>
                        </td>

                        {{-- FLIR % --}}
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <span class="text-xl">🌡️</span>
                                <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    {{ round($danisman['flir_usage_percent'] ?? 0, 0) }}%
                                </span>
                            </div>
                        </td>

                        {{-- Rozetler --}}
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center gap-1 flex-wrap">
                                @php
                                    $badges = $danisman['badge_details']['badges'] ?? [];
                                @endphp
                                @forelse ($badges as $badge)
                                    <span class="text-sm" title="{{ $badge }}">
                                        {{ explode(' ', $badge)[0] }}
                                    </span>
                                @empty
                                    <span class="text-gray-400">-</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            <p>Henüz leaderboard verisi yok. Danışmanların ilanları değerlendirilmeyi bekleyin.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Footer Stats --}}
    <div class="bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-slate-800 px-6 py-4 dark:border-slate-700 dark:bg-slate-900">
        <div class="grid grid-cols-3 gap-4 text-center">
            <div>
                <p class="text-2xl font-bold text-orange-600">{{ count($leaderboard ?? []) }}</p>
                <p class="text-xs text-gray-600 dark:text-gray-400">Toplam Danışman</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-emerald-600">
                    {{ round(($leaderboard ?? [])[0]['average_score'] ?? 0, 1) }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400">En Yüksek Puan</p>
            </div>
            <div>
                <p class="text-2xl font-bold text-blue-600">
                    @php
                        $avgScore = collect($leaderboard ?? [])->avg('average_score');
                    @endphp
                    {{ round($avgScore, 1) }}
                </p>
                <p class="text-xs text-gray-600 dark:text-gray-400">Ortalama Puan</p>
            </div>
        </div>
    </div>
</div>

<style>
    /* Smooth animations */
    @media (prefers-reduced-motion: no-preference) {
        tr {
            transition: background-color 0.15s ease-in-out;
        }
    }
</style>
