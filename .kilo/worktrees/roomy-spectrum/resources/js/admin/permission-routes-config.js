/**
 * Permission Routes Configuration - Merkezi Permission-Based Route Yönetimi (JavaScript)
 *
 * Context7 Standard: C7-PERMISSION-ROUTES-CONFIG-JS-2025-12-06
 *
 * Frontend'de permission kontrolü ile route erişimi sağlar.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

/* global window */

// Prevent multiple declarations
if (typeof window.PermissionRouteConfig === 'undefined') {
    window.PermissionRouteConfig = {
        /**
         * Permission mapping (Backend'den sync edilir)
         */
        permissions: {
            'admin.dashboard.index': 'view-admin-panel',
            'admin.kullanicilar.index': 'manage-users',
            'admin.ilanlar.index': 'manage-ilanlar',
            'admin.kisiler.index': 'view-admin-panel',
            'admin.finans.islemler.index': 'view-admin-panel',
            'admin.crm.dashboard': 'view-admin-panel',
            'admin.ayarlar.index': 'manage-settings',
        },

        /**
         * Kullanıcı permission'ları (Backend'den gelir)
         */
        userPermissions: [],

        /**
         * Kullanıcı rollerini set et
         *
         * @param {Array<string>} permissions Permission listesi
         */
        setUserPermissions(permissions) {
            this.userPermissions = permissions;
        },

        /**
         * Route'a erişim izni var mı kontrol et
         *
         * @param {string} routeName Route ismi
         * @returns {boolean}
         */
        canAccess(routeName) {
            // Public route kontrolü
            const publicRoutes = ['home', 'login', 'register'];
            if (publicRoutes.includes(routeName)) {
                return true;
            }

            // Permission mapping'den permission'ı al
            const permission = this.permissions[routeName];

            if (!permission) {
                // Varsayılan: erişim izni var
                return true;
            }

            // Kullanıcı permission kontrolü
            return this.userPermissions.includes(permission);
        },

        /**
         * Route URL'ini permission kontrolü ile oluştur
         *
         * @param {string} routeName Route ismi
         * @param {...any} params Route parametreleri
         * @returns {string|null} Route URL'i veya null
         */
        url(routeName, ...params) {
            if (!this.canAccess(routeName)) {
                return null;
            }

            // RouteConfig kullanarak URL oluştur
            if (window.RouteConfig) {
                return window.RouteConfig.url(routeName, ...params);
            }

            // Fallback: Basit URL oluşturma
            return `/${routeName.replace(/\./g, '/')}`;
        },

        /**
         * Link oluştur (permission kontrolü ile)
         *
         * @param {string} routeName Route ismi
         * @param {string} text Link metni
         * @param {Object} options Link seçenekleri
         * @returns {string|null} HTML link veya null
         */
        link(routeName, text, options = {}) {
            if (!this.canAccess(routeName)) {
                return null;
            }

            const url = this.url(routeName);
            if (!url) {
                return null;
            }

            const classes = options.class || '';
            const target = options.target || '_self';

            return `<a href="${url}" class="${classes}" target="${target}">${text}</a>`;
        },

        /**
         * Tüm erişilebilir route'ları listele
         *
         * @returns {Array<string>} Route isimleri
         */
        getAccessibleRoutes() {
            return Object.keys(this.permissions).filter((routeName) => this.canAccess(routeName));
        },
    };

    // Backend'den kullanıcı permission'larını yükle
    if (window.userPermissions) {
        window.PermissionRouteConfig.setUserPermissions(window.userPermissions);
    }
}
