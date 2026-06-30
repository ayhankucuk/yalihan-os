/**
 * Auto Save Manager - Form Otomatik Kaydetme
 * Context7 Uyumlu Draft Sistemi
 *
 * @version 1.0.0
 * @date 18 Ekim 2025
 * @context7-compliant
 */

class AutoSaveManager {
    constructor(formSelector = '#stable-create-form') {
        this.form = document.querySelector(formSelector);
        this.saveInterval = 30000; // 30 saniye
        this.lastSaveData = null;
        this.saveTimer = null;
        this.isDirty = false;
        this.isOnline = navigator.onLine;

        this.init();
    }

    init() {
        if (!this.form) {
            console.warn('AutoSave: Form bulunamadı');
            return;
        }

        this.setupEventListeners();
        this.loadDraftData();
        this.startAutoSave();

        console.log('✅ AutoSaveManager initialized');
    }

    setupEventListeners() {
        // Form değişikliklerini izle
        this.form.addEventListener('input', (e) => this.onFormChange(e));
        this.form.addEventListener('change', (e) => this.onFormChange(e));

        // Online/offline durumunu izle
        window.addEventListener('online', () => this.onOnlineStatusChange(true));
        window.addEventListener('offline', () => this.onOnlineStatusChange(false));

        // Sayfa kapatılmadan önce uyar
        window.addEventListener('beforeunload', (e) => this.onBeforeUnload(e));

        // Periyodik kaydetme butonu
        const saveButton = document.querySelector('#saveDraftBtn');
        if (saveButton) {
            saveButton.addEventListener('click', () => this.saveDraft(true));
        }
    }

    onFormChange(event) {
        this.isDirty = true;
        this.resetSaveTimer();

        // Visual feedback
        this.showUnsavedChanges();
    }

    onOnlineStatusChange(isOnline) {
        this.isOnline = isOnline;

        if (isOnline && this.isDirty) {
            // Online olunca bekleyen değişiklikleri kaydet
            setTimeout(() => this.saveDraft(), 1000);
        }

        this.updateConnectionStatus();
    }

    onBeforeUnload(event) {
        if (this.isDirty) {
            event.preventDefault();
            event.returnValue =
                'Kaydedilmemiş değişiklikleriniz var. Sayfadan çıkmak istediğinizden emin misiniz?';
            return event.returnValue;
        }
    }

    startAutoSave() {
        this.resetSaveTimer();
    }

    resetSaveTimer() {
        if (this.saveTimer) {
            clearTimeout(this.saveTimer);
        }

        this.saveTimer = setTimeout(() => {
            if (this.isDirty) {
                this.saveDraft();
            }
        }, this.saveInterval);
    }

    async saveDraft(manual = false) {
        if (!this.isOnline && !manual) {
            console.log('💾 Offline mode: Draft saved locally');
            this.saveToLocalStorage();
            return;
        }

        const formData = this.getFormData();

        // Değişiklik yoksa kaydetme
        if (this.lastSaveData && JSON.stringify(formData) === JSON.stringify(this.lastSaveData)) {
            return;
        }

        try {
            this.showSaveIndicator('saving');

            const response = await fetch('/admin/ilanlar/save-draft', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    draft_data: formData,
                    draft_key: this.getDraftKey(),
                }),
            });

            if (response.ok) {
                const result = await response.json();

                this.lastSaveData = formData;
                this.isDirty = false;

                this.showSaveIndicator('saved');
                this.saveToLocalStorage(); // Backup için local'e de kaydet

                if (manual) {
                    this.showToast('Taslak başarıyla kaydedildi', 'success');
                }

                console.log('💾 Draft saved successfully', result);
            } else {
                throw new Error(`Save failed: ${response.status}`);
            }
        } catch (error) {
            console.error('💾 Draft save error:', error);

            // Hata durumunda local storage'a kaydet
            this.saveToLocalStorage();
            this.showSaveIndicator('error');

            if (manual) {
                this.showToast('Taslak kaydedilemedi, yerel olarak saklandı', 'warning');
            }
        }
    }

    async loadDraftData() {
        try {
            // Önce sunucudan dene
            const response = await fetch(`/admin/ilanlar/load-draft?key=${this.getDraftKey()}`, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (response.ok) {
                const result = await response.json();

                // Handle both response formats for compatibility
                const draftData = result.draft_data || result.data;
                if (draftData) {
                    this.fillForm(draftData);
                    this.showDraftLoadedMessage();
                    return;
                }
            }
        } catch (error) {
            console.warn('Draft load from server failed:', error);
        }

        // Sunucudan yüklenemezse local storage'dan dene
        this.loadFromLocalStorage();
    }

    getFormData() {
        const formData = new FormData(this.form);
        const data = {};

        // Normal form fields
        for (const [key, value] of formData.entries()) {
            if (data[key]) {
                // Multiple values (checkbox arrays)
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }

        // File inputs özel olarak işle
        const fileInputs = this.form.querySelectorAll('input[type="file"]');
        fileInputs.forEach((input) => {
            if (input.files.length > 0) {
                data[input.name + '_info'] = {
                    count: input.files.length,
                    names: Array.from(input.files).map((f) => f.name),
                };
            }
        });

        return data;
    }

    fillForm(data) {
        Object.entries(data).forEach(([key, value]) => {
            const field = this.form.querySelector(`[name="${key}"]`);

            if (!field) return;

            if (field.type === 'checkbox') {
                if (Array.isArray(value)) {
                    // Multiple checkboxes
                    const checkboxes = this.form.querySelectorAll(`[name="${key}"]`);
                    checkboxes.forEach((cb) => {
                        cb.checked = value.includes(cb.value);
                    });
                } else {
                    field.checked = value === 'on' || value === true;
                }
            } else if (field.type === 'radio') {
                const radio = this.form.querySelector(`[name="${key}"][value="${value}"]`);
                if (radio) radio.checked = true;
            } else if (field.tagName === 'SELECT') {
                field.value = value;
            } else if (field.type !== 'file') {
                field.value = value;
            }
        });
    }

    saveToLocalStorage() {
        try {
            const data = this.getFormData();
            localStorage.setItem(
                this.getDraftKey(),
                JSON.stringify({
                    data,
                    timestamp: Date.now(),
                })
            );
        } catch (error) {
            console.error('Local storage save failed:', error);
        }
    }

    loadFromLocalStorage() {
        try {
            const stored = localStorage.getItem(this.getDraftKey());
            if (stored) {
                const parsed = JSON.parse(stored);
                const age = Date.now() - parsed.timestamp;

                // 24 saatten eski taslakları gösterme
                if (age < 24 * 60 * 60 * 1000) {
                    this.fillForm(parsed.data);
                    this.showDraftLoadedMessage('local');
                } else {
                    localStorage.removeItem(this.getDraftKey());
                }
            }
        } catch (error) {
            console.error('Local storage load failed:', error);
        }
    }

    getDraftKey() {
        const userId = window.auth?.user?.id || 'anonymous';
        return `create_form_draft_${userId}`;
    }

    showSaveIndicator(status) {
        const indicator = document.querySelector('#saveIndicator') || this.createSaveIndicator();

        indicator.className = 'save-indicator';
        indicator.classList.add(`save-${status}`);

        switch (status) {
            case 'saving':
                indicator.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
                break;
            case 'saved':
                indicator.innerHTML = '<i class="fas fa-check text-green-600"></i> Kaydedildi';
                setTimeout(() => (indicator.style.opacity = '0'), 2000);
                break;
            case 'error':
                indicator.innerHTML =
                    '<i class="fas fa-exclamation-triangle text-red-600"></i> Hata';
                break;
        }

        indicator.style.opacity = '1';
    }

    createSaveIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'saveIndicator';
        indicator.className = 'save-indicator';
        indicator.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            padding: 8px 16px;
            border-radius: 6px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1000;
        `;
        document.body.appendChild(indicator);
        return indicator;
    }

    showUnsavedChanges() {
        document.title = '● ' + document.title.replace('● ', '');
    }

    showDraftLoadedMessage(source = 'server') {
        const message =
            source === 'local' ? 'Yerel taslak yüklendi' : 'Kaydedilmiş taslak yüklendi';

        this.showToast(message, 'info');
    }

    updateConnectionStatus() {
        const statusEl = document.querySelector('#connectionStatus');
        if (statusEl) {
            statusEl.textContent = this.isOnline ? 'Çevrimiçi' : 'Çevrimdışı';
            statusEl.className = this.isOnline ? 'text-green-600' : 'text-orange-600';
        }
    }

    showToast(message, type = 'info') {
        // Context7 toast sistemi kullan
        if (window.showToast) {
            window.showToast(message, type);
        } else {
            console.log(`Toast (${type}): ${message}`);
        }
    }

    // Temizlik metodu
    clearDraft() {
        localStorage.removeItem(this.getDraftKey());
        this.isDirty = false;
        this.lastSaveData = null;
        document.title = document.title.replace('● ', '');
    }
}

// Auto-initialize
document.addEventListener('DOMContentLoaded', () => {
    new AutoSaveManager();
});

// Global export
window.AutoSaveManager = AutoSaveManager;
