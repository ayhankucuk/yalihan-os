<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_provider_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40);
            $table->string('window', 10);
            $table->unsignedBigInteger('kategori_id')->nullable();
            $table->decimal('accept_rate', 4, 3)->default(0.000);
            $table->integer('avg_latency_ms')->default(0);
            $table->decimal('avg_cost_usd', 8, 6)->default(0.000000);
            $table->decimal('error_rate', 4, 3)->default(0.000);
            $table->decimal('cache_hit_rate', 4, 3)->default(0.000);
            $table->integer('sample_size')->default(0);
            $table->decimal('computed_score', 4, 3)->default(0.000);
            $table->timestamp('computed_at')->useCurrent();

            $table->timestamps();

            $table->unique(['provider', 'window', 'kategori_id'], 'provider_window_cat_unique');
            $table->index('provider');
            $table->index('kategori_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_provider_profiles');
    }
};
