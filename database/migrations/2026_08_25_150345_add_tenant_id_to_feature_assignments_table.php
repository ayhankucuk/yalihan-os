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
            // Tenant isolation: template-level records have tenant_id=null
            // Per-tenant customizations would have specific tenant_id
            $table->unsignedBigInteger('tenant_id')->nullable()->after('source_type');
            $table->index('tenant_id', 'idx_fa_tenant_id');
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
            $table->dropIndex('idx_fa_tenant_id');
            $table->dropColumn('tenant_id');
        });
    }
};
