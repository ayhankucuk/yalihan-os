{{-- resources/views/admin/ilanlar/components/header.blade.php --}}

<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-white/5 p-6 shadow-sm shadow-indigo-500/5 transition-all duration-300 dark:shadow-none">
    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
        <!-- Title & Description -->
        <div class="space-y-2">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-500/10 border border-indigo-500/20 text-indigo-600 dark:text-indigo-400 text-xs font-bold uppercase tracking-wider mb-2">
                <span class="relative flex h-2 w-2">
                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 dark:bg-indigo-300 opacity-75"></span>
                  <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500 dark:bg-indigo-400"></span>
                </span>
                Yönetici Paneli
            </div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">
                İlanlarım<span class="text-indigo-600 dark:text-indigo-400">.</span>
            </h1>
            <p class="text-sm text-slate-500 dark:text-gray-400 font-medium">
                Portfolio ve ilan yönetim merkezi
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-3">
            <!-- Refresh Button -->
            <button
                class="inline-flex items-center gap-2 px-4 py-2 h-10 bg-white dark:bg-slate-900 text-slate-700 dark:text-white text-sm font-semibold rounded-xl shadow-sm border border-slate-200 dark:border-white/10 hover:bg-slate-50 dark:hover:bg-gray-700 transition-all active:scale-95 dark:shadow-none"
                onclick="refreshListings()" title="Yenile">
                <svg class="w-4 h-4 text-indigo-500 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.001 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Yenile
            </button>

            <!-- Export Button -->
            <a href="{{ route('admin.ilanlarim.export', ['format' => 'excel']) }}" id="export-excel-btn"
                class="inline-flex items-center gap-2 px-4 py-2 h-10 bg-emerald-500 dark:bg-emerald-600 text-white text-sm font-semibold rounded-xl shadow-sm hover:bg-emerald-600 dark:hover:bg-emerald-500 transition-all active:scale-95 disabled:opacity-50 dark:shadow-none">
                <svg id="export-excel-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <svg id="export-excel-spinner" class="hidden animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span id="export-excel-text">Excel</span>
            </a>

            <!-- Create New Listing Button -->
            <a href="{{ route('admin.ilanlar.create') }}"
                class="inline-flex items-center gap-2 px-5 py-2 h-10 bg-indigo-600 dark:bg-indigo-500 text-white text-sm font-bold rounded-xl shadow-sm hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-all active:scale-95 dark:shadow-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                <span>Yeni İlan</span>
            </a>
        </div>
    </div>
</div>
