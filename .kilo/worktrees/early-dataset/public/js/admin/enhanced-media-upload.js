/**
 * Enhanced Media Upload System - Context7 Standard
 *
 * 🎯 Amaç:
 * - Drag & drop fotoğraf yükleme
 * - Image preview with thumbnails
 * - Multiple file upload support
 * - Progress tracking
 * - File validation
 * - Resizing and optimization
 *
 * @version 1.0.0
 * @author Context7 Team
 */

class EnhancedMediaUploadSystem {
    constructor() {
        this.uploadContainer = null;
        this.fileInput = null;
        this.previewContainer = null;
        this.uploadedFiles = [];
        this.maxFiles = 20;
        this.maxFileSize = 10 * 1024 * 1024; // 10MB
        this.allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

        this.init();
    }

    init() {
        console.log('🚀 Enhanced Media Upload System initialized');

        this.createUploadInterface();
        this.attachEventListeners();
    }

    /**
     * Upload interface oluştur
     */
    createUploadInterface() {
        const mediaContainer = document.querySelector('#content-media .create-card');
        if (!mediaContainer) return;

        // Mevcut basit input'u gizle
        const existingInput = mediaContainer.querySelector('input[type="file"]');
        if (existingInput) {
            existingInput.style.display = 'none';
        }

        // Enhanced upload interface oluştur
        const uploadHTML = `
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3 dark:text-slate-300">
                    <i class="fas fa-images text-blue-600 mr-2"></i>Fotoğraflar (Sürükle & Bırak)
                </label>

                <!-- Drag & Drop Area -->
                <div id="enhanced-upload-area"
                     class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors cursor-pointer bg-gray-50 hover:bg-blue-50 dark:bg-slate-900">
                    <div class="upload-content">
                        <div class="mb-4">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-700 mb-2 dark:text-slate-300">Fotoğrafları Buraya Sürükleyin</h3>
                        <p class="text-sm text-gray-500 mb-4">veya dosya seçmek için tıklayın</p>
                        <button type="button" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-folder-open mr-2"></i>Dosya Seç
                        </button>
                        <p class="text-xs text-gray-400 mt-2">
                            Maksimum ${this.maxFiles} fotoğraf, dosya başına max 10MB (JPG, PNG, WebP)
                        </p>
                    </div>

                    <!-- Upload Progress -->
                    <div id="upload-progress" class="hidden mt-4">
                        <div class="bg-blue-200 rounded-full h-2">
                            <div id="upload-progress-bar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <p id="upload-status" class="text-sm text-gray-600 mt-2">Yükleniyor...</p>
                    </div>
                </div>

                <!-- Hidden File Input -->
                <input type="file" id="enhanced-file-input" name="fotograflar[]" multiple accept="image/*" class="hidden">

                <!-- Preview Area -->
                <div id="image-preview-container" class="mt-6 hidden">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-md font-medium text-gray-700 dark:text-slate-300">
                            <i class="fas fa-eye text-green-600 mr-2"></i>Seçilen Fotoğraflar (<span id="selected-count">0</span>)
                        </h4>
                        <button type="button" id="clear-all-images" class="text-red-600 hover:text-red-800 text-sm">
                            <i class="fas fa-trash mr-1"></i>Tümünü Temizle
                        </button>
                    </div>
                    <div id="image-previews" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <!-- Preview thumbnails will be inserted here -->
                    </div>
                </div>
            </div>
        `;

        // Mevcut fotoğraf input'unun yerine yeni interface'i ekle
        const existingLabel = mediaContainer.querySelector('label');
        if (existingLabel && existingLabel.textContent.includes('Fotoğraflar')) {
            const parentDiv = existingLabel.parentElement;
            parentDiv.innerHTML = uploadHTML;
        }

        // DOM referanslarını al
        this.uploadContainer = document.getElementById('enhanced-upload-area');
        this.fileInput = document.getElementById('enhanced-file-input');
        this.previewContainer = document.getElementById('image-preview-container');
    }

    /**
     * Event listeners ekle
     */
    attachEventListeners() {
        if (!this.uploadContainer || !this.fileInput) return;

        // Drag & Drop events
        this.uploadContainer.addEventListener('dragover', this.handleDragOver.bind(this));
        this.uploadContainer.addEventListener('dragleave', this.handleDragLeave.bind(this));
        this.uploadContainer.addEventListener('drop', this.handleDrop.bind(this));

        // Click to select files
        this.uploadContainer.addEventListener('click', () => {
            this.fileInput.click();
        });

        // File input change
        this.fileInput.addEventListener('change', (e) => {
            this.handleFiles(e.target.files);
        });

        // Clear all button
        const clearButton = document.getElementById('clear-all-images');
        if (clearButton) {
            clearButton.addEventListener('click', this.clearAllImages.bind(this));
        }
    }

    /**
     * Drag over handler
     */
    handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        this.uploadContainer.classList.add('border-blue-500', 'bg-blue-100');
        this.uploadContainer.classList.remove('border-gray-300', 'bg-gray-50 dark:bg-slate-900');
    }

    /**
     * Drag leave handler
     */
    handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        this.uploadContainer.classList.remove('border-blue-500', 'bg-blue-100');
        this.uploadContainer.classList.add('border-gray-300', 'bg-gray-50 dark:bg-slate-900');
    }

    /**
     * Drop handler
     */
    handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        this.uploadContainer.classList.remove('border-blue-500', 'bg-blue-100');
        this.uploadContainer.classList.add('border-gray-300', 'bg-gray-50 dark:bg-slate-900');

        const files = e.dataTransfer.files;
        this.handleFiles(files);
    }

    /**
     * Dosyaları işle
     */
    handleFiles(files) {
        const fileArray = Array.from(files);
        const validFiles = fileArray.filter((file) => this.validateFile(file));

        if (validFiles.length === 0) {
            this.showToast('Geçerli dosya bulunamadı!', 'error');
            return;
        }

        // Maksimum dosya sayısı kontrolü
        const totalFiles = this.uploadedFiles.length + validFiles.length;
        if (totalFiles > this.maxFiles) {
            this.showToast(`Maksimum ${this.maxFiles} fotoğraf yükleyebilirsiniz!`, 'error');
            return;
        }

        // Dosyaları işle ve preview oluştur
        validFiles.forEach((file) => {
            this.processFile(file);
        });

        this.updateFileInput();
        this.showToast(`${validFiles.length} fotoğraf başarıyla eklendi!`, 'success');
    }

    /**
     * Dosya validasyonu
     */
    validateFile(file) {
        // Dosya tipi kontrolü
        if (!this.allowedTypes.includes(file.type)) {
            this.showToast(`${file.name}: Desteklenmeyen dosya formatı!`, 'error');
            return false;
        }

        // Dosya boyutu kontrolü
        if (file.size > this.maxFileSize) {
            this.showToast(`${file.name}: Dosya boyutu çok büyük (max 10MB)!`, 'error');
            return false;
        }

        return true;
    }

    /**
     * Dosyayı işle ve preview oluştur
     */
    processFile(file) {
        const reader = new FileReader();
        const fileId = 'file_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

        reader.onload = (e) => {
            const fileData = {
                id: fileId,
                file: file,
                preview: e.target.result,
                name: file.name,
                size: file.size,
                type: file.type,
            };

            this.uploadedFiles.push(fileData);
            this.addPreviewThumbnail(fileData);
            this.updateCounters();
        };

        reader.readAsDataURL(file);
    }

    /**
     * Preview thumbnail ekle
     */
    addPreviewThumbnail(fileData) {
        if (!this.previewContainer) return;

        // Preview container'ı göster
        this.previewContainer.classList.remove('hidden');

        const previewsGrid = document.getElementById('image-previews');
        if (!previewsGrid) return;

        const thumbnailHTML = `
            <div id="thumb_${
                fileData.id
            }" class="relative group bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <div class="aspect-square">
                    <img src="${fileData.preview}" alt="${fileData.name}"
                         class="w-full h-full object-cover">
                </div>
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-all duration-200 flex items-center justify-center">
                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex space-x-2">
                        <button type="button" onclick="enhancedMediaUpload.viewFullImage('${
                            fileData.id
                        }')"
                                class="p-2 bg-white bg-opacity-90 rounded-full hover:bg-opacity-100 transition-all dark:bg-slate-900">
                            <i class="fas fa-eye text-gray-700 dark:text-slate-300"></i>
                        </button>
                        <button type="button" onclick="enhancedMediaUpload.removeFile('${
                            fileData.id
                        }')"
                                class="p-2 bg-red-500 bg-opacity-90 rounded-full hover:bg-opacity-100 transition-all">
                            <i class="fas fa-trash text-white"></i>
                        </button>
                    </div>
                </div>
                <div class="p-2 bg-white dark:bg-slate-900">
                    <p class="text-xs text-gray-600 truncate" title="${
                        fileData.name
                    }">${fileData.name}</p>
                    <p class="text-xs text-gray-400">${this.formatFileSize(fileData.size)}</p>
                </div>
            </div>
        `;

        previewsGrid.insertAdjacentHTML('beforeend', thumbnailHTML);
    }

    /**
     * Dosyayı kaldır
     */
    removeFile(fileId) {
        // Array'den dosyayı kaldır
        this.uploadedFiles = this.uploadedFiles.filter((file) => file.id !== fileId);

        // DOM'dan thumbnail'ı kaldır
        const thumbnail = document.getElementById(`thumb_${fileId}`);
        if (thumbnail) {
            thumbnail.remove();
        }

        this.updateCounters();
        this.updateFileInput();
        this.showToast('Fotoğraf kaldırıldı', 'info');

        // Preview container'ı gizle eğer dosya yoksa
        if (this.uploadedFiles.length === 0) {
            this.previewContainer.classList.add('hidden');
        }
    }

    /**
     * Tüm dosyaları temizle
     */
    clearAllImages() {
        if (this.uploadedFiles.length === 0) return;

        if (confirm('Tüm fotoğrafları kaldırmak istediğinizden emin misiniz?')) {
            this.uploadedFiles = [];

            const previewsGrid = document.getElementById('image-previews');
            if (previewsGrid) {
                previewsGrid.innerHTML = '';
            }

            this.previewContainer.classList.add('hidden');
            this.updateCounters();
            this.updateFileInput();
            this.showToast('Tüm fotoğraflar kaldırıldı', 'info');
        }
    }

    /**
     * Fotoğrafı büyük boyutta görüntüle
     */
    viewFullImage(fileId) {
        const fileData = this.uploadedFiles.find((file) => file.id === fileId);
        if (!fileData) return;

        // Modal oluştur
        const modalHTML = `
            <div id="image-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75" onclick="this.remove()">
                <div class="relative max-w-4xl max-h-full p-4">
                    <button onclick="document.getElementById('image-modal').remove()"
                            class="absolute top-2 right-2 text-white bg-black bg-opacity-50 rounded-full w-8 h-8 flex items-center justify-center hover:bg-opacity-75">
                        <i class="fas fa-times"></i>
                    </button>
                    <img src="${fileData.preview}" alt="${
                        fileData.name
                    }" class="max-w-full max-h-full object-contain rounded-lg">
                    <div class="absolute bottom-2 left-2 bg-black bg-opacity-75 text-white px-3 py-2 rounded">
                        <p class="text-sm">${fileData.name}</p>
                        <p class="text-xs opacity-75">${this.formatFileSize(fileData.size)}</p>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    /**
     * File input'u güncelle
     */
    updateFileInput() {
        // DataTransfer ile gerçek files array oluştur
        const dataTransfer = new DataTransfer();

        this.uploadedFiles.forEach((fileData) => {
            dataTransfer.items.add(fileData.file);
        });

        this.fileInput.files = dataTransfer.files;
    }

    /**
     * Sayaçları güncelle
     */
    updateCounters() {
        const countElement = document.getElementById('selected-count');
        if (countElement) {
            countElement.textContent = this.uploadedFiles.length;
        }
    }

    /**
     * Dosya boyutunu formatla
     */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Toast notification göster
     */
    showToast(message, type = 'info') {
        if (typeof window.showToast === 'function') {
            window.showToast(type, message);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    /**
     * Progress bar göster/gizle
     */
    showProgress() {
        const progressElement = document.getElementById('upload-progress');
        if (progressElement) {
            progressElement.classList.remove('hidden');
        }
    }

    hideProgress() {
        const progressElement = document.getElementById('upload-progress');
        if (progressElement) {
            progressElement.classList.add('hidden');
        }
    }

    /**
     * Yüklenen dosyaları getir
     */
    getUploadedFiles() {
        return this.uploadedFiles;
    }

    /**
     * Upload stats
     */
    getStats() {
        const totalSize = this.uploadedFiles.reduce((sum, file) => sum + file.size, 0);

        return {
            totalFiles: this.uploadedFiles.length,
            totalSize: this.formatFileSize(totalSize),
            maxFiles: this.maxFiles,
            remaining: this.maxFiles - this.uploadedFiles.length,
        };
    }
}

// Global namespace
window.EnhancedMediaUploadSystem = EnhancedMediaUploadSystem;

// Auto-initialize on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('#content-media')) {
        window.enhancedMediaUpload = new EnhancedMediaUploadSystem();
    }
});
