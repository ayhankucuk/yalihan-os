/**
 * ✅ Context7: Smart Photo Sorting for Ilan Management
 * Uses SortableJS for drag-and-drop reordering with auto-cover selection
 */

document.addEventListener('DOMContentLoaded', function() {
    const photoGrid = document.getElementById('sortable-photo-grid');
    
    if (!photoGrid) return;
    
    const ilanId = photoGrid.dataset.ilanId;
    
    // Initialize SortableJS
    const sortable = Sortable.create(photoGrid, {
        animation: 150,
        handle: '.photo-drag-handle',
        ghostClass: 'opacity-50',
        dragClass: 'shadow-2xl',
        
        onEnd: function(evt) {
            const photoIds = [];
            photoGrid.querySelectorAll('[data-photo-id]').forEach(item => {
                photoIds.push(parseInt(item.dataset.photoId));
            });
            
            // Background AJAX request
            axios.post(`/admin/ilanlar/${ilanId}/reorder-photos`, {
                photo_ids: photoIds
            })
            .then(response => {
                if (response.data.success) {
                    // Toast notification
                    showToast('✅ ' + response.data.message, 'success');
                    
                    // Update cover badge
                    updateCoverBadge();
                }
            })
            .catch(error => {
                console.error('Photo reorder error:', error);
                showToast('❌ Sıralama hatası', 'error');
                
                // Revert on error
                sortable.option('disabled', true);
                location.reload();
            });
        }
    });
    
    /**
     * Update cover photo badge (first photo gets crown icon)
     */
    function updateCoverBadge() {
        photoGrid.querySelectorAll('.cover-badge').forEach((badge, index) => {
            if (index === 0) {
                badge.classList.remove('hidden');
                badge.innerHTML = '👑 Kapak';
            } else {
                badge.classList.add('hidden');
            }
        });
    }
    
    /**
     * Toast notification helper
     */
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 z-50 ${
            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
        }`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }
});
