# Owner Report — Veritabanı Şeması

**Domain:** D16 Owner Portal
**Sprint:** Task #19 — Raporlar
**SAB:** v6.1.1 | Tarih: 2026-05-16

---

## Genel Bakış

Owner Portal'ın rapor altyapısı 3 tablodan oluşur. Hepsi read-model/projeksiyon yapısındadır — doğrudan domain işlem tabloları değildir.

```
owner_report_rows      ← Aktivite kayıtları (insert-only)
owner_report_metrics   ← Periyodik özetler (aggregate)
owner_report_exports   ← Export talep takibi (job queue)
```

---

## 1. `owner_report_rows` — Aktivite Geçmişi

**Model:** `App\Models\OwnerReportRow`
**Migration:** `2026_05_16_100001_create_owner_report_rows_table`
**Context7:** `C7-OWNER-REPORT-READ-MODEL-V1`

### Kolonlar

| Kolon | Tip | Açıklama |
|-------|-----|----------|
| `id` | bigint PK | — |
| `tenant_id` | bigint | Tenant izolasyonu (FK: tenants) |
| `owner_id` | bigint FK | Mülk sahibi (FK: users) |
| `ilan_id` | bigint FK nullable | İlgili ilan (FK: ilanlar, null on delete) |
| `islem_tipi` | varchar(50) | `kira_odemesi \| danisman_ziyareti \| teklif_alindi \| belge_yuklendi \| genel` |
| `kayit_tarihi` | date | Aktivite tarihi |
| `tutar` | decimal(15,2) nullable | İşlem tutarı |
| `para_birimi` | char(3) | Varsayılan: `TRY` |
| `aciklama` | text nullable | İnsan okunabilir açıklama |
| `durum_kodu` | varchar(30) | `basarili \| beklemede \| iptal \| islemde` |
| `metadata` | json nullable | Aktiviteye özel ek veri |
| `created_at / updated_at` | timestamp | — |

### İndeksler

| İsim | Kolonlar | Amaç |
|------|----------|------|
| `idx_report_rows_tenant_owner` | (tenant_id, owner_id) | Tenant-izole listeleme |
| `idx_report_rows_owner_date` | (owner_id, kayit_tarihi) | Tarih aralığı filtresi |
| `idx_report_rows_ilan_date` | (ilan_id, kayit_tarihi) | İlan bazlı filtre |

### Kullanım Kuralları

- **Insert-only:** Satırlar güncellenmez, silinmez — audit trail.
- Doğrudan `OwnerReportRow::create()` değil; gelecekte `OwnerReportRowWriter` service üzerinden yazılacak.
- `OwnerReportController::index()` bu tabloyu `owner_id` + tarih filtresiyle sorgular.

---

## 2. `owner_report_metrics` — Periyodik Özetler

**Model:** `App\Models\OwnerReportMetric`
**Migration:** `2026_05_16_100002_create_owner_report_metrics_table`
**Context7:** `C7-OWNER-METRIC-READ-MODEL-V1`

### Kolonlar

| Kolon | Tip | Açıklama |
|-------|-----|----------|
| `id` | bigint PK | — |
| `tenant_id` | bigint | Tenant izolasyonu |
| `owner_id` | bigint FK | Mülk sahibi |
| `ilan_id` | bigint FK nullable | Null ise tüm ilanların toplamı |
| `periyot_tipi` | varchar(20) | `gunluk \| haftalik \| aylik \| yillik` |
| `periyot_degeri` | varchar(20) | Örn: `2026-05` (aylık), `2026-W20` (haftalık) |
| `toplam_gelir` | decimal(15,2) | — |
| `toplam_gider` | decimal(15,2) | — |
| `net_kar` | decimal(15,2) | — |
| `para_birimi` | char(3) | Varsayılan: `TRY` |
| `doluluk_orani` | decimal(5,2) | 0.00–100.00 |
| `rezervasyon_sayisi` | int unsigned | — |
| `teklif_sayisi` | int unsigned | — |
| `goruntulenme_sayisi` | int unsigned | — |
| `metric_name` | varchar nullable | UI'da gösterilecek başlık |
| `metric_value` | varchar nullable | UI'da gösterilecek formatlanmış değer |
| `created_at / updated_at` | timestamp | — |

### İndeksler

| İsim | Kolonlar | Amaç |
|------|----------|------|
| `idx_metrics_tenant_owner` | (tenant_id, owner_id) | — |
| `idx_metrics_owner_period` | (owner_id, periyot_tipi, periyot_degeri) | Periyot sorgusu |
| `uq_metrics_owner_ilan_period` | UNIQUE (owner_id, ilan_id, periyot_tipi, periyot_degeri) | Çift kayıt engeli |

### Doldurulma Stratejisi

Şu an boş — nightly job ile doldurulacak. `OwnerReportController::index()` bu tabloyu özet kartlar için kullanır. Boşsa "Özet metrik bulunamadı" gösterir.

---

## 3. `owner_report_exports` — Export Takibi

**Model:** `App\Models\OwnerReportExport`
**Migration:** `2026_05_16_100003_create_owner_report_exports_table`
**Context7:** `C7-OWNER-EXPORT-TRACKER-V1`

### Kolonlar

| Kolon | Tip | Açıklama |
|-------|-----|----------|
| `id` | bigint PK | — |
| `tenant_id` | bigint | Tenant izolasyonu |
| `owner_id` | bigint FK | Mülk sahibi |
| `dosya_adi` | varchar | Örn: `report_AbCdEfGhIj.pdf` |
| `dosya_yolu` | varchar | `exports/owner/{user_id}/{dosya_adi}` |
| `format` | varchar(10) | `csv \| pdf` |
| `islem_durumu` | varchar(20) | `bekliyor → isleniyor → tamamlandi \| hata` |
| `tamamlanma_tarihi` | timestamp nullable | Job bitişinde set edilir |
| `hata_mesaji` | text nullable | Job başarısızsa detay |
| `filtreler` | json nullable | `{ilan_id, baslangic_tarihi, bitis_tarihi, format}` |
| `created_at / updated_at` | timestamp | — |

### İş Akışı

```
POST /owner/reports/export
  → ExportOwnerReportAction::handle()
    → OwnerReportExport::create() (islem_durumu: bekliyor)
    → OwnerReportExportJob::dispatch($export)
      → Job: islem_durumu: isleniyor → CSV/PDF üret → tamamlandi

GET /owner/reports/{export}/download
  → OwnerReportPolicy::download() (owner_id kontrolü)
  → Storage::download()
```

### İndeksler

| İsim | Kolonlar |
|------|----------|
| `idx_exports_tenant_owner` | (tenant_id, owner_id) |
| `idx_exports_owner_durum` | (owner_id, islem_durumu) |

---

## Migration Çalıştırma

```bash
# MySQL servisi açıkken
php artisan migrate

# Sadece bu 3 migration'ı çalıştırmak için
php artisan migrate --path=database/migrations/2026_05_16_100001_create_owner_report_rows_table.php
php artisan migrate --path=database/migrations/2026_05_16_100002_create_owner_report_metrics_table.php
php artisan migrate --path=database/migrations/2026_05_16_100003_create_owner_report_exports_table.php
```

---

## İlişki Diyagramı

```
users (owner_id)
  ├── owner_report_rows      (owner_id FK, ilan_id FK nullable)
  ├── owner_report_metrics   (owner_id FK, ilan_id FK nullable)
  └── owner_report_exports   (owner_id FK)

ilanlar (ilan_id)
  ├── owner_report_rows      (ilan_id nullable FK)
  └── owner_report_metrics   (ilan_id nullable FK)
```

---

*Yazar: Claude (Cowork) | 2026-05-16 | SAB v6.1.1*
