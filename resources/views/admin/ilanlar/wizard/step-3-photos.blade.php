@php
    use App\Helpers\FormStandards;
@endphp

{{-- STEP 2: FOTOĞRAF VE VİDEO --}}
<div class="space-y-6" x-data="photoWizardStep2()">
    <div class="mb-6">
        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
            📸 Fotoğraf ve Video
        </h3>
        <p class="{{ FormStandards::help() }} !text-sm">İlanınız için fotoğraf ve video ekleyin</p>
    </div>

    {{-- Fotoğraflar --}}
    <div
        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-lg p-6 dark:border-slate-700">
        <label class="flex items-center justify-between mb-4 text-gray-900 dark:text-white dark:text-slate-100">
            <span class="wizard-field-label">
                <svg class="w-5 h-5 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                İlan Fotoğrafları <span class="text-red-500">*</span>
            </span>
            <span class="text-xs text-slate-500 dark:text-slate-400 !mt-0 uppercase tracking-wider font-bold">En az 3
                fotoğraf önerilir (Maks: 10)</span>
        </label>

        <div @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false"
            @drop.prevent="dragging = false; handleFiles($event.dataTransfer.files)"
            :class="dragging ? 'border-blue-500 dark:border-blue-400 bg-blue-50 dark:bg-blue-900/10' :
                'border-gray-300 dark:border-gray-600'"
            class="border-2 border-dashed rounded-2xl p-10 text-center transition-all group relative bg-gray-50/50 dark:bg-gray-900/20">

            <input type="file" name="fotograflar[]" id="fotograflar" multiple accept="image/*" class="hidden"
                x-ref="fileInput" @change="handleFiles($event.target.files)">

            <div class="space-y-4">
                <div class="flex flex-col items-center gap-4">
                    <div
                        class="w-16 h-16 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-gray-900 dark:text-white font-medium mb-1 dark:text-slate-100">
                            Fotoğrafları sürükleyip bırakın veya
                        </p>
                        <label for="fotograflar"
                            class="text-blue-600 dark:text-blue-400 font-bold cursor-pointer hover:underline">
                            Dosya Seçin
                        </label>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        JPG, PNG, WEBP formatları desteklenir (Maks: 10MB/fotoğraf)
                    </p>
                </div>
            </div>
        </div>

        {{-- Fotoğraf Önizleme Grid --}}
        <div x-show="photos.length > 0" class="mt-6">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4" id="photo-preview-grid">
                <template x-for="(photo, index) in photos" :key="index">
                    <div class="relative group">
                        <div
                            class="relative overflow-hidden rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                            <img :src="photo.preview" :alt="`Fotoğraf ${index + 1}`"
                                class="w-full h-32 object-cover transition-transform duration-300 group-hover:scale-110">

                            {{-- Overlay Controls --}}
                            <div
                                class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col items-center justify-center gap-2">
                                <button type="button" @click="analyzePhoto(index)"
                                    class="bg-blue-600 text-white px-3 py-1.5 text-xs rounded-full shadow-lg hover:bg-blue-700 flex items-center gap-1.5 transition-all active:scale-95">
                                    <div x-show="!photo.analyzing" class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                        <span>Cortex Analiz</span>
                                    </div>
                                    <div x-show="photo.analyzing" class="flex items-center gap-1.5">
                                        <svg class="animate-spin h-3 w-3 text-white" fill="none"
                                            viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                            </path>
                                        </svg>
                                        <span>Analiz ediliyor...</span>
                                    </div>
                                </button>
                                <button type="button" @click="removePhoto(index)"
                                    class="bg-red-600 text-white px-3 py-1.5 text-xs rounded-full shadow-lg hover:bg-red-700 transition-all active:scale-95">
                                    Kaldır
                                </button>
                            </div>

                            {{-- Index Badge --}}
                            <div class="absolute top-2 left-2 bg-black/60 text-white text-[10px] px-1.5 py-0.5 rounded font-bold"
                                x-text="`#${index + 1}`"></div>
                        </div>

                        {{-- Analysis Results --}}
                        <div x-show="photo.analysis"
                            class="mt-2 p-2 bg-blue-50 dark:bg-blue-900/10 rounded border border-blue-100 dark:border-blue-800 text-[10px]">
                            <div class="flex justify-between items-center mb-1">
                                <span class="font-bold text-gray-700 dark:text-slate-200 dark:text-slate-300"
                                    x-text="photo.analysis?.room_type || 'Bilinmiyor'"></span>
                                <span
                                    class="bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-200 px-1.5 py-0.5 rounded font-bold"
                                    x-text="`Puan: ${photo.analysis?.quality_score || 0}/10`"
                                    :class="(photo.analysis?.quality_score || 0) > 7 ? 'text-green-600' : 'text-blue-600'"></span>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                <template x-for="feature in (photo.analysis?.detected_features || []).slice(0, 3)"
                                    :key="feature">
                                    <span
                                        class="bg-white dark:bg-slate-900 border border-blue-200 dark:border-blue-700 px-1 rounded text-blue-600 dark:text-blue-400"
                                        x-text="feature"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        {{-- 🆕 Phase 6: Bulk AI Analysis Button --}}
        <div x-show="photos.length > 0" class="mt-6 flex justify-center">
            <button type="button" @click="analyzeAllPhotos()" :disabled="aiAnalyzing"
                :class="aiAnalyzing ? 'opacity-75 cursor-not-allowed' : 'hover:shadow-xl  active:scale-95'"
                class="bg-gradient-to-r from-blue-600 to-purple-600 text-white px-8 py-4 rounded-xl font-bold text-lg shadow-lg transition-all flex items-center gap-3">
                <div x-show="!aiAnalyzing" class="flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    <span>Tüm Fotoğrafları AI ile Analiz Et (Cortex)</span>
                </div>
                <div x-show="aiAnalyzing" class="flex items-center gap-3">
                    <svg class="animate-spin h-6 w-6 text-white" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                            stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    <span>Analiz ediliyor...</span>
                </div>
            </button>
        </div>

        {{-- 🆕 Phase 6: AI Suggestions Display --}}
        <div x-show="aiSuggestions.length > 0" x-transition
            class="mt-6 bg-gradient-to-br from-blue-50 to-purple-50 dark:from-blue-900/10 dark:to-purple-900/10 rounded-xl border border-blue-200 dark:border-blue-800 p-6">
            <h4
                class="text-lg font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
                <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                AI Önerileri
                <span class="text-sm font-normal text-gray-600 dark:text-gray-400">(<span
                        x-text="aiSuggestions.length"></span> öneri bulundu)</span>
            </h4>

            {{-- Tabs --}}
            <div class="flex gap-2 mb-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <button @click="activeTab = 'auto'"
                    :class="activeTab === 'auto' ? 'border-green-500 text-green-600 dark:text-green-400' :
                        'border-transparent text-gray-600 dark:text-gray-400'"
                    class="px-4 py-2 border-b-2 font-medium transition-colors flex items-center gap-2">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Otomatik Uygulandı
                    </span>
                    <span
                        class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-0.5 rounded-full text-xs font-bold"
                        x-text="autoApplySuggestions.length"></span>
                </button>
                <button @click="activeTab = 'manual'"
                    :class="activeTab === 'manual' ? 'border-blue-500 text-blue-600 dark:text-blue-400' :
                        'border-transparent text-gray-600 dark:text-gray-400'"
                    class="px-4 py-2 border-b-2 font-medium transition-colors flex items-center gap-2">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                        </svg>
                        Önerilenler
                    </span>
                    <span
                        class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded-full text-xs font-bold"
                        x-text="manualSuggestions.length"></span>
                </button>
            </div>

            {{-- Auto Apply Tab --}}
            <div x-show="activeTab === 'auto'" class="space-y-3">
                <div x-show="autoApplySuggestions.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Otomatik uygulanan öneri yok</p>
                </div>
                <template x-for="suggestion in autoApplySuggestions" :key="suggestion.slug">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-lg border border-green-200 dark:border-green-800 p-4 shadow-sm dark:shadow-none">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900 dark:text-white dark:text-slate-100"
                                        x-text="suggestion.slug"></span>
                                    <span
                                        class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 px-2 py-0.5 rounded-full text-xs font-bold">
                                        ✓ AUTO
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1"
                                    x-text="suggestion.reason || 'Sebep belirtilmemiş'"></p>
                            </div>
                            <button @click="dismissSuggestion(suggestion.slug)"
                                class="text-gray-400 hover:text-red-500 transition-colors" title="Reddet">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mt-2">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-600 dark:text-gray-400">Güven: <span
                                        x-text="Math.round(suggestion.confidence * 100) + '%'"></span></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full transition-all"
                                    :style="`width: ${suggestion.confidence * 100}%`"></div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Manual Suggestions Tab --}}
            <div x-show="activeTab === 'manual'" class="space-y-3">
                <div x-show="manualSuggestions.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>Tüm öneriler uygulandı veya reddedildi</p>
                </div>
                <template x-for="suggestion in manualSuggestions" :key="suggestion.slug">
                    <div
                        class="bg-white dark:bg-slate-900 rounded-lg border border-blue-200 dark:border-blue-800 p-4 shadow-sm dark:shadow-none">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-900 dark:text-white dark:text-slate-100"
                                        x-text="suggestion.slug"></span>
                                    <span
                                        class="bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 px-2 py-0.5 rounded-full text-xs font-bold">
                                        ÖNERİ
                                    </span>
                                </div>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1"
                                    x-text="suggestion.reason || 'Sebep belirtilmemiş'"></p>
                            </div>
                        </div>
                        <div class="mt-2 mb-3">
                            <div class="flex items-center justify-between text-xs mb-1">
                                <span class="text-gray-600 dark:text-gray-400">Güven: <span
                                        x-text="Math.round(suggestion.confidence * 100) + '%'"></span></span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full transition-all"
                                    :style="`width: ${suggestion.confidence * 100}%`"></div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="applyFeatureToWizard(suggestion.slug)"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Uygula
                            </button>
                            <button @click="dismissSuggestion(suggestion.slug)"
                                class="inline-flex items-center justify-center gap-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-slate-200 px-3 py-2 rounded-lg text-sm font-medium transition-colors dark:text-slate-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Reddet
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
<script>
    function photoWizardStep2() {
        return {
            dragging: false,
            photos: [],
            uploading: false,

            // 🆕 Phase 6: Bulk AI Analysis State
            aiAnalyzing: false,
            aiError: null,
            aiSuggestions: [],
            appliedSuggestions: [],
            dismissedSuggestions: [],
            activeTab: 'auto',
            aiRequestId: null,
            aiSuggestionsLoadedAt: null,
            context: null,

            init() {
                // Listen for SSOT data
                window.addEventListener('wizard-context-applied', (e) => {
                    this.context = e.detail.context;
                    console.info('[WIZARD] context + component: PhotoWizard Step2 syncing from SSOT');
                });

                // ✅ Recovery-C Fix: SSOT — rebuild Alpine photos from event files (single write authority)
                // ilanWizard.initPhotoUpload() writes to native input, dispatches this event.
                // Alpine is the sole source of truth for preview rendering.
                window.addEventListener('wizard:photos-updated', (e) => {
                    const incoming = Array.from(e.detail.files || []);
                    window.__wizardUploadedPhotos = incoming;
                    if (incoming.length === 0) {
                        // Silme durumu: tüm fotoları temizle
                        this.photos = [];
                        return;
                    }
                    // Rebuild photos[] from incoming File list — no name-dedup merge,
                    // so Alpine count always equals native input count (invariant proof).
                    this.photos = [];
                    incoming.forEach(file => {
                        const reader = new FileReader();
                        reader.onload = (ev) => {
                            this.photos.push({
                                file: file,
                                preview: ev.target.result,
                                name: file.name,
                                size: file.size,
                                analyzing: false,
                                analysis: null
                            });
                        };
                        reader.readAsDataURL(file);
                    });
                });
            },
            // 🆕 Computed Properties
            get autoApplySuggestions() {
                return this.aiSuggestions.filter(s =>
                    s.auto_apply && !this.dismissedSuggestions.includes(s.slug)
                );
            },

            get manualSuggestions() {
                return this.aiSuggestions.filter(s =>
                    s.suggested &&
                    !this.appliedSuggestions.includes(s.slug) &&
                    !this.dismissedSuggestions.includes(s.slug)
                );
            },

            handleFiles(files) {
                // ✅ Recovery-C Fix: Reentrancy guard — fileInput.files = dt.files
                // programmatically triggers a second @change event in Chromium/Alpine,
                // causing handleFiles to be called twice and doubling the photo count.
                // Guard prevents the re-entrant call from processing.
                if (this._handlingFiles) return;
                this._handlingFiles = true;
                try {
                    const fileInput = this.$refs.fileInput;
                    const fileArray = Array.from(files).filter(file => {
                        return (file.type && file.type.startsWith('image/')) || /\.(jpe?g|png|webp|gif|bmp|heic|avif)$/i.test(file.name);
                    });
                    if (fileArray.length === 0) return;

                    // ✅ Recovery-C Fix: SSOT write — update native input, dispatch event.
                    // Alpine event listener rebuilds photos[] from event files (single path).
                    if (fileInput) {
                        const isNativeInputChange = (files === fileInput.files);
                        let combined = fileArray;
                        if (!isNativeInputChange) {
                            const existing = Array.from(fileInput.files || []);
                            combined = [...existing, ...fileArray];
                            try {
                                const dt = new DataTransfer();
                                combined.forEach(f => dt.items.add(f));
                                fileInput.files = dt.files;
                            } catch (e) {
                                console.warn('DataTransfer error:', e);
                            }
                        }

                        window.__wizardUploadedPhotos = Array.from(fileInput.files && fileInput.files.length > 0 ? fileInput.files : combined);

                        window.dispatchEvent(new CustomEvent('wizard:photos-updated', {
                            detail: {
                                files: window.__wizardUploadedPhotos
                            }
                        }));
                    }
                } finally {
                    this._handlingFiles = false;
                }
            },

            removePhoto(index) {
                // ✅ Recovery-C Fix: SSOT write — update native input, dispatch event.
                // Alpine event listener rebuilds photos[] reactively.
                const fileInput = this.$refs.fileInput;
                if (fileInput) {
                    const files = Array.from(fileInput.files || window.__wizardUploadedPhotos || []);
                    files.splice(index, 1);
                    try {
                        const dt = new DataTransfer();
                        files.forEach(f => dt.items.add(f));
                        fileInput.files = dt.files;
                    } catch (e) {
                        console.warn('DataTransfer error on remove:', e);
                    }
                    window.__wizardUploadedPhotos = files;
                    window.dispatchEvent(new CustomEvent('wizard:photos-updated', {
                        detail: {
                            files
                        }
                    }));
                }
            },

            async analyzePhoto(index) {
                const photo = this.photos[index];
                if (!photo || photo.analyzing) return;

                photo.analyzing = true;
                try {
                    const response = await fetch('{{ route('api.admin.ai.analyze-image') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            image: photo.preview.split(',')[1],
                            context: {
                                kategori: document.getElementById('ana_kategori_id')?.value,
                                yayin_tipi: document.getElementById('junction_id')?.value
                            }
                        })
                    });
                    const result = await response.json();
                    if (result.success) {
                        photo.analysis = result.data;
                    } else {
                        alert('Analiz başarısız: ' + (result.message || 'Bilinmeyen hata'));
                    }
                } catch (e) {
                    console.error('Photo analysis error:', e);
                    alert('Bağlantı hatası oluştu.');
                } finally {
                    photo.analyzing = false;
                }
            },

            // 🆕 Bulk AI Analysis
            async analyzeAllPhotos() {
                if (this.photos.length === 0) {
                    alert('Lütfen önce fotoğraf yükleyin');
                    return;
                }

                this.aiAnalyzing = true;
                this.aiError = null;
                this.aiRequestId = 'req_' + Math.random().toString(36).substr(2, 9);

                try {
                    const kategoriId = document.getElementById('ana_kategori_id')?.value;
                    const yayinTipiId = document.getElementById('junction_id')?.value;

                    if (!kategoriId || !yayinTipiId) {
                        alert('Lütfen önce kategori ve yayın tipi seçin');
                        this.aiAnalyzing = false;
                        return;
                    }

                    const response = await fetch('/api/v1/wizard/analyze-images', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'X-Request-ID': this.aiRequestId
                        },
                        body: JSON.stringify({
                            images: this.photos.map(p => p.name),
                            category_id: parseInt(kategoriId),
                            junction_id: parseInt(yayinTipiId)
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        this.aiSuggestions = result.data.suggestions || [];
                        this.aiSuggestionsLoadedAt = Date.now();

                        // Auto-apply features
                        this.autoApplySuggestions.forEach(suggestion => {
                            this.applyFeatureToWizard(suggestion.slug, true);
                        });

                        console.log('✅ AI Analiz tamamlandı:', this.aiSuggestions.length, 'öneri');
                    } else {
                        this.aiError = result.message || 'Analiz başarısız';
                        alert(this.aiError);
                    }
                } catch (error) {
                    console.error('AI Analysis Error:', error);
                    this.aiError = 'Bağlantı hatası oluştu';
                    alert(this.aiError);
                } finally {
                    this.aiAnalyzing = false;
                }
            },

            // 🆕 Apply Feature to Wizard
            applyFeatureToWizard(slug, isAutoApply = false) {
                // ✅ Recovery-C Fix: Match actual field naming: features[slug] not features[]
                const featureInput = document.querySelector(`input[name="features[${slug}]"]`);

                if (!featureInput) {
                    console.warn('⚠️ UPS Guard: Feature not in template, skipping:', slug);
                    return false;
                }

                // Boolean checkbox: check it if not already checked
                if (featureInput.type === 'checkbox') {
                    if (!featureInput.checked) {
                        featureInput.checked = true;
                        featureInput.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                        if (!isAutoApply) {
                            this.appliedSuggestions.push(slug);
                            this.logTelemetry(slug, 'user_applied');
                        }
                        console.log('✅ Feature checked:', slug);
                        return true;
                    }
                    return false;
                }

                // For text/select/number: if it has a value already, skip
                // (AI suggestion sets a meaningful value — if empty, set a default)
                if (!featureInput.value || featureInput.value.trim() === '') {
                    featureInput.value = '1';
                    featureInput.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                    if (!isAutoApply) {
                        this.appliedSuggestions.push(slug);
                        this.logTelemetry(slug, 'user_applied');
                    }
                    console.log('✅ Feature applied:', slug);
                    return true;
                }

                return false;
            },

            // 🆕 Dismiss Suggestion
            dismissSuggestion(slug) {
                this.dismissedSuggestions.push(slug);
                this.logTelemetry(slug, 'dismissed');
                console.log('❌ Feature dismissed:', slug);
            },

            // 🆕 Phase 7: Telemetry logging
            async logTelemetry(slug, aksiyon) {
                const suggestion = this.aiSuggestions.find(s => s.slug === slug);
                if (!suggestion) return;

                try {
                    await fetch('/api/v1/wizard/telemetry/feature-action', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            kategori_id: parseInt(document.getElementById('ana_kategori_id')
                                ?.value),
                            junction_id: parseInt(document.getElementById('junction_id')?.value),
                            feature_slug: slug,
                            confidence: suggestion.confidence,
                            source_tipi: suggestion.source || 'image',
                            aksiyon: aksiyon,
                            neden: suggestion.reason,
                            neden_detay: suggestion.explainability_detail || null,
                            istek_id: this.aiRequestId,
                            etkilesim_suresi_ms: this.aiSuggestionsLoadedAt ? (Date.now() - this
                                .aiSuggestionsLoadedAt) : 0,
                            deney_id: suggestion.deney_id || null,
                            deney_varyasyon_anahtari: suggestion.deney_varyasyon_anahtari || null
                        })
                    });
                } catch (error) {
                    console.error('Telemetry logging failed:', error);
                }
            }
        };
    }
</script>
