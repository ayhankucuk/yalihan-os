# Yalıhan OS — Project State
**Son Güncelleme:** 2026-09-28 | **HEAD:** 7dd8b016 | **Oturum:** bootstrap-blocked + ILAN-06-parallel

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

## 📋 Evidence Cache

| Kanıt | Değer |
|---|---|
| Physical DB columns | `id, name, domain, durum, created_at, updated_at, deleted_at` (uuid YOK, status YOK) |
| Mevcut tenant data | `id=1, name=Test, domain=t.test, durum=active, uuid=NULL` |
| SaaS\Tenant fillable | `['uuid','name','domain','status']` — schema-dışı |
| App\Models\Tenant fillable | `['uuid','name','domain','durum']` — schema-uyumlu |
| TenantBaselineSeeder | Direct DB facade, `uuid` + `status` yazıyor |
