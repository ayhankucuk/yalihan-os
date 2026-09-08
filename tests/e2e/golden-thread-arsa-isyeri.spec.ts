/**
 * Golden Thread E2E — Arsa & İşyeri Dynamic Fields Traversal
 *
 * TDD scaffold for BACKLOG-01 gap fix.
 * These tests assert that Arsa and İşyeri categories produce
 * schema-driven dynamic fields in Wizard Step 2.
 *
 * Prerequisite: ArsaIsyeriFeatureAssignmentSeeder must have been run.
 *
 * TC-GT-07 — Arsa Satılık:  Step 1→2, assert ada_no, parsel_no, imar_durumu, kaks, taks in DOM
 * TC-GT-08 — İşyeri Satılık: Step 1→2, assert isyeri_tipi, net_m2, personel_kapasitesi in DOM
 * TC-GT-09 — Arsa Kiralık:   Step 1→2, assert depozito_arsa, imar_durumu, kaks, taks, yola_cephe in DOM
 * TC-GT-10 — İşyeri Devren:  Step 1→2, assert devir_bedeli_isyeri, mevcut_ciro, ruhsat_durumu_isyeri,
 *                              demirbas_listesi, isyeri_tipi in DOM
 */

import { test, expect, Page } from '@playwright/test';
import { AuthHelper } from './helpers/auth.helper';

const BASE_URL = process.env.PLAYWRIGHT_BASE_URL || 'http://127.0.0.1:8000';

// ─── Helpers ─────────────────────────────────────────────────────────────────

async function getAlpineWizard(page: Page) {
    return page.evaluate(() => {
        const root = Array.from(document.querySelectorAll<HTMLElement>('[x-data]'))
            .find((el) => (window as any).Alpine?.$data(el)?.wizard !== undefined) ?? null;
        const data = root && (window as any).Alpine?.$data(root);
        return { inst: data?.wizard, root, data };
    });
}

/**
 * Navigate wizard Step 1 → Step 2 by selecting category combo and calling wizard.nextStep().
 * Uses slug-based option matching for category-agnostic selection.
 */
async function navigateStep1To2BySlug(
    page: Page,
    anaSlug: string,
    altSlug: string,
    yayinTipiSlug: string
): Promise<void> {
    // Ensure wizard Alpine instance is ready
    await expect(async () => {
        const { inst } = await getAlpineWizard(page);
        expect(inst != null && inst.currentStep === 1, 'wizard not ready').toBe(true);
    }).toPass({ timeout: 15000 });

    // Select ana kategori by slug
    const anaVal = await page.locator('#ana_kategori_id').evaluate((sel: HTMLSelectElement, slug: string) => {
        const opts = Array.from(sel.options).filter((o) => o.value);
        const match = opts.find((o) => (o.dataset.slug || o.text).toLowerCase().includes(slug));
        return (match || opts[0])?.value ?? null;
    }, anaSlug);
    if (!anaVal) throw new Error(`No ana_kategori matching slug "${anaSlug}"`);
    await page.locator('#ana_kategori_id').selectOption(anaVal);

    // Wait for alt kategori to populate
    await expect(page.locator('#alt_kategori_id')).not.toBeDisabled({ timeout: 10000 });
    await expect(async () => {
        const c = await page.locator('#alt_kategori_id option[value]:not([value=""])').count();
        expect(c).toBeGreaterThan(0);
    }).toPass({ timeout: 10000 });

    // Select alt kategori by slug
    const altVal = await page.locator('#alt_kategori_id').evaluate((sel: HTMLSelectElement, slug: string) => {
        const opts = Array.from(sel.options).filter((o) => o.value);
        const match = opts.find((o) => (o.dataset.slug || o.text).toLowerCase().includes(slug));
        return (match || opts[0])?.value ?? null;
    }, altSlug);
    if (!altVal) throw new Error(`No alt_kategori matching slug "${altSlug}"`);
    await expect(async () => {
        const alt = page.locator('#alt_kategori_id');
        await alt.selectOption(altVal);
        await expect(alt).toHaveValue(altVal);
    }).toPass({ timeout: 10000 });

    // Wait for junction (yayin tipi) to populate
    await expect(page.locator('#junction_id')).not.toBeDisabled({ timeout: 10000 });
    await expect(async () => {
        const c = await page.locator('#junction_id option[value]:not([value=""])').count();
        expect(c).toBeGreaterThan(0);
    }).toPass({ timeout: 10000 });

    // Select yayin tipi by slug
    const jctVal = await page.locator('#junction_id').evaluate((sel: HTMLSelectElement, slug: string) => {
        const opts = Array.from(sel.options).filter((o) => o.value);
        const match = opts.find((o) =>
            (o.dataset.slug || o.text).toLowerCase().replace(/ı/g, 'i').includes(slug)
        );
        return (match || opts[0])?.value ?? null;
    }, yayinTipiSlug);
    if (!jctVal) throw new Error(`No junction matching slug "${yayinTipiSlug}"`);
    await page.locator('#junction_id').selectOption(jctVal);
    await page.waitForTimeout(600);

    // Step 1 → 2 via Alpine wizard.nextStep()
    const result = await page.evaluate(() => {
        const root = Array.from(document.querySelectorAll<HTMLElement>('[x-data]'))
            .find((el) => (window as any).Alpine?.$data(el)?.wizard !== undefined);
        const data = root && (window as any).Alpine?.$data(root);
        const inst2 = data?.wizard;
        if (!inst2) return { ok: false, reason: 'no wizard instance' };
        const r = inst2.nextStep();
        return { ok: r !== false, step: inst2.currentStep };
    });

    if (!result.ok || result.step !== 2) {
        throw new Error(`Step 1→2 failed: ${JSON.stringify(result)}`);
    }
    await expect(page.locator('#baslik')).toBeVisible({ timeout: 15000 });
}

/**
 * Wait for Step 2 schema-driven fields to load (Alpine x-html render).
 * The step2-features-container loads fields via API and renders with renderGroupHtml().
 */
async function waitForStep2Fields(page: Page): Promise<void> {
    // Wait for the features container to finish loading
    await expect(async () => {
        const loading = await page.locator('#step2-features-container [x-show="loading"]').isVisible();
        expect(loading).toBe(false);
    }).toPass({ timeout: 15000 });

    // Wait for dynamic-field elements to appear
    await expect(async () => {
        const count = await page.locator('#step2-features-container .dynamic-field').count();
        expect(count).toBeGreaterThan(0);
    }).toPass({ timeout: 15000 });
}

/**
 * Assert that a field with the given slug exists in the Step 2 DOM.
 */
async function assertFieldExists(page: Page, slug: string): Promise<void> {
    const selector = `#step2-features-container .dynamic-field[data-field-slug="${slug}"]`;
    await expect(
        page.locator(selector),
        `Expected dynamic field "${slug}" to be present in Step 2 DOM`
    ).toBeVisible({ timeout: 5000 });
}

// ─── Tests ───────────────────────────────────────────────────────────────────

test.describe('Golden Thread — Arsa & İşyeri Dynamic Fields', () => {
    test.beforeEach(async ({ page }) => {
        const auth = new AuthHelper(page);
        await auth.loginAsAdmin();
        await page.goto('/admin/ilanlar/create-wizard', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('#ana_kategori_id')).toBeVisible({ timeout: 15000 });
    });

    test('TC-GT-07 — Arsa Satılık: Step 1→2 with ada_no, parsel_no, imar_durumu, kaks, taks fields', async ({ page }) => {
        await navigateStep1To2BySlug(page, 'arsa', 'arsa-konut-villa', 'satilik');
        await waitForStep2Fields(page);

        // Assert Arsa-specific dynamic fields are rendered in DOM
        await assertFieldExists(page, 'ada_no');
        await assertFieldExists(page, 'parsel_no');
        await assertFieldExists(page, 'imar_durumu');
        await assertFieldExists(page, 'kaks');
        await assertFieldExists(page, 'taks');

        // Verify field count > 0 via Alpine state
        const fieldCount = await page.evaluate(() => {
            const inst = (window as any).__step2ActiveInstance;
            return inst?.fields?.length ?? -1;
        });
        expect(fieldCount, 'Step 2 should have > 0 fields for Arsa Satılık').toBeGreaterThan(0);
    });

    test('TC-GT-08 — İşyeri Satılık: Step 1→2 with isyeri_tipi, net_m2, personel_kapasitesi fields', async ({ page }) => {
        await navigateStep1To2BySlug(page, 'isyeri', 'ofis', 'satilik');
        await waitForStep2Fields(page);

        // Assert İşyeri-specific dynamic fields are rendered in DOM
        await assertFieldExists(page, 'isyeri_tipi');
        await assertFieldExists(page, 'net_m2');
        await assertFieldExists(page, 'personel_kapasitesi');

        // Verify field count > 0 via Alpine state
        const fieldCount = await page.evaluate(() => {
            const inst = (window as any).__step2ActiveInstance;
            return inst?.fields?.length ?? -1;
        });
        expect(fieldCount, 'Step 2 should have > 0 fields for İşyeri Satılık').toBeGreaterThan(0);
    });

    test('TC-GT-09 — Arsa Kiralık: Step 1→2 with depozito_arsa, imar_durumu, kaks, taks, yola_cephe fields', async ({ page }) => {
        await navigateStep1To2BySlug(page, 'arsa', 'arsa', 'kiralik');
        await waitForStep2Fields(page);

        // Assert Arsa Kiralık-specific dynamic fields are rendered in DOM
        // Seeder: Arsa Kiralık has 14 fields: ada_no, parsel_no, pafta_no, imar_durumu, kaks, taks,
        //         gabari, yola_cephe, altyapi_*, +depozito_arsa (Kiralık'a özel, -tapu_durumu_arsa)
        await assertFieldExists(page, 'depozito_arsa');
        await assertFieldExists(page, 'imar_durumu');
        await assertFieldExists(page, 'kaks');
        await assertFieldExists(page, 'taks');
        await assertFieldExists(page, 'yola_cephe');

        // Verify field count > 0 via Alpine state
        const fieldCount = await page.evaluate(() => {
            const inst = (window as any).__step2ActiveInstance;
            return inst?.fields?.length ?? -1;
        });
        expect(fieldCount, 'Step 2 should have > 0 fields for Arsa Kiralık').toBeGreaterThan(0);
    });

    test('TC-GT-10 — İşyeri Devren: Step 1→2 with devir_bedeli_isyeri, mevcut_ciro, ruhsat_durumu_isyeri, demirbas_listesi, isyeri_tipi fields', async ({ page }) => {
        await navigateStep1To2BySlug(page, 'isyeri', 'ofis', 'devren');
        await waitForStep2Fields(page);

        // Assert İşyeri Devren-specific dynamic fields are rendered in DOM
        // Seeder: İşyeri Devren has 8 fields: isyeri_tipi, net_m2, depozito_isyeri,
        //         devir_bedeli_isyeri (required), aidat_isyeri, mevcut_ciro, ruhsat_durumu_isyeri, demirbas_listesi
        await assertFieldExists(page, 'devir_bedeli_isyeri');
        await assertFieldExists(page, 'mevcut_ciro');
        await assertFieldExists(page, 'ruhsat_durumu_isyeri');
        await assertFieldExists(page, 'demirbas_listesi');
        await assertFieldExists(page, 'isyeri_tipi');

        // Verify field count > 0 via Alpine state
        const fieldCount = await page.evaluate(() => {
            const inst = (window as any).__step2ActiveInstance;
            return inst?.fields?.length ?? -1;
        });
        expect(fieldCount, 'Step 2 should have > 0 fields for İşyeri Devren').toBeGreaterThan(0);
    });
});
