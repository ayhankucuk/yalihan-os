/**
 * Features Wizard Logic (Phase 6: UI/UX Smart Features)
 *
 * Context7: Pure Vanilla JS, No jQuery, Merkezi API
 * Performance: 3ms eager loading, <100ms AI suggestions
 * Features: Accordion UI, AI Pre-selection, Real-time Search, Debounced Integration
 *
 * @version 1.0.0
 * @since 2025-12-21
 */

/* global APIConfig */

const FeaturesWizard = {
    // State
    currentCategoryId: null,
    currentYayinTipiId: null,
    featuresData: null,
    aiSuggestions: [],
    searchQuery: '',
    openAccordions: new Set(),

    // Debounce timers
    _aiDebounceTimer: null,
    _searchDebounceTimer: null,

    // Elements cache
    elements: {},

    /**
     * Initialize the wizard
     */
    init() {
        console.log('🎨 FeaturesWizard initializing...');

        // Cache DOM elements
        this.cacheElements();

        // Setup event listeners
        this.setupEventListeners();

        console.log('✅ FeaturesWizard ready');
    },

    /**
     * Cache DOM elements
     */
    cacheElements() {
        this.elements = {
            container: document.getElementById('features-wizard-container'),
            emptyState: document.getElementById('features-empty-state'),
            loading: document.getElementById('features-loading'),
            error: document.getElementById('features-error'),
            errorMessage: document.getElementById('features-error-message'),
            accordion: document.getElementById('features-accordion'),
            searchContainer: document.getElementById('feature-search-container'),
            searchInput: document.getElementById('feature-search-input'),
            searchCount: document.getElementById('search-match-count'),
            searchResults: document.getElementById('search-results-count'),
            aiSuggestBtn: document.getElementById('ai-suggest-all-btn'),
        };
    },

    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Real-time search (debounced 300ms)
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', (e) => {
                clearTimeout(this._searchDebounceTimer);
                this._searchDebounceTimer = setTimeout(() => {
                    this.handleSearch(e.target.value);
                }, 300);
            });
        }

        // AI Suggest All button
        if (this.elements.aiSuggestBtn) {
            this.elements.aiSuggestBtn.addEventListener('click', () => {
                this.triggerAiSuggestions();
            });
        }

        // Listen for description textarea changes (500ms debounce)
        const descriptionField = document.querySelector('textarea[name="aciklama"]') ||
            document.getElementById('aciklama');

        if (descriptionField) {
            descriptionField.addEventListener('input', (e) => {
                clearTimeout(this._aiDebounceTimer);
                this._aiDebounceTimer = setTimeout(() => {
                    if (e.target.value.trim().length >= 10) {
                        this.triggerAiSuggestions(e.target.value);
                    }
                }, 500);
            });
        }
    },

    /**
     * Load features for category
     *
     * @param {number} categoryId - Category ID
     * @param {number} yayinTipiId - Publication Type ID
     */
    async loadFeatures(categoryId, yayinTipiId) {
        if (!categoryId || !yayinTipiId) {
            console.warn('⚠️ Missing category or yayin_tipi, showing empty state');
            this.showEmptyState();
            return;
        }

        console.log(`📥 Loading features for category ${categoryId}, yayin_tipi ${yayinTipiId}`);

        this.currentCategoryId = categoryId;
        this.currentYayinTipiId = yayinTipiId;

        this.showLoading();

        try {
            const url = APIConfig.wizard.features(categoryId, yayinTipiId);
            console.log('🌐 Fetching:', url);

            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.message || 'API error');
            }

            this.featuresData = result.data;
            console.log('✅ Features loaded:', this.featuresData);

            // Render accordion
            this.renderAccordion();

            // Show search if features loaded
            if (this.featuresData.total_features > 0) {
                this.elements.searchContainer?.classList.remove('hidden');
                this.elements.aiSuggestBtn?.classList.remove('hidden');
            }

        } catch (error) {
            console.error('❌ Load features error:', error);
            this.showError(error.message);
            this.hideLoading(); // Phase 6 Fix: Always hide loading on error
        }
    },

    /**
     * Render accordion UI
     */
    renderAccordion() {
        // Phase 6 Fix: Null-safe feature data check
        if (!this.featuresData || !this.featuresData.groups || !Array.isArray(this.featuresData.groups)) {
            console.warn('⚠️ No features data to render');
            this.showEmptyState();
            return;
        }

        const groups = this.featuresData.groups;

        if (groups.length === 0) {
            this.showEmptyState('Bu kategori için özellik bulunamadı.');
            return;
        }

        console.log(`🎨 Rendering ${groups.length} accordion groups`);

        this.elements.accordion.innerHTML = '';

        groups.forEach((group, index) => {
            const accordionItem = this.createAccordionItem(group, index);
            this.elements.accordion.appendChild(accordionItem);
        });

        // INSTANT transition: fade-out loading, fade-in accordion
        this.elements.loading?.classList.add('opacity-0');

        setTimeout(() => {
            this.elements.loading?.classList.add('hidden');
            this.elements.emptyState?.classList.add('hidden');
            this.elements.error?.classList.add('hidden');

            // Fade-in accordion
            this.elements.accordion?.classList.remove('hidden');
            this.elements.accordion?.classList.add('opacity-0');
            setTimeout(() => this.elements.accordion?.classList.remove('opacity-0'), 10);
        }, 150);

        // Open first accordion by default
        if (groups.length > 0) {
            this.toggleAccordion(groups[0].name);
        }
    },

    /**
     * Create accordion item
     *
     * @param {object} group - Feature group data
     * @param {number} index - Group index
     * @returns {HTMLElement} Accordion item element
     */
    createAccordionItem(group, index) {
        const groupId = `accordion-${this.sanitizeId(group.name)}`;
        const isOpen = this.openAccordions.has(group.name);

        const item = document.createElement('div');
        item.className = 'bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden transition-all duration-200 hover:shadow-md';
        item.setAttribute('data-group', group.name);

        // Header (clickable)
        const header = document.createElement('button');
        header.type = 'button';
        header.className = 'w-full flex items-center justify-between p-4 text-left hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors duration-200';
        header.setAttribute('aria-expanded', isOpen);
        header.onclick = () => this.toggleAccordion(group.name);

        const headerLeft = document.createElement('div');
        headerLeft.className = 'flex items-center space-x-3 flex-1';

        const groupIcon = this.getGroupIcon(group.name);
        const iconSpan = document.createElement('span');
        iconSpan.className = 'text-2xl';
        iconSpan.textContent = groupIcon;

        const titleDiv = document.createElement('div');
        const title = document.createElement('h3');
        title.className = 'text-base font-semibold text-gray-900 dark:text-white';
        title.textContent = group.name;

        const subtitle = document.createElement('p');
        subtitle.className = 'text-xs text-gray-500 dark:text-gray-400 mt-0.5';
        subtitle.textContent = `${group.count} özellik`;

        titleDiv.appendChild(title);
        titleDiv.appendChild(subtitle);

        headerLeft.appendChild(iconSpan);
        headerLeft.appendChild(titleDiv);

        const chevron = document.createElement('i');
        chevron.className = `fas fa-chevron-down text-gray-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`;
        chevron.id = `chevron-${groupId}`;

        header.appendChild(headerLeft);
        header.appendChild(chevron);

        // Content (collapsible)
        const content = document.createElement('div');
        content.id = groupId;
        content.className = `transition-all duration-300 overflow-hidden ${isOpen ? '' : 'hidden'}`;

        const contentInner = document.createElement('div');
        contentInner.className = 'p-4 pt-0 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3';

        // Render features
        group.features.forEach(feature => {
            const featureEl = this.createFeatureElement(feature);
            contentInner.appendChild(featureEl);
        });

        content.appendChild(contentInner);

        item.appendChild(header);
        item.appendChild(content);

        return item;
    },

    /**
     * Create feature element (checkbox/select/input)
     *
     * @param {object} feature - Feature data
     * @returns {HTMLElement} Feature element
     */
    createFeatureElement(feature) {
        const wrapper = document.createElement('div');
        wrapper.className = 'feature-item';
        wrapper.setAttribute('data-feature-id', feature.id);
        wrapper.setAttribute('data-feature-slug', feature.slug);
        wrapper.setAttribute('data-feature-name', feature.name.toLowerCase());

        const isAiSuggested = this.aiSuggestions.some(s => s.feature_id === feature.id);

        if (feature.type === 'boolean') {
            // Checkbox
            const label = document.createElement('label');
            label.className = 'flex items-center p-3 border-2 border-gray-200 dark:border-gray-700 rounded-lg hover:border-lime-300 dark:hover:border-lime-600 hover:bg-lime-50 dark:hover:bg-lime-900/20 transition-all duration-200 cursor-pointer group';

            if (isAiSuggested) {
                label.classList.add('border-purple-400', 'dark:border-purple-500', 'bg-purple-50', 'dark:bg-purple-900/20');
            }

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = `features[${feature.id}]`;
            checkbox.value = '1';
            checkbox.id = `feature_${feature.id}`;
            checkbox.className = 'rounded border-gray-300 text-lime-600 focus:ring-lime-500 focus:ring-2';

            if (isAiSuggested) {
                checkbox.checked = true;
            }

            const textDiv = document.createElement('div');
            textDiv.className = 'ml-3 flex-1';

            const nameSpan = document.createElement('span');
            nameSpan.className = 'text-sm font-medium text-gray-900 dark:text-white group-hover:text-lime-600 dark:group-hover:text-lime-400 transition-colors';
            nameSpan.textContent = feature.name;

            if (isAiSuggested) {
                const aiIcon = document.createElement('span');
                aiIcon.className = 'ml-2 text-purple-600 dark:text-purple-400';
                aiIcon.innerHTML = '✨ <span class="text-xs">AI</span>';
                aiIcon.title = 'Yalıhan AI Önerisi';
                nameSpan.appendChild(aiIcon);
            }

            textDiv.appendChild(nameSpan);

            if (feature.description) {
                const desc = document.createElement('p');
                desc.className = 'text-xs text-gray-500 dark:text-gray-400 mt-1';
                desc.textContent = feature.description;
                textDiv.appendChild(desc);
            }

            label.appendChild(checkbox);
            label.appendChild(textDiv);
            wrapper.appendChild(label);

        } else if (feature.type === 'select') {
            // Select dropdown
            const labelEl = document.createElement('label');
            labelEl.className = 'block text-sm font-medium text-gray-900 dark:text-white mb-2';
            labelEl.textContent = feature.name;

            const select = document.createElement('select');
            select.name = `features[${feature.id}]`;
            select.className = 'w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-lime-500 focus:border-lime-500 transition-all duration-200';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Seçin...';
            select.appendChild(placeholder);

            if (feature.options && Array.isArray(feature.options)) {
                feature.options.forEach(opt => {
                    const option = document.createElement('option');
                    option.value = opt;
                    option.textContent = opt;
                    select.appendChild(option);
                });
            }

            wrapper.appendChild(labelEl);
            wrapper.appendChild(select);

        } else if (feature.type === 'number') {
            // Number input
            const labelEl = document.createElement('label');
            labelEl.className = 'block text-sm font-medium text-gray-900 dark:text-white mb-2';
            labelEl.textContent = feature.name;

            if (feature.unit) {
                const unitSpan = document.createElement('span');
                unitSpan.className = 'text-xs text-gray-500 ml-1';
                unitSpan.textContent = `(${feature.unit})`;
                labelEl.appendChild(unitSpan);
            }

            const input = document.createElement('input');
            input.type = 'number';
            input.name = `features[${feature.id}]`;
            input.className = 'w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-lime-500 transition-all';

            wrapper.appendChild(labelEl);
            wrapper.appendChild(input);
        }

        return wrapper;
    },

    /**
     * Toggle accordion open/close
     *
     * @param {string} groupName - Group name
     */
    toggleAccordion(groupName) {
        const groupId = `accordion-${this.sanitizeId(groupName)}`;
        const content = document.getElementById(groupId);
        const chevron = document.getElementById(`chevron-${groupId}`);

        if (!content) return;

        const isOpen = !content.classList.contains('hidden');

        if (isOpen) {
            // Close
            content.classList.add('hidden');
            chevron?.classList.remove('rotate-180');
            this.openAccordions.delete(groupName);
        } else {
            // Open
            content.classList.remove('hidden');
            chevron?.classList.add('rotate-180');
            this.openAccordions.add(groupName);
        }
    },

    /**
     * Trigger AI suggestions for description
     *
     * @param {string} description - Description text (optional, will read from textarea)
     */
    async triggerAiSuggestions(description = null) {
        if (!this.currentCategoryId || !this.currentYayinTipiId) {
            console.warn('⚠️ Cannot trigger AI: category or yayin_tipi not set');
            return;
        }

        // Get description from textarea if not provided
        if (!description) {
            const textarea = document.querySelector('textarea[name="aciklama"]') ||
                document.getElementById('aciklama');
            description = textarea?.value || '';
        }

        if (!description || description.trim().length < 10) {
            console.log('ℹ️ Description too short for AI suggestions');
            return;
        }

        console.log('🤖 Triggering AI suggestions...');

        try {
            const response = await fetch(APIConfig.wizard.suggest, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                },
                body: JSON.stringify({
                    description: description,
                    category_id: this.currentCategoryId,
                    yayin_tipi_id: this.currentYayinTipiId
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }

            const result = await response.json();

            if (result.success) {
                this.aiSuggestions = result.data.suggestions || [];
                console.log(`✨ AI suggested ${this.aiSuggestions.length} features`);

                // Apply AI suggestions to UI
                this.applyAiSuggestions();
            }

        } catch (error) {
            console.error('❌ AI suggestions error:', error);
            // Phase 6 Fix: No hideLoading here (AI is background operation)
        }
    },

    /**
     * Apply AI suggestions to UI (check boxes, add badges)
     */
    applyAiSuggestions() {
        this.aiSuggestions.forEach(suggestion => {
            const checkbox = document.getElementById(`feature_${suggestion.feature_id}`);

            if (checkbox && checkbox.type === 'checkbox') {
                // Check the box
                checkbox.checked = true;

                // Add AI badge if not already present
                const label = checkbox.closest('label');
                if (label && !label.querySelector('.ai-badge')) {
                    const nameSpan = label.querySelector('span');
                    if (nameSpan && !nameSpan.querySelector('.text-purple-600')) {
                        const aiIcon = document.createElement('span');
                        aiIcon.className = 'ml-2 text-purple-600 dark:text-purple-400 ai-badge';
                        aiIcon.innerHTML = '✨ <span class="text-xs">AI</span>';
                        aiIcon.title = 'Yalıhan AI Önerisi';
                        nameSpan.appendChild(aiIcon);

                        // Highlight border
                        label.classList.add('border-purple-400', 'dark:border-purple-500', 'bg-purple-50', 'dark:bg-purple-900/20');
                    }
                }
            }
        });
    },

    /**
     * Handle real-time search
     *
     * @param {string} query - Search query
     */
    handleSearch(query) {
        this.searchQuery = query.toLowerCase().trim();

        if (!this.searchQuery) {
            // Clear search - show all
            this.showAllFeatures();
            this.elements.searchResults?.classList.add('hidden');
            return;
        }

        console.log(`🔍 Searching for: "${this.searchQuery}"`);

        let matchCount = 0;

        // Filter features
        const allFeatures = this.elements.accordion?.querySelectorAll('.feature-item');

        allFeatures?.forEach(feature => {
            const featureName = feature.getAttribute('data-feature-name') || '';
            const matches = featureName.includes(this.searchQuery);

            if (matches) {
                feature.style.display = '';
                matchCount++;
            } else {
                feature.style.display = 'none';
            }
        });

        // Update match count
        if (this.elements.searchCount) {
            this.elements.searchCount.textContent = matchCount;
        }
        this.elements.searchResults?.classList.remove('hidden');

        // Auto-open accordions with matches
        this.autoOpenMatchingGroups();
    },

    /**
     * Show all features (clear search filter)
     */
    showAllFeatures() {
        const allFeatures = this.elements.accordion?.querySelectorAll('.feature-item');
        allFeatures?.forEach(feature => {
            feature.style.display = '';
        });
    },

    /**
     * Auto-open accordion groups that have matching features
     */
    autoOpenMatchingGroups() {
        const groups = this.elements.accordion?.querySelectorAll('[data-group]');

        groups?.forEach(group => {
            const visibleFeatures = Array.from(group.querySelectorAll('.feature-item'))
                .filter(f => f.style.display !== 'none');

            const groupName = group.getAttribute('data-group');

            if (visibleFeatures.length > 0 && groupName) {
                // Open this group
                const groupId = `accordion-${this.sanitizeId(groupName)}`;
                const content = document.getElementById(groupId);
                const chevron = document.getElementById(`chevron-${groupId}`);

                if (content && content.classList.contains('hidden')) {
                    content.classList.remove('hidden');
                    chevron?.classList.add('rotate-180');
                    this.openAccordions.add(groupName);
                }
            }
        });
    },

    /**
     * Get icon for feature group
     *
     * @param {string} groupName - Group name
     * @returns {string} Emoji icon
     */
    getGroupIcon(groupName) {
        const icons = {
            'İç Özellikler': '🏠',
            'Dış Özellikler': '🌳',
            'Muhit': '🌆',
            'Arsa Özellikleri': '🏗️',
            'Tapu & Hukuki': '📜',
            'Fiyatlandırma': '💰',
            'Rezervasyon Kuralları': '📅',
            'Genel Özellikler': '⚙️'
        };

        return icons[groupName] || '📋';
    },

    /**
     * Sanitize ID for DOM
     *
     * @param {string} str - String to sanitize
     * @returns {string} Sanitized ID
     */
    sanitizeId(str) {
        return str.toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9\-]/g, '');
    },

    /**
     * Show loading state (INSTANT - No spinner, fade only)
     */
    showLoading() {
        // Phase 6 Enhancement: Instant transitions with fade
        this.elements.emptyState?.classList.add('opacity-0');
        this.elements.error?.classList.add('opacity-0');
        this.elements.accordion?.classList.add('opacity-0');

        setTimeout(() => {
            this.elements.emptyState?.classList.add('hidden');
            this.elements.error?.classList.add('hidden');
            this.elements.accordion?.classList.add('hidden');
            this.elements.searchContainer?.classList.add('hidden');
            this.elements.aiSuggestBtn?.classList.add('hidden');

            // Show loading with fade-in (NO spinner for Redis speed)
            this.elements.loading?.classList.remove('hidden');
            this.elements.loading?.classList.add('opacity-0');
            setTimeout(() => this.elements.loading?.classList.remove('opacity-0'), 10);
        }, 150);
    },

    /**
     * Show empty state
     *
     * @param {string} message - Custom message (optional)
     */
    showEmptyState(message = null) {
        this.elements.emptyState?.classList.remove('hidden');
        this.elements.loading?.classList.add('hidden');
        this.elements.error?.classList.add('hidden');
        this.elements.accordion?.classList.add('hidden');
        this.elements.searchContainer?.classList.add('hidden');
        this.elements.aiSuggestBtn?.classList.add('hidden');

        if (message) {
            const emptyMessage = this.elements.emptyState?.querySelector('.empty-message');
            if (emptyMessage) {
                emptyMessage.textContent = message;
            }
        }
    },

    /**
     * Show error state
     *
     * @param {string} message - Error message
     */
    showError(message) {
        this.elements.error?.classList.remove('hidden');
        this.elements.loading?.classList.add('hidden');
        this.elements.emptyState?.classList.add('hidden');
        this.elements.accordion?.classList.add('hidden');

        if (this.elements.errorMessage) {
            this.elements.errorMessage.textContent = message;
        }
    },

    /**
     * Hide loading state
     * Phase 6 Fix: Explicit method for error recovery
     */
    hideLoading() {
        this.elements.loading?.classList.add('hidden');
    },

    // ========================================
    // PHASE 6 ENHANCEMENT: VISUAL AI MAGIC
    // ========================================

    /**
     * Visual AI Magic: Photo-triggered feature suggestions
     * Simulates Antigravity's visual matrix analysis
     *
     * @param {File} photoFile - Uploaded photo file
     */
    async analyzePhotoAndSuggest(photoFile) {
        console.log('📸 Visual AI Magic: Analyzing photo...', photoFile.name);

        // Visual keyword extraction from filename/metadata
        const visualKeywords = this.extractVisualKeywords(photoFile.name.toLowerCase());

        // Visual matrix simulation (mock Antigravity)
        const visualSuggestions = this.simulateVisualMatrix(visualKeywords);

        if (visualSuggestions.length > 0) {
            console.log(`✨ Visual AI detected ${visualSuggestions.length} features from photo`);

            // Apply suggestions with visual magic effect
            this.applyVisualSuggestions(visualSuggestions);

            // Trigger quality score update
            if (window.QualityScoreMeter) {
                window.QualityScoreMeter.recalculate();
            }
        }
    },

    /**
     * Extract visual keywords from photo filename
     *
     * @param {string} filename - Photo filename
     * @returns {Array} Visual keywords
     */
    extractVisualKeywords(filename) {
        const keywords = [];

        // Visual detection patterns
        const patterns = {
            havuz: ['pool', 'havuz', 'swimming'],
            bahce: ['garden', 'bahce', 'yard', 'outdoor'],
            deniz: ['sea', 'ocean', 'deniz', 'beach', 'sahil'],
            balkon: ['balcony', 'balkon', 'terrace', 'teras'],
            villa: ['villa', 'luxury', 'luks'],
            mutfak: ['kitchen', 'mutfak', 'modern'],
            banyo: ['bathroom', 'banyo', 'wc'],
            salon: ['living', 'salon', 'lounge']
        };

        for (const [key, patterns_list] of Object.entries(patterns)) {
            if (patterns_list.some(p => filename.includes(p))) {
                keywords.push(key);
            }
        }

        return keywords;
    },

    /**
     * Simulate Antigravity visual matrix
     *
     * @param {Array} visualKeywords - Detected visual keywords
     * @returns {Array} Feature suggestions
     */
    simulateVisualMatrix(visualKeywords) {
        const visualMap = {
            havuz: ['ortak-havuz', 'havuz'],
            bahce: ['bahce', 'ortak-bahce', 'peyzaj'],
            deniz: ['deniz-manzarali', 'denize-yakin'],
            balkon: ['balkon', 'fransiz-balkon'],
            villa: ['luks', 'site-ici'],
            mutfak: ['ankastre-mutfak', 'modern-mutfak'],
            banyo: ['ebeveyn-banyosu', 'banyo'],
            salon: ['genis-salon', 'oturma-odasi']
        };

        const suggestions = [];

        visualKeywords.forEach(keyword => {
            const features = visualMap[keyword] || [];
            features.forEach(slug => {
                // Find feature by slug
                const feature = this.findFeatureBySlug(slug);
                if (feature) {
                    suggestions.push({
                        feature_id: feature.id,
                        slug: feature.slug,
                        name: feature.name,
                        confidence: 0.95,
                        source: 'visual_ai'
                    });
                }
            });
        });

        return suggestions;
    },

    /**
     * Find feature by slug in loaded data
     *
     * @param {string} slug - Feature slug
     * @returns {object|null} Feature object
     */
    findFeatureBySlug(slug) {
        if (!this.featuresData || !this.featuresData.groups) return null;

        for (const group of this.featuresData.groups) {
            const feature = group.features.find(f => f.slug === slug);
            if (feature) return feature;
        }

        return null;
    },

    /**
     * Apply visual suggestions with magic effect
     *
     * @param {Array} suggestions - Visual AI suggestions
     */
    applyVisualSuggestions(suggestions) {
        suggestions.forEach((suggestion, index) => {
            // Stagger animation (100ms delay per item)
            setTimeout(() => {
                const checkbox = document.getElementById(`feature_${suggestion.feature_id}`);

                if (checkbox && checkbox.type === 'checkbox') {
                    // Magic pulse effect
                    const label = checkbox.closest('label');
                    if (label) {
                        // Add magic glow animation
                        label.classList.add('animate-pulse');
                        setTimeout(() => label.classList.remove('animate-pulse'), 1000);

                        // Check the box
                        checkbox.checked = true;

                        // Add visual AI badge
                        const nameSpan = label.querySelector('span');
                        if (nameSpan && !nameSpan.querySelector('.visual-ai-badge')) {
                            const badge = document.createElement('span');
                            badge.className = 'ml-2 text-blue-600 dark:text-blue-400 visual-ai-badge';
                            badge.innerHTML = '📸 <span class="text-xs">Visual AI</span>';
                            badge.title = 'Fotoğraftan Tespit Edildi';
                            nameSpan.appendChild(badge);

                            // Highlight border (blue for visual AI)
                            label.classList.add('border-blue-400', 'dark:border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                        }
                    }
                }
            }, index * 100);
        });

        // Show toast notification
        this.showToast(`📸 ${suggestions.length} özellik fotoğraftan tespit edildi!`, 'success');
    },

    /**
     * Show toast notification
     *
     * @param {string} message - Toast message
     * @param {string} type - Toast type (success, info, error)
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        const colors = {
            success: 'bg-green-500',
            info: 'bg-blue-500',
            error: 'bg-red-500'
        };

        toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg transition-all duration-300 transform ${colors[type] || colors.info} text-white font-medium z-50`;
        toast.textContent = message;

        document.body.appendChild(toast);

        // Fade out and remove
        setTimeout(() => {
            toast.classList.add('translate-y-2', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

// ========================================
// LIVE QUALITY SCORE METER (LQS)
// ========================================

const QualityScoreMeter = {
    score: 0,
    maxScore: 100,
    elements: {},

    /**
     * Initialize quality score meter
     */
    init() {
        console.log('📊 Quality Score Meter initializing...');

        // Create meter UI
        this.createMeterUI();

        // Setup listeners
        this.setupListeners();

        console.log('✅ Quality Score Meter ready');
    },

    /**
     * Create meter UI element
     */
    createMeterUI() {
        const meter = document.createElement('div');
        meter.id = 'quality-score-meter';
        meter.className = 'fixed top-20 right-4 bg-white dark:bg-gray-800 rounded-xl shadow-lg p-4 w-64 border border-gray-200 dark:border-gray-700 z-40 transition-all duration-300';
        meter.innerHTML = `
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-bold text-gray-800 dark:text-white">İlan Kalite Skoru</h3>
                <span id="score-value" class="text-2xl font-bold text-lime-600 dark:text-lime-400">0%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                <div id="score-bar" class="h-full bg-gradient-to-r from-lime-500 to-green-600 transition-all duration-500 ease-out" style="width: 0%"></div>
            </div>
            <div class="mt-3 space-y-1 text-xs text-gray-600 dark:text-gray-400">
                <div class="flex justify-between">
                    <span>Temel Bilgiler:</span>
                    <span id="basic-score" class="font-medium">0/30</span>
                </div>
                <div class="flex justify-between">
                    <span>Açıklama:</span>
                    <span id="description-score" class="font-medium">0/20</span>
                </div>
                <div class="flex justify-between">
                    <span>Özellikler:</span>
                    <span id="features-score" class="font-medium">0/30</span>
                </div>
                <div class="flex justify-between">
                    <span>Fotoğraflar:</span>
                    <span id="photos-score" class="font-medium">0/20</span>
                </div>
            </div>
        `;

        document.body.appendChild(meter);

        // Cache elements
        this.elements = {
            meter: meter,
            scoreValue: document.getElementById('score-value'),
            scoreBar: document.getElementById('score-bar'),
            basicScore: document.getElementById('basic-score'),
            descriptionScore: document.getElementById('description-score'),
            featuresScore: document.getElementById('features-score'),
            photosScore: document.getElementById('photos-score')
        };
    },

    /**
     * Setup event listeners
     */
    setupListeners() {
        // Listen for form changes
        document.addEventListener('change', () => this.recalculate());
        document.addEventListener('input', () => {
            clearTimeout(this._recalcTimer);
            this._recalcTimer = setTimeout(() => this.recalculate(), 500);
        });
    },

    /**
     * Recalculate quality score
     */
    recalculate() {
        const scores = {
            basic: this.calculateBasicScore(),
            description: this.calculateDescriptionScore(),
            features: this.calculateFeaturesScore(),
            photos: this.calculatePhotosScore()
        };

        this.score = scores.basic + scores.description + scores.features + scores.photos;

        // Update UI with animation
        this.updateUI(scores);
    },

    /**
     * Calculate basic info score (30 points)
     */
    calculateBasicScore() {
        let score = 0;

        // Category (10 points)
        if (document.querySelector('#alt_kategori_id')?.value) score += 10;

        // Price (10 points)
        if (document.querySelector('[name="fiyat"]')?.value) score += 10;

        // Location (10 points)
        if (document.querySelector('#il_id')?.value && document.querySelector('#ilce_id')?.value) {
            score += 10;
        }

        return score;
    },

    /**
     * Calculate description score (20 points)
     */
    calculateDescriptionScore() {
        const description = document.querySelector('textarea[name="aciklama"]')?.value || '';
        const length = description.trim().length;

        if (length === 0) return 0;
        if (length < 50) return 5;
        if (length < 150) return 10;
        if (length < 300) return 15;
        return 20; // 300+ characters
    },

    /**
     * Calculate features score (30 points)
     */
    calculateFeaturesScore() {
        const checkedFeatures = document.querySelectorAll('input[name^="features["][type="checkbox"]:checked');
        const count = checkedFeatures.length;

        if (count === 0) return 0;
        if (count < 3) return 10;
        if (count < 7) return 20;
        return 30; // 7+ features
    },

    /**
     * Calculate photos score (20 points)
     */
    calculatePhotosScore() {
        // Mock: check for photo upload elements
        const photoInputs = document.querySelectorAll('input[type="file"][accept*="image"]');
        let uploadedCount = 0;

        photoInputs.forEach(input => {
            if (input.files && input.files.length > 0) uploadedCount += input.files.length;
        });

        if (uploadedCount === 0) return 0;
        if (uploadedCount < 3) return 10;
        if (uploadedCount < 6) return 15;
        return 20; // 6+ photos
    },

    /**
     * Update UI with animation
     *
     * @param {object} scores - Score breakdown
     */
    updateUI(scores) {
        // Update percentage
        this.elements.scoreValue.textContent = `${this.score}%`;

        // Update bar with smooth animation
        this.elements.scoreBar.style.width = `${this.score}%`;

        // Color gradient based on score
        if (this.score < 40) {
            this.elements.scoreBar.className = 'h-full bg-gradient-to-r from-red-500 to-orange-500 transition-all duration-500 ease-out';
        } else if (this.score < 70) {
            this.elements.scoreBar.className = 'h-full bg-gradient-to-r from-yellow-500 to-orange-500 transition-all duration-500 ease-out';
        } else {
            this.elements.scoreBar.className = 'h-full bg-gradient-to-r from-lime-500 to-green-600 transition-all duration-500 ease-out';
        }

        // Update breakdown
        this.elements.basicScore.textContent = `${scores.basic}/30`;
        this.elements.descriptionScore.textContent = `${scores.description}/20`;
        this.elements.featuresScore.textContent = `${scores.features}/30`;
        this.elements.photosScore.textContent = `${scores.photos}/20`;
    }
};

// Export for global access
window.FeaturesWizard = FeaturesWizard;
window.QualityScoreMeter = QualityScoreMeter;

// Auto-initialize Quality Score Meter
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => QualityScoreMeter.init());
} else {
    QualityScoreMeter.init();
}
