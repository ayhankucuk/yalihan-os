/**
 * Context7 Standardized JavaScript Framework
 * Yalıhan Emlak Admin Panel - Context7 Integration
 */

class Context7 {
    constructor() {
        this.version = '1.0.0';
        this.initialized = false;
        this.components = new Map();
        this.events = new Map();

        this.init();
    }

    /**
     * Initialize Context7 Framework
     */
    init() {
        if (this.initialized) return;

        console.log('Context7 Framework v' + this.version + ' initializing...');

        // Initialize components when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeComponents());
        } else {
            this.initializeComponents();
        }

        this.initialized = true;
        console.log('Context7 Framework initialized successfully');
    }

    /**
     * Initialize all Context7 components
     */
    initializeComponents() {
        this.initDropdowns();
        this.initMobileMenu();
        this.initNotifications();
        this.initPerformanceMonitoring();
        this.initFormValidation();
        this.initTooltips();
        this.initModals();
        this.initTabs();
        this.initAccordions();
    }

    /**
     * Initialize dropdown components
     */
    initDropdowns() {
        const dropdowns = document.querySelectorAll('[data-context7-dropdown]');

        dropdowns.forEach((dropdown) => {
            const toggle = dropdown.querySelector('.context7-dropdown-toggle, .context7-user-btn');
            const menu = dropdown.querySelector('.context7-dropdown-menu');

            if (toggle && menu) {
                const dropdownInstance = new Context7Dropdown(dropdown, toggle, menu);
                this.components.set(dropdown, dropdownInstance);
            }
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            this.components.forEach((component, element) => {
                if (component instanceof Context7Dropdown && !element.contains(e.target)) {
                    component.close();
                }
            });
        });
    }

    /**
     * Initialize mobile menu
     */
    initMobileMenu() {
        const toggle = document.querySelector('[data-context7-mobile="toggle"]');
        const menu = document.querySelector('[data-context7-mobile="menu"]');

        if (toggle && menu) {
            const mobileMenu = new Context7MobileMenu(toggle, menu);
            this.components.set(menu, mobileMenu);
        }
    }

    /**
     * Initialize notification system
     */
    initNotifications() {
        this.notifications = new Context7Notifications();
        this.components.set('notifications', this.notifications);
    }

    /**
     * Initialize performance monitoring
     */
    initPerformanceMonitoring() {
        if (this.shouldMonitorPerformance()) {
            this.performance = new Context7Performance();
            this.components.set('performance', this.performance);
        }
    }

    /**
     * Initialize form validation
     */
    initFormValidation() {
        const forms = document.querySelectorAll('form[data-context7-form]');

        forms.forEach((form) => {
            const validator = new Context7FormValidator(form);
            this.components.set(form, validator);
        });
    }

    /**
     * Initialize tooltips
     */
    initTooltips() {
        const tooltips = document.querySelectorAll('[data-context7-tooltip]');

        tooltips.forEach((tooltip) => {
            const tooltipInstance = new Context7Tooltip(tooltip);
            this.components.set(tooltip, tooltipInstance);
        });
    }

    /**
     * Initialize modals
     */
    initModals() {
        const modals = document.querySelectorAll('[data-context7-modal]');

        modals.forEach((modal) => {
            const modalInstance = new Context7Modal(modal);
            this.components.set(modal, modalInstance);
        });
    }

    /**
     * Initialize tabs
     */
    initTabs() {
        const tabs = document.querySelectorAll('[data-context7-tabs]');

        tabs.forEach((tab) => {
            const tabInstance = new Context7Tabs(tab);
            this.components.set(tab, tabInstance);
        });
    }

    /**
     * Initialize accordions
     */
    initAccordions() {
        const accordions = document.querySelectorAll('[data-context7-accordion]');

        accordions.forEach((accordion) => {
            const accordionInstance = new Context7Accordion(accordion);
            this.components.set(accordion, accordionInstance);
        });
    }

    /**
     * Check if performance monitoring should be status
     */
    shouldMonitorPerformance() {
        return document.querySelector('[data-context7-metrics]') !== null;
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        if (this.notifications) {
            this.notifications.show(message, type, duration);
        }
    }

    /**
     * Get component instance
     */
    getComponent(element) {
        return this.components.get(element);
    }

    /**
     * Destroy component
     */
    destroyComponent(element) {
        const component = this.components.get(element);
        if (component && component.destroy) {
            component.destroy();
        }
        this.components.delete(element);
    }

    /**
     * Event system
     */
    on(event, callback) {
        if (!this.events.has(event)) {
            this.events.set(event, []);
        }
        this.events.get(event).push(callback);
    }

    emit(event, data) {
        const callbacks = this.events.get(event);
        if (callbacks) {
            callbacks.forEach((callback) => callback(data));
        }
    }
}

/**
 * Context7 Dropdown Component
 */
class Context7Dropdown {
    constructor(element, toggle, menu) {
        this.element = element;
        this.toggle = toggle;
        this.menu = menu;
        this.isOpen = false;

        this.init();
    }

    init() {
        this.toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggleDropdown();
        });
    }

    toggleDropdown() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.menu.classList.remove('hidden');
        this.isOpen = true;
        this.element.setAttribute('data-context7-dropdown-open', 'true');
    }

    close() {
        this.menu.classList.add('hidden');
        this.isOpen = false;
        this.element.removeAttribute('data-context7-dropdown-open');
    }

    destroy() {
        this.toggle.removeEventListener('click', this.toggleDropdown);
    }
}

/**
 * Context7 Mobile Menu Component
 */
class Context7MobileMenu {
    constructor(toggle, menu) {
        this.toggle = toggle;
        this.menu = menu;
        this.isOpen = false;

        this.init();
    }

    init() {
        this.toggle.addEventListener('click', () => {
            this.toggleMenu();
        });
    }

    toggleMenu() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.menu.classList.remove('hidden');
        this.isOpen = true;
    }

    close() {
        this.menu.classList.add('hidden');
        this.isOpen = false;
    }

    destroy() {
        this.toggle.removeEventListener('click', this.toggleMenu);
    }
}

/**
 * Context7 Notifications Component
 */
class Context7Notifications {
    constructor() {
        this.container = this.createContainer();
        this.notifications = new Map();

        document.body.appendChild(this.container);
    }

    createContainer() {
        const container = document.createElement('div');
        container.className = 'context7-notifications fixed top-4 right-4 z-50 space-y-2';
        container.setAttribute('data-context7-notifications', 'container');
        return container;
    }

    show(message, type = 'info', duration = 5000) {
        const id = Date.now().toString();
        const notification = this.createNotification(id, message, type);

        this.container.appendChild(notification);
        this.notifications.set(id, notification);

        // Animate in
        setTimeout(() => {
            notification.classList.add('context7-animate-fade-in');
        }, 10);

        // Auto remove
        if (duration > 0) {
            setTimeout(() => {
                this.remove(id);
            }, duration);
        }

        return id;
    }

    createNotification(id, message, type) {
        const notification = document.createElement('div');
        notification.className = `context7-notification context7-alert context7-alert-${type} max-w-sm w-full shadow-lg rounded-lg pointer-events-auto overflow-hidden`;
        notification.setAttribute('data-context7-notification', id);

        notification.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        ${this.getIcon(type)}
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium">${message}</p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="context7-notification-close bg-white rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-slate-900">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;

        // Close button functionality
        const closeBtn = notification.querySelector('.context7-notification-close');
        closeBtn.addEventListener('click', () => this.remove(id));

        return notification;
    }

    getIcon(type) {
        const icons = {
            success:
                '<svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>',
            error: '<svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>',
            warning:
                '<svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>',
            info: '<svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>',
        };

        return icons[type] || icons.info;
    }

    remove(id) {
        const notification = this.notifications.get(id);
        if (notification) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';

            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
                this.notifications.delete(id);
            }, 300);
        }
    }

    destroy() {
        if (this.container && this.container.parentNode) {
            this.container.parentNode.removeChild(this.container);
        }
        this.notifications.clear();
    }
}

/**
 * Context7 Performance Monitoring Component
 */
class Context7Performance {
    constructor() {
        this.metrics = {
            loadTime: 0,
            memoryUsage: 0,
            queryCount: 0,
            cacheStatus: 'unknown',
        };

        this.init();
    }

    init() {
        this.measureLoadTime();
        this.startMemoryMonitoring();
        this.updateMetrics();

        // Update metrics every 5 seconds
        setInterval(() => this.updateMetrics(), 5000);
    }

    measureLoadTime() {
        if (typeof performance !== 'undefined') {
            window.addEventListener('load', () => {
                this.metrics.loadTime = performance.now();
                this.updateLoadTimeDisplay();
            });
        }
    }

    startMemoryMonitoring() {
        if (typeof performance !== 'undefined' && performance.memory) {
            setInterval(() => {
                this.metrics.memoryUsage = performance.memory.usedJSHeapSize;
                this.updateMemoryDisplay();
            }, 1000);
        }
    }

    updateMetrics() {
        this.updateLoadTimeDisplay();
        this.updateMemoryDisplay();
        this.updateQueryDisplay();
        this.updateCacheDisplay();
    }

    updateLoadTimeDisplay() {
        const element = document.querySelector('.context7-load-time');
        if (element && this.metrics.loadTime) {
            element.textContent = (this.metrics.loadTime / 1000).toFixed(3) + 's';
        }
    }

    updateMemoryDisplay() {
        const element = document.querySelector('.context7-memory');
        if (element && this.metrics.memoryUsage) {
            element.textContent = (this.metrics.memoryUsage / 1024 / 1024).toFixed(2) + 'MB';
        }
    }

    updateQueryDisplay() {
        // This would typically be populated by server-side data
        const element = document.querySelector('.context7-queries');
        if (element) {
            // Placeholder - would need server integration
            element.textContent = 'N/A';
        }
    }

    updateCacheDisplay() {
        const element = document.querySelector('.context7-cache-status');
        if (element) {
            // Placeholder - would need server integration
            element.innerHTML = '<span class="text-green-600">Redis</span>';
        }
    }

    destroy() {
        // Cleanup performance monitoring
    }
}

/**
 * Context7 Form Validator Component (Placeholder)
 */
class Context7FormValidator {
    constructor(form) {
        this.form = form;
        // Form validation logic would go here
    }

    destroy() {
        // Cleanup form validation
    }
}

/**
 * Context7 Tooltip Component (Placeholder)
 */
class Context7Tooltip {
    constructor(element) {
        this.element = element;
        // Tooltip logic would go here
    }

    destroy() {
        // Cleanup tooltip
    }
}

/**
 * Context7 Modal Component (Placeholder)
 */
class Context7Modal {
    constructor(element) {
        this.element = element;
        // Modal logic would go here
    }

    destroy() {
        // Cleanup modal
    }
}

/**
 * Context7 Tabs Component (Placeholder)
 */
class Context7Tabs {
    constructor(element) {
        this.element = element;
        // Tabs logic would go here
    }

    destroy() {
        // Cleanup tabs
    }
}

/**
 * Context7 Accordion Component (Placeholder)
 */
class Context7Accordion {
    constructor(element) {
        this.element = element;
        // Accordion logic would go here
    }

    destroy() {
        // Cleanup accordion
    }
}

// Initialize Context7 when script loads
window.Context7 = new Context7();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = Context7;
}
