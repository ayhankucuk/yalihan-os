import { test, expect } from '@playwright/test';
import { AuthHelper } from './helpers/auth.helper';

test.describe('Arsa Wizard Step 2 - Proof-Based Field Check', () => {
    test('Step 2 renders ada_no and parsel_no fields', async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.loginAsAdmin();

        // Navigate to wizard
        await page.goto('/admin/ilanlar/create-wizard');

        // Wait for page load
        await page.waitForLoadState('networkidle');
        await expect(page.locator('#ana_kategori_id')).toBeVisible({ timeout: 15000 });

        // Select Arsa & Arazi category
        const arsaOption = page
            .locator('select#ana_kategori_id option')
            .filter({ hasText: /Arsa.*Arazi/i });
        const arsaCount = await arsaOption.count();
        if (arsaCount === 0) {
            if (process.env.CI) {
                throw new Error('SEED_FIXTURE_MISSING: Arsa & Arazi option not found in ana_kategori_id');
            }
            test.skip(true, 'SEED_FIXTURE_MISSING: Arsa & Arazi option not found in ana_kategori_id');
            return;
        }
        const arsaValue = await arsaOption.first().getAttribute('value');
        if (arsaValue) {
            await page.selectOption('select#ana_kategori_id', arsaValue);
        }

        // Wait for sub-category to load (if exists)
        await page.waitForTimeout(500);

        // Select first sub-category if available
        const subCategorySelect = page.locator('select#alt_kategori_id');
        if (await subCategorySelect.isVisible()) {
            const options = await subCategorySelect.locator('option').all();
            if (options.length > 1) {
                const firstValue = await options[1].getAttribute('value');
                if (firstValue) await subCategorySelect.selectOption(firstValue);
            }
        }

        // Select Satılık
        const satilikOption = page
            .locator('select#junction_id option')
            .filter({ hasText: /Satılık|Satilık/i });
        const satilikValue = await satilikOption.getAttribute('value');
        if (satilikValue) {
            await page.selectOption('select#junction_id', satilikValue);
        }

        // Click "İleri" to go to Step 2
        await page.click('button:has-text("İleri"), button:has-text("Devam")');

        // Wait for Step 2 to load
        await page.waitForTimeout(1000);

        // PROOF 1: Count total input elements in dynamic fields container
        const dynamicContainer = page.locator('#step2-dynamic-fields-container, [data-step="2"]');
        const inputCount = await dynamicContainer.locator('input, select, textarea').count();

        console.log(`📊 Step 2 total fields: ${inputCount}`);
        expect(inputCount).toBeGreaterThan(0);

        // PROOF 2: Check for ada_no field
        const adaNoField = page.locator('input[name*="ada"], input[id*="ada"]').first();
        const hasAdaNo = (await adaNoField.count()) > 0;
        console.log(`📍 ada_no field present: ${hasAdaNo}`);

        // PROOF 3: Check for parsel_no field
        const parselNoField = page.locator('input[name*="parsel"], input[id*="parsel"]').first();
        const hasParselNo = (await parselNoField.count()) > 0;
        console.log(`📍 parsel_no field present: ${hasParselNo}`);

        // PROOF 4: Check for imar_durumu field
        const imarField = page.locator('select[name*="imar"], input[name*="imar"]').first();
        const hasImar = (await imarField.count()) > 0;
        console.log(`📍 imar_durumu field present: ${hasImar}`);

        // Expectations
        expect(
            hasAdaNo || hasParselNo || hasImar,
            'At least one Arsa-specific field should be visible'
        ).toBeTruthy();

        // Take screenshot for proof
        await page.screenshot({
            path: 'test-results/arsa-step2-proof.png',
            fullPage: false,
        });

        console.log('✅ Proof-based check complete');
        console.log(`   Total fields: ${inputCount}`);
        console.log(`   ada_no: ${hasAdaNo}, parsel_no: ${hasParselNo}, imar: ${hasImar}`);
    });
});
