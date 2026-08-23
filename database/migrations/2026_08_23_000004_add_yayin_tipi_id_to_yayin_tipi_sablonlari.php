<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * P1LOT-01-003 Recovery — yayin_tipi_id ekleme
     *
     * MySQL'de mevcut ama SQLite (test DB) eksik olan sütunu ekler.
     *
     * yayin_tipi_sablonlari.yayin_tipi_id → yayin_tipleri.id FK.
     * YayinTipiSablonu modeli bu sütunu kullanıyor.
     */
    public function up(): void
    {
        if (Schema::hasTable('yayin_tipi_sablonlari')
            && !Schema::hasColumn('yayin_tipi_sablonlari', 'yayin_tipi_id')) {
            Schema::table('yayin_tipi_sablonlari', function (Blueprint $table) {
                $table->unsignedBigInteger('yayin_tipi_id')->nullable()->after('kategori_id');
                $table->foreign('yayin_tipi_id')
                    ->references('id')->on('yayin_tipleri')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('yayin_tipi_sablonlari')
            && Schema::hasColumn('yayin_tipi_sablonlari', 'yayin_tipi_id')) {
            Schema::table('yayin_tipi_sablonlari', function (Blueprint $table) {
                $table->dropForeign(['yayin_tipi_id']);
                $table->dropColumn('yayin_tipi_id');
            });
        }
    }
};
