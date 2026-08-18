# Decision 4.2 — Triggering Events Discovery Report

**Type:** Repository Architecture Analysis
**Parent:** SAAB Decision 4.1 — Materialized Canonical State
**Baseline:** `4b406bf`
**Date:** 2026-08-15
**Scope:** Triggering events, write paths, dual authority problem
**Model:** Claude Sonnet 4.6

---

## 1. Triggering Events — Mevcut Durum

### 1.1 Canonical Reservation Lifecycle Events

| Event | Tetikleniyor mu? | PropertyAvailability etkisi | Channel Sync |
|-------|------------------|---------------------------|-------------|
| `ReservationCreatedEvent` | ✅ Evet | ✅ ReservationService içinde direkt yazılıyor | ❌ Bağlı değil |
| `ReservationModifiedEvent` | ✅ Evet | ✅ ReservationService içinde direkt yazılıyor | ❌ Bağlı değil |
| `ReservationCancelledEvent` | ✅ Evet | ❌ **YAZILMIYOR** (LIFECYCLE-DEBT) | ❌ Bağlı değil |
| `ReservationCompletedEvent` | ❌ Yok | ❌ Yok | ❌ Yok |

### 1.2 Manual/Admin Events

| Event | Mevcut mu? | PropertyAvailability etkisi |
|-------|------------|--------------------------|
| Manual Block (BlockCalendarDatesAction) | ✅ Var | ✅ Direkt yazılıyor |
| Manual Unblock | ⚠️ Kısmi | ⚠️ Sınırlı |
| Maintenance Block | ⚠️ Kısmi | ⚠️ Sınırlı |

---

## 2. CRITICAL: Çift Write Path Problemi

### 2.1 Mevcut Mimari

```
┌─────────────────────────────────────────────────────────────────────┐
│  PATH 1: ReservationService (direkt write)                            │
│                                                                     │
│  ReservationService::createReservation()                              │
│      ↓                                                               │
│  PropertyAvailability::updateOrCreate(source='internal')              │
│      ↓                                                               │
│  ReservationCreatedEvent (AFTER COMMIT)                              │
│      ↓                                                               │
│  ProcessReservationCreated::handle()                                 │
│      ↓                                                               │
│  ❌ AvailabilitySynchronizationService BAĞLI DEĞİL                    │
└─────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────┐
│  PATH 2: AvailabilitySynchronizationService (standby)               │
│                                                                     │
│  Service MEVCUT ama HENÜZ ÇAĞRILMIYOR                             │
│  Kod: app/Application/ChannelManager/Services/                       │
│       AvailabilitySynchronizationService.php                            │
│                                                                     │
│  ReservationCreatedEvent → ❌ HENÜZ BAĞLI DEĞİL                   │
└─────────────────────────────────────────────────────────────────────┘
```

### 2.2 Write Path Karşılaştırması

| Aspect | Path 1: ReservationService | Path 2: AvailabilitySyncService |
|--------|--------------------------|--------------------------------|
| source_system | `'internal'` | `'canonical'` |
| tetiklenme | Synchronous, transaction içinde | Asenkron, afterCommit |
| Channel sync | ❌ Yok | ✅ `SynchronizeAvailabilityJob` |
| Idempotency | ⚠️ updateOrCreate | ✅ idempotency key |
| tenant_id validation | ⚠️ Global scope | ✅ Explicit |
| lockForUpdate | ❌ Yok | ✅ Var |

### 2.3 Kritik Sorun

**Problem:** Aynı `(property_id, date)` kombinasyonu iki ayrı path tarafından yazılabilir:

```php
// Path 1: ReservationService — source='internal'
PropertyAvailability::updateOrCreate(
    ['property_id' => $id, 'date' => $date],
    ['is_available' => false, 'source_system' => 'internal']
);

// Path 2: AvailabilitySyncService — source='canonical'
PropertyAvailability::updateOrCreate(
    ['property_id' => $id, 'date' => $date, 'tenant_id' => $tenantId],
    ['is_available' => false, 'source_system' => 'canonical']
);
```

**Risk:** Aynı tarih için iki farklı `source_system` değeri yazılabilir — deterministic state garantisi bozulur.

---

## 3. Reservation Lifecycle Events — Triggering Analizi

### 3.1 ReservationCreatedEvent

**Tetiklenme Noktası:**
```php
// ReservationService.php:144 — after transaction commits
event(new ReservationCreatedEvent(
    reservationId: $reservation->id,
    tenantId: $reservation->tenant_id,
    ilanId: $reservation->ilan_id,
    startDate: $this->formatDate($reservation->start_date),
    endDate: $this->formatDate($reservation->end_date),
    // ...
));
```

**Availability Etkisi:**
```php
// ReservationService.php:597 — transaction içinde direkt yazıyor
PropertyAvailability::updateOrCreate(
    ['property_id' => $reservation->property_id, 'date' => $dateStr],
    [
        'is_available'   => false,
        'block_reason'   => 'reservation',
        'source_system'  => 'internal',  // ⚠️ Farklı source
        'reservation_id' => $reservation->id,
    ]
);
```

**Kanal Sync Etkisi:** ❌ `AvailabilitySynchronizationService` BAĞLI DEĞİL

---

### 3.2 ReservationModifiedEvent

**Tetiklenme Noktası:**
```php
// ReservationService.php:621 — modifyReservation() sonrası
event(new ReservationModifiedEvent(
    reservationId: $reservation->id,
    // previous ve new dates...
));
```

**Availability Etkisi:**
```php
// ReservationService.php:597 — önce eski tarihleri release, sonra yeni tarihleri block
// Eski tarihler için updateOrCreate(is_available=true)
// Yeni tarihler için updateOrCreate(is_available=false, source='internal')
```

**Kanal Sync Etkisi:** ❌ `AvailabilitySynchronizationService` BAĞLI DEĞİL

---

### 3.3 ReservationCancelledEvent (LIFECYCLE-DEBT)

**Tetiklenme Noktası:**
```php
// ReservationService.php:482 — cancelReservation() sonrası
event(new ReservationCancelledEvent(
    reservationId: $reservation->id,
    tenantId: $reservation->tenant_id,
    ilanId: $reservation->ilan_id,
    startDate: $this->formatDate($reservation->start_date),
    endDate: $this->formatDate($reservation->end_date),
    cancelledBy: $cancelledBy,
    datesToRelease: $datesToRelease,  // Dahil ediliyor
));
```

**Availability Etkisi:** ❌ **YOK** — `cancelReservation()` içinde `PropertyAvailability` yazılmıyor!

```php
// ReservationService.php — cancelReservation() içinde:
// ❌ PropertyAvailability::updateOrCreate YOK
$reservation->update(['reservation_state' => ReservationState::CANCELLED]);
// ❌ Availability release KODU YOK
```

**Problem:** Cancellation sonrası tarihler hâlâ blocked görünür.

**Kanal Sync Etkisi:** ❌ BAĞLI DEĞİL

---

### 3.4 ReservationCompletedEvent

**Durum:** ❌ **EVENT YOK**

Rezervasyon tamamlandığında (check-out) tarihler otomatik olarak açılmalı. Şu an manual intervention gerekiyor.

---

### 3.5 Manual Block (BlockCalendarDatesAction)

**Tetiklenme:** Admin panel — manuel blok

**Availability Etkisi:**
```php
// BlockCalendarDatesAction.php:13
$block = PropertyAvailability::updateOrCreate(
    ['property_id' => $ilanId, 'date' => $date],
    [
        'is_available'  => false,
        'block_reason'  => 'manual',
        'source_system' => 'internal',
    ]
);
```

**Kanal Sync:** ❌ YOK

---

## 4. Triggering Events Matrisi

| Event | Trigger | Availability Action | Channel Sync | MUST |
|-------|---------|---------------------|-------------|------|
| `ReservationCreatedEvent` | ✅ | Block dates | ❌ Pending | 4.2 |
| `ReservationModifiedEvent` | ✅ | Release old + Block new | ❌ Pending | 4.2 |
| `ReservationCancelledEvent` | ✅ | ❌ **Release YOK** | ❌ Pending | 4.2 + LIFECYCLE-DEBT |
| `ReservationCompletedEvent` | ❌ | N/A | N/A | Backlog |
| Manual Block | ✅ | Block | ❌ Pending | 4.2 |
| Manual Unblock | ⚠️ | Unblock | ❌ Pending | 4.2 |

---

## 5. Çift Write Authority Çözüm Seçenekleri

### Seçenek A: Tek Canonical Materializer

> Sadece `AvailabilitySynchronizationService` PropertyAvailability yazar. `ReservationService` doğrudan yazmaz.

**Avantajları:**
- Tek write authority — deterministic state garantisi
- `source_system = 'canonical'` tutarlılığı
- Kanal sync otomatik

**Dezavantajları:**
- ReservationService'ten kod kaldırma (refactor)
- Transaction boundary değişikliği

**Implementasyon:**
```php
// ReservationService::createReservation() — AFTER commit
event(new ReservationCreatedEvent(...));
// ❌ PropertyAvailability::updateOrCreate() KALDIRILIR

// ProcessReservationCreated::handle()
$syncCommand = new SynchronizeAvailabilityCommand(
    tenantId: $event->tenantId,
    propertyId: $event->ilanId,
    reservationId: $event->reservationId,
    operation: 'block',
    // ...
);
AvailabilitySynchronizationService->synchronize($syncCommand, $userId);
```

---

### Seçenek B: Dual Authority (Mevcut Durum — SAAB Onay Gerekli)

> Hem `ReservationService` hem `AvailabilitySyncService` yazar. `source_system` ile ayrılır.

**Avantajları:**
- Mevcut kodu değiştirmeye gerek yok
- Hızlı implementasyon

**Dezavantajları:**
- Çift write authority — race condition riski
- `source_system` tutarsızlığı
- Unique constraint zorunlu (MUST 1)

**Risk:** MUST 1 ve MUST 2 olmadan determinism garantisi yok.

---

### Seçenek C: ReservationService Write Koru, SyncService Read-Only

> `ReservationService` yazar. `AvailabilitySyncService` sadece okur ve kanallara sync eder.

**Avantajları:**
- Mevcut kodu değiştirmeye gerek yok
- Channel sync çalışır

**Dezavantajları:**
- Kanal sync için ayrı mekanizma gerekir
- `source_system` ayrımı karmaşık

---

## 6. SAAB Değerlendirmesi — Öneri

**Öneri: Seçenek A — Tek Canonical Materializer**

**Gerekçe:**
1. Decision 4.1 Option B = materialized canonical state → tek yazı authority
2. Event backbone = reservation'dan availability'e tek geçiş noktası
3. `source_system = 'canonical'` tutarlılığı
4. Channel sync = materialized state okunarak yapılır

**Ancak:** Seçenek B (mevcut durum) MUST 1 + MUST 2 implementasyonu tamamlandıktan sonra geçici olarak kabul edilebilir.

**Transition Planı:**
```
Mevcut: ReservationService → PropertyAvailability (source='internal')
    ↓ MUST 1+2 tamamlandı
Geçiş: Her iki path de çalışır, unique constraint korur
    ↓ Decision 4.2 sonrası
Hedef: Sadece AvailabilitySyncService → PropertyAvailability (source='canonical')
```

---

## 7. LIFECYCLE-DEBT — ReservationCancelledEvent

**Problem:** `cancelReservation()` içinde availability release kodu yok.

**Mevcut:**
```php
// ReservationService.php — cancelReservation()
$reservation->update(['reservation_state' => ReservationState::CANCELLED]);
// ❌ PropertyAvailability::updateOrCreate YOK
event(new ReservationCancelledEvent(...)); // Event tetikleniyor ama availability yazılmıyor
```

**Çözüm:** `cancelReservation()` içinde availability release eklemek VEYA `ReservationCancelledEvent` listener'ı eklemek.

**Öneri:** `ProcessReservationCancelled::handle()` içine availability release eklenmeli.

---

## 8. Decision 4.2 — Triggering Events Framework

### 8.1 Onaylanan Triggering Events

| # | Event | Trigger Noktası | Availability Action |
|---|-------|----------------|---------------------|
| 1 | `ReservationCreatedEvent` | `ReservationService::createReservation()` | Block dates |
| 2 | `ReservationModifiedEvent` | `ReservationService::modifyReservation()` | Release old + Block new |
| 3 | `ReservationCancelledEvent` | `ReservationService::cancelReservation()` | Release dates |
| 4 | Manual Block | `BlockCalendarDatesAction` | Block |

### 8.2 Triggering Matrix

```
Rezervasyon Oluştu
    ↓
ReservationCreatedEvent
    ↓
PropertyAvailability → Block (source='canonical')
    ↓
SynchronizeAvailabilityJob → Channel Sync

Rezervasyon Değişti
    ↓
ReservationModifiedEvent
    ↓
PropertyAvailability → Release old + Block new (source='canonical')
    ↓
SynchronizeAvailabilityJob → Channel Sync

Rezervasyon İptal
    ↓
ReservationCancelledEvent
    ↓
PropertyAvailability → Release (source='canonical')
    ↓
SynchronizeAvailabilityJob → Channel Sync
```

---

## 9. Discovery Sonuçları

| # | Bulgu | Durum | Action |
|---|-------|-------|--------|
| 1 | Çift write path problemi | ⚠️ CRITICAL | Decision 4.2 içinde çözüm gerekli |
| 2 | `ReservationService` direkt yazıyor | ⚠️ Mevcut | Option A veya B SAAB kararı |
| 3 | `AvailabilitySyncService` standby | ⚠️ Bağlı değil | Pipeline'a bağlanmalı |
| 4 | `ReservationCancelledEvent` — availability release yok | ❌ LIFECYCLE-DEBT | Acil çözüm |
| 5 | `ReservationCompletedEvent` yok | ⚠️ Eksik | Backlog |
| 6 | Channel sync event backbone'a bağlı değil | ❌ | Pipeline'a bağlanmalı |

---

## 10. SAAB Kararı İçin Öneriler

### Decision 4.2 — APPROVED with Conditions

**Triggering Events:** Aşağıdaki event'ler availability materialization'ı tetiklemeli:

| Event | Trigger | Availability Action |
|-------|---------|---------------------|
| `ReservationCreatedEvent` | ✅ | Block |
| `ReservationModifiedEvent` | ✅ | Release + Block |
| `ReservationCancelledEvent` | ✅ | Release |
| Manual Block | ✅ | Block |

### Koşullar:

1. **Çift write authority çözümü:** MUST — Option A (tek materializer) veya Option B (dual authority + MUST 1+2)
2. **LIFECYCLE-DEBT fix:** `cancelReservation()` → availability release eklenmeli
3. **Channel sync pipeline:** `AvailabilitySyncService` → `ProcessReservationCreated::handle()` içine bağlanmalı
4. **Unique constraint:** MUST 1 — `UNIQUE(property_id, date, tenant_id)` zorunlu
