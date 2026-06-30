const emptyAiResult = () => ({
    zorunlu_alanlar: [],
    opsiyonel_alanlar: [],
    validasyon_kurallari: {},
    ui_ipuclari: {},
});

export default function aiDescriptionGenerator() {
    return {
        aiLoading: false,
        aiModalOpen: false,
        generatedDescription: '',
        aiError: null,

        // Safe AI Result Schema (SSOT via helper)
        aiResult: emptyAiResult(),

        // Context Data
        context: {
            kategori_id: '',
            il_id: '',
            ilce_id: '',
            mahalle_id: '',
            metrekare: '',
            oda_sayisi: '',
            fiyat: '',
        },

        // Loading State
        loadingStep: 0,
        loadingInterval: null,
        loadingMessages: [
            'Konum analiz ediliyor...',
            'Bölge verileri taranıyor...',
            'Özellikler işleniyor...',
            'İlan metni kurgulanıyor...',
            'Son dokunuşlar yapılıyor...',
        ],

        init() {
            // Listen for wizard events if needed
        },

        openAiModal() {
            // Collect fresh data from DOM or Wizard store
            this.collectContext();

            const validation = this.validateContext();

            if (!validation.valid) {
                if (window.toast) {
                    const missingList = validation.missing.join(', ');
                    window.toast.error(
                        `AI asistanı için lütfen şu bilgileri girin: <strong>${missingList}</strong>`,
                        { duration: 4000 } // Longer duration for reading
                    );
                } else {
                    alert('Lütfen temel bilgileri doldurun: ' + validation.missing.join(', '));
                }
                return;
            }

            this.aiModalOpen = true;
            this.generatedDescription = '';
            this.aiError = null;

            // Auto-start generation if empty
            this.generateDescription();
        },

        collectContext() {
            // [SAB ENFORCEMENT]: Alpine Store'dan veri cek (Deterministic Data Flow)
            // DOM getElementById yerine merkezi state kullanilir.
            // Fallback olarak DOM okunur ama bu gecici bir uyumluluk katmanidir.
            const store = window.Alpine?.store?.('listing') || {};
            const wizardStore = window.Alpine?.store?.('wizard') || {};

            // Helper: Store'dan al, yoksa DOM'dan oku (gecis donemi)
            const getFromStoreOrDom = (storeKey, domId) => {
                if (store[storeKey]) return store[storeKey];
                if (wizardStore[storeKey]) return wizardStore[storeKey];
                return document.getElementById(domId)?.value || '';
            };

            this.context = {
                kategori_id: getFromStoreOrDom('alt_kategori_id', 'alt_kategori_id')
                    || getFromStoreOrDom('ana_kategori_id', 'ana_kategori_id'),
                il_id: getFromStoreOrDom('il_id', 'il_id'),
                ilce_id: getFromStoreOrDom('ilce_id', 'ilce_id'),
                mahalle_id: getFromStoreOrDom('mahalle_id', 'mahalle_id'),
                metrekare: getFromStoreOrDom('metrekare_brut', 'metrekare_brut')
                    || getFromStoreOrDom('metrekare_net', 'metrekare_net'),
                oda_sayisi: getFromStoreOrDom('oda_sayisi_id', 'oda_sayisi_id'),
                fiyat: getFromStoreOrDom('fiyat', 'fiyat'),
                baslik: getFromStoreOrDom('baslik', 'baslik'),
            };
        },

        validateContext() {
            const missing = [];

            if (!this.context.kategori_id) missing.push('Kategori');
            if (!this.context.il_id) missing.push('İl');
            if (!this.context.ilce_id) missing.push('İlçe');
            if (!this.context.metrekare) missing.push('m²');

            return {
                valid: missing.length === 0,
                missing: missing,
            };
        },

        // Helper for UI/Alpine binding
        isContextValid() {
            this.collectContext();
            return this.validateContext().valid;
        },

        async generateDescription() {
            this.aiLoading = true;
            this.aiError = null;
            this.generatedDescription = '';

            // Start Loading Animation
            this.loadingStep = 0;
            this.loadingInterval = setInterval(() => {
                this.loadingStep = (this.loadingStep + 1) % this.loadingMessages.length;
            }, 1500);

            try {
                // Get CSRF token
                const token = document.querySelector('meta[name="csrf-token"]')?.content;

                const response = await fetch('/api/ai/generate-description', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify({
                        features: this.context,
                        tone: 'professional',
                    }),
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || result.message || 'Bir hata oluştu');
                }

                if (result.success && result.data?.description) {
                    this.generatedDescription = result.data.description;
                } else {
                    throw new Error('AI yanıtı geçersiz formatta.');
                }
            } catch (error) {
                console.error('AI Description Error:', error);
                this.aiError = error.message;
                if (window.toast) window.toast.error('Açıklama üretilemedi: ' + error.message);
            } finally {
                this.aiLoading = false;
                if (this.loadingInterval) {
                    clearInterval(this.loadingInterval);
                    this.loadingInterval = null;
                }
            }
        },

        acceptDescription() {
            // [SAB ENFORCEMENT]: Event-driven yazi aktarimi
            // DOM'a dogrudan yazmak yerine event dispatch ederek
            // diger Alpine componentlerinin haberi olmasini sagliyoruz.
            const description = this.generatedDescription;

            // 1. Event dispatch (diger componentler dinlesin)
            document.dispatchEvent(
                new CustomEvent('ai-description-accepted', {
                    detail: { description }
                })
            );

            // 2. Textarea'ya yaz (uyumluluk — gecis donemi)
            const textarea = document.getElementById('aciklama');
            if (textarea) {
                textarea.value = description;
                textarea.dispatchEvent(new Event('input'));
            }

            if (window.toast) window.toast.success('Açıklama ilana uygulandı.');
            this.aiModalOpen = false;
        },

        closeModal() {
            if (!this.aiLoading) {
                this.aiModalOpen = false;
            }
        },

        setAiResult(payload) {
            // SSOT: Use the helper to ensure consistent defaults
            this.aiResult = { ...emptyAiResult(), ...(payload || {}) };
        },
    };
}

// Make it globally available if not using a bundler that handles this
window.aiDescriptionGenerator = aiDescriptionGenerator;
