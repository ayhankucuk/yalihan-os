/**
 * Wizard State Manager - Merkezi State Yönetimi
 *
 * @module wizard/core/wizard-state
 * @version 3.0.0
 * @since 2026-01-28
 * @context7 Modüler Wizard Mimarisi - SEALED
 *
 * Reactive state management for wizard.
 * Proxy-based reactivity with automatic persistence.
 */

import { WizardEventBus, WizardEventTypes } from './wizard-events.js';

/**
 * Default wizard state
 * @type {Object}
 */
const DEFAULT_STATE = {
    // Navigation
    currentStep: 1,
    totalSteps: 6,
    completedSteps: [],
    visitedSteps: [1],

    // Categories
    anaKategoriId: null,
    altKategoriId: null,
    yayinTipiId: null,
    kategoriSlug: null,
    yayinTipiSlug: null,

    // Form Data
    formData: {},

    // Location - Context7: lat/lng canonical
    location: {
        ilId: null,
        ilceId: null,
        mahalleId: null,
        lat: null,
        lng: null,
        adres: null,
    },

    // Photos
    photos: [],
    uploadedPhotoIds: [],

    // AI Quality
    aiQualityResult: null,
    aiOverrideBlock: false,

    // Context
    wizardContext: null,
    templateId: null,

    // UI State
    isLoading: false,
    isSaving: false,
    isSubmitting: false,
    errors: {},

    // Draft
    draftId: null,
    lastSavedAt: null,
};

/**
 * WizardStateManager - Reactive State with Persistence
 */
class WizardStateManagerClass {
    constructor() {
        /** @private */
        this._state = { ...DEFAULT_STATE };

        /** @private */
        this._subscribers = new Map();

        /** @private */
        this._persistKey = 'yalihanWizardState';

        /** @private */
        this._autosaveEnabled = true;

        /** @private */
        this._autosaveDelay = 2000;

        /** @private */
        this._autosaveTimer = null;

        // Create reactive proxy
        this.state = this._createProxy(this._state);
    }

    /**
     * Create reactive proxy for state
     * @private
     */
    _createProxy(target, path = '') {
        const self = this;

        return new Proxy(target, {
            get(obj, prop) {
                const value = obj[prop];
                if (value && typeof value === 'object' && !Array.isArray(value)) {
                    return self._createProxy(value, path ? `${path}.${prop}` : prop);
                }
                return value;
            },

            set(obj, prop, value) {
                const oldValue = obj[prop];
                obj[prop] = value;

                const fullPath = path ? `${path}.${prop}` : prop;

                // Notify subscribers
                self._notifySubscribers(fullPath, value, oldValue);

                // Schedule autosave
                if (self._autosaveEnabled) {
                    self._scheduleAutosave();
                }

                return true;
            },
        });
    }

    /**
     * Notify subscribers of state change
     * @private
     */
    _notifySubscribers(path, newValue, oldValue) {
        // Notify specific path subscribers
        if (this._subscribers.has(path)) {
            this._subscribers.get(path).forEach((callback) => {
                try {
                    callback(newValue, oldValue, path);
                } catch (error) {
                    console.error(`[WizardState] Subscriber error for ${path}:`, error);
                }
            });
        }

        // Notify wildcard subscribers
        if (this._subscribers.has('*')) {
            this._subscribers.get('*').forEach((callback) => {
                try {
                    callback(newValue, oldValue, path);
                } catch (error) {
                    console.error('[WizardState] Wildcard subscriber error:', error);
                }
            });
        }

        // Emit event for major state changes
        const majorPaths = ['currentStep', 'anaKategoriId', 'altKategoriId', 'yayinTipiId'];
        if (majorPaths.includes(path)) {
            WizardEventBus.emit(WizardEventTypes.FIELD_CHANGED, {
                field: path,
                value: newValue,
                previousValue: oldValue,
            });
        }
    }

    /**
     * Schedule autosave
     * @private
     */
    _scheduleAutosave() {
        if (this._autosaveTimer) {
            clearTimeout(this._autosaveTimer);
        }

        this._autosaveTimer = setTimeout(() => {
            this.persist();
        }, this._autosaveDelay);
    }

    /**
     * Subscribe to state changes
     * @param {string} path - State path (use '*' for all changes)
     * @param {Function} callback - Callback(newValue, oldValue, path)
     * @returns {Function} Unsubscribe function
     */
    subscribe(path, callback) {
        if (!this._subscribers.has(path)) {
            this._subscribers.set(path, new Set());
        }
        this._subscribers.get(path).add(callback);

        return () => {
            this._subscribers.get(path)?.delete(callback);
        };
    }

    /**
     * Get state value by path
     * @param {string} path - Dot notation path (e.g., 'location.lat')
     * @returns {*} State value
     */
    get(path) {
        return path.split('.').reduce((obj, key) => obj?.[key], this._state);
    }

    /**
     * Set state value by path
     * @param {string} path - Dot notation path
     * @param {*} value - New value
     */
    set(path, value) {
        const keys = path.split('.');
        const lastKey = keys.pop();
        const target = keys.reduce((obj, key) => {
            if (!obj[key]) obj[key] = {};
            return obj[key];
        }, this._state);

        const oldValue = target[lastKey];
        target[lastKey] = value;
        this._notifySubscribers(path, value, oldValue);

        if (this._autosaveEnabled) {
            this._scheduleAutosave();
        }
    }

    /**
     * Batch update multiple state values
     * @param {Object} updates - Object with path: value pairs
     */
    batch(updates) {
        Object.entries(updates).forEach(([path, value]) => {
            this.set(path, value);
        });
    }

    /**
     * Reset state to defaults
     */
    reset() {
        this._state = { ...DEFAULT_STATE };
        this.state = this._createProxy(this._state);
        this.clearPersisted();
        WizardEventBus.emit(WizardEventTypes.DRAFT_LOADED, { reset: true });
    }

    /**
     * Persist state to localStorage
     */
    persist() {
        try {
            const stateToSave = {
                ...this._state,
                lastSavedAt: new Date().toISOString(),
            };
            localStorage.setItem(this._persistKey, JSON.stringify(stateToSave));
            WizardEventBus.emit(WizardEventTypes.DRAFT_SAVED, {
                timestamp: stateToSave.lastSavedAt,
            });
        } catch (error) {
            console.error('[WizardState] Persist error:', error);
        }
    }

    /**
     * Load state from localStorage
     * @returns {boolean} Success
     */
    restore() {
        try {
            const saved = localStorage.getItem(this._persistKey);
            if (saved) {
                const parsed = JSON.parse(saved);
                this._state = { ...DEFAULT_STATE, ...parsed };
                this.state = this._createProxy(this._state);
                WizardEventBus.emit(WizardEventTypes.DRAFT_LOADED, {
                    timestamp: this._state.lastSavedAt,
                });
                return true;
            }
        } catch (error) {
            console.error('[WizardState] Restore error:', error);
        }
        return false;
    }

    /**
     * Clear persisted state
     */
    clearPersisted() {
        localStorage.removeItem(this._persistKey);
    }

    /**
     * Get full state snapshot
     * @returns {Object}
     */
    getSnapshot() {
        return JSON.parse(JSON.stringify(this._state));
    }

    /**
     * Enable/disable autosave
     * @param {boolean} enabled
     */
    setAutosave(enabled) {
        this._autosaveEnabled = enabled;
    }

    /**
     * Check if step is completed
     * @param {number} step
     * @returns {boolean}
     */
    isStepCompleted(step) {
        return this._state.completedSteps.includes(step);
    }

    /**
     * Mark step as completed
     * @param {number} step
     */
    completeStep(step) {
        if (!this._state.completedSteps.includes(step)) {
            this._state.completedSteps.push(step);
            this._state.completedSteps.sort((a, b) => a - b);
            this._notifySubscribers('completedSteps', this._state.completedSteps, null);
        }
    }

    /**
     * Update form data
     * @param {string} fieldName
     * @param {*} value
     */
    updateFormField(fieldName, value) {
        this._state.formData[fieldName] = value;
        this._notifySubscribers(`formData.${fieldName}`, value, null);

        if (this._autosaveEnabled) {
            this._scheduleAutosave();
        }
    }

    /**
     * Get form data for submission
     * @returns {Object}
     */
    getFormData() {
        return { ...this._state.formData };
    }
}

// Singleton instance
export const WizardState = new WizardStateManagerClass();

// Global export
if (typeof window !== 'undefined') {
    window.YalihanWizard = window.YalihanWizard || {};
    window.YalihanWizard.state = WizardState;
}

export default WizardState;
