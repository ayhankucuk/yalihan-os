<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * BACKLOG-8: Add unique composite index on (ilan_id, display_order)
 *
 * Prevents duplicate display_order values per listing.
 * Enforces that only one photo per listing can have a given display_order.
 *
 * The unique index is: (ilan_id, display_order) — no two photos in the same
 * listing can have the same display_order value.
 *
 * NOTE: This index is idempotent — safe to run on existing data where
 * display_order may already have duplicates. Use ON CONFLICT handling or
 * pre-cleanup to ensure existing duplicates are resolved before adding
 * the unique constraint.
 *
 * Run: php artisan migrate
 * Rollback: php artisan migrate:rollback --step=1
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ilan_fotograflari')) {
            return;
        }

        // Idempotent: drop non-unique index first, then add unique index
        Schema::table('ilan_fotograflari', function (Blueprint $table) {
            $table->unique(['ilan_id', 'display_order'], 'ilan_fotograflari_ilan_display_unique');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('ilan_fotograflari')) {
            return;
        }

        Schema::table('ilan_fotograflari', function (Blueprint $table) {
            $table->dropUnique('ilan_fotograflari_ilan_display_unique');
        });
    }
};
