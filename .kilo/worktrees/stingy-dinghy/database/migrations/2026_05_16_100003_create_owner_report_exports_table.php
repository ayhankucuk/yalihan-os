<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Report Exports — Export talep takip tablosu
 *
 * Context7 Standard: C7-OWNER-EXPORT-TRACKER-V1
 * SAB: v6.1.1 | Owner Portal D16 | Task #19
 *
 * Her satır bir export talebidir. OwnerReportExportJob tarafından işlenir.
 * islem_durumu: bekliyor → isleniyor → tamamlandi | hata
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_report_exports', function (Blueprint $table) {
            $table->id();

            // ── Kapsam ────────────────────────────────────────────────
            $table->unsignedBigInteger('tenant_id')->index()
                ->comment('Tenant izolasyonu');
            $table->foreignId('owner_id')
                ->constrained('users')
                ->onDelete('cascade');

            // ── Dosya Bilgisi ──────────────────────────────────────────
            $table->string('dosya_adi')
                ->comment('Örn: report_AbCdEfGhIj.pdf');
            $table->string('dosya_yolu')
                ->comment('Storage path: exports/owner/{user_id}/{dosya_adi}');
            $table->string('format', 10)->default('csv')
                ->comment('csv | pdf');

            // ── İşlem Durumu ───────────────────────────────────────────
            $table->string('islem_durumu', 20)->default('bekliyor')
                ->comment('bekliyor | isleniyor | tamamlandi | hata (Context7: islem_durumu)');
            $table->timestamp('tamamlanma_tarihi')->nullable()
                ->comment('Job tamamlandığında set edilir');
            $table->text('hata_mesaji')->nullable()
                ->comment('Job başarısız olursa hata detayı (Context7: hata_mesaji)');

            // ── Filtreler ─────────────────────────────────────────────
            $table->json('filtreler')->nullable()
                ->comment('Export sırasındaki filtre parametreleri: {ilan_id, baslangic_tarihi, bitis_tarihi, format}');

            $table->timestamps();

            // ── İndeksler ─────────────────────────────────────────────
            $table->index(['tenant_id', 'owner_id'], 'idx_exports_tenant_owner');
            $table->index(['owner_id', 'islem_durumu'], 'idx_exports_owner_durum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_report_exports');
    }
};
