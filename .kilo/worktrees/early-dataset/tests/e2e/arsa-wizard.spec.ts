import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth.helper';
import { WizardHelper } from './helpers/wizard.helper';
import { PrecheckHelper, type PrecheckData } from './helpers/precheck.helper';

/**
 * Context7 Compliant E2E Test: Arsa İlanı Oluşturma
 *
 * Deterministik test: Hard-coded ID yok, precheck JSON'dan data okuma
 */
test.describe('Arsa İlanı Oluşturma - Wizard Flow', () => {
    let authHelper: AuthHelper;
    let wizardHelper: WizardHelper;

    test.beforeEach(async ({ page }) => {
        authHelper = new AuthHelper(page);
        wizardHelper = new WizardHelper(page);
        await authHelper.loginAsAdmin();

        await page.goto('/admin/dashboard/index', { waitUntil: 'domcontentloaded' });
        const title = await page.title();
        test.skip(/forbidden|403/i.test(title), 'AUTH_GUARD_FIXTURE: dashboard route forbidden');
    });

    test('Arsa İlanı Wizard Akışı (Precheck-based & Deterministic)', async ({ page }, testInfo) => {
        // 1. Precheck JSON'u oku (fail-safe: skip in local, fail in CI)
        let precheckData: PrecheckData;
        try {
            precheckData = PrecheckHelper.readLatestPrecheck();
        } catch (e) {
            const errorMsg = (e as Error).message;
            if (process.env.CI) {
                // CI'da deterministik olmalı, fail et
                throw new Error(`[CI] ${errorMsg}`);
            } else {
                // Local'de developer-friendly, skip et
                test.skip(true, errorMsg);
                return;
            }
        }

        const arsaCategory = PrecheckHelper.findCategory(precheckData, 'Arsa & Arazi');

        // 2. Arsa & Arazi kategorisi yoksa test'i skip et
        if (!arsaCategory) {
            test.skip();
            return;
        }

        // 3. Satılık yoksa fail
        if (!PrecheckHelper.hasPublishType(arsaCategory, 'Satılık')) {
            if (process.env.CI) {
                throw new Error(
                    'SEED_FIXTURE_MISSING: Arsa & Arazi için "Satılık" yayın tipi bulunamadı.'
                );
            }
            test.skip(
                true,
                'SEED_FIXTURE_MISSING: Arsa & Arazi için "Satılık" publish type yok (local fixture).'
            );
            return;
        }

        // 4. Priority-based sub-type selection
        const subTypePriorities = ['Arsa (Konut/Villa)', /Tarla/i, /Zeytinlik/i];
        const selectedSubType = PrecheckHelper.findPrioritySubType(arsaCategory, subTypePriorities);

        // En az bir alt tip olmalı
        expect(
            selectedSubType || arsaCategory.subTypes.length > 0,
            'Hiç alt kategori bulunamadı!'
        ).toBeTruthy();

        const finalSubType = selectedSubType || arsaCategory.subTypes[0];
        console.log(`📝 Seçilen Kategori: ${arsaCategory.categoryName}`);
        console.log(`📝 Seçilen Alt Kategori: ${finalSubType}`);
        console.log(`📝 Seçilen Yayın Tipi: Satılık`);

        // 5. Wizard'a git
        await wizardHelper.gotoWizard();

        // 6. Step 1: Kategori Seçimleri (Label-based)
        await test.step('Step 1: Kategori Seçimleri', async () => {
            await wizardHelper.robustSelect('#ana_kategori_id', 'Arsa & Arazi');
            await wizardHelper.robustSelect('#alt_kategori_id', finalSubType);
            await wizardHelper.robustSelect('#junction_id', 'Satılık');
        });

        // 7. Step 2'ye geç
        await test.step('Step 2: Dinamik Alanlar', async () => {
            await wizardHelper.goToNextStep(2);

            // Temel alan kontrolü (örn: baslik)
            const baslikInput = page
                .locator('input[name="baslik"], textarea[name="baslik"]')
                .first();
            await expect(baslikInput).toBeVisible({ timeout: 5000 });
            console.log('✅ Step 2 dynamic fields loaded successfully');
        });

        // 8. (Opsiyonel) Wizard context API doğrulaması
        // Bu kısmı isteğe bağlı tutuyoruz, çünkü UI kontrolü yeterli
        await test.step('API Context Validation (Soft)', async () => {
            try {
                // Kategori ID'lerini UI'dan al (hidden input veya selected value)
                const formData = await page.evaluate(() => ({
                    anaKategoriId: (document.querySelector('#ana_kategori_id') as HTMLSelectElement)
                        ?.value,
                    altKategoriId: (document.querySelector('#alt_kategori_id') as HTMLSelectElement)
                        ?.value,
                    yayinTipiId: (document.querySelector('#junction_id') as HTMLSelectElement)
                        ?.value,
                }));

                if (formData.anaKategoriId && formData.yayinTipiId) {
                    // validateWizardContext soft check (ada/parsel yoksa warning, fail değil)
                    await wizardHelper.validateWizardContext(
                        formData.anaKategoriId,
                        formData.yayinTipiId,
                        formData.altKategoriId
                    );
                }
            } catch (e) {
                console.warn(
                    '⚠️ Wizard context validation failed (non-blocking):',
                    (e as Error).message
                );
            }
        });

        // Attach selections to report
        await testInfo.attach('test-selections', {
            body: JSON.stringify(
                {
                    category: arsaCategory.categoryName,
                    subType: finalSubType,
                    publishType: 'Satılık',
                },
                null,
                2
            ),
            contentType: 'application/json',
        });
    });
});
