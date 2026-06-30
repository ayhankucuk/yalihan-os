import { chromium } from 'playwright';

const testCases = [
    {
        name: 'Konut + Satılık',
        anaKategori: '12',
        altKategori: 'Daire',
        yayinTipi: 'Satılık',
        expectedFormSelector: 'input[name="oda_sayisi"], #baslik',
        expectedHeader: 'Konut Satılık Detayları',
    },
];

(async () => {
    const browser = await chromium.launch({ headless: true });

    const fs = await import('fs');
    if (!fs.existsSync('test-screenshots')) fs.mkdirSync('test-screenshots');

    console.log('📊 FOCUSED DIAGNOSTIC START');
    console.log('='.repeat(50));

    for (const testCase of testCases) {
        console.log(`\n🧪 TEST: ${testCase.name}`);

        const context = await browser.newContext();
        const page = await context.newPage();

        const logs = [];
        const errors = [];
        page.on('console', (msg) => {
            const text = msg.text();
            if (msg.type() === 'error') errors.push(text);
            logs.push(`[${msg.type()}] ${text}`);
        });

        try {
            // Wait longer for login
            await page.goto('http://127.0.0.1:8002/login', { timeout: 60000 });
            await page.fill('input[name="email"]', 'ayhankucuk@gmail.com');
            await page.fill('input[name="password"]', 'admin123');
            await page.click('button[type="submit"]');
            await page.waitForNavigation({ waitUntil: 'networkidle', timeout: 60000 });

            console.log('  - Logged in successfully');

            await page.goto('http://127.0.0.1:8002/admin/ilanlar/create-wizard', {
                timeout: 60000,
            });
            await page.waitForSelector('#ana_kategori_id', { state: 'attached', timeout: 60000 });

            // Select Ana Kategori
            const anaSelector = '#ana_kategori_id';
            await page.selectOption(anaSelector, testCase.anaKategori);
            console.log(`  - Ana Kategori selected: ${testCase.anaKategori}`);
            await page.waitForTimeout(3000);

            // Select Alt Kategori
            if (testCase.altKategori) {
                const altSelector = '#alt_kategori_id';
                await page.waitForFunction(
                    (sel) => !document.querySelector(sel).disabled,
                    altSelector,
                    { timeout: 20000 }
                );

                await page.selectOption(altSelector, { label: testCase.altKategori });
                console.log(`  - Alt kategori selected: ${testCase.altKategori}`);

                await page.evaluate((sel) => {
                    document
                        .querySelector(sel)
                        .dispatchEvent(new Event('change', { bubbles: true }));
                }, altSelector);

                await page.waitForTimeout(3000);
            }

            // Select Yayın Tipi
            const yayinSelector = '#yayin_tipi_id';
            await page.waitForFunction(
                (sel) => !document.querySelector(sel).disabled,
                yayinSelector,
                { timeout: 20000 }
            );

            await page.selectOption(yayinSelector, { label: testCase.yayinTipi });
            console.log(`  - Yayın tipi selected: ${testCase.yayinTipi}`);

            await page.evaluate((sel) => {
                document.querySelector(sel).dispatchEvent(new Event('change', { bubbles: true }));
            }, yayinSelector);

            await page.waitForTimeout(3000);

            // Click Next
            await page.click('button:has-text("İleri")');
            await page.waitForTimeout(5000);

            // Diagnostics
            const diag = await page.evaluate(() => {
                const result = {
                    currentStep: window.ilanWizard?.()?.currentStep,
                    isAlpineReady: typeof Alpine !== 'undefined',
                    isComponentFuncReady: typeof window.wizardStep2Component === 'function',
                    visibleHeaders: Array.from(document.querySelectorAll('h1, h2, h3, h4'))
                        .filter((h) => h.offsetParent !== null)
                        .map((h) => h.innerText.trim()),
                    cloakElements: Array.from(document.querySelectorAll('[x-cloak]')).length,
                    step2State: null,
                };

                const step2El = document.getElementById('step-2-universal-container');
                if (step2El && step2El.__x) {
                    result.step2State = {
                        currentForm: step2El.__x.$data.currentForm,
                    };
                }

                return result;
            });

            console.log(`  Result: Step ${diag.currentStep}`);
            console.log(`  Function Ready: ${diag.isComponentFuncReady}`);
            console.log(`  Step 2 State: ${JSON.stringify(diag.step2State)}`);
            console.log(`  Visible Headers: ${diag.visibleHeaders.join(' | ') || 'None'}`);
            if (errors.length > 0) {
                console.log(`  ❌ Console Errors: ${errors.slice(0, 3).join(' | ')}`);
            }
            if (diag.cloakElements > 0) {
                console.log(`  ⚠️ x-cloak elements remaining: ${diag.cloakElements}`);
            }

            const headerMatch = diag.visibleHeaders.some((h) =>
                h.includes(testCase.expectedHeader)
            );
            const hasFormFields = await page.evaluate((sel) => {
                const parts = sel.split(',').map((p) => p.trim());
                return parts.some((p) => {
                    const el = document.querySelector(p);
                    return el && (el.offsetParent !== null || el.offsetWidth > 0);
                });
            }, testCase.expectedFormSelector);

            if (diag.currentStep === 2 && headerMatch && hasFormFields) {
                console.log('  ✅ SUCCESS');
            } else {
                console.log('  ❌ FAILED');
                if (!headerMatch)
                    console.log(`    Expected header "${testCase.expectedHeader}" not visible.`);
                if (!hasFormFields)
                    console.log(
                        `    Expected fields "${testCase.expectedFormSelector}" not visible.`
                    );
            }

            await page.screenshot({
                path: `test-screenshots/focused-${testCase.name.replace(/\s+/g, '-').toLowerCase()}.png`,
                fullPage: true,
            });
        } catch (error) {
            console.error(`  ❌ ERROR: ${error.message}`);
            // Capture full log for debugging
            console.log('  --- LAST LOGS ---');
            console.log(logs.slice(-5).join('\n  '));
        } finally {
            await page.close();
            await context.close();
        }
    }

    await browser.close();
    console.log('\n🏁 DIAGNOSTIC FINISHED');
})();
