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
        if (!Schema::hasTable('gorevler')) {
            return;
        }

        Schema::table('gorevler', function (Blueprint $table) {
            if (!Schema::hasColumn('gorevler', 'gorev_tipi')) {
                $table->string('gorev_tipi')->nullable()->after('aciklama');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('gorevler')) {
            return;
        }

        Schema::table('gorevler', function (Blueprint $table) {
            if (Schema::hasColumn('gorevler', 'gorev_tipi')) {
                $table->dropColumn('gorev_tipi');
            }
        });
    }
};
