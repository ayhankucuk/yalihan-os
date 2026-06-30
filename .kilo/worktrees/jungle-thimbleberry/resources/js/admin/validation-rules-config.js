/**
 * Validation Rules Configuration - Merkezi Validation Rules Yönetimi (JavaScript)
 *
 * Context7 Standard: C7-VALIDATION-RULES-CONFIG-JS-2025-12-06
 *
 * Frontend'de validation rules ve hints için JavaScript config.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

/* global window */

// Prevent multiple declarations
if (typeof window.ValidationRulesConfig === 'undefined') {
    window.ValidationRulesConfig = {
        /**
         * Validation rules (Backend'den sync edilir)
         */
        rules: {},

        /**
         * Validation hints (Backend'den sync edilir)
         */
        hints: {},

        /**
         * Validation rules'ı set et
         *
         * @param {Object} rules Validation rules
         */
        setRules(rules) {
            this.rules = rules;
        },

        /**
         * Validation hints'ı set et
         *
         * @param {Object} hints Validation hints
         */
        setHints(hints) {
            this.hints = hints;
        },

        /**
         * Validation rules'ı al
         *
         * @param {string} path Dot notation path (örn: 'user.store')
         * @returns {Object} Validation rules
         */
        get(path) {
            const parts = path.split('.');
            let rules = this.rules;

            for (const part of parts) {
                if (rules[part] === undefined) {
                    return {};
                }
                rules = rules[part];
            }

            return rules || {};
        },

        /**
         * Validation hints'ı al
         *
         * @param {string} fieldName Field ismi
         * @returns {Object|null} Validation hints
         */
        getHints(fieldName) {
            return this.hints[fieldName] || null;
        },

        /**
         * Field için validation hint'leri uygula
         *
         * @param {HTMLElement} field Input field
         * @param {string} fieldName Field ismi
         */
        applyHints(field, fieldName) {
            const hints = this.getHints(fieldName);

            if (!hints) {
                return;
            }

            // Type
            if (hints.type) {
                field.type = hints.type;
            }

            // Placeholder
            if (hints.placeholder) {
                field.placeholder = hints.placeholder;
            }

            // Required
            if (hints.required) {
                field.required = true;
            }

            // Min/Max
            if (hints.min !== undefined) {
                field.min = hints.min;
            }

            if (hints.max !== undefined) {
                field.max = hints.max;
            }

            // MinLength/MaxLength
            if (hints.minLength !== undefined) {
                field.minLength = hints.minLength;
            }

            if (hints.maxLength !== undefined) {
                field.maxLength = hints.maxLength;
            }

            // Pattern
            if (hints.pattern) {
                field.pattern = hints.pattern;
            }

            // Step
            if (hints.step !== undefined) {
                field.step = hints.step;
            }

            // Title (validation message)
            if (hints.message) {
                field.title = hints.message;
            }
        },

        /**
         * Form için validation hints'leri uygula
         *
         * @param {HTMLElement} form Form element
         * @param {string} rulesPath Validation rules path (örn: 'user.store')
         */
        applyFormHints(form, rulesPath) {
            const rules = this.get(rulesPath);

            Object.keys(rules).forEach((fieldName) => {
                const field = form.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    this.applyHints(field, fieldName);
                }
            });
        },
    };

    // Backend'den validation rules'ları yükle
    if (window.validationRules) {
        window.ValidationRulesConfig.setRules(window.validationRules.rules || {});
        window.ValidationRulesConfig.setHints(window.validationRules.hints || {});
    }
}
