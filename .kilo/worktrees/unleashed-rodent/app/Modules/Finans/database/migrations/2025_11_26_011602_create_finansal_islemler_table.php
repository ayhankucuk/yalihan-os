<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Context7 Compliance Migration
 *
 * Context7 Standardı: C7-FINANS-MIGRATION-2025-11-26
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('finansal_islemler', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('ilan_id')->nullable()->constrained('ilanlar')->onDelete('set null');
            $table->foreignId('kisi_id')->nullable()->constrained('kisiler')->onDelete('set null'); // Context7: kisi_id → kisi_id
            $table->foreignId('gorev_id')->nullable()->constrained('gorevler')->onDelete('set null');
            $table->foreignId('onaylayan_id')->nullable()->constrained('users')->onDelete('set null');

            // İşlem Bilgileri
            $table->string('islem_tipi', 50)->comment('komisyon, odeme, masraf, gelir, gider');
            $table->decimal('miktar', 15, 2);
            $table->string('para_birimi', 3)->default('TRY');
            $table->text('aciklama')->nullable();
            $table->date('tarih');

            // Context7: status field (bekliyor, onaylandi, reddedildi, tamamlandi)
            $table->string('islem_statusu', 20)->default('bekliyor')->comment('İşlem durumu (Context7 standard)');

            // Onay Bilgileri
            $table->timestamp('onay_tarihi')->nullable();

            // Referans Bilgileri
            $table->string('referans_no', 100)->nullable();
            $table->string('fatura_no', 100)->nullable();
            $table->text('notlar')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('ilan_id');
            $table->index('kisi_id');
            $table->index('islem_statusu');
            $table->index('islem_tipi');
            $table->index('tarih');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('finansal_islemler');
    }
};
