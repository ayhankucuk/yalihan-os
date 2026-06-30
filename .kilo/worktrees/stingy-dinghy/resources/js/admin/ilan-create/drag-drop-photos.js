// Yalıhan Bekçi - Drag & Drop Photo System
// Advanced drag and drop for photo sorting

class DragDropPhotos {
    constructor() {
        this.draggedElement = null;
        this.dragOverElement = null;
        this.dropZones = new Set();
        this.draggableElements = new Set();
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.injectDragDropCSS();
    }

    // 📋 Drag & drop (Fotoğraf sıralaması)
    setupEventListeners() {
        document.addEventListener('dragstart', this.handleDragStart.bind(this));
        document.addEventListener('dragend', this.handleDragEnd.bind(this));
        document.addEventListener('dragover', this.handleDragOver.bind(this));
        document.addEventListener('dragenter', this.handleDragEnter.bind(this));
        document.addEventListener('dragleave', this.handleDragLeave.bind(this));
        document.addEventListener('drop', this.handleDrop.bind(this));
    }

    handleDragStart(e) {
        const draggable = e.target.closest('[draggable="true"]');
        if (!draggable) return;

        this.draggedElement = draggable;
        draggable.classList.add('dragging');

        // Set drag data
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', draggable.outerHTML);
        e.dataTransfer.setData('text/plain', draggable.dataset.itemId || '');

        // Create drag image
        this.createDragImage(draggable, e);
    }

    handleDragEnd(e) {
        if (this.draggedElement) {
            this.draggedElement.classList.remove('dragging');
            this.draggedElement = null;
        }

        // Clean up drag over states
        document.querySelectorAll('.drag-over').forEach((el) => {
            el.classList.remove('drag-over');
        });

        this.dragOverElement = null;
    }

    handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';

        const dropZone = e.target.closest('[data-drop-zone]');
        if (dropZone && dropZone !== this.dragOverElement) {
            this.updateDragOver(dropZone);
        }
    }

    handleDragEnter(e) {
        e.preventDefault();

        const dropZone = e.target.closest('[data-drop-zone]');
        if (dropZone) {
            dropZone.classList.add('drag-over');
            this.dragOverElement = dropZone;
        }
    }

    handleDragLeave(e) {
        const dropZone = e.target.closest('[data-drop-zone]');
        if (dropZone && !dropZone.contains(e.relatedTarget)) {
            dropZone.classList.remove('drag-over');
        }
    }

    handleDrop(e) {
        e.preventDefault();

        const dropZone = e.target.closest('[data-drop-zone]');
        if (!dropZone || !this.draggedElement) return;

        // Get drop position
        const dropPosition = this.getDropPosition(e, dropZone);

        // Perform the drop
        this.performDrop(this.draggedElement, dropZone, dropPosition);

        // Clean up
        dropZone.classList.remove('drag-over');
        this.dragOverElement = null;
    }

    createDragImage(element, event) {
        const dragImage = element.cloneNode(true);
        dragImage.style.position = 'absolute';
        dragImage.style.top = '-1000px';
        dragImage.style.left = '-1000px';
        dragImage.style.width = element.offsetWidth + 'px';
        dragImage.style.height = element.offsetHeight + 'px';
        dragImage.style.opacity = '0.8';
        dragImage.style.transform = 'rotate(5deg)';
        dragImage.style.zIndex = '9999';

        document.body.appendChild(dragImage);
        event.dataTransfer.setDragImage(dragImage, 0, 0);

        // Remove drag image after drag starts
        setTimeout(() => {
            document.body.removeChild(dragImage);
        }, 0);
    }

    updateDragOver(dropZone) {
        // Remove previous drag over
        if (this.dragOverElement) {
            this.dragOverElement.classList.remove('drag-over');
        }

        // Add new drag over
        dropZone.classList.add('drag-over');
        this.dragOverElement = dropZone;
    }

    getDropPosition(event, dropZone) {
        const rect = dropZone.getBoundingClientRect();
        const y = event.clientY - rect.top;
        const x = event.clientX - rect.left;

        // Determine if it's a horizontal or vertical layout
        const isHorizontal = dropZone.dataset.layout === 'horizontal';

        if (isHorizontal) {
            return x < rect.width / 2 ? 'before' : 'after';
        } else {
            return y < rect.height / 2 ? 'before' : 'after';
        }
    }

    performDrop(draggedElement, dropZone, position) {
        // Find the target element within the drop zone
        const targetElement = this.findTargetElement(dropZone, position);

        if (targetElement) {
            if (position === 'before') {
                dropZone.insertBefore(draggedElement, targetElement);
            } else {
                dropZone.insertBefore(draggedElement, targetElement.nextSibling);
            }
        } else {
            // Drop at the end
            dropZone.appendChild(draggedElement);
        }

        // Dispatch drop event
        this.dispatchDropEvent(draggedElement, dropZone, position);

        // Update sort order
        this.updateSortOrder(dropZone);
    }

    findTargetElement(dropZone, position) {
        const children = Array.from(dropZone.children);
        const centerIndex = Math.floor(children.length / 2);

        if (position === 'before') {
            return children[centerIndex] || null;
        } else {
            return children[centerIndex + 1] || null;
        }
    }

    dispatchDropEvent(draggedElement, dropZone, position) {
        const event = new CustomEvent('photo-reordered', {
            detail: {
                draggedElement,
                dropZone,
                position,
                newOrder: this.getSortOrder(dropZone),
            },
        });

        dropZone.dispatchEvent(event);
    }

    updateSortOrder(container) {
        const items = Array.from(container.querySelectorAll('[data-item-id]'));
        items.forEach((item, index) => {
            item.dataset.sortOrder = index;

            // Update visual indicators
            const orderIndicator = item.querySelector('.sort-order');
            if (orderIndicator) {
                orderIndicator.textContent = index + 1;
            }
        });
    }

    getSortOrder(container) {
        return Array.from(container.querySelectorAll('[data-item-id]')).map(
            (item) => item.dataset.itemId
        );
    }

    // Setup draggable elements
    setupDraggable(element, options = {}) {
        element.draggable = true;
        element.classList.add('draggable');

        if (options.handle) {
            const handle = element.querySelector(options.handle);
            if (handle) {
                handle.style.cursor = 'grab';
                handle.addEventListener('mousedown', () => {
                    element.draggable = true;
                });
            }
        }

        this.draggableElements.add(element);

        // Add drag handle if not present
        if (!element.querySelector('.drag-handle')) {
            const handle = document.createElement('div');
            handle.className = 'drag-handle';
            handle.innerHTML = '<i class="fas fa-grip-vertical"></i>';
            element.appendChild(handle);
        }
    }

    // Setup drop zones
    setupDropZone(element, options = {}) {
        element.setAttribute('data-drop-zone', 'true');

        if (options.layout) {
            element.dataset.layout = options.layout;
        }

        this.dropZones.add(element);
    }

    // Photo upload with drag and drop
    setupPhotoUpload(container, options = {}) {
        const uploadArea = container.querySelector('.photo-upload-area') || container;

        // Setup drag and drop for file upload
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'copy';
            uploadArea.classList.add('drag-over-upload');
        });

        uploadArea.addEventListener('dragleave', (e) => {
            if (!uploadArea.contains(e.relatedTarget)) {
                uploadArea.classList.remove('drag-over-upload');
            }
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('drag-over-upload');

            const files = Array.from(e.dataTransfer.files);
            this.handleFileUpload(files, options);
        });

        // Setup click to upload
        const fileInput = uploadArea.querySelector('input[type="file"]');
        if (fileInput) {
            uploadArea.addEventListener('click', () => {
                fileInput.click();
            });

            fileInput.addEventListener('change', (e) => {
                const files = Array.from(e.target.files);
                this.handleFileUpload(files, options);
            });
        }
    }

    async handleFileUpload(files, options = {}) {
        const imageFiles = files.filter((file) => file.type.startsWith('image/'));

        for (const file of imageFiles) {
            try {
                // Optimize image
                const optimizedFile = await this.optimizeImage(file);

                // Create photo element
                const photoElement = await this.createPhotoElement(optimizedFile);

                // Add to container
                const container = document.querySelector(options.container || '.photo-grid');
                if (container) {
                    container.appendChild(photoElement);

                    // Setup drag and drop for the new photo
                    this.setupDraggable(photoElement, options);
                }

                // Dispatch upload event
                this.dispatchUploadEvent(photoElement, file);
            } catch (error) {
                console.error('Photo upload failed:', error);
                this.showUploadError(error);
            }
        }
    }

    async optimizeImage(file) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();

            img.onload = () => {
                const maxWidth = 1920;
                const maxHeight = 1080;
                let { width, height } = img;

                if (width > maxWidth || height > maxHeight) {
                    const ratio = Math.min(maxWidth / width, maxHeight / height);
                    width *= ratio;
                    height *= ratio;
                }

                canvas.width = width;
                canvas.height = height;

                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(resolve, 'image/jpeg', 0.9);
            };

            img.src = URL.createObjectURL(file);
        });
    }

    async createPhotoElement(file) {
        const photoDiv = document.createElement('div');
        photoDiv.className = 'photo-item';
        photoDiv.draggable = true;
        photoDiv.dataset.itemId = this.generateId();
        photoDiv.dataset.fileName = file.name;

        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.className = 'photo-thumbnail';

        const overlay = document.createElement('div');
        overlay.className = 'photo-overlay';
        overlay.innerHTML = `
            <div class="photo-actions">
                <button class="photo-action" data-action="edit" title="Düzenle">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="photo-action" data-action="delete" title="Sil">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="photo-info">
                <div class="photo-name">${file.name}</div>
                <div class="photo-size">${this.formatFileSize(file.size)}</div>
            </div>
        `;

        photoDiv.appendChild(img);
        photoDiv.appendChild(overlay);

        // Setup actions
        this.setupPhotoActions(photoDiv);

        return photoDiv;
    }

    setupPhotoActions(photoElement) {
        const actions = photoElement.querySelectorAll('.photo-action');

        actions.forEach((action) => {
            action.addEventListener('click', (e) => {
                e.stopPropagation();

                const actionType = action.dataset.action;

                switch (actionType) {
                    case 'edit':
                        this.editPhoto(photoElement);
                        break;
                    case 'delete':
                        this.deletePhoto(photoElement);
                        break;
                }
            });
        });
    }

    editPhoto(photoElement) {
        // Open photo editor
        const event = new CustomEvent('photo-edit', {
            detail: { photoElement },
        });
        document.dispatchEvent(event);
    }

    deletePhoto(photoElement) {
        // Confirm deletion
        if (confirm('Bu fotoğrafı silmek istediğinizden emin misiniz?')) {
            photoElement.remove();

            // Dispatch deletion event
            const event = new CustomEvent('photo-deleted', {
                detail: { photoElement },
            });
            document.dispatchEvent(event);
        }
    }

    dispatchUploadEvent(photoElement, file) {
        const event = new CustomEvent('photo-uploaded', {
            detail: { photoElement, file },
        });
        document.dispatchEvent(event);
    }

    showUploadError(error) {
        // Show error toast
        if (window.toastNotifications) {
            window.toastNotifications.error('Fotoğraf yüklenirken hata oluştu:' + error.message);
        }
    }

    generateId() {
        return 'photo-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    }

    formatFileSize(bytes) {
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        if (bytes === 0) return '0 Bytes';
        const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
        return Math.round((bytes / Math.pow(1024, i)) * 100) / 100 + '' + sizes[i];
    }

    injectDragDropCSS() {
        const dragDropCSS = `
            /* Drag & Drop Styles */
            .draggable {
                cursor: grab;
                transition: all 0.2s ease;
            }

            .draggable:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .draggable.dragging {
                opacity: 0.5;
                transform: rotate(5deg);
                cursor: grabbing;
                z-index: 1000;
            }

            .drag-handle {
                position: absolute;
                top: 8px;
                left: 8px;
                background: rgba(0, 0, 0, 0.7);
                color: white;
                padding: 4px;
                border-radius: 4px;
                cursor: grab;
                opacity: 0;
                transition: opacity 0.2s ease;
                z-index: 10;
            }

            .draggable:hover .drag-handle {
                opacity: 1;
            }

            .drag-handle:active {
                cursor: grabbing;
            }

            /* Drop zones */
            [data-drop-zone] {
                min-height: 100px;
                transition: all 0.2s ease;
            }

            [data-drop-zone].drag-over {
                background: rgba(59, 130, 246, 0.1);
                border: 2px dashed #3b82f6;
                border-radius: 8px;
            }

            /* Photo grid */
            .photo-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 16px;
                padding: 16px;
            }

            .photo-item {
                position: relative;
                aspect-ratio: 1;
                border-radius: 8px;
                overflow: hidden;
                border: 2px solid #e5e7eb;
                transition: all 0.2s ease;
                cursor: grab;
            }

            .photo-item:hover {
                border-color: #3b82f6;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .photo-thumbnail {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .photo-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: linear-gradient(
                    to bottom,
                    rgba(0, 0, 0, 0.7) 0%,
                    transparent 30%,
                    transparent 70%,
                    rgba(0, 0, 0, 0.7) 100%
                );
                opacity: 0;
                transition: opacity 0.2s ease;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                padding: 8px;
            }

            .photo-item:hover .photo-overlay {
                opacity: 1;
            }

            .photo-actions {
                display: flex;
                gap: 4px;
                justify-content: flex-end;
            }

            .photo-action {
                background: rgba(255, 255, 255, 0.9);
                border: none;
                border-radius: 4px;
                width: 28px;
                height: 28px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: all 0.2s ease;
                color: #374151;
            }

            .photo-action:hover {
                background: white;
                transform: scale(1.1);
            }

            .photo-info {
                color: white;
                font-size: 12px;
            }

            .photo-name {
                font-weight: 500;
                margin-bottom: 2px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .photo-size {
                opacity: 0.8;
            }

            /* Upload area */
            .photo-upload-area {
                border: 2px dashed #d1d5db;
                border-radius: 8px;
                padding: 32px;
                text-align: center;
                transition: all 0.2s ease;
                cursor: pointer;
                background: #f9fafb;
            }

            .photo-upload-area:hover,
            .photo-upload-area.drag-over-upload {
                border-color: #3b82f6;
                background: rgba(59, 130, 246, 0.05);
            }

            .upload-icon {
                font-size: 48px;
                color: #9ca3af;
                margin-bottom: 16px;
            }

            .upload-text {
                color: #6b7280;
                font-size: 16px;
                margin-bottom: 8px;
            }

            .upload-hint {
                color: #9ca3af;
                font-size: 14px;
            }

            /* Sort order indicator */
            .sort-order {
                position: absolute;
                top: 8px;
                right: 8px;
                background: rgba(0, 0, 0, 0.7);
                color: white;
                padding: 2px 6px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500;
                z-index: 10;
            }

            /* Dark mode */
            .dark .photo-item {
                border-color: #374151;
            }

            .dark .photo-item:hover {
                border-color: #60a5fa;
            }

            .dark .photo-upload-area {
                border-color: #374151;
                background: #1f2937;
            }

            .dark .photo-upload-area:hover,
            .dark .photo-upload-area.drag-over-upload {
                border-color: #60a5fa;
                background: rgba(96, 165, 250, 0.1);
            }

            .dark .upload-icon {
                color: #6b7280;
            }

            .dark .upload-text {
                color: #d1d5db;
            }

            .dark .upload-hint {
                color: #9ca3af;
            }

            /* Responsive */
            @media (max-width: 768px) {
                .photo-grid {
                    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                    gap: 12px;
                    padding: 12px;
                }

                .photo-upload-area {
                    padding: 24px 16px;
                }

                .upload-icon {
                    font-size: 36px;
                }
            }
        `;

        const style = document.createElement('style');
        style.textContent = dragDropCSS;
        document.head.appendChild(style);
    }

    // Alpine.js integration
    setupAlpineIntegration() {
        document.addEventListener('alpine:init', () => {
            Alpine.directive('drag-drop', (el, { expression }, { evaluateLater, effect }) => {
                const evaluate = evaluateLater(expression);
                let options = {};

                effect(() => {
                    evaluate((value) => {
                        options = value || {};
                        this.setupElement(el, options);
                    });
                });
            });
        });
    }

    setupElement(element, options) {
        if (options.draggable) {
            this.setupDraggable(element, options.draggable);
        }

        if (options.dropZone) {
            this.setupDropZone(element, options.dropZone);
        }

        if (options.photoUpload) {
            this.setupPhotoUpload(element, options.photoUpload);
        }
    }
}

// Global instance
window.dragDropPhotos = new DragDropPhotos();

// Auto-setup Alpine integration
window.dragDropPhotos.setupAlpineIntegration();

// Export for module usage
export default DragDropPhotos;
