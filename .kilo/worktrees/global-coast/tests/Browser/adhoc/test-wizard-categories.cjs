const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const screenshotDir = path.join(__dirname, 'test-screenshots');
if (!fs.existsSync(screenshotDir)) {
    fs.mkdirSync(screenshotDir, { recursive: true });
}

async function delay(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

async function testWizard() {
    console.log('🚀 Wizard testi başlıyor...\n');

    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
    });
    const page = await browser.newPage();
    await page.setViewport({ width: 1920, height: 1080 });

    const results = {
        errors: [],
        warnings: [],
        screenshots: [],
        categoryTests: [],
    };

    try {
        // 1. Login
        console.log('📍 Login sayfasına gidiliyor...');
        await page.goto('http://127.0.0.1:8002/login', {
            waitUntil: 'networkidle2',
            timeout: 30000,
        });

        const currentUrl = page.url();
        if (currentUrl.includes('login')) {
            await page.type('input[name="email"]', 'admin@yalihan.com');
            await page.type('input[name="password"]', 'password');
            await page.click('button[type="submit"]');
            await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 30000 });
            console.log('✅ Giriş başarılı\n');
        }

        // 2. Go to wizard
        console.log('📍 Wizard sayfasına gidiliyor...');
        await page.goto('http://127.0.0.1:8002/admin/ilanlar/create-wizard', {
            waitUntil: 'networkidle2',
            timeout: 30000,
        });

        // Check for Laravel errors
        const pageContent = await page.content();
        if (
            pageContent.includes('Whoops') ||
            (pageContent.includes('Error') && pageContent.includes('Stack trace'))
        ) {
            results.errors.push('Laravel hatası tespit edildi');
            await page.screenshot({
                path: path.join(screenshotDir, 'error-laravel.png'),
                fullPage: true,
            });
        }

        await page.screenshot({
            path: path.join(screenshotDir, '01-step1-initial.png'),
            fullPage: true,
        });
        results.screenshots.push('01-step1-initial.png');
        console.log('📸 Step 1 screenshot alındı');

        // 3. Get categories
        const categories = await page.evaluate(() => {
            const select = document.getElementById('ana_kategori_id');
            if (!select) return [];
            return Array.from(select.options)
                .filter((o) => o.value)
                .map((o) => ({
                    value: o.value,
                    text: o.text.trim(),
                    slug: o.getAttribute('data-slug'),
                }));
        });

        console.log(`\n📋 ${categories.length} kategori bulundu:\n`);
        categories.forEach((c, i) => console.log(`   ${i + 1}. ${c.text} (${c.slug})`));

        // 4. Test each category
        const categoriesToTest = categories.slice(0, 4); // İlk 4 kategori

        for (const category of categoriesToTest) {
            console.log(`\n🔄 Test: ${category.text}...`);
            const testResult = {
                category: category.text,
                slug: category.slug,
                errors: [],
                success: false,
            };

            try {
                // Select category
                await page.select('#ana_kategori_id', category.value);
                await delay(500);

                // Select yayin_tipi (Satılık)
                const yayinTipiExists = await page.$('#yayin_tipi_id');
                if (yayinTipiExists) {
                    const firstOption = await page.evaluate(() => {
                        const sel = document.getElementById('yayin_tipi_id');
                        const opt = sel?.options[1];
                        return opt?.value || '';
                    });
                    if (firstOption) {
                        await page.select('#yayin_tipi_id', firstOption);
                        await delay(300);
                    }
                }

                // Click next button
                const nextBtn = await page.$('button:has-text("İleri")');
                if (!nextBtn) {
                    // Try alternative selector
                    await page.evaluate(() => {
                        const btns = document.querySelectorAll('button');
                        const nextBtn = Array.from(btns).find((b) =>
                            b.textContent.includes('İleri')
                        );
                        if (nextBtn) nextBtn.click();
                    });
                } else {
                    await nextBtn.click();
                }
                await delay(1000);

                // Screenshot Step 2
                const screenshotName = `02-step2-${category.slug || category.value}.png`;
                await page.screenshot({
                    path: path.join(screenshotDir, screenshotName),
                    fullPage: true,
                });
                results.screenshots.push(screenshotName);
                console.log(`   📸 ${screenshotName} alındı`);

                // Check for Step 2 content
                const step2Content = await page.evaluate(() => {
                    // Check if step 2 is visible
                    const step2Div = document.querySelector('[x-show*="currentStep === 2"]');
                    if (!step2Div) return { visible: false };

                    // Check for alan_m2 field
                    const alanM2 = document.getElementById('alan_m2');
                    const fiyatDisplay = document.getElementById('fiyat_display');
                    const crmSection =
                        document.querySelector('[class*="CRM"]') ||
                        document.querySelector('h3:contains("CRM")');

                    return {
                        visible: true,
                        hasAlanM2: !!alanM2,
                        hasFiyat: !!fiyatDisplay,
                        hasCRM: !!crmSection,
                    };
                });

                console.log(`   📐 Alan m²: ${step2Content.hasAlanM2 ? '✅' : '❌'}`);
                console.log(`   💰 Fiyat: ${step2Content.hasFiyat ? '✅' : '❌'}`);

                if (!step2Content.hasAlanM2) {
                    testResult.errors.push('Alan m² alanı bulunamadı');
                }
                if (!step2Content.hasFiyat) {
                    testResult.errors.push('Fiyat alanı bulunamadı');
                }

                testResult.success = testResult.errors.length === 0;

                // Go back to step 1 for next test
                await page.goto('http://127.0.0.1:8002/admin/ilanlar/create-wizard', {
                    waitUntil: 'networkidle2',
                });
                await delay(500);
            } catch (err) {
                testResult.errors.push(err.message);
                console.log(`   ❌ Hata: ${err.message}`);
            }

            results.categoryTests.push(testResult);
        }

        // 5. Test Arsa category specifically
        console.log('\n🏗️ Arsa kategorisi detaylı testi...');
        const arsaCategory = categories.find(
            (c) => c.slug === 'arsa-arazi' || c.text.toLowerCase().includes('arsa')
        );

        if (arsaCategory) {
            await page.select('#ana_kategori_id', arsaCategory.value);
            await delay(500);

            // Select Satılık
            const yayinTipiExists = await page.$('#yayin_tipi_id');
            if (yayinTipiExists) {
                await page.evaluate(() => {
                    const sel = document.getElementById('yayin_tipi_id');
                    if (sel && sel.options[1]) sel.value = sel.options[1].value;
                    sel.dispatchEvent(new Event('change'));
                });
                await delay(300);
            }

            // Go to step 2
            await page.evaluate(() => {
                const btns = document.querySelectorAll('button');
                const nextBtn = Array.from(btns).find((b) => b.textContent.includes('İleri'));
                if (nextBtn) nextBtn.click();
            });
            await delay(1500);

            await page.screenshot({
                path: path.join(screenshotDir, '03-arsa-step2-full.png'),
                fullPage: true,
            });

            // Check Arsa-specific fields
            const arsaFields = await page.evaluate(() => {
                return {
                    adaNo: !!document.querySelector('input[name="ada_no"]'),
                    parselNo: !!document.querySelector('input[name="parsel_no"]'),
                    kaks: !!document.querySelector('input[name="kaks"]'),
                    gabari: !!document.querySelector('input[name="gabari"]'),
                    alanM2: !!document.getElementById('alan_m2'),
                    fiyat: !!document.getElementById('fiyat_display'),
                    gizliNot: !!document.getElementById('gizli_not'),
                    portalIds: {
                        sahibinden: !!document.getElementById('sahibinden_id'),
                        emlakjet: !!document.getElementById('emlakjet_id'),
                        hepsiemlak: !!document.getElementById('hepsiemlak_id'),
                    },
                };
            });

            console.log('\n   🏗️ Arsa Detay Alanları:');
            console.log(`      Ada No: ${arsaFields.adaNo ? '✅' : '❌'}`);
            console.log(`      Parsel No: ${arsaFields.parselNo ? '✅' : '❌'}`);
            console.log(`      KAKS: ${arsaFields.kaks ? '✅' : '❌'}`);
            console.log(`      Gabari: ${arsaFields.gabari ? '✅' : '❌'}`);
            console.log('\n   📐 Genel Alanlar:');
            console.log(`      Alan m²: ${arsaFields.alanM2 ? '✅' : '❌'}`);
            console.log(`      Fiyat: ${arsaFields.fiyat ? '✅' : '❌'}`);
            console.log(`      Gizli Not: ${arsaFields.gizliNot ? '✅' : '❌'}`);
            console.log('\n   🌐 Portal Numaraları:');
            console.log(`      Sahibinden: ${arsaFields.portalIds.sahibinden ? '✅' : '❌'}`);
            console.log(`      Emlakjet: ${arsaFields.portalIds.emlakjet ? '✅' : '❌'}`);
            console.log(`      Hepsiemlak: ${arsaFields.portalIds.hepsiemlak ? '✅' : '❌'}`);

            // Test m² calculation
            console.log('\n   🧮 m² Hesaplama Testi...');
            const fiyatInput = await page.$('#fiyat_display');
            const alanInput = await page.$('#alan_m2');

            if (fiyatInput && alanInput) {
                await fiyatInput.click({ clickCount: 3 });
                await fiyatInput.type('1000000');
                await delay(200);
                await alanInput.click({ clickCount: 3 });
                await alanInput.type('500');
                await delay(500);

                await page.screenshot({
                    path: path.join(screenshotDir, '04-arsa-m2-calculation.png'),
                    fullPage: true,
                });

                // Check if calculation is displayed
                const m2Price = await page.evaluate(() => {
                    const priceEl = document.querySelector('[x-text*="m2Fiyati"]');
                    return priceEl ? priceEl.textContent : null;
                });
                console.log(`      Hesaplanan m² fiyatı: ${m2Price || 'Bulunamadı'}`);
            }
        }

        // 6. Test all steps navigation
        console.log('\n📊 Adım navigasyonu testi...');
        await page.goto('http://127.0.0.1:8002/admin/ilanlar/create-wizard', {
            waitUntil: 'networkidle2',
        });

        const stepButtons = await page.evaluate(() => {
            const buttons = document.querySelectorAll('button');
            return Array.from(buttons)
                .filter((b) => /^\d+\./.test(b.textContent.trim()))
                .map((b) => b.textContent.trim());
        });
        console.log(`   Bulunan adım butonları: ${stepButtons.join(', ')}`);
    } catch (err) {
        results.errors.push(`Genel hata: ${err.message}`);
        console.log(`\n❌ Test hatası: ${err.message}`);
        await page.screenshot({
            path: path.join(screenshotDir, 'error-final.png'),
            fullPage: true,
        });
    }

    await browser.close();

    // Summary
    console.log('\n' + '='.repeat(50));
    console.log('📊 TEST SONUÇLARI');
    console.log('='.repeat(50));
    console.log(`Screenshots: ${results.screenshots.length}`);
    console.log(`Errors: ${results.errors.length}`);
    results.errors.forEach((e) => console.log(`  ❌ ${e}`));
    console.log(`\nCategory Tests:`);
    results.categoryTests.forEach((t) => {
        const status = t.success ? '✅' : '❌';
        console.log(
            `  ${status} ${t.category}: ${t.errors.length === 0 ? 'OK' : t.errors.join(', ')}`
        );
    });

    // Save results
    fs.writeFileSync(
        path.join(screenshotDir, 'test-results.json'),
        JSON.stringify(results, null, 2)
    );
    console.log('\n📁 Sonuçlar test-screenshots/test-results.json dosyasına kaydedildi');
}

testWizard().catch(console.error);
