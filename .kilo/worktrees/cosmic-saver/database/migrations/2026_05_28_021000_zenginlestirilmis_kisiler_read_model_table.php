<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Creates the query-optimized, enriched multi-tenant read model for Kisi (Contact) domain.
     */
    public function up(): void
    {
        if (!Schema::hasTable('kisiler_read_model')) {
            Schema::create('kisiler_read_model', function (Blueprint $table) {
                $table->id();
                
                // SAB Madde 16: Katı Çoklu Kiracı İzolasyon Kısıtı
                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->onDelete('restrict');

                $table->string('uuid', 36)->unique();
                $table->string('ad_soyad');
                $table->string('telefon_numarasi', 30);
                $table->string('eposta_adresi')->nullable();
                
                // Zenginleştirilmiş Domain Analiz Alanları
                $table->string('musteri_segmenti', 50)->default('Standart'); // Potansiyel, VIP, Karaliste
                $table->json('iletisim_tercihleri')->nullable(); // SMS, WhatsApp, Arama izin matrisi
                $table->boolean('kimlik_dogrulama_durumu')->default(false);
                $table->boolean('aktiflik_durumu')->default(true);
                
                // Idempotency Zırhı: Kuyruk gecikmelerinde mükerrer işlemeyi (out-of-order) engeller
                $table->unsignedBigInteger('son_islenen_sira_numarasi')->default(0);
                
                $table->string('olusturulma_zamani');
                $table->string('degistirilme_zamani')->nullable();

                // CQRS ve Çoklu Kiracı Arama Performansı İndeks Kalkanı
                $table->index(['tenant_id', 'aktiflik_durumu', 'musteri_segmenti'], 'idx_tenant_aktif_segment');
                $table->index(['uuid']);
                $table->index(['telefon_numarasi']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kisiler_read_model');
    }
};
