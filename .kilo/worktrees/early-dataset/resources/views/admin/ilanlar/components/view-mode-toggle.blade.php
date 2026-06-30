{{-- resources/views/admin/ilanlar/components/view-mode-toggle.blade.php --}}

<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 mb-6 p-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <!-- View Mode Toggle -->
            <div class="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-1 dark:bg-slate-900">
                <button @click="viewMode = 'table'"
                    :class="viewMode === 'table' ?
                        'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm dark:shadow-none' :
                        'text-gray-600 dark:text-gray-400'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    @lang('admin.table')
                </button>
                <button @click="viewMode = 'grid'"
                    :class="viewMode === 'grid' ?
                        'bg-white dark:bg-gray-600 text-gray-900 dark:text-white shadow-sm dark:shadow-none' :
                        'text-gray-600 dark:text-gray-400'"
                    class="px-3 py-1.5 rounded-md text-sm font-medium transition-all duration-200">
                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    @lang('admin.grid')
                </button>
            </div>

            <!-- Bulk Actions Toolbar -->
            <div x-show="selectedIds.length > 0" x-transition class="flex items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400"
                    x-text="`${selectedIds.length} @lang('admin.selected')`"></span>

                <button @click="bulkAction('activate')" :disabled="processing"
                    class="px-3 py-1.5 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 disabled:opacity-50 transition-all duration-200">
                    ✓ @lang('admin.activate')
                </button>

                <button @click="bulkAction('deactivate')" :disabled="processing"
                    class="px-3 py-1.5 bg-yellow-600 text-white text-sm rounded-lg hover:bg-yellow-700 disabled:opacity-50 transition-all duration-200">
                    ✕ @lang('admin.deactivate')
                </button>

                <button @click="confirmBulkDelete()" :disabled="processing"
                    class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 disabled:opacity-50 transition-all duration-200">
                    🗑️ @lang('admin.delete')
                </button>
            </div>
        </div>
    </div>
</div>
