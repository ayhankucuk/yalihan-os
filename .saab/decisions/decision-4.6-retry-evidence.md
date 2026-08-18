# SAAB Decision 4.6 — Retry/Evidence

**Status:** `DRAFT` → `APPROVED`
**Date:** 2026-08-15
**Gateway:** 4.6 / 6
**Discovery baseline:** 7ce1c8d
**Supersedes:** —
**Instance:** Availability Sync (Sprint 13 E02)

---

## Context

Availability Sync (E02) already implements Decisions 4.1–4.5:

| Decision | Topic | Status |
|----------|-------|--------|
| 4.1 | Canonical Source | ✅ APPROVED |
| 4.2 | Events + Single Materializer | ✅ APPROVED |
| 4.3 | Channel Boundary | ✅ APPROVED |
| 4.4 | Idempotency | ✅ APPROVED |
| 4.5 | Tenant Isolation | ✅ APPROVED |

Decision 4.6 closes the retry and evidence layer for the Availability Sync channel.

---

## Problem Statement

**Channel push (OTA projection) is inherently unreliable.**

Network timeouts, provider 5xx errors, rate limits, and auth expiry are all transient from the channel's perspective. The canonical `PropertyAvailability` state must never be corrupted by a failed OTA projection, and failed jobs must produce evidence that enables safe retry and replay.

Laravel's job layer supports `$tries`, `$backoff`, `$timeout`, and `$failed()` natively — but these mechanics must be wired to the YALIHAN contract: **what constitutes a transient vs. permanent failure, and what evidence survives exhaustion?**

---

## Decision

### D4.6-A — Failure Taxonomy

Every `ChannelTransportResult` carries an explicit `retryable` boolean. This drives the entire retry policy:

```
                    ChannelTransportResult
                           │
              ┌────────────┴────────────┐
              │                         │
         retryable=true             retryable=false
              │                         │
    Transient failure               Permanent failure
    → retry via job                 → evidence only
    → exponential backoff           → no job retry
    → max 3 attempts               → manual resolution
```

**Transient (retryable = true):**
- `TRANSPORT_ERROR` — connection timeout, DNS failure, TCP reset
- `RATE_LIMIT` — 429 with `retry_after` metadata
- `PROVIDER_5XX` — external platform returned 5xx
- `CHANNEL_UNAVAILABLE` — platform API is down

**Permanent (retryable = false):**
- `AUTH_FAILED` — credentials expired or revoked
- `INVALID_LISTING` — external listing no longer exists
- `QUOTA_EXCEEDED` — API quota hard limit
- `NOT_IMPLEMENTED` — adapter stub called in production

### D4.6-B — Job Retry Parameters

`SynchronizeAvailabilityJob` is configured as follows:

```php
public int $tries = 3;       // Attempt ceiling
public int $backoff = 30;    // Seconds; exponential not used — provider rate limits
public int $timeout = 30;    // Hard ceiling per attempt
public bool $deleteWhenMissingModels = true;  // Orphan guard
```

**Rationale:**
- `$tries = 3`: Covers TRANSPORT_ERROR and RATE_LIMIT within a reasonable TTL (~90s). If 3 attempts fail, exhaustion is declared.
- `$backoff = 30`: Fixed backoff. Exponential backoff (`$backoff = [10, 30, 60]`) is deliberately **not** used because most transient channel errors resolve within 30s and the job is not under user-facing latency pressure.
- `$timeout = 30`: External platform calls must complete within 30s. Providers that routinely exceed this should be flagged for adapter review.
- `$deleteWhenMissingModels = true`: If the `ChannelSyncExecution` record is deleted (manual intervention), the job self-destructs rather than sitting in a dead-letter state.

### D4.6-C — Exponential Backoff Variant (Conditional)

For adapters that explicitly signal rate limit with `retry_after` in metadata, the job **may** use exponential backoff:

```php
// In handle(), read retry_after from ChannelTransportResult metadata:
$retryAfter = $result->metadata['retry_after'] ?? null;  // seconds

// Override backoff dynamically via $this->release():
if ($retryAfter) {
    $this->release((int) $retryAfter);
    return;
}
```

This is **conditional**: it only applies when the transport result carries `retry_after` metadata. The base job retains fixed backoff.

### D4.6-D — Evidence State Machine

`ChannelSyncExecution` is the **sole evidence artifact**. Its `status` field drives all retry and replay decisions:

```
   dispatched
       │
       ▼
   processing  ← (job picked up)
       │
   ┌────┴────────────────────┐
   │                         │
completed               failed
   │                         │
   │                    retry_exhausted
   │                         │
   │                    evidence_recorded
   │                         │
   └────── replay ────────────┘
              │
         new execution (new idempotency key)
```

**State transitions:**

| From | To | Trigger |
|------|----|---------|
| `dispatched` | `processing` | Job `handle()` called |
| `processing` | `completed` | All channels synced successfully |
| `processing` | `completed_with_conflicts` | Sync succeeded with conflicts |
| `processing` | `failed` | Single attempt threw exception |
| `failed` | `retry_exhausted` | Job `failed()` called after `$tries` exhausted |
| `retry_exhausted` | *(manual replay)* | Admin triggers replay → new `ChannelSyncExecution` |

### D4.6-E — Retry Exhaustion Protocol

When `$tries` is exhausted, Laravel calls `failed(?\Throwable $exception)`:

```php
public function failed(?\Throwable $exception): void
{
    $this->markExecutionFailed(
        $exception?->getMessage() ?? 'Unknown error'
    );
    // ChannelSyncExecution.status → 'failed'
    // No automatic replay. Manual intervention required.
}
```

**Exhaustion record contains:**
- `error_message` — last error from provider
- `processed_at` — timestamp of exhaustion
- `synced_count` — partial sync count (may be 0)

**Manual replay protocol:**
1. Operator inspects `ChannelSyncExecution` with `status = failed`
2. Operator diagnoses root cause (auth expiry → rotate credentials; listing deleted → deactivate channel sync)
3. Operator calls `AvailabilitySynchronizationService::replay($executionId)`
4. A **new** `ChannelSyncExecution` is created with a new `idempotency_key` (replay is never in-place update)
5. New job is dispatched via `afterCommit()`

### D4.6-F — Rollback Invariant (CRITICAL)

**OTA projection failure must never corrupt canonical `PropertyAvailability` state.**

This invariant is already enforced by the architecture established in Decisions 4.1–4.2:

```
DB Transaction:
  PropertyAvailability updated (canonical state set)
  ChannelSyncExecution created (status = 'dispatched')
  ↓ commit

afterCommit:
  SynchronizeAvailabilityJob dispatched

Job handle():
  Reads ChannelSyncExecution
  Pushes to channels
  Records result in ChannelSyncExecution

  ← If job fails, canonical is already committed and unchanged
```

**Evidence:** `ChannexCanonicalMutationTest` (tests/Feature/ChannelManager/) validates this invariant:
- `test_canonical_availability_unchanged_after_transport_5xx_failure`
- `test_canonical_availability_unchanged_after_auth_failure`
- `test_canonical_availability_unchanged_after_rate_limit`
- `test_no_orphan_availability_on_transport_failure`

### D4.6-G — Replay Is Never In-Place

Replaying a failed sync does **not** update the existing `ChannelSyncExecution`. It creates a new one:

```php
// AvailabilitySynchronizationService::replay(int $executionId)
$original = ChannelSyncExecution::findOrFail($executionId);
$newKey = $original->idempotency_key . ':replay:' . now()->timestamp;

$newExecution = ChannelSyncExecution::create([
    // ... copy from original ...
    'idempotency_key' => $newKey,
    'status' => 'dispatched',
    'correlation_id' => 'replay:' . $original->correlation_id,
]);

SynchronizeAvailabilityJob::dispatch($newExecution->id)->afterCommit();
```

This preserves audit lineage: every execution is a distinct immutable record.

---

## Delivery Semantics Binding

This decision formalizes the terminology approved in Decision 4.5 notes:

> **At-least-once delivery + idempotent processing = effectively-once business effect**

The `ChannelSyncExecution.idempotency_key` is the idempotency anchor. If the job is delivered multiple times (at-least-once), `processQueuedSync()` detects the existing execution via `processed_at !== null` and returns the cached result without re-syncing.

---

## Relationship to Previous Decisions

| Decision | Contract point |
|----------|---------------|
| 4.1 Canonical Source | Evidence state lives in `ChannelSyncExecution`, not job payload |
| 4.2 Single Materializer | `AvailabilitySynchronizationService` is the only writer to both `PropertyAvailability` and `ChannelSyncExecution` |
| 4.3 Channel Boundary | Adapter failure maps to `ChannelTransportResult.retryable` — determines job retry vs. evidence-only |
| 4.4 Idempotency | `idempotency_key` survives retry exhaustion; replay generates a new key |
| 4.5 Tenant Isolation | `failed()` and `markExecutionFailed()` are tenant-scoped via `ChannelSyncExecution` |

---

## Implementation Checklist

- [ ] `SynchronizeAvailabilityJob`: confirm `$backoff` is `30` (already ✅ at line 51)
- [ ] `SynchronizeAvailabilityJob`: confirm `$tries` is `3` (already ✅ at line 46)
- [ ] `SynchronizeAvailabilityJob`: confirm `$timeout = 30` missing — **add**
- [ ] `SynchronizeAvailabilityJob::failed()`: already calls `markExecutionFailed()` (✅ at line 105)
- [ ] `ChannelSyncExecution`: add `retry_exhausted` status value — **add to migration + model docs**
- [ ] `ChannelSyncExecution`: add `attempts` column to track retry count — **add migration**
- [ ] `AvailabilitySynchronizationService`: add `replay(int $executionId)` method
- [ ] `ChannelTransportResult`: document `retry_after` metadata convention
- [ ] Tests: add `test_retry_exhaustion_records_evidence` to `AvailabilitySynchronizationServiceTest`
- [ ] Tests: add `test_replay_creates_new_execution_not_in_place_update`
- [ ] Tests: add `test_permanent_failure_does_not_retry`

---

## Rationale Summary

1. **Canonical protection**: Every design choice prioritizes `PropertyAvailability` immutability. Job failure cannot touch canonical state.
2. **Evidence over implicit retry**: When all retries are exhausted, the system enters a known evidence state rather than silently failing or dead-lettering.
3. **Transient/permanent split**: `ChannelTransportResult.retryable` is the single gate that routes failures into retry or evidence paths.
4. **Replay safety**: New executions are always new records — no in-place update preserves full audit lineage.
5. **Operator agency**: Exhausted executions require human diagnosis; automation is opt-in via `replay()`.

---

## Rejected Alternatives

| Alternative | Reason for rejection |
|-------------|---------------------|
| Exponential backoff by default | Provider rate limits are uniform; fixed 30s backoff is simpler and sufficient |
| Automatic replay after exhaustion | Risk of thundering herd on provider outage; operator must diagnose first |
| Dead-letter queue (DLQ) | `ChannelSyncExecution` *is* the DLQ — a DB-backed record with full context, searchable and tenant-scoped |
| Job payload carries availability delta | Canonical is written before job dispatch; payload duplication adds inconsistency risk |
| `failed()` sends notification | Out of scope for 4.6; can be added as separate notification job in E03 |

---

## SAAB Verdict

**APPROVED** — Decision 4.6 closes the retry/evidence contract for Availability Sync.

When all 6 gates are APPROVED:

```
4.1 ✅  Canonical Source
4.2 ✅  Events + Single Materializer
4.3 ✅  Channel Boundary
4.4 ✅  Idempotency
4.5 ✅  Tenant Isolation
4.6 ✅  Retry/Evidence
─────────────────────────────
→ Charter APPROVED
→ Implementation Authorization 🟢
→ Kilo Code implementation
→ Evidence
→ Tests
→ Certification
```

---

**Board:** SAAB — Yalıhan AI OS Strategic Architecture Board
**Author:** Kilo Code (Agentic)
**Approved by:** Chief AI Architect
**Next:** Decision 4.6 implementation → Kilo Code ticket
