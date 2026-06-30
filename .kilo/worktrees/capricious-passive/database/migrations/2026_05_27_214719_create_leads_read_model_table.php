<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: leads_read_model (Lead Read Model Projection)
 *
 * SAB Phase 15 Sprint 2: CQRS Read Model for Lead Domain
 * Query-optimized denormalized table for fast lead lookups.
 *
 * Anayasal Kararlar:
 * - Madde 1: Idempotency Protection (son_islenen_sira_numarasi)
 * - Madde 2: Tenant Isolation (tenant_id + HasCountryScope)
 * - Madde 3: Query Optimization (denormalized fields)
 * - Madde 4: Event-driven updates (no direct writes)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leads_read_model', function (Blueprint $table) {
            $table->id();

            // Tenant Isolation
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('ulke_id')->nullable()->index()->comment('Country isolation (HasCountryScope)');

            // Aggregate Identity
            $table->string('uuid', 100)->unique()->comment('Aggregate root identifier');

            // Lead Core Data (Denormalized)
            $table->string('platform', 50)->comment('whatsapp, facebook, web, etc.');
            $table->string('platform_user_id')->comment('External platform user ID');
            $table->text('message_text')->nullable();

            // CRM Status
            $table->tinyInteger('crm_durumu')->default(1)->index()->comment('1: Yeni, 2: Aranacak, 3: Görüşüldü, etc.');
            $table->unsignedBigInteger('assigned_to')->nullable()->index()->comment('Assigned advisor user_id');

            // Activity Tracking
            $table->unsignedInteger('contact_attempts')->default(0);
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('converted_at')->nullable();

            // Status
            $table->tinyInteger('aktiflik_durumu')->default(1)->index();

            // Idempotency Protection (Sprint 2 Decision 3)
            $table->unsignedInteger('son_islenen_sira_numarasi')->default(0)->comment('Last processed sequence number for idempotency');

            // Timestamps
            $table->timestamp('olusturulma_zamani')->useCurrent();
            $table->timestamp('degistirilme_zamani')->nullable();

            // Indexes for Query Performance
            $table->index(['tenant_id', 'crm_durumu'], 'idx_tenant_status');
            $table->index(['tenant_id', 'assigned_to'], 'idx_tenant_advisor');
            $table->index(['platform', 'platform_user_id'], 'idx_platform_user');
            $table->index('olusturulma_zamani');

            // Foreign Keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('cascade');

            $table->foreign('assigned_to')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads_read_model');
    }
};
