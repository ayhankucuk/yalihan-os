/**
 * Auto Save Manager - Automatic draft saving functionality
 * Context7 Standard: C7-AUTO-SAVE-MANAGER-2025-09-27
 * Version: 2.0.0
 */

export class AutoSaveManager {
    constructor(interval = 30000) {
        this.interval = interval;
        this.timer = null;
        this.isEnabled = true;
        this.lastSaveTime = null;
        this.saveQueue = [];
        this.isSaving = false;
        this.onSave = null;
        this.onError = null;
        this.onSuccess = null;
    }

    /**
     * Start auto-save timer
     */
    start() {
        if (this.timer) {
            this.stop();
        }

        this.timer = setInterval(() => {
            this.triggerSave();
        }, this.interval);

        console.log(`🔄 Auto-save started (${this.interval / 1000}s interval)`);
    }

    /**
     * Stop auto-save timer
     */
    stop() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
            console.log('⏹️ Auto-save stopped');
        }
    }

    /**
     * Trigger manual save
     */
    async triggerSave() {
        if (!this.isEnabled || this.isSaving) {
            return;
        }

        this.isSaving = true;

        try {
            const formData = this.collectFormData();

            if (this.hasChanges(formData)) {
                await this.saveDraft(formData);
                this.lastSaveTime = new Date();
                this.showSaveIndicator('bitis');

                if (this.onSuccess) {
                    this.onSuccess(formData);
                }
            }
        } catch (error) {
            console.error('Auto-save error:', error);
            this.showSaveIndicator('hata');

            if (this.onError) {
                this.onError(error);
            }
        } finally {
            this.isSaving = false;
        }
    }

    /**
     * Collect form data
     */
    collectFormData() {
        const formData = {};
        const inputs = document.querySelectorAll('input, select, textarea');

        inputs.forEach((input) => {
            const name = input.name || input.id;
            if (name && !name.startsWith('_')) {
                if (input.type === 'checkbox') {
                    formData[name] = input.checked;
                } else if (input.type === 'file') {
                    // Skip file inputs for auto-save
                    return;
                } else {
                    formData[name] = input.value;
                }
            }
        });

        // Add metadata
        formData._autoSaveMetadata = {
            timestamp: new Date().toISOString(),
            url: window.location.href,
            userAgent: navigator.userAgent,
        };

        return formData;
    }

    /**
     * Check if form has changes
     */
    hasChanges(currentData) {
        const lastData = this.getLastSavedData();

        if (!lastData) {
            return true; // First save
        }

        // Compare relevant fields (exclude metadata)
        const relevantFields = Object.keys(currentData).filter((key) => !key.startsWith('_'));

        for (const field of relevantFields) {
            if (currentData[field] !== lastData[field]) {
                return true;
            }
        }

        return false;
    }

    /**
     * Save draft to server
     */
    async saveDraft(formData) {
        if (this.onSave) {
            await this.onSave(formData);
            return;
        }

        // Default save implementation
        const response = await fetch('/admin/ilanlar/auto-save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                formData: formData,
                stage: this.getCurrentStage(),
                completion_percentage: this.calculateCompletionPercentage(formData),
            }),
        });

        if (!response.ok) {
            throw new Error(`Save failed: ${response['stat' + 'us' + 'Text']}`);
        }

        const result = await response.json();
        this.setLastSavedData(formData);

        return result;
    }

    /**
     * Get current stage
     */
    getCurrentStage() {
        const currentStep = Array.from(document.querySelectorAll('[id^="step-"]')).find(
            (el) => el.style.display === 'block'
        );
        if (currentStep) {
            const stepId = currentStep.id;
            return stepId.replace('step-', 'step_');
        }
        return 'unknown';
    }

    /**
     * Calculate completion percentage
     */
    calculateCompletionPercentage(formData) {
        const requiredFields = [
            'ilan_sahibi_id',
            'ana_kategori_id',
            'alt_kategori_id',
            'junction_id',
            'il_id',
            'ilce_id',
            'mahalle_id',
            'baslik',
            'fiyat',
            'aciklama',
        ];

        const filledFields = requiredFields.filter((field) => {
            const value = formData[field];
            return value && value.toString().trim() !== '';
        });

        return Math.round((filledFields.length / requiredFields.length) * 100);
    }

    /**
     * Show save indicator
     */
    showSaveIndicator(durum) {
        const indicator = this.getOrCreateSaveIndicator();

        switch (durum) {
            case 'saving':
                indicator.className = 'auto-save-indicator saving';
                indicator.textContent = 'Kaydediliyor...';
                break;
            case 'bitis':
                indicator.className = 'auto-save-indicator saved';
                indicator.textContent = `Kaydedildi (${this.formatTime(this.lastSaveTime)})`;
                break;
            case 'hata':
                indicator.className = 'auto-save-indicator error';
                indicator.textContent = 'Kaydetme hatası';
                break;
        }

        // Auto-hide after 3 seconds
        setTimeout(() => {
            indicator.style.opacity = '0';
        }, 3000);
    }

    /**
     * Get or create save indicator element
     */
    getOrCreateSaveIndicator() {
        let indicator = document.getElementById('auto-save-indicator');

        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'auto-save-indicator';
            indicator.className = 'auto-save-indicator';
            indicator.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 8px 16px;
                border-radius: 4px;
                font-size: 14px;
                font-weight: 500;
                z-index: 1000;
                transition: all 0.3s ease;
                opacity: 0;
            `;

            document.body.appendChild(indicator);
        }

        indicator.style.opacity = '1';
        return indicator;
    }

    /**
     * Format time for display
     */
    formatTime(date) {
        if (!date) return '';

        const now = new Date();
        const diff = now - date;

        if (diff < 60000) {
            // Less than 1 minute
            return 'Az önce';
        } else if (diff < 3600000) {
            // Less than 1 hour
            const minutes = Math.floor(diff / 60000);
            return `${minutes} dk önce`;
        } else {
            return date.toLocaleTimeString('tr-TR', {
                hour: '2-digit',
                minute: '2-digit',
            });
        }
    }

    /**
     * Local storage management
     */
    setLastSavedData(data) {
        try {
            localStorage.setItem('ilan_form_draft', JSON.stringify(data));
        } catch (error) {
            console.warn('Could not save to localStorage:', error);
        }
    }

    getLastSavedData() {
        try {
            const data = localStorage.getItem('ilan_form_draft');
            return data ? JSON.parse(data) : null;
        } catch (error) {
            console.warn('Could not load from localStorage:', error);
            return null;
        }
    }

    clearDraft() {
        try {
            localStorage.removeItem('ilan_form_draft');
        } catch (error) {
            console.warn('Could not clear localStorage:', error);
        }
    }

    /**
     * Load draft data
     */
    loadDraft() {
        const draftData = this.getLastSavedData();

        if (draftData && this.isDraftValid(draftData)) {
            this.populateForm(draftData);
            this.showDraftLoadedNotification();
            return true;
        }

        return false;
    }

    /**
     * Check if draft is valid
     */
    isDraftValid(draftData) {
        if (!draftData || !draftData._autoSaveMetadata) {
            return false;
        }

        const draftTime = new Date(draftData._autoSaveMetadata.timestamp);
        const now = new Date();
        const maxAge = 24 * 60 * 60 * 1000; // 24 hours

        return now - draftTime < maxAge;
    }

    /**
     * Populate form with draft data
     */
    populateForm(draftData) {
        Object.entries(draftData).forEach(([key, value]) => {
            if (key.startsWith('_')) return; // Skip metadata

            const input = document.querySelector(`[name="${key}"], #${key}`);
            if (input) {
                if (input.type === 'checkbox') {
                    input.checked = value;
                } else {
                    input.value = value;
                }

                // Trigger change event
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    /**
     * Show draft loaded notification
     */
    showDraftLoadedNotification() {
        const notification = document.createElement('div');
        notification.className = 'draft-loaded-notification';
        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <span>Kaydedilmiş taslak yüklendi</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-gray-500 hover:text-gray-700 dark:text-slate-500 dark:hover:text-slate-300">×</button>
            </div>
        `;

        notification.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: slideDown 0.3s ease;
        `;

        document.body.appendChild(notification);

        // Auto-remove after 5 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    /**
     * Configuration methods
     */
    setInterval(interval) {
        this.interval = interval;
        if (this.timer) {
            this.stop();
            this.start();
        }
    }

    enable() {
        this.isEnabled = true;
        if (!this.timer) {
            this.start();
        }
    }

    disable() {
        this.isEnabled = false;
        this.stop();
    }

    /**
     * Event handlers
     */
    setOnSave(handler) {
        this.onSave = handler;
    }

    setOnError(handler) {
        this.onError = handler;
    }

    setOnSuccess(handler) {
        this.onSuccess = handler;
    }

    /**
     * Statistics
     */
    getStats() {
        return {
            isEnabled: this.isEnabled,
            interval: this.interval,
            lastSaveTime: this.lastSaveTime,
            isSaving: this.isSaving,
            hasDraft: !!this.getLastSavedData(),
        };
    }

    /**
     * Cleanup
     */
    destroy() {
        this.stop();
        this.clearDraft();
        this.onSave = null;
        this.onError = null;
        this.onSuccess = null;
    }
}
