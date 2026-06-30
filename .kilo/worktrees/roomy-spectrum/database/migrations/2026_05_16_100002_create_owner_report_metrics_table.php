<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Report Metrics — Mülk sahibi periyodik özet metrikleri
 *
 * Context7 Standard: C7-OWNER-METRIC-READ-MODEL-V1
 * SAB: v6.1.1 | Owner Portal D16 | Task #19
 *
 * Aggregate read model. Her satır bir ilan + periyot kombinasyonudur.
 * Nightly job veya event ile doldurulur. Doğrudan yazma yasak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_report_metrics', function (Blueprint $table) {
            $table->id();

            // ── Kapsam ────────────────────────────────────────────────
            $table->unsignedBigInteger('tenant_id')->index()
                ->comment('Tenant izolasyonu');
            $table->foreignId('owner_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->foreignId('ilan_id')
                ->nullable()
                ->constrained('ilanlar')
                ->nullOnDelete()
                ->comment('Null ise tüm ilanların toplamı');

            // ── Periyot ───────────────────────────────────────────────
            $table->string('periyot_tipi', 20)->default('aylik')
                ->comment('gunluk | haftalik | aylik | yillik');
            $table->string('periyot_degeri', 20)
                ->comment('Örn: 2026-05 (aylık), 2026-W20 (haftalık), 2026 (yıllık)');

            // ── Finansal Metrikler ─────────────────────────────────────
            $table->decimal('toplam_gelir', 15, 2)->default(0);
            $table->decimal('toplam_gider', 15, 2)->default(0);
            $table->decimal('net_kar', 15, 2)->default(0);
            $table->string('para_birimi', 3)->default('TRY');

            // ── Performans Metrikleri ──────────────────────────────────
            $table->decimal('doluluk_orani', 5, 2)->default(0)
                ->comment('0.00 - 100.00 arası yüzde');
            $table->unsignedInteger('rezervasyon_sayisi')->default(0);
            $table->unsignedInteger('teklif_sayisi')->default(0);
            $table->unsignedInteger('goruntulenme_sayisi')->default(0);

            // ── Genel ─────────────────────────────────────────────────
            $table->string('metric_name')->nullable()
                ->comment('UI\'da gösterilecek metrik başlığı');
            $table->string('metric_value')->nullable()
                ->comment('UI\'da gösterilecek formatlanmış değer');

            $table->timestamps();

            // ── İndeksler ─────────────────────────────────────────────
            $table->index(['tenant_id', 'owner_id'], 'idx_metrics_tenant_owner');
            $table->index(['owner_id', 'periyot_tipi', 'periyot_degeri'], 'idx_metrics_owner_period');
            $table->unique(['owner_id', 'ilan_id', 'periyot_tipi', 'periyot_degeri'], 'uq_metrics_owner_ilan_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_report_metrics');
    }
};
