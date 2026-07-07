<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 4.8: Workspace Integration Platform
     *
     * Adds:
     *   - drive_webhook_channel_json  — Drive push channel metadata (id, resourceId, expiration, etc.)
     *   - metadata_json                — Generic metadata store (tracked Drive files, KPI sheets, etc.)
     *
     * These columns are additive (nullable) — safe to run on existing deployments.
     */
    public function up(): void
    {
        Schema::table('portfolio_drive_workspaces', function (Blueprint $table) {
            $table->json('drive_webhook_channel_json')->nullable()
                ->comment('Google Drive push notification channel metadata');
            $table->json('metadata_json')->nullable()
                ->comment('Generic metadata: tracked Drive files, KPI sheets, sync state');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_drive_workspaces', function (Blueprint $table) {
            $table->dropColumn(['drive_webhook_channel_json', 'metadata_json']);
        });
    }
};
