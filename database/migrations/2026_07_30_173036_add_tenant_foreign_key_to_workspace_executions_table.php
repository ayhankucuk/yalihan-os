<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phase 2A E01: Add tenant_id Foreign Key to workspace_executions
     *
     * Tenant isolation enforcement for execution audit trail.
     * Uses restrictOnDelete: execution history must not be silently
     * orphaned when a tenant is deleted — SAAB Tenant Isolation Rule 1.
     */
    public function up(): void
    {
        Schema::table('workspace_executions', function (Blueprint $table) {
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('workspace_executions', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
        });
    }
};
