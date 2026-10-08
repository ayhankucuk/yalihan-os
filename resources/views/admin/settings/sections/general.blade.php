{{-- General Settings Section --}}
<div class="space-y-6">
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2 mb-4">
            <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Genel Ayarlar
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Sitenin temel ayarlarını buradan yönetebilirsiniz.</p>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <x-admin.form-field label="Site Başlığı" name="site_title" required :error="$errors->first('site_title')">
            <x-admin.input
                name="site_title"
                :value="old('site_title', $settings['site_title'] ?? '')"
                placeholder="Yalıhan Emlak"
            />
        </x-admin.form-field>

        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                Varsayılan Para Birimi
            </label>
            @php
                $defaultCurr = $currencies->firstWhere('varsayilan_durumu', true) ?? $currencies->firstWhere('code', 'TRY');
            @endphp
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-gray-700 dark:bg-slate-800/60">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-green-100 text-xs font-bold text-green-800 dark:bg-green-900/40 dark:text-green-300">
                        {{ $defaultCurr->symbol ?? '₺' }}
                    </span>
                    <span class="text-sm font-medium text-gray-900 dark:text-slate-100">
                        {{ $defaultCurr->code ?? 'TRY' }}
                    </span>
                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-green-800 dark:bg-green-900/40 dark:text-green-300">
                        VARSAYILAN
                    </span>
                </div>
                <button type="button" onclick="document.querySelector('.tab-button[data-tab=\'paralar\']').click()" class="text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                    Yönet &rarr;
                </button>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Para birimi ayarları <button type="button" onclick="document.querySelector('.tab-button[data-tab=\'paralar\']').click()" class="text-blue-600 underline dark:text-blue-400">Para Birimleri</button> sekmesinden yönetilir.</p>
        </div>

        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                Varsayılan Dil
            </label>
            @php
                $defaultLang = $languages->firstWhere('varsayilan_durumu', true) ?? $languages->firstWhere('code', 'tr');
            @endphp
            <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 dark:border-gray-700 dark:bg-slate-800/60">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                        {{ strtoupper($defaultLang->code ?? 'TR') }}
                    </span>
                    <span class="text-sm font-medium text-gray-900 dark:text-slate-100">
                        {{ $defaultLang->name ?? 'Türkçe' }}
                    </span>
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-blue-800 dark:bg-blue-900/40 dark:text-blue-300">
                        VARSAYILAN
                    </span>
                </div>
                <button type="button" onclick="document.querySelector('.tab-button[data-tab=\'diller\']').click()" class="text-xs font-semibold text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors">
                    Yönet &rarr;
                </button>
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Sistem dili ayarları <button type="button" onclick="document.querySelector('.tab-button[data-tab=\'diller\']').click()" class="text-blue-600 underline dark:text-blue-400">Diller</button> sekmesinden yönetilir.</p>
        </div>
    </div>
</div>
