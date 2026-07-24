<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 12B: Properties Table Creation
 *
 * SAAB Board Resolution: BR-20260717-Sprint12B
 * Option A: Canonical Property Aggregate Infrastructure
 *
 * Minimal schema as per SAAB approval:
 * - id, tenant_id, canonical_reference, lifecycle_state, timestamps, softDeletes
 *
 * NOT included (per SAAB decision):
 * - Workspace runtime fields
 * - Publishing platform fields
 * - Pricing fields
 * - AI outputs
 * - Media details
 * - CRM details
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Idempotent: skip if table already exists with canonical_reference column
        if (Schema::hasTable('properties') && Schema::hasColumn('properties', 'canonical_reference')) {
            return;
        }

        // If table exists but missing columns, add them
        if (Schema::hasTable('properties') && !Schema::hasColumn('properties', 'canonical_reference')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->string('canonical_reference', 64)->nullable()->unique()->after('id');
                $table->string('lifecycle_state')->default('DRAFT')->after('canonical_reference');
            });
            return;
        }

        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('workspace_id')->nullable()
                ->comment('Link to PropertyWorkspace');
            $table->string('idempotency_key', 64)->nullable()->unique();
            $table->string('canonical_reference', 64)->nullable()->unique()
                ->comment('Deterministic identifier for legacy mapping');
            $table->string('tkgm_id')->nullable();
            $table->string('ada')->nullable();
            $table->string('parsel')->nullable();
            $table->unsignedBigInteger('il_id')->nullable();
            $table->unsignedBigInteger('ilce_id')->nullable();
            $table->unsignedBigInteger('mahalle_id')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->decimal('alan_m2', 10, 2)->nullable();
            $table->integer('bina_yasi')->nullable();
            $table->integer('kat_sayisi')->nullable();
            $table->integer('bulundugu_kat')->nullable();
            $table->integer('oda_sayisi')->nullable();
            $table->integer('banyo_sayisi')->nullable();
            $table->string('kapak_resmi')->nullable();
            $table->string('nitelik')->nullable();
            $table->string('lifecycle_state')->default('DRAFT')
                ->comment('DRAFT | ACTIVE | ARCHIVED');
            $table->string('aktiflik_durumu')->default('DRAFT')
                ->comment('Alias for lifecycle_state (Context7 compatibility)');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'lifecycle_state'], 'properties_tenant_state_idx');
            $table->index(['tenant_id', 'canonical_reference'], 'properties_tenant_canonical_idx');
            $table->index(['tenant_id', 'idempotency_key'], 'properties_tenant_idempotency_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
