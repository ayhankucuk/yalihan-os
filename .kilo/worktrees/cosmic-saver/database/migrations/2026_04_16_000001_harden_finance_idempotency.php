<?php

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
        // 1. Commissions Idempotency
        Schema::table('commissions', function (Blueprint $table) {
            // Check if index exists first to prevent failure if already present
            $table->unique(['ilan_id', 'agent_id'], 'unique_commission_per_listing_agent');
        });

        // 2. Bonuses Idempotency
        Schema::table('bonuses', function (Blueprint $table) {
            $table->unique(['agent_id', 'target_month'], 'unique_bonus_per_agent_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commissions', function (Blueprint $table) {
            $table->dropUnique('unique_commission_per_listing_agent');
        });

        Schema::table('bonuses', function (Blueprint $table) {
            $table->dropUnique('unique_bonus_per_agent_month');
        });
    }
};
