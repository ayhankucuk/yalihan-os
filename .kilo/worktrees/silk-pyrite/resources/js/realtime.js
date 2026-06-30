/**
 * Real-time Updates with WebSocket
 * Handles real-time notifications and updates
 */

class RealTimeManager {
    constructor() {
        this.pusher = null;
        this.channels = new Map();
        this.notifications = [];
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.reconnectDelay = 1000;

        this.init();
    }

    init() {
        // Check if Pusher is available
        if (typeof Pusher === 'undefined') {
            console.warn('Pusher not loaded. Real-time features disabled.');
            return;
        }

        this.pusher = new Pusher(process.env.MIX_PUSHER_APP_KEY, {
            cluster: process.env.MIX_PUSHER_APP_CLUSTER,
            encrypted: true,
            authEndpoint: '/broadcasting/auth',
            auth: {
                headers: {
                    'X-CSRF-Token': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content'),
                },
            },
        });

        this.setupEventListeners();
        this.setupChannels();
    }

    setupEventListeners() {
        // Connection events
        this.pusher.connection.bind('connected', () => {
            console.log('Real-time connection established');
            this.isConnected = true;
            this.reconnectAttempts = 0;
            this.showConnectionStatus('connected');
        });

        this.pusher.connection.bind('disconnected', () => {
            console.log('Real-time connection lost');
            this.isConnected = false;
            this.showConnectionStatus('disconnected');
            this.attemptReconnect();
        });

        this.pusher.connection.bind('error', (error) => {
            console.error('Real-time connection error:', error);
            this.showConnectionStatus('error');
        });
    }

    setupChannels() {
        // General notifications
        this.subscribeToChannel('general', (data) => {
            this.handleNotification(data);
        });

        // User-specific notifications
        if (window.userId) {
            this.subscribeToChannel(`user.${window.userId}`, (data) => {
                this.handleUserNotification(data);
            });
        }

        // Module-specific channels
        this.subscribeToChannel('ilan-updates', (data) => {
            this.handleIlanUpdate(data);
        });

        this.subscribeToChannel('satis-updates', (data) => {
            this.handleSatisUpdate(data);
        });

        this.subscribeToChannel('finans-updates', (data) => {
            this.handleFinansUpdate(data);
        });

        this.subscribeToChannel('dashboard-updates', (data) => {
            this.handleDashboardUpdate(data);
        });

        this.subscribeToChannel('system-notifications', (data) => {
            this.handleSystemNotification(data);
        });
    }

    subscribeToChannel(channelName, callback) {
        const channel = this.pusher.subscribe(channelName);

        channel.bind('real-time-event', (data) => {
            console.log(`Received data on channel ${channelName}:`, data);
            callback(data);
        });

        this.channels.set(channelName, channel);
    }

    handleNotification(data) {
        this.showNotification(data);
        this.updateNotificationCount();
    }

    handleUserNotification(data) {
        this.showNotification(data, 'user');
        this.updateNotificationCount();
    }

    handleIlanUpdate(data) {
        this.showNotification(data);
        this.updateIlanList();
    }

    handleSatisUpdate(data) {
        this.showNotification(data);
        this.updateSatisList();
    }

    handleFinansUpdate(data) {
        this.showNotification(data);
        this.updateFinansList();
    }

    handleDashboardUpdate(data) {
        this.updateDashboardStats(data.stats);
    }

    handleSystemNotification(data) {
        this.showNotification(data, 'system');
    }

    showNotification(data, type = 'general') {
        const notification = {
            id: Date.now(),
            title: data.title,
            message: data.message,
            type: data.type || 'info',
            timestamp: new Date(data.timestamp),
            action: data.action,
            data: data.data,
        };

        this.notifications.unshift(notification);
        this.displayNotification(notification);
        this.updateNotificationCount();
    }

    displayNotification(notification) {
        // Create notification element
        const notificationEl = document.createElement('div');
        notificationEl.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border-l-4 ${
            notification.type === 'error'
                ? 'border-red-500'
                : notification.type === 'success'
                  ? 'border-green-500'
                  : notification.type === 'warning'
                    ? 'border-yellow-500'
                    : 'border-blue-500'
        } transform transition-all duration-300 translate-x-full`;

        notificationEl.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 ${
                            notification.type === 'error'
                                ? 'text-red-400'
                                : notification.type === 'success'
                                  ? 'text-green-400'
                                  : notification.type === 'warning'
                                    ? 'text-yellow-400'
                                    : 'text-blue-400'
                        }"fill="currentColor"viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">${notification.title}</p>
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-500">${notification.message}</p>
                        <p class="mt-1 text-xs text-gray-400 dark:text-slate-600">${this.formatTime(
                            notification.timestamp
                        )}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-slate-900 dark:text-slate-600" onclick="this.parentElement.parentElement.parentElement.remove()">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Add to page
        document.body.appendChild(notificationEl);

        // Animate in
        setTimeout(() => {
            notificationEl.classList.remove('translate-x-full');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notificationEl.classList.add('translate-x-full');
            setTimeout(() => {
                if (notificationEl.parentNode) {
                    notificationEl.parentNode.removeChild(notificationEl);
                }
            }, 300);
        }, 5000);
    }

    updateNotificationCount() {
        const count = this.notifications.length;
        const countEl = document.querySelector('.notification-count');
        if (countEl) {
            countEl.textContent = count;
            countEl.style.display = count > 0 ? 'block' : 'none';
        }
    }

    updateDashboardStats(stats) {
        // Update dashboard statistics
        Object.keys(stats).forEach((key) => {
            const el = document.querySelector(`[data-stat="${key}"]`);
            if (el) {
                this.animateNumber(el, stats[key]);
            }
        });
    }

    updateIlanList() {
        // Refresh ilan list if on ilan page
        if (window.location.pathname.includes('/ilanlar')) {
            this.refreshTable();
        }
    }

    updateSatisList() {
        // Refresh satis list if on satis page
        if (window.location.pathname.includes('/satislar')) {
            this.refreshTable();
        }
    }

    updateFinansList() {
        // Refresh finans list if on finans page
        if (window.location.pathname.includes('/finans')) {
            this.refreshTable();
        }
    }

    refreshTable() {
        // Trigger table refresh
        const refreshBtn = document.querySelector('[data-refresh-table]');
        if (refreshBtn) {
            refreshBtn.click();
        }
    }

    animateNumber(element, newValue) {
        const currentValue = parseInt(element.textContent.replace(/[^\d]/g, ''));
        const increment = (newValue - currentValue) / 20;
        let current = currentValue;

        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= newValue) || (increment < 0 && current <= newValue)) {
                element.textContent = newValue.toLocaleString();
                clearInterval(timer);
            } else {
                element.textContent = Math.floor(current).toLocaleString();
            }
        }, 50);
    }

    showConnectionStatus(status) {
        const statusEl = document.querySelector('.connection-status');
        if (statusEl) {
            statusEl.className = `connection-status ${status}`;
            statusEl.textContent =
                status === 'connected'
                    ? 'Bağlı'
                    : status === 'disconnected'
                      ? 'Bağlantı Kesildi'
                      : 'Hata';
        }
    }

    attemptReconnect() {
        if (this.reconnectAttempts < this.maxReconnectAttempts) {
            this.reconnectAttempts++;
            setTimeout(() => {
                console.log(
                    `Attempting to reconnect... (${this.reconnectAttempts}/${this.maxReconnectAttempts})`
                );
                this.pusher.connect();
            }, this.reconnectDelay * this.reconnectAttempts);
        }
    }

    formatTime(date) {
        const now = new Date();
        const diff = now - date;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'Şimdi';
        if (minutes < 60) return `${minutes} dakika önce`;
        if (hours < 24) return `${hours} saat önce`;
        return `${days} gün önce`;
    }

    // Public methods
    getNotifications() {
        return this.notifications;
    }

    clearNotifications() {
        this.notifications = [];
        this.updateNotificationCount();
    }

    disconnect() {
        if (this.pusher) {
            this.pusher.disconnect();
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.realTimeManager = new RealTimeManager();
});

// Export for use in other scripts
window.RealTimeManager = RealTimeManager;
