<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Canonical requirements for opportunities table:
     * - tenant_id: REQUIRED (multi-tenant isolation)
     * - ilan_id: NOT NULL FK → ilanlar.id
     * - lead_id: NOT NULL FK → leads.id
     * - ikna_metni: AI-generated persuasive text
     * - Composite unique: (tenant_id, ilan_id, lead_id)
     *
     * Cross-database: MySQL (production) + SQLite (testing).
     * MySQL uses raw SQL for FK manipulation.
     * SQLite uses Schema builder (no FK constraints needed for SQLite).
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            $this->migrateForMySQL();
        } else {
            $this->migrateForSQLite();
        }
    }

    protected function migrateForMySQL(): void
    {
        // Step 1: Drop existing FK (SET NULL → must drop before changing to NOT NULL)
        try {
            DB::statement('ALTER TABLE opportunities DROP FOREIGN KEY IF EXISTS opportunities_ilan_id_foreign');
        } catch (\Exception $e) {
            // May already be gone
        }

        // Step 2: Change ilan_id to NOT NULL
        DB::statement('ALTER TABLE opportunities CHANGE ilan_id ilan_id BIGINT UNSIGNED NOT NULL');

        // Step 3: Add tenant_id column
        if (!Schema::hasColumn('opportunities', 'tenant_id')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('lead_id');
            });
        }

        // Step 4: Add ikna_metni
        if (!Schema::hasColumn('opportunities', 'ikna_metni')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->text('ikna_metni')->nullable()->after('firsat_nedeni');
            });
        }

        // Step 5: Add FKs
        Schema::table('opportunities', function (Blueprint $table) {
            try {
                $table->foreign('ilan_id')->references('id')->on('ilanlar')->onDelete('cascade');
            } catch (\Exception $e) { /** @sab-ignore-catch */ }

            try {
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            } catch (\Exception $e) { /** @sab-ignore-catch */ }
        });

        // Step 6: Add indexes
        if (!Schema::hasIndex('opportunities', 'opportunities_tenant_listing_lead_unique')) {
            try {
                Schema::table('opportunities', function (Blueprint $table) {
                    $table->unique(
                        ['tenant_id', 'ilan_id', 'lead_id'],
                        'opportunities_tenant_listing_lead_unique'
                    );
                });
            } catch (\Exception $e) { /** @sab-ignore-catch */ }
        }

        if (!Schema::hasIndex('opportunities', 'opportunities_tenant_id_index')) {
            try {
                Schema::table('opportunities', function (Blueprint $table) {
                    $table->index('tenant_id', 'opportunities_tenant_id_index');
                });
            } catch (\Exception $e) { /** @sab-ignore-catch */ }
        }
    }

    protected function migrateForSQLite(): void
    {
        // SQLite: Schema builder only, no FK enforcement
        if (!Schema::hasColumn('opportunities', 'tenant_id')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('lead_id');
            });
        }

        if (!Schema::hasColumn('opportunities', 'ikna_metni')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->text('ikna_metni')->nullable()->after('firsat_nedeni');
            });
        }

        if (!Schema::hasColumn('opportunities', 'lead_id')) {
            Schema::table('opportunities', function (Blueprint $table) {
                $table->unsignedBigInteger('lead_id')->nullable()->after('ilan_id');
            });
        }

        if (!Schema::hasIndex('opportunities', 'opportunities_tenant_listing_lead_unique')) {
            try {
                Schema::table('opportunities', function (Blueprint $table) {
                    $table->unique(
                        ['tenant_id', 'ilan_id', 'lead_id'],
                        'opportunities_tenant_listing_lead_unique'
                    );
                });
            } catch (\Exception $e) { /** @sab-ignore-catch */ }
        }
    }

    public function down(): void
    {
        // Intentionally minimal — structural fix, not data migration
    }
};
