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
        Schema::create('mesajlar', function (Blueprint $table) {
            $table->id();
            
            // Gönderen ve Alıcı (Her ikisi de users tablosundan, çünkü Owner da User üzerinden login oluyor)
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('gonderen_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('alici_id')->constrained('users')->onDelete('cascade');
            
            // Hangi ilanla ilgili olduğu (opsiyonel, genel mesaj da atabilir)
            $table->foreignId('ilan_id')->nullable()->constrained('ilanlar')->onDelete('set null');
            
            $table->text('icerik');
            $table->boolean('okundu_mu')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mesajlar');
    }
};
