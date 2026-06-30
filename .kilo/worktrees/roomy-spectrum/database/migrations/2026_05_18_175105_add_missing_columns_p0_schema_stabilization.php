<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0 Schema Stabilization Migration
 *
 * Purpose: Fixes critical schema drift identified by EnvDriftGuard v3.2
 * Affects: 49 failing tests
 * SAB Compliance: Idempotent, defensive, Context7 naming
 *
 * Changes:
 * 1. kisiler.kaynak (string, nullable) - Missing column
 * 2. ilanlar.kisi_id (foreignId, nullable) - Missing FK relationship
 * 3. user_devices.device_token (nullable) - Fix NOT NULL constraint
 * 4. property_reservations.property_id (NOT NULL) - Ensure FK exists
 *
 * @see EnvDriftGuard Report: 2026-05-18
 * @see Test Results: logs/test_results_20260518_175545.txt
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add 'kaynak' column to 'kisiler' table (Context7: source field)
        if (Schema::hasTable('kisiler') && !Schema::hasColumn('kisiler', 'kaynak')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->string('kaynak', 50)->nullable()->after('telefon')
                    ->comment('Context7: Lead source (website, telefon, referans, etc.)');
            });
        }

        // 2. Add 'kisi_id' FK to 'ilanlar' table (Context7: property owner relationship)
        if (Schema::hasTable('ilanlar') && !Schema::hasColumn('ilanlar', 'kisi_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->foreignId('kisi_id')->nullable()->after('danisman_id')
                    ->comment('Context7: Property owner (Kişi) relationship')
                    ->constrained('kisiler')->nullOnDelete();
            });
        }

        // 3. Fix 'device_token' NOT NULL constraint in 'user_devices'
        if (Schema::hasTable('user_devices') && Schema::hasColumn('user_devices', 'device_token')) {
            // Check if column is NOT NULL and needs to be changed
            Schema::table('user_devices', function (Blueprint $table) {
                $table->string('device_token', 255)->nullable()->change();
            });
        }

        // 4. Ensure 'property_id' exists in 'property_reservations' (defensive check)
        if (Schema::hasTable('property_reservations') && !Schema::hasColumn('property_reservations', 'property_id')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->foreignId('property_id')->after('id')
                    ->comment('Context7: Property (Ilan) relationship')
                    ->constrained('ilanlar')->cascadeOnDelete();
            });
        }

        // 5. Add index for performance (optional but recommended)
        if (Schema::hasTable('kisiler') && Schema::hasColumn('kisiler', 'kaynak')) {
            Schema::table('kisiler', function (Blueprint $table) {
                if (!$this->indexExists('kisiler', 'kisiler_kaynak_index')) {
                    $table->index('kaynak', 'kisiler_kaynak_index');
                }
            });
        }

        if (Schema::hasTable('ilanlar') && Schema::hasColumn('ilanlar', 'kisi_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                if (!$this->indexExists('ilanlar', 'ilanlar_kisi_id_index')) {
                    $table->index('kisi_id', 'ilanlar_kisi_id_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove added columns in reverse order
        if (Schema::hasTable('ilanlar') && Schema::hasColumn('ilanlar', 'kisi_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropForeign(['kisi_id']);
                $table->dropIndex('ilanlar_kisi_id_index');
                $table->dropColumn('kisi_id');
            });
        }

        if (Schema::hasTable('kisiler') && Schema::hasColumn('kisiler', 'kaynak')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->dropIndex('kisiler_kaynak_index');
                $table->dropColumn('kaynak');
            });
        }

        // Note: We don't reverse device_token nullable change as it's a fix, not a feature
        // Note: We don't remove property_id as it should exist from previous migrations
    }

    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $index): bool
    {
        $connection = Schema::getConnection();
        $schemaManager = $connection->getDoctrineSchemaManager();
        $indexes = $schemaManager->listTableIndexes($table);

        return isset($indexes[$index]);
    }
};
