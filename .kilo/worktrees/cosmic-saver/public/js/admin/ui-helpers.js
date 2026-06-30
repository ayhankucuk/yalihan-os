/**
 * Context7 UI Helper Utilities
 *
 * @description Smooth scroll, highlight, and other UI utilities
 * @author Yalıhan Emlak - Context7 Team
 * @date 2025-11-04
 * @version 1.0.0
 *
 * Yalıhan Bekçi Standards:
 * - Pure vanilla JS
 * - Smooth animations
 * - Accessibility support
 */

const UIHelpers = {
    /**
     * Smooth scroll to element and highlight it
     *
     * @param {string|HTMLElement} target - Element ID or element itself
     * @param {object} options - Scroll and highlight options
     */
    smoothScrollAndHighlight(target, options = {}) {
        const element =
            typeof target === 'string'
                ? document.getElementById(target) || document.querySelector(target)
                : target;

        if (!element) {
            console.warn('Element not found for smooth scroll:', target);
            return;
        }

        const {
            block = 'center',
            behavior = 'smooth',
            highlightDuration = 2000,
            highlightColor = 'rgba(59, 130, 246, 0.3)',
        } = options;

        // Smooth scroll
        element.scrollIntoView({
            behavior,
            block,
            inline: 'nearest',
        });

        // Highlight animation
        this.highlightElement(element, highlightDuration, highlightColor);
    },

    /**
     * Highlight element with animation
     *
     * @param {HTMLElement} element - Element to highlight
     * @param {number} duration - Duration in milliseconds
     * @param {string} color - Highlight color
     */
    highlightElement(element, duration = 2000, color = 'rgba(59, 130, 246, 0.3)') {
        const originalBg = element.style.backgroundColor;
        const originalTransition = element.style.transition;

        element.style.transition = 'background-color 0.3s ease-out';
        element.style.backgroundColor = color;

        setTimeout(() => {
            element.style.backgroundColor = originalBg;
            setTimeout(() => {
                element.style.transition = originalTransition;
            }, 300);
        }, duration);
    },

    /**
     * Show loading spinner on element
     *
     * @param {string|HTMLElement} target - Element to show spinner on
     * @returns {function} Function to hide spinner
     */
    showLoading(target) {
        const element = typeof target === 'string' ? document.querySelector(target) : target;

        if (!element) return () => {};

        const spinner = document.createElement('div');
        spinner.className =
            'loading-spinner absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-gray-900/80 z-50';
        spinner.innerHTML = `
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        `;

        element.style.position = 'relative';
        element.appendChild(spinner);

        return () => {
            if (spinner.parentNode) {
                spinner.parentNode.removeChild(spinner);
            }
        };
    },

    /**
     * Confirm dialog (modern replacement for window.confirm)
     *
     * @param {string} message - Confirmation message
     * @param {object} options - Dialog options
     * @returns {Promise<boolean>} User's choice
     */
    async confirm(message, options = {}) {
        return new Promise((resolve) => {
            const {
                title = 'Onay',
                confirmText = 'Evet',
                cancelText = 'Hayır',
                confirmClass = 'bg-blue-600 hover:bg-blue-700 text-white',
                cancelClass = 'bg-gray-200 hover:bg-gray-300 text-gray-700 dark:text-slate-300',
            } = options;

            const dialog = document.createElement('div');
            dialog.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black/50';
            dialog.innerHTML = `
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 max-w-md w-full mx-4 transform scale-95 transition-transform duration-200">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">${title}</h3>
                    <p class="text-gray-600 dark:text-gray-300 mb-6">${message}</p>
                    <div class="flex justify-end gap-3">
                        <button class="cancel-btn px-4 py-2 rounded-lg font-semibold transition-colors ${cancelClass}">
                            ${cancelText}
                        </button>
                        <button class="confirm-btn px-4 py-2 rounded-lg font-semibold transition-colors ${confirmClass}">
                            ${confirmText}
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(dialog);
            setTimeout(() => dialog.firstElementChild.classList.remove('scale-95'), 10);

            const cleanup = () => {
                dialog.firstElementChild.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    if (dialog.parentNode) {
                        dialog.parentNode.removeChild(dialog);
                    }
                }, 200);
            };

            dialog.querySelector('.confirm-btn').onclick = () => {
                cleanup();
                resolve(true);
            };

            dialog.querySelector('.cancel-btn').onclick = () => {
                cleanup();
                resolve(false);
            };

            dialog.onclick = (e) => {
                if (e.target === dialog) {
                    cleanup();
                    resolve(false);
                }
            };
        });
    },

    /**
     * Update list item after AJAX operation
     *
     * @param {string} listId - List container ID
     * @param {object} newItem - New item data
     * @param {function} renderFn - Function to render item HTML
     */
    updateList(listId, newItem, renderFn) {
        const list = document.getElementById(listId) || document.querySelector(listId);
        if (!list) return;

        const itemHtml = renderFn(newItem);
        list.insertAdjacentHTML('afterbegin', itemHtml);

        // Highlight new item
        const newElement = list.firstElementChild;
        this.smoothScrollAndHighlight(newElement);
    },
};

// Global availability
window.UIHelpers = UIHelpers;
window.smoothScroll = (target, options) => UIHelpers.smoothScrollAndHighlight(target, options);
window.showLoading = (target) => UIHelpers.showLoading(target);
window.confirmDialog = (message, options) => UIHelpers.confirm(message, options);
