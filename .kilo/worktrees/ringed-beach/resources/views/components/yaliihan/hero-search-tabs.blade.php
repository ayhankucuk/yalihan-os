@props([
    'action' => route('ilanlar.index'),
    'tabs' => [
        ['id' => 'all', 'label' => 'Tümü', 'query' => null],
        ['id' => 'sale', 'label' => 'Satılık', 'query' => 'satilik'],
        ['id' => 'rent', 'label' => 'Kiralık', 'query' => 'kiralik'],
    ],
    'locations' => [
        ['value' => '', 'label' => 'Lokasyon Seçin'],
        ['value' => 'Bodrum', 'label' => 'Bodrum'],
        ['value' => 'İstanbul', 'label' => 'İstanbul'],
        ['value' => 'Ankara', 'label' => 'Ankara'],
        ['value' => 'İzmir', 'label' => 'İzmir'],
        ['value' => 'Yalıkavak', 'label' => 'Yalıkavak'],
    ],
    'propertyTypes' => [
        ['value' => '', 'label' => 'Emlak Türü'],
        ['value' => 'villa', 'label' => 'Villa'],
        ['value' => 'konut', 'label' => 'Konut'],
        ['value' => 'arsa', 'label' => 'Arsa'],
        ['value' => 'isyeri', 'label' => 'İşyeri'],
        ['value' => 'yazlik', 'label' => 'Yazlık Kiralık'],
    ],
])

<div
    x-data="{
        activeTab: 'all',
        tabs: {{ collect($tabs)->map(fn ($tab) => ['id' => $tab['id'], 'label' => $tab['label'], 'query' => $tab['query']])->toJson() }},
        get queryValue() {
            const tab = this.tabs.find(tab => tab.id === this.activeTab);
            return tab?.query ?? '';
        }
    }"
    class="hero-search-tabs w-full max-w-4xl mx-auto">
    <div class="myhome-search-form--wrapper bg-white/95 dark:bg-slate-900/95 backdrop-blur-xl rounded-3xl shadow-[0_25px_80px_rgba(0,0,0,0.25)] border border-white/60 dark:border-white/10 overflow-hidden transition-all duration-300">
        <!-- Tabs -->
        <div class="flex items-center justify-center gap-2 bg-slate-100/60 dark:bg-slate-800/70 px-4 py-3">
            <template x-for="tab in tabs" :key="tab.id">
                <button
                    type="button"
                    class="px-4 sm:px-6 py-2 text-xs sm:text-sm font-semibold tracking-wide uppercase rounded-full transition-all duration-300 focus:outline-none"
                    :class="activeTab === tab.id
                        ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30'
                        : 'text-slate-500 dark:text-slate-300 hover:text-amber-500'"
                    x-text="tab.label"
                    @click="activeTab = tab.id">
                </button>
            </template>
        </div>

        <!-- Form -->
        <form :action="`{{ $action }}`" method="GET" class="px-6 sm:px-8 py-6 space-y-6">
            <input type="hidden" name="ilan_turu" :value="queryValue">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Location -->
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Lokasyon</label>
                    <select name="location"
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/60 focus:border-amber-500 transition dark:bg-slate-900/90">
                        @foreach ($locations as $location)
                            <option value="{{ $location['value'] }}">{{ $location['label'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Property Type -->
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Emlak Türü</label>
                    <select name="emlak_turu"
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900 text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/60 focus:border-amber-500 transition dark:bg-slate-900/90">
                        @foreach ($propertyTypes as $type)
                            <option value="{{ $type['value'] }}">{{ $type['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Min Fiyat</label>
                    <input type="number" name="min_fiyat" placeholder="Min"
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-amber-500/60 focus:border-amber-500 transition dark:bg-slate-900/90" />
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Max Fiyat</label>
                    <input type="number" name="max_fiyat" placeholder="Max"
                        class="w-full px-4 py-3 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/90 dark:bg-slate-900 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-amber-500/60 focus:border-amber-500 transition dark:bg-slate-900/90" />
                </div>
            </div>

            <div class="flex items-center justify-center">
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-8 py-3 sm:px-10 sm:py-3.5 rounded-full bg-amber-500 text-white font-semibold tracking-wide uppercase shadow-lg shadow-amber-500/40 hover:shadow-amber-500/60 hover:scale-[1.01] active:scale-95 transition">
                    <span>İlanları Göster</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </form>
    </div>
</div>

