<div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden shadow-sm dark:shadow-none dark:border-slate-700">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Portal ID Yönetimi</h3>
    </div>
    <div class="p-6">
        <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">Portal ID Eşleştirme</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
            @include('admin.ilanlar.components.portal-id-fields')
        </div>
    </div>
</div>
