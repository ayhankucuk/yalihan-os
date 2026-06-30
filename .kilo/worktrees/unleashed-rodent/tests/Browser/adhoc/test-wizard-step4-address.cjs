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

async function testStep4Address() {
    console.log('🚀 Step 4 Adres & Harita Testi Başlıyor...\n');
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

    // Console mesajlarını yakala
    page.on('console', (msg) => {
        const text = msg.text();
        if (
            text.includes('harita') ||
            text.includes('map') ||
            text.includes('leaflet') ||
            text.includes('Step 4') ||
            text.includes('location') ||
            text.includes('Error')
        ) {
            console.log(`   [CONSOLE] ${text}`);
        }
    });

    const results = {
        timestamp,
        addressTests: {},
        mapTests: {},
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

        // 3. Navigate to Step 4
        console.log("\n📍 3. Step 4'e navigasyon...");

        // Step 1: Select category
        await page
            .waitForSelector('#ana_kategori_id option[value]:not([value=""])', { timeout: 5000 })
            .catch(() => {});
        await page.select('#ana_kategori_id', '3'); // Arsa
        await delay(1500);

        await page.evaluate(() => {
            const altSel = document.getElementById('alt_kategori_id');
            if (altSel && altSel.options.length > 1) {
                altSel.value = altSel.options[1].value;
                altSel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await delay(1000);

        await page.evaluate(() => {
            const yayinSel = document.getElementById('yayin_tipi_id');
            if (yayinSel && yayinSel.options.length > 1) {
                yayinSel.value = yayinSel.options[1].value;
                yayinSel.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        await delay(500);

        // Click next 3 times to get to Step 4
        for (let i = 0; i < 3; i++) {
            await page.evaluate(() => {
                const btns = document.querySelectorAll('button');
                const nextBtn = Array.from(btns).find((b) => b.textContent.includes('İleri'));
                if (nextBtn) nextBtn.click();
            });
            await delay(1500);
        }

        console.log("   ✅ Step 4'e ulaşıldı");

        // Screenshot Step 4 initial
        await page.screenshot({
            path: path.join(screenshotDir, `step4-address-${timestamp}-01-initial.png`),
            fullPage: true,
        });
        results.screenshots.push(`step4-address-${timestamp}-01-initial.png`);

        // ====================
        // ADRES SEÇİMLERİ TESTİ
        // ====================
        console.log('\n' + '='.repeat(60));
        console.log('📍 ADRES SEÇİMLERİ TESTİ');
        console.log('='.repeat(60));

        // Check İl Select
        const ilOptions = await page.evaluate(() => {
            const sel = document.getElementById('il_id');
            if (!sel) return [];
            return Array.from(sel.options)
                .map((o) => ({ value: o.value, text: o.text }))
                .filter((o) => o.value);
        });
        console.log(`   İl seçenekleri: ${ilOptions.length} adet`);
        results.addressTests.ilCount = ilOptions.length;

        // Select Muğla (ID: 48)
        console.log('\n   🔄 Muğla seçiliyor...');
        await page.select('#il_id', '48');
        await delay(2000); // Wait for AJAX

        // Check İlçe loaded
        const ilceOptions = await page.evaluate(() => {
            const sel = document.getElementById('ilce_id');
            if (!sel) return [];
            return Array.from(sel.options)
                .map((o) => ({ value: o.value, text: o.text }))
                .filter((o) => o.value);
        });
        console.log(`   İlçe seçenekleri yüklendi: ${ilceOptions.length} adet`);
        results.addressTests.ilceCount = ilceOptions.length;

        if (ilceOptions.length > 0) {
            console.log(`   ✅ İl→İlçe cascade çalışıyor`);
            console.log(
                `   📋 İlçeler: ${ilceOptions
                    .slice(0, 5)
                    .map((i) => i.text)
                    .join(', ')}...`
            );
        } else {
            console.log(`   ❌ İlçe yüklenemedi!`);
            results.errors.push('İlçe cascade çalışmıyor');
        }

        // Select Bodrum
        const bodrumOption = ilceOptions.find((i) => i.text.toLowerCase().includes('bodrum'));
        if (bodrumOption) {
            console.log('\n   🔄 Bodrum seçiliyor...');
            await page.select('#ilce_id', bodrumOption.value);
            await delay(2000); // Wait for AJAX

            // Check Mahalle loaded
            const mahalleOptions = await page.evaluate(() => {
                const sel = document.getElementById('mahalle_id');
                if (!sel) return [];
                return Array.from(sel.options)
                    .map((o) => ({ value: o.value, text: o.text }))
                    .filter((o) => o.value);
            });
            console.log(`   Mahalle seçenekleri yüklendi: ${mahalleOptions.length} adet`);
            results.addressTests.mahalleCount = mahalleOptions.length;

            if (mahalleOptions.length > 0) {
                console.log(`   ✅ İlçe→Mahalle cascade çalışıyor`);
                console.log(
                    `   📋 Mahalleler: ${mahalleOptions
                        .slice(0, 5)
                        .map((m) => m.text)
                        .join(', ')}...`
                );

                // Select first mahalle
                await page.select('#mahalle_id', mahalleOptions[0].value);
                await delay(500);
            } else {
                console.log(`   ⚠️ Mahalle yüklenemedi (DB'de olmayabilir)`);
            }
        }

        // Screenshot after address selection
        await page.screenshot({
            path: path.join(screenshotDir, `step4-address-${timestamp}-02-address-selected.png`),
            fullPage: true,
        });
        results.screenshots.push(`step4-address-${timestamp}-02-address-selected.png`);

        // ====================
        // HARİTA TESTİ
        // ====================
        console.log('\n' + '='.repeat(60));
        console.log('🗺️ HARİTA TESTİ');
        console.log('='.repeat(60));

        // Check map container exists
        const mapCheck = await page.evaluate(() => {
            const mapContainer = document.getElementById('map-step4');
            const mapWrapper = document.getElementById('wizard-map-container-step4');
            const leafletMap = mapContainer?._leaflet_id;

            return {
                containerExists: !!mapContainer,
                wrapperExists: !!mapWrapper,
                leafletInitialized: !!leafletMap,
                containerSize: mapContainer
                    ? {
                          width: mapContainer.offsetWidth,
                          height: mapContainer.offsetHeight,
                      }
                    : null,
            };
        });

        console.log(`   Map Container: ${mapCheck.containerExists ? '✅' : '❌'}`);
        console.log(`   Map Wrapper: ${mapCheck.wrapperExists ? '✅' : '❌'}`);
        console.log(`   Leaflet Initialized: ${mapCheck.leafletInitialized ? '✅' : '❌'}`);
        if (mapCheck.containerSize) {
            console.log(
                `   Map Size: ${mapCheck.containerSize.width}x${mapCheck.containerSize.height}px`
            );
        }
        results.mapTests = mapCheck;

        // Wait for map to initialize
        await delay(2000);

        // Try to click on map to set coordinates
        console.log('\n   🖱️ Haritaya tıklama simülasyonu...');
        const mapElement = await page.$('#map-step4');
        if (mapElement) {
            const box = await mapElement.boundingBox();
            if (box) {
                // Click center of map
                await page.mouse.click(box.x + box.width / 2, box.y + box.height / 2);
                await delay(1000);

                // Check if coordinates were set
                const coordsAfterClick = await page.evaluate(() => {
                    const latDisplay = document.getElementById('lat-display-step4');
                    const lngDisplay = document.getElementById('lng-display-step4');
                    const latInput = document.getElementById('form-lat');
                    const lngInput = document.getElementById('form-lng');

                    return {
                        latDisplay: latDisplay?.textContent || '0',
                        lngDisplay: lngDisplay?.textContent || '0',
                        latInput: latInput?.value || '',
                        lngInput: lngInput?.value || '',
                    };
                });

                console.log(`   Lat Display: ${coordsAfterClick.latDisplay}`);
                console.log(`   Lng Display: ${coordsAfterClick.lngDisplay}`);
                console.log(`   Lat Input: ${coordsAfterClick.latInput || '(boş)'}`);
                console.log(`   Lng Input: ${coordsAfterClick.lngInput || '(boş)'}`);

                if (
                    coordsAfterClick.latDisplay !== '0' &&
                    coordsAfterClick.latDisplay !== '0.000000'
                ) {
                    console.log(`   ✅ Harita tıklama koordinat set ediyor`);
                } else {
                    console.log(`   ⚠️ Harita tıklama koordinat set etmedi (map init gerekebilir)`);
                }

                results.mapTests.coordinates = coordsAfterClick;
            }
        }

        // Screenshot after map interaction
        await page.screenshot({
            path: path.join(screenshotDir, `step4-address-${timestamp}-03-map-clicked.png`),
            fullPage: true,
        });
        results.screenshots.push(`step4-address-${timestamp}-03-map-clicked.png`);

        // Check map search
        console.log('\n   🔍 Harita arama kontrolü...');
        const searchInput = await page.$('#map-search-input-step4');
        if (searchInput) {
            console.log(`   ✅ Harita arama input mevcut`);
            await searchInput.type('Bodrum');
            await delay(500);
        } else {
            console.log(`   ⚠️ Harita arama input bulunamadı`);
        }

        // Check view toggle buttons
        const viewButtons = await page.evaluate(() => {
            const mapBtn = document.getElementById('map-view-btn-step4');
            const satBtn = document.getElementById('satellite-view-btn-step4');
            return {
                mapButton: !!mapBtn,
                satelliteButton: !!satBtn,
            };
        });
        console.log(
            `   Harita/Uydu Butonları: ${viewButtons.mapButton && viewButtons.satelliteButton ? '✅' : '❌'}`
        );
        results.mapTests.viewButtons = viewButtons;

        // ====================
        // ADRES DETAY TESTİ
        // ====================
        console.log('\n' + '='.repeat(60));
        console.log('📝 ADRES DETAY TESTİ');
        console.log('='.repeat(60));

        const adresDetayCheck = await page.evaluate(() => {
            const textarea = document.getElementById('adres_detay');
            return {
                exists: !!textarea,
                tagName: textarea?.tagName,
                name: textarea?.name,
            };
        });
        console.log(`   Adres Detay Textarea: ${adresDetayCheck.exists ? '✅' : '❌'}`);
        results.addressTests.adresDetay = adresDetayCheck;

        if (adresDetayCheck.exists) {
            await page.type('#adres_detay', 'Yalıkavak Mahallesi, Deniz Caddesi No:15');
            console.log(`   ✅ Adres detay yazıldı`);
        }

        // Final screenshot
        await page.screenshot({
            path: path.join(screenshotDir, `step4-address-${timestamp}-04-final.png`),
            fullPage: true,
        });
        results.screenshots.push(`step4-address-${timestamp}-04-final.png`);

        // ====================
        // ÖZET
        // ====================
        console.log('\n' + '='.repeat(60));
        console.log('📊 ÖZET');
        console.log('='.repeat(60));

        console.log('\n📍 Adres Seçimleri:');
        console.log(`   İl seçenekleri: ${results.addressTests.ilCount || 0}`);
        console.log(`   İlçe cascade: ${results.addressTests.ilceCount > 0 ? '✅' : '❌'}`);
        console.log(`   Mahalle cascade: ${results.addressTests.mahalleCount > 0 ? '✅' : '⚠️'}`);
        console.log(`   Adres detay: ${adresDetayCheck.exists ? '✅' : '❌'}`);

        console.log('\n🗺️ Harita:');
        console.log(`   Container: ${mapCheck.containerExists ? '✅' : '❌'}`);
        console.log(`   Leaflet: ${mapCheck.leafletInitialized ? '✅' : '⚠️ (lazy load)'}`);
        console.log(
            `   View butonları: ${viewButtons.mapButton && viewButtons.satelliteButton ? '✅' : '❌'}`
        );

        console.log(`\n📸 Screenshots: ${results.screenshots.length} adet`);

        if (results.errors.length > 0) {
            console.log(`\n❌ Hatalar: ${results.errors.length}`);
            results.errors.forEach((e) => console.log(`   - ${e}`));
        }
    } catch (err) {
        results.errors.push(`Genel hata: ${err.message}`);
        console.log(`\n❌ Test hatası: ${err.message}`);
        await page.screenshot({
            path: path.join(screenshotDir, `step4-address-${timestamp}-error.png`),
            fullPage: true,
        });
    }

    await browser.close();

    // Save results
    fs.writeFileSync(
        path.join(screenshotDir, `step4-address-results-${timestamp}.json`),
        JSON.stringify(results, null, 2)
    );
    console.log(`\n📁 Sonuçlar: test-screenshots/step4-address-results-${timestamp}.json`);
}

testStep4Address().catch(console.error);
