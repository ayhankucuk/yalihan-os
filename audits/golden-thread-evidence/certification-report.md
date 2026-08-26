# Golden Thread E2E Certification Report

**Date:** 2026-08-26
**Test File:** tests/e2e/golden-thread-wizard.spec.ts
**Status:** 4/6 PASS, 2/6 BLOCKED

---

## Evidence Labels

| Claim | Label | Evidence |
|-------|-------|----------|
| Step 1->2 cascade navigation | BROWSER_VERIFIED | TC-GT-01 PASS |
| Step 2->3 basic info + dynamic fields | BROWSER_VERIFIED | TC-GT-02 PASS |
| Step 3 photo SSOT (Alpine=Native=Preview) | RECOVERY_C_CERTIFIED | TC-GT-03 PASS |
| Step 3->4 location navigation | BROWSER_VERIFIED | TC-GT-04 PASS |
| Step 4->5 Il/Ilce/Mahalle cascade | BLOCKED | TC-05/06 SEED_FIXTURE_MISSING |
| Full Step 1->5 + DB submit | BLOCKED | TC-06 SEED_FIXTURE_MISSING |

---

## Test Results

| TC | Description | Result | Duration |
|----|-------------|--------|----------|
| TC-GT-01 | Step 1->2 Kategori cascade | PASS | 2.5s |
| TC-GT-02 | Step 2->3 Temel bilgiler | PASS | 3.0s |
| TC-GT-03 | Step 3 Fotoğraf SSOT (Alpine=Native=Preview=2) | PASS | 4.6s |
| TC-GT-04 | Step 3->4 Location navigation | PASS | 4.5s |
| TC-GT-05 | Step 4->5 Il/Ilce/Mahalle cascade | BLOCKED | - |
| TC-GT-06 | Full traversal + DB submit | BLOCKED | - |

---

## Blocking Issue

### Database State

```
iller:     3 kayıt  — id=1 Muğla (YANLIŞ: olması gereken id=48)
                         id=2 İstanbul (YANLIŞ: olması gereken id=34)
                         id=3 Ankara (YANLIŞ: olması gereken id=6)
ilceler:    5 kayıt  — Bodrum(id=1, il_id=48) ✓
mahalleler: 0 kayıt  — hiç eklenmemiş
```

### TurkiyeLocationSeeder Durumu

- **Dosya:** database/seeders/TurkiyeLocationSeeder.php
- **Durum:** Mevcut, DatabaseSeeder.php'ye kayıtlı
- **İçerik:** 81 il, 13 Muğla ilçesi, 20 Bodrum mahallesi
- **Çalıştırılmış:** HAYIR — hiç çalıştırılmadı veya yarıda kaldı

### TurkiyeLocationSeeder ID Uyumsuzluğu

| Varlık | Mevcut DB ID | TurkiyeLocationSeeder ID | Durum |
|--------|-------------|--------------------------|-------|
| Muğla | id=1 | id=48 | ID ÇAKIŞMASI |
| Bodrum | id=1, il_id=48 | id=1, il_id=48 | Uyumlu |
| Yalıkavak | YOK | id=1, ilce_id=1 | Eksik |

Seeder calışırsa:
- `updateOrInsert id=1` → "Muğla" kaydı "Adana" ile OVERWRITE edilir
- 3 mevcut kayıt DEĞİŞTİRİLİR
- 78 yeni il eklenir

### Wizard Blade Default

step-4-address.blade.php satır 107:
```
{{ old('il_id', 48) == $il->id ? 'selected' : '' }}
```
Muğla icin default = 48, ama DB'de Muğla = 1. Bu bir **tasarım uyumsuzluğu**.

---

## Production Onay Gerektiren İşlemler

> ⚠️ Aşağıdaki işlemlerin hiçbiri production veritabanında calıştırılmamalıdır.
> Explicit onay alınınca migration veya seeder calıştırılmalıdır.

### 1. TurkiyeLocationSeeder Calistirma

Risk: 3 mevcut il kaydı (Muğla, İstanbul, Ankara) overwrite edilir.

```bash
php artisan db:seed --class=TurkiyeLocationSeeder
```

### 2. Idempotent Location Migration (Tercih Edilen)

Düşük riskli yaklaşım:
- Mevcut ID'leri koru
- Sadece eksik kayıtları plaka_kodu bazlı ekle
- mevcut il_id referanslarını bozmaz

---

## Mimari Notlar

- Wizard singleton: window.ilanWizard() - Alpine reactive state, exposes nextStep()
- Photo SSOT: Alpine photos[] <- handleFiles() event <- native input.files
- Step 3 validation: nextStep() returns false if photos.length < 1
- Step 4 validation: nextStep() returns false if il_id/ilce_id/mahalle_id not selected
- Recovery-C (TC-GT-03) fully verified: Alpine=2, Native=2, Preview=2
- Step 4 API cascade: loadIlceler() fetches /api/ilceler?il_id=X

---

## Sonraki Adımlar

- [ ] Production onay alındığında TurkiyeLocationSeeder çalıştır
- [ ] veya idempotent location migration hazırla
- [ ] TC-GT-05 ve TC-GT-06'yı yeniden çalıştır
- [ ] wizard cascade testlerini doğrula
