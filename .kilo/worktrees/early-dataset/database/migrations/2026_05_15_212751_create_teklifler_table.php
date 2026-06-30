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
        Schema::create('teklifler', function (Blueprint $table) {
            $table->id();
            
            // İlişkiler
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('ilan_id')->constrained('ilanlar')->onDelete('cascade');
            $table->foreignId('kisi_id')->constrained('kisiler')->onDelete('cascade');
            
            // Teklif Detayları
            $table->decimal('teklif_tutari', 15, 2);
            $table->string('para_birimi', 3)->default('TRY');
            $table->string('teklif_durumu')->default('beklemede'); // beklemede, kabul_edildi, reddedildi, vs.
            $table->text('mesaj')->nullable();
            
            // Geçerlilik ve Zamanlar
            $table->dateTime('gecerlilik_tarihi')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teklifler');
    }
};
