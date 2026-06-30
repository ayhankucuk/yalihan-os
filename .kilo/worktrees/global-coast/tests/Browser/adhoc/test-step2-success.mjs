import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

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

    // Konut Satılık başlığını bul ve scroll et
    const header = await page.locator('h3:has-text("Konut Satılık Detayları")');
    const headerCount = await header.count();

    console.log(`📊 "Konut Satılık Detayları" başlık sayısı: ${headerCount}`);

    if (headerCount > 0) {
        // Scroll to header
        await header.first().scrollIntoViewIfNeeded();
        await page.waitForTimeout(500);

        // Görünürlük kontrolü
        const isVisible = await header.first().isVisible();
        console.log(`📊 Başlık görünür mü: ${isVisible}`);

        // Bounding box
        const box = await header.first().boundingBox();
        console.log(`📊 Bounding box:`, box);
    }

    // Konut Tipi select
    const konutTipi = await page.locator('select[name="konut_tipi"]');
    const konutTipiCount = await konutTipi.count();
    console.log(`📊 "Konut Tipi" select sayısı: ${konutTipiCount}`);

    if (konutTipiCount > 0) {
        const isVisible = await konutTipi.first().isVisible();
        console.log(`📊 Konut Tipi görünür mü: ${isVisible}`);

        // Try to interact
        if (isVisible) {
            await konutTipi.first().scrollIntoViewIfNeeded();
            await konutTipi.first().selectOption('daire');
            console.log('✅ Konut Tipi seçildi: Daire');
        }
    }

    // Form alanı
    const odaSayisi = await page.locator('input[name="oda_sayisi"]');
    if ((await odaSayisi.count()) > 0) {
        await odaSayisi.first().scrollIntoViewIfNeeded();
        await odaSayisi.first().fill('3');
        console.log('✅ Oda sayısı girildi: 3');
    }

    // Screenshot
    await page.screenshot({ path: 'test-screenshots/step2-success.png', fullPage: true });
    console.log('\n📸 Screenshot: test-screenshots/step2-success.png');

    console.log('\n🎉 TEST TAMAMLANDI!');

    await page.waitForTimeout(15000);
    await browser.close();
})();
