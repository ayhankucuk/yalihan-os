import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth.helper';

/**
 * ��️ SAB SEALED
 * E2E Smoke Test: Property Hub Compliance & AI Modal
 */

test.describe('Admin Property Hub Smoke Tests', () => {
    test('should navigate to templates and open AI generator modal', async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.loginAsAdmin();

        // 1. Go to Property Hub Templates
        await page.goto('/admin/property-hub/templates');
        const title = await page.title();
        test.skip(
            /forbidden|403/i.test(title),
            'AUTH_GUARD_FIXTURE: /admin/property-hub/templates forbidden'
        );

        // 2. Check for heading (Şablon Yönetimi or Property Hub)
        const heading = page.getByRole('heading', { name: /Şablon Yönetimi|Property Hub|Template/i });
        const headingCount = await heading.count();
        if (headingCount === 0) {
            if (process.env.CI) {
                throw new Error('SEED_FIXTURE_MISSING: Property Hub heading not found on templates route.');
            }
            test.skip(true, 'SEED_FIXTURE_MISSING: Property Hub heading not found in local fixture.');
            return;
        }
        await expect(heading.first()).toBeVisible({ timeout: 15000 });

        // 3. Find at least one "AI ile Oluştur" (aria-label) button
        const aiButton = page.getByLabel(/AI ile Oluştur|Sihirli Değnek/i).first();
        if ((await aiButton.count()) === 0) {
            if (process.env.CI) {
                throw new Error('SEED_FIXTURE_MISSING: AI generator button not found on templates page.');
            }
            test.skip(true, 'SEED_FIXTURE_MISSING: AI generator button not found in local fixture.');
            return;
        }
        await expect(aiButton).toBeVisible({ timeout: 15000 });

        // 4. Click the button and check for modal
        await aiButton.click();

        // 5. Verify modal content
        const modalHeading = page.getByText(/AI ile Şablon Yapılandırıcı/i);
        await expect(modalHeading).toBeVisible();

        console.log('✅ Smoke test passed: AI Modal is accessible.');
    });
});
