/**
 * Centralized Photo Handler for Listing Creation Wizard
 * Context7: Merkezi fotoğraf yönetimi - DRY prensibi
 *
 * Bu modül wizard form'da fotoğraf yükleme, önizleme ve yönetimini merkezi olarak yönetir.
 * Duplicate kodları önler ve bakımı kolaylaştırır.
 */

class PhotoHandler {
    constructor(options = {}) {
        this.config = {
            maxSize: options.maxSize || 5 * 1024 * 1024, // 5MB
            maxPhotos: options.maxPhotos || 10,
            allowedTypes: options.allowedTypes || ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            previewContainerId: options.previewContainerId || 'photo-preview',
            fileInputId: options.fileInputId || 'fotograflar',
            uploadAreaId: options.uploadAreaId || 'photo-upload-area',
            ...options
        };

        this.files = [];
        this.initialized = false;
    }

    /**
     * Initialize photo handler
     * @param {HTMLElement} container - Container element (optional, will find if not provided)
     */
    init(container = null) {
        if (this.initialized) {
            console.warn('⚠️ PhotoHandler zaten başlatılmış');
            return;
        }

        const uploadArea = container?.querySelector(`#${this.config.uploadAreaId}`) ||
                          document.getElementById(this.config.uploadAreaId);
        const fileInput = container?.querySelector(`#${this.config.fileInputId}`) ||
                         document.getElementById(this.config.fileInputId);

        if (!uploadArea || !fileInput) {
            console.warn('⚠️ PhotoHandler: Upload area veya file input bulunamadı');
            return;
        }

        this.setupDragAndDrop(uploadArea);
        this.setupClickToUpload(uploadArea, fileInput);
        this.initialized = true;

        if (window.DEBUG_MODE) {
            console.log('✅ PhotoHandler initialized');
        }
    }

    /**
     * Setup drag and drop functionality
     */
    setupDragAndDrop(uploadArea) {
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.add('drag-over');
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('drag-over');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove('drag-over');

            const files = Array.from(e.dataTransfer.files).filter(file =>
                file.type.startsWith('image/')
            );

            if (files.length > 0) {
                this.handleFiles(files);
            } else {
                this.showError('Lütfen geçerli bir resim dosyası seçin');
            }
        });
    }

    /**
     * Setup click to upload functionality
     */
    setupClickToUpload(uploadArea, fileInput) {
        uploadArea.addEventListener('click', (e) => {
            if (e.target.closest('label') || e.target.closest('button')) return;
            fileInput.click();
        });

        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length > 0) {
                this.handleFiles(Array.from(e.target.files));
            }
        });
    }

    /**
     * Handle selected files
     * @param {File[]} files - Array of file objects
     */
    handleFiles(files) {
        // Validate files
        const validFiles = this.validateFiles(files);
        if (validFiles.length === 0) {
            return;
        }

        // Check photo limit
        const fileInput = document.getElementById(this.config.fileInputId);
        if (!fileInput) return;

        const existingFiles = Array.from(fileInput.files || []);
        const totalFiles = existingFiles.length + validFiles.length;

        if (totalFiles > this.config.maxPhotos) {
            const allowedCount = this.config.maxPhotos - existingFiles.length;
            if (allowedCount > 0) {
                this.showWarning(
                    `Maksimum ${this.config.maxPhotos} fotoğraf yükleyebilirsiniz. Sadece ilk ${allowedCount} fotoğraf eklenecek.`
                );
                validFiles.splice(allowedCount);
            } else {
                this.showError(
                    `Maksimum ${this.config.maxPhotos} fotoğraf yükleyebilirsiniz. Lütfen mevcut fotoğraflardan bazılarını silin.`
                );
                return;
            }
        }

        // Add files to input
        const newFiles = [...existingFiles, ...validFiles];
        this.updateFileInput(newFiles);

        // Update preview
        this.updatePreview(newFiles);

        // Show success message
        this.showSuccess(`${validFiles.length} fotoğraf eklendi`);
    }

    /**
     * Validate files
     * @param {File[]} files - Array of file objects
     * @returns {File[]} Valid files
     */
    validateFiles(files) {
        return files.filter(file => {
            // Size check
            if (file.size > this.config.maxSize) {
                this.showError(`${file.name} çok büyük (max ${(this.config.maxSize / 1024 / 1024).toFixed(0)}MB)`);
                return false;
            }

            // Type check
            if (!file.type.startsWith('image/')) {
                this.showError(`${file.name} geçerli bir resim dosyası değil`);
                return false;
            }

            return true;
        });
    }

    /**
     * Update file input with new files
     * @param {File[]} files - Array of file objects
     */
    updateFileInput(files) {
        const fileInput = document.getElementById(this.config.fileInputId);
        if (!fileInput) return;

        try {
            const dataTransfer = new DataTransfer();
            files.forEach(file => {
                try {
                    dataTransfer.items.add(file);
                } catch (e) {
                    if (window.DEBUG_MODE) {
                        console.error('❌ Dosya eklenemedi:', file.name, e);
                    }
                }
            });
            fileInput.files = dataTransfer.files;
        } catch (e) {
            if (window.DEBUG_MODE) {
                console.error('❌ DataTransfer hatası:', e);
            }
            // Fallback: Direct assignment (may not work in all browsers)
            fileInput.files = files;
        }
    }

    /**
     * Update preview container
     * @param {File[]} files - Array of file objects
     */
    updatePreview(files) {
        const previewContainer = document.getElementById(this.config.previewContainerId);
        if (!previewContainer) return;

        previewContainer.innerHTML = '';

        files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'relative group';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview ${index + 1}"
                        class="w-full h-32 object-cover rounded-lg border border-gray-200 dark:border-slate-700">
                    <button type="button" onclick="window.photoHandler?.removePhoto(${index})"
                        class="absolute top-1 right-1 p-1 bg-red-500 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <div class="absolute bottom-1 left-1 bg-black/50 text-white text-xs px-2 py-1 rounded">
                        ${(file.size / 1024 / 1024).toFixed(2)} MB
                    </div>
                `;
                previewContainer.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }

    /**
     * Remove photo by index
     * @param {number} index - Photo index
     */
    removePhoto(index) {
        const fileInput = document.getElementById(this.config.fileInputId);
        if (!fileInput) return;

        const files = Array.from(fileInput.files);
        files.splice(index, 1);

        this.updateFileInput(files);
        this.updatePreview(files);

        if (window.DEBUG_MODE) {
            console.log(`🗑️ Fotoğraf ${index + 1} kaldırıldı`);
        }
    }

    /**
     * Get all photo files
     * @returns {File[]} Array of photo files
     */
    getFiles() {
        const fileInput = document.getElementById(this.config.fileInputId);
        if (!fileInput) return [];
        return Array.from(fileInput.files || []);
    }

    /**
     * Clear all photos
     */
    clear() {
        const fileInput = document.getElementById(this.config.fileInputId);
        const previewContainer = document.getElementById(this.config.previewContainerId);

        if (fileInput) {
            fileInput.value = '';
        }

        if (previewContainer) {
            previewContainer.innerHTML = '';
        }

        this.files = [];
    }

    /**
     * Show error message
     * @param {string} message - Error message
     */
    showError(message) {
        if (window.errorHandler) {
            window.errorHandler.showError(message, 'error');
        } else if (window.toast) {
            window.toast.error(message);
        } else {
            console.error('❌', message);
        }
    }

    /**
     * Show warning message
     * @param {string} message - Warning message
     */
    showWarning(message) {
        if (window.errorHandler) {
            window.errorHandler.showError(message, 'warning');
        } else if (window.toast) {
            window.toast.warning(message);
        } else {
            console.warn('⚠️', message);
        }
    }

    /**
     * Show success message
     * @param {string} message - Success message
     */
    showSuccess(message) {
        if (window.errorHandler) {
            window.errorHandler.showError(message, 'success');
        } else if (window.toast) {
            window.toast.success(message);
        } else {
            console.log('✅', message);
        }
    }
}

// Export singleton instance
if (typeof window !== 'undefined') {
    window.PhotoHandler = PhotoHandler;
    window.photoHandler = new PhotoHandler();
}

export default PhotoHandler;
