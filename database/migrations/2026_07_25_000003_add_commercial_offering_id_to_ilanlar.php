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
     * Additive migration adding commercial_offering_id FK to ilanlar table.
     */
    public function up(): void
    {
        if (! Schema::hasTable('ilanlar')) {
            return;
        }

        Schema::table('ilanlar', function (Blueprint $table) {
            if (! Schema::hasColumn('ilanlar', 'commercial_offering_id')) {
                $table->unsignedBigInteger('commercial_offering_id')->nullable()->after('property_id');
                $table->foreign('commercial_offering_id')->references('id')->on('commercial_offerings')->nullOnDelete();
                $table->index(['tenant_id', 'commercial_offering_id'], 'ilanlar_tenant_offering_idx');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('ilanlar')) {
            return;
        }

        Schema::table('ilanlar', function (Blueprint $table) {
            if (Schema::hasColumn('ilanlar', 'commercial_offering_id')) {
                $table->dropForeign(['commercial_offering_id']);
                $table->dropColumn('commercial_offering_id');
            }
        });
    }
};
