// ilan-create-photos.js - Photo upload and management functionality

let uploadedPhotos = [];
let selectedPhotos = [];
const maxPhotos = 50;
const maxFileSize = 10 * 1024 * 1024; // 10MB
const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

function openFileDialog() {
    const input = document.getElementById('photo-input');
    if (input) {
        input.click();
    }
}

function handleFileSelect(event) {
    const files = Array.from(event.target.files);
    processSelectedFiles(files);
}

function handleDrop(event) {
    event.preventDefault();
    const files = Array.from(event.dataTransfer.files);
    processSelectedFiles(files);
}

function processSelectedFiles(files) {
    // Filter valid image files
    const validFiles = files.filter((file) => {
        if (!allowedTypes.includes(file.type)) {
            showNotification(
                `${file.name}: Geçersiz dosya türü. Sadece JPG, PNG, GIF, WebP kabul edilir.`,
                'error',
            );
            return false;
        }

        if (file.size > maxFileSize) {
            showNotification(`${file.name}: Dosya çok büyük. Maksimum 10MB olabilir.`, 'error');
            return false;
        }

        return true;
    });

    // Check total photo limit
    const currentCount = uploadedPhotos.length;
    const availableSlots = maxPhotos - currentCount;

    if (validFiles.length > availableSlots) {
        showNotification(
            `En fazla ${maxPhotos} fotoğraf yükleyebilirsiniz. ${availableSlots} slot kaldı.`,
            'warning',
        );
        validFiles.splice(availableSlots);
    }

    if (validFiles.length === 0) return;

    // Upload files
    uploadPhotos(validFiles);
}

// Helper functions
function showLoading(message) {
    window.toast?.info(message, 2000);
}

function hideLoading() {
    // Toast otomatik kapanır
}

function showNotification(message, type = 'info') {
    if (type === 'error') {
        window.toast?.error(message);
    } else if (type === 'success') {
        window.toast?.success(message);
    } else {
        window.toast?.info(message);
    }
}

async function uploadPhotos(files) {
    showLoading(`${files.length} fotoğraf yükleniyor...`);

    const formData = new FormData();

    files.forEach((file, index) => {
        formData.append(`photos[${index}]`, file);
    });

    // ✅ Merkezi API Config kullan (hardcoded fallback YOK)
    if (!window.APIConfig?.admin?.photos?.upload) {
        console.error('❌ APIConfig.admin.photos.upload tanımlı değil! api-config.js yüklü mü kontrol edin.');
        showNotification('API config yüklenemedi. Sayfayı yenileyin.', 'error');
        return;
    }

    fetch(window.APIConfig.admin.photos.upload, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content'),
        },
    })
        .then((response) => response.json())
        .then((data) => {
            hideLoading();
            if (data.success) {
                addPhotosToGallery(data.photos);
                showNotification(`${data.photos.length} fotoğraf başarıyla yüklendi`, 'success');
            } else {
                showNotification(data.message || 'Fotoğraflar yüklenemedi', 'error');
            }
        })
        .catch((error) => {
            hideLoading();
            console.error('Photo upload error:', error);
            showNotification('Fotoğraflar yüklenemedi', 'error');
        });
}

function addPhotosToGallery(newPhotos) {
    newPhotos.forEach((photo) => {
        const photoObject = {
            id: photo.id,
            url: photo.url,
            thumbnail: photo.thumbnail || photo.url,
            name: photo.name,
            size: photo.size,
            type: photo.type,
            selected: false,
            isCover: false,
            title: '',
            description: '',
            alt: '',
            order: uploadedPhotos.length,
        };

        uploadedPhotos.push(photoObject);
    });

    updatePhotoGallery();
    updatePhotoCounter();
}

function updatePhotoGallery() {
    if (window.photoManagerInstance) {
        window.photoManagerInstance.photos = [...uploadedPhotos];
    }
}

function updatePhotoCounter() {
    const counter = document.querySelector('[x-text="photos.length + \'/50\'"]');
    if (counter) {
        counter.textContent = `${uploadedPhotos.length}/50`;
    }
}

function selectAllPhotos() {
    selectedPhotos = [...uploadedPhotos];
    uploadedPhotos.forEach((photo) => (photo.selected = true));
    updatePhotoGallery();
}

function clearSelection() {
    selectedPhotos = [];
    uploadedPhotos.forEach((photo) => (photo.selected = false));
    updatePhotoGallery();
}

function toggleSelection(photo) {
    photo.selected = !photo.selected;
    if (photo.selected) {
        selectedPhotos.push(photo);
    } else {
        selectedPhotos = selectedPhotos.filter((p) => p.id !== photo.id);
    }
    updatePhotoGallery();
}

function setAsCover(photo) {
    // Remove cover from all photos
    uploadedPhotos.forEach((p) => (p.isCover = false));

    // Set as cover
    photo.isCover = true;

    // Move to first position
    const index = uploadedPhotos.findIndex((p) => p.id === photo.id);
    if (index > 0) {
        uploadedPhotos.splice(index, 1);
        uploadedPhotos.unshift(photo);
        updatePhotoOrder();
    }

    updatePhotoGallery();
    showNotification('Kapak fotoğrafı ayarlandı', 'success');
}

function editPhoto(photo) {
    if (window.photoManagerInstance) {
        window.photoManagerInstance.editingPhoto = { ...photo };
    }
}

function savePhotoEdit() {
    const editingPhoto = window.photoManagerInstance?.editingPhoto;
    if (!editingPhoto) return;

    // Update photo data
    const originalPhoto = uploadedPhotos.find((p) => p.id === editingPhoto.id);
    if (originalPhoto) {
        originalPhoto.title = editingPhoto.title;
        originalPhoto.description = editingPhoto.description;
        originalPhoto.alt = editingPhoto.alt;
        originalPhoto.isCover = editingPhoto.isCover;

        if (editingPhoto.isCover) {
            setAsCover(originalPhoto);
        }
    }

    // Close modal (Context7 uyumlu - boş obje ile reset)
    if (window.photoManagerInstance) {
        window.photoManagerInstance.editingPhoto = {
            url: '',
            name: '',
            title: '',
            description: '',
            alt: '',
            isCover: false,
        };
    }

    updatePhotoGallery();
    showNotification('Fotoğraf bilgileri kaydedildi', 'success');
}

function deletePhoto(photo) {
    if (!confirm('Bu fotoğrafı silmek istediğinizden emin misiniz?')) return;

    showLoading('Fotoğraf siliniyor...');

    const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.photos ? window.APIConfig.admin.photos.delete(photo.id) : `/api/photos/${photo.id}`;
    fetch(url, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content'),
        },
    })
        .then((response) => response.json())
        .then((data) => {
            hideLoading();
            if (data.success) {
                removePhotoFromGallery(photo.id);
                showNotification('Fotoğraf silindi', 'success');
            } else {
                showNotification(data.message || 'Fotoğraf silinemedi', 'error');
            }
        })
        .catch((error) => {
            hideLoading();
            console.error('Photo delete error:', error);
            showNotification('Fotoğraf silinemedi', 'error');
        });
}

function removePhotoFromGallery(photoId) {
    uploadedPhotos = uploadedPhotos.filter((p) => p.id !== photoId);
    selectedPhotos = selectedPhotos.filter((p) => p.id !== photoId);
    updatePhotoGallery();
    updatePhotoCounter();
}

function updatePhotoOrder() {
    uploadedPhotos.forEach((photo, index) => {
        photo.order = index;
    });
}

function validatePhotos() {
    if (uploadedPhotos.length === 0) {
        showNotification('En az bir fotoğraf yüklemeniz gerekir', 'warning');
        return false;
    }

    const hasCover = uploadedPhotos.some((photo) => photo.isCover);
    if (!hasCover) {
        // Auto-set first photo as cover
        if (uploadedPhotos.length > 0) {
            setAsCover(uploadedPhotos[0]);
        }
    }

    return true;
}

// Drag and drop functionality
function initializeDragAndDrop() {
    const dropZone = document.getElementById('photo-drop-zone');
    if (!dropZone) return;

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach((eventName) => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropZone.classList.add('border-blue-500', 'bg-blue-50');
        dropZone.classList.remove('border-orange-300');
    }

    function unhighlight() {
        dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        dropZone.classList.add('border-orange-300');
    }
}

// Photo sorting functionality
function initializePhotoSorting() {
    // This would require a sortable library like SortableJS
    // For now, we'll implement basic drag-to-reorder
}

// Bulk photo operations
async function deleteSelectedPhotos() {
    if (selectedPhotos.length === 0) {
        showNotification('Silmek için fotoğraf seçin', 'warning');
        return;
    }

    if (!confirm(`${selectedPhotos.length} fotoğrafı silmek istediğinizden emin misiniz?`)) return;

    showLoading('Seçili fotoğraflar siliniyor...');

    const photoIds = selectedPhotos.map((p) => p.id);

    // ✅ Merkezi API Config kullan (hardcoded fallback YOK)
    if (!window.APIConfig?.admin?.photos?.bulkDelete) {
        console.error('❌ APIConfig.admin.photos.bulkDelete tanımlı değil! api-config.js yüklü mü kontrol edin.');
        showNotification('API config yüklenemedi. Sayfayı yenileyin.', 'error');
        return;
    }

    fetch(window.APIConfig.admin.photos.bulkDelete, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute('content'),
        },
        body: JSON.stringify({ photo_ids: photoIds }),
    })
        .then((response) => response.json())
        .then((data) => {
            hideLoading();
            if (data.success) {
                photoIds.forEach((id) => removePhotoFromGallery(id));
                showNotification(`${photoIds.length} fotoğraf silindi`, 'success');
            } else {
                showNotification(data.message || 'Fotoğraflar silinemedi', 'error');
            }
        })
        .catch((error) => {
            hideLoading();
            console.error('Bulk photo delete error:', error);
            showNotification('Fotoğraflar silinemedi', 'error');
        });
}

// Photo compression and optimization
function compressImage(file, maxWidth = 1920, maxHeight = 1080, quality = 0.8) {
    return new Promise((resolve) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const img = new Image();

        img.onload = () => {
            // Calculate new dimensions
            let { width, height } = img;

            if (width > height) {
                if (width > maxWidth) {
                    height = (height * maxWidth) / width;
                    width = maxWidth;
                }
            } else {
                if (height > maxHeight) {
                    width = (width * maxHeight) / height;
                    height = maxHeight;
                }
            }

            canvas.width = width;
            canvas.height = height;

            // Draw and compress
            ctx.drawImage(img, 0, 0, width, height);

            canvas.toBlob(resolve, file.type, quality);
        };

        img.src = URL.createObjectURL(file);
    });
}

// Load existing photos (for edit mode)
function loadExistingPhotos(listingId) {
    if (!listingId) return;

    const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.photos ? window.APIConfig.admin.photos.listByListing(listingId) : `/api/listings/${listingId}/photos`;
    fetch(url)
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                data.photos.forEach((photo) => {
                    uploadedPhotos.push({
                        id: photo.id,
                        url: photo.url,
                        thumbnail: photo.thumbnail || photo.url,
                        name: photo.name,
                        size: photo.size,
                        type: photo.type,
                        selected: false,
                        isCover: photo.is_cover || false,
                        title: photo.title || '',
                        description: photo.description || '',
                        alt: photo.alt || '',
                        order: photo.order || 0,
                    });
                });

                updatePhotoGallery();
                updatePhotoCounter();
            }
        })
        .catch((error) => {
            console.error('Load existing photos error:', error);
        });
}

// Alpine.js data function for photo manager
window.photoManager = function () {
    return {
        photos: [],
        selectedPhotos: [],
        editingPhoto: null, // Context7 uyumlu - modal açılışta gizli

        init() {
            // Load existing photos if editing
            const listingId = document.querySelector('input[name="listing_id"]')?.value;
            if (listingId) {
                loadExistingPhotos(listingId);
            }

            // Initialize drag and drop
            initializeDragAndDrop();
        },

        openFileDialog() {
            openFileDialog();
        },

        handleFileSelect(event) {
            handleFileSelect(event);
        },

        handleDrop(event) {
            handleDrop(event);
        },

        selectAllPhotos() {
            selectAllPhotos();
        },

        clearSelection() {
            clearSelection();
        },

        toggleSelection(photo) {
            toggleSelection(photo);
        },

        setAsCover(photo) {
            setAsCover(photo);
        },

        editPhoto(photo) {
            editPhoto(photo);
        },

        deletePhoto(photo) {
            deletePhoto(photo);
        },

        closeEditModal() {
            // Context7 uyumlu - modal kapatma (null ile reset)
            this.editingPhoto = null;
        },

        savePhotoEdit() {
            savePhotoEdit();
        },
    };
};

// Initialize photo functionality
document.addEventListener('DOMContentLoaded', () => {
    // Store Alpine instance reference
    document.addEventListener('alpine:init', () => {
        window.photoManagerInstance = Alpine.store('photoManager');
    });
});

// Export functions for use in other modules
window.IlanCreatePhotos = {
    openFileDialog,
    handleFileSelect,
    handleDrop,
    uploadPhotos,
    validatePhotos,
    photoManager: window.photoManager,
    loadExistingPhotos,
};
