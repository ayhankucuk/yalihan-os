/**
 * Event Config - Frontend Event Configuration
 *
 * Context7 Standard: C7-EVENT-CONFIG-2025-12-06
 * Yalıhan Bekçi: Temiz, düzenli, merkezi yönetim
 *
 * Frontend'de event'leri dinlemek ve WebSocket/Pusher entegrasyonu için config.
 */

(function () {
    'use strict';

    if (typeof window.EventConfig === 'undefined') {
        window.EventConfig = {};
    }

    /**
     * Event metadata'ları (backend'den yüklenir)
     */
    window.EventConfig.definitions = window.EventConfig.definitions || {};

    /**
     * Event key'ini al
     *
     * @param {string} eventKey Event key (örn: 'ilan.created')
     * @returns {object|null} Event metadata
     */
    window.EventConfig.get = function (eventKey) {
        return this.definitions[eventKey] || null;
    };

    /**
     * Event'i dinle (WebSocket/Pusher için)
     *
     * @param {string} eventKey Event key
     * @param {function} callback Callback function
     * @returns {void}
     */
    window.EventConfig.listen = function (eventKey, callback) {
        const definition = this.get(eventKey);

        if (!definition) {
            console.warn(`Event tanımı bulunamadı: ${eventKey}`);
            return;
        }

        if (!definition.broadcast) {
            console.warn(`Event broadcast yapılmıyor: ${eventKey}`);
            return;
        }

        // WebSocket/Pusher entegrasyonu buraya eklenecek
        const channel = definition.broadcast_channel;

        if (window.Echo) {
            window.Echo.private(channel).listen(eventKey, callback);
        } else {
            console.warn('Echo (Laravel Echo) yüklenmemiş');
        }
    };

    /**
     * Kategoriye göre event'leri al
     *
     * @param {string} category Kategori adı
     * @returns {array} Event key'leri
     */
    window.EventConfig.getByCategory = function (category) {
        const events = [];

        for (const [key, definition] of Object.entries(this.definitions)) {
            if (definition.category === category) {
                events.push(key);
            }
        }

        return events;
    };

    /**
     * Tüm event'leri al
     *
     * @returns {array} Event key'leri
     */
    window.EventConfig.getAll = function () {
        return Object.keys(this.definitions);
    };

    /**
     * Event definitions'ı yükle (backend'den)
     *
     * @returns {Promise<void>}
     */
    window.EventConfig.load = async function () {
        try {
            const response = await fetch(
                (window.APIConfig && window.APIConfig.admin && window.APIConfig.admin.events && window.APIConfig.admin.events.definitions)
                    ? window.APIConfig.admin.events.definitions
                    : '/api/admin/events/definitions',
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                }
            );

            if (response.ok) {
                const data = await response.json();
                this.definitions = data.data?.definitions || {};
                console.log(
                    '✅ Event definitions loaded:',
                    Object.keys(this.definitions).length,
                    'events'
                );
            } else {
                console.warn('⚠️ Event definitions could not be loaded');
            }
        } catch (err) {
            console.warn('⚠️ Event definitions error:', err);
        }
    };

    // Sayfa yüklendiğinde otomatik yükle
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            window.EventConfig.load();
        });
    } else {
        window.EventConfig.load();
    }
})();
