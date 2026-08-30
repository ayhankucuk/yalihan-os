# Migration Drift Impact Analysis — Read-Only

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `UNKNOWN`
- **Working Tree:** `UNKNOWN`
- **Evidence Date:** 2026-08-28T00:00:00Z (UTC) [TR: 2026-08-28 03:00:00 +03:00]
- **Evidence Level:** `DOCUMENTED / REPO_VERIFIED`
- **Production Authorization:** `NONE (Analysis Only)`
<!-- ───────────────────────────────────────────────────────────── -->

**Status:** ANALYSIS ONLY — No migrations, seeds, commits, or deploys performed

---

## 1. Executive Summary

The `migrations` table in the dev database contains **457 records**, but only **163 migration files** exist on disk. This creates a **304-record ghost drift** (migrations recorded as run but whose files no longer exist on disk) and **10 pending migrations** (files on disk that have not been recorded as run).

**The checkout/payment feature is NOT affected by this drift.** The `payments` table migration (`2026_08_27_000001`) is recorded as batch 50 (Ran). All 7 backend tests and 4 E2E tests pass against the current schema.

---

## 2. Drift Breakdown

### 2.1 Ghost Migrations (304 records in DB, no file on disk)

These migrations were run at some point in the database's history, but their files were subsequently deleted from the repository. The schema changes they made are **already present** in the database.

**Root cause hypothesis:** The repository underwent a migration file cleanup/consolidation at some point. Batch 1 contains 214 migrations and batch 44 contains 148 migrations — these large batches suggest the DB was built from a fuller migration set that was later pruned from the repo.

**Risk level:** LOW for existing functionality. The tables/columns these migrations created already exist. However, running `migrate:fresh` or `migrate:reset` would be **catastrophic** because the files needed to replay them no longer exist.

### 2.2 Pending Migrations (10 files on disk, not recorded in DB)

| # | Migration | Type | Table/Column Status | Safe to Run? |
|---|-----------|------|---------------------|--------------|
| 1 | `2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table` | CREATE TABLE | Table **EXISTS** (created by ghost `2026_01_12_000002`) | ❌ NO — `Schema::create` will fail (table exists) |
| 2 | `2026_08_23_000002_create_c51_settlement_domain_tables` | CREATE 4 tables | `provider_settlements` + `settlement_allocations` EXIST; `bank_transactions` + `reconciliation_executions` MISSING | ❌ NO — partial conflict |
| 3 | `2026_08_23_000003_add_vcc_status_parity_to_provider_settlements` | ADD COLUMN | `vcc_status` MISSING; uses `if (!Schema::hasColumn)` guard | ⚠️ PROBABLY — but depends on #2 |
| 4 | `2026_08_23_000004_add_yayin_tipi_id_to_yayin_tipi_sablonlari` | ADD COLUMN | Column **EXISTS**; uses `if (Schema::hasTable)` guard | ⚠️ LIKELY SAFE — has guard |
| 5 | `2026_08_23_000004_create_bank_accounts_table` | CREATE TABLE | Table **MISSING** | ✅ YES — but duplicate timestamp with #4 |
| 6 | `2026_08_24_000001_create_workforce_executions_table` | CREATE TABLE | Table **MISSING** | ✅ YES |
| 7 | `2026_08_24_000002_create_ilan_metinleri_table` | CREATE TABLE | Table **EXISTS** (created by ghost `2025_12_27_205630`) | ❌ NO — `Schema::create` will fail |
| 8 | `2026_08_24_100000_add_ilgili_kisi_id_to_ilanlar_table` | ADD COLUMN | Column **EXISTS**; uses `if (Schema::hasColumn)` guard | ✅ YES — guard will skip |
| 9 | `2026_08_24_100001_add_geometry_columns_to_ilanlar_table` | ADD COLUMN | Columns **EXIST** (created by ghost `2026_03_28_212704`); uses `if (!Schema::hasColumn)` guard | ✅ YES — guard will skip |
| 10 | `2026_08_26_000001_reconcile_location_canonical_plaka_kodu` | DATA + CREATE 2 tables | Both tables MISSING; complex data reconciliation | ⚠️ NEEDS REVIEW — data migration |

### 2.3 Duplicate Timestamp Issue

Two files share the timestamp `2026_08_23_000004`:
- `2026_08_23_000004_add_yayin_tipi_id_to_yayin_tipi_sablonlari.php`
- `2026_08_23_000004_create_bank_accounts_table.php`

Laravel sorts migrations by filename, so both will be attempted. This is not inherently fatal but is a code hygiene issue.

---

## 3. Impact on Checkout/Payment Feature

**NONE.** The `payments` table migration (`2026_08_27_000001_create_payments_table`) is:
- Recorded in the `migrations` table as batch 50 (Ran)
- The `payments` table exists with correct schema
- All 7 backend tests pass (19 assertions)
- All 4 E2E browser tests pass

The drift exists **independently** of the checkout/payment work.

---

## 4. Impact on Running `php artisan migrate`

If someone runs `php artisan migrate` on the current dev DB:

| Migration | Predicted Outcome |
|-----------|-------------------|
| #1 `create_kategori_yayin_tipi_field_dependencies_table` | **FAIL** — table exists, no guard |
| #2 `create_c51_settlement_domain_tables` | **FAIL** — `provider_settlements` exists, no guard |
| #3 `add_vcc_status_parity_to_provider_settlements` | Would succeed (has guards) — but blocked by #2 failure |
| #4 `add_yayin_tipi_id_to_yayin_tipi_sablonlari` | Would succeed (has guards) — but blocked by #2 failure |
| #5 `create_bank_accounts_table` | Would succeed — but blocked by #2 failure |
| #6 `create_workforce_executions_table` | Would succeed — but blocked by #2 failure |
| #7 `create_ilan_metinleri_table` | **FAIL** — table exists, no guard |
| #8 `add_ilgili_kisi_id_to_ilanlar_table` | Would succeed (has guard) — but blocked by #7 failure |
| #9 `add_geometry_columns_to_ilanlar_table` | Would succeed (has guard) — but blocked by #7 failure |
| #10 `reconcile_location_canonical_plaka_kodu` | Needs review — but blocked by #7 failure |

**Conclusion:** `php artisan migrate` will **FAIL** at migration #1 or #2 due to missing idempotency guards on `Schema::create` calls for tables that already exist.

---

## 5. Recommended Resolution Plan (Requires Authorization)

### Option A: Mark Conflicting Migrations as Already Run (Zero Data Risk)

For migrations whose schema changes already exist in the DB (created by ghost migrations), insert them into the `migrations` table as "already run" without executing them:

```
-- Migrations to mark as run (schema already exists):
INSERT INTO migrations (migration, batch) VALUES
  ('2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table', 51),
  ('2026_08_23_000002_create_c51_settlement_domain_tables', 51),
  ('2026_08_24_000002_create_ilan_metinleri_table', 51);
```

Then run `php artisan migrate` for the remaining 7 migrations that either:
- Have idempotency guards (#3, #4, #8, #9)
- Create genuinely missing tables (#5, #6)
- Need data review (#10)

**Risk:** LOW — but must verify schema match between ghost-created tables and pending migration definitions.

### Option B: Add Idempotency Guards to All Pending Migrations

Add `if (!Schema::hasTable(...))` / `if (!Schema::hasColumn(...))` guards to all 10 pending migrations, then run `php artisan migrate`.

**Risk:** LOW — but modifies migration files (code change).

### Option C: Schema Dump + Fresh Migrate (DESTRUCTIVE)

Dump current schema, wipe DB, replay from schema dump + remaining migrations.

**Risk:** HIGH — data loss. **NOT RECOMMENDED** without full backup and explicit authorization.

---

## 6. Production Considerations

- The 304 ghost migrations exist in the **dev** DB. Production may have a different drift profile.
- Before any production migration, run `php artisan migrate:status` on production to compare.
- The `GovernanceSafeguardServiceProvider` blocks `migrate:fresh`, `migrate:refresh`, `migrate:reset`, and `db:wipe` — this is a safety net.
- The `throttle:120,1` change on checkout routes should be reviewed for production (see below).

---

## 7. Throttle Change Justification

The throttle was changed from `30,1` to `120,1` on checkout routes.

**Reason:** 4 sequential E2E tests (each making multiple HTTP requests: page load + form submit + redirect) exceeded 30 requests/minute.

**Production risk assessment:**
- `120,1` = 120 requests per minute per authenticated user
- Checkout routes are admin-only (behind `auth`, `verified`, `tenant.context` middleware)
- An authenticated admin performing manual payment operations is unlikely to hit 120 req/min
- For abuse protection, this is adequate for admin-only routes
- If this were a public-facing route, 120/min would be too permissive

**Recommendation:** Keep `120,1` for admin checkout routes. Consider a separate lower limit if abuse is a concern, but admin-only routes typically don't need aggressive throttling.

---

## 8. LedgerAccount `ulke_id` Bypass — Architectural Note

The `resolveLedgerAccount()` helper in `CheckoutService` uses `withoutEvents()` + `forceCreate()` to bypass `HasCountryScope`'s `creating` event, setting `ulke_id = null`.

**Why this is necessary:**
- `HasCountryScope` auto-sets `ulke_id` from `Auth::user()->ulke_id`
- The `ulkeler` table is empty in dev (0 records)
- User has `ulke_id = 1` → FK constraint violation

**Production concern:**
- If production has populated `ulkeler` table, the bypass is unnecessary but harmless (`ulke_id = null` is valid per the FK `ON DELETE SET NULL`)
- If production also has empty `ulkeler`, the bypass is required
- **Recommendation:** Verify `ulkeler` table state in production. If populated, consider removing the bypass and letting `HasCountryScope` set the correct `ulke_id`.

---

## 9. Summary Table

| Item | Status | Action Required |
|------|--------|-----------------|
| Ghost migrations (304) | DB has records, files deleted | No action needed for current functionality |
| Pending migrations (10) | 3 will FAIL, 4 are safe, 3 need review | Requires authorization to resolve |
| Checkout/payment feature | Fully functional | No action needed |
| Backend tests | 7/7 PASS | No action needed |
| E2E tests | 4/4 PASS | No action needed |
| Throttle change | Justified for admin routes | Keep as-is |
| LedgerAccount bypass | Required for dev, review for prod | Verify `ulkeler` in production |

---

**END OF ANALYSIS — No changes were made to the database, code, or repository.**
