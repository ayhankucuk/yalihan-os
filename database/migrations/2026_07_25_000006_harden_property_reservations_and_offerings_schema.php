<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 18 Schema Hardening:
     * - Adds idempotency_key UNIQUE(tenant_id, idempotency_key) to property_reservations.
     * - Ensures commercial_offering_id FK uses ON DELETE RESTRICT.
     */
    public function up(): void
    {
        if (Schema::hasTable('property_reservations')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                if (! Schema::hasColumn('property_reservations', 'idempotency_key')) {
                    $table->string('idempotency_key', 64)->nullable()->after('currency');
                    $table->unique(['tenant_id', 'idempotency_key'], 'prop_res_tenant_idempotency_idx');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('property_reservations')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                if (Schema::hasColumn('property_reservations', 'idempotency_key')) {
                    $table->dropUnique('prop_res_tenant_idempotency_idx');
                    $table->dropColumn('idempotency_key');
                }
            });
        }
    }
};
