<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('gorevler')) {
            Schema::table('gorevler', function (Blueprint $table) {
                if (!Schema::hasColumn('gorevler', 'lead_id')) {
                    $table->unsignedBigInteger('lead_id')->nullable()->after('kisi_id')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('gorevler')) {
            Schema::table('gorevler', function (Blueprint $table) {
                if (Schema::hasColumn('gorevler', 'lead_id')) {
                    $table->dropColumn('lead_id');
                }
            });
        }
    }
};
