import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    page.on('console', (msg) => console.log(`🖥️ [${msg.type()}]:`, msg.text().substring(0, 80)));

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

    // "Konut Satılık Detayları" başlığı görünür mü?
    const h3Visible = await page.isVisible('h3:has-text("Konut Satılık Detayları")');
    console.log(`\n📊 "Konut Satılık Detayları" başlığı görünür: ${h3Visible}`);

    // Form alanları görünür mü?
    const konutTipiVisible = await page.isVisible('select[name="konut_tipi"]');
    console.log(`📊 "Konut Tipi" select görünür: ${konutTipiVisible}`);

    const odaSayisiVisible = await page.isVisible('input[name="oda_sayisi"]');
    console.log(`📊 "Oda Sayısı" input görünür: ${odaSayisiVisible}`);

    // Structured data form bulundu mu?
    const formExists = await page.evaluate(() => {
        const form = document.getElementById('structured-data-form');
        if (!form) return { found: false };

        const computedStyle = getComputedStyle(form);
        return {
            found: true,
            display: computedStyle.display,
            visibility: computedStyle.visibility,
        };
    });

    console.log(`\n📊 STRUCTURED DATA FORM:`, JSON.stringify(formExists));

    // Screenshot
    await page.screenshot({ path: 'test-screenshots/step2-xif-final.png', fullPage: true });
    console.log('\n📸 Screenshot: test-screenshots/step2-xif-final.png');

    if (h3Visible && konutTipiVisible) {
        console.log('\n✅ BAŞARILI! Step 2 formu görünür durumda!');
    } else {
        console.log('\n❌ HATA: Step 2 formu hala görünmüyor');
    }

    await page.waitForTimeout(15000);
    await browser.close();
})();
