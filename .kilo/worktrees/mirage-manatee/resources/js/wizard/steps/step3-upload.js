/**
 * Step 3: Photo Upload Module
 *
 * @module wizard/steps/step3-upload
 * @version 3.0.0
 * @since 2026-01-28
 * @context7 Modüler Wizard Mimarisi - SEALED
 *
 * Handles photo upload, preview, drag & drop, and reordering.
 */

import { WizardEventBus, WizardEventTypes } from '../core/wizard-events.js';
import { WizardState } from '../core/wizard-state.js';

/**
 * Configuration
 */
const CONFIG = {
    maxFiles: 20,
    maxFileSize: 10 * 1024 * 1024, // 10MB
    allowedTypes: ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
    previewSize: 200,
    uploadEndpoint: '/api/v1/photos/upload',
    qualityCheckEndpoint: '/api/v1/cortex/visual/analyze',
};

/**
 * PhotoUploadManager - Step 3 Controller
 */
class PhotoUploadManagerClass {
    constructor() {
        /** @private */
        this._photos = [];

        /** @private */
        this._uploadQueue = [];

        /** @private */
        this._isUploading = false;

        /** @private */
        this._initialized = false;
    }

    /**
     * Initialize photo upload
     */
    init() {
        if (this._initialized) return;

        const uploadArea = document.getElementById('photo-upload-area');
        const fileInput = document.getElementById('fotograflar');

        if (!uploadArea || !fileInput) {
            console.warn('[PhotoUpload] Upload elements not found');
            return;
        }

        this._setupDragDrop(uploadArea);
        this._setupFileInput(fileInput);
        this._setupClickUpload(uploadArea, fileInput);

        this._initialized = true;
        console.log('[PhotoUpload] Initialized');
    }

    /**
     * Setup drag & drop
     * @private
     */
    _setupDragDrop(uploadArea) {
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.add(
                'drag-over',
                'border-blue-500',
                'bg-blue-50',
                'dark:bg-blue-900/20'
            );
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove(
                'drag-over',
                'border-blue-500',
                'bg-blue-50',
                'dark:bg-blue-900/20'
            );
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            e.stopPropagation();
            uploadArea.classList.remove(
                'drag-over',
                'border-blue-500',
                'bg-blue-50',
                'dark:bg-blue-900/20'
            );

            const files = Array.from(e.dataTransfer.files).filter((file) =>
                CONFIG.allowedTypes.includes(file.type)
            );

            if (files.length > 0) {
                this.addPhotos(files);
            }
        });
    }

    /**
     * Setup file input change handler
     * @private
     */
    _setupFileInput(fileInput) {
        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files || []);
            if (files.length > 0) {
                this.addPhotos(files);
            }
        });
    }

    /**
     * Setup click to upload
     * @private
     */
    _setupClickUpload(uploadArea, fileInput) {
        uploadArea.addEventListener('click', (e) => {
            // Don't trigger if clicking on a button inside
            if (e.target.closest('button') || e.target.closest('label')) return;
            fileInput.click();
        });
    }

    /**
     * Add photos to queue
     * @param {File[]} files
     */
    addPhotos(files) {
        const validFiles = [];
        const errors = [];

        files.forEach((file) => {
            // Check file count
            if (this._photos.length + validFiles.length >= CONFIG.maxFiles) {
                errors.push(`Maksimum ${CONFIG.maxFiles} fotoğraf yüklenebilir`);
                return;
            }

            // Check file type
            if (!CONFIG.allowedTypes.includes(file.type)) {
                errors.push(`${file.name}: Desteklenmeyen dosya tipi`);
                return;
            }

            // Check file size
            if (file.size > CONFIG.maxFileSize) {
                errors.push(
                    `${file.name}: Dosya boyutu ${CONFIG.maxFileSize / 1024 / 1024}MB'dan büyük`
                );
                return;
            }

            validFiles.push(file);
        });

        // Show errors
        if (errors.length > 0) {
            this._showNotification(errors.join('\n'), 'error');
        }

        // Add valid files
        validFiles.forEach((file) => {
            const photo = {
                id: this._generateId(),
                file,
                name: file.name,
                size: file.size,
                type: file.type,
                preview: null,
                uploaded: false,
                uploadedId: null,
                error: null,
            };

            this._photos.push(photo);
            this._generatePreview(photo);

            WizardEventBus.emit(WizardEventTypes.PHOTO_ADDED, {
                id: photo.id,
                name: photo.name,
                size: photo.size,
            });
        });

        this._updatePreviewContainer();
        this._updateFileInput();
        this._updateState();
    }

    /**
     * Generate preview for photo
     * @private
     */
    _generatePreview(photo) {
        const reader = new FileReader();
        reader.onload = (e) => {
            photo.preview = e.target.result;
            this._updatePreviewContainer();
        };
        reader.readAsDataURL(photo.file);
    }

    /**
     * Remove photo by ID
     * @param {string} id
     */
    removePhoto(id) {
        const index = this._photos.findIndex((p) => p.id === id);
        if (index === -1) return;

        const photo = this._photos[index];
        this._photos.splice(index, 1);

        WizardEventBus.emit(WizardEventTypes.PHOTO_REMOVED, {
            id: photo.id,
            name: photo.name,
        });

        this._updatePreviewContainer();
        this._updateFileInput();
        this._updateState();
    }

    /**
     * Remove photo by index (backward compatibility)
     * @param {number} index
     */
    removePhotoByIndex(index) {
        if (index >= 0 && index < this._photos.length) {
            const photo = this._photos[index];
            this.removePhoto(photo.id);
        }
    }

    /**
     * Reorder photos
     * @param {number} fromIndex
     * @param {number} toIndex
     */
    reorderPhotos(fromIndex, toIndex) {
        if (fromIndex === toIndex) return;
        if (fromIndex < 0 || fromIndex >= this._photos.length) return;
        if (toIndex < 0 || toIndex >= this._photos.length) return;

        const [photo] = this._photos.splice(fromIndex, 1);
        this._photos.splice(toIndex, 0, photo);

        WizardEventBus.emit(WizardEventTypes.PHOTO_REORDERED, {
            fromIndex,
            toIndex,
        });

        this._updatePreviewContainer();
        this._updateState();
    }

    /**
     * Set cover photo (first position)
     * @param {string} id
     */
    setCoverPhoto(id) {
        const index = this._photos.findIndex((p) => p.id === id);
        if (index > 0) {
            this.reorderPhotos(index, 0);
        }
    }

    /**
     * Update preview container
     * @private
     */
    _updatePreviewContainer() {
        const container = document.getElementById('photo-preview');
        if (!container) return;

        container.innerHTML = '';

        this._photos.forEach((photo, index) => {
            const div = document.createElement('div');
            div.className = 'relative group';
            div.setAttribute('data-photo-id', photo.id);
            div.setAttribute('draggable', 'true');

            div.innerHTML = `
                <img src="${photo.preview || ''}" alt="Preview ${index + 1}"
                    class="w-full h-32 object-cover rounded-lg border border-gray-200 dark:border-slate-700 transition-all duration-200 ${index === 0 ? 'ring-2 ring-blue-500 dark:ring-blue-400' : ''}">

                ${
                    index === 0
                        ? `
                    <div class="absolute top-1 left-1 bg-blue-500 text-white text-xs px-2 py-0.5 rounded">
                        Kapak
                    </div>
                `
                        : ''
                }

                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center gap-2">
                    ${
                        index !== 0
                            ? `
                        <button type="button" onclick="YalihanWizard.photos.setCoverPhoto('${photo.id}')"
                            class="p-1.5 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition-colors"
                            title="Kapak Yap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3l14 9-14 9V3z"/>
                            </svg>
                        </button>
                    `
                            : ''
                    }
                    <button type="button" onclick="YalihanWizard.photos.removePhoto('${photo.id}')"
                        class="p-1.5 bg-red-500 text-white rounded-full hover:bg-red-600 transition-colors"
                        title="Sil">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="absolute bottom-1 left-1 bg-black/50 text-white text-xs px-2 py-0.5 rounded">
                    ${(photo.size / 1024 / 1024).toFixed(2)} MB
                </div>

                ${
                    photo.error
                        ? `
                    <div class="absolute bottom-1 right-1 bg-red-500 text-white text-xs px-2 py-0.5 rounded">
                        Hata
                    </div>
                `
                        : ''
                }
            `;

            // Drag events
            div.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('text/plain', index.toString());
                div.classList.add('opacity-50');
            });

            div.addEventListener('dragend', () => {
                div.classList.remove('opacity-50');
            });

            div.addEventListener('dragover', (e) => {
                e.preventDefault();
                div.classList.add('ring-2', 'ring-blue-400');
            });

            div.addEventListener('dragleave', () => {
                div.classList.remove('ring-2', 'ring-blue-400');
            });

            div.addEventListener('drop', (e) => {
                e.preventDefault();
                div.classList.remove('ring-2', 'ring-blue-400');
                const fromIndex = parseInt(e.dataTransfer.getData('text/plain'));
                this.reorderPhotos(fromIndex, index);
            });

            container.appendChild(div);
        });
    }

    /**
     * Update file input with current photos
     * @private
     */
    _updateFileInput() {
        const fileInput = document.getElementById('fotograflar');
        if (!fileInput) return;

        const dataTransfer = new DataTransfer();
        this._photos.forEach((photo) => {
            if (photo.file) {
                dataTransfer.items.add(photo.file);
            }
        });
        fileInput.files = dataTransfer.files;
    }

    /**
     * Update wizard state
     * @private
     */
    _updateState() {
        WizardState.set(
            'photos',
            this._photos.map((p) => ({
                id: p.id,
                name: p.name,
                size: p.size,
                uploaded: p.uploaded,
                uploadedId: p.uploadedId,
            }))
        );
    }

    /**
     * Generate unique ID
     * @private
     */
    _generateId() {
        return `photo_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Show notification
     * @private
     */
    _showNotification(message, type = 'info') {
        // Use global notification if available
        if (window.showNotification) {
            window.showNotification(message, type);
        } else {
            console.log(`[PhotoUpload] ${type}: ${message}`);
        }
    }

    /**
     * Get all photos
     * @returns {Array}
     */
    getPhotos() {
        return [...this._photos];
    }

    /**
     * Get photo count
     * @returns {number}
     */
    getPhotoCount() {
        return this._photos.length;
    }

    /**
     * Check if has photos
     * @returns {boolean}
     */
    hasPhotos() {
        return this._photos.length > 0;
    }

    /**
     * Get photo files for form submission
     * @returns {File[]}
     */
    getFiles() {
        return this._photos.map((p) => p.file).filter(Boolean);
    }

    /**
     * Clear all photos
     */
    clear() {
        this._photos = [];
        this._updatePreviewContainer();
        this._updateFileInput();
        this._updateState();
    }

    /**
     * Run AI quality check on photos
     * @returns {Promise<Object>}
     */
    async runQualityCheck() {
        if (this._photos.length === 0) {
            return { success: false, message: 'No photos to check' };
        }

        try {
            const formData = new FormData();
            this._photos.forEach((photo, index) => {
                if (photo.file) {
                    formData.append(`photos[${index}]`, photo.file);
                }
            });

            const response = await fetch(CONFIG.qualityCheckEndpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.content || '',
                    Accept: 'application/json',
                },
                body: formData,
            });

            const result = await response.json();

            if (result.success) {
                WizardEventBus.emit(WizardEventTypes.AI_QUALITY_CHECKED, {
                    source: 'photos',
                    result: result.data,
                });
            }

            return result;
        } catch (error) {
            console.error('[PhotoUpload] Quality check error:', error);
            return { success: false, error: error.message };
        }
    }
}

// Singleton instance
export const PhotoUploadManager = new PhotoUploadManagerClass();

// Global export
if (typeof window !== 'undefined') {
    window.YalihanWizard = window.YalihanWizard || {};
    window.YalihanWizard.photos = PhotoUploadManager;

    // Backward compatibility
    window.removePhoto = (index) => PhotoUploadManager.removePhotoByIndex(index);
    window.handlePhotoSelection = (event) => {
        const files = Array.from(event.target.files || []);
        if (files.length > 0) {
            PhotoUploadManager.addPhotos(files);
        }
    };
}

export default PhotoUploadManager;
