// Yalıhan Bekçi - Service Worker
// PWA özellikleri ve offline capability
// 🚨 DEVELOPMENT MODE: Minimal caching (2025-10-30)

const CACHE_NAME = 'yalihan-bekci-v1.0.1-dev';
const STATIC_CACHE_NAME = 'yalihan-static-v1.0.0';
const DYNAMIC_CACHE_NAME = 'yalihan-dynamic-v1.0.0';
const API_CACHE_NAME = 'yalihan-api-v1.0.0';

// Cache strategies
const CACHE_STRATEGIES = {
    static: 'cache-first',
    dynamic: 'network-first',
    api: 'network-first',
    images: 'cache-first',
};

// Static assets to cache
const STATIC_ASSETS = [
    '/',
    '/css/app.css',
    '/css/design-tokens.css',
    '/js/app.js',
    '/js/admin/ilan-create/state-management.js',
    '/js/admin/ilan-create/lazy-components.js',
    '/js/admin/ilan-create/performance-optimizer.js',
    '/js/admin/ilan-create/skeleton-loader.js',
    '/js/admin/ilan-create/dark-mode-toggle.js',
    '/js/admin/ilan-create/touch-gestures.js',
    '/js/admin/ilan-create/toast-notifications.js',
    '/js/admin/ilan-create/drag-drop-photos.js',
    '/js/admin/unified-search-engine.js',
    '/js/admin/mobile-first-responsive.js',
    '/js/admin/dashboard-modernization.js',
    '/js/admin/ilan-create/master-integration.js',
    '/images/logo.png',
    '/manifest.json',
    '/offline.html',
];

// API endpoints to cache
const API_ENDPOINTS = [
    '/api/admin/ilanlar',
    '/api/admin/kategoriler',
    '/api/admin/kisiler',
    '/api/admin/lokasyonlar',
    '/api/admin/ai/',
    '/api/admin/ai-assist/',
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('Service Worker: Installing... (DEV MODE - minimal caching)');

    // 🚨 DEV MODE: Yine de offline fallback sayfasını önbelleğe al
    event.waitUntil(
        caches
            .open(STATIC_CACHE_NAME)
            .then((cache) => cache.addAll(['/offline.html']))
            .catch(() => {})
    );

    self.skipWaiting();
});

// Activate event - clean up old caches
self.addEventListener('activate', (event) => {
    console.log('Service Worker: Activating...');

    event.waitUntil(
        caches
            .keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames.map((cacheName) => {
                        if (
                            cacheName !== STATIC_CACHE_NAME &&
                            cacheName !== DYNAMIC_CACHE_NAME &&
                            cacheName !== API_CACHE_NAME
                        ) {
                            console.log('Service Worker: Deleting old cache', cacheName);
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
            .then(() => {
                console.log('Service Worker: Activated');
                return self.clients.claim();
            })
    );
});

// Fetch event - implement caching strategies
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip chrome-extension and other non-http requests
    if (!url.protocol.startsWith('http')) {
        return;
    }

    // Determine cache strategy based on request type
    if (isStaticAsset(request)) {
        event.respondWith(cacheFirstStrategy(request));
    } else if (isAPIRequest(request)) {
        event.respondWith(networkFirstStrategy(request));
    } else if (isImageRequest(request)) {
        event.respondWith(cacheFirstStrategy(request));
    } else {
        event.respondWith(networkFirstStrategy(request));
    }
});

// Cache First Strategy - for static assets and images
async function cacheFirstStrategy(request) {
    try {
        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(STATIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.error('Cache First Strategy failed:', error);
        return getOfflineResponse(request);
    }
}

// Network First Strategy - for API requests and dynamic content
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);

        if (networkResponse.ok) {
            const cache = await caches.open(DYNAMIC_CACHE_NAME);
            cache.put(request, networkResponse.clone());
        }

        return networkResponse;
    } catch (error) {
        console.log('Network failed, trying cache:', error);

        const cachedResponse = await caches.match(request);

        if (cachedResponse) {
            return cachedResponse;
        }

        return getOfflineResponse(request);
    }
}

// Stale While Revalidate Strategy - for frequently updated content
async function staleWhileRevalidateStrategy(request) {
    const cache = await caches.open(DYNAMIC_CACHE_NAME);
    const cachedResponse = await cache.match(request);

    const fetchPromise = fetch(request)
        .then((networkResponse) => {
            if (networkResponse.ok) {
                cache.put(request, networkResponse.clone());
            }
            return networkResponse;
        })
        .catch(() => cachedResponse);

    return cachedResponse || fetchPromise;
}

// Helper functions
function isStaticAsset(request) {
    const url = new URL(request.url);
    return url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|woff|woff2|ttf|eot)$/);
}

function isAPIRequest(request) {
    const url = new URL(request.url);
    const apiPrefix = (typeof window !== 'undefined' && window.APIConfig && window.APIConfig.apiPrefix) ? window.APIConfig.apiPrefix : '/api';
    return url.pathname.startsWith(apiPrefix + '/');
}

function isImageRequest(request) {
    const url = new URL(request.url);
    return url.pathname.match(/\.(png|jpg|jpeg|gif|svg|webp)$/);
}

async function getOfflineResponse(request) {
    const url = new URL(request.url);

    // Görseller için basit SVG placeholder
    if (url.pathname.match(/\.(png|jpg|jpeg|gif|svg|webp)$/)) {
        return new Response(
            '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200"><rect width="200" height="200" fill="#f3f4f6"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#9ca3af">Offline</text></svg>',
            { headers: { 'Content-Type': 'image/svg+xml' } }
        );
    }

    // API istekleri için JSON hata yanıtı
    const apiPrefix = (typeof window !== 'undefined' && window.APIConfig && window.APIConfig.apiPrefix) ? window.APIConfig.apiPrefix : '/api';
    if (url.pathname.startsWith(apiPrefix + '/')) {
        return new Response(
            JSON.stringify({
                error: 'Offline',
                message: 'Bu işlem için internet bağlantısı gerekli',
                offline: true,
            }),
            {
                status: 503,
                headers: { 'Content-Type': 'application/json' },
            }
        );
    }

    // Navigasyon (HTML sayfaları) için offline.html dene; yoksa minimal HTML döndür
    if (request.mode === 'navigate') {
        try {
            const cached = await caches.match('/offline.html');
            if (cached) return cached;
        } catch (_) {
            // yoksay
        }
        return new Response(
            '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Çevrimdışı</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;background:#f9fafb;color:#111827}</style></head><body><div><h1>Çevrimdışı</h1><p>Bağlantı yok. İnternet geldiğinde tekrar deneyin.</p></div></body></html>',
            { status: 503, headers: { 'Content-Type': 'text/html; charset=UTF-8' } }
        );
    }

    // Diğer istekler için düz metin fallback
    return new Response('Offline', {
        status: 503,
        headers: { 'Content-Type': 'text/plain; charset=UTF-8' },
    });
}

// Background Sync for offline actions
self.addEventListener('sync', (event) => {
    console.log('Service Worker: Background sync triggered', event.tag);

    if (event.tag === 'background-sync') {
        event.waitUntil(doBackgroundSync());
    }

    if (event.tag === 'form-sync') {
        event.waitUntil(syncOfflineForms());
    }
});

// Push notifications
self.addEventListener('push', (event) => {
    console.log('Service Worker: Push received', event);

    const options = {
        body: event.data ? event.data.text() : 'Yeni bildirim',
        icon: '/images/icon-192x192.png',
        badge: '/images/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            primaryKey: 1,
        },
        actions: [
            {
                action: 'explore',
                title: 'Görüntüle',
                icon: '/images/checkmark.png',
            },
            {
                action: 'close',
                title: 'Kapat',
                icon: '/images/xmark.png',
            },
        ],
    };

    event.waitUntil(self.registration.showNotification('Yalıhan Bekçi', options));
});

// Notification click
self.addEventListener('notificationclick', (event) => {
    console.log('Service Worker: Notification clicked', event);

    event.notification.close();

    if (event.action === 'explore') {
        event.waitUntil(clients.openWindow('/admin/dashboard'));
    }
});

// Message handling
self.addEventListener('message', (event) => {
    console.log('Service Worker: Message received', event.data);

    if (event.data && event.data.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }

    if (event.data && event.data.type === 'GET_VERSION') {
        event.ports[0].postMessage({ version: CACHE_NAME });
    }
});

// Background sync functions
async function doBackgroundSync() {
    console.log('Service Worker: Performing background sync');

    try {
        // Sync offline data
        const offlineData = await getOfflineData();

        for (const item of offlineData) {
            await syncOfflineItem(item);
        }

        console.log('Service Worker: Background sync completed');
    } catch (error) {
        console.error('Service Worker: Background sync failed', error);
    }
}

async function syncOfflineForms() {
    console.log('Service Worker: Syncing offline forms');

    try {
        const offlineForms = await getOfflineForms();

        for (const form of offlineForms) {
            await syncForm(form);
        }

        console.log('Service Worker: Form sync completed');
    } catch (error) {
        console.error('Service Worker: Form sync failed', error);
    }
}

async function getOfflineData() {
    // Get offline data from IndexedDB or localStorage
    return new Promise((resolve) => {
        const request = indexedDB.open('yalihan-offline', 1);

        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['offlineData'], 'readonly');
            const store = transaction.objectStore('offlineData');
            const getAllRequest = store.getAll();

            getAllRequest.onsuccess = () => {
                resolve(getAllRequest.result);
            };

            getAllRequest.onerror = () => {
                resolve([]);
            };
        };

        request.onerror = () => {
            resolve([]);
        };
    });
}

async function getOfflineForms() {
    // Get offline forms from IndexedDB
    return new Promise((resolve) => {
        const request = indexedDB.open('yalihan-offline', 1);

        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['offlineForms'], 'readonly');
            const store = transaction.objectStore('offlineForms');
            const getAllRequest = store.getAll();

            getAllRequest.onsuccess = () => {
                resolve(getAllRequest.result);
            };

            getAllRequest.onerror = () => {
                resolve([]);
            };
        };

        request.onerror = () => {
            resolve([]);
        };
    });
}

async function syncOfflineItem(item) {
    try {
        const response = await fetch(item.url, {
            method: item.method,
            headers: item.headers,
            body: item.body,
        });

        if (response.ok) {
            // Remove from offline storage
            await removeOfflineItem(item.id);
            console.log('Service Worker: Synced offline item', item.id);
        }
    } catch (error) {
        console.error('Service Worker: Failed to sync offline item', error);
    }
}

async function syncForm(form) {
    try {
        const response = await fetch(form.url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': form.csrfToken,
            },
            body: JSON.stringify(form.data),
        });

        if (response.ok) {
            // Remove from offline storage
            await removeOfflineForm(form.id);
            console.log('Service Worker: Synced offline form', form.id);
        }
    } catch (error) {
        console.error('Service Worker: Failed to sync offline form', error);
    }
}

async function removeOfflineItem(id) {
    return new Promise((resolve) => {
        const request = indexedDB.open('yalihan-offline', 1);

        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['offlineData'], 'readwrite');
            const store = transaction.objectStore('offlineData');
            const deleteRequest = store.delete(id);

            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => resolve();
        };

        request.onerror = () => resolve();
    });
}

async function removeOfflineForm(id) {
    return new Promise((resolve) => {
        const request = indexedDB.open('yalihan-offline', 1);

        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['offlineForms'], 'readwrite');
            const store = transaction.objectStore('offlineForms');
            const deleteRequest = store.delete(id);

            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => resolve();
        };

        request.onerror = () => resolve();
    });
}

// Cache management utilities
async function clearOldCaches() {
    const cacheNames = await caches.keys();
    const currentCaches = [STATIC_CACHE_NAME, DYNAMIC_CACHE_NAME, API_CACHE_NAME];

    return Promise.all(
        cacheNames
            .filter((cacheName) => !currentCaches.includes(cacheName))
            .map((cacheName) => caches.delete(cacheName))
    );
}

async function updateCache() {
    const cache = await caches.open(STATIC_CACHE_NAME);
    return cache.addAll(STATIC_ASSETS);
}

// Initialize IndexedDB for offline storage
function initializeIndexedDB() {
    const request = indexedDB.open('yalihan-offline', 1);

    request.onupgradeneeded = (event) => {
        const db = event.target.result;

        // Create offline data store
        if (!db.objectStoreNames.contains('offlineData')) {
            const offlineDataStore = db.createObjectStore('offlineData', {
                keyPath: 'id',
                autoIncrement: true,
            });
            offlineDataStore.createIndex('url', 'url', { unique: false });
            offlineDataStore.createIndex('timestamp', 'timestamp', {
                unique: false,
            });
        }

        // Create offline forms store
        if (!db.objectStoreNames.contains('offlineForms')) {
            const offlineFormsStore = db.createObjectStore('offlineForms', {
                keyPath: 'id',
                autoIncrement: true,
            });
            offlineFormsStore.createIndex('url', 'url', { unique: false });
            offlineFormsStore.createIndex('timestamp', 'timestamp', {
                unique: false,
            });
        }
    };
}

// Initialize IndexedDB
initializeIndexedDB();

console.log('Service Worker: Loaded successfully');
