/**
 * Event Booking Manager - Alpine.js Component
 *
 * Context7: Yazlık rezervasyon ve etkinlik yönetimi için Alpine.js component
 * Tarih: 22 Kasım 2025
 *
 * Kullanım:
 * <div x-data="eventBookingManager(ilanId)">
 *   ...
 * </div>
 */

// Define eventBookingManager globally before Alpine.js initializes
if (typeof window.eventBookingManager === 'undefined') {
    window.eventBookingManager = function (ilanId = null) {
        return {
            ilanId: ilanId,
            events: [],
            currentMonth: new Date(),
            selectedDate: null,
            showCreateModal: false,
            editingEvent: null,
            formData: {
                event_type: 'booking',
                guest_name: '',
                guest_phone: '',
                guest_email: '',
                guest_count: 2,
                check_in: '',
                check_out: '',
                nights: 0,
                total_price: 0,
                status: 'pending',
                notes: '',
            },
            get currentMonthName() {
                return this.currentMonth.toLocaleDateString('tr-TR', {
                    month: 'long',
                    year: 'numeric',
                });
            },
            get calendarDays() {
                const year = this.currentMonth.getFullYear();
                const month = this.currentMonth.getMonth();
                const firstDay = new Date(year, month, 1);
                const lastDay = new Date(year, month + 1, 0);
                const startDay = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1;
                const days = [];
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                for (let i = startDay - 1; i >= 0; i--) {
                    const date = new Date(year, month, -i);
                    days.push(this.createDayObject(date, false));
                }
                for (let i = 1; i <= lastDay.getDate(); i++) {
                    const date = new Date(year, month, i);
                    days.push(this.createDayObject(date, true));
                }
                const remaining = 42 - days.length;
                for (let i = 1; i <= remaining; i++) {
                    const date = new Date(year, month + 1, i);
                    days.push(this.createDayObject(date, false));
                }
                return days;
            },
            get upcomingEvents() {
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                return this.events
                    .filter((e) => new Date(e.check_out) >= today)
                    .sort((a, b) => new Date(a.check_in) - new Date(b.check_in))
                    .slice(0, 5);
            },
            async init() {
                if (this.ilanId) {
                    await this.loadEvents();
                }
            },
            async loadEvents() {
                try {
                    const url = window.APIConfig ? window.APIConfig.admin.events.list(this.ilanId) : `/api/admin/ilanlar/${this.ilanId}/events`;
                    const response = await fetch(url);
                    if (response.ok) {
                        const data = await response.json();
                        this.events = data.events || [];
                    }
                } catch (error) {
                    console.error('Events yüklenemedi:', error);
                }
            },
            createDayObject(date, isCurrentMonth) {
                const dateStr = date.toISOString().split('T')[0];
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                date.setHours(0, 0, 0, 0);
                const bookedEvent = this.events.find(
                    (e) =>
                        e.event_type === 'booking' &&
                        e.status !== 'cancelled' &&
                        dateStr >= e.check_in &&
                        dateStr < e.check_out
                );
                const blockedEvent = this.events.find(
                    (e) =>
                        e.event_type === 'blocked' && dateStr >= e.check_in && dateStr < e.check_out
                );
                return {
                    date: dateStr,
                    dayNumber: date.getDate(),
                    isCurrentMonth: isCurrentMonth,
                    isToday: date.getTime() === today.getTime(),
                    isBooked: !!bookedEvent,
                    isBlocked: !!blockedEvent,
                    isSelected: this.selectedDate === dateStr,
                };
            },
            selectDate(day) {
                if (!day.isCurrentMonth) return;
                this.selectedDate = day.date;
                this.formData.check_in = day.date;
                this.showCreateModal = true;
            },
            previousMonth() {
                this.currentMonth = new Date(
                    this.currentMonth.getFullYear(),
                    this.currentMonth.getMonth() - 1,
                    1
                );
            },
            nextMonth() {
                this.currentMonth = new Date(
                    this.currentMonth.getFullYear(),
                    this.currentMonth.getMonth() + 1,
                    1
                );
            },
            calculateNights() {
                if (this.formData.check_in && this.formData.check_out) {
                    const start = new Date(this.formData.check_in);
                    const end = new Date(this.formData.check_out);
                    const diffTime = end - start;
                    this.formData.nights = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                } else {
                    this.formData.nights = 0;
                }
            },
            async saveEvent() {
                if (!this.formData.check_in || !this.formData.check_out) {
                    window.toast?.('Giriş ve çıkış tarihleri gerekli', 'error');
                    return;
                }
                if (this.formData.event_type === 'booking' && !this.formData.guest_name) {
                    window.toast?.('Misafir adı gerekli', 'error');
                    return;
                }
                const url = this.editingEvent
                    ? (window.APIConfig ? window.APIConfig.admin.events.update(this.editingEvent.id) : `/api/admin/events/${this.editingEvent.id}`)
                    : (window.APIConfig ? window.APIConfig.admin.events.create : '/api/admin/events');
                const method = this.editingEvent ? 'PATCH' : 'POST';
                try {
                    const response = await fetch(url, {
                        method: method,
                        headers: {
                            'X-CSRF-TOKEN':
                                document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            ...this.formData,
                            ilan_id: this.ilanId,
                        }),
                    });
                    if (response.ok) {
                        await this.loadEvents();
                        this.closeModal();
                        window.toast?.('Rezervasyon kaydedildi', 'success');
                    }
                } catch (error) {
                    console.error('Save error:', error);
                    window.toast?.('Kaydetme hatası', 'error');
                }
            },
            editEvent(event) {
                this.editingEvent = event;
                this.formData = { ...event };
                this.showCreateModal = true;
            },
            async deleteEvent(eventId) {
                if (!confirm('Bu rezervasyonu silmek istediğinize emin misiniz?')) return;
                try {
                    const url = window.APIConfig ? window.APIConfig.admin.events.delete(eventId) : `/api/admin/events/${eventId}`;
                    const response = await fetch(url, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN':
                                document.querySelector('meta[name="csrf-token"]').content,
                        },
                    });
                    if (response.ok) {
                        await this.loadEvents();
                        window.toast?.('Rezervasyon silindi', 'success');
                    }
                } catch (error) {
                    console.error('Delete error:', error);
                }
            },
            closeModal() {
                this.showCreateModal = false;
                this.editingEvent = null;
                this.formData = {
                    event_type: 'booking',
                    guest_name: '',
                    guest_phone: '',
                    guest_email: '',
                    guest_count: 2,
                    check_in: '',
                    check_out: '',
                    nights: 0,
                    total_price: 0,
                    status: 'pending',
                    notes: '',
                };
            },
            formatDate(dateStr) {
                if (!dateStr) return '';
                return new Date(dateStr).toLocaleDateString('tr-TR', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric',
                });
            },
            formatPrice(price) {
                return new Intl.NumberFormat('tr-TR', {
                    style: 'currency',
                    currency: 'TRY',
                }).format(price);
            },
        };
    };
}
