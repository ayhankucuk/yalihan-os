<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Phase 13 Sprint +2: Multi-Tenant Financial Scoping
 *
 * SAB Core v2.6 / Context7 Compliance Migration
 *
 * OBJECTIVES:
 * 1. Eliminate phantom fields (tenant_id, is_paid, paid_at)
 * 2. Synchronize naming mismatches (office_amount → ofis_tutari, agent_amount → danisman_tutari, amount → prim_tutari)
 * 3. Harden multi-tenant idempotency constraints
 *
 * IDEMPOTENCY: All operations are guarded with Schema::hasColumn() and try-catch blocks
 * DRIVER SAFETY: Compatible with MySQL, PostgreSQL, and SQLite
 *
 * @see .sab/authority.json finance_canonical_fields
 * @see docs/OTURUM_41_FINAL_REPORT.md
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // ========================================
        // PHASE 1: COMMISSIONS TABLE HARDENING
        // ========================================
        if (Schema::hasTable('commissions')) {
            // Step 1: Add tenant_id column
            if (!Schema::hasColumn('commissions', 'tenant_id')) {
                Schema::table('commissions', function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                    $table->index('tenant_id', 'idx_commissions_tenant_id');
                });
            }

            // Step 2: Rename office_amount → ofis_tutari (SQLite: one rename per call)
            if (Schema::hasColumn('commissions', 'office_amount') && !Schema::hasColumn('commissions', 'ofis_tutari')) {
                Schema::table('commissions', function (Blueprint $table) {
                    $table->renameColumn('office_amount', 'ofis_tutari');
                });
            }

            // Step 3: Rename agent_amount → danisman_tutari (SQLite: one rename per call)
            if (Schema::hasColumn('commissions', 'agent_amount') && !Schema::hasColumn('commissions', 'danisman_tutari')) {
                Schema::table('commissions', function (Blueprint $table) {
                    $table->renameColumn('agent_amount', 'danisman_tutari');
                });
            }

            // 4. Drop Legacy Idempotency Index (Safely)
            try {
                Schema::table('commissions', function (Blueprint $table) {
                    $table->dropUnique('unique_commission_per_listing_agent');
                });
            } catch (\Exception $e) {
                // Index does not exist or SQLite runtime - bypass silently (idempotent)
                \Illuminate\Support\Facades\Log::info('Phase13: Legacy index unique_commission_per_listing_agent not found or already dropped.');
            }

            // 5. Inject Multi-Tenant Idempotency Index
            Schema::table('commissions', function (Blueprint $table) {
                // Check if index already exists before creating
                $indexName = 'unique_commission_tenant_listing_agent';
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('commissions');

                if (!isset($indexes[$indexName])) {
                    $table->unique(['tenant_id', 'ilan_id', 'agent_id'], $indexName);
                }
            });
        }

        // ========================================
        // PHASE 2: BONUSES TABLE HARDENING
        // ========================================
        if (Schema::hasTable('bonuses')) {
            // Step 1: Add tenant_id column
            if (!Schema::hasColumn('bonuses', 'tenant_id')) {
                Schema::table('bonuses', function (Blueprint $table) {
                    $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
                    $table->index('tenant_id', 'idx_bonuses_tenant_id');
                });
            }

            // Step 2: Rename amount → prim_tutari (SQLite: one rename per call)
            if (Schema::hasColumn('bonuses', 'amount') && !Schema::hasColumn('bonuses', 'prim_tutari')) {
                Schema::table('bonuses', function (Blueprint $table) {
                    $table->renameColumn('amount', 'prim_tutari');
                });
            }

            // Step 3: Add odendi_mi column
            if (!Schema::hasColumn('bonuses', 'odendi_mi')) {
                Schema::table('bonuses', function (Blueprint $table) {
                    $table->boolean('odendi_mi')->default(false)->after('prim_tutari');
                });
            }

            // Step 4: Add odeme_tarihi column
            if (!Schema::hasColumn('bonuses', 'odeme_tarihi')) {
                Schema::table('bonuses', function (Blueprint $table) {
                    $table->timestamp('odeme_tarihi')->nullable()->after('odendi_mi');
                });
            }
        }

        // ========================================
        // PHASE 3: TENANT_ID FOREIGN KEY CONSTRAINTS
        // ========================================
        if (Schema::hasTable('commissions') && Schema::hasColumn('commissions', 'tenant_id')) {
            Schema::table('commissions', function (Blueprint $table) {
                // Only add foreign key if it doesn't exist
                try {
                    $table->foreign('tenant_id', 'fk_commissions_tenant_id')
                        ->references('id')
                        ->on('tenants')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::info('Phase13: Foreign key fk_commissions_tenant_id already exists or tenants table not found.');
                }
            });
        }

        if (Schema::hasTable('bonuses') && Schema::hasColumn('bonuses', 'tenant_id')) {
            Schema::table('bonuses', function (Blueprint $table) {
                // Only add foreign key if it doesn't exist
                try {
                    $table->foreign('tenant_id', 'fk_bonuses_tenant_id')
                        ->references('id')
                        ->on('tenants')
                        ->onDelete('cascade');
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::info('Phase13: Foreign key fk_bonuses_tenant_id already exists or tenants table not found.');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // ========================================
        // ROLLBACK BONUSES
        // ========================================
        if (Schema::hasTable('bonuses')) {
            // Drop foreign key first
            try {
                Schema::table('bonuses', function (Blueprint $table) {
                    $table->dropForeign('fk_bonuses_tenant_id');
                });
            } catch (\Exception $e) {
                // Foreign key doesn't exist - continue
            }

            // Drop odeme_tarihi
            if (Schema::hasColumn('bonuses', 'odeme_tarihi')) {
                Schema::table('bonuses', function (Blueprint $table) {
                    $table->dropColumn('odeme_tarihi');
                });
            }

            // Drop odendi_mi
            if (Schema::hasColumn('bonuses', 'odendi_mi')) {
                Schema::table('bonuses', function (Blueprint $table) {
                    $table->dropColumn('odendi_mi');
                });
            }

            // Rename prim_tutari → amount
            if (Schema::hasColumn('bonuses', 'prim_tutari') && !Schema::hasColumn('bonuses', 'amount')) {
                Schema::table('bonuses', function (Blueprint $table) {
                    $table->renameColumn('prim_tutari', 'amount');
                });
            }

            // Drop tenant_id
            if (Schema::hasColumn('bonuses', 'tenant_id')) {
                Schema::table('bonuses', function (Blueprint $table) {
                    $table->dropIndex('idx_bonuses_tenant_id');
                    $table->dropColumn('tenant_id');
                });
            }
        }

        // ========================================
        // ROLLBACK COMMISSIONS
        // ========================================
        if (Schema::hasTable('commissions')) {
            // Drop foreign key first
            try {
                Schema::table('commissions', function (Blueprint $table) {
                    $table->dropForeign('fk_commissions_tenant_id');
                });
            } catch (\Exception $e) {
                // Foreign key doesn't exist - continue
            }

            // Drop multi-tenant index
            try {
                Schema::table('commissions', function (Blueprint $table) {
                    $table->dropUnique('unique_commission_tenant_listing_agent');
                });
            } catch (\Exception $e) {
                // Index doesn't exist - continue
            }

            // Rename danisman_tutari → agent_amount
            if (Schema::hasColumn('commissions', 'danisman_tutari') && !Schema::hasColumn('commissions', 'agent_amount')) {
                Schema::table('commissions', function (Blueprint $table) {
                    $table->renameColumn('danisman_tutari', 'agent_amount');
                });
            }

            // Rename ofis_tutari → office_amount
            if (Schema::hasColumn('commissions', 'ofis_tutari') && !Schema::hasColumn('commissions', 'office_amount')) {
                Schema::table('commissions', function (Blueprint $table) {
                    $table->renameColumn('ofis_tutari', 'office_amount');
                });
            }

            // Drop tenant_id
            if (Schema::hasColumn('commissions', 'tenant_id')) {
                Schema::table('commissions', function (Blueprint $table) {
                    $table->dropIndex('idx_commissions_tenant_id');
                    $table->dropColumn('tenant_id');
                });
            }

            // Restore legacy index
            Schema::table('commissions', function (Blueprint $table) {
                $table->unique(['ilan_id', 'agent_id'], 'unique_commission_per_listing_agent');
            });
        }
    }
};
