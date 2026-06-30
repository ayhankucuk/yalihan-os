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

    // Konut Satılık form içindeki select'i bul (spesifik selector)
    const konutSatilikSelect = page.locator('.konut-satilik-form select[name="konut_tipi"]');
    const yazlikSelect = page.locator('.yazlik-kiralama-form select[name="konut_tipi"]');

    const konutCount = await konutSatilikSelect.count();
    const yazlikCount = await yazlikSelect.count();

    console.log(`📊 Konut Satılık form'daki select sayısı: ${konutCount}`);
    console.log(`📊 Yazlık form'daki select sayısı: ${yazlikCount}`);

    if (konutCount > 0) {
        const isVisible = await konutSatilikSelect.isVisible();
        console.log(`📊 Konut Satılık select görünür: ${isVisible}`);

        if (isVisible) {
            await konutSatilikSelect.selectOption('daire');
            console.log('✅ Konut Tipi seçildi: Daire');

            // Oda sayısı
            const odaSayisi = page.locator('.konut-satilik-form input[name="oda_sayisi"]');
            if ((await odaSayisi.count()) > 0 && (await odaSayisi.isVisible())) {
                await odaSayisi.fill('3');
                console.log('✅ Oda sayısı girildi: 3');
            }

            console.log('\n🎉 BAŞARILI! Konut Satılık formu çalışıyor!');
        } else {
            console.log('❌ Select görünür değil');

            // Parent chain analizi
            const analysis = await page.evaluate(() => {
                const form = document.querySelector('.konut-satilik-form');
                if (!form) return { error: 'form bulunamadı' };

                const chain = [];
                let current = form;

                while (current && current !== document.body) {
                    const style = getComputedStyle(current);
                    chain.push({
                        tag: current.tagName,
                        class: (current.className || '').toString().substring(0, 40),
                        display: style.display,
                        visibility: style.visibility,
                    });
                    current = current.parentElement;
                }

                return { chain: chain.slice(0, 8) };
            });

            console.log('\n📋 Konut Satılık Form Parent Chain:');
            analysis.chain?.forEach((p, i) => {
                const marker = p.display === 'none' || p.visibility === 'hidden' ? '❌' : '✅';
                console.log(`  ${i}. ${marker} ${p.tag} | ${p.display} | ${p.class}`);
            });
        }
    }

    await page.screenshot({ path: 'test-screenshots/step2-konut-satilik.png', fullPage: true });
    console.log('\n📸 Screenshot: test-screenshots/step2-konut-satilik.png');

    await page.waitForTimeout(15000);
    await browser.close();
})();
