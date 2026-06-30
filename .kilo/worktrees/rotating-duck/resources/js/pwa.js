/**
 * PWA (Progressive Web App) Manager
 * Handles PWA installation, offline functionality, and app-like features
 */

class PWAManager {
    constructor() {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.isOnline = navigator.onLine;
        this.offlineQueue = [];

        this.init();
    }

    init() {
        this.registerServiceWorker();
        this.setupInstallPrompt();
        this.setupOfflineHandling();
        this.setupUpdateHandling();
        this.setupBackgroundSync();
        this.setupPushNotifications();
    }

    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.register('/sw.js');
                console.log('Service Worker registered successfully:', registration);

                // Check for updates
                registration.addEventListener('updatefound', () => {
                    const newWorker = registration.installing;
                    newWorker.addEventListener('statechange', () => {
                        if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                            this.showUpdateAvailable();
                        }
                    });
                });
            } catch (error) {
                console.error('Service Worker registration failed:', error);
            }
        }
    }

    setupInstallPrompt() {
        // Listen for beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('PWA install prompt triggered');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });

        // Listen for appinstalled event
        window.addEventListener('appinstalled', () => {
            console.log('PWA installed successfully');
            this.isInstalled = true;
            this.hideInstallButton();
            this.showInstallSuccess();
        });

        // Check if app is already installed
        if (window.matchMedia('(display-mode: standalone)').matches) {
            this.isInstalled = true;
            console.log('PWA is running in standalone mode');
        }
    }

    setupOfflineHandling() {
        // Listen for online/offline events
        window.addEventListener('online', () => {
            console.log('App is online');
            this.isOnline = true;
            this.hideOfflineIndicator();
            this.syncOfflineQueue();
        });

        window.addEventListener('offline', () => {
            console.log('App is offline');
            this.isOnline = false;
            this.showOfflineIndicator();
        });

        // Initial check
        if (!this.isOnline) {
            this.showOfflineIndicator();
        }
    }

    setupUpdateHandling() {
        // Listen for service worker updates
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('Service Worker updated, reloading page');
                window.location.reload();
            });
        }
    }

    setupBackgroundSync() {
        // Register background sync
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            navigator.serviceWorker.ready.then((registration) => {
                // Background sync will be handled by service worker
                console.log('Background sync registered');
            });
        }
    }

    setupPushNotifications() {
        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            this.requestNotificationPermission();
        }
    }

    async installApp() {
        if (!this.deferredPrompt) {
            console.log('Install prompt not available');
            return;
        }

        try {
            this.deferredPrompt.prompt();
            const { outcome } = await this.deferredPrompt.userChoice;

            if (outcome === 'accepted') {
                console.log('User accepted the install prompt');
            } else {
                console.log('User dismissed the install prompt');
            }

            this.deferredPrompt = null;
            this.hideInstallButton();
        } catch (error) {
            console.error('Error during app installation:', error);
        }
    }

    async requestNotificationPermission() {
        try {
            const permission = await Notification.requestPermission();

            if (permission === 'granted') {
                console.log('Notification permission granted');
                this.setupPushSubscription();
            } else {
                console.log('Notification permission denied');
            }
        } catch (error) {
            console.error('Error requesting notification permission:', error);
        }
    }

    async setupPushSubscription() {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            try {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(
                        process.env.MIX_VAPID_PUBLIC_KEY
                    ),
                });

                // Send subscription to server
                await this.sendSubscriptionToServer(subscription);
            } catch (error) {
                console.error('Error setting up push subscription:', error);
            }
        }
    }

    async sendSubscriptionToServer(subscription) {
        try {
            const url = window.APIConfig && window.APIConfig.pwa && window.APIConfig.pwa.pushSubscription
                ? window.APIConfig.pwa.pushSubscription
                : '/api/push-subscription';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document
                        .querySelector('meta[name="csrf-token"]')
                        .getAttribute('content'),
                },
                body: JSON.stringify(subscription),
            });

            if (response.ok) {
                console.log('Push subscription sent to server');
            }
        } catch (error) {
            console.error('Error sending subscription to server:', error);
        }
    }

    showInstallButton() {
        // Create install button
        const installButton = document.createElement('button');
        installButton.id = 'pwa-install-button';
        installButton.className =
            'fixed bottom-4 right-4 bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg hover:bg-blue-700 transition-colors z-50';
        installButton.innerHTML = `
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            Uygulamayı Yükle
        `;

        installButton.addEventListener('click', () => this.installApp());
        document.body.appendChild(installButton);
    }

    hideInstallButton() {
        const installButton = document.getElementById('pwa-install-button');
        if (installButton) {
            installButton.remove();
        }
    }

    showInstallSuccess() {
        this.showNotification('Uygulama başarıyla yüklendi!', 'success');
    }

    showUpdateAvailable() {
        const updateButton = document.createElement('button');
        updateButton.className =
            'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg hover:bg-green-700 transition-colors z-50';
        updateButton.innerHTML = `
            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Güncelleme Mevcut
        `;

        updateButton.addEventListener('click', () => {
            window.location.reload();
        });

        document.body.appendChild(updateButton);

        // Auto remove after 10 seconds
        setTimeout(() => {
            if (updateButton.parentNode) {
                updateButton.remove();
            }
        }, 10000);
    }

    showOfflineIndicator() {
        const offlineIndicator = document.createElement('div');
        offlineIndicator.id = 'offline-indicator';
        offlineIndicator.className =
            'fixed top-0 left-0 right-0 bg-yellow-500 text-white text-center py-2 z-50';
        offlineIndicator.innerHTML = `
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
            </svg>
            İnternet bağlantısı yok - Çevrimdışı modda çalışıyorsunuz
        `;

        document.body.appendChild(offlineIndicator);
    }

    hideOfflineIndicator() {
        const offlineIndicator = document.getElementById('offline-indicator');
        if (offlineIndicator) {
            offlineIndicator.remove();
        }
    }

    async syncOfflineQueue() {
        if (this.offlineQueue.length > 0) {
            console.log('Syncing offline queue...');

            for (const action of this.offlineQueue) {
                try {
                    await this.syncAction(action);
                } catch (error) {
                    console.error('Failed to sync action:', action, error);
                }
            }

            this.offlineQueue = [];
            this.showNotification('Çevrimdışı işlemler senkronize edildi', 'success');
        }
    }

    async syncAction(action) {
        const response = await fetch(action.url, {
            method: action.method,
            headers: action.headers,
            body: action.body,
        });

        if (!response.ok) {
            throw new Error(`Sync failed: ${response.status}`);
        }

        return response;
    }

    queueOfflineAction(action) {
        this.offlineQueue.push(action);
        this.showNotification('İşlem çevrimdışı kuyruğa eklendi', 'info');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white rounded-lg shadow-lg border-l-4 ${
            type === 'success'
                ? 'border-green-500'
                : type === 'error'
                  ? 'border-red-500'
                  : type === 'warning'
                    ? 'border-yellow-500'
                    : 'border-blue-500'
        } transform transition-all duration-300 translate-x-full`;

        notification.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 ${
                            type === 'success'
                                ? 'text-green-400'
                                : type === 'error'
                                  ? 'text-red-400'
                                  : type === 'warning'
                                    ? 'text-yellow-400'
                                    : 'text-blue-400'
                        }"fill="currentColor"viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">${message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <button class="bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none dark:bg-slate-900 dark:text-slate-600" onclick="this.parentElement.parentElement.parentElement.remove()">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }

    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }

    // Public methods
    isAppInstalled() {
        return this.isInstalled;
    }

    isAppOnline() {
        return this.isOnline;
    }

    getOfflineQueue() {
        return this.offlineQueue;
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.pwaManager = new PWAManager();
});

// Export for use in other scripts
window.PWAManager = PWAManager;
