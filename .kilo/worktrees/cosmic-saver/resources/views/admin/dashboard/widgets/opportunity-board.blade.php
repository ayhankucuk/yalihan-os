<div
    class="bg-white dark:bg-slate-900 shadow rounded-lg border border-gray-200 dark:border-slate-800 h-full flex flex-col dark:shadow-none dark:border-slate-700">
    <div
        class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex justify-between items-center bg-gray-50 dark:bg-slate-900 rounded-t-lg dark:border-slate-700">
        <div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                🎯 Fırsat Panosu
                <span
                    class="px-2 py-0.5 text-xs rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300 hidden"
                    id="ob-badge">0</span>
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Yüksek potansiyelli acil satış fırsatları</p>
        </div>
        <div class="flex items-center gap-2">
            <button type="button" id="ob-refresh"
                class="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </button>
            <a href="{{ route('admin.intelligence.opportunity-board') }}"
                class="text-xs font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300">
                Tümünü Gör &rarr;
            </a>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto min-h-[300px] max-h-[400px] p-4 relative" id="ob-container">
        <!-- Loading State -->
        <div class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-800/50 z-10 dark:bg-slate-900/50"
            id="ob-loading">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <!-- Content will be injected here via JS -->
    </div>

    <div class="px-6 py-3 border-t border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 rounded-b-lg dark:border-slate-700">
        <div class="flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
            <span id="ob-durumu">Güncel</span>
            <span class="flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                Canlı İzleme
            </span>
        </div>
    </div>
</div>

<!-- Template for items -->
<template id="ob-item-template">
    <div
        class="mb-3 p-3 rounded-lg border border-gray-200 dark:border-slate-800 transition-all duration-200 hover:shadow-md cursor-pointer group relative overflow-hidden dark:border-slate-700">

        <!-- Priority Stripe -->
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-gray-300 priority-stripe"></div>

        <div class="pl-2 flex justify-between items-start">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate customer-name dark:text-slate-100">Customer Name
                    </h4>
                    <span class="text-xs px-1.5 py-0.5 rounded font-bold priority-badge">PRIORITY</span>
                </div>
                <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-1 request-title">Request Title</p>

                <div class="flex items-center gap-3 mt-2">
                    <div class="flex items-center gap-1" title="Uyumluluk Skoru">
                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-xs font-semibold text-gray-700 dark:text-slate-200 match-score dark:text-slate-300">0%</span>
                    </div>
                    <div class="flex items-center gap-1" title="Kaybetme Riski">
                        <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z">
                            </path>
                        </svg>
                        <span class="text-xs font-semibold text-gray-700 dark:text-slate-200 churn-score dark:text-slate-300">0%</span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-end gap-1 ml-2">
                <div class="text-right">
                    <span class="text-xs text-gray-400">Action Score</span>
                    <div class="text-lg font-black text-gray-900 dark:text-white leading-none action-score dark:text-slate-100">0</div>
                </div>
                <div class="flex gap-1 mt-1">
                    <button type="button"
                        class="opacity-0 group-hover:opacity-100 transition-opacity p-1.5 bg-purple-100 dark:bg-purple-900 text-purple-600 dark:text-purple-300 rounded hover:bg-purple-200 dark:hover:bg-purple-800 listen-btn"
                        title="Sesli Dinle">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z">
                            </path>
                        </svg>
                    </button>
                    <button type="button"
                        class="opacity-0 group-hover:opacity-100 transition-opacity p-1.5 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded hover:bg-blue-200 dark:hover:bg-blue-800 quick-action-btn"
                        title="Hızlı İşlem">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
