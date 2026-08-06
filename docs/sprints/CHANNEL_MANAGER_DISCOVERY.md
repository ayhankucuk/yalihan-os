# CHANNEL_MANAGER — Discovery Charter

**Status:** 🟡 DISCOVERY
**Charter Date:** 2026-08-06
**Author:** Kilo (Sonnet 4.6) — SAAB authorization required
**Prerequisite:** ✅ All Reservation Core capabilities CLOSED
**SAAB Authorization:** ⏳ PENDING

---

## Mission

Provide a provider-independent integration layer that synchronizes YALIHAN's canonical availability with external channels (Airbnb, Booking.com, iCal) in a secure, deterministic, and observable manner.

**Key Constraint:**
Channel Manager makes **no business decisions**. It only transports validated data between YALIHAN's canonical availability system and external channels. Conflict resolution, priority calculation, and override decisions are owned by Reservation Core capabilities.

---

## SAAB Success Question

_"Does YALIHAN safely and deterministically synchronize availability with external channels — importing external blocks into the canonical PropertyAvailability projection and exporting internal availability to channels — while preserving tenant isolation, idempotency, and drift detectability?"_

---

## Scope

### In-Scope

1. **Export: Push canonical availability to channels**
   - Read from `PropertyAvailability` (canonical SSOT)
   - Push availability state to Airbnb, Booking, iCal
   - Idempotent push operations (same input → same result)

2. **Import: Pull external blocks into canonical projection**
   - Read external channel availability
   - Map to `PropertyAvailability` format with `origin` = channel_id
   - Priority: external blocks have lower priority than internal blocks

3. **Sync Contract (Provider-Independent)**
   - `ChannelSyncContract` — unified interface for all adapters
   - `ICalAdapter` — iCal import/export
   - `AirbnbAdapter` — existing (Sprint 13 E03)
   - `BookingAdapter` — future
   - `FutureAdapter` — extensibility

4. **Failure Handling**
   - Retry with exponential backoff
   - Idempotency guarantees
   - Timeout handling
   - Partial failure isolation

5. **Drift Detection**
   - Compare internal `PropertyAvailability` with external state
   - Report discrepancies without auto-correction
   - Audit trail for drift events

6. **Event Model**
   - `ChannelSyncStarted` / `ChannelSyncCompleted` / `ChannelSyncFailed`
   - `ExternalBlockImported` / `ExternalAvailabilityExported`
   - `DriftDetected`

### Out-of-Scope

- Conflict resolution (ConflictDetectionService owns this)
- Priority calculation (PropertyAvailability owns this)
- Override authorization (ConflictOverrideService owns this)
- Reservation creation from external sources (Channel Manager webhook → future)
- Payment processing
- Pricing synchronization

---

## Current Foundation Analysis

### Sprint 13 E01-E03 Legacy

| Component | Status | Notes |
|-----------|--------|-------|
| `ChannelManagerAggregate` | ✅ EXISTS | CQRS aggregate, event sourcing |
| `ChannelManagerCapability` | ✅ EXISTS | Enum: sync, conflict, channel mgmt |
| `ChannelManagerEventVocabulary` | ✅ EXISTS | 15 events defined |
| `AirbnbChannelAdapter` | ✅ EXISTS | Push-only, E03 scope |
| `AirbnbClient` | ✅ EXISTS | HTTP client with exception hierarchy |
| `AirbnbAvailabilityMapper` | ✅ EXISTS | DTO mapping |
| `ChannelAdapterContract` | ❌ MISSING | Interface not yet created |
| `BookingAdapter` | ❌ MISSING | Future |
| `ICalAdapter` | ❌ MISSING | Future |
| Pull availability | ❌ MISSING | Import path |
| Retry policy | ❌ MISSING | Infrastructure |
| Drift detection | ❌ MISSING | Comparison logic |

### Key Insight: Provider-Independent Architecture

The existing `AirbnbChannelAdapter` follows the adapter pattern correctly. The missing piece is the **contract interface** that all adapters must implement.

```
ChannelSyncContract (interface)
        │
        ├── ICalAdapter
        ├── AirbnbChannelAdapter (existing)
        ├── BookingAdapter (future)
        └── FutureAdapter (extensible)
```

---

## Domain Model

### Channel

```php
/**
 * Channel — External platform identifier
 */
enum Channel: string
{
    case AIRBNB   = 'airbnb';
    case BOOKING  = 'booking';
    case ICAL     = 'ical';
    case VRBO     = 'vrbo';
    case MANUAL   = 'manual'; // edge case: admin manually imported
}
```

### Sync Direction

```php
/**
 * SyncDirection — Operation type
 */
enum SyncDirection: string
{
    case EXPORT = 'export'; // Internal → External
    case IMPORT = 'import'; // External → Internal
}
```

### Sync State

```php
/**
 * SyncState — Operational status
 */
enum SyncState: string
{
    case PENDING   = 'pending';
    case IN_PROGRESS = 'in_progress';
    case SUCCESS   = 'success';
    case FAILED    = 'failed';
    case DRIFTED   = 'drifted';
    case PARTIAL   = 'partial'; // Some items succeeded
}
```

### ExternalBlock Origin Mapping

| Channel | Origin Value | Priority Tier |
|---------|-------------|---------------|
| Airbnb | `airbnb` | TIER_EXTERNAL_SYNC (4) |
| Booking | `booking` | TIER_EXTERNAL_SYNC (4) |
| iCal | `ical` | TIER_EXTERNAL_SYNC (4) |
| Vrbo | `vrbo` | TIER_EXTERNAL_SYNC (4) |
| Manual | `manual` | TIER_OWNER_BLOCK (3) |

---

## Capability Boundary

### Channel Manager DOES

| Capability | Description |
|------------|-------------|
| Sync | Synchronize availability bidirectionally |
| Retry | Retry failed operations with backoff |
| Drift Detection | Detect discrepancies between internal and external state |
| Map | Convert between YALIHAN format and channel-specific format |
| Idempotency | Guarantee same input → same result |
| Audit | Record all sync operations as events |

### Channel Manager DOES NOT

| Forbidden | Reason |
|-----------|--------|
| Conflict Resolution | ConflictDetectionService owns this |
| Availability Calculation | PropertyAvailability owns this |
| Override Authorization | ConflictOverrideService owns this |
| Priority Decision | Priority tiers are defined by PropertyAvailability |
| Direct DB Writes | Uses CanonicalAvailabilityService for import |
| Secret Storage | Credentials stored in IlanTakvimSync, never logged |

---

## Adapter Architecture

### ChannelSyncContract (Interface)

```php
interface ChannelSyncContract
{
    /**
     * Get channel identifier
     */
    public function getChannelId(): Channel;

    /**
     * Get channel display name
     */
    public function getChannelName(): string;

    /**
     * Push availability FROM YALIHAN TO channel
     *
     * @param array $availabilityData ['date' => 'Y-m-d', 'available' => bool, 'property_id' => int]
     */
    public function pushAvailability(array $availabilityData): ChannelSyncResponse;

    /**
     * Pull availability FROM channel TO YALIHAN
     *
     * @param string $fromDate Inclusive start (YYYY-MM-DD)
     * @param string $toDate   Exclusive end (YYYY-MM-DD)
     */
    public function pullAvailability(string $fromDate, string $toDate): ChannelSyncResponse;

    /**
     * Test connection to channel
     */
    public function testConnection(): ChannelSyncResponse;
}
```

### ChannelSyncResponse (DTO)

```php
final class ChannelSyncResponse
{
    public function __construct(
        public readonly bool    $success,
        public readonly string  $status,         // 'success' | 'failed' | 'partial'
        public readonly string  $errorCode,       // Machine-readable
        public readonly ?string $errorMessage,    // Human-readable
        public readonly ?string $channelRef,      // External reference ID
        public readonly array   $metadata,        // Adapter-specific data
        public readonly bool    $retryable,       // Can be retried?
    ) {}

    public static function success(string $channelRef, array $metadata = []): self { ... }
    public static function failure(string $errorCode, string $errorMessage, bool $retryable = false): self { ... }
}
```

### Adapter Hierarchy

```
ChannelSyncContract (interface)
    │
    ├── ICalAdapter
    │       └── Reads/writes iCal (.ics) files or URLs
    │
    ├── AirbnbChannelAdapter (existing)
    │       └── Airbnb API integration
    │
    ├── BookingChannelAdapter (future)
    │       └── Booking.com API integration
    │
    └── FutureChannelAdapter (extensibility)
            └── New channels without code changes to core
```

---

## Event Model

### Sync Events

| Event | Payload | Trigger |
|-------|---------|---------|
| `ChannelSyncStarted` | channel_id, property_id, direction, dates | Sync job begins |
| `ChannelSyncCompleted` | channel_id, property_id, items_processed | All items synced |
| `ChannelSyncFailed` | channel_id, property_id, error_code, retryable | Sync failed |
| `ChannelSyncPartial` | channel_id, property_id, succeeded, failed | Partial success |

### Import Events

| Event | Payload | Trigger |
|-------|---------|---------|
| `ExternalBlockImported` | channel_id, property_id, dates[], origin | External block written to PropertyAvailability |
| `ExternalAvailabilityExported` | channel_id, property_id, dates[], reference | Internal state pushed to channel |

### Drift Events

| Event | Payload | Trigger |
|-------|---------|---------|
| `DriftDetected` | channel_id, property_id, internal_state, external_state, dates[] | Discrepancy found |
| `DriftResolved` | channel_id, property_id, resolution, dates[] | Drift manually resolved |

---

## Failure Policy

### Retry Strategy

```php
class RetryPolicy
{
    public const MAX_ATTEMPTS = 3;
    public const INITIAL_DELAY_MS = 1000;    // 1 second
    public const MAX_DELAY_MS = 60000;       // 1 minute
    public const BACKOFF_MULTIPLIER = 2.0;   // Exponential

    /**
     * Calculate delay for attempt n
     */
    public static function delay(int $attempt): int
    {
        $delay = self::INITIAL_DELAY_MS * pow(self::BACKOFF_MULTIPLIER, $attempt - 1);
        return min($delay, self::MAX_DELAY_MS);
    }
}
```

### Failure Taxonomy

| Error Code | Retryable | Action |
|------------|-----------|--------|
| `RATE_LIMIT` | Yes (with backoff) | Retry after delay |
| `AUTH_FAILED` | No | Alert, manual intervention |
| `TRANSPORT_ERROR` | Yes | Retry with backoff |
| `CONFLICT` | No | Report as drift |
| `TIMEOUT` | Yes | Retry with longer timeout |
| `INVALID_CONFIG` | No | Alert, block sync |
| `PARTIAL_FAILURE` | Yes | Retry failed items only |

### Idempotency

Every sync operation generates an idempotency key:

```php
$idempotencyKey = sprintf(
    '%s:%d:%d:%s:%s:%s',
    $channel->value,           // airbnb
    $tenantId,                 // 1
    $propertyId,               // 42
    $direction->value,         // export
    $dateRangeHash,            // md5 of dates
    $attemptTimestamp          // unix timestamp
);
```

---

## Drift Detection Strategy

### Drift Types

| Type | Description | Resolution |
|------|-------------|------------|
| Phantom Block | External shows booked, internal shows available | Import external block |
| Ghost Block | Internal shows booked, external shows available | Export internal block |
| Priority Conflict | Both show booked, different sources | ConflictDetectionService decides |
| Missing Import | External has reservation, internal missing | Import + alert |

### Detection Algorithm

```php
public function detectDrift(
    int    $tenantId,
    int    $propertyId,
    string $startDate,
    string $endDate
): DriftReport {
    // 1. Get internal state from PropertyAvailability
    $internal = $this->queryInternal($tenantId, $propertyId, $startDate, $endDate);

    // 2. Get external state from channel adapter
    $external = $this->adapter->pullAvailability($startDate, $endDate);

    // 3. Compare
    $drifted = [];
    foreach ($dates as $date) {
        if ($internal[$date] !== $external[$date]) {
            $drifted[] = [
                'date' => $date,
                'internal' => $internal[$date],
                'external' => $external[$date],
            ];
        }
    }

    // 4. Return report (NO auto-correction)
    return new DriftReport(
        tenantId: $tenantId,
        propertyId: $propertyId,
        channel: $this->adapter->getChannelId(),
        driftCount: count($drifted),
        items: $drifted,
        detectedAt: now(),
    );
}
```

### Drift Report

```php
final class DriftReport
{
    public function __construct(
        public readonly int     $tenantId,
        public readonly int     $propertyId,
        public readonly Channel  $channel,
        public readonly int      $driftCount,
        public readonly array    $items,        // ['date', 'internal', 'external']
        public readonly DateTime $detectedAt,
    ) {}

    public function hasPhantomBlocks(): bool { ... }
    public function hasGhostBlocks(): bool { ... }
    public function hasPriorityConflicts(): bool { ... }
}
```

---

## Security & Tenant Isolation

### Tenant Isolation Rules

1. **Every adapter operation MUST include tenant_id**
2. **Property lookup MUST verify tenant_id match**
3. **External listing IDs are tenant-scoped via IlanTakvimSync**
4. **No cross-tenant data leakage in logs or events**

### Credential Security

| Rule | Implementation |
|------|----------------|
| No credentials in logs | Log::warning() excludes tokens |
| No credentials in events | Only IDs stored, no secrets |
| Secrets in env/IlanTakvimSync | API keys stored encrypted |
| Rotate without downtime | Dual-key support in adapter |

### Audit Requirements

Every sync operation produces an immutable event:
- `channel_id`, `property_id`, `tenant_id`
- `direction` (export/import)
- `dates[]`, `items_processed`
- `error_code` (if failed)
- `idempotency_key`

---

## Test Strategy

### Unit Tests (per adapter)

| Test | Coverage |
|------|----------|
| `maps_availability_correctly` | DTO mapping |
| `generates_idempotency_key` | Key format |
| `handles_rate_limit_gracefully` | Retry logic |
| `does_not_log_credentials` | Security |
| `respects_tenant_isolation` | Tenant scope |

### Integration Tests (per adapter)

| Test | Coverage |
|------|----------|
| `push_availability_to_live_channel` | E2E (sandbox) |
| `pull_availability_from_live_channel` | E2E (sandbox) |
| `handles_auth_failure` | Error path |
| `handles_partial_failure` | Partial success |

### Contract Tests (all adapters)

| Test | Coverage |
|------|----------|
| `implements_channel_sync_contract` | Interface compliance |
| `returns_correct_channel_id` | Identity |
| `response_matches_expected_structure` | DTO contract |

### Drift Detection Tests

| Test | Coverage |
|------|----------|
| `detects_phantom_block` | Import missing |
| `detects_ghost_block` | Export missing |
| `reports_no_drift_when_clean` | Negative case |
| `does_not_auto_correct` | Policy enforcement |

---

## Definition of Done

Discovery charter is complete when ALL questions answered:

- [x] Domain model (Channel, Sync, Feed, Import/Export)
- [x] Capability boundary (what it does / doesn't do)
- [x] Adapter architecture (Contract + adapters)
- [x] Event model (all sync events)
- [x] Retry policy (backoff, idempotency)
- [x] Drift detection (strategy, no auto-correction)
- [x] Security (tenant isolation, credential handling)
- [x] Test strategy (unit, integration, contract)

---

## Discovery Findings (Evidence)

### Finding 1: ChannelSyncContract Missing

**Evidence:** `AirbnbChannelAdapter` exists but has no interface. All adapters should implement a common contract.

**Decision:** Create `ChannelSyncContract` interface.

### Finding 2: Pull Availability Not Implemented

**Evidence:** `AirbnbChannelAdapter::pullAvailability()` throws `NOT_IMPLEMENTED`.

**Decision:** Implement pull for import path (Phase 3E).

### Finding 3: No Retry Infrastructure

**Evidence:** Adapter catches exceptions but doesn't retry.

**Decision:** Add `RetryPolicy` and retry middleware.

### Finding 4: No Drift Detection

**Evidence:** `ChannelManagerAggregate` has `recordConflict()` but no drift detection logic.

**Decision:** Implement `DriftDetectorService` that compares PropertyAvailability with channel state.

---

## Next Steps

1. **SAAB Review** — Approve this charter
2. **ADR-005** — Channel Manager Architecture
3. **Phase 1 (Foundation):**
   - Create `ChannelSyncContract` interface
   - Create `ChannelSyncResponse` DTO
   - Create `RetryPolicy` infrastructure
4. **Phase 2 (Export):**
   - Enhance `AirbnbChannelAdapter` with retry
   - Implement `BookingChannelAdapter`
   - Implement `ICalAdapter`
5. **Phase 3 (Import):**
   - Implement `pullAvailability()` on all adapters
   - Create `ExternalBlockImporterService`
6. **Phase 4 (Drift):**
   - Implement `DriftDetectorService`
   - Create drift reporting UI

---

## References

- [ADR-001: Availability Projection Architecture](../adrs/ADR-002-Availability-Projection-Architecture.md)
- [ADR-003: Canonical Conflict Detection Architecture](../adrs/ADR-003-Canonical-Conflict-Detection-Architecture.md)
- [CONFLICT_DETECTION_DISCOVERY.md](./CONFLICT_DETECTION_DISCOVERY.md)
- Evidence: `ChannelManagerAggregate` (Sprint 13 E01)
- Evidence: `AirbnbChannelAdapter` (Sprint 13 E03)
- Evidence: `PropertyAvailability.origin` field mapping
