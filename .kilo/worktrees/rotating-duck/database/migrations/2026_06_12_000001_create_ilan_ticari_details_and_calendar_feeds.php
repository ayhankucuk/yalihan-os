<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B-006 P5D: Ghost model resolution
 *
 * Oluşturulan tablolar:
 *   - ilan_ticari_details  (Deprecated\IlanTicariDetail ghost'unun gerçek tablosu)
 *   - ilan_calendar_feeds  (Deprecated\IlanCalendarFeed ghost'unun gerçek tablosu)
 *
 * NOT: property_calendar_feeds AYRI bir tablo — Airbnb/Booking iCal sync içindir.
 *      ilan_calendar_feeds → Token tabanlı outbound ICS feed (IlanCalendarIcsService)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ilan_ticari_details — İşyeri/Ticari ilan dikey detay tablosu
        if (! Schema::hasTable('ilan_ticari_details')) {
            Schema::create('ilan_ticari_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ilan_id')
                    ->unique()
                    ->constrained('ilanlar')
                    ->cascadeOnDelete();
                $table->string('isyeri_tipi')->nullable();     // ofis, dükkan, fabrika, depo, vb.
                $table->text('kira_bilgisi')->nullable();      // serbest metin kira detayı
                $table->decimal('kira_getirisi', 10, 2)->nullable(); // aylık kira getirisi
                $table->integer('kat_adedi')->nullable();      // toplam kat sayısı
                $table->integer('ofis_adedi')->nullable();     // bağımsız bölüm sayısı
                $table->boolean('asansor_var')->default(false);
                $table->boolean('depo_var')->default(false);
                $table->boolean('otopark_var')->default(false);
                $table->json('ek_bilgiler')->nullable();       // genişletilebilir alan
                $table->timestamps();
            });
        }

        // ilan_calendar_feeds — Token tabanlı outbound ICS feed tablosu
        if (! Schema::hasTable('ilan_calendar_feeds')) {
            Schema::create('ilan_calendar_feeds', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ilan_id')
                    ->constrained('ilanlar')
                    ->cascadeOnDelete();
                $table->string('token', 64)->unique();         // güvenli rastgele token
                $table->boolean('aktiflik_durumu')->default(true);
                $table->foreignId('created_by_user_id')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
                $table->timestamp('revoked_at')->nullable();   // iptal zamanı
                $table->timestamps();

                $table->index(['ilan_id', 'aktiflik_durumu']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ilan_calendar_feeds');
        Schema::dropIfExists('ilan_ticari_details');
    }
};
