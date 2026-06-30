// Admin Panel Service Worker
const ADMIN_CACHE_NAME = 'emlakpro-admin-v1.0.0';

const ADMIN_ASSETS = ['/admin', '/css/app.css', '/js/app.js', '/images/admin-logo.png'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(ADMIN_CACHE_NAME).then((cache) => cache.addAll(ADMIN_ASSETS)));
});

self.addEventListener('fetch', (event) => {
    if (event.request.url.includes('/admin/')) {
        event.respondWith(
            caches.match(event.request).then((response) => response || fetch(event.request))
        );
    }
});
