# Migration/Schema Commit — Güvenlik ve Rollback İncelemesi

<!-- YALIHAN OS — ENGINEERING PROTOCOL HEADER -->
- **Repository Commit:** `UNKNOWN` (branch: `integration/era-v-phase2a-e01`)
- **Working Tree:** `Dirty`
- **Evidence Date:** 2026-08-29T05:52:00Z (UTC) [TR: 2026-08-29 08:52:00 +03:00]
- **Evidence Level:** `REPO_VERIFIED`
- **Production Authorization:** `NONE (Pre-deployment Security Review)`
<!-- ───────────────────────────────────────────────────────────── -->

**Görev Sahibi:** Codex

---

## 1. Migration Dosyaları Özeti

### 1.1 Untracked Migration Dosyaları

| Dosya | Tür | Tablo | Risk |
|-------|-----|-------|------|
| `2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php` | CREATE | `kategori_yayin_tipi_field_dependencies` | ORTA |
| `2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php` | IDEMPOTENT | `iller`, `ilceler`, `mahalleler` | **YÜKSEK** |
| `2026_08_26_000002_fix_bina_yasi_column_type.php` | ALTER + DATA | `ilanlar.bina_yasi` | **KRITIK** |

---

## 2. bina_yasi Migration Analizi

### 2.1 Kök Neden

`ilanlar.bina_yasi` kolonu `YEAR` type olarak tanımlanmış, ancak codebase bina yaşını (integer) olarak kullanıyor:

```php
// MySQL YEAR: insert 10 (yaş) → store 2010 ❌
// integer: insert 10 → store 10 ✅
```

### 2.2 Migration Detayı

**up():**
1. Kolon tipini `YEAR` → `unsignedSmallInteger` (0-65535) değiştir
2. `>100` olan değerleri `bina_yasi - 2000` ile yaşa dönüştür (2010 → 10)

**down():**
1. Yaş değerlerini geri `YEAR` formatına çevir (10 → 2010)
2. Kolon tipini `YEAR` olarak geri değiştir (raw SQL)

### 2.3 Risk Değerlendirmesi

| Risk | Seviye | Açıklama |
|------|--------|----------|
| Veri kaybı | DÜŞÜK | Değerler dönüştürülüyor |
| Geri dönüşümlülük | ✅ | `down()` mevcut |
| Veritabanı kilidi | ORTA | Kolon tipi değişikliği DDL kilit gerektirebilir |
| Foreign key etkisi | DÜŞÜK | `ilanlar` tablosu, bağımsız |

### 2.4 Rollback Prosedürü

```bash
# 1. Rollback migration
php artisan migrate:rollback --path=database/migrations/2026_08_26_000002_fix_bina_yasi_column_type.php

# 2. Verify
php artisan tinker --execute="echo \DB::table('ilanlar')->whereNotNull('bina_yasi')->first()->bina_yasi ?? 'NULL';"
```

### 2.5 Kritik Bağımlılık

**UYARI:** `bina_yasi` kolonunu kullanan kod (IlanCrudService vb.) bu migration olmadan çalışamaz. Sıralama zorunlu:

1. **Migration commit** → schema değişikliği
2. **Kod commit** → bina_yasi kullanan kod

---

## 3. Location Reconciliation Migration Analizi

### 3.1 Migration Detayı

**Tür:** Idempotent reconciliation (aynı migration birden fazla çalıştırılabilir)

**İşlemler:**
- iller: plaka kodu bazlı ID taşıma + eksik ekleme
- ilceler: isim eşleşmeli ID taşıma + eksik ekleme
- mahalleler: Bodrum mahalleleri ekleme
- Bodrum FK: il_id → 48 (Mugla) güncelleme

### 3.2 Loglama Mekanizması

| Tablo | Amaç |
|-------|------|
| `location_reconciliation_log` | ID taşıma ve ekleme kayıtları |
| `bodrum_fk_reconcile_log` | Bodrum FK değişikliği |

### 3.3 Risk Değerlendirmesi

| Risk | Seviye | Açıklama |
|------|--------|----------|
| ID çakışması | ✅ | Gecici ID (90000+, 99900+) ile çözülüyor |
| Orphan FK | ✅ | Snapshot + reversal ile korunuyor |
| Geri dönüşümlülük | ✅ | Full rollback mevcut |
| Transaction | ✅ | Tüm işlemler transaction içinde |
| Idempotent | ✅ | Aynı migration tekrar çalıştırılabilir |

### 3.4 Rollback Prosedürü

```bash
# 1. Rollback
php artisan migrate:rollback --path=database/migrations/2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php

# 2. Verify (örnek)
# Bodrum FK eski değere dönmeli
# iller ID'leri orijinal değere dönmeli
```

---

## 4. Field Dependencies Migration Analizi

### 4.1 Migration Detayı

**Tür:** CREATE TABLE
**Tablo:** `kategori_yayin_tipi_field_dependencies`

**Kolonlar:** id, kategori_slug, yayin_tipi_id, yayin_tipi, field_slug, field_name, field_type, field_options, vb.

**İndeksler:** unique constraint + lookup indexes

### 4.2 Risk Değerlendirmesi

| Risk | Seviye | Açıklama |
|------|--------|----------|
| Veri kaybı | DÜŞÜK | Yeni tablo, mevcut veri etkilenmez |
| Foreign key | ✅ | Yok |
| Rollback | ✅ | `dropIfExists()` |

---

## 5. Migration Commit Stratejisi

### 5.1 Önerilen Sıralama

```
1. Migrations commit (schema + data)
2. Kod commit (bina_yasi kullanan kod)
```

### 5.2 Commit Adayı

```bash
git add \
  database/migrations/2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php \
  database/migrations/2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php \
  database/migrations/2026_08_26_000002_fix_bina_yasi_column_type.php

git commit -m "migrations: field dependencies + location reconcile + bina_yasi fix"
```

### 5.3 Ayrı Tutulanlar

- `app/Services/Ilan/IlanCrudService.php` ❌ (kod commit'inde)
- `tests/Feature/Crud/IlanCrudFeatureNormalizationTest.php` ❌ (kod commit'inde)

---

## 6. Production Deploy Notları

### 6.1 Kritik Uyarılar

1. **bina_yasi migration önce çalışmalı** — kod migration'a bağımlı
2. **Location reconciliation** — production veri üzerinde ID manipülasyonu
3. **Downtime:** Opsiyonel, DDL kilidi gerekebilir

### 6.2 Pre-deploy Kontroller

```bash
# 1. Migration dry-run
php artisan migrate --pretend

# 2. bina_yasi current values
php artisan tinker --execute="echo \DB::table('ilanlar')->whereNotNull('bina_yasi')->count();"

# 3. Location snapshot
php artisan tinker --execute="echo \DB::table('iller')->count() . ' iller, ' . \DB::table('ilceler')->count() . ' ilce';"
```

### 6.3 Post-deploy Kontroller

```bash
# 1. Migration status
php artisan migrate:status

# 2. bina_yasi verification
php artisan tinker --execute="\$row = \DB::table('ilanlar')->whereNotNull('bina_yasi')->first(); echo \$row ? \$row->bina_yasi : 'NULL';"

# 3. Bodrum FK
php artisan tinker --execute="echo \DB::table('ilceler')->find(1)->il_id;"

# 4. Feature test
php artisan test --filter=IlanCrudFeatureNormalizationTest
```

---

## 7. Rollback Planı

### 7.1 Senaryo A: Bina Yasi Migration Rollback

```bash
php artisan migrate:rollback --path=database/migrations/2026_08_26_000002_fix_bina_yasi_column_type.php
```

**Etki:**
- bina_yasi geri YEAR tipine döner
- Değerler 10 → 2010 olarak geri dönüşürülür
- Kod migration sonrası çalışmaya devam eder (eski YEAR semantic ile)

### 7.2 Senaryo B: Location Reconciliation Rollback

```bash
php artisan migrate:rollback --path=database/migrations/2026_08_26_000001_reconcile_location_canonical_plaka_kodu.php
```

**Etki:**
- Eklenen kayıtlar silinir
- ID'ler orijinal değere döner
- Bodrum FK log'dan geri yüklenir
- Snapshot'tan ilce FK'leri geri yüklenir

### 7.3 Senaryo C: Tüm Migration Rollback

```bash
php artisan migrate:rollback
```

---

## 8. Sonuç

| Kriter | Durum |
|---------|--------|
| bina_yasi migration güvenli mi? | ✅ (veri korunuyor) |
| Location reconciliation güvenli mi? | ✅ (idempotent + rollback) |
| Field dependencies güvenli mi? | ✅ (yeni tablo) |
| Rollback planı mevcut mu? | ✅ |
| Production pre-check gerekli mi? | ✅ |
| Kod ile migration sıralı gönderilmeli mi? | ✅ EVET |

---

## 9. Sprint 14 Durumu

| Görev | Durum |
|-------|-------|
| Property Hub UI commit | ✅ Staged |
| Migration commit | ⏳ Hazır (Codex onayı bekleniyor) |
| bina_yasi kod commit | ⏳ Migration sonrası |
| G-04 Part 2 | ⏳ BEKLİYOR |
| Sprint 14 certification | ⏳ BEKLİYOR |
