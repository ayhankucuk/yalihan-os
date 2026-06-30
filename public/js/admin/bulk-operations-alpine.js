/**
 * Context7 Alpine.js Bulk Operations Manager
 *
 * Merkezi bulk operations sistemi - Alpine.js ile uyumlu
 * Tüm bulk action işlemleri için tek bir fonksiyon
 *
 * @version 1.0.0
 * @since 2025-12-09
 */

window.BulkOperationsAlpine = {
    /**
     * Alpine.js x-data için bulk operations fonksiyonu
     *
     * @param {number} totalCount - Toplam item sayısı
     * @param {string} bulkActionUrl - Bulk action endpoint URL
     * @param {Object} options - Ek seçenekler
     * @returns {Object} Alpine.js x-data objesi
     */
    createBulkActions(totalCount, bulkActionUrl, options = {}) {
        const {
            checkboxSelector = '.feature-checkbox',
            itemName = 'özellik',
            confirmDelete = true,
            onSuccess = null,
            onError = null,
            idsFieldName = 'ids[]',
            selectedItemsKey = 'selectedIds',
            useFormData = true,
            customHeaders = null
        } = options;

        const stateKey = selectedItemsKey;

        return {
            [stateKey]: [],
            selectAll: false,
            totalCount: totalCount,
            processing: false,

            init() {
                this.$watch(stateKey, (value) => {
                    this.selectAll = value.length === this.totalCount && this.totalCount > 0;
                });
            },

            toggleAll() {
                this.selectAll = !this.selectAll;
                const checkboxes = document.querySelectorAll(checkboxSelector);
                this[stateKey] = this.selectAll ? Array.from(checkboxes).map(cb => cb.value) : [];
            },

            toggleSelect(id) {
                const items = this[stateKey];
                if (items.includes(id)) {
                    this[stateKey] = items.filter(i => i !== id);
                } else {
                    this[stateKey] = [...items, id];
                }
                this.selectAll = this[stateKey].length === this.totalCount && this.totalCount > 0;
            },

            async submitBulkAction(action) {
                const selectedItems = this[stateKey];

                if (selectedItems.length === 0) {
                    window.toast?.error(`Lütfen en az bir ${itemName} seçin!`);
                    return;
                }

                if (action === 'delete' && confirmDelete) {
                    const confirmed = confirm(`${selectedItems.length} ${itemName} silmek istediğinizden emin misiniz?`);
                    if (!confirmed) {
                        return;
                    }
                }

                this.processing = true;

                try {
                    // Context7: bulkActionUrl parametre olarak geliyor, direkt kullan
                    const url = bulkActionUrl;
                    const headers = customHeaders || {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    };

                    let body;
                    if (useFormData) {
                        const formData = new FormData();
                        formData.append('action', action);
                        formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                        selectedItems.forEach(id => formData.append(idsFieldName, id));
                        body = formData;
                    } else {
                        headers['Content-Type'] = 'application/json';
                        body = JSON.stringify({
                            action: action,
                            ids: selectedItems
                        });
                    }

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: headers,
                        body: body,
                    });

                    if (response.ok) {
                        const responseData = await response.json().catch(() => ({}));
                        window.toast?.success(responseData.message || 'İşlem başarıyla tamamlandı');

                        if (onSuccess) {
                            onSuccess(response, responseData);
                        } else {
                            this[stateKey] = [];
                            setTimeout(() => location.reload(), 1000);
                        }
                    } else {
                        const errorData = await response.json().catch(() => ({}));
                        throw new Error(errorData.message || 'İşlem başarısız');
                    }
                } catch (error) {
                    console.error('Bulk action error:', error);
                    window.toast?.error('İşlem sırasında hata oluştu: ' + error.message);

                    if (onError) {
                        onError(error);
                    }
                } finally {
                    this.processing = false;
                }
            }
        };
    }
};
