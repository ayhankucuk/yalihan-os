<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Canonical opportunities table for Cortex fırsat eşleştirme.
     * Supports both MySQL (production) and SQLite (testing).
     */
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ilan_id');
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->decimal('firsat_skoru', 5, 2)->default(0);
            $table->json('skor_detayi')->nullable();
            $table->text('firsat_nedeni')->nullable();
            $table->text('ikna_metni')->nullable();
            $table->string('firsat_durumu', 20)->default('yeni');
            $table->boolean('aktiflik_durumu')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // FK'lar — SQLite'de foreign key desteği Schema builder ile
            $table->foreign('ilan_id')->references('id')->on('ilanlar')->onDelete('cascade');
            $table->foreign('lead_id')->references('id')->on('leads')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');

            // Composite unique
            $table->unique(['tenant_id', 'ilan_id', 'lead_id'], 'opportunities_tenant_listing_lead_unique');

            // Indexes
            $table->index(['tenant_id', 'firsat_durumu']);
            $table->index(['tenant_id', 'aktiflik_durumu']);
            $table->index(['tenant_id', 'firsat_skoru']);
            $table->index('ilan_id');
            $table->index('lead_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};
