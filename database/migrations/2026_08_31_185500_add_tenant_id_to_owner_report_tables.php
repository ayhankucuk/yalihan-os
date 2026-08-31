<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add tenant_id to owner_report_rows and owner_report_metrics tables.
 *
 * Context7 Standard: C7-OWNER-REPORT-TENANT-ISOLATION-V1
 * SAB Rule 1: Tenant isolation strictly enforced on owner reporting projection.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. owner_report_rows
        if (Schema::hasTable('owner_report_rows') && !Schema::hasColumn('owner_report_rows', 'tenant_id')) {
            Schema::table('owner_report_rows', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id', 'idx_owner_report_rows_tenant_id');
                $table->index(['tenant_id', 'owner_id'], 'idx_report_rows_tenant_owner');
            });

            // Backfill tenant_id from users table where owner_id matches
            if (DB::getDriverName() === 'mysql') {
                DB::statement('
                    UPDATE owner_report_rows r
                    INNER JOIN users u ON r.owner_id = u.id
                    SET r.tenant_id = u.tenant_id
                    WHERE u.tenant_id IS NOT NULL
                ');
            } else {
                // SQLite compatible backfill
                DB::statement('
                    UPDATE owner_report_rows
                    SET tenant_id = (
                        SELECT tenant_id FROM users WHERE users.id = owner_report_rows.owner_id
                    )
                    WHERE EXISTS (
                        SELECT 1 FROM users WHERE users.id = owner_report_rows.owner_id AND users.tenant_id IS NOT NULL
                    )
                ');
            }
        }

        // 2. owner_report_metrics
        if (Schema::hasTable('owner_report_metrics') && !Schema::hasColumn('owner_report_metrics', 'tenant_id')) {
            Schema::table('owner_report_metrics', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                $table->index('tenant_id', 'idx_owner_report_metrics_tenant_id');
                $table->index(['tenant_id', 'owner_id'], 'idx_report_metrics_tenant_owner');
            });

            // Backfill tenant_id from users table where owner_id matches
            if (DB::getDriverName() === 'mysql') {
                DB::statement('
                    UPDATE owner_report_metrics m
                    INNER JOIN users u ON m.owner_id = u.id
                    SET m.tenant_id = u.tenant_id
                    WHERE u.tenant_id IS NOT NULL
                ');
            } else {
                // SQLite compatible backfill
                DB::statement('
                    UPDATE owner_report_metrics
                    SET tenant_id = (
                        SELECT tenant_id FROM users WHERE users.id = owner_report_metrics.owner_id
                    )
                    WHERE EXISTS (
                        SELECT 1 FROM users WHERE users.id = owner_report_metrics.owner_id AND users.tenant_id IS NOT NULL
                    )
                ');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('owner_report_metrics') && Schema::hasColumn('owner_report_metrics', 'tenant_id')) {
            Schema::table('owner_report_metrics', function (Blueprint $table) {
                $table->dropIndex('idx_report_metrics_tenant_owner');
                $table->dropIndex('idx_owner_report_metrics_tenant_id');
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('owner_report_rows') && Schema::hasColumn('owner_report_rows', 'tenant_id')) {
            Schema::table('owner_report_rows', function (Blueprint $table) {
                $table->dropIndex('idx_report_rows_tenant_owner');
                $table->dropIndex('idx_owner_report_rows_tenant_id');
                $table->dropColumn('tenant_id');
            });
        }
    }
};
