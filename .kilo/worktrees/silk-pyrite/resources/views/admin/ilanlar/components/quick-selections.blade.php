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
                class="flex flex-col items-center justify-center p-3 rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:shadow-sm transition-all duration-200 group"
                :class="hoverClass(item.color)">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center mb-2 group-hover:scale-110 transition-transform"
                    :class="iconBgClass(item.color)">
                    <i :class="[item.icon, iconTextClass(item.color)]"></i>
                </div>
                <span class="text-xs font-bold text-gray-900 dark:text-white text-center dark:text-slate-100"
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
                    const res = await fetch('/api/v1/wizard/quick-selections');
                    if (!res.ok) return;
                    const json = await res.json();
                    this.selections = json.data || [];
                } catch (e) {
                    console.warn('[QuickSelections] API fetch failed:', e);
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
