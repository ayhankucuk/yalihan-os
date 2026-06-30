import { describe, it, expect, vi, beforeEach } from 'vitest';
import { wait, nextTick } from '../test-helpers/micro.js';
import { createCollector } from '../test-helpers/metrics.js';
import ListPaginateModule from '../admin/services/list-paginate.js';

function setupDOM({ urlPerPage, lsPerPage } = {}) {
    // location
    const href = 'http://example.com/admin?page=1' + (urlPerPage ? `&per_page=${urlPerPage}` : '');
    Object.defineProperty(window, 'location', { value: new URL(href), writable: true });

    // storage
    if (lsPerPage) localStorage.setItem('yalihan_admin_per_page', String(lsPerPage));
    else localStorage.removeItem('yalihan_admin_per_page');

    document.body.innerHTML = `
    <div data-meta="true" data-list-id="kisiler" data-list-endpoint="/api/admin/api/v1/kisiler">
      <span id="meta-total"></span>
      <span id="meta-page"></span>
      <div id="meta-status" role="status" aria-busy="false" aria-live="polite"></div>
      <select data-per-page-select>
        <option value="20">20</option>
        <option value="50">50</option>
        <option value="100">100</option>
      </select>
    </div>
    <table><tbody></tbody></table>
    <div class="mt-6">
      <a href="/admin?page=1">1</a>
      <a href="/admin?page=2">2</a>
    </div>
  `;
}

beforeEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
});

describe('ListPaginate per_page precedence', () => {
    it('URL per_page has priority over localStorage and default', async () => {
        setupDOM({ urlPerPage: 50, lsPerPage: 100 });
        window.ApiAdapter = {
            get: vi
                .fn()
                .mockResolvedValue({
                    data: [],
                    meta: { total: 0, current_page: 1, last_page: 1, per_page: 50 },
                }),
        };
        // Auto-init runs at import; but we call init explicitly for reliability
        await ListPaginateModule.init({
            endpoint: '/api/admin/api/v1/kisiler',
            containerSelector: 'table tbody',
            paginateSelector: '.mt-6',
            perPageKey: 'yalihan_admin_per_page',
            render: () => '',
        });
        await Promise.resolve();
        await new Promise((r) => setTimeout(r, 0));
        const select = document.querySelector('select[data-per-page-select]');
        expect(select.value).toBe('50');
    });

    it('localStorage used when URL missing', async () => {
        setupDOM({ urlPerPage: null, lsPerPage: 100 });
        window.ApiAdapter = {
            get: vi
                .fn()
                .mockResolvedValue({
                    data: [],
                    meta: { total: 0, current_page: 1, last_page: 1, per_page: 100 },
                }),
        };
        await ListPaginateModule.init({
            endpoint: '/api/admin/api/v1/kisiler',
            containerSelector: 'table tbody',
            paginateSelector: '.mt-6',
            perPageKey: 'yalihan_admin_per_page',
            render: () => '',
        });
        await Promise.resolve();
        const select = document.querySelector('select[data-per-page-select]');
        expect(select.value).toBe('100');
    });

    it('default 20 when neither URL nor localStorage present', async () => {
        setupDOM();
        window.ApiAdapter = {
            get: vi
                .fn()
                .mockResolvedValue({
                    data: [],
                    meta: { total: 0, current_page: 1, last_page: 1, per_page: 20 },
                }),
        };
        await ListPaginateModule.init({
            endpoint: '/api/admin/api/v1/kisiler',
            containerSelector: 'table tbody',
            paginateSelector: '.mt-6',
            perPageKey: 'yalihan_admin_per_page',
            render: () => '',
        });
        await Promise.resolve();
        await new Promise((r) => setTimeout(r, 0));
        const select = document.querySelector('select[data-per-page-select]');
        expect(select.value).toBe('20');
    });
});

describe('ListPaginate meta update', () => {
    it('updates meta-total and meta-page', async () => {
        setupDOM();
        const meta = { total: 123, current_page: 2, last_page: 7, per_page: 50 };
        window.ApiAdapter = { get: vi.fn().mockResolvedValue({ data: [], meta }) };
        await ListPaginateModule.init({
            endpoint: '/api/admin/api/v1/kisiler',
            containerSelector: 'table tbody',
            paginateSelector: '.mt-6',
            perPageKey: 'yalihan_admin_per_page',
            render: () => '',
        });
        await Promise.resolve();
        await new Promise((r) => setTimeout(r, 10));
        expect(document.querySelector('#meta-total').textContent).toContain('123');
        expect(document.querySelector('#meta-page').textContent).toContain('2');
        expect(document.querySelector('#meta-page').textContent).toContain('7');
    });
});

describe('AbortController behavior', () => {
    it('only last request applies on rapid clicks', async () => {
        setupDOM();
        // Initial, then page=2, then page=1
        window.ApiAdapter = {
            get: vi
                .fn()
                .mockImplementationOnce(() =>
                    Promise.resolve({
                        data: [],
                        meta: { total: 0, current_page: 1, last_page: 2, per_page: 20 },
                    })
                )
                .mockImplementationOnce(() =>
                    Promise.resolve({
                        data: [],
                        meta: { total: 1, current_page: 2, last_page: 2, per_page: 20 },
                    })
                )
                .mockImplementationOnce(() =>
                    Promise.resolve({
                        data: ['second'],
                        meta: { total: 1, current_page: 1, last_page: 2, per_page: 20 },
                    })
                ),
        };
        await ListPaginateModule.init({
            endpoint: '/api/admin/api/v1/kisiler',
            containerSelector: 'table tbody',
            paginateSelector: '.mt-6',
            perPageKey: 'yalihan_admin_per_page',
            render: (items) => {
                document.querySelector('table tbody').innerHTML = items.length ? 'X' : 'Y';
                return '';
            },
        });
        const links = document.querySelectorAll('.mt-6 a');
        links[1].dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
        links[0].dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
        await nextTick();
        const pageText = document.querySelector('#meta-page').textContent;
        expect(pageText).toContain('1');
        expect(pageText).toContain('2');
    }, 10000);
});
