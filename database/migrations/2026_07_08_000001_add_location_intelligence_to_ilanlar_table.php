<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            $table->json('location_data')->nullable()->after('lng');
            $table->unsignedTinyInteger('location_score')->nullable()->after('location_data');
            $table->enum('location_score_confidence', ['HIGH', 'MEDIUM', 'LOW', 'VERY_LOW'])
                  ->nullable()
                  ->after('location_score');
            $table->timestamp('location_analyzed_at')->nullable()->after('location_score_confidence');
        });
    }

    public function down(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            $table->dropColumn(['location_data', 'location_score', 'location_score_confidence', 'location_analyzed_at']);
        });
    }
};
