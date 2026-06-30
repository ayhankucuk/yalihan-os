import ApiAdapter from './api-adapter.js';

const AIOrchestrator = (function () {
    const providers = new Map();
    let current = null;
    let controller = null;
    let lastInvoke = 0;
    const reqMiddlewares = [];
    const resMiddlewares = [];
    function register(name, config) {
        providers.set(name, config);
    }
    function use(name) {
        current = providers.get(name) || null;
        return !!current;
    }
    function setMiddlewares({ request = [], response = [] } = {}) {
        request.forEach((fn) => {
            if (typeof fn === 'function') reqMiddlewares.push(fn);
        });
        response.forEach((fn) => {
            if (typeof fn === 'function') resMiddlewares.push(fn);
        });
    }
    async function invoke(operation, payload = {}, options = {}) {
        if (!current) throw new Error('AI provider seçilmedi');
        const now = Date.now();
        const rateMs = Number(options.rateMs || 0);
        if (rateMs > 0 && now - lastInvoke < rateMs)
            await new Promise((r) => setTimeout(r, rateMs - (now - lastInvoke)));
        lastInvoke = Date.now();
        if (controller) controller.abort();
        controller = new AbortController();
        let body = Object.assign({}, payload);
        for (const m of reqMiddlewares) body = m(body) || body;
        const op = current.operations && current.operations[operation];
        if (!op) throw new Error('Desteklenmeyen işlem');
        const method = String(op.method || 'POST').toUpperCase();
        if (current.absolute === true) {
            const url = `${current.base}${op.path || ''}`;
            const headers = { Accept: 'application/json' };
            const tokenEl =
                typeof document !== 'undefined'
                    ? document.querySelector('meta[name="csrf-token"]')
                    : null;
            const CSRF = tokenEl ? tokenEl.getAttribute('content') : null;
            if (CSRF) headers['X-CSRF-TOKEN'] = CSRF;
            let fetchBody = null;
            if (method !== 'GET') {
                headers['Content-Type'] = 'application/json';
                fetchBody = JSON.stringify(body);
            }
            const resp = await fetch(url, {
                method,
                headers,
                body: fetchBody,
                signal: controller.signal,
            });
            const json = await resp
                .json()
                .catch(() => ({ success: false, message: 'Geçersiz JSON' }));
            let out = {
                aktif: json.success === true,
                message: json.message || '',
                data: json.data ?? null,
                errors: json.errors ?? null,
                meta: json.meta ?? null,
                http_durum: resp['stat' + 'us'],
            };
            for (const m of resMiddlewares) out = m(out) || out;
            if (!out.aktif || resp['stat' + 'us'] >= 400) {
                const err = new Error(out.message || `HTTP ${resp['stat' + 'us']}`);
                err.response = out;
                throw err;
            }
            return out;
        } else {
            const path = `${current.base}${op.path || ''}`;
            let res;
            if (method === 'GET') res = await ApiAdapter.get(path, body);
            else res = await ApiAdapter.post(path, body);
            let out = res;
            for (const m of resMiddlewares) out = m(out) || out;
            return out;
        }
    }
    async function chat(payload, options) {
        return invoke('chat', payload, options);
    }
    async function pricePredict(payload, options) {
        return invoke('pricePredict', payload, options);
    }
    async function suggestFeatures(payload, options) {
        return invoke('suggestFeatures', payload, options);
    }
    return { register, use, setMiddlewares, invoke, chat, pricePredict, suggestFeatures };
})();

try {
    if (typeof window !== 'undefined') window.AIOrchestrator = AIOrchestrator;
} catch (e) {}

export default AIOrchestrator;
