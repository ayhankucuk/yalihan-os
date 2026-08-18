<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * SAAB Decision 4.6 — MUST 1: property_availabilities unique invariant.
     *
     * A property can have only ONE availability record per date.
     * This invariant is enforced by the unique index.
     *
     * The unique key is (property_id, date) — no two availability records
     * for the same property on the same date.
     *
     * Before this migration, duplicates could be created via:
     *   - Concurrent insertOrIgnore + updateOrCreate races
     *   - Double-dispatch of synchronize() calls
     * After this migration, the DB enforces uniqueness atomically.
     *
     * Existing duplicate rows (if any) must be cleaned before adding
     * the constraint in production. Use:
     *   php artisan db:seed --class=CleanupPropertyAvailabilityDuplicates
     */
    public function up(): void
    {
        Schema::table('property_availabilities', function (Blueprint $table) {
            $table->unique(['property_id', 'date'], 'property_availabilities_property_id_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('property_availabilities', function (Blueprint $table) {
            $table->dropUnique('property_availabilities_property_id_date_unique');
        });
    }
};
