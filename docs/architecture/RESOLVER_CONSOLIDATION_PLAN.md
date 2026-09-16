# P2-DS-01: FeatureTemplateResolver Birleştirme Planı

**Oluşturulma:** 2026-09-08
**Durum:** Planlama — İcra edildi (dead code + deprecated), tam birleştirme ertelendi
**Görev:** TODO(P2-DS-01)

---

## 1. Mevcut Durum

### Sistem Mimarisi — Üç Katman

```
┌─────────────────────────────────────────────────────────────┐
│  KULLANIM                          SİSTEM       KAYNAK     │
├─────────────────────────────────────────────────────────────┤
│  WizardFeatureController          Sistem A      feature_   │
│  (Step 2 priority scoring)                    assignments   │
│                                           (main_category_  │
│                                           id, listing_type_ │
│                                           id — doğrudan)    │
├─────────────────────────────────────────────────────────────┤
│  WizardController                    Sistem A      feature_ │
│  (Step 2 rendering)                              assignments │
│  + 8+ AI/Backend consumer                    (assignable_  │
│                                             type+id —       │
│                                             polymorphic)     │
├─────────────────────────────────────────────────────────────┤
│  IlanWizardController.fieldSchema()  Sistem B      kategori_ │
│  (Admin CRUD altyapısı)                          yayin_tipi_ │
│  ⚠️ DEPRECATED                                 field_       │
│                                               dependencies  │
└─────────────────────────────────────────────────────────────┘
```

### Sistem A: FeatureTemplateResolver (İki Varyant)

#### A1: `App\Services\Wizard\FeatureTemplateResolver`

**Tüketici:** Sadece `WizardFeatureController` (Step 2 priority scoring)

**Query pattern:**
```php
DB::table('feature_assignments as fa')
  ->join('features as f', 'f.id', '=', 'fa.feature_id')
  ->leftJoin('feature_categories as fc', 'fc.id', '=', 'f.feature_category_id')
  ->whereNull('fa.rolled_back_at')
  ->where('fa.aktiflik_durumu', true)
  ->where('fa.is_visible', true)
  // Scope: global OR main_category OR sub_category OR listing_type
```

**Kolon kullanımı:** `main_category_id`, `sub_category_id`, `listing_type_id` (doğrudan)

**Scope öncelik sistemi (önem sırasına göre):**
| Öncelik | Kapsam | Puan |
|---------|--------|------|
| 1 | `ai_design` | 500 |
| 2 | `listing_type_id` matches | 400 |
| 3 | `sub_category_id` matches | 300 |
| 4 | `main_category_id` matches | 200 |
| 5 | Global (tümü null) | 100 |

**Ortak metot:** `resolveMainCategoryFromSub(int $subCategoryId): ?int`
- İlan_kategorileri parent chain'ini Walk eder
- Alt kategoriden (örn. Villa=8) ana kategoriye (Konut=1) traversal
- Seeder'ın ana kategori ID'leriyle eşleşmeyi garantiler

#### A2: `App\Services\Ups\FeatureTemplateResolver`

**Tüketici:** 9+ consumer (WizardController, IlanPublishGateController, AIService, SmartFieldGenerationService, VisionAnalysisService, UpsFeatureGovernanceService, TemplateService, WizardContextService, WizardOrchestrator)

**Query pattern:**
```php
// Template seviyesi (YayinTipiSablonu üzerinden)
FeatureAssignment::where('assignable_type', 'App\Models\YayinTipiSablonu')
    ->where('assignable_id', $yayinTipiId)
    ->with(['feature', 'feature.category'])

// Kategori seviyesi (IlanKategori üzerinden)
FeatureAssignment::where('assignable_type', IlanKategori::class)
    ->where('assignable_id', $kategoriId)
```

**Kolon kullanımı:** `assignable_type` + `assignable_id` (polymorphic)

**Scope öncelik sistemi:**
1. YayinTipiSablonu template assignments (üst override)
2. IlanKategori inheritance chain: child overrides parent

**Ortak metot:** `getInheritanceChain(int $kategoriId): array`
- Root → Leaf sıralı kategori ID array döner
- Walk: Her kategori için kendi parent'ına kadar çıkar
- child → parent → grandparent → ... → root

### Sistem B: `App\Services\Wizard\FieldEngine\FieldResolver` (DEPRECATED)

**Tüketici:** `IlanWizardController.fieldSchema()` — aktif consumer yok
**Kaynak tablo:** `kategori_yayin_tipi_field_dependencies` (slug tabanlı)
**Durum:** 2026-09-08 itibarıyla `@deprecated` işaretli

---

## 2. Davranış Farkları

| Boyut | Wizard\FeatureTemplateResolver | Ups\FeatureTemplateResolver |
|-------|-------------------------------|----------------------------|
| **Query yöntemi** | Doğrudan kolon (main_category_id + listing_type_id) | Polymorphic (assignable_type + assignable_id) |
| **Scope öncelik** | Numaralı puanlama (100–500) | Template > Category inheritance |
| **Alt kategori desteği** | resolveMainCategoryFromSub() ile parent chain walk | getInheritanceChain() ile root → leaf walk |
| **AI design** | Additive merge (score 500) | Yok |
| **Önbellek** | Yok | UpsCacheService (600s TTL) |
| **Feature category whitelist** | Yok | applyFeatureCategoryWhitelist() |
| **Yayın tipi filtresi** | Yok | applyPublicationTypeFilter() (Satılık→airbnb/yazlık exclude) |
| **Kullanım alanı** | Wizard Step 2 priority scoring | Tüm sistemin SSOT'su |

---

## 3. Ortak Mimari Desen: Kategori Zincirleme

Her iki resolver'da aynı problemi çözüyor: **alt kategori verildiğinde doğru ana kategoriyi bulma**.

```
                    ┌──────────────────────────────────────┐
                    │  ilan_kategorileri                   │
                    │  id=1  parent_id=null  slug=konut   │  ← Root (Ana kategori)
                    │  id=8  parent_id=1      slug=villa  │  ← Leaf (Alt kategori)
                    └──────────────────────────────────────┘
                                        │
                    ┌───────────────────┴───────────────────┐
                    │  Her iki resolver da bu zinciri Walk  │
                    │  ederek seed data ile eşleşmeyi        │
                    │  garantiler                            │
                    └───────────────────────────────────────┘
```

**Wizard\FeatureTemplateResolver.resolveMainCategoryFromSub():**
```php
// Villa (id=8) → Konut (id=1) bulunur
// Seeder verileri Konut (id=1) üzerine seed edilmiş
// Walker sonucu: id=1 döner
```

**Ups\FeatureTemplateResolver.getInheritanceChain():**
```php
// Villa (id=8) → [1, 8] array döner (root → leaf)
// Her chain elemanı için ayrı ayrı FeatureAssignment sorgulanır
// Child assignments parent'ı override eder
```

---

## 4. Birleştirme Stratejisi: Adapter/Wrapper

### Önerilen Mimari

```
┌─────────────────────────────────────────────────────────┐
│  Shared\\FeatureResolver (yeni ortak sınıf)            │
│  ├─ getInheritanceChain(kategoriId): array             │
│  ├─ resolveMainCategoryFromSub(subId): ?int             │
│  └─ collapseScopedAssignments(rows, ...): Collection    │
└──────────────────────┬──────────────────────────────────┘
                       │ extends / uses
         ┌─────────────┴──────────────┐
         ▼                             ▼
┌─────────────────┐       ┌─────────────────────────┐
│ WizardResolver   │       │ UpsResolver (SSOT)      │
│ (Step 2 priority │       │ (System-wide, 9+        │
│  scoring only)   │       │  consumers)             │
└─────────────────┘       └─────────────────────────┘
```

### Adımlar (gelecek sprint)

**Faz 1 — Ortak temel (ayır)}
- `App\Services\Shared\FeatureResolverChain` trait veya abstract sınıf oluştur
- `getInheritanceChain()` ve `resolveMainCategoryFromSub()` ortaklaştır
- Mevcut iki resolver bu trait'i kullanır (refactor değil, delegation)

**Faz 2 — WizardResolver delegation**
- `Wizard\FeatureTemplateResolver` → `Ups\FeatureTemplateResolver`'ı delegate olarak kullanır
- Sadece scope öncelik scoring'i Wizard'a özel kalır
- WizardFeatureController dependency injection değişmez

**Faz 3 — FieldResolver (Sistem B) kaldırımı**
- `IlanWizardController.fieldSchema()` endpoint'i kaldır
- `FieldResolver` sınıfı kaldır
- `kategori_yayin_tipi_field_dependencies` tablosu drop migration'ı yaz
- Frontend schema-field-renderer.js zaten silindi (2026-09-08)

### Riskler

| Risk | Seviye | Çözüm |
|------|--------|-------|
| WizardFeatureController davranış değişikliği | Orta | Faz 1'de delegation değil, ortak trait kullan; mevcut query korunur |
| Cache invalidation | Düşük | UpsCacheService zaten registry tutuyor |
| Consumer sayısı (9+) | Yüksek | Faz 2 sadece Wizard'ı etkiler; diğer consumer'lar UpsResolver'a bağlı kalır |

---

## 5. Kararlar

| Karar | Gerekçe |
|-------|---------|
| İki resolver'ı tamamen birleştirmek yerine ortak trait paylaşımı | Risk/yatırım oranı düşük; mevcut kod stable |
| FieldResolver'ı deprecated olarak tut | Sistem B tablosu hâlâ veritabanında; migration ayrı planlanmalı |
| schema-field-renderer.js silme | Frontend zaten kullanmıyor; temizlik |

---

## 6. Referanslar

- `app/Services/Wizard/FeatureTemplateResolver.php` — Wizard-scoped resolver
- `app/Services/Ups/FeatureTemplateResolver.php` — System SSOT resolver
- `app/Services/Wizard/FieldEngine/FieldResolver.php` — Sistem B (deprecated)
- `app/Http/Controllers/Api/IlanWizardController.php:fieldSchema()` — Deprecated endpoint
- `database/migrations/2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php` — Sistem B tablosu
