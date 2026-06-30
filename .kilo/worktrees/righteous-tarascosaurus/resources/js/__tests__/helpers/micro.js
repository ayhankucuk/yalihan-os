export function wait(ms = 0) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function nextTick() {
    await Promise.resolve();
    await wait(0);
}

// Test stub
import { describe, it, expect } from 'vitest'
describe('helpers/micro', () => {
  it('wait resolves', async () => {
    const t = Date.now()
    await wait(1)
    expect(Date.now() - t >= 1).toBe(true)
  })
  it('nextTick resolves', async () => {
    await nextTick()
    expect(true).toBe(true)
  })
})
