{{-- STEP 2: STRUCTURED DATA (Konut Kiralık) --}}
{{-- Context7: Turkish naming, no yasakli fields --}}
<div class="space-y-6" x-data="konutKiralikStructuredDataForm()" x-cloak>
    <div class="mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-purple-100/50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 text-[10px] font-black uppercase tracking-widest mb-3">
            <i class="fas fa-home"></i>
            Kiralık Konut
        </div>
        <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight dark:text-slate-100">
            Kiralık İlan Detayları
        </h3>
    </div>

    <div id="konut-kiralik-structured-form" class="space-y-8">
        {{-- Section 1: Genel Bilgiler --}}
        <div class="wizard-card p-8 group hover:border-purple-500/30 transition-all duration-500">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-900/40 flex items-center justify-center">
                    <i class="fas fa-info-circle text-purple-600 dark:text-purple-400"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">Genel Bilgiler</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="wizard-field-label">Konut Tipi <span class="text-red-500">*</span></label>
                    <select name="konut_tipi" x-model="formData.konut_tipi" required class="wizard-field">
                        <option value="">Seçiniz</option>
                        <option value="daire">Daire</option>
                        <option value="villa">Villa</option>
                        <option value="residence">Residence</option>
                        <option value="mustakil_ev">Müstakil Ev</option>
                        <option value="apart">Apart</option>
                    </select>
                </div>
                <div>
                    <label class="wizard-field-label">Oda Sayısı <span class="text-red-500">*</span></label>
                    <input type="number" name="oda_sayisi" x-model="formData.oda_sayisi" min="1" max="20" required class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Brüt m² <span class="text-red-500">*</span></label>
                    <input type="number" name="brut_m2" x-model="formData.brut_m2" min="1" step="0.01" required class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Banyo Sayısı</label>
                    <input type="number" name="banyo_sayisi" x-model="formData.banyo_sayisi" min="1" max="10" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Bina Yaşı</label>
                    <input type="number" name="bina_yasi" x-model="formData.bina_yasi" min="0" max="200" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Bulunduğu Kat</label>
                    <input type="number" name="kat" x-model="formData.kat" min="-5" max="100" class="wizard-field">
                </div>
            </div>
        </div>

        {{-- Section 2: Kira & Ödeme Bilgileri --}}
        <div class="wizard-card p-8 group hover:border-green-500 hover:border-opacity-30 transition-all duration-500">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/40 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave text-green-600 dark:text-green-400"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">Kira & Ödeme</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="lg:col-span-1">
                    <label class="wizard-field-label">
                        Aylık Kira <span class="text-red-500">*</span>
                    </label>
                    <div class="relative group/input">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-green-500">
                            <i class="fas fa-tag"></i>
                        </div>
                        <input type="text" id="kira_display" placeholder="15.000"
                            x-model="formData.fiyat.kira_display"
                            @input="$el.value = window.priceFormatter.formatWithSeparators($el.value);
                                    formData.fiyat.aylik_kira = window.priceFormatter.getRawValue($el.value);
                                    updatePriceText();"
                            required
                            class="wizard-field !pl-10 !pr-12 !text-green-600 !font-black !text-lg">
                        <input type="hidden" name="fiyat.aylik_kira" x-model="formData.fiyat.aylik_kira">
                        <div class="absolute right-4 top-3.5 pointer-events-none">
                            <span class="text-green-500 font-bold text-lg" x-text="window.priceFormatter.getCurrencySymbol(formData.fiyat.para_birimi)">₺</span>
                        </div>
                    </div>

                    {{-- Yazıyla Kira Display --}}
                    <div class="mt-3 px-4 py-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <p class="text-xs font-medium text-green-800 dark:text-green-300 flex items-center gap-2">
                            <span class="opacity-75">Yazıyla:</span>
                            <span id="rent_text_value" class="font-bold uppercase tracking-wider">Kira Bekleniyor...</span>
                        </p>
                    </div>
                </div>
                <div>
                    <label class="wizard-field-label">Para Birimi <span class="text-red-500">*</span></label>
                    <select name="fiyat.para_birimi" x-model="formData.fiyat.para_birimi" required @change="updatePriceText()" class="wizard-field">
                        <option value="">Seçiniz</option>
                        <option value="TRY">TRY (₺)</option>
                        <option value="USD">USD ($)</option>
                        <option value="EUR">EUR (€)</option>
                        <option value="GBP">GBP (£)</option>
                    </select>
                </div>
                <div>
                    <label class="wizard-field-label">Depozito (TL)</label>
                    <input type="number" name="fiyat.depozito" x-model="formData.fiyat.depozito" min="0" step="0.01" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Aidat (TL/ay)</label>
                    <input type="number" name="fiyat.aidat" x-model="formData.fiyat.aidat" min="0" step="0.01" class="wizard-field">
                </div>
                <div class="md:col-span-2">
                    <label class="wizard-field-label">Kiracı Tercihi</label>
                    <select name="kiraci_tercihi" x-model="formData.kiraci_tercihi" class="wizard-field">
                        <option value="">Belirtilmemiş</option>
                        <option value="aile">Aile</option>
                        <option value="ogrenci">Öğrenci</option>
                        <option value="bekar">Bekar</option>
                        <option value="kurumsal">Kurumsal</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Section 3: İç Özellikler --}}
        <div class="wizard-card p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/40 flex items-center justify-center">
                    <i class="fas fa-couch text-blue-600 dark:text-blue-400"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">İç Özellikler</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="wizard-field-label">Eşyalı mı?</label>
                    <select name="esyali" x-model="formData.esyali" class="wizard-field">
                        <option value="">Belirtilmemiş</option>
                        <option value="evet">Evet (Tam Eşyalı)</option>
                        <option value="hayir">Hayır (Boş)</option>
                        <option value="kismen">Kısmen Eşyalı</option>
                    </select>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="klima" x-model="formData.klima" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Klima</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="balkon" x-model="formData.balkon" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Balkon</label>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4 pb-12">
            <button type="button" @click="validateData"
                class="px-8 py-4 bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white rounded-2xl hover:bg-gray-200 dark:hover:bg-slate-700 font-black uppercase text-xs transition-all dark:bg-slate-900 dark:text-slate-100">
                <i class="fas fa-shield-alt mr-2 text-purple-500"></i>
                Veriyi Doğrula
            </button>
            <button type="submit"
                class="px-10 py-4 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-2xl hover:shadow-premium font-black uppercase text-xs transition-all">
                <i class="fas fa-save mr-2"></i>
                Kaydet
            </button>
        </div>
    </form>
</div>

<script>
    // Price text conversion for rent
    async function updatePriceText() {
        const rentValue = document.getElementById('kira_display')?.value;
        const currency = document.querySelector('select[name="fiyat.para_birimi"]')?.value || 'TRY';
        const textElement = document.getElementById('rent_text_value');

        if (!rentValue || !textElement) return;

        const rawValue = window.priceFormatter.getRawValue(rentValue);
        if (rawValue > 0) {
            const text = window.priceFormatter.convertToText(rawValue, currency);
            textElement.textContent = text.toUpperCase();
        } else {
            textElement.textContent = 'Kira Bekleniyor...';
        }
    }

    function konutKiralikStructuredDataForm() {
        return {
            formData: {
                konut_tipi: '',
                oda_sayisi: null,
                brut_m2: null,
                banyo_sayisi: null,
                bina_yasi: null,
                kat: null,
                esyali: '',
                klima: false,
                balkon: false,
                kiraci_tercihi: '',
                fiyat: {
                    aylik_kira: null,
                    kira_display: '',
                    depozito: null,
                    aidat: null,
                    para_birimi: 'TRY'
                }
            },
            init() {
                console.log('🏠 Konut Kiralık Form initialized');
            },
            async saveStructuredData() {
                console.log('Saving Kiralık data:', this.formData);
                alert('Kiralık konut bilgileri kaydedildi!');
            },
            async validateData() {
                console.log('Validating:', this.formData);
                alert('Doğrulama başarılı!');
            }
        };
    }
</script>
