/**
 * Validation Manager - Centralized validation logic
 * Context7 Standard: C7-VALIDATION-MANAGER-2025-09-27
 * Version: 2.0.0
 */

export class ValidationManager {
    constructor() {
        this.rules = this.initializeValidationRules();
        this.customValidators = new Map();
    }

    /**
     * Initialize validation rules
     */
    initializeValidationRules() {
        return {
            // Step 1: İlan Sahibi
            ilan_sahibi_id: {
                required: true,
                message: 'İlan sahibi seçimi zorunludur',
            },
            danisman_id: {
                required: false,
            },

            // Step 2: Kategori
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

            // Step 3: Konum
            il_id: {
                required: true,
                message: 'İl seçimi zorunludur',
            },
            ilce_id: {
                required: true,
                message: 'İlçe seçimi zorunludur',
            },
            mahalle_id: {
                required: true,
                message: 'Mahalle seçimi zorunludur',
            },
            adres: {
                required: true,
                minLength: 10,
                message: 'Adres en az 10 karakter olmalıdır',
            },

            // Step 4: Özellikler
            ozellikler: {
                required: false,
                type: 'array',
            },

            // Step 5: Temel Bilgiler
            baslik: {
                required: true,
                minLength: 10,
                maxLength: 255,
                message: 'Başlık 10-255 karakter arasında olmalıdır',
            },
            fiyat: {
                required: true,
                type: 'number',
                min: 1,
                message: "Fiyat 0'dan büyük olmalıdır",
            },
            para_birimi: {
                required: true,
                enum: ['TRY', 'USD', 'EUR', 'GBP'],
                message: 'Geçerli bir para birimi seçiniz',
            },
            aciklama: {
                required: true,
                minLength: 20,
                maxLength: 2000,
                message: 'Açıklama 20-2000 karakter arasında olmalıdır',
            },
            net_metrekare: {
                required: false,
                type: 'number',
                min: 1,
                message: "Net metrekare 0'dan büyük olmalıdır",
            },
            brut_metrekare: {
                required: false,
                type: 'number',
                min: 1,
                message: "Brüt metrekare 0'dan büyük olmalıdır",
            },
            oda_sayisi: {
                required: false,
                pattern: /^[0-9]+\+[0-9]+$/,
                message: 'Oda sayısı formatı: 2+1, 3+1 gibi olmalıdır',
            },
            banyo_sayisi: {
                required: false,
                type: 'number',
                min: 0,
                message: 'Banyo sayısı 0 veya pozitif olmalıdır',
            },

            // Step 6: Fotoğraflar
            fotograflar: {
                required: false,
                type: 'array',
                maxItems: 20,
                message: 'En fazla 20 fotoğraf yükleyebilirsiniz',
            },

            // Step 7: Yayın Ayarları
            yayin_durumu: {
                required: true,
                enum: ['taslak', 'yayinda', 'pasif'],
                message: 'Geçerli bir yayın durumu seçiniz',
            },
            is_published: {
                required: false,
                type: 'boolean',
            },
        };
    }

    /**
     * Get validation rules for a field
     */
    getFieldRules(fieldName) {
        return this.rules[fieldName] || {};
    }

    /**
     * Validate a single field
     */
    validateField(fieldName, value, rules = null) {
        const fieldRules = rules || this.getFieldRules(fieldName);

        if (!fieldRules || Object.keys(fieldRules).length === 0) {
            return null; // No validation rules
        }

        // Required validation
        if (fieldRules.required && this.isEmpty(value)) {
            return fieldRules.message || `${fieldName} alanı zorunludur`;
        }

        // Skip other validations if field is empty and not required
        if (this.isEmpty(value) && !fieldRules.required) {
            return null;
        }

        // Type validation
        if (fieldRules.type && !this.validateType(value, fieldRules.type)) {
            return `${fieldName} alanı ${fieldRules.type} tipinde olmalıdır`;
        }

        // String length validation
        if (fieldRules.minLength && value.length < fieldRules.minLength) {
            return (
                fieldRules.message ||
                `${fieldName} en az ${fieldRules.minLength} karakter olmalıdır`
            );
        }

        if (fieldRules.maxLength && value.length > fieldRules.maxLength) {
            return (
                fieldRules.message ||
                `${fieldName} en fazla ${fieldRules.maxLength} karakter olmalıdır`
            );
        }

        // Number validation
        if (fieldRules.min !== undefined && parseFloat(value) < fieldRules.min) {
            return fieldRules.message || `${fieldName} en az ${fieldRules.min} olmalıdır`;
        }

        if (fieldRules.max !== undefined && parseFloat(value) > fieldRules.max) {
            return fieldRules.message || `${fieldName} en fazla ${fieldRules.max} olmalıdır`;
        }

        // Enum validation
        if (fieldRules.enum && !fieldRules.enum.includes(value)) {
            return (
                fieldRules.message ||
                `${fieldName} geçerli değerlerden biri olmalıdır: ${fieldRules.enum.join(', ')}`
            );
        }

        // Pattern validation
        if (fieldRules.pattern && !fieldRules.pattern.test(value)) {
            return fieldRules.message || `${fieldName} formatı geçersizdir`;
        }

        // Array validation
        if (fieldRules.type === 'array') {
            if (!Array.isArray(value)) {
                return `${fieldName} bir liste olmalıdır`;
            }

            if (fieldRules.maxItems && value.length > fieldRules.maxItems) {
                return (
                    fieldRules.message ||
                    `${fieldName} en fazla ${fieldRules.maxItems} öğe içerebilir`
                );
            }
        }

        // Custom validator
        if (this.customValidators.has(fieldName)) {
            const customValidator = this.customValidators.get(fieldName);
            const customError = customValidator(value, fieldRules);
            if (customError) {
                return customError;
            }
        }

        return null; // No errors
    }

    /**
     * Validate multiple fields
     */
    validateFields(fields) {
        const errors = {};

        Object.entries(fields).forEach(([fieldName, value]) => {
            const error = this.validateField(fieldName, value);
            if (error) {
                errors[fieldName] = error;
            }
        });

        return {
            isValid: Object.keys(errors).length === 0,
            errors,
        };
    }

    /**
     * Validate step data
     */
    validateStep(stepNumber, formData) {
        const stepFields = this.getStepFields(stepNumber);
        const stepData = {};

        stepFields.forEach((field) => {
            if (formData.hasOwnProperty(field)) {
                stepData[field] = formData[field];
            }
        });

        return this.validateFields(stepData);
    }

    /**
     * Get fields for a specific step
     */
    getStepFields(stepNumber) {
        const stepFields = {
            1: ['ilan_sahibi_id', 'danisman_id'],
            2: ['ana_kategori_id', 'alt_kategori_id', 'junction_id'],
            3: ['il_id', 'ilce_id', 'mahalle_id', 'adres'],
            4: ['ozellikler'],
            5: [
                'baslik',
                'fiyat',
                'para_birimi',
                'aciklama',
                'net_metrekare',
                'brut_metrekare',
                'oda_sayisi',
                'banyo_sayisi',
            ],
            6: ['fotograflar'],
            7: ['yayin_durumu', 'is_published'],
        };

        return stepFields[stepNumber] || [];
    }

    /**
     * Add custom validator
     */
    addCustomValidator(fieldName, validator) {
        this.customValidators.set(fieldName, validator);
    }

    /**
     * Remove custom validator
     */
    removeCustomValidator(fieldName) {
        this.customValidators.delete(fieldName);
    }

    /**
     * Update validation rules
     */
    updateRules(fieldName, rules) {
        this.rules[fieldName] = { ...this.rules[fieldName], ...rules };
    }

    /**
     * Helper methods
     */
    isEmpty(value) {
        if (value === null || value === undefined) {
            return true;
        }

        if (typeof value === 'string') {
            return value.trim() === '';
        }

        if (Array.isArray(value)) {
            return value.length === 0;
        }

        return false;
    }

    validateType(value, type) {
        switch (type) {
            case 'string':
                return typeof value === 'string';
            case 'number':
                return !isNaN(parseFloat(value)) && isFinite(value);
            case 'boolean':
                return typeof value === 'boolean' || value === 'true' || value === 'false';
            case 'array':
                return Array.isArray(value);
            case 'email':
                return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
            case 'url':
                try {
                    new URL(value);
                    return true;
                } catch {
                    return false;
                }
            default:
                return true;
        }
    }

    /**
     * Get validation summary
     */
    getValidationSummary(formData) {
        const summary = {
            totalFields: 0,
            validFields: 0,
            invalidFields: 0,
            errors: {},
        };

        Object.keys(this.rules).forEach((fieldName) => {
            if (formData.hasOwnProperty(fieldName)) {
                summary.totalFields++;
                const error = this.validateField(fieldName, formData[fieldName]);
                if (error) {
                    summary.invalidFields++;
                    summary.errors[fieldName] = error;
                } else {
                    summary.validFields++;
                }
            }
        });

        summary.isValid = summary.invalidFields === 0;
        summary.completionPercentage =
            summary.totalFields > 0
                ? Math.round((summary.validFields / summary.totalFields) * 100)
                : 0;

        return summary;
    }

    /**
     * Export validation rules
     */
    exportRules() {
        return JSON.stringify(this.rules, null, 2);
    }

    /**
     * Import validation rules
     */
    importRules(rulesJson) {
        try {
            this.rules = JSON.parse(rulesJson);
            return true;
        } catch (error) {
            console.error('Invalid validation rules JSON:', error);
            return false;
        }
    }
}

// Global erişim (Vite ile bundle edilse bile window üzerinden kullanılabilsin)
try {
    if (typeof window !== 'undefined') {
        window.ValidationManager = ValidationManager;
    }
} catch (e) {}
