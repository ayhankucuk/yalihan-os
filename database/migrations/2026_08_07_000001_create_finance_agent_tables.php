<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EX-002 Finance Agent — WAVE 1
 *
 * Creates three core tables for the Finance Agent domain:
 * - airbnb_payout_imports    : Raw Airbnb payout records ingested from the platform
 * - payout_reconciliations   : Reservation-level reconciliation results
 * - owner_payouts            : Prepared net payout per property owner
 *
 * All tables are tenant-isolated and idempotency-keyed.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. airbnb_payout_imports
        // ─────────────────────────────────────────────
        Schema::create('airbnb_payout_imports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            // Idempotency — Airbnb'nin kendi payout transaction id'si
            $table->string('airbnb_payout_id')->unique();

            // Payout dönemi
            $table->date('period_start');
            $table->date('period_end');

            // Finansal bilgiler
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('airbnb_fees', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2);
            $table->string('currency', 3)->default('TRY');

            // Ham veri (replay için)
            $table->json('raw_payload')->nullable();

            // Durum
            $table->string('import_status')->default('pending');
            // pending | processing | reconciled | failed

            $table->unsignedBigInteger('imported_by')->nullable();
            $table->timestamp('imported_at')->nullable();

            // Hata kaydı
            $table->text('error_message')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'period_start', 'period_end']);
            $table->index(['tenant_id', 'import_status']);
        });

        // ─────────────────────────────────────────────
        // 2. payout_reconciliations
        // ─────────────────────────────────────────────
        Schema::create('payout_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            // İlişkiler
            $table->unsignedBigInteger('airbnb_payout_import_id')->index();
            $table->unsignedBigInteger('reservation_id')->nullable()->index();
            $table->unsignedBigInteger('ilan_id')->nullable()->index();

            // Idempotency key
            $table->string('idempotency_key')->unique();

            // Rezervasyon finansal bilgileri
            $table->decimal('reservation_amount', 15, 2);
            $table->decimal('yalihan_commission_rate', 5, 2);
            $table->decimal('yalihan_commission_amount', 15, 2);
            $table->decimal('owner_net_amount', 15, 2);
            $table->string('currency', 3)->default('TRY');

            // Eşleştirme durumu
            $table->string('reconciliation_status')->default('pending');
            // pending | matched | unmatched | disputed | approved

            // Onay
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'reconciliation_status']);
            $table->index(['tenant_id', 'ilan_id']);

            $table->foreign('airbnb_payout_import_id')
                ->references('id')
                ->on('airbnb_payout_imports')
                ->onDelete('restrict');
        });

        // ─────────────────────────────────────────────
        // 3. owner_payouts
        // ─────────────────────────────────────────────
        Schema::create('owner_payouts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();

            // Ev sahibi
            $table->unsignedBigInteger('owner_kisi_id')->index();
            $table->unsignedBigInteger('ilan_id')->index();

            // Idempotency key
            $table->string('idempotency_key')->unique();

            // Dönem
            $table->date('period_start');
            $table->date('period_end');

            // Finansal özet
            $table->decimal('gross_rental_income', 15, 2);
            $table->decimal('total_yalihan_commission', 15, 2);
            $table->decimal('net_owner_payout', 15, 2);
            $table->string('currency', 3)->default('TRY');

            // Reconciliation sayısı
            $table->unsignedInteger('reconciliation_count')->default(0);

            // Durum
            $table->string('payout_status')->default('draft');
            // draft | pending_approval | approved | paid | cancelled

            // Onay akışı
            $table->unsignedBigInteger('prepared_by')->nullable();
            $table->timestamp('prepared_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Ödeme referansı
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'payout_status']);
            $table->index(['tenant_id', 'owner_kisi_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_payouts');
        Schema::dropIfExists('payout_reconciliations');
        Schema::dropIfExists('airbnb_payout_imports');
    }
};
