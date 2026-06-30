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
        // 🏗️ Financial Settings Table (Legacy/Missing)
        if (!Schema::hasTable('financial_settings')) {
            Schema::create('financial_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('default_commission_rate', 5, 2)->default(3.00);
                $table->decimal('min_commission_rate', 5, 2)->default(1.00);
                $table->decimal('max_commission_rate', 5, 2)->default(10.00);
                $table->decimal('office_share', 5, 2)->default(50.00);
                $table->decimal('agent_share', 5, 2)->default(50.00);
                $table->integer('payment_delay_days')->default(30);
                $table->timestamps();
            });

            // Seed default settings
            \Illuminate\Support\Facades\DB::table('financial_settings')->insert([
                'default_commission_rate' => 3.00,
                'min_commission_rate' => 1.00,
                'max_commission_rate' => 10.00,
                'office_share' => 50.00,
                'agent_share' => 50.00,
                'payment_delay_days' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 🏗️ Commissions Table (Legacy/Missing)
        if (!Schema::hasTable('commissions')) {
            Schema::create('commissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ilan_id')->index();
                $table->unsignedBigInteger('agent_id')->index();
                $table->decimal('sale_price', 15, 2);
                $table->decimal('commission_rate', 5, 2);
                $table->decimal('total_commission', 15, 2);
                $table->decimal('office_share_percentage', 5, 2);
                $table->decimal('agent_share_percentage', 5, 2);
                $table->decimal('office_amount', 15, 2);
                $table->decimal('agent_amount', 15, 2);
                $table->string('payment_state')->default('pending');
                $table->date('payout_date')->nullable();
                $table->unsignedBigInteger('calculated_by')->nullable();
                $table->unsignedBigInteger('paid_by')->nullable();
                $table->string('invoice_number')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 🏗️ Bonuses Table (Legacy/Missing)
        if (!Schema::hasTable('bonuses')) {
            Schema::create('bonuses', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('agent_id')->index();
                $table->string('target_month', 7); // YYYY-MM
                $table->decimal('amount', 15, 2);
                $table->string('bonus_type')->default('performance');
                $table->text('reason')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_settings');
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('bonuses');
    }
};
