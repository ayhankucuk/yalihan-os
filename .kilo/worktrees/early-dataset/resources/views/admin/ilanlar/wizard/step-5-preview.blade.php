@php
    use App\Helpers\FormStandards;
@endphp

{{-- STEP 5: ÖNİZLEME VE YAYIN --}}
<div class="space-y-6" x-data="{
    summary: {
        baslik: '',
        fiyat: '',
        kategori: '',
        konum: '',
        photoCount: 0
    },
    updateSummary() {
        this.summary.baslik = document.getElementById('baslik')?.value || 'Başlık Belirtilmedi';
        this.summary.fiyat = document.getElementById('fiyat_display')?.value || '0';

        const cat = document.getElementById('alt_kategori_id');
        this.summary.kategori = cat?.options[cat.selectedIndex]?.text || 'Kategori Seçilmedi';

        const il = document.getElementById('il_id');
        const ilce = document.getElementById('ilce_id');
        this.summary.konum = `${il?.options[il.selectedIndex]?.text || ''} / ${ilce?.options[ilce.selectedIndex]?.text || ''}`;

        // Fotoğraf sayısını senkronize et (Eğer global wizard nesnesi varsa)
        if (window.wizardService && window.wizardService.photos) {
            this.summary.photoCount = window.wizardService.photos.length;
        } else if (typeof photos !== 'undefined') {
            this.summary.photoCount = photos.length;
        }
    }
}" x-init="updateSummary();
window.addEventListener('wizard-step-changed', (e) => { if (e.detail.step === 5) updateSummary() })">

    <div class="mb-6">
        <h3 class="mb-2 text-xl font-bold text-gray-900 dark:text-slate-100">
            ✅ Son Adım: Önizleme ve Yayın
        </h3>
        <p class="{{ FormStandards::help() }} !text-sm">İlanınızı gözden geçirin, CRM bilgilerini tamamlayın ve
            yayınlayın.</p>
    </div>

    {{-- Akıllı Özet Paneli --}}
    <div
        class="rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 p-6 shadow-sm dark:border-blue-800 dark:from-blue-900/20 dark:to-indigo-900/20 dark:shadow-none">
        <div class="mb-6 flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-600 text-white shadow-lg">
                <i class="fas fa-eye text-xl"></i>
            </div>
            <div>
                <h4 class="text-lg font-bold text-gray-900 dark:text-slate-100" x-text="summary.baslik">
                </h4>
                <p class="text-sm font-medium text-blue-700 dark:text-blue-300" x-text="summary.kategori"></p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <div
                class="rounded-xl border border-gray-100 bg-white p-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900">
                <span
                    class="mb-1 block text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-slate-500">Satış
                    Fiyatı</span>
                <span class="text-xl font-black text-green-600 dark:text-green-400"
                    x-text="summary.fiyat + ' ₺'"></span>
            </div>
            <div
                class="rounded-xl border border-gray-100 bg-white p-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900">
                <span
                    class="mb-1 block text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-slate-500">Lokasyon</span>
                <span class="text-sm font-bold text-gray-700 dark:text-slate-100" x-text="summary.konum"></span>
            </div>
            <div
                class="rounded-xl border border-gray-100 bg-white p-4 backdrop-blur-sm dark:border-slate-800 dark:bg-slate-900">
                <span
                    class="mb-1 block text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-slate-500">Fotoğraflar</span>
                <span class="text-sm font-bold text-gray-700 dark:text-slate-100"
                    x-text="summary.photoCount + ' Adet'"></span>
            </div>
        </div>
    </div>

    {{-- 👥 CRM & Sorumlu Yönetimi (Step 2'den taşındı) --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-lg dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 text-orange-600 dark:bg-orange-900/30">
                    <i class="fas fa-users-cog text-xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">CRM & Portföy
                        Yönetimi</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">İlan sahibi ve sorumlu danışman ataması</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            {{-- İlan Sahibi --}}
            <div class="group relative">
                <label for="ilan_sahibi_id" class="wizard-field-label">İlan Sahibi <span
                        class="text-red-500">*</span></label>

                <div class="context7-live-search relative w-full" data-search-type="kisiler"
                    data-placeholder="İsim veya telefon ile ara..." data-endpoint="/api/v1/kisiler/search"
                    data-max-results="10" data-creatable="true">

                    <input type="hidden" name="ilan_sahibi_id" id="ilan_sahibi_id" value="{{ old('ilan_sahibi_id') }}"
                        required>

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-search text-gray-400 dark:text-slate-500"></i>
                        </span>
                        <input type="text" id="ilan_sahibi_search" class="wizard-field pl-10 pr-10"
                            placeholder="Kişi ara..." autocomplete="off">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <i class="fas fa-spinner fa-spin hidden text-gray-400 dark:text-slate-500"
                                id="ilan_sahibi_loading"></i>
                        </span>
                    </div>

                    <div
                        class="context7-search-results absolute z-50 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                    </div>
                </div>

                <button type="button"
                    @click="window.dispatchEvent(new CustomEvent('open-quick-client-modal', {detail: {type: 'owner'}}))"
                    class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-purple-200 bg-purple-50 py-2 text-xs font-semibold text-purple-600 transition-colors hover:bg-purple-100 dark:border-purple-800/30 dark:bg-purple-900/10 dark:text-purple-400 dark:hover:bg-purple-900/30">
                    <i class="fas fa-plus"></i> Yeni Kişi Ekle
                </button>
            </div>

            {{-- İlgili Kişi --}}
            <div class="group relative">
                <label for="ilgili_kisi_id" class="wizard-field-label">İlgili Kişi <span
                        class="ml-1 text-xs font-normal text-gray-400">(Opsiyonel)</span></label>

                <div class="context7-live-search relative w-full" data-search-type="kisiler"
                    data-placeholder="Aracı, avukat vb. ara..." data-endpoint="/api/v1/kisiler/search" data-max-results="10"
                    data-creatable="true">

                    <input type="hidden" name="ilgili_kisi_id" id="ilgili_kisi_id" value="{{ old('ilgili_kisi_id') }}">

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-user-friends text-gray-400 dark:text-slate-500"></i>
                        </span>
                        <input type="text" id="ilgili_kisi_search" class="wizard-field pl-10 pr-10"
                            placeholder="İlgili kişi ara..." autocomplete="off">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <i class="fas fa-spinner fa-spin hidden text-gray-400 dark:text-slate-500"
                                id="ilgili_kisi_loading"></i>
                        </span>
                    </div>

                    <div
                        class="context7-search-results absolute z-50 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                    </div>
                </div>

                <button type="button"
                    @click="window.dispatchEvent(new CustomEvent('open-quick-client-modal', {detail: {type: 'related'}}))"
                    class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-dashed border-gray-200 bg-gray-50 py-2 text-xs font-semibold text-gray-500 transition-colors hover:bg-gray-100 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-400 dark:hover:bg-gray-700">
                    <i class="fas fa-plus"></i> Yeni İlgili Ekle
                </button>
            </div>

            {{-- Sorumlu Danışman --}}
            <div class="group relative">
                <label for="danisman_id" class="wizard-field-label">Sorumlu Danışman <span
                        class="text-red-500">*</span></label>

                <div class="context7-live-search relative w-full" data-search-type="users"
                    data-placeholder="Sistem kullanıcısı seçin..." data-endpoint="/api/v1/admin/list/danismanlar"
                    data-max-results="10" data-creatable="false">

                    <input type="hidden" name="danisman_id" id="danisman_id"
                        value="{{ old('danisman_id', auth()->id()) }}" required>

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-user-tie text-blue-400"></i>
                        </span>
                        <input type="text" id="danisman_search"
                            class="wizard-field border-blue-200 bg-blue-50/30 pl-10 pr-10 dark:border-blue-800 dark:bg-blue-900/10"
                            placeholder="Danışman ara..." autocomplete="off"
                            value="{{ auth()->user()->name ?? '' }}">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <i class="fas fa-spinner fa-spin hidden text-blue-400" id="danisman_loading"></i>
                        </span>
                    </div>

                    <div
                        class="context7-search-results absolute z-50 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                    </div>
                </div>

                <div
                    class="mt-3 rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 dark:border-blue-800/30 dark:bg-blue-900/20">
                    <p class="flex items-start gap-1.5 text-[10px] leading-tight text-blue-600 dark:text-blue-300">
                        <i class="fas fa-info-circle mt-0.5"></i>
                        <span>Sistem kullanıcısıdır. Harici kişi eklenemez.</span>
                    </p>
                </div>
            </div>
        </div>

        {{-- Site/Apartman (Ayrı satır) --}}
        <div
            class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <div class="group relative max-w-md">
                <label for="site_id" class="wizard-field-label">Site/Apartman <span
                        class="ml-1 text-xs font-normal text-gray-400">(Opsiyonel)</span></label>

                <div class="context7-live-search relative w-full" data-search-type="sites"
                    data-placeholder="Site veya apartman ara..." data-endpoint="/api/v1/sites/search"
                    data-max-results="10" data-creatable="false">

                    <input type="hidden" name="site_id" id="site_id" value="{{ old('site_id') }}">

                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <i class="fas fa-building text-gray-400 dark:text-slate-500"></i>
                        </span>
                        <input type="text" id="site_search" class="wizard-field pl-10 pr-10"
                            placeholder="Site/Apartman ara..." autocomplete="off">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                            <i class="fas fa-spinner fa-spin hidden text-gray-400 dark:text-slate-500"
                                id="site_loading"></i>
                        </span>
                    </div>

                    <div
                        class="context7-search-results absolute z-50 mt-1 hidden max-h-60 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-xl dark:border-slate-800 dark:bg-slate-900">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 🤖 AI Price Advisor - Decision Augmentation Panel (Phase 19) --}}
    <div x-data="cortexPriceAdvisor()" x-init="init()" x-show="analysis || loading"
        class="overflow-hidden rounded-3xl border border-slate-800 bg-slate-900 shadow-2xl transition-all duration-500">
        <div
            class="flex items-center justify-between border-b border-slate-800 bg-gradient-to-r from-slate-900 to-slate-800 p-6">
            <div class="flex items-center gap-3">
                <div class="group flex h-10 w-10 items-center justify-center rounded-xl bg-blue-600/20 text-blue-400">
                    <i class="fas fa-chart-line transition-transform group-hover:scale-110"></i>
                </div>
                <div>
                    <h4 class="text-sm font-black uppercase tracking-tighter text-white">Cortex Price Advisor</h4>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Market Strategy Analysis
                    </p>
                </div>
            </div>
            <template x-if="loading">
                <div class="flex items-center gap-2">
                    <div class="h-2 w-2 animate-ping rounded-full bg-blue-500"></div>
                    <span class="text-[10px] font-black uppercase text-white">Analiz Ediliyor...</span>
                </div>
            </template>
        </div>

        <div class="p-6" x-show="analysis">
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                {{-- Recommended --}}
                <div class="rounded-2xl border border-slate-700 bg-slate-800/50 p-4">
                    <span class="mb-1 block text-[9px] font-black uppercase tracking-widest text-slate-500">Önerilen
                        Fiyat</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-lg font-black text-blue-400"
                            x-text="new Intl.NumberFormat('tr-TR').format(analysis?.recommended_price)"></span>
                        <span class="text-[10px] font-bold text-slate-500">₺</span>
                    </div>
                </div>

                {{-- Market Range --}}
                <div class="rounded-2xl border border-slate-700 bg-slate-800/50 p-4">
                    <span class="mb-1 block text-[9px] font-black uppercase tracking-widest text-slate-500">Piyasa
                        Aralığı</span>
                    <div class="text-[11px] font-bold text-slate-300">
                        <span x-text="new Intl.NumberFormat('tr-TR').format(analysis?.price_range?.min)"></span> -
                        <span x-text="new Intl.NumberFormat('tr-TR').format(analysis?.price_range?.max)"></span> ₺
                    </div>
                </div>

                {{-- Position --}}
                <div class="rounded-2xl border border-slate-700 bg-slate-800/50 p-4">
                    <span
                        class="mb-1 block text-[9px] font-black uppercase tracking-widest text-slate-500">Konumlanma</span>
                    <div class="flex items-center gap-2">
                        <div class="h-2 w-2 rounded-full"
                            :class="{
                                'bg-green-500': analysis?.market_position === 'BALANCED' || analysis
                                    ?.market_position === 'COMPETITIVE',
                                'bg-yellow-500': analysis?.market_position === 'PREMIUM',
                                'bg-red-500': analysis?.market_position === 'OVERPRICED'
                            }">
                        </div>
                        <span class="text-xs font-black text-white" x-text="analysis?.market_position"></span>
                    </div>
                </div>

                {{-- Sale Time --}}
                <div class="rounded-2xl border border-slate-700 bg-slate-800/50 p-4">
                    <span class="mb-1 block text-[9px] font-black uppercase tracking-widest text-slate-500">Tahmini
                        Satış</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-lg font-black text-purple-400"
                            x-text="analysis?.predicted_sale_days"></span>
                        <span class="text-[10px] font-bold text-slate-500">Gün</span>
                    </div>
                </div>
            </div>

            {{-- Justification --}}
            <div class="rounded-2xl border border-blue-500/20 bg-blue-600/5 p-5">
                <div class="flex items-start gap-4">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-white">
                        <i class="fas fa-brain text-xs"></i>
                    </div>
                    <div class="space-y-3">
                        <p class="text-[13px] font-medium leading-relaxed text-slate-300"
                            x-text="analysis?.explanation?.summary"></p>

                        <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <template x-for="detail in analysis?.explanation?.details" :key="detail">
                                <div class="flex items-center gap-2 text-[11px] font-bold text-slate-400">
                                    <div class="h-1 w-1 rounded-full bg-blue-500"></div>
                                    <span x-text="detail"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Güven Skoru:</span>
                    <div class="h-1.5 w-24 overflow-hidden rounded-full bg-slate-800">
                        <div class="h-full rounded-full bg-blue-500" :style="`width: ${analysis?.confidence * 100}%`">
                        </div>
                    </div>
                    <span class="text-[10px] font-black text-blue-400"
                        x-text="Math.round(analysis?.confidence * 100) + '%'"></span>
                </div>

                <template x-if="analysis?.meta?.forecast_signal">
                    <div class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1">
                        <span class="text-[10px] font-black uppercase tracking-tighter text-white"
                            :class="{
                                'text-green-400': analysis.meta.forecast_signal === 'BUY' || analysis.meta
                                    .forecast_signal === 'SELL',
                                'text-yellow-400': analysis.meta.forecast_signal === 'WAIT'
                            }"
                            x-text="'FORECAST: ' + analysis.meta.forecast_signal"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- Portal Numaraları & Gizli Not --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Gizli Not --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-900/10">
            <label for="gizli_not"
                class="mb-4 flex items-center gap-2 text-sm font-bold text-amber-800 dark:text-amber-200">
                <i class="fas fa-lock"></i> Gizli Not (Ekibe Özel)
            </label>
            <textarea name="gizli_not" id="gizli_not" rows="4"
                placeholder="Pazarlık payı, acil satış nedeni vb. sadece ekip görebilir..." class="wizard-field"></textarea>
        </div>

        {{-- Portallar --}}
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-6 dark:border-purple-800 dark:bg-purple-900/10">
            <label class="mb-4 flex items-center gap-2 text-sm font-bold text-purple-800 dark:text-purple-200">
                <i class="fas fa-external-link-alt"></i> Portal İlan Numaraları
            </label>
            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="w-20 text-xs font-bold text-gray-500 dark:text-gray-400">Sahibinden:</span>
                    <input type="text" name="sahibinden_id" class="wizard-field !py-1.5 !text-xs">
                </div>
                <div class="flex items-center gap-3">
                    <span class="w-20 text-xs font-bold text-gray-500 dark:text-gray-400">Emlakjet:</span>
                    <input type="text" name="emlakjet_id" class="wizard-field !py-1.5 !text-xs">
                </div>
            </div>
        </div>
    </div>

    {{-- Yayın Durumu Seçimi --}}
    <div class="rounded-2xl border-2 border-blue-500 bg-white p-8 shadow-xl dark:border-blue-400 dark:bg-slate-900">
        <div class="flex flex-col items-center justify-between gap-8 md:flex-row">
            <div class="flex-1">
                <h4 class="mb-2 text-xl font-black text-gray-900 dark:text-slate-100">🚀 Yayına Hazır
                    mısınız?</h4>
                <p class="text-sm text-gray-500 dark:text-gray-400">İlanınızı hemen yayına alabilir veya taslak olarak
                    kaydederek daha sonra tamamlayabilirsiniz.</p>
            </div>

            <div class="flex w-full rounded-2xl bg-gray-100 p-2 dark:bg-slate-900 md:w-auto">
                <label class="group flex-1 cursor-pointer md:w-32">
                    <input type="radio" name="yayin_durumu" value="yayinda" class="peer hidden" checked>
                    <div
                        class="rounded-xl px-6 py-3 text-center text-sm font-bold text-gray-500 transition-all hover:bg-gray-200 peer-checked:bg-green-600 peer-checked:text-white dark:hover:bg-gray-700">
                        Hemen Yayınla
                    </div>
                </label>
                <label class="group flex-1 cursor-pointer md:w-32">
                    <input type="radio" name="yayin_durumu" value="taslak" class="peer hidden">
                    <div
                        class="rounded-xl px-6 py-3 text-center text-sm font-bold text-gray-500 transition-all hover:bg-gray-200 peer-checked:bg-blue-600 peer-checked:text-white dark:text-gray-400 dark:hover:bg-gray-700">
                        Taslak Kaydet
                    </div>
                </label>
            </div>
        </div>
    </div>

</div>
