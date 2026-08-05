<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * RESERVATION_CORE Phase 1: property_reservations tablosuna ilan_id sütunu ekle
 *
 * Problem: PropertyReservation modeli ilan_id kullanıyor ama tablo yapısında yok.
 * Bu migration SQLite test veritabanı için gereklidir.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('property_reservations', 'ilan_id')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->unsignedBigInteger('ilan_id')->nullable()->after('tenant_id');
                $table->foreign('ilan_id')->references('id')->on('ilanlar')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('property_reservations', 'ilan_id')) {
            Schema::table('property_reservations', function (Blueprint $table) {
                $table->dropForeign(['ilan_id']);
                $table->dropColumn('ilan_id');
            });
        }
    }
};
