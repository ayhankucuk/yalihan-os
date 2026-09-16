{{-- 🛸 Cockpit Radar (AI & Market Intelligence) --}}
<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

        {{-- AI Guidance Card (Mediterranean Luxury Deep Navy + Gold) --}}
        <div class="lg:col-span-5 bg-gradient-to-br from-[#0A1628] via-[#112240] to-[#0A1628] border border-[#C9A84C]/30 rounded-xl p-6 text-white shadow-lg relative overflow-hidden">
            <div class="absolute -right-8 -top-8 w-32 h-32 bg-[#C9A84C]/10 rounded-full blur-2xl pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-[#C9A84C]/20 rounded-lg border border-[#C9A84C]/30 text-[#C9A84C]">
                            <x-icon name="yapay-zeka" class="w-5 h-5" />
                        </div>
                        <span class="text-sm font-bold tracking-wide text-slate-100">Cortex AI Stratejisi</span>
                    </div>
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#C9A84C]/20 text-[#C9A84C] border border-[#C9A84C]/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#C9A84C] animate-pulse"></span>
                        CANLI
                    </span>
                </div>

                <p class="text-sm font-medium leading-relaxed italic text-slate-200 opacity-95">
                    "{{ $priceAdvice ?? 'AI analizi bekleniyor... Gerçek zamanlı fiyat konumlandırma ve strateji için tanılama çalıştırın.' }}"
                </p>

                <div class="mt-6 pt-4 border-t border-slate-700/60 flex justify-between items-center">
                    <span class="text-xs font-medium text-slate-400">Güven Seviyesi</span>
                    <span class="text-xs font-bold px-2.5 py-1 bg-[#C9A84C]/20 text-[#C9A84C] rounded-lg border border-[#C9A84C]/30">YÜKSEK (88.4%)</span>
                </div>
            </div>
        </div>

        {{-- Market Trend Chart --}}
        <div class="lg:col-span-7 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Fiyat Trend Analizi</h3>
                    <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mt-0.5">İlan vs Bölge Ortalaması</p>
                </div>

                @if(isset($marketData['diff_percentage']))
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold px-2.5 py-1 rounded-md {{ $marketData['diff_percentage'] > 0 ? 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800' : 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800' }}">
                            {{ $marketData['diff_percentage'] > 0 ? '+' : '' }}{{ $marketData['diff_percentage'] }}% Sapma
                        </span>
                    </div>
                @endif
            </div>

            <div id="cockpitPriceChart" class="w-full h-[220px]"></div>
        </div>
    </div>

    {{-- Rakip İlanlar --}}
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center bg-gray-50/50 dark:bg-slate-800/40">
            <div class="flex items-center gap-2">
                <x-icon name="bina" class="w-4 h-4 text-[#C9A84C]" />
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Bölgesel Rakip İlanlar (En Yakın 5)</h3>
            </div>
            <span class="text-xs font-semibold px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded border border-slate-200 dark:border-slate-700">Aktif Tarama</span>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @forelse($marketData['top_competitors'] ?? [] as $comp)
                    <div class="p-4 bg-gray-50/70 dark:bg-slate-800/50 border border-gray-200/80 dark:border-slate-700/60 rounded-xl hover:border-[#C9A84C]/40 transition-all flex gap-4 group">
                        <div class="w-16 h-16 bg-gray-200 dark:bg-slate-700 rounded-lg overflow-hidden shrink-0">
                            @if(!empty($comp['image']))
                                <img src="{{ \Illuminate\Support\Facades\Storage::url($comp['image']) }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            @else
                                <div class="w-full h-full flex items-center justify-center text-gray-400 text-xs bg-slate-100 dark:bg-slate-800">
                                    <x-icon name="resim" class="w-5 h-5 opacity-40" />
                                </div>
                            @endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white truncate group-hover:text-[#C9A84C] transition-colors">{{ $comp['baslik'] }}</h4>
                            <div class="mt-2 flex items-center justify-between">
                                <span class="text-xs font-extrabold text-emerald-600 dark:text-emerald-400">{{ number_format($comp['fiyat']) }} ₺</span>
                                <span class="text-[11px] font-semibold text-gray-500 dark:text-slate-400">{{ $comp['metrekare'] }} m²</span>
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
