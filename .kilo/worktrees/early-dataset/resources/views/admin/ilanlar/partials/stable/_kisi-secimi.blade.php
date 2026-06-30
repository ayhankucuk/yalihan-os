{{--
    🎨 Kişi Seçimi - Context7 Live Search (Tailwind Modernized)
    Context7 Standardı: C7-STABLE-CREATE-KISI-SECIMI
--}}

<div
    class="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-gray-200 dark:border-slate-700 p-8">
    <!-- Section Header -->
    @if(!($wizardMode ?? false))
    <div
        class="px-5 py-3 border-b border-gray-200 dark:border-gray-700
                bg-gray-50 dark:bg-slate-800
                rounded-t-lg
                flex items-center gap-4 mb-8">
        <div
            class="flex items-center justify-center w-12 h-12 rounded-xl bg-purple-50 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400 font-bold text-lg">
            6
        </div>
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                Kişi Bilgileri
            </h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">İlan sahibi, ilgili kişi ve danışman seçimi
                (Context7 Live Search)</p>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 1. İlan Sahibi --}}
        <div class="group relative bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-5 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all duration-300 dark:border-slate-700">
            <div class="absolute -top-3 -left-3 w-8 h-8 rounded-full bg-purple-600 text-white flex items-center justify-center text-sm font-bold z-10">1</div>

            <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-slate-200 pl-2 dark:text-slate-300">
                İlan Sahibi <span class="text-red-500">*</span>
            </label>

            <div class="context7-live-search relative w-full"
                 data-search-type="kisiler"
                 data-placeholder="İsim veya telefon ile ara..."
                 data-endpoint="/api/v1/kisiler/search"
                 data-max-results="10"
                 data-creatable="true"
                 data-add-modal-id="add_person_modal">

                <input type="hidden" name="ilan_sahibi_id" id="ilan_sahibi_id" value="{{ old('ilan_sahibi_id') }}">

                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                    <input type="text" id="ilan_sahibi_search"
                        class="w-full pl-10 pr-4 py-3 text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all shadow-sm group-hover:bg-white dark:group-hover:bg-gray-900 dark:shadow-none dark:bg-slate-900 dark:text-slate-100"
                        placeholder="Kişi ara..." autocomplete="off">
                </div>

                <div class="context7-search-results absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-xl hidden max-h-60 overflow-y-auto dark:border-slate-700"></div>
            </div>

            <button type="button" @click="window.dispatchEvent(new CustomEvent('open-quick-client-modal', {detail: {type: 'owner'}}))"
                class="mt-3 w-full py-2 flex items-center justify-center gap-2 text-xs font-semibold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/10 hover:bg-purple-100 dark:hover:bg-purple-900/30 rounded-lg transition-colors dashed-border border-purple-200 dark:border-purple-800/30">
                <i class="fas fa-plus"></i> Yeni Kişi Ekle
            </button>
        </div>

        {{-- 2. İlgili Kişi --}}
        <div class="group relative bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-5 hover:shadow-md hover:border-purple-300 dark:hover:border-purple-700 transition-all duration-300 dark:border-slate-700">
            <div class="absolute -top-3 -left-3 w-8 h-8 rounded-full bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 text-gray-500 dark:text-gray-400 flex items-center justify-center text-sm font-bold z-10">2</div>

            <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-slate-200 pl-2 dark:text-slate-300">
                İlgili Kişi <span class="text-xs font-normal text-gray-400 ml-1">(Opsiyonel)</span>
            </label>

            <div class="context7-live-search relative w-full"
                 data-search-type="kisiler"
                 data-placeholder="Arama yap..."
                 data-endpoint="/api/v1/kisiler/search"
                 data-max-results="10"
                 data-creatable="true"
                 data-add-modal-id="add_person_modal">

                <input type="hidden" name="ilgili_kisi_id" id="ilgili_kisi_id" value="{{ old('ilgili_kisi_id') }}">

                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user-friends text-gray-400"></i>
                    </span>
                    <input type="text" id="ilgili_kisi_search"
                        class="w-full pl-10 pr-4 py-3 text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-900/50 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500 transition-all shadow-sm group-hover:bg-white dark:group-hover:bg-gray-900 dark:shadow-none dark:bg-slate-900 dark:text-slate-100"
                        placeholder="İlgili kişi ara..." autocomplete="off">
                </div>

                <div class="context7-search-results absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-xl hidden max-h-60 overflow-y-auto dark:border-slate-700"></div>
            </div>

            <button type="button" @click="window.dispatchEvent(new CustomEvent('open-quick-client-modal', {detail: {type: 'related'}}))"
                class="mt-3 w-full py-2 flex items-center justify-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-slate-900 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors dashed-border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <i class="fas fa-plus"></i> Yeni İlgili Ekle
            </button>
        </div>

        {{-- 3. Danışman --}}
        <div class="group relative bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 p-5 hover:shadow-md hover:border-blue-300 dark:hover:border-blue-700 transition-all duration-300 dark:border-slate-700">
            <div class="absolute -top-3 -left-3 w-8 h-8 rounded-full bg-blue-600 text-white flex items-center justify-center text-sm font-bold z-10">3</div>

            <label class="block mb-2 text-sm font-bold text-gray-700 dark:text-slate-200 pl-2 dark:text-slate-300">
                Danışman <span class="text-red-500">*</span>
            </label>

            <div class="context7-live-search relative w-full"
                 data-search-type="users"
                 data-placeholder="Sistem kullanıcısı seçin..."
                 data-endpoint="/api/v1/users/search"
                 data-max-results="10"
                 data-creatable="false">

                <input type="hidden" name="danisman_id" id="danisman_id" value="{{ old('danisman_id', auth()->id()) }}" required>

                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user-tie text-blue-400"></i>
                    </span>
                    <input type="text" id="danisman_search"
                        class="w-full pl-10 pr-4 py-3 text-sm font-medium border border-blue-200 dark:border-blue-800 rounded-lg bg-blue-50/30 dark:bg-blue-900/10 text-gray-900 dark:text-white placeholder-blue-400 dark:placeholder-blue-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm group-hover:bg-blue-50/50 dark:group-hover:bg-blue-900/20 dark:shadow-none dark:text-slate-100"
                        placeholder="Danışman ara..." autocomplete="off">
                </div>

                <div class="context7-search-results absolute z-50 w-full mt-1 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-xl hidden max-h-60 overflow-y-auto dark:border-slate-700"></div>
            </div>

            <div class="mt-3 group relative inline-block">
                 <i class="fas fa-info-circle text-blue-400 cursor-help"></i>
                 <span class="invisible group-hover:visible absolute left-0 bottom-full mb-2 w-48 p-2 bg-gray-900 text-white text-xs rounded shadow-lg z-50">
                    Sistem kullanıcısıdır. Harici kişi eklenemez.
                 </span>
            </div>

             @error('danisman_id')
                <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

