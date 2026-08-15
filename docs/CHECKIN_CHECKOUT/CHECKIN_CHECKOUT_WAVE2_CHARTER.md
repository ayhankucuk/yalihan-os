# CHECKIN_CHECKOUT Wave 2 — Guest Arrival Readiness
## Architecture Charter

> **Baseline:** `e66b58d` (CHECKOUT_WAVE1 — Operational task automation)
> **SAAB Review:** Required before implementation
> **Builder:** Claude Sonnet 4.6
> **Status:** Frozen — Implementation Scope

---

## 1. Domain: Guest Arrival Readiness

Guest Arrival Readiness = Mülkün misafir girişine hazır olması durumu.
Sistem otomatik olarak bu hazırlığı izler, eksiklikleri işaretler ve check-in
pencere açılmadan önce müdahale gerektiğinde uyarır.

**Mekansal kapsam:** Bodrum/Yalıhan AI OS — Yalıhan Emlak
**Günlük operasyonel sorumluluk:** Temizlik ekibi, Ayhan (manuel), AI (otomatik)

---

## 2. Wave 2 Scope — Dondurulmuş

### 2.1 Core Capabilities (Bu charter'ın konusu)

| ID | Capability | Açıklama |
|----|-------------|----------|
| W2-01 | Reservation Validity Gate | Rezervasyon check-in yapılabilir mi kontrolü |
| W2-02 | Property Readiness Tracker | Mülk hazırlık durumu izleme |
| W2-03 | Preparation Task Completion | Hazırlık görevi tamamlanma takibi |
| W2-04 | Guest Contact Readiness | Misafir iletişim bilgilerinin eksiksizliği |
| W2-05 | Access Credential Safety | Giriş kodu/anahtar güvenliği |
| W2-06 | Cancellation/Date-Change Handler | İptal/tarih değişikliği durumunda readiness invalidation |
| W2-07 | Check-in Window Management | Check-in penceresi açma/kapama |
| W2-08 | Idempotency & Tenant Isolation | Wave 1'in üzerine invariants |

### 2.2 Out of Scope (Bu charter'ın konusu DEĞİL)

- Check-in/Check-out physical process (Handshake, Digital signature)
- n8n/Telegram/WhatsApp notification automation
- Guest portal / Self-check-in
- Financial reconciliation
- External channel sync (Booking.com, Airbnb)
- Housekeeping scheduling UI
- AI agent orchestration (Hermes, Photo Agent, Description Agent)

---

## 3. Data Model Extensions

### 3.1 Migration: `property_readiness` table

```php
// Migration: 2026_08_XX_add_property_readiness_table.php
Schema::create('property_readiness', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('tenant_id');         // Tenant isolation root
    $table->unsignedBigInteger('reservation_id');     // FK → property_reservations
    $table->unsignedBigInteger('ilan_id');            // FK → ilanlar

    // Readiness dimensions
    $table->boolean('property_clean');               // Temizlik tamamlandı mı?
    $table->boolean('access_credential_ready');      // Kod/anahtar hazır mı?
    $table->boolean('guest_contact_ready');          // Misafir iletişimi eksiksiz mi?
    $table->boolean('amenity_check_complete');       // Tesisat kontrol edildi mi?
    $table->boolean('welcome_kit_prepared');         // Karşılama seti hazır mı?

    // Computed aggregate (set by service, not stored initially)
    $table->boolean('is_ready')->default(false);     // Tümü true olmalı

    // Timestamps
    $table->timestamps();

    // Indexes
    $table->index(['tenant_id', 'reservation_id']);
    $table->unique(['reservation_id']);              // 1 readiness per reservation
    $table->index(['tenant_id', 'ilan_id']);
});
```

### 3.2 Migration: `access_credentials` table

```php
// Migration: 2026_08_XX_add_access_credentials_table.php
Schema::create('access_credentials', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('tenant_id');         // Tenant isolation root
    $table->unsignedBigInteger('ilan_id');            // FK → ilanlar

    // Credential types
    $table->string('credential_type');                // 'key', 'code', 'lockbox', 'smart_lock'
    $table->string('credential_value');               // Şifrelenmiş değer — KESİNLİKLE loglanmaz
    $table->string('credential_location')->nullable(); // Lockbox kodu, vs.

    // Safety metadata
    $table->boolean('is_active')->default(true);
    $table->boolean('requires_reset')->default(false); // Misafir sonrası reset gerekiyor mu?
    $table->date('last_reset_at')->nullable();
    $table->date('expires_at')->nullable();          // Kod geçerlilik süresi

    // Audit
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index(['tenant_id', 'ilan_id', 'is_active']);
});
```

### 3.3 PropertyReservation extensions

```php
// Migration: 2026_08_XX_add_checkin_window_to_reservations.php
Schema::table('property_reservations', function (Blueprint $table) {
    // Check-in window: enabled when guest is within arrival window
    $table->timestamp('checkin_window_opened_at')->nullable()->after('completed_at');
    $table->timestamp('checked_in_at')->nullable()->change(); // Wave 1'den var, nullable olacak

    // Guest arrival info
    $table->string('arrival_time_estimated')->nullable()->after('checked_in_at'); // "18:00"
    $table->text('arrival_notes')->nullable();    // "Late arrival, key in lockbox"
});
```

---

## 4. Invariant Specifications

### 4.1 Tenant Isolation Invariants

```
INV-W2-T1: PropertyReadiness belongs to exactly one tenant
  ∀ pr ∈ property_readiness: pr.tenant_id == pr.reservation.tenant_id == pr.ilan.tenant_id
  Implied: reservation ve ilan tenant_id'leri eşleşmezse ReadinessService RuntimeException atar

INV-W2-T2: AccessCredential belongs to exactly one tenant
  ∀ ac ∈ access_credentials: ac.tenant_id == ac.ilan.tenant_id
  Implied: Credential CRUD sırasında tenant_id doğrulaması zorunlu

INV-W2-T3: Cross-tenant Readiness query is impossible
  Tüm ReadinessService query'leri tenant_id scope ile çalışır
  Tenant context: auth()->user()->tenant_id veya request tenant header
```

### 4.2 Idempotency Invariants

```
INV-W2-I1: One readiness record per reservation
  ∀ r ∈ reservations: |{ pr ∈ property_readiness | pr.reservation_id == r.id }| <= 1
  Uygulama: READINESS table'da unique constraint + upsert pattern

INV-W2-I2: Readiness task creation is idempotent
  OperationalGorevService.taskExists() kontrolü Wave 2'ye taşınır
  createHazirlikReadinessTask() → taskExists() guard

INV-W2-I3: Access credential retrieval is idempotent per reservation
  Guest check-in sırasında aynı rezervasyon için credential tekrar üretilmez
  Stored credential kullanılır veya exception atılır

INV-W2-I4: Check-in window open is idempotent
  Aynı rezervasyon için checkin_window_opened_at birden fazla kez SET EDİLMEZ
  Sadece NULL → timestamp geçişi yapılır
```

### 4.3 Validity Invariants

```
INV-W2-V1: Reservation must be CONFIRMED to open check-in window
  checkin_window_open() → reservation.reservation_state == CONFIRMED
  CANCELLED/PENDING/BLOCKED → RuntimeException

INV-W2-V2: Check-in window opens 24 hours before start_date
  now() >= start_date - 24h → pencere açılabilir
  Şablon: `Carbon::parse($reservation->start_date)->subDay()->startOfDay()`

INV-W2-V3: Reservation must not be cancelled
  cancelled_at != null → reservation_state == CANCELLED olmalı
  Cancelled rezervasyon için readiness yapılmaz, varsa invalidation edilir

INV-W2-V4: Property must be rental_enabled
  Mülk kiralama için aktif değilse readiness oluşturulmaz
  Kullanım: $ilan->rental_enabled === true
```

### 4.4 Date-Change Handling Invariants

```
INV-W2-D1: Date change invalidates readiness record
  start_date veya end_date değişirse property_readiness.is_ready = false
  Mevcut Gorev'ler pasif yapılır (gorev_durumu = 'iptal')

INV-W2-D2: Date change creates new hazirlik task
  ReservationModifiedEvent tetiklenir → new hazirlik Gorev oluşur
  Yeni tarihlerle deadline hesaplanır

INV-W2-D3: New readiness record created on date change
  property_readiness tablosuna yeni kayıt eklenir (upsert değil, INSERT)
  Eski readiness_log tablosuna snapshot alınır (audit trail)
```

### 4.5 Cancellation Handling Invariants

```
INV-W2-C1: Cancellation invalidates readiness
  ReservationCancelledEvent → property_readiness record set to invalid
  Tüm boolean dimension fields = false
  is_ready = false

INV-W2-C2: Cancellation cancels pending hazirlik Gorev
  gorev_durumu = 'iptal' for hazirlik tasks linked to reservation
  Notification trigger: GorevCreated event → n8n → Ayhan'a bildirim

INV-W2-C3: Cancelled reservation cannot open check-in window
  cancelled_at != null → checkin_window_open() blocked
```

### 4.6 Access Credential Safety Invariants

```
INV-W2-S1: Credential values never logged
  credential_value, credential_location: Log::debug/info/warning/alert — TÜMÜ masked "***"
  Debug modunda bile gerçek değer görünmez

INV-W2-S2: Credential stored encrypted
  credential_value: Laravel Crypt facade ile encrypted
  Database: encrypted string, raw değil

INV-W2-S3: Credential expires after checkout + 24h
  expires_at = end_date + 24 hours
  Otomatik cleanup job: ResetCredentialJob

INV-W2-S4: Smart lock codes auto-expire
  Smart lock provider'a bağlı: API expiration desteklenir
  Fallback: expires_at + manual reset flag
```

---

## 5. Service Architecture

### 5.1 GuestArrivalReadinessService

```php
// app/Services/Reservation/GuestArrivalReadinessService.php

/**
 * GuestArrivalReadinessService — Wave 2 core service
 *
 * Responsibilities:
 * - Create/update property_readiness records
 * - Aggregate readiness dimensions
 * - Validate reservation validity for check-in
 * - Handle date-change and cancellation invalidation
 *
 * Uses GuardsAgentWrites: YES
 * Tenant isolation: Enforced via tenant_id on all queries
 * Idempotency: Upsert pattern with unique constraint
 */
class GuestArrivalReadinessService
{
    // W2-01: Reservation Validity Gate
    public function canCheckIn(PropertyReservation $reservation): ValidityResult;
    public function validateReadinessPreconditions(PropertyReservation $reservation): void;

    // W2-02: Property Readiness Tracker
    public function getReadiness(PropertyReservation $reservation): ?PropertyReadiness;
    public function getReadinessForIlan(int $ilanId, Carbon $date): Collection;

    // W2-03: Preparation Task Completion tracking
    public function onHazirlikTaskCompleted(Gorev $task): void; // Gorev durumu → readiness
    public function getPendingReadinessItems(PropertyReservation $reservation): array;

    // W2-07: Check-in Window Management
    public function openCheckinWindow(PropertyReservation $reservation): bool;
    public function closeCheckinWindow(PropertyReservation $reservation): bool;
    public function isCheckinWindowOpen(PropertyReservation $reservation): bool;

    // W2-06: Cancellation/Date-Change
    public function invalidateOnCancellation(PropertyReservation $reservation): void;
    public function invalidateOnDateChange(
        PropertyReservation $reservation,
        string $oldStartDate,
        string $oldEndDate
    ): void;
}
```

### 5.2 AccessCredentialService

```php
// app/Services/Reservation/AccessCredentialService.php

/**
 * AccessCredentialService — Wave 2 credential management
 *
 * Responsibilities:
 * - Store/retrieve encrypted access credentials
 * - Enforce credential safety invariants
 * - Handle credential lifecycle (issue, expire, reset)
 *
 * Uses GuardsAgentWrites: YES
 * Safety: credential_value NEVER appears in logs
 */
class AccessCredentialService
{
    public function getActiveCredential(Ilan $ilan): ?AccessCredential;
    public function issueCredential(
        PropertyReservation $reservation,
        Ilan $ilan
    ): AccessCredential;
    public function markRequiresReset(AccessCredential $credential): void;
    public function cleanupExpiredCredentials(): int; // Returns count of cleaned
}
```

---

## 6. Event Wiring

### 6.1 Wave 2 Event Flow

```
[Rezervasyon Oluşturuldu]
        │
        ▼
ReservationCreatedEvent ──▶ CreateOperationalTasksJob (Wave 1)
        │                              │
        │                              ▼
        │                    OperationalGorevService::createPreArrivalTask()
        │                              │
        │                              ▼
        │                    GorevCreated ──▶ n8n ──▶ Telegram
        │
        ▼
[GuestArrivalReadinessService::onReservationCreated()]
        │
        ▼
property_readiness UPSERT (hazirlik task varsa)

        │
        ▼ (24h before start_date)
CheckinWindowOpenJob ──▶ GuestArrivalReadinessService::openCheckinWindow()
        │
        ▼
ReservationCheckedInEvent ──▶ (future: Wave 3)

[Hazirlik Gorev Tamamlandı]
        │
        ▼
GorevCompletedEvent ──▶ GuestArrivalReadinessService::onHazirlikTaskCompleted()

[Rezervasyon İptal Edildi]
        │
        ▼
ReservationCancelledEvent ──▶ GuestArrivalReadinessService::invalidateOnCancellation()
        │                              │
        │                              ▼
        │                    property_readiness: is_ready = false
        │
        ▼
Hazirlik Gorev → iptal (opsiyonel: reservation_id ile)

[Tarih Değişikliği]
        │
        ▼
ReservationModifiedEvent ──▶ GuestArrivalReadinessService::invalidateOnDateChange()
```

### 6.2 New Events (Wave 2)

```php
// app/Events/Reservation/CheckinWindowOpenedEvent.php
class CheckinWindowOpenedEvent
{
    public readonly int $reservationId;
    public readonly int $tenantId;
    public readonly Carbon $openedAt;
    public readonly Carbon $checkinTime; // start_date + check_in_time
}

// app/Events/Reservation/ReadinessCompletedEvent.php
class ReadinessCompletedEvent
{
    public readonly int $reservationId;
    public readonly int $tenantId;
    public readonly bool $isReady; // true = tüm kontroller tamam
    public readonly array $completedDimensions; // ['property_clean', 'access_credential_ready', ...]
}
```

---

## 7. Test Strategy

### 7.1 Evidence Tests (Mandatory — E# pattern)

```
E1:  Rezervasyon oluşturuldu → property_readiness record oluşur
E2:  Duplicate rezervasyon oluşturma → tek readiness record (idempotency)
E3:  Cross-tenant readiness erişimi → RuntimeException
E4:  CANCELLED rezervasyon → checkin_window_open() exception atar
E5:  cancelled_at set → readiness is_ready = false
E6:  Hazirlik Gorev tamamlandı → readiness property_clean = true
E7:  Tüm readiness dimensions true → is_ready = true
E8:  Date change → readiness is_ready = false + yeni hazirlik Gorev
E9:  Access credential stored → logda maskelenmiş görünür
E10: Access credential expires_at set → end_date + 24h
E11: Check-in window 24h before → isCheckinWindowOpen() = true
E12: Check-in window BEFORE 24h → isCheckinWindowOpen() = false
E13: No regression: mevcut Wave 1 Gorev oluşturma davranışı değişmez
E14: tenant_id mismatch → RuntimeException on credential access
E15: Kiralama kapalı mülk → readiness oluşturulmaz
```

### 7.2 Test File Location

```
tests/Feature/CheckinCheckoutWave2Test.php
tests/Unit/GuestArrivalReadinessServiceTest.php
tests/Unit/AccessCredentialServiceTest.php
```

---

## 8. File Manifest

### 8.1 New Files (Wave 2)

| Dosya | Açıklama |
|-------|----------|
| `app/Services/Reservation/GuestArrivalReadinessService.php` | Core readiness orchestration |
| `app/Services/Reservation/AccessCredentialService.php` | Credential lifecycle |
| `app/Models/PropertyReadiness.php` | Readiness aggregate model |
| `app/Models/AccessCredential.php` | Credential model |
| `app/Events/Reservation/CheckinWindowOpenedEvent.php` | Check-in window event |
| `app/Events/Reservation/ReadinessCompletedEvent.php` | Readiness completion event |
| `app/Jobs/Reservation/OpenCheckinWindowJob.php` | 24h trigger job |
| `app/Jobs/Reservation/ResetAccessCredentialJob.php` | Post-checkout cleanup |
| `database/migrations/2026_08_XX_000000_add_property_readiness_table.php` | Readiness table |
| `database/migrations/2026_08_XX_000001_add_access_credentials_table.php` | Credentials table |
| `database/migrations/2026_08_XX_000002_add_checkin_window_to_reservations.php` | Reservation extensions |
| `tests/Feature/CheckinCheckoutWave2Test.php` | Evidence tests |
| `tests/Unit/GuestArrivalReadinessServiceTest.php` | Unit tests |
| `tests/Unit/AccessCredentialServiceTest.php` | Unit tests |

### 8.2 Modified Files (Wave 2)

| Dosya | Değişiklik |
|-------|-----------|
| `app/Providers/EventServiceProvider.php` | Wave 2 event listeners |
| `app/Console/Kernel.php` | OpenCheckinWindowJob schedule (daily) |
| `app/Console/Kernel.php` | ResetAccessCredentialJob schedule (daily) |
| `app/Models/PropertyReservation.php` | Yeni fields (checkin_window_opened_at, arrival_time_estimated, arrival_notes) |
| `app/Models/Ilan.php` | Rental_enabled ilişki |
| `app/Services/Reservation/OperationalGorevService.php` | onHazirlikTaskCompleted() — readiness update |

### 8.3 Files NOT Modified (SAB Lock)

```
app/Services/Ilan/IlanCrudService.php       ← Protected
app/Services/AI/YalihanCortex.php           ← Protected
app/Services/ReservationService.php          ← Wave 1 baseline, genişletme için wrapper olabilir
```

---

## 9. SAB Compliance Checklist

| Kural | Durum | Not |
|-------|-------|-----|
| Thin Controller | ✅ | Tüm iş mantığı service katmanında |
| Tenant Isolation | ✅ | Tüm query'ler tenant_id scope |
| No Eloquent in Controller | ✅ | Read/Write zinciri korunur |
| Context7 Kanonik Alan Adları | ✅ | İngilizce enum/string literal'ler muaf |
| No env() in app/ | ✅ | config() kullanılır |
| No empty catch | ✅ | Log + rethrow veya @sab-ignore-catch |
| No Font Awesome | ✅ | x-icon component kullanılır |
| No hardcoded admin URL | ✅ | route() kullanılır |
| Guarded writes | ✅ | GuardsAgentWrites trait kullanılır |

---

## 10. Implementation Order

```
[1] Migrations (idempotent — up/down balanced)
    ├── add_property_readiness_table.php
    ├── add_access_credentials_table.php
    └── add_checkin_window_to_reservations.php

[2] Models
    ├── PropertyReadiness.php
    └── AccessCredential.php

[3] Core Services
    ├── AccessCredentialService.php (credential safety invariants)
    └── GuestArrivalReadinessService.php (readiness orchestration)

[4] Event Classes
    ├── CheckinWindowOpenedEvent.php
    └── ReadinessCompletedEvent.php

[5] Jobs
    ├── OpenCheckinWindowJob.php
    └── ResetAccessCredentialJob.php

[6] Wiring
    ├── EventServiceProvider: GorevCompleted → readiness update
    ├── Kernel: OpenCheckinWindowJob schedule
    └── Kernel: ResetAccessCredentialJob schedule

[7] Evidence Tests
    ├── CheckinCheckoutWave2Test.php (9/15 E#)
    ├── GuestArrivalReadinessServiceTest.php
    └── AccessCredentialServiceTest.php

[8] Regression Gate
    └── php artisan test → 0 failures on existing suite
```

---

## 11. Success Criteria

| Kriter | Hedef | Metod |
|--------|-------|-------|
| Evidence tests | 15/15 PASS | `php artisan test` |
| Regression | 0 new failures | Existing test suite |
| Idempotency | Double event dispatch = single record | E1, E2 |
| Tenant isolation | Cross-tenant = exception | E3, E14 |
| Credential safety | No raw value in logs | Manual log inspection |
| SAB integrity | 0 AST violations | `php artisan sab:integrity-scan` |
| Type safety | 0 static analysis errors | PHPStan level 5 |

---

## 12. Open Questions (Resolve Before Implementation)

- [ ] **Q1:** Access credential için lockbox kodu mı, smart lock API mi? (Smart lock provider'a bağlı)
- [ ] **Q2:** Check-in window açıldığında Telegram bildirimi Wave 2'de mi yoksa Wave 3'te mi?
- [ ] **Q3:** property_readiness tablosunda is_ready aggregate'i store mı edilmeli yoksa her zaman compute mu edilmeli?
- [ ] **Q4:** Date change invalidation için eski readiness snapshot'ı history tablosuna mı taşınmalı?
- [ ] **Q5:** Access credential reset job cron schedule: her gün mü, her saat mi?

---

**Charter Status:** READY FOR SAAB REVIEW
**Prepared by:** Kilo Agent (Claude Opus 4.8)
**Baseline:** e66b58d
**Date:** 2026-08-16
