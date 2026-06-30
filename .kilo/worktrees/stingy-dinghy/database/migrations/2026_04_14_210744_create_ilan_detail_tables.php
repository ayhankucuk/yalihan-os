<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Context7 Compliance Migration Template
 *
 * ⚠️ CONTEXT7 PERMANENT STANDARDS:
 * - ALWAYS use 'display_order' field, NEVER use 'o-word'
 * - ALWAYS use boolean 'aktif' field, NEVER use deprecated terms
 * - Pre-commit hook will BLOCK migrations with forbidden patterns
 * - This is a PERMANENT STANDARD - NO EXCEPTIONS
 */
return new class extends Migration
{
    /**
     * Create ilan_turizm_details and ilan_arsa_details tables.
     * These were referenced by 10+ services but never had backing tables.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ilan_turizm_details')) {
            Schema::create('ilan_turizm_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ilan_id')->unique();
                $table->string('check_in_saati', 10)->nullable();
                $table->string('check_out_saati', 10)->nullable();
                $table->integer('min_konaklama')->nullable();
                $table->integer('max_misafir')->nullable();
                $table->decimal('gunluk_fiyat', 12, 2)->nullable();
                $table->decimal('temizlik_ucreti', 10, 2)->nullable();
                $table->boolean('havuz_var')->default(false);
                $table->date('sezon_baslangic')->nullable();
                $table->date('sezon_bitis')->nullable();
                $table->timestamps();

                $table->foreign('ilan_id')->references('id')->on('ilanlar')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('ilan_arsa_details')) {
            Schema::create('ilan_arsa_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('ilan_id')->unique();
                $table->string('ada_no', 50)->nullable();
                $table->string('parsel_no', 50)->nullable();
                $table->string('imar_durumu', 100)->nullable();
                $table->decimal('kaks', 5, 2)->nullable();
                $table->decimal('taks', 5, 2)->nullable();
                $table->timestamps();

                $table->foreign('ilan_id')->references('id')->on('ilanlar')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ilan_arsa_details');
        Schema::dropIfExists('ilan_turizm_details');
    }
};
