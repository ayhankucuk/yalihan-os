<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Report Rows — Mülk sahibi aktivite geçmişi (read-only projeksiyon)
 *
 * Context7 Standard: C7-OWNER-REPORT-READ-MODEL-V1
 * SAB: v6.1.1 | Owner Portal D16 | Task #19
 *
 * Her satır bir aktivite kaydıdır: kira ödemesi, danışman ziyareti,
 * teklif alımı, belge işlemi vb. Insert-only, update yasak.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_report_rows', function (Blueprint $table) {
            $table->id();

            // ── Kapsam (Tenant & Sahiplik) ─────────────────────────────
            $table->unsignedBigInteger('tenant_id')->index()
                ->comment('Tenant izolasyonu — cross-tenant erişim yasak');
            $table->foreignId('owner_id')
                ->constrained('users')
                ->onDelete('cascade')
                ->comment('Mülk sahibi (owner rolüne sahip user)');
            $table->foreignId('ilan_id')
                ->nullable()
                ->constrained('ilanlar')
                ->nullOnDelete()
                ->comment('İlgili ilan — null ise genel hesap aktivitesi');

            // ── Aktivite Detayı ────────────────────────────────────────
            $table->string('islem_tipi', 50)
                ->comment('kira_odemesi | danisman_ziyareti | teklif_alindi | belge_yuklendi | genel');
            $table->date('kayit_tarihi')
                ->comment('Aktivitenin gerçekleştiği tarih (Context7: kayit_tarihi)');
            $table->decimal('tutar', 15, 2)->nullable()
                ->comment('İşlem tutarı (ödeme varsa)');
            $table->string('para_birimi', 3)->default('TRY');
            $table->text('aciklama')->nullable()
                ->comment('İnsan okunabilir aktivite açıklaması');
            $table->string('durum_kodu', 30)->default('basarili')
                ->comment('basarili | beklemede | iptal | islemde (Context7: durum_kodu)');

            // ── Ek Veri ───────────────────────────────────────────────
            $table->json('metadata')->nullable()
                ->comment('Aktiviteye özel ek veri (esnek yapı)');

            $table->timestamps();

            // ── İndeksler ─────────────────────────────────────────────
            $table->index(['tenant_id', 'owner_id'], 'idx_report_rows_tenant_owner');
            $table->index(['owner_id', 'kayit_tarihi'], 'idx_report_rows_owner_date');
            $table->index(['ilan_id', 'kayit_tarihi'], 'idx_report_rows_ilan_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_report_rows');
    }
};
