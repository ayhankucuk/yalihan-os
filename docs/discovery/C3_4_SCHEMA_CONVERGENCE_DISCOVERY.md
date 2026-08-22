# C3.4 — Production Schema Convergence Discovery Report

**Target Commit:** `integration/era-v-phase2a-e01 @ 898d4e2`  
**Server:** Hetzner Production (`157.180.116.63`)  
**Database:** `yalihanai_v2_production`  
**Date:** 2026-08-22  
**Role:** SAAB Production Recovery / Antigravity  
**Mode:** READ-ONLY Discovery (No DB / code changes executed)

---

## 1. Executive Summary

During C3 deployment verification on Hetzner Production, `PayoutReadinessService::getPayoutReadyReservations(1)` threw:
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'finansal_durum' in 'where clause'
```

A three-way schema audit revealed that `property_reservations` on the production database (`yalihanai_v2_production`) was provisioned from the migration history starting at `2026_05_03_000000_restore_missing_ci_schema.php` (which created the table with `status` and `reservation_state`), while the Money Core financial fields (`finansal_durum`, `currency`, `depozito_tutari`, `depozito_durumu`, `locked_nightly_rate`, `booking_currency`, `booking_fx_rate`) were only present in `database/schema/mysql-schema.sql` without a standalone incremental migration.

---

## 2. Üçlü Şema Karşılaştırması (Three-Way Schema Matrix)

| Sütun (Column) | `mysql-schema.sql` (Canonical) | Repository Migrations (`database/migrations/`) | Gerçek Production DB (`yalihanai_v2_production`) | C1–C3 Runtime İhtiyacı | Durum |
|---|---|---|---|---|---|
| `id` | `bigint unsigned PK auto_increment` | `restore_missing_ci_schema` | `bigint unsigned PK auto_increment` | Primary Key | ✅ Parity |
| `tenant_id` | `bigint unsigned NULL MUL` | `add_tenant_id_to_core_tables` | `bigint unsigned NULL MUL` | SAB Tenant Isolation | ✅ Parity |
| `property_id` | `bigint unsigned NOT NULL FK(ilanlar)` | `restore_missing_ci_schema` | `bigint unsigned NOT NULL` | Listing FK | ✅ Parity |
| `start_date` / `end_date` | `date NOT NULL` | `restore_missing_ci_schema` | `date NOT NULL` | Rezervasyon tarihleri | ✅ Parity |
| `nights` | `int unsigned NOT NULL` | `restore_missing_ci_schema` | `int NULL` | Gece sayısı | ✅ Parity |
| `guest_name` / `phone` / `email` | `varchar(255)` | `restore_missing_ci_schema` + `run60` | `varchar(255)` | Misafir kimliği | ✅ Parity |
| `reservation_state` | `varchar(255) NOT NULL DEFAULT 'pending'` | `restore_missing_ci_schema` | `varchar(255) NOT NULL DEFAULT 'pending'` | Operasyonel Durum (`ReservationState`) | ✅ Parity |
| `status` | ❌ *Yok (Legacy)* | `restore_missing_ci_schema` | `varchar(255) NOT NULL DEFAULT 'pending'` | Legacy genel durum | ⚠️ Sadece Prod'da var |
| `total_amount` | `decimal(12,2) NULL` | `restore_missing_ci_schema` | `decimal(10,2) NULL` | Brüt Rezervasyon Tutarı | ✅ Parity |
| `confirmed_at` / `cancelled_at` | `datetime NULL` | `run66` + `run70` | `timestamp NULL` | Yaşam döngüsü zamanları | ✅ Parity |
| `checked_in_at` / `out_at` / `completed_at` | `timestamp NULL` | `2026_08_16_000001` | `timestamp NULL` | Operasyonel tamamlama | ✅ Parity |
| `management_model_snapshot` | `varchar(30) NULL` | `2026_08_22_000001` (C3.1) | `varchar(30) NULL` | C3.1 Yönetim Sözleşmesi Modeli | ✅ Parity |
| `commission_rate_snapshot` | `decimal(5,4) NULL` | `2026_08_22_000001` (C3.1) | `decimal(5,4) NULL` | C3.1 Komisyon Oranı Snapshot | ✅ Parity |
| **`finansal_durum`** | `varchar(30) NOT NULL DEFAULT 'draft'` | ❌ **Eksik migration** | ❌ **YOK** | **KRİTİK**: C1/C2/C3 Finance State | ❌ **DRIFT** |
| **`currency`** | `varchar(3) NOT NULL DEFAULT 'TRY'` | ❌ **Eksik migration** | ❌ **YOK** | **YÜKSEK**: Ledger & Payout Currency | ❌ **DRIFT** |
| **`depozito_tutari`** | `decimal(12,2) NULL` | ❌ **Eksik migration** | ❌ **YOK** | **ORTA**: Depozito Muhasebesi | ❌ **DRIFT** |
| **`depozito_durumu`** | `varchar(30) NULL` | ❌ **Eksik migration** | ❌ **YOK** | **ORTA**: Depozito Durumu | ❌ **DRIFT** |
| **`locked_nightly_rate`** | `decimal(12,2) NULL` | ❌ **Eksik migration** | ❌ **YOK** | **DÜŞÜK**: Fiyat Kitleme | ❌ **DRIFT** |
| **`booking_currency`** | `varchar(3) NOT NULL DEFAULT 'TRY'` | ❌ **Eksik migration** | ❌ **YOK** | **DÜŞÜK**: FX Takibi | ❌ **DRIFT** |
| **`booking_fx_rate`** | `decimal(15,6) NULL` | ❌ **Eksik migration** | ❌ **YOK** | **DÜŞÜK**: Kilitli Kur | ❌ **DRIFT** |
| **`booking_country_code`** | `varchar(2) NOT NULL DEFAULT 'TR'` | ❌ **Eksik migration** | ❌ **YOK** | **DÜŞÜK**: Ülke Kodu | ❌ **DRIFT** |
| **`ulke_id`** | `bigint unsigned NULL` | ❌ **Eksik migration** | ❌ **YOK** | **DÜŞÜK**: Ülke İlişkisi | ❌ **DRIFT** |

---

## 3. Semantik Ayrım: `reservation_state` vs. `status` vs. `finansal_durum`

SAAB direktifinde belirtildiği gibi `status` veya `reservation_state`'i `finansal_durum`'a bağlamak / alias etmek **kesinlikle yanlıştır**.

### Kanıt:
Canlı DB'deki tek kayıt (`id: 1`, Pilot Misafir):
- `reservation_state = 'confirmed'` (Misafir rezervasyonu rezerve edilmiş / takvim kapatılmıştır).
- `completed_at = NULL` (Misafir henüz konaklamayı tamamlamamıştır).
- `finansal_durum` bu aşamada **`pending`** olmalıdır.
- Eğer `reservation_state` (`confirmed`) `finansal_durum` olarak kabul edilseydi, henüz tamamlanmamış bir rezervasyon erken financial completion'a uğrar ve hakedişi payout-ready hale gelirdi. Bu, finansal bir güvenlik açığı yaratırdı.

### Domain Ayrımı:
1. **`reservation_state` (`App\Enums\ReservationState`):** Operasyonel durum (`pending`, `confirmed`, `checked_in`, `checked_out`, `completed`, `cancelled`).
2. **`finansal_durum` (`App\ValueObjects\TransactionStatus`):** Çift taraflı muhasebe / tahakkuk durumu (`pending`, `confirmed`, `paid`, `cancelled`, `refunded`, `failed`).

---

## 4. Canlı Veri Durumu (Existing Rows Audit)

`yalihanai_v2_production.property_reservations` tablosunda şu anda **yalnızca 1 adet kayıt** bulunmaktadır:
- **ID:** 1
- **Tenant ID:** 1
- **Misafir:** Ali & Selin Demir (Pilot Misafir)
- **Tarihler:** 2026-08-20 $\rightarrow$ 2026-08-23
- **Kanal:** `airbnb` (`RES-CHNX-PILOT-001`)
- **Durum:** `reservation_state = 'confirmed'`, `completed_at = NULL`
- **Finansal Durum Kararı:** Bu kayıt için `finansal_durum` başlangıç değeri olarak **`pending`** atanması %100 semantik ve muhasebesel olarak doğrudur (işlem tamamlanmamış / check-out yapılmamıştır).

---

## 5. Önerilen Güvenli ve İdempotent Convergence Migration Tasarımı

Dosya Adı: `database/migrations/2026_08_22_000002_converge_property_reservations_financial_schema.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * C3.4: Production Schema Convergence for property_reservations
     * Reconciles Money Core financial fields in production DB.
     */
    public function up(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            if (!Schema::hasColumn('property_reservations', 'finansal_durum')) {
                $table->string('finansal_durum', 30)->default('pending')->after('reservation_state');
                $table->index('finansal_durum', 'idx_reservations_finansal');
            }

            if (!Schema::hasColumn('property_reservations', 'currency')) {
                $table->string('currency', 3)->default('TRY')->after('total_amount');
            }

            if (!Schema::hasColumn('property_reservations', 'depozito_tutari')) {
                $table->decimal('depozito_tutari', 12, 2)->nullable()->after('finansal_durum');
            }

            if (!Schema::hasColumn('property_reservations', 'depozito_durumu')) {
                $table->string('depozito_durumu', 30)->nullable()->after('depozito_tutari');
            }

            if (!Schema::hasColumn('property_reservations', 'locked_nightly_rate')) {
                $table->decimal('locked_nightly_rate', 12, 2)->nullable()->after('depozito_durumu');
            }

            if (!Schema::hasColumn('property_reservations', 'booking_currency')) {
                $table->string('booking_currency', 3)->default('TRY')->after('locked_nightly_rate');
            }

            if (!Schema::hasColumn('property_reservations', 'booking_fx_rate')) {
                $table->decimal('booking_fx_rate', 15, 6)->nullable()->after('booking_currency');
            }

            if (!Schema::hasColumn('property_reservations', 'booking_country_code')) {
                $table->string('booking_country_code', 2)->default('TR')->after('booking_fx_rate');
            }

            if (!Schema::hasColumn('property_reservations', 'ulke_id')) {
                $table->unsignedBigInteger('ulke_id')->nullable()->after('booking_country_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('property_reservations', function (Blueprint $table) {
            $columns = [
                'finansal_durum',
                'currency',
                'depozito_tutari',
                'depozito_durumu',
                'locked_nightly_rate',
                'booking_currency',
                'booking_fx_rate',
                'booking_country_code',
                'ulke_id',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('property_reservations', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
```

---

## 6. Traffic Topology Bulgusu (Host Nginx İncelemesi)

Host üzerindeki `/etc/nginx/sites-available/yalihanemlak.com.tr.conf` incelendiğinde:
- Port 80 ve 443 doğrudan `http://127.0.0.1:8010` (Docker V2 Nginx) adresine `proxy_pass` yapmaktadır.
- Port 8002 üzerinde (Legacy V1) çalışan herhangi bir process bulunmamaktadır.
- Bu durum, önceki seanslarda V2 staging container'ının ana proxy hedefi olarak yapılandırıldığını göstermektedir. Şimdilik Nginx konfigürasyonuna dokunulmamış ve C3.4 Discovery sonrasına bırakılmıştır.
