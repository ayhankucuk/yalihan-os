@props(['odalar'])

@if ($odalar && $odalar->count() > 0)
    <div class="space-y-4">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                </path>
            </svg>
            Nerede Uyuyacaksınız?
        </h3>

        <div class="space-y-4">
            @foreach ($odalar as $oda)
                <div
                    class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600 hover:shadow-md transition-all duration-200 dark:bg-slate-900 dark:border-slate-700">
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">{{ $oda->oda_adi }}</h4>

                    <div class="space-y-1 mb-3">
                        @php
                            $yataklar = [];
                            if ($oda->tek_kisilik_yatak_sayisi > 0) {
                                $yataklar[] = "{$oda->tek_kisilik_yatak_sayisi} Tek Kişilik Yatak";
                            }
                            if ($oda->cift_kisilik_yatak_sayisi > 0) {
                                $yataklar[] = "{$oda->cift_kisilik_yatak_sayisi} Çift Kişilik Yatak";
                            }
                            if ($oda->kral_yatak_sayisi > 0) {
                                $yataklar[] = "{$oda->kral_yatak_sayisi} Kral Yatak";
                            }
                            if ($oda->cift_katli_yatak_sayisi > 0) {
                                $yataklar[] = "{$oda->cift_katli_yatak_sayisi} Çift Katlı Yatak";
                            }
                            if ($oda->kanepe_yatak_sayisi > 0) {
                                $yataklar[] = "{$oda->kanepe_yatak_sayisi} Kanepe Yatak";
                            }
                        @endphp

                        @foreach ($yataklar as $yatak)
                            <div class="flex items-center gap-2 text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span>{{ $yatak }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if ($oda->toplam_kapasite > 0 || $oda->oda_aciklama)
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-600 space-y-1 dark:border-slate-700">
                            @if ($oda->toplam_kapasite > 0)
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium">Kapasite:</span> {{ $oda->toplam_kapasite }} kişi
                                </div>
                            @endif
                            @if ($oda->oda_aciklama)
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $oda->oda_aciklama }}</p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
