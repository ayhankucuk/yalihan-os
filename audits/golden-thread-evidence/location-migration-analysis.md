# Golden Thread E2E — Location Migration Analiz Raporu

**Tarih:** 2026-08-26
**Konu:** Location seed/data migration risk analizi ve önerilen yaklaşım

---

## 1. Mevcut Veritabanı Durumu

### Location Tabloları

| Tablo | Kayıt | Durum |
|-------|--------|--------|
| `iller` | 3 | Yanlış ID (Muğla=id1, olması gereken id=48) |
| `ilceler` | 5 | Tümü orphan (il_id=48/34 DB'de YOK) |
| `mahalleler` | 0 | Boş |

### Mevcut iller

| id | il_adi | plaka_kodu | TurkiyeLocationSeeder BEKLENEN |
|----|---------|------------|---------------------------|
| 1 | Muğla | 48 | **id=48 olmalı** |
| 2 | İstanbul | 34 | **id=34 olmalı** |
| 3 | Ankara | 6 | **id=6 olmalı** |

### ilceler (5 kayıt — tümü orphan)

| id | il_id | ilce_adi | FK Uyumu |
|----|-------|-----------|---------|
| 1 | 48 | Bodrum | ⚠️ iller.id=48 DB'de YOK |
| 2 | 48 | Marmaris | ⚠️ iller.id=48 DB'de YOK |
| 3 | 48 | Milas | ⚠️ iller.id=48 DB'de YOK |
| 4 | 34 | Beşiktaş | ⚠️ iller.id=34 DB'de YOK |
| 5 | 34 | Kadıköy | ⚠️ iller.id=34 DB'de YOK |

---

## 2. Referans Analizi

### Aktif Veri Referansı Olan Tablolar

| Tablo | il_id referansı | Risk |
|-------|-----------------|------|
| `ilanlar` | **0 kayıt** | Yok |
| `talepler` | **0 kayıt** | Yok |
| `kisiler` (aktif) | **0 kayıt** | Yok |
| `site_apartmanlar` | **0 kayıt** | Yok |
| `sites` | **0 kayıt** | Yok |
| `proj_listings` | **0 kayıt** | Yok |

**Sonuç:** Mevcut 3 yanlış il kaydı hiçbir aktif tablo tarafından kullanılmıyor. Sadece `ilceler` tablosunda 5 orphan referans var — FK constraint mevcut ama InnoDB'de veri zaten orphaned durumda.

---

## 3. TurkiyeLocationSeeder Analizi

### Seeder Yapısı

```
database/seeders/TurkiyeLocationSeeder.php
├── 81 il (id=1..81, plaka_kodu=01..81)
├── 13 Muğla ilçesi (id=1..13, il_id=48)
└── 20 Bodrum mahallesi (id=1..20, ilce_id=1)
```

### updateOrInsert Davranışı

| Kayıt | Mevcut DB | Seeder | Sonuç |
|-------|-----------|--------|--------|
| id=1 (Adana) | Muğla | id=1 (Adana) | **OVERWRITE** |
| id=2 (Adıyaman) | İstanbul | id=2 (Adıyaman) | **OVERWRITE** |
| id=3 (Afyonkarahisar) | Ankara | id=3 (Afyonkarahisar) | **OVERWRITE** |
| id=4..47 | YOK | INSERT | Eklenir |
| id=48 (Muğla) | YOK | INSERT | Eklenir |
| id=49..81 | YOK | INSERT | Eklenir |

**3 mevcut yanlış kayıt OVERWRITE edilir.**
**78 yeni il INSERT edilir.**

### Bodrum/ilceler Uyumu

| Alan | Mevcut DB | TurkiyeLocationSeeder | Uyumluluk |
|------|-----------|----------------------|-----------|
| Bodrum.id | 1 | 1 | ✅ |
| Bodrum.il_id | 48 | 48 | ✅ |
| Bodrum.ilce_adi | Bodrum | Bodrum | ✅ |
| Bodrum mahalleleri | YOK | id=1..20 | ✅ (eklenecek) |

Bodrum verisi **uyumlu** — sadece 20 yeni mahalle eklenecek.

---

## 4. Migration Yaklaşımı

### Önerilen: TurkiyeLocationSeeder'ı Doğal Anahtar Bazlı Güncelleme

**Temel prensip:** Mevcut yanlış kayıtları **idempotent** şekilde doğru ID'lere güncelle.

#### strateji: plaka_kodu bazlı upsert

```php
// YANLIŞ (güncel) — id bazlı
DB::table('iller')->updateOrInsert(['id' => 48], [...]);

// DOĞRU (önerilen) — plaka_kodu bazlı
$existing = DB::table('iller')->where('plaka_kodu', 48)->first();
if ($existing) {
    DB::table('iller')->where('id', $existing->id)->update(['id' => 48]);
} else {
    DB::table('iller')->insert([...]);
}
```

### Adım Adım Plan

1. **Mevcut orphan ilceler tespit:** Bodrum (id=1) → yeni Bodrum (id=1) uyumlu, sadece FK güncellenir
2. **Mevcut yanlış iller'i doğru ID'lere migrate et:**
   - Muğla (id=1, plaka=48) → id=48 (TurkiyeLocationSeeder uyumlu)
   - İstanbul (id=2, plaka=34) → id=34
   - Ankara (id=3, plaka=6) → id=6
3. **Orphan ilceler FK'sini güncelle:**
   - Bodrum/Marmaris/Milas il_id=48 → id=1 (doğru Bodrum.id)
   - Beşiktaş/Kadıköy il_id=34 → id=2 (doğru İstanbul.id)
4. **Eksik 81 il'i ekle:** TurkiyeLocationSeeder id=4..47, 49..81
5. **Eksik Muğla ilçelerini ekle:** TurkiyeLocationSeeder id=2..13
6. **Bodrum mahallelerini ekle:** TurkiyeLocationSeeder id=1..20

### Risk Matrisi

| Adım | Risk | Önlem |
|------|------|--------|
| 1. Orphan ilceler FK güncelleme | Düşük — veri referansı yok | FK constraint deferrable |
| 2. iller ID migration | Orta — 3 kayıt OVERWRITE | Plaka_kodu bazlı idempotent |
| 3. ilceler il_id güncelleme | Düşük — 5 orphan | Constraint kontrolü |
| 4. Yeni iller INSERT | Düşük — sadece ekleme | conflict target |
| 5-6. ilce/mahalle ekleme | Düşük | updateOrInsert |

---

## 5. İleri Görüş Uyarısı

> ⚠️ Aşağıdaki ifadeler KESİNLEŞTİRİLMEMİŞTİR ve operasyonel nottur:
> - "TurkiyeLocationSeeder çalıştırmak gerekecek" — **kesin değil**
> - Seeder, ancak plaka kodu bazlı reconciliation migration'ın veri modeliyle uyumu doğrulandıktan sonra kullanılmalıdır
> - Token maliyeti not olarak kalmıştır, mimari kanıt sayılmaz

## 6. Golden Thread TC-05/06 İçin Öneri

Mevcut DB'de 0 ilan olduğundan, TC-05/06 için **lokal test DB** kullanılabilir:

```php
// Local .env.testing veya test DB override
DB_DATABASE=yalihanai_test

// Test DB'de TurkiyeLocationSeeder çalıştır
php artisan db:seed --class=TurkiyeLocationSeeder
```

Production DB'de **hiçbir veri migration çalıştırılmamalıdır** — explicit onay alınıncaya kadar.

---

## 7. Sonraki Adımlar

- [ ] Local/test ortamında TurkiyeLocationSeeder doğrulaması
- [ ] Idempotent migration script hazırlanması (plaka_kodu bazlı)
- [ ] Orphan ilceler FK repair scripti
- [ ] Production migration için ayrı rollback planı
- [ ] Production onay sonrası migration çalıştırma
- [ ] TC-GT-05/06 Golden Thread tamamlama
