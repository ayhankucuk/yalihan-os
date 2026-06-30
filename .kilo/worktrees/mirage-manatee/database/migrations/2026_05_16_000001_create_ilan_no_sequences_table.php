<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * İlan numarası otomatik üretim için sequence tablosu.
     * 
     * Her tip+kategori+yıl kombinasyonu için ayrı sayaç tutar.
     * Race condition'dan korunmak için lockForUpdate() kullanılır.
     * 
     * Format: {TIP}-{KATEGORİ}-{YIL}-{SIRA}
     * Örnek: STL-DRE-2024-001
     */
    public function up(): void
    {
        Schema::create('ilan_no_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('tip_kodu', 3)->comment('STL, KRL, YZL, GNL');
            $table->string('kategori_kodu', 3)->comment('DRE, VLA, ARS, ISY, KNT, TCR');
            $table->integer('yil')->comment('2024, 2025, ...');
            $table->integer('son_sira')->default(0)->comment('Son kullanılan sıra numarası');
            $table->timestamps();
            
            // Her tip+kategori+yıl kombinasyonu benzersiz olmalı
            $table->unique(['tip_kodu', 'kategori_kodu', 'yil'], 'idx_sequence_unique');
            
            // Hızlı arama için index
            $table->index(['tip_kodu', 'kategori_kodu', 'yil'], 'idx_sequence_lookup');
        });

        // ilanlar tablosuna index ekle (ilan_no zaten var)
        Schema::table('ilanlar', function (Blueprint $table) {
            // Hızlı arama için index
            if (!Schema::hasIndex('ilanlar', 'idx_ilan_no')) {
                $table->index('ilan_no', 'idx_ilan_no');
            }
            
            // Benzersizlik için unique constraint (opsiyonel - veri temizliği sonrası)
            // $table->unique('ilan_no', 'idx_ilan_no_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            $table->dropIndex('idx_ilan_no');
            // $table->dropUnique('idx_ilan_no_unique');
        });
        
        Schema::dropIfExists('ilan_no_sequences');
    }
};
