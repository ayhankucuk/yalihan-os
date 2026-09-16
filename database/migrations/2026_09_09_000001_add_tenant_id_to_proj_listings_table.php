<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ADR-042 Phase 2 — proj_listings tenant_id addition.
 *
 * Adds tenant_id column to proj_listings CQRS projection table.
 * This enables the fail-closed tenant isolation pattern on listing projections.
 *
 * Idempotent: column added only if absent.
 * Backfill: existing proj_listings records get tenant_id from their ilan row.
 * Index: tenant_id FK index for efficient tenant-scoped queries.
 *
 * HOTSPOT_LOCK: database/migrations/* — Kilo, TTL 7200s
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('proj_listings')) {
            return;
        }

        if (Schema::hasColumn('proj_listings', 'tenant_id')) {
            return;
        }

        Schema::table('proj_listings', function (Blueprint $table) {
            // Tenant isolation key — nullable during backfill, enforced via model after
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        // Backfill: join proj_listings.ilan_id → ilanlar.id to resolve tenant_id
        if (Schema::hasColumn('ilanlar', 'tenant_id')) {
            if (config('database.default') === 'mysql') {
                DB::statement("
                    UPDATE proj_listings pl
                    INNER JOIN ilanlar i ON i.id = pl.ilan_id
                    SET pl.tenant_id = i.tenant_id
                    WHERE pl.tenant_id IS NULL
                      AND i.deleted_at IS NULL
                ");
            } else {
                DB::statement("
                    UPDATE proj_listings
                    SET tenant_id = (SELECT tenant_id FROM ilanlar WHERE ilanlar.id = proj_listings.ilan_id)
                    WHERE tenant_id IS NULL
                ");
            }
        }

        // FK index for fast tenant-scoped queries
        if (config('database.default') === 'mysql') {
            Schema::table('proj_listings', function (Blueprint $table) {
                $table->index('tenant_id', 'proj_listings_tenant_id_idx');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('proj_listings')) {
            return;
        }

        Schema::table('proj_listings', function (Blueprint $table) {
            $table->dropIndex('proj_listings_tenant_id_idx');
            $table->dropColumn('tenant_id');
        });
    }
};
