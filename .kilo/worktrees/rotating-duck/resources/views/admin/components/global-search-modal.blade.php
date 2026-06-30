<div x-data="globalSearch()" 
     @open-global-search.window="openModal()"
     @keydown.meta.k.window="openModal()" 
     @keydown.ctrl.k.window="openModal()"
     @keydown.escape.window="closeModal()"
     x-show="isOpen" 
     class="fixed inset-0 z-[100] overflow-y-auto p-4 sm:p-6 md:p-20" 
     role="dialog" 
     aria-modal="true" 
     style="display: none;"
     x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-500/75 dark:bg-gray-900/80 backdrop-blur-sm transition-opacity" @click="closeModal()"></div>

    <!-- Modal Content -->
    <div class="mx-auto max-w-2xl transform divide-y divide-gray-100 dark:divide-gray-800 overflow-hidden rounded-2xl bg-white dark:bg-slate-900 shadow-2xl ring-1 ring-black ring-opacity-5 transition-all"
         x-transition:enter="ease-out duration-300"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="ease-in duration-200"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        
        <div class="relative">
            <svg class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-gray-400 dark:text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
            </svg>
            <input type="text" 
                   x-model="query" 
                   @input.debounce.300ms="search()"
                   class="h-12 w-full border-0 bg-transparent pl-11 pr-4 text-gray-900 dark:text-slate-100 placeholder:text-gray-400 focus:ring-0 sm:text-sm dark:placeholder:text-slate-500 dark:text-white" 
                   placeholder="İlan, müşteri, görev veya lead ara... (⌘K)" 
                   x-ref="searchInput">
        </div>

        <!-- Default content: Quick Actions -->
        <div x-show="!query && !loading && !hasResults" class="p-4">
            <h2 class="mb-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Hızlı İşlemler</h2>
            <div class="grid grid-cols-2 gap-4">
                <a href="{{ route('admin.ilanlar.create-wizard') }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-slate-800 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni İlan</div>
                        <div class="text-xs text-gray-500">Portföy ekle</div>
                    </div>
                </a>
                <a href="{{ route('admin.kisiler.create') }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 dark:border-slate-800 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all group">
                    <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni Müşteri</div>
                        <div class="text-xs text-gray-500">Müşteri ekle</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Results -->
        <div x-show="query && (loading || hasResults)" class="max-h-96 overflow-y-auto scroll-py-2 p-2 px-4 pb-4">
            <template x-if="loading">
                <div class="flex items-center justify-center py-6">
                    <svg class="animate-spin h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            </template>

            <template x-if="!loading && hasResults">
                <div class="space-y-4">
                    <template x-for="(items, category) in results" :key="category">
                        <div x-show="items.length > 0">
                            <h3 class="mt-4 mb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider" x-text="category.charAt(0).toUpperCase() + category.slice(1)"></h3>
                            <ul class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                <template x-for="item in items" :key="item.id + item.type">
                                    <li>
                                        <a :href="item.url" class="flex select-none items-center rounded-lg px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors group">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-bold text-gray-900 dark:text-white truncate dark:text-slate-100" x-text="item.title"></span>
                                                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-slate-900 text-gray-500" x-text="item.type"></span>
                                                </div>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <span class="text-xs text-gray-500 truncate" x-text="item.subtitle"></span>
                                                    <span x-show="item.meta" class="text-xs font-medium text-blue-600 dark:text-blue-400" x-text="item.meta"></span>
                                                </div>
                                            </div>
                                            <svg class="h-5 w-5 text-gray-400 group-hover:text-blue-600 transition-colors" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </template>
                </div>
            </template>
        </div>

        <!-- Empty state -->
        <div x-show="query && !loading && !hasResults" class="py-14 px-6 text-center sm:px-14">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <p class="mt-4 text-sm text-gray-900 dark:text-white font-medium dark:text-slate-100">Sonuç bulunamadı</p>
            <p class="mt-2 text-sm text-gray-500">Aramanızla eşleşen herhangi bir kayıt bulamadık. Lütfen farklı bir terim deneyin.</p>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between bg-gray-50 dark:bg-gray-800/50 px-4 py-2.5 text-xs text-gray-500 dark:bg-slate-900">
            <div class="flex items-center gap-4">
                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white dark:bg-slate-900 dark:border-slate-700 dark:text-slate-100">↵</kbd> seç</span>
                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white dark:bg-slate-900 dark:border-slate-700 dark:text-slate-100">↑↓</kbd> gezin</span>
                <span class="flex items-center gap-1"><kbd class="px-1.5 py-0.5 rounded bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-900 dark:text-white dark:bg-slate-900 dark:border-slate-700 dark:text-slate-100">esc</kbd> kapat</span>
            </div>
            <div>Powered by Cortex™</div>
        </div>
    </div>
</div>

<script>
function globalSearch() {
    return {
        isOpen: false,
        query: '',
        loading: false,
        results: {
            ilanlar: [],
            kisiler: [],
            gorevler: [],
            leadler: []
        },
        hasResults: false,

        openModal() {
            this.isOpen = true;
            this.$nextTick(() => {
                this.$refs.searchInput.focus();
            });
        },

        closeModal() {
            this.isOpen = false;
            this.query = '';
            this.results = { ilanlar: [], kisiler: [], gorevler: [], leadler: [] };
            this.hasResults = false;
        },

        async search() {
            if (this.query.length < 2) {
                this.results = { ilanlar: [], kisiler: [], gorevler: [], leadler: [] };
                this.hasResults = false;
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(`/api/v1/admin/global-search?q=${encodeURIComponent(this.query)}`);
                const data = await response.json();
                
                if (data.success) {
                    this.results = data.data;
                    this.hasResults = Object.values(this.results).some(arr => arr.length > 0);
                }
            } catch (error) {
                console.error('Global search error:', error);
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
