<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B-006 P5A — ConfigOption kanonik tablo
 *
 * Kategori ve Yayın Tipi bazlı dinamik config seçenekleri.
 * Deprecated\ConfigOption ghost'unun fiziksel karşılığı.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_options', function (Blueprint $table) {
            $table->id();

            $table->string('option_key', 255)->comment('Config anahtarı, örn: oda_sayisi_options');
            $table->enum('option_type', ['simple', 'associative', 'object_array', 'nested'])
                  ->default('simple');
            $table->json('option_value')->comment('JSON formatında config değeri');

            $table->foreignId('kategori_id')
                  ->nullable()
                  ->constrained('ilan_kategorileri')
                  ->nullOnDelete();

            $table->foreignId('yayin_tipi_id')
                  ->nullable()
                  ->constrained('yayin_tipi_sablonlari')
                  ->nullOnDelete();

            $table->string('label', 255)->nullable()->comment('Yönetim paneli etiket');
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('aktiflik_durumu')->default(true);
            $table->integer('display_order')->default(0);

            $table->timestamps();

            // En spesifik eşleşme için bileşik index
            $table->index(['option_key', 'kategori_id', 'yayin_tipi_id'], 'config_opt_key_kat_yay_idx');
            $table->index(['aktiflik_durumu', 'display_order'], 'config_opt_aktif_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_options');
    }
};
