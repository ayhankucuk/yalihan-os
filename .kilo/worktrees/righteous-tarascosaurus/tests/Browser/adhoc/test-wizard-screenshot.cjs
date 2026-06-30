const puppeteer = require('puppeteer');

(async () => {
    console.log('🚀 Test başlatılıyor...');

    const browser = await puppeteer.launch({ headless: 'new' });
    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    try {
        // Önce login sayfasına git
        console.log('🔐 Login yapılıyor...');
        await page.goto('http://localhost:8002/login', {
            waitUntil: 'networkidle2',
            timeout: 30000,
        });

        // Login form kontrolü
        const loginForm = await page.$('form');
        if (loginForm) {
            await page.type('input[name="email"]', 'ayhankucuk@gmail.com');
            await page.type('input[name="password"]', 'admin123');
            await page.click('button[type="submit"]');
            await new Promise((r) => setTimeout(r, 2000));
            console.log('✅ Login başarılı');
        }

        console.log('🌐 Create Wizard sayfasına gidiliyor...');
        await page.goto('http://localhost:8002/admin/ilanlar/create-wizard', {
            waitUntil: 'networkidle2',
            timeout: 30000,
        });

        // Sayfa içeriğini kontrol et
        const pageTitle = await page.title();
        console.log('📄 Sayfa başlığı:', pageTitle);

        await page.screenshot({ path: 'test-screenshots/step1-initial.png', fullPage: true });
        console.log('📸 Step 1 ekran görüntüsü alındı');

        // Kategori select'lerini kontrol et
        const hasAnaKategori = await page.$('#ana_kategori_id');
        console.log('📋 Ana kategori select:', hasAnaKategori ? 'Bulundu' : 'Bulunamadı');

        if (!hasAnaKategori) {
            // Sayfanın HTML'ini kaydet
            const html = await page.content();
            require('fs').writeFileSync('test-screenshots/page-content.html', html);
            console.log('📄 Sayfa içeriği kaydedildi: test-screenshots/page-content.html');
            throw new Error('Kategori select elementi bulunamadı');
        }

        // Ana kategori seç: Arsa & Arazi (id=3)
        console.log('🔄 Ana kategori seçiliyor: Arsa & Arazi...');
        await page.select('#ana_kategori_id', '3');
        await new Promise((r) => setTimeout(r, 1500));

        // Alt kategorilerin yüklenmesini bekle
        await page.waitForSelector('#alt_kategori_id option:not([value=""])', { timeout: 5000 });

        // Alt kategori seç
        console.log('🔄 Alt kategori seçiliyor: Arsa (Konut/Villa)...');
        await page.select('#alt_kategori_id', '15');
        await new Promise((r) => setTimeout(r, 500));

        // Yayın tiplerinin yüklenmesini bekle
        await page.waitForSelector('#yayin_tipi_id option:not([value=""])', { timeout: 5000 });

        // Yayın tipi seç
        console.log('🔄 Yayın tipi seçiliyor: Satılık...');
        await page.select('#yayin_tipi_id', '78');
        await new Promise((r) => setTimeout(r, 2500));

        await page.screenshot({ path: 'test-screenshots/step1-selected.png', fullPage: true });
        console.log('📸 Step 1 (seçimler yapıldı) ekran görüntüsü alındı');

        // Features container'ı kontrol et
        const featuresInfo = await page.evaluate(() => {
            const root = document.getElementById('features-dynamic-root');
            const content = document.getElementById('features-content');
            const container = document.getElementById('features-container');
            return {
                rootExists: !!root,
                contentExists: !!content,
                containerExists: !!container,
                contentChildren: content ? content.children.length : 0,
                contentHTML: content ? content.innerHTML.substring(0, 500) : 'N/A',
            };
        });
        console.log('📊 Features durumu:', featuresInfo);

        // İleri butonuna tıkla
        console.log("➡️ Step 2'ye geçiliyor...");
        const clicked = await page.evaluate(() => {
            const buttons = document.querySelectorAll('button');
            for (const btn of buttons) {
                if (btn.textContent.includes('İleri')) {
                    btn.click();
                    return true;
                }
            }
            return false;
        });
        console.log('🖱️ İleri butonu tıklandı:', clicked);

        await new Promise((r) => setTimeout(r, 2000));

        await page.screenshot({ path: 'test-screenshots/step2-features.png', fullPage: true });
        console.log('📸 Step 2 (özellikler) ekran görüntüsü alındı');

        // Scroll to features
        await page.evaluate(() => {
            const content = document.getElementById('features-content');
            if (content) {
                content.scrollIntoView({ behavior: 'instant', block: 'start' });
            }
        });

        await new Promise((r) => setTimeout(r, 500));
        await page.screenshot({ path: 'test-screenshots/step2-features-detail.png' });
        console.log('📸 Features detay ekran görüntüsü alındı');

        console.log('\n✅ Test tamamlandı!');
        console.log('📁 Ekran görüntüleri: test-screenshots/ klasöründe');
    } catch (error) {
        console.error('❌ Hata:', error.message);
        await page.screenshot({ path: 'test-screenshots/error.png', fullPage: true });
    }

    await browser.close();
})();
