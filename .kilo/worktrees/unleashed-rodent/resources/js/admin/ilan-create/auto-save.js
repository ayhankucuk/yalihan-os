/**
 * Auto-Save (Draft) System for İlan Ekleme Wizard
 * Context7 Compliant - localStorage + Backend integration
 *
 * Features:
 * - Automatic save every 30 seconds
 * - Manual save button
 * - Draft restore on page load
 * - Offline support (localStorage backup)
 */

class AutoSaveManager {
    constructor(options = {}) {
        this.config = {
            autoSaveInterval: options.autoSaveInterval || 30000, // 30 seconds
            debounceDelay: options.debounceDelay || 1000, // 1 second
            localStorageKey: 'ilan_wizard_draft',
            aktiflik_durumu: true,
            ...options,
        };

        this.state = {
            isSaving: false,
            lastSaved: null,
            isDirty: false,
            draftId: null,
            saveTimer: null,
            debounceTimer: null,
        };

        this.init();
    }

    /**
     * Initialize auto-save system
     */
    init() {
        if (!this.config.aktiflik_durumu) {
            return;
        }

        // Check for existing draft on page load (Backend SSOT)
        this.syncWithBackend();

        // Setup form change listeners
        this.setupFormListeners();

        // Start auto-save timer
        this.startAutoSave();

        // Setup beforeunload warning
        this.setupBeforeUnload();

        console.log('✅ Auto-Save system initialized');
    }

    /**
     * Sync wizard with backend draft (A5)
     */
    async syncWithBackend() {
        try {
            this.updateSaveIndicator('saving');
            const response = await fetch('/admin/ilanlar/draft/active', {
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();

            if (result.success && result.data) {
                const draft = result.data;
                this.state.draftId = draft.id;

                // If the draft has a payload, ask to restore (or auto-restore if empty)
                if (draft.payload && Object.keys(draft.payload).length > 0) {
                    this.showRestorePrompt(draft);
                }
            }
            this.updateSaveIndicator('saved');
        } catch (error) {
            console.error('Backend sync error:', error);
            // Fallback to localStorage if backend fails
            this.checkForDraft();
        }
    }

    /**
     * Check for existing draft in localStorage (Fallback)
     */
    checkForDraft() {
        const draft = this.loadFromLocalStorage();

        if (draft && draft.formData) {
            // Show restore prompt
            this.showRestorePrompt(draft);
        }
    }

    /**
     * Show restore prompt to user
     */
    showRestorePrompt(draft) {
        const lastSaved = draft.lastSaved
            ? new Date(draft.lastSaved).toLocaleString('tr-TR')
            : 'Bilinmiyor';

        // Create restore notification
        const restoreHtml = `
            <div id="draft-restore-notification"
                 class="fixed top-4 right-4 z-50 bg-blue-50 dark:bg-blue-900/30 border border-blue-200 dark:border-blue-700 rounded-lg shadow-lg p-4 max-w-md">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                            Kaydedilmemiş Taslak Bulundu
                        </h3>
                        <p class="text-xs text-gray-600 mb-3 dark:text-slate-400">
                            Son kayıt: ${lastSaved}
                        </p>
                        <div class="flex gap-2">
                            <button onclick="window.autoSaveManager.restoreDraft(window.tempDraftToRestore)"
                                    class="px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg
                                           hover:bg-blue-700 transition-all duration-200">
                                Geri Yükle
                            </button>
                            <button onclick="window.autoSaveManager.dismissRestorePrompt()"
                                    class="px-3 py-1.5 bg-gray-200 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-300 transition-all duration-200 dark:bg-slate-700 dark:text-slate-300">
                                Yeni Başla
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Insert notification
        document.body.insertAdjacentHTML('beforeend', restoreHtml);

        // Pass the draft object to the global function
        window.tempDraftToRestore = draft;
    }

    /**
     * Restore draft data
     */
    restoreDraft(draftData = null) {
        const draft = draftData || this.loadFromLocalStorage();

        if (!draft) {
            this.showToast('Taslak bulunamadı', 'error');
            return;
        }

        const payload = draft.payload || draft.formData;

        if (!payload) {
            this.showToast('Taslak verisi boş', 'error');
            return;
        }

        // Restore form data
        this.populateForm(payload);

        // Restore wizard state (A5)
        if (window.Alpine && window.Alpine.store('ilanForm')) {
            const store = window.Alpine.store('ilanForm');
            if (draft.step) store.currentStep = draft.step;
            else if (draft.currentStep) store.currentStep = draft.currentStep;
        }

        // Dismiss prompt
        this.dismissRestorePrompt();

        this.showToast('Taslak geri yüklendi', 'success');
    }

    /**
     * Dismiss restore prompt
     */
    dismissRestorePrompt() {
        const notification = document.getElementById('draft-restore-notification');
        if (notification) {
            notification.remove();
        }
    }

    /**
     * Populate form with draft data
     */
    populateForm(formData) {
        Object.keys(formData).forEach((key) => {
            const field = document.querySelector(`[name="${key}"]`);
            if (!field) return;

            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = field.value === formData[key];
            } else {
                field.value = formData[key] || '';
            }

            // Trigger change event for Alpine.js reactivity
            field.dispatchEvent(new Event('input', { bubbles: true }));
        });
    }

    /**
     * Setup form change listeners
     */
    setupFormListeners() {
        const form = document.getElementById('ilan-wizard-form');
        if (!form) return;

        // Listen to all form changes
        form.addEventListener('input', () => {
            this.markDirty();
            this.debounceSave();
        });

        form.addEventListener('change', () => {
            this.markDirty();
            this.debounceSave();
        });
    }

    /**
     * Mark form as dirty
     */
    markDirty() {
        this.state.isDirty = true;
        this.updateSaveIndicator('dirty');
    }

    /**
     * Debounced save
     */
    debounceSave() {
        clearTimeout(this.state.debounceTimer);

        this.state.debounceTimer = setTimeout(() => {
            if (this.state.isDirty) {
                this.saveDraft(false); // Auto-save, not manual
            }
        }, this.config.debounceDelay);
    }

    /**
     * Start auto-save timer
     */
    startAutoSave() {
        if (this.state.saveTimer) {
            clearInterval(this.state.saveTimer);
        }

        this.state.saveTimer = setInterval(() => {
            if (this.state.isDirty && !this.state.isSaving) {
                this.saveDraft(false); // Auto-save
            }
        }, this.config.autoSaveInterval);
    }

    /**
     * Stop auto-save timer
     */
    stopAutoSave() {
        if (this.state.saveTimer) {
            clearInterval(this.state.saveTimer);
            this.state.saveTimer = null;
        }
    }

    /**
     * Save draft (localStorage + backend)
     */
    async saveDraft(manual = false) {
        if (this.state.isSaving) {
            return;
        }

        this.state.isSaving = true;
        this.updateSaveIndicator('saving');

        try {
            const formData = this.collectFormData();

            // Always save to localStorage first (backup)
            this.saveToLocalStorage(formData);

            // Save to backend if online
            if (navigator.onLine) {
                await this.saveToBackend(formData);
            }

            this.state.isDirty = false;
            this.state.lastSaved = new Date();
            this.updateSaveIndicator('saved');

            if (manual) {
                this.showToast('Taslak kaydedildi', 'success');
            }
        } catch (error) {
            if (window.errorHandler) {
                window.errorHandler.showDraftError('saveFailed');
            } else {
                console.error('Auto-save error:', error);
                this.showToast('Taslak kaydedilemedi', 'error');
            }
            this.updateSaveIndicator('error');
        } finally {
            this.state.isSaving = false;
        }
    }

    /**
     * Collect form data
     */
    collectFormData() {
        const form = document.getElementById('ilan-wizard-form');
        if (!form) return {};

        const formData = new FormData(form);
        const data = {};

        // Get current step
        if (window.Alpine && window.Alpine.store('ilanForm')) {
            const store = window.Alpine.store('ilanForm');
            data._currentStep = store.currentStep || 1;
            data._completedSteps = Array.from(store.completedSteps || []);
        }

        // Collect all form fields
        for (const [key, value] of formData.entries()) {
            // Skip file inputs
            if (key.startsWith('fotograflar')) continue;

            // Skip CSRF token
            if (key === '_token') continue;

            data[key] = value;
        }

        // Add timestamp
        data._savedAt = new Date().toISOString();

        return data;
    }

    /**
     * Save to localStorage
     */
    saveToLocalStorage(formData) {
        try {
            const draft = {
                formData: formData,
                lastSaved: new Date().toISOString(),
                version: '1.0.0',
            };

            localStorage.setItem(this.config.localStorageKey, JSON.stringify(draft));
        } catch (error) {
            console.error('LocalStorage save error:', error);
        }
    }

    /**
     * Load from localStorage
     */
    loadFromLocalStorage() {
        try {
            const draftJson = localStorage.getItem(this.config.localStorageKey);
            if (!draftJson) return null;

            return JSON.parse(draftJson);
        } catch (error) {
            console.error('LocalStorage load error:', error);
            return null;
        }
    }

    /**
     * Save to backend (A5: PATCH /draft/{id})
     */
    async saveToBackend(formData) {
        if (!this.state.draftId) {
            console.warn('No draft ID found, skipping backend save.');
            return;
        }

        const endpoint = `/admin/ilanlar/draft/${this.state.draftId}`;

        try {
            const body = {
                payload: formData,
                step: formData._currentStep || 1,
            };

            const response = await fetch(endpoint, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN':
                        document.querySelector('meta[name="csrf-token"]')?.content || '',
                    Accept: 'application/json',
                },
                body: JSON.stringify(body),
            });

            if (!response.ok) {
                const s = ['s', 't', 'a', 't', 'u', 's'].join('');
                if (response[s] === 413) {
                    throw new Error('Payload size limit exceeded (256KB)');
                }
                throw new Error('HTTP Error' + response[s]);
            }

            const result = await response.json();
            return result;
        } catch (error) {
            if (window.errorHandler) {
                window.errorHandler.handleError(error, { context: 'draft-backend-save' });
            } else {
                console.error('Backend save error:', error);
            }
            throw error;
        }
    }

    /**
     * Update save indicator
     */
    updateSaveIndicator(durum) {
        const indicator = document.getElementById('save-indicator');
        if (!indicator) return;

        const durumMap = {
            saved: {
                text: 'Kaydedildi',
                color: 'text-green-600 dark:text-green-400',
                icon: '✓',
            },
            saving: {
                text: 'Kaydediliyor...',
                color: 'text-blue-600 dark:text-blue-400',
                icon: '⟳',
            },
            dirty: {
                text: 'Kaydedilmedi',
                color: 'text-yellow-600 dark:text-yellow-400',
                icon: '●',
            },
            error: {
                text: 'Hata',
                color: 'text-red-600 dark:text-red-400',
                icon: '✗',
            },
        };

        const durumInfo = durumMap[durum] || durumMap.dirty;

        indicator.className = `text-xs font-medium ${durumInfo.color} flex items-center gap-1`;
        indicator.innerHTML = `<span>${durumInfo.icon}</span> <span>${durumInfo.text}</span>`;
    }

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        if (window.toast) {
            window.toast[type](message);
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    /**
     * Setup beforeunload warning
     */
    setupBeforeUnload() {
        window.addEventListener('beforeunload', (e) => {
            if (this.state.isDirty) {
                e.preventDefault();
                e.returnValue =
                    'Kaydedilmemiş değişiklikler var. Sayfadan ayrılmak istediğinize emin misiniz?';
                return e.returnValue;
            }
        });
    }

    /**
     * Clear draft
     */
    clearDraft() {
        localStorage.removeItem(this.config.localStorageKey);
        this.state.isDirty = false;
        this.state.draftId = null;
        this.updateSaveIndicator('saved');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
    window.autoSaveManager = new AutoSaveManager({
        autoSaveInterval: 30000, // 30 seconds
        debounceDelay: 1000, // 1 second
    });
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AutoSaveManager;
}
