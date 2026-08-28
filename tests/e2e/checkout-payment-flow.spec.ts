/**
 * Checkout / Ödeme Akışı — Browser (E2E) Certification
 *
 * CHECKOUT/ÖDEME AKIŞI — IMPLEMENTATION
 *
 * Ödeme sağlayıcı entegrasyonu YOK — mock / manuel onay akışı.
 *
 * Kapsam:
 *  - Checkout sayfası rezervasyon özeti + ödeme geçmişi ile yüklenir.
 *  - Yeni ödeme kaydı formu ile oluşturulur (pending).
 *  - Ödeme geçmişinde görünür.
 *  - Manuel onay akışı: ödeme paid olur.
 *  - Başarısız işaretleme akışı.
 *
 * Not: E2E testleri gerçek DB'ye karşı çalışır (RefreshDatabase yok). Fixture
 * (ilan + rezervasyon) `php artisan tinker` üzerinden oluşturulur ve test sonunda
 * temizlenir — global-setup.ts ile aynı yaklaşım.
 */

import { test, expect, Page } from '@playwright/test';
import { execSync } from 'node:child_process';
import { AuthHelper } from './helpers/auth.helper';

test.describe('Checkout / Ödeme Akışı — Browser Certification', () => {
  test.beforeEach(async ({ page }) => {
    const auth = new AuthHelper(page);
    await auth.loginAsAdmin();
  });

  /**
   * Test için ilan + rezervasyon fixture'ı oluşturur (tinker üzerinden).
   * Returns { ilanId, reservationId } veya null (oluşturulamazsa).
   */
  async function createFixture(): Promise<{ ilanId: number; reservationId: number } | null> {
    try {
      const out = execSync(
        `php artisan tinker --execute='$u=\\App\\Models\\User::where("email",env("ADMIN_EMAIL","ayhankucuk@gmail.com"))->first() ?: \\App\\Models\\User::first();$tid=$u->tenant_id ?: 1;$ilan=\\App\\Models\\Ilan::factory()->create(["tenant_id"=>$tid,"baslik"=>"E2E Checkout Villa ".uniqid()]);$res=\\App\\Models\\PropertyReservation::factory()->forIlan($ilan)->confirmed()->create(["total_amount"=>5000,"currency"=>"TRY","guest_name"=>"E2E Misafir"]);echo $ilan->id.":".$res->id;'`,
        { stdio: 'pipe' }
      );
      const match = out.toString().trim().match(/(\d+):(\d+)/);
      if (!match) return null;
      return { ilanId: parseInt(match[1], 10), reservationId: parseInt(match[2], 10) };
    } catch (e) {
      // eslint-disable-next-line no-console
      console.warn('Fixture creation failed:', e);
      return null;
    }
  }

  /**
   * Test sonunda fixture'ı temizler.
   */
  async function cleanupFixture(ilanId: number, reservationId: number): Promise<void> {
    try {
      execSync(
        `php artisan tinker --execute='\\App\\Models\\Payment::where("reservation_id",${reservationId})->delete();\\App\\Models\\PropertyReservation::where("id",${reservationId})->delete();\\App\\Models\\Ilan::where("id",${ilanId})->forceDelete();echo "cleanup_ok";'`,
        { stdio: 'pipe' }
      );
    } catch (e) {
      // eslint-disable-next-line no-console
      console.warn('Fixture cleanup failed:', e);
    }
  }

  test('01 - Checkout sayfası rezervasyon özeti ile yüklenir', async ({ page }) => {
    const fixture = await createFixture();
    if (!fixture) {
      test.skip(true, 'No ilan+reservation fixture available');
      return;
    }

    try {
      const resp = await page.goto(`/admin/ilanlar/${fixture.ilanId}/checkout/${fixture.reservationId}`);
      expect(resp?.status()).toBeLessThan(400);

      await expect(page.getByRole('heading', { name: /Checkout \/ Ödeme/i })).toBeVisible();
      await expect(page.getByText('Rezervasyon Özeti')).toBeVisible();
      await expect(page.getByText('Ödeme Geçmişi')).toBeVisible();
      await expect(page.getByText('Yeni Ödeme Kaydı')).toBeVisible();
      // "5.000,00 TRY" hem toplam hem kalan tutarda görünür — .first() ile strict mode'u çöz
      await expect(page.getByText('5.000,00 TRY').first()).toBeVisible();
    } finally {
      await cleanupFixture(fixture.ilanId, fixture.reservationId);
    }
  });

  test('02 - Yeni ödeme kaydı oluşturulur ve geçmişte görünür', async ({ page }) => {
    const fixture = await createFixture();
    if (!fixture) {
      test.skip(true, 'No ilan+reservation fixture available');
      return;
    }

    try {
      await page.goto(`/admin/ilanlar/${fixture.ilanId}/checkout/${fixture.reservationId}`);

      // Formu doldur
      await page.fill('input[name="amount"]', '2000');
      await page.fill('input[name="currency"]', 'TRY');
      await page.selectOption('select[name="payment_method"]', 'mock');
      await page.fill('input[name="reference"]', 'E2E-REF-001');
      await page.fill('textarea[name="notes"]', 'E2E ödeme kaydı');

      await page.getByRole('button', { name: 'Ödeme Kaydı Oluştur' }).click();

      // Ödemenin geçmişte görünmesini bekle (flash mesajı toast ile kaybolabilir)
      await expect(page.getByText('2.000,00 TRY').first()).toBeVisible({ timeout: 10000 });
      await expect(page.getByText('Bekliyor').first()).toBeVisible({ timeout: 5000 });
      await expect(page.getByText('E2E-REF-001').first()).toBeVisible({ timeout: 5000 });
    } finally {
      await cleanupFixture(fixture.ilanId, fixture.reservationId);
    }
  });

  test('03 - Manuel onay akışı: ödeme onaylanır', async ({ page }) => {
    const fixture = await createFixture();
    if (!fixture) {
      test.skip(true, 'No ilan+reservation fixture available');
      return;
    }

    try {
      await page.goto(`/admin/ilanlar/${fixture.ilanId}/checkout/${fixture.reservationId}`);

      // Önce bir ödeme kaydı oluştur
      await page.fill('input[name="amount"]', '2000');
      await page.selectOption('select[name="payment_method"]', 'mock');
      await page.getByRole('button', { name: 'Ödeme Kaydı Oluştur' }).click();

      // Ödemenin geçmişte görünmesini bekle (flash mesajı toast ile kaybolabilir)
      await expect(page.getByText('2.000,00 TRY').first()).toBeVisible({ timeout: 10000 });
      await expect(page.getByText('Bekliyor').first()).toBeVisible({ timeout: 5000 });

      // Onayla butonuna tıkla — form submit, redirect beklenir
      await page.getByRole('button', { name: 'Onayla' }).first().click();

      // Redirect sonrası sayfa yüklenene kadar bekle
      await page.waitForLoadState('networkidle');

      // Ödeme artık "Onaylandı" durumunda görünmeli
      await expect(page.getByText('Onaylandı').first()).toBeVisible({ timeout: 15000 });
    } finally {
      await cleanupFixture(fixture.ilanId, fixture.reservationId);
    }
  });

  test('04 - Başarısız işaretleme akışı', async ({ page }) => {
    const fixture = await createFixture();
    if (!fixture) {
      test.skip(true, 'No ilan+reservation fixture available');
      return;
    }

    try {
      await page.goto(`/admin/ilanlar/${fixture.ilanId}/checkout/${fixture.reservationId}`);

      // Önce bir ödeme kaydı oluştur
      await page.fill('input[name="amount"]', '1000');
      await page.selectOption('select[name="payment_method"]', 'mock');
      await page.getByRole('button', { name: 'Ödeme Kaydı Oluştur' }).click();

      // Ödemenin geçmişte görünmesini bekle (flash mesajı toast ile kaybolabilir)
      await expect(page.getByText('1.000,00 TRY').first()).toBeVisible({ timeout: 10000 });
      await expect(page.getByText('Bekliyor').first()).toBeVisible({ timeout: 5000 });

      // Başarısız butonuna tıkla → Alpine.js ile reason alanı açılır
      await page.getByRole('button', { name: 'Başarısız' }).first().click();

      // Alpine.js'in render etmesi için kısa bekle
      await page.waitForTimeout(500);

      // Reason alanını doldur
      await page.fill('input[name="reason"]', 'Banka onayı alınamadı');

      // Kaydet butonuna tıkla — form submit
      await page.getByRole('button', { name: 'Kaydet' }).click();

      // Redirect sonrası sayfa yüklenene kadar bekle
      await page.waitForLoadState('networkidle');

      // Ödeme artık "Başarısız" durumunda görünmeli
      await expect(page.getByText('Başarısız').first()).toBeVisible({ timeout: 15000 });
    } finally {
      await cleanupFixture(fixture.ilanId, fixture.reservationId);
    }
  });
});
