<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Controlled Bulk Convergence for Run #61
     */
    public function up(): void
    {
        // Add missing columns
        if (Schema::hasTable('eslesmeler') && !Schema::hasColumn('eslesmeler', 'notlar')) {
            Schema::table('eslesmeler', function (Blueprint $table) {
                $table->text('notlar')->nullable();
            });
        }

        if (Schema::hasTable('ups_feature_pack_items') && !Schema::hasColumn('ups_feature_pack_items', 'display_order')) {
            Schema::table('ups_feature_pack_items', function (Blueprint $table) {
                $table->integer('display_order')->nullable()->default(0);
            });
        }

        if (Schema::hasTable('property_calendar_feeds') && !Schema::hasColumn('property_calendar_feeds', 'last_sync_hash')) {
            Schema::table('property_calendar_feeds', function (Blueprint $table) {
                $table->string('last_sync_hash', 64)->nullable();
            });
        }

        if (Schema::hasTable('tenants') && !Schema::hasColumn('tenants', 'deleted_at')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->softDeletes();
            });
        }

        // Create missing tables
        if (!Schema::hasTable('ilan_price_history')) {
            Schema::create('ilan_price_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ilan_id');
                $table->decimal('old_price', 15, 2)->nullable();
                $table->decimal('new_price', 15, 2);
                $table->string('currency', 3)->default('TRY');
                $table->string('changed_by')->nullable();
                $table->string('change_reason')->nullable();
                $table->timestamps();

                $table->index('ilan_id');
            });
        }

        if (!Schema::hasTable('country_financial_rules')) {
            Schema::create('country_financial_rules', function (Blueprint $table) {
                $table->id();
                $table->string('country_code', 2);
                $table->string('country_name');
                $table->decimal('rental_commission_rate', 5, 4)->default(0);
                $table->decimal('sales_commission_rate', 5, 4)->default(0);
                $table->decimal('advisory_fee_rate', 5, 4)->default(0);
                $table->decimal('tax_rate', 5, 4)->default(0);
                $table->boolean('aktiflik_durumu')->default(true);
                $table->timestamps();

                $table->unique('country_code');
            });
        }

        if (!Schema::hasTable('market_trend_projections')) {
            Schema::create('market_trend_projections', function (Blueprint $table) {
                $table->id();
                $table->integer('city')->nullable();
                $table->integer('district')->nullable();
                $table->string('property_type')->nullable();
                $table->decimal('avg_price', 15, 2)->nullable();
                $table->decimal('median_price', 15, 2)->nullable();
                $table->decimal('price_change_7d', 8, 2)->nullable();
                $table->decimal('price_change_30d', 8, 2)->nullable();
                $table->decimal('demand_index', 8, 2)->nullable();
                $table->integer('listing_count')->nullable();
                $table->timestamps();

                $table->index(['city', 'district', 'property_type']);
            });
        }

        if (!Schema::hasTable('governance_incidents')) {
            Schema::create('governance_incidents', function (Blueprint $table) {
                $table->id();
                $table->string('tenant_id')->default('SYSTEM');
                $table->string('olay_tipi');
                $table->string('kaynak');
                $table->string('risk_seviyesi');
                $table->string('snapshot_id')->nullable();
                $table->string('imza_hash')->nullable();
                $table->json('details')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'olay_tipi']);
                $table->index('risk_seviyesi');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('eslesmeler', 'notlar')) {
            Schema::table('eslesmeler', function (Blueprint $table) {
                $table->dropColumn('notlar');
            });
        }

        if (Schema::hasColumn('ups_feature_pack_items', 'display_order')) {
            Schema::table('ups_feature_pack_items', function (Blueprint $table) {
                $table->dropColumn('display_order');
            });
        }

        if (Schema::hasColumn('property_calendar_feeds', 'last_sync_hash')) {
            Schema::table('property_calendar_feeds', function (Blueprint $table) {
                $table->dropColumn('last_sync_hash');
            });
        }

        if (Schema::hasColumn('tenants', 'deleted_at')) {
            Schema::table('tenants', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        Schema::dropIfExists('governance_incidents');
        Schema::dropIfExists('market_trend_projections');
        Schema::dropIfExists('country_financial_rules');
        Schema::dropIfExists('ilan_price_history');
    }
};
