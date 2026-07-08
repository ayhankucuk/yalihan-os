<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ilan_fotograflari', function (Blueprint $table) {
            $table->boolean('kapak_fotografi')->default(false)->after('media_data');
        });
    }

    public function down(): void
    {
        Schema::table('ilan_fotograflari', function (Blueprint $table) {
            $table->dropColumn('kapak_fotografi');
        });
    }
};
