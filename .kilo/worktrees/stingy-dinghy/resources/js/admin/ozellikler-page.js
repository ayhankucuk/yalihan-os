/**
 * Özellikler Sayfası - Merkezi JavaScript
 *
 * Context7: Merkezi sistem kullanımı
 * - BulkOperationsAlpine entegrasyonu
 * - Tab yönetimi
 * - Özel endpoint'ler için override
 */

(function() {
    'use strict';

    if (typeof window.ozelliklerPageData !== 'undefined') {
        return; // Zaten yüklenmiş
    }

    /**
     * Özellikler sayfası için Alpine.js data fonksiyonu
     *
     * @param {number} totalCount - Toplam özellik sayısı
     * @param {string} bulkActionUrl - Bulk action endpoint
     * @param {string} activeTabDefault - Varsayılan aktif tab
     * @returns {Object} Alpine.js x-data objesi
     */
    window.ozelliklerPageData = function(totalCount, bulkActionUrl, activeTabDefault) {
        const bulkActions = window.BulkOperationsAlpine?.createBulkActions(
            totalCount,
            bulkActionUrl,
            {
                checkboxSelector: 'input[name="ids[]"]',
                itemName: 'özellik',
                idsFieldName: 'ids[]',
                selectedItemsKey: 'selectedItems'
            }
        ) || {
            selectedItems: [],
            selectAll: false,
            totalCount: totalCount,
            processing: false,
            toggleAll: function() {},
            toggleSelect: function() {},
            submitBulkAction: async function() {
                window.toast?.error('BulkOperationsAlpine yüklenemedi');
            }
        };

        // Tab yönetimi ekle
        bulkActions.activeTab = activeTabDefault || 'ozellikler';
        bulkActions.setTab = function(tab) {
            this.activeTab = tab;
            window.location.hash = tab;
        };

        // Init metodunu override et
        const originalInit = bulkActions.init || function() {};
        bulkActions.init = function() {
            if (typeof originalInit === 'function') {
                originalInit.call(this);
            }
            // Hash'ten tab'ı al
            if (window.location.hash) {
                this.activeTab = window.location.hash.substring(1);
            }
        };

        return bulkActions;
    };
})();
