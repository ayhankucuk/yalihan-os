# Checkout / Ödeme Akışı — Production Doğrulama ve Yetkilendirme

**Document:** `docs/production/CHECKOUT_PAYMENT_VERIFICATION.md`
**Date:** 2026-08-28
**Status:** IMPLEMENTATION COMPLETE — Production Gate Checklist

---

## 1. Kapsam

Checkout / Ödeme Akışı (CHECKOUT/ÖDEME AKIŞI) — mock / manuel onay akışı.

**Ödeme sağlayıcı entegrasyonu YOK.** Bu akış yalnızca:
- Ödeme kaydı (payment record) oluşturur.
- Durum makinesi işletir: `pending → paid | failed`.
- Rezervasyon-finansal durum bağlantısını kurar (`property_reservations.finansal_durum`).
- Ledger çift kayıt (FinancialLedgerService) ile finansal iz bırakır.

Gerçek bir ödeme sağlayıcı (kart/EFT/havale doğrulaması) entegre edilmeden
**production'da gerçek para tahsilatı yapılmamalıdır.** Bu akış yalnızca manuel
onay ile ödeme kaydı tutar.

---

## 2. Veri Sözleşmesi

### 2.1 `payments` tablosu

| Alan | Tip | Açıklama |
|------|-----|----------|
| `id` | bigint PK | |
| `tenant_id` | FK → tenants | RULE-T1: zorunlu tenant izolasyonu |
| `ulke_id` | bigint nullable | ülke izolasyonu (HasCountryScope) |
| `reservation_id` | FK → property_reservations | rezervasyon bağlantısı |
| `amount` | decimal(15,2) | ödeme tutarı |
| `currency` | char(3) | para birimi (varsayılan TRY) |
| `payment_method` | string(50) | `kart\|eft\|havale\|nakit\|mock` |
| `status` | string(20) | `pending \| paid \| failed` |
| `reference` | string(100) nullable | banka referansı / makbuz no |
| `notes` | text nullable | açıklama |
| `idempotency_key` | string(100) unique nullable | aynı ödemenin iki kez kaydedilmesini önler |
| `recorded_by` | FK → users | kaydeden kullanıcı |
| `verified_by` | FK → users nullable | onaylayan kullanıcı |
| `verified_at` | timestamp nullable | onay zamanı |

### 2.2 Durum makinesi

```
recordPayment()  → Payment.status = pending
approvePayment() → Payment.status = paid  + rezervasyon.finansal_durum = paid + ledger çift kayıt
failPayment()    → Payment.status = failed
```

- `isTerminal()` → `paid | failed` (yeniden onaylanamaz / değiştirilemez).
- `approvePayment` ve `failPayment`, terminal durumdaki ödemeyi reddeder.

---

## 3. Yetkilendirme (Authorization)

### 3.1 Route katmanı

Checkout route'ları şu middleware zincirinden geçer:

```
web
auth
verified
tenant.context        ← SetTenantContext (tenant izolasyonu)
throttle:30,1
can:manage-ilanlar    ← controller constructor middleware
```

### 3.2 Controller katmanı (defense-in-depth)

`CheckoutController` dört guard uygular:

1. **`guardTenantAccess($ilan)`** — ilan, kimlik doğrulanmış kullanıcının tenant'ına ait olmalı. Aksi halde `403`.
   - **Neden gerekli:** `SetTenantContext` middleware'i `web` grubunda `SubstituteBindings`'ten SONRA çalışır; route model binding (`Ilan $ilan`) sırasında `TenantScope` henüz doğru tenant ile filtrelemez. Bu guard tenant izolasyonunu controller katmanında açıkça zorlar.
2. **`guardReservationBelongsToIlan($ilan, $reservation)`** — rezervasyon ilana ait olmalı. Aksi halde `404`.
3. **`guardPaymentBelongsTo($reservation, $payment)`** — ödeme rezervasyona ait olmalı. Aksi halde `404`.
4. **`can:manage-ilanlar`** — yalnızca `manage-ilanlar` yetkisine sahip kullanıcılar erişebilir.

### 3.3 Servis katmanı

`CheckoutService`:
- `GuardsAgentWrites` trait'i — agent yazma izolasyonu (inner lock).
- `TenantContextResolver` — tenant izolasyonu (auth kullanıcısının tenant_id'si).
- `HasCountryScope` — ulke_id izolasyonu.
- `approvePayment` / `failPayment` — ödemenin tenant kapsamında olup olmadığını kontrol eder (`payment.tenant_id !== tenantId` → `RuntimeException`).

### 3.4 Idempotency

- `idempotency_key` benzersizdir (DB unique constraint).
- `recordPayment()` aynı `idempotency_key` ile ikinci çağrıda mevcut kaydı döner, yeni kayıt oluşturmaz.
- `approvePayment()` ledger çift kaydı için idempotent `idempotencyKey` kullanır.

---

## 4. Production Doğrulama Checklist

### 4.1 Migration

- [ ] `php artisan migrate` — `payments` tablosu oluşturulur.
- [ ] `payments.idempotency_key` unique index doğrulanır.
- [ ] `payments.tenant_id` FK → `tenants` (cascadeOnDelete) doğrulanır.
- [ ] `payments.reservation_id` FK → `property_reservations` (cascadeOnDelete) doğrulanır.

### 4.2 Backend testleri

- [ ] `php artisan test tests/Feature/Admin/CheckoutPaymentFlowTest.php` — 7 test geçer.
  - Checkout sayfası yüklenir.
  - Ödeme kaydı pending oluşturulur.
  - Onay → paid + rezervasyon finansal_durum güncellenir.
  - Başarısız işaretleme.
  - Tenant izolasyonu (cross-tenant erişim deterministik `403` ile engellenir — `assertForbidden`).
  - Idempotency (aynı key ile ikinci kayıt oluşmaz).
  - Rezervasyon–ödeme eşleşme guard'ı.

### 4.3 Browser testleri

- [ ] `npx playwright test tests/e2e/checkout-payment-flow.spec.ts` — 4 test geçer.
  - Checkout sayfası rezervasyon özeti ile yüklenir.
  - Yeni ödeme kaydı oluşturulur ve geçmişte görünür.
  - Manuel onay akışı.
  - Başarısız işaretleme akışı.

### 4.4 Manuel doğrulama (staging)

- [ ] Admin olarak giriş yap.
- [ ] Bir ilanın takviminden rezervasyon seç → Checkout sayfasına git.
- [ ] Rezervasyon özeti (misafir, giriş/çıkış, toplam tutar) doğru görünür.
- [ ] Yeni ödeme kaydı oluştur → "Bekliyor" durumunda görünür.
- [ ] Ödemeyi onayla → "Onaylandı" olur, rezervasyon finansal durumu güncellenir.
- [ ] Ödemeyi başarısız işaretle → "Başarısız" olur.
- [ ] Farklı tenant kullanıcısı aynı ilana erişemez (403/404).

### 4.5 Güvenlik doğrulaması

- [ ] Cross-tenant erişim engellenir (controller guard + TenantScope).
- [ ] `manage-ilanlar` yetkisi olmayan kullanıcı erişemez.
- [ ] Idempotency: aynı `idempotency_key` ile iki kez POST → tek kayıt.
- [ ] Terminal durumdaki ödeme yeniden onaylanamaz / değiştirilemez.

---

## 5. Bilinen Sınırlamalar / Gelecek İş

| Konu | Durum | Not |
|------|-------|-----|
| Ödeme sağlayıcı entegrasyonu | YOK | Mock / manuel onay akışı. Gerçek tahsilat için POSAL/EFT entegrasyonu gerekir. |
| `SetTenantContext` web grubunda | Kısmi | Middleware `api` grubunda; checkout route'larına `tenant.context` açıkça eklendi. Diğer web route'ları için genişletme değerlendirilmeli. |
| `payments` tablosu | Yeni | Mevcut `transactions` tablosuyla ilişkisi ayrıca değerlendirilmeli (çift kayıt riski). |
| Ledger hesap eşlemesi | Sabit | `Tahsilat / Kasa` ve `Misafir Alacakları` hesapları `firstOrCreate` ile çözümlenir. |

---

## 6. Rollback Planı

Migration geri alınabilir:

```bash
php artisan migrate:rollback --step=1
```

Route'lar `routes/admin.php` içindeki checkout bloğu kaldırılarak devre dışı bırakılabilir.

---

## 7. Sonuç

Checkout / Ödeme Akışı **implementation tamamlandı** ve backend + browser testleriyle
doğrulandı. **Production'a almadan önce** yukarıdaki 4.4 ve 4.5 maddelerindeki manuel
ve güvenlik doğrulamaları yapılmalıdır. Gerçek ödeme sağlayıcı entegrasyonu olmadan
bu akış yalnızca manuel onay ve ödeme kaydı tutar — gerçek para tahsilatı yapmaz.