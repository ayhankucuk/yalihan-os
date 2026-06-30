/**
 * Portal Manager Alpine.js Component
 *
 * Çoklu portal entegrasyonu ve senkronizasyon yönetimi için Alpine.js bileşeni
 * - Portal seçimi ve yayın programlama
 * - Senkronizasyon statusu takibi
 * - Portal özel fiyatlandırma
 * - Toplu portal işlemleri
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('portalManager', (ilanId) => ({
        // State
        ilan: null,
        ilanId: ilanId,
        loading: false,
        error: null,

        // Portal data
        availablePortals: {},
        selectedPortals: [],
        syncStatus: {},
        portalIds: {},
        portalPricing: {},
        masterPublishEnabled: true,
        scheduledPortals: [],

        // UI state
        showPortalModal: false,
        showPricingModal: false,
        showSyncModal: false,
        activeTab: 'sync', // sync, pricing, schedule

        // Operations
        syncingPortals: new Set(),
        syncResults: {},

        init() {
            this.loadPortalData();
            this.loadSyncStatus();

            // Refresh sync status every 30 seconds
            setInterval(() => {
                this.loadSyncStatus();
            }, 30000);
        },

        // ==================== DATA LOADING ====================

        async loadPortalData() {
            try {
                const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.portals && window.APIConfig.admin.portals.list
                    ? window.APIConfig.admin.portals.list
                    : '/api/portals';
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.availablePortals = data.data.portals;
                } else {
                    this.error = data.message || 'Portal verileri yüklenemedi';
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                console.error('Portal data loading error:', error);
            }
        },

        async loadSyncStatus() {
            this.loading = true;
            this.error = null;

            try {
                const urlS = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.portals && window.APIConfig.admin.portals.status
                    ? window.APIConfig.admin.portals.status(this.ilanId)
                    : `/api/ilanlar/${this.ilanId}/portals/status`;
                const response = await fetch(urlS, {
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.updatePortalState(data.data);
                } else {
                    this.error = data.message || 'Senkronizasyon statusu alınamadı';
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                console.error('Sync status loading error:', error);
            } finally {
                this.loading = false;
            }
        },

        updatePortalState(data) {
            this.portalIds = data.portal_ids || {};
            this.syncStatus = data.sync_status || {};
            this.masterPublishEnabled = data.master_publish_status;
            this.scheduledPortals = data.scheduled_portals || [];

            // Update selected portals based on scheduled portals
            this.selectedPortals = [...this.scheduledPortals];

            // Load portal specific pricing if exists
            this.loadPortalPricing();
        },

        async loadPortalPricing() {
            // Portal pricing is typically loaded with the ilan data
            // This is a placeholder for future implementation
        },

        // ==================== SYNC OPERATIONS ====================

        async syncToPortal(portal) {
            if (this.syncingPortals.has(portal)) return;

            this.syncingPortals.add(portal);
            this.error = null;

            try {
                const urlSync = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.portals && window.APIConfig.admin.portals.sync
                    ? window.APIConfig.admin.portals.sync(this.ilanId, portal)
                    : `/api/ilanlar/${this.ilanId}/portals/sync/${portal}`;
                const response = await fetch(urlSync, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                    },
                });

                const data = await response.json();

                if (data.success) {
                    this.syncResults[portal] = {
                        success: true,
                        message: 'Başarılı',
                    };
                    await this.loadSyncStatus();
                    this.showSuccessMessage(
                        `${this.availablePortals[portal]?.name} senkronizasyonu başarılı!`
                    );
                } else {
                    this.syncResults[portal] = {
                        success: false,
                        message: data.message,
                    };
                    this.error = data.message || 'Senkronizasyon başarısız';
                }
            } catch (error) {
                this.syncResults[portal] = {
                    success: false,
                    message: error.message,
                };
                this.error = 'Ağ hatası: ' + error.message;
                console.error('Sync error:', error);
            } finally {
                this.syncingPortals.delete(portal);
            }
        },

        async syncToAllPortals() {
            this.loading = true;
            this.error = null;

            try {
                const urlSyncAll = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.portals && window.APIConfig.admin.portals.syncAll
                    ? window.APIConfig.admin.portals.syncAll(this.ilanId)
                    : `/api/ilanlar/${this.ilanId}/portals/sync-all`;
                const response = await fetch(urlSyncAll, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                    },
                    body: JSON.stringify({
                        portals: this.selectedPortals,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadSyncStatus();
                    this.showSuccessMessage('Toplu senkronizasyon tamamlandı!');
                } else {
                    this.error = data.message || 'Toplu senkronizasyon başarısız';
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                console.error('Bulk sync error:', error);
            } finally {
                this.loading = false;
            }
        },

        async removeFromPortal(portal) {
            if (
                !confirm(
                    `${this.availablePortals[portal]?.name} portalından ilanı kaldırmak istediğinizden emin misiniz?`
                )
            ) {
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const urlRemove = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.portals && window.APIConfig.admin.portals.remove
                    ? window.APIConfig.admin.portals.remove(this.ilanId, portal)
                    : `/api/ilanlar/${this.ilanId}/portals/${portal}`;
                const response = await fetch(urlRemove, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                    },
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadSyncStatus();
                    this.showSuccessMessage(
                        `${this.availablePortals[portal]?.name} portalından ilan kaldırıldı!`
                    );
                } else {
                    this.error = data.message || 'Portal kaldırma başarısız';
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                console.error('Remove portal error:', error);
            } finally {
                this.loading = false;
            }
        },

        // ==================== PRICING OPERATIONS ====================

        async updatePortalPricing() {
            this.loading = true;
            this.error = null;

            const pricingData = [];

            Object.keys(this.portalPricing).forEach((portal) => {
                const pricing = this.portalPricing[portal];
                if (pricing && pricing.price) {
                    pricingData.push({
                        portal: portal,
                        price: pricing.price,
                        currency: pricing.currency || 'TRY',
                        notes: pricing.notes || null,
                    });
                }
            });

            try {
                const urlPricing = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.portals && window.APIConfig.admin.portals.pricing
                    ? window.APIConfig.admin.portals.pricing(this.ilanId)
                    : `/api/ilanlar/${this.ilanId}/portals/pricing`;
                const response = await fetch(urlPricing, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                    },
                    body: JSON.stringify({
                        pricing: pricingData,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.showPricingModal = false;
                    this.showSuccessMessage('Portal fiyatlandırması güncellendi!');
                } else {
                    this.error = data.message || 'Fiyatlandırma güncellenemedi';
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                console.error('Pricing update error:', error);
            } finally {
                this.loading = false;
            }
        },

        // ==================== SCHEDULE OPERATIONS ====================

        async updatePublishSchedule() {
            this.loading = true;
            this.error = null;

            try {
                const urlSchedule = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.portals && window.APIConfig.admin.portals.schedule
                    ? window.APIConfig.admin.portals.schedule(this.ilanId)
                    : `/api/ilanlar/${this.ilanId}/portals/schedule`;
                const response = await fetch(urlSchedule, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                    },
                    body: JSON.stringify({
                        portals: this.selectedPortals,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.scheduledPortals = this.selectedPortals;
                    this.showSuccessMessage('Portal yayın programı güncellendi!');
                } else {
                    this.error = data.message || 'Program güncellenemedi';
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                console.error('Schedule update error:', error);
            } finally {
                this.loading = false;
            }
        },

        async toggleMasterStatus() {
            this.loading = true;
            this.error = null;

            try {
                const urlMaster = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.portals && window.APIConfig.admin.portals.masterStatus
                    ? window.APIConfig.admin.portals.masterStatus(this.ilanId)
                    : `/api/ilanlar/${this.ilanId}/portals/master-status`;
                const response = await fetch(urlMaster, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                    },
                    body: JSON.stringify({
                        status: this.masterPublishStatus,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.showSuccessMessage('Master yayın statusu güncellendi!');
                } else {
                    this.error = data.message || 'Durum güncellenemedi';
                    // Revert the toggle
                    this.masterPublishStatus = !this.masterPublishStatus;
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                // Revert the toggle
                this.masterPublishStatus = !this.masterPublishStatus;
                console.error('Master status toggle error:', error);
            } finally {
                this.loading = false;
            }
        },

        // ==================== UI HELPERS ====================

        getSyncStatusClass(portal) {
            const status = this.syncStatus[portal];
            if (!status) return 'bg-gray-100 text-gray-800';

            switch (status.status) {
                case 'success':
                    return 'bg-green-100 text-green-800';
                case 'failed':
                case 'error':
                    return 'bg-red-100 text-red-800';
                case 'pending':
                    return 'bg-yellow-100 text-yellow-800';
                default:
                    return 'bg-gray-100 text-gray-800';
            }
        },

        getSyncStatusText(portal) {
            const status = this.syncStatus[portal];
            if (!status) return 'Senkronize edilmemiş';

            switch (status.status) {
                case 'success':
                    return 'Başarılı';
                case 'failed':
                    return 'Başarısız';
                case 'error':
                    return 'Hata';
                case 'pending':
                    return 'Bekliyor';
                default:
                    return 'Bilinmiyor';
            }
        },

        getSyncLastTime(portal) {
            const status = this.syncStatus[portal];
            if (!status || !status.last_sync) return '-';

            return new Date(status.last_sync).toLocaleString('tr-TR');
        },

        isPortalPublished(portal) {
            return this.portalIds.hasOwnProperty(portal) && this.portalIds[portal];
        },

        isPortalSyncing(portal) {
            return this.syncingPortals.has(portal);
        },

        formatCurrency(amount, currency = 'TRY') {
            if (!amount) return '-';
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: currency,
            }).format(amount);
        },

        showSuccessMessage(message) {
            // Alpine.js toast notification or similar
            if (window.toastr) {
                window.toastr.success(message);
            } else {
                alert(message);
            }
        },

        // ==================== MODAL CONTROLS ====================

        openPortalModal() {
            this.showPortalModal = true;
            this.activeTab = 'sync';
        },

        closePortalModal() {
            this.showPortalModal = false;
            this.error = null;
        },

        openPricingModal() {
            // Initialize pricing data for selected portals
            this.selectedPortals.forEach((portal) => {
                if (!this.portalPricing[portal]) {
                    this.portalPricing[portal] = {
                        price: null,
                        currency: 'TRY',
                        notes: null,
                    };
                }
            });
            this.showPricingModal = true;
        },

        closePricingModal() {
            this.showPricingModal = false;
            this.error = null;
        },

        // ==================== COMPUTED PROPERTIES ====================

        get publishedPortalCount() {
            return Object.keys(this.portalIds).length;
        },

        get successfulSyncs() {
            return Object.values(this.syncStatus).filter((status) => status.status === 'success')
                .length;
        },

        get failedSyncs() {
            return Object.values(this.syncStatus).filter(
                (status) => status.status === 'failed' || status.status === 'error'
            ).length;
        },

        get syncSuccessRate() {
            const total = Object.keys(this.syncStatus).length;
            if (total === 0) return 0;
            return Math.round((this.successfulSyncs / total) * 100);
        },

        get canSyncToPortals() {
            return this.masterPublishStatus && this.selectedPortals.length > 0;
        },

        get hasPortalPricing() {
            return Object.keys(this.portalPricing).some(
                (portal) => this.portalPricing[portal] && this.portalPricing[portal].price
            );
        },
    }));
});
