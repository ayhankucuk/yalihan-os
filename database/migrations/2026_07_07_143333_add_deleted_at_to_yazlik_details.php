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
        if (!Schema::hasTable('yazlik_details')) {
            return;
        }

        Schema::table('yazlik_details', function (Blueprint $table) {
            // ✅ YazlikDetail model uses SoftDeletes trait but migration missing deleted_at column
            if (!Schema::hasColumn('yazlik_details', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('yazlik_details')) {
            return;
        }

        Schema::table('yazlik_details', function (Blueprint $table) {
            if (Schema::hasColumn('yazlik_details', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
