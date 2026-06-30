/**
 * Wizard Validation - Form Validation Engine
 *
 * @module wizard/core/wizard-validation
 * @version 3.0.0
 * @since 2026-01-28
 * @context7 Modüler Wizard Mimarisi - SEALED
 *
 * Template-based validation with SSOT rules from backend.
 */

import { WizardEventBus, WizardEventTypes } from './wizard-events.js';
import { WizardState } from './wizard-state.js';

/**
 * Built-in validation rules
 */
const BUILT_IN_RULES = {
    required: (value) => {
        if (value === null || value === undefined) return false;
        if (typeof value === 'string') return value.trim().length > 0;
        if (Array.isArray(value)) return value.length > 0;
        return true;
    },

    numeric: (value) => {
        if (!value) return true; // Skip if empty (use required for that)
        const num = String(value).replace(/\./g, '').replace(',', '.');
        return !isNaN(parseFloat(num));
    },

    min: (value, min) => {
        if (!value) return true;
        const num = parseFloat(String(value).replace(/\./g, '').replace(',', '.'));
        return !isNaN(num) && num >= min;
    },

    max: (value, max) => {
        if (!value) return true;
        const num = parseFloat(String(value).replace(/\./g, '').replace(',', '.'));
        return !isNaN(num) && num <= max;
    },

    minLength: (value, min) => {
        if (!value) return true;
        return String(value).length >= min;
    },

    maxLength: (value, max) => {
        if (!value) return true;
        return String(value).length <= max;
    },

    email: (value) => {
        if (!value) return true;
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    },

    phone: (value) => {
        if (!value) return true;
        const cleaned = String(value).replace(/[\s\-\(\)]/g, '');
        return /^(\+90|0)?[5][0-9]{9}$/.test(cleaned);
    },

    url: (value) => {
        if (!value) return true;
        try {
            new URL(value);
            return true;
        } catch {
            return false;
        }
    },

    // Context7: Coordinate validation
    latitude: (value) => {
        if (!value) return true;
        const lat = parseFloat(value);
        return !isNaN(lat) && lat >= -90 && lat <= 90;
    },

    longitude: (value) => {
        if (!value) return true;
        const lng = parseFloat(value);
        return !isNaN(lng) && lng >= -180 && lng <= 180;
    },
};

/**
 * Default error messages (Turkish)
 */
const DEFAULT_MESSAGES = {
    required: 'Bu alan zorunludur',
    numeric: 'Sayısal bir değer giriniz',
    min: 'Minimum {min} olmalıdır',
    max: 'Maksimum {max} olmalıdır',
    minLength: 'En az {min} karakter giriniz',
    maxLength: 'En fazla {max} karakter giriniz',
    email: 'Geçerli bir e-posta adresi giriniz',
    phone: 'Geçerli bir telefon numarası giriniz',
    url: 'Geçerli bir URL giriniz',
    latitude: 'Geçerli bir enlem değeri giriniz (-90 ile 90 arası)',
    longitude: 'Geçerli bir boylam değeri giriniz (-180 ile 180 arası)',
};

/**
 * Step field definitions
 * Context7: Step numbering - 1:Kategori, 2:Bilgiler, 3:Fotoğraf, 4:Konum, 5:Fiyat, 6:Önizleme
 */
const STEP_FIELDS = {
    1: ['ana_kategori_id', 'alt_kategori_id', 'junction_id'],
    2: ['baslik', 'aciklama'], // Dynamic fields added from template
    3: ['fotograflar'],
    4: ['il_id', 'ilce_id', 'mahalle_id', 'lat', 'lng'], // Context7: lat/lng canonical
    5: ['fiyat', 'para_birimi'],
    6: [], // Preview - no validation
};

/**
 * WizardValidation - Validation Engine
 */
class WizardValidationClass {
    constructor() {
        /** @private */
        this._rules = { ...BUILT_IN_RULES };

        /** @private */
        this._messages = { ...DEFAULT_MESSAGES };

        /** @private */
        this._templateRules = {};

        /** @private */
        this._templateMessages = {};

        /** @private */
        this._stepFields = { ...STEP_FIELDS };
    }

    /**
     * Add custom validation rule
     * @param {string} name - Rule name
     * @param {Function} validator - Validation function
     * @param {string} message - Error message
     */
    addRule(name, validator, message) {
        this._rules[name] = validator;
        if (message) {
            this._messages[name] = message;
        }
    }

    /**
     * Set template validation rules from backend
     * @param {Object} rules - Field rules from template
     * @param {Object} messages - Custom messages
     */
    setTemplateRules(rules, messages = {}) {
        this._templateRules = rules || {};
        this._templateMessages = messages || {};
    }

    /**
     * Update step fields based on template
     * @param {number} step
     * @param {Array} fields
     */
    setStepFields(step, fields) {
        this._stepFields[step] = fields;
    }

    /**
     * Get fields for a step
     * @param {number} step
     * @returns {Array}
     */
    getStepFields(step) {
        return this._stepFields[step] || [];
    }

    /**
     * Parse rule string (e.g., "required|min:3|max:100")
     * @private
     */
    _parseRuleString(ruleString) {
        if (!ruleString) return [];

        return ruleString.split('|').map((rule) => {
            const [name, param] = rule.split(':');
            return { name, param: param ? parseFloat(param) : null };
        });
    }

    /**
     * Validate a single field
     * @param {string} fieldName
     * @param {*} value
     * @returns {{ valid: boolean, errors: string[] }}
     */
    validateField(fieldName, value) {
        const errors = [];

        // Get rule string from template or default
        const ruleString = this._templateRules[fieldName] || '';
        const rules = this._parseRuleString(ruleString);

        // Check HTML required attribute
        const element = document.querySelector(`[name="${fieldName}"]`);
        if (element?.hasAttribute('required') && !rules.some((r) => r.name === 'required')) {
            rules.unshift({ name: 'required', param: null });
        }

        for (const rule of rules) {
            const validator = this._rules[rule.name];
            if (!validator) continue;

            const isValid = rule.param !== null ? validator(value, rule.param) : validator(value);

            if (!isValid) {
                // Get custom message
                let message =
                    this._templateMessages[`${fieldName}.${rule.name}`] ||
                    this._messages[rule.name] ||
                    'Geçersiz değer';

                // Replace placeholders
                if (rule.param !== null) {
                    message = message.replace(`{${rule.name}}`, rule.param);
                    message = message.replace('{min}', rule.param);
                    message = message.replace('{max}', rule.param);
                }

                errors.push(message);
            }
        }

        return { valid: errors.length === 0, errors };
    }

    /**
     * Validate a step
     * @param {number} step
     * @returns {{ valid: boolean, errors: Object }}
     */
    validateStep(step) {
        const fields = this._stepFields[step] || [];
        const form = document.getElementById('ilan-wizard-form');
        const allErrors = {};
        let isValid = true;

        fields.forEach((fieldName) => {
            const element = form?.querySelector(`[name="${fieldName}"]`);
            if (!element) return;

            let value;
            if (element.type === 'checkbox') {
                value = element.checked;
            } else if (element.type === 'file') {
                value = element.files?.length > 0 ? element.files : null;
            } else {
                value = element.value;
            }

            const result = this.validateField(fieldName, value);

            if (!result.valid) {
                isValid = false;
                allErrors[fieldName] = result.errors;
                this.showFieldError(element, result.errors[0]);
            } else {
                this.hideFieldError(element);
            }
        });

        // Update state
        WizardState.set('errors', allErrors);

        // Emit event
        WizardEventBus.emit(WizardEventTypes.STEP_VALIDATED, {
            step,
            valid: isValid,
            errors: allErrors,
        });

        return { valid: isValid, errors: allErrors };
    }

    /**
     * Validate entire form
     * @returns {{ valid: boolean, errors: Object, invalidSteps: number[] }}
     */
    validateForm() {
        const allErrors = {};
        const invalidSteps = [];

        for (let step = 1; step <= WizardState.state.totalSteps; step++) {
            const result = this.validateStep(step);
            if (!result.valid) {
                invalidSteps.push(step);
                Object.assign(allErrors, result.errors);
            }
        }

        WizardEventBus.emit(WizardEventTypes.FORM_VALIDATED, {
            valid: invalidSteps.length === 0,
            errors: allErrors,
            invalidSteps,
        });

        return {
            valid: invalidSteps.length === 0,
            errors: allErrors,
            invalidSteps,
        };
    }

    /**
     * Show field error in UI
     * @param {HTMLElement} element
     * @param {string} message
     */
    showFieldError(element, message) {
        if (!element) return;

        // Add error styling
        element.classList.add('border-red-500', 'dark:border-red-500');
        element.classList.remove('border-gray-300', 'dark:border-gray-600');

        // Find or create error message element
        const parent = element.closest('.form-group') || element.parentElement;
        let errorEl = parent?.querySelector('.field-error');

        if (!errorEl) {
            errorEl = document.createElement('p');
            errorEl.className = 'field-error text-sm text-red-500 mt-1';
            parent?.appendChild(errorEl);
        }

        errorEl.textContent = message;
    }

    /**
     * Hide field error
     * @param {HTMLElement} element
     */
    hideFieldError(element) {
        if (!element) return;

        // Remove error styling
        element.classList.remove('border-red-500', 'dark:border-red-500');
        element.classList.add('border-gray-300', 'dark:border-gray-600');

        // Remove error message
        const parent = element.closest('.form-group') || element.parentElement;
        const errorEl = parent?.querySelector('.field-error');
        if (errorEl) {
            errorEl.remove();
        }
    }

    /**
     * Clear all errors
     */
    clearAllErrors() {
        document.querySelectorAll('.field-error').forEach((el) => el.remove());
        document.querySelectorAll('.border-red-500').forEach((el) => {
            el.classList.remove('border-red-500', 'dark:border-red-500');
            el.classList.add('border-gray-300', 'dark:border-gray-600');
        });
        WizardState.set('errors', {});
    }
}

// Singleton instance
export const WizardValidation = new WizardValidationClass();

// Global export
if (typeof window !== 'undefined') {
    window.YalihanWizard = window.YalihanWizard || {};
    window.YalihanWizard.validation = WizardValidation;
}

export default WizardValidation;
