<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ✅ FIX (Oturum 39-B): ai_logs.endpoint nullable yapıldı.
 * IntelligenceHub insert'te endpoint göndermiyor — NOT NULL constraint hatası veriyordu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_logs', function (Blueprint $table) {
            $table->string('endpoint', 100)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('ai_logs', function (Blueprint $table) {
            $table->string('endpoint', 100)->nullable(false)->change();
        });
    }
};
