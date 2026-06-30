import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: false });
const page = await browser.newPage();

try {
    // Login
    console.log('🔄 Login...');
    await page.goto('http://127.0.0.1:8002/login');
    await page.fill('input[name="email"]', 'ayhankucuk@gmail.com');
    await page.fill('input[name="password"]', 'admin123');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(2000);
    console.log('✅ Login URL:', page.url());

    // Wizard
    console.log('🔄 Wizard sayfası...');
    await page.goto('http://127.0.0.1:8002/admin/ilanlar/create-wizard');
    await page.waitForTimeout(3000);
    console.log('✅ Wizard URL:', page.url());

    // Ana Kategori
    console.log('📋 Ana Kategori seçiliyor...');
    await page.selectOption('#ana_kategori_id', { label: 'Konut' });
    console.log('✅ Ana Kategori: Konut');
    await page.waitForTimeout(2000);

    // Alt Kategori
    const altOptions = await page.locator('#alt_kategori_id option').allTextContents();
    console.log('📋 Alt Kategoriler:', altOptions);
    await page.selectOption('#alt_kategori_id', { label: 'Daire' });
    console.log('✅ Alt Kategori: Daire');
    await page.waitForTimeout(2000);

    // Yayin Tipi
    const yayinOptions = await page.locator('#yayin_tipi_id option').allTextContents();
    console.log('📋 Yayin Tipleri:', yayinOptions);
    await page.selectOption('#yayin_tipi_id', { label: 'Satılık' });
    console.log('✅ Yayin Tipi: Satılık');
    await page.waitForTimeout(1000);

    // Ileri butonu
    console.log('🔄 Step 2 ye geçiliyor...');
    await page.click('button:has-text("İleri")');
    console.log('✅ İleri tıklandı');
    await page.waitForTimeout(3000);

    // Screenshot
    await page.screenshot({ path: 'test-screenshots/wizard-step2-result.png', fullPage: true });
    console.log('📸 Screenshot: test-screenshots/wizard-step2-result.png');

    // Step 2 görünür mü kontrol
    const baslikInput = await page
        .locator('#baslik')
        .isVisible()
        .catch(() => false);
    console.log('📋 Başlık input görünür mü:', baslikInput);

    console.log('✅ Test tamamlandı! Tarayıcı 30 saniye açık kalacak...');
    await page.waitForTimeout(30000);
} catch (error) {
    console.error('❌ Hata:', error.message);
    await page.screenshot({ path: 'test-screenshots/wizard-error.png', fullPage: true });
} finally {
    await browser.close();
}
