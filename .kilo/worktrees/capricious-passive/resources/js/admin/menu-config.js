/**
 * Menu Configuration - Merkezi Menu Yönetim Sistemi (JavaScript)
 *
 * Context7 Standard: C7-MENU-CONFIG-JS-2025-12-06
 *
 * Frontend'de menu yönetimi için JavaScript config.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

/* global window */

// Prevent multiple declarations
if (typeof window.MenuConfig === 'undefined') {
    window.MenuConfig = {
        /**
         * Menu items (Backend'den sync edilir)
         */
        items: [],

        /**
         * Menu item'larını set et
         *
         * @param {Array} items Menu item'ları
         */
        setItems(items) {
            this.items = items;
        },

        /**
         * Menu item'larını al
         *
         * @param {string} menuName Menu ismi (örn: 'admin.sidebar')
         * @returns {Array} Menu item'ları
         */
        get(menuName) {
            // Permission kontrolü ile filtrele
            return this.items.filter(item => {
                if (item.permission) {
                    return window.PermissionRouteConfig?.canAccess(item.route) ?? true;
                }
                return true;
            });
        },

        /**
         * Menu item render et
         *
         * @param {Object} item Menu item
         * @returns {string} HTML
         */
        renderItem(item) {
            if (item.type === 'group') {
                return this.renderGroup(item);
            }
            return this.renderLink(item);
        },

        /**
         * Link item render et
         *
         * @param {Object} item Menu item
         * @returns {string} HTML
         */
        renderLink(item) {
            const url = window.PermissionRouteConfig?.url(item.route) ?? '#';
            const isActive = window.location.pathname.includes(item.route.replace(/\./g, '/'));

            let html = `<a href="${url}" class="menu-link ${isActive ? 'active' : ''}">`;
            
            if (item.icon) {
                html += `<svg class="w-5 h-5">${this.getIcon(item.icon)}</svg>`;
            }
            
            html += `<span>${item.name}</span>`;
            
            if (item.badge) {
                html += `<span class="badge">${item.badge}</span>`;
            }
            
            html += '</a>';

            return html;
        },

        /**
         * Group item render et
         *
         * @param {Object} item Menu item
         * @returns {string} HTML
         */
        renderGroup(item) {
            let html = '<div class="menu-group">';
            html += `<button class="menu-group-button">${item.name}</button>`;
            html += '<div class="menu-group-children">';
            
            if (item.children) {
                item.children.forEach(child => {
                    html += this.renderLink(child);
                });
            }
            
            html += '</div>';
            html += '</div>';

            return html;
        },

        /**
         * Icon SVG path'ini al
         *
         * @param {string} iconName Icon ismi
         * @returns {string} SVG path
         */
        getIcon(iconName) {
            const icons = {
                dashboard: '<rect x="3" y="3" width="7" height="9" /><rect x="14" y="3" width="7" height="5" />',
                users: '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" />',
                // Diğer icon'lar...
            };

            return icons[iconName] || '';
        },
    };

    // Backend'den menu item'larını yükle
    if (window.menuItems) {
        window.MenuConfig.setItems(window.menuItems);
    }
}

