@props([
    'name' => 'photos',
    'label' => 'Fotoğraflar',
    'multiple' => true,
    'maxFiles' => 10,
    'maxSize' => 5, // MB cinsinden
    'accept' => 'image/*',
    'existingPhotos' => [],
    'coverPhotoIndex' => 0,
    'required' => false,
    'help' => null,
])

<div x-data="{
    files: [],
    existingPhotos: {{ json_encode($existingPhotos) }},
    coverPhotoIndex: {{ $coverPhotoIndex }},
    previewUrls: [],
    dragOver: false,

    init() {
        this.$watch('files', value => {
            this.previewUrls = [];
            for (let i = 0; i < value.length; i++) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrls.push({
                        url: e.target.result,
                        name: value[i].name,
                        size: this.formatFileSize(value[i].size)
                    });
                    this.$nextTick(() => this.makeElementsSortable());
                };
                reader.readAsDataURL(value[i]);
            }
        });

        this.$nextTick(() => this.makeElementsSortable());
    },

    makeElementsSortable() {
        if (typeof Sortable !== 'undefined') {
            new Sortable(this.$refs.previewContainer, {
                animation: 150,
                ghostClass: 'bg-blue-100',
                onEnd: (evt) => {
                    // Dosya sırasını güncelle
                    const files = Array.from(this.files);
                    const moved = files.splice(evt.oldIndex, 1)[0];
                    files.splice(evt.newIndex, 0, moved);

                    // FileList nesnesini taklit eden yeni bir DataTransfer nesnesi oluştur
                    const dataTransfer = new DataTransfer();
                    files.forEach(file => dataTransfer.items.add(file));

                    // Input'un files özelliğini güncelle
                    this.$refs.fileInput.files = dataTransfer.files;
                    this.files = dataTransfer.files;

                    // Önizleme URL'lerini de güncelle
                    const urls = Array.from(this.previewUrls);
                    const movedUrl = urls.splice(evt.oldIndex, 1)[0];
                    urls.splice(evt.newIndex, 0, movedUrl);
                    this.previewUrls = urls;

                    // Eğer kapak fotoğrafı taşınan dosya ise, indeksini güncelle
                    if (this.coverPhotoIndex === evt.oldIndex) {
                        this.coverPhotoIndex = evt.newIndex;
                    }
                }
            });

            if (this.existingPhotos.length > 0) {
                new Sortable(this.$refs.existingContainer, {
                    animation: 150,
                    ghostClass: 'bg-blue-100',
                    onEnd: (evt) => {
                        // Mevcut fotoğrafların sırasını güncelle
                        const photos = Array.from(this.existingPhotos);
                        const moved = photos.splice(evt.oldIndex, 1)[0];
                        photos.splice(evt.newIndex, 0, moved);
                        this.existingPhotos = photos;

                        // Eğer kapak fotoğrafı taşınan dosya ise, indeksini güncelle
                        if (this.coverPhotoIndex === evt.oldIndex) {
                            this.coverPhotoIndex = evt.newIndex;
                        }
                    }
                });
            }
        }
    },

    removeFile(index) {
        const dt = new DataTransfer();
        const files = Array.from(this.files);

        for (let i = 0; i < files.length; i++) {
            if (i !== index) {
                dt.items.add(files[i]);
            }
        }

        this.$refs.fileInput.files = dt.files;
        this.files = dt.files;
        this.previewUrls.splice(index, 1);

        // Kapak fotoğrafı indeksini güncelle
        if (index === this.coverPhotoIndex) {
            this.coverPhotoIndex = 0;
        } else if (index < this.coverPhotoIndex) {
            this.coverPhotoIndex--;
        }
    },

    removeExistingPhoto(index) {
        this.existingPhotos.splice(index, 1);

        // Kapak fotoğrafı indeksini güncelle
        if (index === this.coverPhotoIndex) {
            this.coverPhotoIndex = 0;
        } else if (index < this.coverPhotoIndex) {
            this.coverPhotoIndex--;
        }
    },

    formatFileSize(size) {
        if (size < 1024) {
            return size + ' bytes';
        } else if (size < 1024 * 1024) {
            return (size / 1024).toFixed(2) + ' KB';
        } else {
            return (size / (1024 * 1024)).toFixed(2) + ' MB';
        }
    },

    setCoverPhoto(index, type = 'new') {
        if (type === 'new') {
            this.coverPhotoIndex = index;
        } else {
            this.coverPhotoIndex = index;
        }
    }
}" class="space-y-2">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    <div
        x-on:dragover.prevent="dragOver = true"
        x-on:dragleave.prevent="dragOver = false"
        x-on:drop.prevent="dragOver = false; files = $event.dataTransfer.files"
        :class="{'bg-blue-50 dark:bg-blue-900/20 border-blue-300 dark:border-blue-700': dragOver}"
        class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-dashed rounded-lg transition-colors duration-200 border-gray-300 dark:border-gray-600"
    >
        <div class="space-y-1 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                <label for="{{ $name }}" class="relative cursor-pointer bg-white dark:bg-slate-900 rounded-lg font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 focus-within:outline-none">
                    <span>Dosya yükle</span>
                    <input
                        id="{{ $name }}"
                        name="{{ $name }}{{ $multiple ? '[]' : '' }}"
                        type="file"
                        class="sr-only"
                        x-ref="fileInput"
                        x-on:change="files = $event.target.files"
                        {{ $multiple ? 'multiple' : '' }}
                        accept="{{ $accept }}"
                        {{ $required ? 'required' : '' }}
                    >
                </label>
                <p class="pl-1">veya sürükleyip bırakın</p>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $multiple ? 'En fazla ' . $maxFiles . ' adet ' : '' }}{{ $maxSize }}MB'a kadar {{ $accept == 'image/*' ? 'PNG, JPG, GIF' : $accept }} dosyalar
            </p>

            @if($help)
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $help }}</p>
            @endif
        </div>
    </div>

    <!-- Mevcut fotoğraflar -->
    <div x-show="existingPhotos.length > 0" class="mt-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Mevcut Fotoğraflar</h4>
        <div x-ref="existingContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <template x-for="(photo, index) in existingPhotos" :key="photo.id">
                <div class="relative group border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden dark:border-slate-700">
                    <div class="aspect-w-4 aspect-h-3">
                        <img :src="photo.url" :alt="photo.alt_text || 'Fotoğraf'" class="w-full h-full object-cover">
                    </div>
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 flex space-x-2">
                            <button type="button" @click="setCoverPhoto(index, 'existing')" class="p-1 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" :class="{'ring-2 ring-offset-2 ring-yellow-500': coverPhotoIndex === index}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </button>
                            <button type="button" @click="removeExistingPhoto(index)" class="p-1 bg-red-500 text-white rounded-full hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div x-show="coverPhotoIndex === index" class="absolute top-0 left-0 bg-yellow-500 text-white text-xs px-2 py-1 rounded-br-lg">
                        Kapak
                    </div>
                    <input type="hidden" :name="`existing_photos[${index}]`" :value="photo.id">
                </div>
            </template>
        </div>
    </div>

    <!-- Yeni yüklenen fotoğraflar önizleme -->
    <div x-show="previewUrls.length > 0" class="mt-4">
        <h4 class="text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Yeni Fotoğraflar</h4>
        <div x-ref="previewContainer" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <template x-for="(preview, index) in previewUrls" :key="index">
                <div class="relative group border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden dark:border-slate-700">
                    <div class="aspect-w-4 aspect-h-3">
                        <img :src="preview.url" :alt="preview.name" class="w-full h-full object-cover">
                    </div>
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 flex space-x-2">
                            <button type="button" @click="setCoverPhoto(index)" class="p-1 bg-blue-500 text-white rounded-full hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" :class="{'ring-2 ring-offset-2 ring-yellow-500': coverPhotoIndex === index}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </button>
                            <button type="button" @click="removeFile(index)" class="p-1 bg-red-500 text-white rounded-full hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 text-white text-xs p-1 truncate">
                        <span x-text="preview.name"></span> (<span x-text="preview.size"></span>)
                    </div>
                    <div x-show="coverPhotoIndex === index" class="absolute top-0 left-0 bg-yellow-500 text-white text-xs px-2 py-1 rounded-br-lg">
                        Kapak
                    </div>
                </div>
            </template>
        </div>
    </div>

    <input type="hidden" name="kapak_fotografi" x-model="coverPhotoIndex">

    @error($name)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
    <x-csp-script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js" />
    @endpush
@endonce
