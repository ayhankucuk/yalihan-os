import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: false });
const page = await browser.newPage();

// Console mesajlarını yakala
page.on('console', (msg) => {
    if (msg.type() === 'error' || msg.type() === 'warning') {
        console.log(`🖥️ [${msg.type()}]:`, msg.text());
    }
});

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
    await page.waitForTimeout(5000); // Sayfa tam yüklensin
    console.log('✅ Wizard URL:', page.url());

    // Wizard objesinin yüklendiğini bekle
    const wizardLoaded = await page.evaluate(() => {
        return typeof window.ilanWizard !== 'undefined';
    });
    console.log('📋 Wizard JS yüklendi mi:', wizardLoaded);

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

    // Seçilen değerleri kontrol et
    const selectedAna = await page.locator('#ana_kategori_id').inputValue();
    const selectedAlt = await page.locator('#alt_kategori_id').inputValue();
    const selectedYayin = await page.locator('#yayin_tipi_id').inputValue();
    console.log('📋 Seçilen değerler:', {
        ana: selectedAna,
        alt: selectedAlt,
        yayin: selectedYayin,
    });

    // Screenshot before click
    await page.screenshot({ path: 'test-screenshots/wizard-before-next.png', fullPage: true });
    console.log('📸 Before next screenshot');

    // Ileri butonu - JavaScript ile tıkla
    console.log('🔄 Step 2 ye geçiliyor...');

    // Wizard objesi üzerinden nextStep çağır
    const nextResult = await page.evaluate(() => {
        const wizard = window.ilanWizard ? window.ilanWizard() : null;
        if (wizard && wizard.nextStep) {
            const result = wizard.nextStep();
            return { success: result, currentStep: wizard.currentStep };
        }
        return { error: 'Wizard not found' };
    });
    console.log('📋 nextStep sonucu:', nextResult);

    await page.waitForTimeout(3000);

    // Step 2 görünür mü kontrol
    const currentStep = await page.evaluate(() => {
        const wizard = window.ilanWizard ? window.ilanWizard() : null;
        return wizard ? wizard.currentStep : 'unknown';
    });
    console.log('📋 Şu anki step:', currentStep);

    // Screenshot
    await page.screenshot({ path: 'test-screenshots/wizard-after-next.png', fullPage: true });
    console.log('📸 After next screenshot');

    // Notification mesajı var mı?
    const notification = await page
        .locator('.notification, .toast, [role="alert"]')
        .first()
        .textContent()
        .catch(() => 'no notification');
    console.log('📋 Notification:', notification);

    console.log('✅ Test tamamlandı! Tarayıcı 30 saniye açık kalacak...');
    await page.waitForTimeout(30000);
} catch (error) {
    console.error('❌ Hata:', error.message);
    await page.screenshot({ path: 'test-screenshots/wizard-error-detailed.png', fullPage: true });
} finally {
    await browser.close();
}
