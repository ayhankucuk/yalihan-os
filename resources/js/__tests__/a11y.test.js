import { describe, it, expect, vi } from 'vitest';
import ListPaginateModule from '../admin/services/list-paginate.js';

function setup() {
    document.body.innerHTML = `
    <div data-meta="true" data-list-id="kisiler" data-list-endpoint="/api/admin/api/v1/kisiler">
      <span id="meta-total"></span>
      <span id="meta-page"></span>
      <div id="meta-status"></div>
      <select data-per-page-select>
        <option value="20">20</option>
      </select>
    </div>
    <table><tbody></tbody></table>
    <div class="mt-6"></div>
  `;
}

describe('A11Y checks', () => {
    it('status and navigation roles are applied', async () => {
        setup();
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
        const status = document.querySelector('#meta-status');
        expect(status.getAttribute('role')).toBe('status');
        expect(status.getAttribute('aria-live')).toBe('polite');
        const nav = document.querySelector('.mt-6');
        expect(nav.getAttribute('role')).toBe('navigation');
        expect(nav.getAttribute('aria-label')).toBe('Sayfalama');
    });
});
