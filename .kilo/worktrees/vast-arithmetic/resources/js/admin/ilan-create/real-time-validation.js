/**
 * Real-Time Validation System for İlan Ekleme Wizard
 * Context7 Compliant - Client-side validation with instant feedback
 *
 * Features:
 * - Real-time field validation (onblur, oninput)
 * - Inline error messages
 * - Field-level success indicators
 * - Step validation before navigation
 * - Form completion percentage
 */

class RealTimeValidator {
    constructor(options = {}) {
        this.config = {
            validateOnBlur: true,
            validateOnInput: true,
            showSuccessIndicators: true,
            debounceDelay: 300,
            ...options,
        };

        this.rules = {};
        this.errors = {};
        this.validatedFields = new Set();
        this.debounceTimers = new Map();

        this.init();
    }

    /**
     * Initialize validation system
     */
    init() {
        // SSOT Mode: Wait for wizard-context-applied event for validation rules
        document.addEventListener('wizard-context-applied', (e) => {
            if (e.detail?.context?.template?.validation_rules) {
                this.setRulesFromSSOT(e.detail.context.template.validation_rules);
            }
        });

        // Setup form listeners
        this.setupFormListeners();

        // Setup step validation
        this.setupStepValidation();

        console.log('✅ Real-Time Validation system initialized (SSOT mode)');
    }

    /**
     * Set validation rules from SSOT context
     */
    setRulesFromSSOT(ssotRules) {
        if (!ssotRules || typeof ssotRules !== 'object') {
            console.warn('⚠️ Invalid SSOT rules, keeping defaults');
            return;
        }

        this.rules = { ...this.rules, ...ssotRules };
        console.log('✅ Validation rules updated from SSOT:', Object.keys(ssotRules).length);
    }

    /**
     * Load default validation rules (NO API CALL)
     * SSOT will override these via wizard-context-applied event
     */
    async loadValidationRules() {
        // DEPRECATED: Soft mode - Use defaults, SSOT overrides via event
        console.log('ℹ️ Using default validation rules (SSOT will override)');

        // Default validation rules
        this.rules = {
            // Step 1: Temel Bilgiler
            ana_kategori_id: {
                required: true,
                message: 'Ana kategori seçimi zorunludur',
            },
            alt_kategori_id: {
                required: true,
                message: 'Alt kategori seçimi zorunludur',
            },
            junction_id: {
                required: true,
                message: 'Yayın tipi seçimi zorunludur',
            },
            baslik: {
                required: true,
                minLength: 10,
                maxLength: 255,
                message: 'Başlık 10-255 karakter arasında olmalıdır',
            },
            fiyat: {
                required: true,
                numeric: true,
                min: 0,
                message: 'Fiyat geçerli bir sayı olmalıdır',
            },
            para_birimi: {
                required: true,
                in: ['TRY', 'USD', 'EUR', 'GBP'],
                message: 'Geçerli bir para birimi seçiniz',
            },
            il_id: {
                required: true,
                message: 'İl seçimi zorunludur',
            },
            ilce_id: {
                required: true,
                message: 'İlçe seçimi zorunludur',
            },
            mahalle_id: {
                required: false, // Optional
            },
            adres: {
                required: true,
                minLength: 10,
                message: 'Adres en az 10 karakter olmalıdır',
            },

            // Step 2: Detaylar (Kategoriye özel - dinamik yüklenecek)
            metrekare: {
                required: false,
                numeric: true,
                min: 0,
                message: 'Metrekare geçerli bir sayı olmalıdır',
            },
            oda_sayisi: {
                required: false,
                integer: true,
                min: 0,
                message: 'Oda sayısı geçerli bir sayı olmalıdır',
            },

            // Step 3: Ek Bilgiler
            aciklama: {
                required: true,
                minLength: 50,
                message: 'Açıklama en az 50 karakter olmalıdır',
            },
            ilan_sahibi_id: {
                required: true,
                message: 'İlan sahibi seçimi zorunludur',
            },
            aktiflik_durumu: {
                required: true,
                in: ['Aktif', 'Deaktif', 'İncelemede', 'Bekleniyor', 'Taslak'],
                message: 'Geçerli bir durum seçiniz',
            },
        };
    }

    /**
     * Setup form listeners for real-time validation
     */
    setupFormListeners() {
        const form = document.getElementById('ilan-wizard-form');
        if (!form) return;

        // Validate on blur
        if (this.config.validateOnBlur) {
            form.addEventListener(
                'blur',
                (e) => {
                    if (e.target.matches('input, select, textarea')) {
                        this.validateField(e.target.name, e.target.value, true);
                    }
                },
                true
            );
        }

        // Validate on input (debounced)
        if (this.config.validateOnInput) {
            form.addEventListener(
                'input',
                (e) => {
                    if (e.target.matches('input, select, textarea')) {
                        this.debounceValidate(e.target.name, e.target.value);
                    }
                },
                true
            );
        }
    }

    /**
     * Debounced validation
     */
    debounceValidate(fieldName, value) {
        clearTimeout(this.debounceTimers.get(fieldName));

        const timer = setTimeout(() => {
            this.validateField(fieldName, value, false);
        }, this.config.debounceDelay);

        this.debounceTimers.set(fieldName, timer);
    }

    /**
     * Validate a single field
     */
    validateField(fieldName, value, showErrors = true) {
        const rule = this.rules[fieldName];
        if (!rule) {
            // No rule = valid
            this.clearFieldError(fieldName);
            return true;
        }

        const errors = [];

        // Required check
        if (rule.required) {
            if (
                value === null ||
                value === undefined ||
                value === '' ||
                (Array.isArray(value) && value.length === 0)
            ) {
                errors.push(rule.message || `${fieldName} zorunludur`);
            }
        }

        // Skip other validations if empty and not required
        if (!value && !rule.required) {
            this.clearFieldError(fieldName);
            if (this.config.showSuccessIndicators) {
                this.showFieldSuccess(fieldName);
            }
            return true;
        }

        // Min length check
        if (rule.minLength && value && value.length < rule.minLength) {
            errors.push(rule.message || `${fieldName} en az ${rule.minLength} karakter olmalıdır`);
        }

        // Max length check
        if (rule.maxLength && value && value.length > rule.maxLength) {
            errors.push(
                rule.message || `${fieldName} en fazla ${rule.maxLength} karakter olmalıdır`
            );
        }

        // Numeric check
        if (rule.numeric && value && isNaN(parseFloat(value))) {
            errors.push(rule.message || `${fieldName} geçerli bir sayı olmalıdır`);
        }

        // Integer check
        if (rule.integer && value && !Number.isInteger(parseFloat(value))) {
            errors.push(rule.message || `${fieldName} tam sayı olmalıdır`);
        }

        // Min value check
        if (rule.min !== undefined && value !== null && parseFloat(value) < rule.min) {
            errors.push(rule.message || `${fieldName} en az ${rule.min} olmalıdır`);
        }

        // Max value check
        if (rule.max !== undefined && value !== null && parseFloat(value) > rule.max) {
            errors.push(rule.message || `${fieldName} en fazla ${rule.max} olmalıdır`);
        }

        // In array check
        if (rule.in && !rule.in.includes(value)) {
            errors.push(rule.message || `${fieldName} geçerli bir değer seçiniz`);
        }

        // Custom validator function
        if (rule.validator && typeof rule.validator === 'function') {
            const customError = rule.validator(value);
            if (customError) {
                errors.push(customError);
            }
        }

        // Show/hide errors
        if (errors.length > 0) {
            if (showErrors) {
                const errorMessage = errors[0];
                this.showFieldError(fieldName, errorMessage);

                // Use standardized error handler if available
                if (window.errorHandler) {
                    window.errorHandler.showValidationError(fieldName, 'required', null);
                }
            }
            this.errors[fieldName] = errors;
            this.validatedFields.delete(fieldName);
            return false;
        } else {
            this.clearFieldError(fieldName);
            if (this.config.showSuccessIndicators) {
                this.showFieldSuccess(fieldName);
            }
            delete this.errors[fieldName];
            this.validatedFields.add(fieldName);
            return true;
        }
    }

    /**
     * Show field error
     */
    showFieldError(fieldName, message) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        // Remove existing error
        this.clearFieldError(fieldName);

        // Add error class
        field.classList.add('border-red-500', 'dark:border-red-500');
        field.classList.remove('border-green-500', 'dark:border-green-500');

        // Create error message element
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error text-xs text-red-600 dark:text-red-400 mt-1';
        errorDiv.id = `error-${fieldName}`;
        errorDiv.textContent = message;

        // Insert after field
        const fieldContainer = field.closest('.space-y-2') || field.parentElement;
        if (fieldContainer) {
            fieldContainer.appendChild(errorDiv);
        } else {
            field.insertAdjacentElement('afterend', errorDiv);
        }
    }

    /**
     * Show field success indicator
     */
    showFieldSuccess(fieldName) {
        const field = document.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        field.classList.add('border-green-500', 'dark:border-green-500');
        field.classList.remove('border-red-500', 'dark:border-red-500');
    }

    /**
     * Clear field error
     */
    clearFieldError(fieldName) {
        const errorDiv = document.getElementById(`error-${fieldName}`);
        if (errorDiv) {
            errorDiv.remove();
        }

        const field = document.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.remove('border-red-500', 'dark:border-red-500');
        }
    }

    /**
     * Validate step
     */
    validateStep(stepNumber) {
        const stepFields = this.getStepFields(stepNumber);
        let isValid = true;

        stepFields.forEach((fieldName) => {
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (!field) return;

            const value = this.getFieldValue(field);
            const fieldValid = this.validateField(fieldName, value, true);

            if (!fieldValid) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Get fields for a step
     */
    getStepFields(stepNumber) {
        const stepFieldMap = {
            1: [
                'ana_kategori_id',
                'alt_kategori_id',
                'junction_id',
                'baslik',
                'fiyat',
                'para_birimi',
                'il_id',
                'ilce_id',
                'adres',
            ],
            2: ['metrekare', 'oda_sayisi', 'banyo_sayisi'], // Kategoriye özel alanlar dinamik yüklenecek
            3: ['aciklama', 'ilan_sahibi_id', 'aktiflik_durumu'],
        };

        return stepFieldMap[stepNumber] || [];
    }

    /**
     * Get field value
     */
    getFieldValue(field) {
        if (field.type === 'checkbox') {
            return field.checked;
        } else if (field.type === 'radio') {
            const checked = document.querySelector(`[name="${field.name}"]:checked`);
            return checked ? checked.value : null;
        } else if (field.tagName === 'SELECT' && field.multiple) {
            return Array.from(field.selectedOptions).map((opt) => opt.value);
        } else {
            return field.value;
        }
    }

    /**
     * Setup step validation hooks
     */
    setupStepValidation() {
        // Validation hook wizard'ın nextStep fonksiyonuna blade'de eklendi
        // Burada sadece initialization yapıyoruz
        console.log('✅ Step validation hooks ready');
    }

    /**
     * Get form completion percentage
     */
    getFormCompletionPercentage() {
        const allFields = Object.keys(this.rules);
        const requiredFields = allFields.filter((field) => this.rules[field].required);
        const completedRequiredFields = requiredFields.filter((field) => {
            const fieldElement = document.querySelector(`[name="${field}"]`);
            if (!fieldElement) return false;
            const value = this.getFieldValue(fieldElement);
            return value !== null && value !== undefined && value !== '';
        });

        if (requiredFields.length === 0) return 100;
        return Math.round((completedRequiredFields.length / requiredFields.length) * 100);
    }

    /**
     * Update form completion indicator
     */
    updateFormCompletionIndicator() {
        const percentage = this.getFormCompletionPercentage();
        const indicator = document.getElementById('form-completion-indicator');
        if (indicator) {
            indicator.textContent = `Form Tamamlanma: %${percentage}`;
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.realTimeValidator = new RealTimeValidator({
        validateOnBlur: true,
        validateOnInput: true,
        showSuccessIndicators: true,
        debounceDelay: 300,
    });

    // Update completion indicator periodically
    setInterval(() => {
        if (window.realTimeValidator) {
            window.realTimeValidator.updateFormCompletionIndicator();
        }
    }, 2000);
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RealTimeValidator;
}
