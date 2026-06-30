{{--
    Site/Apartman Ekleme Modalı
    Context7 Standardı: C7-SITE-EKLE-MODAL

    Usage: "Yoksa Ekle" özelliği için
--}}

<div id="add_site_modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-gray-50 dark:bg-slate-900 rounded-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            {{-- Modal Header --}}
            <div class="flex items-center justify-between p-6 border-b dark:border-slate-800">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-slate-200">Yeni Site/Apartman Ekle</h2>
                <button type="button" id="close_add_site_modal"
                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 text-2xl">
                    ×
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-200px)]">
                <form id="add_site_form" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Site/Apartman
                                Adı *</label>
                            <input type="text" name="name" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Tip</label>
                            <select  name="site_tipi"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                <option value="site">Site</option>
                                <option value="apartman">Apartman</option>
                                <option value="rezidans">Rezidans</option>
                                <option value="villa">Villa Kompleksi</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">İl *</label>
                            <select  name="il_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                <option value="">İl Seçin</option>
                                @foreach (\App\Models\Il::orderBy('il_adi')->get() as $il)
                                    <option value="{{ $il->id }}">{{ $il->il_adi }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">İlçe
                                *</label>
                            <select  name="ilce_id" required
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                <option value="">Önce il seçin</option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Mahalle</label>
                            <select  name="mahalle_id"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                                <option value="">Önce ilçe seçin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Toplam Daire
                                Sayısı</label>
                            <input type="number" name="toplam_daire_sayisi" min="0"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Adres</label>
                        <textarea name="address" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Özellikler</label>
                        <textarea name="description" rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Havuz, spor salonu, güvenlik, vs..."></textarea>
                    </div>
                </form>
            </div>

            {{-- Modal Footer --}}
            <div class="flex justify-end space-x-4 p-6 border-t dark:border-slate-800 bg-gray-50 dark:bg-gray-900/50 dark:bg-slate-900">
                <button type="button" id="cancel_add_site"
                    class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors dark:text-slate-100">
                    İptal
                </button>
                <button type="button" id="save_add_site"
                    class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    💾 Kaydet
                </button>
            </div>
        </div>
    </div>
</div>
