/**
 * 🛡️ HTML Sanitization Utilities
 *
 * Prevents XSS by escaping dynamic values before DOM injection.
 */

/**
 * Escape HTML special characters to prevent XSS.
 * Use this for any dynamic value injected via innerHTML/template literals.
 *
 * @param {string|number|null|undefined} str
 * @returns {string}
 */
function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// Make globally available
window.escapeHtml = escapeHtml;
