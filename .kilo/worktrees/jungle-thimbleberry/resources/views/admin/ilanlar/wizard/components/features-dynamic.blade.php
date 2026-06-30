{{-- 🔱 MOD-1: Dinamik Özellikler (UPS Features) --}}
<div class="bg-white dark:bg-slate-900 rounded-xl border dark:border border-gray-200 dark:border-slate-800 shadow-lg overflow-hidden mt-6 dark:border-slate-700">
    <div class="p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-gray-800/50 dark:bg-slate-900 dark:border-slate-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Özellikler
        </h3>
        <p class="{{ FormStandards::help() }}">Kategoriye özel özellikler (dinamik olarak yüklenir)</p>
    </div>
    <div class="p-6">
        <div id="features-container" class="grid grid-cols-2 md:grid-cols-3 gap-3">
            <p class="col-span-3 text-gray-500 text-sm">Kategori seçtikten sonra özellikler burada gösterilecek...</p>
        </div>
    </div>
</div>

{{-- 🔱 MOD-1: POI Mühürlü Etiketler (Sealed Badges) --}}
<div id="poi-sealed-badges-wrapper" class="bg-white dark:bg-slate-900 rounded-xl border dark:border border-gray-200 dark:border-slate-800 shadow-lg overflow-hidden mt-6 hidden dark:border-slate-700">
    <div class="p-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2 dark:text-slate-100">
            <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Çevresel Veriler (Mühürlü)
        </h3>
        <p class="{{ FormStandards::help() }}">Haversine formülü ile hesaplanmış, matematiksel olarak doğrulanmış veriler</p>
    </div>
    <div class="p-6">
        <div id="poi-sealed-badges" class="flex flex-wrap gap-2">
            {{-- POI badges dinamik olarak eklenecek --}}
        </div>
    </div>
</div>

{{-- Hidden Form Fields (Coordinates + Sealed Metadata) --}}
<input type="hidden" name="lat" id="form-lat" value="">
<input type="hidden" name="lng" id="form-lng" value="">
<input type="hidden" name="koordinat_muhurleri" id="form-sealed-coords" value="">
