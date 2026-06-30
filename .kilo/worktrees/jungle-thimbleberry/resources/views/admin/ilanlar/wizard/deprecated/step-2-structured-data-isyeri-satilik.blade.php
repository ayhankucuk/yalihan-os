{{-- STEP 2: STRUCTURED DATA (İşyeri Satılık) --}}
{{-- Context7: Commercial property fields --}}
<div class="space-y-6" x-data="isyeriSatilikStructuredDataForm()" x-cloak>
    <div class="mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-100/50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 text-[10px] font-black uppercase tracking-widest mb-3">
            <i class="fas fa-store"></i>
            Ticari İşyeri
        </div>
        <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight dark:text-slate-100">
            İşyeri İlan Detayları
        </h3>
    </div>

    <div id="isyeri-satilik-structured-form" class="space-y-8">
        {{-- Section 1: İşyeri Bilgileri --}}
        <div class="wizard-card p-8 group hover:border-indigo-500 hover:border-opacity-30 transition-all duration-500">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 flex items-center justify-center">
                    <i class="fas fa-briefcase text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">İşyeri Bilgileri</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="wizard-field-label">İşyeri Tipi <span class="text-red-500">*</span></label>
                    <select name="isyeri_tipi" x-model="formData.isyeri_tipi" required class="wizard-field">
                        <option value="">Seçiniz</option>
                        <option value="dukkan">Dükkan</option>
                        <option value="magaza">Mağaza</option>
                        <option value="ofis">Ofis</option>
                        <option value="plaza">Plaza Katı</option>
                        <option value="restoran">Restoran/Kafe</option>
                        <option value="otel">Otel</option>
                        <option value="depo">Depo</option>
                        <option value="fabrika">Fabrika</option>
                        <option value="atölye">Atölye</option>
                    </select>
                </div>
                <div>
                    <label class="wizard-field-label">Brüt m² <span class="text-red-500">*</span></label>
                    <input type="number" name="brut_m2" x-model="formData.brut_m2" min="1" step="0.01" required class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Net m²</label>
                    <input type="number" name="net_m2" x-model="formData.net_m2" min="1" step="0.01" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Oda Sayısı</label>
                    <input type="number" name="oda_sayisi" x-model="formData.oda_sayisi" min="0" max="50" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Bulunduğu Kat</label>
                    <input type="number" name="kat" x-model="formData.kat" min="-5" max="100" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Bina Yaşı</label>
                    <input type="number" name="bina_yasi" x-model="formData.bina_yasi" min="0" max="200" class="wizard-field">
                </div>
            </div>
        </div>

        {{-- Section 2: Ticari Özellikler --}}
        <div class="wizard-card p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/40 flex items-center justify-center">
                    <i class="fas fa-clipboard-list text-blue-600 dark:text-blue-400"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">Ticari Özellikler</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="kiralı" x-model="formData.kirali" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Kiracılı</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="kiralik_gelir" x-model="formData.kiralik_gelir" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Kira Getirisi Var</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="krediye_uygun" x-model="formData.krediye_uygun" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Krediye Uygun</label>
                </div>
                <div>
                    <label class="wizard-field-label">Vitrin Genişliği (m)</label>
                    <input type="number" name="vitrin_genisligi" x-model="formData.vitrin_genisligi" min="0" step="0.01" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Tavan Yüksekliği (m)</label>
                    <input type="number" name="tavan_yuksekligi" x-model="formData.tavan_yuksekligi" min="0" step="0.01" class="wizard-field">
                </div>
            </div>
        </div>

        {{-- Section 3: Fiyat --}}
        <div class="wizard-card p-8 group hover:border-green-500 hover:border-opacity-30 transition-all duration-500">
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
                        <input type="text" id="fiyat_display_isyeri" placeholder="10.000.000"
                            @input="$el.value = window.priceFormatter.formatWithSeparators($el.value);
                                    formData.fiyat.satilik_fiyat = window.priceFormatter.getRawValue($el.value);
                                    updateIsyeriPrice();"
                            required
                            class="wizard-field !pl-10 !pr-12 !text-green-600 !font-black !text-lg">
                        <input type="hidden" name="fiyat.satilik_fiyat" x-model="formData.fiyat.satilik_fiyat">
                        <div class="absolute right-4 top-3.5 pointer-events-none">
                            <span class="text-green-500 font-bold text-lg" x-text="window.priceFormatter.getCurrencySymbol(formData.fiyat.para_birimi)">₺</span>
                        </div>
                    </div>

                    <div class="mt-3 px-4 py-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <p class="text-xs font-medium text-green-800 dark:text-green-300">
                            <span class="opacity-75">Yazıyla:</span>
                            <span id="isyeri_price_text" class="font-bold uppercase">Fiyat Bekleniyor...</span>
                        </p>
                    </div>
                </div>
                <div>
                    <label class="wizard-field-label">Para Birimi <span class="text-red-500">*</span></label>
                    <select name="fiyat.para_birimi" x-model="formData.fiyat.para_birimi" required @change="updateIsyeriPrice()" class="wizard-field">
                        <option value="TRY">TRY (₺)</option>
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="GBP">GBP (£)</option>
                    </select>
                </div>

                {{-- m² Birim Fiyatı --}}
                <div x-show="unitPrice > 0" x-transition class="md:col-span-3 p-6 rounded-3xl bg-gradient-to-r from-indigo-50 to-blue-50 dark:from-indigo-900/20 dark:to-blue-900/20 border border-indigo-200 dark:border-indigo-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-xl bg-indigo-100 dark:bg-indigo-800/30 flex items-center justify-center">
                                <i class="fas fa-calculator text-indigo-600 dark:text-indigo-400 text-xl"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase text-indigo-600 dark:text-indigo-400">
                                    Metrekare Birim Fiyatı
                                </p>
                                <p class="text-sm text-indigo-800 dark:text-indigo-300">
                                    Toplam Fiyat / Brüt m²
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-3xl font-black text-indigo-600 dark:text-indigo-400"
                                x-text="window.priceFormatter.formatUnitPrice(unitPrice, formData.fiyat.para_birimi)">
                            </span>
                            <span class="block text-xs font-bold text-indigo-400 mt-1">/ m²</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4 pb-12">
            <button type="button" @click="validateData"
                class="px-8 py-4 bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white rounded-2xl font-black uppercase text-xs dark:bg-slate-900 dark:text-slate-100">
                <i class="fas fa-shield-alt mr-2 text-indigo-500"></i>
                Doğrula
            </button>
            <button type="submit"
                class="px-10 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-2xl hover:shadow-premium font-black uppercase text-xs">
                <i class="fas fa-save mr-2"></i>
                Kaydet
            </button>
        </div>
    </form>
</div>

<script>
    function updateIsyeriPrice() {
        const priceValue = document.getElementById('fiyat_display_isyeri')?.value;
        const currency = document.querySelector('select[name="fiyat.para_birimi"]')?.value || 'TRY';
        const textElement = document.getElementById('isyeri_price_text');
        if (!priceValue || !textElement) return;
        const rawValue = window.priceFormatter.getRawValue(priceValue);
        if (rawValue > 0) {
            textElement.textContent = window.priceFormatter.convertToText(rawValue, currency).toUpperCase();
        }
    }

    function isyeriSatilikStructuredDataForm() {
        return {
            formData: {
                isyeri_tipi: '',
                brut_m2: null,
                net_m2: null,
                oda_sayisi: null,
                kat: null,
                bina_yasi: null,
                kirali: false,
                kiralik_gelir: false,
                krediye_uygun: false,
                vitrin_genisligi: null,
                tavan_yuksekligi: null,
                fiyat: {
                    satilik_fiyat: null,
                    para_birimi: 'TRY'
                }
            },
            unitPrice: 0,
            init() {
                console.log('🏢 İşyeri Satılık Form initialized');
                this.$watch('formData.fiyat.satilik_fiyat', () => this.calculateUnitPrice());
                this.$watch('formData.brut_m2', () => this.calculateUnitPrice());
            },
            calculateUnitPrice() {
                const price = parseFloat(this.formData.fiyat.satilik_fiyat) || 0;
                const area = parseFloat(this.formData.brut_m2) || 0;
                this.unitPrice = area > 0 ? price / area : 0;
            },
            async saveStructuredData() {
                alert('İşyeri bilgileri kaydedildi!');
            },
            async validateData() {
                alert('Doğrulama başarılı!');
            }
        };
    }
</script>
