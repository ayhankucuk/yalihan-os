{{-- 🔍 CORTEX SMART SEARCH & FILTERS --}}
<div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-md p-6 sm:p-8">
    <form @submit.prevent="applyFilters()" method="GET" action="{{ route('admin.ilanlar.filter') }}">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-6">
            {{-- Search Bar --}}
            <div class="md:col-span-12 lg:col-span-12 flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative group">
                    <div class="absolute inset-y-0 left-5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-[#C9A84C] transition-colors">
                        <x-icon name="arama" class="w-5 h-5" />
                    </div>
                    <input type="text" name="search" x-model="filters.search"
                        @input.debounce.500ms="applyFilters()"
                        placeholder="İlan başlığı, referans no veya bölge..."
                        class="w-full pl-14 pr-6 py-3.5 bg-slate-50 dark:bg-slate-800/60 border-2 border-slate-100 dark:border-slate-800 rounded-xl text-slate-900 dark:text-white placeholder:text-slate-400 focus:border-[#C9A84C] dark:focus:border-[#C9A84C] focus:ring-0 transition-all duration-300 text-sm">
                </div>

                <button type="button" @click="showFilters = true"
                    class="flex items-center justify-center gap-2.5 px-6 py-3.5 bg-slate-50 dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-800 rounded-xl hover:border-[#C9A84C] transition-all group">
                    <x-icon name="filtrele" class="w-4 h-4 text-slate-400 group-hover:text-[#C9A84C] transition-colors" />
                    <span class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-300 group-hover:text-[#C9A84C] transition-colors">
                        Gelişmiş Filtreler
                    </span>
                </button>
            </div>

            {{-- Quick Filter Selects --}}
            <div class="md:col-span-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Yayın Durumu --}}
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-2 ml-1">{{ __('admin.status') }}</label>
                    <select name="yayin_durumu" x-model="filters.yayin_durumu" @change="applyFilters()"
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border-2 border-slate-100 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:border-[#C9A84C] focus:ring-0 transition-all cursor-pointer">
                        <option value="">{{ __('admin.all_statuses') }}</option>
                        <option value="yayinda" {{ request('yayin_durumu') == 'yayinda' ? 'selected' : '' }}>{{ __('admin.status_published') }}</option>
                        <option value="beklemede" {{ request('yayin_durumu') == 'beklemede' ? 'selected' : '' }}>{{ __('admin.status_pending') }}</option>
                        <option value="taslak" {{ request('yayin_durumu') == 'taslak' ? 'selected' : '' }}>{{ __('admin.status_draft') }}</option>
                        <option value="pasif" {{ request('yayin_durumu') == 'pasif' ? 'selected' : '' }}>{{ __('admin.status_passive') }}</option>
                        <option value="arsiv" {{ request('yayin_durumu') == 'arsiv' ? 'selected' : '' }}>{{ __('admin.status_archived') }}</option>
                    </select>
                </div>

                {{-- Kategori --}}
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-2 ml-1">Kategori</label>
                    <select name="kategori_id" x-model="filters.kategori_id" @change="applyFilters()"
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border-2 border-slate-100 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:border-[#C9A84C] focus:ring-0 transition-all cursor-pointer">
                        <option value="">Tümü</option>
                        @if (isset($kategoriler))
                            @foreach ($kategoriler as $kategori)
                                <option value="{{ $kategori->id }}"
                                    {{ request('kategori_id') == $kategori->id ? 'selected' : '' }}>
                                    {{ $kategori->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                {{-- Kiralama Türü --}}
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-2 ml-1">Kiralama Türü</label>
                    <select name="kiralama_turu" x-model="filters.kiralama_turu" @change="applyFilters()"
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border-2 border-slate-100 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:border-[#C9A84C] focus:ring-0 transition-all cursor-pointer">
                        <option value="">Tümü</option>
                        <option value="gunluk" {{ request('kiralama_turu') == 'gunluk' ? 'selected' : '' }}>Günlük</option>
                        <option value="haftalik" {{ request('kiralama_turu') == 'haftalik' ? 'selected' : '' }}>Haftalık</option>
                        <option value="aylik" {{ request('kiralama_turu') == 'aylik' ? 'selected' : '' }}>Aylık</option>
                        <option value="uzun_donem" {{ request('kiralama_turu') == 'uzun_donem' ? 'selected' : '' }}>Uzun Dönem</option>
                        <option value="sezonluk" {{ request('kiralama_turu') == 'sezonluk' ? 'selected' : '' }}>Sezonluk</option>
                    </select>
                </div>

                {{-- Sıralama --}}
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-[0.2em] text-slate-400 dark:text-slate-500 mb-2 ml-1">Sıralama</label>
                    <select name="sort" x-model="filters.sort" @change="applyFilters()"
                        class="w-full px-4 py-3 bg-slate-50 dark:bg-slate-800/60 border-2 border-slate-100 dark:border-slate-800 rounded-xl text-xs font-bold text-slate-900 dark:text-white focus:border-[#C9A84C] focus:ring-0 transition-all cursor-pointer">
                        <option value="created_desc" {{ request('sort') === 'created_desc' ? 'selected' : '' }}>En Yeni</option>
                        <option value="created_asc" {{ request('sort') === 'created_asc' ? 'selected' : '' }}>En Eski</option>
                        <option value="price_desc" {{ request('sort') === 'price_desc' ? 'selected' : '' }}>Fiyat (Yüksekten Düşüğe)</option>
                        <option value="price_asc" {{ request('sort') === 'price_asc' ? 'selected' : '' }}>Fiyat (Düşükten Yükseğe)</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex justify-end items-center gap-3 mt-6 pt-6 border-t border-slate-100 dark:border-slate-800">
            <button type="button" @click="clearFilters()"
                class="px-6 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 font-bold rounded-xl hover:bg-slate-200 dark:hover:bg-slate-700 transition-all text-xs uppercase tracking-wider">
                Sıfırla
            </button>
            <button type="button" @click="applyFilters()" :disabled="loading"
                class="inline-flex items-center gap-2 px-8 py-2.5 bg-[#0A1628] hover:bg-[#132238] text-[#C9A84C] font-black rounded-xl shadow-md transition-all text-xs uppercase tracking-wider disabled:opacity-50 dark:bg-[#C9A84C] dark:text-[#0A1628] dark:hover:bg-amber-400">
                <x-icon x-show="!loading" name="arama" class="w-3.5 h-3.5" />
                <x-icon x-show="loading" name="yukleniyor" class="animate-spin w-3.5 h-3.5" />
                <span x-text="loading ? 'İşleniyor...' : 'Filtrele'"></span>
            </button>
        </div>
    </form>
</div>
