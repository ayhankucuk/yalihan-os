# Decision 4.4 — Idempotency Discovery Report

**Type:** Repository Architecture Analysis
**Parent:** SAAB Decision 4.1 + 4.2 + 4.3
**Baseline:** `fa9b189`
**Date:** 2026-08-15
**Scope:** eventId, correlationId, idempotency keys, execution records, queue job replay
**Model:** Claude Sonnet 4.6

---

## 1. Temel Kavram Ayrımı

### 1.1 Idempotency vs Concurrency Locking

| Kavram | Tanım | Amaç | Mekanizma |
|--------|-------|------|-----------|
| **Idempotency** | Aynı girdinin tekrar işlenmesi aynı sonucu üretir | Duplicate request koruması | `eventId`, `idempotency_key`, `correlationId` |
| **Concurrency Locking** | Paralel execution'ların çakışmasını önlemek | Race condition koruması | `lockForUpdate()`, `DB::transaction()` |

**Kritik Ayrım:** İkisi farklı problemleri çözer ve birlikte kullanılır.

---

## 2. Idempotency Mekanizmaları

### 2.1 ChannelSyncExecution — Idempotency Key

```php
// SynchronizeAvailabilityCommand.php:45-54
public function getIdempotencyKey(): string
{
    if ($this->idempotencyKey) {
        return $this->idempotencyKey;
    }
    // Format: tenantId:propertyId:reservationId:operation:startDate:endDate
    return "{$this->tenantId}:{$this->propertyId}:{$this->reservationId}:{$this->operation}:{$start}:{$end}";
}
```

**Yapı:** Composite idempotency key — aynı operation için aynı key üretilir.

### 2.2 findExistingSync — Idempotency Check

```php
// AvailabilitySynchronizationService.php:287-292
private function findExistingSync(string $idempotencyKey, int $tenantId): ?ChannelSyncExecution
{
    return ChannelSyncExecution::where('idempotency_key', $idempotencyKey)
        ->where('tenant_id', $tenantId)
        ->orderBy('id')  // Deterministic: en eski kayıt
        ->first();
}
```

**Akış:**
```
Same idempotency key arrives
    ↓
findExistingSync() → kayıt bulundu
    ↓
buildResultFromExistingSync() → mevcut sonucu döndür
    ↓
NO new execution created ✅
```

### 2.3 ChannelSyncExecution — Immutable Audit Record

```php
// ChannelSyncExecution.php
// Immutable record — never update after creation
// Replay creates a NEW execution with a new idempotency key

// Status lifecycle: dispatched → processing → completed/failed
public function scopePending($query)
{
    return $query->whereIn('status', ['dispatched', 'processing']);
}
```

---

## 3. Reservation Event Idempotency

### 3.1 ReservationEventLog — eventId Based Idempotency

```php
// YdlReservationOrchestrator.php
// R1-T6: Duplicate event_id → idempotent no-op

if ($this->eventLog->exists($token->eventId)) {
    return YdlReservationEvidence::idempotent($token->eventId, [
        'reason' => "Event {$token->eventId} already in log",
    ]);
}
```

**Pattern:** eventIdempotency — aynı eventId iki kez işlenirse no-op döndürür.

### 3.2 eventId Scope

| Context | eventId Kullanımı | Idempotency Türü |
|---------|------------------|------------------|
| Reservation orchestration | `YdlPublishApprovalToken.eventId` | Event log duplicate check |
| Channel sync | `idempotency_key` | ChannelSyncExecution lookup |
| Correlation | `correlationId` | Execution chain tracing |

---

## 4. Concurrency Locking Mekanizmaları

### 4.1 lockForUpdate — Pessimistic Row Lock

**Kullanım Alanları:**

| Servis | Amaç | Satır |
|--------|------|-------|
| `ReservationService::createReservation()` | Overlap check | Line 64 |
| `ReservationService::modifyReservation()` | Overlap check | Line 557 |
| `ReservationService::cancelReservation()` | State transition | Line 448 |
| `AvailabilitySynchronizationService` | Availability materialization | Line 92 |
| `BookingReservationIngestService` | External reservation lookup | Line 170 |
| `IlanNoGenerator` | Sequence generation | Line 83 |
| `RefSequence::getNextSequence()` | Thread-safe sequence | Line 65 |

### 4.2 lockForUpdate Detay

```php
// ReservationService.php:59-65
$overlapCount = PropertyReservation::where('property_id', $propertyId)
    ->where('start_date', '<', $end->format('Y-m-d'))
    ->where('end_date', '>', $start->format('Y-m-d'))
    ->where('reservation_state', '!=', 'cancelled')
    ->lockForUpdate() // Prevent concurrent reading
    ->count();

if ($overlapCount > 0) {
    throw new Exception("Conflict detected...");
}
```

**Amaç:** Paralel transaction'ların aynı overlapping rezervasyonu görmesini önlemek.

### 4.3 AvailabilitySyncService — lockForUpdate

```php
// AvailabilitySynchronizationService.php:88-93
DB::transaction(function () use ($command, &$conflictDates, &$blockedDates) {
    foreach ($command->getDates() as $date) {
        $existing = PropertyAvailability::where('property_id', $command->propertyId)
            ->where('date', $date)
            ->where('tenant_id', $command->tenantId)
            ->lockForUpdate()
            ->first();
        // ...
    }
});
```

**Amaç:** Race condition koruması — SAAB 4.5 MUST 2.

---

## 5. Queue Job Idempotency

### 5.1 SynchronizeAvailabilityJob — Replay Safety

```php
// SynchronizeAvailabilityJob.php
public function handle(AvailabilitySynchronizationService $service): void
{
    // Check if already processed
    if ($syncRecord->processed_at !== null) {
        return $this->buildResultFromExistingSync($syncRecord);
    }
    // Process...
}
```

**Design:** Replay = yeni execution, mutation yok.

### 5.2 ProcessReservationCreated — Event Idempotency

```php
// ReservationService.php — Idempotency via eventId
// Aynı eventId iki kez gelirse:
// 1. Event log'da bulunur
// 2. Idempotent no-op döndürülür
// 3. Reservation çift oluşturulmaz
```

### 5.3 AfterCommit Dispatch

```php
// AvailabilitySynchronizationService.php:137
SynchronizeAvailabilityJob::dispatch($syncRecord->id)
    ->afterCommit();  // Transaction commit sonrası çalışır

// ReservationService.php:144
event(new ReservationCreatedEvent(...));
// AFTER transaction commits — event fires outside closure
```

**Kritik Güvence:** Event/job, transaction commit edilmeden önce çalışmaz.

---

## 6. Idempotency Türleri Matrisi

| Tür | Mechanism | Scope | Duplicate Sonucu |
|-----|-----------|-------|----------------|
| **Event Idempotency** | `eventId` check in event log | Reservation lifecycle | Idempotent no-op |
| **Sync Idempotency** | `idempotency_key` in ChannelSyncExecution | Channel sync | Existing result returned |
| **Correlation Idempotency** | `correlationId` passed to external API | Booking/Airbnb | External idempotency |
| **Conflict Detection** | `lockForUpdate()` + overlap check | Concurrent requests | Exception thrown |
| **Replay Safety** | Immutable execution record | Job retry | New record created |

---

## 7. channel_sync_executions Tablosu

### 7.1 Idempotency Key Yapısı

```php
// SynchronizeAvailabilityCommand
idempotency_key = "tenantId:propertyId:reservationId:operation:startDate:endDate"
```

**Örnek:** `1:100:42:block:2026-09-01:2026-09-05`

### 7.2 Status Lifecycle

```
dispatched → processing → completed | completed_with_conflicts | failed
```

### 7.3 Immutable Record Design

```php
// ChannelSyncExecution — created once, never mutated
// Replay = yeni kayıt, eski kayıt korunur
```

---

## 8. OTA Idempotency Semantics (SAAB 4.5 MUST 3)

### 8.1 Booking.com Idempotency

```php
// BookingChannelAdapter — correlationId passed to OTA API
correlationId: 'sync-{date}-{random}'
// OTA standard: same correlationId = same result (at-least-once)
```

**Sınırlamalar:**
- OTA idempotency garantisi **at-least-once**, exactly-once **değil**
- Retry sonrası aynı sonuç garanti edilir
- Aynı gün içinde aynı correlationId → idempotent

### 8.2 Airbnb/Channex Idempotency

```php
// AirbnbChannelAdapter — correlationId via transport
// Channel-specific semantics
```

---

## 9. SAAB 4.4 — Idempotency Findings

### 9.1 Discovery Results

| # | Bulgu | Evidence |
|---|-------|----------|
| 1 | Event idempotency: `eventId` + event log | `YdlReservationOrchestrator.php` |
| 2 | Sync idempotency: `idempotency_key` | `ChannelSyncExecution` lookup |
| 3 | Concurrency lock: `lockForUpdate()` | Multiple services |
| 4 | Replay safety: immutable records | `ChannelSyncExecution` |
| 5 | AfterCommit: transaction-safe dispatch | `->afterCommit()` |
| 6 | OTA semantics: at-least-once | MUST 3 doc |

### 9.2 Two-Layer Idempotency

```
┌─────────────────────────────────────────────────────────────┐
│  Layer 1: YALIHAN Internal Idempotency                          │
│                                                              │
│  eventId / idempotency_key → duplicate request → no-op         │
│  lockForUpdate() → concurrent request → exception              │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│  Layer 2: External OTA Idempotency                             │
│                                                              │
│  correlationId → OTA API → at-least-once guarantee            │
│  (NOT exactly-once)                                          │
└─────────────────────────────────────────────────────────────┘
```

### 9.3 Concurrency Locking ≠ Idempotency

| Senaryo | Problem | Çözüm |
|---------|---------|--------|
| Aynı event iki kez geldi | Idempotent işlem | `eventId` check |
| İki paralel rezervasyon aynı tarihi istedi | Concurrent overlap | `lockForUpdate()` |
| Job retry — aynı sync tekrar çalıştı | Replay | Immutable record + idempotency key |

---

## 10. Decision 4.4 — Idempotency Kararı

### 10.1 Idempotency Contract

| Tür | Mekanizma | Scope | Garantisi |
|-----|-----------|-------|----------|
| Event idempotency | `eventId` + event log | Internal | Exactly-once |
| Sync idempotency | `idempotency_key` | Internal | Exactly-once |
| OTA idempotency | `correlationId` | External | At-least-once |

### 10.2 Concurrency Lock Contract

| Mekanizma | Kullanım | Garantisi |
|-----------|---------|----------|
| `lockForUpdate()` | Paralel rezervasyon overlap | Serialized execution |
| `DB::transaction()` | Atomic write | All-or-nothing |
| `afterCommit()` | Event/job timing | Post-commit only |

### 10.3 SAAB Guarantees

```
YALIHAN Internal:
├── Event duplicate → idempotent no-op ✅
├── Sync duplicate → existing result returned ✅
└── Concurrent overlap → exception ✅

External OTA:
├── Same correlationId → at-least-once ✅
└── NOT exactly-once ⚠️ (documented)
```

---

## 11. Recommendations

### 11.1 Idempotency — FROZEN

**Decision 4.4: ✅ Idempotency APPROVED**

| Tür | Mechanism | Status |
|-----|-----------|--------|
| Event idempotency | `eventId` + event log | ✅ Implemented |
| Sync idempotency | `idempotency_key` | ✅ Implemented |
| Concurrency lock | `lockForUpdate()` | ✅ Implemented |
| Replay safety | Immutable records | ✅ Implemented |
| OTA semantics | at-least-once | ⚠️ Documented (MUST 3) |

### 11.2 Implementation Notes

| Note | Detail |
|------|--------|
| OTA idempotency | at-least-once, not exactly-once |
| `lockForUpdate()` scope | Sadece kritik overlap check için |
| Immutable records | Replay = yeni kayıt |
| AfterCommit | Event/job transaction sonrası |

### 11.3 Next Decision: 4.5 + 4.6

- **4.5 Tenant Isolation:** tenant_id validation in all paths
- **4.6 Retry/Evidence:** Queue retry + execution audit
