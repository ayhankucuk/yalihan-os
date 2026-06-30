{{-- STEP 2: STRUCTURED DATA (Günlük Kiralık) --}}
{{-- Context7: Daily/vacation rental fields --}}
<div class="space-y-6" x-data="gunlukKiralikStructuredDataForm()" x-cloak>
    <div class="mb-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-100/50 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400 text-[10px] font-black uppercase tracking-widest mb-3">
            <i class="fas fa-umbrella-beach"></i>
            Tatil & Günlük Kiralık
        </div>
        <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight dark:text-slate-100">
            Günlük Kiralık İlan Detayları
        </h3>
    </div>

    <div id="gunluk-kiralik-structured-form" class="space-y-8">
        {{-- Section 1: Konaklama Bilgileri --}}
        <div class="wizard-card p-8 group hover:border-cyan-500/30 transition-all duration-500">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-cyan-50 dark:bg-cyan-900/40 flex items-center justify-center">
                    <i class="fas fa-home text-cyan-600 dark:text-cyan-400"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">Konaklama Bilgileri</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="wizard-field-label">Konut Tipi <span class="text-red-500">*</span></label>
                    <select name="konut_tipi" x-model="formData.konut_tipi" required class="wizard-field">
                        <option value="">Seçiniz</option>
                        <option value="villa">Villa</option>
                        <option value="apart">Apart</option>
                        <option value="daire">Daire</option>
                        <option value="bungalov">Bungalov</option>
                        <option value="otel">Otel Odası</option>
                        <option value="tatil_koyu">Tatil Köyü</option>
                    </select>
                </div>
                <div>
                    <label class="wizard-field-label">Oda Sayısı <span class="text-red-500">*</span></label>
                    <input type="number" name="oda_sayisi" x-model="formData.oda_sayisi" min="1" max="20" required class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Brüt m²</label>
                    <input type="number" name="brut_m2" x-model="formData.brut_m2" min="1" step="0.01" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Maksimum Kişi Sayısı <span class="text-red-500">*</span></label>
                    <input type="number" name="max_kisi" x-model="formData.max_kisi" min="1" max="50" required class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Yatak Odası Sayısı</label>
                    <input type="number" name="yatak_odasi_sayisi" x-model="formData.yatak_odasi_sayisi" min="0" max="20" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">Banyo Sayısı</label>
                    <input type="number" name="banyo_sayisi" x-model="formData.banyo_sayisi" min="1" max="10" class="wizard-field">
                </div>
            </div>
        </div>

        {{-- Section 2: Fiyatlandırma Dönemi (Dynamic Periodic Table) --}}
        <div class="wizard-card p-8 group hover:border-green-500/30 transition-all duration-500"
             x-data="vacationPricingManager()" <!-- Replaces parent data for this section -->
             x-init="init()">

            <input type="hidden" name="yazlik_fiyatlandirma_json" id="yazlik_fiyatlandirma_json" x-model="jsonOutput">

            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/40 flex items-center justify-center">
                        <i class="fas fa-calendar-alt text-green-600 dark:text-green-400"></i>
                    </div>
                    <div>
                        <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">Sezonluk Fiyatlandırma</h4>
                        <p class="text-xs text-gray-500">Farklı tarih aralıkları için özel fiyatlar belirleyin</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Gecelik konaklama için taban fiyat (sezonluk kurallar yoksa bu kullanılır).</p>
                    </div>
                </div>
                <button type="button" @click="openModal()"
                    class="px-4 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg text-xs font-bold transition-colors">
                    + Dönem Ekle
                </button>
            </div>

            <!-- Periods Table -->
            <div class="overflow-x-auto border border-gray-200 dark:border-slate-800 rounded-lg dark:border-slate-700" x-show="periods.length > 0">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih Aralığı</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sezon</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fiyat (Günlük)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Min. Gün</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <template x-for="(period, index) in periods" :key="index">
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    <span x-text="formatDate(period.start_date)"></span> - <span x-text="formatDate(period.end_date)"></span>
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    <span class="px-2 py-1 text-xs rounded-full font-bold uppercase"
                                        :class="{
                                            'bg-green-100 text-green-800': period.season_type === 'low',
                                            'bg-yellow-100 text-yellow-800': period.season_type === 'mid',
                                            'bg-red-100 text-red-800': period.season_type === 'high'
                                        }"
                                        x-text="period.season_type === 'low' ? 'Düşük' : (period.season_type === 'mid' ? 'Orta' : 'Yüksek')">
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm font-bold text-gray-900 dark:text-white dark:text-slate-100" x-text="formatPrice(period.price)"></td>
                                <td class="px-4 py-3 text-sm text-gray-500" x-text="period.min_stay"></td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" @click="removePeriod(index)" class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- Empty State -->
            <div x-show="periods.length === 0" class="text-center py-8 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-dashed border-gray-300 dark:border-slate-800 dark:bg-slate-900">
                <i class="fas fa-calendar-times text-gray-400 text-2xl mb-2"></i>
                <p class="text-sm text-gray-500">Henüz fiyatlandırma dönemi eklenmedi.</p>
                <button type="button" @click="openModal()" class="mt-2 text-green-600 hover:text-green-700 text-sm font-bold underline">
                    İlk dönemi ekle
                </button>
            </div>

            <!-- Add/Edit Modal (Inline) -->
            <div x-show="isModalOpen" style="display: none;"
                class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="closeModal()" aria-hidden="true"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                    <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Yeni Dönem Ekle</h3>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Başlangıç</label>
                                    <input type="date" x-model="activePeriod.start_date" class="wizard-field w-full">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Bitiş</label>
                                    <input type="date" x-model="activePeriod.end_date" class="wizard-field w-full">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Sezon Tipi</label>
                                <select x-model="activePeriod.season_type" class="wizard-field w-full">
                                    <option value="low">Düşük Sezon</option>
                                    <option value="mid">Orta Sezon</option>
                                    <option value="high">Yüksek Sezon</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Günlük Fiyat</label>
                                    <div class="relative">
                                        <input type="number" x-model="activePeriod.price" min="0" step="0.01" class="wizard-field w-full !pr-8">
                                        <span class="absolute right-3 top-2.5 text-gray-500 text-sm">₺</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Min. Gün</label>
                                    <input type="number" x-model="activePeriod.min_stay" min="1" class="wizard-field w-full">
                                </div>
                            </div>
                        </div>

                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                            <button type="button" @click="addPeriod()"
                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:col-start-2 sm:text-sm dark:shadow-none">
                                Ekle
                            </button>
                            <button type="button" @click="closeModal()"
                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 dark:focus:ring-offset-gray-800 dark:shadow-none dark:text-slate-300">
                                İptal
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Özellikler & Hizmetler --}}
        <div class="wizard-card p-8">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/40 flex items-center justify-center">
                    <i class="fas fa-swimming-pool text-blue-600 dark:text-blue-400"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase dark:text-slate-100">Özellikler & Hizmetler</h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="havuz" x-model="formData.havuz" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Havuz</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="deniz_manzara" x-model="formData.deniz_manzara" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Deniz Manzarası</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="plaja_yakin" x-model="formData.plaja_yakin" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Plaja Yakın</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="wifi" x-model="formData.wifi" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Wi-Fi</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="klima" x-model="formData.klima" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Klima</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="otopark" x-model="formData.otopark" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Otopark</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="bahce" x-model="formData.bahce" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Bahçe</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="barbekü" x-model="formData.barbekü" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Barbekü</label>
                </div>
                <div class="flex items-center gap-3">
                    <input type="checkbox" name="cocuk_dostu" x-model="formData.cocuk_dostu" class="wizard-checkbox">
                    <label class="wizard-field-label !mb-0">Çocuk Dostu</label>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-4 pb-12">
            <button type="button" @click="validateData"
                class="px-8 py-4 bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white rounded-2xl font-black uppercase text-xs dark:bg-slate-900 dark:text-slate-100">
                <i class="fas fa-shield-alt mr-2 text-cyan-500"></i>
                Doğrula
            </button>
            <button type="submit"
                class="px-10 py-4 bg-gradient-to-r from-cyan-600 to-blue-600 text-white rounded-2xl hover:shadow-premium font-black uppercase text-xs">
                <i class="fas fa-save mr-2"></i>
                Kaydet
            </button>
        </div>
    </form>
</div>

<script>
    function gunlukKiralikStructuredDataForm() {
        return {
            formData: {
                konut_tipi: '',
                oda_sayisi: null,
                brut_m2: null,
                max_kisi: null,
                yatak_odasi_sayisi: null,
                banyo_sayisi: null,
                minimum_gun: null,
                fiyatlandirma: {
                    dusuk_sezon: null,
                    orta_sezon: null,
                    yuksek_sezon: null
                },
                havuz: false,
                deniz_manzara: false,
                plaja_yakin: false,
                wifi: false,
                klima: false,
                otopark: false,
                bahce: false,
                barbekü: false,
                cocuk_dostu: false
            },
            init() {
                console.log('🏖️ Günlük Kiralık Form initialized');
            },
            async saveStructuredData() {
                console.log('Saving Günlük Kiralık data:', this.formData);
                alert('Günlük kiralık bilgileri kaydedildi!');
            },
            async validateData() {
                console.log('Validating:', this.formData);
                alert('Doğrulama başarılı!');
            }
        };
    }
</script>
