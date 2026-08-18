<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ilan_takvim_sync', function (Blueprint $table) {
            // Token-based auth fields (ADR-009 §4)
            // Migration: api_key/api_secret → token_access/refresh/expires
            $table->string('token_access', 1000)->nullable()->after('api_secret');
            $table->string('token_refresh', 500)->nullable()->after('token_access');
            $table->timestamp('token_expires_at')->nullable()->after('token_refresh');
        });
    }

    public function down(): void
    {
        Schema::table('ilan_takvim_sync', function (Blueprint $table) {
            $table->dropColumn(['token_access', 'token_refresh', 'token_expires_at']);
        });
    }
};
