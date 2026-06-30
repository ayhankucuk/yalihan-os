<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Context7 Compliance Migration Template - Update Version
 *
 * ⚠️ CONTEXT7 PERMANENT STANDARDS:
 * - ALWAYS use 'display_order' field, NEVER use 'o-word'
 * - ALWAYS use boolean 'aktif' field, NEVER use deprecated terms
 * - ALWAYS use DB::statement() for column renames (MySQL compatibility)
 * - ALWAYS preserve column properties (type, nullable, default)
 * - ALWAYS handle indexes before column rename
 *
 * @see .context7/MIGRATION_STANDARDS.md for complete migration standards
 */
return new class extends Migration
{
    /**
     * Fix ilanlar.yayin_tipi_id FK: legacy eski_ilan_kategori_yayin_tipleri → yayin_tipi_sablonlari
     *
     * Root cause: Validation checks exists:yayin_tipi_sablonlari,id (correct)
     * but DB FK points to eski_ilan_kategori_yayin_tipleri (legacy, wrong).
     * This causes FK violations on insert when yayin_tipi_id > 16.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ilanlar') || !Schema::hasTable('yayin_tipi_sablonlari')) {
            return;
        }

        // Drop old FK pointing to legacy table (only if it exists)
        if (DB::getDriverName() !== 'sqlite') {
            $fkExists = DB::select("
                SELECT COUNT(*) as cnt
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_NAME = 'ilanlar_yayin_tipi_id_foreign'
                  AND TABLE_NAME = 'ilanlar'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            if ($fkExists[0]->cnt > 0) {
                Schema::table('ilanlar', function (Blueprint $table) {
                    $table->dropForeign('ilanlar_yayin_tipi_id_foreign');
                });
            }
        }

        Schema::table('ilanlar', function (Blueprint $table) {
            // Create new FK pointing to canonical table
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('yayin_tipi_id')
                      ->references('id')
                      ->on('yayin_tipi_sablonlari')
                      ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse: restore FK to legacy table
     */
    public function down(): void
    {
        if (!Schema::hasTable('ilanlar') || !Schema::hasTable('eski_ilan_kategori_yayin_tipleri')) {
            return;
        }

        if (DB::getDriverName() !== 'sqlite') {
            $fkExists = DB::select("
                SELECT COUNT(*) as cnt
                FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
                WHERE CONSTRAINT_NAME = 'ilanlar_yayin_tipi_id_foreign'
                  AND TABLE_NAME = 'ilanlar'
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");

            if ($fkExists[0]->cnt > 0) {
                Schema::table('ilanlar', function (Blueprint $table) {
                    $table->dropForeign(['yayin_tipi_id']);
                });
            }
        }

        Schema::table('ilanlar', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->foreign('yayin_tipi_id')
                      ->references('id')
                      ->on('eski_ilan_kategori_yayin_tipleri')
                      ->onDelete('set null');
            }
        });
    }
};
