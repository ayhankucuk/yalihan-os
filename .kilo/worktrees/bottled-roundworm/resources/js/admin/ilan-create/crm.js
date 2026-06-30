// crm.js - CRM ve Kişi Yönetimi

/**
 * Person CRM Manager
 * Kişi seçimi ve CRM entegrasyonu
 */
window.personCrmManager = function () {
    return {
        // Seçili kişiler
        selectedOwner: null,
        selectedAgent: null,
        selectedRelated: null,

        // Kişi bilgileri
        ownerInfo: null,
        agentInfo: null,

        // CRM skorları
        crmScore: 0,
        crmInsights: [],

        /**
         * Kişi seç
         */
        selectPerson(type, personId) {
            if (type === 'owner') {
                this.selectedOwner = personId;
                this.loadPersonInfo(personId, 'owner');
            } else if (type === 'agent') {
                this.selectedAgent = personId;
                this.loadPersonInfo(personId, 'agent');
            } else if (type === 'related') {
                this.selectedRelated = personId;
            }
        },

        /**
         * Kişi bilgilerini yükle
         */
        async loadPersonInfo(personId, type) {
            try {
                const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.person
                    ? window.APIConfig.admin.person.detail(personId)
                    : `/api/kisiler/${personId}`;
                const response = await fetch(url);
                const data = await response.json();

                if (data.success) {
                    if (type === 'owner') {
                        this.ownerInfo = data.data;
                    } else if (type === 'agent') {
                        this.agentInfo = data.data;
                    }

                    // CRM analizi
                    this.calculateCRMScore(data.data);
                }
            } catch (error) {
                console.error('Person info load error:', error);
            }
        },

        /**
         * CRM skoru hesapla
         */
        calculateCRMScore(person) {
            // Basit CRM skoru (0-100)
            let score = 50; // Base score

            if (person.total_listings > 10) score += 20;
            if (person.successful_sales > 5) score += 15;
            if (person.last_activity_days < 30) score += 15;

            this.crmScore = Math.min(100, score);

            // Insights
            this.crmInsights = [
                `${person.total_listings || 0} toplam ilan`,
                `${person.successful_sales || 0} başarılı satış`,
                `Son aktivite: ${person.last_activity_days || '-'} gün önce`,
            ];
        },

        /**
         * Yeni kişi ekle (modal)
         */
        async addNewPerson(type) {
            window.toast?.info("Yeni kişi ekleme modal'ı açılıyor...");
            // TODO: Modal implement edilecek
        },

        init() {
            console.log('CRM manager initialized');
        },
    };
};

// Export
export default window.personCrmManager;
