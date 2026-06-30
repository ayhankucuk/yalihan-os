# PR Planı — Property Engine Runtime Fix (Faz 1)

**Tarih:** 11 Nisan 2026
**Base SHA:** `b1877d1` (`feat(governance): Gold Line CI finalization + DB schema reconciliation`)
**Branch:** `fix/pe-runtime-bugs`
**Risk:** LOW (minimal diff, no data mutation, no domain redesign)

---

## 1. PR Kapsamı — Kesin Sınır

### DAHİL (Bug Fix — Runtime Kırıkları)

| # | Bug | Dosya | Değişiklik | Satır |
|---|---|---|---|---|
| 1 | Wizard resolver sub-category ile 0 feature döndürüyor | `app/Services/Wizard/FeatureTemplateResolver.php` | `listing_type` scope dalı: `when()` → 3-branch OR | +18 / −10 |
| 2 | `Feature::aktif()` scope tanımsız → 500 hatası | `app/Models/Feature.php` | `scopeAktif(Builder)` metodu eklendi | +8 |
| 3 | Feature Categories eksik view → ViewNotFoundError | `app/Http/Controllers/Admin/PropertyHubController.php` | `featureCategories()` → redirect | +1 / −2 |

**Toplam net diff (sadece bug fix):** +27 / −12 = **39 satır**

### HARİÇ (Bu PR'a girMEZ)

| Konu | Neden | Nereye Gider |
|---|---|---|
| `@deprecated` annotation'ları (PropertyHubController, 4 adet) | Bug fix değil, doctrine/annotation. Davranış değiştirmiyor ama diff kirliliği. | Ayrı `chore/phb-deprecated-annotations` PR |
| Category-specific feature assignment değişikliği | Domain redesign — Faz 3 konusu | ADR + ayrı PR |
| Yeni özellik ekleme (Havuz, Ada/Parsel vb.) | Ürün kararı gerektirir | Product decision sonrası |
| Bağımlılık kuralları oluşturma | Domain policy | Faz 3 |
| Feature packs oluşturma | Domain policy | Faz 3 |
| Kategori Matrisi admin UI | Yeni özellik | Backlog |

---

## 2. Dosya Etki Listesi

### Doğrudan Değişen Dosyalar (3)

| Dosya | Değişiklik Tipi | Rollback Güvenliği |
|---|---|---|
| `app/Services/Wizard/FeatureTemplateResolver.php` | Query logic fix | `git checkout b1877d1 -- <file>` |
| `app/Models/Feature.php` | Scope ekleme (additive) | Scope kaldır veya revert |
| `app/Http/Controllers/Admin/PropertyHubController.php` | Redirect (davranış değişikliği) | Revert + blade dosyası oluştur |

### Dolaylı Etkilenen Bileşenler

| Bileşen | Etki | Risk |
|---|---|---|
| `WizardFeatureController::index()` | Resolver'dan artık doğru feature set alıyor | Pozitif — 0→22 feature |
| `WizardContextService` | Resolver tüketir | Dolaylı — değişiklik yok |
| `EffectiveWizardSchemaResolver` | Resolver tüketir | Dolaylı — değişiklik yok |
| Wizard Step 2 Frontend (`create-wizard.blade.php`) | Artık field'ları görüyor | Pozitif — UI düzeliyor |
| `/admin/property-hub/yayin-tipi-sablonlari` | `Feature::aktif()` artık çalışıyor | Pozitif — 500 düzeliyor |
| `/admin/property-hub/features/categories` | Redirect → `admin.ozellikler.kategoriler.index` | Pozitif — ViewNotFound düzeliyor |
| `Ups\FeatureTemplateResolver` (9+ consumer) | Dokunulmadı | Etki yok |
| `feature_assignments` tablosu | Dokunulmadı, veri değişikliği yok | Etki yok |
| `features` tablosu | Dokunulmadı, veri değişikliği yok | Etki yok |

### Dokunulmayan Kritik Dosyalar (Onay)

- `app/Services/Ups/FeatureTemplateResolver.php` — SSOT resolver, DOKUNULMADI
- `app/Http/Controllers/Admin/IlanCrudController.php` — Write authority, DOKUNULMADI
- `app/Services/IlanCrudService.php` — Write authority, DOKUNULMADI
- `database/migrations/*` — Migration YOK, schema değişikliği YOK
- `feature_assignments` veri — Veri mutasyonu YOK

---

## 3. Test Matrix

### Mevcut Testler (Otomatik)

| Test | Kapsam | Durum |
|---|---|---|
| `WizardSchemaStep2Test` (82 test, 455 assertion) | Resolver, schema, feature mapping | ✅ PASS |
| `Wizard/TemplateResolutionTest` | Template resolution priority | ✅ PASS (full suite içinde) |
| `IlanWizardTest` | Wizard e2e flow | ✅ PASS (full suite içinde) |
| Full PHPUnit Suite (1154 test) | Regression | ✅ PASS (exit code 0) |

### Eksik Testler (PR'a Eklenmeli)

| Test | Dosya | Ne Test Eder |
|---|---|---|
| `test_feature_aktif_scope_filters_correctly` | `tests/Unit/Models/FeatureTest.php` (yeni) | `Feature::aktif()` sadece `aktiflik_durumu=1` döndürür |
| `test_yayin_tipi_sablonlari_page_loads` | `tests/Feature/PropertyHub/TemplatePageTest.php` (yeni) | Route 200 döndürür (500 regression guard) |
| `test_feature_categories_redirects` | `tests/Feature/PropertyHub/FeatureCategoriesRedirectTest.php` (yeni) | Redirect 302 doğrulama |
| `test_resolver_returns_features_with_subcategory` | `tests/Feature/Wizard/TemplateResolutionTest.php` (mevcut dosyaya ekle) | `resolveFeatures(1, 9, 1)` → 22 feature (0 regression guard) |

---

## 4. Rollback Planı

### Senaryo A — PR Merge Sonrası Sorun

```bash
# 1. Geri al (tek commit ise)
git revert <merge-sha> --no-edit

# 2. Veya spesifik dosya bazlı
git checkout b1877d1 -- app/Services/Wizard/FeatureTemplateResolver.php
git checkout b1877d1 -- app/Models/Feature.php
git checkout b1877d1 -- app/Http/Controllers/Admin/PropertyHubController.php
git commit -m "revert: PE runtime fix rollback"
```

### Senaryo B — Deploy Sonrası Sorun

```bash
# 1. Production'da: önceki release'e dön
git checkout b1877d1
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

# 2. Doğrulama
curl -s https://api.yalihan.com/health | jq .
php artisan tinker --execute="echo App\Models\Feature::count();"
```

### Rollback Etki Analizi

| Bileşen | Rollback Etkisi |
|---|---|
| Wizard Step 2 | 0 feature göstermeye geri döner (sub-category seçildiğinde) |
| `/admin/property-hub/yayin-tipi-sablonlari` | 500 hatası geri döner |
| Feature Categories | ViewNotFoundError geri döner |
| Veritabanı | Etki yok — migration/seed yok |
| Cache | `php artisan optimize:clear` yeterli |

### Rollback Süresi: ~2 dakika

---

## 5. Merge Checklist

```
[ ] Branch adı: fix/pe-runtime-bugs
[ ] @deprecated annotation'ları bu PR'dan çıkarıldı mı?
[ ] 3 dosya dışında değişiklik var mı? (OLMAMALI)
[ ] WizardSchemaStep2Test — 82/82 PASS
[ ] Full PHPUnit Suite — 1154/1154 PASS (10 skip OK)
[ ] Eksik testler eklendi mi? (4 yeni test)
[ ] Manual smoke: /admin/property-hub/yayin-tipi-sablonlari → 200
[ ] Manual smoke: Wizard Step 2 (Konut > Daire > Satılık) → 22 field
[ ] Manual smoke: /admin/property-hub/features/categories → redirect 302
[ ] feature_assignments tablosunda veri değişikliği YOK
[ ] Migration dosyası YOK
[ ] CHANGELOG.md güncellendi mi?
```

---

## 6. @deprecated Annotation'ları — Ayrı Mini PR

**Branch:** `chore/phb-deprecated-annotations`
**Dosya:** `app/Http/Controllers/Admin/PropertyHubController.php`
**İçerik:** 4 adet `@deprecated` phpdoc annotation
**Risk:** ZERO — sadece comment, davranış değişikliği yok
**Test:** Full suite geçer (annotation davranışı etkilemez)

Bu PR, Faz 1'den SONRA, bağımsız olarak merge edilebilir.

---

## 7. Faz 2 Tetikleme Koşulları

Faz 1 merge edildikten SONRA, Faz 2'ye geçiş için:

| # | Koşul | Durum |
|---|---|---|
| 1 | Faz 1 PR merge + production stable | ⏳ |
| 2 | Assignment Audit Raporu hazır (kanıtlı matrix) | ⏳ |
| 3 | Ürün sahibi onayı (hangi kategori → hangi feature) | ⏳ |
| 4 | ADR yazıldı (`docs/adr/category-specific-feature-assignment.md`) | ⏳ |
| 5 | Migration + rollback stratejisi belgelendi | ⏳ |

**Kural:** Bu 5 koşul sağlanmadan Faz 3'e (kategori-bazlı assignment) geçilmez.
