<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * CQRS Projection Tenant Isolation
     *
     * ADR-042 Karar #4: 6 CQRS projection tablosuna tenant_id eklenir.
     * TenantScope fail-closed hazırlığı: projection tabloları tenant-aware olmalı.
     *
     * Tables: listing_search_projection, listing_velocity_projections,
     *         market_trend_projections, buyer_interest_projections,
     *         talep_match_projection, buyer_intent_projection
     *
     * NOTE: Mevcut projection tabloları BOŞ (cqrs-projection-research §1).
     *       Backfill gerekmez — yeni kayıtlar için tenant_id zorunlu.
     *       Mevcut null kayıtlar: TenantScope fail-closed sonrası görünmez olur.
     */
    public function up(): void
    {
        $tables = [
            'listing_search_projection',
            'listing_velocity_projections',
            'market_trend_projections',
            'buyer_interest_projections',
            'talep_match_projection',
            'buyer_intent_projection',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->index()
                        ->after('id');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'listing_search_projection',
            'listing_velocity_projections',
            'market_trend_projections',
            'buyer_interest_projections',
            'talep_match_projection',
            'buyer_intent_projection',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'tenant_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropIndex(['tenant_id']);
                    $table->dropColumn('tenant_id');
                });
            }
        }
    }
};
