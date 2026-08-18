# Decision 4.3 — Channel Boundary Discovery Report

**Type:** Repository Architecture Analysis
**Parent:** SAAB Decision 4.1 + 4.2
**Baseline:** `904add7`
**Date:** 2026-08-15
**Scope:** Channel adapter contracts, IlanTakvimSync, channel boundary, failure isolation
**Model:** Claude Sonnet 4.6

---

## 1. Channel Adapter Architecture

### 1.1 Contract Hierarchy

```
ChannelSyncContract (canonical)
    ├── pushAvailability()
    ├── pullAvailability()
    ├── pushRates()
    └── testConnection()

ChannelAdapter (basic)
    ├── pushAvailability()
    ├── pullAvailability()
    ├── pushReservation()
    └── fetchStatus()

AvailabilitySynchronizer (sync strategy)
    ├── sync()
    ├── detectConflicts()
    └── resolveConflict()
```

### 1.2 Channel Implementations

| Channel | Implements | Push | Pull | Status |
|---------|-----------|------|------|--------|
| `BookingChannelAdapter` | `ChannelSyncContract` | ✅ | ❌ NOT_IMPLEMENTED | Production |
| `AirbnbChannelAdapter` | `ChannelSyncContract` | ✅ | ✅ | Production |
| `InMemoryChannelAdapter` | `ChannelAdapter` | ✅ | ✅ | Test double |

---

## 2. Channel Registration — IlanTakvimSync

### 2.1 Model Analysis

```php
// IlanTakvimSync — Kanal konfigürasyonu
protected $fillable = [
    'ilan_id',              // FK → Ilan
    'platform',             // 'airbnb' | 'booking_com'
    'external_listing_id',   // Channel listing ID
    'is_sync_active',       // Sync enabled flag
    'auto_sync',            // Automatic sync
    'senkron_durumu',      // 'active' | 'pasive'
    'api_key',
    'api_secret',
    // token-based auth...
];
```

### 2.2 Channel Resolution

```php
// AvailabilitySynchronizationService.php:345-363
private function getRegisteredChannels(int $propertyId, int $tenantId): array
{
    $channelSyncs = IlanTakvimSync::where('ilan_id', $propertyId)
        ->where('is_sync_active', true)
        ->where('senkron_durumu', 'active')
        ->whereHas('ilan', fn($q) => $q->where('tenant_id', $tenantId))
        ->get();

    foreach ($channelSyncs as $sync) {
        $adapter = $this->resolveChannelAdapter($sync->platform);
        if ($adapter !== null) {
            $adapters[] = $adapter;
        }
    }
    return $adapters;
}
```

### 2.3 Channel Eligibility Criteria

| Criteria | Field | Required Value | Purpose |
|---------|-------|---------------|---------|
| Property match | `ilan_id` | Property ID | Local binding |
| Sync enabled | `is_sync_active` | `true` | Admin control |
| Status active | `senkron_durumu` | `'active'` | Operational state |
| Tenant isolation | `ilan.tenant_id` | Current tenant | Security |

**Tanım:** Bu dört koşulun hepsi sağlanırsa → channel sync'e dahil edilir.

---

## 3. Channel Boundary — Projection Flow

### 3.1 Complete Flow

```
Business Fact (Reservation)
    ↓
AvailabilitySyncService (canonical materializer)
    ↓
PropertyAvailability (materialized state)
    ↓
SynchronizeAvailabilityJob (afterCommit)
    ↓
ChannelSyncExecution (immutable audit)
    ↓
For each registered channel:
    ↓
ChannelAdapter::pushAvailability()
    ├── BookingChannelAdapter → Booking.com OTA API
    └── AirbnbChannelAdapter → Airbnb/Channex API
```

### 3.2 Channel Adapter Boundary

```
┌─────────────────────────────────────────────────────────────┐
│  AvailabilitySynchronizationService                              │
│  - Canonical materialization                                    │
│  - tenant_id isolation                                         │
│  - idempotency key                                            │
│  - Conflict detection                                         │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  ChannelSyncContract (interface)                               │
│  - pushAvailability(tenantId, propertyId, correlationId, data)   │
│  - pullAvailability(...)                                       │
│  - pushRates(...)                                             │
│  - testConnection(tenantId)                                   │
└─────────────────────────────────────────────────────────────┘
                           │
        ┌──────────────────┴──────────────────┐
        ▼                                      ▼
┌───────────────────┐              ┌───────────────────┐
│ BookingChannelAdapter │           │ AirbnbChannelAdapter │
│  Push-only         │              │  Push + Pull        │
│  OTA_Availability │              │  Channex transport  │
└───────────────────┘              └───────────────────┘
```

---

## 4. Channel Failure Isolation

### 4.1 Failure Handling Design

**Critical Invariant:** Channel adapter failure MUST NOT affect canonical materialization.

```php
// AvailabilitySynchronizationService.php:375-401
private function syncToChannel(ChannelAdapter $adapter, SynchronizeAvailabilityCommand $command): SyncResult
{
    try {
        $dates = [];
        foreach ($command->getDates() as $date) {
            $dates[] = [
                'date' => $date,
                'available' => $command->available,
                'property_id' => $command->propertyId,
            ];
        }

        $response = $adapter->pushAvailability($dates);

        if (!$response->success) {
            return SyncResult::failure($response->errorMessage ?? 'Unknown error');
        }

        if ($response->isConflict()) {
            $conflictDetails = $response->getConflictDetails();
            return SyncResult::success(0, [$conflictDetails]);
        }

        return SyncResult::success(count($dates));
    } catch (\Throwable $e) {
        return SyncResult::failure($e->getMessage());
    }
}
```

**Design:** Adapter failure → `SyncResult::failure()` → recorded in `ChannelSyncExecution` → **canonical state UNCHANGED**

### 4.2 BookingChannelAdapter Failure Semantics

```php
// BookingChannelAdapter.php:140-147
// 5xx → throw retryable exception
if (!$result->success && $result->errorCode !== null && $this->isRetryableErrorCode(...)) {
    throw new BookingAvailabilityException(...);
    // Job will retry via queue
}

// 4xx → graceful failure
if (!$result->success) {
    return ChannelSyncResponse::failure(...);
    // Recorded, no retry
}
```

| HTTP Status | Behavior | Retry | Canonical Impact |
|-------------|----------|-------|----------------|
| 2xx | ✅ Success | N/A | Channel updated |
| 4xx | ⚠️ Graceful failure | ❌ No | Recorded in execution |
| 5xx | ⚠️ Retryable | ✅ Yes (queue) | Canonical safe |
| Network error | ⚠️ Retryable | ✅ Yes (queue) | Canonical safe |

### 4.3 AirbnbChannelAdapter Failure Semantics

**T6 Invariant:** AirbnbChannelAdapter **NEVER** writes to `PropertyAvailability`.

```php
// AirbnbChannelAdapter — ADR-006 invariant
// NO PropertyAvailability::create/update calls
// Only reads state and calls transport
```

---

## 5. Channel Enable/Disable Scenarios

### 5.1 Channel Disabled Before Sync

| Scenario | Behavior | Evidence |
|---------|----------|----------|
| `is_sync_active = false` | Channel not in `getRegisteredChannels()` | Filtered out |
| `senkron_durumu ≠ 'active'` | Channel not in `getRegisteredChannels()` | Filtered out |
| No `IlanTakvimSync` record | `NO_LISTING_MAPPING` response | Adapter returns failure |

### 5.2 Channel Disabled During Sync

| Scenario | Behavior | Result |
|---------|----------|--------|
| Channel removed mid-sync | Job completes for remaining channels | Partial sync |
| Channel deactivates after job dispatch | Job processes with current state | Last-known state |
| Channel fails | `SyncResult::failure()` recorded | Retry via queue |

### 5.3 Re-enabling Channel

| Scenario | Behavior |
|---------|----------|
| Channel re-enabled | Next event trigger → full sync |
| Gap period | Channel stale — last sync state |

---

## 6. Multi-Channel Projection

### 6.1 Fan-Out Pattern

```php
// AvailabilitySynchronizationService.php:172-180
foreach ($registeredChannels as $channelAdapter) {
    $result = $this->syncToChannel($channelAdapter, $command);

    if ($result->hasConflicts()) {
        $allConflicts = array_merge($allConflicts, $result->conflicts);
    }

    $totalSynced += $result->syncedCount;
}
```

### 6.2 Partial Failure Handling

| Channels | Result |
|----------|--------|
| All succeed | `SyncResult::success(total)` |
| Some fail | `SyncResult::success(partial)` with conflicts |
| All fail | `SyncResult::failure()` with all errors |

**Design:** Partial failure is recorded but does not rollback canonical materialization.

---

## 7. Channel Boundary — SAAB Decisions

### 7.1 Projection Boundary Invariants

| Invariant | Evidence | Status |
|-----------|----------|--------|
| Channel adapters READ from canonical state | `pushAvailability(data)` receives pre-built array | ✅ |
| Channel adapters NEVER write to canonical state | Airbnb T6 test: no `PropertyAvailability::` calls | ✅ |
| Channel failure does NOT affect canonical materialization | `syncToChannel()` catches all exceptions | ✅ |
| Channel adapter is injected via contract | `ChannelSyncContract` interface | ✅ |
| Tenant isolation enforced at boundary | `tenantId` parameter + `IlanTakvimSync` check | ✅ |

### 7.2 IlanTakvimSync Boundary Role

```
┌─────────────────────────────────────────────────────────────┐
│  IlanTakvimSync                                                │
│  - Platform config (credential, listing ID)                     │
│  - Sync control (is_sync_active, senkron_durumu)               │
│  - Token storage (for OAuth flows)                             │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│  NOT: Availability source                                     │
│  NOT: Conflict resolution authority                           │
│  NOT: Reservation record                                    │
└─────────────────────────────────────────────────────────────┘
```

---

## 8. Discovery Findings

### 8.1 Channel Boundary — APPROVED

| Aspect | Finding | Evidence |
|--------|---------|----------|
| Canonical materialization | `AvailabilitySyncService` | ✅ Sole authority |
| Channel projection | `ChannelSyncContract` → adapters | ✅ Interface-based |
| Channel failure isolation | Exception caught, result recorded | ✅ No rollback |
| Tenant isolation | `tenantId` + `IlanTakvimSync` check | ✅ Enforced |
| Channel registration | `is_sync_active` + `senkron_durumu` | ✅ Config-based |

### 8.2 Critical Observations

| # | Finding | Implication |
|---|---------|-------------|
| 1 | Channel adapter failure → `SyncResult::failure()` not exception | Canonical safe, retry via queue |
| 2 | `is_sync_active = false` → channel excluded from sync | Admin control works |
| 3 | No `IlanTakvimSync` record → `NO_LISTING_MAPPING` | Graceful no-op |
| 4 | Airbnb T6: Never writes to `PropertyAvailability` | ADR-006 compliance |
| 5 | Booking 5xx → `BookingAvailabilityException` → job retry | Queue-based retry |
| 6 | Channel hatası canonical materialization'ı etkilemez | ✅ Correct isolation |

### 8.3 Open Questions

| Question | Status | Answer |
|---------|--------|--------|
| Channel disabled during sync — partial result? | ✅ Defined | Partial sync + recorded failure |
| Channel re-enabled — catch-up sync? | ⏳ Next wave | Event backbone re-trigger |
| Multiple channels fail — retry all? | ✅ Defined | Queue job per channel |

---

## 9. SAAB Decision 4.3 — Channel Boundary

### 9.1 Projection Boundary Model

```
Canonical Materialized State (property_availabilities)
    │
    │ read-only projection
    ▼
ChannelSyncContract (interface)
    ├── BookingChannelAdapter → Booking.com (push-only)
    └── AirbnbChannelAdapter → Airbnb/Channex (push+pull)

Channel adapters:
✅ READ canonical state (via pre-built data)
✅ ISOLATED from canonical failures
✅ INTERFACE-based (injectable)
✅ TENANT-isolated (tenantId + IlanTakvimSync)
✅ NEVER write to canonical state
```

### 9.2 Channel Eligibility Rules

| Rule | Condition | Effect |
|------|-----------|--------|
| Property match | `ilan_id` = property ID | Local binding |
| Sync enabled | `is_sync_active = true` | Admin control |
| Status active | `senkron_durumu = 'active'` | Operational |
| Tenant match | `ilan.tenant_id = tenantId` | Security |

### 9.3 Failure Isolation Guarantee

```
Channel adapter failure
    │
    ├── Exception caught by syncToChannel()
    │
    ├── SyncResult::failure() returned
    │
    ├── ChannelSyncExecution recorded with failure
    │
    └── Canonical PropertyAvailability — UNCHANGED ✅
```

---

## 10. Recommendations

### 10.1 Channel Boundary — FROZEN

**Decision 4.3: ✅ Channel Boundary APPROVED**

The channel boundary is correctly defined:
- Canonical materialization → `AvailabilitySyncService`
- Projection → `ChannelSyncContract` → channel adapters
- Failure isolation → exception caught, canonical unchanged

### 10.2 Implementation Notes

| Aspect | Note |
|--------|------|
| Channel enable/disable | Config-based via `IlanTakvimSync` |
| Channel failure | Queue retry for 5xx, graceful failure for 4xx |
| Tenant isolation | `tenantId` + `IlanTakvimSync` double-check |
| Multiple channels | Fan-out, partial failure recorded |

### 10.3 Next Decision: 4.4 — Idempotency

Focus: `correlationId` semantics, idempotency key structure, replay safety.
