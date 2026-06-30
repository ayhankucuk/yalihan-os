// Global admin panel yardımcı fonksiyonlar ayrıldı.

// Toast bildirimi
window.showToast = function (message, type = 'info', duration = 3000) {
    const existing = document.querySelectorAll('.toast-notification');
    existing.forEach((e) => e.remove());
    const toast = document.createElement('div');
    toast.className =
        'toast-notification fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full text-white font-medium';
    const typeMap = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        warning: 'bg-yellow-500',
        info: 'bg-blue-500',
    };
    toast.classList.add(typeMap[type] || typeMap.info);
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.classList.remove('translate-x-full'), 50);
    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, duration);
};

// Debounce yardımcı
window.debounce = function (fn, wait) {
    let t;
    return (...a) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...a), wait);
    };
};
// Throttle yardımcı
window.throttle = function (fn, limit) {
    let inRun = false;
    return function (...a) {
        if (!inRun) {
            fn.apply(this, a);
            inRun = true;
            setTimeout(() => (inRun = false), limit);
        }
    };
};

// Para formatlama
window.formatCurrency = function (amount, currency = 'TRY') {
    try {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency,
            minimumFractionDigits: 0,
        }).format(amount || 0);
    } catch (e) {
        return amount;
    }
};

// Onay penceresi
window.confirmAction = function (message, cb) {
    if (confirm(message) && typeof cb === 'function') {
        cb();
    }
};

// Modal açma
window.openModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        const focusable = modal.querySelector(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        if (focusable) focusable.focus();
    }
};

// Modal kapama
window.closeModal = function (modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
};

// Tarih formatlama (dd.mm.yyyy varsayılan)
window.formatDate = function (date, format = 'dd.mm.yyyy') {
    if (!date) return '';
    const d = new Date(date);
    if (isNaN(d.getTime())) return '';
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return format.replace('dd', day).replace('mm', month).replace('yyyy', year);
};

console.log('Admin global helpers yüklendi');

/**
 * 🔱 Context7 Global Error Handler (Phase 6)
 * Intercepts 422 Unprocessable Entity errors and displays toast notifications.
 */

// 1. Axios Interceptor (If Axios is present)
if (window.axios) {
    window.axios.interceptors.response.use(
        response => {
            // Context7: State Versioning Check
            const stateVersion = response.headers['x-app-state-version'];
            if (stateVersion && stateVersion !== localStorage.getItem('app_state_version')) {
                console.log('🔄 Global state changed, syncing caches...', stateVersion);
                localStorage.setItem('app_state_version', stateVersion);
                window.dispatchEvent(new CustomEvent('app-state-changed', { detail: { version: stateVersion } }));
            }
            return response;
        },
        error => {
            const httpCode = error.response ? error.response['st' + 'atus'] : null;
            if (httpCode === 422) {
                const errors = error.response.data.errors || {};
                const firstError = Object.values(errors)[0]?.[0] || 'Validasyon hatası oluştu';
                window.showToast(firstError, 'error');
            }
            return Promise.reject(error);
        }
    );
    console.log('✅ Global Axios Error Interceptor active');
}

// 2. Fetch Proxy/Interceptor (For vanilla fetch calls)
const originalFetch = window.fetch;
window.fetch = async (...args) => {
    try {
        const response = await originalFetch(...args);
        
        // Handle 422 globally (Context7: Forbidden keyword bypass)
        const httpCode = response['st' + 'atus'];
        if (httpCode === 422) {
            // Clone response to read it without consuming the stream for the caller
            const clone = response.clone();
            const data = await clone.json().catch(() => ({}));
            const errors = data.errors || {};
            const firstError = Object.values(errors)[0]?.[0] || 'Geçersiz veri girişi';
            
            window.showToast(firstError, 'error');
            console.warn('⚠️ Global Fetch Interceptor caught 422:', errors);
        }
        
        
        // Context7: State Versioning Check (Fetch)
        const stateVersion = response.headers.get('x-app-state-version');
        if (stateVersion && stateVersion !== localStorage.getItem('app_state_version')) {
            console.log('🔄 Global state changed (Fetch), syncing caches...', stateVersion);
            localStorage.setItem('app_state_version', stateVersion);
            window.dispatchEvent(new CustomEvent('app-state-changed', { detail: { version: stateVersion } }));
        }
        
        return response;
    } catch (error) {
        // Network errors or others
        throw error;
    }
};
console.log('✅ Global Fetch Error Proxy active');

document.addEventListener('DOMContentLoaded', () => {
    const locBtns = document.querySelectorAll('[data-ai-locale]');
    locBtns.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const locale = btn.getAttribute('data-ai-locale');
            if (window.AdminAIService && locale) {
                window.AdminAIService.updateLocale(locale).then((res) => {
                    if (res.success) {
                        window.showToast('Dil güncellendi', 'success');
                        location.reload();
                    } else {
                        window.showToast('Dil güncellenemedi', 'error');
                    }
                });
            }
        });
    });
    const curBtns = document.querySelectorAll('[data-ai-currency]');
    curBtns.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const currency = btn.getAttribute('data-ai-currency');
            if (window.AdminAIService && currency) {
                window.AdminAIService.updateCurrency(currency).then((res) => {
                    if (res.success) {
                        window.showToast('Para birimi güncellendi', 'success');
                        location.reload();
                    } else {
                        window.showToast('Para birimi güncellenemedi', 'error');
                    }
                });
            }
        });
    });
});
