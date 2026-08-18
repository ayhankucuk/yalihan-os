# SAAB CERTIFICATION REVIEW — Sprint 4.14

**Document:** `audits/SAAB-S44-BOOKING-WAVE5-CERTIFICATION.md`
**Date:** 2026-08-12
**Reviewer:** SAAB (Kilo Agent)
**Status:** DRAFT — Pre-implementation architecture gate

---

## Executive Summary

Booking.com Wave 5 (Rates Push) is the final development wave before Production Certification Gate. The adapter endpoint (`pushRates`) and OTA payload builder already exist. The critical gap is the **upstream pipeline** — no service extracts canonical rate data and feeds it to the adapter. This review prescribes the complete Wave 5 architecture, identifies all implementation tasks, and defines the Production Certification Gate checklist.

**Wave 5 Scope:** Build the Rate Projection → BookingChannelAdapter pipeline. Do NOT build a new pricing engine.

**Verdict:** APPROVED WITH PRESCRIPTION — Wave 5 can proceed. Three decisions are locked in the Sprint 4.14 Charter (not separate ADRs).

---

## 0. Sprint 4.14 Charter — Locked Decisions

| ID | Decision | Rationale |
|----|-----------|-----------|
| W5-01 | `PropertyPricingService` is the rate source of truth | Canonical resolver; reads `Ilan.fiyat` + `PropertySeasonalRate` seasonal overrides |
| W5-02 | Send native currency from `Ilan.para_birimi` to Booking.com | No hardcoded conversion in Wave 5; Booking.com handles guest-facing conversion |
| W5-03 | Availability and Rate sync = separate execution/job boundaries | Different triggers, different API endpoints (`/ota/Availability` vs `/ota/HotelRateAmountNotif`), independent retry semantics |

**Implementation scope is strictly bounded:**

```
PropertyPricingService
        ↓
RateProjectionService          (Wave 5 deliverable — no pricing logic)
        ↓
RateSynchronizationService    (Wave 5 deliverable — orchestration only)
        ↓
SynchronizeRatesJob           (Wave 5 deliverable — queue/retry boundary)
        ↓
ChannelSyncContract           (Extend with pushRates())
        ↓
BookingChannelAdapter::pushRates()  (EXISTS — just needs feeding)
```

**Hard constraints:**
- `RateProjectionService` does NOT calculate prices — it only projects already-resolved canonical prices into `[['date', 'rate', 'currency']]` format
- `RateSynchronizationService` does NOT make pricing decisions — it handles provider selection, mapping, and sync orchestration
- `SynchronizeRatesJob` is the queue/retry/execution boundary only

---

## 1. Current State Assessment

### What Already Exists

| Component | Status | Location |
|-----------|--------|----------|
| `BookingChannelAdapter::pushRates()` | ✅ Implemented | `app/Infrastructure/ChannelManager/Adapters/BookingChannelAdapter.php:234` |
| `buildOtaRatesPayload()` | ✅ Implemented | `BookingChannelAdapter.php:414` |
| `OTA_HotelRateAmountNotif` payload format | ✅ Correct | Booking.com Connectivity spec compliant |
| `BookingRatesException` | ✅ Implemented | `app/Infrastructure/ChannelManager/Booking/BookingRatesException.php` |
| `ChannelSyncContract` | ⚠️ Missing `pushRates()` | Contract needs extension |
| BW5-01..BW5-10 tests | ✅ Exist | `tests/Feature/ChannelManager/Booking/` |

### The Gap: No Rate Projection Pipeline

```
YALIHAN Canonical Pricing          [PropertyPricingService — EXISTS]
          ↓
Rate Projection                    [MISSING — Wave 5 deliverable]
          ↓
SynchronizeRatesJob               [MISSING — Wave 5 deliverable]
          ↓
BookingChannelAdapter::pushRates()  [EXISTS — just needs feeding]
          ↓
Booking Transport                 [EXISTS]
          ↓
Booking.com Rates                 [EXISTS]
```

The adapter's `pushRates()` accepts `ratesData` as `[['date' => 'Y-m-d', 'rate' => float, 'currency' => string], ...]`. This is the canonical format for Wave 5.

---

## 2. Wave 5 Prescribed Architecture

### 2.1 Rate Projection Service

**File:** `app/Services/ChannelManager/RateProjectionService.php`

Responsibility: Extract canonical rates for a property date range and project them into adapter-ready format. **Does not write to DB.**

```php
class RateProjectionService
{
    public function __construct(
        private readonly PropertyPricingService $pricingService,
        private readonly IlanTakvimSync $syncRepo,
    ) {}

    /**
     * Project rates for a date range.
     * @param int    $ilanId
     * @param int    $tenantId
     * @param string $fromDate  Y-m-d
     * @param string $toDate    Y-m-d
     * @return array [['date' => 'Y-m-d', 'rate' => float, 'currency' => string], ...]
     */
    public function projectRates(int $ilanId, int $tenantId, string $fromDate, string $toDate): array
}
```

**Logic:**
1. Iterate each date in `[fromDate, toDate)`
2. For each date: call `PropertyPricingService->resolveNightlyRate($ilanId, $date)` — this reads `PropertySeasonalRate` (seasonal override) or falls back to `Ilan.fiyat`
3. Apply currency conversion if `Ilan.para_birimi !== target currency`
4. Return adapter-ready array

**Source of truth for rates:**
- `Ilan.fiyat` (base nightly rate, TRY)
- `PropertySeasonalRate` (seasonal overrides — already modeled, already queried by `PropertyPricingService`)
- `Ilan.para_birimi` (currency)

**Output format matches `BookingChannelAdapter::pushRates()` input exactly.**

### 2.2 Rate Sync Orchestrator Service

**File:** `app/Application/ChannelManager/Services/RateSynchronizationService.php`

Mirrors the `AvailabilitySynchronizationService` pattern:

1. **Idempotency** — `ChannelSyncExecution` with `operation = 'rate_sync'`
2. **DB-first** — rates are canonical data; they come from `Ilan` + `PropertySeasonalRate`, not external
3. **Queue-first** — external API call dispatched via `afterCommit()`
4. **Tenant isolation** — `Ilan.tenant_id` check
5. **Replay-safe** — `processed_at` guard

### 2.3 SynchronizeRatesJob

**File:** `app/Jobs/ChannelManager/SynchronizeRatesJob.php`

Mirrors `SynchronizeAvailabilityJob`:
- `public readonly int $syncRecordId`
- `$tries = 3`, `$backoff = 30`
- `uniqueId(): string` → `'rate_sync_' . $syncRecordId`
- `tags(): array` → `['channel-manager', 'rate-sync', 'sync_record:N']`
- `handle(RateSynchronizationService $service)`

### 2.4 Trigger Points

Rates must be synced to Booking.com when:

| Trigger | Mechanism |
|---------|-----------|
| Seasonal rate created/updated (`PropertySeasonalRate`) | Observer on `PropertySeasonalRate` model |
| Base price updated (`Ilan.fiyat`) | Observer on `Ilan` model |
| Channel sync activated for Booking.com | Observer on `IlanTakvimSync` |
| Manual sync requested | Controller action |
| Scheduled full sync | `SynchronizeAllRatesJob` (cron, weekly) |

**Recommended approach for Sprint 4.14:** Observer pattern on `PropertySeasonalRate` and `Ilan`. Dispatch `RateSynchronizationService::synchronize()` → `afterCommit()` → job. Keep it simple; schedule/full-sync can be added post-Wave-5.

### 2.5 Contract Extension

**File:** `app/Contracts/ChannelManager/ChannelSyncContract.php`

Add to interface:
```php
/**
 * Push rates FROM Yalihan TO the external channel.
 *
 * @param int    $tenantId
 * @param int    $propertyId
 * @param string $correlationId
 * @param array  $ratesData [['date' => 'Y-m-d', 'rate' => float, 'currency' => string], ...]
 *
 * @return ChannelSyncResponse
 */
public function pushRates(
    int    $tenantId,
    int    $propertyId,
    string $correlationId,
    array  $ratesData,
): ChannelSyncResponse;
```

This makes `pushRates` a first-class contract method instead of an undocumented adapter extension.

---

## 3. Production Certification Gate

After Wave 5 is implemented and BW5 tests pass, the following gate must be cleared before production credentials are used.

### 3.1 Credential & Config Readiness

| # | Check | Method |
|---|-------|--------|
| G-01 | `ILAN_TAKVIM_SYNC` has active `booking_com` record with valid `token_access` | SQL: `SELECT * FROM ilan_takvim_sync WHERE platform='booking_com' AND is_sync_active=1` |
| G-02 | Booking.com sandbox credentials match `.env` / secrets vault | Manual review |
| G-03 | OAuth2 token exchange works end-to-end in sandbox | `php artisan tinker` → `BookingAuthTransport::getToken()` |
| G-04 | No hardcoded credentials in source code | `grep -r "booking" app/ --include="*.php" \| grep -i "secret\\|token\\|password"` |

### 3.2 Connectivity

| # | Check | Method |
|---|-------|--------|
| G-05 | Sandbox endpoint `POST /ota/Availability` returns 200 | BW4 tests green |
| G-06 | Sandbox endpoint `POST /ota/HotelRateAmountNotif` returns 200 | BW5 tests green |
| G-07 | Token auto-refresh works (expired token → new token → success) | `BookingCredentialManager` unit test |
| G-08 | 401 → auto-retry → success within same request | `BookingTransport` unit test |

### 3.3 Reservation Round-trip

| # | Check | Method |
|---|-------|--------|
| G-09 | Booking.com → Yalıhan: new reservation ingested, canonical created | `BookingReservationIngestServiceTest` |
| G-10 | Booking.com → Yalıhan: modification applied via `modifyReservation` | `BookingModificationProcessorTest` |
| G-11 | Booking.com → Yalıhan: cancellation applied via `cancelReservation` | `BookingCancellationProcessorTest` |
| G-12 | Yalıhan → Booking.com: availability pushed and acknowledged | `AvailabilitySynchronizationService` integration test |
| G-13 | Yalıhan → Booking.com: rates pushed and acknowledged | `RateSynchronizationService` integration test |

### 3.4 Retry & Recovery

| # | Check | Method |
|---|-------|--------|
| G-14 | `SynchronizeAvailabilityJob` retries 3× on 5xx | Job test with mocked 503 |
| G-15 | `SynchronizeRatesJob` retries 3× on 5xx | Job test with mocked 503 |
| G-16 | `failed()` callback fires after 3 retries | Job test |
| G-17 | Idempotent: same `correlationId` → same result, no duplicate push | BW4-06, BW5-06 tests |
| G-18 | Out-of-order modification does not corrupt state | ADR-008 test |
| G-19 | Out-of-order cancellation is silently ignored | ADR-008 test |

### 3.5 Tenant Isolation

| # | Check | Method |
|---|-------|--------|
| G-20 | Cross-tenant availability push returns `CROSS_TENANT_ACCESS` | BW4-05 test |
| G-21 | Cross-tenant rate push returns `CROSS_TENANT_ACCESS` | BW5-05 test |
| G-22 | Deleted `IlanTakvimSync` record → `NOT_REGISTERED` | BW4-05, BW5-05 test |
| G-23 | `tenant_id` mismatch on `Ilan` → operation rejected | BW4-05, BW5-05 test |

### 3.6 Secrets & Auth

| # | Check | Method |
|---|-------|--------|
| G-24 | Credentials stored in `ilan_takvim_sync`, not env | Code review |
| G-25 | Token not logged (scrubbed from Log::info) | Code review of `BookingTransport` |
| G-26 | Two-legged OAuth2 only (no user-facing OAuth) | ADR-009 §4 review |

### 3.7 Queue & Scheduler

| # | Check | Method |
|---|-------|--------|
| G-27 | `SynchronizeAvailabilityJob` uses `afterCommit()` | `dispatch()->afterCommit()` in service |
| G-28 | `SynchronizeRatesJob` uses `afterCommit()` | Same pattern |
| G-29 | Jobs are `ShouldQueue` + `SerializesModels` | Class declaration check |
| G-30 | Job `$tries = 3`, `$backoff = 30s` | Job class check |
| G-31 | No external API calls inside DB transactions | Code review of sync services |

### 3.8 Observability

| # | Check | Method |
|---|-------|--------|
| G-32 | `ChannelSyncExecution` records every sync (success + failure) | Query: `SELECT * FROM channel_sync_executions ORDER BY id DESC LIMIT 10` |
| G-33 | Failed syncs logged with `errorCode` + `errorMessage` | Log check |
| G-34 | Correlation ID propagates through full pipeline | Log grep for `correlation_id` |
| G-35 | No silent catch blocks swallowing exceptions | AST scan: `EmptyCatch`
 |

---

## 4. Wave 5 Implementation Task Map

### Must Implement

- [ ] `RateProjectionService` — project canonical rates to `[['date', 'rate', 'currency']]` format
- [ ] `RateSynchronizationService` — orchestrate sync with idempotency + queue dispatch
- [ ] `SynchronizeRatesJob` — queue job with `$tries=3`, `$backoff=30`, `afterCommit()`
- [ ] Observer on `PropertySeasonalRate` → dispatch rate sync on create/update
- [ ] Observer on `Ilan` → dispatch rate sync when `fiyat` changes
- [ ] Extend `ChannelSyncContract` with `pushRates()`
- [ ] Wire observers in `EventServiceProvider` or dedicated provider

### Must Add Tests (Certification Requirement)

The existing BW5-01..BW5-12 test suite covers adapter-only behavior (translation + transport layer). Five critical behaviors are untested and must be added for certification:

| New Test | Covers | Gap |
|----------|--------|-----|
| `test_bw5_13_cross_tenant_isolation_secondary_check` | `ilan_takvim_sync` exists but `ilan.tenant_id !== tenantId` → `CROSS_TENANT_ACCESS` | BW5-05 only tests the "no sync record" path; secondary check untested |
| `test_bw5_14_rate_projection_uses_seasonal_override` | `PropertySeasonalRate` seasonal rate resolves over `Ilan.fiyat` base | No test that `PropertyPricingService` + seasonal override feeds the projection |
| `test_bw5_15_rate_projection_date_range_iteration` | Date range → one projection per night, correct dates | No test of the projection service's date iteration logic |
| `test_bw5_16_synchronize_rates_job_replay_idempotent` | Job retries 3× on 5xx, `processed_at` guard, `failed()` callback | No `SynchronizeRatesJob` test exists |
| `test_bw5_17_rate_synchronization_service_orchestration` | Full pipeline: project → orchestrate → dispatch job | No `RateSynchronizationService` integration test |

> **Certification rule:** 10/10 adapter test coverage is not sufficient. BW5-01..BW5-12 must be supplemented with the 5 tests above to achieve **invariant coverage** across the full Wave 5 pipeline. Test count is a vanity metric; invariant coverage is what certifies.

### Could Implement Post-Wave-5

- [ ] Scheduled full rate sync (weekly cron — `SynchronizeAllRatesJob`)
- [ ] `testConnection()` in `BookingChannelAdapter`
- [ ] ADR-009 §6 recovery job for missed reservations
- [ ] Currency conversion in `RateProjectionService` (EUR/USD support)

---

## 5. ADR Compliance Checklist

| ADR | Rule | Wave 5 Compliance |
|-----|------|-------------------|
| ADR-006 | Transport-agnostic, tenant-isolated | ✅ `BookingTransport` injected, tenant check in adapter |
| ADR-006 | Rates deferred until pricing domain stable | ✅ Pricing domain (`PropertyPricingService`) is stable |
| ADR-007 | Thin canonical chain wrapper | ✅ Adapter is translation layer only |
| ADR-008 | Out-of-order idempotent | ✅ Same pattern as Wave 3/4 |
| ADR-009 | Two-legged OAuth2 only | ✅ `BookingAuthTransport` is machine-account |
| ADR-009 | ACK ordering invariant | ✅ Separate acknowledger service |
| SAB | Thin Controller | ✅ All logic in services |
| SAB | Tenant isolation (Rule 1) | ✅ Enforced in adapter |
| SAB | No `env()` in app/ | ✅ Credentials via `IlanTakvimSync` |
| SAB | No empty catch blocks | ✅ All catch → log + rethrow or event |

---

## 6. BW5 Test Coverage Audit

### Existing BW5-01..BW5-12: What Is Covered

All 10 core adapter behaviors are covered at the adapter layer:

| Behavior | Test(s) | Status |
|----------|---------|--------|
| Correct OTA endpoint | BW5-01 | ✅ |
| OTA_Rates payload structure | BW5-02, BW5-12 | ✅ |
| `CurrencyCode` from `ratesData` | BW5-04 | ✅ |
| `StartDate`/`EndDate` date mapping | BW5-02 | ✅ |
| Tenant isolation — no sync record → failure | BW5-05 | ✅ |
| Idempotent push (same data → both hit transport) | BW5-06 | ✅ |
| 5xx → `BookingRatesException` | BW5-07 | ✅ |
| 4xx → `ChannelSyncResponse::failure` | BW5-08 | ✅ |
| Empty `ratesData` → success no-op | BW5-09 | ✅ |
| HTTP 200 → `ChannelSyncResponse::success` | BW5-10 | ✅ |

### What Is NOT Covered (Certification Blockers)

| Missing Behavior | Impact | Fix |
|-----------------|--------|-----|
| Secondary tenant isolation (`ilan_takvim_sync` exists, `ilan.tenant_id !== tenantId`) | **Security** — cross-tenant rate push bypass | Add `test_bw5_13` |
| `RateProjectionService` uses `PropertyPricingService` + `PropertySeasonalRate` | **Functional** — projection may use wrong rate | Add `test_bw5_14` |
| Date range iteration in projection | **Functional** — missing nights, wrong dates | Add `test_bw5_15` |
| `SynchronizeRatesJob` retry/backoff/idempotency | **Reliability** — no job-level retry coverage | Add `test_bw5_16` |
| `RateSynchronizationService` orchestration | **Integration** — no service-level integration test | Add `test_bw5_17` |

---

## 7. SAAB Verdict

| Dimension | Status | Notes |
|-----------|--------|-------|
| Architecture alignment | ✅ PASS | Correct pipeline: Projection → Adapter → Transport |
| ADR compliance | ✅ PASS | All existing ADRs respected |
| SAB compliance | ✅ PASS | Thin services, tenant isolation, no env() |
| Gap: Rate projection | 🔴 ACTION REQUIRED | Must be built in Wave 5 |
| Gap: Job orchestrator | 🔴 ACTION REQUIRED | Must be built in Wave 5 |
| Gap: Contract extension | 🔴 ACTION REQUIRED | `pushRates()` must enter contract |
| Gap: Trigger observers | 🔴 ACTION REQUIRED | Rates must propagate on price change |
| Pre-existing debt | ⚠️ Minor | `pullAvailability()` stub, `BookingConnectivityAdapter` legacy stub |
| Production readiness | ⏳ GATED | Gate must clear before production credentials |

**Recommendation:** Proceed with Wave 5 implementation. The three ADRs (W5-01 through W5-03) should be formally recorded before coding begins.

---

*SAAB v9 | Sprint 4.14 Wave 5 Certification Review | Kilo Agent | 2026-08-12*
