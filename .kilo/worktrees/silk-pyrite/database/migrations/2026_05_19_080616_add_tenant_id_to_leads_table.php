<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * P1.3 - Webhook Tenant İzolasyonu
     *
     * CRITICAL: Lead tablosuna tenant_id eklenerek webhook'lardan gelen
     * lead'lerin tenant context'i ile ilişkilendirilmesi sağlanır.
     *
     * Bu migration olmadan webhook'lar cross-tenant data leakage riski taşır.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Add tenant_id column after id
            $table->unsignedBigInteger('tenant_id')->after('id')->nullable();

            // Add foreign key constraint
            $table->foreign('tenant_id')
                  ->references('id')
                  ->on('tenants')
                  ->onDelete('cascade');

            // Add index for tenant-scoped queries
            $table->index('tenant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
