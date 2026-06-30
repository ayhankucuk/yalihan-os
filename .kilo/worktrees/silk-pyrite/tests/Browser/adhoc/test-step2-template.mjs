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

    // Template ve durum analizi
    const analysis = await page.evaluate(() => {
        // Step 2 wrapper'ı bul
        const step2Wrapper = document.querySelector('[x-data*="isKonutSatilik"]');
        if (!step2Wrapper) return { error: 'Step 2 wrapper bulunamadı' };

        // Alpine.js data stack
        const alpineData = step2Wrapper._x_dataStack?.[0];

        // Template'leri bul
        const templates = step2Wrapper.querySelectorAll('template[x-if]');
        const templateInfo = [];

        templates.forEach((tpl, i) => {
            const xIf = tpl.getAttribute('x-if');
            // Template content var mı?
            const hasContent = tpl.content && tpl.content.childNodes.length > 0;
            // Template next sibling'de render edilmiş mi?
            const nextSibling = tpl.nextElementSibling;

            templateInfo.push({
                index: i,
                xIf: xIf,
                hasContent: hasContent,
                contentChildCount: tpl.content?.childNodes?.length || 0,
                nextSiblingTag: nextSibling?.tagName,
                nextSiblingDisplay: nextSibling ? getComputedStyle(nextSibling).display : null,
            });
        });

        // DOM'da "Konut Satılık Detayları" var mı?
        const konutHeader = document.querySelector('h3');
        const allH3 = document.querySelectorAll('h3');
        const h3Texts = Array.from(allH3).map((h) => h.textContent.trim());

        return {
            alpineData: alpineData
                ? {
                      isKonutSatilik: alpineData.isKonutSatilik,
                      isYazlikKiralama: alpineData.isYazlikKiralama,
                  }
                : null,
            templates: templateInfo,
            allH3Texts: h3Texts,
            step2HTML: step2Wrapper.innerHTML.substring(0, 2000),
        };
    });

    console.log('\n📊 TEMPLATE ANALYSIS:');
    console.log('Alpine Data:', JSON.stringify(analysis.alpineData, null, 2));
    console.log('Templates:', JSON.stringify(analysis.templates, null, 2));
    console.log('H3 texts:', analysis.allH3Texts);
    console.log('\n📄 Step 2 HTML (ilk 500):');
    console.log(analysis.step2HTML?.substring(0, 500));

    await page.screenshot({ path: 'test-screenshots/step2-template-debug.png', fullPage: true });
    console.log('\n📸 Screenshot: test-screenshots/step2-template-debug.png');

    await page.waitForTimeout(15000);
    await browser.close();
})();
