<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds ilgili_kisi_id to ilanlar.
 *
 * The column exists in production MySQL but was never captured in a migration.
 * This migration makes the test SQLite DB consistent with production schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ilanlar', 'ilgili_kisi_id')) {
            return; // idempotent — already present in production
        }

        Schema::table('ilanlar', function (Blueprint $table) {
            $table->unsignedBigInteger('ilgili_kisi_id')->nullable()->after('ilan_sahibi_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('ilanlar', 'ilgili_kisi_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropColumn('ilgili_kisi_id');
            });
        }
    }
};
