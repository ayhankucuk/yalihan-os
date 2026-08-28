<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Checkout / Ödeme Akışı — Payment Record Table
 *
 * CHECKOUT/ÖDEME AKIŞI — IMPLEMENTATION
 *
 * Rezervasyon–ödeme veri sözleşmesi:
 * - Bir rezervasyon (property_reservations) birden çok ödeme kaydına (payments) sahip olabilir.
 * - Ödeme durum makinesi: pending → paid | failed  (TransactionStatus değerleri)
 * - Ödeme sağlayıcı entegrasyonu YOK — mock / manuel onay akışı.
 * - Tenant izolasyonu: tenant_id + ulke_id (HasCountryScope).
 * - Idempotency: idempotency_key benzersiz — aynı ödemenin iki kez kaydedilmesini engeller.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            // ── Tenant / Country isolation ────────────────────────────────
            $table->foreignId('tenant_id')
                  ->comment('RULE-T1: zorunlu tenant izolasyonu')
                  ->constrained('tenants')
                  ->cascadeOnDelete();
            $table->unsignedBigInteger('ulke_id')->nullable()->index();

            // ── Reservation linkage ───────────────────────────────────────
            $table->foreignId('reservation_id')
                  ->constrained('property_reservations')
                  ->cascadeOnDelete();

            // ── Financial fields ──────────────────────────────────────────
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3)->default('TRY');
            $table->string('payment_method', 50)->default('mock')
                  ->comment('kart|eft|havale|nakit|mock — sağlayıcı entegrasyonu yok');
            $table->string('status', 20)->default('pending')
                  ->comment('pending | paid | failed (TransactionStatus)');

            // ── Reference / audit ─────────────────────────────────────────
            $table->string('reference', 100)->nullable()
                  ->comment('Banka referansı / makbuz no');
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 100)->nullable()->unique()
                  ->comment('Aynı ödemenin iki kez kaydedilmesini önler');

            // ── Who recorded / verified ───────────────────────────────────
            $table->foreignId('recorded_by')->constrained('users');
            $table->foreignId('verified_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            // ── Indexes ───────────────────────────────────────────────────
            $table->index(['tenant_id', 'reservation_id']);
            $table->index(['tenant_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};