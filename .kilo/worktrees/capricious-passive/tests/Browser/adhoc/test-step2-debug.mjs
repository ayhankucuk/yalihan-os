import { chromium } from 'playwright';

const browser = await chromium.launch({ headless: false });
const page = await browser.newPage();

// Console mesajlarını yakala
page.on('console', (msg) => {
    const text = msg.text();
    if (text.includes('isKonut') || text.includes('Step 2') || text.includes('checkCategory')) {
        console.log(`🖥️ [${msg.type()}]:`, text);
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

    // Wizard
    console.log('🔄 Wizard sayfası...');
    await page.goto('http://127.0.0.1:8002/admin/ilanlar/create-wizard');
    await page.waitForTimeout(5000);

    // Ana Kategori
    await page.selectOption('#ana_kategori_id', { label: 'Konut' });
    console.log('✅ Ana Kategori: Konut');
    await page.waitForTimeout(2000);

    // Alt Kategori
    await page.selectOption('#alt_kategori_id', { label: 'Daire' });
    console.log('✅ Alt Kategori: Daire');
    await page.waitForTimeout(2000);

    // Yayin Tipi
    await page.selectOption('#yayin_tipi_id', { label: 'Satılık' });
    console.log('✅ Yayin Tipi: Satılık');
    await page.waitForTimeout(1000);

    // Step 2'ye geç
    console.log('🔄 Step 2 ye geçiliyor...');
    await page.click('button:has-text("İleri")');
    await page.waitForTimeout(3000);

    // Step 2 Alpine.js data kontrolü
    const step2Data = await page.evaluate(() => {
        // Step 2 div'ini bul
        const step2Div = document.querySelector('[x-show="wizard?.currentStep === 2"]');
        if (!step2Div) return { error: 'Step 2 div not found' };

        // Alpine.js data'sını al
        const alpineData = step2Div._x_dataStack?.[0] || {};
        return {
            isYazlikKiralama: alpineData.isYazlikKiralama,
            isKonutSatilik: alpineData.isKonutSatilik,
            // Select değerleri
            anaKategori: document.getElementById('ana_kategori_id')?.value,
            altKategori: document.getElementById('alt_kategori_id')?.value,
            yayinTipi: document.getElementById('yayin_tipi_id')?.value,
            // Option slug'ları
            anaSlug: document
                .getElementById('ana_kategori_id')
                ?.selectedOptions?.[0]?.getAttribute('data-slug'),
            yayinSlug: document
                .getElementById('yayin_tipi_id')
                ?.selectedOptions?.[0]?.getAttribute('data-slug'),
        };
    });
    console.log('📋 Step 2 Alpine Data:', step2Data);

    // Step 2 içeriği görünür mü?
    const step2InfoVisible = await page
        .locator('#baslik')
        .isVisible()
        .catch(() => false);
    const step2KonutVisible = await page
        .locator('[x-show="isKonutSatilik && !isYazlikKiralama"]')
        .isVisible()
        .catch(() => false);
    const step2NormalVisible = await page
        .locator('[x-show="!isYazlikKiralama && !isKonutSatilik"]')
        .isVisible()
        .catch(() => false);

    console.log('📋 Form görünürlüğü:', {
        baslikInput: step2InfoVisible,
        konutSatilikDiv: step2KonutVisible,
        normalFormDiv: step2NormalVisible,
    });

    // Screenshot
    await page.screenshot({ path: 'test-screenshots/step2-debug.png', fullPage: true });
    console.log('📸 Screenshot: test-screenshots/step2-debug.png');

    // Konut Satılık formu manuel olarak kontrol et
    const konutFormHtml = await page.evaluate(() => {
        const konutDiv = document.querySelector('[x-show="isKonutSatilik && !isYazlikKiralama"]');
        return konutDiv ? konutDiv.innerHTML.substring(0, 500) : 'DIV NOT FOUND';
    });
    console.log('📋 Konut Satılık Form HTML (ilk 500 karakter):', konutFormHtml.substring(0, 200));

    console.log('✅ Test tamamlandı! Tarayıcı 30 saniye açık kalacak...');
    await page.waitForTimeout(30000);
} catch (error) {
    console.error('❌ Hata:', error.message);
    await page.screenshot({ path: 'test-screenshots/step2-error.png', fullPage: true });
} finally {
    await browser.close();
}
