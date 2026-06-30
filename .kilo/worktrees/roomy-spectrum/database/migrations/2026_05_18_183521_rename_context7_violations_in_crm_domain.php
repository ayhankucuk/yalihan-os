<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * P1.1 CRM Vertical Slice: Context7 Violations Cleanup
 *
 * Purpose: Eliminates generic English naming violations in CRM domain
 * Scope: Atomic vertical slice (Kisi model + pivot tables)
 * SAB Compliance: Idempotent, defensive, Context7 canonical naming
 *
 * Changes:
 * 1. kisiler.email → kisiler.eposta (Context7: Turkish canonical)
 * 2. kisiler.last_contacted_at → kisiler.son_etkilesim_tarihi (Context7: Turkish canonical)
 * 3. ilan_favorileri.is_active → ilan_favorileri.aktiflik_durumu (Context7: Boolean state)
 *
 * @see P0 Schema Stabilization: 2026-05-18
 * @see Context7 Dictionary: https://context7.dev/standards/naming
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rename 'email' to 'eposta' in kisiler table
        if (Schema::hasTable('kisiler') && Schema::hasColumn('kisiler', 'email')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->renameColumn('email', 'eposta');
            });
        }

        // 2. Rename 'last_contacted_at' to 'son_etkilesim_tarihi' in kisiler table
        if (Schema::hasTable('kisiler') && Schema::hasColumn('kisiler', 'last_contacted_at')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->renameColumn('last_contacted_at', 'son_etkilesim_tarihi');
            });
        }

        // 3. Rename 'is_active' to 'aktiflik_durumu' in ilan_favorileri pivot table
        if (Schema::hasTable('ilan_favorileri') && Schema::hasColumn('ilan_favorileri', 'is_active')) {
            Schema::table('ilan_favorileri', function (Blueprint $table) {
                $table->renameColumn('is_active', 'aktiflik_durumu');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse in opposite order
        if (Schema::hasTable('ilan_favorileri') && Schema::hasColumn('ilan_favorileri', 'aktiflik_durumu')) {
            Schema::table('ilan_favorileri', function (Blueprint $table) {
                $table->renameColumn('aktiflik_durumu', 'is_active');
            });
        }

        if (Schema::hasTable('kisiler') && Schema::hasColumn('kisiler', 'son_etkilesim_tarihi')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->renameColumn('son_etkilesim_tarihi', 'last_contacted_at');
            });
        }

        if (Schema::hasTable('kisiler') && Schema::hasColumn('kisiler', 'eposta')) {
            Schema::table('kisiler', function (Blueprint $table) {
                $table->renameColumn('eposta', 'email');
            });
        }
    }
};
