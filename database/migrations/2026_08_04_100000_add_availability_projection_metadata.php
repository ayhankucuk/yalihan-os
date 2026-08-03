<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 22 E01 — SAAB Enhancement Fields
 *
 * Adds to property_availabilities:
 *   - availability_version     : Monotonic version counter per property projection
 *   - origin                   : Event source (reservation|owner|maintenance|ical|booking|airbnb|manual|system)
 *   - projection_generated_at  : Timestamp when this daily record was last projected
 *   - projection_source        : Which engine/source triggered the projection (rebuild|reservation|block|external_sync)
 *   - projection_version       : Foreign key to a future projection_version log (nullable for now)
 *   - conflict_reason          : Detailed conflict code when block was rejected
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('property_availabilities')) {
            Schema::table('property_availabilities', function (Blueprint $table) {
                if (!Schema::hasColumn('property_availabilities', 'availability_version')) {
                    $table->unsignedBigInteger('availability_version')->default(1)->after('idempotency_key')
                          ->comment('Monotonic version per property projection rebuild');
                }
                if (!Schema::hasColumn('property_availabilities', 'origin')) {
                    $table->string('origin', 32)->nullable()->after('availability_version')
                          ->comment('Event source: reservation|owner|maintenance|ical|booking|airbnb|manual|system');
                }
                if (!Schema::hasColumn('property_availabilities', 'projection_generated_at')) {
                    $table->timestamp('projection_generated_at')->nullable()->after('origin')
                          ->comment('When this daily record was last projected/rebuilt');
                }
                if (!Schema::hasColumn('property_availabilities', 'projection_source')) {
                    $table->string('projection_source', 32)->nullable()->after('projection_generated_at')
                          ->comment('Engine that produced this record: rebuild|reservation|block|external_sync');
                }
                if (!Schema::hasColumn('property_availabilities', 'conflict_reason')) {
                    $table->string('conflict_reason', 64)->nullable()->after('projection_source')
                          ->comment('Detailed conflict code when a block was rejected');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('property_availabilities')) {
            Schema::table('property_availabilities', function (Blueprint $table) {
                $columns = [
                    'availability_version',
                    'origin',
                    'projection_generated_at',
                    'projection_source',
                    'conflict_reason',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('property_availabilities', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
