// Yalıhan Bekçi - AI Enhanced State Management for Stable Create
// Advanced Alpine.js State Management with AI Integration

document.addEventListener('alpine:init', () => {
    // Ana İlan Form State Management
    Alpine.store('ilanForm', {
        // Form State
        currentStep: 1,
        totalSteps: 11,
        completedSteps: [],
        isValid: false,
        autoSave: true,
        lastSaved: null,
        isDirty: false,

        // AI Integration State
        aiEnabled: true,
        aiSuggestions: {},
        aiAnalyzing: false,
        aiResults: {},

        // Form Data
        formData: {
            // Step 1: Temel Bilgiler
            baslik: '',
            aciklama: '',
            kategori_id: null,
            emlak_tipi: null,

            // Step 2: Lokasyon
            il_id: null,
            ilce_id: null,
            mahalle_id: null,
            adres: '',
            koordinatlar: null,

            // Step 3: Fiyat Bilgileri
            fiyat: null,
            para_birimi: 'TRY',
            kira_satilik: 'satilik',
            kdv_dahil: false,

            // Step 4: Özellikler
            oda_sayisi: null,
            banyo_sayisi: null,
            balkon_sayisi: null,
            metrekare: null,
            yas: null,

            // Step 5: Ek Özellikler
            features: [],
            additional_features: [],

            // Step 6: Görseller
            images: [],
            virtual_tour: null,

            // Step 7: İletişim
            iletisim_tipi: 'telefon',
            telefon: '',
            email: '',
            whatsapp: false,

            // Step 8: SEO
            meta_title: '',
            meta_description: '',
            slug: '',

            // Step 9: Yayın Ayarları
            yayin_durumu: 'taslak',
            yayin_tarihi: null,
            bitis_tarihi: null,

            // Step 10: Pazarlama
            premium_ilan: false,
            featured_ilan: false,
            spotlight_ilan: false,

            // Step 11: Onay
            terms_accepted: false,
            privacy_accepted: false,
        },

        // Methods
        init() {
            this.loadDraft();
            this.setupAutoSave();
            this.setupValidation();
            this.setupAI();
        },

        // Step Management
        nextStep() {
            if (this.validateCurrentStep()) {
                this.completedSteps.push(this.currentStep);
                this.currentStep = Math.min(this.currentStep + 1, this.totalSteps);
                this.isDirty = true;
                this.autoSave();
            }
        },

        prevStep() {
            this.currentStep = Math.max(this.currentStep - 1, 1);
        },

        goToStep(step) {
            if (step <= this.currentStep || this.completedSteps.includes(step - 1)) {
                this.currentStep = step;
            }
        },

        // Validation
        validateCurrentStep() {
            const validators = {
                1: () => this.validateBasicInfo(),
                2: () => this.validateLocation(),
                3: () => this.validatePrice(),
                4: () => this.validateFeatures(),
                5: () => this.validateAdditionalFeatures(),
                6: () => this.validateImages(),
                7: () => this.validateContact(),
                8: () => this.validateSEO(),
                9: () => this.validatePublishing(),
                10: () => this.validateMarketing(),
                11: () => this.validateTerms(),
            };

            return validators[this.currentStep] ? validators[this.currentStep]() : true;
        },

        validateBasicInfo() {
            return !!(
                this.formData.baslik &&
                this.formData.kategori_id &&
                this.formData.emlak_tipi
            );
        },

        validateLocation() {
            return !!(this.formData.il_id && this.formData.ilce_id);
        },

        validatePrice() {
            return !!(this.formData.fiyat && this.formData.fiyat > 0);
        },

        validateFeatures() {
            return !!(this.formData.oda_sayisi && this.formData.metrekare);
        },

        validateAdditionalFeatures() {
            return true; // Optional step
        },

        validateImages() {
            return this.formData.images.length > 0;
        },

        validateContact() {
            return !!(this.formData.telefon || this.formData.email);
        },

        validateSEO() {
            return true; // Auto-generated if empty
        },

        validatePublishing() {
            return true; // Default values are valid
        },

        validateMarketing() {
            return true; // Optional features
        },

        validateTerms() {
            return this.formData.terms_accepted && this.formData.privacy_accepted;
        },

        // Auto-save System
        setupAutoSave() {
            if (this.autoSave) {
                setInterval(() => {
                    if (this.isDirty) {
                        this.saveDraft();
                    }
                }, 30000); // 30 seconds
            }
        },

        saveDraft() {
            const draftData = {
                formData: this.formData,
                currentStep: this.currentStep,
                completedSteps: this.completedSteps,
                timestamp: new Date().toISOString(),
            };

            // Local Storage
            localStorage.setItem('ilan_draft', JSON.stringify(draftData));

            // Server Save (AJAX)
            this.saveToServer(draftData);

            this.lastSaved = new Date();
            this.isDirty = false;
        },

        async saveToServer(draftData) {
            try {
                // ✅ API Helper kullan (merkezi yönetim)
                const result = await window.APIHelper?.request('admin.ilanlar.draft.create', {
                    method: 'POST',
                    body: JSON.stringify(draftData),
                });

                if (result.success) {
                    console.log('Draft saved to server');
                }
            } catch (error) {
                console.error('Failed to save draft to server:', error);
            }
        },

        loadDraft() {
            // Load from Local Storage
            const localDraft = localStorage.getItem('ilan_draft');
            if (localDraft) {
                const draft = JSON.parse(localDraft);
                this.formData = { ...this.formData, ...draft.formData };
                this.currentStep = draft.currentStep || 1;
                this.completedSteps = draft.completedSteps || [];
                this.lastSaved = draft.timestamp;
            }

            // Load from Server
            this.loadFromServer();
        },

        async loadFromServer() {
            try {
                // ✅ API Helper kullan (merkezi yönetim)
                const result = await window.APIHelper?.request('admin.ilanlar.draft.get');

                if (result.success && result.data) {
                    const serverDraft = result.data;
                    if (serverDraft.timestamp > this.lastSaved) {
                        this.formData = { ...this.formData, ...serverDraft.formData };
                        this.currentStep = serverDraft.currentStep || 1;
                        this.completedSteps = serverDraft.completedSteps || [];
                        this.lastSaved = serverDraft.timestamp;
                    }
                }
            } catch (error) {
                console.error('Failed to load draft from server:', error);
            }
        },

        // AI Integration
        setupAI() {
            if (this.aiEnabled) {
                this.autoGenerateSEO();
                this.analyzeContent();
            }
        },

        async autoGenerateSEO() {
            if (!this.formData.baslik || !this.formData.aciklama) return;

            try {
                // ✅ API Helper kullan (merkezi yönetim)
                const result = await window.APIHelper?.request('admin.aiAssist.seoOptimize', {
                    method: 'POST',
                    body: JSON.stringify({
                        title: this.formData.baslik,
                        description: this.formData.aciklama,
                        category_id: this.formData.kategori_id,
                    }),
                });

                if (result.success && result.data) {
                    this.formData.meta_title = result.data.meta_title;
                    this.formData.meta_description = result.data.meta_description;
                    this.formData.slug = result.data.slug;
                    this.isDirty = true;
                }
            } catch (error) {
                console.error('AI SEO generation failed:', error);
                if (window.NotificationHelper) {
                    window.NotificationHelper.error('SEO optimizasyonu yapılamadı');
                }
            }
        },

        async analyzeContent() {
            if (!this.formData.baslik || !this.formData.aciklama) return;

            this.aiAnalyzing = true;

            try {
                // ✅ API Helper kullan (merkezi yönetim)
                const result = await window.APIHelper?.request('admin.aiAssist.autoCategorize', {
                    method: 'POST',
                    body: JSON.stringify({
                        title: this.formData.baslik,
                        description: this.formData.aciklama,
                    }),
                });

                if (result.success && result.data) {
                    this.aiSuggestions.category = result.data.suggested_category;
                    this.aiSuggestions.confidence = result.data.confidence;
                }
            } catch (error) {
                console.error('AI content analysis failed:', error);
                if (window.NotificationHelper) {
                    window.NotificationHelper.error('Kategori analizi yapılamadı');
                }
            } finally {
                this.aiAnalyzing = false;
            }
        },

        async suggestPrice() {
            if (!this.formData.kategori_id || !this.formData.il_id) return;

            try {
                // ✅ API Helper kullan (merkezi yönetim)
                const result = await window.APIHelper?.request('admin.aiAssist.priceSuggest', {
                    method: 'POST',
                    body: JSON.stringify({
                        category_id: this.formData.kategori_id,
                        location_id: this.formData.il_id,
                        features: this.formData.features,
                        metrekare: this.formData.metrekare,
                    }),
                });

                if (result.success && result.data) {
                    this.aiSuggestions.price = result.data.suggested_price;
                    this.aiSuggestions.priceRange = result.data.price_range;
                }
            } catch (error) {
                console.error('AI price suggestion failed:', error);
                if (window.NotificationHelper) {
                    window.NotificationHelper.error('Fiyat önerisi alınamadı');
                }
            }
        },

        async generateDescription() {
            if (!this.formData.baslik || !this.formData.features) return;

            try {
                // ✅ API Helper kullan (merkezi yönetim)
                const result = await window.APIHelper?.request('admin.aiAssist.descriptionGenerate', {
                    method: 'POST',
                    body: JSON.stringify({
                        title: this.formData.baslik,
                        category_id: this.formData.kategori_id,
                        features: this.formData.features,
                        location: {
                            il: this.formData.il_id,
                            ilce: this.formData.ilce_id,
                        },
                    }),
                });

                if (result.success && result.data) {
                    this.formData.aciklama = result.data.description;
                    this.isDirty = true;
                }
            } catch (error) {
                console.error('AI description generation failed:', error);
                if (window.NotificationHelper) {
                    window.NotificationHelper.error('Açıklama oluşturulamadı');
                }
            }
        },

        // Utility Methods
        getStepProgress() {
            return Math.round((this.completedSteps.length / this.totalSteps) * 100);
        },

        getStepTitle(step) {
            const titles = {
                1: 'Temel Bilgiler',
                2: 'Lokasyon',
                3: 'Fiyat Bilgileri',
                4: 'Özellikler',
                5: 'Ek Özellikler',
                6: 'Görseller',
                7: 'İletişim',
                8: 'SEO',
                9: 'Yayın Ayarları',
                10: 'Pazarlama',
                11: 'Onay',
            };
            return titles[step] || `Adım ${step}`;
        },

        isStepCompleted(step) {
            return this.completedSteps.includes(step);
        },

        canGoToStep(step) {
            return step <= this.currentStep || this.completedSteps.includes(step - 1);
        },

        resetForm() {
            this.formData = {
                baslik: '',
                aciklama: '',
                kategori_id: null,
                emlak_tipi: null,
                // ... reset all fields
            };
            this.currentStep = 1;
            this.completedSteps = [];
            this.isDirty = false;
            this.lastSaved = null;
            localStorage.removeItem('ilan_draft');
        },

        // Form Submission
        async submitForm() {
            if (!this.validateCurrentStep()) {
                return false;
            }

            try {
                const urlCreate = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.ilanlar && window.APIConfig.admin.ilanlar.create
                    ? window.APIConfig.admin.ilanlar.create
                    : '/admin/ilanlar';
                const response = await fetch(urlCreate, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(this.formData),
                });

                if (response.ok) {
                    this.resetForm();
                    return true;
                }
            } catch (error) {
                console.error('Form submission failed:', error);
            }

            return false;
        },
    });

    // AI Analysis Store
    Alpine.store('aiAnalysis', {
        isAnalyzing: false,
        results: {},
        suggestions: {},

        async analyzeImages(images) {
            this.isAnalyzing = true;

            try {
                // ✅ API Helper kullan (merkezi yönetim)
                const result = await window.APIHelper?.request('admin.aiAssist.imageAnalyze', {
                    method: 'POST',
                    body: JSON.stringify({ images }),
                });

                if (result.success && result.data) {
                    this.results.imageAnalysis = result.data;
                }
            } catch (error) {
                console.error('Image analysis failed:', error);
                if (window.NotificationHelper) {
                    window.NotificationHelper.error('Görsel analizi yapılamadı');
                }
            } finally {
                this.isAnalyzing = false;
            }
        },
    });
});
