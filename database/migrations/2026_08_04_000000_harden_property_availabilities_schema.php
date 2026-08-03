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
        if (Schema::hasTable('property_availabilities')) {
            Schema::table('property_availabilities', function (Blueprint $table) {
                if (!Schema::hasColumn('property_availabilities', 'tenant_id')) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
                }
                if (!Schema::hasColumn('property_availabilities', 'priority_tier')) {
                    $table->unsignedTinyInteger('priority_tier')->default(3)->after('block_reason');
                }
                if (!Schema::hasColumn('property_availabilities', 'idempotency_key')) {
                    $table->string('idempotency_key', 128)->nullable()->index()->after('priority_tier');
                }
                if (!Schema::hasColumn('property_availabilities', 'ulke_id')) {
                    $table->unsignedBigInteger('ulke_id')->nullable()->after('idempotency_key');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('property_availabilities')) {
            Schema::table('property_availabilities', function (Blueprint $table) {
                if (Schema::hasColumn('property_availabilities', 'tenant_id')) {
                    $table->dropColumn('tenant_id');
                }
                if (Schema::hasColumn('property_availabilities', 'priority_tier')) {
                    $table->dropColumn('priority_tier');
                }
                if (Schema::hasColumn('property_availabilities', 'idempotency_key')) {
                    $table->dropColumn('idempotency_key');
                }
                if (Schema::hasColumn('property_availabilities', 'ulke_id')) {
                    $table->dropColumn('ulke_id');
                }
            });
        }
    }
};
