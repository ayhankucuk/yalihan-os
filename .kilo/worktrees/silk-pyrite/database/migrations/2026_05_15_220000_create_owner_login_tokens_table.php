<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Portal — OTP / Magic-link token tablosu
 *
 * Mülk sahibi (owner) kullanıcılar email veya SMS ile
 * tek kullanımlık token alarak /owner portalına giriş yapar.
 * Şifre zorunluluğu yoktur; token 15 dakika geçerlidir.
 *
 * RULE-T1: tenant_id zorunludur.
 * Context7: Tüm alan isimleri Türkçedir.
 * SAB v6.1.2 — Owner Portal sprint.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_login_tokens', function (Blueprint $table) {
            $table->id();

            // RULE-T1: tenant izolasyonu
            $table->foreignId('tenant_id')
                  ->comment('RULE-T1: zorunlu tenant izolasyonu')
                  ->constrained('tenants')->cascadeOnDelete();

            // Hangi User'a ait (owner role'ü olan)
            $table->foreignId('user_id')
                  ->constrained('users')->cascadeOnDelete();

            // Token değeri (SHA-256 hash olarak saklanır)
            $table->string('token_hash', 64)->unique();

            // Giriş kanalı: 'email' veya 'sms'
            $table->string('giris_kanali', 10)->default('email');

            // Token kaç dakika geçerli
            $table->timestamp('gecerlilik_bitis')->nullable();

            // Kullanıldı mı?
            $table->boolean('kullanildi')->default(false);

            // Kullanıldığı IP
            $table->string('kullanilan_ip', 45)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'kullanildi']);
            $table->index('gecerlilik_bitis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_login_tokens');
    }
};
