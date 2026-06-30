<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Context7 Compliance Migration
 *
 * Context7 Standardı: C7-KOMISYON-MIGRATION-2025-11-26
 * Split Commission Support: C7-SPLIT-COMMISSION-2025-11-25
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('komisyonlar', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('ilan_id')->constrained('ilanlar')->onDelete('cascade');
            $table->foreignId('kisi_id')->constrained('kisiler')->onDelete('cascade'); // Context7: kisi_id → kisi_id
            $table->foreignId('danisman_id')->nullable()->constrained('users')->onDelete('set null'); // Deprecated - backward compatibility

            // Split Commission Fields (Context7: C7-SPLIT-COMMISSION-2025-11-25)
            $table->foreignId('satici_danisman_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('alici_danisman_id')->nullable()->constrained('users')->onDelete('set null');

            // Komisyon Bilgileri
            $table->string('komisyon_tipi', 50)->comment('satis, kiralama, danismanlik');
            $table->decimal('komisyon_orani', 5, 2)->default(0);
            $table->decimal('komisyon_tutari', 15, 2)->default(0);

            // Split Commission Fields
            $table->decimal('satici_komisyon_orani', 5, 2)->nullable();
            $table->decimal('alici_komisyon_orani', 5, 2)->nullable();
            $table->decimal('satici_komisyon_tutari', 15, 2)->nullable();
            $table->decimal('alici_komisyon_tutari', 15, 2)->nullable();

            // Fiyat ve Para Birimi
            $table->decimal('ilan_fiyati', 15, 2);
            $table->string('para_birimi', 3)->default('TRY');

            // Tarihler
            $table->date('hesaplama_tarihi')->nullable();
            $table->date('odeme_tarihi')->nullable();

            // Context7: status field (hesaplandi, onaylandi, odendi)
            $table->string('odeme_statusu', 20)->default('hesaplandi')->comment('Ödeme durumu (Context7 standard)');

            // Notlar
            $table->text('notlar')->nullable();

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('ilan_id');
            $table->index('kisi_id');
            $table->index('danisman_id');
            $table->index('satici_danisman_id');
            $table->index('alici_danisman_id');
            $table->index('komisyon_tipi');
            $table->index('odeme_statusu');
            $table->index('hesaplama_tarihi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('komisyonlar');
    }
};
