# RESERVATION-AVAILABILITY-SYNC — Sprint Charter (DRAFT)

> **Status:** 🔲 CHARTER DRAFT — 4.1+4.2 APPROVED, 4.3+ OPEN
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

### 4.3 Channel Sync Boundary

**Decision:** OPEN — depends on 4.2.

| Kanal | Mevcut Altyapı | Scope |
|-------|--------------|-------|
| Booking.com | `BookingChannelAdapter::pushAvailability()` | ✅ Wave 1 |
| Airbnb | `AirbnbChannelAdapter::pushAvailability()` | Stub — Wave 1 dışı |
| Sahibinden | — | ❌ Wave 1 dışı |
| Yalıhan.com | — | ❌ Wave 1 dışı |

**Beklenen:** Booking.com öncelikli — mevcut `pushAvailability()` kullanılır.

### 4.4 Idempotency & Replay

**Decision:** OPEN — depends on 4.3.

- Her sync job: `eventId` bazlı deduplication
- Replay: yeni event üretir, eski event değişmez
- Channel adapter: `property_id + date` bazlı idempotency kontrolü ayrıca değerlendirilir

**Beklenen:** `eventId` → idempotency key olarak kullanılır.

### 4.5 Tenant Isolation

**Decision:** OPEN — depends on 4.3.

- Event envelope `tenantId` → dispatcher'a geçer
- Her channel adapter: tenant-scoped listing mapping kullanır
- `property_availability`: `BelongsToTenant` global scope

**Beklenen:** Mevcut `BelongsToTenant` scope + event `tenantId` kombinasyonu.

### 4.6 Failure / Retry Behavior

**Decision:** OPEN — depends on 4.5.

| Senaryo | Davranış |
|---------|---------|
| HTTP 5xx from Booking | Retry: exponential backoff, max 3 |
| HTTP 4xx (validation error) | Fatal — no retry, log + alert |
| Channel offline | Retry: 5xx backoff |
| Tenant not configured | Skip silently |

**Beklenen:** `SynchronizeRatesJob` retry pattern referansı: `$tries=3`, `$backoff=[30,60,120]`, `afterCommit=true`.

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
- [ ] SAAB channel sync boundary (4.3) kararı donduruldu
- [x] LIFECYCLE-DEBT Option A: Override → `ReservationCancelledEvent` çözüldü ✅
- [ ] SyncAvailabilityJob idempotent (eventId deduplication)
- [ ] Tenant isolation: event tenantId → channel adapter
- [ ] Retry: $tries=3, backoff [30, 60, 120], afterCommit=true
- [ ] Booking.com pushAvailability() çağrısı
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
