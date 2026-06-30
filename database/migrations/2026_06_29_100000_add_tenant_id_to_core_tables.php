<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add tenant_id to ilanlar
        if (Schema::hasTable('ilanlar') && !Schema::hasColumn('ilanlar', 'tenant_id')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            });
        }

        // 2. Add tenant_id to talepler
        if (Schema::hasTable('talepler') && !Schema::hasColumn('talepler', 'tenant_id')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            });
        }

        // 3. Add tenant_id to kisiler
        if (Schema::hasTable('kisiler') && !Schema::hasColumn('kisiler', 'tenant_id')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            });
        }

        // 4. Add tenant_id to property_reservations
        if (Schema::hasTable('property_reservations') && !Schema::hasColumn('property_reservations', 'tenant_id')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            });
        }

        // 5. Add tenant_id to ilan_fotograflari
        if (Schema::hasTable('ilan_fotograflari') && !Schema::hasColumn('ilan_fotograflari', 'tenant_id')) {
            Schema::table('ilan_fotograflari', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            });
        }

        // 🛡️ GOVERNANCE: Backfill existing records to prevent data disappearance / visibility drift
        try {
            DB::statement("UPDATE ilanlar SET tenant_id = (SELECT tenant_id FROM users WHERE users.id = ilanlar.danisman_id) WHERE tenant_id IS NULL");
            DB::statement("UPDATE talepler SET tenant_id = (SELECT tenant_id FROM users WHERE users.id = talepler.danisman_id) WHERE tenant_id IS NULL");
            DB::statement("UPDATE kisiler SET tenant_id = (SELECT tenant_id FROM users WHERE users.id = kisiler.danisman_id) WHERE tenant_id IS NULL");
            DB::statement("UPDATE property_reservations SET tenant_id = (SELECT tenant_id FROM ilanlar WHERE ilanlar.id = property_reservations.property_id) WHERE tenant_id IS NULL");
            DB::statement("UPDATE ilan_fotograflari SET tenant_id = (SELECT tenant_id FROM ilanlar WHERE ilanlar.id = ilan_fotograflari.ilan_id) WHERE tenant_id IS NULL");
        } catch (\Throwable $e) {
            // Log and allow migration to proceed if tables are empty or users don't exist yet in clean test environment
            logger()->warning('SAB Tenant backfill failed or skipped: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ilanlar')) {
            Schema::table('ilanlar', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('talepler')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('kisiler')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('property_reservations')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('ilan_fotograflari')) {
            Schema::table('ilan_fotograflari', function (Blueprint $table) {
                $table->dropColumn('tenant_id');
            });
        }
    }
};
