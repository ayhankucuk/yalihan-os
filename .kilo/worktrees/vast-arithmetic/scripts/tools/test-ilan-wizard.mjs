import puppeteer from 'puppeteer';
import { mkdir } from 'fs/promises';
import { existsSync } from 'fs';
import path from 'path';

const BASE_URL = 'http://127.0.0.1:8002';
const SCREENSHOT_DIR = 'assets/screenshots/wizard-test';
const LOGIN_EMAIL = 'ayhankucuk@gmail.com';
const LOGIN_PASSWORD = 'admin123';

async function ensureDir(dir) {
    if (!existsSync(dir)) {
        await mkdir(dir, { recursive: true });
    }
}

async function takeScreenshot(page, name, description) {
    const filename = `${name}-${Date.now()}.png`;
    const filepath = path.join(SCREENSHOT_DIR, filename);

    await page.screenshot({
        path: filepath,
        fullPage: true,
    });

    console.log(`📸 ${description}: ${filepath}`);
    return filepath;
}

async function waitForElement(page, selector, timeout = 5000) {
    try {
        await page.waitForSelector(selector, { timeout });
        return true;
    } catch (e) {
        console.warn(`⚠️ Element bulunamadı: ${selector}`);
        return false;
    }
}

async function testWizard() {
    console.log('🚀 İlan Wizard Test Başlatılıyor...\n');

    await ensureDir(SCREENSHOT_DIR);

    const browser = await puppeteer.launch({
        headless: false,
        args: ['--no-sandbox', '--disable-setuid-sandbox'],
        defaultViewport: { width: 1920, height: 1080 },
        executablePath:
            process.platform === 'darwin'
                ? '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome'
                : undefined,
    });

    const page = await browser.newPage();

    try {
        const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

        console.log('1️⃣ Login sayfasına gidiliyor...');
        await page.goto(`${BASE_URL}/login`, { waitUntil: 'networkidle2' });
        await delay(2000);
        await takeScreenshot(page, '01-login', 'Login Sayfası');

        console.log('2️⃣ Login yapılıyor...');
        await page.waitForSelector('#email', { timeout: 5000 });
        await page.type('#email', LOGIN_EMAIL);
        await page.type('#password', LOGIN_PASSWORD);

        const submitButton =
            (await page.$('button[type="submit"]')) || (await page.$('form button'));
        if (submitButton) {
            await submitButton.click();
            await page
                .waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 })
                .catch(() => {
                    console.log('Navigation timeout, sayfa yüklendi olabilir');
                });
            await delay(2000);
            await takeScreenshot(page, '02-after-login', 'Login Sonrası');
        } else {
            console.warn('Submit butonu bulunamadı');
            await takeScreenshot(page, '02-login-form', 'Login Form');
        }

        console.log('3️⃣ İlan Wizard sayfasına gidiliyor...');
        await page.goto(`${BASE_URL}/admin/ilanlar/create-wizard`, { waitUntil: 'networkidle2' });
        await delay(2000);
        await takeScreenshot(page, '03-wizard-initial', 'Wizard İlk Yükleme');

        console.log('4️⃣ Step 1 - Temel Bilgiler kontrol ediliyor...');
        const step1Visible = await waitForElement(page, '#ana_kategori_id', 3000);
        if (step1Visible) {
            await takeScreenshot(page, '04-step1-basic-info', 'Step 1: Temel Bilgiler');

            console.log('5️⃣ Kategori seçimi test ediliyor...');
            await page.select(
                '#ana_kategori_id',
                await page.evaluate(() => {
                    const select = document.getElementById('ana_kategori_id');
                    if (select && select.options.length > 1) {
                        return select.options[1].value;
                    }
                    return '';
                })
            );
            await delay(1000);
            await takeScreenshot(page, '05-kategori-secildi', 'Ana Kategori Seçildi');
        }

        console.log('6️⃣ Konum Bilgileri test ediliyor...');
        const ilSelectVisible = await waitForElement(page, '#il_id', 3000);
        if (ilSelectVisible) {
            await takeScreenshot(page, '06-location-section', 'Konum Bilgileri Bölümü');

            console.log('7️⃣ İl seçimi test ediliyor...');
            const ilOptions = await page.evaluate(() => {
                const select = document.getElementById('il_id');
                if (!select) return [];
                return Array.from(select.options)
                    .filter((opt) => opt.value)
                    .slice(0, 5)
                    .map((opt) => ({ value: opt.value, text: opt.text }));
            });

            if (ilOptions.length > 0) {
                console.log(`   İl seçenekleri bulundu: ${ilOptions.length}`);
                await page.select('#il_id', ilOptions[0].value);
                await delay(2000);
                await takeScreenshot(page, '07-il-secildi', `İl Seçildi: ${ilOptions[0].text}`);

                console.log('8️⃣ İlçe dropdown kontrol ediliyor...');
                const ilceSelect = await page.$('#ilce_id');
                if (ilceSelect) {
                    const ilceDisabled = await page.evaluate((el) => el.disabled, ilceSelect);
                    const ilceOptions = await page.evaluate(() => {
                        const select = document.getElementById('ilce_id');
                        if (!select) return [];
                        return Array.from(select.options).map((opt) => opt.text);
                    });

                    console.log(
                        `   İlçe dropdown durumu: ${ilceDisabled ? 'disabled' : 'enabled'}`
                    );
                    console.log(`   İlçe seçenekleri: ${ilceOptions.length}`);

                    if (!ilceDisabled && ilceOptions.length > 1) {
                        await page.select(
                            '#ilce_id',
                            await page.evaluate(() => {
                                const select = document.getElementById('ilce_id');
                                if (select && select.options.length > 1) {
                                    return select.options[1].value;
                                }
                                return '';
                            })
                        );
                        await delay(2000);
                        await takeScreenshot(page, '08-ilce-secildi', 'İlçe Seçildi');

                        console.log('9️⃣ Mahalle dropdown kontrol ediliyor...');
                        const mahalleSelect = await page.$('#mahalle_id');
                        if (mahalleSelect) {
                            const mahalleDisabled = await page.evaluate(
                                (el) => el.disabled,
                                mahalleSelect
                            );
                            const mahalleOptions = await page.evaluate(() => {
                                const select = document.getElementById('mahalle_id');
                                if (!select) return [];
                                return Array.from(select.options).map((opt) => opt.text);
                            });

                            console.log(
                                `   Mahalle dropdown durumu: ${mahalleDisabled ? 'disabled' : 'enabled'}`
                            );
                            console.log(`   Mahalle seçenekleri: ${mahalleOptions.length}`);

                            if (!mahalleDisabled && mahalleOptions.length > 1) {
                                await takeScreenshot(
                                    page,
                                    '09-mahalle-yuklendi',
                                    'Mahalleler Yüklendi'
                                );
                            }
                        }
                    }
                }
            }
        }

        console.log('🔟 Harita bölümü kontrol ediliyor...');
        const mapContainer = await page.$('#wizard-map-container');
        if (mapContainer) {
            await page.evaluate(() => {
                window.scrollTo(0, document.getElementById('wizard-map-container').offsetTop - 100);
            });
            await delay(1000);
            await takeScreenshot(page, '10-map-section', 'Harita Bölümü');
        }

        console.log("1️⃣1️⃣ Step 2'ye geçiş test ediliyor...");
        const nextButton = await page.$('button:has-text("İleri")');
        if (nextButton) {
            const isDisabled = await page.evaluate((el) => el.disabled, nextButton);
            console.log(`   İleri butonu: ${isDisabled ? 'disabled' : 'enabled'}`);

            if (!isDisabled) {
                await nextButton.click();
                await delay(2000);
                await takeScreenshot(page, '11-step2-details', 'Step 2: Detaylar');
            }
        }

        console.log('1️⃣2️⃣ Progress bar kontrol ediliyor...');
        const progressBar = await page.$('.bg-blue-600.rounded-full');
        if (progressBar) {
            const progressWidth = await page.evaluate((el) => el.style.width, progressBar);
            console.log(`   Progress: ${progressWidth}`);
        }

        console.log('1️⃣3️⃣ Form validasyonu test ediliyor...');
        const requiredFields = await page.evaluate(() => {
            const fields = document.querySelectorAll('[required]');
            return Array.from(fields).map((f) => ({
                id: f.id,
                name: f.name,
                type: f.type || f.tagName,
            }));
        });
        console.log(`   Zorunlu alanlar: ${requiredFields.length}`);
        console.log(`   Alanlar: ${requiredFields.map((f) => f.id || f.name).join(', ')}`);

        console.log('1️⃣4️⃣ JavaScript hataları kontrol ediliyor...');
        const jsErrors = [];
        page.on('pageerror', (error) => {
            jsErrors.push(error.message);
        });
        await delay(1000);

        if (jsErrors.length > 0) {
            console.log(`   ⚠️ JavaScript hataları bulundu: ${jsErrors.length}`);
            jsErrors.forEach((err) => console.log(`      - ${err}`));
        } else {
            console.log('   ✅ JavaScript hatası yok');
        }

        console.log('\n✅ Test tamamlandı!');
        await takeScreenshot(page, '14-final-state', 'Final Durum');
    } catch (error) {
        console.error('❌ Test hatası:', error);
        await takeScreenshot(page, 'error-state', 'Hata Durumu');
    } finally {
        await browser.close();
    }
}

testWizard().catch(console.error);
