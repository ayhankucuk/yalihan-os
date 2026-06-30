/**
 * Modern Form Wizard - Context7 Standard
 *
 * 🎯 Hedefler:
 * - Form Completion Rate: 67% → 89%
 * - Time to Complete: 8.5 min → 5.2 min
 * - Error Resolution Time: 45s → 12s
 *
 * @version 1.0.0
 * @author Context7 Team
 */

class ModernFormWizard {
    constructor(options = {}) {
        this.config = {
            totalSteps: 11, // Updated: 12 → 11 (Section 11 removed, consolidated into Section 9)
            currentStep: 1,
            autoSave: true,
            autoSaveInterval: 30000, // 30 seconds
            validationMode: 'real-time', // 'real-time' | 'on-submit'
            animationDuration: 300,
            ...options,
        };

        this.state = {
            formData: new Map(),
            errors: new Map(),
            completedSteps: new Set(),
            isSubmitting: false,
            lastSaved: null,
        };

        this.validators = new Map();
        this.observers = new Map();
        this.autoSaveTimer = null;

        this.init();
    }

    /**
     * Initialize the wizard
     */
    init() {
        this.setupEventListeners();
        this.setupValidators();
        this.setupAutoSave();
        this.render();

        console.log('🚀 Modern Form Wizard initialized');
    }

    /**
     * Setup event listeners for form interactions
     */
    setupEventListeners() {
        // Navigation buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-wizard-action="next"]')) {
                e.preventDefault();
                this.nextStep();
            }
            if (e.target.matches('[data-wizard-action="prev"]')) {
                e.preventDefault();
                this.prevStep();
            }
            if (e.target.matches('[data-wizard-action="submit"]')) {
                e.preventDefault();
                this.submitForm();
            }
        });

        // Real-time validation
        document.addEventListener('input', (e) => {
            if (e.target.matches('[data-wizard-field]')) {
                this.validateField(e.target);
            }
        });

        // Step navigation
        document.addEventListener('click', (e) => {
            if (e.target.matches('[data-wizard-step]')) {
                const step = parseInt(e.target.dataset.wizardStep);
                this.goToStep(step);
            }
        });

        // Auto-save on form changes
        document.addEventListener('change', (e) => {
            if (e.target.matches('[data-wizard-field]')) {
                this.updateFormData();
                this.scheduleAutoSave();
            }
        });
    }

    /**
     * Setup field validators
     */
    setupValidators() {
        // Required field validator
        this.validators.set('required', (value, field) => {
            if (!value || value.toString().trim() === '') {
                return `${field.dataset.label || 'Bu alan'} zorunludur`;
            }
            return null;
        });

        // Email validator
        this.validators.set('email', (value, field) => {
            if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                return 'Geçerli bir e-posta adresi giriniz';
            }
            return null;
        });

        // Phone validator
        this.validators.set('phone', (value, field) => {
            if (value && !/^[\+]?[0-9\s\-\(\)]{10,}$/.test(value)) {
                return 'Geçerli bir telefon numarası giriniz';
            }
            return null;
        });

        // Number validator
        this.validators.set('number', (value, field) => {
            if (value && isNaN(parseFloat(value))) {
                return 'Geçerli bir sayı giriniz';
            }
            return null;
        });

        // Min length validator
        this.validators.set('minLength', (value, field) => {
            const minLength = parseInt(field.dataset.minLength) || 0;
            if (value && value.length < minLength) {
                return `En az ${minLength} karakter olmalıdır`;
            }
            return null;
        });

        // Custom validators
        this.validators.set('price', (value, field) => {
            if (value && (isNaN(parseFloat(value)) || parseFloat(value) <= 0)) {
                return 'Geçerli bir fiyat giriniz';
            }
            return null;
        });

        this.validators.set('area', (value, field) => {
            if (value && (isNaN(parseFloat(value)) || parseFloat(value) <= 0)) {
                return 'Geçerli bir alan giriniz';
            }
            return null;
        });
    }

    /**
     * Setup auto-save functionality
     */
    setupAutoSave() {
        if (this.config.autoSave) {
            this.autoSaveTimer = setInterval(() => {
                this.autoSave();
            }, this.config.autoSaveInterval);
        }
    }

    /**
     * Validate a single field
     */
    async validateField(field) {
        const fieldName = field.name || field.id;
        const value = this.getFieldValue(field);
        const validationRules = field.dataset.validation?.split(' ') || [];

        // Clear existing errors
        this.state.errors.delete(fieldName);
        this.clearFieldError(field);

        // Run validators
        for (const rule of validationRules) {
            const validator = this.validators.get(rule);
            if (validator) {
                const error = await validator(value, field);
                if (error) {
                    this.state.errors.set(fieldName, error);
                    this.showFieldError(field, error);
                    return false;
                }
            }
        }

        // Server-side validation for specific fields
        if (['ilan_basligi', 'fiyat', 'alan_m2'].includes(fieldName)) {
            const isValid = await this.validateFieldOnServer(fieldName, value);
            if (!isValid) {
                return false;
            }
        }

        this.showFieldSuccess(field);
        return true;
    }

    /**
     * Validate field on server
     */
    async validateFieldOnServer(fieldName, value) {
        try {
            const response = await fetch('/admin/validate-field', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content'),
                },
                body: JSON.stringify({
                    field: fieldName,
                    value: value,
                    context: this.getFormContext(),
                }),
            });

            const result = await response.json();
            if (!result.valid) {
                this.state.errors.set(fieldName, result.message);
                return false;
            }
            return true;
        } catch (error) {
            console.warn('Server validation error:', error);
            return true; // Allow client-side validation to proceed
        }
    }

    /**
     * Validate current step
     */
    async validateStep(stepNumber) {
        const stepFields = document.querySelectorAll(
            `[data-wizard-step="${stepNumber}"] [data-wizard-field]`
        );
        let isValid = true;

        for (const field of stepFields) {
            const fieldValid = await this.validateField(field);
            if (!fieldValid) {
                isValid = false;
            }
        }

        return isValid;
    }

    /**
     * Navigate to next step
     */
    async nextStep() {
        const isValid = await this.validateStep(this.config.currentStep);

        if (isValid && this.config.currentStep < this.config.totalSteps) {
            this.config.currentStep++;
            this.state.completedSteps.add(this.config.currentStep - 1);
            this.updateFormData();
            this.render();
            this.showStepTransition('next');
        } else {
            this.showValidationErrors();
        }
    }

    /**
     * Navigate to previous step
     */
    prevStep() {
        if (this.config.currentStep > 1) {
            this.config.currentStep--;
            this.updateFormData();
            this.render();
            this.showStepTransition('prev');
        }
    }

    /**
     * Navigate to specific step
     */
    async goToStep(stepNumber) {
        if (stepNumber < this.config.currentStep || this.state.completedSteps.has(stepNumber - 1)) {
            this.config.currentStep = stepNumber;
            this.updateFormData();
            this.render();
        } else {
            this.showNotification(
                'Bu adıma geçmek için önceki adımları tamamlamalısınız',
                'warning'
            );
        }
    }

    /**
     * Update form data from DOM
     */
    updateFormData() {
        const fields = document.querySelectorAll('[data-wizard-field]');

        fields.forEach((field) => {
            const fieldName = field.name || field.id;
            const value = this.getFieldValue(field);
            this.state.formData.set(fieldName, value);
        });
    }

    /**
     * Get field value based on field type
     */
    getFieldValue(field) {
        switch (field.type) {
            case 'checkbox':
                return field.checked;
            case 'radio':
                return field.checked ? field.value : null;
            case 'file':
                return field.files;
            default:
                return field.value;
        }
    }

    /**
     * Render the wizard
     */
    render() {
        this.renderProgressBar();
        this.renderStepIndicators();
        this.renderStepContent();
        this.renderNavigation();
        this.renderFormSummary();
    }

    /**
     * Render progress bar
     */
    renderProgressBar() {
        const progressBar = document.getElementById('wizard-progress-bar');
        const progressPercent = document.getElementById('wizard-progress-percent');

        if (progressBar) {
            const percent = Math.round((this.config.currentStep / this.config.totalSteps) * 100);
            progressBar.style.width = `${percent}%`;

            if (progressPercent) {
                progressPercent.textContent = `${percent}%`;
            }
        }
    }

    /**
     * Render step indicators
     */
    renderStepIndicators() {
        for (let i = 1; i <= this.config.totalSteps; i++) {
            const indicator = document.getElementById(`wizard-step-${i}`);
            if (indicator) {
                indicator.classList.remove('completed', 'current', 'pending');

                if (i < this.config.currentStep) {
                    indicator.classList.add('completed');
                } else if (i === this.config.currentStep) {
                    indicator.classList.add('current');
                } else {
                    indicator.classList.add('pending');
                }
            }
        }
    }

    /**
     * Render step content
     */
    renderStepContent() {
        // Hide all steps
        for (let i = 1; i <= this.config.totalSteps; i++) {
            const step = document.getElementById(`wizard-step-content-${i}`);
            if (step) {
                step.style.display = 'none';
            }
        }

        // Show current step
        const currentStep = document.getElementById(
            `wizard-step-content-${this.config.currentStep}`
        );
        if (currentStep) {
            currentStep.style.display = 'block';
            this.animateStepIn(currentStep);

            // Dispatch step changed event
            const event = new CustomEvent('wizard-step-changed', {
                detail: { step: this.config.currentStep },
            });
            document.dispatchEvent(event);
        }
    }

    /**
     * Render navigation buttons
     */
    renderNavigation() {
        const prevBtn = document.getElementById('wizard-prev-btn');
        const nextBtn = document.getElementById('wizard-next-btn');
        const submitBtn = document.getElementById('wizard-submit-btn');

        if (prevBtn) {
            prevBtn.style.display = this.config.currentStep > 1 ? 'inline-flex' : 'none';
        }

        if (nextBtn && submitBtn) {
            if (this.config.currentStep === this.config.totalSteps) {
                nextBtn.style.display = 'none';
                submitBtn.style.display = 'inline-flex';
            } else {
                nextBtn.style.display = 'inline-flex';
                submitBtn.style.display = 'none';
            }
        }
    }

    /**
     * Render form summary
     */
    renderFormSummary() {
        const summaryContainer = document.getElementById('wizard-summary');
        if (!summaryContainer) return;

        const summary = this.generateFormSummary();
        summaryContainer.innerHTML = summary;
    }

    /**
     * Generate form summary
     */
    generateFormSummary() {
        const data = Object.fromEntries(this.state.formData);

        return `
            <div class="wizard-summary-content">
                <h3 class="text-lg font-semibold mb-4">📋 Form Özeti</h3>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">İlan Başlığı:</span>
                        <span class="font-medium">${data.ilan_basligi || '-'}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Fiyat:</span>
                        <span class="font-medium">${
                            data.fiyat ? `${data.fiyat} ${data.para_birimi || 'TRY'}` : '-'
                        }</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Kategori:</span>
                        <span class="font-medium">${this.getCategoryText()}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Konum:</span>
                        <span class="font-medium">${this.getLocationText()}</span>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Get category text for summary
     */
    getCategoryText() {
        const anaKategori = document.getElementById('ana_kategori_id');
        const altKategori = document.getElementById('alt_kategori_id');
        const yayinTipi = document.getElementById('yayin_tipi_id');

        if (anaKategori && altKategori && yayinTipi) {
            const anaText = anaKategori.selectedOptions[0]?.textContent || '';
            const altText = altKategori.selectedOptions[0]?.textContent || '';
            const yayinText = yayinTipi.selectedOptions[0]?.textContent || '';

            if (anaText && altText && yayinText) {
                return `${anaText} > ${altText} > ${yayinText}`;
            }
        }
        return '-';
    }

    /**
     * Get location text for summary
     */
    getLocationText() {
        const il = document.getElementById('il_id');
        const ilce = document.getElementById('ilce_id');
        const mahalle = document.getElementById('mahalle_id');

        if (il && ilce) {
            const ilText = il.selectedOptions[0]?.textContent || '';
            const ilceText = ilce.selectedOptions[0]?.textContent || '';
            const mahalleText = mahalle?.selectedOptions[0]?.textContent || '';

            if (ilText && ilceText) {
                return mahalleText
                    ? `${ilText} / ${ilceText} / ${mahalleText}`
                    : `${ilText} / ${ilceText}`;
            }
        }
        return '-';
    }

    /**
     * Show field error
     */
    showFieldError(field, message) {
        this.clearFieldError(field);

        const errorElement = document.createElement('div');
        errorElement.className = 'field-error text-red-600 text-sm mt-1';
        errorElement.textContent = message;

        field.parentNode.appendChild(errorElement);
        field.classList.add('border-red-500', 'focus:border-red-500');
    }

    /**
     * Clear field error
     */
    clearFieldError(field) {
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        field.classList.remove('border-red-500', 'focus:border-red-500');
    }

    /**
     * Show field success
     */
    showFieldSuccess(field) {
        field.classList.add('border-green-500', 'focus:border-green-500');
        setTimeout(() => {
            field.classList.remove('border-green-500', 'focus:border-green-500');
        }, 2000);
    }

    /**
     * Show validation errors
     */
    showValidationErrors() {
        const errorMessages = Array.from(this.state.errors.values());
        if (errorMessages.length > 0) {
            this.showNotification(errorMessages.join('<br>'), 'error');
        }
    }

    /**
     * Show step transition animation
     */
    showStepTransition(direction) {
        const currentStep = document.getElementById(
            `wizard-step-content-${this.config.currentStep}`
        );
        if (currentStep) {
            currentStep.style.transform =
                direction === 'next' ? 'translateX(100%)' : 'translateX(-100%)';
            currentStep.style.opacity = '0';

            setTimeout(() => {
                currentStep.style.transform = 'translateX(0)';
                currentStep.style.opacity = '1';
            }, this.config.animationDuration);
        }
    }

    /**
     * Animate step in
     */
    animateStepIn(step) {
        step.style.transform = 'translateY(20px)';
        step.style.opacity = '0';

        setTimeout(() => {
            step.style.transform = 'translateY(0)';
            step.style.opacity = '1';
        }, 50);
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 ${
            type === 'error'
                ? 'bg-red-500 text-white'
                : type === 'warning'
                  ? 'bg-yellow-500 text-white'
                  : type === 'success'
                    ? 'bg-green-500 text-white'
                    : 'bg-blue-500 text-white'
        }`;
        notification.innerHTML = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 5000);
    }

    /**
     * Auto-save form data
     */
    async autoSave() {
        if (this.state.isSubmitting) return;

        this.updateFormData();
        const formData = Object.fromEntries(this.state.formData);

        try {
            const response = await fetch('/admin/ilanlar/auto-save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content'),
                },
                body: JSON.stringify(formData),
            });

            if (response.ok) {
                this.state.lastSaved = new Date();
                this.showAutoSaveIndicator();
            }
        } catch (error) {
            console.warn('Auto-save failed:', error);
        }
    }

    /**
     * Schedule auto-save
     */
    scheduleAutoSave() {
        if (this.autoSaveTimer) {
            clearTimeout(this.autoSaveTimer);
        }

        this.autoSaveTimer = setTimeout(() => {
            this.autoSave();
        }, 5000); // Save 5 seconds after last change
    }

    /**
     * Show auto-save indicator
     */
    showAutoSaveIndicator() {
        const indicator = document.getElementById('auto-save-indicator');
        if (indicator) {
            indicator.textContent = `Otomatik kaydedildi: ${this.state.lastSaved.toLocaleTimeString()}`;
            indicator.classList.add('text-green-600');

            setTimeout(() => {
                indicator.classList.remove('text-green-600');
            }, 3000);
        }
    }

    /**
     * Submit form
     */
    async submitForm() {
        if (this.state.isSubmitting) return;

        this.state.isSubmitting = true;
        this.updateFormData();

        // Final validation
        const isValid = await this.validateStep(this.config.currentStep);
        if (!isValid) {
            this.showValidationErrors();
            this.state.isSubmitting = false;
            return;
        }

        // Submit form
        const form = document.getElementById('ilanForm');
        if (form) {
            form.submit();
        }
    }

    /**
     * Get form context for server validation
     */
    getFormContext() {
        return Object.fromEntries(this.state.formData);
    }

    /**
     * Destroy wizard instance
     */
    destroy() {
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
        }

        // Remove event listeners
        document.removeEventListener('click', this.handleClick);
        document.removeEventListener('input', this.handleInput);
        document.removeEventListener('change', this.handleChange);

        console.log('🧹 Modern Form Wizard destroyed');
    }
}

// Export for use in other modules
window.ModernFormWizard = ModernFormWizard;

// Auto-initialize if data-wizard attribute is present
document.addEventListener('DOMContentLoaded', () => {
    const wizardElement = document.querySelector('[data-wizard]');
    if (wizardElement) {
        window.formWizard = new ModernFormWizard({
            totalSteps: parseInt(wizardElement.dataset.totalSteps) || 7,
            autoSave: wizardElement.dataset.autoSave !== 'false',
            validationMode: wizardElement.dataset.validationMode || 'real-time',
        });
    }
});
