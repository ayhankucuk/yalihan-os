<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            $table->unsignedTinyInteger('media_health_score')->nullable()->after('location_score_confidence');
            $table->unsignedTinyInteger('media_quality_score')->nullable()->after('media_health_score');
            $table->unsignedTinyInteger('media_tamamlanma_oran')->nullable()->after('media_quality_score');
            $table->json('eksik_odalar')->nullable()->after('media_tamamlanma_oran');
            $table->foreignId('hero_fotograf_id')
                  ->nullable()
                  ->after('eksik_odalar')
                  ->constrained('ilan_fotograflari')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            $table->dropForeign(['hero_fotograf_id']);
            $table->dropColumn([
                'media_health_score', 'media_quality_score',
                'media_tamamlanma_oran', 'eksik_odalar', 'hero_fotograf_id',
            ]);
        });
    }
};
