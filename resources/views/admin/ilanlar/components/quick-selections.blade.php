{{-- Hızlı Kategori Seçimi (Resolver-validated, no phantom slugs) --}}
<div class="mt-8" x-data="quickSelections()" x-init="loadSelections()">
    <div class="flex items-center gap-2 mb-4">
        <div class="w-1 h-6 bg-blue-600 rounded-full"></div>
        <h4 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">
            Hızlı Seçim
        </h4>
        <span class="text-xs text-gray-500 dark:text-gray-400 font-normal normal-case">
            En çok kullanılan kombinasyonlar
        </span>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <template x-for="item in selections" :key="item.ana_slug + item.alt_slug + item.yayin_tipi_slug">
            <button type="button" x-on:click="applySelection(item)"
                class="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm hover:shadow-md transition-all duration-200 group"
                :class="hoverClass(item.color)">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-2 group-hover:scale-110 transition-transform"
                    :class="[iconBgClass(item.color), iconTextClass(item.color)]">
                    <svg x-show="item.icon === 'bina'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <svg x-show="item.icon === 'anahtar'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                    </svg>
                    <svg x-show="item.icon === 'arsa'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    <svg x-show="item.icon === 'villa'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <svg x-show="item.icon === 'tatil'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                    </svg>
                    <svg x-show="item.icon === 'dukkan'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                    <svg x-show="!['bina', 'anahtar', 'arsa', 'villa', 'tatil', 'dukkan'].includes(item.icon)" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"></path>
                    </svg>
                </div>
                <span class="text-xs font-bold text-gray-900 dark:text-slate-100 text-center"
                    x-text="item.label"></span>
            </button>
        </template>
    </div>
</div>

<script>
    function quickSelections() {
        return {
            selections: [],

            async loadSelections() {
                try {
                    const url = '/api/v1/wizard/quick-selections';
                    const res = await fetch(url);
                    console.log('[QuickSelections] HTTP:', res.status, 'URL:', url);
                    if (!res.ok) {
                        console.warn('[QuickSelections] HTTP error:', res.status, res.statusText, '-> selections stays empty');
                        return;
                    }
                    const json = await res.json();
                    console.log('[QuickSelections] json:', json, 'json.data:', json?.data, 'length:', json?.data?.length);
                    if (json?.data?.length > 0) {
                        this.selections = json.data;
                        console.log('[QuickSelections] ✅ SET:', this.selections.length, 'cards');
                    } else {
                        console.warn('[QuickSelections] ⚠️ json.data is empty — API returned 200 but data[] is empty. Check backend resolver/policy.');
                    }
                } catch (e) {
                    console.error('[QuickSelections] ❌ Fetch failed:', e);
                }
            },

            applySelection(item) {
                const config = {
                    anaSlug: item.ana_slug,
                    altSlug: item.alt_slug,
                    tipSlug: item.yayin_tipi_slug,
                    anaId: item.ana_kategori_id || null,
                    altId: item.alt_kategori_id || null,
                    tipId: item.yayin_tipi_id || null,
                };

                if (typeof window.quickSelectCategory === 'function') {
                    return window.quickSelectCategory(config);
                }

                const cascadeFn = window.YalihanWizard?.cascade?.quickSelectCategory;
                if (typeof cascadeFn === 'function') {
                    return cascadeFn(config);
                }

                console.warn('[QuickSelect] Cascade function is not ready yet.');
            },

            hoverClass(color) {
                const map = {
                    blue: 'hover:border-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20',
                    emerald: 'hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/20',
                    orange: 'hover:border-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/20',
                    indigo: 'hover:border-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20',
                    rose: 'hover:border-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20',
                    amber: 'hover:border-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20',
                };
                return map[color] || map.blue;
            },

            iconBgClass(color) {
                const map = {
                    blue: 'bg-blue-100 dark:bg-blue-900/30',
                    emerald: 'bg-emerald-100 dark:bg-emerald-900/30',
                    orange: 'bg-orange-100 dark:bg-orange-900/30',
                    indigo: 'bg-indigo-100 dark:bg-indigo-900/30',
                    rose: 'bg-rose-100 dark:bg-rose-900/30',
                    amber: 'bg-amber-100 dark:bg-amber-900/30',
                };
                return map[color] || map.blue;
            },

            iconTextClass(color) {
                const map = {
                    blue: 'text-blue-600 dark:text-blue-400',
                    emerald: 'text-emerald-600 dark:text-emerald-400',
                    orange: 'text-orange-600 dark:text-orange-400',
                    indigo: 'text-indigo-600 dark:text-indigo-400',
                    rose: 'text-rose-600 dark:text-rose-400',
                    amber: 'text-amber-600 dark:text-amber-400',
                };
                return map[color] || map.blue;
            },
        };
    }
</script>
