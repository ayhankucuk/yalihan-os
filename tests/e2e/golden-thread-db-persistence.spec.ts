/**
 * Golden Thread — DB Persistence Diagnostic
 *
 * TC-GT-06'nın zayıf noktası: yalnızca URL yönlendirmesini kontrol eder,
 * ilanlar tablosuna gerçek kayıt oluşup oluşmadığını DOĞRULAMAZ.
 *
 * Bu teşhis testi:
 *  1. Wizard'ı Step 1→5 tam gezer
 *  2. Gerçek submit butonunu (type="submit") tıklar
 *  3. submitForm()'un hangi validasyon kapısına takıldığını yakalar
 *  4. /api/ilanlar üzerinden DB'de gerçek kayıt oluştuğunu doğrular
 *  5. Sonucu audits/golden-thread-evidence/ altına kanıt olarak yazar
 *
 * Evidence labels:
 *  Step traversal  = BROWSER_VERIFIED
 *  DB persistence   = DB_VERIFIED (SELECT after submit)
 */

import { test, expect, Page } from '@playwright/test';
import path from 'path';
import fs from 'fs';

const EVIDENCE_DIR = path.join(process.cwd(), 'audits', 'golden-thread-evidence');
if (!fs.existsSync(EVIDENCE_DIR)) {
    fs.mkdirSync(EVIDENCE_DIR, { recursive: true });
}

// ─── Minimal test JPEG fixture (auto-generated, same as golden-thread-wizard) ─
const FIXTURE_DIR = path.join(process.cwd(), 'tests', 'fixtures', 'images');
if (!fs.existsSync(FIXTURE_DIR)) fs.mkdirSync(FIXTURE_DIR, { recursive: true });

function ensureTestImage(name: string): string {
    const p = path.join(FIXTURE_DIR, name);
    if (!fs.existsSync(p)) {
        // 1×1 red JPEG
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

const IMG1 = ensureTestImage('gt-photo-1.jpg');

async function getAlpineWizard(page: Page) {
    return page.evaluate(() => {
        const root = Array.from(document.querySelectorAll<HTMLElement>('[x-data]'))
            .find((el) => (window as any).Alpine?.$data(el)?.wizard !== undefined) ?? null;
        const data = root && (window as any).Alpine?.$data(root);
        return { inst: data?.wizard, root, data };
    });
}

async function navigateStep1To2(page: Page): Promise<void> {
    await expect(async () => {
        const { inst } = await getAlpineWizard(page);
        expect(inst != null && inst.currentStep === 1, 'wizard not ready').toBe(true);
    }).toPass({ timeout: 15000 });

    const anaVal = await page.locator('#ana_kategori_id').evaluate((sel: HTMLSelectElement) => {
        const opts = Array.from(sel.options).filter((o) => o.value);
        const konut = opts.find((o) => (o.dataset.slug || o.text).toLowerCase().includes('konut'));
        return (konut || opts[0])?.value ?? null;
    });
    if (!anaVal) throw new Error('No ana_kategori options');
    await page.locator('#ana_kategori_id').selectOption(anaVal);
    await expect(page.locator('#alt_kategori_id')).not.toBeDisabled({ timeout: 10000 });
    await expect(async () => {
        const c = await page.locator('#alt_kategori_id option[value]:not([value=""])').count();
        expect(c).toBeGreaterThan(0);
    }).toPass({ timeout: 10000 });

    const altVal = await page.locator('#alt_kategori_id').evaluate((sel: HTMLSelectElement) => {
        const opts = Array.from(sel.options).filter((o) => o.value);
        const villa = opts.find((o) => (o.dataset.slug || o.text).toLowerCase().includes('villa'));
        return (villa || opts[0])?.value ?? null;
    });
    if (!altVal) throw new Error('No alt_kategori options');
    await expect(async () => {
        const alt = page.locator('#alt_kategori_id');
        await alt.selectOption(altVal);
        await expect(alt).toHaveValue(altVal);
    }).toPass({ timeout: 10000 });
    await expect(page.locator('#junction_id')).not.toBeDisabled({ timeout: 10000 });
    await expect(async () => {
        const c = await page.locator('#junction_id option[value]:not([value=""])').count();
        expect(c).toBeGreaterThan(0);
    }).toPass({ timeout: 10000 });

    const jctVal = await page.locator('#junction_id').evaluate((sel: HTMLSelectElement) => {
        const opts = Array.from(sel.options).filter((o) => o.value);
        const satilik = opts.find((o) =>
            (o.dataset.slug || o.text).toLowerCase().replace(/ı/g, 'i').includes('satilik')
        );
        return (satilik || opts[0])?.value ?? null;
    });
    if (!jctVal) throw new Error('No junction options');
    await page.locator('#junction_id').selectOption(jctVal);
    await page.waitForTimeout(600);

    const result = await page.evaluate(() => {
        const root = Array.from(document.querySelectorAll<HTMLElement>('[x-data]'))
            .find((el) => (window as any).Alpine?.$data(el)?.wizard !== undefined);
        const data = root && (window as any).Alpine?.$data(root);
        const inst = data?.wizard;
        if (!inst) return { ok: false, reason: 'no wizard instance' };
        const r = inst.nextStep();
        return { ok: r !== false, step: inst.currentStep };
    });
    if (!result.ok || result.step !== 2) {
        throw new Error(`Step 1→2 failed: ${JSON.stringify(result)}`);
    }
    await expect(page.locator('#baslik')).toBeVisible({ timeout: 15000 });
}

async function navigateStep2To3(page: Page): Promise<void> {
    await page.fill('#baslik', 'DB Persistence Test İlanı — ' + Date.now());
    const fiyatVisible = await page.locator('#fiyat').isVisible().catch(() => false);
    if (fiyatVisible) {
        await page.fill('#fiyat', '2500000');
    }
    await page.waitForTimeout(400);

    const result = await page.evaluate(() => {
        const root = Array.from(document.querySelectorAll<HTMLElement>('[x-data]'))
            .find((el) => (window as any).Alpine?.$data(el)?.wizard !== undefined);
        const data = root && (window as any).Alpine?.$data(root);
        const inst = data?.wizard;
        if (!inst) return { ok: false, reason: 'no wizard instance' };
        const r = inst.nextStep();
        return { ok: r !== false, step: inst.currentStep };
    });
    if (!result.ok || result.step !== 3) {
        throw new Error(`Step 2→3 failed: ${JSON.stringify(result)}`);
    }
    await expect(page.locator('#fotograflar')).toBeAttached({ timeout: 12000 });
}

async function navigateStep3To4(page: Page): Promise<void> {
    const state = await page.evaluate(() => {
        const input = document.getElementById('fotograflar') as HTMLInputElement | null;
        const nativeCount: number = input?.files?.length ?? 0;
        const cacheCount: number = Array.isArray((window as any).__wizardUploadedPhotos)
            ? (window as any).__wizardUploadedPhotos.length : 0;
        return { nativeCount, cacheCount };
    });
    if (state.nativeCount === 0 && state.cacheCount === 0) {
        await page.locator('#fotograflar').setInputFiles([IMG1]);
        await page.waitForTimeout(1200);
    }

    const result = await page.evaluate(() => {
        const root = Array.from(document.querySelectorAll<HTMLElement>('[x-data]'))
            .find((el) => (window as any).Alpine?.$data(el)?.wizard !== undefined);
        const data = root && (window as any).Alpine?.$data(root);
        const inst = data?.wizard;
        if (!inst) return { ok: false, reason: 'no wizard instance' };
        const r = inst.nextStep();
        return { ok: r !== false, step: inst.currentStep };
    });
    if (!result.ok || result.step !== 4) {
        throw new Error(`Step 3→4 failed: ${JSON.stringify(result)}`);
    }
    await expect(page.locator('#il_id')).toBeVisible({ timeout: 15000 });
}

async function navigateStep4To5(page: Page): Promise<void> {
    await expect(page.locator('#il_id')).toBeVisible({ timeout: 15000 });
    await page.waitForTimeout(5000);

    const ilValue = await page.evaluate(() => {
        const sel = document.getElementById('il_id') as HTMLSelectElement | null;
        if (!sel || sel.options.length <= 1) return null;
        const muglaOpt = Array.from(sel.options).find((o) => o.value === '48' || /muğla|mugla/i.test(o.text));
        return (muglaOpt || Array.from(sel.options).find((o) => o.value))?.value ?? null;
    });
    if (!ilValue) throw new Error('No il options found in Step 4');

    const ilcelerPromise = page.waitForResponse(
        (resp) => resp.url().includes('/api/v1/location/district') && resp.status() === 200
    ).catch(() => null);
    await page.locator('#il_id').selectOption(ilValue);
    await ilcelerPromise;
    await page.waitForTimeout(800);

    const ilceValue = await page.evaluate(() => {
        const sel = document.getElementById('ilce_id') as HTMLSelectElement | null;
        if (!sel || sel.options.length <= 1) return null;
        return Array.from(sel.options).find((o) => o.value)?.value ?? null;
    });
    if (ilceValue) {
        const mahallePromise = page.waitForResponse(
            (resp) => resp.url().includes('/api/v1/location/neighborhood') && resp.status() === 200
        ).catch(() => null);
        await page.locator('#ilce_id').selectOption(ilceValue);
        await mahallePromise;
        await page.waitForTimeout(800);
    }

    const mahalleValue = await page.evaluate(() => {
        const sel = document.getElementById('mahalle_id') as HTMLSelectElement | null;
        if (!sel || sel.options.length <= 1) return null;
        return Array.from(sel.options).find((o) => o.value)?.value ?? null;
    });
    if (mahalleValue) {
        await page.locator('#mahalle_id').selectOption(mahalleValue);
        await page.waitForTimeout(400);
    }

    const result = await page.evaluate(() => {
        const root = Array.from(document.querySelectorAll<HTMLElement>('[x-data]'))
            .find((el) => (window as any).Alpine?.$data(el)?.wizard !== undefined);
        const data = root && (window as any).Alpine?.$data(root);
        const inst = data?.wizard;
        if (!inst) return { ok: false, reason: 'no wizard instance' };
        const r = inst.nextStep();
        return { ok: r !== false, step: inst.currentStep };
    });
    if (!result.ok || result.step !== 5) {
        throw new Error(`Step 4→5 failed: ${JSON.stringify(result)}`);
    }
    await expect(page.locator('.wizard-step-label.current', { hasText: 'Önizleme' }))
        .toBeVisible({ timeout: 10000 });
}

test.describe('Golden Thread — DB Persistence Diagnostic (TC-GT-06 hardening)', () => {
    let consoleErrors: string[] = [];

    test.beforeEach(async ({ page }) => {
        consoleErrors = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') consoleErrors.push(msg.text());
        });
        page.on('pageerror', (err) => consoleErrors.push(`PAGEERROR: ${err.message}`));
    });

    test('TC-GT-06-DB — Submit sonrası ilanlar tablosunda gerçek kayıt oluşmalı', async ({ page, request }) => {
        // ── Network yakalama: submitForm'un yaptığı POST isteklerini izle ─────
        const networkLog: Array<{ method: string; url: string; status: number; body: string }> = [];
        page.on('response', async (resp) => {
            const url = resp.url();
            if (resp.request().method() === 'POST' && (url.includes('/ilanlar') || url.includes('/draft'))) {
                let body = '';
                try { body = (await resp.text()).slice(0, 500); } catch (e) { body = '<unreadable>'; }
                networkLog.push({ method: 'POST', url, status: resp.status(), body });
            }
        });
        // ── Baseline: DB'deki mevcut ilan sayısı ─────────────────────────────
        const baseResp = await request.get('/api/v1/ilanlar?per_page=1');
        const baseData = await baseResp.json();
        const baseTotal = baseData.meta?.total ?? baseData.total ?? 0;
        console.log(`📊 Baseline ilan count: ${baseTotal}`);

        // ── Navigate all steps ────────────────────────────────────────────────
        await page.goto('/admin/ilanlar/create-wizard');
        await expect(page.locator('#ana_kategori_id')).toBeVisible({ timeout: 15000 });
        await navigateStep1To2(page);
        await navigateStep2To3(page);
        await navigateStep3To4(page);
        await navigateStep4To5(page);

        await page.screenshot({ path: path.join(EVIDENCE_DIR, 'tc-gt-06b-step5-reached.png'), fullPage: true });

        // ── Submit: gerçek type="submit" butonunu bul ve tıkla ────────────────
        const submitBtn = page.locator('#ilan-wizard-form button[type="submit"]');
        await expect(submitBtn).toBeVisible({ timeout: 10000 });
        const btnText = (await submitBtn.innerText()).trim();
        console.log(`🔘 Submit button text: "${btnText}"`);

        // Submit öncesi form state'i yakala — gerçek getSelected*Slug() metodlarını çağır
        const preSubmitState = await page.evaluate(() => {
            const photoInput = document.getElementById('fotograflar') as HTMLInputElement | null;
            const nativeCount = photoInput?.files?.length || 0;
            const cacheCount = Array.isArray((window as any).__wizardUploadedPhotos)
                ? (window as any).__wizardUploadedPhotos.length : 0;
            const qualityResult = (window as any).ilanWizardQualityResult || null;

            // Gerçek slug metodlarını çağır (submitForm'un kullandığı aynı metodlar)
            const yayinTipiSlug = (window as any).getSelectedYayinTipiSlug
                ? (window as any).getSelectedYayinTipiSlug() : null;
            const kategoriSlug = (window as any).getSelectedKategoriSlug
                ? (window as any).getSelectedKategoriSlug() : null;

            // Dropdown'ların gerçek durumu
            const junction = document.getElementById('junction_id') as HTMLSelectElement | null;
            const altKategori = document.getElementById('alt_kategori_id') as HTMLSelectElement | null;
            const junctionOpt = junction?.selectedIndex >= 0 ? junction.options[junction.selectedIndex] : null;
            const altOpt = altKategori?.selectedIndex >= 0 ? altKategori.options[altKategori.selectedIndex] : null;

            return {
                photoNative: nativeCount,
                photoCache: cacheCount,
                qualityRecommendation: qualityResult?.recommendation ?? null,
                yayinTipiSlug,
                kategoriSlug,
                junctionValue: junction?.value ?? null,
                junctionDataSlug: junctionOpt?.getAttribute('data-slug') ?? null,
                junctionText: junctionOpt?.text ?? null,
                altValue: altKategori?.value ?? null,
                altDataSlug: altOpt?.getAttribute('data-slug') ?? null,
                altText: altOpt?.text ?? null,
            };
        });
        console.log('🔍 Pre-submit state:', JSON.stringify(preSubmitState, null, 2));

        // Submit tıklaması
        await submitBtn.click();

        // ── Submit sonrası: URL değişimini ve DB kaydını bekle ────────────────
        let finalURL = page.url();
        let navigated = false;
        try {
            await page.waitForURL(/\/admin\/ilanlar\/[\d]+/, { timeout: 30000 });
            finalURL = page.url();
            navigated = true;
        } catch (e) {
            console.log('⚠️ No redirect to /admin/ilanlar/{id} — submit may have failed validation');
        }
        await page.waitForLoadState('networkidle').catch(() => {});

        // ── DB persistence doğrulaması ────────────────────────────────────────
        await page.waitForTimeout(2000);
        const afterResp = await request.get('/api/v1/ilanlar?per_page=1');
        const afterData = await afterResp.json();
        const afterTotal = afterData.meta?.total ?? afterData.total ?? 0;
        const recordCreated = afterTotal > baseTotal;

        console.log(`📊 After-submit ilan count: ${afterTotal} (baseline ${baseTotal})`);
        console.log(`  Record created: ${recordCreated ? '✅ YES' : '❌ NO'}`);

        // ── Sonuçları topla ve yaz ────────────────────────────────────────────
        const results = {
            timestamp: new Date().toISOString(),
            baselineIlanCount: baseTotal,
            afterSubmitIlanCount: afterTotal,
            recordCreated,
            navigatedToIlanDetail: navigated,
            finalURL,
            submitButtonText: btnText,
            preSubmitState,
            networkLog,
            consoleErrorsCount: consoleErrors.length,
            consoleErrors: consoleErrors.slice(0, 20),
        };

        fs.writeFileSync(
            path.join(EVIDENCE_DIR, 'tc-gt-06b-db-persistence.json'),
            JSON.stringify(results, null, 2)
        );

        console.log('\n📊 TC-GT-06b RESULTS:');
        console.log(`  Baseline: ${baseTotal} → After: ${afterTotal}`);
        console.log(`  Record created: ${recordCreated ? '✅' : '❌'}`);
        console.log(`  Navigated to detail: ${navigated ? '✅' : '❌'}`);
        console.log(`  Submit button: "${btnText}"`);
        console.log(`  Pre-submit state: ${JSON.stringify(preSubmitState)}`);
        console.log(`  Network POSTs: ${networkLog.length}`);
        networkLog.forEach((n) => console.log(`    → ${n.method} ${n.url} [${n.status}] ${n.body.slice(0, 200)}`));
        console.log(`  Console errors: ${consoleErrors.length}`);
        consoleErrors.forEach((e) => console.log(`    ⚠️ ${e}`));

        // ── Assertions ────────────────────────────────────────────────────────
        expect(recordCreated, `DB persistence FAILED: ilan count ${baseTotal} → ${afterTotal}. Submit button "${btnText}", pre-state ${JSON.stringify(preSubmitState)}`).toBe(true);
        expect(consoleErrors.length, `Console errors: ${consoleErrors.join(' | ')}`).toBe(0);
    });
});