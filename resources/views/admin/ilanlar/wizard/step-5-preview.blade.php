@php
    use App\Helpers\FormStandards;
@endphp

{{-- STEP 5: ÖNİZLEME VE YAYIN --}}
<div class="space-y-6" x-data="{
    summary: {
        baslik: 'Başlık Belirtilmedi',
        fiyat: '0',
        kategori: 'Kategori Seçilmedi',
        konum: 'Konum Belirtilmedi',
        photoCount: 0,
        ilanSahibi: 'Seçilmedi',
        ilgiliKisi: 'Belirtilmedi',
        danisman: '{{ auth()->user()->name ?? 'Atılay' }}'
    },
    updateSummary() {
        this.summary.baslik = document.getElementById('baslik')?.value?.trim() || 'Başlık Belirtilmedi';
        this.summary.fiyat = document.getElementById('fiyat_display')?.value || document.getElementById('fiyat')?.value || '0';

        const cat = document.getElementById('alt_kategori_id');
        this.summary.kategori = cat?.options[cat.selectedIndex]?.text || 'Kategori Seçilmedi';

        const il = document.getElementById('il_id');
        const ilce = document.getElementById('ilce_id');
        const ilText = il?.options[il.selectedIndex]?.text || '';
        const ilceText = ilce?.options[ilce.selectedIndex]?.text || '';
        this.summary.konum = (ilText && ilceText) ? `${ilText} / ${ilceText}` : (ilText || ilceText || 'Konum Belirtilmedi');

        // CRM & Portföy Alanları
        let ownerVal = document.getElementById('ilan_sahibi_search')?.value?.trim();
        if (!ownerVal && window.context7SelectedOwner) ownerVal = window.context7SelectedOwner;
        this.summary.ilanSahibi = ownerVal || 'Seçilmedi';

        let contactVal = document.getElementById('ilgili_kisi_search')?.value?.trim();
        if (!contactVal && window.context7SelectedContact) contactVal = window.context7SelectedContact;
        this.summary.ilgiliKisi = contactVal || 'Belirtilmedi';

        let advVal = document.getElementById('danisman_search')?.value?.trim();
        if (!advVal && window.context7SelectedAdvisor) advVal = window.context7SelectedAdvisor;
        this.summary.danisman = advVal || '{{ auth()->user()->name ?? 'Atılay' }}';

        // Fotoğraf sayısını senkronize et
        const photoInput = document.getElementById('fotograflar');
        let count = 0;
        if (photoInput && photoInput.files && photoInput.files.length > 0) {
            count = photoInput.files.length;
        } else if (window.__wizardUploadedPhotos && window.__wizardUploadedPhotos.length > 0) {
            count = window.__wizardUploadedPhotos.length;
        } else if (window.wizardService && window.wizardService.photos) {
            count = window.wizardService.photos.length;
        } else if (typeof photos !== 'undefined' && Array.isArray(photos)) {
            count = photos.length;
        }
        this.summary.photoCount = count;
    }
}" x-init="
    window.updateStep5Preview = () => updateSummary();
    updateSummary();
    window.addEventListener('wizard-step-changed', () => updateSummary());
    window.addEventListener('context7:search:selected', () => updateSummary());
    window.addEventListener('context7:selected', () => updateSummary());
    document.addEventListener('input', () => updateSummary());
    document.addEventListener('change', () => updateSummary());
    {{-- P2-FIX: setInterval polling kaldırıldı — event-driven güncelleme yeterli --}}
">

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
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
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

    {{-- 👥 CRM & Sorumlu Özeti --}}
    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 text-orange-600 dark:bg-orange-900/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-slate-100">CRM & Portföy Özeti</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Step 2'de seçilen kişi ve danışman bilgileri</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="p-3 bg-gray-50 dark:bg-slate-800/50 rounded-lg">
                <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">İlan Sahibi</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-slate-100" x-text="summary.ilanSahibi"></span>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-slate-800/50 rounded-lg">
                <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">İlgili Kişi</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-slate-100" x-text="summary.ilgiliKisi"></span>
            </div>
            <div class="p-3 bg-gray-50 dark:bg-slate-800/50 rounded-lg">
                <span class="text-xs text-gray-500 dark:text-gray-400 block font-medium">Sorumlu Danışman</span>
                <span class="text-sm font-semibold text-gray-900 dark:text-slate-100" x-text="summary.danisman"></span>
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
                    <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-black uppercase tracking-tighter text-white">Cortex Price Advisor</h4>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Market Strategy Analysis
                    </p>
                </div>
            </div>
            <div x-show="loading" class="flex items-center gap-2">
                <div class="h-2 w-2 animate-ping rounded-full bg-blue-500"></div>
                <span class="text-[10px] font-black uppercase tracking-widest text-blue-400">Piyasa Analizi
                    Yapılıyor...</span>
            </div>
            <div x-show="!loading && analysis" class="flex items-center gap-2">
                <span
                    class="rounded-full bg-blue-500/10 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-blue-400 border border-blue-500/20">Cortex
                    v2.4 Active</span>
            </div>
        </div>

        <div x-show="!loading && analysis" class="space-y-6 p-6">
            {{-- Metrics Grid --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-2xl border border-slate-800 bg-slate-800/40 p-4">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">AI Hedef
                        Fiyat</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-lg font-black text-white"
                            x-text="analysis ? formatCurrency(analysis.recommended_price) : ''"></span>
                        <span class="text-[10px] font-bold text-slate-500">TL</span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-800/40 p-4">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Piyasa
                        Aralığı</span>
                    <div class="flex items-baseline gap-1">
                        <span class="text-xs font-bold text-slate-300"
                            x-text="analysis ? (formatCurrency(analysis.price_range?.min) + ' - ' + formatCurrency(analysis.price_range?.max)) : ''"></span>
                    </div>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-800/40 p-4">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Fiyat
                        Konumu</span>
                    <span
                        class="inline-block rounded-md px-2 py-0.5 text-[10px] font-black uppercase tracking-wider"
                        :class="{
                            'bg-green-500/20 text-green-400': analysis?.market_position === 'below',
                            'bg-blue-500/20 text-blue-400': analysis?.market_position === 'fair',
                            'bg-red-500/20 text-red-400': analysis?.market_position === 'above'
                        }"
                        x-text="analysis?.market_position === 'fair' ? 'Piyasa Değerinde' : (analysis?.market_position === 'below' ? 'Fırsat Fiyatı' : 'Piyasa Üstü')"></span>
                </div>

                <div class="rounded-2xl border border-slate-800 bg-slate-800/40 p-4">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-500">Tahmini
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
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
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

                <div x-show="analysis?.meta?.forecast_signal" class="rounded-lg border border-slate-700 bg-slate-800 px-3 py-1">
                    <span class="text-[10px] font-black uppercase tracking-tighter text-white"
                        :class="{
                            'text-green-400': analysis?.meta?.forecast_signal === 'BUY' || analysis?.meta
                                ?.forecast_signal === 'SELL',
                            'text-yellow-400': analysis?.meta?.forecast_signal === 'WAIT'
                        }"
                        x-text="'FORECAST: ' + (analysis?.meta?.forecast_signal || '')"></span>
                </div>
            </div>
        </div>
    </div>

    {{-- Portal Numaraları & Gizli Not --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Gizli Not --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-900/10">
            <label for="gizli_not"
                class="mb-4 flex items-center gap-2 text-sm font-bold text-amber-800 dark:text-amber-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                Gizli Not (Ekibe Özel)
            </label>
            <textarea name="gizli_not" id="gizli_not" rows="4"
                placeholder="Pazarlık payı, acil satış nedeni vb. sadece ekip görebilir..." class="wizard-field"></textarea>
        </div>

        {{-- Portallar --}}
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-6 dark:border-purple-800 dark:bg-purple-900/10">
            <label class="mb-4 flex items-center gap-2 text-sm font-bold text-purple-800 dark:text-purple-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                Portal İlan Numaraları
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
