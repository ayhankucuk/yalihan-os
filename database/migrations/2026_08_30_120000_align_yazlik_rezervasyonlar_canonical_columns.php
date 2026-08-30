<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Canonical schema alignment for yazlik_rezervasyonlar table.
     *
     * Old production schema:
     *   - durum          (string)   → rezervasyon_durumu (string)
     *   - giris_tarihi  (date)     → check_in         (date)
     *   - cikis_tarihi  (date)     → check_out        (date)
     *   - toplam_tutar  (decimal)  → toplam_fiyat      (decimal)
     *   - musteri_adi               → already exists
     *   - musteri_telefon           → already exists
     *   - musteri_email             → already exists
     *
     * Strategy: ADD new canonical columns, COPY data, DO NOT DROP old columns.
     * This migration is IDEMPOTENT — safe to re-run.
     *
     * After deploy: old columns can be dropped in a future migration.
     */
    public function up(): void
    {
        // 1. Add rezervasyon_durumu (canonical replacement for durum)
        if (!Schema::hasColumn('yazlik_rezervasyonlar', 'rezervasyon_durumu')) {
            Schema::table('yazlik_rezervasyonlar', function (Blueprint $table) {
                $table->string('rezervasyon_durumu')->default('Beklemede')->after('toplam_fiyat');
            });
            // Copy existing data from durum → rezervasyon_durumu
            DB::statement("
                UPDATE yazlik_rezervasyonlar
                SET rezervasyon_durumu = durum
                WHERE rezervasyon_durumu IS NULL OR rezervasyon_durumu = ''
            ");
        }

        // 2. Add check_in (canonical replacement for giris_tarihi)
        if (!Schema::hasColumn('yazlik_rezervasyonlar', 'check_in')) {
            Schema::table('yazlik_rezervasyonlar', function (Blueprint $table) {
                $table->date('check_in')->nullable()->after('musteri_email');
            });
            // Copy existing data from giris_tarihi → check_in
            DB::statement("
                UPDATE yazlik_rezervasyonlar
                SET check_in = giris_tarihi
                WHERE check_in IS NULL AND giris_tarihi IS NOT NULL
            ");
        }

        // 3. Add check_out (canonical replacement for cikis_tarihi)
        if (!Schema::hasColumn('yazlik_rezervasyonlar', 'check_out')) {
            Schema::table('yazlik_rezervasyonlar', function (Blueprint $table) {
                $table->date('check_out')->nullable()->after('check_in');
            });
            // Copy existing data from cikis_tarihi → check_out
            DB::statement("
                UPDATE yazlik_rezervasyonlar
                SET check_out = cikis_tarihi
                WHERE check_out IS NULL AND cikis_tarihi IS NOT NULL
            ");
        }

        // 4. Add toplam_fiyat (canonical replacement for toplam_tutar)
        if (!Schema::hasColumn('yazlik_rezervasyonlar', 'toplam_fiyat')) {
            Schema::table('yazlik_rezervasyonlar', function (Blueprint $table) {
                $table->decimal('toplam_fiyat', 12, 2)->nullable()->after('ozel_istekler');
            });
            // Copy existing data from toplam_tutar → toplam_fiyat
            DB::statement("
                UPDATE yazlik_rezervasyonlar
                SET toplam_fiyat = toplam_tutar
                WHERE (toplam_fiyat IS NULL OR toplam_fiyat = 0 OR toplam_fiyat = '')
                AND toplam_tutar IS NOT NULL AND toplam_tutar > 0
            ");
        }

        // 5. Add kapora_tutari if missing (added to YazlikRezervasyon model but never migrated)
        if (!Schema::hasColumn('yazlik_rezervasyonlar', 'kapora_tutari')) {
            Schema::table('yazlik_rezervasyonlar', function (Blueprint $table) {
                $table->decimal('kapora_tutari', 12, 2)->nullable()->after('toplam_fiyat');
            });
        }

        // 6. Add onay_tarihi if missing (added to YazlikRezervasyon model but never migrated)
        if (!Schema::hasColumn('yazlik_rezervasyonlar', 'onay_tarihi')) {
            Schema::table('yazlik_rezervasyonlar', function (Blueprint $table) {
                $table->timestamp('onay_tarihi')->nullable()->after('iptal_nedeni');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * NOTE: We do NOT drop the old columns on rollback.
     * The old columns (durum, giris_tarihi, cikis_tarihi, toplam_tutar)
     * are kept so the codebase can still function if rollback occurs.
     * They should be dropped in a separate future migration.
     */
    public function down(): void
    {
        // Intentionally empty — do NOT drop old columns on rollback.
        // This preserves data if rollback is needed before the next deploy.
        //
        // To drop old columns after full migration validation, run:
        // Schema::table('yazlik_rezervasyonlar', function (Blueprint $table) {
        //     $table->dropColumn(['rezervasyon_durumu', 'check_in', 'check_out', 'toplam_fiyat']);
        // });
    }
};
