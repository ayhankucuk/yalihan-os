# RESERVATION-AVAILABILITY-SYNC — Sprint Charter (DRAFT)

> **Status:** 🔲 CHARTER DRAFT — 4.1+4.2+4.3+4.4+4.5 APPROVED, 4.6 OPEN
> **Baseline:** `865d3b4` — Guest Communication Wave 1 + Oturum 121 closed
> **SAAB Direction:** Oturum 121 — Availability Sync Charter hazırlanabilir
> **Model:** Claude Sonnet 4.6 (escalation → Claude Opus 4.8 / SAAB)

---

## 1. Mission

`ReservationCreatedEvent` ve `ReservationCancelledEvent` sonrasında canonical availability state'i downstream channel adapter'lara (Booking.com, Airbnb) outbound sync olarak yaymak. Sync, idempotent, tenant-scoped ve queue-first olmalı.

**Business Question:** YALIHAN, rezervasyon oluşturulduğunda veya iptal edildiğinde tüm dış kanalların availability state'ini insan müdahalesi olmadan güncelleyebiliyor mu?

---

## 2. Canonical Ownership Principle

```
Channel availability, canonical availability'nin SAHİBİ değil;
projection/synchronization HEDEFİDİR.
```

YALIHAN'ın `property_availability` tablosu (veya türevi canonical model) **tek doğruluk kaynağıdır**. Booking.com veya Airbnb'nin durumu YALIHAN'ın availability gerçeğini **belirlemez** — sadece downstream hedef olarak bilgilendirilir.

Bu ilke şunları ifade eder:
- Canonical availability, dış kanallardan BAĞIMSIZDIR
- Sync push-only'dur: YALIHAN değişti → kanallar güncellenir
- Dış kanal hatası canonical availability'yi **değiştirmez**
- Inbound webhook'lar ayrı event-driven path üzerinden canonical model'e yazılır

**Bu ilke, Sprint 12B Property Runtime ve Event Backbone yaklaşımıyla uyumludur.**

> **SAAB Kararı:** Seçenek A — Override path, canonical `ReservationCancelledEvent` üretmelidir. Seçenek B (sync'in doğrudan DB okuması) reddedildi — Event Backbone bypass yaratır.

---

## 3. LIFECYCLE-DEBT Etkisi — Override Path Availability

> ⚠️ **LIFECYCLE-DEBT, yalnızca Guest Communication cancellation wave'ini değil — aynı zamanda availability release davranışını da etkiler.**

**Mevcut durum:** Override path'inde conflict rezervasyon doğrudan DB UPDATE edilir ve `ReservationCancelledEvent` üretilmez.

**Availability için etkisi:**

| Senaryo | Normal Cancellation | Override Cancellation |
|---------|------------------|---------------------|
| DB state | Rezervasyon `CANCELLED` | Rezervasyon `CANCELLED` |
| Event | `ReservationCancelledEvent` tetiklenir | Event **yok** |
| Availability | `SyncAvailabilityJob` dates'i release eder | **Release yok** — dates hâlâ blocked görünür |
| Channel sync | Booking güncellenir | Booking **out of sync** |

**Risk:** Override sonrası Booking.com ve Airbnb'de aynı tarihler hâlâ "blocked" görünür.

**Karar:** **Seçenek A** — Override path, canonical `ReservationCancelledEvent` üretmelidir.

**Gerekçe:** Seçenek B, Event Backbone'u bypass ederek Availability Sync'i ikinci bir state-detection mekanizmasına dönüştürür. Bu, replay, audit ve ileride başka channel adapter'lar açısından iki ayrı gerçeklik yaratır. Canonical reservation → availability → channel projection yönü korunmalıdır.

**DoD etkisi:** LIFECYCLE-DEBT çözümü (Option A) onaylandıktan sonra SyncAvailabilityJob implementasyonu başlayabilir.

---

## 4. Critical Architecture Decisions — Dependency Order

> ⚠️ Kararlar aşağıdaki sırayla çözülmelidir. Her karar sonrakinin的前提ıdır.

```
Canonical source (4.1)
    ↓
Lifecycle/Override semantics (Seçenek A onaylandı)
    ↓
Triggering events (4.2)
    ↓
Projection boundary (4.3)
    ↓
Idempotency / Tenant (4.4–4.5)
    ↓
Retry / Evidence (4.6)
    ↓
→ APPROVED → Implementation
```

### 4.1 Decision: Canonical Availability Source — APPROVED

### 4.1 Canonical Availability Source

**Decision:** ✅ `property_availabilities` tablosu — KESİNLEŞTİ.

**Kanıt — kod analizi (5e0cc07 baseline):**

| Tablo | Rol | Canonical mı? |
|--------|-----|----------------|
| `property_availabilities` | Domain availability state — `ReservationService::createReservation()` yazar | ✅ TEK KAYNAK |
| `property_reservations` | Booking record — overlap check + canonical write trigger | ✅ YAZAR |
| `IlanTakvimSync` | Platform credential/config — sadece `external_listing_id` sağlar | ❌ KANAL CONFIG |
| `AvailabilitySynchronizationService` | `property_availabilities` okur → channel adapter'lara push | ✅ OKUR |
| `BookingChannelAdapter::pushAvailability()` | Pre-built data alır — doğrudan tablo OKUMAZ | ✅ ALICI |

**Write path:**
```
property_reservations (confirmed)
  → ReservationService::createReservation()
    ├── lockForUpdate() overlap check
    ├── insertOrIgnore() → property_availabilities (ensure rows)
    ├── lockForUpdate() on property_availabilities
    ├── PropertyReservation::create()
    └── Update: is_available=false, reservation_id=$id
        ↓
    ReservationCreatedEvent dispatched
        ↓
    AvailabilitySynchronizationService::syncToChannel()
        ↓
    SynchronizeAvailabilityJob
        ↓
    BookingChannelAdapter::pushAvailability(pre-built data)
```

**`IlanTakvimSync` yanlış anlaşılma riski:** Bu tablo sadece `external_listing_id` (HotelCode) ve credential sağlar. Availability **verisi değil**. Channel adapter'lar `property_availabilities`'ı doğrudan OKUMAZ — `AvailabilitySynchronizationService` pre-built data ile çağırır.

**Sonuç:** `property_availabilities` = tek doğruluk kaynağı. `ReservationService` = tek yazı otoritesi. Channel adapter'lar sadece okur.

### 4.2 Triggering Events — APPROVED

**Decision:** Wave 1 — `ReservationCreatedEvent` + `ReservationCancelledEvent` only.

**Kanıt — kod analizi (3ff5dc7 baseline):**

| Event | Availability Etkisi | property_availabilities | Kullanılabilir mi? |
|-------|------------------|----------------------|----------------|
| `ReservationCreatedEvent` | BLOCK dates | `is_available=false` | ✅ Wave 1 dahil |
| `ReservationCancelledEvent` | RELEASE dates | `is_available=true` | ✅ Wave 1 dahil |
| `ReservationModifiedEvent` | RELEASE old + BLOCK new | her ikisi de | ❌ Wave N |
| `ReservationCompletedEvent` | yok | — | ❌ İgnore |

**Override (Option A):** `ReservationCancelledEvent` → override sonrası conflict rezervasyon içinkullanılır — Availability release tetikler. `ReservationService` override path'ine eklenecek.

**Dış kanal etkisi:** Tüm event'ler `ilanId` + `tenantId` taşır — `AvailabilitySynchronizationService` bu ID'leri kullanarak `property_availabilities`'ı okur ve channel adapter'lara push eder.

**ProcessReservationCreated/Modified/Cancelled:** Event lifecycle'ın mevcut job'ları — bunlardan `SyncAvailabilityJob` zincirlenecek.

### 4.3 Channel Sync Boundary — APPROVED

**Decision:** `ChannelSyncContract::pushAvailability()` — mevcut üretim adapter'ları bu kontratı implemente ediyor.

**Kritik bulgu — mevcut kodda tip uyumsuzluğu:**

```
AvailabilitySynchronizationService::syncToChannel()
  ├─ Çağırıyor: $adapter->pushAvailability($dates)
  └─ Beklenen: ChannelSyncContract::pushAvailability(int $tenantId, int $propertyId,
                                                             string $correlationId, array $availabilityData)
```

`BookingChannelAdapter` ve `AirbnbChannelAdapter` → `ChannelSyncContract` implemente eder.
Ama `AvailabilitySynchronizationService::syncToChannel()` → `ChannelAdapter` interface kullanır (E01 artifact, hiçbir adapter tarafından implemente edilmiyor).

**Düzeltme (Wave 1 implementasyonu):**
1. `syncToChannel()` imzası → `(int $tenantId, int $propertyId, string $correlationId, array $dates)`
2. `AvailabilitySynchronizer` binding → `ChannelSyncContract`'e çözümlenir
3. `ChannelAdapter` interface (E01 artifact) → deprecated veya `ChannelSyncContract` ile uyumlu hale getirilir

**Channel Scope:**

| Kanal | Kontrat | Status | Scope |
|-------|---------|--------|-------|
| Booking.com | `ChannelSyncContract::pushAvailability()` | ✅ Production | ✅ Wave 1 |
| Airbnb | `ChannelSyncContract::pushAvailability()` | ✅ Production | ❌ Wave 1 dışı |
| Sahibinden | — | — | ❌ Wave 1 dışı |
| Yalıhan.com | — | — | ❌ Wave 1 dışı |

**ChannelSyncContract avantajları:**
- Tenant-aware: `$tenantId` açık parametre
- Idempotent: `$correlationId` deduplication
- `ChannelSyncResponse` — structured success/failure
- Her iki adapter da implemente ediyor

### 4.4 Idempotency — APPROVED

**Decision:** Üç katmanlı idempotency modeli — mevcut pipeline mimarisi üzerine inşa edilir.

---

#### Üç Katman Modeli

**Katman 1 — Event Consumption Idempotency**

Event tekrarlarına karşı koruma: Aynı `ReservationCreatedEvent` veya `ReservationCancelledEvent` iki kez teslim edilirse sistem aynı sonucu üretir.

Birim: `ChannelSyncExecution.idempotency_key`
```
Tüm katmanlarda tek idempotency_key:
  {tenantId}:{propertyId}:{reservationId}:{eventType}:{startDate}:{endDate}
```
Değer: `eventType` = `block` veya `release`
Yazılış: `AvailabilitySynchronizationService::synchronize()` — mevcut `getIdempotencyKey()` genişletilir.

DB guard: `ChannelSyncExecution.idempotency_key` UNIQUE constraint (mevcut, migration `2026_07_29`)
Kontrol: `findExistingSync()` — SELECT-then-act, race condition riski kabul edilir.
Kontrol zamanı: Transaction öncesi — mevcut kod korunur.

Event yok: `ReservationCreatedEvent`'te `eventId` alanı yok. Tek kimlik `reservationId`. Event tekrarları katman 1'den önce `ProcessReservationCreated`'de engellenmez — katman 1 idempotency check'a güvenilir.

**Katman 2 — Availability Projection Idempotency**

`property_availabilities` tablosunda aynı `property_id + date` için tekrarlanan yazma işlemleri idempotent olmalıdır.

Mevcut mekanizma:
- `ReservationService::createReservation()`: `insertOrIgnore()` + `lockForUpdate()` + conflict check
- `AvailabilitySynchronizationService::synchronize()`: `lockForUpdate()` + `is_available` kontrolü
- Aynı `reservationId + date` tekrar yazıldığında değerler değişmez — idempotent overwrite

DB uniqueness: `property_availabilities` tablosunda `(property_id, date)` unique constraint YOK. Uygulama seviyesinde lock + conflict check ile korunur. Bu kısıtlama mevcut kodda bilinen bir gap olarak kabul edilir; bu Wave'de değiştirilmez.

**Katman 3 — Channel Dispatch Idempotency**

Kanal API çağrıları için: Aynı payload tekrar gönderildiğinde sistem doğru davranır.

Birim: `ChannelSyncContract::pushAvailability($tenantId, $propertyId, $correlationId, $availabilityData)`
Kayıt: `ChannelSyncExecution.correlation_id` — her dispatch için benzersiz, storedempotent API idempotency key olarak kaydedilir.
Kullanım: Log ve izleme — OTA API'ya header olarak gönderilmez (mevcut implementasyon).

Queue deduplication: `SynchronizeAvailabilityJob::uniqueId()` — `'availability_sync_' . $syncRecordId`. Mevcut kod korunur.

Replay koruması: `ChannelSyncExecution.processed_at !== null` guard mevcut — aynı kayıt tekrar çalıştırılmaz.

---

#### Critical Findings — Implementation öncesi bilinmeli

| Bulgu | Durum | Aksiyon |
|-------|-------|---------|
| `correlationId` OTA API'ya header olarak gönderilmiyor | Biliniyor | Belgelendi |
| `ChannelSyncExecution` race condition (SELECT-INSERT) | Biliniyor | Belgelendi |
| `property_availabilities` unique constraint yok | Biliniyor | Belgelendi |
| `ReservationEvent`'te `eventId` yok | Biliniyor | Katman 1 ile telafi |

---

#### Guarantee Seviyesi

Sistem **at-least-once** garanti verir:

- Aynı event birden fazla tetiklenirse, idempotency_key tekrarları engeller
- Aynı channel dispatch idempotency_key ile kaydedilir, tekrarlar `findExistingSync` tarafından yakalanır
- OTA API idempotency: uygulama seviyesinde `correlationId` loglanır; OTA'nın kendi retry mekanizmasına güvenilir
- Exactly-once garantisi yoktur; Outbox pattern bu Wave'de uygulanmaz

**Kabul edilen residual risk:** OTA timeout + yerel kayıt tutarsızlığı — `processed_at` guard retry'i bloke eder; reconciliation worker ayrı Wave'e bırakılır.

---

#### 4.4 Nihai İnvaryantlar

| İnvaryant | Açıklama |
|-----------|----------|
| Aynı event iki kez tetiklenmez | Katman 1 idempotency_key |
| Aynı date/property tekrar yazılmaz | Katman 2 lockForUpdate |
| Kanal idempotency_key benzersiz | Katman 3 unique constraint |
| Replay aynı sonucu üretir | processed_at guard |
| Queue job tekrarlanmaz | uniqueId() + afterCommit |

---

#### 4.4 Test Sözleşmesi

| ID | Senaryo | Given | When | Then |
|----|---------|-------|-------|------|
| I4-T1 | Duplicate Created event | Aynı ReservationCreatedEvent iki kez tetiklenir | SyncAvailabilityJob iki kez çalışır | Sadece bir ChannelSyncExecution kaydı, tek API çağrısı |
| I4-T2 | Duplicate Cancelled event | Aynı ReservationCancelledEvent iki kez | Aynı channel sync tetiklenir | Tek kayıt, tek API çağrısı |
| I4-T3 | Eşzamanlı worker | İki worker aynı idempotency_key ile çağrı yapar | Her iki worker çalışır | Unique constraint biri kaydeder, diğeri unique violation alır |
| I4-T4 | Stale job after canonical release | Gecikmiş Created job çalışır ama tarih release edilmiş | processed_at guard tetiklenir | No-op, hata yok |
| I4-T5 | Channel timeout | OTA timeout döner, kayıt yapılmaz | Retry başlar | Attempt 2'de aynı idempotency_key kullanılır |
| I4-T6 | Tenant izolasyonu | Tenant A ve B aynı property_id + date yazar | Her biri ayrı tenant context ile çalışır | Tenant B'nin kaydı Tenant A'ninkini ezmez |

---

#### 4.5'e Bağımlılık

Tenant isolation kararı (4.5) katman 1'in tenant sınırını netleştirir. Katman 1 zaten tenant koruması içerir; 4.5 bu korumayı onaylar.

#### 4.6'ya Bağımlılık

Retry kararı (4.6) katman 3'ün retry davranışını netleştirir. `tries=3`, `backoff` ve `processed_at` guard zaten mevcut. 4.6 bu pattern'i onaylar ve gerekirse genişletir.

### 4.5 Tenant Isolation — APPROVED

**Decision:** Mevcut `BelongsToTenant` scope + event `tenantId` kombinasyonu.

**Kanıt:**

| Katman | Mechanizma |
|--------|-----------|
| `property_availabilities` | `BelongsToTenant` global scope — tüm query'lere otomatik `tenant_id` filtresi |
| `ChannelSyncExecution` | `tenantId` açık parametre olarak geçer |
| `IlanTakvimSync` | `platform = 'booking_com'` + `is_sync_active = true` JOIN ile tenant izolasyonu |
| Event envelope | `tenantId` — `ReservationCreatedEvent`'te mevcut, dispatcher'a geçer |
| `ChannelSyncContract::pushAvailability()` | `$tenantId` açık parametre — adapter seviyesinde koruma |

### 4.6 Failure / Retry Behavior

**SAAB Decision:** APPROVED — Oturum 135
**Baseline:** 865d3b4 · Date: 2026-08-16

Availability synchronization **at-least-once** çalışabilir; fakat **replay-safe** ve **idempotent** olmak zorundadır.

---

#### 4.6.1 Retry Trigger

SyncAvailabilityJob **queue-first** çalışır.

Retry yalnızca **transient/retryable** failure için yapılır:

| Retryable | Non-Retryable |
|-----------|---------------|
| timeout | invalid credentials/config |
| connection/network failure | invalid property mapping |
| HTTP 429 (rate limit) | validation/schema failure |
| provider 5xx | unsupported operation |
| adapter tarafından `retryable=true` sınıflandırılan hata | provider 4xx (429 hariç), aksini açıkça belirtmedikçe |

**Kural:** Job, provider-specific karar vermemelidir. `SyncAvailabilityJob` "Booking 500 ise..." gibi kurallar taşımamalıdır. Adapter/transport sonucu `retryable` semantics ile dönmeli; job sadece ortak retry politikasını uygular.

---

#### 4.6.2 Retry Semantics

1. **Retry yeni business operation değildir.** Aynı synchronization execution'ın yeniden denenmesidir.
2. **Replay-safe:** Aynı availability state tekrar gönderildiğinde duplicate business side-effect yaratmamalıdır.
3. **DB transaction içinde external API çağrısı yapılmaz.** Queue-first garantisi.
4. **Retry sırasında canonical availability state yeniden okunur.** Eski serialized availability snapshot körlemesine gönderilmez.
5. **Tenant context her retry'da yeniden doğrulanır.** `BelongsToTenant` scope korunur.

---

#### 4.6.3 Observable Attempt Contract

Her attempt observable olmalıdır:

```
execution_id      → ChannelSyncExecution UUID
tenant_id         → Tenant context
property_id       → Property reference
provider/channel   → Booking.com / Airbnb
attempt           → 1, 2, 3, ...
result            → SUCCESS / FAILURE
retryable         → true / false
error             → exception message / code
timestamps        → created_at + processed_at
```

---

#### 4.6.4 Exhaustion Policy

**Retry exhaustion → silent failure YOK.**

```
Retry max → FAILED/EXHAUSTED evidence üretir
          → Human intervention queue'a düşer
          → Alert / notification tetiklenir
```

---

#### 4.6.5 Retry Configuration

| Parametre | Değer | Not |
|-----------|-------|-----|
| `$tries` | 3 (configurable) | Domain invariant değil; operational default |
| `$backoff` | [30, 120, 300] | Exponential — 30s → 120s → 300s |
| `afterCommit` | true | Queue-first garantisi |
| `Retry-After` header | adapter override | Provider daha güçlü bilgi verirse tercih edilir |

**Provider adapter daha güçlü rate-limit bilgisi/Retry-After veriyorsa onu tercih eder.**

---

#### 4.6.6 Technical Invariants

| İnvaryant | Açıklama |
|-----------|-----------|
| No external call in DB transaction | Queue-first korunur |
| Fresh canonical read on retry | `property_availabilities` her attempt'ta okunur |
| Tenant context re-validated | Her attempt'ta `tenantId` kontrolü |
| Observable | Her attempt `ChannelSyncExecution` kaydı |
| No silent failure | Exhaustion → evidence + human intervention |
| Adapter returns retryable semantics | Job provider-specific mantık TAŞIMAZ |

---

#### 4.6.7 Retry Classification Contract (Adapter Interface)

```php
interface ChannelSyncContract {
    // ...
    public function classifyFailure(\Throwable $e): FailureClassification;
}

enum FailureClassification {
    case TRANSIENT;   // Retryable — timeout, network, 429, 5xx
    case PERMANENT;   // Non-retryable — auth, validation, schema, unsupported
    case UNKNOWN;     // Retry — linear backoff
}
```

Job, adapter'dan `FailureClassification` alır. `PERMANENT` → retry yapmaz, `TRANSIENT`/`UNKNOWN` → retry policy uygular.

---

#### 4.6.8 DoD Checklist (4.6)

- [x] SAAB 4.6 Retry Policy kararı donduruldu ✅ (Oturum 135)
- [x] Queue-first garantisi korunur ✅
- [x] Transient/permanent failure ayrımı dokümante edildi ✅
- [x] Retry job provider-specific mantık TAŞIMAZ ✅
- [x] Exhaustion → evidence + intervention dokümante edildi ✅
- [x] Observable attempt contract tanımlandı ✅
- [x] Configurable retry params (domain invariant değil) ✅

---

## 5. Scope (Wave 1)

### Included
- `ReservationCreatedEvent` → block dates → channel sync
- `ReservationCancelledEvent` → release dates → channel sync
- Booking.com availability push (mevcut `pushAvailability()` kullanılır)
- Queue-first: job-based, not synchronous
- Tenant isolation: event envelope `tenantId`
- Idempotency: `eventId` deduplication
- Retry: `$tries=3`, backoff `[30, 60, 120]`
- Evidence: channel-specific sync state (DB veya log)

### Excluded (Future Waves)
- `ReservationModifiedEvent` → date change sync
- Airbnb availability sync
- Sahibinden / Yalıhan.com sync
- Partial date sync (date range optimization)
- Real-time push yerine batch sync

---

## 6. Event Wiring

```
ReservationCreatedEvent
  → ListenReservationCreated (queued)
    → ProcessReservationCreated::handle()
      ├── SendGuestConfirmationJob ✅ (Guest Comm Wave 1)
      └── SyncAvailabilityJob (YENİ — Wave 1)

ReservationCancelledEvent
  → ListenReservationCancelled (queued)
    → ProcessReservationCancelled::handle()
      └── SyncAvailabilityJob (YENİ — Wave 1)
```

---

## 7. File Map (Planlı)

| File | Type | Açıklama |
|------|------|---------|
| `app/Jobs/Reservation/SyncAvailabilityJob.php` | **NEW** | Queued, idempotent, tenant-scoped |
| `app/Services/ChannelManager/AvailabilitySyncPolicy.php` | **NEW** | Channel routing + availability calculation |
| `app/Services/ChannelManager/AvailabilityProjectionService.php` | **NEW** | Reservation → blocked dates projection |
| `app/Jobs/Reservation/ProcessReservationCancelled.php` | **MODIFY** | Wire SyncAvailabilityJob |
| `tests/Feature/Reservation/AvailabilitySyncWave1Test.php` | **NEW** | Certification tests |

---

## 8. DoD Checklist

- [x] SAAB canonical availability source (4.1) kararı donduruldu ✅
- [x] SAAB triggering events (4.2) kararı donduruldu ✅
- [x] SAAB channel sync boundary (4.3) kararı donduruldu ✅
- [x] SAAB idempotency (4.4) kararı donduruldu ✅
- [x] SAAB tenant isolation (4.5) kararı donduruldu ✅
- [x] SAAB retry policy (4.6) kararı donduruldu ✅
- [x] LIFECYCLE-DEBT Option A: Override → `ReservationCancelledEvent` çözüldü ✅
- [ ] SyncAvailabilityJob idempotent (eventId deduplication)
- [ ] Tenant isolation: event tenantId → channel adapter
- [ ] Retry: $tries=3, backoff [30, 120, 300], afterCommit=true
- [ ] Booking.com pushAvailability() çağrısı
- [ ] FailureClassification enum (TRANSIENT/PERMANENT/UNKNOWN)
- [ ] Exhaustion → FAILED/EXHAUSTED evidence + human intervention
- [ ] AvailabilitySyncWave1Test: TBD
- [ ] SAB integrity scan: 0 new violations
- [ ] EB regression: 7/7 PASS
- [ ] Guest Comm regression: 12/12 PASS

---

## 9. Debt Link

| Debt | Durum | Not |
|------|-------|-----|
| LIFECYCLE-DEBT | 🟡 OPEN → OPTION A SELECTED | Seçenek A: Override → `ReservationCancelledEvent` üretmeli. SAAB review'da çözülecek. |
| G34 REGRESSION-DEBT | 🟡 TRACKED | Pre-existing. |

---

## 10. Implementation Model

- **Default:** Claude Sonnet 4.6
- **Escalation:** Claude Opus 4.8 / SAAB — yalnızca şu durumlarda:
  - Canonical availability source yeni mimari karar gerektiriyor
  - LIFECYCLE-DEBT seçeneği (A veya B) kararı gerektiriyor
  - Event sınırı değişikliği gerekiyor

**Approval sequence:** Charter → SAAB Approval → Implementation → Evidence → Testing → Certification → Handoff
