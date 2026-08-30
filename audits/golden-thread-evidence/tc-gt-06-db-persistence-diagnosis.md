# TC-GT-06 DB Persistence Teşhis Raporu

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `UNKNOWN`
- **Working Tree:** `UNKNOWN`
- **Evidence Date:** 2026-08-27T00:00:00Z (UTC) [TR: 2026-08-27 03:00:00 +03:00]
- **Evidence Level:** `PRODUCTION_VERIFIED / TEST_VERIFIED`
- **Production Authorization:** `AUTHORIZED (Backend Test Persistence)`
<!-- ───────────────────────────────────────────────────────────── -->

**Ortam:** `yalihanai_clone` (production kopyası) + `yalihanai_v2_production` (canlı)
**Durum:** Migration `TEST_VERIFIED` — TC-GT-06 DB persistence kanıtı **tamamlandı** (backend-driven test ile `DB_VERIFIED`). `syncFeatures` bug'ı ve `bina_yasi` schema hatası **çözüldü**; Golden Thread **`CERTIFIED`** (workaround'suz) ve production'da **`PRODUCTION_VERIFIED`**.

---

## 1. Özet

TC-GT-06 testi **PASS** olarak işaretleniyordu ancak `ilanlar` tablosunda **hiçbir kayıt oluşturmuyordu**. Bu bir **test boşluğu** (test gap), **migration hatası değil**. Migration'ın kendisi doğru çalışıyor; sorun, testin formu eksik doldurması ve zayıf assertion kullanması.

---

## 2. Kök Neden (Root Cause)

### 2.1 Testin zayıf assertion'ı

TC-GT-06'nın orijinal assertion'ı yalnızca **URL yönlendirmesini** kontrol ediyordu:

```ts
await page.waitForURL(/\/admin\/ilanlar\/[\d]+/, { timeout: 30000 }).catch(() => {});
const submitted = finalURL.includes('/admin/ilanlar/');
```

- `waitForURL` `.catch(() => {})` ile **hatayı yutuyor** — yönlendirme olmasa bile test devam ediyor.
- `submitted` yalnızca URL'de `/admin/ilanlar/` geçip geçmediğine bakıyor — wizard sayfasının kendisi zaten `/admin/ilanlar/create-wizard` olduğu için bu her zaman `true` dönebilir.
- **DB'de kayıt oluşup oluşmadığı hiç sorgulanmıyor.**

### 2.2 Form eksik dolduruluyor → 422

Teşhis testi (TC-GT-06b) submit sonrası ağ trafiğini yakaladı:

```
POST http://127.0.0.1:8001/admin/ilanlar [422]
{"message":"İlan sahibi seçimi zorunludur. (and 31 more errors)",
 "errors":{
   "ilan_sahibi_id":["İlan sahibi seçimi zorunludur."],
   "kat":["kat alanı zorunludur."],
   "havuz":["havuz alanı zorunludur."],
   "brut-alan":["brut-alan alanı zorunludur."],
   "arsa-alani":["arsa-alani alanı zorunludur."],
   "otopark":["otopark alanı zorunludur."],
   "esyali":["esyali alanı zorunludur."],
   "tapu-durumu":["tapu-durumu alanı zorunludur."],
   "net-alan":["net-alan alanı zorunludur."],
   ... (toplam 32 hata)
 }}
```

**Kök neden:** Test, wizard'ı gezerken yalnızca `baslik`, `fiyat`, konum ve fotoğraf dolduruyor. `ilan_sahibi_id`, `kat`, `havuz`, `brut-alan`, `arsa-alani`, `otopark`, `esyali`, `tapu-durumu`, `net-alan` gibi **32 zorunlu alan** boş kalıyor. Sunucu tarafı validasyonu 422 döndürüyor, `submitForm()` hata gösterip erken dönüyor — **hiçbir kayıt oluşmuyor.**

### 2.3 submitForm() akışı (doğrulandı)

1. Client-side kapılar **GEÇİYOR** (teşhis doğruladı):
   - `photoNative: 1` ✅ (fotoğraf var)
   - `kategoriSlug: "villa"` ✅
   - `yayinTipiSlug: "villa-satilik"` ✅
   - `qualityRecommendation: null` ✅
2. `wizardFetch(commitUrl, { method: 'POST', body: formData })` → `/admin/ilanlar`
3. Sunucu **422** döndürüyor (32 zorunlu alan eksik)
4. `submitForm()` hata mesajlarını gösterip `return` ediyor
5. **DB'de kayıt oluşmuyor**

---

## 3. Kanıt

| Kanıt | Dosya | İçerik |
|-------|-------|--------|
| Teşhis sonucu | `audits/golden-thread-evidence/tc-gt-06b-db-persistence.json` | Baseline 0 → After 0, recordCreated=false, 422 network log |
| Ekran görüntüsü | `audits/golden-thread-evidence/tc-gt-06b-step5-reached.png` | Step 5'e ulaşıldı |
| DB durumu | MySQL `yalihanai_clone` | iller=81, ilceler=15, mahalleler=20, ilanlar=0 |

---

## 4. Migration Durumu (Etkilenmedi)

Migration **doğru ve sağlam** — başarısız submit migration'ı etkilemedi:

| Tablo | Önce | Sonra (up) | down() sonrası |
|-------|------|-----------|----------------|
| iller | 3 | 81 | 3 |
| ilceler | 5 | 15 | 5 |
| mahalleler | 0 | 20 | 0 |

Migration `migrations` tablosunda kayıtlı: `2026_08_26_000001_reconcile_location_canonical_plaka_kodu`

---

## 5. Sonuç ve Öneriler

### 5.1 Migration için
- Migration **`TEST_VERIFIED`** — DB persistence sorunu migration'dan bağımsız.
- Production öncesi adımlar (kullanıcının listelediği) geçerli:
  1. Production DB backup doğrulaması
  2. Migration dosyası ve checksum son kontrol
  3. Yalnızca location reconciliation migration'ı çalıştır
  4. Kayıt sayıları ve orphan FK doğrulaması
  5. Property Hub HTTP 200 kontrolü
  6. Wizard cascade + gerçek DB persistence testi **ayrıca**

### 5.2 TC-GT-06 için (test boşluğu düzeltmesi)
TC-GT-06'nın gerçek DB persistence'ı kanıtlaması için:
1. **Tüm zorunlu alanları doldur** (ilan_sahibi_id, kat, havuz, brut-alan, arsa-alani, otopark, esyali, tapu-durumu, net-alan, vb.)
2. **DB'yi submit sonrası sorgula** (detail endpoint + doğrudan DB sorgusu)
3. **422 hata durumunu assertion'a bağla** — 422 alındıysa test FAIL olmalı
4. `waitForURL` `.catch()` kaldır — yönlendirme zorunlu olmalı

### 5.3 Production onayı
> **Production migration için hâlâ açık onay gerekiyor.** Clone testi tek başına canlı veritabanında çalıştırma yetkisi vermez. Kullanıcının onayı olmadan production DB'de migration çalıştırılmamalıdır.

---

## 6. Çözüm: Backend-Driven DB Persistence Testi (TC-GT-06-BE)

### 6.1 Sonuç — `DB_VERIFIED`

`tests/e2e/golden-thread-db-persistence-backend.spec.ts` testi, tam ve geçerli bir payload ile `POST /admin/ilanlar` çağırıp **200** aldı ve ilanı DB'de doğruladı:

| Doğrulama | Sonuç |
|-----------|-------|
| POST /admin/ilanlar | **200** (hata yutulmadı) |
| İlan ID | 6 |
| yayin_durumu | `taslak` |
| ana_kategori_id | 1 (Konut) |
| alt_kategori_id | 8 (Villa) |
| yayin_tipi_id | 22 (villa-satilik) |
| il_id / ilce_id / mahalle_id | 48 / 1 / 1 (Muğla / Bodrum) |
| ilan_sahibi_id | 1 (Atılay) |
| danisman_id | 2 (E2E Admin) |
| fiyat | 2500000.00 TRY |

Kanıt: `audits/golden-thread-evidence/tc-gt-06b-backend-db-persistence.json`

### 6.2 Testin doğrulama zinciri (kullanıcının istediği)

1. **Geçerli Kisi/ilan_sahibi fixture** — DB'de mevcut `kisiler.id=1` (Atılay, tenant 1) kullanıldı.
2. **Property Engine'in tüm zorunlu alanları** — schema resolver (8,22) villa-satilik kombinasyonunun tüm alanları geçerli değerlerle dolduruldu.
3. **POST 2xx** — `200` alındı, hata yutulmadı.
4. **İlan ID doğrulaması** — response'dan `id=6` çekildi.
5. **DB ilişkileri** — doğrudan DB sorgusu ile tenant/kategori/yayın tipi/konum/sahip/danışman doğrulandı.
6. **Başarısız durumda raporlama** — 422/500 durumunda response body + validation errors `tc-gt-06b-backend-failure.json`'a yazılıyor.

### 6.3 Bulunan GERÇEK uygulama bug'ı (syncFeatures) — ÇÖZÜLDÜ

Test sırasında `IlanCrudService::syncFeatures()` içinde **gerçek bir uygulama bug'ı** tespit edildi:

- `syncFeatures`, schema option değerlerini (`'hayir'`, `'6-10-yil'`, `['dogalgaz']`) doğrudan `ilanlar` tablosundaki **DB-native kolonlara** yazıyor.
- `esyali` → `tinyint(1)` kolonuna `'hayir'` (string) → **MySQL strict mode 500**
- `bina-yasi` → `year` kolonuna `'6-10-yil'` (string) → **MySQL strict mode 500**
- `isitma` → `isinma_tipi` (string cast) kolonuna `['dogalgaz']` (array) → **"Array to string conversion" 500**

**Etki:** Villa-satış (8/22) akışında bu üç alan doldurulduğunda kayıt 500 ile başarısız olur. Bu, wizard'ın gerçek kullanımında da tetiklenebilecek bir defekt.

**Test workaround'u (eski):** Bu üç alan `features[]` yerine **top-level** gönderildi — validasyon geçer, ancak `syncFeatures` bunları DB'ye yazmaz. Bu, DB persistence'ın çekirdek akışını kanıtlar ve bug'ı belgeler.

---

### 6.4 Çözüm — syncFeatures normalizasyonu + bina_yasi schema düzeltmesi

**Durum: ÇÖZÜLDÜ (2026-08-26).** Workaround kaldırıldı; üç alan artık canonical `features[]` akışıyla gönderiliyor ve DB-native değerlere normalize ediliyor.

#### 6.4.1 `IlanCrudService::syncFeatures()` — değer normalizasyonu

`app/Services/Ilan/IlanCrudService.php` içinde direct-column mapping'e bir normalizasyon katmanı eklendi:

- `$columnType` haritası: her `ilanlar` kolonu için DB-native tip (`boolean`, `int`, `float`, `year`, `string`).
- `normalizeFeatureValue()`: schema option değerini DB-native tipe dönüştürür.
  - `normalizeBoolean('evet'/'hayir'/'kismen')` → `true`/`false`
  - `normalizeYear('6-10-yil')` → `10` (aralık üst sınırı)
  - `normalizeString(['dogalgaz'])` → `'dogalgaz'` (array → comma-joined string)
- **İkinci latent bug:** `isitma` → `isinma_tipi` mapping'i düzeltildi. Test SQLite şemasında `isinma_tipi` kolonu yok; canonical kolon `isitma`. Mapping `'isitma' => 'isitma'` olarak değiştirildi.

#### 6.4.2 Schema bug'ı — `bina_yasi` kolonu `YEAR` → `INTEGER`

E2E testi, `bina_yasi` değerinin `10` yerine `2010` olarak saklandığını ortaya çıkardı. Kök neden: `ilanlar.bina_yasi` kolonu `YEAR` tipinde tanımlanmıştı, ancak kod tabanı bunu **bina yaşı (yıl cinsinden integer)** olarak kullanıyor:

- `PerformanceScoringService`: `$ilan->bina_yasi < 10` (yeni bina)
- `KiraTahminiService`: `$features['bina_yasi'] <= 5`
- `IlanReadModel`: `unsignedSmallInteger('bina_yasi')->comment('Building age in years')`

MySQL `YEAR` kolonu küçük integer'ları 4 haneli yıla çevirir: `10` → `2010`. Bu, "bina yaşı" semantiğini bozar.

**Düzeltme:** `database/migrations/2026_08_26_000002_fix_bina_yasi_column_type.php`
- Kolon tipi `YEAR` → `unsignedSmallInteger` (bina yaşı).
- Mevcut `YEAR` değerleri (>100) `-2000` ile yaşa çevrilir (örn. `2010` → `10`).
- `down()`: yaş → `+2000` ile `YEAR`'a geri döner (raw SQL ile, çünkü Doctrine DBAL `year` tipini tanımıyor).
- Up/down döngüsü doğrulandı.

#### 6.4.3 E2E test — workaround kaldırıldı

`tests/e2e/golden-thread-db-persistence-backend.spec.ts`:
- `esyali`, `bina-yasi`, `isitma` artık **canonical `features[]`** içinde gönderiliyor (workaround yok).
- Değerler schema slug'larıyla eşleşiyor: `'evet'`, `'6-10-yil'`, `['dogalgaz']`.
- DB assertion'ları normalize edilmiş değerleri doğruluyor: `esyali=1`, `bina_yasi=10`, `isitma='dogalgaz'`.

#### 6.4.4 Regresyon testi

`tests/Feature/Crud/IlanCrudFeatureNormalizationTest.php` — 7 test:
- `esyali` boolean normalizasyonu (`'evet'` → true, `'hayir'` → false)
- `bina-yasi` aralık → yıl (`'6-10-yil'` → 10, `'5'` → 5)
- `isitma` array → string (`['dogalgaz']` → `'dogalgaz'`)
- Numeric kolonlar (`brut-metrekare` → float, `oda-sayisi` → int)
- Eşlenmemiş slug'lar pivot tabloya persist edilir

**Sonuç:** 7/7 PASS.

---

### 6.5 Re-Certification — Golden Thread `CERTIFIED`

Workaround-free POST ve DB ilişkileri doğrulandı:

| Doğrulama | Sonuç |
|-----------|-------|
| POST /admin/ilanlar | **200** (canonical `features[]` akışı, workaround yok) |
| İlan ID | 8 |
| yayin_durumu | `taslak` |
| ana_kategori_id / alt_kategori_id / yayin_tipi_id | 1 / 8 / 22 |
| il_id / ilce_id / mahalle_id | 48 / 1 / 1 |
| ilan_sahibi_id / danisman_id | 1 / 2 |
| **esyali** (normalize) | `1` (boolean) |
| **bina_yasi** (normalize) | `10` (integer, schema düzeltmesi sonrası) |
| **isitma** (normalize) | `'dogalgaz'` (string) |

Kanıt: `audits/golden-thread-evidence/tc-gt-06b-backend-db-persistence.json`

**Golden Thread durumu: `CERTIFIED`** — TC-GT-06 backend persistence, migration clone, dynamic feature persistence artık workaround'suz doğrulandı. Production migration ve villa kayıt düzeltmesi bu bug çözüldükten sonra birlikte yapılabilir.

---

## 7. Production Re-Certification — Golden Thread `PRODUCTION_VERIFIED`

### 7.1 Production Migration Uygulaması (2026-08-27)

İki migration, ayrı backup + doğrulama + rollback kaydıyla production (`yalihanai_v2_production`) üzerinde uygulandı:

| Migration | Backup | Batch | Doğrulama |
|-----------|--------|-------|-----------|
| A: Location Reconciliation | `prod_pre_location_20260827_021626.sql` | 48 | iller=81, ilceler=15, mahalleler=20, Bodrum FK il_id=48 |
| B: `bina_yasi` YEAR→smallint | `prod_pre_bina_yasi_20260727_022923.sql` | 49 | `bina_yasi` = `smallint unsigned`, ilanlar=0 |

**Not:** Production `location_reconciliation_log` tablosunda schema drift vardı (eski şema: `old_id`/`new_id`/`action` kolonları eksik, `record_id` NOT NULL). `createLogTable()` idempotent schema fix ile çözüldü.

### 7.2 Production Golden Thread Testi (2026-08-27)

Production DB'ye bağlı geçici sunucu (port 8002) üzerinde E2E testi çalıştırıldı:

| Doğrulama | Sonuç |
|-----------|-------|
| POST /admin/ilanlar | **200** (canonical `features[]` akışı, workaround yok) |
| İlan ID | 1 |
| yayin_durumu | `taslak` |
| ana_kategori_id / alt_kategori_id / yayin_tipi_id | 1 / 8 / 22 |
| il_id / ilce_id / mahalle_id | 48 / 1 / 1 |
| ilan_sahibi_id / danisman_id | 1 / 2 |
| **esyali** (normalize) | `1` (boolean) |
| **bina_yasi** (normalize) | `10` (integer, migration B sonrası) |
| **isitma** (normalize) | `'dogalgaz'` (string) |

Kanıt: `audits/golden-thread-evidence/tc-gt-06b-backend-db-persistence.json` (timestamp `2026-08-27T06:08:23.671Z`)

Test ilanı (id=1) doğrulama sonrası production'dan temizlendi (ilanlar=0 geri yüklendi). Geçici sunucu (port 8002) durduruldu.

**Golden Thread durumu: `PRODUCTION_VERIFIED`** — Production DB üzerinde workaround-free POST, DB persistence ve syncFeatures normalizasyonu (`esyali`→boolean, `bina_yasi`→integer, `isitma`→string) doğrulandı.