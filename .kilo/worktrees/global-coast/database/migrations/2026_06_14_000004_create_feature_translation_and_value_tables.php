<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * B-006 P5E — Feature/Emlak translation + value tabloları
 *
 * feature_values              : Polimorfik özellik değerleri (HasFeatures trait)
 * feature_translations        : Feature çevirileri (Feature model)
 * feature_category_translations: FeatureCategory çevirileri (FeatureCategory model)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---------------------------------------------------------------
        // feature_values — polimorfik (valuable_type + valuable_id)
        // ---------------------------------------------------------------
        Schema::create('feature_values', function (Blueprint $table) {
            $table->id();

            // Polimorfik ilişki (Ilan, Kisi, vb.)
            $table->morphs('valuable');

            $table->foreignId('feature_id')
                  ->constrained('features')
                  ->cascadeOnDelete();

            $table->text('value')->nullable()->comment('Ham değer; typed_value accessor parse eder');
            $table->string('value_type', 30)->nullable()->comment('string, integer, boolean, json');

            $table->timestamps();

            $table->index(['valuable_type', 'valuable_id', 'feature_id'], 'fv_valuable_feature_idx');
        });

        // ---------------------------------------------------------------
        // feature_translations
        // ---------------------------------------------------------------
        Schema::create('feature_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feature_id')
                  ->constrained('features')
                  ->cascadeOnDelete();

            $table->string('locale', 10)->comment('tr, en, de, ...');
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(['feature_id', 'locale'], 'ft_feature_locale_unique');
        });

        // ---------------------------------------------------------------
        // feature_category_translations
        // ---------------------------------------------------------------
        Schema::create('feature_category_translations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feature_category_id')
                  ->constrained('feature_categories')
                  ->cascadeOnDelete();

            $table->string('locale', 10)->comment('tr, en, de, ...');
            $table->string('name', 255);
            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(['feature_category_id', 'locale'], 'fct_category_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_category_translations');
        Schema::dropIfExists('feature_translations');
        Schema::dropIfExists('feature_values');
    }
};
