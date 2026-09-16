# BACKLOG-01 — Arsa & İşyeri Dynamic Fields / Feature Matrix Gap Analysis

**Tarih:** 2026-09-08
**Hazırlayan:** Wenox (QA/E2E Agent)
**Branch:** release-candidate/RC2
**HEAD:** 4f195599
**Durum:** ANALYSIS_COMPLETE

---

## 1. Özet (Executive Summary)

Bu rapor, Arsa & Arazi ve İşyeri kategorileri için Wizard Step 2'de görüntülenen dinamik alanların (dynamic fields) mevcut durumunu, eksikliklerini ve kök nedenlerini analiz eder.

**Kritik Bulgu:** Wizard Step 2'nin aktif şema motoru (`step2-schema.blade.php` → `/api/v1/wizard/features` → `Wizard\FeatureTemplateResolver`) SADECE `feature_assignments` tablosunu sorgular. Bu tablo ise yalnızca Villa/Konut kategorileri için seed edilmiştir (119 kayıt). Arsa ve İşyeri kategorileri için `feature_assignments` tablosunda HİÇBİR kayıt yoktur.

Sonuç: **Arsa ve İşyeri kategorileri seçildiğinde Wizard Step 2'de sıfır dinamik alan görüntülenir.**

---

## 2. Mimari Çatışma — Üç Paralel Sistem

Sistemde dinamik alan tanımları için ÜÇ ayrı tablo ve seed mekanizması mevcuttur. Ancak Wizard Step 2'nin aktif render motoru bunlardan yalnızca birini kullanır.

### 2.1. Sistem A — `feature_assignments` (AKTİF — Wizard Step 2 SSOT)

| Özellik | Değer |
|---------|-------|
| **Tablo** | `feature_assignments` |
| **Seeder** | `FeatureAssignmentSeeder.php` |
| **Kapsama** | Villa Satılık (35), Villa Kiralık (36), Villa Günlük (35), Konut Global (8), Global (5) |
| **Toplam Kayıt** | 119 |
| **Arsa Kayıt Sayısı** | **0** |
| **İşyeri Kayıt Sayısı** | **0** |
| **Kullanan Controller** | `WizardFeatureController@index()` → `Wizard\FeatureTemplateResolver::resolveFeatures()` |
| **Kullanan View** | `step2-schema.blade.php` → `wizardStep2FeaturesComponent` |

**Sorgu Yolu:**
```
step2-schema.blade.php
  → fetch('/api/v1/wizard/features?ana_kategori_id=X&alt_kategori_id=Y&yayin_tipi_id=Z')
    → WizardFeatureController@index()
      → Wizard\FeatureTemplateResolver::resolveFeatures(mainCategoryId, subCategoryId, listingTypeId)
        → DB::table('feature_assignments as fa')
          ->join('features as f', ...)
          ->whereNull('fa.rolled_back_at')
          ->where('fa.aktiflik_durumu', true)
          ->where('fa.is_visible', true)
          ->where([scope cascades: global, main_category, sub_category, listing_type])
        → collapseScopedAssignments() (scope priority: listing_type=400 > sub_category=300 > main_category=200 > global=100)
```

### 2.2. Sistem B — `kategori_yayin_tipi_field_dependencies` (PASİF — Wizard'ta kullanılmıyor)

| Özellik | Değer |
|---------|-------|
| **Tablo** | `kategori_yayin_tipi_field_dependencies` |
| **Seeder** | `SmartFormsCanonicalSeeder.php` |
| **Kapsama** | Konut Satılık (14), Konut Kiralık (10), Arsa Satılık (4), İşyeri Satılık (3), İşyeri Kiralık (3), Yazlık (8) |
| **Toplam Kayıt** | ~42 |
| **Arsa Kayıt Sayısı** | 4 (brut-metrekare, imar-durumu, tapu-durumu, takas) |
| **İşyeri Kayıt Sayısı** | 6 (satilik: brut-metrekare, kat, tapu-durumu; kiralik: brut-metrekare, kat, aidat) |
| **Kullanan Controller** | `IlanWizardController@fieldSchema()` → `Wizard\FieldEngine\FieldResolver` |
| **Kullanan View** | Hiçbir aktif Blade view (deprecated step-2-structured-data-* şablonları referans verir) |

**Not:** `WizardFeatureController` docblock'unda "FieldResolver (kategori_yayin_tipi_field_dependencies tablosuna bağımlı) kaldırıldı" yazar. Bu sistem aktif Wizard Step 2'den çıkarılmıştır.

### 2.3. Sistem C — `category_field_schema` (PASİF — Hiçbir kod referans vermiyor)

| Özellik | Değer |
|---------|-------|
| **Tablo** | `category_field_schema` |
| **Seeder** | `CategoryFieldSchemaSeeder.php` |
| **Kapsama** | Konut Satılık, Konut Kiralık, Arsa Satılık, İşyeri Satılık, İşyeri Kiralık, Yazlık Günlük |
| **Arsa Kayıt Sayısı** | 14 (ada_no, parsel_no, pafta_no, imar_durumu, kaks, taks, gabari, yola_cephe, altyapi_su, altyapi_elektrik, altyapi_dogalgaz, altyapi_kanalizasyon, altyapi_yol, tapu_durumu) |
| **İşyeri Satılık Kayıt Sayısı** | 6 (isyeri_tipi, net_m2, bulundugu_kat, cephe, personel_kapasitesi, aidat) |
| **İşyeri Kiralık Kayıt Sayısı** | 4 (isyeri_tipi, net_m2, depozito, aidat) |
| **Kullanan Controller** | **HİÇBİRİ** — `grep -r "category_field_schema" app/` → 0 sonuç |
| **Kullanan View** | **HİÇBİRİ** |

**KRİTİK:** Bu tablo zengin Arsa/İşyeri alan tanımları içerir (KAKS, TAKS, gabari, ada/parsel, altyapı, işyeri tipi, personel kapasitesi) ancak uygulama içinde hiçbir kod bu tabloyu sorgulamamaktadır. Dead table.

---

## 3. Kategori ID Matrisi

`IlanKategoriSeeder.php`'den alınan kök kategoriler:

| Kategori | Slug | ID | Seviye | Alt Kategoriler |
|----------|------|-----|--------|-----------------|
| Konut | `konut` | 1 | 0 | Daire, Villa, Müstakil, ... |
| İşyeri | `isyeri` | 2 | 0 | Ofis, Dükkan, Fabrika, Depo |
| Arsa & Arazi | `arsa-arazi` | 3 | 0 | Arsa (Konut/Villa), Sanayi/Ticari, Tarla, Zeytinlik, Bağ-Bahçe, ... |
| Yazlık Kiralama | `yazlik-kiralama` | 4 | 0 | Villa Tipi, ... |
| Turistik Tesisler | `turistik-tesisler` | 5 | 0 | — |
| Projeden Satış | `projeden-satis` | 6 | 0 | — |

**Not:** ID'ler `updateOrCreate` ile sıralı atanır. Gerçek DB'de farklı olabilir.

---

## 4. Arsa & Arazi — Mevcut Durum

### 4.1. Wizard Step 2'de Görüntülenen Alanlar

**Sonuç: 0 alan**

`feature_assignments` tablosunda `main_category_id = 3 (arsa-arazi)` veya alt kategorileri (15-22) için hiçbir kayıt yoktur. `Wizard\FeatureTemplateResolver::resolveFeatures()` boş koleksiyon döner.

### 4.2. Olması Gereken Alanlar (Sistem C — `CategoryFieldSchemaSeeder`'den)

Arsa Satılık için tanımlı ama KULLANILMAYAN alanlar:

| field_slug | field_name | type | required | category |
|------------|-----------|------|----------|----------|
| `ada_no` | Ada No | text | false | temel |
| `parsel_no` | Parsel No | text | false | temel |
| `pafta_no` | Pafta No | text | false | temel |
| `imar_durumu` | İmar Durumu | select | **true** | temel |
| `kaks` | KAKS (Emsal) | number | false | fiziksel |
| `taks` | TAKS | number | false | fiziksel |
| `gabari` | Gabari (Kat) | number | false | fiziksel |
| `yola_cephe` | Yola Cephe | number | false | fiziksel |
| `altyapi_su` | Su | boolean | false | altyapi |
| `altyapi_elektrik` | Elektrik | boolean | false | altyapi |
| `altyapi_dogalgaz` | Doğalgaz | boolean | false | altyapi |
| `altyapi_kanalizasyon` | Kanalizasyon | boolean | false | altyapi |
| `altyapi_yol` | Yol | boolean | false | altyapi |
| `tapu_durumu` | Tapu Durumu | select | false | finansal |

### 4.3. Minimal Set (Sistem B — `SmartFormsCanonicalSeeder`'den)

Arsa Satılık için tanımlı ama KULLANILMAYAN alanlar (sadece 4):

| field_slug | field_name | type | required |
|------------|-----------|------|----------|
| `brut-metrekare` | Alan (m²) | number | true |
| `imar-durumu` | İmar Durumu | select | true |
| `tapu-durumu` | Tapu Durumu | select | false |
| `takas` | Takas | boolean | false |

### 4.4. Config'te Tanımlı Sözlükler (Kullanılmıyor)

`config/arsa-dictionaries.php` (deprecated) ve `config/yali_options.php`'de zengin Arsa sözlükleri mevcuttur:

- `imar_statusu`: 6 tip (İmarlı, İmarsız, Tarla, Villa İmarlı, Konut İmarlı, Ticari İmarlı)
- `kaks_ranges`: 5 aralık
- `taks_ranges`: 5 aralık
- `gabari_ranges`: 5 aralık
- `altyapi`: 6 tip
- `arsa_tipleri`: 10 tip
- `yola_cephe_tipleri`: 4 tip
- `konum_avantajlari`: 8 tip
- `parsel_nitelikleri`: 7 tip

Bu sözlükler hiçbir aktif kod tarafından Wizard Step 2'ye bağlanmamaktadır.

### 4.5. UPS Category Whitelist (Config)

`config/ups.php` → `category_whitelist['arsa-arazi']` şu feature_category slug'larını izinli listeler:
- `imar`, `altyapi`, `tapu`, `cevre`, `ulasim`, `yakin-cevre`, `arsa-ozellikleri`

Ancak bu whitelist, `Ups\FeatureTemplateResolver::applyFeatureCategoryWhitelist()` tarafından uygulanır — ki bu resolver Wizard Step 2'de KULLANILMAZ. Wizard-scoped `Wizard\FeatureTemplateResolver` bu whitelist'i uygulamaz.

### 4.6. Feature Assignment Rules (Config)

`config/feature-assignment-rules.php` → `arsa-arazi.satilik` şu slug'ları yasaklar:
- Konut iç mekân: oda-sayisi, banyo-sayisi, salon-sayisi, balkon-sayisi, yatak, oda, banyo, salon, balkon
- Teknik donanım: ankastre, klima, wifi, tv, buzdolabi, camasir, bulasik, asansor, jenerator, hidrofor
- Site: havuz, fitness, guvenlik, kamerali-guvenlik, cocuk-oyun, basketbol, tenis-kortu
- Yazlık/kiralama: gunluk-fiyat, haftalik-fiyat, aylik-fiyat, sezonluk-fiyat, min-konaklama, max-misafir, check-in, check-out, depozito, temizlik-ucreti, pet-friendly
- İşyeri: kira-getirisi, aidat, isitma-bedeli, ciro

Bu kurallar "yasaklı" listesidir. Ancak yasaklanacak özelliklerin önce `feature_assignments` tablosunda TANIMLANMIŞ olması gerekir. Arsa için hiçbir özellik tanımlanmadığından, bu yasak listesi anlamsızdır (boş küme üzerinde filtreleme).

---

## 5. İşyeri — Mevcut Durum

### 5.1. Wizard Step 2'de Görüntülenen Alanlar

**Sonuç: 0 alan**

`feature_assignments` tablosunda `main_category_id = 2 (isyeri)` veya alt kategorileri (ofis, dükkan, fabrika, depo) için hiçbir kayıt yoktur.

### 5.2. Olması Gereken Alanlar (Sistem C — `CategoryFieldSchemaSeeder`'den)

İşyeri Satılık için tanımlı ama KULLANILMAYAN alanlar:

| field_slug | field_name | type | required | category |
|------------|-----------|------|----------|----------|
| `isyeri_tipi` | İşyeri Tipi | select | **true** | temel |
| `net_m2` | Net m² | number | **true** | fiziksel |
| `bulundugu_kat` | Bulunduğu Kat | select | false | fiziksel |
| `cephe` | Cephe | select | false | fiziksel |
| `personel_kapasitesi` | Personel Kapasitesi | number | false | isyeri |
| `aidat` | Aidat | number | false | finansal |

İşyeri Kiralık için tanımlı ama KULLANILMAYAN alanlar:

| field_slug | field_name | type | required | category |
|------------|-----------|------|----------|----------|
| `isyeri_tipi` | İşyeri Tipi | select | **true** | temel |
| `net_m2` | Net m² | number | **true** | fiziksel |
| `depozito` | Depozito | number | false | finansal |
| `aidat` | Aidat | number | false | finansal |

### 5.3. Minimal Set (Sistem B — `SmartFormsCanonicalSeeder`'den)

İşyeri Satılık (3 alan): brut-metrekare, kat, tapu-durumu
İşyeri Kiralık (3 alan): brut-metrekare, kat, aidat

### 5.4. Feature Assignment Rules (Config)

`config/feature-assignment-rules.php` → `isyeri.satilik` yasaklar:
- Kiralama: gunluk-fiyat, aylik-kira, depozito, aidat-dahil
- Konut: oda-sayisi, banyo-sayisi, balkon-sayisi, mutfak, ankastre, ebeveyn-banyosu, denize-mesafe, plaj, havuz-kullanim

`isyeri.kiralik` yasaklar:
- Satış: tapu-durumu, ipotek, kredi-uygun, takas
- Konut: oda-sayisi, banyo-sayisi, balkon-sayisi, mutfak, ankastre, denize-mesafe

Aynı sorun: yasaklanacak özellikler `feature_assignments`'ta tanımlı değil, kural boş küme üzerinde çalışır.

---

## 6. Kök Neden Analizi

### 6.1. Birincik Neden — Resolver Çatışması

İki `FeatureTemplateResolver` sınıfı mevcuttur:

| Sınıf | Namespace | Kullanım Yeri |
|-------|-----------|---------------|
| SSOT | `App\Services\Ups\FeatureTemplateResolver` | `WizardController` (eski), 9+ tüketici |
| Wizard-scoped | `App\Services\Wizard\FeatureTemplateResolver` | `WizardFeatureController` (aktif Step 2) |

Wizard-scoped resolver, `feature_assignments` tablosunu doğrudan sorgular (raw SQL). SSOT resolver ise `FeatureAssignment` Eloquent modeli üzerinden `assignable_type` + `assignable_id` polymorphic ilişkisini kullanır.

**Sorun:** İki resolver farklı sorgu desenleri kullanır ve farklı kategoriler için veri görür.

### 6.2. İkincil Neden — Seeder Kapsamı

`FeatureAssignmentSeeder` docblock'unda açıkça belirtilir:
> Coverage:
> - Villa Satılık (main=1, sub=8, lt=1) = 35 fields
> - Villa Kiralık (main=1, sub=8, lt=2) = 36 fields
> - Villa Günlük (main=1, sub=8, lt=5) = 35 fields
> - Konut Global (main=1, sub=null, lt=null) = 8 fields
> - Global (main=null, sub=null, lt=null) = 5 fields
> Total: 119 assignments

Arsa (main=3) ve İşyeri (main=2) için hiçbir assignment tanımlanmamıştır.

### 6.3. Üçüncül Neden — Dead Table

`CategoryFieldSchemaSeeder` zengin Arsa/İşyeri alanlarını `category_field_schema` tablosuna seed eder. Ancak:
- `app/` dizininde bu tabloyu sorgulayan HİÇBİR kod yoktur
- `resources/views/` dizininde bu tabloya referans veren HİÇBİR view yoktur
- Tablo var ama erişilemez durumdadır (dead infrastructure)

---

## 7. Etki Analizi

### 7.1. Kullanıcı Etkisi

| Senaryo | Sonuç |
|---------|-------|
| Admin → Yeni Arsa İlanı → Wizard Step 2 | Boş form, sıfır dinamik alan |
| Admin → Yeni İşyeri İlanı → Wizard Step 2 | Boş form, sıfır dinamik alan |
| Admin → Yeni Konut/Villa İlanı → Wizard Step 2 | 35+ dinamik alan (çalışıyor) |
| Admin → Yeni Yazlık İlanı → Wizard Step 2 | Boş form (Villa günlük hariç) |

### 7.2. Veri Etkisi

Arsa/İşyeri ilanları oluşturulduğunda, dinamik özellik değerleri (`ilan_features` tablosu) boş kalır. İlan detay sayfasında, filtreleme sisteminde ve AI eşleştirme sisteminde bu özellikler eksiktir.

### 7.3. Golden Thread Etkisi

Golden Thread E2E testi (TC-GT-01 → TC-GT-06) Konut/Villa kategorisi ile çalıştığı için bu boşluk testlerde görünmez. Arsa/İşyeri kategorileri için benzer bir E2E test mevcut değildir.

---

## 8. Çözüm Önerileri

### 8.1. Kısa Vadeli (Quick Fix) — Önerilmez

`FeatureAssignmentSeeder`'a Arsa ve İşyeri kategorileri için `feature_assignments` kayıtları eklemek.

**Dezavantaj:** `features` tablosunda Arsa/İşyeri'ne özgü feature'lar (ada_no, parsel_no, kaks, taks, gabari, isyeri_tipi, personel_kapasitesi) tanımlı değildir. Önce `features` tablosuna bu feature'ların eklenmesi gerekir.

### 8.2. Orta Vadeli (Doğru Çözüm) — Önerilen

`CategoryFieldSchemaSeeder`'ın seed ettiği `category_field_schema` tablosunu aktif Wizard Step 2 akışına bağlamak:

1. `features` tablosuna Arsa/İşyeri'ne özgü feature'ları ekle (ada_no, parsel_no, pafta_no, kaks, taks, gabari, yola_cephe, altyapi_su/elektrik/dogalgaz/kanalizasyon/yol, isyeri_tipi, personel_kapasitesi, cephe)
2. `feature_assignments` tablosuna Arsa Satılık, Arsa Kiralık, İşyeri Satılık, İşyeri Kiralık, İşyeri Devren için assignment'lar ekle
3. `FeatureAssignmentSeeder`'ı genişlet veya yeni bir `ArsaIsyeriFeatureAssignmentSeeder` oluştur
4. `config/ups.php` → `category_whitelist`'i gözden geçir (Arsaat zaten tanımlı, İşyeri için `ticari` whitelist'i mevcut)

### 8.3. Uzun Vadeli (Mimari Temizlik)

1. `category_field_schema` dead table'ını kaldır veya `feature_assignments` ile birleştir
2. `SmartFormsCanonicalSeeder` → `kategori_yayin_tipi_field_dependencies` sistemini de `feature_assignments` ile birleştir
3. İki `FeatureTemplateResolver` sınıfını tek SSOT'ta birleştir (`TODO(P2-DS-01)` zaten işaretli)
4. `config/arsa-dictionaries.php` (deprecated) → `config/yali_options.php` migrasyonunu tamamla

---

## 9. Önerilen Öncelik Sırası

| Öncelik | Görev | Efor | Etki |
|---------|-------|------|------|
| P0 | Arsa Satılık için `feature_assignments` seed (14 alan) | 2-3 saat | Arsa ilanlarında dinamik alan görünür |
| P0 | İşyeri Satılık/Kiralık için `feature_assignments` seed (10 alan) | 2 saat | İşyeri ilanlarında dinamik alan görünür |
| P1 | `features` tablosuna Arsa/İşyeri feature'larını ekle | 1 saat | Seed için önkoşul |
| P1 | Arsa Kiralık için `feature_assignments` seed | 1 saat | Arsa kiralama akışı tamamlanır |
| P2 | `category_field_schema` dead table'ını kaldır | 30 dk | Mimari temizlik |
| P2 | İki FeatureTemplateResolver'ı birleştir | 4-6 saat | SSOT tek nokta |
| P3 | Arsa/İşyeri için E2E test (TC-GT-07+) | 3-4 saat | Regression koruması |

---

## 10. Ek — Dosya Referans Matrisi

### Aktif Wizard Step 2 Akışı

```
resources/views/admin/ilanlar/wizard/step-2-unified.blade.php
  → @include('admin.ilanlar.wizard.step2-schema')
    → resources/views/admin/ilanlar/wizard/step2-schema.blade.php
      → x-data="wizardStep2FeaturesComponent({ ilanId: ... })"
        → fetch('/api/v1/wizard/features?...')
          → routes/api/v1/ilan-wizard.php
            → WizardFeatureController@index()
              → Wizard\FeatureTemplateResolver::resolveFeatures()
                → DB::table('feature_assignments') ← SADECE BU TABLO
```

### Pasif / Dead Sistemler

```
# Sistem B (pasif)
app/Services/Wizard/FieldEngine/FieldResolver.php
  → KategoriYayinTipiFieldDependency::where('kategori_slug', ...)
  → kategori_yayin_tipi_field_dependencies tablosu
  → WizardFeatureController docblock: "kaldırıldı"

# Sistem C (dead)
database/seeders/CategoryFieldSchemaSeeder.php
  → category_field_schema tablosu
  → app/ içinde 0 referans
```

### Config Dosyaları

```
config/arsa-dictionaries.php     → Deprecated, zengin Arsa sözlükleri (KAKS, TAKS, imar, altyapı)
config/yali_options.php          → Aktif config, arsaimar_statusu + arsa_tipleri
config/feature-assignment-rules.php → Yasaklı feature slug'ları (Arsa/İşyeri tanımlı)
config/ups.php                  → category_whitelist (arsa-arazi, konut, yazlik, ticari, villa)
```

### Seeder'lar

```
database/seeders/FeatureAssignmentSeeder.php      → 119 kayıt (Villa/Konut ONLY)
database/seeders/SmartFormsCanonicalSeeder.php    → ~42 kayıt (tüm kategoriler, pasif tablo)
database/seeders/CategoryFieldSchemaSeeder.php    → ~60+ kayıt (Arsa/İşyeri zengin, dead tablo)
```

---

## 11. Sonuç

Arsa ve İşyeri kategorileri için Wizard Step 2'de dinamik alan görüntülenmemesinin kök nedeni, aktif resolver'ın (`Wizard\FeatureTemplateResolver`) sorguladığı `feature_assignments` tablosunun yalnızca Villa/Konut için seed edilmiş olmasıdır. Arsa/İşyeri için zengin alan tanımları mevcuttur (`CategoryFieldSchemaSeeder` ve `SmartFormsCanonicalSeeder`) ancak bu tanımlar pasif/dead tablolardadır ve aktif Wizard akışına bağlı değildir.

**Gap severity: HIGH** — Arsa ve İşyeri kategorileri için Wizard Step 2 tamamen boştur. Kullanıcı bu kategorilerde ilan oluştururken hiçbir kategoriye özgü özellik giremez.

---

**Rapor Sonu** — Wenox, 2026-09-08T17:25Z
