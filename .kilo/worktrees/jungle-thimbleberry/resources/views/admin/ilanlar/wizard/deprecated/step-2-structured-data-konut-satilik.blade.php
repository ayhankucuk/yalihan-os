{{-- STEP 3: STRUCTURED DATA (Konut Satılık) --}}
{{-- NOT: Parent div ile kontrol ediliyor --}}
<div class="space-y-6" x-data="konutSatilikStructuredDataForm()" x-cloak>
    <div class="mb-10">
        <div
            class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-100/50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-[10px] font-black uppercase tracking-widest mb-3">
            <i class="fas fa-home"></i>
            Teknik Veriler
        </div>
        <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight dark:text-slate-100">
            İlan Detayları
        </h3>
    </div>

    <div id="konut-satilik-structured-form" class="space-y-8">
        {{-- Section 1: Genel Bilgiler --}}
        <div class="wizard-card p-8 group hover:border-blue-500/30 transition-all duration-500 relative overflow-hidden">
            <div
                class="absolute -top-12 -right-12 w-32 h-32 bg-blue-500/5 dark:bg-blue-500/10 rounded-full blur-3xl group-hover:bg-blue-500/10 dark:group-hover:bg-blue-500/20 transition-colors">
            </div>

            <div class="flex items-center gap-3 mb-8">
                <div
                    class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-400 shadow-inner">
                    <i class="fas fa-info-circle"></i>
                </div>
                <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">Genel Bilgiler
                </h4>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div>
                    <label class="wizard-field-label">
                        Konut Tipi <span class="text-red-500">*</span>
                    </label>
                    <select name="konut_tipi" x-model="formData.konut_tipi" required class="wizard-field">
                        <option value="">Seçiniz</option>
                        <option value="villa">Villa</option>
                        <option value="daire">Daire</option>
                        <option value="residence">Residence</option>
                        <option value="mustakil_ev">Müstakil Ev</option>
                        <option value="tas_ev">Taş Ev</option>
                        <option value="malikane">Malikane</option>
                    </select>
                </div>
                <div>
                    <label class="wizard-field-label">
                        Oda Sayısı <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="oda_sayisi" x-model="formData.oda_sayisi" min="1" max="20"
                        required class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">
                        Salon Sayısı <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="salon_sayisi" x-model="formData.salon_sayisi" min="0"
                        max="5" required class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">
                        Brüt m² <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="brut_m2" x-model="formData.brut_m2" min="1" max="10000"
                        step="0.01" required class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">
                        Net m²
                    </label>
                    <input type="number" name="net_m2" x-model="formData.net_m2" min="1" max="10000"
                        step="0.01" class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">
                        Banyo Sayısı <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="banyo_sayisi" x-model="formData.banyo_sayisi" min="1"
                        max="10" required class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">
                        Bina Yaşı
                    </label>
                    <input type="number" name="bina_yasi" x-model="formData.bina_yasi" min="0" max="200"
                        class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">
                        Bulunduğu Kat
                    </label>
                    <input type="number" name="kat" x-model="formData.kat" min="-5" max="100"
                        class="wizard-field">
                </div>
                <div>
                    <label class="wizard-field-label">
                        Toplam Kat
                    </label>
                    <input type="number" name="toplam_kat" x-model="formData.toplam_kat" min="1" max="100"
                        class="wizard-field">
                </div>
            </div>

            {{-- Section 3: İç Özellikler --}}
            <div
                class="wizard-card p-8 group hover:border-pink-500/30 transition-all duration-500 relative overflow-hidden">
                <div
                    class="absolute -top-12 -right-12 w-32 h-32 bg-pink-500/5 dark:bg-pink-500/10 rounded-full blur-3xl group-hover:bg-pink-500/10 dark:group-hover:bg-pink-500/20 transition-colors">
                </div>

                <div class="flex items-center gap-3 mb-8">
                    <div
                        class="w-10 h-10 rounded-xl bg-pink-50 dark:bg-pink-900/40 flex items-center justify-center text-pink-600 dark:text-pink-400 shadow-inner">
                        <i class="fas fa-couch"></i>
                    </div>
                    <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">İç
                        Özellikler</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div>
                        <label class="wizard-field-label">
                            Eşyalı Durumu
                        </label>
                        <select name="ic_ozellikler.esyali" x-model="formData.ic_ozellikler.esyali"
                            class="wizard-field">
                            <option value="">Seçiniz</option>
                            <option value="esyali">Eşyalı</option>
                            <option value="kismi_esyali">Kısmi Eşyalı</option>
                            <option value="esyasiz">Eşyasız</option>
                        </select>
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Cephe
                        </label>
                        <select name="ic_ozellikler.cephe" x-model="formData.ic_ozellikler.cephe"
                            class="wizard-field">
                            <option value="">Seçiniz</option>
                            <option value="kuzey">Kuzey</option>
                            <option value="guney">Güney</option>
                            <option value="dogu">Doğu</option>
                            <option value="bati">Batı</option>
                        </select>
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Manzara
                        </label>
                        <select name="ic_ozellikler.manzara" x-model="formData.ic_ozellikler.manzara"
                            class="wizard-field">
                            <option value="">Seçiniz</option>
                            <option value="deniz">Deniz</option>
                            <option value="dag">Dağ</option>
                            <option value="sehir">Şehir</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-8">
                    <template
                        x-for="(val, key) in {
                    klima: 'Klima',
                    merkezi_isitma: 'Merkezi Isıtma',
                    somine: 'Şömine',
                    jakuzi: 'Jakuzi',
                    balkon: 'Balkon',
                    teras: 'Teras'
                }"
                        :key="key">
                        <label
                            class="flex items-center gap-3 p-3 rounded-2xl border border-gray-100 dark:border-slate-800 bg-slate-50/50 dark:bg-gray-900/50 hover:border-pink-500/30 dark:hover:border-pink-500/50 transition-all cursor-pointer group/item">
                            <input type="checkbox" :name="`ic_ozellikler.${key}`"
                                x-model="formData.ic_ozellikler[key]"
                                class="rounded-lg text-pink-600 focus:ring-pink-500 bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-800">
                            <span
                                class="text-[11px] font-bold text-gray-600 dark:text-gray-400 group-hover/item:text-pink-600 transition-colors"
                                x-text="val"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Section 4: Dış Özellikler --}}
            <div
                class="wizard-card p-8 group hover:border-orange-500/30 transition-all duration-500 relative overflow-hidden">
                <div
                    class="absolute -top-12 -right-12 w-32 h-32 bg-orange-500/5 dark:bg-orange-500/10 rounded-full blur-3xl group-hover:bg-orange-500/10 dark:group-hover:bg-orange-500/20 transition-colors">
                </div>

                <div class="flex items-center gap-3 mb-8">
                    <div
                        class="w-10 h-10 rounded-xl bg-orange-50 dark:bg-orange-900/40 flex items-center justify-center text-orange-600 dark:text-orange-400 shadow-inner">
                        <i class="fas fa-tree"></i>
                    </div>
                    <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">Dış
                        Özellikler</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div
                        class="md:col-span-2 lg:col-span-1 flex items-center gap-4 p-4 rounded-2xl bg-orange-50/30 dark:bg-orange-900/10 border border-orange-100 dark:border-orange-900/20">
                        <input type="checkbox" name="dis_ozellikler.bahce_var"
                            x-model="formData.dis_ozellikler.bahce_var"
                            class="w-5 h-5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <div>
                            <span
                                class="block text-xs font-black text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Bahçe
                                Var mı?</span>
                        </div>
                    </div>
                    <div x-show="formData.dis_ozellikler.bahce_var" x-transition>
                        <label class="wizard-field-label">
                            Bahçe Büyüklüğü (m²)
                        </label>
                        <input type="number" name="dis_ozellikler.bahce_buyuklugu"
                            x-model="formData.dis_ozellikler.bahce_buyuklugu" min="0" class="wizard-field">
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Otopark Durumu
                        </label>
                        <select name="dis_ozellikler.otopark" x-model="formData.dis_ozellikler.otopark"
                            class="wizard-field">
                            <option value="">Seçiniz</option>
                            <option value="kapali">Kapalı</option>
                            <option value="acik">Açık</option>
                            <option value="yok">Yok</option>
                        </select>
                    </div>
                    <div x-show="formData.dis_ozellikler.otopark !== 'yok'" x-transition>
                        <label class="wizard-field-label">
                            Kapasite (Araç)
                        </label>
                        <input type="number" name="dis_ozellikler.otopark_kapasitesi"
                            x-model="formData.dis_ozellikler.otopark_kapasitesi" min="0" class="wizard-field">
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-2xl border border-gray-100 dark:border-slate-800">
                        <input type="checkbox" name="dis_ozellikler.barbeku"
                            x-model="formData.dis_ozellikler.barbeku"
                            class="rounded text-orange-600 focus:ring-orange-500">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400">Barbekü</label>
                    </div>
                    <div class="flex items-center gap-3 p-3 rounded-2xl border border-gray-100 dark:border-slate-800">
                        <input type="checkbox" name="dis_ozellikler.havuz" x-model="formData.dis_ozellikler.havuz"
                            class="rounded text-orange-600 focus:ring-orange-500">
                        <label class="text-[11px] font-bold text-gray-600 dark:text-gray-400">Havuz</label>
                    </div>
                </div>
            </div>

            {{-- Section 5: Bina Özellikleri --}}
            <div
                class="wizard-card p-8 group hover:border-gray-500/30 transition-all duration-500 relative overflow-hidden">
                <div class="flex items-center gap-3 mb-8">
                    <div
                        class="w-10 h-10 rounded-xl bg-gray-50 dark:bg-gray-900/40 flex items-center justify-center text-gray-600 dark:text-gray-400 shadow-inner dark:bg-slate-900">
                        <i class="fas fa-building"></i>
                    </div>
                    <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">Bina
                        Özellikleri</h4>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <template
                        x-for="(val, key) in {
                    asansor: 'Asansör',
                    site_icinde: 'Site İçinde',
                    guvenlik: 'Güvenlik',
                    kapali_otopark: 'Kapalı Otopark',
                    cocuk_oyun_alani: 'Çocuk Oyun Alanı',
                    spor_salonu: 'Spor Salonu',
                    havuz: 'Site Havuzu',
                    yonetim: 'Yönetim'
                }"
                        :key="key">
                        <label
                            class="flex items-center gap-3 p-3 rounded-2xl border border-gray-100 dark:border-slate-800 bg-slate-50/30 dark:bg-gray-900/30 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all cursor-pointer group/item">
                            <input type="checkbox" :name="`bina.${key}`" x-model="formData.bina[key]"
                                class="rounded text-gray-600 dark:text-gray-400 focus:ring-gray-500 bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-800">
                            <span
                                class="text-[11px] font-bold text-gray-600 dark:text-gray-400 group-hover/item:text-blue-500 transition-colors"
                                x-text="val"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Section 6: Tapu & İmar --}}
            <div
                class="wizard-card p-8 group hover:border-yellow-500/30 transition-all duration-500 relative overflow-hidden">
                <div class="flex items-center gap-3 mb-8">
                    <div
                        class="w-10 h-10 rounded-xl bg-yellow-50 dark:bg-yellow-900/40 flex items-center justify-center text-yellow-600 dark:text-yellow-400 shadow-inner">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">Tapu & İmar
                    </h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div>
                        <label class="wizard-field-label">
                            Tapu Durumu
                        </label>
                        <select name="tapu_imar.tapu_durumu" x-model="formData.tapu_imar.tapu_durumu"
                            class="wizard-field">
                            <option value="">Seçiniz</option>
                            <option value="kat_irtifaki">Kat İrtifakı</option>
                            <option value="kat_mulkiyeti">Kat Mülkiyeti</option>
                            <option value="arsa_tapusu">Arsa Tapusu</option>
                        </select>
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            İmar Durumu
                        </label>
                        <select name="tapu_imar.imar_durumu" x-model="formData.tapu_imar.imar_durumu"
                            class="wizard-field">
                            <option value="">Seçiniz</option>
                            <option value="imarli">İmarlı</option>
                            <option value="imarsiz">İmarsız</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-4">
                        <label
                            class="flex items-center gap-3 p-3 rounded-2xl border border-gray-100 dark:border-slate-800">
                            <input type="checkbox" name="tapu_imar.krediye_uygun"
                                x-model="formData.tapu_imar.krediye_uygun"
                                class="rounded text-yellow-600 focus:ring-yellow-500">
                            <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400">Krediye Uygun</span>
                        </label>
                        <label
                            class="flex items-center gap-3 p-3 rounded-2xl border border-gray-100 dark:border-slate-800">
                            <input type="checkbox" name="tapu_imar.takas" x-model="formData.tapu_imar.takas"
                                class="rounded text-yellow-600 focus:ring-yellow-500">
                            <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400">Takas</span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Section 7: Ulaşım & Mesafeler --}}
            <div
                class="wizard-card p-8 group hover:border-cyan-500/30 transition-all duration-500 relative overflow-hidden">
                <div
                    class="absolute -top-12 -right-12 w-32 h-32 bg-cyan-500/5 dark:bg-cyan-500/10 rounded-full blur-3xl group-hover:bg-cyan-500/10 dark:group-hover:bg-cyan-500/20 transition-colors">
                </div>

                <div class="flex items-center gap-3 mb-8">
                    <div
                        class="w-10 h-10 rounded-xl bg-cyan-50 dark:bg-cyan-900/40 flex items-center justify-center text-cyan-600 dark:text-cyan-400 shadow-inner">
                        <i class="fas fa-bus"></i>
                    </div>
                    <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">Ulaşım &
                        Mesafeler (metre)</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div>
                        <label class="wizard-field-label">
                            Otobüs Durağı
                        </label>
                        <input type="number" name="ulasim.otobus_duragi_mesafe"
                            x-model="formData.ulasim.otobus_duragi_mesafe" min="0" class="wizard-field"
                            placeholder="0">
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Metro
                        </label>
                        <input type="number" name="ulasim.metro_mesafe" x-model="formData.ulasim.metro_mesafe"
                            min="0" class="wizard-field" placeholder="0">
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Market
                        </label>
                        <input type="number" name="ulasim.market_mesafe" x-model="formData.ulasim.market_mesafe"
                            min="0" class="wizard-field" placeholder="0">
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Okul
                        </label>
                        <input type="number" name="ulasim.okul_mesafe" x-model="formData.ulasim.okul_mesafe"
                            min="0" class="wizard-field" placeholder="0">
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Sağlık Merkezi
                        </label>
                        <input type="number" name="ulasim.saglik_merkezi_mesafe"
                            x-model="formData.ulasim.saglik_merkezi_mesafe" min="0" class="wizard-field"
                            placeholder="0">
                    </div>
                </div>
            </div>

            {{-- Section 8: Sosyal & Konfor --}}
            <div
                class="wizard-card p-8 group hover:border-indigo-500/30 transition-all duration-500 relative overflow-hidden">
                <div class="flex items-center gap-3 mb-8">
                    <div
                        class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shadow-inner">
                        <i class="fas fa-smile"></i>
                    </div>
                    <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">Sosyal &
                        Konfor</h4>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <template
                        x-for="(val, key) in {
                    wifi: 'WiFi',
                    uydu: 'Uydu',
                    tv: 'TV',
                    muzik_sistemi: 'Müzik Sistemi'
                }"
                        :key="key">
                        <label
                            class="flex items-center gap-3 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 bg-slate-50/30 dark:bg-gray-900/30 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-all cursor-pointer">
                            <input type="checkbox" :name="`sosyal.${key}`" x-model="formData.sosyal[key]"
                                class="rounded-lg text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500 bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-800">
                            <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400"
                                x-text="val"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Section 9: Güvenlik --}}
            <div
                class="wizard-card p-8 group hover:border-rose-500/30 transition-all duration-500 relative overflow-hidden">
                <div class="flex items-center gap-3 mb-8">
                    <div
                        class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-900/40 flex items-center justify-center text-rose-600 dark:text-rose-400 shadow-inner">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">Güvenlik
                    </h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <template
                        x-for="(val, key) in {
                    kamera: 'Güvenlik Kamerası',
                    alarm: 'Alarm Sistemi',
                    interkom: 'Görüntülü Diafon'
                }"
                        :key="key">
                        <label
                            class="flex items-center gap-3 p-4 rounded-2xl border border-gray-100 dark:border-slate-800 bg-slate-50/30 dark:bg-gray-900/30 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-all cursor-pointer">
                            <input type="checkbox" :name="`guvenlik.${key}`" x-model="formData.guvenlik[key]"
                                class="rounded-lg text-rose-600 dark:text-rose-400 focus:ring-rose-500 bg-white dark:bg-slate-900 border-gray-300 dark:border-slate-800">
                            <span class="text-[11px] font-bold text-gray-600 dark:text-gray-400"
                                x-text="val"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Section 10: Enerji --}}
            <div
                class="wizard-card p-8 group hover:border-amber-500/30 transition-all duration-500 relative overflow-hidden">
                <div
                    class="absolute -top-12 -right-12 w-32 h-32 bg-amber-500/5 dark:bg-amber-500/10 rounded-full blur-3xl group-hover:bg-amber-500/10 dark:group-hover:bg-amber-500/20 transition-colors">
                </div>

                <div class="flex items-center gap-3 mb-8">
                    <div
                        class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/40 flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-inner">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">Enerji &
                        Isıtma</h4>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div>
                        <label class="wizard-field-label">
                            Isıtma Tipi
                        </label>
                        <select name="enerji.isitma_tipi" x-model="formData.enerji.isitma_tipi" class="wizard-field">
                            <option value="">Seçiniz</option>
                            <option value="merkezi">Merkezi</option>
                            <option value="kombi">Kombi</option>
                            <option value="soba">Soba</option>
                            <option value="klima">Klima</option>
                            <option value="yerden_isitma">Yerden Isıtma</option>
                        </select>
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Yakıt Tipi
                        </label>
                        <select name="enerji.yakit_tipi" x-model="formData.enerji.yakit_tipi" class="wizard-field">
                            <option value="">Seçiniz</option>
                            <option value="dogalgaz">Doğalgaz</option>
                            <option value="elektrik">Elektrik</option>
                            <option value="komur">Kömür</option>
                            <option value="fuel_oil">Fuel-oil</option>
                            <option value="lpg">LPG</option>
                        </select>
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Enerji Sınıfı
                        </label>
                        <select name="enerji.enerji_sinifi" x-model="formData.enerji.enerji_sinifi"
                            class="wizard-field">
                            <option value="">Seçiniz</option>
                            <option value="A">A Sınıfı</option>
                            <option value="B">B Sınıfı</option>
                            <option value="C">C Sınıfı</option>
                            <option value="D">D Sınıfı</option>
                            <option value="E">E Sınıfı</option>
                            <option value="F">F Sınıfı</option>
                            <option value="G">G Sınıfı</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Section 11: Fiyat & Finans --}}
            <div
                class="wizard-card p-8 group hover:border-green-500/30 transition-all duration-500 relative overflow-hidden">
                <div
                    class="absolute -top-12 -right-12 w-32 h-32 bg-green-500/10 dark:bg-green-500/20 rounded-full blur-3xl transition-all duration-700 group-hover:scale-150">
                </div>

                <div class="flex items-center justify-between mb-8 relative z-10">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-green-50 dark:bg-green-900/40 flex items-center justify-center text-green-600 dark:text-green-400 shadow-inner">
                            <i class="fas fa-wallet"></i>
                        </div>
                        <div>
                            <h4 class="text-base font-black text-gray-900 dark:text-white uppercase tracking-wider dark:text-slate-100">
                                Fiyat & Finans</h4>
                            <p
                                class="text-[10px] text-green-600 dark:text-green-400 font-bold uppercase tracking-widest mt-0.5">
                                Yatırım Değerlemesi</p>
                        </div>
                    </div>

                    {{-- Visual Highlight for "Investment Ready" --}}
                    <div
                        class="flex items-center gap-2 px-3 py-1.5 rounded-2xl border border-gray-100 dark:border-slate-800 bg-slate-50/30 dark:bg-gray-900/30 shadow-sm backdrop-blur-sm dark:shadow-none">
                        <div class="w-2 h-2 rounded-full bg-green-500 dark:bg-green-400 animate-pulse"></div>
                        <span
                            class="text-[9px] font-black text-gray-700 dark:text-slate-200 uppercase tracking-widest dark:text-slate-300">Yatırıma
                            Hazır</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 relative z-10">
                    <div class="lg:col-span-1">
                        <label class="wizard-field-label">
                            Satılık Fiyat <span class="text-red-500">*</span>
                        </label>
                        <div class="relative group/input">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-green-500 group-hover/input:scale-110 transition-transform">
                                <i class="fas fa-tag"></i>
                            </div>
                            <input type="text" id="fiyat_display" placeholder="27.000.000"
                                x-model="formData.fiyat.satilik_fiyat_display"
                                @input="$el.value = window.priceFormatter.formatWithSeparators($el.value);
                                    formData.fiyat.satilik_fiyat = window.priceFormatter.getRawValue($el.value);
                                    updatePriceText();"
                                required class="wizard-field !pl-10 !pr-12 !text-green-600 !font-black !text-lg">
                            <input type="hidden" name="fiyat.satilik_fiyat" x-model="formData.fiyat.satilik_fiyat">
                            <div class="absolute right-4 top-3.5 pointer-events-none">
                                <span class="text-green-500 font-bold text-lg"
                                    x-text="window.priceFormatter.getCurrencySymbol(formData.fiyat.para_birimi)">₺</span>
                            </div>
                        </div>

                        {{-- Yazıyla Fiyat Display --}}
                        <div
                            class="mt-3 px-4 py-2 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <p class="text-xs font-medium text-green-800 dark:text-green-300 flex items-center gap-2">
                                <span class="opacity-75">Yazıyla:</span>
                                <span id="price_text_value" class="font-bold uppercase tracking-wider">Fiyat
                                    Bekleniyor...</span>
                            </p>
                        </div>
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Para Birimi <span class="text-red-500">*</span>
                        </label>
                        <select name="fiyat.para_birimi" x-model="formData.fiyat.para_birimi" required
                            @change="updatePriceText()" class="wizard-field">
                            <option value="">Seçiniz</option>
                            <option value="TRY">TRY (₺)</option>
                            <option value="USD">USD ($)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="GBP">GBP (£)</option>
                        </select>
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Aidat (TL/ay)
                        </label>
                        <input type="number" name="fiyat.aidat" x-model="formData.fiyat.aidat" min="0"
                            step="0.01" class="wizard-field">
                    </div>
                    <div>
                        <label class="wizard-field-label">
                            Kira Getirisi (TL/ay)
                        </label>
                        <input type="number" name="fiyat.kira_getirisi" x-model="formData.fiyat.kira_getirisi"
                            min="0" step="0.01" class="wizard-field">
                    </div>
                    <div
                        class="md:col-span-2 flex items-center gap-6 p-6 rounded-3xl border border-gray-100 dark:border-slate-800 bg-slate-50/30 dark:bg-gray-900/30 shadow-inner">
                        <label class="relative inline-flex items-center cursor-pointer group">
                            <input type="checkbox" name="fiyat.yatirimlik" x-model="formData.fiyat.yatirimlik"
                                class="sr-only peer">
                            <div
                                class="w-14 h-7 rounded-full bg-gray-200 dark:bg-gray-700 peer-focus:outline-none peer peer-checked:after:translate-x-full peer-checked:after:border-white dark:peer-checked:after:border-gray-200 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white dark:after:bg-gray-200 after:border-gray-300 dark:after:border-gray-500 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-green-600 dark:peer-checked:bg-green-500 shadow-inner">
                            </div>
                        </label>
                        <div>
                            <span
                                class="block text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight dark:text-slate-100">Bu
                                Bir Yatırım Fırsatı mı?</span>
                            <span
                                class="block text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-widest mt-0.5">Yüksek
                                Kira Getirisi & Hızlı Değer Artışı</span>
                        </div>
                    </div>

                    {{-- m² Birim Fiyatı Display --}}
                    <div x-show="unitPrice > 0" x-transition
                        class="md:col-span-3 p-6 rounded-3xl bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-800">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-12 h-12 rounded-xl bg-blue-100 dark:bg-blue-800/30 flex items-center justify-center text-blue-600 dark:text-blue-400">
                                    <i class="fas fa-calculator text-xl"></i>
                                </div>
                                <div>
                                    <p
                                        class="text-xs font-bold uppercase tracking-widest text-blue-600 dark:text-blue-400">
                                        Metrekare Birim Fiyatı
                                    </p>
                                    <p class="text-sm text-blue-800 dark:text-blue-300 mt-0.5">
                                        Toplam Fiyat / Brüt m²
                                    </p>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-3xl font-black text-blue-600 dark:text-blue-400"
                                    x-text="window.priceFormatter.formatUnitPrice(unitPrice, formData.fiyat.para_birimi)">
                                </span>
                                <span class="block text-xs font-bold text-blue-400 mt-1">/ m²</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-4 pb-12">
                <button type="button" @click="validateData"
                    class="px-8 py-4 bg-gray-100 dark:bg-slate-800 text-gray-900 dark:text-white rounded-2xl hover:bg-gray-200 dark:hover:bg-slate-700 font-black uppercase tracking-widest text-xs transition-all duration-300 active:scale-95 shadow-sm dark:shadow-none dark:bg-slate-900 dark:text-slate-100">
                    <i class="fas fa-shield-alt mr-2 text-blue-500"></i>
                    Veriyi Doğrula
                </button>
                <button type="submit"
                    class="px-10 py-4 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-2xl hover:shadow-premium font-black uppercase tracking-widest text-xs transition-all duration-500 active:scale-95 transform hover:-translate-y-1">
                    <i class="fas fa-save mr-2"></i>
                    Detayları Kaydet
                </button>
            </div>
    </form>
</div>

<script>
    // Price text conversion function
    async function updatePriceText() {
        const priceValue = document.getElementById('fiyat_display')?.value;
        const currency = document.querySelector('select[name="fiyat.para_birimi"]')?.value || 'TRY';
        const textElement = document.getElementById('price_text_value');

        if (!priceValue || !textElement) return;

        const rawValue = window.priceFormatter.getRawValue(priceValue);
        if (rawValue > 0) {
            const text = window.priceFormatter.convertToText(rawValue, currency);
            textElement.textContent = text.toUpperCase();
        } else {
            textElement.textContent = 'Fiyat Bekleniyor...';
        }
    }

    function konutSatilikStructuredDataForm() {
        return {
            districts: [],
            neighborhoods: [],
            loadingDistricts: false,
            loadingNeighborhoods: false,
            unitPrice: 0,
            formData: {
                konut_tipi: '',
                oda_sayisi: null,
                salon_sayisi: null,
                brut_m2: null,
                net_m2: null,
                banyo_sayisi: null,
                bina_yasi: null,
                kat: null,
                toplam_kat: null,
                lokasyon: {
                    il_id: null,
                    ilce_id: null,
                    mahalle_id: null,
                    adres: '',
                    merkez_mesafe: null,
                    deniz_mesafe: null,
                },
                ic_ozellikler: {
                    esyali: '',
                    klima: false,
                    merkezi_isitma: false,
                    somine: false,
                    jakuzi: false,
                    balkon: false,
                    teras: false,
                    cephe: '',
                    manzara: '',
                },
                dis_ozellikler: {
                    bahce_var: false,
                    bahce_buyuklugu: null,
                    otopark: '',
                    otopark_kapasitesi: null,
                    barbeku: false,
                    havuz: false,
                    havuz_turu: '',
                },
                bina: {
                    asansor: false,
                    site_icinde: false,
                    guvenlik: false,
                    kapali_otopark: false,
                    cocuk_oyun_alani: false,
                    spor_salonu: false,
                    havuz: false,
                    yonetim: false,
                },
                tapu_imar: {
                    tapu_durumu: '',
                    imar_durumu: '',
                    krediye_uygun: false,
                    takas: false,
                    deprem_yonetmeligi: '',
                },
                ulasim: {
                    otobus_duragi_mesafe: null,
                    metro_mesafe: null,
                    market_mesafe: null,
                    okul_mesafe: null,
                    saglik_merkezi_mesafe: null,
                },
                sosyal: {
                    wifi: false,
                    uydu: false,
                    tv: false,
                    muzik_sistemi: false,
                },
                guvenlik: {
                    kamera: false,
                    alarm: false,
                    interkom: false,
                },
                enerji: {
                    isitma_tipi: '',
                    yakit_tipi: '',
                    enerji_sinifi: '',
                },
                fiyat: {
                    satilik_fiyat: null,
                    satilik_fiyat_display: '',
                    para_birimi: 'TRY',
                    aidat: null,
                    kira_getirisi: null,
                    yatirimlik: false
                },
            },
            init() {
                // Edit modu kontrolü (eğer veriler dolu gelirse)
                this.$watch('formData.lokasyon.il_id', (value) => {
                    if (value) this.loadIlceler(value);
                });
                this.$watch('formData.lokasyon.ilce_id', (value) => {
                    if (value) this.loadMahalleler(value);
                });

                console.log('🏠 Konut Satılık Form component initialized');

                // Watch for price and area changes to calculate unit price
                this.$watch('formData.fiyat.satilik_fiyat', () => this.calculateUnitPrice());
                this.$watch('formData.brut_m2', () => this
            .calculateUnitPrice()); // Changed from formData.genel.alan_m2_brut to formData.brut_m2 based on existing formData structure
            },
            async loadIlceler(ilId) {
                if (!ilId) {
                    this.districts = [];
                    return;
                }
                this.loadingDistricts = true;
                try {
                    const response = await fetch(`/api/v1/location/districts/${ilId}`);
                    const result = await response.json();
                    if (result.success) {
                        this.districts = result.data;
                    }
                } catch (e) {
                    console.error('İlçeler yüklenemedi', e);
                } finally {
                    this.loadingDistricts = false;
                }
            },
            async loadMahalleler(ilceId) {
                if (!ilceId) {
                    this.neighborhoods = [];
                    return;
                }
                this.loadingNeighborhoods = true;
                try {
                    const response = await fetch(`/api/v1/location/neighborhoods/${ilceId}`);
                    const result = await response.json();
                    if (result.success) {
                        this.neighborhoods = result.data;
                    }
                } catch (e) {
                    console.error('Mahalleler yüklenemedi', e);
                } finally {
                    this.loadingNeighborhoods = false;
                }
            },
            calculateUnitPrice() {
                const price = parseFloat(this.formData.fiyat.satilik_fiyat) || 0;
                const area = parseFloat(this.formData.brut_m2) ||
                    0; // Changed from formData.genel.alan_m2_brut to formData.brut_m2 based on existing formData structure
                this.unitPrice = area > 0 ? price / area : 0;
            },
            getIlanId() {
                if (window.ilanId && window.ilanId > 0) {
                    return window.ilanId;
                }
                const hiddenInput = document.querySelector('[name="ilan_id"]');
                if (hiddenInput && hiddenInput.value) {
                    return hiddenInput.value;
                }
                const urlMatch = window.location.pathname.match(/ilanlar\/(\d+)/);
                if (urlMatch && urlMatch[1]) {
                    return urlMatch[1];
                }
                return null;
            },
            async saveStructuredData() {
                const ilanId = this.getIlanId();
                if (!ilanId) {
                    alert('İlan ID bulunamadı');
                    return;
                }

                const response = await fetch(`/admin/ilanlar/${ilanId}/structured-data/konut`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        structured_data: this.formData
                    }),
                });

                const data = await response.json();
                if (data.success) {
                    alert('Kaydedildi');
                } else {
                    alert('Hata: ' + (data.message || 'Bilinmeyen hata'));
                }
            },
            async validateData() {
                const ilanId = this.getIlanId();
                if (!ilanId) {
                    alert('İlan ID bulunamadı');
                    return;
                }

                const response = await fetch(`/admin/ilanlar/${ilanId}/structured-data/konut/validate`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        structured_data: this.formData
                    }),
                });

                const data = await response.json();
                if (data.success) {
                    alert('Validation başarılı');
                } else {
                    alert('Validation hataları: ' + JSON.stringify(data.errors));
                }
            },
        };
    }
</script>
