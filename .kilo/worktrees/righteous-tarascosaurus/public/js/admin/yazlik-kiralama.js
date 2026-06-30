/**
 * Yazlık Kiralama Manager
 *
 * Context7 Standardı: C7-YAZLIK-KIRALAMA-JS-2025-12-06
 *
 * Frontend JavaScript for yazlık kiralama management
 */

document.addEventListener('DOMContentLoaded', function () {
    if (typeof window.APIConfig === 'undefined') {
        console.error('APIConfig is not defined. Make sure api-config.js is loaded.');
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    /**
     * Yazlık Kiralama API Helper
     */
    const YazlikKiralamaAPI = {
        /**
         * Get calendar data
         */
        async getCalendar(ilanId, startDate = null, endDate = null) {
            const params = new URLSearchParams();
            if (startDate) params.append('start_date', startDate);
            if (endDate) params.append('end_date', endDate);

            const url = window.APIConfig.yazlikKiralama.takvim(ilanId);
            const response = await fetch(`${url}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            return response.json();
        },

        /**
         * Calculate price
         */
        async calculatePrice(data) {
            const response = await fetch(window.APIConfig.yazlikKiralama.fiyatHesapla, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(data),
            });

            return response.json();
        },

        /**
         * Check availability
         */
        async checkAvailability(data) {
            const response = await fetch(window.APIConfig.yazlikKiralama.musaitlikKontrol, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(data),
            });

            return response.json();
        },

        /**
         * Create reservation
         */
        async createReservation(data) {
            const response = await fetch(window.APIConfig.yazlikKiralama.rezervasyon, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(data),
            });

            return response.json();
        },

        /**
         * Get pricing list
         */
        async getPricing(ilanId) {
            const response = await fetch(
                window.APIConfig.yazlikKiralama.fiyatlandirmaList(ilanId),
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                }
            );

            return response.json();
        },

        /**
         * Create pricing
         */
        async createPricing(data) {
            const response = await fetch(window.APIConfig.yazlikKiralama.fiyatlandirmaCreate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(data),
            });

            return response.json();
        },

        /**
         * Update pricing
         */
        async updatePricing(id, data) {
            const response = await fetch(window.APIConfig.yazlikKiralama.fiyatlandirmaUpdate(id), {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify(data),
            });

            return response.json();
        },

        /**
         * Delete pricing
         */
        async deletePricing(id) {
            const response = await fetch(window.APIConfig.yazlikKiralama.fiyatlandirmaDelete(id), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            return response.json();
        },
    };

    /**
     * Price Calculator
     */
    const PriceCalculator = {
        /**
         * Calculate and display price
         */
        async calculate(ilanId, checkIn, checkOut, misafirSayisi = null) {
            try {
                const result = await YazlikKiralamaAPI.calculatePrice({
                    ilan_id: ilanId,
                    check_in: checkIn,
                    check_out: checkOut,
                    misafir_sayisi: misafirSayisi,
                });

                if (result.success) {
                    return result.data;
                } else {
                    throw new Error(result.message || 'Fiyat hesaplama başarısız');
                }
            } catch (error) {
                console.error('Price calculation error:', error);
                throw error;
            }
        },
    };

    /**
     * Availability Checker
     */
    const AvailabilityChecker = {
        /**
         * Check and display availability
         */
        async check(ilanId, checkIn, checkOut, excludeReservationId = null) {
            try {
                const result = await YazlikKiralamaAPI.checkAvailability({
                    ilan_id: ilanId,
                    check_in: checkIn,
                    check_out: checkOut,
                    exclude_reservation_id: excludeReservationId,
                });

                if (result.success) {
                    return result.data;
                } else {
                    throw new Error(result.message || 'Müsaitlik kontrolü başarısız');
                }
            } catch (error) {
                console.error('Availability check error:', error);
                throw error;
            }
        },
    };

    /**
     * Calendar Manager
     */
    const CalendarManager = {
        /**
         * Load and display calendar
         */
        async load(ilanId, containerSelector, startDate = null, endDate = null) {
            try {
                const result = await YazlikKiralamaAPI.getCalendar(ilanId, startDate, endDate);

                if (result.success) {
                    this.render(result.data, containerSelector);
                    return result.data;
                } else {
                    throw new Error(result.message || 'Takvim yüklenemedi');
                }
            } catch (error) {
                console.error('Calendar load error:', error);
                throw error;
            }
        },

        /**
         * Render calendar
         */
        render(calendarData, containerSelector) {
            const container = document.querySelector(containerSelector);
            if (!container) {
                console.error(`Container not found: ${containerSelector}`);
                return;
            }

            // Calendar rendering logic here
            // This is a basic example, can be enhanced with a calendar library
            container.innerHTML = `
                <div class="calendar-wrapper">
                    <h3>${calendarData.ilan_baslik} - Müsaitlik Takvimi</h3>
                    <div class="calendar-grid">
                        ${calendarData.calendar
                            .map(
                                (day) => `
                            <div class="calendar-day ${day.is_booked ? 'booked' : 'available'}"
                                 data-date="${day.date}"
                                 data-price="${day.daily_price}">
                                <div class="date">${new Date(day.date).getDate()}</div>
                                <div class="price">${day.daily_price} ₺</div>
                                ${day.is_booked ? '<div class="status booked">Dolu</div>' : '<div class="status available">Müsait</div>'}
                            </div>
                        `
                            )
                            .join('')}
                    </div>
                </div>
            `;
        },
    };

    /**
     * Reservation Manager
     */
    const ReservationManager = {
        /**
         * Create reservation
         */
        async create(data) {
            try {
                const result = await YazlikKiralamaAPI.createReservation(data);

                if (result.success) {
                    return result.data;
                } else {
                    throw new Error(result.message || 'Rezervasyon oluşturulamadı');
                }
            } catch (error) {
                console.error('Reservation creation error:', error);
                throw error;
            }
        },
    };

    // Export to global scope
    window.YazlikKiralamaManager = {
        API: YazlikKiralamaAPI,
        PriceCalculator,
        AvailabilityChecker,
        CalendarManager,
        ReservationManager,
    };

    console.log('Yazlık Kiralama Manager initialized');
});
