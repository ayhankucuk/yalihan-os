<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix schema drift: yayin_tipi_sablonlari.yayin_tipi_id column was missing.
 *
 * Root cause: YayinTipiSablonu model has yayin_tipi_id in $fillable and
 * multiple tests/seeders use it, but no migration ever created the column.
 *
 * Fix: Add nullable FK to yayin_tipleri.id. Existing rows are preserved
 * with NULL (no backfill — no canonical slug→id mapping proven).
 *
 * 🛡️ Context7 compliance:
 * - Idempotent (Schema::hasColumn guard)
 * - No historical migration rewrite
 * - Nullable — does not break existing rows
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('yayin_tipi_sablonlari', 'yayin_tipi_id')) {
            return;
        }

        Schema::table('yayin_tipi_sablonlari', function (Blueprint $table): void {
            $table->foreignId('yayin_tipi_id')
                ->nullable()
                ->after('id')
                ->constrained('yayin_tipleri')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('yayin_tipi_sablonlari', 'yayin_tipi_id')) {
            return;
        }

        Schema::table('yayin_tipi_sablonlari', function (Blueprint $table): void {
            // SQLite does not support dropForeign — guard required
            if (config('database.default') !== 'sqlite') {
                $table->dropForeign(['yayin_tipi_id']);
            }
            $table->dropColumn('yayin_tipi_id');
        });
    }
};
