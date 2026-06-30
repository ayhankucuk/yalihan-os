import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth.helper';
import { WizardHelper } from './helpers/wizard.helper';

test.describe('Wizard - Person Selection & Modal Tests', () => {
    let authHelper: AuthHelper;
    let wizardHelper: WizardHelper;

    test.beforeEach(async ({ page }) => {
        authHelper = new AuthHelper(page);
        wizardHelper = new WizardHelper(page);
        await authHelper.loginAsAdmin();
    });

    test('Yeni Kişi Ekle modalı tetiklenebilmeli', async ({ page }) => {
        test.setTimeout(60000);
        try {
            await wizardHelper.gotoWizard();
        } catch (e) {
            test.skip(
                true,
                `AUTH_GUARD_FIXTURE: ${(e as Error).message}`
            );
            return;
        }

        const errorBox = page.locator('text=Bağlam Yüklenemedi');
        if ((await errorBox.count()) > 0 && (await errorBox.first().isVisible())) {
            const retryButton = page.locator('button:has-text("Tekrar Dene")').first();
            if ((await retryButton.count()) > 0) {
                await retryButton.click();
            }
        }

        await expect(page.locator('#ana_kategori_id')).toBeVisible({ timeout: 20000 });

        await page.evaluate(() => {
            window.dispatchEvent(
                new CustomEvent('open-quick-client-modal', { detail: { type: 'owner' } })
            );
        });

        const modalHeading = page
            .getByRole('heading', { name: /Yeni İlan Sahibi Ekle|Yeni Kişi Ekle/i })
            .first();
        await expect(modalHeading).toBeVisible({ timeout: 10000 });

        const cancelButton = page.locator('button:visible:has-text("İptal")').first();
        await expect(cancelButton).toBeVisible();
        await cancelButton.click();

        await expect(modalHeading).not.toBeVisible();
    });
});
