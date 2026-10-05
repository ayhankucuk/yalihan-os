# Yalıhan OS — Project State

**Son Güncelleme:** 2026-10-05 | **HEAD:** 216a2c93 | **Oturum:** CRM_03_REMEDIATION

---

## 🔍 Production Target

| Field | Value | Source |
|---|---|---|
| Production IP | `157.180.116.63` | SSH verified |
| SSH USER | `root` | Production auth records |
| App Path | `/opt/yalihan2026/current` | rc2-production-deploy.sh |
| Database | `yalihanai_v2_production` | .env |
| Production Git | `9cb41e20` | CDH-002 isolated artifact |

---

## ✅ CLOSED FINDINGS (2026-10-05)

### CRM-03 TALEP CRITERIA FIX — 2026-10-05

| Attribute | Value |
|---|---|
| **Task ID** | CRM_03_TALEP_CRITERIA_AND_MATCHING_REVALIDATION_01 |
| **Evidence Level** | REPO_VERIFIED (migration created, syntax validated) |
| **Root Cause** | Form field name drift: `min_alan`/`max_alan` vs canonical DB `min_metrekare`/`max_metrekare` |
| **Issue 2** | Non-existent columns: `min_oda_sayisi`/`max_oda_sayisi` had no DB columns |

#### Ayhan Human Gate Decision (2026-10-05)
- **Area Contract:** Canonical = `min_metrekare` / `max_metrekare`
- **Room Criterion:** Additive migration approved for `min_oda_sayisi` / `max_oda_sayisi`

#### Fixes Applied
1. Form field names corrected: `min_alan`/`max_alan` → `min_metrekare`/`max_metrekare`
2. Migration created: `2026_10_05_000001_add_oda_sayisi_columns_to_talepler_table.php`
3. Model `$fillable` and `$casts` updated
4. Controller validation updated (store + update)
5. TalepAuthorityService updated
6. Domain DTOs updated (TalepCreateCommand, TalepUpdateCommand)
7. CreateTalepUseCase updated

#### Canonical Contract
| Field | Form | Validation | DB |
|---|---|---|---|
| Alan (min) | `min_metrekare` | ✅ | `min_metrekare` |
| Alan (max) | `max_metrekare` | ✅ | `max_metrekare` |
| Oda (min) | `min_oda_sayisi` | ✅ | `min_oda_sayisi` (NEW) |
| Oda (max) | `max_oda_sayisi` | ✅ | `max_oda_sayisi` (NEW) |

#### ✅ Additional Fixes (2026-10-05 - CRM_03_COMPLETION_AND_MATCHING_INTEGRATION_01)
1. **analiz_detay.blade.php** — Stale field references fixed:
   - `$talep->oda_sayisi` → `min_oda_sayisi`/`max_oda_sayisi` range display
   - `$talep->metraj` → `min_metrekare`/`max_metrekare` range display
   - `$eslesme['emlak']->metraj` → `brut_m2 ?? alan_m2` canonical

2. **DemandMatchingEngine.php** — Oda criteria SQL filtering added:
   - `min_oda_sayisi` → hard `>=` constraint (no tolerance)
   - `max_oda_sayisi` → hard `<=` constraint (no tolerance)
   - NULL = no constraint applied

3. **TalepEditFormContractTest.php** — Test contamination fixed:
   - All `min_alan`/`max_alan` → `min_metrekare`/`max_metrekare`
   - 3 new canonical criteria tests added

4. **DemandMatchingEngineTest.php** — 5 new room criteria regression tests

#### Test Results (2026-10-05)
| Test Suite | Result | Duration |
|---|---|---|
| TalepEditFormContractTest | 18 PASSED | 28.73s |
| TalepStoreContractIndependentVerificationTest | 6 PASSED | 9.80s |
| DemandMatchingEngineTest | 10 PASSED | 8.23s |

#### Canonical Contract (Updated)
| Field | Form | Validation | DB | Matching |
|---|---|---|---|---|
| Alan (min) | `min_metrekare` | ✅ | `min_metrekare` | ✅ SQL filter |
| Alan (max) | `max_metrekare` | ✅ | `max_metrekare` | ✅ SQL filter |
| Oda (min) | `min_oda_sayisi` | ✅ | `min_oda_sayisi` (NEW) | ✅ SQL filter |
| Oda (max) | `max_oda_sayisi` | ✅ | `max_oda_sayisi` (NEW) | ✅ SQL filter |

#### ⚠️ Known Issues
- ~~Test contamination: `TalepEditFormContractTest.php` uses `min_alan`/`max_alan`~~ **FIXED**
- Production schema: UNKNOWN — migration not applied to production

### CDA-006 & CDA-007 — PRODUCTION_VERIFIED / NON_BLOCKER

| Finding | Status | Evidence |
|---|---|---|
| **CDA-006** | `VERIFIED — NON_BLOCKER` | Production has 4 columns, all synchronized via DB defaults |
| **CDA-007** | `VERIFIED — NON_BLOCKER` | All 4 columns = `'active'` |

#### Production Schema (yalihanai_v2_production.tenants)
```
Field               Type             Null  Default
--------------------------------------------------
aktiflik_durumu     varchar(255)     NO    'active'  -- Context7 canonical
status              varchar(255)     NO    'active'  -- Legacy
durum               varchar(50)      NO    'active'  -- Legacy
is_active           tinyint(1)       NO    1        -- Legacy
```

#### Key Finding
- **Code Drift:** REAL — `SaaS\Tenant` writes `status`, ignores `aktiflik_durumu`
- **Data Drift:** NONE — All 4 columns synchronized via DB defaults
- **Runtime Impact:** NONE — Production stable

#### Ayhan Decision (Optional — Not Urgent)
- **Option A:** Add `aktiflik_durumu` to `$fillable` + sync logic
- **Option C:** Migration to consolidate to single column

**Evidence:** `.project-brain/CDA_006_007_PRODUCTION_VERIFICATION.md`

---

## ✅ POI_ANALIZ_NULL_COORDINATES — CLOSED (2026-10-05)

| Attribute | Value |
|---|---|
| **Status** | `CLOSED` |
| **Evidence Level** | `TEST_VERIFIED` (production: UNKNOWN) |
| **Commit** | `3b1f0453` |
| **Root Cause** | `IlanAnalizService::getDetayliRapor()` calls `PoiService::getHighlights()` without null-check |
| **Fix** | Guard: `($ilan->lat !== null && $ilan->lng !== null) ? $poiService->getHighlights(...) : []` |
| **Test** | `tests/Feature/Analytics/IlanAnalizServiceNullCoordinatesTest.php` — 4/4 PASS |

---

## ✅ EXT-06E — WhatsApp W2/W3 Regression FIXED

| Item | Detail |
|---|---|
| Commit | `9b8aca27` |
| Bug 1 | `\Http::withToken()` → FQCN |
| Bug 2 | `Lead::where()` → `withoutGlobalScopes()->where()` |
| Tests | WhatsAppTenantIngressTest 19/19, Webhook 37/37, LeadTenantBoundary 10/10 |

---

## ✅ CDH-002 — CLOSED / PRODUCTION_VERIFIED

| Attribute | Value |
|---|---|
| Status | `CLOSED` |
| Evidence Level | `PRODUCTION_VERIFIED` |
| Certified Production Artifact | `9cb41e20` |
| Production Verification | `CDH002_INDEPENDENT_PRODUCTION_VERIFY_07 = PASS` |

---

## 🟡 BLOCKED TASKS (Production'da Engelli)

| Task | Reason | Solution |
|---|---|---|
| None currently | — | — |

**Note:** CDA-006/007 artık blocker değil. Production stable.

---

## 📋 Scheduled Pipeline Status

| Task | Status | Next Run |
|---|---|---|
| Watchdog (22:00) | ⚙️ CONFIGURED | 2026-10-05 22:00 |
| Drift Hunter (23:00) | ⚙️ CONFIGURED | 2026-10-05 23:00 |
| Morning Triage (09:00) | ⚙️ CONFIGURED | 2026-10-06 09:00 |

---

## 📋 Evidence Cache

| Item | Value |
|---|---|
| Physical DB columns | `id, name, domain, aktiflik_durumu, status, uuid, is_active, created_at, updated_at, deleted_at, durum` |
| Mevcut tenant data | `id=1, aktiflik_durumu='active', status='active', durum='active', is_active=1` |

---

## Active Protocol Locks

None currently.

---

*Son Güncelleme: 2026-10-05*
HOTSPOT_LOCK:database/migrations/2026_10_05_000001_add_oda_sayisi_columns_to_talepler_table.php:Ayhan-CRM03-Commit:2026-10-05T11:39:53Z:3600
HOTSPOT_LOCK:database/schema/mysql-schema.sql:Ayhan-CRM03-Commit:2026-10-05T11:40:36Z:3600
---

## ✅ ADMIN_RBAC_REMEDIATION_01 — CLOSED (2026-10-05)

| Attribute | Value |
|---|---|
| **Task ID** | `ADMIN_RBAC_ROLE_CASE_AUTHORITY_01` |
| **Evidence Level** | `REPO_VERIFIED` |
| **Commit** | `54ad84b8` |
| **ROOT CAUSE** | Hardcoded role validation instead of dynamic DB-driven validation |

### Investigation Results

| Item | Result |
|---|---|
| Canonical Role Authority | `'admin'` (lowercase) — REPO_VERIFIED |
| Canonical Vocabulary | `super-admin \| admin \| danisman \| musteri \| owner` |
| hasRole('admin') calls | 59 in app/, all lowercase ✅ |
| hasRole('Admin') calls | 0 in app/ ✅ |
| Code-level case mismatch | NONE |

### Fixes Applied

1. **UserController.php:** Dynamic validation via `Role::pluck('name')`
2. **index.blade.php:** Dynamic dropdown + `getRoleNames()` instead of legacy `role_id`

### Production Role Data

| Item | Status |
|---|---|
| Current production `roles.name` values | UNKNOWN |
| Production Read-Only Audit | PENDING |
| Migration required | NO — Code-level fix only |

---

## 📋 Session 21 Findings Summary

| Finding | Status | Evidence |
|---|---|---|
| POI null coordinates | CLOSED ✅ | TEST_VERIFIED |
| Scheduled Task Pipeline | VERIFIED ✅ | Design correct |
| ADMIN_RBAC dynamic roles | CLOSED ✅ | REPO_VERIFIED |
