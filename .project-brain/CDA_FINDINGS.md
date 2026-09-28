# Canonical Drift Audit Findings (CDA)

## Rapor Formatı

Her bulgu 5N1K formatında raporlanır:
- **NE?** — Pattern tanımı
- **NEREDE?** — WRITE ve READ lokasyonları
- **NE ZAMAN?** — İlk bozulma tarihi
- **NASIL?** — Drift mekanizması
- **NEDEN?** — Root cause
- **KİM?** — ETKİ, SAHİP, BLAST

---

## CDA-001: AUTHORITY_CONTRACT_DRIFT — Ilan Fiyat Partial Update Corruption

*(Önceki bulgu — kapatıldı)*

---

## CDA-002: AUTHORITY_STATE_DRIFT — yayin_durumu Naming Standard Sapması

*(Önceki bulgu — belgelendi)*

---

## CDA-003: AUTHORITY_MODEL_DRIFT — Ilan/Ozellik Duplicate Model Potansiyeli

*(Önceki bulgu — çözüldü)*

---

## CDA-006: AUTHORITY_MODEL_DRIFT + AUTHORITY_CONTRACT_DRIFT — Tenant Split-Brain (CRITICAL)

### 5N1K Raporu

| Alan | İçerik |
|---|---|
| **NE?** | `AUTHORITY_MODEL_DRIFT` + `AUTHORITY_CONTRACT_DRIFT` — Aynı kavram için iki model sınıfı; biri schema-uyumlu, diğeri değil |
| **NEREDE?** | **Model A (schema-uyumlu):** `App\Models\Tenant` → fillable: `['uuid','name','domain','durum']` + auto-uuid boot <br> **Model B (schema-dışı):** `App\Models\SaaS\Tenant` → fillable: `['uuid','name','domain','status']` + SoftDeletes <br> **Physical Schema:** `tenants` tablosu → `id, name, domain, durum, created_at, updated_at, deleted_at` (uuid YOK, status YOK) |
| **NE ZAMAN?** | 2026-05-03 — SaaS Monetization Foundation migration'ı ile `App\Models\SaaS\Tenant` oluştu; 2026-05-06'da `durum` eklendi ama SaaS\Tenant güncellenmedi |
| **NASIL?** | 1. `2026_05_03_010000_create_saas_monetization_foundation_tables.php` → `tenants` tablosunu `uuid` + `status` ile oluşturdu (L24, L27) <br> 2. `2026_05_06_173500_add_remaining_columns_run64.php` → `durum` kolonu ekledi (status DEĞİL) (L50-54) <br> 3. `App\Models\Tenant` → `durum` fillable + auto-uuid boot → SCHEMA-UYUMLU <br> 4. `App\Models\SaaS\Tenant` → `status` fillable → SCHEMA-DIŞI <br> 5. Runtime middleware + billing: `App\Models\SaaS\Tenant` kullanıyor → `status` yazmaya çalışır → DB `durum` bekliyor |
| **NEDEN?** | Migration silsilesinde `status` → `durum` dönüşümü yapıldı ama `App\Models\SaaS\Tenant` fillable'ı güncellenmedi. İki model sınıfı aynı physical tabloya farklı kontratlarla erişiyor. |
| **KİM?** | **ETKİ:** SetTenantContext middleware (L68) `Tenant::find()` yapıyor → SaaS\Tenant kullanır → `status` yazmaya çalışırsa DB'ye `status` gönderilir ama tablo `durum` bekliyor. Subscription billing ledger + BillingLedgerService `tenant_id` üzerinden erişir. <br> **SAHİP:** Backend Team <br> **BLAST:** TenantContextService, SetTenantContext middleware, BillingLedgerService, SubscriptionService, tüm tenant-scoped HTTP istekleri |

### Migration Lineage (Kronolojik)

```
2026_05_03_010000_create_saas_monetization_foundation_tables.php
  → Schema::create('tenants'):
    - id, uuid, name, domain, status (default 'active'), timestamps, softDeletes

2026_05_06_173500_add_remaining_columns_run64.php  
  → Schema::table('tenants') → ADD durum (NOT status) column
    - "if hasTable('tenants') && !hasColumn('durum')"
    - NOT renaming status → durum, just ADDING durum
    - Physical tabloya both 'status' AND 'durum' olabilir

2026_05_17_194127_add_aktiflik_durumu_to_tenants_table.php
  → ALSO adds durum (checks same condition)
    - Multiple migrations adding same column (idempotent-guard'lı)

restore_missing_ci_schema.php (L414-427)
  → ALSO creates tenants with status + uuid + is_active
  → Conflict: restore CI = SaaS migration + different schema
```

### Modellerin Durumu

**`App\Models\Tenant` (Schema-uyumlu):**
```php
fillable: ['uuid', 'name', 'domain', 'durum']  // ✅ Physical schema match
boot: auto uuid on creating                     // ✅ Auto uuid available
uses: HasCountryScope trait                     // ✅
```

**`App\Models\SaaS\Tenant` (Schema-dışı):**
```php
fillable: ['uuid', 'name', 'domain', 'status']  // ❌ Physical schema: durum NOT status
uses: SoftDeletes, HasFactory                   // Additional traits
No auto-uuid boot                               // ❌ Missing
```

### Runtime Consumerlar

| Bileşen | Model | Sorun |
|---|---|---|
| `SetTenantContext` middleware (L68) | `App\Models\SaaS\Tenant` | `status` fillable, physical `durum` bekliyor |
| `TenantContextService` | `App\Models\SaaS\Tenant` | Tenant tipi olarak kullanılıyor |
| `Subscription` relation | `App\Models\SaaS\Tenant` | hasOne Subscription |
| BillingLedgerEntry relation | `App\Models\SaaS\Tenant` | hasMany BillingLedgerEntry |
| ChannelManager tests | `App\Models\SaaS\Tenant` | Test fixture |
| `YazlikKiralamaController` (L490) | `App\Models\SaaS\Tenant::find()` | Direct usage |
| `TenantBaselineSeeder` | Direct DB facade | `status` + `uuid` yazıyor, DB `durum` bekliyor |
| `App\Models\Tenant` | (Unused in runtime consumer'larda) | Sadece model var, aktif kullanım yok gibi |

### TenantBaselineSeeder Kontrat Uyuşmazlığı

Seeder `direct DB facade` kullanıyor, model üzerinden değil:
```php
// TenantBaselineSeeder.php L44-47
DB::table('tenants')->updateOrInsert(
    ['id' => $tenant['id']],
    $tenant  // ['uuid' => ..., 'status' => 'active', ...]
);
```

Ama physical DB'de `status` kolonu YOK, `durum` var. Seeder şu kolonlara yazmaya çalışıyor:
- `uuid` → ❌ DB'de YOK
- `status` → ❌ DB'de YOK (varolan satırlarda `durum` var)

**Mevcut data (test DB):** `id=1, name=Test, domain=t.test, durum=active, (uuid=NULL)`

### Drift Etki Matrisi

| Senaryo | Sonuç |
|---|---|
| SaaS\Tenant üzerinden `->save()` veya mass-assign | `status` → DB hatası (kolon yok) veya yanlış kolona yazılır |
| SaaS\Tenant üzerinden `->update(['status' => 'suspended'])` | Silent fail veya SQL hatası — kolon yok |
| SetTenantContext middleware `Tenant::find()` | **CRITICAL**: `App\Models\SaaS\Tenant` kullanır → find sonrası model `status` bekler ama DB `durum` döndürür → accessor `durum` döndürmez → null |
| TenantBaselineSeeder çalışırsa | `uuid` + `status`写入 → DB hatası veya yanlış kolon |

### Silent Failure Analizi

`SetTenantContext` middleware'de (L68):
```php
$tenant = Cache::remember(..., fn() => Tenant::find($user->tenant_id));
$this->tenantContextService->setTenant($tenant);
```

Eğer `Tenant::find()` bir `App\Models\SaaS\Tenant` döndürürse (ki öyle — middleware import SaaS\Tenant):
- Model `status` accessor'ı yok (sadece `durum` var) → `null` döner
- `TenantContextService::getTenant()` çalışır → tenant objesi var ama `status` property'si boş
- Subscription sorgusu yapılırsa → `tenant->subscription()` yanlış sonuç verebilir

### Önceliklendirme

| ETKİ ALANI | ÖNCELİK |
|---|---|
| Tenant middleware + runtime context | **CRITICAL** — Tüm HTTP tenant-isolated istekleri etkilenir |
| Billing/Subscription system | **CRITICAL** — SaaS\Tenant üzerinden subscription relation |
| TenantBaselineSeeder | HIGH — Şu anda fail değil ama yanlış kontratla çalışıyor |
| AdminUserSeeder | MEDIUM — Tenant'a bağlı |

### Çözüm Yönleri (Ayhan'a Sunulacak)

**A (Seeder fix — minimal, bounded):**
TenantBaselineSeeder'ı physical schema'ya uydur: `'durum' => 'active'` + uuid AUTO (model boot'ta). Model üzerinden değil direct DB kullanmaya devam. Risk: uuid üretilmez.

**B (Model canonicalization — kapsamlı):**
`App\Models\Tenant` → canonical, `App\Models\SaaS\Tenant` → deprecated/silgi.
Tüm SaaS\Tenant kullanan bileşenleri App\Models\Tenant'a point et. Migration gerekir: ya `durum` → `status` rename (tehlikeli, data riskli) ya da SaaS\Tenant'ı `durum` kullanacak şekilde güncelle.

**C (Migration-add-uuid — Ayhan onaylı):**
Physical schema'ya `uuid` kolonu ekle. Seeder `uuid` üretmeye devam eder. Status/durum ayrıştırması sonra yapılır.

**D (Daha fazla araştırma):**
`restore_missing_ci_schema.php`'daki tenants tablo tanımı ile SaaS migration'ınki farklı. Hangi migration local DB'ye uygulandı? CI'da hangisi çalışıyor? Bu fark aydınlatılmalı.

### Durum

| Alan | Değer |
|---|---|
| **Finding** | REAL_FINDING |
| **Reproduction** | REPO_VERIFIED — Physical schema doğrulandı |
| **Evidence Level** | REPO_VERIFIED |
| **Repository Status** | OPEN — Ayhan kararı bekleniyor |
| **F-DRIFT-02 ile İlişki** | AYNI KÖK NEDEN — F-DRIFT-02 yükseltildi |
| **Blocked Tasks** | TenantBaselineSeeder, AdminUserSeeder |

---

## CDA-005: AUTHORITY_TABLE_DRIFT — İki tenants Tablo Oluşturma Migration'ı

### 5N1K Raporo

| Alan | İçerik |
|---|---|
| **NE?** | `AUTHORITY_TABLE_DRIFT` — Aynı tablo için iki farklı schema tanımı |
| **NEREDE?** | `2026_05_03_010000_create_saas_monetization_foundation_tables.php` (uuid+status) <br> `2026_05_03_000000_restore_missing_ci_schema.php:414` (uuid+status+is_active) |
| **NE ZAMAN?** | 2026-05-03 — CI schema recovery + SaaS foundation aynı gün |
| **NASIL?** | İki farklı migration aynı `tenants` tablosunu oluşturmaya çalışıyor. `if (!Schema::hasTable())` guard'ları var ama farklı schema tanımları içeriyorlar. |
| **NEDEN?** | CI recovery + SaaS foundation paralel geliştirme sırasında koordine edilemedi |
| **KİM?** | **ETKİ:** Local DB ve CI DB farklı schema alabilir. <br> **SAHİP:** DevOps/Backend |

### Schema Farkı

| Kolon | SaaS Foundation Migration | CI Restore Migration |
|---|---|---|
| uuid | ✅ unique | ✅ nullable |
| status | ✅ default active | ✅ default active |
| is_active | ❌ | ✅ default true |
| softDeletes | ✅ | ✅ (ekstra guard) |

### Durum

**Status:** DOCUMENTED — Production migration order'a bağlı

---

*Son Güncelleme: 2026-09-28 | HEAD: 7dd8b016*
*Kaynak: TENANT_CANONICAL_AUTHORITY_RESOLVE_01 Forensic Research*
