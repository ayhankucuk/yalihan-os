<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * T-UPS-V2-FULL (2026-06-24)
 *
 * ilanlar tablosuna ekstra_ozellikler JSON kolonu ekler.
 * Amaç: Kategori/dikey bazlı dinamik alanları (imar durumu, sezon bilgisi,
 * ticari detaylar vb.) tek bir kanonik JSON kolonunda toplamak.
 *
 * Context7: ekstra_ozellikler (canonical) — metadata ile karıştırılmamalı.
 * metadata: sistem meta verisi (SEO, portal sync)
 * ekstra_ozellikler: domain/kategori bazlı dinamik form alanları
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            $table->json('ekstra_ozellikler')
                ->nullable()
                ->after('metadata')
                ->comment('T-UPS-V2-FULL: Kategori bazlı dinamik alan deposu (JSON). metadata ile karıştırılmamalı.');
        });
    }

    public function down(): void
    {
        Schema::table('ilanlar', function (Blueprint $table) {
            $table->dropColumn('ekstra_ozellikler');
        });
    }
};
