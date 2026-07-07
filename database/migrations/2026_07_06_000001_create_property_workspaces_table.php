<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_workspaces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('ilan_id')->index();
            $table->uuid('workspace_uuid')->unique();
            $table->string('intent')->nullable();
            $table->string('template_id')->nullable();
            $table->string('state')->default('workspace_created');
            $table->timestamps();
            $table->softDeletes();

            // Composite index for tenant + ilan queries
            $table->index(['tenant_id', 'ilan_id']);
            // Index for state queries within tenant
            $table->index(['tenant_id', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_workspaces');
    }
};
