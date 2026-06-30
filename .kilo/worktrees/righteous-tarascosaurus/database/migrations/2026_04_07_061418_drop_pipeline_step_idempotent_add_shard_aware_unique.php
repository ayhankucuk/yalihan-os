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
    public function up(): void
    {
        if (!Schema::hasTable('pipeline_steps')) {
            return;
        }

        // Check if index exists before attempting to drop
        $indexExists = collect(DB::select("SHOW INDEX FROM pipeline_steps WHERE Key_name = 'pipeline_step_idempotent'"))->isNotEmpty();

        if ($indexExists) {
            Schema::table('pipeline_steps', function (Blueprint $table) {
                $table->dropUnique('pipeline_step_idempotent');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('pipeline_steps')) {
            return;
        }

        Schema::table('pipeline_steps', function (Blueprint $table) {
            $table->unique(['pipeline_run_id', 'adim_adi'], 'pipeline_step_idempotent');
        });
    }
};
