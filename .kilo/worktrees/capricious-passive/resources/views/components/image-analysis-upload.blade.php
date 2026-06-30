{{-- Context7 Image Analysis Upload Component --}}
<div x-data="imageAnalysisComponent()" class="image-analysis-container">
    <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-slate-900">
        <h3 class="text-lg font-semibold mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            AI Görsel Analizi
        </h3>

        <!-- Upload Area -->
        <div
            @drop.prevent="handleDrop"
            @dragover.prevent
            @dragenter.prevent
            :class="{
                'upload-area': true,
                'drag-over': isDragOver,
                'uploading': isUploading
            }"
            class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center transition-colors hover:border-blue-400"
        >
            <input
                type="file"
                @change="handleFileSelect"
                multiple
                accept="image/*"
                class="hidden"
                ref="fileInput"
            >

            <div x-show="!isUploading && !analysisResults">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="text-lg font-medium text-gray-700 mb-2 dark:text-slate-300">
                    Emlak fotoğraflarını sürükleyin veya seçin
                </p>
                <p class="text-sm text-gray-500 mb-4">
                    JPG, PNG, WebP formatları desteklenir (Max 10MB)
                </p>
                <button
                    @click="$refs.fileInput.click()"
                    class="inline-flex items-center px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Fotoğraf Seç
                </button>
            </div>

            <!-- Uploading State -->
            <div x-show="isUploading" class="space-y-4">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
                <p class="text-lg font-medium text-blue-600">AI analiz ediyor...</p>
                <p class="text-sm text-gray-500" x-text="uploadProgress"></p>
            </div>
        </div>

        <!-- Analysis Results -->
        <div x-show="analysisResults" x-transition class="mt-6 space-y-6">
            <!-- Property Type Detection -->
            <div class="bg-gray-50 rounded-lg p-4 dark:bg-slate-900">
                <h4 class="font-semibold text-gray-800 mb-2 flex items-center dark:text-slate-200">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Tespit Edilen Emlak Tipi
                </h4>
                <div class="flex items-center space-x-2">
                    <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium"
                          x-text="analysisResults.detected_type || 'Bilinmiyor'"></span>
                    <span class="text-sm text-gray-600"
                          x-text="analysisResults.confidence_score ? '(Güven: ' + Math.round(analysisResults.confidence_score * 100) + '%)' : ''"></span>
                </div>
            </div>

            <!-- Detected Features -->
            <div class="bg-gray-50 rounded-lg p-4 dark:bg-slate-900">
                <h4 class="font-semibold text-gray-800 mb-3 flex items-center dark:text-slate-200">
                    <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    Tespit Edilen Özellikler
                </h4>
                <div class="flex flex-wrap gap-2">
                    <template x-for="feature in analysisResults.features || []" :key="feature">
                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm"
                              x-text="feature"></span>
                    </template>
                </div>
            </div>

            <!-- Quality Analysis -->
            <div class="bg-gray-50 rounded-lg p-4 dark:bg-slate-900">
                <h4 class="font-semibold text-gray-800 mb-3 flex items-center dark:text-slate-200">
                    <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    Kalite Analizi
                </h4>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Genel Durum</span>
                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-sm dark:bg-slate-900 dark:text-slate-200"
                              x-text="analysisResults.condition || 'Bilinmiyor'"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Mimari Tarz</span>
                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-sm dark:bg-slate-900 dark:text-slate-200"
                              x-text="analysisResults.style || 'Bilinmiyor'"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Boyut Tahmini</span>
                        <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-sm dark:bg-slate-900 dark:text-slate-200"
                              x-text="analysisResults.size_estimation || 'Bilinmiyor'"></span>
                    </div>
                </div>
            </div>

            <!-- AI Generated Description -->
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4">
                <h4 class="font-semibold text-gray-800 mb-3 flex items-center dark:text-slate-200">
                    <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    AI Üretilen Açıklama
                </h4>
                <div class="bg-white rounded-lg p-4 border dark:bg-slate-900">
                    <p class="text-gray-700 leading-relaxed dark:text-slate-300" x-text="analysisResults.description || 'Açıklama üretilemedi'"></p>
                </div>
                <div class="mt-3 flex space-x-2">
                    <button
                        @click="copyDescription()"
                        class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded text-sm transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        Kopyala
                    </button>
                    <button
                        @click="useDescription()"
                        class="inline-flex items-center px-3 py-1 bg-green-500 hover:bg-green-600 text-white rounded text-sm transition-colors"
                    >
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Kullan
                    </button>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center pt-4 border-t">
                <button
                    @click="resetAnalysis()"
                    class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg transition-colors"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Yeni Analiz
                </button>

                <div class="text-sm text-gray-500">
                    İşlem süresi: <span x-text="processingTime"></span>ms
                </div>
            </div>
        </div>

        <!-- Error Display -->
        <div x-show="error" x-transition class="mt-4 p-4 bg-red-100 border border-red-300 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-red-800" x-text="error"></span>
            </div>
        </div>
    </div>
</div>

<script>
function imageAnalysisComponent() {
    return {
        isDragOver: false,
        isUploading: false,
        analysisResults: null,
        error: '',
        uploadProgress: '',
        processingTime: 0,

        init() {
            // Component initialization
        },

        handleDrop(e) {
            this.isDragOver = false;
            const files = Array.from(e.dataTransfer.files);
            this.processFiles(files);
        },

        handleFileSelect(e) {
            const files = Array.from(e.target.files);
            this.processFiles(files);
        },

        async processFiles(files) {
            if (files.length === 0) return;

            // Validate files
            const validFiles = files.filter(file => {
                const isValidType = file.type.startsWith('image/');
                const isValidSize = file.size <= 10 * 1024 * 1024; // 10MB

                if (!isValidType) {
                    this.error = 'Sadece resim dosyaları kabul edilir';
                    return false;
                }

                if (!isValidSize) {
                    this.error = 'Dosya boyutu 10MB\'dan küçük olmalıdır';
                    return false;
                }

                return true;
            });

            if (validFiles.length === 0) return;

            this.isUploading = true;
            this.error = '';
            this.analysisResults = null;
            const startTime = Date.now();

            try {
                // Convert files to base64
                const base64Files = await Promise.all(
                    validFiles.map(file => this.fileToBase64(file))
                );

                this.uploadProgress = 'Resimler analiz ediliyor...';

                // Call AI analysis API
                const response = await fetch('/api/ai/image-analysis', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        images: base64Files,
                        property_type: null, // Auto-detect
                        additional_data: {}
                    })
                });

                const data = await response.json();

                if (data.success) {
                    this.analysisResults = data;
                    this.processingTime = Date.now() - startTime;
                    this.showNotification('Görsel analiz tamamlandı! ✅', 'success');
                } else {
                    this.error = data.message || 'Görsel analiz başarısız';
                }
            } catch (error) {
                this.error = 'Analiz hatası: ' + error.message;
                console.error('Image analysis error:', error);
            } finally {
                this.isUploading = false;
                this.uploadProgress = '';
            }
        },

        fileToBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => resolve(reader.result);
                reader.onerror = error => reject(error);
            });
        },

        copyDescription() {
            if (this.analysisResults?.description) {
                navigator.clipboard.writeText(this.analysisResults.description);
                this.showNotification('Açıklama kopyalandı! 📋', 'success');
            }
        },

        useDescription() {
            // Emit event to parent component or fill form field
            this.$dispatch('use-description', {
                description: this.analysisResults?.description
            });
            this.showNotification('Açıklama kullanıldı! ✅', 'success');
        },

        resetAnalysis() {
            this.analysisResults = null;
            this.error = '';
            this.processingTime = 0;
            this.$refs.fileInput.value = '';
        },

        showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
                type === 'success' ? 'bg-green-100 border-green-500 text-green-800' :
                type === 'error' ? 'bg-red-100 border-red-500 text-red-800' :
                'bg-blue-100 border-blue-500 text-blue-800'
            } border-l-4`;

            notification.innerHTML = `
                <div class="flex items-center">
                    <span>${message}</span>
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }
}
</script>

<style>
.image-analysis-container {
    max-width: 800px;
}

.upload-area.drag-over {
    border-color: #3b82f6;
    background-color: #eff6ff;
}

.upload-area.uploading {
    pointer-events: none;
    opacity: 0.7;
}

.upload-area:hover {
    border-color: #3b82f6;
    background-color: #f8fafc;
}
</style>
