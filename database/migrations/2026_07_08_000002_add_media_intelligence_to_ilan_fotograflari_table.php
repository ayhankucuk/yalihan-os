<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ilan_fotograflari', function (Blueprint $table) {
            $table->string('oda_turu', 30)->nullable()->after('aciklama');
            $table->float('oda_turu_guven')->nullable()->after('oda_turu');
            $table->unsignedTinyInteger('kalite_puani')->nullable()->after('oda_turu_guven');
            $table->json('kalite_ayrinti')->nullable()->after('kalite_puani');
            $table->float('hero_skoru')->nullable()->after('kalite_ayrinti');
            $table->json('media_data')->nullable()->after('hero_skoru');
        });
    }

    public function down(): void
    {
        Schema::table('ilan_fotograflari', function (Blueprint $table) {
            $table->dropColumn([
                'oda_turu', 'oda_turu_guven', 'kalite_puani',
                'kalite_ayrinti', 'hero_skoru', 'media_data',
            ]);
        });
    }
};
