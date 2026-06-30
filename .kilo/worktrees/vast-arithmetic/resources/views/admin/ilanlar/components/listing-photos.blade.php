{{-- Section 9: İlan Fotoğrafları --}}
<div class="bg-gray-50 dark:bg-slate-900 rounded-xl shadow-lg p-6">
    <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200 mb-6 flex items-center">
        <span
            class="bg-orange-100 dark:bg-orange-900 text-orange-600 dark:text-orange-400 rounded-full w-8 h-8 flex items-center justify-center text-sm font-bold mr-3">9</span>
        📸 İlan Fotoğrafları
    </h2>

    <div x-data="photoManager()" class="space-y-6">
        {{-- Fotoğraf Yükleme Alanı --}}
        <div
            class="bg-gradient-to-r from-orange-50 to-yellow-50 dark:from-orange-900/20 dark:to-yellow-900/20 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-slate-200 flex items-center">
                    <i class="fas fa-cloud-upload-alt mr-2 text-orange-600"></i>
                    Fotoğraf Yükleme
                </h4>
                <div class="flex items-center space-x-2">
                    <button type="button" @click="openFileDialog()"
                        class="px-4 py-2 bg-orange-500 text-white text-sm rounded-lg hover:bg-orange-600 transition-colors">
                        <i class="fas fa-folder-open mr-1"></i>Dosyadan Seç
                    </button>
                    <input type="file" id="photo-input" multiple accept="image/*" class="hidden"
                        @change="handleFileSelect($event)">
                </div>
            </div>

            {{-- Drag & Drop Alanı --}}
            <div id="photo-drop-zone"
                class="border-2 border-dashed border-orange-300 dark:border-orange-600 rounded-lg p-8 text-center transition-colors hover:border-orange-400 dark:hover:border-orange-500"
                @dragover.prevent @drop.prevent="handleDrop($event)">
                <div class="space-y-4">
                    <i class="fas fa-cloud-upload-alt text-4xl text-orange-400"></i>
                    <div>
                        <p class="text-lg font-medium text-gray-900 dark:text-white dark:text-slate-100">Fotoğrafları sürükleyip
                            bırakın</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">veya yukarıdaki butona tıklayarak
                            seçin</p>
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        <p>Maksimum 50 fotoğraf • Her fotoğraf maksimum 10MB</p>
                        <p>Desteklenen formatlar: JPG, PNG, GIF, WebP</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Fotoğraf Galerisi --}}
        <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-800 dark:text-slate-200 flex items-center">
                    <i class="fas fa-images mr-2 text-gray-600 dark:text-gray-400"></i>
                    Fotoğraf Galerisi
                    <span class="ml-2 text-sm text-gray-500 dark:text-gray-400"
                        x-text="'(' + photos.length + '/50)'"></span>
                </h4>
                <div class="flex items-center space-x-2">
                    <button type="button" @click="selectAllPhotos()"
                        class="px-3 py-1 text-sm bg-gray-500 text-white rounded hover:bg-gray-600">
                        Tümünü Seç
                    </button>
                    <button type="button" @click="clearSelection()"
                        class="px-3 py-1 text-sm bg-red-500 text-white rounded hover:bg-red-600">
                        Seçimi Temizle
                    </button>
                </div>
            </div>

            {{-- Fotoğraf Grid --}}
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                <template x-for="(photo, index) in photos" :key="photo.id">
                    <div class="relative group">
                        {{-- Fotoğraf --}}
                        <div class="aspect-square bg-gray-200 dark:bg-gray-600 rounded-lg overflow-hidden">
                            <img :src="photo.url" :alt="photo.name"
                                class="w-full h-full object-cover transition-transform group-hover:scale-105">
                        </div>

                        {{-- Seçim Overlay --}}
                        <div x-show="photo.selected"
                            class="absolute inset-0 bg-blue-500 bg-opacity-75 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check text-white text-2xl"></i>
                        </div>

                        {{-- Hover Overlay --}}
                        <div
                            class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 rounded-lg transition-all flex items-center justify-center opacity-0 group-hover:opacity-100">
                            <div class="flex space-x-2">
                                <button type="button" @click="toggleSelection(photo)"
                                    class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full text-white dark:bg-slate-900">
                                    <i class="fas" :class="photo.selected ? 'fa-check-circle' : 'fa-circle'"></i>
                                </button>
                                <button type="button" @click="setAsCover(photo)"
                                    class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full text-white dark:bg-slate-900">
                                    <i class="fas fa-star"></i>
                                </button>
                                <button type="button" @click="editPhoto(photo)"
                                    class="p-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full text-white dark:bg-slate-900">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" @click="deletePhoto(photo)"
                                    class="p-2 bg-red-500 bg-opacity-80 hover:bg-opacity-100 rounded-full text-white">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>

                        {{-- Kapak Fotoğrafı İşareti --}}
                        <div x-show="photo.isCover"
                            class="absolute top-2 left-2 bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            <i class="fas fa-star mr-1"></i>Kapak
                        </div>

                        {{-- Fotoğraf Sırası --}}
                        <div
                            class="absolute top-2 right-2 bg-gray-900 bg-opacity-75 text-white px-2 py-1 rounded-full text-xs">
                            <span x-text="index + 1"></span>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Boş Galeri --}}
            <div x-show="photos.length === 0" class="text-center py-12">
                <i class="fas fa-images text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">Henüz fotoğraf yüklenmemiş</p>
                <p class="text-sm text-gray-400 dark:text-gray-500">Yukarıdaki alana sürükleyip bırakın veya dosyadan
                    seçin</p>
            </div>
        </div>

        {{-- Fotoğraf Düzenleme Modal (Context7 uyumlu - sadece fotoğraf seçildiğinde görünür) --}}
        <div x-show="editingPhoto !== null" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="closeEditModal()">
            <div class="bg-gray-50 dark:bg-slate-900 rounded-lg max-w-2xl w-full mx-4 max-h-90vh overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Fotoğraf Düzenle</h3>
                        <button type="button" @click="closeEditModal()"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Fotoğraf Önizleme (Context7 uyumlu - null guard) --}}
                        <div x-show="editingPhoto !== null">
                            <img :src="editingPhoto?.url || ''" :alt="editingPhoto?.name || ''"
                                class="w-full rounded-lg shadow-lg">
                        </div>

                        {{-- Düzenleme Formu --}}
                        <template x-if="editingPhoto !== null">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Fotoğraf Başlığı</label>
                                    <input type="text" x-model="editingPhoto.title"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg"
                                        placeholder="Fotoğraf başlığı girin">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Açıklama</label>
                                    <textarea x-model="editingPhoto.description" rows="3"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg"
                                        placeholder="Fotoğraf açıklaması girin"></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Alternatif Metin (SEO)</label>
                                    <input type="text" x-model="editingPhoto.alt"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 rounded-lg"
                                        placeholder="SEO için alternatif metin">
                                </div>

                                <div class="flex items-center">
                                    <input type="checkbox" x-model="editingPhoto.isCover" id="edit-is-cover"
                                        class="mr-2 rounded focus:ring-blue-500 dark:focus:ring-blue-400">
                                    <label for="edit-is-cover" class="text-sm text-gray-900 dark:text-white dark:text-slate-100">Kapak fotoğrafı yap</label>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" @click="closeEditModal()"
                            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                            İptal
                        </button>
                        <button type="button" @click="savePhotoEdit()"
                            class="px-4 py-2 bg-orange-500 text-white rounded hover:bg-orange-600">
                            Kaydet
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
