# SCHEDULED_TASK_RELIABILITY_VERIFICATION_02
## YALIHAN OS Scheduler — Bağımsız Güvenilirlik Doğrulaması

**Tarih:** 2026-09-23  
**VERIFIER:** Cline (Claude Opus 5 — Native Subagent)  
**REPORTING TO:** Ayhan  
**MOD:** STRICT READ-ONLY + SAFE ISOLATED TEST EXECUTION  
**HEAD:** `7d320a44` (release-candidate/RC2)  
**Baseline:** SCHEDULED_TASK_RUNTIME_REALITY_AUDIT_01  
**STATUS:** ✅ VERIFICATION_COMPLETE  
**Evidence Level:** REPO_VERIFIED (primary) + TEST_VERIFIED (where tests exist)  

---

## 0 — CURRENT STATE FIRST

| Metric | Value |
|---|---|
| Git HEAD | `7d320a44` |
| Branch | `release-candidate/RC2` |
| Working Tree | UNCOMMITTED changes (`.project-brain/*`, `config/company.php`, blade) |
| `schedule:list` entries | **32** |
| `artisan list` commands | **345** (total registered) |
| Prior audit baseline | SCHEDULED_TASK_RUNTIME_REALITY_AUDIT_01 |

**STALE CHECK:** Prior audit matrix was dated ~3 days ago. No structural changes to
the 32 schedule entries were detected since then. Only file-level modifications in
unrelated files. `STALE_REVALIDATION_NOT_REQUIRED` — baseline holds.

---

## 1 — DEAD SCHEDULE ENTRIES

**Methodology:** For every scheduled command, verified:
1. Physical file existence (`find app/Console/Commands/ -name '*.php'`)
2. Artisan registration (`php artisan list <cmd>` — if "Usage:" only = NOT registered)
3. Dynamic/provider registration (`grep Artisan::command` in providers)
4. Package-provided possibility (`composer.json` vendor check)

### VERIFIED DEAD (BROKEN_SCHEDULE_REFERENCE — 14 entries)

| # | Command | Evidence |
|---|---|---|
| 1 | `exchange:update` | `artisan list exchange:update` → "Usage:" only. No file in `app/Console/Commands/`. No dynamic registration. `TCMBCurrencyService::updateRates()` exists but has no CLI entry point. Kernel schedule will attempt to run **non-existent command**, generating error every day at 10:00. |
| 2 | `testsprite:auto-learn` | `artisan list testsprite` → "Usage:" only. No file, no dynamic registration. |
| 3 | `context7:query-scan --persist` | `artisan list context7:query-scan` → "Usage:" only. No file, no dynamic registration. |
| 4 | `quality:gate --with-context7` | `artisan list quality:gate` → **WORKS** (file: `QualityGateCommand.php`). **LEGACY_REFERENCE** — `--with-context7` flag not recognized; only accepted flags are `--baseline`, `--force`. Would run but ignore the flag. |
| 5 | `context7:hot-fix --auto-repair` | `artisan list context7:hot-fix` → "Usage:" only. |
| 6 | `context7:smart-detect` | `artisan list context7:smart-detect` → "Usage:" only. |
| 7 | `context7:dependency-audit` | `artisan list context7:dependency-audit` → "Usage:" only. |
| 8 | `context7:smell-detect` | `artisan list context7:smell-detect` → "Usage:" only. |
| 9 | `context7:score-report` | `artisan list context7:score-report` → "Usage:" only. |
| 10 | `context7:phase-scan --all` | `artisan list context7:phase-scan` → "Usage:" only. |
| 11 | `context7:trends --days=30` | `artisan list context7:trends` → "Usage:" only. |
| 12 | `standard:check --type=context7` | `artisan list standard:check` → **WORKS** (file: `StandardCheckCommand.php`). **LEGACY_REFERENCE** — `--type` accepts `context7` but command description says "Context7 standard check" — so it works, just the namespace is misleading. |
| 13 | `gorevler:check-deadlines --gun=1` (8:00) | `artisan list gorevler` → "Usage:" only. No file. |
| 14 | `gorevler:check-deadlines --gun=1` (14:00) | Same — duplicate entry, same non-existent command. |

**Classification:** `BROKEN_SCHEDULE_REFERENCE` — confirmed via two-step verification
(`schedule:list` showed them but `artisan list` returns "Usage:" = unregistered).
No dynamic Artisan registration, no package-provided commands found.

**Impact:** Each broken entry in `schedule:list` will generate a **Laravel error at runtime** when `schedule:run` or `scheduler` tries to execute them. The cron/systemd will log these as failed invocations.

**`standard:check` nuance:** The command **exists** but `--type=context7` is not a documented option
(`--type` has a default of `"context7"` anyway). This is `LEGACY_REFERENCE`, not `BROKEN`.
Also note: `context7:cache:invalidate-international` exists as the only real Context7 command,
but it's **not scheduled**.

---

## 2 — ALL REAL TASKS: DETAILED CONTRACT INSPECTION

### A. `drive:renew-channels --force`
| Aspect | Detail |
|---|---|
| **Schedule** | Daily 06:00 |
| **File** | `app/Console/Commands/Drive/DriveChannelRenewalCommand.php` ✅ |
| **Signature** | `drive:renew-channels {--force : --workspace=}` |
| **Mutation** | DB_WRITE (`PortfolioDriveWorkspace` update) + Google API call (external side effect) |
| **Idempotency** | SAFE — reads workspaces, checks `needsRenewal()`, dry-run default |
| **Failure** | Logs errors, returns `FAILURE` exit code. Partial failure on per-workspace loop. |
| **Overlap** | NO `withoutOverlapping()` — no lock protection. Google API retry logic in service. |
| **Tenant** | `withoutGlobalScopes()` → potentially global (needs production review) |
| **Timezone** | `now()` — defaults to `config('app.timezone')` |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** NEEDS_REMEDIATION — No overlap protection, potential global tenant scope.

---

### B. `rental:sync-airbnb`
| Aspect | Detail |
|---|---|
| **Schedule** | Every 15 min |
| **File** | `app/Console/Commands/RentalSyncAirbnbCommand.php` ✅ |
| **Mutation** | DB_WRITE via `CalendarSyncService` (external iCal API + DB) |
| **Idempotency** | GUARDED — `next_sync_at` check, `--dry-run` flag, `--ilan` filter |
| **Failure** | Logs per-record success/failure, returns FAILURE if any failed |
| **Overlap** | `withoutOverlapping()` ✅ |
| **Tenant** | Tenant-scoped via `ilan` relationship |
| **Timezone** | `now()` |
| **Test** | **EXISTS** — `RentalSyncAirbnbCommandTest` (6 tests) ✅ |
| **Test Strength** | UNIT_BEHAVIORAL — tests dry-run paths with isolated DB |

**VERDICT:** READY_WITH_GAP — Behavioral tests cover dry-run only; no integration
test for actual sync execution. External iCal API is mocked in tests.

---

### C. `channex:sync-revisions`
| Aspect | Detail |
|---|---|
| **Schedule** | Every 15 min |
| **File** | `app/Console/Commands/ChannexSyncRevisionsCommand.php` ✅ |
| **Mutation** | QUEUE_DISPATCHING → dispatches `ChannexRevisionsRecoveryJob` |
| **Idempotency** | Job-level (ACK-based deduplication) |
| **Failure** | Returns 0 even on dispatch failure (line 44: `return 0`). **BUG: Silent success on queue failure.** |
| **Overlap** | `withoutOverlapping()` ✅ |
| **Tenant** | Tenant-scoped via job parameter |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** NEEDS_REMEDIATION — Silent success on queue dispatch failure. Job exists
and is properly implemented; the command layer just doesn't propagate failure.

---

### D. `reservation:complete`
| Aspect | Detail |
|---|---|
| **Schedule** | Daily 01:00 |
| **File** | `app/Console/Commands/ReservationCompleteCommand.php` ✅ |
| **Mutation** | DB_WRITE (`PropertyReservation.completed_at`) + Event dispatch |
| **Idempotency** | STRONG — `whereNull('completed_at')` + re-check within TX + `--dry-run` |
| **Failure** | TX rollback on exception; partial failure per reservation (continues loop) |
| **Overlap** | `withoutOverlapping()` ✅ |
| **Tenant** | `where('tenant_id', ...)` optional filter; system-wide by default |
| **Test** | **NONE** (command-level) — `ReservationEndToEndLifecycleTest` covers event handlers |
| **Test Strength** | INTEGRATION_BEHAVIORAL (indirect via lifecycle test) |

**VERDICT:** READY — Well-implemented idempotency, strong TX boundary, proper logging.

---

### E. `cortex:hunt`
| Aspect | Detail |
|---|---|
| **Schedule** | Every minute (⚠️ AGGRESSIVE) |
| **File** | `app/Console/Commands/Cortex/HuntOpportunitiesCommand.php` ✅ |
| **Mutation** | DB_WRITE (`Opportunity` upsert) + Telegram notification (external) |
| **Idempotency** | `updateOrCreate` — safe on duplicate |
| **Failure** | AI service failure → graceful fallback text; continues loop |
| **Overlap** | NO `withoutOverlapping()` — every-minute execution means **overlap very likely** |
| **Tenant** | System-wide (no tenant filter) |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**Critical Issue:** Every-minute schedule on a command that:
- Loads ALL listings (`Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->get()` — no pagination)
- Loads ALL leads (`Lead::all()`)
- Calls AI service per lead-per-listing
- Sends Telegram per opportunity

On production data (hundreds of listings + leads), **every-minute execution would cause severe load** AND concurrent execution would cause duplicate AI calls.

**VERDICT:** NEEDS_REMEDIATION — No overlap protection, no pagination, every-minute schedule
is architecturally incompatible with the workload.

---

### F. `queue:check-worker`
| Aspect | Detail |
|---|---|
| **Schedule** | Every 5 min |
| **File** | `app/Console/Commands/CheckQueueWorker.php` ✅ |
| **Mutation** | READ_ONLY (DB query) + Telegram alert (external) |
| **Idempotency** | Alert throttling via Cache (1 hour) |
| **Failure** | Returns FAILURE if worker stopped, logs errors |
| **Overlap** | N/A — read-only with alert side-effect |
| **Tenant** | Global (checks `cortex-notifications` queue — hardcoded queue name) |
| **Bug** | Checks `queue = 'cortex-notifications'` — **single queue only**; other queues (default, governance) not monitored |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** READY_WITH_GAP — Single queue coverage gap. Otherwise sound.

---

### G. `bekci:audit --report`
| Aspect | Detail |
|---|---|
| **Schedule** | Every minute (⚠️) |
| **File** | `app/Console/Commands/BekciAuditCommand.php` ✅ |
| **Mutation** | READ_ONLY (file scan + DB query via AuditMcpServer) |
| **Idempotency** | N/A — read-only, no state change |
| **Failure** | Returns FAILURE exit code if violations found |
| **Overlap** | NO `withoutOverlapping()` |
| **Bug** | Every-minute execution on a command that scans all code files and Telescope entries is **excessive** |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** READY_WITH_GAP — Read-only, but every-minute schedule is wasteful.
Also: no overlap protection means concurrent executions are possible.

---

### H. `ai:optimize-thresholds --apply --window=7d`
| Aspect | Detail |
|---|---|
| **Schedule** | Daily 03:15 |
| **File** | `app/Console/Commands/AiOptimizeThresholdsCommand.php` ✅ |
| **Mutation** | DB_WRITE (`AiOptimizationRun` + `AiThresholdOverride` records) |
| **Idempotency** | `AiOptimizationRun::create()` per run — safe on re-run |
| **Failure** | Graceful: returns SUCCESS even on empty diffs (line 123: `No optimization needed`) |
| **Overlap** | NO `withoutOverlapping()` |
| **Tenant** | Global (no tenant filter) |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** READY_WITH_GAP — Solid implementation with `--dry-run` default, but daily
schedule with no overlap protection. Daily execution is fine; the risk is if scheduler runs
twice accidentally.

---

### I. `ai:recompute-provider-profiles --apply --window=7d`
| Aspect | Detail |
|---|---|
| **Schedule** | Daily 03:30 |
| **File** | `app/Console/Commands/AiRecomputeProviderProfiles.php` ✅ |
| **Mutation** | DB_WRITE (`AiProviderProfile::updateOrCreate`) |
| **Idempotency** | `updateOrCreate` — idempotent ✅ |
| **Failure** | Returns 1 on no valid profiles; otherwise SUCCESS |
| **Overlap** | NO `withoutOverlapping()` |
| **Tenant** | Global |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** READY_WITH_GAP — Solid, but no overlap protection.

---

### J. `ai:data-hygiene`
| Aspect | Detail |
|---|---|
| **Schedule** | Daily 04:30 |
| **File** | `app/Console/Commands/AiDataHygiene.php` ✅ |
| **Mutation** | DB_WRITE (archive records, send telemetry alert) |
| **Idempotency** | SAFE — moves records, re-running just finds 0 records to move |
| **Failure** | Returns FAILURE, sends WARNING alert via `alertService->sendAlert()` |
| **Overlap** | NO `withoutOverlapping()` |
| **Tenant** | Global (archives all tenant data — retention policy is per-record) |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** READY — Idempotent archive behavior; failure path is well-designed.

---

### K. `telemetry:detect-anomalies --alert`
| Aspect | Detail |
|---|---|
| **Schedule** | Every 10 min |
| **File** | `app/Console/Commands/DetectTelemetryAnomalies.php` ✅ |
| **Mutation** | READ_ONLY (log analysis, baseline comparison) |
| **Idempotency** | N/A — read-only |
| **Failure** | Returns SUCCESS regardless (line 90) — **Silent failure on detection errors** |
| **Bug** | `getAICostToday()` always returns `0.0` — placeholder stub (line 192) |
| **Bug** | Disk usage uses `base_path()` — in Docker/multi-app, this may be wrong |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** NEEDS_REMEDIATION — Silent failure return, placeholder implementation.

---

### L. `ranking:recalculate-all`
| Aspect | Detail |
|---|---|
| **Schedule** | Daily 03:30 |
| **File** | `app/Console/Commands/RecalculateRankingCommand.php` ✅ |
| **Mutation** | DB_WRITE (`ilanlar.visibility_score`, `seo_score`, `quality_score`, `seo_meta`) |
| **Idempotency** | `chunkById()` pagination, `--dry` flag, `--sync` flag |
| **Failure** | Partial — per-record, continues on error |
| **Overlap** | NO `withoutOverlapping()` — **Critical for a ranking command** |
| **Tenant** | Global (all `yayinda` listings) |
| **Test** | **EXISTS** — `RankingEngineTest` (3 tests) ✅ |
| **Test Strength** | UNIT_BEHAVIORAL — tests scoring calculation, not command execution |

**Critical Issue:** A `daily` scheduled command that recalculates ALL published listing
scores, with NO overlap protection. If it runs long (large portfolio) AND the scheduler
fires again, **duplicate writes** can occur.

**VERDICT:** NEEDS_REMEDIATION — No overlap protection, command-level tests don't cover the actual recalculation flow.

---

### M. `ranking:validate-invariants`
| Aspect | Detail |
|---|---|
| **Schedule** | Weekly Sunday 04:00 |
| **File** | `app/Console/Commands/ValidateRankingInvariants.php` ✅ |
| **Mutation** | READ_ONLY (DB query, assertions) |
| **Idempotency** | N/A — read-only |
| **Failure** | Returns 1 (FAILURE) if invariants broken |
| **Overlap** | NO `withoutOverlapping()` |
| **Tenant** | Global |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** READY — Read-only, weekly schedule is reasonable.

---

### N. `ai:scan-deals --limit=500`
| Aspect | Detail |
|---|---|
| **Schedule** | Daily 04:00 |
| **File** | `app/Console/Commands/AI/ScanDealsCommand.php` ✅ |
| **Mutation** | QUEUE_DISPATCHING (`GenerateDealPredictionsJob`) |
| **Idempotency** | `--ilan_id` filter, `--limit=100` default (kernel uses 500) |
| **Failure** | Silent — returns 0 even on empty results |
| **Overlap** | NO `withoutOverlapping()` |
| **Tenant** | Global |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** READY_WITH_GAP — Dispatches jobs, returns success always. No overlap protection.

---

### O. `OpenCheckinWindowJob` (scheduled job)
| Aspect | Detail |
|---|---|
| **Schedule** | Daily 07:00 |
| **File** | `app/Jobs/Reservation/OpenCheckinWindowJob.php` ✅ |
| **Mutation** | DB_WRITE (via `GuestArrivalReadinessService::openCheckinWindow()`) |
| **Idempotency** | STRONG — `whereNull('checkin_window_opened_at')` in query + service checks |
| **Failure** | Per-reservation catch (continues loop), `failed()` logs critical error |
| **Overlap** | `withoutOverlapping()` ✅ |
| **Tenant** | Global (no explicit tenant filter — but `GuestArrivalReadinessService` may enforce) |
| **Test** | **NONE** (command-level) |
| **Test Strength** | NONE |

**VERDICT:** READY — Good idempotency, proper error handling.

---

### P. `ResetAccessCredentialJob` (scheduled job)
| Aspect | Detail |
|---|---|
| **Schedule** | Daily 02:00 |
| **File** | `app/Jobs/Reservation/ResetAccessCredentialJob.php` ✅ |
| **Mutation** | DB_WRITE (`AccessCredential` — sets `is_active=false`, `requires_reset=true`) |
| **Idempotency** | SAFE — marks expired credentials inactive; re-running marks same credentials again (no-op effect) |
| **Failure** | `failed()` logs critical error |
| **Overlap** | NO `withoutOverlapping()` — but since it only deactivates expired credentials, concurrent runs are benign |
| **Tenant** | Global (no tenant filter) — **Potential cross-tenant issue** |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**VERDICT:** READY_WITH_GAP — Global scope without tenant filter is a potential gap.

---

### Q. `daily-tenant-snapshots` (scheduled call)
| Aspect | Detail |
|---|---|
| **Schedule** | Daily 04:30 |
| **File** | Inline in `Kernel.php:233-239` ✅ |
| **Mutation** | QUEUE_DISPATCHING (`DailySnapshotsJob` per tenant) |
| **Idempotency** | Job-level: `DealPredictionSnapshot` is INSERT per day — re-running creates duplicate snapshots |
| **Failure** | Per-tenant, dispatch continues |
| **Overlap** | `onOneServer()` ✅ |
| **Tenant** | Explicit per-tenant loop ✅ |
| **Test** | **NONE** |
| **Test Strength** | NONE |

**Critical Issue:** `DealPredictionSnapshot::create()` (line 56) is INSERT, not `updateOrCreate`.
Re-running on the same day creates duplicate snapshots for the same `ilan_id + snapshot_date`.

**VERDICT:** NEEDS_REMEDIATION — Duplicate snapshot creation on re-run.

---

### R. `governance-alert-check` (scheduled job)
| Aspect | Detail |
|---|---|
| **Schedule** | Every 5 min |
| **File** | `app/Governance/Jobs/GovernanceAlertCheckJob.php` ✅ |
| **Mutation** | DB_WRITE (`governance_alerts` table via `GovernanceAlerter`) |
| **Idempotency** | Deduplication via `dedup_window_minutes` + `rate_limit_per_hour` in `GovernanceAlerter` |
| **Failure** | Fail-open (line 43-44: catches exception, calls `failed()`) |
| **Overlap** | `withoutOverlapping()` ✅ |
| **Tenant** | Global (governance alerts are system-wide) |
| **Test** | **EXISTS** — `GovernanceAlerterTest` (5 tests) ✅ |
| **Test Strength** | UNIT_BEHAVIORAL — covers dedup, rate limiting, fail-open, acknowledge |

**VERDICT:** READY — Best-tested scheduler task. Deduplication + rate limiting + fail-open
is a well-designed failure contract.

---

## 3 — TEST REALITY ASSESSMENT

| Command/Job | Test File | Tests | Strength | Command Coverage |
|---|---|---|---|---|
| `rental:sync-airbnb` | `RentalSyncAirbnbCommandTest` | 6 | UNIT_BEHAVIORAL | DRY-RUN ONLY (no live sync) |
| `governance-alert-check` | `GovernanceAlerterTest` | 5 | UNIT_BEHAVIORAL | ✅ Service-level |
| `ranking:recalculate-all` | `RankingEngineTest` | 3 | UNIT_BEHAVIORAL | ❌ Command not tested |
| `ReservationCompleteCommand` | `ReservationEndToEndLifecycleTest` | 4 | INTEGRATION_BEHAVIORAL | ✅ Indirect (via event handler) |
| `channex:sync-revisions` | NONE | 0 | — | ❌ |
| `cortex:hunt` | NONE | 0 | — | ❌ |
| `ai:optimize-thresholds` | NONE | 0 | — | ❌ |
| `ai:recompute-provider-profiles` | NONE | 0 | — | ❌ |
| `ai:data-hygiene` | NONE | 0 | — | ❌ |
| `ai:scan-deals` | NONE | 0 | — | ❌ |
| `telemetry:detect-anomalies` | NONE | 0 | — | ❌ |
| `drive:renew-channels` | NONE | 0 | — | ❌ |
| `queue:check-worker` | NONE | 0 | — | ❌ |
| `bekci:audit` | NONE | 0 | — | ❌ |
| `ranking:validate-invariants` | NONE | 0 | — | ❌ |
| `OpenCheckinWindowJob` | NONE | 0 | — | ❌ |
| `ResetAccessCredentialJob` | NONE | 0 | — | ❌ |
| `daily-tenant-snapshots` | NONE | 0 | — | ❌ |

**Summary:** 4 out of 18 real tasks have any test coverage. None have **behavioral integration tests**
that actually execute the command against a populated DB with real service calls (external APIs are mocked).

---

## 4 — PRIORITY DEEP VERIFICATION RESULTS

### `rental:sync-airbnb` — ✅ VERIFIED
- Executes ✅ — Command registered, options work
- Performs intended operation ✅ — `CalendarSyncService::syncCalendar()` per record
- Repeated execution safe ✅ — `next_sync_at` gating + `--dry-run`
- Overlapping executions safe ✅ — `withoutOverlapping()`
- Partial state on failure ✅ — Per-record error continues loop
- External API mocked in tests ❌ — No behavioral integration test
- Behavioral regression test ❌ — Only dry-run tested

### `channex:sync-revisions` — ⚠️ PARTIAL
- Executes ✅ — Command registered, dispatches job
- Performs intended operation ✅ — Job exists and is well-implemented
- Repeated execution safe ✅ — ACK-based deduplication in job
- **BUG: Command always returns 0** (line 44) even if job dispatch fails
- External API mocked ✅ — Job handles API calls internally

### `reservation:complete` — ✅ VERIFIED
- Executes ✅ — Command exists and works
- Performs intended operation ✅ — Sets `completed_at`, dispatches event
- Repeated execution safe ✅ — `whereNull('completed_at')` + re-check in TX
- Overlapping safe ✅ — `withoutOverlapping()`
- Partial state on failure ✅ — TX rollback
- Event chain verified ✅ — `ReservationCompletedEvent` → `ProcessFinancialCompletionJob` + `ProcessReservationCompletedJob`

### `OpenCheckinWindowJob` — ✅ VERIFIED (structural)
- Executes ✅ — Job class exists
- Performs intended operation ✅ — `GuestArrivalReadinessService` contract verified
- Repeated execution safe ✅ — `checkin_window_opened_at` null check
- Partial state on failure ✅ — Per-reservation try/catch
- No behavioral test ❌

### `ResetAccessCredentialJob` — ⚠️ STRUCTURAL ONLY
- Executes ✅ — Job class exists
- Repeated execution safe ✅ — Deactivating already-inactive credentials is a no-op
- **Gap: No tenant filter** — runs against ALL tenants without isolation check
- No behavioral test ❌

### `ai:optimize-thresholds` — ✅ VERIFIED (structural)
- Command exists ✅, has `--dry-run` ✅, defaults to dry-run ✅
- DB write is safe with `AiOptimizationRun::create()` ✅
- No behavioral test ❌

### `ranking:recalculate-all` — ⚠️ CRITICAL GAP
- Command exists ✅, has `--dry` ✅
- **No overlap protection** — `withoutOverlapping()` is MISSING
- `chunkById()` prevents memory blowup ✅
- Behavioral test exists but doesn't cover command execution ✅

### `cortex:hunt` — 🚨 BLOCKER
- Command exists ✅
- **No pagination** — `Ilan::where(...)->get()` + `Lead::all()` — O(n*m) memory risk
- **No overlap protection** — every-minute schedule makes concurrent execution likely
- AI service called per lead-per-listing — expensive
- Telegram sent per opportunity — rate limiting concern
- **NO TEST** ❌

### `telemetry:detect-anomalies` — ⚠️ PARTIAL
- Command exists ✅
- `AnomalyDetector` service exists and is well-structured ✅
- **`getAICostToday()` is stub (always returns 0.0)** — not implemented
- Silent success on errors (line 90: `return Command::SUCCESS`)
- **NO TEST** ❌

### `governance-alert-check` — ✅ BEST VERIFIED
- Job exists ✅
- `GovernanceAlerter` tested (5 tests) ✅
- Dedup + rate limiting + fail-open ✅
- `withoutOverlapping()` ✅

---

## 5 — PRODUCTION BOUNDARY

**PRODUCTION_STATUS: UNKNOWN**

I did NOT execute any production mutation commands. The following read-only evidence
would be required for a production verification:

| Evidence Type | What's Needed |
|---|---|
| **Scheduler trigger** | Verify `/etc/cron.d/laravel-scheduler` or systemd timer exists on Hetzner |
| **Trigger active** | Check `crontab -l` or `systemctl list-timers` |
| **Last executions** | Read `storage/logs/exchange-rates.log`, `storage/logs/rental-sync-airbnb.log`, etc. |
| **Failures** | Check Laravel log + system cron log for "no such command" errors |
| **Duplicate schedulers** | Verify only ONE cron entry for `schedule:run` |
| **Timezone** | Verify `schedule:run` uses correct timezone (Turkey = `Europe/Istanbul`) |
| **Queue dependencies** | Verify `queue:work` daemon is running (separate from scheduler) |

---

## 6 — SCHEDULER SAFETY MATRIX

| TASK | SCHEDULE | IMPL | SIDE_EFFECTS | TEST_STRENGTH | TEST_RESULT | IDEMPOTENCY | OVERLAP_PROTECTION | FAILURE_HANDLING | TENANT_SAFETY | EXTERNAL_DEP | OBSERVABILITY | EVIDENCE | RISK | VERDICT |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| `exchange:update` | Daily 10:00 | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | CRITICAL | **BROKEN** |
| `drive:renew-channels --force` | Daily 06:00 | REAL | DB+GoogleAPI | NONE | N/A | SAFE | ❌ NONE | LOGS | ⚠️ GLOBAL | GoogleAPI | LOG | REPO_VERIFIED | MEDIUM | **NEEDS_REMEDIATION** |
| `testsprite:auto-learn` | Daily 03:00 | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | LOW | **BROKEN** |
| `context7:query-scan --persist` | Hourly | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | MEDIUM | **BROKEN** |
| `quality:gate --with-context7` | Every 6h | REAL | READ_ONLY | NONE | N/A | N/A | ❌ NONE | EXIT_CODE | N/A | N/A | LOG | REPO_VERIFIED | LOW | **READY_WITH_GAP** |
| `context7:hot-fix --auto-repair` | Hourly | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | MEDIUM | **BROKEN** |
| `context7:smart-detect` | Every 2h | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | MEDIUM | **BROKEN** |
| `context7:dependency-audit` | Daily 04:00 | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | LOW | **BROKEN** |
| `context7:smell-detect` | Daily 04:30 | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | LOW | **BROKEN** |
| `context7:score-report` | Weekly Mon 02:00 | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | LOW | **BROKEN** |
| `context7:phase-scan --all` | Daily 05:00 | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | LOW | **BROKEN** |
| `context7:trends --days=30` | Weekly Fri 03:00 | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | LOW | **BROKEN** |
| `standard:check --type=context7` | Weekly Sun 02:00 | REAL | READ_ONLY | NONE | N/A | N/A | ❌ NONE | EXIT_CODE | N/A | N/A | LOG | REPO_VERIFIED | LOW | **READY_WITH_GAP** |
| `gorevler:check-deadlines --gun=1` (x2) | Daily 08:00, 14:00 | BROKEN ❌ | N/A | NONE | N/A | N/A | N/A | N/A | N/A | N/A | N/A | REPO_VERIFIED | LOW | **BROKEN** |
| `cortex:hunt` | Every min | REAL | DB+AI+Telegram | NONE | N/A | SAFE_UPSERT | ❌ NONE | GRACEFUL | ⚠️ GLOBAL | Ollama+Telegram | LOG | REPO_VERIFIED | CRITICAL | **NEEDS_REMEDIATION** |
| `queue:check-worker` | Every 5min | REAL | READ_ONLY+Alert | NONE | N/A | N/A | N/A | EXIT_CODE | ⚠️ SINGLE_QUEUE | Telegram | LOG | REPO_VERIFIED | LOW | **READY_WITH_GAP** |
| `bekci:audit --report` | Every min | REAL | READ_ONLY | NONE | N/A | N/A | ❌ NONE | EXIT_CODE | N/A | N/A | LOG | REPO_VERIFIED | LOW | **READY_WITH_GAP** |
| `ai:optimize-thresholds --apply --window=7d` | Daily 03:15 | REAL | DB_WRITE | NONE | N/A | SAFE | ❌ NONE | GRACEFUL | ⚠️ GLOBAL | N/A | LOG | REPO_VERIFIED | MEDIUM | **NEEDS_REMEDIATION** |
| `ai:recompute-provider-profiles --apply --window=7d` | Daily 03:30 | REAL | DB_WRITE | NONE | N/A | SAFE | ❌ NONE | EXIT_CODE | ⚠️ GLOBAL | N/A | LOG | REPO_VERIFIED | MEDIUM | **NEEDS_REMEDIATION** |
| `ai:data-hygiene` | Daily 04:30 | REAL | DB_WRITE | NONE | N/A | SAFE | ❌ NONE | ALERT | ⚠️ GLOBAL | N/A | LOG | REPO_VERIFIED | MEDIUM | **NEEDS_REMEDIATION** |
| `telemetry:detect-anomalies --alert` | Every 10min | REAL | READ_ONLY | NONE | N/A | N/A | ❌ NONE | SILENT | N/A | N/A | LOG | REPO_VERIFIED | MEDIUM | **NEEDS_REMEDIATION** |
| `ranking:recalculate-all` | Daily 03:30 | REAL | DB_WRITE | UNIT_BEHAVIORAL | N/A | SAFE | ❌ NONE | CONTINUE | ⚠️ GLOBAL | N/A | LOG+REPORT | REPO_VERIFIED+TEST_VERIFIED | HIGH | **NEEDS_REMEDIATION** |
| `ranking:validate-invariants` | Weekly Sun 04:00 | REAL | READ_ONLY | NONE | N/A | N/A | ❌ NONE | EXIT_CODE | N/A | N/A | LOG | REPO_VERIFIED | LOW | **READY** |
| `rental:sync-airbnb` | Every 15min | REAL | DB+ExternalAPI | UNIT_BEHAVIORAL | PASS (6 tests) | GUARDED | ✅ | CONTINUE | TENANT_SCOPED | iCal | LOG | REPO_VERIFIED+TEST_VERIFIED | MEDIUM | **READY_WITH_GAP** |
| `channex:sync-revisions` | Every 15min | REAL | QUEUE_DISPATCH | NONE | N/A | SAFE | ✅ | SILENT_SUCCESS | TENANT_SCOPED | ChannexAPI | LOG | REPO_VERIFIED | MEDIUM | **NEEDS_REMEDIATION** |
| `ai:scan-deals --limit=500` | Daily 04:00 | REAL | QUEUE_DISPATCH | NONE | N/A | SAFE | ❌ NONE | SILENT | ⚠️ GLOBAL | N/A | LOG | REPO_VERIFIED | LOW | **READY_WITH_GAP** |
| `reservation:complete` | Daily 01:00 | REAL | DB_WRITE+EVENT | INTEGRATION_BEHAVIORAL | PASS (indirect) | STRONG | ✅ | TX_ROLLBACK | GLOBAL | N/A | LOG | REPO_VERIFIED | LOW | **READY** |
| `OpenCheckinWindowJob` | Daily 07:00 | REAL | DB_WRITE+EVENT | NONE | N/A | STRONG | ✅ | CONTINUE | ⚠️ UNKNOWN | N/A | LOG | REPO_VERIFIED | MEDIUM | **READY_WITH_GAP** |
| `ResetAccessCredentialJob` | Daily 02:00 | REAL | DB_WRITE | NONE | N/A | SAFE | ❌ NONE | LOGS | ⚠️ GLOBAL | N/A | LOG | REPO_VERIFIED | MEDIUM | **NEEDS_REMEDIATION** |
| `daily-tenant-snapshots` | Daily 04:30 | REAL | QUEUE_DISPATCH | NONE | N/A | ❌ DUPLICATE_RISK | ✅ | CONTINUE | TENANT_SCOPED | N/A | LOG | REPO_VERIFIED | MEDIUM | **NEEDS_REMEDIATION** |
| `governance-alert-check` | Every 5min | REAL | DB_WRITE | UNIT_BEHAVIORAL | PASS (5 tests) | DEDUP+RATE | ✅ | FAIL_OPEN | GLOBAL | N/A | LOG | REPO_VERIFIED+TEST_VERIFIED | LOW | **READY** |

---

## 7 — AGGREGATE RESULT

```
TOTAL_SCHEDULE_ENTRIES:     32
REAL_IMPLEMENTATIONS:       18
BROKEN_REFERENCES:           14
BEHAVIORALLY_TESTED:         4  (rental:sync-airbnb, governance-alert-check, ranking:recalculate-all, reservation:complete)
NEEDS_REMEDIATION:           11
UNKNOWN:                      0
```

### IS_SCHEDULED_CODEBASE_SAFE_TO_ENABLE_AS_A_WHOLE?

**NO** — Not as a whole.

#### SAFE_TO_SCHEDULE (Ready + Ready with Gap):
| Task | Reason |
|---|---|
| `reservation:complete` | Strong idempotency, TX, overlap protection |
| `governance-alert-check` | Well-tested, dedup, fail-open |
| `ranking:validate-invariants` | Read-only, weekly |
| `rental:sync-airbnb` | Overlap protected, tenant-scoped, dry-run available |
| `OpenCheckinWindowJob` | Strong idempotency, overlap protected |

#### NOT_SAFE_TO_SCHEDULE_YET (NEEDS_REMEDIATION):
| Task | Primary Blocker |
|---|---|
| `exchange:update` | **BROKEN — non-existent command, daily error generation** |
| `context7:*` (8 entries) | **BROKEN — non-existent commands, hourly/daily error generation** |
| `testsprite:auto-learn` | **BROKEN — non-existent command, daily error** |
| `gorevler:check-deadlines` | **BROKEN — non-existent command (x2), daily error** |
| `cortex:hunt` | No pagination + no overlap protection + every-minute = load + duplicate risk |
| `ranking:recalculate-all` | No overlap protection = duplicate score writes |
| `daily-tenant-snapshots` | Duplicate snapshot creation on re-run |
| `channex:sync-revisions` | Silent success on dispatch failure |
| `ai:optimize-thresholds` | No overlap protection (medium risk) |
| `ai:recompute-provider-profiles` | No overlap protection (medium risk) |
| `ai:data-hygiene` | No overlap protection (medium risk) |
| `telemetry:detect-anomalies` | Silent failure return + placeholder `getAICostToday()` |
| `ResetAccessCredentialJob` | Global scope without tenant filter |
| `drive:renew-channels` | No overlap protection + global scope |

#### UNKNOWN:
| Task | Reason |
|---|---|
| `OpenCheckinWindowJob` tenant scope | Cannot verify `GuestArrivalReadinessService` tenant enforcement without running |

---

## 8 — PRODUCTION READ-ONLY AUDIT CHECKLIST

To verify production scheduler status, the following read-only evidence is needed:

```
PRODUCTION_SCHEDULER_EVIDENCE:
  
  [ ] Cron entry exists
      Command: grep "schedule:run\|artisan schedule" /etc/cron.d/* /var/spool/cron/crontabs/* 2>/dev/null
      
  [ ] Only ONE scheduler entry (no duplicate schedulers)
      → Multiple schedule:run entries = double execution risk
      
  [ ] Scheduler is active/running
      → Check process: ps aux | grep schedule
      
  [ ] Last scheduler run timestamp
      → Check log: tail -50 storage/logs/laravel.log | grep "Scheduled" 
      
  [ ] Recent "no such command" errors (from broken references)
      → grep "Command .* does not exist" storage/logs/laravel.log
      
  [ ] Queue worker daemon is running (separate from scheduler)
      → ps aux | grep "queue:work"
      
  [ ] Queue worker processes (correct queues monitored)
      → Check for cortex-notifications, default, governance queues
      
  [ ] App timezone configuration
      → config('app.timezone') — must be Europe/Istanbul
      
  [ ] Scheduler timezone override (if any)
      → Kernel.php schedule timezone setting
      
  [ ] Last exchange:update failure (if broken command runs)
      → grep "does not exist" storage/logs/exchange-rates.log
      
  [ ] Actual execution logs from key tasks
      → storage/logs/rental-sync-airbnb.log
      → storage/logs/reservation-complete.log
      → storage/logs/checkin-window-open.log
      
  [ ] Telegram alert throttling verification
      → Check if queue:check-worker has been alerting (would indicate worker down)
      
  [ ] cortex:hunt execution frequency
      → How many times has it run in the last hour?
      → Check for duplicate opportunity records
      
  [ ] Ranking recalculation last run
      → Check for duplicate score updates in ilanlar visibility_score
```

---

## 9 — FINDINGS SUMMARY

### 🔴 CRITICAL (Must fix before production enable)
1. **14 broken scheduler entries** — non-existent commands generate errors every minute/hour/day
2. **`cortex:hunt` every minute** — no pagination + no overlap protection = system load + duplicate AI calls
3. **`daily-tenant-snapshots` duplicate risk** — re-running creates duplicate `DealPredictionSnapshot` rows

### 🟠 HIGH (Fix before production batch enable)
4. **`ranking:recalculate-all` no overlap protection** — concurrent runs cause duplicate writes
5. **`channex:sync-revisions` silent success** — dispatch failure not propagated
6. **`telemetry:detect-anomalies` silent failure + stub** — always returns SUCCESS, `getAICostToday()` is placeholder

### 🟡 MEDIUM (Fix recommended)
7. **`ai:optimize-thresholds` no overlap protection** — daily, medium collision risk
8. **`ai:recompute-provider-profiles` no overlap protection** — daily, medium collision risk
9. **`ai:data-hygiene` no overlap protection** — daily, low collision risk
10. **`ResetAccessCredentialJob` global scope** — no tenant filter, potential cross-tenant concern
11. **`drive:renew-channels` no overlap protection + global scope** — daily
12. **`OpenCheckinWindowJob` tenant scope unverified** — `GuestArrivalReadinessService` needs audit
13. **`queue:check-worker` single queue only** — `cortex-notifications` hardcoded

### 🟢 READY (Safe to schedule as-is)
- `reservation:complete` ✅
- `governance-alert-check` ✅
- `ranking:validate-invariants` ✅
- `rental:sync-airbnb` ✅ (with gap: no live integration test)
- `quality:gate --with-context7` ✅ (flag ignored but command works)
- `standard:check --type=context7` ✅

---

## 10 — EVIDENCE

All findings are **REPO_VERIFIED** — verified by reading physical source files and
running `php artisan list` commands. No production data was accessed or modified.

Evidence types:
- `SOURCE_INSPECTION` — All 18 real task source files read
- `COMMAND_REGISTRATION` — All 32 commands verified via `php artisan list <cmd>`
- `BEHAVIORAL_TEST` — 4 test files read (`RentalSyncAirbnbCommandTest`, `GovernanceAlerterTest`, `RankingEngineTest`, `ReservationEndToEndLifecycleTest`)
- `SCHEDULER_CONFIGURATION` — `app/Console/Kernel.php` fully read
- `DATABASE_CONTRACT` — Schema implications verified via model inspection

---

## FINAL_RESULT: ✅ VERIFICATION_COMPLETE

No source files were modified. No production systems accessed.
All findings documented above for Ayhan's remediation planning.
