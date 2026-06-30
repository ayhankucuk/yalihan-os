<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B-006 P5F — DemirbasKategori + Communication kanonik tablolar (yeni bulgular)
 *
 * demirbas_kategorileri : Demirbas kategori hiyerarşisi
 * communications        : Çok kanallı iletişim kaydı (polimorfik)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // demirbas_kategorileri
        // ---------------------------------------------------------------
        Schema::create('demirbas_kategorileri', function (Blueprint $table) {
            $table->id();
            $table->string('ad', 255);
            $table->string('slug', 255)->unique()->nullable();
            $table->text('aciklama')->nullable();
            $table->boolean('aktiflik_durumu')->default(true);
            $table->integer('display_order')->default(0);
            $table->timestamps();

            $table->index(['aktiflik_durumu', 'display_order'], 'dk_aktif_order_idx');
        });

        // ---------------------------------------------------------------
        // communications — çok kanallı iletişim (polimorfik)
        // ---------------------------------------------------------------
        Schema::create('communications', function (Blueprint $table) {
            $table->id();

            // Polimorfik ilişki (Ilan, Kisi, vb.)
            $table->nullableMorphs('communicable');

            $table->string('channel', 50)->comment('telegram, whatsapp, instagram, email, web');
            $table->text('message');
            $table->string('sender_name', 255)->nullable();
            $table->string('sender_phone', 50)->nullable();
            $table->string('sender_email', 255)->nullable();
            $table->string('sender_instagram', 100)->nullable();
            $table->string('sender_id', 255)->nullable()->comment('Kanal bazlı unique sender ID');
            $table->json('ai_analysis')->nullable()->comment('AI analiz sonuçları');
            $table->string('reply_durumu', 30)->default('bekliyor')
                  ->comment('bekliyor, cevaplandi, arşivlendi');
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index(['channel', 'reply_durumu'], 'comm_channel_reply_idx');
            $table->index('sender_id', 'comm_sender_id_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communications');
        Schema::dropIfExists('demirbas_kategorileri');
    }
};
