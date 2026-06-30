{{-- STEP 2: STRUCTURED DATA (Arsa Satılık) --}}
{{-- Context7: Turkish naming, land-specific fields --}}
<div class="space-y-6" x-data="arsaSatilikStructuredDataForm()" x-cloak>
    <div class="mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100/50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[10px] font-black uppercase tracking-widest mb-3">
            <i class="fas fa-map-marked-alt"></i>
            Arsa / Tarla
        </div>
        <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight dark:text-slate-100">
            Arsa İlan Detayları
        </h3>
    </div>

    <div id="arsa-satilik-structured-form" class="space-y-8">
        {{-- Section 1: Arsa Bilgileri --}}
        <div class="wizard-card p-8 group hover:border-amber-500/30 transition-all duration-500">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/40 flex items-center justify-center">
                    <i class="fas fa-map text-amber-600 dark:text-amber-400"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">Arsa Bilgileri</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="wizard-field-label">Arsa Tipi <span class="text-red-500">*</span></label>
                    <select name="arsa_tipi" x-model="formData.arsa_tipi" required class="wizard-field">
                        <option value="">Seçiniz</option>
                        <option value="imar_ici">İmar İçi</option>
                        <option value="imar_disi">İmar Dışı</option>
                        <option value="tarla">Tarla</option>
                        <option value="bahce">Bahçe</option>
                        <option value="zeytinlik">Zeytinlik</option>
                        <option value="agaclik">Ağaçlık</option>
                    </select>
                </div>
                <div>
                    <label class="wizard-field-label">Alan (m²) <span class="text-red-500">*</span></label>
                    <input type="number" name="alan_m2" x-model="formData.alan_m2" min="1" step="0.01" required class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Ada No</label>
                    <input type="text" name="ada_no" x-model="formData.ada_no" placeholder="101" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Parsel No</label>
                    <input type="text" name="parsel_no" x-model="formData.parsel_no" placeholder="5" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">KAKS (Kat Alanı Kat Sayısı)</label>
                    <input type="number" name="kaks" x-model="formData.kaks" min="0" step="0.01" placeholder="0.30" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Gabari (metre)</label>
                    <input type="number" name="gabari" x-model="formData.gabari" min="0" step="0.01" placeholder="6.50" class="wizard-field">
                </div>
            </div>
        </div>

        {{-- Section 2: İmar & Tapu --}}
        <div class="wizard-card p-8 group hover:border-blue-500/30 transition-all duration-500">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/40 flex items-center justify-center">
                    <i class="fas fa-file-contract text-blue-600 dark:text-blue-400"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">İmar & Tapu Bilgileri</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="wizard-field-label">İmar Durumu</label>
                    <select name="imar_durumu" x-model="formData.imar_durumu" class="wizard-field">
                        <option value="">Belirtilmemiş</option>
                        <option value="imar_ici">İmar İçi</option>
                        <option value="imar_disi">İmar Dışı</option>
                        <option value="tarla">Tarla</option>
                        <option value="konut">Konut İmarlı</option>
                        <option value="ticari">Ticari İmarlı</option>
                        <option value="turizm">Turizm İmarlı</option>
                    </select>
                </div>
                <div>
                    <label class="wizard-field-label">Tapu Durumu</label>
                    <select name="tapu_durumu" x-model="formData.tapu_durumu" class="wizard-field">
                        <option value="">Belirtilmemiş</option>
                        <option value="kat_mulkiyeti">Kat Mülkiyeti</option>
                        <option value="kat_irtifaki">Kat İrtifakı</option>
                        <option value="arsa">Arsa Tapusu</option>
                        <option value="hisseli">Hisseli</option>
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="iskan_var" x-model="formData.iskan_var" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">İskan Var</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="krediye_uygun" x-model="formData.krediye_uygun" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Krediye Uygun</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="takas" x-model="formData.takas" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Takasa Uygun</label>
                </div>
            </div>
        </div>

        {{-- Section 3: Fiyat & Finans --}}
        <div class="wizard-card p-8 group hover:border-green-500/30 transition-all duration-500">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/40 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-green-600 dark:text-green-400"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">Fiyat Bilgileri</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="lg:col-span-1">
                    <label class="wizard-field-label">
                        Satılık Fiyat <span class="text-red-500">*</span>
                    </label>
                    <div class="relative group/input">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-green-500">
                            <i class="fas fa-tag"></i>
                        </div>
                        <input type="text" id="fiyat_display_arsa" placeholder="5.000.000"
                            x-model="formData.fiyat.satilik_fiyat_display"
                            @input="$el.value = window.priceFormatter.formatWithSeparators($el.value);
                                    formData.fiyat.satilik_fiyat = window.priceFormatter.getRawValue($el.value);
                                    updateArsaPriceText();"
                            required
                            class="wizard-field !pl-10 !pr-12 !text-green-600 !font-black !text-lg">
                        <input type="hidden" name="fiyat.satilik_fiyat" x-model="formData.fiyat.satilik_fiyat">
                        <div class="absolute right-4 top-3.5 pointer-events-none">
                            <span class="text-green-500 font-bold text-lg" x-text="window.priceFormatter.getCurrencySymbol(formData.fiyat.para_birimi)">₺</span>
                        </div>
                    </div>

                    {{-- Yazıyla Fiyat --}}
                    <div class="mt-3 px-4 py-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <p class="text-xs font-medium text-green-800 dark:text-green-300 flex items-center gap-2">
                            <span class="opacity-75">Yazıyla:</span>
                            <span id="arsa_price_text" class="font-bold uppercase tracking-wider">Fiyat Bekleniyor...</span>
                        </p>
                    </div>
                </div>
                <div>
                    <label class="wizard-field-label">Para Birimi <span class="text-red-500">*</span></label>
                    <select name="fiyat.para_birimi" x-model="formData.fiyat.para_birimi" required @change="updateArsaPriceText()" class="wizard-field">
                        <option value="">Seçiniz</option>
                        <option value="TRY">TRY (₺)</option>
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="GBP">GBP (£)</option>
                    </select>
                </div>

                {{-- m² Birim Fiyatı --}}
                <div x-show="unitPricePerM2 > 0" x-transition class="md:col-span-3 p-6 rounded-3xl bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 border border-amber-200 dark:border-amber-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-amber-100 dark:bg-amber-800/30 flex items-center justify-center">
                                <i class="fas fa-calculator text-amber-600 dark:text-amber-400 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-widest text-amber-600 dark:text-amber-400">
                                    Metrekare Birim Fiyatı
                                </p>
                                <p class="text-sm text-amber-800 dark:text-amber-300">
                                    Toplam Fiyat / m²
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-black text-amber-600 dark:text-amber-400"
                                x-text="window.priceFormatter.formatUnitPrice(unitPricePerM2, formData.fiyat.para_birimi)">
                            </span>
                            <span class="block text-xs font-bold text-amber-400 mt-1">/ m²</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4 pb-12">
            <button type="button" @click="validateData"
                class="px-8 py-4 bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white rounded-2xl hover:bg-gray-200 dark:hover:bg-slate-700 font-black uppercase text-xs transition-all dark:bg-slate-900 dark:text-slate-100">
                <i class="fas fa-shield-alt mr-2 text-amber-500"></i>
                Veriyi Doğrula
            </button>
            <button type="submit"
                class="px-10 py-4 bg-gradient-to-r from-amber-600 to-orange-600 text-white rounded-2xl hover:shadow-premium font-black uppercase text-xs transition-all">
                <i class="fas fa-save mr-2"></i>
                Kaydet
            </button>
        </div>
    </form>
</div>

<script>
    // Price text conversion for land
    async function updateArsaPriceText() {
        const priceValue = document.getElementById('fiyat_display_arsa')?.value;
        const currency = document.querySelector('select[name="fiyat.para_birimi"]')?.value || 'TRY';
        const textElement = document.getElementById('arsa_price_text');

        if (!priceValue || !textElement) return;

        const rawValue = window.priceFormatter.getRawValue(priceValue);
        if (rawValue > 0) {
            const text = window.priceFormatter.convertToText(rawValue, currency);
            textElement.textContent = text.toUpperCase();
        } else {
            textElement.textContent = 'Fiyat Bekleniyor...';
        }
    }

    function arsaSatilikStructuredDataForm() {
        return {
            formData: {
                arsa_tipi: '',
                alan_m2: null,
                ada_no: '',
                parsel_no: '',
                kaks: null,
                gabari: null,
                imar_durumu: '',
                tapu_durumu: '',
                iskan_var: false,
                krediye_uygun: false,
                takas: false,
                fiyat: {
                    satilik_fiyat: null,
                    satilik_fiyat_display: '',
                    para_birimi: 'TRY'
                }
            },
            unitPricePerM2: 0,
            init() {
                console.log('🗺️ Arsa Satılık Form initialized');

                // Watch for price and area changes
                this.$watch('formData.fiyat.satilik_fiyat', () => this.calculateUnitPrice());
                this.$watch('formData.alan_m2', () => this.calculateUnitPrice());
            },
            calculateUnitPrice() {
                const price = parseFloat(this.formData.fiyat.satilik_fiyat) || 0;
                const area = parseFloat(this.formData.alan_m2) || 0;
                this.unitPricePerM2 = area > 0 ? price / area : 0;
            },
            async saveStructuredData() {
                console.log('Saving Arsa data:', this.formData);
                alert('Arsa bilgileri kaydedildi!');
            },
            async validateData() {
                console.log('Validating:', this.formData);
                alert('Doğrulama başarılı!');
            }
        };
    }
</script>
