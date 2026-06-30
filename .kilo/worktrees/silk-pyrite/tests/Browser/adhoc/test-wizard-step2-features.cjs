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

async function testStep2Features() {
    console.log('🚀 Step 2 Özellik Testi Başlıyor...\n');
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

    // Enable console log capture
    page.on('console', (msg) => {
        const text = msg.text();
        if (text.includes('[WIZARD]') || text.includes('Features') || text.includes('Error')) {
            console.log(`   [CONSOLE] ${text}`);
        }
    });

    const results = {
        timestamp,
        step2Features: {},
        errors: [],
        screenshots: [],
        consoleMessages: [],
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

            // Click and wait for navigation
            await Promise.all([
                page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
                page.click('button[type="submit"]'),
            ]);

            console.log('   ✅ Giriş başarılı');
            console.log(`   📍 Login sonrası URL: ${page.url()}`);
        }

        // 2. Go to wizard
        console.log('\n📍 2. Wizard sayfasına gidiliyor...');
        await page.goto('http://127.0.0.1:8002/admin/ilanlar/create-wizard', {
            waitUntil: 'domcontentloaded',
            timeout: 60000,
        });
        await delay(3000); // Wait for page to fully load

        // Debug: Check if we're on wizard page or redirected
        const pageUrl = page.url();
        console.log(`   📍 Current URL: ${pageUrl}`);

        // Check for Laravel error page
        const hasError = await page.evaluate(() => {
            return (
                document.body.innerHTML.includes('Whoops') ||
                document.body.innerHTML.includes('Exception') ||
                document.body.innerHTML.includes('Error')
            );
        });
        if (hasError) {
            console.log('   ⚠️ Laravel hata sayfası tespit edildi!');
            // Save HTML for debugging
            const html = await page.content();
            const fs = require('fs');
            fs.writeFileSync(path.join(screenshotDir, 'wizard-error.html'), html);
        }

        // Screenshot: Step 1 Initial
        await page.screenshot({
            path: path.join(screenshotDir, `step2-test-${timestamp}-01-step1.png`),
            fullPage: true,
        });
        results.screenshots.push(`step2-test-${timestamp}-01-step1.png`);
        console.log('   📸 Step 1 screenshot alındı');

        // 3. Select Arsa category
        console.log('\n📍 3. Kategori seçiliyor...');

        // Wait for select element to be populated
        await page
            .waitForSelector('#ana_kategori_id option[value]:not([value=""])', { timeout: 10000 })
            .catch(() => {
                console.log("   ⚠️ Kategori option'ları yüklenmedi, devam ediliyor...");
            });

        // First, list all available categories
        const categories = await page.evaluate(() => {
            const select = document.getElementById('ana_kategori_id');
            if (!select) return [];
            return Array.from(select.options).map((o) => ({
                value: o.value,
                text: o.text,
                slug: o.getAttribute('data-slug'),
            }));
        });
        console.log(
            '   📋 Mevcut kategoriler:',
            categories
                .filter((c) => c.value)
                .map((c) => `${c.text} (${c.slug})`)
                .join(', ')
        );

        // Select first available category with slug (skip empty option)
        const targetCat =
            categories.find((c) => c.slug === 'arsa-arazi') ||
            categories.find((c) => c.value && c.value !== '');
        if (targetCat) {
            await page.select('#ana_kategori_id', targetCat.value);
            console.log(`   ✅ Ana kategori: ${targetCat.text} (${targetCat.slug})`);
            await delay(1500); // Wait for alt_kategori to load via AJAX

            // Select first alt kategori
            const altKatSelected = await page.evaluate(() => {
                const altSel = document.getElementById('alt_kategori_id');
                if (altSel && altSel.options.length > 1) {
                    altSel.value = altSel.options[1].value;
                    altSel.dispatchEvent(new Event('change', { bubbles: true }));
                    return {
                        text: altSel.options[1].text,
                        slug: altSel.options[1].getAttribute('data-slug'),
                    };
                }
                return null;
            });
            if (altKatSelected) {
                console.log(`   ✅ Alt kategori: ${altKatSelected.text} (${altKatSelected.slug})`);
            } else {
                console.log('   ⚠️ Alt kategori bulunamadı - loading devam ediyor olabilir');
            }
            await delay(1000);
        } else {
            console.log('   ❌ Hiç kategori bulunamadı');
            results.errors.push('Kategoriler yüklenemedi');
        }

        // Select Satılık
        console.log('\n📍 4. Yayın tipi seçiliyor...');
        await page.evaluate(() => {
            const sel = document.getElementById('yayin_tipi_id');
            if (sel && sel.options.length > 1) {
                sel.value = sel.options[1].value;
                sel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await delay(300);

        // Go to Step 2
        console.log("\n📍 5. Step 2'ye geçiliyor...");
        await page.evaluate(() => {
            const btns = document.querySelectorAll('button');
            const nextBtn = Array.from(btns).find((b) => b.textContent.includes('İleri'));
            if (nextBtn) nextBtn.click();
        });
        await delay(2000); // Wait for features to load

        // Screenshot: Step 2 Full
        await page.screenshot({
            path: path.join(screenshotDir, `step2-test-${timestamp}-02-step2-full.png`),
            fullPage: true,
        });
        results.screenshots.push(`step2-test-${timestamp}-02-step2-full.png`);
        console.log('   📸 Step 2 full screenshot alındı');

        // 6. Check all Step 2 features
        console.log('\n' + '='.repeat(60));
        console.log('📋 STEP 2 ÖZELLİK KONTROLÜ');
        console.log('='.repeat(60));

        const step2Check = await page.evaluate(() => {
            const check = (selector, name) => {
                const el = document.querySelector(selector);
                return {
                    exists: !!el,
                    visible: el ? el.offsetParent !== null : false,
                    value: el ? el.value || el.textContent || '' : '',
                };
            };

            return {
                // 1. İlan Başlığı
                baslik: check('#baslik', 'Başlık'),
                aiTitleBtn: check('button:has(svg)', 'AI Başlık Butonu'),
                seoScore: check('[x-text*="seoScore"]', 'SEO Skoru'),

                // 2. Fiyat
                fiyatDisplay: check('#fiyat_display', 'Fiyat Display'),
                fiyatHidden: check('#fiyat', 'Fiyat Hidden'),
                paraBirimi: check('#para_birimi', 'Para Birimi'),
                priceTextValue: check('#price_text_value', 'Yazıyla Fiyat'),

                // 3. Alan m²
                alanM2: check('#alan_m2', 'Alan m²'),
                m2Hesaplama: check('[x-show*="m2Fiyati"]', 'm² Hesaplama'),

                // 4. Arsa Detayları
                adaNo: check('input[name="ada_no"]', 'Ada No'),
                parselNo: check('input[name="parsel_no"]', 'Parsel No'),
                kaks: check('input[name="kaks"]', 'KAKS'),
                gabari: check('input[name="gabari"]', 'Gabari'),

                // 5. Features Dynamic
                featuresRoot: check('#features-dynamic-root', 'Features Root'),
                featuresContainer: check('#features-container', 'Features Container'),
                featuresContent: check('#features-content', 'Features Content'),
                featuresEmptyState: check('#features-empty-state', 'Features Empty State'),

                // 6. Açıklama
                aciklama: check('#aciklama', 'Açıklama'),
                aiDescBtn: {
                    exists: !!document.querySelector('button[onclick*="generateDescription"]'),
                    visible: true,
                },

                // 7. CRM
                ilanSahibi: check('#ilan_sahibi_id', 'İlan Sahibi'),
                crmSection: {
                    exists: !!document.querySelector('h3')?.textContent?.includes('CRM'),
                    visible: true,
                },

                // 8. Gizli Not
                gizliNot: check('#gizli_not', 'Gizli Not'),

                // 9. Portal IDs
                sahibindenId: check('#sahibinden_id', 'Sahibinden ID'),
                emlakjetId: check('#emlakjet_id', 'Emlakjet ID'),
                hepsiemlakId: check('#hepsiemlak_id', 'Hepsiemlak ID'),
            };
        });

        results.step2Features = step2Check;

        // Print results
        const featureGroups = [
            { name: '📝 İlan Başlığı', fields: ['baslik', 'seoScore'] },
            { name: '💰 Fiyat Alanı', fields: ['fiyatDisplay', 'priceTextValue', 'paraBirimi'] },
            { name: '📐 Alan & Hesaplama', fields: ['alanM2', 'm2Hesaplama'] },
            { name: '🏗️ Arsa Detayları', fields: ['adaNo', 'parselNo', 'kaks', 'gabari'] },
            {
                name: '✨ Dinamik Özellikler',
                fields: ['featuresRoot', 'featuresContainer', 'featuresContent'],
            },
            { name: '📄 Açıklama', fields: ['aciklama'] },
            { name: '👥 CRM', fields: ['ilanSahibi'] },
            { name: '🔒 Gizli Not', fields: ['gizliNot'] },
            {
                name: '🌐 Portal Numaraları',
                fields: ['sahibindenId', 'emlakjetId', 'hepsiemlakId'],
            },
        ];

        let missingFeatures = [];

        for (const cat of featureGroups) {
            console.log(`\n${cat.name}:`);
            for (const field of cat.fields) {
                const info = step2Check[field];
                const checkIcon = info?.exists ? '✅' : '❌';
                const visibility = info?.visible ? '' : ' (hidden)';
                console.log(`   ${checkIcon} ${field}${visibility}`);

                if (!info?.exists) {
                    missingFeatures.push(`${cat.name} > ${field}`);
                }
            }
        }

        // 7. Test Yazıyla Fiyat
        console.log('\n' + '='.repeat(60));
        console.log('🧪 FONKSİYONEL TESTLER');
        console.log('='.repeat(60));

        console.log('\n💰 Yazıyla Fiyat Testi...');
        const fiyatInput = await page.$('#fiyat_display');
        if (fiyatInput) {
            await fiyatInput.click({ clickCount: 3 });
            await fiyatInput.type('1500000');
            await delay(500);

            const priceText = await page.evaluate(() => {
                return document.getElementById('price_text_value')?.textContent || '';
            });
            console.log(`   Girilen: 1.500.000 → Yazıyla: "${priceText}"`);

            if (priceText.toLowerCase().includes('milyon')) {
                console.log('   ✅ Yazıyla fiyat çalışıyor');
            } else {
                console.log('   ❌ Yazıyla fiyat çalışmıyor');
                results.errors.push('Yazıyla fiyat çalışmıyor');
            }
        }

        // 8. Test m² Hesaplama
        console.log('\n📐 m² Birim Fiyatı Testi...');
        const alanInput = await page.$('#alan_m2');
        if (alanInput) {
            await alanInput.click({ clickCount: 3 });
            await alanInput.type('500');
            await delay(800);

            const m2Visible = await page.evaluate(() => {
                const el = document.querySelector('[x-show*="m2Fiyati"]');
                return el && el.offsetParent !== null;
            });

            if (m2Visible) {
                console.log('   ✅ m² hesaplama görünüyor');
                console.log('   📊 1.500.000 / 500 = 3.000 ₺/m² bekleniyor');
            } else {
                console.log('   ⚠️ m² hesaplama henüz görünmüyor');
            }
        }

        // Screenshot after tests
        await page.screenshot({
            path: path.join(screenshotDir, `step2-test-${timestamp}-03-after-tests.png`),
            fullPage: true,
        });
        results.screenshots.push(`step2-test-${timestamp}-03-after-tests.png`);

        // 9. Check Features API
        console.log('\n✨ Dinamik Özellikler Kontrolü...');
        const featuresState = await page.evaluate(() => {
            const empty = document.getElementById('features-empty-state');
            const content = document.getElementById('features-content');
            const loading = document.getElementById('features-loading');

            return {
                emptyVisible: empty && !empty.classList.contains('hidden'),
                contentVisible: content && !content.classList.contains('hidden'),
                loadingVisible: loading && !loading.classList.contains('hidden'),
                contentHTML: content ? content.innerHTML.substring(0, 200) : '',
            };
        });

        if (featuresState.emptyVisible) {
            console.log('   ⚠️ "Kategori Seçimi Gerekli" mesajı görünüyor');
            console.log('   → Kategori seçilmesine rağmen özellikler yüklenmemiş');
            results.errors.push('Features yüklenemedi - kategori eventi tetiklenmemiş olabilir');
        } else if (featuresState.contentVisible) {
            console.log('   ✅ Dinamik özellikler yüklendi');
        } else if (featuresState.loadingVisible) {
            console.log('   ⏳ Özellikler yükleniyor...');
        }

        // 10. Scroll down and take more screenshots
        await page.evaluate(() => window.scrollTo(0, 1000));
        await delay(300);
        await page.screenshot({
            path: path.join(screenshotDir, `step2-test-${timestamp}-04-scrolled.png`),
            fullPage: false,
        });
        results.screenshots.push(`step2-test-${timestamp}-04-scrolled.png`);

        // Check CRM section visibility
        await page.evaluate(() => window.scrollTo(0, 2000));
        await delay(300);
        await page.screenshot({
            path: path.join(screenshotDir, `step2-test-${timestamp}-05-crm-section.png`),
            fullPage: false,
        });
        results.screenshots.push(`step2-test-${timestamp}-05-crm-section.png`);

        // Summary
        console.log('\n' + '='.repeat(60));
        console.log('📊 ÖZET');
        console.log('='.repeat(60));
        console.log(`\n✅ Alınan Screenshots: ${results.screenshots.length}`);
        console.log(`❌ Bulunan Hatalar: ${results.errors.length}`);
        results.errors.forEach((e) => console.log(`   - ${e}`));
        console.log(`\n⚠️ Eksik Özellikler: ${missingFeatures.length}`);
        missingFeatures.forEach((f) => console.log(`   - ${f}`));
    } catch (err) {
        results.errors.push(`Genel hata: ${err.message}`);
        console.log(`\n❌ Test hatası: ${err.message}`);
        await page.screenshot({
            path: path.join(screenshotDir, `step2-test-${timestamp}-error.png`),
            fullPage: true,
        });
    }

    await browser.close();

    // Save results
    fs.writeFileSync(
        path.join(screenshotDir, `step2-test-results-${timestamp}.json`),
        JSON.stringify(results, null, 2)
    );
    console.log(`\n📁 Sonuçlar kaydedildi: test-screenshots/step2-test-results-${timestamp}.json`);
    console.log('📸 Screenshots: test-screenshots/step2-test-*.png');
}

testStep2Features().catch(console.error);
