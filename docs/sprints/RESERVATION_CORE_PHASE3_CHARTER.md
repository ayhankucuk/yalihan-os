# RESERVATION_CORE Phase 3 — Conflict Detection Charter

**Charter Tarihi:** 2026-08-06
**Hazırlayan:** WenOX (Discovery)
**Sprint Tipi:** Conflict Detection Capability
**Önkoşul:** ✅ RESERVATION_CORE Phase 2 CLOSED (2026-08-06)

---

## Misyon

YALIHAN, aynı mülk için çakışan tarih aralıklarını tenant-safe, transaction-safe ve deterministik biçimde tespit edip engelleyebiliyor.

---

## Başarı Sorusu

> YALIHAN, aynı mülk için çakışan tarih aralıklarını tenant-safe, transaction-safe ve deterministik biçimde tespit edip ikinci rezervasyonu engelleyebiliyor mu?

---

## Mevcut Durum (Discovery Findings)

### 1. Date Semantics — HALKLARAK

| Field | Semantics | Kanıt |
|-------|-----------|-------|
| `start_date` | Check-in günü — **dahil** | Model definition |
| `end_date` | Check-out günü — **hariç** `[start, end)` | CanonicalAvailabilityService L156-190 |
| Aynı gün çıkış/yeni giriş | **ÇAKIŞMA YOK** | `end > start` → checkout günü available |

**Doğrulama:**
```php
// CanonicalAvailabilityService.php:156-190
$cursor = $start->copy();
while ($cursor->lt($end)) {  // lt = less than, NOT lte
    $dates[] = $cursor->format('Y-m-d');
    $cursor->addDay();
}
// end_date günü dahil DEĞİL
```

### 2. Conflict Detection — MEVCUT (Phase 2'den)

**Yer:** `ReservationService::createReservation()` (satır 79-94)

```php
// Overlap check — confirmed/pending reservations block dates.
$overlapQuery = PropertyReservation::where('property_id', $propertyId)
    ->where('start_date', '<', $end->format('Y-m-d'))   // new.start < existing.end
    ->where('end_date', '>', $start->format('Y-m-d'))   // new.end > existing.start
    ->whereNotIn('reservation_state', [
        ReservationState::CANCELLED->value,
        ReservationState::COMPLETED->value,
        ReservationState::NO_SHOW->value,
    ]);  // PENDING ve CONFIRMED çakışma üretir

if ($overlapQuery->lockForUpdate()->count() > 0) {
    throw new Exception("Conflict detected: The selected dates overlap with an existing reservation.");
}
```

### 3. Availability Blocking Conflict — MEVCUT

**Yer:** `CanonicalAvailabilityService::blockDateRange()` (satır 202-234)

```php
// Priority tier comparison
if (!$rec->is_available && $rec->priority_tier <= $priorityTier) {
    // Conflict detected — existing block prevents new block
    $conflictReasonCode = $this->resolveConflictReasonCode(...);
    event(new PropertyAvailabilityConflictDetectedEvent(...));
    return ['success' => false, 'status' => 'CONFLICT_REJECTED', ...];
}
```

### 4. Priority Tiers — KANONIK

| Tier | Value | Açıklama |
|------|-------|----------|
| `TIER_MAINTENANCE` | 1 | Highest — Safety/Legal/Repair |
| `TIER_RESERVATION` | 2 | Confirmed Internal Guest |
| `TIER_OWNER_BLOCK` | 3 | Property Owner Personal Use |
| `TIER_EXTERNAL_SYNC` | 4 | iCal/Channel Sync |
| `TIER_HOLD_PENDING` | 5 | Temporary checkout hold |

**Kural:** Düşük numara = yüksek öncelik. `priority_tier <=` = conflict.

### 5. State Participation Matrix

| State | Overlap Üretir mi? | Availability Bloke mi? |
|-------|-------------------|------------------------|
| `PENDING` | ✅ Evet | ❌ Hayır (bloke yok) |
| `CONFIRMED` | ✅ Evet | ✅ Evet |
| `CANCELLED` | ❌ Hayır | ❌ Hayır (blok zaten freed) |
| `COMPLETED` | ❌ Hayır | ❌ Hayır (tarih geçmiş) |
| `NO_SHOW` | ❌ Hayır | ❌ Hayır |

### 6. Events — MEVCUT

| Event | Kullanım | Durum |
|-------|----------|-------|
| `PropertyAvailabilityConflictDetectedEvent` | blockDateRange conflict | ✅ Var |
| `PropertyAvailabilityBlockedEvent` | Block başarılı | ✅ Var |
| `PropertyAvailabilityUnblockedEvent` | Block freed | ✅ Var |

### 7. Concurrency — MEVCUT

```php
// ReservationService::createReservation()
DB::transaction(function () {
    // ...
    if ($overlapQuery->lockForUpdate()->count() > 0) {  // Satır 92
        throw new Exception(...);
    }
    // ...
});

// CanonicalAvailabilityService::blockDateRange()
$existingRecords = PropertyAvailability::where(...)
    ->lockForUpdate()  // Satır 195
    ->get();
```

---

## Gap Analysis — Eksik Olanlar

### G1: Unified Conflict Detection API

**Sorun:** İki ayrı conflict check mekanizması var:
1. `ReservationService::createReservation()` — reservation overlap
2. `CanonicalAvailabilityService::blockDateRange()` — availability tier conflict

**İhtiyaç:** Tek `ConflictDetectionService` — her iki kaynağı birleştiren.

### G2: Conflict Response Policy

**Sorun:** Mevcut sistem sadece "throw exception". Override yetkisi tanımlı değil.

**İhtiyaç:**
- Override yetkisi: kim yapabilir?
- Which block types overridable?
- Audit trail for overrides

### G3: Conflict Event Enhancement

**Sorun:** `PropertyAvailabilityConflictDetectedEvent` sadece availability conflict için. Reservation conflict için ayrı event yok.

**İhtiyaç:**
- `ReservationConflictDetectedEvent`
- Veya tek `AvailabilityConflictEvent` her iki senaryo için

### G4: Conflict Resolution UI

**Sorun:** Conflict tespit ediliyor ama yönetilebilir değil.

**İhtiyaç:** (Phase 3 kapsamı dışı — sonraki sprint)

---

## Phase 3 Scope

### In Scope (P0)

| Task | Açıklama |
|------|----------|
| P0.1 | ConflictDetectionService — unified conflict API |
| P0.2 | ReservationConflictDetectedEvent — new event |
| P0.3 | Conflict check for PENDING vs CONFIRMED overlap |
| P0.4 | Override policy with audit trail |
| P0.5 | Conflict listener for observability |
| P0.6 | Tenant isolation enforcement |

### In Scope (P1)

| Task | Açıklama |
|------|----------|
| P1.1 | Availability vs Reservation conflict merge |
| P1.2 | Override conflict test coverage |
| P1.3 | Drift detection for orphaned conflicts |

### Out of Scope (Phase 3)

| Task | Neden |
|------|-------|
| Channel Manager | Ayrı capability |
| Airbnb/Booking adapter | Ayrı capability |
| Operational Calendar UI | Ayrı capability |
| Auto-override | Ayrı karar gerektirir |

---

## Conflict Source Matrix

```
Kaynak A vs Kaynak B        | Sonuç
Reservation vs Reservation    | Conflict (PENDING + CONFIRMED)
Reservation vs Owner Block    | Conflict
Reservation vs Maintenance    | Conflict
Reservation vs External (iCal)| Conflict
Reservation vs Completed     | No conflict
Reservation vs Cancelled      | No conflict
Reservation vs No-show        | No conflict
Owner Block vs Owner Block    | İkinci rejected
Owner Block vs Maintenance    | İkinci rejected
Maintenance vs Maintenance    | İkinci rejected
External vs External          | Channel Manager kapsamı
```

---

## Date Overlap Rule (Canonical)

```
Definition: Two reservations R1 [s1, e1) and R2 [s2, e2) overlap iff:
  s1 < e2 AND s2 < e1

Equivalently: NOT (e1 <= s2 OR e2 <= s1)

Examples:
  R1: [Jun 10, Jun 13) — 3 nights
  R2: [Jun 13, Jun 16) — 3 nights
  → NO OVERLAP (R1 checkout Jun 13, R2 checkin Jun 13)
  → e1 = Jun 13, s2 = Jun 13 → e1 <= s2 → TRUE → no overlap

  R1: [Jun 10, Jun 14)
  R2: [Jun 13, Jun 16)
  → OVERLAP (Jun 13, 14 ortak)
  → s1=Jun 10 < e2=Jun 16 TRUE AND s2=Jun 13 < e1=Jun 14 TRUE → overlap
```

---

## Implementation Plan

### Epoch 1: ConflictDetectionService (P0.1)

```php
interface ConflictDetectionServiceContract
{
    public function detectConflicts(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate,
        ?int $excludeReservationId = null
    ): ConflictReport;

    public function hasConflict(
        int $tenantId,
        int $propertyId,
        string $startDate,
        string $endDate
    ): bool;
}

class ConflictReport
{
    public bool $hasConflict;
    public array $conflictingReservations; // PropertyReservation[]
    public array $conflictingBlocks;       // PropertyAvailability[]
    public array $conflictDates;
}
```

### Epoch 2: ReservationConflictDetectedEvent (P0.2)

```php
class ReservationConflictDetectedEvent
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $propertyId,
        public readonly int $newReservationId,
        public readonly string $startDate,
        public readonly string $endDate,
        public readonly array $conflictingReservationIds,
        public readonly array $conflictDates,
    ) {}
}
```

### Epoch 3: Override Policy (P0.4)

```php
// Override rules
const OVERRIDE_ALLOWED = [
    'maintenance' => false,  // Never overridable
    'owner_block' => ['admin', 'owner'],
    'reservation' => ['admin'],
    'external' => ['admin', 'channel_manager'],
];
```

---

## Zorunlu Test Listesi

| # | Test | Açıklama |
|---|------|----------|
| T1 | overlapping_dates_rejected | R1 [Jun 10-13), R2 [Jun 12-15) → reject |
| T2 | checkout_checkin_no_conflict | R1 [Jun 10-13), R2 [Jun 13-16) → OK |
| T3 | pending_vs_confirmed_conflict | PENDING overlap CONFIRMED → reject |
| T4 | pending_vs_pending_conflict | PENDING overlap PENDING → reject |
| T5 | cancelled_no_conflict | CANCELLED rezervasyon çakışma üretmez |
| T6 | completed_no_conflict | COMPLETED rezervasyon çakışma üretmez |
| T7 | cross_tenant_no_conflict | Tenant A vs Tenant B → ignore |
| T8 | maintenance_block_conflict | Reservation vs MAINTENANCE → reject |
| T9 | owner_block_conflict | Reservation vs OWNER_BLOCK → reject |
| T10 | same_reservation_no_conflict | Update kendi rezervasyonu → OK |
| T11 | override_creates_audit | Admin override → audit trail |
| T12 | override_requires_authorization | Non-admin → exception |

---

## Artisan Komut Önerisi

```bash
# Conflict check
php artisan conflict:check {tenantId} {propertyId} {startDate} {endDate}

# Conflict report
php artisan conflict:report {tenantId} [--since=2026-01-01]
```

---

## Sonraki Adımlar

```
Phase 3 Conflict Detection
        ↓
Phase 3.1: ConflictDetectionService (E1)
        ↓
Phase 3.2: Events + Override (E2)
        ↓
Phase 3.3: Tests + Certification (E3)
        ↓
Phase 4: Operational Calendar
        ↓
Phase 5: Channel Manager
```

---

## Kapanış Kriteri

| Kriter | Hedef |
|--------|-------|
| ConflictDetectionService | Tüm conflict kaynaklarını birleştirir |
| ReservationConflictDetectedEvent | Yeni event, mevcutleri tamamlar |
| Test coverage | 12/12 mandatory test PASS |
| Tenant isolation | Cross-tenant conflict engelli |
| Override policy | Audit trail ile dokümante |

---

*SAAB onayı bekleniyor.*
