// publication.js - Yayın Yönetimi

/**
 * Publication Manager
 * İlan yayınlama ve durum yönetimi
 */
window.publicationManager = function () {
    return {
        // Yayın durumu
        durum: 'Taslak',
        publishDate: null,
        expiryDate: null,

        // Görünürlük ayarları
        isVisible: true,
        showOnWebsite: true,
        showOnMobile: true,

        // Öncelik
        priority: 'normal',
        isFeatured: false,
        isUrgent: false,

        // Portal durumları (Context7 uyumlu)
        portalDurumlari: {
            sahibinden: { aktif: false, durum_anahtari: 'pending', message: '' },
            hepsiemlak: { aktif: false, durum_anahtari: 'pending', message: '' },
            emlakjet: { aktif: false, durum_anahtari: 'pending', message: '' },
            zingat: { aktif: false, durum_anahtari: 'pending', message: '' },
            hurriyetemlak: { aktif: false, durum_anahtari: 'pending', message: '' },
            emlak365: { aktif: false, durum_anahtari: 'pending', message: '' },
        },

        /**
         * Durumu değiştir
         */
        durumDegistir(yeniDurum) {
            const gecerliDurumlar = ['Taslak', 'Aktif', 'Pasif', 'Beklemede'];

            if (gecerliDurumlar.includes(yeniDurum)) {
                this.durum = yeniDurum;

                if (yeniDurum === 'Aktif' && !this.publishDate) {
                    this.publishDate = new Date().toISOString().split('T')[0];
                }

                window.toast?.success(`Durum: ${yeniDurum}`);
            }
        },

        /**
         * Hemen yayınla
         */
        publishNow() {
            this.durum = 'Aktif';
            this.publishDate = new Date().toISOString().split('T')[0];
            this.isVisible = true;
            this.showOnWebsite = true;

            window.toast?.success('İlan aktif olarak ayarlandı');
        },

        /**
         * Taslak olarak kaydet
         */
        saveAsDraft() {
            this.durum = 'Taslak';
            window.toast?.info('Taslak olarak kaydedilecek');
        },

        /**
         * Öne çıkar
         */
        toggleFeatured() {
            this.isFeatured = !this.isFeatured;

            if (this.isFeatured) {
                window.toast?.success('İlan öne çıkarıldı');
            }
        },

        /**
         * Acil ilan yap
         */
        toggleUrgent() {
            this.isUrgent = !this.isUrgent;

            if (this.isUrgent) {
                this.priority = 'high';
                window.toast?.success('Acil ilan olarak işaretlendi');
            } else {
                this.priority = 'normal';
            }
        },

        init() {
            console.log('Publication manager initialized');
        },
    };
};

// Export
export default window.publicationManager;
