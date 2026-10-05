# Yalıhan OS — Project State

**Son Güncelleme:** 2026-10-05 | **HEAD:** 9baf6021 | **Oturum:** CDA_006_007_VERIFIED

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
