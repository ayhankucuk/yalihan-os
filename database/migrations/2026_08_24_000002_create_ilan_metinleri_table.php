<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sprint 13 — Test Isolation Fix
     *
     * Creates ilan_metinleri table required by ListingRankingService.
     * Previously created inline in C5WizardMediaContractTest::setUp() —
     * that caused SQLite auto-commit contamination between test runs.
     */
    public function up(): void
    {
        Schema::create('ilan_metinleri', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ilan_id')->nullable()->index();
            $table->string('yapay_zeka_durumu', 30)->default('beklemede');
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->text('ai_description')->nullable();
            $table->text('ai_features')->nullable();
            $table->string('seo_baslik', 255)->nullable();
            $table->text('seo_aciklama')->nullable();
            $table->timestamps();

            $table->foreign('ilan_id')->references('id')->on('ilanlar')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfConstraints('ilan_metinleri');
        Schema::dropIfExists('ilan_metinleri');
    }
};
