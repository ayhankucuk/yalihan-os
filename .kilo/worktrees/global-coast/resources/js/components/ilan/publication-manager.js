/**
 * Publication Manager Alpine.js Component
 *
 * İlan yayın yaşam döngüsü yönetimi için Alpine.js bileşeni
 * - Yayın bitiş tarihi takibi
 * - Otomatik yenileme yönetimi
 * - Hatırlatma programlama
 * - Maliyet hesaplamaları
 */

document.addEventListener('alpine:init', () => {
    Alpine.data('publicationManager', (ilanId) => ({
        // State
        ilan: null,
        ilanId: ilanId,
        loading: false,
        error: null,

        // Publication data
        expiryDate: null,
        remainingDays: null,
        needsRenewal: false,
        expiringSoon: false,
        autoRenewal: false,
        reminderDates: [],
        renewalCount: 0,
        lastRenewalDate: null,
        publicationCost: null,
        publicationPeriod: 12,

        // UI state
        showRenewalModal: false,
        showReminderModal: false,
        showAutoRenewalSettings: false,
        renewalPeriod: 12,
        enableAutoRenewal: false,

        // Counters and timers
        countdownTimer: null,
        timeRemaining: {
            days: 0,
            hours: 0,
            minutes: 0,
            seconds: 0,
        },

        init() {
            this.loadPublicationData();
            this.startCountdownTimer();

            // Refresh data every 5 minutes
            setInterval(() => {
                this.loadPublicationData();
            }, 300000);
        },

        destroy() {
            if (this.countdownTimer) {
                clearInterval(this.countdownTimer);
            }
        },

        // ==================== DATA LOADING ====================

        async loadPublicationData() {
            this.loading = true;
            this.error = null;

            try {
                const url = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.ilanlar && window.APIConfig.admin.ilanlar.publication
                    ? window.APIConfig.admin.ilanlar.publication.expiryStatus(this.ilanId)
                    : `/api/ilanlar/${this.ilanId}/publication/expiry-status`;
                const response = await fetch(
                    url,
                    {
                        headers: {
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                        },
                    }
                );

                const data = await response.json();

                if (data.success) {
                    this.updatePublicationState(data.data);
                } else {
                    this.error = data.message || 'Veri yüklenirken hata oluştu';
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                console.error('Publication data loading error:', error);
            } finally {
                this.loading = false;
            }
        },

        updatePublicationState(data) {
            this.expiryDate = data.expiry_date;
            this.remainingDays = data.remaining_days;
            this.needsRenewal = data.needs_renewal;
            this.expiringSoon = data.expiring_soon;
            this.autoRenewal = data.auto_renewal_status;
            this.reminderDates = data.reminders || [];
            this.renewalCount = data.renewal_count || 0;
            this.lastRenewalDate = data.last_renewal_date;
            this.publicationCost = data.publication_cost;
            this.publicationPeriod = data.publication_period_months || 12;

            // UI state güncelle
            this.enableAutoRenewal = this.autoRenewal;
            this.renewalPeriod = this.publicationPeriod;
        },

        // ==================== COUNTDOWN TIMER ====================

        startCountdownTimer() {
            if (this.countdownTimer) {
                clearInterval(this.countdownTimer);
            }

            this.countdownTimer = setInterval(() => {
                this.updateCountdown();
            }, 1000);
        },

        updateCountdown() {
            if (!this.expiryDate) {
                this.timeRemaining = {
                    days: 0,
                    hours: 0,
                    minutes: 0,
                    seconds: 0,
                };
                return;
            }

            const now = new Date();
            const expiry = new Date(this.expiryDate);
            const diff = expiry - now;

            if (diff <= 0) {
                this.timeRemaining = {
                    days: 0,
                    hours: 0,
                    minutes: 0,
                    seconds: 0,
                };
                this.needsRenewal = true;
                return;
            }

            this.timeRemaining = {
                days: Math.floor(diff / (1000 * 60 * 60 * 24)),
                hours: Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)),
                minutes: Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60)),
                seconds: Math.floor((diff % (1000 * 60)) / 1000),
            };
        },

        // ==================== RENEWAL OPERATIONS ====================

        async renewListing() {
            this.loading = true;
            this.error = null;

            try {
                const urlRenew = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.ilanlar && window.APIConfig.admin.ilanlar.publication
                    ? window.APIConfig.admin.ilanlar.publication.renew(this.ilanId)
                    : `/api/ilanlar/${this.ilanId}/publication/renew`;
                const response = await fetch(urlRenew, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content'),
                    },
                    body: JSON.stringify({
                        period_months: this.renewalPeriod,
                        auto_renewal: this.enableAutoRenewal,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    await this.loadPublicationData();
                    this.showRenewalModal = false;
                    this.showSuccessMessage('İlan başarıyla yenilendi!');
                } else {
                    this.error = data.message || 'Yenileme işlemi başarısız';
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                console.error('Renewal error:', error);
            } finally {
                this.loading = false;
            }
        },

        async toggleAutoRenewal() {
            this.loading = true;
            this.error = null;

            try {
                const urlAuto = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.ilanlar && window.APIConfig.admin.ilanlar.publication
                    ? window.APIConfig.admin.ilanlar.publication.autoRenewal(this.ilanId)
                    : `/api/ilanlar/${this.ilanId}/publication/auto-renewal`;
                const response = await fetch(
                    urlAuto,
                    {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                        },
                        body: JSON.stringify({
                            status: this.enableAutoRenewal,
                            period_months: this.renewalPeriod,
                        }),
                    }
                );

                const data = await response.json();

                if (data.success) {
                    this.autoRenewal = this.enableAutoRenewal;
                    this.showAutoRenewalSettings = false;
                    this.showSuccessMessage('Otomatik yenileme ayarı güncellendi!');
                } else {
                    this.error = data.message || 'Ayar güncellenemedi';
                    this.enableAutoRenewal = this.autoRenewal; // Revert
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                this.enableAutoRenewal = this.autoRenewal; // Revert
                console.error('Auto renewal toggle error:', error);
            } finally {
                this.loading = false;
            }
        },

        // ==================== REMINDER OPERATIONS ====================

        async scheduleReminders() {
            this.loading = true;
            this.error = null;

            try {
                const urlSched = window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.ilanlar && window.APIConfig.admin.ilanlar.publication
                    ? window.APIConfig.admin.ilanlar.publication.scheduleReminders(this.ilanId)
                    : `/api/ilanlar/${this.ilanId}/publication/schedule-reminders`;
                const response = await fetch(
                    urlSched,
                    {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute('content'),
                        },
                    }
                );

                const data = await response.json();

                if (data.success) {
                    await this.loadPublicationData();
                    this.showReminderModal = false;
                    this.showSuccessMessage('Hatırlatmalar programlandı!');
                } else {
                    this.error = data.message || 'Hatırlatma programlama başarısız';
                }
            } catch (error) {
                this.error = 'Ağ hatası: ' + error.message;
                console.error('Reminder scheduling error:', error);
            } finally {
                this.loading = false;
            }
        },

        // ==================== UI HELPERS ====================

        getStatusClass() {
            if (this.needsRenewal) return 'bg-red-100 text-red-800';
            if (this.expiringSoon) return 'bg-yellow-100 text-yellow-800';
            return 'bg-green-100 text-green-800';
        },

        getStatusText() {
            if (this.needsRenewal) return 'Süresi Dolmuş';
            if (this.expiringSoon) return 'Yakında Sona Eriyor';
            return 'Aktif';
        },

        getRemainingTimeText() {
            if (this.needsRenewal) return 'Süresi dolmuş';
            if (this.remainingDays === null) return 'Belirsiz';
            if (this.remainingDays === 0) return 'Bugün sona eriyor';
            if (this.remainingDays === 1) return '1 gün kaldı';
            return `${this.remainingDays} gün kaldı`;
        },

        formatDate(dateString) {
            if (!dateString) return '-';
            return new Date(dateString).toLocaleDateString('tr-TR');
        },

        formatCurrency(amount) {
            if (!amount) return '-';
            return new Intl.NumberFormat('tr-TR', {
                style: 'currency',
                currency: 'TRY',
            }).format(amount);
        },

        showSuccessMessage(message) {
            // Alpine.js toast notification or similar
            if (window.toastr) {
                window.toastr.success(message);
            } else {
                alert(message);
            }
        },

        // ==================== MODAL CONTROLS ====================

        openRenewalModal() {
            this.renewalPeriod = this.publicationPeriod;
            this.enableAutoRenewal = this.autoRenewal;
            this.showRenewalModal = true;
        },

        closeRenewalModal() {
            this.showRenewalModal = false;
            this.error = null;
        },

        openReminderModal() {
            this.showReminderModal = true;
        },

        closeReminderModal() {
            this.showReminderModal = false;
            this.error = null;
        },

        openAutoRenewalSettings() {
            this.enableAutoRenewal = this.autoRenewal;
            this.renewalPeriod = this.publicationPeriod;
            this.showAutoRenewalSettings = true;
        },

        closeAutoRenewalSettings() {
            this.showAutoRenewalSettings = false;
            this.error = null;
        },

        // ==================== COMPUTED PROPERTIES ====================

        get isExpired() {
            return this.needsRenewal;
        },

        get isExpiringSoon() {
            return this.expiringSoon && !this.needsRenewal;
        },

        get isActive() {
            return !this.needsRenewal && !this.expiringSoon;
        },

        get hasReminders() {
            return this.reminderDates && this.reminderDates.length > 0;
        },

        get pendingReminders() {
            if (!this.reminderDates) return [];
            const today = new Date().toISOString().split('T')[0];
            return this.reminderDates.filter(
                (reminder) => reminder.date >= today && !reminder.sent
            );
        },

        get nextReminderDate() {
            const pending = this.pendingReminders;
            if (pending.length === 0) return null;
            return pending[0].date;
        },
    }));
});
