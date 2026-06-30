{{-- resources/views/admin/ilanlar/components/stats-cards.blade.php --}}
@props(['stats'])

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Listings -->
    <div class="group bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden dark:shadow-none">
        <div class="flex items-center gap-3 relative z-10">
            <div class="p-2.5 rounded-lg bg-blue-500/10 text-blue-600 dark:text-blue-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">@lang('admin.total_listings')</div>
                <div class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_listings'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Active Listings -->
    <div class="group bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden dark:shadow-none">
        <div class="flex items-center gap-3 relative z-10">
            <div class="p-2.5 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">@lang('admin.active_listings')</div>
                <div class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['active_listings'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Pending Listings -->
    <div class="group bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden dark:shadow-none">
        <div class="flex items-center gap-3 relative z-10">
            <div class="p-2.5 rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">@lang('admin.pending_listings')</div>
                <div class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['pending_listings'] ?? 0) }}</div>
            </div>
        </div>
    </div>

    <!-- Total Views -->
    <div class="group bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-white/5 shadow-sm hover:shadow-md transition-all duration-300 relative overflow-hidden dark:shadow-none">
        <div class="flex items-center gap-3 relative z-10">
            <div class="p-2.5 rounded-lg bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">@lang('admin.total_views')</div>
                <div class="text-xl font-bold text-slate-900 dark:text-white">{{ number_format($stats['total_views'] ?? 0) }}</div>
            </div>
        </div>
    </div>
</div>
