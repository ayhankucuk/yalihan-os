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
    await page.waitForTimeout(1500);

    // Step 1 - Seçimler
    await page.selectOption('#ana_kategori_id', '12');
    await page.waitForTimeout(500);
    console.log('✅ Ana Kategori: Konut');

    await page.selectOption('#alt_kategori_id', { label: 'Daire' });
    await page.waitForTimeout(500);
    console.log('✅ Alt Kategori: Daire');

    await page.selectOption('#yayin_tipi_id', { label: 'Satılık' });
    await page.waitForTimeout(500);
    console.log('✅ Yayin Tipi: Satılık');

    // Step 2
    console.log('🔄 Step 2 ye geçiliyor...');
    await page.click('button:has-text("İleri")');
    await page.waitForTimeout(2000);

    // x-show directive'lerini manuel kontrol et
    const xShowStatus = await page.evaluate(() => {
        // Step 2 wrapper div (x-data with isKonutSatilik)
        const step2Wrapper = document.querySelector('[x-data*="isKonutSatilik"]');

        // Bu divlerin computed style'larını al
        const yazlikDiv = document.querySelector('div[x-show="isYazlikKiralama"]');
        const konutDiv = document.querySelector(
            'div[x-show="isKonutSatilik && !isYazlikKiralama"]'
        );
        const normalDiv = document.querySelector(
            'div[x-show="!isYazlikKiralama && !isKonutSatilik"]'
        );

        // Alpine.js $data al
        const step2Data = step2Wrapper?._x_dataStack?.[0];

        return {
            step2WrapperFound: !!step2Wrapper,
            step2DataStack: step2Data
                ? {
                      isKonutSatilik: step2Data.isKonutSatilik,
                      isYazlikKiralama: step2Data.isYazlikKiralama,
                      checkCategoryFn: typeof step2Data.checkCategoryFn,
                  }
                : null,
            yazlikDivDisplay: yazlikDiv ? getComputedStyle(yazlikDiv).display : 'not found',
            konutDivDisplay: konutDiv ? getComputedStyle(konutDiv).display : 'not found',
            normalDivDisplay: normalDiv ? getComputedStyle(normalDiv).display : 'not found',
            // İç form div kontrolü
            innerFormDiv: (() => {
                const formDiv = konutDiv?.querySelector('div.space-y-6');
                if (!formDiv) return 'not found';
                return {
                    display: getComputedStyle(formDiv).display,
                    hasXData: formDiv.hasAttribute('x-data'),
                    xDataValue: formDiv.getAttribute('x-data'),
                };
            })(),
        };
    });

    console.log('\n📊 X-SHOW STATUS:');
    console.log(JSON.stringify(xShowStatus, null, 2));

    // Screenshot
    await page.screenshot({ path: 'test-screenshots/step2-xshow-debug.png' });
    console.log('\n📸 Screenshot: test-screenshots/step2-xshow-debug.png');

    await page.waitForTimeout(15000);
    await browser.close();
})();
