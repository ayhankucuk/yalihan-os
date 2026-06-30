{{-- Form Progress Indicator --}}
<div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-3 mb-4 dark:border-slate-700">
    <div class="flex items-center justify-between mb-1.5">
        <span class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Form İlerlemesi</span>
        <span id="form-progress-text" class="text-sm text-gray-500 dark:text-gray-400">%0 tamamlandı</span>
    </div>
    <div class="w-full bg-gray-200 dark:bg-slate-900 rounded-full h-2">
        <div id="form-progress-bar" class="h-full bg-red-500 rounded-full transition-all duration-500"
            style="width: 0%"></div>
    </div>
    <div class="flex items-center justify-between mt-1.5">
        <span id="save-indicator" class="text-xs text-gray-400 flex items-center">
            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Otomatik kayıt aktif
        </span>
        <span class="text-xs text-gray-400">Her 30 saniyede</span>
    </div>
</div>

