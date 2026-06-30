{{-- 🏢 Cockpit Social/CRM --}}
<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

    {{-- Owner & Contacts --}}
    <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden shadow-sm dark:shadow-none dark:border-slate-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">İlan Sahibi</h3>
        </div>

        <div class="p-6 space-y-8">
            {{-- Owner Section --}}
            <div class="flex items-start gap-6">
                <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-full border border-blue-200 dark:border-blue-800 flex items-center justify-center text-2xl font-bold text-blue-600 dark:text-blue-400 shrink-0">
                    {{ mb_substr($ilan->ilanSahibi->ad ?? '?', 0, 1) }}{{ mb_substr($ilan->ilanSahibi->soyad ?? '?', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Mal Sahibi</span>
                        <div class="h-px bg-gray-200 dark:bg-gray-700 flex-1"></div>
                    </div>
                    <h4 class="text-xl font-bold text-gray-900 dark:text-white mt-1 dark:text-slate-100">{{ $ilan->ilanSahibi->ad ?? 'Belirsiz' }} {{ $ilan->ilanSahibi->soyad ?? '' }}</h4>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="tel:{{ $ilan->ilanSahibi->telefon }}" class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-slate-900 hover:bg-gray-200 dark:hover:bg-gray-700 text-xs font-semibold text-gray-900 dark:text-white rounded-lg border border-gray-200 dark:border-slate-800 transition-all dark:border-slate-700 dark:text-slate-100">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                            Telefon
                        </a>
                        @if($ilan->ilanSahibi->eposta)
                            <a href="mailto:{{ $ilan->ilanSahibi->eposta }}" class="flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-slate-900 hover:bg-gray-200 dark:hover:bg-gray-700 text-xs font-semibold text-gray-900 dark:text-white rounded-lg border border-gray-200 dark:border-slate-800 transition-all dark:border-slate-700 dark:text-slate-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                                E-posta
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Context Info --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400 block mb-2">Konum</span>
                    <p class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                        {{ optional($ilan->il)->il_adi }}, {{ optional($ilan->ilce)->ilce_adi }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ optional($ilan->mahalle)->mahalle_adi }}</p>
                </div>

                <div class="p-4 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400 block mb-2">Kategori</span>
                    <div class="flex flex-wrap gap-2">
                        @if($ilan->ana_kategori_id) <span class="px-2 py-0.5 bg-gray-200 dark:bg-gray-700 text-xs font-semibold text-gray-900 dark:text-white rounded dark:text-slate-100">{{ $ilan->kategori->name ?? 'Belirsiz' }}</span> @endif
                        @if($ilan->junction_id) <span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-semibold border border-blue-200 dark:border-blue-800 rounded">Aktif</span> @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Site/Apartman --}}
    @if(view()->exists('admin.ilanlar.components.site-apartman-context7'))
        <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden shadow-sm dark:shadow-none dark:border-slate-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Site Bilgileri</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @if($ilan->site_id)
                        <div class="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <div>
                                <span class="text-xs font-medium text-green-600 dark:text-green-400">Aktif Site</span>
                                <h5 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $ilan->site->ad ?? 'Belirsiz Site' }}</h5>
                            </div>
                            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full text-green-600 dark:text-green-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                            </div>
                        </div>
                    @else
                        <div class="py-12 text-center text-gray-500 dark:text-gray-400">
                            <span class="text-sm font-medium">Site bilgisi yok</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
