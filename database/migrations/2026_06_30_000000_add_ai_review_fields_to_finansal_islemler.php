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
        Schema::table('finansal_islemler', function (Blueprint $table) {
            $table->boolean('ai_inceleme_gerekli')->default(false)->after('notlar');
            $table->string('ai_modeli')->nullable()->after('ai_inceleme_gerekli');
            $table->string('ai_saglayici')->nullable()->after('ai_modeli');
            $table->string('ai_dogrulama_durumu')->nullable()->after('ai_saglayici');
            $table->text('ai_hata_sebebi')->nullable()->after('ai_dogrulama_durumu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finansal_islemler', function (Blueprint $table) {
            $table->dropColumn([
                'ai_inceleme_gerekli',
                'ai_modeli',
                'ai_saglayici',
                'ai_dogrulama_durumu',
                'ai_hata_sebebi',
            ]);
        });
    }
};
