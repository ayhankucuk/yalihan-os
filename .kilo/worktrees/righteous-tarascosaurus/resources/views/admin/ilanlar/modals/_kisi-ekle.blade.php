{{--
    Kişi Ekleme Modalı
    Context7 Standardı: C7-KISI-EKLE-MODAL

    Usage: "Yoksa Ekle" özelliği için
--}}

<div id="add_person_modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between p-6 border-b dark:border-slate-800">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200">Yeni Kişi Ekle</h2>
                <button type="button" id="close_add_person_modal"
                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-2xl">
                    ×
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
                <form id="add_person_form" class="space-y-4" data-person-type="owner">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Ad *</label>
                            <input type="text" name="ad"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Soyad
                                *</label>
                            <input type="text" name="soyad"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Telefon
                                *</label>
                            <input type="text" name="telefon"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Email</label>
                            <input type="email" name="email"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Müşteri Tipi
                                *</label>
                            <select  name="musteri_tipi"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200"
                                required>
                                <option value="">Seçin...</option>
                                <option value="ev_sahibi">Ev Sahibi</option>
                                <option value="satici">Satıcı</option>
                                <option value="alici">Alıcı</option>
                                <option value="kiraci">Kiracı</option>
                                <option value="yatirimci">Yatırımcı</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Durum</label>
                            <select  name="status"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                <option value="Aktif">Aktif</option>
                                <option value="Pasif">Pasif</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Notlar</label>
                        <textarea name="notlar" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                </form>
            </div>

            {{-- Modal Footer --}}
            <div class="flex justify-end space-x-4 p-6 border-t dark:border-slate-800 bg-gray-50 dark:bg-gray-900/50 dark:bg-slate-900">
                <button type="button" id="cancel_add_person"
                    class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors dark:text-slate-100">
                    İptal
                </button>
                <button type="button" id="save_add_person"
                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    💾 Kaydet
                </button>
            </div>
        </div>
    </div>
</div>
