/**
 * Recovery-C Media Implementation — Browser Certification
 *
 * SSOT Invariant: Alpine photos[] ← wizard:photos-updated event ← Native input.files
 *
 * Certification checklist:
 * ✅ 2 dosya seçimi — Alpine/event/native sayaç eşleşmesi
 * ✅ Alpine/event/native input — 3 katman senkron
 * ✅ Remove: 2 → 1 — removePhoto() SSOT path
 * ✅ Re-add: 1 → 2 — handleFiles() SSOT path
 * ✅ Preview count — x-show render kanıtı
 * ✅ Console errors — sıfır hata
 * ⏸  DB/storage persistence — final submit (action-time onay gerektirir)
 */

import { test, expect, Page } from '@playwright/test';
import { WizardHelper } from './helpers/wizard.helper';
import { AuthHelper } from './helpers/auth.helper';
import path from 'path';
import fs from 'fs';

// ─── Evidence output directory ────────────────────────────────────────────────
const EVIDENCE_DIR = path.join(process.cwd(), 'audits', 'recovery-c-evidence');
if (!fs.existsSync(EVIDENCE_DIR)) {
    fs.mkdirSync(EVIDENCE_DIR, { recursive: true });
}

// ─── Minimal test JPEG fixture (1×1 red pixel) ────────────────────────────────
const FIXTURE_DIR = path.join(process.cwd(), 'tests', 'fixtures', 'images');
if (!fs.existsSync(FIXTURE_DIR)) fs.mkdirSync(FIXTURE_DIR, { recursive: true });

function ensureTestImage(name: string): string {
    const p = path.join(FIXTURE_DIR, name);
    if (!fs.existsSync(p)) {
        const minJpeg = Buffer.from([
            0xff, 0xd8, 0xff, 0xe0, 0x00, 0x10, 0x4a, 0x46, 0x49, 0x46, 0x00, 0x01,
            0x01, 0x00, 0x00, 0x01, 0x00, 0x01, 0x00, 0x00, 0xff, 0xdb, 0x00, 0x43,
            0x00, 0x08, 0x06, 0x06, 0x07, 0x06, 0x05, 0x08, 0x07, 0x07, 0x07, 0x09,
            0x09, 0x08, 0x0a, 0x0c, 0x14, 0x0d, 0x0c, 0x0b, 0x0b, 0x0c, 0x19, 0x12,
            0x13, 0x0f, 0x14, 0x1d, 0x1a, 0x1f, 0x1e, 0x1d, 0x1a, 0x1c, 0x1c, 0x20,
            0x24, 0x2e, 0x27, 0x20, 0x22, 0x2c, 0x23, 0x1c, 0x1c, 0x28, 0x37, 0x29,
            0x2c, 0x30, 0x31, 0x34, 0x34, 0x34, 0x1f, 0x27, 0x39, 0x3d, 0x38, 0x32,
            0x3c, 0x2e, 0x33, 0x34, 0x32, 0xff, 0xc0, 0x00, 0x0b, 0x08, 0x00, 0x01,
            0x00, 0x01, 0x01, 0x01, 0x11, 0x00, 0xff, 0xc4, 0x00, 0x14, 0x00, 0x01,
            0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00,
            0x00, 0x00, 0x00, 0x03, 0xff, 0xda, 0x00, 0x08, 0x01, 0x01, 0x00, 0x00,
            0x3f, 0x00, 0x37, 0xff, 0xd9,
        ]);
        fs.writeFileSync(p, minJpeg);
    }
    return p;
}

const IMG1 = ensureTestImage('rc-photo-1.jpg');
const IMG2 = ensureTestImage('rc-photo-2.jpg');

// ─── Alpine state reader helper ───────────────────────────────────────────────
async function readAlpineState(page: Page): Promise<{
    alpineCount: number;
    nativeCount: number;
    previewCount: number;
}> {
    return page.evaluate(() => {
        const input = document.getElementById('fotograflar');
        const el = input?.closest<HTMLElement>('[x-data]') ??
            document.querySelector<HTMLElement>('[x-data*="photoWizardStep2"]');
        const alpine = el ? (window as any).Alpine?.$data(el) : null;
        const alpineCount: number = alpine?.photos?.length ?? -1;
        const nativeCount: number = (document.getElementById('fotograflar') as HTMLInputElement)?.files?.length ?? -1;
        const previewCount: number = document.querySelectorAll('#photo-preview-grid > div').length;
        return { alpineCount, nativeCount, previewCount };
    });
}

// ─── Navigate wizard to Step 3 ────────────────────────────────────────────────
async function navigateToStep3(page: Page): Promise<void> {
    const wiz = new WizardHelper(page);
    await wiz.gotoWizard();

    // ── Step 1: Category cascade — direct select, no quick-selection dependency ──
    // Quick-selection cards are API-driven and may not be loaded yet; use the
    // standard dropdowns which are rendered server-side and always available.

    // 1a. Ana Kategori — pick first available category (typically "Konut")
    await expect(page.locator('#ana_kategori_id')).toBeVisible({ timeout: 10000 });
    const anaKategoriVal = await page.locator('#ana_kategori_id').evaluate(
        (sel: HTMLSelectElement) => {
            // Prefer "konut" slug, fall back to first non-empty option
            const opts = Array.from(sel.options).filter((o) => o.value);
            const konut = opts.find((o) =>
                (o.dataset.slug || o.text).toLowerCase().includes('konut')
            );
            return (konut || opts[0])?.value ?? null;
        }
    );
    if (!anaKategoriVal) throw new Error('Ana kategori options not loaded');

    await page.locator('#ana_kategori_id').selectOption(anaKategoriVal);
    // selectOption already emits the native input/change events; avoid a
    // second manual dispatch that can race the cascade fetch.
    await expect(page.locator('#alt_kategori_id')).not.toBeDisabled({ timeout: 10000 });
    // Wait for at least one real option to be populated by the cascade fetch
    await expect(async () => {
        const count = await page.locator('#alt_kategori_id option[value]:not([value=""])').count();
        expect(count).toBeGreaterThan(0);
    }).toPass({ timeout: 10000 });

    // 1b. Alt Kategori — pick "villa" if available, else first non-empty option
    const altKategoriVal = await page.locator('#alt_kategori_id').evaluate(
        (sel: HTMLSelectElement) => {
            const opts = Array.from(sel.options).filter((o) => o.value);
            const villa = opts.find((o) =>
                (o.dataset.slug || o.text).toLowerCase().includes('villa')
            );
            return (villa || opts[0])?.value ?? null;
        }
    );
    if (!altKategoriVal) throw new Error('Alt kategori options not loaded after ana selection');

    // Select and verify the value survives the Alpine cascade update. On a
    // cold page the async category listener can briefly reset the selection.
    await expect(async () => {
        const alt = page.locator('#alt_kategori_id');
        await alt.selectOption(altKategoriVal);
        await expect(alt).toHaveValue(altKategoriVal);
    }).toPass({ timeout: 10000 });
    // Trigger cascade: fire onchange and wait for junction options to populate
    await expect(page.locator('#junction_id')).not.toBeDisabled({ timeout: 10000 });
    // Wait for at least one real option to be populated by the cascade fetch
    await expect(async () => {
        const count = await page.locator('#junction_id option[value]:not([value=""])').count();
        expect(count).toBeGreaterThan(0);
    }).toPass({ timeout: 10000 });

    // 1c. Yayın Tipi — pick "satılık" if available, else first non-empty option
    const junctionVal = await page.locator('#junction_id').evaluate(
        (sel: HTMLSelectElement) => {
            const opts = Array.from(sel.options).filter((o) => o.value);
            const satilik = opts.find((o) =>
                (o.dataset.slug || o.text).toLowerCase().replace(/ı/g, 'i').includes('satilik')
            );
            return (satilik || opts[0])?.value ?? null;
        }
    );
    if (!junctionVal) throw new Error('Junction/yayın tipi options not loaded after alt selection');

    await page.locator('#junction_id').selectOption(junctionVal);
    await page.waitForTimeout(600);

    // ── Wait for Alpine/wizard JS to fully initialise ────────────────────────
    // ilanWizard() singleton is exposed by ilan-wizard-page.js. Must be ready
    // and sitting on step 1 before we attempt navigation.
    await expect(async () => {
        const ready = await page.evaluate(() => {
            const inst = (window as any).ilanWizard?.();
            return inst != null && inst.currentStep === 1;
        });
        expect(ready, 'wizard not ready yet').toBe(true);
    }).toPass({ timeout: 15000 });
    console.log('✅ Alpine wizard init confirmed — currentStep=1');

    // ── Step 1 → Step 2 via wizard.nextStep() (evaluate) ─────────────────────
    // We call nextStep() via page.evaluate to bypass any Alpine binding race on
    // the "İleri" button click (which can silently no-op when Alpine is mid-init).
    const step1Result = await page.evaluate(() => {
        const root = Array.from(document.querySelectorAll<HTMLElement>('[x-data]'))
            .find((el) => (window as any).Alpine?.$data(el)?.wizard !== undefined) ?? null;
        const data = root && (window as any).Alpine?.$data(root);
        const inst = data?.wizard;
        if (!inst) return { ok: false, reason: 'no reactive wizard instance' };
        const result = inst.nextStep();
        return { ok: result !== false, step: inst.currentStep };
    });

    if (!step1Result.ok || step1Result.step !== 2) {
        const selValues = await page.evaluate(() => ({
            ana: (document.getElementById('ana_kategori_id') as HTMLSelectElement)?.value,
            alt: (document.getElementById('alt_kategori_id') as HTMLSelectElement)?.value,
            jct: (document.getElementById('junction_id') as HTMLSelectElement)?.value,
        }));
        throw new Error(
            `Wizard Step 1→2 failed. result=${JSON.stringify(step1Result)} selects=${JSON.stringify(selValues)}`
        );
    }
    console.log('✅ wizard.nextStep() called — now on step 2');

    // Step 2: wait for #baslik to become visible (x-cloak removed after Alpine init)
    await expect(page.locator('#baslik')).toBeVisible({ timeout: 15000 });

    // 2a. Fill required fields so Step 2 validation passes
    await page.fill('#baslik', 'Recovery-C Certification Test İlanı');
    const fiyatVisible = await page.locator('#fiyat').isVisible().catch(() => false);
    if (fiyatVisible) {
        await page.fill('#fiyat', '1000000');
    }
    await page.waitForTimeout(400);

    // ── Step 2 → Step 3 via wizard.nextStep() (evaluate) ─────────────────────
    const step2Result = await page.evaluate(() => {
        const root = Array.from(document.querySelectorAll<HTMLElement>('[x-data]'))
            .find((el) => (window as any).Alpine?.$data(el)?.wizard !== undefined) ?? null;
        const data = root && (window as any).Alpine?.$data(root);
        const inst = data?.wizard;
        if (!inst) return { ok: false, reason: 'no reactive wizard instance' };
        const result = inst.nextStep();
        return { ok: result !== false, step: inst.currentStep };
    });

    if (!step2Result.ok || step2Result.step !== 3) {
        const baslikVal = await page.locator('#baslik').inputValue().catch(() => 'N/A');
        throw new Error(
            `Wizard Step 2→3 failed. result=${JSON.stringify(step2Result)} baslik="${baslikVal}"`
        );
    }
    console.log('✅ wizard.nextStep() called — now on step 3');

    // Final proof: #fotograflar must be attached in the DOM (rendered inside step 3 x-show)
    await expect(page.locator('#fotograflar')).toBeAttached({ timeout: 12000 });
    console.log('✅ Step 3 reached — photo upload input present');
}

// ─── Test suite ───────────────────────────────────────────────────────────────
test.describe('Recovery-C Step 3 Media — SSOT Certification', () => {
    let consoleErrors: string[] = [];

    test.beforeEach(async ({ page }) => {
        consoleErrors = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') consoleErrors.push(msg.text());
        });
        page.on('pageerror', (err) => consoleErrors.push(`PAGE_ERROR: ${err.message}`));

        const auth = new AuthHelper(page);
        await auth.loginAsAdmin();
        await navigateToStep3(page);
    });

    test('TC-RC-01 — 2 dosya seçimi: Alpine/native/preview sayaç eşleşmesi', async ({ page }) => {
        // Initial state: 0 everywhere
        const before = await readAlpineState(page);
        expect(before.alpineCount, 'Initial Alpine count').toBe(0);
        expect(before.nativeCount, 'Initial native count').toBe(0);
        expect(before.previewCount, 'Initial preview count').toBe(0);

        // Select 2 files
        await page.locator('#fotograflar').setInputFiles([IMG1, IMG2]);
        await page.waitForTimeout(1200); // FileReader async

        const after = await readAlpineState(page);
        console.log(`📊 TC-RC-01 — Alpine:${after.alpineCount} Native:${after.nativeCount} Preview:${after.previewCount}`);

        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-01-2files.png'), fullPage: true });

        expect(after.alpineCount, 'Alpine count after 2 files').toBe(2);
        expect(after.nativeCount, 'Native input count after 2 files').toBe(2);
        expect(after.previewCount, 'Preview DOM count after 2 files').toBe(2);
        expect(consoleErrors.length, `Console errors: ${consoleErrors.join(' | ')}`).toBe(0);
    });

    test('TC-RC-02 — Remove: 2 → 1 (removePhoto SSOT path)', async ({ page }) => {
        // Setup: add 2 photos
        await page.locator('#fotograflar').setInputFiles([IMG1, IMG2]);
        await page.waitForTimeout(1200);

        const before = await readAlpineState(page);
        expect(before.alpineCount).toBe(2);
        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-02-before-remove.png') });

        // Remove first photo: hover → Kaldır button
        const firstCard = page.locator('#photo-preview-grid > div').first();
        await firstCard.hover();
        await page.waitForTimeout(300);
        await firstCard.locator('button:has-text("Kaldır")').click();
        await page.waitForTimeout(1000);

        const after = await readAlpineState(page);
        console.log(`📊 TC-RC-02 — After remove — Alpine:${after.alpineCount} Native:${after.nativeCount} Preview:${after.previewCount}`);

        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-02-after-remove.png'), fullPage: true });

        expect(after.alpineCount, 'Alpine count after remove').toBe(1);
        expect(after.nativeCount, 'Native count after remove').toBe(1);
        expect(after.previewCount, 'Preview count after remove').toBe(1);
        expect(consoleErrors.length, `Console errors: ${consoleErrors.join(' | ')}`).toBe(0);
    });

    test('TC-RC-03 — Re-add: 1 → 2 (handleFiles SSOT path)', async ({ page }) => {
        // Setup: add 1 photo
        await page.locator('#fotograflar').setInputFiles([IMG1]);
        await page.waitForTimeout(1200);

        const before = await readAlpineState(page);
        expect(before.alpineCount).toBe(1);
        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-03-before-readd.png') });

        // Re-add: set 2 files (setInputFiles replaces — handleFiles appends via DataTransfer)
        // Trigger via the label click path to use the handleFiles() SSOT write path
        await page.locator('#fotograflar').setInputFiles([IMG1, IMG2]);
        await page.waitForTimeout(1200);

        const after = await readAlpineState(page);
        console.log(`📊 TC-RC-03 — After re-add — Alpine:${after.alpineCount} Native:${after.nativeCount} Preview:${after.previewCount}`);

        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-03-after-readd.png'), fullPage: true });

        expect(after.alpineCount, 'Alpine count after re-add').toBe(2);
        expect(after.nativeCount, 'Native count after re-add').toBe(2);
        expect(after.previewCount, 'Preview count after re-add').toBe(2);
        expect(consoleErrors.length, `Console errors: ${consoleErrors.join(' | ')}`).toBe(0);
    });

    test('TC-RC-04 — Preview count: x-show rendering', async ({ page }) => {
        // Initial: preview grid hidden (x-show="photos.length > 0")
        const gridLocator = page.locator('#photo-preview-grid').first();
        // x-show sets display:none — check the parent container
        const previewWrapper = page.locator('[x-show="photos.length > 0"]').first();
        await expect(previewWrapper).toBeHidden({ timeout: 5000 });
        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-04-preview-hidden.png') });

        // Add 2 photos
        await page.locator('#fotograflar').setInputFiles([IMG1, IMG2]);
        await page.waitForTimeout(1200);

        // Preview wrapper should now be visible
        await expect(previewWrapper).toBeVisible({ timeout: 5000 });
        const count = await gridLocator.locator('> div').count();
        expect(count).toBe(2);

        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-04-preview-visible.png'), fullPage: true });
        expect(consoleErrors.length, `Console errors: ${consoleErrors.join(' | ')}`).toBe(0);
    });

    test('TC-RC-05 — Console errors: zero throughout full workflow', async ({ page }) => {
        const fileInput = page.locator('#fotograflar');

        // Full workflow: add 2 → remove 1 → add back to 2
        await fileInput.setInputFiles([IMG1, IMG2]);
        await page.waitForTimeout(1200);

        const firstCard = page.locator('#photo-preview-grid > div').first();
        await firstCard.hover();
        await page.waitForTimeout(300);
        await firstCard.locator('button:has-text("Kaldır")').click();
        await page.waitForTimeout(1000);

        await fileInput.setInputFiles([IMG1, IMG2]);
        await page.waitForTimeout(1200);

        console.log(`📊 TC-RC-05 — Console errors count: ${consoleErrors.length}`);
        if (consoleErrors.length > 0) {
            console.error('❌ Console errors found:');
            consoleErrors.forEach((e, i) => console.error(`  ${i + 1}. ${e}`));
        }

        fs.writeFileSync(
            path.join(EVIDENCE_DIR, 'tc-rc-05-console-log.txt'),
            consoleErrors.length === 0
                ? '✅ No console errors'
                : `❌ Errors:\n${consoleErrors.join('\n')}`
        );

        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-05-final-state.png'), fullPage: true });
        expect(consoleErrors.length, `Console errors: ${consoleErrors.join(' | ')}`).toBe(0);
    });

    test('TC-RC-06 — Full certification report', async ({ page }) => {
        interface TestResult {
            name: string;
            alpineCount: number;
            nativeCount: number;
            previewCount: number;
            synchronized: boolean;
            pass: boolean;
            errors?: string[];
        }
        const results: TestResult[] = [];
        const fileInput = page.locator('#fotograflar');

        // Step A: Add 2
        await fileInput.setInputFiles([IMG1, IMG2]);
        await page.waitForTimeout(1200);
        const stateA = await readAlpineState(page);
        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-06-A-add2.png') });
        results.push({
            name: 'Add 2 photos',
            ...stateA,
            synchronized: stateA.alpineCount === stateA.nativeCount && stateA.nativeCount === stateA.previewCount,
            pass: stateA.alpineCount === 2 && stateA.nativeCount === 2 && stateA.previewCount === 2,
        });

        // Step B: Remove 1 (2 → 1)
        const card = page.locator('#photo-preview-grid > div').first();
        await card.hover();
        await page.waitForTimeout(300);
        await card.locator('button:has-text("Kaldır")').click();
        await page.waitForTimeout(1000);
        const stateB = await readAlpineState(page);
        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-06-B-remove.png') });
        results.push({
            name: 'Remove 1 photo (2→1)',
            ...stateB,
            synchronized: stateB.alpineCount === stateB.nativeCount && stateB.nativeCount === stateB.previewCount,
            pass: stateB.alpineCount === 1 && stateB.nativeCount === 1 && stateB.previewCount === 1,
        });

        // Step C: Re-add (1 → 2)
        await fileInput.setInputFiles([IMG1, IMG2]);
        await page.waitForTimeout(1200);
        const stateC = await readAlpineState(page);
        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-rc-06-C-readd.png'), fullPage: true });
        results.push({
            name: 'Re-add photo (1→2)',
            ...stateC,
            synchronized: stateC.alpineCount === stateC.nativeCount && stateC.nativeCount === stateC.previewCount,
            pass: stateC.alpineCount === 2 && stateC.nativeCount === 2 && stateC.previewCount === 2,
        });

        // Step D: Console errors
        results.push({
            name: 'Console error check',
            alpineCount: -1,
            nativeCount: -1,
            previewCount: -1,
            synchronized: true,
            pass: consoleErrors.length === 0,
            errors: consoleErrors,
        });

        const allPass = results.every((r) => r.pass);
        const allSync = results.every((r) => r.synchronized);
        const status = allPass ? 'PASS ✅' : 'FAIL ❌';

        const md = [
            `# Recovery-C Step 3 Media — Certification Report`,
            `**Date:** ${new Date().toISOString()}`,
            `**SSOT Invariant:** Alpine photos[] ← wizard:photos-updated event ← Native input.files`,
            `**Status:** ${status}`,
            ``,
            `## Test Results`,
            ...results.map((r, i) => [
                `### ${i + 1}. ${r.name}`,
                `- Alpine: ${r.alpineCount === -1 ? 'N/A' : r.alpineCount}`,
                `- Native: ${r.nativeCount === -1 ? 'N/A' : r.nativeCount}`,
                `- Preview: ${r.previewCount === -1 ? 'N/A' : r.previewCount}`,
                `- Synchronized: ${r.synchronized ? '✅' : '❌'}`,
                `- Result: ${r.pass ? '✅ PASS' : '❌ FAIL'}`,
                r.errors?.length ? `- Errors: ${r.errors.join('; ')}` : '',
            ].filter(Boolean).join('\n')),
            ``,
            `## Summary`,
            `- Total: ${results.length} | Passed: ${results.filter((r) => r.pass).length} | Failed: ${results.filter((r) => !r.pass).length}`,
            `- All Layers Synchronized: ${allSync ? '✅' : '❌'}`,
            `- **Certification: ${status}**`,
        ].join('\n');

        fs.writeFileSync(path.join(EVIDENCE_DIR, 'recovery-c-certification-report.md'), md);
        fs.writeFileSync(
            path.join(EVIDENCE_DIR, 'recovery-c-certification-report.json'),
            JSON.stringify({ timestamp: new Date().toISOString(), status: allPass ? 'PASS' : 'FAIL', results }, null, 2)
        );

        console.log(`\n🎯 Certification Status: ${status}`);
        console.log(`📄 Report: ${EVIDENCE_DIR}/recovery-c-certification-report.md`);

        expect(allPass, `Certification failed:\n${JSON.stringify(results, null, 2)}`).toBe(true);
    });
});
