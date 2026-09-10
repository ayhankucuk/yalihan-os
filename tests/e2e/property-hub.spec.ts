import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth.helper';

/**
 * PropertyHub E2E Certification Tests
 *
 * Sprint 14 — G-01 Capability + G-03 Operational verification
 * Verifies that /admin/property-hub loads without HTTP 500 and all hub modules render correctly.
 */
test.describe('PropertyHub Dashboard — Sprint 14 Certification', () => {

    test.beforeEach(async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.loginAsAdmin();
    });

    test('PropertyHub dashboard loads without error (200, no 500)', async ({ page }) => {
        const response = await page.goto('/admin/property-hub');
        expect(response?.status()).toBe(200);

        // Verify structural heading
        const heading = page.locator('h1');
        await expect(heading).toContainText(/Property Configuration Hub|Property Hub/i);

        // Verify health score badge
        const healthBadge = page.getByText(/Sistem Sağlığı:\s*\d+/i);
        await expect(healthBadge).toBeVisible();
    });

    test('PropertyHub templates section loads successfully (200)', async ({ page }) => {
        const response = await page.goto('/admin/property-hub/templates');
        expect(response?.status()).toBe(200);

        await page.waitForLoadState('networkidle');
        const content = page.locator('body');
        await expect(content).toBeVisible();
    });

    test('PropertyHub features section loads successfully (200)', async ({ page }) => {
        const response = await page.goto('/admin/property-hub/features');
        expect(response?.status()).toBe(200);

        await page.waitForLoadState('networkidle');
        const content = page.locator('body');
        await expect(content).toBeVisible();
    });

    test('PropertyHub analytics section loads successfully (200)', async ({ page }) => {
        const response = await page.goto('/admin/property-hub/analytics');
        expect(response?.status()).toBe(200);

        await page.waitForLoadState('networkidle');
        const content = page.locator('body');
        await expect(content).toBeVisible();
    });

    test('no critical console errors on PropertyHub dashboard', async ({ page }) => {
        const errors: string[] = [];
        page.on('console', msg => {
            if (msg.type() === 'error') {
                errors.push(msg.text());
            }
        });

        await page.goto('/admin/property-hub');
        await page.waitForLoadState('networkidle');
        await page.waitForTimeout(1500);

        // Filter non-actionable external noise (fonts, icons, analytics)
        const actionableErrors = errors.filter(e =>
            !/favicon|manifest\.json|googleapis\.com|font|source\.unsplash/i.test(e)
        );

        expect(actionableErrors).toHaveLength(0);
    });
});
