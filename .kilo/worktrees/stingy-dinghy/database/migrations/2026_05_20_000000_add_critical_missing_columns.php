<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P0.1 Schema Stabilization Migration
 *
 * SAB Compliance: Idempotent + Defensive
 * - Schema::hasColumn() guards prevent "already exists" errors
 * - onDelete('set null') for soft dependencies
 * - onDelete('cascade') for hard dependencies
 *
 * Target: Fix 49 failing tests due to schema drift
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. kisiler.kaynak - CRM Akışı için kritik (Context7: kaynak)
        if (Schema::hasTable('kisiler') && !Schema::hasColumn('kisiler', 'kaynak')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->string('kaynak', 50)->nullable()->after('telefon')
                      ->comment('CRM lead source: web, referral, agent, etc.');
            });
        }

        // 2. ilanlar.kisi_id - İlişkisel bütünlük (Soft dependency)
        if (Schema::hasTable('ilanlar') && !Schema::hasColumn('ilanlar', 'kisi_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->foreignId('kisi_id')->nullable()->after('danisman_id')
                      ->constrained('kisiler')->onDelete('set null')
                      ->comment('İlan sahibi (Kişi) - nullable for orphaned listings');
            });
        }

        // 3. user_devices.device_token - Nullable düzeltmesi (Mobile Fix)
        if (Schema::hasTable('user_devices') && Schema::hasColumn('user_devices', 'device_token')) {
            Schema::table('user_devices', function (Blueprint $table) {
                $table->string('device_token', 255)->nullable()->change();
            });
        }

        // 4. property_reservations.property_id - Zorunlu ilişki (Hard dependency)
        if (Schema::hasTable('property_reservations') && !Schema::hasColumn('property_reservations', 'property_id')) {
            // Check if 'properties' table exists before creating foreign key
            if (Schema::hasTable('properties')) {
                Schema::table('property_reservations', function (Blueprint $table) {
                    $table->foreignId('property_id')->after('id')
                          ->constrained('properties')->onDelete('cascade')
                          ->comment('Property reference - cascade delete on property removal');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Geri alma işlemleri (Defensive rollback)

        if (Schema::hasTable('kisiler') && Schema::hasColumn('kisiler', 'kaynak')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->dropColumn('kaynak');
            });
        }

        if (Schema::hasTable('ilanlar') && Schema::hasColumn('ilanlar', 'kisi_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropForeign(['kisi_id']);
                $table->dropColumn('kisi_id');
            });
        }

        if (Schema::hasTable('property_reservations') && Schema::hasColumn('property_reservations', 'property_id')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->dropForeign(['property_id']);
                $table->dropColumn('property_id');
            });
        }

        // user_devices.device_token rollback skipped (reverting nullable to NOT NULL is risky)
    }
};
