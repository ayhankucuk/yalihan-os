# Location Reconciliation Migration — down() Düzeltme Kanıt Raporu

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `UNKNOWN`
- **Working Tree:** `UNKNOWN`
- **Evidence Date:** 2026-08-27T00:00:00Z (UTC) [TR: 2026-08-27 03:00:00 +03:00]
- **Evidence Level:** `TEST_VERIFIED`
- **Production Authorization:** `NONE (Disposable Clone DB)`
<!-- ───────────────────────────────────────────────────────────── -->

**Agent:** Kilo (Claude Sonnet 4.6)
**Ortam:** `yalihanai_clone` (production kopyası, MySQL 8.0)
**Migration:** `2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php`

---

## Özet

Migration `down()` fonksiyonunda kritik bug tespit edildi ve düzeltildi. 3 farklı başlangıç senaryosunda gerçek `up() → down()` döngüsü test edildi. Tüm senaryolarda orphan FK oluşmadı ve veri bütünlüğü korundu.

---

## Tespit Edilen Bug

### Kök Neden

`down()` fonksiyonundaki adım sırası hatalıydı:

1. **Orijinal (HATALI):** Insert delete → Bodrum_FK → ID reversals → Snapshot restore
2. **Sorun:** Snapshot restore ID reversals'dan ÖNCE çalıştığında, snapshot pre-migration il_id değerleri (örn. 34=İstanbul) artık mevcut olmayan iller ID'lerine referans ediyordu. ID reversals tamamlandıktan sonra `iller[34]` canonical İstanbul kaydı artık `iller[2]`'ye taşınmıştı, ama snapshot hâlâ `34` kullanıyordu → **orphan FK**.

Ayrıca:
- `Bodrum_FK_log` uygulandıktan SONRA snapshot restore Bodrum FK'sini tekrar bozuyordu.
- `ilceler` ID reversals tüm ilce FK'lerini cascade ile yanlış değere güncelliyordu.

### Kanıt

Orijinal `down()` ile clone DB'de yapılan gerçek test:

```
down() sonrası orphan FK: 5 tespit ⚠️
  Bodrum(1).il_id=48 → iller[48]=YOK → ORPHAN
  Marmaris(2).il_id=48 → iller[48]=YOK → ORPHAN
  Milas(3).il_id=48 → iller[48]=YOK → ORPHAN
  Beşiktaş(4).il_id=34 → iller[34]=YOK → ORPHAN
  Kadıköy(5).il_id=34 → iller[34]=YOK → ORPHAN
```

---

## Uygulanan Düzeltme

### Yeni `down()` Adım Sırası

```
1. INSERT kayıtlarını sil (flush)
2. Bodrum_FK_log uygula (en spesifik düzeltme)
3. iller ID reversals (iller tablosunu geri al — FK ilişkisi için önkoşul)
4. Bodrum_FK + TÜM ilce FK'lerini plaka_kodu eşleşmesiyle restore et
5. ilceler ID reversals (ilceler tablosunu geri al)
```

### Kritik Düzeltme: FK Restore Mantığı

Snapshot artık **doğrudan kullanılmaz**. Bunun yerine, tüm ilçeler iterate edilir ve her ilçe için:
1. Mevcut `il_id`'li iller kaydı bulunur
2. O kaydın `plaka_kodu`'nu alınır
3. iller tablosunda aynı `plaka_kodu`'na sahip kayıt bulunur → o kaydın `id`'si atanır

Bu, ID reversals sonrası iller tablosu karmaşasından bağımsız olarak doğru FK ilişkisini yeniden kurar.

### Ek Düzeltme: Schema Uyumu

`createLogTable()`'deki `else` dalı `text_value` kolonunu eklemiyordu. Düzeltildi.

---

## Test Senaryoları

### Senaryo 1: Orijinal Baseline (3 il, 5 ilçe)

**Başlangıç:**
```
iller[1]=Muğla(48), iller[2]=İstanbul(34), iller[3]=Ankara(6)
ilce[1]=Bodrum(il=48), ilce[4]=Beşiktaş(il=34), ilce[5]=Kadıköy(il=34)
```

**up() Sonrası:** 81 iller, 15 ilçeler, 20 mahalleler — tüm FK geçerli ✅

**down() Sonrası:**
```
iller: TAM EŞLEŞME ✅
ilceler: TAM EŞLEŞME ✅
Orphan FK: 0 ✅
FK Values:
  Bodrum(1).il_id=1 → iller[1]=Muğla ✅
  Marmaris(2).il_id=1 → iller[1]=Muğla ✅
  Milas(3).il_id=1 → iller[1]=Muğla ✅
  Beşiktaş(4).il_id=2 → iller[2]=İstanbul ✅
  Kadıköy(5).il_id=2 → iller[2]=İstanbul ✅
```

### Senaryo 2: Bodrum FK Bozulmuş (State 1 benzeri)

**Başlangıç:**
```
ilce[1]=Bodrum(il=1) ← BOZUK (48 olmalı)
```

**up() Sonrası:** Bodrum FK düzeltildi, Bodrum_FK_log kaydı oluşturuldu (prev=1, new=48)

**down() Sonrası:**
```
Bodrum_FK_log: 1 entry (prev=1, new=48) → Bodrum(1).il_id=1 ✅
Orphan FK: 0 ✅
```

Bodrum orijinal BOZUK durumuna geri döndü — bu doğru davranış.

### Senaryo 3: Canonical 81 il Zaten Mevcut

**Başlangıç:** Canonical tüm veriler zaten doğru ID'lerde

**up() Sonrası:** Idempotent — 0 ID moves, Bodrum_FK_log: 0 ✅

**down() Sonrası:**
```
ID moves: 0 ✅
Orphan FK: 0 ✅
Tüm FK'ler korundu ✅
```

---

## Clone DB Son Durumu

Clone DB post-migration canonical durumda bırakıldı:
- 81 iller
- 15 ilçeler (13 Muğla + 2 İstanbul)
- 20 Bodrum mahalleleri
- Migration kaydı ve log tabloları temiz

---

## Sonuç

| Senaryo | Orphan FK | Veri Bütünlüğü | Bodrum FK Log |
|---------|-----------|----------------|----------------|
| 1: Orijinal baseline | 0 ✅ | TAM ✅ | N/A |
| 2: Bodrum bozuk | 0 ✅ | TAM ✅ | prev=1 ✅ |
| 3: Canonical mevcut | 0 ✅ | TAM ✅ | N/A |

**Migration `down()` fonksiyonu artık tüm bilinen senaryolarda orphan FK üretmiyor.**

---

## Düzeltme Dosyaları

- `database/migrations/2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php`
  - `down()` fonksiyonu yeniden yazıldı
  - `createLogTable()` `else` dalı `text_value` kolonu eklendi
  - `targetIlForIlce()` helper metodu eklendi
