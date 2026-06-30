<div x-show="showFilters"
     class="fixed inset-0 z-[80] overflow-hidden"
     style="display: none;"
     x-transition:enter="transition ease-in-out duration-300 transform"
     x-transition:enter-start="translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in-out duration-300 transform"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="translate-x-full">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-gray-500/75 dark:bg-gray-900/80 backdrop-blur-sm transition-opacity" @click="showFilters = false"></div>

    <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
        <div class="w-screen max-w-md shadow-2xl">
            <div class="flex h-full flex-col overflow-y-scroll bg-white dark:bg-slate-900 shadow-xl border-l border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <!-- Header -->
                <div class="px-6 py-6 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:border-slate-700">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                            Gelişmiş Filtreler
                        </h2>
                        <button type="button" @click="showFilters = false" class="rounded-md text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="relative flex-1 px-6 py-6 space-y-8">

                    {{-- Arama --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Metin Arama</label>
                        <div class="relative">
                            <input type="text" x-model="filters.search" class="w-full px-4 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm transition-all focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:border-slate-700" placeholder="İsim, açıklama, referans...">
                        </div>
                    </div>

                    {{-- Lokasyon --}}
                    <div class="space-y-4">
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Lokasyon</label>
                        <select x-model="filters.il_id" @change="fetchIlceler()" class="w-full px-4 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm dark:border-slate-700">
                            <option value="">Tüm İller</option>
                            @if(isset($iller))
                                @foreach($iller as $il)
                                    <option value="{{ $il->id }}">{{ $il->il_adi }}</option>
                                @endforeach
                            @endif
                        </select>
                        <select x-model="filters.ilce_id" class="w-full px-4 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm dark:border-slate-700" :disabled="!filters.il_id">
                            <option value="">Tüm İlçeler</option>
                            <template x-for="ilce in ilceler" :key="ilce.id">
                                <option :value="ilce.id" x-text="ilce.ilce_adi"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Fiyat Aralığı --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Fiyat Aralığı (₺)</label>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="number" x-model="filters.min_fiyat" placeholder="Min" class="w-full px-4 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm dark:border-slate-700">
                            <input type="number" x-model="filters.max_fiyat" placeholder="Max" class="w-full px-4 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm dark:border-slate-700">
                        </div>
                    </div>

                    {{-- Metrekare (Quick Area Chips) --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Net Alan (m²)</label>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <button @click="setArea(0, 150)" :class="filters.min_m2 == 0 && filters.max_m2 == 150 ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-900 text-gray-600 dark:text-gray-400'" class="px-3 py-1.5 rounded-full text-xs font-medium transition-all">0 - 150m²</button>
                            <button @click="setArea(150, 300)" :class="filters.min_m2 == 150 && filters.max_m2 == 300 ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-900 text-gray-600 dark:text-gray-400'" class="px-3 py-1.5 rounded-full text-xs font-medium transition-all">150 - 300m²</button>
                            <button @click="setArea(300, 500)" :class="filters.min_m2 == 300 && filters.max_m2 == 500 ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-900 text-gray-600 dark:text-gray-400'" class="px-3 py-1.5 rounded-full text-xs font-medium transition-all">300 - 500m²</button>
                            <button @click="setArea(500, null)" :class="filters.min_m2 == 500 && !filters.max_m2 ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-slate-900 text-gray-600 dark:text-gray-400'" class="px-3 py-1.5 rounded-full text-xs font-medium transition-all">500m² +</button>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <input type="number" x-model="filters.min_m2" placeholder="Min m²" class="w-full px-4 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm dark:border-slate-700">
                            <input type="number" x-model="filters.max_m2" placeholder="Max m²" class="w-full px-4 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm dark:border-slate-700">
                        </div>
                    </div>

                    {{-- Yayın Durumu --}}
                    <div>
                        <label class="block text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">Yayın Durumu</label>
                        <select x-model="filters.yayin_durumu" class="w-full px-4 py-2 bg-gray-50 dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg text-sm dark:border-slate-700">
                            <option value="">Tüm Durumlar</option>
                            <option value="1">Aktif</option>
                            <option value="2">Pasif</option>
                            <option value="3">Süresi Dolan</option>
                            <option value="4">Silinenler</option>
                        </select>
                    </div>

                </div>

                <!-- Footer -->
                <div class="px-6 py-6 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:border-slate-700">
                    <div class="flex gap-4">
                        <button type="button" @click="clearFilters()" class="flex-1 px-4 py-2.5 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-xl text-sm font-bold hover:bg-gray-300 dark:hover:bg-gray-600 transition-all dark:text-slate-300">Sıfırla</button>
                        <button type="button" @click="applyFilters(); showFilters = false;" class="flex-[2] px-4 py-2.5 bg-blue-600 text-white rounded-xl text-sm font-bold hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all">Sonuçları Göster</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
