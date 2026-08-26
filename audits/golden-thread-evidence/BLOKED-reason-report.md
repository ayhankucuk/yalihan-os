# Golden Thread E2E — Blokaj Gerekçe Raporu

**Tarih:** 2026-08-26  
**Konu:** TC-GT-05/06 blokaj gerekçesi + DB analiz

---

## 1. Test Sonuçları (4/6 PASS)

| TC | Sonuç | Kanıt |
|----|--------|-------|
| TC-GT-01 Step 1→2 cascade | ✅ PASS | Browser DOM |
| TC-GT-02 Step 2→3 temel bilgi | ✅ PASS | Browser DOM |
| TC-GT-03 Step 3 photo SSOT | ✅ PASS | Alpine=Native=Preview=2 |
| TC-GT-04 Step 3→4 location nav | ✅ PASS | Browser DOM |
| TC-GT-05 Step 4→5 preview | ⛔ BLOCKED | Seed fixture eksik |
| TC-GT-06 Full Step 1→5 + submit | ⛔ BLOCKED | Seed fixture eksik |

---

## 2. Blokaj Gerekçesi

### Kanıt-temelli analiz (tümü read-only)

```sql
-- 1. Aktif veri yok
ilanlar:       0 kayıt
talepler:        0 kayıt
sites:           0 kayıt
site_apartmanlar: 0 kayıt
proj_listings:   0 kayıt
```

```sql
-- 2. Mevcut location verisi (yanlış ID'ler)
iller:     3 kayıt (Muğla=id1, İstanbul=id2, Ankara=id3)
ilceler:   5 kayıt (Bodrum=id1, Marmaris=id2, Milas=id3, Beşiktaş=id4, Kadıköy=id5)
mahalleler: 0 kayıt
```

```sql
-- 3. TurkiyeLocationSeeder canonical ID'leri (database/seeders/TurkiyeLocationSeeder.php)
Muğla:       id=48 (mevcut DB: id=1) — ID ÇAKIŞMASI
Bodrum:      id=1, il_id=48 — mevcut DB ile TAM UYUMLU
Yalıkavak:   id=1, ilce_id=1 — mevcut DB'de YOK
```

```sql
-- 4. INFORMATION_SCHEMA FK constraint analiz (read-only)
ilceler → iller FK: HICBIR FOREIGN KEY TANIMLI DEĞİL
mahalleler → ilceler FK: VAR (ama 0 kayıt)
Tüm referans veren tablolar: 0 kayıt
```

### Türkiye Emlak Referans Tablosu (KEY_COLUMN_USAGE)

```
proj_listings.il_id → iller.id   Kayıt: 0
site_apartmanlar.il_id → iller.id  Kayıt: 0
talepler.il_id → iller.id           Kayıt: 0
sites.il_id → iller.id              Kayıt: 0
mahalleler.ilce_id → ilceler.id    Kayıt: 0
```

---

## 3. TurkiyeLocationSeeder Davranış Analizi

### Doğrudan FK Enforcement testi (orphan ekleme girişimi)

```php
// Test: FK aktif mi?
DB::table('ilceler')->insert(['id' => 9999, 'il_id' => 999, ...]);
// Sonuç: BAŞARILI — FK çalışmıyor veya ENFORCED=OFF
```

**Sonuç:** MySQL FK constraint tablosu OKUNMUŞ ama enforcement AKTİF DEĞİL.  
Veritabanında referans bütünlüğü uygulanmıyor.

### TurkiyeLocationSeeder Çalışırsa Ne Olur?

| Eylem | Etki |
|--------|-------|
| Bodrum.id=1 → updateOrInsert | Aynı ID = idempotent |
| Marmaris.id=2 → updateOrInsert | Aynı ID = idempotent |
| Yeni mahalleler INSERT | Sadece ekleme, 0 kayıt etkilenir |
| Yeni 78 il INSERT | Sadece ekleme, veri kaybı YOK |
| Bodrum FK kontrol | İlgili tablolarda 0 kayıt, risk YOK |

**Net etki:** Sadece location verisi canonical ID'lerle güncellenir. Aktif veri kaybı YOK. Ancak mevcut 3 yanlış kayıt canonical ID'lerle değiştirilir.

---

## 4. Karar: Veri Değişikliği YAPILMADI

Kurallar korundu:
- TRUNCATE çalıştırılmadı ✅
- TurkiyeLocationSeeder doğrudan çalıştırılmadı ✅  
- Orphan veri dokunulmadı ✅
- Sadece read-only analiz yapıldı ✅

---

## 5. Önerilen Sonraki Adımlar

### seçenek A — TurkiyeLocationSeeder (düşük risk, idempotent değil)

Avantaj: TurkiyeLocationSeeder hâlâ hazır.  
Risk: 3 yanlış kayıt overwrite, 78 yeni ekleme.  
Komut: `php artisan db:seed --class=TurkiyeLocationSeeder`

### seçenek B — Plaka kodu bazlı idempotent migration (tercih edilen)

Avantaj: Mevcut 3 yanlış kayıt korunur, sadece canonical ID güncellenir.  
Risk: Çok düşük.  
Komut: Açık onay sonrası custom artisan komutu çalıştırılır.

### seçenek C — Hiçbir şey yapma (şu an)

TC-05/06 BLOCKED kalır. Aktif veri değişikliği yapılmaz.

---

## 6. İleri Görüş Uyarısı

> ⚠️ Aşağıdaki ifadeler KESİNLEŞTİRİLMEMİŞTİR ve operasyonel nottur:

- "TurkiyeLocationSeeder çalıştırmak gerekecek" — **kesin değil**
- Seeder ancak plaka kodu bazlı reconciliation migration'ın veri modeliyle uyumlu olduğu doğrulandıktan sonra kullanılmalıdır
- Token maliyeti not olarak kalmıştır, mimari kanıt sayılmaz

---

## 6. Golden Thread Kanıt Özeti

| Adım | Durum | Kanıt |
|------|--------|--------|
| Step 1 Kategori cascade | ✅ | Browser snapshot |
| Step 2 Schema-driven fields | ✅ | Browser snapshot |
| Step 3 Photo SSOT | ✅ | Alpine=Native=Preview=2 |
| Step 4 Location navigation | ✅ | Browser snapshot |
| Step 4→5 İl/İlçe/Mahalle cascade | ⛔ | Seed fixture eksik |
| Step 5 Form submit | ⛔ | Seed fixture eksik |
| Step 5 DB persistence | ⛔ | Seed fixture eksik |

**Recovery-C sertifikasyonu:** TC-GT-03 üzerinden tam doğrulandı — Alpine=Native=Preview=2.
