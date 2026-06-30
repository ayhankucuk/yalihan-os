/**
 * Wizard Event Bus - Merkezi Event Yönetimi
 *
 * @module wizard/core/wizard-events
 * @version 3.0.0
 * @since 2026-01-28
 * @context7 Modüler Wizard Mimarisi - SEALED
 *
 * Tüm wizard modülleri arası iletişim bu event bus üzerinden yapılır.
 * Global window event'leri yerine bu merkezi sistem kullanılmalıdır.
 */

/**
 * Event tipleri - Type-safe event isimleri
 * @readonly
 * @enum {string}
 */
export const WizardEventTypes = Object.freeze({
    // Navigation Events
    STEP_CHANGE: 'wizard:step-change',
    STEP_VALIDATED: 'wizard:step-validated',
    STEP_LOADED: 'wizard:step-loaded',

    // Category Events
    CATEGORY_CHANGED: 'wizard:category-changed',
    SUBCATEGORY_CHANGED: 'wizard:subcategory-changed',
    YAYIN_TIPI_CHANGED: 'wizard:yayin-tipi-changed',

    // Form Events
    FIELD_CHANGED: 'wizard:field-changed',
    FORM_VALIDATED: 'wizard:form-validated',
    DRAFT_SAVED: 'wizard:draft-saved',
    DRAFT_LOADED: 'wizard:draft-loaded',

    // Location Events
    LOCATION_CHANGED: 'wizard:location-changed',
    MAP_MARKER_MOVED: 'wizard:map-marker-moved',
    COORDINATES_UPDATED: 'wizard:coordinates-updated',

    // Photo Events
    PHOTO_ADDED: 'wizard:photo-added',
    PHOTO_REMOVED: 'wizard:photo-removed',
    PHOTO_REORDERED: 'wizard:photo-reordered',

    // AI Events
    AI_TITLE_GENERATED: 'wizard:ai-title-generated',
    AI_QUALITY_CHECKED: 'wizard:ai-quality-checked',
    AI_DESCRIPTION_GENERATED: 'wizard:ai-description-generated',

    // Context Events
    CONTEXT_RESOLVED: 'wizard:context-resolved',
    TEMPLATE_APPLIED: 'wizard:template-applied',

    // Submission Events
    SUBMIT_STARTED: 'wizard:submit-started',
    SUBMIT_SUCCESS: 'wizard:submit-success',
    SUBMIT_ERROR: 'wizard:submit-error',

    // Error Events
    ERROR_OCCURRED: 'wizard:error-occurred',
    VALIDATION_ERROR: 'wizard:validation-error',
});

/**
 * WizardEventBus - Singleton Event Manager
 *
 * @example
 * // Subscribe to an event
 * WizardEventBus.on(WizardEventTypes.STEP_CHANGE, (data) => {
 *     console.log('Step changed to:', data.step);
 * });
 *
 * // Emit an event
 * WizardEventBus.emit(WizardEventTypes.STEP_CHANGE, { step: 2, previous: 1 });
 *
 * // One-time listener
 * WizardEventBus.once(WizardEventTypes.SUBMIT_SUCCESS, (data) => {
 *     window.location.href = data.redirect;
 * });
 */
class WizardEventBusClass {
    constructor() {
        /** @private */
        this._listeners = new Map();

        /** @private */
        this._onceListeners = new Map();

        /** @private */
        this._history = [];

        /** @private */
        this._maxHistory = 50;

        /** @private */
        this._debugMode = false;
    }

    /**
     * Enable/disable debug mode
     * @param {boolean} enabled
     */
    setDebugMode(enabled) {
        this._debugMode = enabled;
    }

    /**
     * Subscribe to an event
     * @param {string} eventType - Event type from WizardEventTypes
     * @param {Function} callback - Callback function
     * @returns {Function} Unsubscribe function
     */
    on(eventType, callback) {
        if (!this._listeners.has(eventType)) {
            this._listeners.set(eventType, new Set());
        }
        this._listeners.get(eventType).add(callback);

        // Return unsubscribe function
        return () => this.off(eventType, callback);
    }

    /**
     * Subscribe to an event once
     * @param {string} eventType - Event type
     * @param {Function} callback - Callback function
     */
    once(eventType, callback) {
        if (!this._onceListeners.has(eventType)) {
            this._onceListeners.set(eventType, new Set());
        }
        this._onceListeners.get(eventType).add(callback);
    }

    /**
     * Unsubscribe from an event
     * @param {string} eventType - Event type
     * @param {Function} callback - Callback to remove
     */
    off(eventType, callback) {
        if (this._listeners.has(eventType)) {
            this._listeners.get(eventType).delete(callback);
        }
        if (this._onceListeners.has(eventType)) {
            this._onceListeners.get(eventType).delete(callback);
        }
    }

    /**
     * Emit an event
     * @param {string} eventType - Event type
     * @param {Object} data - Event data
     */
    emit(eventType, data = {}) {
        const eventData = {
            type: eventType,
            data,
            timestamp: Date.now(),
        };

        // Log in debug mode
        if (this._debugMode) {
            console.log(`[WizardEventBus] ${eventType}`, data);
        }

        // Add to history
        this._history.push(eventData);
        if (this._history.length > this._maxHistory) {
            this._history.shift();
        }

        // Call regular listeners
        if (this._listeners.has(eventType)) {
            this._listeners.get(eventType).forEach((callback) => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(`[WizardEventBus] Error in listener for ${eventType}:`, error);
                }
            });
        }

        // Call once listeners and remove them
        if (this._onceListeners.has(eventType)) {
            const onceCallbacks = this._onceListeners.get(eventType);
            onceCallbacks.forEach((callback) => {
                try {
                    callback(data);
                } catch (error) {
                    console.error(
                        `[WizardEventBus] Error in once listener for ${eventType}:`,
                        error
                    );
                }
            });
            this._onceListeners.delete(eventType);
        }

        // Also dispatch as DOM event for backward compatibility
        document.dispatchEvent(new CustomEvent(eventType, { detail: data }));
    }

    /**
     * Get event history
     * @param {string} [eventType] - Filter by event type
     * @returns {Array} Event history
     */
    getHistory(eventType = null) {
        if (eventType) {
            return this._history.filter((e) => e.type === eventType);
        }
        return [...this._history];
    }

    /**
     * Clear all listeners
     */
    clear() {
        this._listeners.clear();
        this._onceListeners.clear();
    }

    /**
     * Get listener count for an event type
     * @param {string} eventType
     * @returns {number}
     */
    listenerCount(eventType) {
        const regular = this._listeners.get(eventType)?.size || 0;
        const once = this._onceListeners.get(eventType)?.size || 0;
        return regular + once;
    }
}

// Singleton instance
export const WizardEventBus = new WizardEventBusClass();

// Global export for backward compatibility
if (typeof window !== 'undefined') {
    window.YalihanWizard = window.YalihanWizard || {};
    window.YalihanWizard.events = WizardEventBus;
    window.YalihanWizard.EventTypes = WizardEventTypes;
}

export default WizardEventBus;
