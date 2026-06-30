<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Phase 13 Sprint 1: Context7 Schema Synchronization
 *
 * Add: aktiflik_durumu column (ilanlar table)
 * SAB §3.1: Context7 Kanonik İsimlendirme
 *
 * Note: is_active kolonu zaten yok, sadece yeni kolon ekliyoruz
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Yeni kanonik kolonu ekle
        Schema::table('ilanlar', function (Blueprint $table) {
            if (!Schema::hasColumn('ilanlar', 'aktiflik_durumu')) {
                $table->unsignedTinyInteger('aktiflik_durumu')
                    ->default(1)
                    ->after('id')
                    ->comment('Context7 kanonik aktiflik durumu (1=aktif, 0=pasif)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kolonu düşür
        Schema::table('ilanlar', function (Blueprint $table) {
            if (Schema::hasColumn('ilanlar', 'aktiflik_durumu')) {
                $table->dropColumn('aktiflik_durumu');
            }
        });
    }
};
