{{-- Konut Kategorisi Özel Alanlar --}}
<div x-show="selectedKategoriSlug && (selectedKategoriSlug.includes('konut') || selectedKategoriSlug.includes('daire') || selectedKategoriSlug.includes('villa') || selectedKategoriSlug.includes('residence'))"
    x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 transform scale-95"
    x-transition:enter-end="opacity-100 transform scale-100" x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 transform scale-100" x-transition:leave-end="opacity-0 transform scale-95"
    class="space-y-4 mb-4">

    {{-- Konut Kategorisi Bilgilendirme --}}
    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
        <div class="flex items-center gap-2 mb-2">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
            <span class="font-semibold text-blue-900 dark:text-blue-100">Konut Kategorisi Seçildi</span>
        </div>
        <p class="text-sm text-blue-700 dark:text-blue-300">
            Konut kategorisine özel alanlar (oda_sayisi, banyo_sayisi, balkon, asansor, vb.) aktif edildi.
        </p>
    </div>

    {{-- Konut Akıllı Validasyon ve Metrikler --}}
    <div x-data="{
        netM2: null,
        brutM2: null,
        satisFiyati: null,
        m2BirimFiyat: null,
        validationError: null,

        init() {
            // Input değişikliklerini dinle
            this.$watch('netM2', () => this.validateM2());
            this.$watch('brutM2', () => this.validateM2());
            this.$watch('satisFiyati', () => this.calculateM2Price());
            this.$watch('brutM2', () => this.calculateM2Price());

            // Form input'larını dinle
            setTimeout(() => {
                const netInput = document.getElementById('field_net_metrekare') ||
                    document.getElementById('field_net-metrekare') ||
                    document.querySelector('[name*="net_metrekare"]') ||
                    document.querySelector('[name*="net-metrekare"]');
                const brutInput = document.getElementById('field_brut_metrekare') ||
                    document.getElementById('field_brut-metrekare') ||
                    document.querySelector('[name*="brut_metrekare"]') ||
                    document.querySelector('[name*="brut-metrekare"]');
                const fiyatInput = document.querySelector('[name="satis_fiyati"]') ||
                    document.querySelector('[name="fiyat"]');
                
                if (netInput) {
                    netInput.addEventListener('input', () => {
                        this.netM2 = parseFloat(netInput.value) || null;
                    });
                }
                if (brutInput) {
                    brutInput.addEventListener('input', () => {
                        this.brutM2 = parseFloat(brutInput.value) || null;
                    });
                }
                if (fiyatInput) {
                    fiyatInput.addEventListener('input', () => {
                        this.satisFiyati = parseFloat(fiyatInput.value) || null;
                    });
                }
            }, 1000);
        },

        validateM2() {
        if (this.netM2 && this.brutM2 && this.netM2 > this.brutM2) {
        this.validationError = 'Net metrekare, Brüt metrekareden büyük olamaz!';
        return false;
        }
        this.validationError = null;
        return true;
        },

        calculateM2Price() {
        if (this.satisFiyati && this.brutM2 && this.brutM2 > 0) {
        this.m2BirimFiyat = Math.round(this.satisFiyati / this.brutM2);
        } else {
        this.m2BirimFiyat = null;
        }
        }
        }"
        class="p-6 bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 border-2 border-blue-200 dark:border-blue-800 rounded-xl shadow-md dark:shadow-none">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                <div
                    class="flex items-center justify-center w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 text-white shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-bold text-blue-900 dark:text-blue-100 mb-1 flex items-center gap-2">
                    🏠 Konut Akıllı Validasyon
                </h3>
                <p class="text-sm text-blue-700 dark:text-blue-300 mb-4">
                    Net/Brüt m² tutarlılığı ve birim fiyat hesaplaması otomatik kontrol edilir.
                </p>

                {{-- Canlı Validasyon Uyarısı --}}
                <div x-show="validationError" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border-2 border-red-300 dark:border-red-700 rounded-lg">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm font-semibold text-red-800 dark:text-red-200"
                            x-text="validationError"></span>
                    </div>
                </div>

                {{-- Birim Fiyat Badge --}}
                <div x-show="m2BirimFiyat" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 dark:bg-slate-900 border border-gray-300 dark:border-slate-800 rounded-lg shadow-sm dark:shadow-none">
                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Birim:</span>
                    <span class="text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100"
                        x-text="m2BirimFiyat ? m2BirimFiyat.toLocaleString('tr-TR') + ' TL/m²' : ''"></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Net/Brüt m² canlı validasyon (Global)
    (function() {
        function setupKonutValidation() {
            const netInput = document.getElementById('field_net_metrekare') ||
                document.getElementById('field_net-metrekare') ||
                document.querySelector('[name*=\"net_metrekare\"]') ||
                document.querySelector('[name*=\"net-metrekare\"]');
            const brutInput = document.getElementById('field_brut_metrekare') ||
                document.getElementById('field_brut-metrekare') ||
                document.querySelector('[name*=\"brut_metrekare\"]') ||
                document.querySelector('[name*=\"brut-metrekare\"]');

            if (!netInput || !brutInput) return;

            function validate() {
                const net = parseFloat(netInput.value) || 0;
                const brut = parseFloat(brutInput.value) || 0;

                if (net > 0 && brut > 0 && net > brut) {
                    // Hata statusu: Kırmızı çerçeve
                    netInput.classList.add('border-red-500', 'ring-2', 'ring-red-300');
                    brutInput.classList.add('border-red-500', 'ring-2', 'ring-red-300');

                    // Hata mesajı göster
                    let errorMsg = netInput.parentElement.querySelector('.m2-error-message');
                    if (!errorMsg) {
                        errorMsg = document.createElement('p');
                        errorMsg.className = 'm2-error-message text-xs text-red-600 dark:text-red-400 mt-1';
                        netInput.parentElement.appendChild(errorMsg);
                    }
                    errorMsg.textContent = 'Net metrekare, Brüt metrekareden büyük olamaz!';
                } else {
                    // Normal status
                    netInput.classList.remove('border-red-500', 'ring-2', 'ring-red-300');
                    brutInput.classList.remove('border-red-500', 'ring-2', 'ring-red-300');

                    const errorMsg = netInput.parentElement.querySelector('.m2-error-message');
                    if (errorMsg) errorMsg.remove();
                }
            }

            netInput.addEventListener('input', validate);
            brutInput.addEventListener('input', validate);
        }

        // Sayfa yüklendiğinde ve dinamik alanlar oluşturulduğunda çalıştır
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', setupKonutValidation);
        } else {
            setupKonutValidation();
        }

        // Dinamik alanlar oluşturulduğunda tekrar çalıştır
        window.addEventListener('fields-rendered', setupKonutValidation);
    })();
</script>
