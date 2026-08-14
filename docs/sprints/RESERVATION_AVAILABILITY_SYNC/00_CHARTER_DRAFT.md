# RESERVATION-AVAILABILITY-SYNC — Sprint Charter (DRAFT)

> **Status:** 🔲 CHARTER DRAFT — SAAB approval required before implementation
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

**Karar gerektiren soru:** Availability Sync, LIFECYCLE-DEBT çözülmeden **onaylanmamalıdır**. İki seçenek:

| Seçenek | Açıklama | Avantaj | Risk |
|---------|---------|---------|------|
| **A — Override'a Event ekle** | `ReservationCancelledEvent` override path'e eklenir | En temiz, tek event modeli | LIFECYCLE-DEBT değişikliği gerektirir |
| **B — Sync direkt DB okur** | `SyncAvailabilityJob` sadece canonical tabloyu okur, event'e bağlı değildir | LIFECYCLE-DEBT'ten bağımsız | Canonical tablo doğruluğu kritik |

**SAAB'a sunulacak karar:** Seçenek A mı B mi?

---

## 4. Critical Architecture Decisions to Freeze

> ⚠️ Aşağıdaki kararlar Charter approval'dan ÖNCE SAAB tarafından dondurulmalıdır.

### 4.1 Canonical Availability Source

**Question:** Canonical availability hangi tablodan okunur?

| Seçenek | Avantaj | Risk |
|---------|---------|------|
| `property_availability` tablosu | Domain model, ayrı tablo | Sync lag |
| `property_reservations` (runtime) | Fresh, no lag | Consistency boundary |
| Derived from reservation events | Event-driven consistency | Complexity |

**SAAB Kararı Bekleniyor.**

### 4.2 Reservation/Block Source

**Question:** Blocking dates hangi event'lerden türetilir?

- `ReservationCreatedEvent` → block dates
- `ReservationCancelledEvent` → release dates
- `ReservationModifiedEvent` → release old + block new
- Override → (LIFECYCLE-DEBT kararına bağlı)

**Beklenen:** Tüm reservation lifecycle event'leri — ProcessReservationCreated/Modified/Cancelled job'larından zincirlenen yeni job'lar.

### 4.3 Channel Sync Boundary

**Question:** Sync hangi kanalları kapsar?

| Kanal | Mevcut Altyapı | Scope |
|-------|--------------|-------|
| Booking.com | `BookingChannelAdapter::pushAvailability()` | ✅ Wave 1 |
| Airbnb | `AirbnbChannelAdapter::pushAvailability()` | Stub — Wave 1 dışı |
| Sahibinden | — | ❌ Wave 1 dışı |
| Yalıhan.com | — | ❌ Wave 1 dışı |

**Beklenen:** Booking.com öncelikli — mevcut `pushAvailability()` kullanılır.

### 4.4 Idempotency & Replay

**Question:** Sync nasıl idempotent olur?

- Her sync job: `eventId` bazlı deduplication
- Replay: yeni event üretir, eski event değişmez
- Channel adapter: `property_id + date` bazlı idempotency kontrolü ayrıca değerlendirilir

**Beklenen:** `eventId` → idempotency key olarak kullanılır.

### 4.5 Tenant Isolation

**Question:** Sync hangi tenant context'inde çalışır?

- Event envelope `tenantId` → dispatcher'a geçer
- Her channel adapter: tenant-scoped listing mapping kullanır
- `property_availability`: `BelongsToTenant` global scope

**Beklenen:** Mevcut `BelongsToTenant` scope + event `tenantId` kombinasyonu.

### 4.6 Failure / Retry Behavior

**Question:** Channel sync başarısız olursa ne olur?

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

- [ ] SAAB canonical availability source kararı donduruldu
- [ ] SAAB LIFECYCLE-DEBT override seçeneği (A veya B) kararı donduruldu
- [ ] SAAB channel sync boundary kararı donduruldu
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
| LIFECYCLE-DEBT | 🟡 OPEN | Override → `ReservationCancelledEvent` eksik. Availability Sync + cancellation wave öncesi çözülecek. |
| G34 REGRESSION-DEBT | 🟡 TRACKED | Pre-existing. |

---

## 10. Implementation Model

- **Default:** Claude Sonnet 4.6
- **Escalation:** Claude Opus 4.8 / SAAB — yalnızca şu durumlarda:
  - Canonical availability source yeni mimari karar gerektiriyor
  - LIFECYCLE-DEBT seçeneği (A veya B) kararı gerektiriyor
  - Event sınırı değişikliği gerekiyor

**Approval sequence:** Charter → SAAB Approval → Implementation → Evidence → Testing → Certification → Handoff
