/**
 * Schema Field Renderer — Alpine.js Engine
 *
 * Wizard Step 2 için schema-driven field rendering motoru.
 * KategoriYayinTipiFieldDependency tablosundan gelen schema'yı
 * dinamik olarak DOM'a render eder.
 *
 * Kullanım:
 *   <div x-data="schemaFieldRenderer" data-kategori-id="5" data-yayin-tipi-id="2">
 *     <template x-for="group in groupedFields" :key="group.slug">...</template>
 *   </div>
 *
 * @version 2.0.0
 * @context Category-Driven Wizard Engine
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('schemaFieldRenderer', () => ({
        // State
        schema: null,
        fields: [],
        groupedFields: [],
        fieldValues: {},
        meta: {},
        loading: false,
        loaded: false,
        error: null,

        // Draft State (Phase 2)
        draftId: null,
        version: 1, // Phase 2.5: Concurrency tracking
        isSaving: false,
        lastSavedAt: null,
        saveTimeout: null,
        saveStatus: 'idle', // idle, saving, saved, error, offline
        isDirty: false, // Phase 4.6: Track unsaved changes
        retryCount: 0,
        maxRetries: 3,
        fieldErrors: {}, // Phase 3: Field-level validation errors
        aiSuggestions: {}, // Phase 4: AI Field Suggestions
        isAnalyzing: false, // Phase 4: AI Analysis Loading State
        validationValid: true, // Phase 4.7: Overall validity status

        // Category context
        kategoriId: null,
        yayinTipiId: null,

        /**
         * Initialize — reads category context and draft info
         */
        init() {
            // Read draft ID from global window, session or URL
            this.draftId = window.ilanId || window.draftId || null;

            // Read from DOM data attributes
            this.kategoriId = this.$el?.dataset?.kategoriId
                || document.getElementById('alt_kategori_id')?.value
                || null;
            this.yayinTipiId = this.$el?.dataset?.yayinTipiId
                || document.getElementById('junction_id')?.value
                || document.getElementById('yayin_tipi_id')?.value
                || null;

            // Listen for Step 1 completion (which returns draft_id)
            window.addEventListener('wizard:step1-completed', (e) => {
                const { draft_id, kategori_id, yayin_tipi_id } = e.detail || {};
                if (draft_id) this.draftId = draft_id;
                if (kategori_id) this.kategoriId = kategori_id;
                if (yayin_tipi_id) this.yayinTipiId = yayin_tipi_id;
                this.loadSchema();
            });

            // Listen for field value changes (from child components)
            this.$el?.addEventListener('field-changed', (e) => {
                const { slug, value } = e.detail || {};
                if (slug) {
                    this.fieldValues[slug] = value;
                    this.isDirty = true;
                    this.evaluateDependencies();
                    
                    // Trigger Auto-save
                    if (this.draftId) {
                        this.queueAutoSave(slug, value);
                    }
                }
            });

            // Phase 4.6: Guard against unsaved changes
            window.addEventListener('beforeunload', (e) => {
                if (this.isDirty || this.saveStatus === 'saving') {
                    e.preventDefault();
                    e.returnValue = 'Kaydedilmemiş değişiklikleriniz var. Ayrılmak istediğinize emin misiniz?';
                }
            });

            // Auto-load if context is already available
            if (this.kategoriId && this.yayinTipiId) {
                this.loadSchema();
            }
        },

        /**
         * Queues a field for auto-save with 3-second debounce
         */
        queueAutoSave(field, value) {
            this.saveStatus = 'idle';
            
            if (this.saveTimeout) {
                clearTimeout(this.saveTimeout);
            }

            this.saveTimeout = setTimeout(() => {
                this.persistField(field, value);
            }, 3500); // 3.5 seconds debounce
        },

        /**
         * 💾 Manual Save Trigger
         */
        forceSave() {
            if (this.saveTimeout) {
                clearTimeout(this.saveTimeout);
            }
            
            // Find last changed field or save all currently in memory
            // For simplicity, we trigger persist on any field if dirty
            if (this.isDirty) {
                const lastField = Object.keys(this.fieldValues).pop();
                if (lastField) {
                    this.persistField(lastField, this.fieldValues[lastField]);
                }
            }
        },

        /**
         * Persists a single field to the backend API
         */
        async persistField(field, value, attempt = 0) {
            if (!this.draftId) return;
            
            // Network check
            if (!navigator.onLine) {
                this.saveStatus = 'offline';
                return;
            }

            this.isSaving = true;
            this.saveStatus = 'saving';
            
            // Clear specific field error before trying again
            if (this.fieldErrors[field]) {
                delete this.fieldErrors[field];
            }

            try {
                const response = await fetch('/api/v1/wizard/update-field', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                    body: JSON.stringify({
                        draft_id: this.draftId,
                        field: field,
                        value: value
                    })
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.message || 'Kayıt başarısız');
                }

                // Phase 4.7: Hybrid Success Response Handling
                // Veri her durumda kaydedilir, ancak doğrulama durumunu senkronize ederiz.
                const validation = result.data?.validation || { is_valid: true, errors: {} };
                
                // Hataları temizle ve yeni hataları ekle
                this.fieldErrors = validation.errors || {};

                // Phase 3: State Sync-back
                if (result.success && result.data && result.data.payload) {
                    this.fieldValues = result.data.payload;
                    this.version = result.data.version || this.version;
                }

                // Status Update
                this.saveStatus = 'saved';
                this.validationValid = validation.is_valid;
                this.isDirty = false;
                this.lastSavedAt = new Date();
                this.retryCount = 0;
                
                console.log(`💾 Field saved (Valid: ${validation.is_valid}): ${field}`);
                this.evaluateDependencies();
            } catch (err) {
                console.error('Auto-save error:', err);
                
                // Phase 4.6: Exponential Backoff Retry Logic for network/server errors
                if (attempt < this.maxRetries && !this.fieldErrors[field]) {
                    this.saveStatus = 'saving';
                    const delay = Math.pow(2, attempt) * 1000;
                    console.log(`🔄 Retrying save in ${delay}ms... (Attempt ${attempt + 1})`);
                    setTimeout(() => this.persistField(field, value, attempt + 1), delay);
                } else {
                    this.saveStatus = 'error';
                    this.error = err.message;
                }
            } finally {
                this.isSaving = false;
            }
        },

        /**
         * Load schema from API
         */
        async loadSchema() {
            if (!this.kategoriId || !this.yayinTipiId) {
                this.fields = [];
                this.groupedFields = [];
                this.loaded = false;
                return;
            }

            this.loading = true;
            this.error = null;

            try {
                const url = `/api/v1/wizard/field-schema?kategori_id=${this.kategoriId}&yayin_tipi_id=${this.yayinTipiId}`;
                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });

                if (!response.ok) {
                    throw new Error(`Schema yüklenemedi (${response.status})`);
                }

                const result = await response.json();
                const data = result.data || result;

                this.schema = data;
                this.fields = data.fields || [];
                this.groupedFields = data.grouped || [];
                this.meta = data.meta || {};
                this.loaded = true;

                // Initialize field values from existing form data
                this.hydrateFromExistingValues();

                // Evaluate dependencies after initial load
                this.evaluateDependencies();

                console.log(`✅ Schema loaded: ${this.fields.length} fields for ${this.meta.kategori_slug}/${this.meta.yayin_tipi_slug}`);

                // Dispatch schema-loaded event for other components
                window.dispatchEvent(new CustomEvent('wizard:schema-loaded', {
                    detail: {
                        fields: this.fields,
                        meta: this.meta,
                        kategoriId: this.kategoriId,
                        yayinTipiId: this.yayinTipiId,
                    },
                }));

            } catch (err) {
                console.error('Schema yükleme hatası:', err);
                this.error = err.message;
                this.fields = [];
                this.groupedFields = [];
            } finally {
                this.loading = false;
            }
        },

        /**
         * 🪄 AI Assistant: Fetch field suggestions from Step 1 context
         */
        async fetchAiSuggestions() {
            if (!this.draftId || this.isAnalyzing) return;
            
            this.isAnalyzing = true;
            this.error = null;

            try {
                const response = await fetch(`/api/v1/wizard/ai-suggestions/${this.draftId}`);
                const result = await response.json();

                if (result.success) {
                    this.aiSuggestions = result.data.suggestions || {};
                    
                    const count = Object.keys(this.aiSuggestions).length;
                    if (count > 0) {
                        // Global event for toast/notification
                        window.dispatchEvent(new CustomEvent('notification', {
                            detail: { type: 'success', message: `${count} alanda akıllı öneri bulundu! 🪄` }
                        }));
                    }
                }
            } catch (err) {
                console.error('AI Suggestions error:', err);
            } finally {
                this.isAnalyzing = false;
            }
        },

        /**
         * 🪄 Apply specific AI suggestion to a field
         */
        async applyAiSuggestion(slug, value) {
            // Update local value
            this.fieldValues[slug] = value;
            
            // Clean up the suggestion icon
            if (this.aiSuggestions[slug]) {
                delete this.aiSuggestions[slug];
            }

            this.evaluateDependencies();

            // Persist immediately
            await this.persistField(slug, value);
        },

        /**
         * Hydrate field values from existing form inputs
         * (handles edit mode + draft resume)
         */
        hydrateFromExistingValues() {
            this.fields.forEach(field => {
                // Check for existing form input value
                const input = document.querySelector(`[name="features[${field.slug}]"]`);
                if (input && input.value) {
                    this.fieldValues[field.slug] = input.value;
                    return;
                }

                // Check for existing ilan data (edit mode)
                if (window.ilanData && window.ilanData[field.slug] !== undefined) {
                    this.fieldValues[field.slug] = window.ilanData[field.slug];
                    return;
                }

                // Check draft data
                if (window.draftData?.features?.[field.slug] !== undefined) {
                    this.fieldValues[field.slug] = window.draftData.features[field.slug];
                }
            });
        },

        /**
         * Evaluate field dependencies (visible_if, required_if, depends_on)
         */
        evaluateDependencies() {
            this.fields.forEach(field => {
                // visible_if evaluation
                if (field.visible_if) {
                    field._visible = this.evaluateCondition(field.visible_if);
                } else {
                    field._visible = true;
                }

                // required_if evaluation
                if (field.required_if) {
                    field._dynamicRequired = this.evaluateCondition(field.required_if);
                } else {
                    field._dynamicRequired = field.required || false;
                }

                // depends_on: simple "show if parent has value"
                if (field.depends_on) {
                    const parentValue = this.fieldValues[field.depends_on];
                    field._visible = field._visible && (parentValue !== undefined && parentValue !== '' && parentValue !== null && parentValue !== false && parentValue !== '0');
                }
            });
        },

        /**
         * Evaluate a condition object: { field: "slug", operator: "eq", value: "x" }
         * or simple { field: "slug" } (truthy check)
         */
        evaluateCondition(condition) {
            if (!condition || typeof condition !== 'object') return true;

            const { field, operator, value } = condition;
            if (!field) return true;

            const currentValue = this.fieldValues[field];

            switch (operator) {
                case 'eq':
                case '=':
                case '==':
                    return currentValue == value;
                case 'neq':
                case '!=':
                    return currentValue != value;
                case 'gt':
                case '>':
                    return Number(currentValue) > Number(value);
                case 'gte':
                case '>=':
                    return Number(currentValue) >= Number(value);
                case 'in':
                    return Array.isArray(value) && value.includes(currentValue);
                case 'not_empty':
                    return currentValue !== undefined && currentValue !== '' && currentValue !== null;
                default:
                    // Default: truthy check
                    return !!currentValue;
            }
        },

        /**
         * Check if a field should be visible
         */
        isFieldVisible(field) {
            return field._visible !== false;
        },

        /**
         * Check if a field is effectively required
         */
        isFieldRequired(field) {
            return field._dynamicRequired || field.required || false;
        },

        /**
         * Get field value
         */
        getFieldValue(slug) {
            return this.fieldValues[slug] ?? '';
        },

        /**
         * Set field value programmatically (used by AI auto-fill)
         */
        setFieldValue(slug, value) {
            this.fieldValues[slug] = value;

            // Update DOM input
            const input = document.querySelector(`[name="features[${slug}]"]`);
            if (input) {
                input.value = value;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }

            this.evaluateDependencies();
        },

        /**
         * Bulk set field values (AI auto-fill batch)
         */
        bulkSetFieldValues(values) {
            Object.entries(values).forEach(([slug, value]) => {
                this.setFieldValue(slug, value);
            });
        },

        /**
         * Get completion percentage for quality indicator
         */
        get completionPercentage() {
            if (this.fields.length === 0) return 0;

            const requiredFields = this.fields.filter(f => f.required && this.isFieldVisible(f));
            if (requiredFields.length === 0) return 100;

            const filledRequired = requiredFields.filter(f => {
                const val = this.fieldValues[f.slug];
                return val !== undefined && val !== '' && val !== null;
            });

            return Math.round((filledRequired.length / requiredFields.length) * 100);
        },

        /**
         * Get missing required fields
         */
        get missingRequiredFields() {
            return this.fields.filter(f =>
                f.required && this.isFieldVisible(f) && !this.fieldValues[f.slug]
            );
        },

        /**
         * Dispatch auto-save event (debounced by parent)
         */
        dispatchAutoSave() {
            window.dispatchEvent(new CustomEvent('wizard:auto-save', {
                detail: {
                    step: 2,
                    fieldValues: { ...this.fieldValues },
                    completion: this.completionPercentage,
                },
            }));
        },

        /**
         * Validate all visible required fields
         * @returns {{ valid: boolean, errors: object }}
         */
        validate() {
            const errors = {};
            this.fields.forEach(field => {
                if (!this.isFieldVisible(field)) return;
                if (!this.isFieldRequired(field)) return;

                const val = this.fieldValues[field.slug];
                if (val === undefined || val === '' || val === null) {
                    errors[field.slug] = `${field.name} alanı zorunludur`;
                }
            });

            return {
                valid: Object.keys(errors).length === 0,
                errors,
            };
        },

        /**
         * Get field type for Blade component mapping
         * Maps schema types → blade component names
         */
        getComponentName(type) {
            const map = {
                'text': 'input-text',
                'string': 'input-text',
                'number': 'input-number',
                'integer': 'input-number',
                'decimal': 'input-number',
                'float': 'input-number',
                'boolean': 'toggle',
                'toggle': 'toggle',
                'select': 'select',
                'dropdown': 'select',
                'multiselect': 'multiselect',
                'tags': 'multiselect',
                'textarea': 'textarea',
                'longtext': 'textarea',
            };
            return map[type] || 'input-text';
        },
    }));
});

// Export for non-module usage
if (typeof window !== 'undefined') {
    window.SchemaFieldRenderer = true;
}
