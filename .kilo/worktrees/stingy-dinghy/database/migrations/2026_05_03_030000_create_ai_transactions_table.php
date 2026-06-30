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
        if (!Schema::hasTable('ai_transactions')) {
            Schema::create('ai_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('wallet_id');
                $table->bigInteger('amount');
                $table->unsignedBigInteger('final_balance');
                $table->string('reason');
                $table->string('reference_type')->nullable();
                $table->unsignedBigInteger('reference_id')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index(['reference_type', 'reference_id']);
                $table->index(['tenant_id', 'created_at']);
                $table->index('reason');
                
                $table->foreign('wallet_id')->references('id')->on('ai_workspace_wallets')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_transactions');
    }
};
