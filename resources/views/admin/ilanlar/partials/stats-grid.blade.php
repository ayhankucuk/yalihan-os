{{-- 📊 Quick Stats Grid - Mediterranean Luxury Design --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

    {{-- Toplam İlan --}}
    <div class="group relative bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-6 hover:shadow-lg dark:hover:shadow-slate-950/50 transition-all duration-300 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-[#0A1628]/5 to-transparent dark:from-[#C9A84C]/5 dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="relative flex items-center gap-4">
            <div class="w-12 h-12 bg-slate-100 dark:bg-slate-800 rounded-xl flex items-center justify-center text-[#0A1628] dark:text-[#C9A84C] group-hover:scale-110 transition-transform duration-300">
                <x-icon name="bina" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">{{ __('admin.total_listings') }}</p>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">
                    {{ number_format($stats['total'] ?? $ilanlar->total()) }}
                </h3>
            </div>
        </div>
    </div>

    {{-- Aktif İlanlar --}}
    <div class="group relative bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-6 hover:shadow-lg dark:hover:shadow-slate-950/50 transition-all duration-300 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/50 to-transparent dark:from-emerald-900/10 dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="relative flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:scale-110 transition-transform duration-300">
                <x-icon name="onay-daire" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">{{ __('admin.active_listings') }}</p>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">
                    {{ number_format($stats['active'] ?? $tabCounts['active'] ?? 0) }}
                </h3>
            </div>
        </div>
    </div>

    {{-- Bu Ay Eklenenler --}}
    <div class="group relative bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-6 hover:shadow-lg dark:hover:shadow-slate-950/50 transition-all duration-300 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-amber-50/50 to-transparent dark:from-amber-900/10 dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="relative flex items-center gap-4">
            <div class="w-12 h-12 bg-amber-50 dark:bg-amber-900/20 rounded-xl flex items-center justify-center text-[#C9A84C] dark:text-[#C9A84C] group-hover:scale-110 transition-transform duration-300">
                <x-icon name="takvim" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">{{ __('admin.this_month') }}</p>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">
                    {{ number_format($stats['this_month'] ?? 0) }}
                </h3>
            </div>
        </div>
    </div>

    {{-- Bekleyen İlanlar --}}
    <div class="group relative bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 p-6 hover:shadow-lg dark:hover:shadow-slate-950/50 transition-all duration-300 overflow-hidden">
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/50 to-transparent dark:from-indigo-900/10 dark:to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
        <div class="relative flex items-center gap-4">
            <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/20 rounded-xl flex items-center justify-center text-indigo-600 dark:text-indigo-400 group-hover:scale-110 transition-transform duration-300">
                <x-icon name="saat" class="w-6 h-6" />
            </div>
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-1">{{ __('admin.pending_listings') }}</p>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">
                    {{ number_format($stats['pending'] ?? 0) }}
                </h3>
            </div>
        </div>
    </div>

</div>
