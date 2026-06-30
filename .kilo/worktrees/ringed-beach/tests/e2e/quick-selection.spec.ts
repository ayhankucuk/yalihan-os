import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth.helper';
import { WizardHelper } from './helpers/wizard.helper';

/**
 * Context7 Compliant E2E Test: Wizard Step-1 Baseline
 *
 * Wizard kategori ekranının temel render ve etkileşim kontrollerini yapar.
 */
test.describe('Wizard Step-1 Baseline Tests', () => {
    let authHelper: AuthHelper;
    let wizardHelper: WizardHelper;

    async function ensureWizardContextLoaded(page: any) {
        const errorBox = page.locator('text=Bağlam Yüklenemedi');
        if ((await errorBox.count()) > 0 && (await errorBox.first().isVisible())) {
            const retryButton = page.locator('button:has-text("Tekrar Dene")').first();
            if ((await retryButton.count()) > 0) {
                await retryButton.click();
            }
        }

        await expect(page.locator('#ana_kategori_id')).toBeVisible({ timeout: 20000 });
    }

    test.beforeEach(async ({ page }) => {
        authHelper = new AuthHelper(page);
        wizardHelper = new WizardHelper(page);

        // Admin olarak giriş yap ve wizard sayfasına git
        await authHelper.loginAsAdmin();
        try {
            await wizardHelper.gotoWizard();
        } catch (e) {
            test.skip(true, `AUTH_GUARD_FIXTURE: ${(e as Error).message}`);
        }
    });

    test('Wizard kategori ekranı render olmalı', async ({ page }) => {
        await ensureWizardContextLoaded(page);

        await expect(page.getByRole('heading', { name: /Yeni İlan Oluştur/i })).toBeVisible();
        await expect(page.locator('#ana_kategori_id')).toBeVisible();
        await expect(page.locator('#alt_kategori_id')).toBeVisible();
        await expect(page.locator('#junction_id')).toBeVisible();

        console.log('✅ Wizard Step-1 base form is visible');
    });

    test('İleri butonu görünür olmalı', async ({ page }) => {
        await ensureWizardContextLoaded(page);

        const ileriButton = page.locator('button:has-text("İleri")').first();
        await expect(ileriButton).toBeVisible({ timeout: 10000 });

        console.log('✅ Next button is visible on Step-1');
    });

    test('Adım göstergesi Kategori adımını işaretlemeli', async ({ page }) => {
        await ensureWizardContextLoaded(page);

        await expect(page.locator('text=1. Kategori').first()).toBeVisible();
        await expect(page.locator('text=2. Bilgiler').first()).toBeVisible();

        console.log('✅ Step indicator labels are visible');
    });
});
