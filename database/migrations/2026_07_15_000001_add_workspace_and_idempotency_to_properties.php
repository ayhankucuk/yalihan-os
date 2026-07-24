<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 11 Task 001: Property Aggregate Root
     *
     * Adds:
     * - workspace_id: Every Property belongs to a Workspace (Invariant 1)
     * - idempotency_key: Prevent duplicate Property creation (Invariant 6)
     *
     * Idempotent: safe to run on both fresh and existing databases.
     * Guards against running before the base properties table exists.
     */
    public function up(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'workspace_id')) {
                $table->unsignedBigInteger('workspace_id')
                    ->nullable()
                    ->after('tenant_id');
            }

            if (! Schema::hasColumn('properties', 'idempotency_key')) {
                $table->string('idempotency_key', 64)
                    ->nullable()
                    ->unique()
                    ->after('uuid');
            }

            if (! Schema::hasIndex('properties', ['tenant_id', 'workspace_id'])) {
                $table->index(['tenant_id', 'workspace_id'], 'properties_tenant_workspace_idx');
            }

            if (! Schema::hasIndex('properties', ['tenant_id', 'idempotency_key'])) {
                $table->index(['tenant_id', 'idempotency_key'], 'properties_idempotency_idx');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('properties')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasIndex('properties', ['tenant_id', 'workspace_id'])) {
                $table->dropIndex(['tenant_id', 'workspace_id']);
            }
            if (Schema::hasIndex('properties', ['tenant_id', 'idempotency_key'])) {
                $table->dropIndex(['tenant_id', 'idempotency_key']);
            }
            if (Schema::hasColumn('properties', 'idempotency_key')) {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            }
            if (Schema::hasColumn('properties', 'workspace_id')) {
                $table->dropColumn('workspace_id');
            }
        });
    }
};
