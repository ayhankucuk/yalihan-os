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

    // Parent chain analizi
    const parentAnalysis = await page.evaluate(() => {
        // "Konut Satılık Detayları" başlığını bul
        const allH3 = document.querySelectorAll('h3');
        let targetH3 = null;

        for (const h3 of allH3) {
            if (h3.textContent.includes('Konut Satılık Detayları')) {
                targetH3 = h3;
                break;
            }
        }

        if (!targetH3) return { error: 'H3 bulunamadı' };

        // Parent chain
        const chain = [];
        let current = targetH3;
        let hiddenFound = null;

        while (current && current !== document.body) {
            const style = getComputedStyle(current);
            const display = style.display;
            const visibility = style.visibility;
            const opacity = style.opacity;
            const inlineStyle = current.getAttribute('style');
            const xShow = current.getAttribute('x-show');
            const xData = current.getAttribute('x-data');

            const isHidden = display === 'none' || visibility === 'hidden' || opacity === '0';

            chain.push({
                tag: current.tagName,
                className: current.className.substring(0, 50),
                display,
                visibility,
                opacity,
                inlineStyle: inlineStyle?.substring(0, 50),
                xShow,
                xData: xData?.substring(0, 30),
                isHidden,
            });

            if (isHidden && !hiddenFound) {
                hiddenFound = {
                    tag: current.tagName,
                    className: current.className,
                    inlineStyle,
                    xShow,
                };
            }

            current = current.parentElement;
        }

        return {
            h3Text: targetH3.textContent.trim(),
            parentChain: chain,
            firstHiddenElement: hiddenFound,
        };
    });

    console.log('\n📊 PARENT CHAIN ANALYSIS:');
    console.log('H3 Text:', parentAnalysis.h3Text);
    console.log(
        '\n🔴 First Hidden Element:',
        JSON.stringify(parentAnalysis.firstHiddenElement, null, 2)
    );
    console.log('\n📋 Full Parent Chain:');
    parentAnalysis.parentChain?.forEach((p, i) => {
        const marker = p.isHidden ? '❌' : '✅';
        console.log(
            `  ${i}. ${marker} ${p.tag} | display: ${p.display} | xShow: ${p.xShow || '-'}`
        );
    });

    await page.screenshot({ path: 'test-screenshots/step2-parent-chain.png', fullPage: true });

    await page.waitForTimeout(15000);
    await browser.close();
})();
