# Playwright E2E Test Suite - Yalıhan Emlak

Context7-compliant end-to-end testing altyapısı.

## 🚀 Kurulum (Tamamlandı)

```bash
# Playwright ve dependencies kuruldu
npm install -D @playwright/test @types/node
```

## ⚙️ Environment Configuration

### baseURL Authority & Resolution

**PRIMARY AUTHORITY:** `PLAYWRIGHT_BASE_URL`
- Browser testleri için explicit e2e authority
- Production/staging/local tüm ortamlarda bu kullanılmalı
- Yeni kurulumlar mutlaka `PLAYWRIGHT_BASE_URL` kullanmalı

**LEGACY FALLBACK:** `APP_URL`
- Yalnızca backward compatibility için
- **Browser test authority değildir**
- Kullanıldığında console'da warning görünür
- Yeni projeler için deprecated

**LOCAL FALLBACK:** `http://127.0.0.1:8000`
- Zero-config local dev için
- Kullanıldığında console'da warning görünür

**Resolution Precedence (deterministik):**
1. `PLAYWRIGHT_BASE_URL` (PRIMARY)
2. `APP_URL` (LEGACY FALLBACK — deprecated for e2e)
3. `http://127.0.0.1:8000` (LOCAL FALLBACK)

**ÖNEMLI:** Source selection her zaman console'da görünür olacak.

### Local Development Setup

```bash
# 1. Dev server başlat
php artisan serve --port=8000

# 2. Environment variable set et (önerilen)
export PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000

# 3. Testleri çalıştır
npm run test:browser
```

### CI/CD Setup

```bash
# CI environment'ta
export PLAYWRIGHT_BASE_URL=http://localhost:8000
npm run test:browser
```

### Troubleshooting

#### ERR_NAME_NOT_RESOLVED

**Sebep:** `PLAYWRIGHT_BASE_URL` set edilmemiş, `.env` içindeki `APP_URL` placeholder.

**Çözüm:**
```bash
export PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000
npm run test:browser
```

#### E2E_CONFIG_ERROR: baseURL placeholder içeriyor

**Sebep:** URL içinde `REAL_DOMAIN` veya `example.com` gibi placeholder var.

**Çözüm:**
```bash
# .env dosyasını kontrol et
grep APP_URL .env

# Geçerli URL set et
export PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000
```

#### E2E_CONFIG_ERROR: localhost için port gerekli

**Sebep:** `http://localhost` kullanılmış, port belirtilmemiş.

**Çözüm:**
```bash
export PLAYWRIGHT_BASE_URL=http://localhost:8000
```

#### ⚠️ Using APP_URL as fallback

**Sebep:** `PLAYWRIGHT_BASE_URL` set edilmemiş, `APP_URL` kullanılıyor.

**Çözüm:**
```bash
# PRIMARY authority kullan
export PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000
```

#### ⚠️ Using hardcoded fallback

**Sebep:** Ne `PLAYWRIGHT_BASE_URL` ne de `APP_URL` set edilmiş.

**Çözüm:**
```bash
# Explicit authority set et
export PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000
```


## 📁 Test Yapısı

```
tests/e2e/
├── helpers/
│   ├── auth.helper.ts       # Giriş/çıkış yardımcıları
│   └── wizard.helper.ts     # Wizard test utilities
├── arsa-wizard.spec.ts      # Arsa wizard E2E testleri
└── smoke.spec.ts            # Temel sayfa erişim testleri
```

## 🧪 Testleri Çalıştırma

### Tüm Testleri Çalıştır

```bash
npx playwright test
```

### Belirli Bir Test Dosyası

```bash
npx playwright test tests/e2e/arsa-wizard.spec.ts
```

### Smoke Tests (Hızlı Kontrol)

```bash
npx playwright test tests/e2e/smoke.spec.ts
```

### Headless Mode Kapalı (Browser Görünerek)

```bash
npx playwright test --headed
```

### UI Mode (Interactive)

```bash
npx playwright test --ui
```

### Debug Mode

```bash
npx playwright test --debug
```

### Belirli Bir Test Case

```bash
npx playwright test -g "Arsa wizard - Step 2"
```

## 📊 Test Raporları

### HTML Rapor Görüntüle

```bash
npx playwright show-report
```

Raporlar `playwright-report/` klasöründe oluşturulur.

## ✅ Mevcut Test Scenarios

### Smoke Tests (`smoke.spec.ts`)

- ✅ Ana sayfa yüklenir
- ✅ Login sayfası erişilebilir
- ✅ Admin login başarılı
- ✅ İlan listesi erişilebilir
- ✅ Wizard sayfası erişilebilir
- ✅ Dark mode toggle çalışır
- ✅ API health check

### Arsa Wizard Tests (`arsa-wizard.spec.ts`)

- ✅ Admin dashboard görünür
- ✅ Step 1: Kategori seçimi
- ✅ Step 2: Dynamic fields yüklenir
- ✅ Complete flow: İlan oluşturma
- ✅ API context validation

## 🎯 Context7 Compliance

Tüm testler Context7 kurallarına uyumlu:

- ✅ Turkish field name validations
- ✅ `aktiflik_durumu` kontrolü (not `status`)
- ✅ No forbidden fields (`status`, `active`, `order`)
- ✅ Dark mode variant checks

## 🔧 Test Yazma Örnekleri

### Basit Sayfa Testi

```typescript
test('sayfa yüklenir', async ({ page }) => {
    await page.goto('/admin/ilanlar');
    await expect(page).toHaveTitle(/İlanlar/);
});
```

### Helper Kullanımı

```typescript
import { AuthHelper } from './helpers/auth.helper';
import { WizardHelper } from './helpers/wizard.helper';

test('wizard testi', async ({ page }) => {
    const auth = new AuthHelper(page);
    const wizard = new WizardHelper(page);

    await auth.loginAsAdmin();
    await wizard.gotoWizard();
    await wizard.selectQuickCategory('Satılık Arsa');
    await wizard.goToNextStep();
});
```

### API Testi

```typescript
test('API test', async ({ request }) => {
    const response = await request.get('/api/v1/wizard/context', {
        params: { kategori_id: '14', yayin_tipi_id: '153' },
    });

    expect(response.ok()).toBeTruthy();
    const data = await response.json();
    expect(data.context.features).toBeDefined();
});
```

## 📸 Screenshots ve Video

Test başarısız olursa otomatik olarak:

- Screenshot alınır
- Video kaydedilir
- Trace dosyası oluşturulur

Bunlar `test-results/` klasöründe saklanır.

## 🚨 Troubleshooting

### "Cannot find module @playwright/test"

```bash
npm install -D @playwright/test @types/node
```

### "Browser not installed"

```bash
npx playwright install
```

### "Server not running"

```bash
# Ayrı terminalde
php artisan serve
# Sonra testleri çalıştır
npx playwright test
```

## 🎬 CI/CD Integration

GitHub Actions için:

```yaml
- name: Install Playwright
  run: npm ci && npx playwright install --with-deps

- name: Run tests
  run: npx playwright test

- name: Upload report
  uses: actions/upload-artifact@v3
  if: always()
  with:
      name: playwright-report
      path: playwright-report/
```

## 📝 Test Admin Credentials

```
Email: ayhankucuk@gmail.com
Password: admin123
```

(proje.md'den - development ortamı)

## 🔗 Faydalı Komutlar

```bash
# Codegen - Test kaydı al
npx playwright codegen http://127.0.0.1:8000

# Inspector
npx playwright test --debug

# Trace viewer
npx playwright show-trace trace.zip

# Test listesi
npx playwright test --list

# Specific browser
npx playwright test --project=chromium
npx playwright test --project=firefox
```

---

**Notlar:**

- Server'ın çalıştığından emin ol (`php artisan serve`)
- Database seed'lenmiş olmalı (Arsa features için)
- Dev environment'ta çalışıyor (production için ayrı config gerekir)
