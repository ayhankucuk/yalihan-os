# ADR-003: SSOT & Determinism Anayasası — Template/Resolver/Feature/Cache Katmanı

**Tarih:** 21 Şubat 2026
**Durum:** KABUL EDİLDİ — Bağlayıcı
**Kapsam:** Template / Resolver / FeatureAssignment / Cache katmanı
**Tetikleyici:** Determinism Denetimi bulgular (P0: silent fallback, P1: ORDER BY eksikliği × 3, P1: cache authority karışıklığı × 2)

---

## Context

21 Şubat 2026 tarihinde gerçekleştirilen "Resolver & Cache Determinism Denetimi" 8 kritik bulgu
tespit etti:

| Seviye | Bulgu                                                                                            |
| ------ | ------------------------------------------------------------------------------------------------ |
| P0     | `TemplateService::resolveTemplateFeatures` exception yutarak hardcoded 3-feature set dönüyor     |
| P1     | `AltKategoriYayinTipi::first()` — hem doğrudan pivot hem parent pivot sorgusunda ORDER BY yok    |
| P1     | `getTemplatesForCategory($kategoriId)` — kategori argümanı DB sorgusunda kullanılmıyor           |
| P1     | `TemplateService::clearCache` — `invalidateForJunction` yerine `invalidate('templates')` çağrısı |
| P1     | `UpsCacheService::getAssignments` closure — `$kategoriId` `use()` listesinden eksik              |
| P1     | `TemplateResolver::resolve` CASE ORDER BY — secondary tie-break sort yok                         |
| P1     | `TemplateResolver::resolve` — `orWhere('ad', $yayinTipi)` normalize edilmemiş string eşleşmesi   |
| P2     | `CategoryFeatureWhitelist::pluck()` — ORDER BY yok                                               |

Bu bulgular sistemin çekirdek katmanında üç temel garantinin eksik olduğunu ortaya koydu:
**SSOT**, **Determinizm**, **Write-path Governance**.

---

## Decision

### 1. SSOT (Tek Otorite)

- Template seçimi tek resolver üzerinden yapılır: `TemplateResolver` veya `TemplateService` (delegate).
- Feature set üretimi tek servis üzerinden yapılır: `FeatureTemplateResolver`.
- Config dosyaları (`ups.php`, `filters.php`) DB karar mekanizmasının **yerine geçemez**.
- Hardcoded feature seti (`getMinimalFeatureSet`) production path'inde **yasaktır**.
- DB dışı ikinci/üçüncü otorite oluşturmak **yasaktır**.
- Yeni seçim yolu eklemek → **ADR zorunludur**.

### 2. Determinism Protokolü

- `first()` kullanımı ORDER BY **olmadan yasaktır**.
- ORDER BY her zaman **secondary tie-break** (en az `->orderBy('id')`) içermelidir.
- Slug/name eşleşmeleri `Str::lower()` + `trim()` ile normalize edilmelidir.
- Aynı input → aynı output garantisi **test ile kanıtlanmalıdır**.

### 3. Write-path Governance

- `feature_assignments`, `alt_kategori_yayin_tipi`, `yayin_tipi_sablonu` tabloları için
  raw DB write yasaktır.
- Observer tetiklenmeyen write merge edilemez.
- Write sonrası cache invalidation otomatik olmalıdır (Observer + `invalidateForJunction`).

### 4. Fallback Politikası

- Exception swallow **yasaktır**.
- `catch` içinde sessiz default dönüş **yasaktır**.
- Hardcoded minimal set production'da **yasaktır**.
- Fallback, yalnızca feature flag + audit log ile mümkündür.

### 5. Cache Authority

- Cache key üretimi: `UpsCacheService` tek otorite.
- Invalidation: `invalidateForJunction()` tek entry point.
- Manuel `Cache::put` / `Cache::forget` dışarıdan yasaktır.
- `clearCache()` çağrısı resolver + feature_grouped + templates namespace'lerini birlikte temizlemelidir.

### 6. CI Enforcement

- Yeni CI Guard: `scripts/ci-guard-determinism.sh`
- Taranan pattern'ler:
    - `getMinimalFeatureSet` çağrısı (`app/` altında, `tests/` dışında)
    - `Cache::forget` / `Cache::put` `UpsCacheService.php` dışında
    - Hardcoded `'baslik'`, `'fiyat'`, `'aciklama'` üçlüsü feature array içinde
- Quality Gate STEP 5.2'ye eklendi.

---

## Consequences

**Pozitif:**

- Aynı input → aynı template/feature output garantisi
- Exception sessizce yutulmuyor, gözlemlenebilir
- Cache invalidation tam coverage (3 namespace birlikte temizleniyor)
- CI, constitution ihlalini PR öncesinde yakalar

**Negatif/Trade-off:**

- `resolveTemplateFeatures` artık exception fırlatıyor → çağıran tarafların `try/catch` ile
  explicit handle etmesi gerekiyor (kontrollü hata, gizli hata değil)
- `getMinimalFeatureSet` metodu korunuyor ama yalnızca feature-flag + log ile çağrılabilir

**Kaldırılan Davranışlar:**

- `getMinimalFeatureSet` artık `resolveTemplateFeatures` catch bloğundan çıkarıldı
- `TemplateService::clearCache` artık `invalidate('templates')` yerine tüm namespace'leri temizliyor

---

## Alternatives Considered

1. **Feature flag ile fallback koruma** — Reddedildi: hata gizlenmeye devam eder, sadece konfigüre edilir.
2. **Minimal set DB'den çekme** — Reddedildi: SSOT zaten `FeatureTemplateResolver`; yeni yol = ikinci otorite.
3. **secondarySort için `updated_at`** — Reddedildi: timestamplar unique değil. `id` (auto-increment) deterministik.

---

## Frozen Invariants (ADR-002 ile uyumlu)

Bu ADR, ADR-002'nin (feature_assignments Architectural Freeze) kardeşidir.

| #   | İnvariant                                                                            |
| --- | ------------------------------------------------------------------------------------ |
| F-1 | Template seçimi her zaman pivot bağlantılı, ORDER BY `display_order, id` ile yapılır |
| F-2 | Feature set üretimi `FeatureTemplateResolver` üzerinden — hardcoded set yasak        |
| F-3 | Exception production path'inde yutulmaz                                              |
| F-4 | Cache invalidation `invalidateForJunction` üzerinden yapılır                         |
| F-5 | CI Guard ihlalleri merge blocker'dır                                                 |

---

## Yürürlük

Bu ADR merge edildiği andan itibaren bağlayıcıdır.
Bu çerçeve dışında davranış üretmek sistemik borç üretir.
