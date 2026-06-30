import { chromium } from 'playwright';

(async () => {
    const browser = await chromium.launch({ headless: false });
    const page = await browser.newPage();

    // Tüm console mesajlarını topla
    const logs = [];
    const errors = [];

    page.on('console', (msg) => {
        const text = msg.text();
        if (msg.type() === 'error') {
            errors.push(text);
        }
        logs.push(`[${msg.type()}]: ${text}`);
    });

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
    await page.waitForTimeout(500);

    // Step 2
    console.log('🔄 Step 2 ye geçiliyor...');
    await page.click('button:has-text("İleri")');
    await page.waitForTimeout(2500);

    // İç form div'in stilleri
    const innerFormStyles = await page.evaluate(() => {
        const konutDiv = document.querySelector(
            'div[x-show="isKonutSatilik && !isYazlikKiralama"]'
        );
        const innerForm = konutDiv?.querySelector('[x-data*="konutSatilikStructuredDataForm"]');

        if (!innerForm) return { found: false };

        const computedStyle = getComputedStyle(innerForm);
        const inlineStyle = innerForm.getAttribute('style');

        // Alpine.js tarafından eklenen stiller
        const alpineInit = innerForm.hasAttribute('x-init');
        const alpineCloak = innerForm.hasAttribute('x-cloak');

        // Parent zincirini kontrol et
        let parent = innerForm.parentElement;
        const parentChain = [];
        while (parent && parent !== document.body) {
            const parentStyle = getComputedStyle(parent);
            parentChain.push({
                tag: parent.tagName,
                display: parentStyle.display,
                visibility: parentStyle.visibility,
                xShow: parent.getAttribute('x-show'),
                style: parent.getAttribute('style'),
            });
            parent = parent.parentElement;
        }

        return {
            found: true,
            display: computedStyle.display,
            visibility: computedStyle.visibility,
            opacity: computedStyle.opacity,
            inlineStyle: inlineStyle,
            alpineInit: alpineInit,
            alpineCloak: alpineCloak,
            height: computedStyle.height,
            overflow: computedStyle.overflow,
            parentChain: parentChain.slice(0, 5), // İlk 5 parent
        };
    });

    console.log('\n📊 INNER FORM STYLES:');
    console.log(JSON.stringify(innerFormStyles, null, 2));

    // Errors
    if (errors.length > 0) {
        console.log('\n❌ CONSOLE ERRORS:');
        errors.forEach((e) => console.log('  - ' + e.substring(0, 150)));
    }

    // konutSatilikStructuredDataForm() fonksiyonu tanımlı mı?
    const fnCheck = await page.evaluate(() => {
        return {
            konutSatilikStructuredDataForm: typeof window.konutSatilikStructuredDataForm,
            Alpine: typeof window.Alpine,
            AlpineVersion: window.Alpine?.version,
        };
    });

    console.log('\n📊 FUNCTION CHECK:');
    console.log(JSON.stringify(fnCheck, null, 2));

    await page.screenshot({ path: 'test-screenshots/step2-inner-form-debug.png' });
    console.log('\n📸 Screenshot: test-screenshots/step2-inner-form-debug.png');

    await page.waitForTimeout(15000);
    await browser.close();
})();
