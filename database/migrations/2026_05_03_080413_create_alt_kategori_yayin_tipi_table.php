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
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('alt_kategori_yayin_tipi')) {
            Schema::create('alt_kategori_yayin_tipi', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('alt_kategori_id');
                $table->unsignedBigInteger('yayin_tipi_id');
                $table->tinyInteger('is_active')->default(1); // 0=inactive, 1=active
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->foreign('alt_kategori_id')->references('id')->on('ilan_kategorileri');
                $table->foreign('yayin_tipi_id')->references('id')->on('yayin_tipi_sablonlari');
                $table->unique(['alt_kategori_id', 'yayin_tipi_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alt_kategori_yayin_tipi');
    }
};
