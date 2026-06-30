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

    // Konut Tipi select'in parent chain'ini kontrol et
    const selectAnalysis = await page.evaluate(() => {
        const select = document.querySelector('select[name="konut_tipi"]');
        if (!select) return { error: 'select bulunamadı' };

        const chain = [];
        let current = select;
        let hiddenFound = null;

        while (current && current !== document.body) {
            const style = getComputedStyle(current);
            const rect = current.getBoundingClientRect();
            const isHidden =
                style.display === 'none' ||
                style.visibility === 'hidden' ||
                style.opacity === '0' ||
                rect.width === 0 ||
                rect.height === 0;

            chain.push({
                tag: current.tagName,
                class: (current.className || '').toString().substring(0, 40),
                display: style.display,
                visibility: style.visibility,
                opacity: style.opacity,
                width: rect.width,
                height: rect.height,
                isHidden,
            });

            if (isHidden && !hiddenFound) {
                hiddenFound = {
                    tag: current.tagName,
                    class: current.className,
                    reason:
                        style.display === 'none'
                            ? 'display:none'
                            : style.visibility === 'hidden'
                              ? 'visibility:hidden'
                              : style.opacity === '0'
                                ? 'opacity:0'
                                : rect.width === 0
                                  ? 'width:0'
                                  : rect.height === 0
                                    ? 'height:0'
                                    : 'unknown',
                };
            }

            current = current.parentElement;
        }

        return {
            selectRect: select.getBoundingClientRect(),
            chain: chain.slice(0, 10),
            hiddenFound,
        };
    });

    console.log('\n📊 SELECT ANALYSIS:');
    console.log('Select Rect:', selectAnalysis.selectRect);
    console.log('Hidden Found:', JSON.stringify(selectAnalysis.hiddenFound, null, 2));
    console.log('\nParent Chain:');
    selectAnalysis.chain?.forEach((p, i) => {
        const marker = p.isHidden ? '❌' : '✅';
        console.log(
            `  ${i}. ${marker} ${p.tag} | ${p.display} | w:${p.width} h:${p.height} | ${p.class}`
        );
    });

    await page.screenshot({ path: 'test-screenshots/step2-select-debug.png', fullPage: true });

    await page.waitForTimeout(15000);
    await browser.close();
})();
