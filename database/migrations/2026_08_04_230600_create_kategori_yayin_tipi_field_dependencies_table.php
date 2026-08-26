<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Canonical schema: kategori_yayin_tipi_field_dependencies
     *
     * Field dependencies for wizard forms — maps kategori + yayin_tipi to dynamic fields.
     * Source: mysql-schema.sql line 2504-2533
     *
     * @see docs/architecture/models.md
     * @see docs/features/WIZARD_FLOW.md
     */
    public function up(): void
    {
        Schema::create('kategori_yayin_tipi_field_dependencies', function (Blueprint $table) {
            $table->id();
            $table->string('kategori_slug'); // e.g., 'konut', 'arsa', 'ticari'
            $table->unsignedBigInteger('yayin_tipi_id')->nullable();
            $table->string('yayin_tipi'); // e.g., 'satilik', 'kiralik'
            $table->string('field_slug'); // e.g., 'brut-metrekare', 'oda-sayisi'
            $table->string('field_name'); // e.g., 'Brüt Metrekare'
            $table->string('field_type')->default('text'); // text, number, select, checkbox, etc.
            $table->string('field_category')->nullable(); // e.g., 'Temel Bilgiler'
            $table->json('field_options')->nullable();
            $table->string('field_unit')->nullable();
            $table->string('field_icon')->nullable();
            $table->boolean('required')->default(false);
            $table->integer('display_order')->default(0);
            $table->boolean('ai_auto_fill')->default(false);
            $table->boolean('ai_suggestion')->default(false);
            $table->string('ai_prompt_key')->nullable();
            $table->boolean('searchable')->default(false);
            $table->boolean('show_in_card')->default(false);
            $table->boolean('aktiflik_durumu')->default(true);
            $table->timestamps();

            // Unique constraint: same field cannot exist twice for same kategori+yayin_tipi
            $table->unique(['kategori_slug', 'yayin_tipi', 'field_slug'], 'idx_kytfd_unique');

            // Indexes for common lookups
            $table->index(['kategori_slug', 'yayin_tipi'], 'idx_kytfd_lookup');
            $table->index('kategori_slug');
            $table->index('yayin_tipi');
            $table->index('field_slug');
            $table->index('yayin_tipi_id');
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::dropIfExists('kategori_yayin_tipi_field_dependencies');
    }
};
