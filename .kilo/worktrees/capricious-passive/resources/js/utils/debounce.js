/**
 * Debounce Utility - Kullanıcı girişi ve API çağrılarını optimize eder
 * 
 * @context7 YALIHAN_BEKCI_APPROVED
 * - Performans optimizasyonu için debounce ve throttle
 * - Map search ve reverse geocoding için kullanılır
 * - Memory leak önleme ile temiz cleanup
 */

/**
 * Debounce - Son çağrıdan sonra belirtilen süre geçene kadar bekler
 * Kullanım: Kullanıcı yazma bitene kadar API çağrısı yapma
 * 
 * @param {Function} func - Çalıştırılacak fonksiyon
 * @param {number} wait - Bekleme süresi (ms)
 * @returns {Function} Debounced fonksiyon
 * 
 * @example
 * const searchAddress = debounce((query) => {
 *     fetch(`/api/v1/geo/geocode`, { body: JSON.stringify({ query }) });
 * }, 500);
 * 
 * searchInput.addEventListener('input', (e) => searchAddress(e.target.value));
 */
export function debounce(func, wait = 300) {
    let timeout;
    
    const debounced = function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func.apply(this, args);
        };
        
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
    
    // Cleanup method - Memory leak önleme
    debounced.cancel = function() {
        clearTimeout(timeout);
    };
    
    return debounced;
}

/**
 * Throttle - Belirtilen süre içinde sadece bir kez çalışır
 * Kullanım: Scroll veya resize olaylarında performans
 * 
 * @param {Function} func - Çalıştırılacak fonksiyon
 * @param {number} limit - Minimum çalışma aralığı (ms)
 * @returns {Function} Throttled fonksiyon
 * 
 * @example
 * const updateMapBounds = throttle((bounds) => {
 *     console.log('Map bounds updated:', bounds);
 * }, 1000);
 * 
 * map.on('moveend', () => updateMapBounds(map.getBounds()));
 */
export function throttle(func, limit = 1000) {
    let inThrottle;
    let lastResult;
    
    const throttled = function executedFunction(...args) {
        if (!inThrottle) {
            lastResult = func.apply(this, args);
            inThrottle = true;
            
            setTimeout(() => {
                inThrottle = false;
            }, limit);
        }
        
        return lastResult;
    };
    
    // Cleanup method
    throttled.cancel = function() {
        inThrottle = false;
    };
    
    return throttled;
}

/**
 * Advanced Debounce - Leading ve trailing edge desteği ile
 * 
 * @param {Function} func - Çalıştırılacak fonksiyon
 * @param {number} wait - Bekleme süresi (ms)
 * @param {Object} options - { leading: boolean, trailing: boolean }
 * @returns {Function} Advanced debounced fonksiyon
 * 
 * @example
 * // İlk tıklamada hemen çalış, sonra debounce
 * const saveForm = debounceAdvanced(handleSave, 2000, { leading: true, trailing: false });
 */
export function debounceAdvanced(func, wait = 300, options = {}) {
    const { leading = false, trailing = true } = options;
    let timeout;
    let result;
    
    const debounced = function executedFunction(...args) {
        const context = this;
        
        const later = () => {
            timeout = null;
            if (trailing) {
                result = func.apply(context, args);
            }
        };
        
        const callNow = leading && !timeout;
        
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
        
        if (callNow) {
            result = func.apply(context, args);
        }
        
        return result;
    };
    
    debounced.cancel = function() {
        clearTimeout(timeout);
        timeout = null;
    };
    
    return debounced;
}

/**
 * RequestAnimationFrame Throttle - Scroll/resize için optimize
 * 
 * @param {Function} func - Çalıştırılacak fonksiyon
 * @returns {Function} RAF throttled fonksiyon
 */
export function rafThrottle(func) {
    let rafId = null;
    
    const throttled = function executedFunction(...args) {
        if (rafId !== null) {
            return;
        }
        
        rafId = requestAnimationFrame(() => {
            func.apply(this, args);
            rafId = null;
        });
    };
    
    throttled.cancel = function() {
        if (rafId !== null) {
            cancelAnimationFrame(rafId);
            rafId = null;
        }
    };
    
    return throttled;
}

// Browser uyumluluğu kontrolü
if (typeof window !== 'undefined') {
    window.YalihanUtils = window.YalihanUtils || {};
    window.YalihanUtils.debounce = debounce;
    window.YalihanUtils.throttle = throttle;
    window.YalihanUtils.debounceAdvanced = debounceAdvanced;
    window.YalihanUtils.rafThrottle = rafThrottle;
}
