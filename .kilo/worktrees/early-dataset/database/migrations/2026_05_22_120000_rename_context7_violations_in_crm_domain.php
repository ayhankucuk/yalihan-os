<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CRM Domain Context7 Compliance Migration
 *
 * Oturum 32 (2026-05-22): Hybrid Vertical Slicing Strategy
 *
 * Scope:
 * 1. kisiler.email → kisiler.eposta
 * 2. kisiler.last_contacted_at → kisiler.son_etkilesim_tarihi
 * 3. ilan_favorileri.aktiflik_durumu (yeni kolon ekleme)
 *
 * Rationale:
 * - Context7 Türkçe naming standardına uyum
 * - CRM ve Property Hub domain'leri arasında pivot tablo izolasyonu
 * - Zero Trust prensibi: Fail-Loud ile regresyon önleme
 *
 * SAB Authority: .sab/authority.json
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. kisiler tablosu: email → eposta
        if (Schema::hasTable('kisiler')) {
            Schema::table('kisiler', function (Blueprint $table) {
                if (Schema::hasColumn('kisiler', 'email') && !Schema::hasColumn('kisiler', 'eposta')) {
                    $table->renameColumn('email', 'eposta');
                }

                if (Schema::hasColumn('kisiler', 'last_contacted_at') && !Schema::hasColumn('kisiler', 'son_etkilesim_tarihi')) {
                    $table->renameColumn('last_contacted_at', 'son_etkilesim_tarihi');
                }
            });
        }

        // 2. ilan_favorileri tablosu: aktiflik_durumu kolonu ekleme
        if (Schema::hasTable('ilan_favorileri')) {
            Schema::table('ilan_favorileri', function (Blueprint $table) {
                if (!Schema::hasColumn('ilan_favorileri', 'aktiflik_durumu')) {
                    $table->boolean('aktiflik_durumu')->default(true)->after('ilan_id');
                    $table->index('aktiflik_durumu', 'idx_ilan_favorileri_aktiflik');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. kisiler tablosu: eposta → email (rollback)
        if (Schema::hasTable('kisiler')) {
            Schema::table('kisiler', function (Blueprint $table) {
                if (Schema::hasColumn('kisiler', 'eposta') && !Schema::hasColumn('kisiler', 'email')) {
                    $table->renameColumn('eposta', 'email');
                }

                if (Schema::hasColumn('kisiler', 'son_etkilesim_tarihi') && !Schema::hasColumn('kisiler', 'last_contacted_at')) {
                    $table->renameColumn('son_etkilesim_tarihi', 'last_contacted_at');
                }
            });
        }

        // 2. ilan_favorileri tablosu: aktiflik_durumu kolonu kaldırma
        if (Schema::hasTable('ilan_favorileri')) {
            Schema::table('ilan_favorileri', function (Blueprint $table) {
                if (Schema::hasColumn('ilan_favorileri', 'aktiflik_durumu')) {
                    $table->dropIndex('idx_ilan_favorileri_aktiflik');
                    $table->dropColumn('aktiflik_durumu');
                }
            });
        }
    }
};
