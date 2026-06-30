// portals.js - Portal Yönetimi Modülü

/**
 * Modern Portal Selector
 * 6 portal için entegrasyon yönetimi
 */
window.modernPortalSelector = function () {
    return {
        // Portal states
        portals: {
            sahibinden: { durum: false, id: '', price: '' },
            hepsiemlak: { durum: false, id: '', price: '' },
            emlakjet: { durum: false, id: '', price: '' },
            zingat: { durum: false, id: '', price: '' },
            hurriyetemlak: { durum: false, id: '', price: '' },
            emlak365: { durum: false, id: '', price: '' },
        },

        // Portal durumları (sync durumu)
        portalDurumlari: {
            sahibinden: { durum_anahtarı: 'pending', message: '', aktif: false },
            hepsiemlak: { durum_anahtarı: 'pending', message: '', aktif: false },
            emlakjet: { durum_anahtarı: 'pending', message: '', aktif: false },
            zingat: { durum_anahtarı: 'pending', message: '', aktif: false },
            hurriyetemlak: { durum_anahtarı: 'pending', message: '', aktif: false },
            emlak365: { durum_anahtarı: 'pending', message: '', aktif: false },
        },

        allSelected: false,

        /**
         * Seçili portal sayısı
         */
        get selectedPortalCount() {
            return Object.values(this.portals).filter((p) => p.durum).length;
        },

        /**
         * Tümünü seç/kaldır
         */
        toggleAll() {
            this.allSelected = !this.allSelected;
            Object.keys(this.portals).forEach((key) => {
                this.portals[key].durum = this.allSelected;
            });
        },

        /**
         * Portal toggle
         */
        togglePortal(portalName) {
            this.portals[portalName].durum = !this.portals[portalName].durum;
            this.updateAllSelectedState();
        },

        /**
         * All selected state güncelle
         */
        updateAllSelectedState() {
            this.allSelected = Object.values(this.portals).every((p) => p.durum);
        },

        /**
         * Portal fiyatı güncelle
         */
        updatePortalPrice(portalName, price) {
            this.portals[portalName].price = price;
        },

        /**
         * Senkronizasyon başlat
         */
        async syncToPortals() {
            const seciliPortallar = Object.entries(this.portals)
                .filter(([key, portal]) => portal.durum)
                .map(([key]) => key);

            if (seciliPortallar.length === 0) {
                window.toast?.warning('Lütfen en az bir portal seçin');
                return;
            }

            window.toast?.info(`${seciliPortallar.length} portala senkronizasyon başlatılıyor...`);

            // Simulate sync (gerçek API entegrasyonu için)
            seciliPortallar.forEach((portalName) => {
                this.portalDurumlari[portalName] = {
                    durum_anahtarı: 'syncing',
                    message: 'Senkronize ediliyor...',
                    aktif: true,
                };
            });

            // TODO: Gerçek API çağrısı
            console.log('Syncing to portals:', seciliPortallar);
        },

        init() {
            console.log('Portal selector initialized');
        },
    };
};

// Export for Vite
export default window.modernPortalSelector;
