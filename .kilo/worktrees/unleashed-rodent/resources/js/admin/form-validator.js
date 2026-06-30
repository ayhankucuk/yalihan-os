/**
 * Neo Form Validation Framework
 *
 * Standardize form validations and dynamic behaviors
 * Usage: import { FormValidator } from './form-validator.js'
 */

class FormValidator {
    constructor(formSelector) {
        this.form = document.querySelector(formSelector);
        this.rules = new Map();
        this.dynamicFields = new Map();

        if (this.form) {
            this.init();
        }
    }

    /**
     * Initialize form validation
     */
    init() {
        this.setupEventListeners();
        this.setupDynamicFields();
        console.log('FormValidator initialized for:', this.form.id || 'unnamed form');
    }

    /**
     * Add validation rule for a field
     * @param {string} fieldName
     * @param {object} rules
     */
    addRule(fieldName, rules) {
        this.rules.set(fieldName, rules);
        return this;
    }

    /**
     * Add dynamic field behavior
     * @param {string} triggerField
     * @param {string} targetField
     * @param {function} condition
     */
    addDynamicField(triggerField, targetField, condition) {
        // ✅ FIX: Check if form exists before querying
        if (!this.form) {
            console.warn('FormValidator: Form not found, skipping addDynamicField');
            return this;
        }

        if (!this.dynamicFields.has(triggerField)) {
            this.dynamicFields.set(triggerField, []);
        }

        this.dynamicFields.get(triggerField).push({
            target: targetField,
            condition: condition,
        });

        // Setup event listener for trigger field
        // ✅ FIX: Double check form exists before querySelector
        if (!this.form) {
            console.warn('FormValidator: Form is null, cannot add dynamic field');
            return this;
        }

        const triggerElement = this.form.querySelector(
            `[name="${triggerField}"], #${triggerField}`,
        );
        if (triggerElement) {
            triggerElement.addEventListener('change', () => {
                this.handleDynamicField(triggerField);
            });
        }

        return this;
    }

    /**
     * Handle dynamic field changes
     * @param {string} triggerField
     */
    handleDynamicField(triggerField) {
        const behaviors = this.dynamicFields.get(triggerField);
        if (!behaviors) return;

        const triggerElement = this.form.querySelector(
            `[name="${triggerField}"], #${triggerField}`,
        );
        const triggerValue = triggerElement.value;

        behaviors.forEach((behavior) => {
            const targetElement = this.form.querySelector(
                `[name="${behavior.target}"], #${behavior.target}`,
            );
            const targetContainer =
                targetElement?.closest('.form-field') || targetElement?.parentElement;

            if (targetElement && targetContainer) {
                const shouldShow = behavior.condition(triggerValue, triggerElement);

                if (shouldShow) {
                    targetContainer.classList.remove('hidden');
                    targetElement.setAttribute('required', 'required');
                } else {
                    targetContainer.classList.add('hidden');
                    targetElement.removeAttribute('required');
                    targetElement.value = ''; // Clear value when hidden
                }
            }
        });
    }

    /**
     * Setup common event listeners
     */
    setupEventListeners() {
        // Auto-slug generation
        this.setupSlugGeneration();

        // Form submission validation
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.showValidationErrors();
            }
        });
    }

    /**
     * Setup slug generation from name field
     */
    setupSlugGeneration() {
        const nameField = this.form.querySelector('[name="name"], #name');
        const slugField = this.form.querySelector('[name="slug"], #slug');

        if (nameField && slugField) {
            nameField.addEventListener('input', () => {
                if (!slugField.value) {
                    slugField.value = this.generateSlug(nameField.value);
                    this.checkSlugUniqueness(slugField.value);
                }
            });

            slugField.addEventListener('input', () => {
                this.debounce(() => {
                    this.checkSlugUniqueness(slugField.value);
                }, 500)();
            });
        }
    }

    /**
     * Setup dynamic fields on initialization
     */
    setupDynamicFields() {
        // Initialize all dynamic fields
        this.dynamicFields.forEach((behaviors, triggerField) => {
            this.handleDynamicField(triggerField);
        });
    }

    /**
     * Generate slug from text
     * @param {string} text
     * @returns {string}
     */
    generateSlug(text) {
        return text
            .toLowerCase()
            .replace(/ğ/g, 'g')
            .replace(/ü/g, 'u')
            .replace(/ş/g, 's')
            .replace(/ı/g, 'i')
            .replace(/ö/g, 'o')
            .replace(/ç/g, 'c')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    /**
     * Check slug uniqueness via API
     * @param {string} slug
     */
    async checkSlugUniqueness(slug) {
        const slugField = this.form.querySelector('[name="slug"], #slug');
        const feedback = document.getElementById('slug-feedback');

        if (!slug || !feedback) return;

        feedback.textContent = 'Kontrol ediliyor...';
        feedback.className = 'mt-1 text-xs text-gray-500';

        try {
            const checkUrl =
                this.form.dataset.slugCheckUrl ||
                `/admin/ozellikler/features/slug/check?slug=${encodeURIComponent(slug)}`;

            const response = await fetch(checkUrl);
            const data = await response.json();

            if (data.unique) {
                feedback.textContent = 'Uygun ✓';
                feedback.className = 'mt-1 text-xs text-green-600';
                slugField.classList.remove('border-red-500');
                slugField.classList.add('border-green-500');
            } else {
                feedback.textContent = 'Bu slug kullanımda ✗';
                feedback.className = 'mt-1 text-xs text-red-600';
                slugField.classList.remove('border-green-500');
                slugField.classList.add('border-red-500');
            }
        } catch (error) {
            feedback.textContent = 'Kontrol edilemedi';
            feedback.className = 'mt-1 text-xs text-red-600';
        }
    }

    /**
     * Validate entire form
     * @returns {boolean}
     */
    validateForm() {
        let isValid = true;

        // HTML5 validation first
        if (!this.form.checkValidity()) {
            isValid = false;
        }

        // Custom validations
        this.rules.forEach((rules, fieldName) => {
            const field = this.form.querySelector(`[name="${fieldName}"], #${fieldName}`);
            if (field && !this.validateField(field, rules)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Validate individual field
     * @param {HTMLElement} field
     * @param {object} rules
     * @returns {boolean}
     */
    validateField(field, rules) {
        let isValid = true;

        // Required validation
        if (rules.required && !field.value.trim()) {
            this.showFieldError(field, 'Bu alan zorunludur');
            isValid = false;
        }

        // Custom validator function
        if (rules.validator && typeof rules.validator === 'function') {
            const result = rules.validator(field.value);
            if (result !== true) {
                this.showFieldError(field, result);
                isValid = false;
            }
        }

        return isValid;
    }

    /**
     * Show field error
     * @param {HTMLElement} field
     * @param {string} message
     */
    showFieldError(field, message) {
        // Remove existing error
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        // Add new error
        const errorDiv = document.createElement('div');
        const errId = `${field.id || field.name}-error`;
        errorDiv.id = errId;
        errorDiv.className = 'field-error mt-1 text-sm text-red-600';
        errorDiv.setAttribute('role', 'status');
        errorDiv.setAttribute('aria-live', 'polite');
        errorDiv.textContent = message;
        field.parentElement.appendChild(errorDiv);

        // Add error styling to field
        field.classList.add('border-red-500', 'focus:ring-red-500');
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', errId);
    }

    /**
     * Clear field error
     * @param {HTMLElement} field
     */
    clearFieldError(field) {
        const existingError = field.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }

        field.classList.remove('border-red-500', 'focus:ring-red-500');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
    }

    /**
     * Show validation errors summary
     */
    showValidationErrors() {
        // Scroll to first error
        const firstError = this.form.querySelector('.field-error');
        if (firstError) {
            try {
                const reduce =
                    window.matchMedia &&
                    window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                firstError.scrollIntoView({
                    behavior: reduce ? 'auto' : 'smooth',
                    block: 'center',
                });
            } catch {
                firstError.scrollIntoView();
            }
            const field = this.form.querySelector('[aria-invalid="true"]');
            if (field && typeof field.focus === 'function') field.focus();
        }
    }

    /**
     * Debounce function
     * @param {function} func
     * @param {number} wait
     * @returns {function}
     */
    // ✅ DUPLICATE REMOVED: debounce global.js'de tanımlı
    debounce(func, wait) {
        // Global debounce kullan, yoksa fallback
        if (window.debounce) {
            return window.debounce(func, wait);
        }
        // Fallback implementation
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
}

/**
 * Common form configurations
 */
export const FormConfigs = {
    // Feature form configuration
    feature: {
        selector: 'form[action*="features"]',
        rules: {
            name: { required: true },
            type: { required: true },
            category_id: { required: true },
        },
        dynamicFields: [
            {
                trigger: 'type',
                target: 'options',
                condition: (value) => value === 'select',
            },
        ],
    },

    // Category form configuration
    category: {
        selector: 'form[action*="kategoriler"]',
        rules: {
            name: { required: true },
            slug: {
                validator: (value) => {
                    if (value && value.length < 3) {
                        return 'Slug en az 3 karakter olmalı';
                    }
                    return true;
                },
            },
        },
    },
};

/**
 * Auto-initialize form validator based on current page
 */
document.addEventListener('DOMContentLoaded', () => {
    // Try to initialize based on URL or form detection
    const currentUrl = window.location.pathname;

    // ✅ FIX: Exclude config-options and features-management pages (no form needed)
    const excludedPaths = ['config-options', 'features-management', 'passive'];
    const shouldExclude = excludedPaths.some(path => currentUrl.includes(path));

    if (currentUrl.includes('features') && !shouldExclude) {
        const formElement = document.querySelector(FormConfigs.feature.selector);
        if (formElement) {
            try {
                const validator = new FormValidator(FormConfigs.feature.selector);

                // Add rules
                Object.entries(FormConfigs.feature.rules).forEach(([field, rules]) => {
                    validator.addRule(field, rules);
                });

                // Add dynamic fields (only if validator has form)
                if (validator.form) {
                    FormConfigs.feature.dynamicFields?.forEach((config) => {
                        validator.addDynamicField(config.trigger, config.target, config.condition);
                    });
                }
            } catch (error) {
                console.warn('FormValidator: Error initializing', error);
            }
        } else {
            console.log('FormValidator: Form not found, skipping initialization');
        }
    }
});

export { FormValidator };
