## 🔍 Production Target (UPDATED 2026-10-03)

| Field | Value | Source |
|---|---|---|
| Production IP | `157.180.116.63` | Verified — multiple repo sources |
| SSH USER | `root` | Production auth records |
| App Path | `/opt/yalihan2026/current` | rc2-production-deploy.sh |
| Legacy Oracle Cloud | `168.138.101.124` | SUPERSEDED — docker-compose.production.yml |

**Note:** `docs/architecture-lite.md` lists Oracle Cloud IP — STALE. Active production is Hetzner at `157.180.116.63`.
# Yalıhan OS — Project State
**Son Güncelleme:** 2026-10-05 | **HEAD:** 3b1f0453 | **Oturum:** POI-FIXED

---

## 🟡 PARTIAL BOOTSTRAP — BLOCKED

### Yapılan
- `RoleSeeder` → mutation uygulandı, 5 role mevcut (id=1 Admin, id=2-5 yeni)
- `ADMIN_LOCAL_PASSWORD=admin123` → .env'e eklendi

### Blokeli
| Görev | Durum | Neden |
|---|---|---|
| `TenantBaselineSeeder` | BLOCKED | CDA-006: `uuid` + `status` yazıyor, physical DB `durum` bekliyor, `uuid` yok |
| `AdminUserSeeder` | HELD | TenantBaselineSeeder'a bağlı |

### Root Cause (CDA-006)
```
App\Models\SaaS\Tenant (fillable: status) ≠ Physical DB (kolon: durum)
App\Models\Tenant (fillable: durum)       = Physical DB ✅
Runtime consumer'lar SaaS\Tenant kullanıyor  = Schema-dışı model aktif ❌
TenantBaselineSeeder direct DB facade kullanıyor = uuid+status yazmaya çalışıyor ❌
```

**Çözüm:** Ayhan kararı bekleniyor (A: seeder fix, B: model canonicalization, C: migration, D: daha fazla araştırma)

---

## ✅ ILAN-06 — PARALLEL (Bağımsız)

Domain convergence çalışması devam ediyor. Tenant drift'ten bağımsız.

---

## 🏛️ Mimari Durum

### Tenant Split-Brain (CDA-006 — OPEN)
- `App\Models\Tenant` ← schema-uyumlu (durum)
- `App\Models\SaaS\Tenant` ← schema-dışı (status) — RUNTIME'da aktif kullanılıyor
- Middleware + BillingLedgerService + SubscriptionService → SaaS\Tenant'a bağlı
- Physical DB: `durum` kolonu var, `status` yok, `uuid` yok

### Migration Drift (CDA-005 — DOCUMENTED)
- İki farklı migration `tenants` tablosu oluşturmaya çalışıyor
- `if (!Schema::hasTable())` guard'ları var — race condition riski

---

## 🔐 Güvenlik Durumu

| Alan | Durum |
|---|---|
| Tenant Isolation | CRITICAL risk — middleware yanlış model kullanıyor olabilir |
| Role permissions | RoleSeeder mutation uygulandı |
| Admin user | AdminUserSeeder held |

---

## 📁 Kritik Dosyalar

| Dosya | Bulgu |
|---|---|
| `app/Models/SaaS/Tenant.php` | `fillable: ['uuid','name','domain','status']` → `durum` değil |
| `app/Models/Tenant.php` | `fillable: ['uuid','name','domain','durum']` → schema-uyumlu |
| `app/Services/SaaS/TenantContextService.php` | `App\Models\SaaS\Tenant` kullanıyor |
| `app/Http/Middleware/SetTenantContext.php` | `App\Models\SaaS\Tenant::find()` (L68) |
| `database/migrations/2026_05_06_173500_add_remaining_columns_run64.php` | `durum` ekliyor, `status` DEĞİL |
| `database/seeders/TenantBaselineSeeder.php` | `uuid` + `status` direct DB yazıyor |
| `database/migrations/2026_05_03_010000_create_saas_monetization_foundation_tables.php` | `uuid` + `status` ile tablo oluşturuyor |

---

## ✅ EXT-06E — WhatsApp W2/W3 Regression FIXED (2026-10-03)

| Item | Detail |
|---|---|
| Commit | `9b8aca27` — EXT-06E: Fix WhatsApp W2/W3 tenant ingress regression |
| Bug 1 | `\Http::withToken()` → FQCN `\Illuminate\Support\Facades\Http::withToken()` |
| Bug 2 | `Lead::where()` → `Lead::withoutGlobalScopes()->where()` in W2/W3 |
| Root Cause | `finally{}` clears TenantContextService AFTER response, BEFORE assertions |
| Architecture | Intentional (singleton cleanup). Tests must use `withoutGlobalScopes()` |
| Verified | WhatsAppTenantIngressTest 19/19, Webhook 37/37, LeadTenantBoundary 10/10 |

---

## ✅ CDA-001 — CLOSED / STALE_FINDING (2026-10-05)

| Attribute | Value |
|---|---|
| **Claim** | Antigravity auto-loads `.agents/AGENTS.md` as competing authority |
| **Status** | `STALE_FINDING / ORIGINAL_FINDING_NOT_PROVEN` |
| **Decision** | NO REMEDIATION |
| **Reason** | `.agents/AGENTS.md` NOT loaded by any agent |
| **Evidence** | TOOL_RUNTIME verification → REJECTED |
| **Pipeline Lesson** | Contradictory Evidence Gate needed |

---

## 📋 Evidence Cache

| Kanıt | Değer |
|---|---|
| Physical DB columns | `id, name, domain, durum, created_at, updated_at, deleted_at` (uuid YOK, status YOK) |
| Mevcut tenant data | `id=1, name=Test, domain=t.test, durum=active, uuid=NULL` |
| SaaS\Tenant fillable | `['uuid','name','domain','status']` — schema-dışı |
| App\Models\Tenant fillable | `['uuid','name','domain','durum']` — schema-uyumlu |
## ✅ CDH-001 — CLOSED (2026-10-04)

| Item | Detail |
|---|---|
| Commit | `8333bd6f` |
| Finding | `EslesmeController` phantom `one_cikan` column reference |
| Canonical Authority | `eslesmeler` table NEVER had `one_cikan` column |
| Remediation | `one_cikan` removed from select clause + validation rules |
| Verification | VERIFIED_PASS (Ayhan, 2026-10-04) |
| Test Result | SecurityTest: 17/17 PASS |

**Note:** 6 RuntimeTest failures = pre-existing F02-R fail-closed regression. OUT OF SCOPE. Backlog item: `F02R-TEST-UPDATE`.

---

## ✅ CDH-002 — CLOSED / PRODUCTION_VERIFIED (2026-10-04)

| Attribute | Value / Evidence |
|---|---|
| **Status** | `CLOSED` |
| **Evidence Level** | `PRODUCTION_VERIFIED` |
| **Source Implementation Commit** | `fdc421bc3f55ac1c4f2ee73b8b67df87b8c1c2d6` (REPO_VERIFIED + TEST_VERIFIED) |
| **Certified Production Artifact** | `9cb41e20405ae8b561c0d6ecc71f78d76f4bdcd1` (Isolated backport commit) |
| **Production Base Before Deploy** | `1172824699243659c87977ccca8a9b0c307101fa` |
| **Production Target** | `root@157.180.116.63` (`/opt/yalihan2026/current`, `release-candidate/RC2`) |
| **Production Verification Task** | `CDH002_INDEPENDENT_PRODUCTION_VERIFY_07 = PASS` |
| **Runtime Artifact Hash** | SHA256: `a129ad4880e865a649cb33d83c2711e3cc4027f366269e8f95a4677612a2b0d0` (Host = App = Queue) |
| **Physical Schema Contract** | `users.aktiflik_durumu` tinyint(1) default 1; `is_active` physical column ABSENT |
| **Runtime Query Contract** | `User::active()` query count = `User::where('aktiflik_durumu', true)` (3 = 3) |
| **Critical Provenance Note** | `fdc421bc` remains the original source implementation. `9cb41e20` is the isolated artifact actually deployed and independently verified in production. Primary worktree HEAD (`fdc421bc`) is not required to equal production HEAD (`9cb41e20`). Do NOT redeploy `fdc421bc` as CDH-002. |

---

## 📋 Evidence Cache
| TenantBaselineSeeder | Direct DB facade, `uuid` + `status` yazıyor |

## Active Protocol Locks
## ✅ POI_ANALIZ_NULL_COORDINATES — CLOSED (2026-10-05)

| Attribute | Value |
|---|---|
| **Status** | `CLOSED` |
| **Evidence Level** | `TEST_VERIFIED` (production: UNKNOWN) |
| **Commit** | `3b1f0453` — fix(analytics): guard POI highlights calculation against null listing coordinates |
| **Root Cause** | `IlanAnalizService::getDetayliRapor()` calls `PoiService::getHighlights($ilan->lat, $ilan->lng)` without null-check |
| **Symptom** | Published ilan without coordinates → HTTP 500 on `/ilan/{id}` detail page |
| **Fix** | Guard: `($ilan->lat !== null && $ilan->lng !== null) ? $poiService->getHighlights(...) : []` |
| **Test** | `tests/Feature/Analytics/IlanAnalizServiceNullCoordinatesTest.php` — 4 scenarios |
| **PoiService Contract** | UNCHANGED — strict spatial contract preserved |

---

## 📋 Evidence Cache
| TenantBaselineSeeder | Direct DB facade, `uuid` + `status` yazıyor |

## Active Protocol Locks

