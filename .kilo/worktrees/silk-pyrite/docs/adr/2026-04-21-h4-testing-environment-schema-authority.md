# ADR: H4 Testing Environment — Schema Authority & Dump Drift

**Date:** 2026-04-21
**Status:** Preflight Sealed (execution deferred)
**Risk:** LOW (preflight-only), MEDIUM (when execution begins)
**Scope:** Test infrastructure only, no application code

---

## Context

Full `scripts/quality-gate.sh` run on 21 Nisan 2026 exited with code 1 due to 36 `QueryException` failures in three service test suites:

- `Tests\Unit\Services\MarketIntelligence\PricingPositionServiceTest`
- `Tests\Unit\Services\Property\FeaturePackServiceTest`
- `Tests\Unit\Services\Property\PropertyBulkOperationsServiceTest`

Reported symptom: `Table 'yalihanai_test.ilan_kategorileri' doesn't exist`.

### Preflight Discovery (Actual Root Cause)

The reported symptom was misleading. Direct inspection revealed:

| Check | Result |
|---|---|
| `.env.testing` presence | ✅ Mevcut (DB: `yalihanai_test`) |
| MySQL connection | ✅ OK |
| Live table count in `yalihanai_test` | **218 tables** |
| `migrations` table row count | 330 |
| `database/migrations/*.php` file count | 26 (dump-based reset implied) |
| `ilan_kategorileri` table existence | ✅ **EXISTS** (not missing) |
| `database/schema/mysql-schema.sql` date | 10 Nisan 2026 (11 days stale) |
| `mysql-schema.sql` `CREATE TABLE` count | **198** |
| Live DB vs dump delta | **20 tables drift** |
| `TestCase::initializeTestDatabase()` behavior | `db:wipe` → load dump → `migrate` |

### Critical Insight

`TestCase.php` wipes the testing database and reloads `database/schema/mysql-schema.sql` at the start of each test suite. Because the dump is 11 days old and missing 20 tables, any seeder or fixture that references a post-dump table fails at insert time with the exact `42S02` symptom — even though the table exists in the live DB.

**Therefore:**

- The problem is **not** runtime schema drift.
- The problem is **not** missing migrations.
- The problem is **schema authority drift**: `mysql-schema.sql` has fallen behind as the canonical snapshot of the test-expected schema.

Running `php artisan migrate --env=testing --force` would be a transient fix — the next test suite would reload the stale dump and reproduce the failure.

---

## Decision

H4 is partitioned into clear phases. Only the recon phase is executed in this session.

### H4-P0 — Preflight Recon (✅ EXECUTED)

Read-only environment inspection. No destructive action. Findings recorded above.

### H4-P1 — Playwright Chromium Install (⏳ DEFERRED)

Non-destructive toolchain step:

```
npx playwright install chromium
```

Downloads Chromium binary into `~/Library/Caches/ms-playwright/`. Resolves the second gate blocker (E2E browser missing). Must be run in foreground; no async/nohup.

### H4-P2 — Schema Authority Refresh (⏳ DEFERRED, HIGH-ATTENTION)

Refreshes `database/schema/mysql-schema.sql` to match the canonical source of truth:

```
php artisan schema:dump --prune
```

**Critical Pre-condition — Dump Source Authority:**

The ADR author must **explicitly verify** which database is the canonical SSOT before running this command. Running it against a non-canonical DB would formalize an incorrect schema and compound the drift rather than resolve it.

Verification checklist before H4-P2:

1. Confirm which DB connection `schema:dump` will target in the current environment
2. Confirm that connection's migration state is complete and forward-only
3. Confirm no orphan/half-migrated tables exist in the source DB
4. Review `git diff database/schema/mysql-schema.sql` after dump to validate the delta is expected (net additions for 20 drifted tables, no unexpected removals)
5. If any doubt exists about source DB canonicity, **do not run** — investigate first

### H4-P3 — Verification

1. Isolated smoke test: `php artisan test tests/Unit/Services/MarketIntelligence/PricingPositionServiceTest.php`
2. Full gate rerun: `bash scripts/quality-gate.sh`
3. Exit code is authoritative. "Environment blockers cleared" is the success criterion, not "gate passed".

---

## Consequences

### Positive

- Testing environment becomes self-consistent: `TestCase` bootstrap aligns with canonical schema
- Gate results become interpretable again (env noise removed)
- Future schema drift is visible via `git diff` on `mysql-schema.sql`
- Documents a non-obvious failure mode (stale dump masking as "missing table") for future operators

### Negative / Risks

- `schema:dump --prune` overwrites `database/schema/mysql-schema.sql` — reversible only via git
- If source DB is not canonical, ADR formalizes wrong state
- Chromium install adds ~150MB to developer machines (not CI concern here)
- Does not address the `211 modified / 298 file` working tree — that is H5 scope

### Neutral

- Zero application code change
- Zero runtime behavior change
- Zero SSOT change in application domain

---

## Alternatives Considered

### A. Run `migrate --env=testing --force` only

- **Rejected.** `TestCase` reloads the stale dump at the start of every run, so this is transient at best. It masks the real problem (authority drift) and will re-fail on the next clean run.

### B. Disable `TestCase::initializeTestDatabase()` temporarily

- **Rejected.** Bypasses the schema authority mechanism instead of fixing it. Introduces hidden test-to-test state coupling and violates determinism governance.

### C. Regenerate dump from a hand-crafted migration sequence

- **Rejected.** Over-engineering. `schema:dump --prune` exists precisely for this purpose and is the supported Laravel pathway.

### D. Split H4 into separate Playwright-only and DB-only ADRs

- **Rejected.** Both are testing environment concerns; separating them fragments governance. The phased plan (P0 → P1 → P2 → P3) already provides execution isolation.

---

## Observability Muscle (per Rehabilitation Doctrine)

Per the 21 Nisan system rehabilitation doctrine, every slice must leave the system better able to detect its own disfunction. H4 adds the following observability points:

1. **Schema dump age guard (proposed, not in this ADR):** A future CI/Bekçi check can warn when `database/schema/mysql-schema.sql` mtime exceeds 7 days, preventing recurrence.
2. **Gate error classifier (proposed, not in this ADR):** `scripts/quality-gate.sh` currently prints "OpenClaw Agent Safety Gate FAILED" when any preceding suite fails. This is misleading. A follow-up can attribute failures to the actual failing suite.
3. **This ADR itself:** Future operators encountering the `42S02 ilan_kategorileri missing` symptom can read this document and skip the wrong fix.

---

## References

- `tests/TestCase.php` lines 33–90 (bootstrap mechanism)
- `database/schema/mysql-schema.sql` (10 Nisan 2026 snapshot)
- `.env.testing` (testing DB config)
- `scripts/quality-gate.sh` (gate orchestration)
- Rehabilitation doctrine agreed 2026-04-21 (session memory)
- H2+H3 Closure ADR context (CODE PASS / ENV BLOCKED classification)
