{{-- STEP 2: UNIFIED FEATURES ENGINE (CREATE + EDIT) --}}
{{-- Single engine for both modes. CREATE: empty form. EDIT: hydrated form. --}}
{{-- SSOT: FeatureTemplateResolver → /api/v1/wizard/features → this template --}}

<div x-data="wizardStep2FeaturesComponent({ ilanId: {{ $ilan->id ?? 'null' }} })" id="step2-features-container" class="space-y-6">

    {{-- Loading State --}}
    <div x-show="loading" x-transition class="flex items-center justify-center py-12">
        <div class="text-center">
            <svg class="animate-spin h-8 w-8 text-blue-500 mx-auto mb-3" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
            <p class="text-sm text-gray-500 dark:text-slate-400">Özellikler yükleniyor...</p>
        </div>
    </div>

    {{-- Error State --}}
    <div x-show="error && !loading" x-transition
        class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4">
        <div class="flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-red-700 dark:text-red-400" x-text="error"></p>
        </div>
        <button type="button" @click="load()"
            class="mt-2 text-sm text-red-600 dark:text-red-400 underline hover:text-red-800">
            Tekrar Dene
        </button>
    </div>

    {{-- Empty State (no category/yayin_tipi selected) --}}
    <div x-show="!loading && !error && !loaded" x-transition
        class="bg-gray-50 dark:bg-slate-800/50 border border-gray-200 dark:border-slate-700 rounded-xl p-8 text-center">
        <svg class="w-12 h-12 text-gray-300 dark:text-slate-600 mx-auto mb-3" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-sm text-gray-500 dark:text-slate-400">
            Kategori ve yayın tipi seçtikten sonra özellikler burada gösterilecek.
        </p>
    </div>

    {{-- Schema-Driven Fields --}}
    <div x-show="loaded && !loading && fields.length > 0" class="space-y-6">

        {{-- Schema Meta Info --}}
        <div class="flex items-center justify-between px-1">
            <p class="text-xs text-gray-400 dark:text-slate-500">
                <span x-text="meta?.field_count || 0"></span> özellik ·
                <span x-text="meta?.required_count || 0"></span> zorunlu
                <span x-show="ilanId" class="ml-2 text-blue-400">· düzenleme modu</span>
            </p>
        </div>

        {{-- Grouped Fields --}}
        <template x-for="groupItem in groups" :key="groupItem.group">
            <div
                class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-700 shadow-sm overflow-hidden mb-6">

                {{-- Group Header --}}
                <div
                    class="px-5 py-3 border-b border-gray-100 dark:border-slate-800 bg-gray-50 dark:bg-slate-800/50">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-slate-300 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span x-text="groupItem.group"></span>
                    </h4>
                </div>

                {{-- Group Fields Grid --}}
                <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                     x-html="renderGroupHtml(groupItem)">
                </div>
            </div>
        </template>
    </div>
    
    <div x-show="fields.length === 0 && loaded" x-transition
        class="text-center py-8 text-sm text-gray-400 dark:text-slate-500 bg-white dark:bg-slate-900 rounded-xl border border-dashed border-gray-300 dark:border-slate-700">
        <svg class="w-10 h-10 text-gray-300 dark:text-slate-600 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        Bu kategori/yayın tipi için özel özellik tanımı bulunmamaktadır.
    </div>
</div>
