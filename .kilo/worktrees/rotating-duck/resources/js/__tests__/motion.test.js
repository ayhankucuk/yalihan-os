import { describe, it, expect } from 'vitest';
import { shouldReduceMotion, safeScrollIntoView } from '../admin/utils/motion.js';

describe('motion utils', () => {
    it('shouldReduceMotion returns a boolean', () => {
        expect(typeof shouldReduceMotion()).toBe('boolean');
    });

    it('safeScrollIntoView does not throw with null', () => {
        expect(() => safeScrollIntoView(null)).not.toThrow();
    });
});
