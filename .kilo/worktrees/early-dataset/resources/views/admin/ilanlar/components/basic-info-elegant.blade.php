{{--
🎨 TEMEL BİLGİLER - Ultra Modern Edition
Context7: %100, Tailwind CSS ONLY
--}}

<x-admin.ilanlar.components.elegant-form-wrapper sectionId="section-basic-info" title="Temel Bilgiler"
    subtitle="İlanınızın başlık ve açıklamasını girin" badgeNumber="4" badgeColor="blue" :icon="'<svg class=\'w-6 h-6\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'>
                  <path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\'
                        d=\'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z\' />
                </svg>'"
    glassEffect="false">

    <div class="space-y-6">
        {{-- İlan Başlığı --}}
        <x-admin.ilanlar.components.elegant-input name="baslik" type="text" label="İlan Başlığı"
            placeholder="Örn: Bodrum Yalıkavak'ta Deniz Manzaralı Satılık Villa" :value="old('baslik', $ilan->baslik ?? '')" :required="true"
            :maxLength="255" :floating="true" :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                                  <path fill-rule=\'evenodd\' d=\'M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z\' clip-rule=\'evenodd\' />
                                </svg>'"
            helpText="Müşterilerin dikkatini çekecek, açıklayıcı bir başlık yazın" />

        {{-- İlan Açıklaması --}}
        <x-admin.ilanlar.components.elegant-input name="aciklama" type="textarea" label="İlan Açıklaması"
            placeholder="İlanınız hakkında detaylı bilgi verin..." :value="old('aciklama', $ilan->aciklama ?? '')" :required="false" rows="6"
            :floating="true" :icon="'<svg class=\'w-5 h-5\' fill=\'currentColor\' viewBox=\'0 0 20 20\'>
                                  <path fill-rule=\'evenodd\' d=\'M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h6a1 1 0 110 2H4a1 1 0 01-1-1z\' clip-rule=\'evenodd\' />
                                </svg>'" helpText="Özellikler, konum, ulaşım gibi detayları ekleyin" />

        {{-- AI Yardımcısı --}}
        <div
            class="mt-8 p-6 rounded-xl
                    bg-gradient-to-br from-purple-50 to-pink-50
                    dark:from-purple-900/20 dark:to-pink-900/20
                    border border-purple-200 dark:border-purple-800">
            <div class="flex items-center gap-3 mb-4">
                <div
                    class="flex items-center justify-center w-12 h-12
                            rounded-xl bg-gradient-to-br from-purple-600 to-pink-600
                            text-white shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        AI Dijital Danışman
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Yapay zeka ile otomatik başlık ve açıklama önerileri
                    </p>
                </div>
                <div
                    class="text-xs font-bold px-3 py-1.5 rounded-lg
                            bg-green-100 dark:bg-green-900/30
                            text-green-700 dark:text-green-400">
                    🤖 Aktif
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <button type="button" id="ai-generate-title"
                    class="group inline-flex items-center justify-center gap-2
                               px-5 py-3.5 rounded-xl
                               bg-white dark:bg-gray-800
                               border-2 border-blue-200 dark:border-blue-800
                               text-blue-700 dark:text-blue-300
                               hover:bg-blue-50 dark:hover:bg-blue-900/30
                               hover:border-blue-400 dark:hover:border-blue-600
                               transition-all duration-300
                               hover:scale-105 hover:shadow-lg
                               font-semibold">
                    <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Başlık Öner
                </button>

                <button type="button" id="ai-generate-description"
                    class="group inline-flex items-center justify-center gap-2
                               px-5 py-3.5 rounded-xl
                               bg-white dark:bg-gray-800
                               border-2 border-green-200 dark:border-green-800
                               text-green-700 dark:text-green-300
                               hover:bg-green-50 dark:hover:bg-green-900/30
                               hover:border-green-400 dark:hover:border-green-600
                               transition-all duration-300
                               hover:scale-105 hover:shadow-lg
                               font-semibold">
                    <svg class="w-5 h-5 group-hover:rotate-12 transition-transform duration-300" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Açıklama Öner
                </button>
            </div>
        </div>
    </div>
</x-admin.ilanlar.components.elegant-form-wrapper>

{{-- AI Button Scripts --}}
@push('scripts')
    <script>
        document.getElementById('ai-generate-title')?.addEventListener('click', function() {
            const aciklama = document.getElementById('aciklama')?.value;
            if (!aciklama || aciklama.length < 20) {
                alert('💡 Önce açıklama alanına en az 20 karakter yazın');
                return;
            }
            // TODO: AI başlık önerisi API çağrısı
            console.log('🤖 AI Başlık Önerisi istendi');
            this.innerHTML =
                '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Düşünüyorum...';
        });

        document.getElementById('ai-generate-description')?.addEventListener('click', function() {
            const baslik = document.getElementById('baslik')?.value;
            if (!baslik || baslik.length < 10) {
                alert('💡 Önce başlık alanına en az 10 karakter yazın');
                return;
            }
            // TODO: AI açıklama önerisi API çağrısı
            console.log('🤖 AI Açıklama Önerisi istendi');
            this.innerHTML =
                '<svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Yazıyorum...';
        });
    </script>
@endpush
