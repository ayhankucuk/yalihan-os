<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('tenants')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                // SQLite driver drift protection: SQLite doesn't support dropping foreign keys cleanly in this syntax
                if (\Illuminate\Support\Facades\DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign(['tenant_id']);
                }
                $table->dropColumn('tenant_id');
            });
        }
    }
};
