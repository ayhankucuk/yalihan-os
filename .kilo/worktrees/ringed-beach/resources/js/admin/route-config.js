/**
 * Route Configuration - Merkezi Route Yönetim Sistemi (JavaScript)
 *
 * Context7 Standard: C7-ROUTE-CONFIG-JS-2025-12-06
 *
 * Merkezi route yönetimi için JavaScript config dosyası.
 * Tüm route'lar buradan alınır, hardcoded route'lar yasaktır.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

/* global window */

// Prevent multiple declarations
if (typeof window.RouteConfig === 'undefined') {
    window.RouteConfig = {
        /**
         * Admin Routes
         */
        admin: {
            dashboard: {
                index: '/admin/dashboard',
                root: '/admin',
            },
            kullanicilar: {
                index: '/admin/kullanicilar',
                create: '/admin/kullanicilar/create',
                show: (id) => `/admin/kullanicilar/${id}`,
                edit: (id) => `/admin/kullanicilar/${id}/edit`,
                permissions: {
                    update: (id) => `/admin/kullanicilar/${id}/permissions`,
                },
            },
            ilanlar: {
                index: '/admin/ilanlar',
                create: '/admin/ilanlar/create',
                show: (id) => `/admin/ilanlar/${id}`,
                edit: (id) => `/admin/ilanlar/${id}/edit`,
            },
            kisiler: {
                index: '/admin/kisiler',
                create: '/admin/kisiler/create',
                show: (id) => `/admin/kisiler/${id}`,
                edit: (id) => `/admin/kisiler/${id}/edit`,
            },
            finans: {
                islemler: {
                    index: '/admin/finans/islemler',
                    create: '/admin/finans/islemler/create',
                    show: (id) => `/admin/finans/islemler/${id}`,
                    edit: (id) => `/admin/finans/islemler/${id}/edit`,
                },
            },
            crm: {
                dashboard: '/admin/crm/dashboard',
                customers: {
                    index: '/admin/crm/customers',
                    show: (id) => `/admin/crm/customers/${id}`,
                },
            },
            intelligence: {
                opportunities: '/admin/intelligence/opportunities',
            },
        },

        /**
         * Public Routes
         */
        public: {
            home: '/',
            about: '/hakkimizda',
            contact: '/iletisim',
            ilanlar: {
                index: '/ilanlar',
                show: (id) => `/ilanlar/${id}`,
            },
        },

        /**
         * Route helper - Route ismini URL'e çevir
         *
         * @param {string} path Dot notation path (örn: 'admin.kullanicilar.index')
         * @param {...any} params Route parametreleri
         * @returns {string} Route URL'i
         */
        url(path, ...params) {
            const parts = path.split('.');
            let route = this;

            for (const part of parts) {
                if (route[part] === undefined) {
                    console.warn(`Route bulunamadı: ${path}`);
                    return '#';
                }
                route = route[part];
            }

            if (typeof route === 'function') {
                return route(...params);
            }

            return route || '#';
        },
    };
}

