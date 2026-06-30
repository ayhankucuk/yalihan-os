import { describe, it, expect, vi } from 'vitest';
import { debounce, throttle } from '../admin/utils/debounce.js';

describe('debounce', () => {
    it('calls function after wait', async () => {
        const fn = vi.fn();
        const d = debounce(fn, 50);
        d();
        d();
        d();
        await new Promise((r) => setTimeout(r, 70));
        expect(fn).toHaveBeenCalledTimes(1);
    });

    it('preserves arguments', async () => {
        const fn = vi.fn();
        const d = debounce(fn, 10);
        d(1, 2, 3);
        await new Promise((r) => setTimeout(r, 20));
        expect(fn).toHaveBeenCalledWith(1, 2, 3);
    });
});

describe('throttle', () => {
    it('limits calls within window', async () => {
        const fn = vi.fn();
        const t = throttle(fn, 50);
        t();
        t();
        t();
        expect(fn).toHaveBeenCalledTimes(1);
        await new Promise((r) => setTimeout(r, 55));
        t();
        expect(fn).toHaveBeenCalledTimes(2);
    });
});
