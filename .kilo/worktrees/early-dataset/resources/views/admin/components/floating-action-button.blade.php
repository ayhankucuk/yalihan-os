<div x-data="{ open: false }" class="fixed bottom-6 right-6 z-[90]">
    <!-- Menu Items -->
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-4 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-4 scale-95"
         class="absolute bottom-16 right-0 mb-2 w-56 flex flex-col gap-2"
         @click.away="open = false">

        <!-- Action: Yeni İlan -->
        <a href="{{ route('admin.ilanlar.create-wizard') }}" class="flex items-center justify-between p-3 bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all group">
            <span class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni İlan</span>
            <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
        </a>

        <!-- Action: Yeni Müşteri -->
        <a href="{{ route('admin.kisiler.create') }}" class="flex items-center justify-between p-3 bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all group">
            <span class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni Müşteri</span>
            <div class="w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            </div>
        </a>

        <!-- Action: Yeni Görev -->
        <a href="#" class="flex items-center justify-between p-3 bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all group">
            <span class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni Görev</span>
            <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            </div>
        </a>
    </div>

    <!-- Main FAB Button -->
    <button @click="open = !open"
            :class="open ? 'rotate-45 bg-gray-900 dark:bg-white text-white dark:text-gray-900' : 'bg-blue-600 dark:bg-blue-500 shadow-blue-500/20 dark:shadow-blue-900/40'"
            class="w-14 h-14 rounded-full shadow-xl dark:shadow-2xl flex items-center justify-center transition-all duration-300 transform hover:scale-110 active:scale-95 hover:shadow-2xl dark:hover:shadow-3xl">
        <svg class="w-6 h-6 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
    </button>
</div>
