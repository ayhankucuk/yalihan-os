/**
 * Critical JavaScript - Essential functionality for above-the-fold content
 * Context7: Critical JS for AI Settings page
 */

// Global toast system
window.toast = {
    success: function (message) {
        this.show(message, 'success');
    },

    error: function (message) {
        this.show(message, 'error');
    },

    info: function (message) {
        this.show(message, 'info');
    },

    show: function (message, type = 'info') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.toast');
        existingToasts.forEach((toast) => toast.remove());

        // Create new toast
        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        toast.textContent = message;

        // Add to DOM
        document.body.appendChild(toast);

        // Show toast
        setTimeout(() => toast.classList.add('show'), 10);

        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    },
};

// Global loading state manager
window.loading = {
    show: function (element) {
        if (element) {
            element.classList.add('loading');
        }
    },

    hide: function (element) {
        if (element) {
            element.classList.remove('loading');
        }
    },
};

// Provider status management
window.providerStatus = {
    update: function (provider, status, message, responseTime) {
        const badge = document.getElementById(`${provider}-status`);
        if (!badge) return;

        let icon, bgClass, textClass, statusText;

        switch (status) {
            case 'success':
                icon = 'fa-check-circle';
                bgClass = 'bg-green-100 dark:bg-green-900';
                textClass = 'text-green-700 dark:text-green-300';
                statusText = `✅ Aktif (${responseTime}ms)`;
                break;
            case 'error':
                icon = 'fa-times-circle';
                bgClass = 'bg-red-100 dark:bg-red-900';
                textClass = 'text-red-700 dark:text-red-300';
                statusText = '❌ Hata';
                break;
            case 'testing':
                icon = 'fa-spinner fa-spin';
                bgClass = 'bg-blue-100 dark:bg-blue-900';
                textClass = 'text-blue-700 dark:text-blue-300';
                statusText = '🔄 Test ediliyor...';
                break;
            default:
                icon = 'fa-circle';
                bgClass = 'bg-gray-100 dark:bg-gray-700';
                textClass = 'text-gray-600 dark:text-gray-400';
                statusText = 'Henüz Test Edilmedi';
        }

        badge.className = `px-3 py-1 text-xs font-medium ${bgClass} ${textClass} rounded-full flex items-center gap-1`;
        badge.innerHTML = `<i class="fas ${icon}"></i><span>${statusText}</span>`;
        badge.title = message;
    },
};

// CSRF token helper
window.csrfToken = function () {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
};

// Fetch helper with CSRF
window.fetchWithCSRF = function (url, options = {}) {
    const defaultOptions = {
        headers: {
            'X-CSRF-TOKEN': window.csrfToken(),
            'Content-Type': 'application/json',
            ...options.headers,
        },
    };

    return fetch(url, { ...defaultOptions, ...options });
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    console.log('✅ Critical JS loaded - AI Settings ready');

    // Initialize provider statuses
    const providers = ['google', 'openai', 'anthropic', 'deepseek', 'ollama'];
    providers.forEach((provider) => {
        window.providerStatus.update(provider, 'unknown', 'Henüz test edilmedi', 0);
    });
});

// Export for global access
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        toast: window.toast,
        loading: window.loading,
        providerStatus: window.providerStatus,
        csrfToken: window.csrfToken,
        fetchWithCSRF: window.fetchWithCSRF,
    };
}
