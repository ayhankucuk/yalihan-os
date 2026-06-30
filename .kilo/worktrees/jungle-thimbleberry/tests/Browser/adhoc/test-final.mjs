import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: true });
    console.log('📊 FINAL STABILITY CHECK START');

    const context = await browser.newContext();
    const page = await context.newPage();

    try {
        console.log('  - Navigating to Login...');
        await page.goto('http://127.0.0.1:8002/login');
        await page.fill('input[name="email"]', 'ayhankucuk@gmail.com');
        await page.fill('input[name="password"]', 'admin123');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin**');

        console.log('  - Logged in. Navigating to Wizard...');
        await page.goto('http://127.0.0.1:8002/admin/ilanlar/create-wizard');
        await page.waitForSelector('#ana_kategori_id');

        console.log('  - Selecting: Konut (12) + Satılık');
        await page.selectOption('#ana_kategori_id', '12');

        // Wait for Alt Kategori to be enabled
        await page.waitForFunction(() => !document.getElementById('alt_kategori_id').disabled, {
            timeout: 30000,
        });
        await page.selectOption('#alt_kategori_id', { label: 'Daire' });

        // Wait for Yayin Tipi to be enabled
        await page.waitForFunction(() => !document.getElementById('yayin_tipi_id').disabled, {
            timeout: 30000,
        });
        await page.selectOption('#yayin_tipi_id', { label: 'Satılık' });

        console.log('  - Clicking Next...');
        await page.click('button:has-text("İleri")');
        await page.waitForTimeout(3000);

        const state = await page.evaluate(() => {
            const el = document.getElementById('step-2-universal-container');
            return {
                step: window.ilanWizard?.().currentStep,
                visible: el ? el.offsetParent !== null : false,
                currentForm: el?.__x?.$data?.currentForm,
                title: document.querySelector('#step-2-universal-container h3')?.innerText,
                hasBaslik: !!document.getElementById('baslik'),
            };
        });

        console.log(`  Result: Step ${state.step}`);
        console.log(`  Visible: ${state.visible}`);
        console.log(`  Current Form: ${state.currentForm}`);
        console.log(`  Title: ${state.title}`);
        console.log(`  Has Başlık Input: ${state.hasBaslik}`);

        if (
            state.step === 2 &&
            state.visible &&
            state.currentForm === 'konut_satilik' &&
            state.hasBaslik
        ) {
            console.log('  ✅ SUCCESS: UNIFIED SYSTEM VERIFIED');
        } else {
            console.log('  ❌ FAILURE: System not working as expected');
        }

        await page.screenshot({ path: 'test-screenshots/final-verification.png', fullPage: true });
    } catch (error) {
        console.error(`  ❌ ERROR: ${error.message}`);
    } finally {
        await browser.close();
        console.log('🏁 CHECK FINISHED');
    }
})();
