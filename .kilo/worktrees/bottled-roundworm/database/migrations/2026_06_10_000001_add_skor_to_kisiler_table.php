<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Context7: kisiler tablosuna skor kolonu ekleme
 * getLeadSourceAnalytics() metodu avg(skor) kullandığından bu kolon zorunludur.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kisiler') && !Schema::hasColumn('kisiler', 'skor')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->unsignedSmallInteger('skor')->default(0)->after('crm_surec_asamasi')
                    ->comment('Context7: CRM lead scoring 0-100');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('kisiler') && Schema::hasColumn('kisiler', 'skor')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->dropColumn('skor');
            });
        }
    }
};
