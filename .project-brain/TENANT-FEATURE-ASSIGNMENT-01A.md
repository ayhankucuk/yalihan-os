# TENANT-FEATURE-ASSIGNMENT-01A — Discovery Raporu (Düzeltilmiş)

```
STATUS: BLOCKED_PENDING_SECURITY
SUPERSEDED_BY: SECURITY-WIZARD-FEATURE-SUGGESTIONS-01
RESULT: BLOCKED_PENDING_SECURITY
REASON: Security bulgusu ürün kararını bloke etti
UPDATED: 2026-09-12
```

---

## Gözlemler (Düzeltilmiş Kanıt Seviyeleri)

### T-01

```yaml
test_id: T-01
question: "feature_assignments.tenant_id yerel runtime'da var mı?"
observed_behavior: >
  Migration [46] kolon ekledi: tenant_id (nullable, unsignedBigInteger, indeksli).
  Migration [47] tenant-aware composite unique index ekledi.
  Model fillable'da tenant_id YOK.
source_evidence: >
  database/migrations/2026_08_25_150345_add_tenant_id_to_feature_assignments_table.php
  database/migrations/2026_08_25_150439_add_tenant_aware_unique_index_to_feature_assignments.php
  app/Models/FeatureAssignment.php:18-47
local_runtime_evidence: "migrate:status [46][47] → Ran"
kanit_seviyesi: REPO_VERIFIED
duzeltme_notu: >
  "Bugün application write tenant_id göndermiyor" denebilir.
  "Hiçbir kod yolu yazamaz" denemez — BelongsToTenant eklense yazılabilir.
```

### T-02

```yaml
test_id: T-02
question: "AI suggestion bugün hangi tenant_id ile kayıt oluşturuyor?"
observed_behavior: >
  AiFieldSuggestionEngine:196 — FeatureAssignment::create() çağrısında tenant_id parametre YOK.
  $fillable'da tenant_id olmadığı için mass assignment koruması NULL atar.
  Ayrıca options_json içinde gönderilen tenant_id json string olarak saklanır, DB kolonu değil.
source_evidence: app/Services/Wizard/AiFieldSuggestionEngine.php:196-213
local_runtime_evidence: "FeatureAssignment::$fillable — tenant_id yok"
kanit_seviyesi: REPO_VERIFIED
duzeltme_notu: >
  "Hiçbir kod yolu yazamaz" yerine:
  "Mevcut application write çağrıları tenant_id göndermiyor."
```

### T-03

```yaml
test_id: T-03
question: "Tenant context değişince FeatureTemplateResolver farklı sonuç döndürüyor mu?"
observed_behavior: >
  FeatureTemplateResolver — DB::table() query builder, tenant_id filtresi YOK.
  Tüm sorgular tenant_id = NULL global kayıtları döndürür.
  FeatureAssignment modeli: HasCountryScope (ülke bazlı) kullanıyor, BelongsToTenant DEĞİL.
source_evidence: app/Services/Wizard/FeatureTemplateResolver.php:71-96; app/Models/FeatureAssignment.php:16
local_runtime_evidence: "Kodda tenant_id filtre yok"
kanit_seviyesi: REPO_VERIFIED
INFERRED_notu: >
  "İki tenant gerçek sızıntı sonucu" — tenant fixture testi olmadan INFERRED.
  Sızıntı potansiyeli REPO_VERIFIED; gerçek etki INFERRED.
```

### T-04

```yaml
test_id: T-04
question: "Global ve tenant-özel aynı assignment unique indexte nasıl davranıyor?"
observed_behavior: >
  Composite unique index: [feature_id, main_category_id, sub_category_id, listing_type_id, tenant_id]
  tenant_id nullable.
  MySQL NULL semantiği: NULL = NULL her zaman FALSE — iki NULL ayrı unique satır kabul edilir.
source_evidence: database/migrations/2026_08_25_150439_add_tenant_aware_unique_index_to_feature_assignments.php
local_runtime_evidence: "Migration kodu okundu; MySQL NULL davranışı teorik bilgi"
kanit_seviyesi: INFERRED
duzeltme_notu: >
  MySQL NULL unique davranışı hedef MySQL üzerinde disposable integration test olmadan
  INFERRED — yalnız migration kodu ile kesin doğrulama yapılamaz.
```

### T-05

```yaml
test_id: T-05
question: "Rollback sorgusu başka tenant'ın kaydına erişebiliyor mu?"
observed_behavior: >
  AiFieldSuggestionEngine:241-256 — DB::table() query builder ile rollback.
  Filtre: assignment_id + source_type + rolled_back_at IS NULL.
  Tenant_id kontrolü YOK.
  P0 GÜVENLİK BULGUSU: PUBLIC ROUTE + tenant_id filtresi eksikliği zinciri doğrulandı.
  Rollback endpoint'i auth/tenant koruması olmadan public — yetkisiz erişim mümkün.
source_evidence: app/Services/Wizard/AiFieldSuggestionEngine.php:241-256
local_runtime_evidence: "Kodda tenant_id filtresi yok; route dosyasında auth middleware yok"
kanit_seviyesi: REPO_VERIFIED
artmis_etki: >
  SECURITY-WIZARD-FEATURE-SUGGESTIONS-01 olarak P0 yükseltildi.
  Ayrıca: public route zinciri REPO_VERIFIED → etki yüksek.
```

---

## Düzeltilmiş Karar Durumu

```yaml
CURRENT_BEHAVIOR: GLOBAL_TEMPLATE_DEFAULT
PRODUCT_DECISION: BLOCKED_PENDING_SECURITY
BLOCKED_BY: SECURITY-WIZARD-FEATURE-SUGGESTIONS-01
REASON: >
  Migration'lar hem canonical seed hem tenant-customization tasarımını öngörüyor.
  "Bugün global yazılıyor" ürünün kalıcı A kararını kanıtlamaz.
  Auth/tenant koruması olmadan ürün kararı verilemez — yanlışlıkla tenant verisi yazılabilir.
```

### Karar Gerekçesi

1. **T-04 INFERRED:** MySQL NULL unique davranışı disposable test olmadan doğrulanamaz. Seed verisi ile çakışma riski bilinmiyor.

2. **T-03 INFERRED bileşeni:** Tenant-özel kayıt yazılabilse sızıntı olur — ancak gerçek cross-tenant etki test edilmeden bilinemez.

3. **T-05 P0 yükseltme:** Rollback endpoint public + tenant_id filtresi yok → SECURITY-WIZARD-FEATURE-SUGGESTIONS-01. Bu bulgu ürün kararını bloke etti.

4. **Migration tasarım kanıtı:** Kolon ve index açıkça tenant-customization öngörüyor. Bu, ürün kararının "geçici olarak A" olduğunu gösterir — kalıcı karar değil.

### Doğru Sıralama

```
1. SECURITY-WIZARD-FEATURE-SUGGESTIONS-01 (P0, ayrı worktree)
   → Auth + role + tenant.context + authorization ekleme
   → Rollback'e tenant_id doğrulaması
   → Test paketi yazma

2. TENANT-FEATURE-ASSIGNMENT-01A sonra tamamlanır
   → Artık güvenli ortamda A/B kararı verilir
   → TENANT-FEATURE-ASSIGNMENT-01B açılır veya kapatılır
```

## Düzeltmeler Özeti

| Gözlem | Önceki Seviye | Düzeltilmiş Seviye | Neden |
|---|---|---|---|
| T-01 | REPO_VERIFIED | REPO_VERIFIED | Doğru, düzeltme notu eklendi |
| T-02 | REPO_VERIFIED | REPO_VERIFIED | Doğru, dil netleştirildi |
| T-03 | REPO_VERIFIED | REPO_VERIFIED + INFERRED bileşeni | Sızıntı potansiyeli REPO; gerçek etki INFERRED |
| T-04 | REPO_VERIFIED | INFERRED | MySQL NULL davranışı test edilmeden bilinemez |
| T-05 | REPO_VERIFIED | REPO_VERIFIED (P0 yükseltildi) | Public route zinciri doğrulandı |
