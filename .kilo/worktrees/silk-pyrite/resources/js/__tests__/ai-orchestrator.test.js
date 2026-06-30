import { describe, it, expect, beforeEach, vi } from 'vitest';
import AIOrchestrator from '../admin/services/AIOrchestrator.js';

describe('AIOrchestrator absolute fetch', () => {
    beforeEach(() => {
        vi.restoreAllMocks();
    });
    it('invokes chat with absolute provider and returns normalized response', async () => {
        const fakeJson = { success: true, message: 'ok', data: { answer: 'Merhaba' } };
        vi.spyOn(global, 'fetch').mockResolvedValue({
            ok: true,
            status: 200,
            json: vi.fn().mockResolvedValue(fakeJson),
        });
        AIOrchestrator.register('backend', {
            base: '/api/admin/ai',
            absolute: true,
            operations: { chat: { path: '/chat', method: 'POST' } },
        });
        AIOrchestrator.use('backend');
        const res = await AIOrchestrator.chat({ user_msg: 'selam' }, { rateMs: 0 });
        expect(res.status).toBe(true);
        expect(res.data.answer).toBe('Merhaba');
    });
});
