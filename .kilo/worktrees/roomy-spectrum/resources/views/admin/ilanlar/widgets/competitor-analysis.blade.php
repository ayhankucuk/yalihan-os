@props(['ilan', 'analysis'])

@if($analysis && isset($analysis['top_competitors']) && count($analysis['top_competitors']) > 0)
    <div class="bg-gradient-to-r from-slate-700 to-slate-900 rounded-lg p-6 text-white shadow-lg">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold">🗺️ Pazar Hakimiyeti Analizi</h3>
            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-white/20 dark:bg-slate-900/20">
                {{ $analysis['total_competitors'] ?? 0 }} Rakip
            </span>
        </div>

        <!-- Fiyatlandırma Tavsiyesi -->
        <div class="bg-white/10 rounded-lg p-4 mb-6 border border-white/20 dark:bg-slate-900/10 dark:bg-slate-800/40">
            <p class="text-sm font-semibold mb-2 opacity-90">💡 Fiyatlandırma Tavsiyesi:</p>
            <p class="text-lg font-bold">{{ $analysis['recommendation'] ?? 'Analiz yapılamadı' }}</p>
            @if(isset($analysis['suggested_price']) && $analysis['suggested_price'])
                <div class="mt-3 flex items-center gap-4">
                    <div>
                        <p class="text-xs opacity-70">Mevcut Fiyat</p>
                        <p class="text-xl font-bold text-yellow-300">₺{{ number_format($analysis['our_listing']['price'], 0, ',', '.') }}</p>
                    </div>
                    <div class="text-2xl opacity-50">→</div>
                    <div>
                        <p class="text-xs opacity-70">Önerilen Fiyat</p>
                        <p class="text-xl font-bold text-green-300">₺{{ number_format($analysis['suggested_price'], 0, ',', '.') }}</p>
                    </div>
                    @if(isset($analysis['suggested_discount']) && $analysis['suggested_discount'] > 0)
                        <div>
                            <p class="text-xs opacity-70">İndirim</p>
                            <p class="text-xl font-bold text-red-300">-₺{{ number_format($analysis['suggested_discount'], 0, ',', '.') }}</p>
                        </div>
                    @endif
                </div>
            @endif
            <p class="text-xs opacity-70 mt-3">
                Güvenilirlik: {{ $analysis['confidence'] ?? 0 }}%
                @if(isset($analysis['price_gap_percent']))
                    • Fiyat Farkı: {{ number_format(abs($analysis['price_gap_percent']), 2) }}%
                @endif
            </p>
        </div>

        <!-- Bizim Mülk -->
        <div class="bg-white/5 rounded-lg p-4 mb-4 border-2 border-yellow-400 dark:bg-slate-900/5">
            <p class="text-xs opacity-70 mb-1">BİZİM MÜLK</p>
            <p class="text-2xl font-bold text-yellow-300">₺{{ number_format($analysis['our_listing']['price'], 0, ',', '.') }}</p>
            <p class="text-sm opacity-80 mt-1">{{ $analysis['our_listing']['title'] ?? 'N/A' }}</p>
            <p class="text-xs opacity-60 mt-1">{{ $analysis['our_listing']['location'] ?? '' }}</p>
        </div>

        <!-- Rakip Karşılaştırması -->
        <div class="space-y-3">
            <p class="text-sm font-semibold mb-2 opacity-90">Top 3 Rakip:</p>
            @foreach($analysis['top_competitors'] as $competitor)
                <div class="bg-white/5 rounded-lg p-4 border border-white/10 hover:bg-white/10 transition-all duration-200 dark:bg-slate-900/5">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex-1">
                            <p class="text-xs opacity-70 mb-1">RAKIP #{{ $loop->iteration }}</p>
                            <p class="font-bold text-lg">₺{{ number_format($competitor['price'], 0, ',', '.') }}</p>
                            <p class="text-sm opacity-80 mt-1">{{ Str::limit($competitor['title'] ?? 'N/A', 50) }}</p>
                            <p class="text-xs opacity-60 mt-1">{{ $competitor['location'] ?? '' }}</p>
                        </div>

                        <div class="text-right">
                            @if($competitor['price_gap'] > 0)
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-red-500 text-white">
                                    +{{ number_format(abs($competitor['price_gap_percent']), 1) }}% PAHALISI
                                </span>
                            @elseif($competitor['price_gap'] < 0)
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-green-500 text-white">
                                    {{ number_format(abs($competitor['price_gap_percent']), 1) }}% UCUZUMUZ
                                </span>
                            @else
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-bold bg-gray-500 text-white">
                                    AYNI FİYAT
                                </span>
                            @endif
                            <p class="text-xs opacity-60 mt-2">
                                Fark: ₺{{ number_format(abs($competitor['price_gap']), 0, ',', '.') }}
                            </p>
                        </div>
                    </div>
                    @if(isset($competitor['url']))
                        <a href="{{ $competitor['url'] }}" target="_blank"
                            class="inline-flex items-center gap-1 text-xs text-blue-300 hover:text-blue-200 mt-2">
                            İlanı Gör
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@else
    <div class="bg-gray-100 dark:bg-slate-900 rounded-lg p-6 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="flex items-center gap-3 mb-2">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">🗺️ Pazar Hakimiyeti Analizi</h3>
        </div>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            @if($analysis && isset($analysis['recommendation']))
                {{ $analysis['recommendation'] }}
            @else
                Rakip analizi yapılamadı. Yeterli rakip bulunamadı veya analiz hatası oluştu.
            @endif
        </p>
    </div>
@endif

