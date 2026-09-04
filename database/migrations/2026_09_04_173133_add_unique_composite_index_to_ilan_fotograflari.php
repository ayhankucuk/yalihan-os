<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * BACKLOG-8: Add unique composite index on (ilan_id, display_order)
 *
 * Cross-DB compatible: uses Schema::hasIndex() for index existence check.
 * Preflight: verifies no existing duplicate (ilan_id, display_order) pairs.
 *
 * Run: php artisan migrate
 * Rollback: php artisan migrate:rollback --step=1
 * Production note: Run against MySQL production DB only after backup.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ilan_fotograflari')) {
            return;
        }

        $indexName = 'ilan_fotografi_unique_ilan_display_order';

        // Idempotent: skip if index already exists (cross-DB: MySQL + SQLite)
        if (Schema::hasIndex('ilan_fotograflari', $indexName, 'unique')) {
            return;
        }

        // Preflight: verify no duplicate (ilan_id, display_order) pairs exist
        $dupes = DB::table('ilan_fotograflari')
            ->whereNull('deleted_at')
            ->groupBy('ilan_id', 'display_order')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($dupes) {
            throw new \RuntimeException(
                "BACKLOG-8 migration aborted: found duplicate (ilan_id, display_order) pairs. "
                . "Clean up duplicates before applying this migration."
            );
        }

        Schema::table('ilan_fotograflari', function (Blueprint $table) use ($indexName) {
            $table->unique(['ilan_id', 'display_order'], $indexName);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ilan_fotograflari')) {
            return;
        }

        $indexName = 'ilan_fotografi_unique_ilan_display_order';

        if (! Schema::hasIndex('ilan_fotograflari', $indexName, 'unique')) {
            return;
        }

        Schema::table('ilan_fotograflari', function (Blueprint $table) use ($indexName) {
            $table->dropUnique($indexName);
        });
    }
};
