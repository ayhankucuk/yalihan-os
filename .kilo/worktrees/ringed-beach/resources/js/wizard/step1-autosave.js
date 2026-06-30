/**
 * Step 1 Auto-Save Manager
 * Context7: Draft saving system for wizard form
 */

import { logger } from './step1-core.js';

/**
 * Auto-Save Manager for Wizard Form
 * Handles localStorage + backend draft saving
 */
export class AutoSaveManager {
    constructor(formId = 'ilan-wizard-form') {
        this.form = document.getElementById(formId);
        this.saveInterval = 30000; // 30 seconds
        this.saveTimer = null;
        this.lastSaveData = null;
        this.isDirty = false;
        this.isSaving = false;
        this.draftKey = `ilan_draft_${Date.now()}`;
        this.isOnline = navigator.onLine;
        this.lastOnlineCheck = Date.now();
        this.onlineCheckInterval = 60000; // 1 minute

        if (!this.form) {
            logger.warn('Form not found for auto-save');
            return;
        }

        this.init();
    }

    /**
     * Initialize auto-save system
     */
    init() {
        // Check for existing draft on load
        this.loadDraft();

        // Listen to form changes
        this.form.addEventListener('input', () => this.markDirty());
        this.form.addEventListener('change', () => this.markDirty());

        // Start auto-save timer
        this.startAutoSave();

        // Save before page unload
        window.addEventListener('beforeunload', () => this.saveDraft(true));

        // Online/offline detection
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.lastOnlineCheck = Date.now();
            this.saveDraft();
        });
        window.addEventListener('offline', () => {
            this.isOnline = false;
            logger.warn('Offline mode: Drafts will be saved locally only');
        });

        // Periodic online check (navigator.onLine can be unreliable)
        setInterval(() => {
            this.checkOnlineStatus();
        }, this.onlineCheckInterval);

        // Manual save button (if exists)
        const saveButton = document.getElementById('save-draft-btn');
        if (saveButton) {
            saveButton.addEventListener('click', () => this.saveDraft(true));
        }

        logger.log('✅ Auto-save system initialized');
    }

    /**
     * Mark form as dirty (has unsaved changes)
     */
    markDirty() {
        this.isDirty = true;
        this.updateSaveIndicator('unsaved');
    }

    /**
     * Start auto-save timer
     */
    startAutoSave() {
        if (this.saveTimer) {
            clearInterval(this.saveTimer);
        }

        this.saveTimer = setInterval(() => {
            if (this.isDirty && !this.isSaving) {
                this.saveDraft();
            }
        }, this.saveInterval);
    }

    /**
     * Stop auto-save timer
     */
    stopAutoSave() {
        if (this.saveTimer) {
            clearInterval(this.saveTimer);
            this.saveTimer = null;
        }
    }

    /**
     * Collect form data
     * @returns {Object} Form data as object
     */
    collectFormData() {
        const data = {};

        // ✅ SAB: Collect form data directly from form elements (skip file inputs)
        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach((field) => {
            const name = field.name;
            if (!name || name === '_token' || name === 'csrf_token') return;

            // Skip file inputs (cannot be serialized)
            if (field.type === 'file') {
                return;
            }

            // Skip live search search fields (only save the hidden ID field)
            if (name.endsWith('_search')) {
                return;
            }

            // Handle different field types
            if (name.endsWith('[]') && field.type === 'checkbox') {
                // Array checkbox'lar (site_ozellikleri[], vb.)
                const arrayKey = name.slice(0, -2);
                if (!data[arrayKey]) {
                    data[arrayKey] = [];
                }
                if (field.checked) {
                    const value = field.value || '1';
                    if (!data[arrayKey].includes(value)) {
                        data[arrayKey].push(value);
                    }
                }
            } else if (field.type === 'checkbox') {
                // Normal checkbox'lar
                data[name] = field.checked ? field.value || '1' : '0';
            } else if (field.type === 'radio') {
                // Radio button'lar
                if (field.checked) {
                    data[name] = field.value;
                }
            } else if (field.tagName === 'SELECT' && field.multiple) {
                // Multi-select
                const selectedValues = Array.from(field.selectedOptions)
                    .map((opt) => opt.value)
                    .filter((v) => v);
                data[name] = selectedValues.length > 0 ? selectedValues : [];
            } else {
                // Normal input, select, textarea
                data[name] = field.value || '';
            }
        });

        // Get current step (try Alpine.js, fallback to DOM)
        try {
            if (window.Alpine) {
                const wizardElement = document.querySelector('[x-data*="ilanWizard"]');
                if (wizardElement) {
                    const wizardComponent = window.Alpine.$data(wizardElement);
                    if (wizardComponent) {
                        data._current_step = wizardComponent.currentStep;
                        data._completed_steps = Array.from(wizardComponent.completedSteps || []);
                    }
                }
            }
        } catch (error) {
            // Alpine.js not available, skip step tracking
            logger.debug('Alpine.js not available for step tracking');
        }

        // Add timestamp
        data._saved_at = new Date().toISOString();

        return data;
    }

    /**
     * Save draft to localStorage
     */
    saveToLocalStorage(data = null) {
        try {
            const formData = data || this.collectFormData();
            localStorage.setItem('ilan_draft', JSON.stringify(formData));
            localStorage.setItem('ilan_draft_timestamp', new Date().toISOString());
            logger.debug('Draft saved to localStorage');
        } catch (error) {
            logger.error('localStorage save error:', error);
        }
    }

    /**
     * Check online status with actual network test
     */
    async checkOnlineStatus() {
        // Skip if recently checked
        if (Date.now() - this.lastOnlineCheck < 30000) {
            return;
        }

        try {
            // Quick health check to a lightweight endpoint
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 3000); // 3 second timeout

            const url =
                window.APIConfig && window.APIConfig.ai && window.APIConfig.ai.health
                    ? window.APIConfig.ai.health
                    : '/api/v1/ai/health';
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            clearTimeout(timeoutId);

            if (response.ok || response.status === 401) {
                // 401 means server is reachable (just not authorized)
                this.isOnline = true;
                this.lastOnlineCheck = Date.now();
            } else {
                this.isOnline = false;
            }
        } catch (error) {
            // Network error - assume offline
            this.isOnline = false;
        }
    }

    /**
     * Load draft from localStorage
     */
    loadFromLocalStorage() {
        try {
            const draftData = localStorage.getItem('ilan_draft');
            const timestamp = localStorage.getItem('ilan_draft_timestamp');

            if (draftData && timestamp) {
                const data = JSON.parse(draftData);
                const savedDate = new Date(timestamp);
                const daysSinceSave = (Date.now() - savedDate.getTime()) / (1000 * 60 * 60 * 24);

                // Only load if saved within last 7 days
                if (daysSinceSave < 7) {
                    return { data, timestamp: savedDate };
                } else {
                    // Clear old draft
                    localStorage.removeItem('ilan_draft');
                    localStorage.removeItem('ilan_draft_timestamp');
                }
            }
        } catch (error) {
            logger.error('localStorage load error:', error);
        }

        return null;
    }

    /**
     * Save draft to backend
     * @param {boolean} manual - Is this a manual save?
     */
    async saveDraft(manual = false) {
        if (!this.isDirty && !manual) {
            return;
        }

        if (this.isSaving) {
            logger.debug('Save already in progress, skipping...');
            return;
        }

        this.isSaving = true;
        this.updateSaveIndicator('saving');

        const formData = this.collectFormData();

        // Always save to localStorage first (backup)
        this.saveToLocalStorage(formData);

        // Check if data changed
        if (this.lastSaveData && JSON.stringify(formData) === JSON.stringify(this.lastSaveData)) {
            this.isSaving = false;
            this.updateSaveIndicator('saved');
            return;
        }

        // Save to backend if online
        if (this.isOnline) {
            try {
                const draftUrl = window.APIConfig?.admin?.saveDraft || '/admin/ilanlar/draft';
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

                if (!csrfToken) {
                    throw new Error('CSRF token not found');
                }

                const response = await fetch(draftUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(formData),
                });

                if (response.ok) {
                    const result = await response.json();
                    this.lastSaveData = formData;
                    this.isDirty = false;
                    this.updateSaveIndicator('saved');

                    if (manual && window.toast) {
                        window.toast.success('Taslak kaydedildi');
                    }

                    logger.log('✅ Draft saved to backend');
                } else {
                    // 401 veya 404 durumunda offline moda geç
                    if (response.status === 401 || response.status === 404) {
                        logger.warn('Draft endpoint not available, using localStorage only');
                        this.isOnline = false;
                        this.updateSaveIndicator('offline');
                    } else {
                        throw new Error(`Save failed: ${response.status}`);
                    }
                }
            } catch (error) {
                // Network hatası durumunda offline moda geç
                if (
                    error.message?.includes('Failed to fetch') ||
                    error.message?.includes('NetworkError')
                ) {
                    logger.warn('Network error, using localStorage only');
                    this.isOnline = false;
                    this.updateSaveIndicator('offline');
                } else {
                    logger.error('Backend save error:', error);
                    this.updateSaveIndicator('error');
                }
                // Still saved to localStorage, so user data is safe
            }
        } else {
            logger.warn('Offline: Draft saved to localStorage only');
            this.updateSaveIndicator('offline');
        }

        this.isSaving = false;
    }

    /**
     * Load draft from backend or localStorage
     */
    async loadDraft() {
        // First try backend
        if (this.isOnline) {
            try {
                const draftUrl = window.APIConfig?.admin?.loadDraft || '/admin/ilanlar/draft';
                const response = await fetch(draftUrl, {
                    method: 'GET',
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (response.ok) {
                    const result = await response.json();
                    if (result.success && result.data && Object.keys(result.data).length > 0) {
                        this.restoreDraft(result.data);
                        return;
                    }
                }
            } catch (error) {
                logger.warn('Backend draft load failed, trying localStorage:', error);
            }
        }

        // Fallback to localStorage
        const localDraft = this.loadFromLocalStorage();
        if (localDraft) {
            this.showDraftRestorePrompt(localDraft);
        }
    }

    /**
     * Show draft restore prompt
     * @param {Object} draft - Draft data with timestamp
     */
    showDraftRestorePrompt(draft) {
        const { data, timestamp } = draft;
        const savedDate = new Date(timestamp);
        const timeAgo = this.getTimeAgo(savedDate);

        // Create restore prompt
        const prompt = document.createElement('div');
        prompt.className =
            'fixed top-4 right-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 shadow-lg z-50 max-w-md';
        prompt.innerHTML = `
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                        Kaydedilmiş Taslak Bulundu
                    </h3>
                    <p class="text-xs text-gray-600 mb-3 dark:text-slate-400">
                        ${timeAgo} kaydedilmiş bir taslak var. Geri yüklemek ister misiniz?
                    </p>
                    <div class="flex gap-2">
                        <button id="restore-draft-btn"
                            class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition-all duration-200">
                            Geri Yükle
                        </button>
                        <button id="discard-draft-btn"
                            class="px-3 py-1.5 bg-gray-200 text-gray-700 text-xs rounded-lg hover:bg-gray-300 transition-all duration-200 dark:bg-slate-700 dark:text-slate-300">
                            Yoksay
                        </button>
                    </div>
                </div>
                <button id="close-draft-prompt"
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(prompt);

        // Restore button
        document.getElementById('restore-draft-btn').addEventListener('click', () => {
            this.restoreDraft(data);
            prompt.remove();
        });

        // Discard button
        document.getElementById('discard-draft-btn').addEventListener('click', () => {
            this.clearDraft();
            prompt.remove();
        });

        // Close button
        document.getElementById('close-draft-prompt').addEventListener('click', () => {
            prompt.remove();
        });

        // Auto-close after 10 seconds
        setTimeout(() => {
            if (prompt.parentNode) {
                prompt.remove();
            }
        }, 10000);
    }

    /**
     * Restore draft data to form
     * @param {Object} data - Draft data
     */
    restoreDraft(data) {
        if (!data || Object.keys(data).length === 0) {
            return;
        }

        // Restore form fields
        Object.keys(data).forEach((key) => {
            // Skip internal fields
            if (key.startsWith('_')) {
                return;
            }

            // Skip file inputs (cannot be programmatically set)
            if (key.includes('fotograflar') || key.includes('photo') || key.includes('file')) {
                return;
            }

            // Skip live search search fields (only restore the hidden ID field)
            if (key.endsWith('_search')) {
                return;
            }

            const field = this.form.querySelector(`[name="${key}"]`);
            if (!field) {
                return;
            }

            // Skip file input fields (cannot be programmatically set)
            if (field.type === 'file') {
                return;
            }

            // Handle different field types
            if (field.type === 'checkbox') {
                field.checked = Array.isArray(data[key])
                    ? data[key].includes(field.value)
                    : data[key] === field.value;
            } else if (field.type === 'radio') {
                if (field.value === data[key]) {
                    field.checked = true;
                }
            } else if (field.tagName === 'SELECT' && field.multiple) {
                // Multi-select
                const values = Array.isArray(data[key]) ? data[key] : [data[key]];
                Array.from(field.options).forEach((option) => {
                    option.selected = values.includes(option.value);
                });
            } else {
                try {
                    field.value = data[key];
                    // Trigger change event for cascade dropdowns
                    field.dispatchEvent(new Event('change', { bubbles: true }));
                } catch (error) {
                    // Skip fields that cannot be set (e.g., file inputs)
                    logger.debug(`⚠️ Cannot set value for field: ${key}`, error);
                }
            }
        });

        // Restore wizard step (try Alpine.js, fallback to manual)
        if (data._current_step) {
            try {
                if (window.Alpine) {
                    const wizardElement = document.querySelector('[x-data*="ilanWizard"]');
                    if (wizardElement) {
                        const wizardComponent = window.Alpine.$data(wizardElement);
                        if (wizardComponent && wizardComponent.goToStep) {
                            setTimeout(() => {
                                wizardComponent.goToStep(data._current_step);
                            }, 500);
                        }
                    }
                }
            } catch (error) {
                logger.warn('Could not restore wizard step:', error);
            }
        }

        this.lastSaveData = data;
        this.isDirty = false;
        this.updateSaveIndicator('restored');

        // Only show toast if actual data was restored (not empty draft)
        const hasRealData = Object.keys(data).filter((key) => !key.startsWith('_')).length > 0;
        if (hasRealData && window.toast) {
            window.toast.success('Taslak geri yüklendi');
        }

        logger.log('✅ Draft restored');
    }

    /**
     * Clear draft (localStorage + backend)
     */
    async clearDraft() {
        // Clear localStorage
        localStorage.removeItem('ilan_draft');
        localStorage.removeItem('ilan_draft_timestamp');

        // Clear backend
        if (this.isOnline) {
            try {
                const draftUrl = window.APIConfig?.admin?.clearDraft || '/admin/ilanlar/draft';
                await fetch(draftUrl, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN':
                            document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
            } catch (error) {
                logger.error('Backend draft clear error:', error);
            }
        }

        this.lastSaveData = null;
        this.isDirty = false;
        this.updateSaveIndicator('cleared');

        logger.log('✅ Draft cleared');
    }

    /**
     * Update save indicator
     * @param {string} status - 'saving', 'saved', 'unsaved', 'error', 'offline', 'restored', 'cleared'
     */
    updateSaveIndicator(status) {
        let indicator = document.getElementById('draft-save-indicator');
        if (!indicator) {
            // Create indicator if it doesn't exist
            indicator = document.createElement('div');
            indicator.id = 'draft-save-indicator';
            indicator.className =
                'fixed bottom-4 right-4 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 px-3 py-2 text-sm z-50 dark:bg-slate-900 dark:border-slate-700';
            document.body.appendChild(indicator);
        }

        const statusConfig = {
            saving: {
                text: '💾 Kaydediliyor...',
                color: 'text-blue-600 dark:text-blue-400',
            },
            saved: {
                text: '✅ Kaydedildi',
                color: 'text-green-600 dark:text-green-400',
            },
            unsaved: {
                text: '⚪ Kaydedilmemiş değişiklikler',
                color: 'text-gray-600 dark:text-gray-400',
            },
            error: {
                text: '❌ Kaydetme hatası',
                color: 'text-red-600 dark:text-red-400',
            },
            offline: {
                text: '📴 Çevrimdışı (Yerel kayıt)',
                color: 'text-yellow-600 dark:text-yellow-400',
            },
            restored: {
                text: '↩️ Taslak geri yüklendi',
                color: 'text-blue-600 dark:text-blue-400',
            },
            cleared: {
                text: '🗑️ Taslak temizlendi',
                color: 'text-gray-600 dark:text-gray-400',
            },
        };

        const config = statusConfig[status] || statusConfig.unsaved;
        indicator.innerHTML = `<span class="${config.color}">${config.text}</span>`;

        // Auto-hide after 3 seconds (except for unsaved)
        if (status !== 'unsaved' && status !== 'saving') {
            setTimeout(() => {
                if (indicator && status !== 'unsaved') {
                    indicator.style.opacity = '0';
                    setTimeout(() => {
                        if (indicator && status !== 'unsaved') {
                            indicator.remove();
                        }
                    }, 300);
                }
            }, 3000);
        } else {
            indicator.style.opacity = '1';
        }
    }

    /**
     * Get time ago string
     * @param {Date} date - Date to compare
     * @returns {string} Time ago string
     */
    getTimeAgo(date) {
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) {
            return 'Az önce';
        } else if (minutes < 60) {
            return `${minutes} dakika önce`;
        } else if (hours < 24) {
            return `${hours} saat önce`;
        } else {
            return `${days} gün önce`;
        }
    }
}

// Export to window for global access
if (typeof window !== 'undefined') {
    window.AutoSaveManager = AutoSaveManager;

    // Auto-initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            initializeAutoSave();
        });
    } else {
        initializeAutoSave();
    }

    function initializeAutoSave() {
        // Wait a bit for form to be ready
        setTimeout(() => {
            const form = document.getElementById('ilan-wizard-form');
            if (form && !window.autoSaveManager) {
                window.autoSaveManager = new AutoSaveManager('ilan-wizard-form');
                logger.log('✅ Auto-save system auto-initialized');
            }
        }, 500);
    }
}
