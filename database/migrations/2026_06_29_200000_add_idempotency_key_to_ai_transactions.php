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
        if (Schema::hasTable('ai_transactions')) {
            Schema::table('ai_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('ai_transactions', 'idempotency_key')) {
                    $table->string('idempotency_key')->nullable()->unique();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ai_transactions')) {
            Schema::table('ai_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('ai_transactions', 'idempotency_key')) {
                    $table->dropColumn('idempotency_key');
                }
            });
        }
    }
};
