<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ilan_fotograflari — vision_data
        Schema::table('ilan_fotograflari', function (Blueprint $table) {
            $table->json('vision_data')->nullable()->after('media_data');
        });

        // ilanlar — AI Vision Intelligence
        Schema::table('ilanlar', function (Blueprint $table) {
            $table->unsignedTinyInteger('vision_score')->nullable()->after('eksik_odalar');
            $table->decimal('vision_ai_confidence', 4, 3)->nullable()->after('vision_score');
            $table->json('vision_rooms')->nullable()->after('vision_ai_confidence');
            $table->json('vision_amenities')->nullable()->after('vision_rooms');
            $table->json('vision_luxury')->nullable()->after('vision_amenities');
            $table->json('vision_media')->nullable()->after('vision_luxury');
        });
    }

    public function down(): void
    {
        Schema::table('ilan_fotograflari', function (Blueprint $table) {
            $table->dropColumn('vision_data');
        });

        Schema::table('ilanlar', function (Blueprint $table) {
            $table->dropColumn([
                'vision_score',
                'vision_ai_confidence',
                'vision_rooms',
                'vision_amenities',
                'vision_luxury',
                'vision_media',
            ]);
        });
    }
};
