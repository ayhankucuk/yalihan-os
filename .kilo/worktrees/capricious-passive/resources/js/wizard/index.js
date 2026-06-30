/**
 * Wizard Modular System - Entry Point
 *
 * @module wizard/index
 * @version 3.0.0
 * @since 2026-01-28
 * @context7 Modüler Wizard Mimarisi - SEALED
 *
 * Central entry point for the modular wizard system.
 * Implements lazy-loading for optimal performance.
 *
 * Architecture:
 * ┌─────────────────────────────────────────────────────────────────┐
 * │                    YalihanWizard Namespace                       │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Core Modules (Always Loaded)                                   │
 * │  ├── wizard-events.js   → Event Bus (pub/sub)                  │
 * │  ├── wizard-state.js    → Reactive State Manager               │
 * │  └── wizard-validation.js → Form Validation Engine             │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Step Modules (Lazy Loaded)                                     │
 * │  ├── step1-cascade.js   → Category Cascade (SSOT)              │
 * │  ├── step3-upload.js    → Photo Upload                         │
 * │  ├── step4-location.js  → Location & Map                       │
 * │  └── step5-price.js     → Price & Investment                   │
 * ├─────────────────────────────────────────────────────────────────┤
 * │  Feature Modules (Lazy Loaded)                                  │
 * │  ├── ai-title-generator.js → AI Title Suggestions              │
 * │  ├── ai-quality-check.js   → Quality Gate                      │
 * │  └── poi-widget.js         → POI Display                       │
 * └─────────────────────────────────────────────────────────────────┘
 */

// ============================================================================
// CORE IMPORTS (Always Loaded)
// ============================================================================

import { WizardEventBus, WizardEventTypes } from './core/wizard-events.js';
import { WizardState } from './core/wizard-state.js';
import { WizardValidation } from './core/wizard-validation.js';

// ============================================================================
// LAZY LOAD DEFINITIONS
// ============================================================================

/**
 * Step module loaders
 * @type {Object.<number, Function>}
 */
const stepModules = {
    1: () => import('./step1-cascade.js'),
    3: () => import('./steps/step3-upload.js'),
    4: () => import('./steps/step4-location.js'),
    5: () => import('./steps/step5-price.js'),
};

/**
 * Feature module loaders
 * @type {Object.<string, Function>}
 */
const featureModules = {
    aiTitle: () => import('./features/ai-title-generator.js'),
    aiQuality: () => import('./features/ai-quality-check.js'),
    poi: () => import('./features/poi-widget.js'),
};

/**
 * Loaded modules cache
 * @type {Map<string, any>}
 */
const loadedModules = new Map();

// ============================================================================
// YALIHAN WIZARD NAMESPACE
// ============================================================================

/**
 * YalihanWizard - Global Namespace
 * @namespace
 */
const YalihanWizard = {
    /**
     * Version
     * @type {string}
     */
    version: '3.0.0',

    /**
     * Initialization status
     * @type {boolean}
     */
    initialized: false,

    /**
     * Core modules
     */
    events: WizardEventBus,
    EventTypes: WizardEventTypes,
    state: WizardState,
    validation: WizardValidation,

    /**
     * Loaded step modules (populated on demand)
     */
    steps: {},

    /**
     * Loaded feature modules (populated on demand)
     */
    features: {},

    /**
     * Initialize the wizard system
     * @param {Object} options - Configuration options
     */
    async init(options = {}) {
        if (this.initialized) {
            console.warn('[YalihanWizard] Already initialized');
            return;
        }

        console.log('[YalihanWizard] Initializing v' + this.version);

        // Restore state from localStorage if available
        if (options.restoreState !== false) {
            WizardState.restore();
        }

        // Setup step change listener for lazy loading
        WizardEventBus.on(WizardEventTypes.STEP_CHANGE, async (data) => {
            await this.loadStep(data.step);
        });

        // Load initial step
        const initialStep = options.initialStep || WizardState.state.currentStep || 1;
        await this.loadStep(initialStep);

        // Pre-load next step in background
        if (initialStep < WizardState.state.totalSteps) {
            this.preloadStep(initialStep + 1);
        }

        this.initialized = true;
        console.log('[YalihanWizard] Initialized successfully');

        // Emit init event
        WizardEventBus.emit('wizard:initialized', { version: this.version });
    },

    /**
     * Load a step module
     * @param {number} stepNum - Step number
     * @returns {Promise<Object|null>}
     */
    async loadStep(stepNum) {
        const cacheKey = `step_${stepNum}`;

        // Return cached if available
        if (loadedModules.has(cacheKey)) {
            return loadedModules.get(cacheKey);
        }

        // Check if loader exists
        if (!stepModules[stepNum]) {
            console.log(`[YalihanWizard] No lazy loader for step ${stepNum}`);
            return null;
        }

        try {
            console.log(`[YalihanWizard] Loading step ${stepNum} module`);
            const module = await stepModules[stepNum]();

            // Initialize if has init method
            if (module.default?.init) {
                module.default.init();
            }

            // Cache the module
            loadedModules.set(cacheKey, module.default);
            this.steps[stepNum] = module.default;

            // Emit loaded event
            WizardEventBus.emit(WizardEventTypes.STEP_LOADED, { step: stepNum });

            return module.default;
        } catch (error) {
            console.error(`[YalihanWizard] Failed to load step ${stepNum}:`, error);
            return null;
        }
    },

    /**
     * Preload a step module in background
     * @param {number} stepNum
     */
    preloadStep(stepNum) {
        if (loadedModules.has(`step_${stepNum}`)) return;
        if (!stepModules[stepNum]) return;

        // Use requestIdleCallback if available
        const load = () => this.loadStep(stepNum);

        if ('requestIdleCallback' in window) {
            window.requestIdleCallback(load, { timeout: 2000 });
        } else {
            setTimeout(load, 100);
        }
    },

    /**
     * Load a feature module
     * @param {string} featureName - Feature name
     * @returns {Promise<Object|null>}
     */
    async loadFeature(featureName) {
        const cacheKey = `feature_${featureName}`;

        // Return cached if available
        if (loadedModules.has(cacheKey)) {
            return loadedModules.get(cacheKey);
        }

        // Check if loader exists
        if (!featureModules[featureName]) {
            console.warn(`[YalihanWizard] Unknown feature: ${featureName}`);
            return null;
        }

        try {
            console.log(`[YalihanWizard] Loading feature: ${featureName}`);
            const module = await featureModules[featureName]();

            // Initialize if has init method
            if (module.default?.init) {
                module.default.init();
            }

            // Cache the module
            loadedModules.set(cacheKey, module.default);
            this.features[featureName] = module.default;

            return module.default;
        } catch (error) {
            console.error(`[YalihanWizard] Failed to load feature ${featureName}:`, error);
            return null;
        }
    },

    /**
     * Navigate to a step
     * @param {number} stepNum
     */
    async goToStep(stepNum) {
        const currentStep = WizardState.state.currentStep;

        // Validate current step before moving forward
        if (stepNum > currentStep) {
            const validation = WizardValidation.validateStep(currentStep);
            if (!validation.valid) {
                WizardEventBus.emit(WizardEventTypes.VALIDATION_ERROR, {
                    step: currentStep,
                    errors: validation.errors,
                });
                return false;
            }

            // Mark current step as completed
            WizardState.completeStep(currentStep);
        }

        // Load target step
        await this.loadStep(stepNum);

        // Update state
        WizardState.state.currentStep = stepNum;
        if (!WizardState.state.visitedSteps.includes(stepNum)) {
            WizardState.state.visitedSteps.push(stepNum);
        }

        // Emit event
        WizardEventBus.emit(WizardEventTypes.STEP_CHANGE, {
            step: stepNum,
            previous: currentStep,
        });

        // Preload next step
        if (stepNum < WizardState.state.totalSteps) {
            this.preloadStep(stepNum + 1);
        }

        return true;
    },

    /**
     * Go to next step
     * @returns {Promise<boolean>}
     */
    async nextStep() {
        const current = WizardState.state.currentStep;
        if (current >= WizardState.state.totalSteps) return false;
        return this.goToStep(current + 1);
    },

    /**
     * Go to previous step
     * @returns {Promise<boolean>}
     */
    async prevStep() {
        const current = WizardState.state.currentStep;
        if (current <= 1) return false;
        return this.goToStep(current - 1);
    },

    /**
     * Reset wizard
     */
    reset() {
        WizardState.reset();
        WizardValidation.clearAllErrors();
        loadedModules.clear();
        this.steps = {};
        this.features = {};
        this.initialized = false;
    },

    /**
     * Get debug info
     * @returns {Object}
     */
    debug() {
        return {
            version: this.version,
            initialized: this.initialized,
            currentStep: WizardState.state.currentStep,
            completedSteps: WizardState.state.completedSteps,
            loadedModules: Array.from(loadedModules.keys()),
            eventHistory: WizardEventBus.getHistory().slice(-10),
            state: WizardState.getSnapshot(),
        };
    },
};

// ============================================================================
// GLOBAL EXPORT
// ============================================================================

// Export to window
if (typeof window !== 'undefined') {
    window.YalihanWizard = YalihanWizard;

    // Auto-init on DOMContentLoaded if data attribute present
    document.addEventListener('DOMContentLoaded', () => {
        const wizardEl = document.querySelector('[data-wizard-auto-init]');
        if (wizardEl) {
            YalihanWizard.init();
        }
    });
}

// ES Module exports
export { YalihanWizard, WizardEventBus, WizardEventTypes, WizardState, WizardValidation };

export default YalihanWizard;
