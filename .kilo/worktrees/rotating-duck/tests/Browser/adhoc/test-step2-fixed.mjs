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

    // YENİ Form ID'leri ile kontrol
    const formCheck = await page.evaluate(() => {
        // Konut Satılık form
        const konutForm = document.getElementById('konut-satilik-structured-form');
        // Yazlık Kiralama form
        const yazlikForm = document.getElementById('yazlik-kiralama-structured-form');

        // Konut Tipi select
        const konutTipi = document.querySelector('select[name="konut_tipi"]');

        return {
            konutFormFound: !!konutForm,
            konutFormDisplay: konutForm ? getComputedStyle(konutForm).display : null,
            yazlikFormFound: !!yazlikForm,
            yazlikFormDisplay: yazlikForm ? getComputedStyle(yazlikForm).display : null,
            konutTipiSelectFound: !!konutTipi,
            konutTipiDisplay: konutTipi ? getComputedStyle(konutTipi).display : null,
            konutTipiParentDisplay: konutTipi?.parentElement
                ? getComputedStyle(konutTipi.parentElement).display
                : null,
        };
    });

    console.log('\n📊 FORM CHECK:');
    console.log(JSON.stringify(formCheck, null, 2));

    // Konut Tipi select görünür mü?
    const konutTipiLocator = page.locator('select[name="konut_tipi"]').first();
    const isVisible = await konutTipiLocator.isVisible();
    console.log(`\n📊 Konut Tipi select görünür: ${isVisible}`);

    if (isVisible) {
        // Konut tipini seç
        await konutTipiLocator.selectOption('daire');
        console.log('✅ Konut Tipi: Daire seçildi');

        // Oda sayısı gir
        await page.fill('input[name="oda_sayisi"]', '3');
        console.log('✅ Oda Sayısı: 3 girildi');

        console.log('\n🎉 BAŞARILI! Form görünür ve etkileşilebilir!');
    } else {
        console.log('\n❌ Form hala görünmüyor');
    }

    await page.screenshot({ path: 'test-screenshots/step2-fixed.png', fullPage: true });
    console.log('📸 Screenshot: test-screenshots/step2-fixed.png');

    await page.waitForTimeout(15000);
    await browser.close();
})();
