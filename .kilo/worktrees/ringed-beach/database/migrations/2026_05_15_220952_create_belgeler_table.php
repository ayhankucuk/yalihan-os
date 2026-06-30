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
        Schema::create('belgeler', function (Blueprint $table) {
            $table->id();
            
            // İlişkiler
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('ilan_id')->nullable()->constrained('ilanlar')->onDelete('set null');
            
            // Dosya Bilgileri
            $table->string('baslik');
            $table->string('dosya_yolu');
            $table->string('dosya_tipi', 10); // pdf, doc, jpeg vb.
            $table->string('belge_turu')->default('diger'); // tapu, sozlesme, fatura, kimlik, diger
            $table->integer('boyut_kb')->default(0);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('belgeler');
    }
};
