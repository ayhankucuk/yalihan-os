/**
 * Enhanced Progress Indicator for İlan Ekleme Wizard
 * Context7 Compliant - Detailed step progress with visual indicators
 *
 * Features:
 * - Step-by-step progress visualization
 * - Completion durum per step
 * - Visual step indicators
 * - Step preview on hover
 * - Overall completion percentage
 */

class ProgressIndicator {
    constructor(options = {}) {
        this.config = {
            showStepDetails: true,
            showCompletionStatus: true,
            showStepPreview: true,
            updateInterval: 2000, // 2 seconds
            ...options,
        };

        this.steps = [
            {
                number: 1,
                title: 'Temel Bilgiler',
                description: 'Kategori, başlık, fiyat, lokasyon',
                completed: false,
            },
            {
                number: 2,
                title: 'Detaylar',
                description: 'Kategoriye özel alanlar',
                completed: false,
            },
            {
                number: 3,
                title: 'Ek Bilgiler',
                description: 'Açıklama, fotoğraflar, yayın durumu',
                completed: false,
            },
        ];

        this.init();
    }

    /**
     * Initialize progress indicator
     */
    init() {
        this.renderProgressIndicator();
        this.setupStepListeners();
        this.startAutoUpdate();

        console.log('✅ Enhanced Progress Indicator initialized');
    }

    /**
     * Render progress indicator
     */
    renderProgressIndicator() {
        const progressContainer = document.querySelector('.wizard-progress-container');
        if (!progressContainer) return;

        const progressHtml = `
            <div class="wizard-progress-enhanced space-y-4">
                <!-- Overall Progress Bar -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 dark:bg-slate-900 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Genel İlerleme</span>
                        <span id="overall-progress-percentage" class="text-sm text-gray-500 dark:text-slate-500">%0</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden dark:bg-slate-700">
                        <div id="overall-progress-bar" class="h-3 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full transition-all duration-500" style="width: 0%"></div>
                    </div>
                </div>

                <!-- Step-by-Step Progress -->
                <div class="bg-white rounded-lg border border-gray-200 p-4 dark:bg-slate-900 dark:border-slate-700">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Adım Detayları</h3>
                    <div class="space-y-3" id="step-progress-list">
                        ${this.steps.map((step) => this.renderStepItem(step)).join('')}
                    </div>
                </div>
            </div>
        `;

        progressContainer.innerHTML = progressHtml;
    }

    /**
     * Render step item
     */
    renderStepItem(step) {
        const durumIcon = step.completed ? '✓' : step.current ? '⟳' : '○';
        const durumColor = step.completed
            ? 'text-green-600 dark:text-green-400'
            : step.current
              ? 'text-blue-600 dark:text-blue-400'
              : 'text-gray-400 dark:text-gray-600';

        return `
            <div class="step-item flex items-start gap-3 p-3 rounded-lg border border-gray-200 ${step.current ? dark:border-slate-700"bg-blue-50 dark:bg-blue-900/20' : ''}
                        hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200"
                 data-step="${step.number}">
                <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center
                            ${step.completed ? 'bg-green-100 dark:bg-green-900/30' : step.current ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-gray-100 dark:bg-gray-800'}">
                    <span class="text-sm font-bold ${durumColor}">${durumIcon}</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                            Adım ${step.number}: ${step.title}
                        </h4>
                        ${step.completed ? '<span class="text-xs text-green-600 dark:text-green-400">Tamamlandı</span>' : ''}
                    </div>
                    <p class="text-xs text-gray-500 mt-1 dark:text-slate-500">${step.description}</p>
                    ${step.current ? '<div class="mt-2 text-xs text-blue-600 dark:text-blue-400">Devam ediyor...</div>' : ''}
                </div>
            </div>
        `;
    }

    /**
     * Update progress indicator
     */
    updateProgress() {
        // Get current step from Alpine.js
        const wizardComponent = Alpine.$data(document.querySelector('[x-data*="ilanWizard"]'));
        const currentStep = wizardComponent?.currentStep || 1;
        const completedSteps = wizardComponent?.completedSteps || new Set();

        // Update step durum
        this.steps.forEach((step, index) => {
            step.current = step.number === currentStep;
            step.completed = completedSteps.has(step.number) || step.number < currentStep;
        });

        // Calculate overall progress
        const completedCount = this.steps.filter((s) => s.completed).length;
        const overallPercentage = Math.round((completedCount / this.steps.length) * 100);

        // Update UI
        this.updateProgressBar(overallPercentage);
        this.updateStepList();
    }

    /**
     * Update progress bar
     */
    updateProgressBar(percentage) {
        const progressBar = document.getElementById('overall-progress-bar');
        const progressPercentage = document.getElementById('overall-progress-percentage');

        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
        }

        if (progressPercentage) {
            progressPercentage.textContent = `%${percentage}`;
        }
    }

    /**
     * Update step list
     */
    updateStepList() {
        const stepList = document.getElementById('step-progress-list');
        if (!stepList) return;

        stepList.innerHTML = this.steps.map((step) => this.renderStepItem(step)).join('');
    }

    /**
     * Setup step listeners
     */
    setupStepListeners() {
        // Listen to step changes
        document.addEventListener('wizard-step-changed', (e) => {
            this.updateProgress();
        });

        // Listen to step completion
        document.addEventListener('wizard-step-completed', (e) => {
            this.updateProgress();
        });
    }

    /**
     * Start auto-update
     */
    startAutoUpdate() {
        setInterval(() => {
            this.updateProgress();
        }, this.config.updateInterval);
    }

    /**
     * Get step completion durum
     */
    getStepCompletionDurum(stepNumber) {
        const step = this.steps.find((s) => s.number === stepNumber);
        if (!step) return { completed: false, percentage: 0 };

        // Calculate step-specific completion percentage
        const stepFields = this.getStepFields(stepNumber);
        const completedFields = stepFields.filter((field) => {
            const fieldElement = document.querySelector(`[name="${field}"]`);
            if (!fieldElement) return false;
            const value = this.getFieldValue(fieldElement);
            return value !== null && value !== undefined && value !== '';
        });

        const percentage =
            stepFields.length > 0
                ? Math.round((completedFields.length / stepFields.length) * 100)
                : 0;

        return {
            completed: step.completed,
            percentage: percentage,
            fieldsCompleted: completedFields.length,
            fieldsTotal: stepFields.length,
        };
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
            2: ['metrekare', 'oda_sayisi'], // Kategoriye özel alanlar dinamik
            3: ['aciklama', 'ilan_sahibi_id', 'yayin_durumu'],
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
        } else {
            return field.value;
        }
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    // Wait for wizard to be ready
    setTimeout(() => {
        const progressContainer = document.querySelector('.wizard-progress-container');
        if (progressContainer) {
            window.progressIndicator = new ProgressIndicator({
                showStepDetails: true,
                showCompletionStatus: true,
                showStepPreview: true,
                updateInterval: 2000,
            });
        }
    }, 500);
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ProgressIndicator;
}
