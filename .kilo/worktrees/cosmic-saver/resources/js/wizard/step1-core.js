/**
 * Step 1 Core Utilities
 * Context7: Production-safe logging and coordinate helpers
 */

// ✅ SAB: Production-safe logging
const DEBUG = import.meta.env.DEV || window.APP_DEBUG === true;
export const logger = {
    log: (...args) => DEBUG && console.log(...args),
    warn: (...args) => DEBUG && console.warn(...args),
    error: (...args) => console.error(...args), // Errors always logged
    info: (...args) => DEBUG && console.info(...args),
    debug: (...args) => DEBUG && console.debug(...args),
};

/**
 * Save coordinates to form
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 */
export function saveCoordinates(lat, lng) {
    const form = document.getElementById('ilan-wizard-form');
    if (!form) return;

    // ✅ SAB: lat/lng standart, enlem/boylam/latitude/longitude YASAK
    let latInput = document.querySelector('[name="lat"]');
    let lngInput = document.querySelector('[name="lng"]');

    if (!latInput) {
        latInput = document.createElement('input');
        latInput.type = 'hidden';
        latInput.name = 'lat'; // ✅ SAB
        form.appendChild(latInput);
    }

    if (!lngInput) {
        lngInput = document.createElement('input');
        lngInput.type = 'hidden';
        lngInput.name = 'lng'; // ✅ SAB
        form.appendChild(lngInput);
    }

    latInput.value = lat.toFixed(6);
    lngInput.value = lng.toFixed(6);
}

/**
 * Reverse geocoding helper
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @param {Function} callback - Callback function
 */
export function reverseGeocode(lat, lng, callback) {
    // ✅ SAB: Merkezi config kullan, fallback yok
    if (!window.APIConfig?.geo?.reverseGeocode) {
        throw new Error('API endpoint yapılandırılmamış: geo.reverseGeocode');
    }
    const url = window.APIConfig.geo.reverseGeocode;
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            Accept: 'application/json',
        },
        body: JSON.stringify({
            latitude: lat,
            longitude: lng,
        }),
    })
        .then((res) => res.json())
        .then((result) => {
            const data = result.success ? result.data : result;
            if (data?.address) {
                const addr = data.address;
                const parts = [];
                if (addr.road) parts.push(addr.road);
                if (addr.house_number) parts.push('No:' + addr.house_number);
                if (addr.suburb || addr.neighbourhood)
                    parts.push(addr.suburb || addr.neighbourhood);
                if (addr.town || addr.city_district) parts.push(addr.town || addr.city_district);
                if (addr.province || addr.state) parts.push(addr.province || addr.state);
                callback(parts.join(', '));
            }
        })
        .catch(() => callback(null));
}

/**
 * Reverse geocoding detailed helper
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @returns {Promise<Object|null>} Detailed reverse geocode data
 */
export async function reverseGeocodeDetailed(lat, lng) {
    if (!window.APIConfig?.geo?.reverseGeocode) {
        throw new Error('API endpoint yapılandırılmamış: geo.reverseGeocode');
    }
    const url = window.APIConfig.geo.reverseGeocode;
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                Accept: 'application/json',
            },
            body: JSON.stringify({ latitude: lat, longitude: lng }),
        });
        const result = await res.json();
        return result?.data || result || null;
    } catch {
        return null;
    }
}

export function selectLocationFromReverseGeocode(data) {
    if (!data) return;
    const addr = data.address || {};
    const provinceId = data.province_id || data.il_id || null;
    const districtId = data.district_id || data.ilce_id || null;
    const neighborhoodId = data.neighborhood_id || data.mahalle_id || null;

    const ilSelect = document.getElementById('il_id');
    const ilceSelect = document.getElementById('ilce_id');
    const mahalleSelect = document.getElementById('mahalle_id');
    const adresField = document.getElementById('adres') || document.querySelector('[name="adres"]');

    // ✅ Açık Adres field'ını güncelle
    if (adresField && data.formatted_address) {
        adresField.value = data.formatted_address;
        adresField.classList.add('ring-2', 'ring-green-500');
        setTimeout(() => adresField.classList.remove('ring-2', 'ring-green-500'), 2000);
    } else if (adresField && addr) {
        // Fallback: Address objesinden adres string'i oluştur
        const addressParts = [];
        if (addr.road || addr.street) addressParts.push(addr.road || addr.street);
        if (addr.house_number) addressParts.push(addr.house_number);
        if (addr.neighbourhood || addr.neighborhood) addressParts.push(addr.neighbourhood || addr.neighborhood);
        if (addr.town || addr.city_district) addressParts.push(addr.town || addr.city_district);
        if (addr.city || addr.county) addressParts.push(addr.city || addr.county);
        if (addr.province || addr.state) addressParts.push(addr.province || addr.state);
        
        if (addressParts.length > 0) {
            adresField.value = addressParts.join(', ');
            adresField.classList.add('ring-2', 'ring-green-500');
            setTimeout(() => adresField.classList.remove('ring-2', 'ring-green-500'), 2000);
        }
    }

    if (ilSelect) {
        if (provinceId) {
            ilSelect.value = String(provinceId);
            ilSelect.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (addr.province || addr.state) {
            const target = (addr.province || addr.state).toString().toUpperCase();
            for (const opt of ilSelect.options) {
                if (opt.text.toUpperCase().includes(target)) {
                    ilSelect.value = opt.value;
                    ilSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    break;
                }
            }
        }
    }

    const setDistrict = () => {
        if (!ilceSelect) return;
        if (districtId) {
            ilceSelect.value = String(districtId);
            ilceSelect.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (addr.town || addr.city || addr.city_district || addr.county) {
            const target = (addr.town || addr.city_district || addr.city || addr.county)
                .toString()
                .toUpperCase();
            for (const opt of ilceSelect.options) {
                if (opt.text.toUpperCase().includes(target)) {
                    ilceSelect.value = opt.value;
                    ilceSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    break;
                }
            }
        }
    };

    const setNeighborhood = () => {
        if (!mahalleSelect) return;
        if (neighborhoodId) {
            mahalleSelect.value = String(neighborhoodId);
            mahalleSelect.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (addr.neighbourhood || addr.neighborhood || addr.suburb) {
            const target = (addr.neighbourhood || addr.neighborhood || addr.suburb)
                .toString()
                .toUpperCase();
            for (const opt of mahalleSelect.options) {
                if (opt.text.toUpperCase().includes(target)) {
                    mahalleSelect.value = opt.value;
                    mahalleSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    break;
                }
            }
        }
    };

    setDistrict();
    setTimeout(setDistrict, 300);
    setTimeout(setNeighborhood, 600);
}

/**
 * Escape string for HTML attributes
 * @param {string} str - String to escape
 * @returns {string} Escaped string
 */
export function escapeForAttribute(str) {
    if (!str) return '';
    return String(str)
        .replace(/\\/g, '\\\\')
        .replace(/'/g, "\\'")
        .replace(/"/g, '\\"')
        .replace(/\n/g, '\\n')
        .replace(/\r/g, '\\r');
}

/**
 * Copy to clipboard helper
 * @param {string} text - Text to copy
 * @param {string} label - Label for toast message
 */
export function copyToClipboard(text, label = 'Metin') {
    if (!text) {
        logger.warn('Kopyalanacak metin yok');
        return;
    }

    // Modern Clipboard API
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
            .writeText(text)
            .then(() => {
                if (window.toast) {
                    window.toast.success(`${label} panoya kopyalandı: ${text}`);
                } else {
                    alert(`${label} panoya kopyalandı:\n${text}`);
                }
                logger.log(`✅ ${label} kopyalandı:`, text);
            })
            .catch((err) => {
                logger.error('Kopyalama hatası:', err);
                fallbackCopyToClipboard(text, label);
            });
    } else {
        fallbackCopyToClipboard(text, label);
    }
}

/**
 * Fallback copy to clipboard (old browsers)
 * @param {string} text - Text to copy
 * @param {string} label - Label for toast message
 */
function fallbackCopyToClipboard(text, label) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            if (window.toast) {
                window.toast.success(`${label} panoya kopyalandı: ${text}`);
            } else {
                alert(`${label} panoya kopyalandı:\n${text}`);
            }
            logger.log(`✅ ${label} kopyalandı (fallback):`, text);
        } else {
            logger.error('Kopyalama başarısız (fallback)');
            if (window.toast) {
                window.toast.error('Kopyalama başarısız oldu.');
            } else {
                alert('Kopyalama başarısız oldu.');
            }
        }
    } catch (err) {
        logger.error('Kopyalama hatası (fallback):', err);
        if (window.toast) {
            window.toast.error('Kopyalama sırasında hata oluştu.');
        } else {
            alert('Kopyalama sırasında hata oluştu.');
        }
    } finally {
        document.body.removeChild(textArea);
    }
}

// Export to window for global access
if (typeof window !== 'undefined') {
    window.copyToClipboard = copyToClipboard;
    window.logger = logger; // For Alpine.js components in Blade templates
    window.selectLocationFromReverseGeocode = selectLocationFromReverseGeocode;
    window.reverseGeocodeDetailed = reverseGeocodeDetailed;
}
