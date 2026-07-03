<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 4.4 — Digital Property Lifecycle: DriveWorkspace
     *
     * Stores Google Drive workspace metadata for each portfolio.
     * Enables tenant-isolated Drive folder management.
     */
    public function up(): void
    {
        Schema::create('portfolio_drive_workspaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ilan_id')->index();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->string('drive_folder_id', 100)->nullable()->unique();
            $table->string('drive_folder_url', 500)->nullable();
            $table->string('workspace_status', 20)->default('creating')->index();
            $table->string('root_folder_name', 255)->nullable();
            $table->string('portfolio_no', 50)->nullable()->index();
            $table->json('subfolders_json')->nullable();
            $table->timestamps();

            // Composite indexes for common queries
            $table->index(['ilan_id', 'tenant_id']);
            $table->index(['workspace_status', 'tenant_id']);
            $table->index(['portfolio_no', 'tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfolio_drive_workspaces');
    }
};
