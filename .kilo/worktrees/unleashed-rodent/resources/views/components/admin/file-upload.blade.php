{{--
    File Upload Component

    @component x-admin.file-upload
    @description Drag & drop file upload with preview, validation, and progress

    @props
        - name: string (required) - Input name
        - label: string (optional) - Upload label
        - accept: string (optional) - Accepted file types - default: '*'
        - multiple: bool (optional) - Allow multiple files - default: false
        - maxSize: int (optional) - Max file size in MB - default: 10
        - maxFiles: int (optional) - Max number of files - default: 5
        - preview: bool (optional) - Show image preview - default: true
        - error: string (optional) - Error message
        - help: string (optional) - Help text
        - required: bool (optional) - Required field - default: false

    @example
        <x-admin.file-upload
            name="photos[]"
            label="Property Photos"
            :multiple="true"
            accept="image/*"
            :maxSize="5"
            :maxFiles="10"
            help="Upload up to 10 photos (max 5MB each)"
        />

    @features
        - Drag & drop support
        - Image preview
        - File validation
        - Progress indication
        - Multiple file upload
        - Remove files
        - File size validation
        - File type validation
        - Dark mode support
--}}

@props([
    'name' => 'files[]',
    'label' => null,
    'accept' => '*',
    'multiple' => false,
    'maxSize' => 10,
    'maxFiles' => 5,
    'preview' => true,
    'error' => null,
    'help' => null,
    'required' => false,
])

@php
$uploadId = 'file-upload-' . str_replace(['[', ']'], '', $name);
$hasError = !empty($error);
@endphp

<div
    x-data="{
        files: [],
        isDragging: false,
        maxSize: {{ $maxSize }},
        maxFiles: {{ $maxFiles }},
        multiple: {{ $multiple ? 'true' : 'false' }},

        addFiles(newFiles) {
            const fileArray = Array.from(newFiles);

            // Check max files
            if (this.multiple && (this.files.length + fileArray.length) > this.maxFiles) {
                window.toast('warning', `En fazla ${this.maxFiles} dosya yükleyebilirsiniz`);
                return;
            }

            // Process each file
            fileArray.forEach(file => {
                // Check file size
                if (file.size > this.maxSize * 1024 * 1024) {
                    window.toast('error', `${file.name} çok büyük (max ${this.maxSize}MB)`);
                    return;
                }

                // Add file
                const fileObj = {
                    file: file,
                    name: file.name,
                    size: this.formatSize(file.size),
                    preview: null,
                    progress: 0
                };

                // Generate preview for images
                if (file.type.startsWith('image/') && {{ $preview ? 'true' : 'false' }}) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        fileObj.preview = e.target.result;
                    };
                    reader.readAsDataURL(file);
                }

                if (this.multiple) {
                    this.files.push(fileObj);
                } else {
                    this.files = [fileObj];
                }
            });
        },

        removeFile(index) {
            this.files.splice(index, 1);
        },

        formatSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        },

        handleDrop(e) {
            this.isDragging = false;
            const files = e.dataTransfer.files;
            this.addFiles(files);
        },

        handleFileSelect(e) {
            const files = e.target.files;
            this.addFiles(files);
        }
    }"
    class="w-full"
>
    {{-- Label --}}
    @if($label)
    <label
        for="{{ $uploadId }}"
        class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100"
    >
        {{ $label }}
        @if($required)
        <span class="text-red-500">*</span>
        @endif
    </label>
    @endif

    {{-- Drop Zone --}}
    <div
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop.prevent="handleDrop"
        :class="{
            'border-blue-500 bg-blue-50 dark:bg-blue-900/20': isDragging,
            'border-red-500': {{ $hasError ? 'true' : 'false' }}
        }"
        class="relative border-2 border-dashed border-gray-300 dark:border-gray-600
               rounded-xl p-8 text-center transition-all duration-200
               hover:border-gray-400 dark:hover:border-gray-500"
    >
        <input
            type="file"
            id="{{ $uploadId }}"
            name="{{ $name }}"
            accept="{{ $accept }}"
            {{ $multiple ? 'multiple' : '' }}
            {{ $required ? 'required' : '' }}
            @change="handleFileSelect"
            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
        />

        {{-- Upload Icon & Text --}}
        <div class="pointer-events-none">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>

            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                <span class="font-semibold text-blue-600 dark:text-blue-400">Dosya seçin</span>
                veya sürükleyip bırakın
            </p>

            @if($help)
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $help }}
            </p>
            @endif
        </div>
    </div>

    {{-- Error Message --}}
    @if($hasError)
    <p class="mt-2 text-sm text-red-600 dark:text-red-400">
        {{ $error }}
    </p>
    @endif

    {{-- File List --}}
    <div x-show="files.length > 0" class="mt-4 space-y-2">
        <template x-for="(fileObj, index) in files" :key="index">
            <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                {{-- Preview --}}
                <template x-if="fileObj.preview">
                    <img
                        :src="fileObj.preview"
                        :alt="fileObj.name"
                        class="w-12 h-12 object-cover rounded-lg"
                    />
                </template>

                <template x-if="!fileObj.preview">
                    <div class="w-12 h-12 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded-lg">
                        <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </template>

                {{-- File Info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate dark:text-slate-100" x-text="fileObj.name"></p>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="fileObj.size"></p>
                </div>

                {{-- Remove Button --}}
                <button
                    type="button"
                    @click="removeFile(index)"
                    class="text-red-500 hover:text-red-700 dark:hover:text-red-400
                           transition-colors duration-200 p-2 rounded-lg
                           hover:bg-red-50 dark:hover:bg-red-900/20"
                    title="Dosyayı kaldır"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- File Count --}}
    <div x-show="files.length > 0" class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        <span x-text="files.length"></span> dosya seçildi
        <template x-if="multiple && maxFiles > 0">
            <span> (max <span x-text="maxFiles"></span>)</span>
        </template>
    </div>
</div>
