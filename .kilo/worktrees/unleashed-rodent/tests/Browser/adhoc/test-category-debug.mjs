import { chromium } from 'playwright';

const BASE_URL = 'http://127.0.0.1:8002';
const LOGIN_EMAIL = 'ayhankucuk@gmail.com';
const LOGIN_PASS = 'admin123';

async function debugCategorySelection() {
    const browser = await chromium.launch({ headless: false }); // Visible browser
    const context = await browser.newContext();
    const page = await context.newPage();

    console.log('🔐 Giriş yapılıyor...');
    await page.goto(`${BASE_URL}/admin/login`);
    await page.fill('input[name="email"]', LOGIN_EMAIL);
    await page.fill('input[name="password"]', LOGIN_PASS);
    await page.click('button[type="submit"]');
    await page.waitForURL('**/admin/**');

    console.log('📝 Wizard açılıyor...');
    await page.goto(`${BASE_URL}/admin/ilanlar/create-wizard`);
    await page.waitForTimeout(2000);

    // Step 1 bileşenlerini kontrol et
    console.log('\n📋 STEP 1 SELECT ANALİZİ:');
    console.log('─'.repeat(50));

    // Ana Kategori Select
    const anaKategoriSelect = await page.locator('select[x-model="selectedCategoryId"]');
    const anaKategoriExists = (await anaKategoriSelect.count()) > 0;
    console.log(`Ana Kategori Select: ${anaKategoriExists ? '✅ Var' : '❌ Yok'}`);

    if (anaKategoriExists) {
        const options = await anaKategoriSelect.locator('option').allTextContents();
        console.log(`  Options: ${options.join(', ')}`);
    }

    // Alt Kategori Select
    const altKategoriSelect = await page.locator('select[x-model="selectedSubCategoryId"]');
    const altKategoriExists = (await altKategoriSelect.count()) > 0;
    console.log(`Alt Kategori Select: ${altKategoriExists ? '✅ Var' : '❌ Yok'}`);

    // Yayın Tipi Selecti
    const yayinTipiSelect = await page.locator('select[x-model="selectedPublicationType"]');
    const yayinTipiExists = (await yayinTipiSelect.count()) > 0;
    console.log(`Yayın Tipi Select: ${yayinTipiExists ? '✅ Var' : '❌ Yok'}`);

    // Radio buttons kontrol et (belki radio buton)
    const radioButtons = await page.locator('input[type="radio"]').count();
    console.log(`Radio Button sayısı: ${radioButtons}`);

    // Tüm selectleri listele
    console.log('\n📊 TÜM SELECTLER:');
    const allSelects = await page.locator('select').all();
    for (let i = 0; i < allSelects.length; i++) {
        const sel = allSelects[i];
        const id = (await sel.getAttribute('id')) || '(no id)';
        const xModel = (await sel.getAttribute('x-model')) || '(no x-model)';
        const name = (await sel.getAttribute('name')) || '(no name)';
        const isVisible = await sel.isVisible();
        console.log(`  [${i}] id="${id}" x-model="${xModel}" name="${name}" visible=${isVisible}`);
    }

    // Step 1 Alpine data'sını kontrol et
    console.log('\n🔧 ALPINE DATA (Step 1):');
    const alpineData = await page.evaluate(() => {
        const step1 = document.querySelector('[x-data*="categorySelection"]');
        if (step1 && step1.__x) {
            return {
                selectedCategoryId: step1.__x.$data.selectedCategoryId,
                selectedSubCategoryId: step1.__x.$data.selectedSubCategoryId,
                selectedPublicationType: step1.__x.$data.selectedPublicationType,
                categories: step1.__x.$data.categories?.map((c) => ({ id: c.id, name: c.name })),
                subCategories: step1.__x.$data.subCategories?.map((c) => ({
                    id: c.id,
                    name: c.name,
                })),
            };
        }
        // Alpine 3 format
        const allElements = document.querySelectorAll('[x-data]');
        for (const el of allElements) {
            if (el._x_dataStack && el._x_dataStack[0]?.selectedCategoryId !== undefined) {
                const data = el._x_dataStack[0];
                return {
                    selectedCategoryId: data.selectedCategoryId,
                    selectedSubCategoryId: data.selectedSubCategoryId,
                    selectedPublicationType: data.selectedPublicationType,
                    categories: data.categories
                        ?.slice(0, 5)
                        .map((c) => ({ id: c.id, name: c.name })),
                    subCategories: data.subCategories
                        ?.slice(0, 5)
                        .map((c) => ({ id: c.id, name: c.name })),
                };
            }
        }
        return null;
    });

    console.log(JSON.stringify(alpineData, null, 2));

    // Konut seçmeyi dene
    console.log('\n🏠 KONUT SEÇİMİ DENENİYOR...');

    // Ana kategori selectini bul
    const kategoriSelectActual = await page.locator('select').first();
    await kategoriSelectActual.selectOption({ label: 'Konut' });
    await page.waitForTimeout(1000);

    // Alt kategorileri kontrol et
    console.log('Alt kategoriler yükleniyor...');
    const altKategoriler = await page.evaluate(() => {
        const selects = document.querySelectorAll('select');
        for (const sel of selects) {
            const options = Array.from(sel.options).map((o) => o.text);
            if (options.some((o) => o.includes('Daire') || o.includes('Villa'))) {
                return options;
            }
        }
        return [];
    });
    console.log(`Alt kategoriler: ${altKategoriler.join(', ')}`);

    // Daire seç
    const subCatSelect = await page.locator('select').nth(1);
    try {
        await subCatSelect.selectOption({ label: 'Daire' });
        console.log('✅ Daire seçildi');
    } catch (e) {
        console.log('❌ Daire seçilemedi:', e.message);
    }

    await page.waitForTimeout(500);

    // Yayın tipi seç
    const pubTypeSelect = await page.locator('select').nth(2);
    try {
        await pubTypeSelect.selectOption({ label: 'Satılık' });
        console.log('✅ Satılık seçildi');
    } catch (e) {
        console.log('❌ Satılık seçilemedi:', e.message);
    }

    await page.waitForTimeout(1000);

    // Son durumu kontrol et
    console.log('\n🔍 SEÇİM SONRASI ALPINE DATA:');
    const finalData = await page.evaluate(() => {
        const allElements = document.querySelectorAll('[x-data]');
        for (const el of allElements) {
            if (el._x_dataStack) {
                for (const stack of el._x_dataStack) {
                    if (stack.isKonutSatilik !== undefined) {
                        return {
                            isKonutSatilik: stack.isKonutSatilik,
                            isYazlikKiralama: stack.isYazlikKiralama,
                            selectedCategoryId: stack.selectedCategoryId,
                            selectedSubCategoryId: stack.selectedSubCategoryId,
                            selectedPublicationType: stack.selectedPublicationType,
                        };
                    }
                }
            }
        }
        return null;
    });
    console.log(JSON.stringify(finalData, null, 2));

    // İleri butonuna tıkla
    console.log("\n➡️ STEP 2'YE GEÇİŞ...");
    const nextButton = await page.locator('button:has-text("Devam"), button:has-text("İleri")');
    if ((await nextButton.count()) > 0) {
        await nextButton.first().click();
        await page.waitForTimeout(2000);

        // Step 2 formlarını kontrol et
        console.log('\n📝 STEP 2 FORM DURUMU:');
        const step2Status = await page.evaluate(() => {
            const konutForm = document.querySelector('.konut-satilik-form');
            const yazlikForm = document.querySelector('.yazlik-kiralama-form');
            const infoForm = document.querySelector('#ilan-info-form');

            return {
                konutFormExists: !!konutForm,
                konutFormVisible: konutForm
                    ? getComputedStyle(konutForm).display !== 'none'
                    : false,
                konutFormStyle: konutForm ? konutForm.getAttribute('style') : null,
                yazlikFormExists: !!yazlikForm,
                yazlikFormVisible: yazlikForm
                    ? getComputedStyle(yazlikForm).display !== 'none'
                    : false,
                infoFormExists: !!infoForm,
                infoFormVisible: infoForm ? getComputedStyle(infoForm).display !== 'none' : false,
            };
        });
        console.log(JSON.stringify(step2Status, null, 2));

        // H3 başlıklarını kontrol et
        const visibleH3 = await page.locator('h3:visible').allTextContents();
        console.log(`Görünen H3 başlıkları: ${visibleH3.join(', ')}`);
    }

    console.log('\n🔴 Tarayıcı 30 saniye açık kalacak (manuel kontrol için)...');
    await page.waitForTimeout(30000);

    await browser.close();
    console.log('\n✅ Test tamamlandı');
}

debugCategorySelection().catch(console.error);
