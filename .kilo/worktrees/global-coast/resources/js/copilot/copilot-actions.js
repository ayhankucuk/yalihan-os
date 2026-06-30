/**
 * Copilot Actions — Frontend Executor
 *
 * Integrates with wizard Alpine.js form state to execute copilot-generated actions.
 * Handles: preview, apply, undo, reject, diff tracking, and auto-run mode.
 *
 * Usage:
 *   const executor = new CopilotActionExecutor();
 *   const result = await executor.fetchActions(formState, 'suggest');
 *   executor.applyAction(action, alpineComponent);
 *   executor.undoLast(alpineComponent);
 */

import { tStart, tEnd } from '../wizard/core/telemetry.js';

/**
 * @typedef {Object} CopilotAction
 * @property {string} id
 * @property {string} type - field_autofill|multi_field_apply|pricing_apply|full_listing_generate
 * @property {string} label
 * @property {string} description
 * @property {string} target - Field path (e.g. 'baslik', 'features.oda_sayisi')
 * @property {*} value
 * @property {Array} alternatives
 * @property {number} priority
 * @property {number} confidence
 * @property {boolean} requires_confirmation
 * @property {string} source
 */

const COPILOT_ACTIONS_URL = '/admin/copilot/actions';
const COPILOT_APPLY_URL = '/admin/copilot/actions/apply';
const COPILOT_UNDO_URL = '/admin/copilot/actions/undo';
const COPILOT_REJECT_URL = '/admin/copilot/actions/reject';

class CopilotActionExecutor {
    constructor() {
        /** @type {CopilotAction[]} */
        this.actions = [];
        /** @type {Array<{action: CopilotAction, previousValue: *, logId: number|null}>} */
        this.undoStack = [];
        /** @type {number|null} */
        this.currentLogId = null;
        this.loading = false;
        this.error = null;
    }

    /**
     * Fetch copilot actions for the current wizard form state.
     *
     * @param {Object} formState - Current wizard form data
     * @param {string} mode - 'suggest' | 'auto_run' | 'full_generate'
     * @param {number|null} ilanId - Existing listing ID (for edit mode)
     * @returns {Promise<{actions: CopilotAction[], confidence: number, meta: Object}>}
     */
    async fetchActions(formState, mode = 'suggest', ilanId = null) {
        this.loading = true;
        this.error = null;

        const timer = tStart('copilot_fetch_actions');

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(COPILOT_ACTIONS_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    form_state: formState,
                    mode: mode,
                    ilan_id: ilanId,
                }),
            });

            const data = await response.json();

            tEnd(timer, {
                http_durum_kodu: response.status,
                basarili: response.ok,
                istek_url: COPILOT_ACTIONS_URL,
                action_count: data.actions?.length || 0,
            });

            if (!response.ok) {
                throw new Error(data.message || `HTTP ${response.status}`);
            }

            this.actions = data.actions || [];
            this.loading = false;

            return data;
        } catch (err) {
            tEnd(timer, {
                basarili: false,
                hata_mesaji: err.message,
                istek_url: COPILOT_ACTIONS_URL,
            });

            this.error = err.message;
            this.loading = false;
            throw err;
        }
    }

    /**
     * Apply a single action to the Alpine.js form state.
     *
     * @param {CopilotAction} action - The action to apply
     * @param {Object} alpineComponent - Alpine.js component with form data
     * @returns {{applied: boolean, previousValue: *}}
     */
    applyAction(action, alpineComponent) {
        const target = action.target;
        const previousValue = this._getNestedValue(alpineComponent, target);

        // Store for undo
        this.undoStack.push({
            action,
            previousValue,
            logId: this.currentLogId,
        });

        // Apply the value
        this._setNestedValue(alpineComponent, target, action.value);

        // Dispatch change event for dependency re-evaluation
        const fieldName = target.includes('.') ? target.split('.').pop() : target;
        const fieldEl = document.querySelector(
            `[name="${fieldName}"], [x-model="${target}"], [x-model="formData.${target}"]`
        );
        if (fieldEl) {
            fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
            fieldEl.dispatchEvent(new Event('input', { bubbles: true }));
        }

        // Emit custom event for wizard to re-evaluate dependencies
        window.dispatchEvent(
            new CustomEvent('copilot:action-applied', {
                detail: { action, previousValue, target },
            })
        );

        return { applied: true, previousValue };
    }

    /**
     * Apply multiple actions at once.
     *
     * @param {CopilotAction[]} actions - Actions to apply
     * @param {Object} alpineComponent - Alpine.js component
     * @returns {{applied: number, skipped: number}}
     */
    applyAll(actions, alpineComponent) {
        let applied = 0;
        let skipped = 0;

        for (const action of actions) {
            if (action.requires_confirmation) {
                skipped++;
                continue;
            }

            this.applyAction(action, alpineComponent);
            applied++;
        }

        return { applied, skipped };
    }

    /**
     * Undo the last applied action.
     *
     * @param {Object} alpineComponent - Alpine.js component
     * @returns {{undone: boolean, action: CopilotAction|null}}
     */
    undoLast(alpineComponent) {
        if (this.undoStack.length === 0) {
            return { undone: false, action: null };
        }

        const entry = this.undoStack.pop();
        this._setNestedValue(alpineComponent, entry.action.target, entry.previousValue);

        // Dispatch change event
        const fieldName = entry.action.target.includes('.')
            ? entry.action.target.split('.').pop()
            : entry.action.target;
        const fieldEl = document.querySelector(`[name="${fieldName}"]`);
        if (fieldEl) {
            fieldEl.dispatchEvent(new Event('change', { bubbles: true }));
        }

        window.dispatchEvent(
            new CustomEvent('copilot:action-undone', {
                detail: { action: entry.action },
            })
        );

        return { undone: true, action: entry.action };
    }

    /**
     * Report an action as applied to the backend.
     *
     * @param {number} logId - CopilotActionLog ID
     * @param {Object} appliedFields - Map of field → value that were applied
     * @param {Object|null} diffSnapshot - Before/after snapshot
     */
    async reportApply(logId, appliedFields, diffSnapshot = null) {
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch(COPILOT_APPLY_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                log_id: logId,
                applied_fields: appliedFields,
                diff_snapshot: diffSnapshot,
            }),
        });

        return response.json();
    }

    /**
     * Report an undo to the backend.
     */
    async reportUndo(logId) {
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch(COPILOT_UNDO_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ log_id: logId }),
        });

        return response.json();
    }

    /**
     * Reject an action suggestion.
     */
    async reportReject(logId, reason = null) {
        const csrfToken =
            document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        const response = await fetch(COPILOT_REJECT_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
            body: JSON.stringify({ log_id: logId, reason }),
        });

        return response.json();
    }

    /**
     * Build a diff preview between current form state and proposed actions.
     *
     * @param {CopilotAction[]} actions
     * @param {Object} alpineComponent
     * @returns {Array<{field: string, label: string, current: *, proposed: *, confidence: number}>}
     */
    buildDiffPreview(actions, alpineComponent) {
        return actions.map((action) => ({
            field: action.target,
            label: action.label,
            current: this._getNestedValue(alpineComponent, action.target) ?? '',
            proposed: action.value,
            confidence: action.confidence,
            type: action.type,
            source: action.source,
        }));
    }

    // --- Internal helpers ---

    _getNestedValue(obj, path) {
        const keys = path.split('.');
        let current = obj;
        for (const key of keys) {
            if (current == null) return undefined;
            current = current[key];
        }
        return current;
    }

    _setNestedValue(obj, path, value) {
        const keys = path.split('.');
        let current = obj;
        for (let i = 0; i < keys.length - 1; i++) {
            if (current[keys[i]] == null) {
                current[keys[i]] = {};
            }
            current = current[keys[i]];
        }
        current[keys[keys.length - 1]] = value;
    }
}

// Export as module and attach to window for Alpine.js access
export { CopilotActionExecutor };

// Global instance for blade templates
window.CopilotActionExecutor = CopilotActionExecutor;
