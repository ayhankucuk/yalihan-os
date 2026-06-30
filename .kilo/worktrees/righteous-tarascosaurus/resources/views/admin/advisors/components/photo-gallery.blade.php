{{-- Advisor Photo Gallery Component --}}
{{-- Phase 5.3.2 Frontend --}}

<div 
    x-data="advisorPhotoGallery()"
    x-init="init()"
    @:data-advisor-id="{{ $advisor->id ?? $advisorId }}"
    class="w-full max-w-4xl mx-auto p-6"
>
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-lg p-6">
        {{-- Header --}}
        <div class="mb-6 pb-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                📸 Danışman Fotoğrafları
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                Yüksek kaliteli fotoğraflar danışman profilinizi güçlendirir
            </p>
        </div>

        {{-- Upload Section --}}
        <div class="mb-8">
            {{-- Drag & Drop Area --}}
            <div
                @dragover="onDragOver($event)"
                @dragleave="onDragLeave($event)"
                @drop="onDrop($event)"
                :class="{
                    'bg-blue-50 dark:bg-blue-900 border-blue-400 dark:border-blue-500':
                        dragOverActive,
                    'bg-gray-50 dark:bg-gray-700 border-gray-300 dark:border-gray-600':
                        !dragOverActive,
                }"
                class="border-2 border-dashed rounded-lg p-8 text-center transition-colors duration-200"
            >
                {{-- Upload Icon --}}
                <svg
                    class="mx-auto h-12 w-12 text-gray-400 dark:text-slate-200 mb-4"
                    stroke="currentColor"
                    fill="none"
                    viewBox="0 0 48 48"
                    aria-hidden="true"
                >
                    <path
                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v4a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32 0a2 2 0 00-2-2h-2.718a2 2 0 00-1.591.763l-2.22-2.219a2 2 0 00-2.828 0l-2.22 2.219a2 2 0 00-1.591-.763h-2.718a2 2 0 00-2 2m32 0H8m40 0a8 8 0 11-16 0 8 8 0 0116 0z"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />
                </svg>

                {{-- Upload Text --}}
                <div class="mb-4">
                    <p class="text-lg font-medium text-gray-900 dark:text-white dark:text-slate-100">
                        📁 Fotoğrafları sürükle ya da tıkla
                    </p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        JPEG, PNG, GIF veya WebP • Max 10 MB
                    </p>
                </div>

                {{-- Hidden File Input --}}
                <input
                    type="file"
                    accept="image/*"
                    @change="onFileSelect($event)"
                    :disabled="uploading"
                    x-ref="fileInput"
                    class="hidden"
                />

                {{-- Upload Button --}}
                <button
                    @click="$refs.fileInput.click()"
                    :disabled="uploading"
                    :class="{
                        'opacity-50 cursor-not-allowed': uploading,
                    }"
                    class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200 disabled:hover:bg-blue-600"
                >
                    <span x-show="!uploading" class="flex items-center gap-2">
                        <svg
                            class="w-5 h-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M3 17a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm3.172-5.829a1 1 0 011.414 0L9 12.586V3a1 1 0 011-1h2a1 1 0 011 1v9.586l1.414-1.415a1 1 0 011.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                            />
                        </svg>
                        Fotoğraf Seç
                    </span>
                    <span x-show="uploading" class="flex items-center gap-2">
                        <svg
                            class="animate-spin h-5 w-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                d="M11 3a8 8 0 100 16 8 8 0 000-16zm0 14a6 6 0 100-12 6 6 0 000 12zm0-7a1 1 0 11-2 0 1 1 0 012 0z"
                            />
                        </svg>
                        Yükleniyor...
                    </span>
                </button>
            </div>
        </div>

        {{-- Loading State --}}
        <div x-show="loading" class="text-center py-12">
            <div
                class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 dark:border-blue-400"
            ></div>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                Fotoğraflar yükleniyor...
            </p>
        </div>

        {{-- Photos Grid --}}
        <div x-show="!loading && photos.length > 0" class="mt-8">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                📋 Galerisi (<span x-text="photos.length"></span> fotoğraf)
            </h3>

            <div
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
            >
                <template x-for="(photo, index) in photos" :key="photo.id">
                    <div
                        :class="{
                            'ring-2 ring-yellow-400 dark:ring-yellow-300':
                                photo.featured,
                        }"
                        class="rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 shadow-md hover:shadow-lg transition-shadow duration-200 dark:shadow-none dark:bg-slate-900"
                    >
                        {{-- Featured Badge --}}
                        <div
                            x-show="photo.featured"
                            class="absolute top-2 left-2 bg-yellow-400 dark:bg-yellow-500 text-gray-900 px-3 py-1 rounded-full text-sm font-semibold flex items-center gap-1 z-10 dark:text-slate-100 dark:text-white"
                        >
                            <span>⭐</span>
                            <span>Öne Çıkan</span>
                        </div>

                        {{-- Image Container --}}
                        <div class="relative h-48 overflow-hidden bg-gray-200 dark:bg-gray-600">
                            <img
                                :src="photo.dosya_yolu"
                                :alt="`Danışman Fotoğrafı ${index + 1}`"
                                class="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                            />
                        </div>

                        {{-- Photo Info --}}
                        <div class="p-4">
                            {{-- Order & Quality Score --}}
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100"
                                    >
                                        #<span x-text="photo.display_order"></span>
                                    </span>
                                </div>
                                <div
                                    :class="getQualityColor(photo.quality_score)"
                                    class="px-3 py-1 rounded-full text-white text-sm font-medium"
                                >
                                    <span x-text="photo.quality_score"></span>/100
                                </div>
                            </div>

                            {{-- Quality Badge --}}
                            <p
                                x-text="getQualityBadge(photo.quality_score)"
                                class="text-xs text-gray-600 dark:text-gray-400 mb-3"
                            ></p>

                            {{-- Upload Date --}}
                            <p
                                class="text-xs text-gray-500 dark:text-gray-400 mb-4"
                            >
                                📅
                                <span
                                    x-text="new Date(photo.created_at).toLocaleDateString('tr-TR')"
                                ></span>
                            </p>

                            {{-- Delete Button --}}
                            <button
                                @click="deletePhoto(photo.id)"
                                class="w-full px-3 py-2 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition-colors duration-200 text-sm flex items-center justify-center gap-2"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                Sil
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- Empty State --}}
        <div
            x-show="!loading && photos.length === 0"
            class="text-center py-12"
        >
            <svg
                class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                />
            </svg>
            <p class="mt-4 text-gray-600 dark:text-gray-400">
                Henüz fotoğraf yüklenmemiştir
            </p>
            <p class="text-sm text-gray-500 dark:text-gray-500 mt-2">
                Yukarıdaki alana fotoğraf yükleyerek başlayın
            </p>
        </div>

        {{-- Info Box --}}
        <div
            class="mt-8 p-4 bg-blue-50 dark:bg-blue-900 border border-blue-200 dark:border-blue-700 rounded-lg"
        >
            <h4
                class="font-semibold text-blue-900 dark:text-blue-100 mb-2"
            >
                💡 Kalite Puanlama Sistemi
            </h4>
            <ul
                class="text-sm text-blue-800 dark:text-blue-200 space-y-1 ml-4 list-disc"
            >
                <li>⭐ <strong>90+:</strong> Mükemmel - Profesyonel kalite</li>
                <li>✅ <strong>75-89:</strong> İyi - Uygun kalite</li>
                <li>
                    ⚠️ <strong>60-74:</strong> Kabul Edilebilir - Geliştirilmeye
                    açık
                </li>
                <li>❌ <strong>0-59:</strong> Düşük - Yükseltme önerilir</li>
            </ul>
            <p class="text-xs text-blue-700 dark:text-blue-300 mt-3">
                Sistem otomatik olarak en yüksek kaliteli fotoğrafı "Öne
                Çıkan" olarak ayarlar.
            </p>
        </div>
    </div>
</div>

<script>
    // Import the gallery component
    import { initAdvisorPhotoGallery } from '@/js/admin/advisor/advisor-photo-gallery.js';

    // Make it global for Alpine
    window.advisorPhotoGallery = initAdvisorPhotoGallery;
</script>
