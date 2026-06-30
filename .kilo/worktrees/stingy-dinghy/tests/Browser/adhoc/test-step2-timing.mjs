import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    // Tüm console loglarını topla
    const allLogs = [];
    page.on('console', (msg) => {
        allLogs.push(`[${msg.type()}]: ${msg.text()}`);
        if (msg.text().includes('Konut') || msg.text().includes('konut')) {
            console.log(`🖥️ ${msg.text().substring(0, 120)}`);
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

    // Step 2'ye geçmeden önce, konut-satilik-form element'inin durumunu kontrol et
    const beforeStep2 = await page.evaluate(() => {
        const form = document.querySelector('.konut-satilik-form');
        if (!form) return { found: false };
        return {
            found: true,
            display: getComputedStyle(form).display,
            inlineStyle: form.getAttribute('style'),
        };
    });
    console.log('📊 Step 2 ÖNCESİ konut-satilik-form:', JSON.stringify(beforeStep2));

    // Step 2
    console.log('🔄 Step 2 ye geçiliyor...');
    await page.click('button:has-text("İleri")');
    await page.waitForTimeout(500);

    // Hemen kontrol et
    const afterStep2_500ms = await page.evaluate(() => {
        const form = document.querySelector('.konut-satilik-form');
        if (!form) return { found: false };
        return {
            found: true,
            display: getComputedStyle(form).display,
            inlineStyle: form.getAttribute('style'),
        };
    });
    console.log('📊 Step 2 SONRASI (500ms) konut-satilik-form:', JSON.stringify(afterStep2_500ms));

    await page.waitForTimeout(2500);

    // 3 saniye sonra kontrol et
    const afterStep2_3s = await page.evaluate(() => {
        const form = document.querySelector('.konut-satilik-form');
        if (!form) return { found: false };
        return {
            found: true,
            display: getComputedStyle(form).display,
            inlineStyle: form.getAttribute('style'),
        };
    });
    console.log('📊 Step 2 SONRASI (3s) konut-satilik-form:', JSON.stringify(afterStep2_3s));

    // x-init log geldi mi?
    const xInitLogs = allLogs.filter((l) => l.includes('x-init'));
    console.log('\n📋 x-init logları:', xInitLogs);

    await page.waitForTimeout(15000);
    await browser.close();
})();
