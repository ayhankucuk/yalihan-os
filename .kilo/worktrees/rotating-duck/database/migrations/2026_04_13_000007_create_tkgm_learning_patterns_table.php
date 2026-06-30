<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tkgm_learning_patterns')) {
        Schema::create('tkgm_learning_patterns', function (Blueprint $table) {
            $table->id();
            $table->string('pattern_type')->index(); // price_kaks, location_premium, imar_effect, velocity, roi
            $table->unsignedBigInteger('il_id')->nullable()->index();
            $table->unsignedBigInteger('ilce_id')->nullable()->index();
            $table->unsignedBigInteger('mahalle_id')->nullable()->index();
            $table->json('pattern_data')->nullable();
            $table->unsignedInteger('sample_count')->default(0);
            $table->decimal('confidence_level', 5, 2)->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->unsignedInteger('prediction_count')->default(0);
            $table->decimal('prediction_accuracy', 5, 2)->nullable();
            $table->unsignedInteger('successful_predictions')->default(0);
            $table->boolean('pattern_aktiflik_durumu')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pattern_type', 'il_id', 'ilce_id']);
            $table->index(['confidence_level', 'pattern_aktiflik_durumu'], 'idx_tkgm_patterns_conf_active');
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tkgm_learning_patterns');
    }
};
