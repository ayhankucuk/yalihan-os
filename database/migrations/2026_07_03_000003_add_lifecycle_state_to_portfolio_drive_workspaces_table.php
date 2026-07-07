<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 4.5 — Digital Property Intelligence Platform
     *
     * Adds Property Digital Twin lifecycle state machine to workspace model.
     * Enables event-driven state transitions and dashboard progress tracking.
     */
    public function up(): void
    {
        Schema::table('portfolio_drive_workspaces', function (Blueprint $table) {
            // Lifecycle state machine — tracks position in the AI workforce pipeline
            // Values: draft → workspace_created → media_ready → description_ready
            //       → quality_checked → ready_for_publish → published → active → archived
            $table->string('lifecycle_state', 30)
                ->default('workspace_created')
                ->after('workspace_status');

            // Timestamps for state transitions — enables completion tracking
            $table->timestamp('state_changed_at')
                ->nullable()
                ->after('lifecycle_state');

            $table->timestamp('workspace_created_at')
                ->nullable()
                ->after('state_changed_at');

            // AI workforce completion tracking
            $table->unsignedTinyInteger('ai_completion_percent')
                ->default(0)
                ->after('workspace_created_at')
                ->comment('0-100: AI workforce pipeline completion percentage');

            // Tracks which AI agents have completed for this workspace
            $table->json('ai_completion_flags')
                ->nullable()
                ->after('ai_completion_percent')
                ->comment('{"photo_agent": true, "description_agent": false, ...}');

            // Index for efficient state queries
            $table->index('lifecycle_state', 'idx_workspace_lifecycle_state');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('portfolio_drive_workspaces', function (Blueprint $table) {
            $table->dropIndex('idx_workspace_lifecycle_state');
            $table->dropColumn([
                'lifecycle_state',
                'state_changed_at',
                'workspace_created_at',
                'ai_completion_percent',
                'ai_completion_flags',
            ]);
        });
    }
};
