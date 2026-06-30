/**
 * Step 3 Photo Handler
 * Context7: Fotoğraf yükleme ve önizleme
 */

import { logger } from './step1-core.js';

let photoIndex = 0;
const photoFiles = [];

/**
 * Preview photos after selection
 * @param {HTMLInputElement} input - File input element
 */
export function previewPhotos(input) {
    const preview = document.getElementById('photo-preview');
    if (!preview) return;

    preview.innerHTML = '';
    preview.classList.remove('hidden');

    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function (e) {
                const div = document.createElement('div');
                div.className = 'relative';
                div.innerHTML = `
                    <img src="${e.target.result}" alt="Preview ${index + 1}"
                         class="w-full h-24 object-cover rounded-lg">
                    <button type="button" onclick="removePhoto(${photoIndex})"
                            class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-700 transition-colors">
                        ×
                    </button>
                `;
                preview.appendChild(div);
                photoFiles[photoIndex] = file;
                photoIndex++;
            };
            reader.readAsDataURL(file);
        });
    }

    logger.log(`✅ ${input.files.length} fotoğraf önizlemesi eklendi`);
}

/**
 * Remove photo from preview
 * @param {number} index - Photo index
 */
export function removePhoto(index) {
    const preview = document.getElementById('photo-preview');
    if (!preview) return;

    const photoDiv = preview.children[index];
    if (photoDiv) {
        photoDiv.remove();
        delete photoFiles[index];
        logger.log(`🗑️ Fotoğraf ${index + 1} kaldırıldı`);
    }
}

/**
 * Handle photo selection (for file input onchange)
 * @param {HTMLInputElement} input - File input element
 */
export function handlePhotoSelection(input) {
    if (!input || !input.files) return;
    previewPhotos(input);
}

/**
 * Get selected photo files
 * @returns {File[]} Array of photo files
 */
export function getPhotoFiles() {
    return photoFiles.filter(file => file !== undefined);
}

/**
 * Clear all photos
 */
export function clearPhotos() {
    const preview = document.getElementById('photo-preview');
    if (preview) {
        preview.innerHTML = '';
    }
    photoFiles.length = 0;
    photoIndex = 0;
    logger.log('🗑️ Tüm fotoğraflar temizlendi');
}

// Export to window for global access
if (typeof window !== 'undefined') {
    window.previewPhotos = previewPhotos;
    window.removePhoto = removePhoto;
    window.handlePhotoSelection = handlePhotoSelection;
    window.getPhotoFiles = getPhotoFiles;
    window.clearPhotos = clearPhotos;
}
