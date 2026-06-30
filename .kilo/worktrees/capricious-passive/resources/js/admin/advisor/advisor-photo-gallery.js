/**
 * Advisor Photo Gallery - Alpine.js Component
 * Phase 5.3.2 Frontend Implementation
 * 
 * Features:
 * - Upload photos with drag-drop
 * - Auto-quality scoring
 * - Featured photo auto-selection
 * - Reordering by quality
 */

export function initAdvisorPhotoGallery() {
    return {
        photos: [],
        uploading: false,
        dragOverActive: false,
        loading: true,
        advisorId: null,

        init() {
            this.advisorId = this.$el.dataset.advisorId;
            this.loadPhotos();
        },

        async loadPhotos() {
            try {
                const response = await fetch(
                    `/api/v1/admin/advisors/${this.advisorId}/photos`
                );
                const data = await response.json();
                this.photos = data.data || [];
                this.loading = false;
            } catch (error) {
                console.error('Photo loading error:', error);
                this.loading = false;
            }
        },

        async uploadPhoto(file) {
            if (!file) return;

            // Validation
            if (!this.validateFile(file)) return;

            this.uploading = true;
            const formData = new FormData();
            formData.append('photo', file);

            try {
                const response = await fetch(
                    `/api/v1/admin/advisors/${this.advisorId}/photos/upload`,
                    {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-CSRF-TOKEN': document
                                .querySelector('[name="_token"]')
                                .value,
                        },
                    }
                );

                const data = await response.json();

                if (!response.ok) {
                    this.showError(
                        data.message || 'Upload başarısız oldu'
                    );
                    return;
                }

                // Add new photo to gallery
                this.photos.push(data.data.photo);

                // Sort by quality (descending)
                this.photos.sort((a, b) => b.quality_score - a.quality_score);

                // Show success message
                this.showSuccess(
                    `📸 Fotoğraf yüklendi! Kalite: ${data.data.analysis.quality_score}/100`
                );

                // Refresh photos
                await this.loadPhotos();
            } catch (error) {
                console.error('Upload error:', error);
                this.showError('Upload sırasında hata oluştu');
            } finally {
                this.uploading = false;
            }
        },

        validateFile(file) {
            const maxSize = 10 * 1024 * 1024; // 10 MB
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if (!allowedTypes.includes(file.type)) {
                this.showError('❌ Dosya türü desteklenmiyor. JPEG, PNG, GIF veya WebP kullanın.');
                return false;
            }

            if (file.size > maxSize) {
                this.showError('❌ Dosya çok büyük (max 10 MB)');
                return false;
            }

            return true;
        },

        async deletePhoto(photoId) {
            if (
                !confirm(
                    '⚠️ Bu fotoğrafı silmek istediğiniz emin misiniz?'
                )
            ) {
                return;
            }

            try {
                const response = await fetch(
                    `/api/v1/admin/advisors/${this.advisorId}/photos/${photoId}`,
                    {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document
                                .querySelector('[name="_token"]')
                                .value,
                        },
                    }
                );

                if (!response.ok) {
                    this.showError('Silme işlemi başarısız');
                    return;
                }

                // Remove from local array
                this.photos = this.photos.filter((p) => p.id !== photoId);
                this.showSuccess('🗑️ Fotoğraf silindi ve sıralama yenilendi');

                // Refresh
                await this.loadPhotos();
            } catch (error) {
                console.error('Delete error:', error);
                this.showError('Silme sırasında hata oluştu');
            }
        },

        onFileSelect(event) {
            const file = event.target.files?.[0];
            if (file) {
                this.uploadPhoto(file);
            }
        },

        onDragOver(event) {
            event.preventDefault();
            this.dragOverActive = true;
        },

        onDragLeave(event) {
            event.preventDefault();
            this.dragOverActive = false;
        },

        async onDrop(event) {
            event.preventDefault();
            this.dragOverActive = false;

            const file = event.dataTransfer?.files?.[0];
            if (file) {
                this.uploadPhoto(file);
            }
        },

        getQualityBadge(score) {
            if (score >= 90) return '⭐ Mükemmel (90+)';
            if (score >= 75) return '✅ İyi (75+)';
            if (score >= 60) return '⚠️ Kabul Edilebilir (60+)';
            return '❌ Düşük (<60)';
        },

        getQualityColor(score) {
            if (score >= 90) return 'bg-green-500';
            if (score >= 75) return 'bg-blue-500';
            if (score >= 60) return 'bg-yellow-500';
            return 'bg-red-500';
        },

        showSuccess(message) {
            // Toast notification
            const toast = document.createElement('div');
            toast.className =
                'fixed top-4 right-4 bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg z-50';
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        },

        showError(message) {
            // Toast notification
            const toast = document.createElement('div');
            toast.className =
                'fixed top-4 right-4 bg-red-500 text-white px-4 py-3 rounded-lg shadow-lg z-50';
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        },
    };
}
