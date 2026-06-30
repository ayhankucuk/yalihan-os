<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: etki_alani_olaylari_hatali (Dead Letter Queue)
 *
 * SAB Phase 15 Sprint 2: Dead Letter Queue for Failed Projection Events
 * Forensic tracking and manual replay capability for failed event processing.
 *
 * Anayasal Kararlar:
 * - Madde 1: Zero data loss (3 retry sonrası DLQ'ya kaydet)
 * - Madde 2: Forensic tracking (stack trace + error message)
 * - Madde 3: Manual replay capability (islem_durumu tracking)
 * - Madde 4: Tenant isolation (tenant_id mandatory)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('etki_alani_olaylari_hatali', function (Blueprint $table) {
            $table->id();

            // Tenant Isolation
            $table->unsignedBigInteger('tenant_id')->index();

            // Event Metadata
            $table->string('olay_turu', 150)->comment('Event type that failed');
            $table->string('kaynak_kimligi', 100)->comment('Source aggregate identifier');

            // Event Payload
            $table->json('olay_verisi')->comment('Original event data');

            // Error Details
            $table->text('hata_mesaji')->comment('Exception message');
            $table->text('stack_trace')->comment('Full stack trace for forensic analysis');

            // Processing Status
            $table->tinyInteger('islem_durumu')->default(1)->comment('1: İncelemede, 2: Yeniden Oynatıldı, 3: Arşivlendi');

            // Timestamps
            $table->timestamp('olusturulma_zamani')->useCurrent();
            $table->timestamp('islenme_zamani')->nullable()->comment('When manually replayed');

            // Indexes
            $table->index(['tenant_id', 'olay_turu'], 'idx_tenant_event_type');
            $table->index('islem_durumu');
            $table->index('olusturulma_zamani');

            // Foreign Keys
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->onDelete('restrict'); // Prevent tenant deletion if DLQ entries exist
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('etki_alani_olaylari_hatali');
    }
};
