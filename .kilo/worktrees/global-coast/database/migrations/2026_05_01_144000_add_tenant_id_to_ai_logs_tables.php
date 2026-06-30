<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('ai_logs', 'tenant_id')) {
            Schema::table('ai_logs', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id');
            });
        }

        if (!Schema::hasColumn('ai_feature_usages', 'tenant_id')) {
            Schema::table('ai_feature_usages', function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        Schema::table('ai_logs', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn('tenant_id');
        });

        Schema::table('ai_feature_usages', function (Blueprint $table) {
            $table->foreignId('tenant_id')->nullable()->after('id')->constrained('tenants')->nullOnDelete();
        });
    }
};
