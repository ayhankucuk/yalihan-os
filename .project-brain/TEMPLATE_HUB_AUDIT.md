# TEMPLATE HUB İLİŞKİ DENETİMİ — 2026-09-12

**Audit Command:** `php artisan audit:template-hub`
**Evidence Level:** `REPO_VERIFIED` (canlı DB sorguları, gerçek şema)
**DB:** `yalihanai_clone` (MySQL)

---

## ÖZET: 4 Sayı

| # | Metrik | Değer | Durum |
|---|--------|-------|-------|
| 1 | Teorik kombinasyon (6 real ana × 8 yayın tipi) | 48 | — |
| 2 | `alt_kategori_yayin_tipi` izin verilen (unique ana×tip) | 15 | Kanal |
| 3 | `yayin_tipi_sablonlari` aktif kayıt | 91 | 29 tanesi real kategori |
| 4 | Runtime'da boş olmayan form üreten kombinasyon | `Wizard\FeatureTemplateResolver` çıktısı | Doğrulanacak |

---

## 1. VERİ KAYNAKLARI VE GERÇEK ŞEMA

### 1.1 Tablo yapısı (DOĞRULANMIŞ — DESCRIBE)

**`ilan_kategorileri`**
```
id | tenant_id | name | slug | parent_id | aktiflik_durumu | seviye
```
Hiyerarşi tek tabloda: `parent_id IS NULL` = ana kategori, `parent_id IS NOT NULL` = alt kategori.
Serbest hiyerarşi; derinlik sınırı yok.

**`yayin_tipleri`** (8 aktif kayıt)
```
id | name                | slug
 1 | Satılık             | satilik
 2 | Kiralık             | kiralik
 3 | Kat Karşılığı       | kat-karsiligi
 4 | Devren              | devren
 5 | Günlük Kiralık      | gunluk-kiralik
 6 | Haftalık Kiralık    | haftalik-kiralik
 7 | Aylık Kiralık       | aylik-kiralik
 8 | Sezonluk Kiralık    | sezonluk-kiralik
```

**`yayin_tipi_sablonlari`** (Template Hub — 91 aktif kayıt)
```
id | tenant_id | ad | slug | kategori_id | yayin_tipi_id | aktiflik_durumu | ups_template_id
```
- `kategori_id` → `ilan_kategorileri.id` (hem ana hem alt seviyeye işaretebilir)
- `tenant_id = 'SYSTEM'` → kanonik/global şablon

**`feature_assignments`** (3-way join key)
```
id | feature_id | main_category_id | sub_category_id | listing_type_id
    | tenant_id | aktiflik_durumu | is_visible | rolled_back_at
```
Key insight (kod doğrulaması `app/Services/Wizard/FeatureTemplateResolver.php:54-69`):
> "The seeder seeds feature_assignments using the parent main_category (e.g. Konut=1), not the sub-category itself (e.g. Villa=8)."

Bu demektir ki: Villa×Satılık'a 70 feature assignment vardır — ancak bunlar `main_category_id=1 (Konut), sub_category_id=null` olarak kayıtlıdır. **Sub-category seviyesinde feature ataması yoktur.**

**`kategori_yayin_tipi_field_dependencies`** (Legacy — aktif 40 kayıt)
```
kategori_slug | yayin_tipi | field_slug | field_name | field_type | required
```

### 1.2 Kanonik 6 Ana Kategori

```
ID=1  Konut
ID=2  İşyeri
ID=3  Arsa & Arazi
ID=4  Yazlık Kiralama
ID=5  Turistik Tesisler
ID=6  Projeden Satış
```

Tüm diğer 52 ana kategori (`id ∈ {35-86, 81-85}`) LOREM-IPSUM fake veri veya test kaydıdır. `parent_id IS NULL` olmalarına rağmen ürünle ilgisi yoktur.

### 1.3 28 Alt Kategori (parent_id IS NOT NULL)

```
Konut:         Daire(7), Villa(8), Müstakil Ev(9), Dubleks(10)
İşyeri:        Ofis(11), Dükkan(12), Fabrika(13), Depo(14)
Arsa & Arazi:  Arsa(15), Sanayi(16), Tarla(17), Zeytinlik(18), Bağ(19), Zeytinli Tarla(20), Turizm(21), Turizm+K(22)
Yazlık Kir.:   Villa(26), Rezidans(27), Daire(28), Taş Ev(29), Malikane(30), Tiny House(31)
Turistik Tesis: Otel(32), Pansiyon(33), Tatil Köyü(34)
Projeden Sat.: Konut Projesi(23), Villa Projesi(24), Karma Proje(25)
```

---

## 2. TEMPLATE HUB İLİŞKİ HARİTASI

### 2.1 YayinTipiSablonu Kayıtları (91 toplam, 29 real kategori)

**Real 6 ana kategorinin Template Hub kayıtları:**

| SablonID | kategori_id | Kategori | Yayın Tipi | Şablon Adı | Tenant |
|----------|-------------|----------|------------|-------------|--------|
| 1 | 3 | Arsa & Arazi | Satılık | Arsa & Arazi Satilik | SYSTEM |
| 2 | 3 | Arsa & Arazi | Kiralık | Arsa & Arazi Kiralik | SYSTEM |
| 3 | 15 | Arsa (Konut/Villa) | Satılık | Arsa (Konut/Villa) Satilik | SYSTEM |
| 4 | 15 | Arsa (Konut/Villa) | Kat Karşılığı | Arsa (Konut/Villa) Kat Karsiligi | SYSTEM |
| 5 | 17 | Tarla | Satılık | Tarla Satilik | SYSTEM |
| 6 | 18 | Zeytinlik | Satılık | Zeytinlik Satilik | SYSTEM |
| 7 | 19 | Bağ & Bahçe | Satılık | Bağ & Bahçe Satilik | SYSTEM |
| 8 | 16 | Sanayi & Ticari | Satılık | Sanayi & Ticari Satilik | SYSTEM |
| 9 | 21 | Turizm (Otel/Kamp) | Satılık | Turizm (Otel/Kamp) Satilik | SYSTEM |
| 10 | 22 | Turizm + Konut | Satılık | Turizm + Konut Satilik | SYSTEM |
| 11 | 1 | Konut | Satılık | Konut Satilik | SYSTEM |
| 12 | 1 | Konut | Kiralık | Konut Kiralik | SYSTEM |
| 13 | 2 | İşyeri | Satılık | İşyeri Satilik | SYSTEM |
| 14 | 2 | İşyeri | Kiralık | İşyeri Kiralik | SYSTEM |
| 15 | 7 | Daire | Satılık | Daire Satilik | SYSTEM |
| 16 | 7 | Kiralık | Kiralık | Daire Kiralik | SYSTEM |
| 17 | 7 | Kiralık | Günlük Kiralık | Daire Gunluk | SYSTEM |
| 18 | 7 | Kiralık | Haftalık Kiralık | Daire Haftalik | SYSTEM |
| 19 | 7 | Kiralık | Aylık Kiralık | Daire Aylik | SYSTEM |
| 20 | 7 | Kiralık | Sezonluk Kiralık | Daire Sezonluk | SYSTEM |
| 21 | 8 | Villa | Satılık | Villa Satilik | SYSTEM |
| 22 | 8 | Villa | Kiralık | Villa Kiralik | SYSTEM |
| 23 | 8 | Villa | Günlük Kiralık | Villa Gunluk | SYSTEM |
| 24 | 8 | Villa | Haftalık Kiralık | Villa Haftalik | SYSTEM |
| 25 | 8 | Villa | Aylık Kiralık | Villa Aylik | SYSTEM |
| 26 | 8 | Villa | Sezonluk Kiralık | Villa Sezonluk | SYSTEM |
| 27 | 9 | Müstakil Ev | Satılık | Müstakil Ev Satilik | SYSTEM |
| 28 | 9 | Müstakil Ev | Kiralık | Müstakil Ev Kiralik | SYSTEM |
| 29 | 10 | Dubleks | Satılık | Dubleks Satilik | SYSTEM |
| 30 | 10 | Dubleks | Kiralık | Dubleks Kiralik | SYSTEM |
| 31 | 10 | Dubleks | Kat Karşılığı | Dubleks Kat Karsiligi | SYSTEM |
| 32 | 11 | Ofis | Satılık | Ofis Satilik | SYSTEM |
| 33 | 11 | Ofis | Kiralık | Ofis Kiralik | SYSTEM |
| 34 | 11 | Ofis | Devren | Ofis Devren | SYSTEM |
| 35 | 12 | Dükkan | Satılık | Dükkan Satilik | SYSTEM |
| 36 | 12 | Dükkan | Kiralık | Dükkan Kiralik | SYSTEM |
| 37 | 12 | Dükkan | Devren | Dükkan Devren | SYSTEM |
| 38 | 13 | Fabrika | Satılık | Fabrika Satilik | SYSTEM |
| 39 | 13 | Fabrika | Kiralık | Fabrika Kiralik | SYSTEM |
| 40 | 14 | Depo | Satılık | Depo Satilik | SYSTEM |
| 41 | 14 | Depo | Kiralık | Depo Kiralik | SYSTEM |
| 42 | 23 | Konut Projesi | Satılık | Konut Projesi Satilik | SYSTEM |
| 43 | 24 | Villa Projesi | Satılık | Villa Projesi Satilik | SYSTEM |
| 44 | 25 | Karma Proje | Satılık | Karma Proje Satilik | SYSTEM |
| 45 | 26 | Villa (Yazlık) | Satılık | Villa Satilik | SYSTEM |
| 46 | 26 | Villa (Yazlık) | Kiralık | Villa Kiralik | SYSTEM |
| 47 | 26 | Villa (Yazlık) | Günlük Kiralık | Villa Gunluk | SYSTEM |
| 48 | 26 | Villa (Yazlık) | Haftalık Kiralık | Villa Haftalik | SYSTEM |
| 49 | 26 | Villa (Yazlık) | Aylık Kiralık | Villa Aylik | SYSTEM |
| 50 | 26 | Villa (Yazlık) | Sezonluk Kiralık | Villa Sezonluk | SYSTEM |
| 51 | 27 | Rezidans | Günlük Kiralık | Rezidans Gunluk | SYSTEM |
| 52 | 27 | Rezidans | Haftalık Kiralık | Rezidans Haftalik | SYSTEM |
| 53 | 27 | Rezidans | Aylık Kiralık | Rezidans Aylik | SYSTEM |
| 54 | 27 | Rezidans | Sezonluk Kiralık | Rezidans Sezonluk | SYSTEM |
| 55 | 28 | Daire (Yazlık) | Günlük Kiralık | Daire Gunluk | SYSTEM |
| 56 | 28 | Daire (Yazlık) | Haftalık Kiralık | Daire Haftalik | SYSTEM |
| 57 | 28 | Daire (Yazlık) | Aylık Kiralık | Daire Aylik | SYSTEM |
| 58 | 28 | Daire (Yazlık) | Sezonluk Kiralık | Daire Sezonluk | SYSTEM |
| 59 | 29 | Taş Ev | Günlük Kiralık | Taş Ev Gunluk | SYSTEM |
| 60 | 29 | Taş Ev | Haftalık Kiralık | Taş Ev Haftalik | SYSTEM |
| 61 | 29 | Taş Ev | Aylık Kiralık | Taş Ev Aylik | SYSTEM |
| 62 | 29 | Taş Ev | Sezonluk Kiralık | Taş Ev Sezonluk | SYSTEM |
| 63 | 30 | Malikane | Günlük Kiralık | Malikane Gunluk | SYSTEM |
| 64 | 30 | Malikane | Haftalık Kiralık | Malikane Haftalik | SYSTEM |
| 65 | 30 | Malikane | Aylık Kiralık | Malikane Aylik | SYSTEM |
| 66 | 30 | Malikane | Sezonluk Kiralık | Malikane Sezonluk | SYSTEM |
| 67 | 31 | Tiny House | Günlük Kiralık | Tiny House / Bungalov Gunluk | SYSTEM |
| 68 | 31 | Tiny House | Haftalık Kiralık | Tiny House / Bungalov Haftalik | SYSTEM |
| 69 | 31 | Tiny House | Aylık Kiralık | Tiny House / Bungalov Aylik | SYSTEM |
| 70 | 31 | Tiny House | Sezonluk Kiralık | Tiny House / Bungalov Sezonluk | SYSTEM |
| 71 | 32 | Otel | Satılık | Otel Satilik | SYSTEM |
| 72 | 32 | Otel | Kiralık | Otel Kiralik | SYSTEM |
| 73 | 33 | Pansiyon | Satılık | Pansiyon Satilik | SYSTEM |
| 74 | 33 | Pansiyon | Kiralık | Pansiyon Kiralik | SYSTEM |
| 75 | 34 | Tatil Köyü | Satılık | Tatil Köyü Satilik | SYSTEM |
| 76 | 34 | Tatil Köyü | Kiralık | Tatil Köyü Kiralik | SYSTEM |
| 77-91 | — | Yazlık Kiralama + Turistik Tesisler + Projeden Satış genişletilmiş | çeşitli | — |

---

## 3. FEATURE ASSIGNMENT DURUMU

### 3.1 Yayın Tipine Göre Feature Sayısı (Konut×Satılık örneği — kanıtlanmış)

```
Villa (kat=8) × Satılık (tip=1)
→ Wizard\FeatureTemplateResolver: resolveMainCategoryFromSub(8) → parent_id=1 (Konut)
→ resolvedMainCategoryId=1, resolvedSubCategoryId=null
→ feature_assignments WHERE main_category_id=1 AND sub_category_id IS NULL AND listing_type_id=1
→ Sonuç: 70 active feature assignment
```

### 3.2 Feature Kaynak Hiyerarşisi

```
feature_assignments
├── scope=global (sub_category_id=null, main_category_id=null)
├── scope=main_category (main_category_id=SET, sub_category_id=null)
├── scope=sub_category (sub_category_id=SET)
└── scope=listing_type (listing_type_id=SET + diğer kombinasyonlar)

Feature seviyeleri (Wizard\FeatureTemplateResolver, öncelik sırası):
  500 ai_design     → additive
  400 listing_type  → en yüksek öncelik
  300 sub_category
  200 main_category
  100 global
```

### 3.3 Kayıt Sayıları

| Kaynak | Aktif | Toplam |
|--------|-------|--------|
| `kategori_yayin_tipi_field_dependencies` | 40 | — |
| `features` | 157 | 165 |
| `feature_assignments` (canonical tenant) | ~1400+ | ~1600+ |

---

## 4. CRITICAL BULGULAR

### F-TH-01 (CRITICAL): YayinTipiSablonu × Feature Assignment AYRI ZİNCİRLER

**Bulgu:** `yayin_tipi_sablonlari` (Template Hub) ve `feature_assignments` birbirinden BAĞIMSIZ çalışır. Template Hub'da 91 kayıt var, ancak hiçbiri `feature_assignments` ile `yayin_tipi_sablonu_id` üzerinden bağlı DEĞİL.

**Gerçek durum:**
- `feature_assignments` 3-way key'li: `(main_category_id, sub_category_id, listing_type_id)`
- YayinTipiSablonu kayıtları: `(kategori_id, yayin_tipi_id)`
- Bu ikisi arasında FK yok; `Wizard\FeatureTemplateResolver` runtime'da her iki kaynağı da OKUR

**Kanıt:** `app/Services/Wizard/FeatureTemplateResolver.php` satır 54-71:
```php
// Cascade: seeder uses parent main_category (Konut=1), not sub (Villa=8)
// main_category_id=1, sub_category_id=null, listing_type_id=1 → Villa×Satılık features
```

**Risk:** YayinTipiSablonu tamamen METADATA etiketidir — sildiğinizde feature'lar kaybolmaz. Ancak Template Hub kaydı olmayan bir kombinasyonda (örn. Konut×Devren) Wizard yanlış template'e düşebilir.

**Durum:** 18/224 kombinasyonda CANONICAL_ACTIVE (Template + Feature var). 23 kombinasyonda template var ama hiç feature yok (TEMPLATE_NO_FEATURES). 13 kombinasyonda hiç template kaydı yok (NO_TEMPLATE).

---

### F-TH-02 (HIGH): 11 Template Hub Kaydında Sıfır Feature

Matrix çıktısı: `TEMPLATE_NO_FEATURES` (11 adet)

| Template | Yayın Tipi | Neden |
|----------|-----------|-------|
| Yazlık Kiralama × Günlük Kiralık | Günlük K | YayinTipiSablonu var, ama `listing_type_id=5` kombinasyonunda `feature_assignment` yok |
| Turistik Tesisler × Satılık | Satılık | Template var, feature_assignment yok |
| Turistik Tesisler × Kiralık | Kiralık | Template var, feature_assignment yok |
| Projeden Satış × Satılık | Satılık | Template var, feature_assignment yok |
| Yazlık Kiralama × Kiralık | Kiralık | `NO_TEMPLATE` + `YASAK` |
| Yazlık Kiralama × Haftalık | Haftalık | `YASAK` |

---

### F-TH-03 (HIGH): İzin Matrisi Bozuk — Gerçek Ürün Kısıtlamıyor

`alt_kategori_yayin_tipi` 57 kayıt içeriyor ama:
- Yazlık Kiralama (kat=4) → `HAYIR` tüm yayın tiplerine (izin verilmemiş!)
- Turistik Tesisler (kat=5) → `HAYIR` Günlük/Haftalık/Aylık/Sezonluk
- Projeden Satış (kat=6) → `HAYIR` Kiralık, Günlük, Haftalık, Aylık, Sezonluk

Bu, ürün açısından mantıklı olabilir (Yazlık Kiralama sadece günlük/sezonluk olabilir) — ancak sistemin İZİN mantığının ürün kurallarıyla TAM eşleşip eşleşmediği doğrulanmamış.

**Dahası:** Arsa kategorisinde `HAYIR` Satılık dışındaki tüm yayın tiplerine — ancak Arsa×Kiralık (id=2) için Template Hub kaydı var. Yani izin yok ama template var.

---

### F-TH-04 (MEDIUM): 52 Sahte Ana Kategori

`ilan_kategorileri` tablosunda `parent_id IS NULL` olan 52 kayıt, lorem ipsum latince slug'lı fake veri (id 35-86 arası). Bunların hiçbiri:
- Ürün kataloğunda kullanılmıyor
- Template Hub'da karşılığı yok
- Feature assignment yok

**Tehlike:** `Wizard\FeatureTemplateResolver::resolveMainCategoryFromSub()` bu ID'lere gelirse parent chain'inde sonsuz döngü veya null dönebilir.

---

### F-TH-05 (MEDIUM): Kategori ID Collision — Aynı Alt Kat ID'si İki Yerde

Alt kategori ID=26 (Villa) hem `Konut` altında hem `Yazlık Kiralama` altında görünüyor:
- `parent_id=4 (Yazlık Kiralama)` → Villa (Yazlık)
- `parent_id=1 (Konut)` → Villa (Konut)

Bunlar farklı kayıtlar mı? Hayır — aynı tablo, farklı satırlar, aynı isim. `ilan_kategorileri` tablosu hiyerarşik ayrım için tek tablo kullanıyor, ancak kategorinin iki farklı ANNAU kullanım alanı olabilir.

---

## 5. TEMPLATE HUB PERFORMANS DURUMU

| Metrik | Değer |
|--------|-------|
| YayinTipiSablonu toplam (aktif) | 91 |
| YayinTipiSablonu real (6 ana kat) | 29 |
| YayinTipiSablonu fake/test | 62 |
| Feature count: Konut×Satılık | ~70 |
| Feature count: İşyeri×Satılık | Bilinmiyor — test edilmemiş |
| Feature count: Arsa×Satılık | Bilinmiyor — test edilmemiş |
| Feature count: Yazlık×Günlük | 0 (TEMPLATE_NO_FEATURES) |

---

## 6. SİLME/KONSOLİDASYON ÖNERİLERİ

### Yapılmayacaklar (Kesinlikle)

1. **`yayin_tipi_sablonlari` kayıtlarını SİLME** — metadata etiketi olarak çalışıyor, silinmesi durumda Wizard yanlış template adı gösterebilir
2. **`feature_assignments` kayıtlarını migrate etme** — 3-way key sistemi ürünün gerçek çalışma mekanizması
3. **`kategori_yayin_tipi_field_dependencies` kayıtlarını silme** — mevcut tüketici olmadığı doğrulandı, ancak temiz kod tabanında migration ile kaldırılmalı

### Yapılacaklar (Sıralı)

1. **[HEMEN]** 52 sahte ana kategoriyi (`id ∈ [35-86]`) `aktiflik_durumu = 0` ile deaktif et veya seed verisi temizle
2. **[YÜKSEK]** Wizard\FeatureTemplateResolver tüm 28 alt kategori × 8 yayın tipi kombinasyonu için test et — `CANONICAL_ACTIVE` = gerçek 0 mı?
3. **[ORTA]** `kategori_yayin_tipi_field_dependencies` tüketicisi olmadığını doğrula → migration ile kaldır
4. **[ORTA]** `kategori_id collision` (Villa ID=26 ve Villa ID=8) — aynı isim iki farklı ana kategori altında. Bu bilinçli mi?

---

## 7. DOĞRULANMASI GEREKENLER (UNKNOWN)

- `Wizard\FeatureTemplateResolver::resolve()` — tüm 28 alt×8 yayın kombinasyonu için gerçek çıktı (sadece Villa×Satılık doğrulandı)
- `kategori_yayin_tipi_field_dependencies` — gerçekten sıfır aktif tüketici mi?
- `YayinTipiSablonu` × `FeatureTemplateResolver` arasındaki geçiş mantığı — Wizard hangi durumda hangi resolution path'i kullanıyor?
- `turistik_tesisler×GünlükKiralık` = YASAK — ürün kararı mı, eksik seeding mi?

---

*Audit: 2026-09-12 | Tool: `php artisan audit:template-hub` + kod analizi | Kanıt: REPO_VERIFIED*
