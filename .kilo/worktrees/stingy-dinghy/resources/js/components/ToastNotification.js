/**
 * Toast Notification System
 * 
 * Lightweight toast notification component for displaying feedback messages.
 * Supports multiple types: success, error, warning, info
 * 
 * Context7 Compliance:
 * - ✅ Tailwind CSS with dark mode
 * - ✅ No hardcoded colors
 * - ✅ Smooth transitions
 * 
 * @example
 * // Show toast programmatically:
 * window.showToast('Operation successful!', 'success');
 * 
 * // Or dispatch custom event:
 * window.dispatchEvent(new CustomEvent('show-toast', {
 *   detail: { message: 'Saved!', type: 'success' }
 * }));
 * 
 * @example
 * // In Blade template:
 * <div x-data="toastNotification()"></div>
 */

/**
 * Creates Toast Notification Alpine.js component
 * 
 * @returns {Object} Alpine.js component data
 */
export default function toastNotification() {
    return {
        // === DATA STATE ===
        visible: false,
        message: '',
        type: 'info', // 'success' | 'error' | 'warning' | 'info'
        timeout: null,
        
        // === LIFECYCLE ===
        init() {
            // Listen for global toast events
            window.addEventListener('show-toast', (event) => {
                const { message, type = 'info', duration = 3000 } = event.detail || {};
                this.show(message, type, duration);
            });
            
            // Expose global helper function
            window.showToast = (message, type = 'info', duration = 3000) => {
                this.show(message, type, duration);
            };
            
            console.log('✅ Toast Notification System initialized');
        },
        
        // === METHODS ===
        
        /**
         * Show toast notification
         * 
         * @param {string} message - Message to display
         * @param {string} type - Toast type ('success', 'error', 'warning', 'info')
         * @param {number} duration - Display duration in milliseconds (default: 3000)
         */
        show(message, type = 'info', duration = 3000) {
            // Clear existing timeout
            if (this.timeout) {
                clearTimeout(this.timeout);
            }
            
            // Update state
            this.message = message || 'Notification';
            this.type = type;
            this.visible = true;
            
            // Auto-hide after duration
            this.timeout = setTimeout(() => {
                this.hide();
            }, duration);
        },
        
        /**
         * Hide toast notification
         */
        hide() {
            this.visible = false;
            
            // Clear timeout if exists
            if (this.timeout) {
                clearTimeout(this.timeout);
                this.timeout = null;
            }
        },
        
        /**
         * Get icon based on toast type
         */
        get icon() {
            const icons = {
                success: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                error: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
                warning: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>',
                info: '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            };
            return icons[this.type] || icons.info;
        },
        
        /**
         * Get CSS classes based on toast type
         */
        get classes() {
            const baseClasses = 'flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg border backdrop-blur-sm transition-all duration-300';
            
            const typeClasses = {
                success: 'bg-green-50/90 dark:bg-green-900/30 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
                error: 'bg-red-50/90 dark:bg-red-900/30 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
                warning: 'bg-yellow-50/90 dark:bg-yellow-900/30 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
                info: 'bg-blue-50/90 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200'
            };
            
            return `${baseClasses} ${typeClasses[this.type] || typeClasses.info}`;
        }
    };
}

// Expose globally for inline usage
if (typeof window !== 'undefined') {
    window.toastNotification = toastNotification;
    window.toastNotificationComponent = toastNotification; // Alias for x-data usage
    window.showToast = (message, type = 'info', duration = 3000) => {
        const event = new CustomEvent('show-toast', {
            detail: { message, type, duration }
        });
        window.dispatchEvent(event);
    };
}
