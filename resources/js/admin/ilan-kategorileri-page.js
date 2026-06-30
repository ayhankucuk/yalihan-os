/**
 * İlan Kategorileri Sayfası - Merkezi JavaScript
 *
 * Context7: Merkezi sistem kullanımı
 * - BulkOperationsAlpine entegrasyonu
 * - Filter yönetimi
 * - Bulk actions
 */

(function () {
    'use strict';

    if (typeof window.ilanKategorileriPageData !== 'undefined') {
        return; // Zaten yüklenmiş
    }

    /**
     * İlan Kategorileri sayfası için Alpine.js data fonksiyonu
     *
     * @param {number} totalCount - Toplam kategori sayısı
     * @param {string} bulkActionUrl - Bulk action endpoint
     * @param {Array} kategoriIds - Kategori ID'leri array'i
     * @param {Object} initialFilters - Başlangıç filtreleri
     * @returns {Object} Alpine.js x-data objesi
     */
    window.ilanKategorileriPageData = function (
        totalCount,
        bulkActionUrl,
        kategoriIds = [],
        initialFilters = {}
    ) {
        const bulkActions =
            window.BulkOperationsAlpine?.createBulkActions(totalCount, bulkActionUrl, {
                checkboxSelector: 'input[name="kategori_ids[]"]',
                itemName: 'kategori',
                idsFieldName: 'kategori_ids[]',
                selectedItemsKey: 'selectedItems',
            }) ||
            kategorilerManagerFallback(totalCount, bulkActionUrl, kategoriIds, initialFilters);

        // Filter yönetimi ekle
        bulkActions.filters = {
            search: initialFilters.search || '',
            ana_kategori: initialFilters.ana_kategori || '',
            seviye: initialFilters.seviye || '',
            aktiflik_durumu: initialFilters.aktiflik_durumu || '',
        };

        bulkActions.applyFilters = function () {
            this.loading = true;

            const params = new URLSearchParams();
            if (this.filters.search) params.append('search', this.filters.search);
            if (this.filters.ana_kategori) params.append('ana_kategori', this.filters.ana_kategori);
            if (this.filters.seviye) params.append('seviye', this.filters.seviye);
            if (this.filters.aktiflik_durumu !== '')
                params.append('aktiflik_durumu', this.filters.aktiflik_durumu);

            const indexUrl =
                window.APIConfig?.ilanKategorileri?.index || '/admin/ilan-kategorileri';
            const url = `${indexUrl}?${params.toString()}`;

            try {
                window.location.href = url;
            } catch (error) {
                console.error('Filter uygulanırken hata:', error);
                window.toast?.error('Filtreler uygulanırken hata oluştu');
            } finally {
                this.loading = false;
            }
        };

        bulkActions.clearFilters = function () {
            this.filters = {
                search: '',
                ana_kategori: '',
                seviye: '',
                aktiflik_durumu: '',
            };
            this.applyFilters();
        };

        bulkActions.submitFilters = function () {
            this.applyFilters();
        };

        bulkActions.applyInitialFilters = function () {
            // Initial filter setup if needed
        };

        // Bulk action override - modern confirm dialog kullan
        const originalSubmitBulkAction = bulkActions.submitBulkAction;
        bulkActions.submitBulkAction = async function (action) {
            if (this.selectedItems.length === 0) {
                window.toast?.error('Lütfen en az bir kategori seçin');
                return;
            }

            const actionMessages = {
                activate: 'etkinleştirmek',
                deactivate: 'pasifleştirmek',
                delete: 'silmek',
            };

            // Modern confirm dialog kullan
            const confirmed = await (window.UIHelpers?.confirm || confirm)(
                `Seçili ${this.selectedItems.length} kategoriyi ${actionMessages[action]} istediğinizden emin misiniz?`,
                {
                    title: 'Toplu İşlem Onayı',
                    confirmText: action === 'delete' ? 'Sil' : 'Onayla',
                    cancelText: 'İptal',
                    confirmClass:
                        action === 'delete'
                            ? 'bg-red-600 hover:bg-red-700 text-white'
                            : 'bg-blue-600 hover:bg-blue-700 text-white',
                }
            );

            if (!confirmed) {
                return;
            }

            // Original bulk action'ı çağır
            if (typeof originalSubmitBulkAction === 'function') {
                await originalSubmitBulkAction.call(this, action);
            }
        };

        // Delete kategori fonksiyonu ekle
        bulkActions.deleteKategori = async function (id, name) {
            const confirmed = await (window.UIHelpers?.confirm || confirm)(
                `"${name}" kategorisini silmek istediğinizden emin misiniz?`,
                {
                    title: 'Kategori Silme Onayı',
                    confirmText: 'Sil',
                    cancelText: 'İptal',
                    confirmClass: 'bg-red-600 hover:bg-red-700 text-white',
                }
            );

            if (!confirmed) {
                return;
            }

            this.processing = true;

            try {
                const formData = new FormData();
                formData.append(
                    '_token',
                    document.querySelector('meta[name="csrf-token"]')?.content || ''
                );
                formData.append('_method', 'DELETE');

                const deleteUrl =
                    window.APIConfig?.ilanKategorileri?.destroy?.(id) ||
                    `/admin/ilan-kategorileri/${id}`;
                const response = await fetch(deleteUrl, {
                    method: 'POST',
                    body: formData,
                });

                if (response.ok) {
                    window.toast?.success('Kategori başarıyla silindi');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    throw new Error('Silme işlemi başarısız');
                }
            } catch (error) {
                console.error('Silme hatası:', error);
                window.toast?.error('Kategori silinirken hata oluştu');
            } finally {
                this.processing = false;
            }
        };

        // Init metodunu override et
        const originalInit = bulkActions.init || function () {};
        bulkActions.init = function () {
            if (typeof originalInit === 'function') {
                originalInit.call(this);
            }
            this.applyInitialFilters();
        };

        return bulkActions;
    };

    /**
     * Fallback manager (BulkOperationsAlpine yüklenemezse)
     */
    function kategorilerManagerFallback(totalCount, bulkActionUrl, kategoriIds, initialFilters) {
        return {
            contentLoaded: false,
            loading: false,
            processing: false,
            selectedItems: [],
            filters: {
                search: initialFilters.search || '',
                ana_kategori: initialFilters.ana_kategori || '',
                seviye: initialFilters.seviye || '',
                aktiflik_durumu: initialFilters.aktiflik_durumu || '',
            },

            get isAllSelected() {
                return totalCount > 0 && this.selectedItems.length === totalCount;
            },

            get isPartiallySelected() {
                return this.selectedItems.length > 0 && !this.isAllSelected;
            },

            init() {
                this.applyInitialFilters();
            },

            toggleSelectAll() {
                if (this.isAllSelected) {
                    this.selectedItems = [];
                } else {
                    this.selectedItems = [...kategoriIds];
                }
            },

            toggleItemSelection(itemId) {
                const index = this.selectedItems.indexOf(itemId);
                if (index > -1) {
                    this.selectedItems.splice(index, 1);
                } else {
                    this.selectedItems.push(itemId);
                }
            },

            clearSelection() {
                this.selectedItems = [];
            },

            async applyFilters() {
                this.loading = true;
                const params = new URLSearchParams();
                if (this.filters.search) params.append('search', this.filters.search);
                if (this.filters.ana_kategori)
                    params.append('ana_kategori', this.filters.ana_kategori);
                if (this.filters.seviye) params.append('seviye', this.filters.seviye);
                if (this.filters.aktiflik_durumu !== '')
                    params.append('aktiflik_durumu', this.filters.aktiflik_durumu);

                const indexUrl =
                    window.APIConfig?.ilanKategorileri?.index || '/admin/ilan-kategorileri';
                const url = `${indexUrl}?${params.toString()}`;

                try {
                    window.location.href = url;
                } catch (error) {
                    console.error('Filter uygulanırken hata:', error);
                    window.toast?.error('Filtreler uygulanırken hata oluştu');
                } finally {
                    this.loading = false;
                }
            },

            clearFilters() {
                this.filters = {
                    search: '',
                    ana_kategori: '',
                    seviye: '',
                    aktiflik_durumu: '',
                };
                this.applyFilters();
            },

            submitFilters() {
                this.applyFilters();
            },

            applyInitialFilters() {
                // Initial filter setup if needed
            },

            async submitBulkAction(action) {
                if (this.selectedItems.length === 0) {
                    window.toast?.error('Lütfen en az bir kategori seçin');
                    return;
                }

                const actionMessages = {
                    activate: 'etkinleştirmek',
                    deactivate: 'pasifleştirmek',
                    delete: 'silmek',
                };

                const confirmed = await (window.UIHelpers?.confirm || confirm)(
                    `Seçili ${this.selectedItems.length} kategoriyi ${actionMessages[action]} istediğinizden emin misiniz?`
                );

                if (!confirmed) {
                    return;
                }

                this.processing = true;
                try {
                    const formData = new FormData();
                    formData.append(
                        '_token',
                        document.querySelector('meta[name="csrf-token"]')?.content || ''
                    );
                    formData.append('action', action);
                    this.selectedItems.forEach((id) => {
                        formData.append('kategori_ids[]', id);
                    });

                    const response = await fetch(bulkActionUrl, {
                        method: 'POST',
                        body: formData,
                    });

                    if (response.ok) {
                        let updatedIds = [];
                        try {
                            const json = await response.json();
                            if (json && json.updated_ids) updatedIds = json.updated_ids;
                        } catch {}

                        if (updatedIds.length > 0) {
                            updatedIds.forEach((id) => {
                                const row = document.querySelector(`tr[data-kategori-id='${id}']`);
                                if (row) {
                                    row.classList.add('bg-green-50', 'dark:bg-green-900/20');
                                    setTimeout(
                                        () =>
                                            row.classList.remove(
                                                'bg-green-50',
                                                'dark:bg-green-900/20'
                                            ),
                                        2000
                                    );
                                }
                            });
                        }

                        window.toast?.success('Toplu işlem başarıyla tamamlandı');
                        this.selectedItems = [];
                    } else {
                        throw new Error('İşlem başarısız');
                    }
                } catch (error) {
                    console.error('Toplu işlem hatası:', error);
                    window.toast?.error('Toplu işlem sırasında hata oluştu');
                } finally {
                    this.processing = false;
                }
            },

            async deleteKategori(id, name) {
                const confirmed = await (window.UIHelpers?.confirm || confirm)(
                    `"${name}" kategorisini silmek istediğinizden emin misiniz?`
                );

                if (!confirmed) {
                    return;
                }

                this.processing = true;

                try {
                    const formData = new FormData();
                    formData.append(
                        '_token',
                        document.querySelector('meta[name="csrf-token"]')?.content || ''
                    );
                    formData.append('_method', 'DELETE');

                    const deleteUrl =
                        window.APIConfig?.ilanKategorileri?.destroy?.(id) ||
                        `/admin/ilan-kategorileri/${id}`;
                    const response = await fetch(deleteUrl, {
                        method: 'POST',
                        body: formData,
                    });

                    if (response.ok) {
                        window.toast?.success('Kategori başarıyla silindi');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        throw new Error('Silme işlemi başarısız');
                    }
                } catch (error) {
                    console.error('Silme hatası:', error);
                    window.toast?.error('Kategori silinirken hata oluştu');
                } finally {
                    this.processing = false;
                }
            },
        };
    }
})();
