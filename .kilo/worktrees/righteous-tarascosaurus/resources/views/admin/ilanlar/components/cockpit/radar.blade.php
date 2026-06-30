{{-- 🛸 Cockpit Radar (AI & Market Intelligence) --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- AI Guidance Card --}}
        <div class="lg:col-span-4 bg-blue-600 rounded-lg p-6 text-white shadow-md relative overflow-hidden dark:shadow-none">
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-4">
                    <div class="p-2 bg-white/20 rounded-lg dark:bg-slate-900/20">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-sm font-semibold">Cortex AI Tavsiyesi</span>
                </div>

                <p class="text-base font-medium leading-relaxed italic opacity-95">
                    "{{ $priceAdvice ?? 'AI analizi bekleniyor... Gerçek zamanlı fiyat konumlandırma ve strateji için tanılama çalıştırın.' }}"
                </p>

                <div class="mt-6 pt-4 border-t border-white/20 flex justify-between items-center">
                    <span class="text-xs font-medium opacity-75">Güven Seviyesi</span>
                    <span class="text-xs font-semibold px-2 py-0.5 bg-white/20 rounded dark:bg-slate-900/20">YÜKSEK (88.4%)</span>
                </div>
            </div>
        </div>

        {{-- Market Trend Chart --}}
        <div class="lg:col-span-8 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg p-6 dark:border-slate-700">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Fiyat Trend Analizi</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-medium mt-1">İlan vs Bölge Ortalaması</p>
                </div>

                @if(isset($marketData['diff_percentage']))
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold {{ $marketData['diff_percentage'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                            {{ $marketData['diff_percentage'] > 0 ? '+' : '' }}{{ $marketData['diff_percentage'] }}% Fark
                        </span>
                    </div>
                @endif
            </div>

            <div id="cockpitPriceChart" class="w-full h-[220px]"></div>
        </div>
    </div>

    {{-- Rakip İlanlar --}}
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden dark:border-slate-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Bölgesel Rakip İlanlar (En Yakın 5)</h3>
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Aktif Tarama</span>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($marketData['top_competitors'] ?? [] as $comp)
                    <div class="p-4 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all flex gap-4 dark:border-slate-700">
                        <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg overflow-hidden shrink-0">
                            @if($comp['image'])
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($comp['image']) }}" class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 text-sm">-</div>
                            @endif
                        </div>
                        <div class="min-w-0">
                            <h4 class="text-xs font-semibold text-gray-900 dark:text-white truncate dark:text-slate-100">{{ $comp['baslik'] }}</h4>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-xs font-semibold text-green-600 dark:text-green-400">{{ number_format($comp['fiyat']) }} ₺</span>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $comp['metrekare'] }} m²</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full py-8 text-center text-gray-500 dark:text-gray-400 text-sm font-medium">
                        Bu sektörde rakip ilan tespit edilmedi.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
