import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    page.on('console', (msg) => {
        if (msg.text().includes('STYLE_CHANGE') || msg.text().includes('Konut')) {
            console.log(`🖥️ ${msg.text().substring(0, 150)}`);
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
    await page.waitForTimeout(500);

    // MutationObserver ekle
    await page.evaluate(() => {
        const target = document.querySelector('.konut-satilik-form');
        if (!target) {
            console.log('STYLE_CHANGE: Element bulunamadı');
            return;
        }

        console.log(
            'STYLE_CHANGE: MutationObserver başlatıldı, current style:',
            target.getAttribute('style')
        );

        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                    const newStyle = target.getAttribute('style');
                    console.log('STYLE_CHANGE: Style değişti ->', newStyle);

                    // Stack trace almaya çalış
                    try {
                        throw new Error('Stack trace');
                    } catch (e) {
                        console.log(
                            'STYLE_CHANGE: Stack:',
                            e.stack.split('\n').slice(1, 5).join(' | ')
                        );
                    }
                }
            });
        });

        observer.observe(target, { attributes: true, attributeFilter: ['style'] });
    });

    await page.waitForTimeout(1500);

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

    await page.waitForTimeout(15000);
    await browser.close();
})();
