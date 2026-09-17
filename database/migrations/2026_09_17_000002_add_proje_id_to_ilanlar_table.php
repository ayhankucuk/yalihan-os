<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ADR #006 Schema Gap Remediation: Add ilanlar.proje_id
 *
 * Establishes the schema column for Ilan → Emlak Proje bounded-context relationship.
 *
 * IMPORTANT:
 * - NO foreign-key constraint added in this migration.
 * - Production data/schema compatibility audit pending (ADR #006 Phase 1C protocol).
 * - Column is nullable to preserve existing ilanlar records.
 *
 * Once production compatibility verified, a future additive migration may introduce
 * the FK constraint: ilanlar.proje_id → emlak_projeleri.id
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            if (!Schema::hasColumn('ilanlar', 'proje_id')) {
                // Use unsignedBigInteger to match emlak_projeleri.id (auto-increment BIGINT)
                // Position after lng to group with location/context fields
                $table->unsignedBigInteger('proje_id')->nullable()->after('lng');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            if (Schema::hasColumn('ilanlar', 'proje_id')) {
                $table->dropColumn('proje_id');
            }
        });
    }
};
