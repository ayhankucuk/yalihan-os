<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Make tenant_id NOT NULL — enforce tenant isolation boundary
        Schema::table('kisiler', function (Blueprint $table) {
            // Drop nullable constraint and add NOT NULL
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });

        // 2. Add company/legal entity fields for legal entity support
        Schema::table('kisiler', function (Blueprint $table) {
            $table->string('vergi_kimlik_no', 20)->nullable()
                ->comment('Tax identification number for legal entities');
            $table->string('kurum_unvani', 255)->nullable()
                ->comment('Company name / legal entity title');
            $table->string('mersis_no', 20)->nullable()
                ->comment('MERSiS number (Trade Registry Information System)');
            $table->string('sicil_no', 20)->nullable()
                ->comment('Trade registry number / sicil number');
        });

        // 3. Add tenant_id index for query performance
        Schema::table('kisiler', function (Blueprint $table) {
            // Index already exists (kisiler_tenant_id_index) per schema inspection
            // Add composite index for common query patterns
            $table->index(['tenant_id', 'aktiflik_durumu'], 'kisiler_tenant_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('kisiler', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->change();
            $table->dropColumn(['vergi_kimlik_no', 'kurum_unvani', 'mersis_no', 'sicil_no']);
            $table->dropIndex('kisiler_tenant_active_idx');
        });
    }
};
