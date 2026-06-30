{{-- Arsa Kategorisi Özel Alanlar --}}
<div x-show="selectedKategoriSlug && (selectedKategoriSlug.includes('arsa') || selectedKategoriSlug.includes('land'))"
    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95"
    class="space-y-4 mb-4">

    {{-- Arsa Kategorisi Bilgilendirme --}}
    <div class="p-4 bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-800 rounded-lg">
        <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-semibold text-orange-900 dark:text-orange-100">Arsa Kategorisi Seçildi</span>
        </div>
        <p class="text-sm text-orange-700 dark:text-orange-300">
            Arsa kategorisine özel alanlar (ada_no, parsel_no, imar_statusu, kaks, taks, gabari) aktif edildi.
        </p>
    </div>

    {{-- Cortex İmar & İnşaat Analizi --}}
    <div x-data="{
        analyzing: false,
        aiAnalysisResult: null,
        analyzeConstruction() {
            // FormData'dan gerekli verileri al
            const formData = Alpine.store('formData') || {};
            const features = formData.features || {};

            // Form'dan direkt değerleri al (fallback)
            const adaNo = features.ada_no ||
                document.querySelector('[name*="ada_no"]')?.value || '';
            const parselNo = features.parsel_no ||
                document.querySelector('[name*="parsel_no"]')?.value || '';
            const alanM2 = features.alan_m2 ||
                features.metrekare ||
                document.querySelector('[name*="alan_m2"]')?.value ||
                document.querySelector('[name*="metrekare"]')?.value || '';
            const ilce = document.querySelector('[name="ilce_id"]')?.selectedOptions[0]?.text ||
                document.querySelector('[data-selected-ilce]')?.getAttribute('data-selected-ilce') || '';
            const mahalle = document.querySelector('[name="mahalle_id"]')?.selectedOptions[0]?.text ||
                document.querySelector('[data-selected-mahalle]')?.getAttribute('data-selected-mahalle') || null;
            
            // Validasyon
            if (!adaNo || !parselNo || !alanM2 || !ilce) {
                alert('Lütfen Ada No, Parsel No, Alan (m²) ve İlçe bilgilerini giriniz.');
                return;
            }
            
            this.analyzing = true;
            this.aiAnalysisResult = null;
            
            // API çağrısı
            const endpoint = window.APIConfig?.ai?.analyzeConstruction || '/api/ai/analyze-construction';
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    ada_no: adaNo,
                    parsel_no: parselNo,
                    alan_m2: parseFloat(alanM2),
                    ilce: ilce,
                    mahalle: mahalle || null
                })
            })
            .then(response => response.json())
            .then(data => {
                this.analyzing = false;
                if (data.success) {
                    this.aiAnalysisResult = data.data;
                } else {
                    alert(data.message || 'İmar plan analizi başarısız.');
                }
            })
            .catch(error => {
                this.analyzing = false;
                console.error('Cortex Knowledge Service Error:', error);
                alert('Knowledge Base\'e erişilemedi, lütfen manuel kontrol edin.');
            });
        }
        }"
        class="p-6 bg-indigo-50 dark:bg-indigo-900/20 border-2 border-indigo-200 dark:border-indigo-800 rounded-xl shadow-md hover:shadow-lg transition-all duration-200 dark:shadow-none">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div
                    class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-white shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-indigo-900 dark:text-indigo-100 mb-1 flex items-center gap-2">
                    🏗️ Cortex İmar & İnşaat Analizi
                </h3>
                <p class="text-sm text-indigo-700 dark:text-indigo-300 mb-4">
                    Yapay zeka, bölgenin plan notlarını tarayarak inşaat hakkını hesaplar.
                </p>

                <button type="button" @click="analyzeConstruction()" :disabled="analyzing"
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-400 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 disabled:cursor-not-allowed disabled:opacity-75 dark:shadow-none">
                    <svg x-show="!analyzing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    <svg x-show="analyzing" class="w-5 h-5 animate-spin" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="analyzing ? 'Plan notları okunuyor...' : 'Analizi Başlat'"></span>
                </button>

                {{-- Sonuç Alanı --}}
                <div x-show="aiAnalysisResult" x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform translate-y-4"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    class="mt-6 p-4 bg-white dark:bg-slate-900 border border-indigo-200 dark:border-indigo-700 rounded-lg shadow-sm dark:shadow-none">
                    <h4 class="font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2 dark:text-slate-100">
                        <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Analiz Sonuçları
                    </h4>
                    <div class="space-y-3 text-sm">
                        <template x-if="aiAnalysisResult.kaks">
                            <div
                                class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <span class="font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">KAKS (Emsal):</span>
                                <span class="text-gray-900 dark:text-white font-bold dark:text-slate-100"
                                    x-text="aiAnalysisResult.kaks"></span>
                            </div>
                        </template>
                        <template x-if="aiAnalysisResult.taks">
                            <div
                                class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <span class="font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">TAKS:</span>
                                <span class="text-gray-900 dark:text-white font-bold dark:text-slate-100"
                                    x-text="aiAnalysisResult.taks"></span>
                            </div>
                        </template>
                        <template x-if="aiAnalysisResult.gabari">
                            <div
                                class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <span class="font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">Gabari (Yükseklik):</span>
                                <span class="text-gray-900 dark:text-white font-bold dark:text-slate-100"
                                    x-text="aiAnalysisResult.gabari + ' m'"></span>
                            </div>
                        </template>
                        <template x-if="aiAnalysisResult.toplam_insaat_alani">
                            <div
                                class="flex justify-between items-center py-2 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <span class="font-semibold text-gray-700 dark:text-slate-200 dark:text-slate-300">Toplam İnşaat Alanı:</span>
                                <span class="text-gray-900 dark:text-white font-bold dark:text-slate-100"
                                    x-text="aiAnalysisResult.toplam_insaat_alani + ' m²'"></span>
                            </div>
                        </template>
                        <template x-if="aiAnalysisResult.cekme_mesafeleri">
                            <div class="py-2">
                                <span class="font-semibold text-gray-700 dark:text-slate-200 block mb-2 dark:text-slate-300">Çekme
                                    Mesafeleri:</span>
                                <div class="grid grid-cols-3 gap-2 text-xs">
                                    <div class="text-center p-2 bg-gray-100 dark:bg-gray-700 rounded dark:bg-slate-900">
                                        <div class="text-gray-600 dark:text-gray-400">Ön</div>
                                        <div class="font-bold text-gray-900 dark:text-white dark:text-slate-100"
                                            x-text="aiAnalysisResult.cekme_mesafeleri.on + ' m'"></div>
                                    </div>
                                    <div class="text-center p-2 bg-gray-100 dark:bg-gray-700 rounded dark:bg-slate-900">
                                        <div class="text-gray-600 dark:text-gray-400">Arka</div>
                                        <div class="font-bold text-gray-900 dark:text-white dark:text-slate-100"
                                            x-text="aiAnalysisResult.cekme_mesafeleri.arka + ' m'"></div>
                                    </div>
                                    <div class="text-center p-2 bg-gray-100 dark:bg-gray-700 rounded dark:bg-slate-900">
                                        <div class="text-gray-600 dark:text-gray-400">Yan</div>
                                        <div class="font-bold text-gray-900 dark:text-white dark:text-slate-100"
                                            x-text="aiAnalysisResult.cekme_mesafeleri.yan + ' m'"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template x-if="aiAnalysisResult.kaynak">
                            <div class="pt-2 text-xs text-gray-500 dark:text-gray-400">
                                <span class="font-semibold">Kaynak:</span> <span
                                    x-text="aiAnalysisResult.kaynak"></span>
                            </div>
                        </template>
                        <template x-if="aiAnalysisResult.raw_response && !aiAnalysisResult.kaks">
                            <div class="pt-2 p-3 bg-gray-50 dark:bg-slate-900 rounded text-xs text-gray-700 dark:text-slate-200 whitespace-pre-wrap dark:text-slate-300"
                                x-text="aiAnalysisResult.raw_response"></div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
