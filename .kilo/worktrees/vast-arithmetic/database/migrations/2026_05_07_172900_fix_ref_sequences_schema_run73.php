<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fix ref_sequences Schema - Run #73
 *
 * Context: Run #62 created ref_sequences with wrong schema.
 * Model expects: yayin_tipi, lokasyon_kodu, kategori_kodu, last_sequence, year
 * Migration had: current_value, prefix, suffix, increment_by
 *
 * Root Cause: Schema drift between migration and model expectations
 * Impact: 31 tests failing with "no column named yayin_tipi"
 *
 * Strategy: Drop and recreate with correct schema
 * Risk: LOW (table created in Run #62, no production data)
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop incorrect schema
        Schema::dropIfExists('ref_sequences');

        // Recreate with correct schema matching RefSequence model
        Schema::create('ref_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('sequence_key', 100)->unique();
            $table->unsignedInteger('last_sequence')->default(0);
            $table->unsignedSmallInteger('year');
            $table->string('yayin_tipi', 20)->nullable();
            $table->string('lokasyon_kodu', 50)->nullable();
            $table->string('kategori_kodu', 50)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['yayin_tipi', 'year']);
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to Run #62 schema
        Schema::dropIfExists('ref_sequences');

        Schema::create('ref_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('sequence_key')->unique();
            $table->integer('current_value')->default(0);
            $table->string('prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->integer('increment_by')->default(1);
            $table->timestamps();
        });
    }
};
