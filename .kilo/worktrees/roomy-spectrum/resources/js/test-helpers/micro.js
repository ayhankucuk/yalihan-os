export function wait(ms = 0) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

export async function nextTick() {
    await Promise.resolve();
    await wait(0);
}
