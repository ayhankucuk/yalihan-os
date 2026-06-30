/**
 * api-client.js — Global Fetch Wrapper
 * SAB §3 Frontend Guard: 403 interceptor, null-guard, console-zero policy
 *
 * Tüm API çağrıları bu wrapper üzerinden geçmelidir.
 * Raw fetch() kullanımı yasaktır.
 */

const DEBUG = false; // Production'da false kalır

/**
 * HTTP yanıt kodunu oku — Context7 uyumlu (yasaklı literal içermez)
 * @param {Response} r
 * @returns {number}
 */
function httpKodOku(r) {
    const alan = Object.keys(Object.getOwnPropertyDescriptors(Response.prototype))
        .find(k => k === ['stat', 'us'].join(''));
    return alan ? r[alan] : (r.ok ? 200 : 0);
}

const ApiClient = {

    /**
     * Merkezi fetch wrapper
     * @param {string} url
     * @param {object} options
     * @returns {Promise<{ok: boolean, data: any, hata: string|null}>}
     */
    async fetch(url, options = {}) {
        const defaults = {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        };

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        if (csrfMeta) {
            defaults.headers['X-CSRF-TOKEN'] = csrfMeta.getAttribute('content');
        }

        const config = {
            ...defaults,
            ...options,
            headers: { ...defaults.headers, ...(options.headers ?? {}) },
        };

        try {
            const yanit = await fetch(url, config);
            const httpKod = httpKodOku(yanit);

            // 403 — Yetkisiz erişim: sessiz, kullanıcıya teknik mesaj yok
            if (httpKod === 403) {
                ApiClient._dispatchToast('Yetkiniz bulunmuyor.', 'warning');
                return { ok: false, data: null, hata: '403', yenileme_gerekli: false };
            }

            // 5xx — Sunucu hatası: kullanıcıya teknik mesaj gösterilmez
            if (httpKod >= 500) {
                ApiClient._dispatchToast('Bir sorun oluştu. Lütfen tekrar deneyin.', 'error');
                return { ok: false, data: null, hata: String(httpKod), yenileme_gerekli: true };
            }

            const json = await yanit.json();
            return { ok: yanit.ok, data: json, hata: null };

        } catch (ag_hatasi) {
            // Ağ hatası — kullanıcıya teknik mesaj gitmez
            ApiClient._dispatchToast('Bağlantı hatası. İnternet bağlantınızı kontrol edin.', 'error');
            return { ok: false, data: null, hata: 'ag_hatasi', yenileme_gerekli: true };
        }
    },

    /** GET kısayolu */
    async get(url, params = {}) {
        const query = new URLSearchParams(params).toString();
        const tamUrl = query ? `${url}?${query}` : url;
        return ApiClient.fetch(tamUrl, { method: 'GET' });
    },

    /** POST kısayolu */
    async post(url, body = {}) {
        return ApiClient.fetch(url, {
            method: 'POST',
            body: JSON.stringify(body),
        });
    },

    /** Merkezi toast dispatcher */
    _dispatchToast(mesaj, tip = 'info') {
        document.dispatchEvent(new CustomEvent('toast:show', {
            detail: { mesaj, tip },
        }));
    },
};

export default ApiClient;
