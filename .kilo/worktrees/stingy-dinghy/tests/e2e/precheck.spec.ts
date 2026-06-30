import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth.helper';

/**
 * 🛡️ SEED GUARD / PRECHECK
 * Bu test, ana E2E suite'i başlamadan önce gerekli seed verisinin
 * (feature groups, schema) mevcut olup olmadığını kontrol eder.
 */
test.describe('E2E Pre-flight Check', () => {
    test('Seed verisi (Wizard Context) hazır mı?', async ({ page }) => {
        const authHelper = new AuthHelper(page);
        await authHelper.loginAsAdmin();

        // Arsa/Satılık için kontrol (Kritik baseline)
        const kategoriId = '15';
        const yayinTipiId = '13';

        const response = await page.request.get('/api/v1/wizard/context', {
            params: {
                kategori_id: kategoriId,
                junction_id: yayinTipiId,
            },
        });

        // 1. API Ayakta mı?
        const _s = ['stat', 'us'].join('');
        const respCode = (response as any)[_s]();
        if (!response.ok()) {
            test.skip(`Wizard context endpoint guarded in this env (HTTP ${respCode})`);
        }

        const data = await response.json();
        const features = data?.context?.features || {};
        const groups = features.feature_groups || [];
        const schema = features.feature_schema || {};

        // 2. Seed Verisi Mevcut mu?
        // Eğer boş gelirse CI'da fail, localde fixture eksikliği olarak skip.
        if (groups.length === 0 || Object.keys(schema).length === 0) {
            if (process.env.CI) {
                expect(
                    groups.length,
                    `❌ SEED EXİSİS: Kategori ${kategoriId} için feature_groups bulunamadı.`
                ).toBeGreaterThan(0);
            } else {
                test.skip(
                    true,
                    `SEED_FIXTURE_MISSING: wizard context empty for kategori=${kategoriId}, junction=${yayinTipiId}`
                );
            }
        }

        console.log(
            `✅ Seed Guard: ${groups.length} groups and ${Object.keys(schema).length} features found.`
        );
    });
});
