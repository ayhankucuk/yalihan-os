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
        if (!Schema::hasTable('ai_workspace_wallets')) {
            Schema::create('ai_workspace_wallets', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->nullable()->constrained('tenants');
                $table->decimal('balance', 15, 4)->default(0.0000);
                $table->string('currency', 3)->default('USD');
                $table->string('status')->default('active'); // ✅ Orthodox: status (was durum)
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_workspace_wallets');
    }
};
