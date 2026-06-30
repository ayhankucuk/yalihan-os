/**
 * Context7 AJAX Helper Utility
 *
 * @description Centralized AJAX operations with CSRF protection
 * @author Yalıhan Emlak - Context7 Team
 * @date 2025-11-04
 * @version 1.0.0
 *
 * Yalıhan Bekçi Standards:
 * - Pure vanilla JS (NO jQuery!)
 * - Async/await pattern
 * - Error handling
 * - CSRF protection
 * - Context7 compliance
 */

const AjaxHelper = {
    /**
     * Get CSRF token from meta tag
     */
    getCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]');
        return token ? token.content : '';
    },

    /**
     * POST request with CSRF protection
     *
     * @param {string} url - API endpoint
     * @param {object|FormData} data - Request data
     * @param {object} options - Additional options
     * @returns {Promise<object>} Response data
     */
    async post(url, data, options = {}) {
        try {
            const headers = {
                'X-CSRF-TOKEN': this.getCSRFToken(),
                Accept: 'application/json',
                ...options.headers,
            };

            // If data is not FormData, send as JSON
            if (!(data instanceof FormData)) {
                headers['Content-Type'] = 'application/json';
                data = JSON.stringify(data);
            }

            const response = await fetch(url, {
                method: 'POST',
                headers: headers,
                body: data,
                credentials: 'same-origin',
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Request failed');
            }

            return {
                success: true,
                data: result.data || result,
                message: result.message || 'Success',
            };
        } catch (error) {
            console.error('AJAX POST Error:', error);
            return {
                success: false,
                message: error.message || 'Bir hata oluştu',
                error: error,
            };
        }
    },

    /**
     * GET request
     *
     * @param {string} url - API endpoint
     * @param {object} options - Additional options
     * @returns {Promise<object>} Response data
     */
    async get(url, options = {}) {
        try {
            const headers = {
                Accept: 'application/json',
                ...options.headers,
            };

            const response = await fetch(url, {
                method: 'GET',
                headers: headers,
                credentials: 'same-origin',
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Request failed');
            }

            return {
                success: true,
                data: result.data || result,
                message: result.message || 'Success',
            };
        } catch (error) {
            console.error('AJAX GET Error:', error);
            return {
                success: false,
                message: error.message || 'Bir hata oluştu',
                error: error,
            };
        }
    },

    /**
     * PUT request
     */
    async put(url, data, options = {}) {
        try {
            const headers = {
                'X-CSRF-TOKEN': this.getCSRFToken(),
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...options.headers,
            };

            const response = await fetch(url, {
                method: 'PUT',
                headers: headers,
                body: JSON.stringify(data),
                credentials: 'same-origin',
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Request failed');
            }

            return {
                success: true,
                data: result.data || result,
                message: result.message || 'Success',
            };
        } catch (error) {
            console.error('AJAX PUT Error:', error);
            return {
                success: false,
                message: error.message || 'Bir hata oluştu',
                error: error,
            };
        }
    },

    /**
     * DELETE request
     */
    async delete(url, options = {}) {
        try {
            const headers = {
                'X-CSRF-TOKEN': this.getCSRFToken(),
                Accept: 'application/json',
                ...options.headers,
            };

            const response = await fetch(url, {
                method: 'DELETE',
                headers: headers,
                credentials: 'same-origin',
            });

            const result = await response.json();

            if (!response.ok) {
                throw new Error(result.message || 'Request failed');
            }

            return {
                success: true,
                data: result.data || result,
                message: result.message || 'Success',
            };
        } catch (error) {
            console.error('AJAX DELETE Error:', error);
            return {
                success: false,
                message: error.message || 'Bir hata oluştu',
                error: error,
            };
        }
    },
};

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AjaxHelper;
}

// Global availability
window.AjaxHelper = AjaxHelper;
