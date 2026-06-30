<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cortex_neural_connections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulke_id')->nullable()->index();
            $table->string('source_module');
            $table->string('target_module');
            $table->string('connection_type')->default('direct');
            $table->decimal('connection_strength', 8, 2)->default(0);
            $table->unsignedInteger('interaction_count')->default(0);
            $table->decimal('success_rate', 5, 2)->default(0);
            $table->decimal('avg_performance', 8, 2)->default(0);
            $table->json('learned_patterns')->nullable();
            $table->json('usage_context')->nullable();
            $table->timestamp('first_interaction_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->tinyInteger('aktiflik_durumu')->default(1)->index();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['source_module', 'target_module']);
            $table->index(['connection_type', 'aktiflik_durumu']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cortex_neural_connections');
    }
};
