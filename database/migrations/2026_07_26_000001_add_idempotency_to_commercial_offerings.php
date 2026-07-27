<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 20: CommercialOffering Aggregate
     * Adds idempotency_key for replay-safe operations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('commercial_offerings', 'idempotency_key')) {
            Schema::table('commercial_offerings', function (Blueprint $table) {
                $table->string('idempotency_key', 64)->nullable()->unique()->after('uuid');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('commercial_offerings', 'idempotency_key')) {
            Schema::table('commercial_offerings', function (Blueprint $table) {
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
