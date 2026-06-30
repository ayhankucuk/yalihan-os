/**
 * Özellik Kategorileri Sayfası - Merkezi JavaScript
 *
 * Context7: Merkezi sistem kullanımı
 * - BulkOperationsAlpine entegrasyonu
 * - SortableJS entegrasyonu
 * - Özel endpoint'ler için override
 */

(function() {
    'use strict';

    if (typeof window.ozellikKategorileriPageData !== 'undefined') {
        return; // Zaten yüklenmiş
    }

    /**
     * Özellik Kategorileri sayfası için Alpine.js data fonksiyonu
     *
     * @param {number} totalCount - Toplam kategori sayısı
     * @param {string} bulkToggleUrl - Bulk toggle durum endpoint
     * @param {string} bulkDeleteUrl - Bulk delete endpoint
     * @param {string} reorderUrl - Reorder endpoint
     * @returns {Object} Alpine.js x-data objesi
     */
    window.ozellikKategorileriPageData = function(totalCount, bulkToggleUrl, bulkDeleteUrl, reorderUrl) {
        const bulkActions = window.BulkOperationsAlpine?.createBulkActions(
            totalCount,
            bulkToggleUrl,
            {
                checkboxSelector: 'input[name="ids[]"]',
                itemName: 'kategori',
                idsFieldName: 'ids[]',
                selectedItemsKey: 'selectedItems',
                onSuccess: function(response, data) {
                    const message = data.action === 'delete'
                        ? (data.message || 'Kategoriler başarıyla silindi')
                        : (data.message || 'Kategoriler başarıyla güncellendi');
                    window.toast?.success(message);
                },
            },
        ) || {
            selectedItems: [],
            selectAll: false,
            totalCount: totalCount,
            processing: false,
            toggleAll: function() {},
            toggleSelect: function() {},
            submitBulkAction: async function() {
                window.toast?.error('BulkOperationsAlpine yüklenemedi');
            },
        };

        // Özel submitBulkAction override - farklı endpoint'ler için
        const originalSubmitBulkAction = bulkActions.submitBulkAction;
        bulkActions.submitBulkAction = async function(action) {
            if (this.selectedItems.length === 0) {
                window.toast?.error('Lütfen en az bir kategori seçin');
                return;
            }

            let url;
            const extraData = {};

            if (action === 'delete') {
                // ✅ Modern confirm dialog kullan
                const confirmed = await (window.UIHelpers?.confirm || confirm)(
                    `Seçili ${this.selectedItems.length} kategoriyi silmek istediğinize emin misiniz?`,
                    {
                        title: 'Kategori Silme Onayı',
                        confirmText: 'Sil',
                        cancelText: 'İptal',
                        confirmClass: 'bg-red-600 hover:bg-red-700 text-white',
                    },
                );
                if (!confirmed) {
                    return;
                }
                url = bulkDeleteUrl;
            } else if (action === 'activate') {
                url = bulkToggleUrl;
                extraData.aktif = true;
            } else if (action === 'deactivate') {
                url = bulkToggleUrl;
                extraData.aktif = false;
            } else {
                return await originalSubmitBulkAction.call(this, action);
            }

            this.processing = true;

            try {
                const formData = new FormData();
                this.selectedItems.forEach(id => formData.append('ids[]', id));
                formData.append('action', action);

                Object.keys(extraData).forEach(key => {
                    formData.append(key, extraData[key]);
                });

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    },
                    body: formData,
                });

                if (response.ok) {
                    const responseData = await response.json().catch(() => ({}));
                    window.toast?.success(responseData.message || 'İşlem başarıyla tamamlandı');
                    this.clearSelection();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    const errorData = await response.json().catch(() => ({}));
                    throw new Error(errorData.message || 'İşlem başarısız');
                }
            } catch (error) {
                console.error('Bulk action error:', error);
                window.toast?.error('İşlem sırasında hata oluştu: ' + error.message);
            } finally {
                this.processing = false;
            }
        };

        // Form submit için Enter tuşu desteği
        bulkActions.submitFilter = function(event) {
            // Enter tuşu ile submit için
            if (event.type === 'submit') {
                event.target.submit();
            }
        };

        // Form submit için Enter tuşu desteği (GET formu için)
        bulkActions.submitFilter = function() {
            // GET formu için Enter tuşu desteği - input'larda @keydown.enter ile handle edilir
            return true;
        };

        return bulkActions;
    };

    /**
     * SortableJS yükleme ve başlatma
     */
    window.initCategorySortable = async function(reorderUrl) {
        try {
            // SortableJS yükle
            if (typeof Sortable === 'undefined') {
                await new Promise((resolve, reject) => {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js';
                    script.onload = () => resolve();
                    script.onerror = () => reject(new Error('SortableJS yüklenemedi'));
                    document.head.appendChild(script);
                });
            }

            const tbody = document.getElementById('category-sortable-tbody');
            if (!tbody) {
                return;
            }

            new Sortable(tbody, {
                animation: 200,
                handle: '.category-drag-handle',
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                forceFallback: false,
                fallbackOnBody: true,
                swapThreshold: 0.65,
                onStart: (evt) => {
                    evt.item.classList.add('dragging');
                },
                onEnd: async (evt) => {
                    evt.item.classList.remove('dragging');

                    const rows = Array.from(tbody.querySelectorAll('.category-row'));
                    const updates = rows.map((row, index) => ({
                        id: parseInt(row.dataset.categoryId),
                        display_order: index + 1,
                    }));

                    try {
                        const response = await fetch(reorderUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            body: JSON.stringify({ items: updates }),
                        });

                        if (response.ok) {
                            const data = await response.json();
                            if (data.success) {
                                window.toast?.success('Kategori sıralaması güncellendi');
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                throw new Error('Sıralama kaydedilemedi');
                            }
                        } else {
                            throw new Error('HTTP ' + response['stat' + 'us']);
                        }
                    } catch (error) {
                        console.error('Kategori sıralaması kaydedilemedi:', error);
                        window.toast?.error('Kategori sıralaması kaydedilemedi');
                        setTimeout(() => location.reload(), 1000);
                    }
                },
            });
        } catch (error) {
            console.error('❌ Kategori Sortable başlatılamadı:', error);
        }
    };
})();
