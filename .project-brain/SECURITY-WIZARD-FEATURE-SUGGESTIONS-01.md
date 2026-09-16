# SECURITY-WIZARD-FEATURE-SUGGESTIONS-01 — Wizard Feature Suggestions Auth Gap

## Görev Durumu
```
STATUS: DESIGN_APPROVED
PRIORITY: P0
WORKTREE: codex/security-wizard-feature-suggestions-01
COMMIT_BASE: 3638a978
DESIGN_DATE: 2026-09-13
MODE: Yazma izni bekleniyor
```

## Bulgu

### P0 — Yetkilendirme Zinciri Eksik

`POST /api/v1/wizard/field-suggestions`, `POST /api/v1/wizard/field-suggestions/approve`, `POST /api/v1/wizard/field-suggestions/rollback` endpoint'leri yalnız `ThrottleApiRequests` middleware ile açık.

Eksik: `auth`, `role`, `tenant.context`, yetki kontrolü YOK.

### Etkilenen Endpoint'ler

| Endpoint | Risk |
|---|---|
| `POST /api/v1/wizard/field-suggestions` | Yetkisiz AI suggestion listesi + intelligence disclosure |
| `POST /api/v1/wizard/field-suggestions/approve` | **Yetkisiz global feature assignment oluşturma** |
| `POST /api/v1/wizard/field-suggestions/rollback` | **Yetkisiz global assignment geri alma (IDOR)** |

### Saldırı Zinciri

```
Yetkisiz istemci (token yok / yanlış token)
  → POST /api/v1/wizard/field-suggestions/approve
  → AiFieldSuggestionEngine:196 → FeatureAssignment::create()
  → Global feature assignment (tenant_id = NULL)

Yetkisiz istemci
  → POST /api/v1/wizard/field-suggestions/rollback + assignment_id
  → AiFieldSuggestionEngine:254 → DB::table()->update()
  → Global assignment geri alma (IDOR benzeri)
```

## Kanıt Durumu

| Bulgu | Kanıt Seviyesi |
|---|---|
| Route middleware eksikliği | `REPO_VERIFIED` — routes/api/v1/common.php:88-90 |
| Controller auth/tenant kontrolü yok | `REPO_VERIFIED` — WizardFeatureController.php:218-300 |
| Engine global write | `REPO_VERIFIED` — AiFieldSuggestionEngine.php:196 |
| Rollback IDOR | `REPO_VERIFIED` — AiFieldSuggestionEngine.php:241-256 |
| fieldSuggestions intelligence disclosure | `REPO_VERIFIED` — AiFieldSuggestionEngine.php |
| Canlı VPS erişilebilirlik | `UNKNOWN` |

## Tasarım Kararı — 2026-09-13

### 4 Karar Noktası

**1. Guard: `auth:sanctum`**
`api` grubunun üstüne `auth:sanctum` eklenir. Sanctum mevcut ve kanonik.

```
// Sonra
Route::prefix('v1')->middleware(['throttle:api', 'auth:sanctum'])->group(...)
```

**2. Tenant context: `tenant.context` middleware (route seviyesi)**
Controller değil, route middleware oluşturur. Controller yalnız request doğrulama yapar.

```
// Middleware sırası
['auth:sanctum', 'tenant.context', 'throttle:api']
```

**3. Rol yetkisi**

| Endpoint | Yetki |
|---|---|
| `fieldSuggestions` | `auth:sanctum` + `tenant.context` + rate limit |
| `approveSuggestion` | `auth:sanctum` + `tenant.context` + `role:admin|super_admin` |
| `rollbackSuggestion` | `auth:sanctum` + `tenant.context` + `role:admin|super_admin` |

Mevcut rol sistemi (`admin`, `super_admin`) kullanılır. Yeni capability (`manage-wizard-schema`) ileride ayrı görev olarak açılabilir.

**4. Rollback: İki savunma hattı**

```
HAT 1 — Controller
  → authenticated user / role kontrolü (admin|super_admin)
  → tenant context mevcut mu?

HAT 2 — Engine / Repository (zorunlu)
  → assignment erişim politikasını tekrar doğrula
  → tenant-custom kayıt ise tenant_id ile sınırla
  → global kayıt ise yalnız platform-schema yetkisiyle işlem yap
```

### Hedef Mimari

```
Public write endpoint
        ↓
Authenticated, tenant-context-aware, role-limited schema administration
        ↓
Engine-level authorization
        ↓
Audit trail
```

## Değişiklik Kapsamı

### Değişecek (BU PAKET)
```
routes/api/v1/common.php              → middleware zinciri
app/Http/Controllers/Api/V1/WizardFeatureController.php  → role kontrolü (savunma hattı 1)
app/Services/Wizard/AiFieldSuggestionEngine.php           → tenant/auth guard (savunma hattı 2)
```

### Değişmeyecek
```
app/Models/FeatureAssignment.php      → model, migration, seeder YOK
app/Services/Wizard/FeatureTemplateResolver.php
app/Services/Wizard/WizardFormSchemaProvider
BRIDGE-02B veya diğer paketler
```

## WizardFormSchemaProvider Pipeline Bağlantısı

```
FeatureTemplateResolver (bugün tenant-siz, global sadece)
  ↓
WizardFormSchemaProvider (tasarım aşamasında, WizardStep2 composition)
  ├── 1. FieldKeyMappingRegistry
  ├── 2. Domain Policy kapsam kontrolü
  ├── 3. Çakışma Guard
  └── 4. Tenant allowlist (canary)
```

Güvenlik düzeltmesi: `approveSuggestion` → `FeatureAssignment` yazdığında → pipeline'ın bir parçası olacak mı? Gelecekte tenant-custom açılırsa resolver, cache, unique constraint, rollback birlikte tenant-aware olmalı.

### ai.cost.guard Değerlendirmesi — 2026-09-13

**Soru:** `fieldSuggestions` dış AI/ücretli işlem tetikliyor mu?

**Bulgular:**

| Katman | Bulgu | Dış AI çağrısı? |
|---|---|---|
| `AiFieldSuggestionEngine::suggest()` | Pipeline: gap analysis + scoring (tamamen internal DB) | ❌ Hayır |
| `FieldGapAnalyzer` | DB verisi üzerinde istatistiksel analiz | ❌ Hayır |
| `FieldSuggestionScorer` | DB verisi üzerinde ağırlıklı puanlama | ❌ Hayır |
| Mevcut `ai.cost.guard` kullanımı | `IlanAITitleDescriptionController`, `GenerateIlanTitleAction` | ✅ Ücretli işlem — ayrı |

**Karar:** `ai.cost.guard` → **GEREKLI DEĞİL** bu endpoint için.

Mevcut `ThrottleApiRequests` (60req/min) yeterli rate limiting sağlar.

> Ayrı: gelecekte AI sağlayıcı entegrasyonu eklendiğinde `ai.cost.guard` ayrı görev olarak değerlendirilir.

## Doğrulama Gerekenler

```yaml
pre_write_gates:
  - Tüm 5 bulgu REPO_VERIFIED doğrulandı
  - Tasarım kararı onaylandı (2026-09-13)
  - Değişiklik kapsamı netleştirildi
  - Model/migration/seeder değişikliği YOK
  - ai.cost.guard gerekli değil (fieldSuggestions dış AI çağırmıyor)
  - Ayrı worktree'de yazılacak
```

## Sonraki Adım

Yazma izni verildiğinde (yalnız `codex/security-wizard-feature-suggestions-01` worktree):

**Kapsam:**
```
routes/api/v1/common.php
app/Http/Controllers/Api/V1/WizardFeatureController.php
app/Services/Wizard/AiFieldSuggestionEngine.php
feature/authorization testleri
```

**Uygulama sırası:**
1. `routes/api/v1/common.php` → `auth:sanctum` + `tenant.context` middleware ekleme
2. `WizardFeatureController` → `approveSuggestion` + `rollbackSuggestion`'a `role:admin|super_admin` kontrolü
3. `AiFieldSuggestionEngine::rollbackSuggestion()` → tenant_id doğrulaması ekleme (savunma hattı 2)
4. Feature test yazma: authenticated + unauthorized senaryoları

**Ana worktree yazılmaz.**
