<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add ilan_id and kaynak columns to talepler.
     *
     * Safe additive migration:
     * - ilan_id: nullable FK — links Talep to the specific Ilan that triggered the contact form.
     *   Allows reverse-lookup: "which Taleps originated from this Ilan's contact page?"
     *   Also enables Eslesme pipeline to work without separate ilan_id tracking.
     *
     * - kaynak: nullable string — tracks Talep origin ('frontend_ilan_form', 'telegram', etc.)
     *
     * Task: WEB_PROPERTY_DETAIL_CRM_CONTACT_REMEDIATION_16
     */
    public function up(): void
    {
        if (! Schema::hasColumn('talepler', 'ilan_id')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->unsignedBigInteger('ilan_id')
                    ->nullable()
                    ->after('kisi_id');

                $table->index('ilan_id', 'idx_talepler_ilan_id');
            });
        }

        if (! Schema::hasColumn('talepler', 'kaynak')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->string('kaynak', 100)
                    ->nullable()
                    ->after('mahalle_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('talepler', 'ilan_id')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->dropIndex('idx_talepler_ilan_id');
                $table->dropColumn('ilan_id');
            });
        }

        if (Schema::hasColumn('talepler', 'kaynak')) {
            Schema::table('talepler', function (Blueprint $table) {
                $table->dropColumn('kaynak');
            });
        }
    }
};
