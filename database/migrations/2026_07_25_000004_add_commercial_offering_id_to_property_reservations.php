<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Sprint 18 Integration Hardening:
     * Additive migration adding commercial_offering_id FK to property_reservations table.
     */
    public function up(): void
    {
        if (! Schema::hasTable('property_reservations')) {
            return;
        }

        Schema::table('property_reservations', function (Blueprint $table) {
            if (! Schema::hasColumn('property_reservations', 'commercial_offering_id')) {
                $table->unsignedBigInteger('commercial_offering_id')->nullable()->after('property_id');
                $table->foreign('commercial_offering_id')->references('id')->on('commercial_offerings')->nullOnDelete();
                $table->index(['tenant_id', 'commercial_offering_id'], 'prop_res_tenant_offering_idx');
            }
            if (! Schema::hasColumn('property_reservations', 'islem_tutari')) {
                $table->decimal('islem_tutari', 12, 2)->nullable()->after('reservation_state');
            }
            if (! Schema::hasColumn('property_reservations', 'currency')) {
                $table->string('currency', 3)->default('TRY')->after('islem_tutari');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('property_reservations')) {
            return;
        }

        Schema::table('property_reservations', function (Blueprint $table) {
            if (Schema::hasColumn('property_reservations', 'commercial_offering_id')) {
                $table->dropForeign(['commercial_offering_id']);
                $table->dropColumn('commercial_offering_id');
            }
        });
    }
};
