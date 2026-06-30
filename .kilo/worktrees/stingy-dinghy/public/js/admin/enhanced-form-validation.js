/**
 * Enhanced Form Validation System v2.0
 * Real-time client-side validation with server-side integration
 * Neo Design System compatible
 */

class EnhancedFormValidator {
    constructor(formElement, options = {}) {
        this.form = formElement;
        this.options = {
            validateOnInput: true,
            validateOnBlur: true,
            debounceTime: 300,
            showSuccessStates: true,
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            ...options,
        };

        this.validators = new Map();
        this.errors = new Map();
        this.fieldStates = new Map();

        this.init();
    }

    init() {
        this.setupValidationRules();
        this.setupEventListeners();
        this.setupRealTimeValidation();

        console.log('[Enhanced Validator] Initialized for form:', this.form.id);
    }

    setupValidationRules() {
        // İlan sahibi validasyonu
        this.addValidator('ilan_sahibi_id', [
            {
                rule: 'required',
                message: 'İlan sahibi seçimi zorunludur',
            },
        ]);

        // Kategori validasyonları
        this.addValidator('ana_kategori_id', [
            {
                rule: 'required',
                message: 'Ana kategori seçimi zorunludur',
            },
        ]);

        this.addValidator('alt_kategori_id', [
            {
                rule: 'required',
                message: 'Alt kategori seçimi zorunludur',
            },
        ]);

        this.addValidator('yayin_tipi_id', [
            {
                rule: 'required',
                message: 'Yayın tipi seçimi zorunludur',
            },
        ]);

        // Konum validasyonları
        this.addValidator('il_id', [
            {
                rule: 'required',
                message: 'İl seçimi zorunludur',
            },
        ]);

        this.addValidator('ilce_id', [
            {
                rule: 'required',
                message: 'İlçe seçimi zorunludur',
            },
        ]);

        // İlan başlığı validasyonu
        this.addValidator('ilan_basligi', [
            {
                rule: 'required',
                message: 'İlan başlığı zorunludur',
            },
            {
                rule: 'minLength',
                value: 10,
                message: 'İlan başlığı en az 10 karakter olmalıdır',
            },
            {
                rule: 'maxLength',
                value: 200,
                message: 'İlan başlığı en fazla 200 karakter olabilir',
            },
            {
                rule: 'pattern',
                value: /^[a-zA-ZğĞüÜşŞıİöÖçÇ0-9\s\-\.\,\!\?\(\)]+$/,
                message:
                    'İlan başlığında sadece harf, rakam ve temel noktalama işaretleri kullanılabilir',
            },
        ]);

        // Fiyat validasyonu
        this.addValidator('fiyat', [
            {
                rule: 'required',
                message: 'Fiyat bilgisi zorunludur',
            },
            {
                rule: 'numeric',
                message: 'Fiyat sadece sayı olabilir',
            },
            {
                rule: 'min',
                value: 1,
                message: "Fiyat 0'dan büyük olmalıdır",
            },
            {
                rule: 'max',
                value: 999999999,
                message: 'Fiyat çok yüksek',
            },
        ]);

        // Açıklama validasyonu
        this.addValidator('aciklama', [
            {
                rule: 'required',
                message: 'İlan açıklaması zorunludur',
            },
            {
                rule: 'minLength',
                value: 20,
                message: 'İlan açıklaması en az 20 karakter olmalıdır',
            },
            {
                rule: 'maxLength',
                value: 2000,
                message: 'İlan açıklaması en fazla 2000 karakter olabilir',
            },
        ]);

        // Alan validasyonu (arsa için)
        this.addValidator('alan_m2', [
            {
                rule: 'conditional',
                condition: () => {
                    const anaKategori = document.getElementById('ana_kategori_id');
                    return anaKategori && anaKategori.value === '1'; // Arsa kategorisi
                },
                validators: [
                    {
                        rule: 'required',
                        message: 'Arsa alanı zorunludur',
                    },
                    {
                        rule: 'numeric',
                        message: 'Alan sadece sayı olabilir',
                    },
                    {
                        rule: 'min',
                        value: 1,
                        message: "Alan 0'dan büyük olmalıdır",
                    },
                ],
            },
        ]);
    }

    addValidator(fieldName, rules) {
        this.validators.set(fieldName, rules);
    }

    setupEventListeners() {
        const fields = this.form.querySelectorAll('input, select, textarea');

        fields.forEach((field) => {
            if (this.options.validateOnInput) {
                let debounceTimer;
                field.addEventListener('input', (e) => {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => {
                        this.validateField(field.name, field.value, field);
                    }, this.options.debounceTime);
                });
            }

            if (this.options.validateOnBlur) {
                field.addEventListener('blur', (e) => {
                    this.validateField(field.name, field.value, field);
                });
            }

            // Focus event - clear errors
            field.addEventListener('focus', (e) => {
                this.clearFieldError(field.name);
            });
        });
    }

    setupRealTimeValidation() {
        // Form wizard integration
        if (window.ilanFormWizard) {
            const originalValidateStep = window.ilanFormWizard.validateStep;
            window.ilanFormWizard.validateStep = (step) => {
                return this.validateStep(step);
            };
        }
    }

    async validateField(fieldName, value, fieldElement = null) {
        const rules = this.validators.get(fieldName);
        if (!rules) return true;

        const errors = [];

        for (const rule of rules) {
            if (rule.rule === 'conditional') {
                if (rule.condition && rule.condition()) {
                    // Şartlı validasyon - alt kuralları kontrol et
                    for (const subRule of rule.validators) {
                        const result = await this.applyRule(subRule, value, fieldElement);
                        if (!result.valid) {
                            errors.push(result.message);
                            break;
                        }
                    }
                }
            } else {
                const result = await this.applyRule(rule, value, fieldElement);
                if (!result.valid) {
                    errors.push(result.message);
                    break; // İlk hatada dur
                }
            }
        }

        if (errors.length > 0) {
            this.setFieldError(fieldName, errors[0]);
            return false;
        } else {
            this.setFieldSuccess(fieldName);
            return true;
        }
    }

    async applyRule(rule, value, fieldElement) {
        switch (rule.rule) {
            case 'required':
                return {
                    valid: value && value.toString().trim() !== '',
                    message: rule.message,
                };

            case 'minLength':
                return {
                    valid: !value || value.toString().length >= rule.value,
                    message: rule.message,
                };

            case 'maxLength':
                return {
                    valid: !value || value.toString().length <= rule.value,
                    message: rule.message,
                };

            case 'numeric':
                return {
                    valid: !value || (!isNaN(parseFloat(value)) && isFinite(value)),
                    message: rule.message,
                };

            case 'min':
                const numValue = parseFloat(value);
                return {
                    valid: !value || numValue >= rule.value,
                    message: rule.message,
                };

            case 'max':
                const numValueMax = parseFloat(value);
                return {
                    valid: !value || numValueMax <= rule.value,
                    message: rule.message,
                };

            case 'pattern':
                return {
                    valid: !value || rule.value.test(value),
                    message: rule.message,
                };

            case 'server':
                return await this.validateOnServer(rule.endpoint, {
                    value,
                    field: fieldElement?.name,
                });

            default:
                return { valid: true, message: '' };
        }
    }

    async validateOnServer(endpoint, data) {
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.options.csrfToken,
                },
                body: JSON.stringify(data),
            });

            const result = await response.json();
            return {
                valid: result.valid,
                message: result.message || 'Server validation failed',
            };
        } catch (error) {
            console.error('Server validation error:', error);
            return { valid: true, message: '' }; // Fail gracefully
        }
    }

    validateStep(step) {
        const stepFields = this.getStepFields(step);
        let allValid = true;

        stepFields.forEach(async (fieldName) => {
            const field = this.form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                const isValid = await this.validateField(fieldName, field.value, field);
                if (!isValid) allValid = false;
            }
        });

        return allValid;
    }

    getStepFields(step) {
        const stepFieldMap = {
            1: ['ilan_sahibi_id', 'danisman_id'],
            2: ['ana_kategori_id', 'alt_kategori_id', 'yayin_tipi_id'],
            3: ['il_id', 'ilce_id', 'mahalle_id', 'adres'],
            4: [], // Dinamik özellikler
            5: ['ilan_basligi', 'fiyat', 'para_birimi', 'aciklama', 'alan_m2'],
            6: ['fotograflar'],
            7: [], // Önizleme
        };

        return stepFieldMap[step] || [];
    }

    setFieldError(fieldName, message) {
        this.errors.set(fieldName, message);
        this.fieldStates.set(fieldName, 'error');
        this.updateFieldUI(fieldName, 'error', message);
    }

    setFieldSuccess(fieldName) {
        this.errors.delete(fieldName);
        this.fieldStates.set(fieldName, 'success');
        if (this.options.showSuccessStates) {
            this.updateFieldUI(fieldName, 'success');
        }
    }

    clearFieldError(fieldName) {
        this.errors.delete(fieldName);
        this.fieldStates.delete(fieldName);
        this.updateFieldUI(fieldName, 'neutral');
    }

    updateFieldUI(fieldName, state, message = '') {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (!field) return;

        const container = field.closest('.neo-form-group') || field.closest('.form-group');
        if (!container) return;

        // Error message element
        let errorElement = container.querySelector('.field-error');
        if (!errorElement) {
            errorElement = document.createElement('div');
            errorElement.className = 'field-error mt-1 text-sm';
            container.appendChild(errorElement);
        }

        // Success icon element
        let successIcon = container.querySelector('.field-success-icon');
        if (!successIcon) {
            successIcon = document.createElement('div');
            successIcon.className =
                'field-success-icon absolute right-3 top-1/2 transform -translate-y-1/2';
            successIcon.innerHTML =
                '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
            const inputWrapper = container.querySelector('.relative') || container;
            if (inputWrapper.classList.contains('relative')) {
                inputWrapper.appendChild(successIcon);
            } else {
                inputWrapper.classList.add('relative');
                inputWrapper.appendChild(successIcon);
            }
        }

        // Clear all states
        container.classList.remove('neo-field-error', 'neo-field-success', 'neo-field-neutral');
        field.classList.remove('border-red-500', 'border-green-500', 'border-gray-300');
        errorElement.style.display = 'none';
        successIcon.style.display = 'none';

        // Apply new state
        switch (state) {
            case 'error':
                container.classList.add('neo-field-error');
                field.classList.add('border-red-500');
                errorElement.textContent = message;
                errorElement.className = 'field-error mt-1 text-sm text-red-600';
                errorElement.style.display = 'block';

                // Animate attention
                this.flashAttention(container, 'error');
                break;

            case 'success':
                container.classList.add('neo-field-success');
                field.classList.add('border-green-500');
                successIcon.style.display = 'block';

                // Animate success
                this.flashAttention(successIcon, 'success');
                break;

            case 'neutral':
            default:
                container.classList.add('neo-field-neutral');
                field.classList.add('border-gray-300');
                break;
        }
    }

    flashAttention(element, type) {
        const originalTransform = element.style.transform;

        if (type === 'error') {
            element.style.animation = 'shake 0.5s ease-in-out';
        } else if (type === 'success') {
            element.style.animation = 'bounce 0.6s ease-in-out';
        }

        setTimeout(() => {
            element.style.animation = '';
            element.style.transform = originalTransform;
        }, 600);
    }

    // Public API
    validateAllFields() {
        const fields = this.form.querySelectorAll('input, select, textarea');
        let allValid = true;

        fields.forEach(async (field) => {
            if (field.name && this.validators.has(field.name)) {
                const isValid = await this.validateField(field.name, field.value, field);
                if (!isValid) allValid = false;
            }
        });

        return allValid;
    }

    getErrors() {
        return Object.fromEntries(this.errors);
    }

    hasErrors() {
        return this.errors.size > 0;
    }

    clearAllErrors() {
        this.errors.clear();
        this.fieldStates.clear();

        const containers = this.form.querySelectorAll('.neo-field-error, .neo-field-success');
        containers.forEach((container) => {
            this.updateFieldUI(container.querySelector('input, select, textarea')?.name, 'neutral');
        });
    }
}

// CSS Animations
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    .neo-field-error {
        animation: fieldErrorPulse 0.3s ease-in-out;
    }

    .neo-field-success {
        animation: fieldSuccessFade 0.4s ease-in-out;
    }

    @keyframes fieldErrorPulse {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
        100% { box-shadow: 0 0 0 4px rgba(239, 68, 68, 0); }
    }

    @keyframes fieldSuccessFade {
        0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.4); }
        100% { box-shadow: 0 0 0 4px rgba(34, 197, 94, 0); }
    }

    .field-error {
        animation: fadeInUp 0.3s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;

document.head.appendChild(style);

// Global initialization
window.EnhancedFormValidator = EnhancedFormValidator;

// Auto-initialize for ilan forms
document.addEventListener('DOMContentLoaded', function () {
    const ilanForm = document.getElementById('ilanForm');
    if (ilanForm) {
        window.ilanFormValidator = new EnhancedFormValidator(ilanForm);
        console.log('[Enhanced Validator] Auto-initialized for ilan form');
    }
});
