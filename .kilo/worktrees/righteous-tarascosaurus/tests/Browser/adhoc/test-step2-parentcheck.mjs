import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    page.on('console', (msg) => {
        if (msg.type() === 'log' && msg.text().includes('Step 2')) {
            console.log(`🖥️ ${msg.text().substring(0, 100)}`);
        }
    });

    console.log('🔄 Login...');
    await page.goto('http://127.0.0.1:8002/login');
    await page.fill('input[name="email"]', 'ayhankucuk@gmail.com');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);

    console.log('🔄 Wizard sayfası...');
    await page.goto('http://127.0.0.1:8002/admin/ilanlar/create-wizard');
    await page.waitForTimeout(2000);

    // Step 1 - Seçimler
    await page.selectOption('#ana_kategori_id', '12');
    await page.waitForTimeout(500);
    await page.selectOption('#alt_kategori_id', { label: 'Daire' });
    await page.waitForTimeout(500);
    await page.selectOption('#yayin_tipi_id', { label: 'Satılık' });
    await page.waitForTimeout(1000);

    // Step 2
    console.log('🔄 Step 2 ye geçiliyor...');
    await page.click('button:has-text("İleri")');
    await page.waitForTimeout(3000);

    // Konut satilik parent div kontrolü
    const parentCheck = await page.evaluate(() => {
        const konutForm = document.querySelector('.konut-satilik-form');
        if (!konutForm) return { error: 'konut-satilik-form bulunamadı' };

        // Parent div'i bul
        const parentDiv = konutForm.parentElement;

        return {
            parentTag: parentDiv?.tagName,
            parentClass: parentDiv?.className,
            parentHidden: parentDiv?.classList.contains('hidden'),
            parentDisplay: parentDiv ? getComputedStyle(parentDiv).display : null,

            // konut form display
            formDisplay: getComputedStyle(konutForm).display,

            // Form içindeki select
            selectElement: (() => {
                const select = konutForm.querySelector('select[name="konut_tipi"]');
                if (!select) return { found: false };
                return {
                    found: true,
                    display: getComputedStyle(select).display,
                    visibility: getComputedStyle(select).visibility,
                    parentDisplay: getComputedStyle(select.parentElement).display,
                };
            })(),
        };
    });

    console.log('\n📊 PARENT CHECK:');
    console.log(JSON.stringify(parentCheck, null, 2));

    // Konut tipi select'i bulmayı dene
    const konutTipiLocator = page.locator('select[name="konut_tipi"]');
    const count = await konutTipiLocator.count();
    console.log(`\n📊 Konut Tipi select sayısı: ${count}`);

    if (count > 0) {
        // İlkini seç ve etkileşim dene
        try {
            await konutTipiLocator.first().scrollIntoViewIfNeeded({ timeout: 5000 });
            const isVisible = await konutTipiLocator.first().isVisible();
            console.log(`📊 İlk select görünür mü: ${isVisible}`);
        } catch (e) {
            console.log(`❌ scrollIntoView hatası: ${e.message}`);
        }
    }

    await page.screenshot({ path: 'test-screenshots/step2-final-check.png', fullPage: true });
    console.log('\n📸 Screenshot: test-screenshots/step2-final-check.png');

    await page.waitForTimeout(15000);
    await browser.close();
})();
