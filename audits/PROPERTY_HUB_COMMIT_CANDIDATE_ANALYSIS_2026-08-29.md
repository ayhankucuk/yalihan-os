# Property Hub Diff Boundary Analizi — Commit Candidate

**Tarih:** 2026-08-29 05:45 UTC
**Branch:** `integration/era-v-phase2a-e01`
**Görev Sahibi:** Codex (Kilo Antigravity destek)

---

## 1. Diff Sınırı İncelemesi

### 1.1 Toplam Uncommitted Değişiklikler

| Kategori | Dosya Sayısı | Değişiklik Türü |
|-----------|-------------|------------------|
| `app/` (uygulama) | 10 | Modified + 1 Deleted |
| `resources/` (view) | 2 | Modified |
| `tests/` | 6 | Modified |
| `database/migrations/` | 3 | **Untracked** |
| `audits/` | 1 | Modified |
| `docs/` | 4 | Modified |
| `.project-brain/` | 3 | Modified |
| Diğer | 3 | Mixed |

### 1.2 Property Hub ile İlişkili Değişiklikler

**Kriter:** `/admin/property-hub`, `/admin/ilan-kategorileri`, template yönetimi, feature atama

| Dosya | Değişiklik | İlişki |
|-------|------------|---------|
| `app/Http/Controllers/Admin/IlanKategoriController.php` | +4, -2 | ✅ Dashboard data passthrough |
| `app/Http/Controllers/Admin/TemplateController.php` | +15, -2 | ✅ Template view validation |
| `app/Services/Ilan/IlanKategoriService.php` | +37, -2 | ✅ Dashboard data + template stats |
| `app/Enums/UpsFeatureLifecycle.php` | +13 | ✅ STABLE enum durumu eklendi |
| `resources/views/admin/ilan-kategorileri/index.blade.php` | +1097, -1006 | ✅ Dashboard UI |
| `resources/views/admin/property-hub/templates/index.blade.php` | +1018, -1006 | ✅ Template UI + Türkçe format |

**Toplam:** 6 uygulama dosyası. Testler ve bu audit raporu ile commit sınırı 10 dosyadır.

### 1.3 Property Hub DIŞI Değişiklikler

| Dosya | Değişiklik | Durum |
|-------|------------|-------|
| `app/Services/Ilan/IlanCrudService.php` | +217, -95 | ⚠️ Ayrı commit gerekli |
| `app/Http/Resources/IlanPublicResource.php` | +22, -14 | ⚠️ API/Golden Thread veri sözleşmesi; ayrı commit gerekli |
| `app/Services/Hermes/Handlers/Workflow/PublishDecisionAgent.php` | +2 | ⚠️ Ayrı commit gerekli |
| `app/Services/Hermes/Handlers/Workforce/PortfolioAgent.php` | **-309** (silindi) | ⚠️ Ayrı commit gerekli |
| İlişkisiz `tests/` dosyaları | Mixed | ⚠️ Ayrı commit gerekli |
| `database/migrations/` (3 adet) | **Untracked** | ❌ Bu commit'e dahil DEĞİL |
| `audits/` (golden-thread) | Ekran görüntüleri | ❌ Bu commit'e dahil DEĞİL |
| `docs/` dosyaları | Dokümantasyon | ❌ Bu commit'e dahil DEĞİL |

---

## 2. Commit Adayı: Property Hub UI & Data Layer

### 2.1 Önerilen Commit Mesajı

```
feat(property-hub): Property Hub dashboard UI ve data layer güncellemeleri

- IlanKategoriService: getDashboardData() template stats desteği
- IlanKategoriController: templateStats view'e passthrough
- TemplateController: kategori fallback strict validation
- UpsFeatureLifecycle: STABLE enum durumu eklendi
- View: Türkçe format ve UI improvements
- Property Hub ve lifecycle odak testleri
```

### 2.2 Dahil Edilecek Dosyalar

```
app/Http/Controllers/Admin/IlanKategoriController.php
app/Http/Controllers/Admin/TemplateController.php
app/Services/Ilan/IlanKategoriService.php
app/Enums/UpsFeatureLifecycle.php
resources/views/admin/ilan-kategorileri/index.blade.php
resources/views/admin/property-hub/templates/index.blade.php
tests/Feature/Admin/IlanKategoriHubTest.php
tests/Feature/Admin/PropertyHubTemplateManagerTest.php
tests/Unit/Enums/UpsFeatureLifecycleTest.php
audits/PROPERTY_HUB_COMMIT_CANDIDATE_ANALYSIS_2026-08-29.md
```

### 2.3 Dahil EDİLMEYECEK Dosyalar

```
app/Services/Ilan/IlanCrudService.php              ❌
app/Http/Resources/IlanPublicResource.php          ❌
app/Services/Hermes/Handlers/Workflow/...         ❌
app/Services/Hermes/Handlers/Workforce/...       ❌ (silindi)
database/migrations/                              ❌
tests/Feature/Admin/IntelligenceHubAuthorityBridgeTest.php ❌
tests/Unit/Models/IlanTest.php                    ❌
tests/Unit/Repositories/...                       ❌
tests/e2e/golden-thread-wizard.spec.ts           ❌
audits/                                          ❌
docs/                                            ❌
```

---

## 3. Migration Durumu

### 3.1 Untracked Migration Dosyaları

```
database/migrations/2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php
database/migrations/2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php
database/migrations/2026_08_26_000002_fix_bina_yasi_column_type.php
```

**Not:** Bu dosyalar `??` (untracked) durumunda. Bu commit'e dahil DEĞİLLER.

### 3.2 Migration Planı

| Migration | Durum | Commit |
|-----------|-------|--------|
| Field dependencies table | Untracked | Ayrı migration commit |
| Location reconcile | Untracked | Ayrı migration commit |
| Bina yasi fix | Untracked | Ayrı migration commit |

---

## 4. Önerilen Commit Stratejisi

### Commit A: Property Hub UI & Data (BU ADAY)
```bash
git add \
  app/Http/Controllers/Admin/IlanKategoriController.php \
  app/Http/Controllers/Admin/TemplateController.php \
  app/Services/Ilan/IlanKategoriService.php \
  app/Enums/UpsFeatureLifecycle.php \
  resources/views/admin/ilan-kategorileri/index.blade.php \
  resources/views/admin/property-hub/templates/index.blade.php \
  tests/Feature/Admin/IlanKategoriHubTest.php \
  tests/Feature/Admin/PropertyHubTemplateManagerTest.php \
  tests/Unit/Enums/UpsFeatureLifecycleTest.php \
  audits/PROPERTY_HUB_COMMIT_CANDIDATE_ANALYSIS_2026-08-29.md
git commit -m "feat(property-hub): Property Hub dashboard UI ve data layer güncellemeleri"
```

### Commit B: IlanCrudService (Ayrı)
```bash
git add app/Services/Ilan/IlanCrudService.php
git commit -m "refactor(ilan): IlanCrudService normalization"
```

### Commit C: Hermes Handlers (Ayrı)
```bash
git add \
  app/Services/Hermes/Handlers/Workflow/PublishDecisionAgent.php \
  app/Services/Hermes/Handlers/Workforce/PortfolioAgent.php
git commit -m "chore(hermes): Workforce/PortfolioAgent removal + PublishDecisionAgent fix"
```

### Commit D: Migrations (Ayrı)
```bash
git add database/migrations/
git commit -m "migrations: field dependencies + location reconcile + bina_yasi fix"
```

---

## 5. Doğrulama

### 5.1 Property Hub Browser Test

| Sayfa | HTTP | Console |
|-------|------|---------|
| `/admin/property-hub` | 200 | 0 error |
| `/admin/property-hub/templates` | 200 | - |
| `/admin/property-hub/features` | 200 | - |
| `/admin/analytics/command-center` | 200 | 0 error |

**Sonuç:** ✅ Tüm Property Hub sayfaları çalışıyor.

### 5.2 Etkilenmeyen Dosyalar

- Sprint 14 audit raporları: ✅ Değişmemiş
- Governance düzeltmesi (7d402de): ✅ Zaten commit'li

---

## 6. Sonuç

| Madde | Durum |
|-------|-------|
| Property Hub diff边界 | ✅ Belirlendi |
| Commit candidate | ✅ Hazır |
| Migration ayrımı | ✅ Yapıldı |
| Sprint 14 etkilenmedi | ✅ Doğrulandı |
