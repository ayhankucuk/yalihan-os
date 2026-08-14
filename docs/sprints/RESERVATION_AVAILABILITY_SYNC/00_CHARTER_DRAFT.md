# RESERVATION-AVAILABILITY-SYNC — Sprint Charter (DRAFT)

> **Status:** 🔲 CHARTER DRAFT — SAAB approval required before implementation
> **Baseline:** `e681d3b` — Guest Communication Wave 1
> **SAAB Direction:** Oturum 121 — Availability Sync Charter hazırlanabilir
> **Model:** Claude Sonnet 4.6 (escalation → Claude Opus 4.8 / SAAB)

---

## 1. Mission

`ReservationCreatedEvent` ve `ReservationCancelledEvent` sonrasında canonical availability state'i downstream channel adapter'lara (Booking.com, Airbnb, Airbnb) outbound sync olarak yaymak. Sync, idempotent, tenant-scoped ve queue-first olmalı.

**Business Question:** YALIHAN, rezervasyon oluşturulduğunda veya iptal edildiğinde tüm dış kanalların availability state'ini insan müdahalesi olmadan güncelleyebiliyor mu?

---

## 2. Critical Architecture Decisions to Freeze Before Implementation

> ⚠️ Aşağıdaki kararlar Charter approval'dan ÖNCE SAAB tarafından dondurulmalıdır. Bu kararlar dondurulmadan implementation başlatılmamalıdır.

### 2.1 Canonical Availability Source

**Question:** Canonical availability hangi tablodan okunur?

| Seçenek | Avantaj | Risk |
|---------|---------|------|
| `property_availability` tablosu | Domain model | Sync lag |
| `property_reservations` tablosu (runtime) | Fresh, no lag | Consistency boundary |
| Derived: reservation table events | Event-driven consistency | Complexity |

**SAAB Kararı Bekleniyor.**

### 2.2 Reservation/Block Source

**Question:** Blocking dates hangi event'lerden türetilir?

- `ReservationCreatedEvent` → block dates
- `ReservationCancelledEvent` → release dates
- `ReservationModifiedEvent` → release old + block new
- Override → release conflict + block new

**Beklenen:** Tüm reservation lifecycle event'leri —aynı ProcessReservationCreated/Modified/Cancelled job'larından zincirlenen yeni job'lar.

### 2.3 Channel Sync Boundary

**Question:** Sync hangi kanalları kapsar?

| Kanal | Mevcut Altyapı | Sync Yöntemi |
|-------|--------------|-------------|
| Booking.com | `BookingChannelAdapter::pushAvailability()` | Wave 4'te yazıldı |
| Airbnb | `AirbnbChannelAdapter::pushAvailability()` | Stub |
| Sahibinden | — | Implementasyon beklenmiyor |
| Yalıhan.com | — | Implementasyon beklenmiyor |

**Beklenen:** Booking.com öncelikli — mevcut altyapı kullanılır. Airbnb stub'ı Wave 1 kapsamı dışında.

### 2.4 Idempotency & Replay

**Question:** Sync nasıl idempotent olur?

- Her sync job: `eventId` bazlı deduplication
- Replay: yeni event üretir, eski event değişmez (ExecutionRuntime replay semantics)
- Channel adapter: zaten `property_id + date` bazlı idempotency destekliyor mu?

**Beklenen:** `eventId` → idempotency key olarak kullanılır. Channel adapter idempotency kontrolü ayrıca değerlendirilir.

### 2.5 Tenant Isolation

**Question:** Sync hangi tenant context'inde çalışır?

- Event envelope `tenantId` → dispatcher'a geçer
- Her channel adapter: tenant-scoped listing mapping kullanır
- `property_availability`: `BelongsToTenant` global scope

**Beklenen:** Mevcut `BelongsToTenant` scope + event `tenantId` kombinasyonu.

### 2.6 Failure / Retry Behavior

**Question:** Channel sync başarısız olursa ne olur?

| Senaryo | Davranış |
|---------|---------|
| HTTP 5xx from Booking | Retry: exponential backoff, max 3 |
| HTTP 4xx (validation error) | Fatal — no retry, log + alert |
| Channel offline | Retry: 5xx backoff |
| Tenant not configured | Skip silently — no evidence |

**Beklenen:** Mevcut `SynchronizeRatesJob` retry pattern'i referans alınır: `$tries=3`, `$backoff=[30,60,120]`, `afterCommit=true`.

---

## 3. Scope (Wave 1)

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

## 4. Event Wiring

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

## 5. File Map (Planlı)

| File | Type | Açıklama |
|------|------|---------|
| `app/Jobs/Reservation/SyncAvailabilityJob.php` | **NEW** | Queued, idempotent, tenant-scoped |
| `app/Services/ChannelManager/AvailabilitySyncPolicy.php` | **NEW** | Channel routing + availability calculation |
| `app/Services/ChannelManager/AvailabilityProjectionService.php` | **NEW** | Reservation → blocked dates projection |
| `app/Jobs/Reservation/ProcessReservationCancelled.php` | **MODIFY** | Wire SyncAvailabilityJob |
| `tests/Feature/Reservation/AvailabilitySyncWave1Test.php` | **NEW** | Certification tests |

---

## 6. DoD Checklist

- [ ] SAAB canonical availability source kararı donduruldu
- [ ] SAAB channel sync boundary kararı donduruldu
- [ ] SyncAvailabilityJob idempotent (eventId deduplication)
- [ ] Tenant isolation: event tenantId → channel adapter
- [ ] Retry: $tries=3, backoff [30, 60, 120], afterCommit=true
- [ ] Booking.com pushAvailability() çağrısı
- [ ] AvailabilitySyncWave1Test: TBD
- [ ] SAB integrity scan: 0 new violations
- [ ] EB regression: 7/7 PASS

---

## 7. Debt Link

| Debt | Durum | Not |
|------|-------|-----|
| LIFECYCLE-DEBT | 🟡 OPEN | Override → `ReservationCancelledEvent` eksik. Availability Sync'i etkilemez — cancellation wave öncesi çözülecek. |
| G34 REGRESSION-DEBT | 🟡 TRACKED | Pre-existing. |

---

## 8. Implementation Model

- **Default:** Claude Sonnet 4.6
- **Escalation:** Claude Opus 4.8 / SAAB — yalnızca şu durumlarda:
  - Canonical availability source yeni mimari karar gerektiriyor
  - Event sınırı değişikliği gerekiyor
  - Yeni domain entity eklenmesi söz konusu

**Approval sequence:** Charter → SAAB Approval → Implementation → Evidence → Testing → Certification → Handoff
