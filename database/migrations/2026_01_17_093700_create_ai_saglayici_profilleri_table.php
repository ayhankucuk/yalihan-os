<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ai_saglayici_profilleri')) {
            return;
        }

        Schema::create('ai_saglayici_profilleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->unsignedBigInteger('yayin_tipi_id')->nullable();
            $table->string('saglayici', 40);
            $table->unsignedInteger('ort_gecikme_ms')->default(0);
            $table->decimal('ort_maliyet_usd', 8, 6)->default(0);
            $table->decimal('kabul_orani', 5, 2)->default(0)->comment('0.00-100.00');
            $table->unsignedInteger('ornek_sayisi')->default(0);
            $table->timestamps();

            $table->unique(['kategori_id', 'yayin_tipi_id', 'saglayici'], 'idx_provider_unique');
            $table->index('kategori_id', 'ai_saglayici_profilleri_kategori_id_index');
            $table->index('yayin_tipi_id', 'ai_saglayici_profilleri_yayin_tipi_id_index');
            $table->index('saglayici', 'ai_saglayici_profilleri_saglayici_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_saglayici_profilleri');
    }
};
