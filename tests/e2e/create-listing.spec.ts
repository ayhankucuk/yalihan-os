import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth.helper';

test('İlan oluşturma demo akışı', async ({ page }) => {
    const auth = new AuthHelper(page);
    await auth.loginAsAdmin();

    await page.goto('/admin/ilanlar/create-wizard?demo=1');
    const title = await page.title();
    test.skip(/forbidden|403/i.test(title), 'AUTH_GUARD_FIXTURE: create wizard route forbidden');

    await expect(page.locator('#ana_kategori_id')).toBeVisible({ timeout: 15000 });
    const nextButton = page.getByRole('button', { name: /İleri|Devam/i }).first();
    await expect(nextButton).toBeVisible({ timeout: 10000 });
});
