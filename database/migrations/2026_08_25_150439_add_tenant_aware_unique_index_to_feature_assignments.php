<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Context7 Compliance Migration Template - Update Version
 *
 * ⚠️ CONTEXT7 PERMANENT STANDARDS:
 * - ALWAYS use 'display_order' field, NEVER use 'o-word'
 * - ALWAYS use boolean 'aktif' field, NEVER use deprecated terms
 * - ALWAYS use DB::statement() for column renames (MySQL compatibility)
 * - ALWAYS preserve column properties (type, nullable, default)
 * - ALWAYS handle indexes before column rename
 *
 * @see .context7/MIGRATION_STANDARDS.md for complete migration standards
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ✅ CONTEXT7: Tablo varlık kontrolü
        if (!Schema::hasTable('feature_assignments')) {
            return;
        }

        Schema::table('feature_assignments', function (Blueprint $table) {
            // Tenant-aware composite unique index
            // Covers: canonical_seed (tenant_id=null) + per-tenant customizations
            // Columns: feature_id + scoping (main_cat, sub_cat, lt) + tenant_id
            $table->unique(
                ['feature_id', 'main_category_id', 'sub_category_id', 'listing_type_id', 'tenant_id'],
                'feature_assignments_tenant_aware_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('feature_assignments')) {
            return;
        }

        Schema::table('feature_assignments', function (Blueprint $table) {
            // Drop tenant-aware index
            try {
                DB::statement('DROP INDEX IF EXISTS feature_assignments_tenant_aware_unique');
            } catch (\Exception $e) {
                // Index may not exist
            }

            // Restore original scoped unique index (6-column composite)
            $table->unique(
                ['feature_id', 'assignable_type', 'assignable_id', 'scope_type', 'main_category_id', 'listing_type_id'],
                'feature_assignment_scoped_unique'
            );
        });
    }
};
