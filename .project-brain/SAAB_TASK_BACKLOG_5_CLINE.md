# SAAB TASK ASSIGNMENT — BACKLOG-5: Lead Tenant Boundary

**Agent:** Cline (Security Agent)
**Priority:** P0 (CRITICAL)
**Branch:** `client/lead-tenant-boundary` (base: `cd798d1`)
**Status:** OPEN — WORKTREE_ASSIGNED
**Önkoşul:** ✅ Tamamlandı (ai_provider_profiles migration kurtarma — Codex Oturum 148)

---

## GÖREV TANIMI

`Lead` modelinde tenant boundary eksik. Cross-tenant lead görünürlüğü ve manipülasyonu riski var.

## KANIT (REPO_VERIFIED)

### 1. Lead Model — `BelongsToTenant` trait yok
- **Dosya:** `app/Models/Lead.php`
- `tenant_id` fillable'da var ama global scope uygulanmıyor
- 22 diğer model `BelongsToTenant` kullanıyor (Property, Kisi, Ilan, vs.) — Lead kullanmıyor
- **Trait dosyası:** `app/Traits/BelongsToTenant.php`
  - `bootBelongsToTenant()` → `TenantScope` global scope ekler
  - `creating` event → `tenant_id` otomatik atar
  - `scopeWithoutTenant()` → tenant scope bypass
  - `scopeForTenant($tenantId)` → belirli tenant'a filtrele

### 2. LeadAuthorityService — tenant filtresi yok
- **Dosya:** `app/Services/CRM/LeadAuthorityService.php`
- **Satır 119:** `Lead::firstOrCreate(['platform' => ..., 'platform_user_id' => ...])` — `tenant_id` yok
- **Satır 119:** `firstOrCreate` search attribute'larında `tenant_id` yok → cross-tenant lead çakışması
- Webhook lead oluştururken `tenant_id` ataması yapılmıyor

### 3. DB Schema
- `leads.tenant_id` kolonu nullable olarak mevcut
- Migration: `2026_05_19_080616_add_tenant_id_to_leads_table.php`
- Unique index: `(platform, platform_user_id)` — `tenant_id` dahil değil

## YAPILACAK İŞLER

### Commit 1: Model — BelongsToTenant trait
1. `app/Models/Lead.php` — `use BelongsToTenant;` ekle
2. `use App\Traits\BelongsToTenant;` import ekle
3. Mevcut `tenant_id`'li kayıtlar korunmalı (nullable → dolu)
4. Global scope otomatik `tenant_id` filtresi uygulayacak

### Commit 2: Authority Service — tenant-scoped queries
1. `app/Services/CRM/LeadAuthorityService.php` satır 119:
   - `firstOrCreate` search attribute'larına `tenant_id` ekle
   - `tenant_id` → `TenantContextService::getTenant()->id` veya `auth()->user()->tenant_id`
2. `ensureScoreExists()` → `AILeadScore::where('lead_id', $lead->id)` → tenant-scoped query

### Commit 3: Migration — unique index güncelleme
1. `(platform, platform_user_id)` unique index → `(tenant_id, platform, platform_user_id)`
2. Eski index drop, yeni index create
3. `BLOCKED_PENDING_PRODUCTION_AUTH` — production deploy ayrı

### Commit 4: Test
1. `tests/Feature/Security/LeadTenantBoundaryTest.php`
2. Test senaryoları:
   - Lead oluşturma → doğru `tenant_id` ile
   - Cross-tenant lead görünürlük → erişilemez
   - Webhook lead → `tenant_id` atanmış
   - `firstOrCreate` → aynı `platform_user_id` farklı tenant → ayrı kayıtlar

## EXIT CRITERIA

1. `Lead::query()->where('id', $id)->first()` → otomatik tenant scope
2. Webhook'dan gelen lead → doğru `tenant_id` ile kaydedilir
3. Unique index `(tenant_id, platform, platform_user_id)` DB'de mevcut
4. Cross-tenant lead erişimi → 403/404
5. Test: tüm senaryolar PASS

## ÖNCELİK SIRASI

1. **Model** (Commit 1) — en kritik, global scope eksik
2. **Authority Service** (Commit 2) — webhook lead oluşturma
3. **Migration** (Commit 3) — unique index (production auth bekler)
4. **Test** (Commit 4) — doğrulama

## BAĞLANTILI DOSYALAR

- `app/Models/Lead.php` — model
- `app/Traits/BelongsToTenant.php` — trait (mevcut, kullanılacak)
- `app/Scopes/TenantScope.php` — global scope
- `app/Services/CRM/LeadAuthorityService.php` — authority service
- `app/Services/SaaS/TenantContextService.php` — tenant context
- `database/migrations/2026_05_19_080616_add_tenant_id_to_leads_table.php` — mevcut migration

## NOTLAR

- `BelongsToTenant` trait otomatik `tenant_id` atar (`creating` event)
- `TenantScope` global scope `tenant_id` filtresi uygular
- Mevcut `tenant_id`'li kayıtlar korunur — nullable → dolu geçiş
- `governance-bypass` comment'leri LeadAuthorityService'de mevcut — SAB thin controller pattern
- BACKLOG-9 (Lead Unique Key Cross-Tenant) bu göreve bağlı — BACKLOG-5 tamamlanınca başlayabilir
