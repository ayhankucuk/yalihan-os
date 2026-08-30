# Location Reconciliation Migration — Clone Test Raporu

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `UNKNOWN`
- **Working Tree:** `UNKNOWN`
- **Evidence Date:** 2026-08-26T00:00:00Z (UTC) [TR: 2026-08-26 03:00:00 +03:00]
- **Evidence Level:** `TEST_VERIFIED`
- **Production Authorization:** `NONE (Disposable Clone DB)`
<!-- ───────────────────────────────────────────────────────────── -->

**Ortam:** `yalihanai_clone` (production kopyası, MySQL 8.0)  
**Migration:** `2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php`

---

## 1. Özet

Production'a güvenli geçiş için, location reconciliation migration'ı **yalihanai_clone** (production kopyası) üzerinde tam döngü (up → doğrulama → down → doğrulama) test edildi. Migration hem `up()` hem `down()` yönünde **başarıyla** çalıştı ve Golden Thread E2E testleri (TC-GT-05/06) artık **PASS** durumunda.

---

## 2. Baseline (Migration Öncesi)

| Tablo | Kayıt | Detay |
|-------|-------|-------|
| `iller` | 3 | Muğla(id=1, plaka=48), İstanbul(id=2, plaka=34), Ankara(id=3, plaka=6) |
| `ilceler` | 5 | Bodrum(1,il=48), Marmaris(2,il=48), Milas(3,il=48), Beşiktaş(4,il=34), Kadıköy(5,il=34) |
| `mahalleler` | 0 | — |

**Tespit edilen veri sorunları:**
- Ankara plaka kodu `6` (canonical `06` olmalı) — `str_pad` ile normalize edildi
- İlçeler canonical olmayan ID'lerde (Bodrum=1, Marmaris=2, Milas=3)
- Beşiktaş ve Kadıköy (İstanbul ilçeleri) canonical Muğla ilçelerinin ID'lerini işgal ediyor
- `mahalleler` tamamen boş

---

## 3. Migration `up()` Sonucu

| Tablo | Önce | Sonra | Değişim |
|-------|------|-------|---------|
| `iller` | 3 | **81** | +78 (tüm canonical iller eklendi) |
| `ilceler` | 5 | **15** | +10 (13 canonical Muğla ilçesi + Beşiktaş + Kadıköy) |
| `mahalleler` | 0 | **20** | +20 (Bodrum mahalleleri) |

**İlçe ID dağılımı (doğrulandı):**
```
1  Bodrum      (il=48)    8  Köyceğiz   (il=48)
2  Fethiye     (il=48)    9  Menteşe    (il=48)
3  Marmaris    (il=48)   10  Ortaca     (il=48)
4  Milas       (il=48)   11  Seydikemer (il=48)
5  Dalaman     (il=48)   12  Ula        (il=48)
6  Datça       (il=48)   13  Yatağan    (il=48)
7  Kavaklıdere (il=48)   14  Beşiktaş   (il=34)
                         15  Kadıköy    (il=34)
```

- **13 canonical Muğla ilçesi** canonical ID'lerinde (1-13) ✅
- **Non-canonical ilçeler** (Beşiktaş, Kadıköy) canonical ID'lerin dışına (14-15) taşındı ✅
- **Geçici ID kalmadı** (99900+ yok) ✅
- **Orphan FK düzeltildi:** ilçeler artık il=48 (Muğla) ve il=34 (İstanbul) referans ediyor ✅

---

## 4. Migration `down()` Rollback Sonucu

| Tablo | Önce | Sonra | Değişim |
|-------|-------|-------|---------|
| `iller` | 81 | **3** | -78 (canonical iller silindi) |
| `ilceler` | 15 | **5** | -10 (eklenen ilçeler silindi) |
| `mahalleler` | 20 | **0** | -20 (mahalleler silindi) |

**Rollback sonrası tam orijinal durum geri yüklendi:**
- `iller`: Muğla(1, plaka=48), İstanbul(2, plaka=34), Ankara(3, plaka=6) ✅
- `ilceler`: Bodrum(1,il=48), Marmaris(2,il=48), Milas(3,il=48), Beşiktaş(4,il=34), Kadıköy(5,il=34) ✅
- `mahalleler`: 0 ✅
- Log tabloları (`location_reconciliation_log`, `bodrum_fk_reconcile_log`) temizlendi ✅

**Rollback doğruluğu:** ID taşımaları ters sırada geri alındı, zincirleme çakışmalar geçici ID'lerle çözüldü, eklenen kayıtlar ID taşımalarından ÖNCE silindi (orijinal ID'lerin serbest kalması için).

---

## 5. Golden Thread E2E Testleri

Migration sonrası canonical veri (81 il, 15 ilçe, 20 mahalle) ile **tüm 6 Golden Thread testi PASS** oldu.

| TC | Sonuç | Önceki Durum |
|----|-------|--------------|
| TC-GT-01 Step 1→2 cascade | ✅ PASS | ✅ PASS |
| TC-GT-02 Step 2→3 temel bilgi | ✅ PASS | ✅ PASS |
| TC-GT-03 Step 3 photo SSOT | ✅ PASS | ✅ PASS |
| TC-GT-04 Step 3→4 location nav | ✅ PASS | ✅ PASS |
| **TC-GT-05 Step 4→5 preview** | ✅ **PASS** | ⛔ BLOCKED |
| **TC-GT-06 Full Step 1→5 + submit** | ✅ **PASS** | ⛔ BLOCKED |

**TC-GT-06 sonuçları:**
- Steps 1–5 reached: ✅
- Console errors: 0 ✅
- Kanıt: `audits/golden-thread-evidence/tc-gt-06-results.json`

> ✅ **DB persistence KANITLANDI (TC-GT-06-BE):** Orijinal TC-GT-06 testi PASS olmasına rağmen `ilanlar` tablosunda kayıt oluşturmuyordu (test boşluğu — form eksik dolduruluyor, sunucu 422 döndürüyordu). Bu boşluk, backend-driven test (`golden-thread-db-persistence-backend.spec.ts`) ile kapatıldı: tam ve geçerli payload ile `POST /admin/ilanlar` **200** döndü, ilan DB'de oluşturuldu ve tüm ilişkiler (tenant/kategori/yayın tipi/konum/sahip/danışman) doğrulandı. Kanıt: `tc-gt-06b-backend-db-persistence.json`. Detay: `tc-gt-06-db-persistence-diagnosis.md`.
>
> ⚠️ **Bulunan uygulama bug'ı:** Test sırasında `IlanCrudService::syncFeatures()` içinde gerçek bir defekt tespit edildi — `esyali`, `bina-yasi`, `isitma` schema option değerleri DB-native kolonlara (tinyint/year/varchar) uyumsuz yazılıyor ve 500 hatası üretiyor. Bu, migration'dan bağımsız ayrı bir düzeltme işidir.

---

## 6. Yapılan Düzeltmeler

Migration'ın clone'da test edilmesi sırasında aşağıdaki hatalar tespit edilip düzeltildi:

1. **PHP syntax hatası** — `file_put_contents` bloğunda parantez/ternary hatası
2. **`Duplicate entry '1'`** — Muğla(id=1) canonical Adana(id=1) ile çakışıyordu → PHASE 1 (il ID taşıma) eklendi
3. **`Duplicate entry '3'`** — Ankara plaka `6` canonical `06` ile eşleşmiyordu → plaka kodu `str_pad` ile normalize edildi
4. **İlçe ID çakışmaları** — Fethiye/Dalaman/Marmaris/Milas canonical ID'leri mevcut yanlış kayıtlarla çakışıyordu → ilçe ID yeniden eşleme eklendi
5. **`down()` `Duplicate entry`** — zincirleme ID taşımaları ters sırada geri alınmıyordu → geçici ID'lerle çözüm
6. **Non-canonical ilçeler (Beşiktaş, Kadıköy)** canonical ID'leri işgal ediyordu → canonical ID'lerin dışına (14+) taşındı
7. **`down()` silme sırası** — eklenen kayıtlar ID taşımalarından ÖNCE silinmeliydi (aksi halde geçici ID'lere taşınıp silinemiyordu)

---

## 7. Sonuç ve Öneri

**Migration production'a güvenle uygulanabilir.** Clone üzerinde:
- `up()` tüm canonical veriyi doğru şekilde yükledi (81 il, 15 ilçe, 20 mahalle)
- `down()` orijinal durumu tam olarak geri yükledi
- Golden Thread E2E testleri (TC-GT-05/06) artık PASS

**Not:** TC-GT-06'nın DB persistence doğrulaması, backend-driven test (`golden-thread-db-persistence-backend.spec.ts`) ile **tamamlandı** — `POST /admin/ilanlar` 200 döndü, ilan DB'de oluşturuldu ve tüm ilişkiler doğrulandı. Kanıt: `tc-gt-06b-backend-db-persistence.json`. Ayrıca `IlanCrudService::syncFeatures()` içinde gerçek bir uygulama bug'ı tespit edildi (esyali/bina-yasi/isitma DB-native kolon uyumsuzluğu → 500). Bu, migration'dan bağımsız ayrı bir düzeltme işidir; **migration'ın kendisi doğru çalışıyor.**

**Öneri:** Production'a uygulamadan önce:
1. Migration'ı production DB'nin yedek kopyasında bir kez daha çalıştırın
2. `up()` sonrası kayıt sayılarını doğrulayın
3. Gerekirse `down()` ile rollback yapın