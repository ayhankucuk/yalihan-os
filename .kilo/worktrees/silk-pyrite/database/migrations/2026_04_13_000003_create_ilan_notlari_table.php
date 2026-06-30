<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ilan_notlari', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulke_id')->nullable()->index();
            $table->unsignedBigInteger('ilan_id')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->text('not_icerigi');
            $table->string('not_tipi')->default('genel')->index(); // genel, pitch, ai, sistem
            $table->boolean('onemli_mi')->default(false);
            $table->boolean('is_ai_generated')->default(false)->index();
            $table->string('channel')->nullable(); // web, api, bot
            $table->timestamps();

            $table->index(['ilan_id', 'not_tipi']);
            $table->index(['ilan_id', 'is_ai_generated']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ilan_notlari');
    }
};
