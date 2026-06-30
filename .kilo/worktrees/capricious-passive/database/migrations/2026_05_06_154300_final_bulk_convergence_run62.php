<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Final Bulk Convergence for Run #62
     */
    public function up(): void
    {
        // Add missing column
        if (Schema::hasTable('property_calendar_feeds') && !Schema::hasColumn('property_calendar_feeds', 'last_synced_at')) {
            Schema::table('property_calendar_feeds', function (Blueprint $table) {
                $table->timestamp('last_synced_at')->nullable();
            });
        }

        // Create missing tables
        if (!Schema::hasTable('listing_velocity_projections')) {
            Schema::create('listing_velocity_projections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('listing_id');
                $table->integer('view_count')->default(0);
                $table->integer('favorite_count')->default(0);
                $table->integer('inquiry_count')->default(0);
                $table->integer('share_count')->default(0);
                $table->decimal('activity_score', 8, 2)->nullable();
                $table->timestamps();

                $table->index('listing_id');
                $table->index('activity_score');
            });
        }

        if (!Schema::hasTable('ref_sequences')) {
            Schema::create('ref_sequences', function (Blueprint $table) {
                $table->id();
                $table->string('sequence_key')->unique();
                $table->integer('current_value')->default(0);
                $table->string('prefix')->nullable();
                $table->string('suffix')->nullable();
                $table->integer('increment_by')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('financial_transactions')) {
            Schema::create('financial_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id')->nullable();
                $table->string('islem_tipi');
                $table->string('islem_durumu');
                $table->decimal('base_amount', 15, 2);
                $table->string('currency', 3)->default('TRY');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index(['property_id', 'islem_tipi', 'islem_durumu']);
                $table->index('created_at');
            });
        }

        if (!Schema::hasTable('property_growth_projections')) {
            Schema::create('property_growth_projections', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('property_id');
                $table->decimal('yearly_growth_rate', 8, 4)->nullable();
                $table->integer('projection_years')->default(1);
                $table->timestamps();

                $table->index('property_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('property_calendar_feeds', 'last_synced_at')) {
            Schema::table('property_calendar_feeds', function (Blueprint $table) {
                $table->dropColumn('last_synced_at');
            });
        }

        Schema::dropIfExists('property_growth_projections');
        Schema::dropIfExists('financial_transactions');
        Schema::dropIfExists('ref_sequences');
        Schema::dropIfExists('listing_velocity_projections');
    }
};
