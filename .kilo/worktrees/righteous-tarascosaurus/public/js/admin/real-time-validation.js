/**
 * Real-time Form Validation System
 * İlan ekleme formunda anlık validation feedback sistemi
 * Context7 compliant - Field-by-field validation
 */

class RealTimeValidator {
    constructor() {
        this.validators = new Map();
        this.errors = new Map();
        this.isValid = false;
        this.init();
    }

    init() {
        this.setupValidators();
        this.bindEvents();
        console.log('Real-time validation initialized');
    }

    setupValidators() {
        // Fiyat validasyonu
        this.validators.set('fiyat', {
            rules: [
                { type: 'required', message: 'Fiyat zorunludur' },
                { type: 'numeric', message: 'Sadece sayı girebilirsiniz' },
                {
                    type: 'min',
                    value: 1000,
                    message: 'Minimum fiyat 1.000 TL olmalıdır',
                },
                {
                    type: 'max',
                    value: 100000000,
                    message: 'Maksimum fiyat 100.000.000 TL olmalıdır',
                },
            ],
        });

        // Başlık validasyonu
        this.validators.set('baslik', {
            rules: [
                { type: 'required', message: 'Başlık zorunludur' },
                {
                    type: 'minlength',
                    value: 10,
                    message: 'Başlık en az 10 karakter olmalıdır',
                },
                {
                    type: 'maxlength',
                    value: 100,
                    message: 'Başlık en fazla 100 karakter olabilir',
                },
            ],
        });

        // Alan (m²) validasyonu
        this.validators.set('alan_m2', {
            rules: [
                { type: 'required', message: 'Alan bilgisi zorunludur' },
                { type: 'numeric', message: 'Sadece sayı girebilirsiniz' },
                { type: 'min', value: 1, message: 'Alan en az 1 m² olmalıdır' },
                {
                    type: 'max',
                    value: 100000,
                    message: 'Alan en fazla 100.000 m² olabilir',
                },
            ],
        });

        // Açıklama validasyonu
        this.validators.set('aciklama', {
            rules: [
                { type: 'required', message: 'Açıklama zorunludur' },
                {
                    type: 'minlength',
                    value: 50,
                    message: 'Açıklama en az 50 karakter olmalıdır',
                },
                {
                    type: 'maxlength',
                    value: 2000,
                    message: 'Açıklama en fazla 2000 karakter olabilir',
                },
            ],
        });

        // Telefon validasyonu
        this.validators.set('telefon', {
            rules: [
                { type: 'required', message: 'Telefon zorunludur' },
                {
                    type: 'phone',
                    message: 'Geçerli bir telefon numarası giriniz',
                },
            ],
        });

        // Email validasyonu
        this.validators.set('email', {
            rules: [{ type: 'email', message: 'Geçerli bir email adresi giriniz' }],
        });
    }

    bindEvents() {
        // Tüm input alanlarını dinle
        const inputs = document.querySelectorAll('input, textarea, select');

        inputs.forEach((input) => {
            // Blur event - field terk edildiğinde
            input.addEventListener('blur', (e) => {
                this.validateField(e.target);
            });

            // Input event - typing sırasında (debounced)
            let timeout;
            input.addEventListener('input', (e) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this.validateField(e.target);
                }, 500); // 500ms debounce
            });

            // Change event - value değişikliğinde
            input.addEventListener('change', (e) => {
                this.validateField(e.target);
            });
        });

        // Form submit event
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!this.validateAll()) {
                    e.preventDefault();
                    this.showFormErrors();
                }
            });
        }
    }

    validateField(field) {
        const fieldName = field.name || field.id;
        const value = field.value.trim();
        const validator = this.validators.get(fieldName);

        if (!validator) return true;

        const errors = [];

        // Her rule'u kontrol et
        validator.rules.forEach((rule) => {
            const isValid = this.checkRule(value, rule);
            if (!isValid) {
                errors.push(rule.message);
            }
        });

        // Hata durumunu güncelle
        if (errors.length > 0) {
            this.errors.set(fieldName, errors);
            this.showFieldError(field, errors[0]);
            return false;
        } else {
            this.errors.delete(fieldName);
            this.showFieldSuccess(field);
            return true;
        }
    }

    checkRule(value, rule) {
        switch (rule.type) {
            case 'required':
                return value.length > 0;

            case 'numeric':
                return !isNaN(value) && !isNaN(parseFloat(value));

            case 'min':
                const numValue = parseFloat(value);
                return !isNaN(numValue) && numValue >= rule.value;

            case 'max':
                const maxValue = parseFloat(value);
                return !isNaN(maxValue) && maxValue <= rule.value;

            case 'minlength':
                return value.length >= rule.value;

            case 'maxlength':
                return value.length <= rule.value;

            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return value === '' || emailRegex.test(value);

            case 'phone':
                const phoneRegex = /^[0-9+\-\s\(\)]{10,}$/;
                return phoneRegex.test(value);

            default:
                return true;
        }
    }

    showFieldError(field, message) {
        // Input'u error state'e çevir
        field.classList.remove('border-green-500', 'border-gray-300');
        field.classList.add('border-red-500');

        // Error message göster
        this.removeFieldMessage(field);

        const errorDiv = document.createElement('div');
        errorDiv.className = 'validation-error mt-1 text-sm text-red-600 dark:text-red-400';
        errorDiv.innerHTML = `<i class="fas fa-exclamation-circle mr-1"></i>${message}`;

        // Check if parentNode exists before appending
        if (field.parentNode) {
            field.parentNode.appendChild(errorDiv);
        } else {
            console.warn('Field parentNode is null, cannot append error message');
        }

        // Error icon ekle
        this.addFieldIcon(field, 'error');
    }

    showFieldSuccess(field) {
        // Input'u success state'e çevir
        field.classList.remove('border-red-500', 'border-gray-300');
        field.classList.add('border-green-500');

        // Error message'ı kaldır
        this.removeFieldMessage(field);

        // Success icon ekle
        this.addFieldIcon(field, 'success');
    }

    removeFieldMessage(field) {
        if (!field.parentNode) return;

        const existing = field.parentNode.querySelector('.validation-error, .validation-success');
        if (existing) {
            existing.remove();
        }
    }

    addFieldIcon(field, type) {
        // Check if parentNode exists
        if (!field.parentNode) {
            console.warn('Field parentNode is null, cannot add validation icon');
            return;
        }

        // Mevcut icon'u kaldır
        const existingIcon = field.parentNode.querySelector('.validation-icon');
        if (existingIcon) {
            existingIcon.remove();
        }

        // Field'in relative positioning'i olmalı
        if (!field.parentNode.style.position) {
            field.parentNode.style.position = 'relative';
        }

        const icon = document.createElement('i');
        icon.className = `validation-icon fas ${
            type === 'error' ? 'fa-times-circle text-red-500' : 'fa-check-circle text-green-500'
        } absolute right-3 top-1/2 transform -translate-y-1/2`;

        field.parentNode.appendChild(icon);
    }

    validateAll() {
        const inputs = document.querySelectorAll('input, textarea, select');
        let allValid = true;

        inputs.forEach((input) => {
            const isValid = this.validateField(input);
            if (!isValid) {
                allValid = false;
            }
        });

        this.isValid = allValid;
        this.updateFormProgress();

        return allValid;
    }

    showFormErrors() {
        // Form üstünde genel hata mesajı göster
        const form = document.querySelector('form');
        if (form && this.errors.size > 0) {
            const errorCount = this.errors.size;
            this.showToast(`${errorCount} alanda hata var. Lütfen düzeltin.`, 'error');

            // İlk hatalı field'e scroll
            const firstErrorField = document.querySelector('.border-red-500');
            if (firstErrorField) {
                firstErrorField.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                });
                firstErrorField.focus();
            }
        }
    }

    updateFormProgress() {
        // Form completion progress bar güncelle
        const totalFields = document.querySelectorAll('input, textarea, select').length;
        const validFields = totalFields - this.errors.size;
        const percentage = Math.round((validFields / totalFields) * 100);

        const progressBar = document.querySelector('.progress-fill');
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
        }

        const progressText = document.querySelector('.progress-text');
        if (progressText) {
            progressText.textContent = `Form %${percentage} tamamlandı`;
        }

        // Progress color
        if (progressBar) {
            if (percentage < 50) {
                progressBar.className = 'progress-fill bg-red-500';
            } else if (percentage < 80) {
                progressBar.className = 'progress-fill bg-yellow-500';
            } else {
                progressBar.className = 'progress-fill bg-green-500';
            }
        }
    }

    showToast(message, type = 'info') {
        // Toast notification göster
        if (window.showNotification) {
            window.showNotification(message, type);
        } else {
            console.log(`${type.toUpperCase()}: ${message}`);
        }
    }

    // Programmatic validation
    isFieldValid(fieldName) {
        return !this.errors.has(fieldName);
    }

    getFieldErrors(fieldName) {
        return this.errors.get(fieldName) || [];
    }

    getAllErrors() {
        return Array.from(this.errors.entries());
    }

    clearAllErrors() {
        this.errors.clear();
        document.querySelectorAll('.validation-error').forEach((el) => el.remove());
        document.querySelectorAll('.validation-icon').forEach((el) => el.remove());
        document.querySelectorAll('input, textarea, select').forEach((field) => {
            field.classList.remove('border-red-500', 'border-green-500');
            field.classList.add('border-gray-300');
        });
    }
}

// Initialize real-time validation
let realTimeValidator;

document.addEventListener('DOMContentLoaded', function () {
    realTimeValidator = new RealTimeValidator();
});

// Global export for external access
window.RealTimeValidator = RealTimeValidator;
window.realTimeValidator = realTimeValidator;
