const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const screenshotDir = path.join(__dirname, 'test-screenshots');
if (!fs.existsSync(screenshotDir)) {
    fs.mkdirSync(screenshotDir, { recursive: true });
}

const timestamp = new Date().toISOString().replace(/[:.]/g, '-');

async function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function testAllSteps() {
    console.log('🚀 Wizard Tüm Adımlar Testi Başlıyor...\n');
    console.log('='.repeat(60));

    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
        timeout: 60000,
    });
    const page = await browser.newPage();
    await page.setDefaultTimeout(60000);
    await page.setDefaultNavigationTimeout(60000);
    await page.setViewport({ width: 1920, height: 1200 });

    const results = {
        timestamp,
        steps: {},
        errors: [],
        screenshots: [],
    };

    try {
        // 1. Login
        console.log('\n📍 1. Login...');
        await page.goto('http://127.0.0.1:8002/login', {
            waitUntil: 'domcontentloaded',
            timeout: 60000,
        });

        const currentUrl = page.url();
        if (currentUrl.includes('login')) {
            await page.type('input[name="email"]', 'ayhankucuk@gmail.com');
            await page.type('input[name="password"]', 'admin123');

            await Promise.all([
                page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
                page.click('button[type="submit"]'),
            ]);

            console.log('   ✅ Giriş başarılı');
        }

        // 2. Go to wizard
        console.log('\n📍 2. Wizard sayfasına gidiliyor...');
        await page.goto('http://127.0.0.1:8002/admin/ilanlar/create-wizard', {
            waitUntil: 'domcontentloaded',
            timeout: 60000,
        });
        await delay(2000);

        // Screenshot Step 1
        await page.screenshot({
            path: path.join(screenshotDir, `all-steps-${timestamp}-step1.png`),
            fullPage: true,
        });
        results.screenshots.push(`all-steps-${timestamp}-step1.png`);

        // ====================
        // STEP 1: Kategori
        // ====================
        console.log('\n' + '='.repeat(60));
        console.log('📋 STEP 1: KATEGORİ');
        console.log('='.repeat(60));

        const step1Check = await page.evaluate(() => {
            return {
                anaKategori: !!document.getElementById('ana_kategori_id'),
                altKategori: !!document.getElementById('alt_kategori_id'),
                yayinTipi: !!document.getElementById('yayin_tipi_id'),
            };
        });
        results.steps.step1 = step1Check;
        console.log(`   Ana Kategori: ${step1Check.anaKategori ? '✅' : '❌'}`);
        console.log(`   Alt Kategori: ${step1Check.altKategori ? '✅' : '❌'}`);
        console.log(`   Yayın Tipi: ${step1Check.yayinTipi ? '✅' : '❌'}`);

        // Select Arsa category
        await page
            .waitForSelector('#ana_kategori_id option[value]:not([value=""])', { timeout: 5000 })
            .catch(() => {});
        await page.select('#ana_kategori_id', '3'); // Arsa & Arazi
        await delay(1500);

        // Select alt kategori
        await page.evaluate(() => {
            const altSel = document.getElementById('alt_kategori_id');
            if (altSel && altSel.options.length > 1) {
                altSel.value = altSel.options[1].value;
                altSel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await delay(1000);

        // Select yayin tipi
        await page.evaluate(() => {
            const yayinSel = document.getElementById('yayin_tipi_id');
            if (yayinSel && yayinSel.options.length > 1) {
                yayinSel.value = yayinSel.options[1].value;
                yayinSel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await delay(500);

        // Go to Step 2
        await page.evaluate(() => {
            const btns = document.querySelectorAll('button');
            const nextBtn = Array.from(btns).find((b) => b.textContent.includes('İleri'));
            if (nextBtn) nextBtn.click();
        });
        await delay(2000);

        // ====================
        // STEP 2: Bilgiler
        // ====================
        console.log('\n' + '='.repeat(60));
        console.log('📋 STEP 2: BİLGİLER');
        console.log('='.repeat(60));

        await page.screenshot({
            path: path.join(screenshotDir, `all-steps-${timestamp}-step2.png`),
            fullPage: true,
        });
        results.screenshots.push(`all-steps-${timestamp}-step2.png`);

        const step2Check = await page.evaluate(() => {
            return {
                baslik: !!document.getElementById('baslik'),
                fiyatDisplay: !!document.getElementById('fiyat_display'),
                alanM2: !!document.getElementById('alan_m2'),
                aciklama: !!document.getElementById('aciklama'),
                featuresRoot: !!document.getElementById('features-dynamic-root'),
                adaNo: !!document.querySelector('input[name="ada_no"]'),
                parselNo: !!document.querySelector('input[name="parsel_no"]'),
            };
        });
        results.steps.step2 = step2Check;
        console.log(`   Başlık: ${step2Check.baslik ? '✅' : '❌'}`);
        console.log(`   Fiyat: ${step2Check.fiyatDisplay ? '✅' : '❌'}`);
        console.log(`   Alan m²: ${step2Check.alanM2 ? '✅' : '❌'}`);
        console.log(`   Açıklama: ${step2Check.aciklama ? '✅' : '❌'}`);
        console.log(`   Features: ${step2Check.featuresRoot ? '✅' : '❌'}`);
        console.log(`   Ada No: ${step2Check.adaNo ? '✅' : '❌'}`);
        console.log(`   Parsel No: ${step2Check.parselNo ? '✅' : '❌'}`);

        // Fill required fields for Step 2
        const baslikInput = await page.$('#baslik');
        if (baslikInput) {
            await baslikInput.type('Test İlan Başlığı - Deneme Arsası');
        }

        const fiyatInput = await page.$('#fiyat_display');
        if (fiyatInput) {
            await fiyatInput.type('1500000');
        }

        const alanInput = await page.$('#alan_m2');
        if (alanInput) {
            await alanInput.type('500');
        }
        await delay(500);

        // Go to Step 3
        await page.evaluate(() => {
            const btns = document.querySelectorAll('button');
            const nextBtn = Array.from(btns).find((b) => b.textContent.includes('İleri'));
            if (nextBtn) nextBtn.click();
        });
        await delay(2000);

        // ====================
        // STEP 3: Fotoğraf
        // ====================
        console.log('\n' + '='.repeat(60));
        console.log('📋 STEP 3: FOTOĞRAF VE VİDEO');
        console.log('='.repeat(60));

        await page.screenshot({
            path: path.join(screenshotDir, `all-steps-${timestamp}-step3.png`),
            fullPage: true,
        });
        results.screenshots.push(`all-steps-${timestamp}-step3.png`);

        const step3Check = await page.evaluate(() => {
            return {
                fotografInput: !!document.getElementById('fotograflar'),
                dropZone: !!document.querySelector('[class*="border-dashed"]'),
                videoUrl:
                    !!document.querySelector('input[name="video_url"]') ||
                    !!document.getElementById('video_url'),
                photoPreviewGrid: !!document.getElementById('photo-preview-grid'),
            };
        });
        results.steps.step3 = step3Check;
        console.log(`   Fotoğraf Input: ${step3Check.fotografInput ? '✅' : '❌'}`);
        console.log(`   Drop Zone: ${step3Check.dropZone ? '✅' : '❌'}`);
        console.log(`   Photo Preview Grid: ${step3Check.photoPreviewGrid ? '✅' : '❌'}`);
        console.log(`   Video URL: ${step3Check.videoUrl ? '✅' : '❌'}`);

        // Go to Step 4
        await page.evaluate(() => {
            const btns = document.querySelectorAll('button');
            const nextBtn = Array.from(btns).find((b) => b.textContent.includes('İleri'));
            if (nextBtn) nextBtn.click();
        });
        await delay(2000);

        // ====================
        // STEP 4: Adres
        // ====================
        console.log('\n' + '='.repeat(60));
        console.log('📋 STEP 4: ADRES');
        console.log('='.repeat(60));

        await page.screenshot({
            path: path.join(screenshotDir, `all-steps-${timestamp}-step4.png`),
            fullPage: true,
        });
        results.screenshots.push(`all-steps-${timestamp}-step4.png`);

        const step4Check = await page.evaluate(() => {
            return {
                ilSelect: !!document.getElementById('il_id'),
                ilceSelect: !!document.getElementById('ilce_id'),
                mahalleSelect: !!document.getElementById('mahalle_id'),
                mapContainer:
                    !!document.getElementById('map-step4') ||
                    !!document.querySelector('[id*="map"]'),
                latInput:
                    !!document.querySelector('input[name="lat"]') ||
                    !!document.getElementById('lat'),
                lngInput:
                    !!document.querySelector('input[name="lng"]') ||
                    !!document.getElementById('lng'),
                adresInput:
                    !!document.querySelector('input[name="adres"]') ||
                    !!document.getElementById('adres'),
            };
        });
        results.steps.step4 = step4Check;
        console.log(`   İl Select: ${step4Check.ilSelect ? '✅' : '❌'}`);
        console.log(`   İlçe Select: ${step4Check.ilceSelect ? '✅' : '❌'}`);
        console.log(`   Mahalle Select: ${step4Check.mahalleSelect ? '✅' : '❌'}`);
        console.log(`   Harita: ${step4Check.mapContainer ? '✅' : '❌'}`);
        console.log(`   Lat/Lng: ${step4Check.latInput && step4Check.lngInput ? '✅' : '❌'}`);
        console.log(`   Adres: ${step4Check.adresInput ? '✅' : '❌'}`);

        // Go to Step 5
        await page.evaluate(() => {
            const btns = document.querySelectorAll('button');
            const nextBtn = Array.from(btns).find((b) => b.textContent.includes('İleri'));
            if (nextBtn) nextBtn.click();
        });
        await delay(2000);

        // ====================
        // STEP 5: Önizleme
        // ====================
        console.log('\n' + '='.repeat(60));
        console.log('📋 STEP 5: ÖNİZLEME VE YAYIN');
        console.log('='.repeat(60));

        await page.screenshot({
            path: path.join(screenshotDir, `all-steps-${timestamp}-step5.png`),
            fullPage: true,
        });
        results.screenshots.push(`all-steps-${timestamp}-step5.png`);

        const step5Check = await page.evaluate(() => {
            return {
                ilanSahibi: !!document.getElementById('ilan_sahibi_id'),
                danismanSelect: !!document.getElementById('danisman_id'),
                siteSelect: !!document.getElementById('site_id'),
                yayinDurumuRadios:
                    document.querySelectorAll('input[name="yayin_durumu"]').length > 0,
                submitBtn:
                    !!document.querySelector('button[type="submit"]') ||
                    !!Array.from(document.querySelectorAll('button')).find(
                        (b) => b.textContent.includes('Kaydet') || b.textContent.includes('Yayınla')
                    ),
            };
        });
        results.steps.step5 = step5Check;
        console.log(`   İlan Sahibi: ${step5Check.ilanSahibi ? '✅' : '❌'}`);
        console.log(`   Danışman: ${step5Check.danismanSelect ? '✅' : '❌'}`);
        console.log(`   Site/Apartman: ${step5Check.siteSelect ? '✅' : '❌'}`);
        console.log(`   Yayın Durumu: ${step5Check.yayinDurumuRadios ? '✅' : '❌'}`);
        console.log(`   Submit Butonu: ${step5Check.submitBtn ? '✅' : '❌'}`);

        // ====================
        // SUMMARY
        // ====================
        console.log('\n' + '='.repeat(60));
        console.log('📊 ÖZET');
        console.log('='.repeat(60));

        const allSteps = ['step1', 'step2', 'step3', 'step4', 'step5'];
        let totalFields = 0;
        let passedFields = 0;

        for (const step of allSteps) {
            const stepData = results.steps[step];
            if (stepData) {
                const fields = Object.values(stepData);
                totalFields += fields.length;
                passedFields += fields.filter((v) => v === true).length;
            }
        }

        console.log(`\n✅ Toplam: ${passedFields}/${totalFields} alan bulundu`);
        console.log(`📸 Screenshots: ${results.screenshots.length} adet`);

        if (results.errors.length > 0) {
            console.log(`❌ Hatalar: ${results.errors.length}`);
            results.errors.forEach((e) => console.log(`   - ${e}`));
        }
    } catch (err) {
        results.errors.push(`Genel hata: ${err.message}`);
        console.log(`\n❌ Test hatası: ${err.message}`);
        await page.screenshot({
            path: path.join(screenshotDir, `all-steps-${timestamp}-error.png`),
            fullPage: true,
        });
    }

    await browser.close();

    // Save results
    fs.writeFileSync(
        path.join(screenshotDir, `all-steps-results-${timestamp}.json`),
        JSON.stringify(results, null, 2)
    );
    console.log(`\n📁 Sonuçlar: test-screenshots/all-steps-results-${timestamp}.json`);
    console.log(`📸 Screenshots: test-screenshots/all-steps-${timestamp}-*.png`);
}

testAllSteps().catch(console.error);
