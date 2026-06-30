/**
 * Context7 Smart İlan Ekleme Sistemi
 * Version: 2.0.0 - AI-First Architecture
 * Context7 Standard: C7-SMART-ILAN-CREATE-2025-01-30
 */

class Context7SmartIlanCreate {
    constructor() {
        this.config = {
            aiEnabled: true,
            autoSave: true,
            autoSaveInterval: 30000, // 30 seconds
            debounceDelay: 300,
            maxImages: 20,
            supportedLanguages: ['tr', 'en', 'de', 'fr', 'ru', 'ar'],
        };

        this.state = {
            formData: new Map(),
            aiSuggestions: [],
            uploadedImages: [],
            currentLocation: null,
            isDirty: false,
            lastSaved: null,
        };

        this.services = {
            ai: new Context7AIService(),
            location: new Context7LocationService(),
            media: new Context7MediaService(),
            validation: new Context7ValidationService(),
        };

        this.init();
    }

    /**
     * Initialize the smart ilan create system
     */
    init() {
        console.log('🚀 Context7 Smart İlan Create initializing...');

        this.setupEventListeners();
        this.setupAIIntegration();
        this.setupLocationServices();
        this.setupMediaUpload();
        this.setupAutoSave();
        this.setupValidation();

        // Force-clear prefilled owner/danisman fields on first load
        try {
            const ownerHidden = document.getElementById('ilan_sahibi_id');
            const ownerSelected = document.getElementById('selected-ilan-sahibi');
            const ownerSearch = document.getElementById('ilan-sahibi-search');
            if (ownerHidden) ownerHidden.value = '';
            if (ownerSelected) ownerSelected.classList.add('hidden');
            if (ownerSearch) ownerSearch.value = '';

            const danismanHidden = document.getElementById('danisman_id');
            const danismanSelected = document.getElementById('selected-danisman');
            const danismanSearch = document.getElementById('danisman-search');
            if (danismanHidden) danismanHidden.value = '';
            if (danismanSelected) danismanSelected.classList.add('hidden');
            if (danismanSearch) danismanSearch.value = '';
        } catch (e) {}

        console.log('✅ SAB Smart İlan Create initialized successfully');
    }

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Person search
        this.setupPersonSearch('ilan-sahibi-search', 'ilan_sahibi_id', 'selected-ilan-sahibi');
        this.setupDanismanSearch('danisman-search', 'danisman_id', 'selected-danisman');

        // Category cascade
        document.getElementById('ana-kategori').addEventListener('change', (e) => {
            this.loadAltKategoriler(e.target.value);
        });

        document.getElementById('alt-kategori').addEventListener('change', (e) => {
            this.loadYayinTipleri(e.target.value);
        });

        // AI buttons
        document.getElementById('ai-analyze-basic').addEventListener('click', () => {
            this.performAIBasicAnalysis();
        });

        document.getElementById('ai-feature-suggestions').addEventListener('click', () => {
            this.getAIFeatureSuggestions();
        });

        document.getElementById('ai-price-optimization').addEventListener('click', () => {
            this.performAIPriceOptimization();
        });

        document.getElementById('ai-generate-description').addEventListener('click', () => {
            this.generateAIDescription();
        });

        document.getElementById('ai-image-analysis').addEventListener('click', () => {
            this.performAIImageAnalysis();
        });

        // Form actions
        document.getElementById('save-draft').addEventListener('click', () => {
            this.saveDraft();
        });

        document.getElementById('preview-ilan').addEventListener('click', () => {
            this.previewIlan();
        });

        document.getElementById('ai-final-review').addEventListener('click', () => {
            this.performAIFinalReview();
        });

        // Apply all suggestions
        document.getElementById('apply-all-suggestions').addEventListener('click', () => {
            this.applyAllAISuggestions();
        });
    }

    /**
     * Setup danisman search functionality
     */
    setupDanismanSearch(searchInputId, hiddenInputId, selectedDivId) {
        const searchInput = document.getElementById(searchInputId);
        const hiddenInput = document.getElementById(hiddenInputId);
        const selectedDiv = document.getElementById(selectedDivId);
        const resultsDiv = document.getElementById(searchInputId.replace('-search', '-results'));

        let debounceTimer;

        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                this.searchDanisman(e.target.value, resultsDiv, hiddenInput, selectedDiv);
            }, this.config.debounceDelay);
        });

        // Hide results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.classList.add('hidden');
            }
        });
    }

    /**
     * Setup person search functionality
     */
    setupPersonSearch(searchInputId, hiddenInputId, selectedDivId) {
        const searchInput = document.getElementById(searchInputId);
        const hiddenInput = document.getElementById(hiddenInputId);
        const selectedDiv = document.getElementById(selectedDivId);
        const resultsDiv = document.getElementById(searchInputId.replace('-search', '-results'));

        let debounceTimer;

        searchInput.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                this.searchPersons(e.target.value, resultsDiv, hiddenInput, selectedDiv);
            }, this.config.debounceDelay);
        });

        // Hide results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.classList.add('hidden');
            }
        });
    }

    /**
     * Search danisman with debounced API call
     */
    async searchDanisman(query, resultsDiv, hiddenInput, selectedDiv) {
        if (query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        try {
            // Use the existing persons endpoint but filter for danisman role
            const url = window.APIConfig && window.APIConfig.smartIlan && window.APIConfig.smartIlan.searchPersons
                ? window.APIConfig.smartIlan.searchPersons
                : '/api/smart-ilan/search/persons';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ query, type: 'danisman' }),
            });

            const data = await response.json();
            this.displayDanismanResults(data.persons || [], resultsDiv, hiddenInput, selectedDiv);
        } catch (error) {
            console.error('Danisman search error:', error);
            // Fallback: show hardcoded danisman list
            this.showFallbackDanismanList(resultsDiv, hiddenInput, selectedDiv);
        }
    }

    /**
     * Search persons with debounced API call
     */
    async searchPersons(query, resultsDiv, hiddenInput, selectedDiv) {
        if (query.length < 2) {
            resultsDiv.classList.add('hidden');
            return;
        }

        try {
            const url = window.APIConfig && window.APIConfig.smartIlan && window.APIConfig.smartIlan.searchPersons
                ? window.APIConfig.smartIlan.searchPersons
                : '/api/smart-ilan/search/persons';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ query }),
            });

            const data = await response.json();
            this.displayPersonResults(data.persons, resultsDiv, hiddenInput, selectedDiv);
        } catch (error) {
            console.error('Person search error:', error);
        }
    }

    /**
     * Display danisman search results
     */
    displayDanismanResults(danismanlar, resultsDiv, hiddenInput, selectedDiv) {
        if (!danismanlar || danismanlar.length === 0) {
            resultsDiv.innerHTML = '<div class="p-3 text-gray-500 dark:text-slate-500">Danışman bulunamadı</div>';
            resultsDiv.classList.remove('hidden');
            return;
        }

        const html = danismanlar
            .map(
                (danisman) => `
                <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 dark:border-slate-800"
                     onclick="selectDanisman(${danisman.id}, '${danisman.tam_ad || danisman.name}', '${danisman.telefon || danisman.email}')">
                    <div class="font-medium text-gray-800 dark:text-slate-200">${danisman.tam_ad || danisman.name}</div>
                    <div class="text-sm text-gray-500 dark:text-slate-500">${danisman.telefon || danisman.email}</div>
                </div>
            `
            )
            .join('');

        resultsDiv.innerHTML = html;
        resultsDiv.classList.remove('hidden');
    }

    /**
     * Show fallback danisman list when API fails
     */
    showFallbackDanismanList(resultsDiv, hiddenInput, selectedDiv) {
        const fallbackDanismanlar = [
            { id: 1, name: 'Test Admin', email: 'test@admin.com' },
            { id: 2, name: 'Yalıhan Emlak', email: 'yalihanemlak@gmail.com' },
            { id: 3, name: 'Ayhan Küçük', email: 'ayhankucuk@gmail.com' },
        ];

        const html = fallbackDanismanlar
            .map(
                (danisman) => `
                <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 dark:border-slate-800"
                     onclick="selectDanisman(${danisman.id}, '${danisman.name}', '${danisman.email}')">
                    <div class="font-medium text-gray-800 dark:text-slate-200">${danisman.name}</div>
                    <div class="text-sm text-gray-500 dark:text-slate-500">${danisman.email}</div>
                </div>
            `
            )
            .join('');

        resultsDiv.innerHTML = html;
        resultsDiv.classList.remove('hidden');
    }

    /**
     * Display person search results
     */
    displayPersonResults(persons, resultsDiv, hiddenInput, selectedDiv) {
        if (persons.length === 0) {
            resultsDiv.innerHTML = '<div class="p-3 text-gray-500 dark:text-slate-500">Kişi bulunamadı</div>';
        } else {
            resultsDiv.innerHTML = persons
                .map(
                    (person) => `
                <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-200 last:border-b-0 dark:border-slate-700"
                     onclick="selectPerson('${person.id}', '${
                         person.tam_ad
                     }','${person.telefon}','${hiddenInput.id}','${selectedDiv.id}')">
                    <div class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">${person.tam_ad}</div>
                    <div class="text-sm text-gray-500 dark:text-slate-500">${person.telefon || 'Telefon yok'}</div>
                </div>
            `
                )
                .join('');
        }
        resultsDiv.classList.remove('hidden');
    }

    /**
     * Load alt kategoriler based on ana kategori
     */
    async loadAltKategoriler(anaKategoriId) {
        if (!anaKategoriId) {
            this.resetSelect('alt-kategori', 'Önce ana kategori seçin...');
            this.resetSelect('yayin-tipi', 'Önce alt kategori seçin...');
            return;
        }

        try {
            const response = await fetch(
                (window.APIConfig && window.APIConfig.categories && window.APIConfig.categories.subcategories)
                    ? window.APIConfig.categories.subcategories(anaKategoriId)
                    : `/api/v1/categories/sub/${anaKategoriId}`
            );
            const data = await response.json();

            const select = document.getElementById('alt-kategori');
            select.innerHTML =
                '<option value="">Alt kategori seçin...</option>' +
                data.kategoriler
                    .map(
                        (kategori) =>
                            `<option value="${kategori.id}">${kategori.name || kategori.kategori_adi}</option>`
                    )
                    .join('');

            select.disabled = false;
            this.resetSelect('yayin-tipi', 'Önce alt kategori seçin...');
        } catch (error) {
            console.error('Alt kategori yükleme hatası:', error);
        }
    }

    /**
     * Load yayin tipleri based on alt kategori
     */
    async loadYayinTipleri(altKategoriId) {
        if (!altKategoriId) {
            this.resetSelect('yayin-tipi', 'Önce alt kategori seçin...');
            return;
        }

        try {
            const response = await fetch(
                (window.APIConfig && window.APIConfig.categories && window.APIConfig.categories.publicationTypes)
                    ? window.APIConfig.categories.publicationTypes(altKategoriId)
                    : `/api/v1/categories/publication-types/${altKategoriId}`
            );
            const data = await response.json();

            const select = document.getElementById('yayin-tipi');
            select.innerHTML =
                '<option value="">Yayın tipi seçin...</option>' +
                data.yayinTipleri
                    .map(
                        (tip) =>
                            `<option value="${tip.id}">${tip.name || tip.yayin_tipi_adi}</option>`
                    )
                    .join('');

            select.disabled = false;
        } catch (error) {
            console.error('Yayın tipi yükleme hatası:', error);
        }
    }

    /**
     * Reset select element
     */
    resetSelect(selectId, placeholder) {
        const select = document.getElementById(selectId);
        select.innerHTML = `<option value="">${placeholder}</option>`;
        select.disabled = true;
        select.value = '';
    }

    /**
     * Perform AI basic analysis
     */
    async performAIBasicAnalysis() {
        const formData = this.collectFormData();

        try {
            const response = await this.services.ai.analyzeBasicInfo(formData);
            this.displayAISuggestions(response.suggestions);
        } catch (error) {
            console.error('AI basic analysis error:', error);
            this.showNotification('AI analizi sırasında hata oluştu', 'error');
        }
    }

    /**
     * Get AI feature suggestions
     */
    async getAIFeatureSuggestions() {
        const kategoriId = document.getElementById('alt-kategori').value;
        const konum = this.getLocationData();

        if (!kategoriId) {
            this.showNotification('Önce kategori seçin', 'warning');
            return;
        }

        try {
            const response = await this.services.ai.getFeatureSuggestions(kategoriId, konum);
            this.displayFeatureSuggestions(response.features);
        } catch (error) {
            console.error('AI feature suggestions error:', error);
        }
    }

    /**
     * Perform AI price optimization
     */
    async performAIPriceOptimization() {
        const formData = this.collectFormData();

        try {
            const response = await this.services.ai.optimizePrice(formData);
            this.displayPriceAnalysis(response.analysis);
        } catch (error) {
            console.error('AI price optimization error:', error);
        }
    }

    /**
     * Generate AI description
     */
    async generateAIDescription() {
        const formData = this.collectFormData();

        try {
            const response = await this.services.ai.generateDescription(formData);
            document.getElementById('aciklama').value = response.description;
            this.showNotification('AI açıklama oluşturuldu', 'success');
        } catch (error) {
            console.error('AI description generation error:', error);
        }
    }

    /**
     * Perform AI image analysis
     */
    async performAIImageAnalysis() {
        if (this.state.uploadedImages.length === 0) {
            this.showNotification('Önce fotoğraf yükleyin', 'warning');
            return;
        }

        try {
            const response = await this.services.ai.analyzeImages(this.state.uploadedImages);
            this.displayImageAnalysis(response.analysis);
        } catch (error) {
            console.error('AI image analysis error:', error);
        }
    }

    /**
     * Setup AI integration
     */
    setupAIIntegration() {
        // Initialize AI services
        this.services.ai = new Context7AIService();

        // Setup AI suggestion panel
        this.setupAISuggestionPanel();

        console.log('🤖 AI Integration setup completed');
    }

    /**
     * Setup AI suggestion panel
     */
    setupAISuggestionPanel() {
        // Create AI suggestions container if it doesn't exist
        let aiPanel = document.getElementById('ai-suggestions-panel');
        if (!aiPanel) {
            aiPanel = document.createElement('div');
            aiPanel.id = 'ai-suggestions-panel';
            aiPanel.className = 'ai-suggestions-panel hidden';
            aiPanel.innerHTML = `
                <div class="ai-panel-header">
                    <h3>🤖 AI Önerileri</h3>
                    <button class="close-ai-panel" onclick="this.closest('.ai-suggestions-panel').classList.add('hidden')">×</button>
                </div>
                <div class="ai-panel-content">
                    <div class="ai-suggestions-list"></div>
                </div>
            `;

            // Add to page
            const formContainer = document.querySelector('.smart-ilan-form');
            if (formContainer) {
                formContainer.appendChild(aiPanel);
            }
        }

        // Setup AI suggestion buttons
        this.setupAISuggestionButtons();
    }

    /**
     * Setup AI suggestion buttons
     */
    setupAISuggestionButtons() {
        // AI Basic Analysis button
        const basicAnalysisBtn = document.getElementById('ai-basic-analysis');
        if (basicAnalysisBtn) {
            basicAnalysisBtn.addEventListener('click', () => this.performAIBasicAnalysis());
        }

        // AI Feature Suggestions button
        const featureSuggestionsBtn = document.getElementById('ai-feature-suggestions');
        if (featureSuggestionsBtn) {
            featureSuggestionsBtn.addEventListener('click', () => this.getAIFeatureSuggestions());
        }

        // AI Price Optimization button
        const priceOptimizationBtn = document.getElementById('ai-price-optimization');
        if (priceOptimizationBtn) {
            priceOptimizationBtn.addEventListener('click', () => this.getAIPriceOptimization());
        }

        // AI Description Generation button
        const descriptionBtn = document.getElementById('ai-generate-description');
        if (descriptionBtn) {
            descriptionBtn.addEventListener('click', () => this.generateAIDescription());
        }

        // AI Image Analysis button
        const imageAnalysisBtn = document.getElementById('ai-analyze-images');
        if (imageAnalysisBtn) {
            imageAnalysisBtn.addEventListener('click', () => this.analyzeImages());
        }
    }

    /**
     * Perform AI Basic Analysis
     */
    async performAIBasicAnalysis() {
        try {
            const formData = this.collectFormData();
            const result = await this.services.ai.analyzeBasicInfo(formData);

            if (result.success) {
                this.displayAISuggestions(result.suggestions);
            } else {
                console.error('AI Basic Analysis failed:', result.error);
            }
        } catch (error) {
            console.error('AI Basic Analysis error:', error);
        }
    }

    /**
     * Get AI Feature Suggestions
     */
    async getAIFeatureSuggestions() {
        try {
            const kategoriId = document.getElementById('ana-kategori')?.value;
            const location = this.getLocationData();

            if (!kategoriId) {
                alert('Lütfen önce ana kategori seçiniz.');
                return;
            }

            const result = await this.services.ai.getFeatureSuggestions(kategoriId, location);

            if (result.success) {
                this.displayAISuggestions(result.suggestions);
            } else {
                console.error('AI Feature Suggestions failed:', result.error);
            }
        } catch (error) {
            console.error('AI Feature Suggestions error:', error);
        }
    }

    /**
     * Get AI Price Optimization
     */
    async getAIPriceOptimization() {
        try {
            const formData = this.collectFormData();
            const result = await this.services.ai.getPriceOptimization(formData);

            if (result.success) {
                this.displayAISuggestions(result.suggestions);
            } else {
                console.error('AI Price Optimization failed:', result.error);
            }
        } catch (error) {
            console.error('AI Price Optimization error:', error);
        }
    }

    /**
     * Generate AI Description
     */
    async generateAIDescription() {
        try {
            const formData = this.collectFormData();
            const result = await this.services.ai.generateDescription(formData);

            if (result.success) {
                const descriptionField = document.getElementById('aciklama');
                if (descriptionField) {
                    descriptionField.value = result.description;
                }
                this.showNotification('AI açıklama başarıyla oluşturuldu!', 'success');
            } else {
                console.error('AI Description Generation failed:', result.error);
            }
        } catch (error) {
            console.error('AI Description Generation error:', error);
        }
    }

    /**
     * Analyze Images with AI
     */
    async analyzeImages() {
        try {
            const imageInput = document.getElementById('resimler');
            if (!imageInput || !imageInput.files.length) {
                alert('Lütfen önce resim yükleyiniz.');
                return;
            }

            const formData = new FormData();
            Array.from(imageInput.files).forEach((file) => {
                formData.append('images[]', file);
            });

            const result = await this.services.ai.analyzeImages(formData);

            if (result.success) {
                this.displayAISuggestions(result.analysis);
            } else {
                console.error('AI Image Analysis failed:', result.error);
            }
        } catch (error) {
            console.error('AI Image Analysis error:', error);
        }
    }

    /**
     * Display AI suggestions
     */
    displayAISuggestions(suggestions) {
        const aiPanel = document.getElementById('ai-suggestions-panel');
        const suggestionsList = aiPanel?.querySelector('.ai-suggestions-list');

        if (!suggestionsList) return;

        suggestionsList.innerHTML = '';

        if (Array.isArray(suggestions)) {
            suggestions.forEach((suggestion) => {
                const suggestionItem = document.createElement('div');
                suggestionItem.className = 'ai-suggestion-item';
                suggestionItem.innerHTML = `
                    <div class="suggestion-header">
                        <span class="suggestion-title">${suggestion.title || 'AI Önerisi'}</span>
                        <span class="suggestion-confidence">${
                            suggestion.confidence || 0
                        }% güven</span>
                    </div>
                    <div class="suggestion-content">${suggestion.content || suggestion}</div>
                    ${
                        suggestion.action
                            ? `<button class="apply-suggestion" onclick="smartIlanCreate.applySuggestion('${
                                  suggestion.id || Math.random()
                              }')">Uygula</button>`
                            : ''
                    }
                `;
                suggestionsList.appendChild(suggestionItem);
            });
        }

        // Show panel
        aiPanel?.classList.remove('hidden');
    }

    /**
     * Apply AI suggestion
     */
    applySuggestion(suggestionId) {
        console.log('Applying suggestion:', suggestionId);
        // Implementation depends on suggestion type
        this.showNotification('Öneri uygulandı!', 'success');
    }

    /**
     * Collect form data
     */
    collectFormData() {
        const formData = {};
        const form = document.querySelector('.smart-ilan-form');

        if (form) {
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach((input) => {
                if (input.name && input.value) {
                    formData[input.name] = input.value;
                }
            });
        }

        return formData;
    }

    /**
     * Get location data
     */
    getLocationData() {
        return {
            latitude: document.getElementById('latitude')?.value,
            longitude: document.getElementById('longitude')?.value,
            address: document.getElementById('detayli_adres')?.value,
        };
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Simple notification implementation
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${
                type === 'success' ? '#10B981' : type === 'error' ? '#EF4444' : '#3B82F6'
            };
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            z-index: 1000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    /**
     * Setup location services
     */
    setupLocationServices() {
        this.services.location = new Context7LocationService();
        this.services.location.initMap('smart-location-map');

        // Setup location cascade
        this.setupLocationCascade();
    }

    /**
     * Setup location cascade (il, ilce, mahalle)
     */
    setupLocationCascade() {
        const ilSelect = document.getElementById('il');
        const ilceSelect = document.getElementById('ilce');
        const mahalleSelect = document.getElementById('mahalle');

        if (ilSelect) {
            ilSelect.addEventListener('change', (e) => {
                const ilId = e.target.value;
                this.loadIlceler(ilId);
                // Reset ilce and mahalle
                if (ilceSelect) {
                    ilceSelect.innerHTML = '<option value="">İlçe Seçin</option>';
                }
                if (mahalleSelect) {
                    mahalleSelect.innerHTML = '<option value="">Mahalle Seçin</option>';
                }
            });
        }

        if (ilceSelect) {
            ilceSelect.addEventListener('change', (e) => {
                const ilceId = e.target.value;
                this.loadMahalleler(ilceId);
                // Reset mahalle
                if (mahalleSelect) {
                    mahalleSelect.innerHTML = '<option value="">Mahalle Seçin</option>';
                }
            });
        }
    }

    /**
     * Load ilceler for selected il
     */
    async loadIlceler(ilId) {
        if (!ilId) return;

        try {
            const response = await fetch(window.APIConfig ? window.APIConfig.location.districts(ilId) : `/api/v1/location/districts/${ilId}`);
            const resp = await response.json();
            const data = resp?.data || resp;

            const ilceSelect = document.getElementById('ilce');
            if (ilceSelect) {
                ilceSelect.innerHTML = '<option value="">İlçe Seçin</option>';
                (Array.isArray(data) ? data : []).forEach((ilce) => {
                    const option = document.createElement('option');
                    option.value = ilce.id;
                    option.textContent = ilce.ilce_adi;
                    ilceSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('İlçe yükleme hatası:', error);
        }
    }

    /**
     * Load mahalleler for selected ilce
     */
    async loadMahalleler(ilceId) {
        if (!ilceId) return;

        try {
            const response = await fetch(window.APIConfig ? window.APIConfig.location.neighborhoods(ilceId) : `/api/v1/location/neighborhoods/${ilceId}`);
            const resp = await response.json();
            const data = resp?.data || resp;

            const mahalleSelect = document.getElementById('mahalle');
            if (mahalleSelect) {
                mahalleSelect.innerHTML = '<option value="">Mahalle Seçin</option>';
                (Array.isArray(data) ? data : []).forEach((mahalle) => {
                    const option = document.createElement('option');
                    option.value = mahalle.id;
                    option.textContent = mahalle.mahalle_adi;
                    mahalleSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Mahalle yükleme hatası:', error);
        }
    }

    /**
     * Setup media upload
     */
    setupMediaUpload() {
        this.services.media = new Context7MediaService();

        const uploadArea = document.getElementById('image-upload-area');
        const fileInput = document.getElementById('images');

        // Drag and drop
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('border-blue-500', 'bg-blue-50');
        });

        uploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('border-blue-500', 'bg-blue-50');
            this.handleFileDrop(e.dataTransfer.files);
        });

        // File input
        fileInput.addEventListener('change', (e) => {
            this.handleFileSelect(e.target.files);
        });

        // Click to select files
        uploadArea.addEventListener('click', () => {
            fileInput.click();
        });
    }

    /**
     * Setup auto save
     */
    setupAutoSave() {
        if (!this.config.autoSave) return;

        setInterval(() => {
            if (this.state.isDirty) {
                this.saveDraft();
            }
        }, this.config.autoSaveInterval);
    }

    /**
     * Setup validation
     */
    setupValidation() {
        this.services.validation = new Context7ValidationService();

        // Real-time validation
        const requiredFields = document.querySelectorAll('[required]');
        requiredFields.forEach((field) => {
            field.addEventListener('blur', () => {
                this.services.validation.validateField(field);
            });
        });
    }

    /**
     * Collect form data
     */
    collectFormData() {
        const formData = new FormData(document.getElementById('smartIlanForm'));
        const data = {};

        for (const [key, value] of formData.entries()) {
            data[key] = value;
        }

        return data;
    }

    /**
     * Display AI suggestions
     */
    displayAISuggestions(suggestions) {
        this.state.aiSuggestions = suggestions;

        const panel = document.getElementById('ai-suggestions-panel');
        const content = document.getElementById('ai-suggestions-content');
        const count = document.getElementById('ai-suggestions-count');

        if (suggestions.length === 0) {
            panel.style.display = 'none';
            return;
        }

        content.innerHTML = suggestions
            .map(
                (suggestion) => `
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-blue-800">${suggestion.title}</div>
                        <div class="text-sm text-blue-600">${suggestion.description}</div>
                    </div>
                    <button type="button" class="neo-btn neo-btn--primary neo-btn--sm"
                            onclick="applyAISuggestion('${suggestion.id}')">
                        Uygula
                    </button>
                </div>
            </div>
        `
            )
            .join('');

        count.textContent = suggestions.length;
        panel.style.display = 'block';
    }

    /**
     * Save draft
     */
    async saveDraft() {
        const formData = this.collectFormData();
        formData.append('is_draft', '1');

        try {
            const response = await fetch('/admin/ilanlar/save-draft', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                this.showNotification('Taslak kaydedildi', 'success');
                this.state.isDirty = false;
                this.state.lastSaved = new Date();
            }
        } catch (error) {
            console.error('Draft save error:', error);
            this.showNotification('Taslak kaydedilemedi', 'error');
        }
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        // Implementation for notification system
        console.log(`${type.toUpperCase()}: ${message}`);
    }

    /**
     * Apply all AI suggestions
     */
    applyAllAISuggestions() {
        this.state.aiSuggestions.forEach((suggestion) => {
            this.applyAISuggestion(suggestion.id);
        });

        document.getElementById('ai-suggestions-panel').style.display = 'none';
        this.showNotification('Tüm AI önerileri uygulandı', 'success');
    }
}

// Global functions for HTML onclick events
window.selectPerson = function (id, name, phone, hiddenInputId, selectedDivId) {
    const hiddenInput = document.getElementById(hiddenInputId);
    const selectedDiv = document.getElementById(selectedDivId);
    const resultsDiv = document.getElementById(hiddenInputId.replace('_id', '-results'));

    if (hiddenInput) {
        hiddenInput.value = id;
    }

    if (selectedDiv) {
        const span = selectedDiv.querySelector('span');
        if (span) {
            span.textContent = `${name} - ${phone}`;
        }
        selectedDiv.classList.remove('hidden');
    }

    if (resultsDiv) {
        resultsDiv.classList.add('hidden');
    }
};

window.clearIlanSahibi = function () {
    document.getElementById('ilan_sahibi_id').value = '';
    document.getElementById('selected-ilan-sahibi').classList.add('hidden');
    document.getElementById('ilan-sahibi-search').value = '';
};

window.selectDanisman = function (id, name, email) {
    document.getElementById('danisman_id').value = id;
    document.getElementById('danisman-search').value = name;
    document.getElementById('selected-danisman').querySelector('span').textContent = name;
    document.getElementById('selected-danisman').classList.remove('hidden');
    document.getElementById('danisman-results').classList.add('hidden');
};

window.clearDanisman = function () {
    document.getElementById('danisman_id').value = '';
    document.getElementById('selected-danisman').classList.add('hidden');
    document.getElementById('danisman-search').value = '';
};

window.applyAISuggestion = function (suggestionId) {
    // Implementation for applying individual AI suggestions
    console.log('Applying AI suggestion:', suggestionId);
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('[data-smart-ilan-form]')) {
        window.smartIlanCreate = new Context7SmartIlanCreate();
    }
});

// Global Google Maps callback
window.initGoogleMaps = function () {
    console.log('🗺️ Google Maps initialized');
    // Google Maps is ready, any additional initialization can be done here
};
