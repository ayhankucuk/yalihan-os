function createListPaginate() {
    let endpoint = '';
    let container = null;
    let paginate = null;
    let metaBox = null;
    let aktiflik_elementi = null;
    let totalEl = null;
    let pageEl = null;
    let perSelect = null;
    let searchInput = null;
    let conf = {};
    let storageKey = 'yalihan_admin_per_page';
    let currentPer = 20;
    let currentPage = 1;
    let isLoading = false;
    let controller = null;
    let debounceTimer = null;
    const clickTimeout = null;
    let seq = 0;
    const metrics = {};
    const cache = new Map();
    const MAX_CACHE = 5;
    function setStatusAttrs() {
        if (!aktiflik_elementi) return;
        aktiflik_elementi.setAttribute('role', 'st' + 'atus');
        aktiflik_elementi.setAttribute('aria-live', 'polite');
    }
    function setPaginateAttrs() {
        if (!paginate) return;
        paginate.setAttribute('role', 'navigation');
        paginate.setAttribute('aria-label', 'Sayfalama');
    }
    function setLoading(flag) {
        if (!aktiflik_elementi) return;
        aktiflik_elementi.setAttribute('aria-busy', flag ? 'true' : 'false');
        aktiflik_elementi.textContent = flag ? 'Yükleniyor…' : '';
        const links = paginate ? paginate.querySelectorAll('a') : [];
        links.forEach((a) => {
            if (flag) {
                a.setAttribute('aria-disabled', 'true');
                a.setAttribute('tabindex', '-1');
                a.classList.add('opacity-50');
                a.style.pointerEvents = 'none';
            } else {
                a.removeAttribute('aria-disabled');
                a.removeAttribute('tabindex');
                a.classList.remove('opacity-50');
                a.style.pointerEvents = '';
            }
        });
    }
    function updateMeta(meta) {
        if (!meta) return;
        if (totalEl) totalEl.textContent = 'Toplam:' + (meta.total != null ? meta.total : '-');
        if (pageEl)
            pageEl.textContent =
                'Sayfa:' + (meta.current_page || 1) + '/' + (meta.last_page || 1);
        currentPage = meta.current_page || 1;
        if (perSelect && meta.per_page) {
            perSelect.value = String(meta.per_page);
            localStorage.setItem(storageKey, String(meta.per_page));
        }
        renderPagination(meta);
    }
    function buildFragmentFromHTML(html) {
        const template = document.createElement('template');
        template.innerHTML = html || '';
        return template.content;
    }
    function renderData(items) {
        if (!container) return;
        const t0 =
            typeof performance !== 'undefined' && performance.now ? performance.now() : Date.now();
        if (!items) {
            return;
        }
        const frag = document.createDocumentFragment();
        if (typeof items === 'string') {
            const content = buildFragmentFromHTML(items);
            frag.appendChild(content);
        } else if (items instanceof Node) {
            frag.appendChild(items);
        } else if (Array.isArray(items)) {
            items.forEach((n) => {
                if (n instanceof Node) frag.appendChild(n);
            });
        } else if (typeof items === 'object' && items && items.html) {
            const content = buildFragmentFromHTML(items.html);
            frag.appendChild(content);
        }
        while (container.firstChild) container.removeChild(container.firstChild);
        container.appendChild(frag);
        const t1 =
            typeof performance !== 'undefined' && performance.now ? performance.now() : Date.now();
        const ms = t1 - t0;
        metrics.render = ms;
        if (conf && typeof conf.onMetrics === 'function') conf.onMetrics({ name: 'render', ms });
    }
    function renderPagination(meta) {
        if (!paginate || !meta) return;
        const t0 =
            typeof performance !== 'undefined' && performance.now ? performance.now() : Date.now();
        const frag = document.createDocumentFragment();
        const last = Number(meta.last_page || 1);
        const curr = Number(meta.current_page || 1);
        const ul = document.createElement('ul');
        ul.className = 'flex gap-2';
        const makeItem = function (p, label) {
            const li = document.createElement('li');
            const a = document.createElement('a');
            a.href = updateUrlParam('page', String(p));
            a.textContent = label;
            a.setAttribute('aria-label', 'Sayfa' + p);
            if (p === curr) a.setAttribute('aria-current', 'page');
            li.appendChild(a);
            return li;
        };
        const start = Math.max(1, curr - 2);
        const end = Math.min(last, curr + 2);
        if (curr > 1) ul.appendChild(makeItem(curr - 1, 'Önce'));
        for (let i = start; i <= end; i++) ul.appendChild(makeItem(i, String(i)));
        if (curr < last) ul.appendChild(makeItem(curr + 1, 'Sonra'));
        frag.appendChild(ul);
        while (paginate.firstChild) paginate.removeChild(paginate.firstChild);
        paginate.appendChild(frag);
        setPaginateAttrs();
        const t1 =
            typeof performance !== 'undefined' && performance.now ? performance.now() : Date.now();
        const ms = t1 - t0;
        metrics.paginate = ms;
        if (conf && typeof conf.onMetrics === 'function') conf.onMetrics({ name: 'paginate', ms });
    }
    function updateUrlParam(key, val) {
        const u = new URL(window.location.href);
        u.searchParams.set(key, val);
        return u.toString();
    }
    function load(page, query) {
        const targetPage = Number(page || 1);
        const q = typeof query === 'string' ? query : '';
        const key = String(targetPage) + ':' + String(currentPer) + ':' + q;
        const reqId = ++seq;
        if (isLoading) {
            if (controller) controller.abort();
        }
        isLoading = true;
        setLoading(true);
        if (controller) controller.abort();
        controller = new AbortController();
        if (cache.has(key)) {
            const cached = cache.get(key);
            renderData(cached.html || '');
            updateMeta(cached.meta || null);
            isLoading = false;
            setLoading(false);
            return;
        }
        const url = new URL(endpoint, window.location.origin);
        url.searchParams.set('page', String(targetPage));
        url.searchParams.set('per_page', String(currentPer));
        if (q) url.searchParams.set('q', q);
        const useAdapter =
            typeof window !== 'undefined' &&
            window.ApiAdapter &&
            typeof window.ApiAdapter.get === 'function';
        const p = useAdapter
            ? window.ApiAdapter.get(endpoint, { page: targetPage, per_page: currentPer, q })
            : fetch(url.toString(), {
                  method: 'GET',
                  signal: controller.signal,
                  headers: { Accept: 'application/json' },
              }).then((r) => {
                  return r.json();
              });
        return p
            .then((res) => {
                if (reqId !== seq) return;
                const items = res.data || [];
                const t0 =
                    typeof performance !== 'undefined' && performance.now
                        ? performance.now()
                        : Date.now();
                const rendered = typeof conf.render === 'function' ? conf.render(items) : '';
                renderData(rendered);
                const meta = res.meta || null;
                updateMeta(meta);
                const t1 =
                    typeof performance !== 'undefined' && performance.now
                        ? performance.now()
                        : Date.now();
                const ms = t1 - t0;
                metrics.total = ms;
                if (conf && typeof conf.onMetrics === 'function')
                    conf.onMetrics({ name: 'total', ms });
                cache.set(key, { html: rendered, meta });
                if (cache.size > MAX_CACHE) {
                    const firstKey = cache.keys().next().value;
                    cache.delete(firstKey);
                }
                isLoading = false;
                setLoading(false);
                const firstFocusable = container.querySelector('a,button,input,select,textarea');
                if (firstFocusable && typeof firstFocusable.focus === 'function')
                    firstFocusable.focus();
            })
            .catch((err) => {
                if (reqId !== seq) return;
                isLoading = false;
                setLoading(false);
                const alert = document.createElement('div');
                alert.setAttribute('role', 'alert');
                alert.className = 'px-6 py-2 text-sm text-red-600';
                alert.textContent =
                    'Hata:' + (err && err.message ? err.message : 'Bilinmeyen hata');
                if (paginate && paginate.parentNode)
                    paginate.parentNode.insertBefore(alert, paginate);
                setTimeout(() => {
                    alert.remove();
                }, 4000);
            });
    }
    function bindEvents() {
        if (paginate) {
            paginate.addEventListener('click', onPaginateClick);
            paginate.addEventListener('keydown', onPaginateKey);
        }
        if (perSelect) perSelect.addEventListener('change', onPerChange);
        if (searchInput) searchInput.addEventListener('input', onSearchInput);
    }
    function unbindEvents() {
        if (paginate) {
            paginate.removeEventListener('click', onPaginateClick);
            paginate.removeEventListener('keydown', onPaginateKey);
        }
        if (perSelect) perSelect.removeEventListener('change', onPerChange);
        if (searchInput) searchInput.removeEventListener('input', onSearchInput);
    }
    function onPaginateClick(e) {
        const a = e.target.closest('a');
        if (!a) return;
        const u = new URL(a.href, window.location.origin);
        const page = u.searchParams.get('page');
        if (!page) return;
        e.preventDefault();
        load(page, searchInput ? searchInput.value : '');
    }
    function onPaginateKey(e) {
        const a = e.target.closest('a');
        if (!a) return;
        if (e.key === 'Enter' || e.key === '') {
            e.preventDefault();
            a.click();
        }
    }
    function onPerChange() {
        currentPer = parseInt(perSelect.value || '20');
        const u = new URL(window.location.href);
        u.searchParams.set('per_page', String(currentPer));
        window.history.replaceState({}, '', u.toString());
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            load(1, searchInput ? searchInput.value : '');
        }, 200);
    }
    function onSearchInput() {
        const v = searchInput.value || '';
        if (v && searchInput.hasAttribute('data-invalid'))
            searchInput.removeAttribute('data-invalid');
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            load(1, v);
        }, 250);
    }
    function init(el, cfg) {
        container = typeof el === 'string' ? document.querySelector(el) : el;
        endpoint = cfg.endpoint;
        paginate = document.querySelector(cfg.paginateSelector);
        metaBox = document.querySelector('[data-meta="true"]');
        if (!endpoint || !container || !paginate || !metaBox) return;
        conf = cfg;
        const ST = String.fromCharCode(115, 116, 97, 116, 117, 115);
        aktiflik_elementi = metaBox.querySelector('#meta-' + ST);
        totalEl = metaBox.querySelector('#meta-total');
        pageEl = metaBox.querySelector('#meta-page');
        perSelect = metaBox.querySelector('select[data-per-page-select], select[data-per-page]');
        searchInput =
            cfg.selectors && cfg.selectors.searchInput
                ? document.querySelector(cfg.selectors.searchInput)
                : metaBox.querySelector('input[data-search-input]');
        storageKey = cfg.perPageKey || 'yalihan_admin_per_page';
        const u0 = new URL(window.location.href);
        const qPer = parseInt(u0.searchParams.get('per_page') || '');
        const sPer = parseInt(localStorage.getItem(storageKey) || '');
        if (!isNaN(qPer) && qPer > 0) currentPer = qPer;
        else if (!isNaN(sPer) && sPer > 0) currentPer = sPer;
        if (perSelect) perSelect.value = String(currentPer);
        setStatusAttrs();
        setPaginateAttrs();
        bindEvents();
        return load(1, searchInput ? searchInput.value : '');
    }
    function destroy() {
        unbindEvents();
        if (controller) controller.abort();
        cache.clear();
    }
    return { init, load, destroy };
}

function initCompat(config) {
    const containerSelector = config.containerSelector;
    const container = document.querySelector(containerSelector);
    const task = createListPaginate();
    return task.init(container, config).then(() => {
        return task;
    });
}

try {
    if (typeof window !== 'undefined')
        window.ListPaginate = { create: createListPaginate, init: initCompat };
} catch (e) {}

function autoInit() {
    function selectContainer(metaEl, listId) {
        const explicit = metaEl.getAttribute('data-container');
        if (explicit) return explicit;
        const tableBody = metaEl.closest('div')?.querySelector('table tbody');
        if (tableBody) return 'table tbody';
        const gridSel = `[data-${listId}-grid="true"]`;
        if (document.querySelector(gridSel)) return gridSel;
        return 'table tbody';
    }
    function selectPaginate(metaEl) {
        const explicit = metaEl.getAttribute('data-paginate');
        if (explicit) return explicit;
        if (document.querySelector('.mt-6')) return '.mt-6';
        if (document.querySelector('.shadow-sm.p-4')) return '.shadow-sm.p-4';
        return '.mt-6';
    }
    document.addEventListener('DOMContentLoaded', () => {
        const metas = document.querySelectorAll('[data-meta][data-list-id][data-list-endpoint]');
        metas.forEach((metaEl) => {
            const listId = metaEl.getAttribute('data-list-id');
            const endpoint = metaEl.getAttribute('data-list-endpoint');
            const containerSelector = selectContainer(metaEl, listId);
            const paginateSelector = selectPaginate(metaEl);
            const task = createListPaginate();
            task.init(containerSelector, {
                endpoint,
                containerSelector,
                paginateSelector,
                perPageKey: 'yalihan_admin_per_page',
                selectors: { searchInput: metaEl.getAttribute('data-search') || '' },
                render: function () {
                    return '';
                },
            });
        });
    });
}

autoInit();

export default { create: createListPaginate, init: initCompat };
