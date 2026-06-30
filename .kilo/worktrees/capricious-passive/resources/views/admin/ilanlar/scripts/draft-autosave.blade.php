    <!-- =================================== -->
    <!-- Draft Auto-save System -->
    <!-- Context7: %100, Yalıhan Bekçi: ✅ -->
    <!-- =================================== -->
    <script>
        const DraftAutoSave = {
            formId: 'ilan-create-form',
            interval: null,
            saveIntervalSeconds: 30,
            hasChanges: false,

            init() {
                this.checkForDraft();
                this.startAutoSave();
                this.preventDataLoss();
                this.trackChanges();
                this.updateProgressBar(); // Initial progress
            },

            checkForDraft() {
                const draft = this.loadDraft();

                if (draft && draft.timestamp) {
                    const draftAge = Date.now() - draft.timestamp;
                    const hours = Math.floor(draftAge / (1000 * 60 * 60));
                    const minutes = Math.floor((draftAge % (1000 * 60 * 60)) / (1000 * 60));

                    this.showRestoreButton(draft, hours, minutes);
                }
            },

            showRestoreButton(draft, hours, minutes) {
                const timeAgo = hours > 0 ?
                    `${hours} saat ${minutes} dakika önce` :
                    `${minutes} dakika önce`;

                const banner = document.createElement('div');
                banner.className =
                    'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500 p-4 mb-6 rounded-lg flex items-center justify-between animate-pulse';
                banner.innerHTML = `
            <div class="flex items-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                        💾 Kaydedilmemiş taslak bulundu
                    </p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                        Son kayıt: ${timeAgo}
                    </p>
                </div>
            </div>
            <div class="flex gap-2">
                <button type="button" onclick="DraftAutoSave.restoreDraft()"
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 hover:scale-105 focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                    Geri Yükle
                </button>
                <button type="button" onclick="DraftAutoSave.discardDraft()"
                        class="px-4 py-2 bg-gray-200 dark:bg-slate-900 text-gray-900 dark:text-white text-sm font-medium rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors dark:text-slate-100">
                    Sil
                </button>
            </div>
        `;

                const container = document.getElementById('draft-restore-banner');
                if (container) {
                    container.appendChild(banner);
                } else {
                    // Create container if it doesn't exist
                    const form = document.getElementById('ilan-create-form');
                    if (form) {
                        const newContainer = document.createElement('div');
                        newContainer.id = 'draft-restore-banner';
                        form.insertBefore(newContainer, form.firstChild);
                        newContainer.appendChild(banner);
                    }
                }
            },

            startAutoSave() {
                this.interval = setInterval(() => {
                    if (this.hasChanges) {
                        this.saveDraft();
                    }
                }, this.saveIntervalSeconds * 1000);

                console.log('✅ Auto-save başlatıldı (her 30 saniyede)');
            },

            saveDraft() {
                try {
                    const form = document.getElementById(this.formId);
                    if (!form) return;

                    const formData = new FormData(form);
                    const data = {};

                    formData.forEach((value, key) => {
                        if (value && value !== '') {
                            data[key] = value;
                        }
                    });

                    const draft = {
                        data: data,
                        timestamp: Date.now(),
                        version: '1.0',
                    };

                    localStorage.setItem('ilan_draft', JSON.stringify(draft));

                    console.log('✅ Draft saved:', new Date().toLocaleTimeString());

                    this.showSaveIndicator();
                    this.hasChanges = false; // Reset after save

                } catch (error) {
                    console.error('❌ Draft save error:', error);
                }
            },

            loadDraft() {
                try {
                    const draftJson = localStorage.getItem('ilan_draft');
                    return draftJson ? JSON.parse(draftJson) : null;
                } catch (error) {
                    console.error('❌ Draft load error:', error);
                    return null;
                }
            },

            restoreDraft() {
                const draft = this.loadDraft();
                if (!draft || !draft.data) return;

                let restoredCount = 0;

                Object.entries(draft.data).forEach(([key, value]) => {
                    const field = document.querySelector(`[name="${key}"]`);
                    if (field) {
                        if (field.type === 'checkbox') {
                            field.checked = value === 'on' || value === '1' || value === 1;
                        } else if (field.type === 'radio') {
                            if (field.value === value) {
                                field.checked = true;
                            }
                        } else {
                            field.value = value;
                        }

                        // Trigger change event (Alpine.js reactivity)
                        field.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                        restoredCount++;
                    }
                });

                window.toast?.success(`Taslak geri yüklendi (${restoredCount} alan)`);
                const bannerContainer = document.getElementById('draft-restore-banner');
                if (bannerContainer) {
                    bannerContainer.innerHTML = '';
                }
                this.updateProgressBar();
            },

            discardDraft() {
                localStorage.removeItem('ilan_draft');
                const bannerContainer = document.getElementById('draft-restore-banner');
                if (bannerContainer) {
                    bannerContainer.innerHTML = '';
                }
                window.toast?.success('Taslak silindi');
            },

            clearDraft() {
                localStorage.removeItem('ilan_draft');
                this.hasChanges = false;
                console.log('✅ Draft cleared');
            },

            preventDataLoss() {
                window.addEventListener('beforeunload', (e) => {
                    if (this.hasChanges) {
                        e.preventDefault();
                        e.returnValue =
                            'Kaydedilmemiş değişiklikler var! Sayfadan ayrılmak istediğinize emin misiniz?';
                    }
                });
            },

            trackChanges() {
                const form = document.getElementById(this.formId);
                if (!form) return;

                form.addEventListener('input', () => {
                    this.hasChanges = true;
                });

                form.addEventListener('change', () => {
                    this.hasChanges = true;
                    this.updateProgressBar();
                });

                form.addEventListener('submit', () => {
                    this.hasChanges = false;
                    this.clearDraft();
                });
            },

            showSaveIndicator() {
                const indicator = document.getElementById('save-indicator');
                if (!indicator) return;

                indicator.innerHTML = `
            <svg class="w-3 h-3 mr-1 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span class="text-green-600">Kaydedildi ✓</span>
        `;

                setTimeout(() => {
                    indicator.innerHTML = `
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Otomatik kayıt aktif
            `;
                }, 2000);
            },

            getProgress() {
                const form = document.getElementById(this.formId);
                if (!form) return 0;

                const requiredFields = form.querySelectorAll('[required]');
                if (requiredFields.length === 0) return 0;

                const filledFields = Array.from(requiredFields).filter(field => {
                    if (field.type === 'checkbox') return field.checked;
                    return field.value && field.value.trim() !== '';
                });

                return Math.round((filledFields.length / requiredFields.length) * 100);
            },

            updateProgressBar() {
                const progress = this.getProgress();
                const progressBar = document.getElementById('form-progress-bar');
                const progressText = document.getElementById('form-progress-text');

                if (progressBar) {
                    progressBar.style.width = `${progress}%`;
                    progressBar.className =
                        `h-full rounded-full transition-all duration-500 ${this.getProgressColor(progress)}`;
                }

                if (progressText) {
                    progressText.textContent = `%${progress} tamamlandı`;
                }
            },

            getProgressColor(progress) {
                if (progress < 33) return 'bg-red-500';
                if (progress < 66) return 'bg-yellow-500';
                return 'bg-green-500';
            }
        };

        // Initialize Draft Auto-save on page load
        document.addEventListener('DOMContentLoaded', () => {
            DraftAutoSave.init();
            console.log('✅ Draft Auto-save initialized');
        });
    </script>
