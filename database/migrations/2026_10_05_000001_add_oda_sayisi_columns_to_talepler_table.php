<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ayhan Human Gate Approval: CRM-03 remediation
     * Adds room count criteria columns to talepler table.
     *
     * Backward compatible: nullable columns, no data destruction.
     */
    public function up(): void
    {
        Schema::table('talepler', function (Blueprint $table) {
            if (!Schema::hasColumn('talepler', 'min_oda_sayisi')) {
                $table->unsignedTinyInteger('min_oda_sayisi')
                    ->nullable()
                    ->comment('Minimum room count criterion for matching')
                    ->after('max_metrekare');
            }

            if (!Schema::hasColumn('talepler', 'max_oda_sayisi')) {
                $table->unsignedTinyInteger('max_oda_sayisi')
                    ->nullable()
                    ->comment('Maximum room count criterion for matching')
                    ->after('min_oda_sayisi');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('talepler', function (Blueprint $table) {
            if (Schema::hasColumn('talepler', 'max_oda_sayisi')) {
                $table->dropColumn('max_oda_sayisi');
            }
            if (Schema::hasColumn('talepler', 'min_oda_sayisi')) {
                $table->dropColumn('min_oda_sayisi');
            }
        });
    }
};
