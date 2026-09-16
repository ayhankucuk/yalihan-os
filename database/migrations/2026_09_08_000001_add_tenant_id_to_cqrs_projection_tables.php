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
        // SQLite CI bootstrap parity for tables present in MySQL schema dump
        if (!Schema::hasTable('buyer_interest_projections')) {
            Schema::create('buyer_interest_projections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('listing_id');
                $table->integer('candidate_count')->default(0);
                $table->integer('avg_match_score')->default(0);
                $table->integer('top_match_score')->default(0);
                $table->integer('high_intent_buyer_count')->default(0);
                $table->integer('recent_query_count')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('buyer_intent_projection')) {
            Schema::create('buyer_intent_projection', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('buyer_id');
                $table->string('locale', 10)->nullable();
                $table->string('preferred_city')->nullable();
                $table->string('preferred_district')->nullable();
                $table->decimal('min_budget', 15, 2)->nullable();
                $table->decimal('max_budget', 15, 2)->nullable();
                $table->json('property_types')->nullable();
                $table->json('room_preferences')->nullable();
                $table->json('feature_preferences')->nullable();
                $table->integer('urgency_level')->default(0);
                $table->decimal('recent_activity_score', 5, 2)->default(0.00);
                $table->timestamp('last_contact_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('talep_match_projection') && !Schema::hasColumn('talep_match_projection', 'talep_id')) {
            Schema::table('talep_match_projection', function (Blueprint $table) {
                $table->unsignedBigInteger('talep_id')->nullable();
                $table->unsignedBigInteger('buyer_id')->nullable();
                $table->json('features')->nullable();
                $table->integer('purchase_intent_level')->default(0);
            });
        }

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
                    $table->unsignedBigInteger('tenant_id')->nullable()->index();
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
