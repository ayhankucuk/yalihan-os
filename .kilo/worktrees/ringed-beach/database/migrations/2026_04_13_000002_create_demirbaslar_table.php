<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demirbaslar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulke_id')->nullable()->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('brand')->nullable();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('kategori_id')->nullable()->index();
            $table->unsignedBigInteger('ilan_kategori_id')->nullable()->index();
            $table->unsignedBigInteger('yayin_tipi_id')->nullable()->index();
            $table->tinyInteger('aktiflik_durumu')->default(1)->index();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Pivot: ilan_demirbas
        Schema::create('ilan_demirbas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ilan_id')->index();
            $table->unsignedBigInteger('demirbas_id')->index();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->tinyInteger('aktiflik_durumu')->default(1);
            $table->timestamps();

            $table->unique(['ilan_id', 'demirbas_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ilan_demirbas');
        Schema::dropIfExists('demirbaslar');
    }
};
