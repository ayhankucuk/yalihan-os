<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds geometry_type and geometry columns to ilanlar.
 *
 * These columns exist in production MySQL but were never captured in a migration.
 * This migration makes the SQLite test DB consistent with production schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            if (!Schema::hasColumn('ilanlar', 'geometry_type')) {
                $table->string('geometry_type', 20)->nullable()->after('lng');
            }
            if (!Schema::hasColumn('ilanlar', 'geometry')) {
                $table->json('geometry')->nullable()->after('geometry_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('ilanlar', 'geometry'))      $cols[] = 'geometry';
            if (Schema::hasColumn('ilanlar', 'geometry_type')) $cols[] = 'geometry_type';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
