# Decision 4.1 — Canonical Source Discovery Report

**Type:** Repository Architecture Analysis
**Baseline:** `7e00da1`
**Date:** 2026-08-15
**Scope:** Availability model, write/read paths, reservation lifecycle
**Model:** Claude Sonnet 4.6

---

## 1. Repository Model Map

### 1.1 Ana Tablolar

| Tablo | Rol | tenant_id | Canonical Source mu? |
|-------|-----|-----------|---------------------|
| `property_reservations` | Business fact — rezervasyon kaydı | ✅ Var | ✅ Business Fact |
| `property_availabilities` | Materialized state — tarih bazlı blok durumu | ✅ Var | ⚠️ Materialized Projection |
| `ilan_takvim_sync` | Channel config — platform credential + listing ID | ❌ Yok | ❌ Kanal Config |
| `channel_sync_executions` | Immutable event log — sync audit trail | ✅ Var | ⚠️ Audit Event |

### 1.2 PropertyAvailability Model

```php
// app/Models/PropertyAvailability.php
protected $fillable = [
    'property_id',    // FK → Ilan
    'date',           // Tarih
    'is_available',   // boolean — true=open, false=blocked
    'block_reason',   // 'reservation' | 'maintenance' | 'manual' | null
    'source_system',   // 'internal' | 'canonical' | 'external' | 'conflict_resolution'
    'external_ref',    // Dış kanal referansı
    'reservation_id',  // FK → PropertyReservation (nullable)
];
```

**Kritik Bulgu:** `tenant_id` **fillable değil** — `HasCountryScope` trait üzerinden global scope ile erişiliyor.

### 1.3 PropertyReservation Model

```php
// app/Models/PropertyReservation.php
// tenant_id FILLABLE
protected $fillable = [
    'tenant_id',
    'property_id',
    'ilan_id',
    'start_date',
    'end_date',
    'reservation_state',  // confirmed | cancelled | completed | override
    // ...
];
```

---

## 2. Write Path Analizi

### 2.1 Path A: ReservationService → Internal Block

**Dosya:** `app/Services/ReservationService.php:597`

```php
// createReservation() içinde — reservation oluşturulduktan sonra
foreach ($dates as $dateStr) {
    PropertyAvailability::updateOrCreate(
        ['property_id' => $reservation->property_id, 'date' => $dateStr],
        [
            'is_available'   => false,
            'block_reason'   => 'reservation',
            'source_system'  => 'internal',
            'reservation_id' => $reservation->id,
        ]
    );
}
// → Ardından ReservationCreatedEvent tetiklenir
```

**Mantık:** Rezervasyon oluşturuldu = tarihler blocked olmalı.

### 2.2 Path B: AvailabilitySynchronizationService → Canonical Sync

**Dosya:** `app/Application/ChannelManager/Services/AvailabilitySynchronizationService.php:109`

```php
// synchronize() — canonical availability write
PropertyAvailability::updateOrCreate(
    [
        'property_id' => $command->propertyId,
        'date'        => $date,
        'tenant_id'   => $command->tenantId,  // Explicit tenant scope
    ],
    [
        'is_available'   => $command->available,
        'block_reason'   => $command->isBlocking() ? $command->blockReason : null,
        'source_system'  => 'canonical',
        'reservation_id' => $command->isBlocking() ? $command->reservationId : null,
    ]
);
```

**Mantık:** Canonical event sonrası materialized state güncellenir.

### 2.3 Path C: BlockCalendarDatesAction → Manual Block

**Dosya:** `app/Actions/Admin/Calendar/BlockCalendarDatesAction.php:13`

```php
// Manual block — admin tarafından
$block = PropertyAvailability::updateOrCreate(
    ['property_id' => $ilanId, 'date' => $date],
    [
        'is_available'  => false,
        'block_reason'  => 'manual',
        'source_system' => 'internal',
    ]
);
```

### 2.4 Write Path Özeti

```
Rezervasyon oluştu
    ↓
ReservationService::createReservation()
    ↓
PropertyAvailability::updateOrCreate() — source='internal'
    ↓
ReservationCreatedEvent (AFTER COMMIT)
    ↓
Listener → ProcessReservationCreated::dispatch()
    ↓
AvailabilitySynchronizationService::synchronize()
    ↓
PropertyAvailability::updateOrCreate() — source='canonical'
    ↓
SynchronizeAvailabilityJob::dispatch() — afterCommit
    ↓
Channel adapter → Booking.com / Airbnb
```

---

## 3. Event Backbone Analizi

### 3.1 Canonical Event'ler

| Event | Tetiklenme Noktası | Downstream Etkisi |
|-------|---------------------|-------------------|
| `ReservationCreatedEvent` | `ReservationService::createReservation()` — after commit | Guest comm + Availability sync |
| `ReservationModifiedEvent` | `ReservationService::modifyReservation()` — after commit | Availability re-block |
| `ReservationCancelledEvent` | `ReservationService::cancelReservation()` — after commit | Availability release |

### 3.2 Event Envelope

```php
ReservationCreatedEvent {
    reservationId: int
    tenantId: int
    ilanId: int
    startDate: string    // Business fact
    endDate: string      // Business fact
    // ... guest data
}
```

**Kritik:** Event, `start_date` ve `end_date` string'lerini taşır — tarih array'i availability tablosundan değil, reservation kaydından türetilir.

---

## 4. Kaynak Sistemi Karşılaştırması

### 4.1 property_availabilities Kaynak Analizi

| source_system | Açıklama | Business Fact mu? |
|--------------|----------|-------------------|
| `'internal'` | Manual block veya internal işlem | ⚠️ User action |
| `'canonical'` | AvailabilitySyncService sync | ⚠️ Canonical projection |
| `'external'` | Dış kanal (Booking/Airbnb) — **şu an kullanılmıyor** | ❌ Kanal verisi |
| `'conflict_resolution'` | Conflict çözümü sonrası | ⚠️ Reconciliation |

**Mevcut durum:** `'external'` source_system'i **kodda yazılıyor** ancak **kullanılmıyor**. Availability sadece internal/canonical üzerinden yönetiliyor.

### 4.2 IlanTakvimSync Rolü

```php
// IlanTakvimSync — kanal credential + config
protected $fillable = [
    'ilan_id',              // FK → Ilan
    'platform',             // 'airbnb' | 'booking'
    'external_listing_id',  // Platform listing ID
    'is_sync_active',       // Sync enabled
    'auto_sync',            // Automatic sync
    // credentials...
];
```

**Sonuç:** `IlanTakvimSync` sadece **kanal konfigürasyonu** saklar. Availability verisi barındırmaz.

---

## 5. A/B/C Seçenek Değerlendirmesi

### Option A: Business Truth Kaynağı

> `property_availabilities` ham business truth olur.

**Kanıt:**
- ❌ Yok — Mevcut kodda `property_availabilities` doğrudan yazılmıyor
- ❌ Reservation lifecycle event'leri availability tablosunu **türetiyor**
- ❌ `source_system` = `'canonical'` — zaten bir **türetilmiş tablo** olduğunu belirtiyor

**Değerlendirme:** ❌ **REDDED** — Mevcut repository gerçekliğiyle uyumsuz.

---

### Option B: Materialized Canonical State ✅

> `property_availabilities`, reservation/block/lifecycle **business facts**'lerinden deterministik olarak türetilen materialized canonical state olur.

**Kanıt:**
```
✅ property_reservations.start_date + end_date → business facts
✅ ReservationCreatedEvent → event-driven trigger
✅ PropertyAvailability::updateOrCreate() → deterministic materialization
✅ source_system = 'canonical' → projection marker
✅ Channel adapter'lar availability TABLOSUNU OKUR, doğrudan reservation'ı değil
```

**Detay:**
- Rezervasyon = business fact (create/cancel/modify)
- Event backbone = fact'tan event'e dönüşüm
- PropertyAvailability = event sonrası materialized state
- Channel sync = materialized state'in dış kanal projection'ı

**Değerlendirme:** ✅ **EVIDENCE-BACKED** — Mevcut repository gerçekliğiyle tam uyumlu.

---

### Option C: Sadece Kanal Projection'ı

> `property_availabilities` sadece dış kanal senkronizasyonu için kullanılır, canonical state değildir.

**Kanıt:**
- ❌ `/check-availability` endpoint'leri `property_availabilities`'i OKUR
- ❌ Conflict detection `property_availabilities`'i kullanır
- ❌ `AvailabilitySyncAggregate` bu tabloyu okur ve yazar

**Değerlendirme:** ❌ **REDDED** — Tablo aynı zamanda internal read path'lerde kullanılıyor.

---

## 6. SAAB Mimari Yönü — Onaylanan Model

```
┌─────────────────────────────────────────────────────────────┐
│  BUSINESS FACTS (Event Backbone)                             │
│                                                              │
│  property_reservations                                       │
│    ├── reservation_state = confirmed/cancelled/override        │
│    ├── start_date / end_date                                │
│    └── tenant_id + property_id                               │
│                                                              │
│  BlockCalendarDatesAction                                    │
│    └── manual block (business action)                        │
└─────────────────────────────────────────────────────────────┘
                         │ deterministik türetim
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  MATERIALIZED CANONICAL STATE                               │
│                                                              │
│  property_availabilities                                     │
│    ├── is_available = false/true                            │
│    ├── block_reason = 'reservation'|'manual'|'maintenance'   │
│    ├── source_system = 'canonical'|'internal'                │
│    ├── reservation_id → FK to property_reservations          │
│    └── tenant_id + property_id + date (unique constraint)    │
└─────────────────────────────────────────────────────────────┘
                         │ projection
                         ▼
┌─────────────────────────────────────────────────────────────┐
│  EXTERNAL CHANNEL PROJECTION                                 │
│                                                              │
│  Booking.com / Airbnb / Channex                              │
│    └── pushAvailability() — read from materialized state      │
└─────────────────────────────────────────────────────────────┘
```

---

## 7. Unique Constraint — MUST 1 ile İlişki

**Mevcut durum:** `property_availabilities` tablosunda **unique constraint yok**.

**Sorun:** Aynı `(property_id, date)` kombinasyonu iki farklı path tarafından yazılabilir:

```
Path A: ReservationService → source='internal'
Path B: AvailabilitySyncService → source='canonical'
```

Her iki path de aynı tarihi `updateOrCreate` ile yazar. Unique constraint olmadan race condition riski vardır.

**SAAB 4.5 MUST 1:**
```sql
ALTER TABLE property_availabilities
ADD CONSTRAINT uq_property_availabilities_tenant_date
UNIQUE (property_id, date, tenant_id);
```

**Bu constraint, Option B seçildiğinde ZORUNLUDUR** — çünkü materialized state'in deterministik olması gerekir.

---

## 8. LIFECYCLE-DEBT Etkisi — Override Path

**Mevcut durum:** Override path'inde conflict rezervasyon **doğrudan DB UPDATE** edilir ve `ReservationCancelledEvent` **tetiklenmez**.

**Problem:**
```
Override → Conflict rezervasyon DB::update(state=CANCELLED)
    ↓
PropertyAvailability HÂLÂ blocked (event yok → sync yok)
    ↓
Booking.com hâlâ "blocked" görür
```

**SAAB Kararı:** Option A — Override path `ReservationCancelledEvent` üretmeli.

**Decision 4.1 açısından:** Event backbone tamamlandığında, override path de event üretmeli → availability otomatik olarak release olmalı.

---

## 9. Discovery Sonuç Özeti

| # | Bulgu | Kanıt |
|---|-------|-------|
| 1 | `property_availabilities` ham business fact değil | `source_system = 'canonical'` + event-driven write |
| 2 | Rezervasyon = business fact | `property_reservations` + event backbone |
| 3 | Availability = deterministic materialization | `ReservationService` → event → `AvailabilitySyncService` → tablo |
| 4 | IlanTakvimSync = kanal config, NOT availability | Sadece credential + listing_id saklar |
| 5 | Unique constraint eksikliği race condition riski | MUST 1 — `UNIQUE(property_id, date, tenant_id)` |
| 6 | Override path event üretmiyor | LIFECYCLE-DEBT — Decision 4.1 sonrası çözülecek |

---

## 10. SAAB Kararı İçin Öneri

**Seçenek B: Materialized Canonical State**

```
property_availabilities = reservation/block facts'tan
deterministik türetilen materialized canonical state
```

**Gerekçe:**
1. Mevcut repository gerçekliğiyle tam uyumlu
2. Event backbone korunur
3. Channel adapter'lar materialized state'i okur
4. Unique constraint + race condition çözümü = deterministic guarantee
5. SAAB 4.5 MUST 1 bu modelin invariant'ı olur

**Sonraki Adımlar:**
1. SAAB 4.1 Decision — Option B onaylanır
2. MUST 1 + MUST 2 implementasyonu başlar
3. Override path → `ReservationCancelledEvent` üretir (LIFECYCLE-DEBT)
4. Availability Sync Charter APPROVED olur
