<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FIX (2026-08-26): bina_yasi column type YEAR → INTEGER
 *
 * The `ilanlar.bina_yasi` column was defined as `YEAR` in the core baseline
 * migration, but the codebase treats it as a BUILDING AGE in years (integer):
 *   - PerformanceScoringService:  `$ilan->bina_yasi < 10`  (new building)
 *   - KiraTahminiService:         `$features['bina_yasi'] <= 5`
 *   - IlanReadModel:              `unsignedSmallInteger('bina_yasi')->comment('Building age in years')`
 *
 * MySQL `YEAR` columns auto-convert small integers to 4-digit years:
 * inserting `10` (age) stores `2010`. This corrupts the "building age"
 * semantic and breaks every consumer that compares against an age threshold.
 *
 * This migration:
 *   1. Rejects YEAR values outside MySQL's reversible two-digit age windows.
 *   2. Backs up original non-null YEAR values for exact rollback.
 *   3. Alters the column to `unsignedSmallInteger` (0–65535).
 *   4. Maps 1970–1999 to ages 70–99 and 2000–2069 to ages 0–69.
 *
 * This is a prerequisite for DB-native feature normalization. Production use
 * still requires a read-only value preflight and an explicit release approval.
 */
return new class extends Migration
{
    private const BACKUP_TABLE = 'bina_yasi_year_migration_backup';

    public function up(): void
    {
        $column = DB::selectOne("SHOW COLUMNS FROM ilanlar LIKE 'bina_yasi'");
        if (!$column || strtolower((string) $column->Type) !== 'year') {
            throw new RuntimeException('Expected ilanlar.bina_yasi to be YEAR before migration.');
        }

        if (Schema::hasTable(self::BACKUP_TABLE)) {
            throw new RuntimeException('Stale bina_yasi migration backup table exists; manual review required.');
        }

        // MySQL YEAR maps two-digit inputs as 00-69 → 2000-2069 and
        // 70-99 → 1970-1999. Values outside those windows cannot be safely
        // interpreted as an age and must stop the migration before DDL.
        $invalidCount = DB::table('ilanlar')
            ->whereNotNull('bina_yasi')
            ->where('bina_yasi', '<>', 0)
            ->whereRaw('bina_yasi NOT BETWEEN 1970 AND 1999')
            ->whereRaw('bina_yasi NOT BETWEEN 2000 AND 2069')
            ->count();

        if ($invalidCount > 0) {
            throw new RuntimeException("Found {$invalidCount} bina_yasi values outside reversible YEAR age ranges.");
        }

        // Keep every original non-null YEAR value for exact rollback. DDL in
        // MySQL implicitly commits, so this table intentionally survives up().
        Schema::create(self::BACKUP_TABLE, function (Blueprint $table) {
            $table->unsignedBigInteger('ilan_id')->primary();
            $table->unsignedSmallInteger('original_year');
        });

        DB::statement(
            'INSERT INTO ' . self::BACKUP_TABLE . ' (ilan_id, original_year) '
            . 'SELECT id, CAST(bina_yasi AS UNSIGNED) FROM ilanlar WHERE bina_yasi IS NOT NULL'
        );

        // Alter first so normalized ages are not converted back by YEAR.
        Schema::table('ilanlar', function (Blueprint $table) {
            $table->unsignedSmallInteger('bina_yasi')
                ->nullable()
                ->comment('Building age in years')
                ->change();
        });

        DB::statement(<<<'SQL'
            UPDATE ilanlar
            SET bina_yasi = CASE
                WHEN bina_yasi = 0 THEN 0
                WHEN bina_yasi BETWEEN 1970 AND 1999 THEN bina_yasi - 1900
                WHEN bina_yasi BETWEEN 2000 AND 2069 THEN bina_yasi - 2000
                ELSE bina_yasi
            END
            WHERE bina_yasi IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        if (!Schema::hasTable(self::BACKUP_TABLE)) {
            throw new RuntimeException('Exact rollback requires the bina_yasi migration backup table.');
        }

        $invalidNewCount = DB::table('ilanlar as i')
            ->leftJoin(self::BACKUP_TABLE . ' as b', 'b.ilan_id', '=', 'i.id')
            ->whereNull('b.ilan_id')
            ->whereNotNull('i.bina_yasi')
            ->where('i.bina_yasi', '>', 99)
            ->count();

        if ($invalidNewCount > 0) {
            throw new RuntimeException("Found {$invalidNewCount} post-migration ages that cannot be represented as YEAR.");
        }

        // Convert rows created after up() using MySQL's reversible two-digit
        // YEAR windows. Existing rows are restored exactly from the backup.
        DB::statement(
            'UPDATE ilanlar i LEFT JOIN ' . self::BACKUP_TABLE . ' b ON b.ilan_id = i.id '
            . 'SET i.bina_yasi = CASE '
            . 'WHEN i.bina_yasi BETWEEN 0 AND 69 THEN i.bina_yasi + 2000 '
            . 'WHEN i.bina_yasi BETWEEN 70 AND 99 THEN i.bina_yasi + 1900 '
            . 'ELSE i.bina_yasi END '
            . 'WHERE b.ilan_id IS NULL AND i.bina_yasi IS NOT NULL'
        );

        DB::statement(
            'UPDATE ilanlar i INNER JOIN ' . self::BACKUP_TABLE . ' b ON b.ilan_id = i.id '
            . 'SET i.bina_yasi = b.original_year'
        );

        DB::statement('ALTER TABLE ilanlar MODIFY bina_yasi YEAR NULL');

        Schema::drop(self::BACKUP_TABLE);
    }
};
