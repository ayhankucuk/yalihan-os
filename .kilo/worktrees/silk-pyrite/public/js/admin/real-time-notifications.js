/**
 * Real-time Notification System
 * Pusher.js ile real-time bildirim yönetimi
 */

class RealTimeNotifications {
    constructor() {
        this.pusher = null;
        this.channel = null;
        this.userId = null;
        this.isInitialized = false;

        this.init();
    }

    /**
     * Initialize Pusher connection
     */
    init() {
        // Get user ID from meta tag or global variable
        this.userId = this.getUserId();

        if (!this.userId) {
            console.warn('User ID not found, real-time notifications disabled');
            return;
        }

        // Check if Pusher app key is available
        if (!window.PUSHER_APP_KEY || window.PUSHER_APP_KEY === '') {
            console.info(
                'Pusher app key not configured, real-time notifications disabled. To enable, configure PUSHER_APP_KEY in .env file.'
            );
            return;
        }

        // Initialize Pusher
        this.pusher = new Pusher(window.PUSHER_APP_KEY, {
            cluster: window.PUSHER_APP_CLUSTER,
            encrypted: true,
        });

        // Subscribe to user's notification channel
        this.channel = this.pusher.subscribe(`user.${this.userId}.notifications`);

        this.bindEvents();
        this.isInitialized = true;

        console.log('Real-time notifications initialized for user:', this.userId);
    }

    /**
     * Bind Pusher events
     */
    bindEvents() {
        if (!this.channel) return;

        // New notification received
        this.channel.bind('notification.received', (data) => {
            this.handleNewNotification(data);
        });

        // Notification marked as read
        this.channel.bind('notification.read', (data) => {
            this.handleNotificationRead(data);
        });

        // All notifications marked as read
        this.channel.bind('notifications.all_read', (data) => {
            this.handleAllNotificationsRead(data);
        });

        // Notification deleted
        this.channel.bind('notification.deleted', (data) => {
            this.handleNotificationDeleted(data);
        });

        // Test notification
        this.channel.bind('notification.test', (data) => {
            this.handleTestNotification(data);
        });
    }

    /**
     * Handle new notification
     */
    handleNewNotification(data) {
        console.log('New notification received:', data);

        // Show browser notification if permission granted
        this.showBrowserNotification(data);

        // Update notification count in header
        this.updateNotificationCount(data.unread_count);

        // Add notification to list if on notifications page
        this.addNotificationToList(data);

        // Show toast notification
        this.showToastNotification(data);
    }

    /**
     * Handle notification marked as read
     */
    handleNotificationRead(data) {
        console.log('Notification marked as read:', data);

        // Update notification count
        this.updateNotificationCount(data.unread_count);

        // Update notification in list
        this.updateNotificationInList(data.id, { read_at: data.read_at });
    }

    /**
     * Handle all notifications marked as read
     */
    handleAllNotificationsRead(data) {
        console.log('All notifications marked as read:', data);

        // Update notification count
        this.updateNotificationCount(data.unread_count);

        // Update all notifications in list
        this.markAllNotificationsAsRead();
    }

    /**
     * Handle notification deleted
     */
    handleNotificationDeleted(data) {
        console.log('Notification deleted:', data);

        // Update notification count
        this.updateNotificationCount(data.unread_count);

        // Remove notification from list
        this.removeNotificationFromList(data.id);
    }

    /**
     * Handle test notification
     */
    handleTestNotification(data) {
        console.log('Test notification received:', data);
        this.showToastNotification({
            type: 'success',
            title: 'Test Bildirimi',
            message: data.message,
        });
    }

    /**
     * Show browser notification
     */
    showBrowserNotification(data) {
        if (!('Notification' in window)) {
            return;
        }

        if (Notification.permission === 'granted') {
            const notification = new Notification(data.title, {
                body: data.message,
                icon: '/favicon.ico',
                tag: `notification-${data.id}`,
            });

            notification.onclick = () => {
                window.focus();
                window.location.href = '/admin/notifications';
                notification.close();
            };

            // Auto close after 5 seconds
            setTimeout(() => {
                notification.close();
            }, 5000);
        }
    }

    /**
     * Show toast notification
     */
    showToastNotification(data) {
        // Create toast element
        const toast = document.createElement('div');
        toast.className=`fixed top-4 right-4 z-50 max-w-sm w-full bg-white shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden notification-toast dark:bg-slate-900`;

        const typeColors = {
            success: 'bg-green-50 border-green-200',
            warning: 'bg-yellow-50 border-yellow-200',
            error: 'bg-red-50 border-red-200',
            info: 'bg-blue-50 border-blue-200',
        };

        const typeIcons = {
            success: 'M5 13l4 4L19 7',
            warning:
                'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 18.5c-.77.833.192 2.5 1.732 2.5z',
            error: 'M6 18L18 6M6 6l12 12',
            info: 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        };

        toast.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-${
                            data.type === 'success'
                                ? 'green'
                                : data.type === 'warning'
                                  ? 'yellow'
                                  : data.type === 'error'
                                    ? 'red'
                                    : 'blue'
                        }-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${
                                typeIcons[data.type] || typeIcons.info
                            }"></path>
                        </svg>
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900 dark:text-slate-100">${data.title}</p>
                        <p class="mt-1 text-sm text-gray-500">${data.message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-slate-900" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Add to page
        document.body.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    }

    /**
     * Update notification count in header
     */
    updateNotificationCount(count) {
        const countElement = document.querySelector('.notification-count');
        if (countElement) {
            countElement.textContent = count;
            countElement.style.display = count > 0 ? 'inline' : 'none';
        }
    }

    /**
     * Add notification to list (if on notifications page)
     */
    addNotificationToList(data) {
        if (!window.location.pathname.includes('/notifications')) {
            return;
        }

        // This would be implemented based on your specific list structure
        console.log('Adding notification to list:', data);
    }

    /**
     * Update notification in list
     */
    updateNotificationInList(notificationId, updates) {
        if (!window.location.pathname.includes('/notifications')) {
            return;
        }

        const notificationElement = document.querySelector(
            `[data-notification-id="${notificationId}"]`
        );
        if (notificationElement) {
            // Update the notification element
            console.log('Updating notification in list:', notificationId, updates);
        }
    }

    /**
     * Mark all notifications as read in list
     */
    markAllNotificationsAsRead() {
        if (!window.location.pathname.includes('/notifications')) {
            return;
        }

        // Update all notification elements in the list
        console.log('Marking all notifications as read in list');
    }

    /**
     * Remove notification from list
     */
    removeNotificationFromList(notificationId) {
        if (!window.location.pathname.includes('/notifications')) {
            return;
        }

        const notificationElement = document.querySelector(
            `[data-notification-id="${notificationId}"]`
        );
        if (notificationElement) {
            notificationElement.remove();
        }
    }

    /**
     * Get user ID from meta tag or global variable
     */
    getUserId() {
        // Try to get from meta tag
        const metaUserId = document.querySelector('meta[name="user-id"]');
        if (metaUserId) {
            return metaUserId.getAttribute('content');
        }

        // Try to get from global variable
        if (window.USER_ID) {
            return window.USER_ID;
        }

        // Try to get from auth user data
        if (window.auth && window.auth.user) {
            return window.auth.user.id;
        }

        return null;
    }

    /**
     * Request notification permission
     */
    requestNotificationPermission() {
        if (!('Notification' in window)) {
            return Promise.resolve(false);
        }

        if (Notification.permission === 'granted') {
            return Promise.resolve(true);
        }

        if (Notification.permission === 'denied') {
            return Promise.resolve(false);
        }

        return Notification.requestPermission().then((permission) => {
            return permission === 'granted';
        });
    }

    /**
     * Send test notification
     */
    sendTestNotification() {
        if (!this.isInitialized) {
            console.warn('Real-time notifications not initialized');
            return;
        }

        fetch('/admin/notifications/test', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute('content'),
                'Content-Type': 'application/json',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    console.log('Test notification sent');
                } else {
                    console.error('Failed to send test notification:', data.message);
                }
            })
            .catch((error) => {
                console.error('Error sending test notification:', error);
            });
    }

    /**
     * Disconnect from Pusher
     */
    disconnect() {
        if (this.pusher) {
            this.pusher.disconnect();
            this.isInitialized = false;
            console.log('Real-time notifications disconnected');
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function () {
    // Request notification permission
    if (window.realTimeNotifications) {
        window.realTimeNotifications.requestNotificationPermission();
    }
});

// Global instance
window.realTimeNotifications = new RealTimeNotifications();

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RealTimeNotifications;
}
