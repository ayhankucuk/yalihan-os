<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * RESERVATION_CORE Phase 2 E04: Tenant Isolation Audit Trail
     *
     * Records all cross-tenant access attempts for:
     * - Security auditing
     * - Compliance reporting
     * - Investigation of data leaks
     * - Legal evidence
     */
    public function up(): void
    {
        if (!Schema::hasTable('cross_tenant_violation_audit')) {
            Schema::create('cross_tenant_violation_audit', function (Blueprint $table) {
                $table->id();
                $table->string('event_type', 100); // cross_tenant_project, cross_tenant_cancel, etc.
                $table->unsignedInteger('requesting_tenant_id')->index();
                $table->unsignedInteger('property_id')->nullable()->index();
                $table->unsignedInteger('reservation_id')->nullable()->index();
                $table->text('message');
                $table->string('ip_address', 45)->nullable(); // IPv6 compatible
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();

                // Indexes
                $table->index(['event_type', 'created_at']);
                $table->index(['requesting_tenant_id', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cross_tenant_violation_audit');
    }
};
