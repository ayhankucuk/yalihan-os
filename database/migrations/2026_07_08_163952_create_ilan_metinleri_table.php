<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ilan_metinleri', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ilan_id')->constrained('ilanlar')->cascadeOnDelete();
            $table->string('baslik', 255)->nullable();
            $table->text('aciklama')->nullable();
            $table->string('ton', 50)->nullable();
            $table->boolean('taslak_durumu')->default(true);
            $table->tinyInteger('is_active')->default(1);
            $table->boolean('yapay_zeka_durumu')->default(false);
            $table->json('kaynak_veriler')->nullable();
            $table->timestamps();

            $table->index(['ilan_id', 'is_active']);
            $table->index(['ilan_id', 'yapay_zeka_durumu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ilan_metinleri');
    }
};
