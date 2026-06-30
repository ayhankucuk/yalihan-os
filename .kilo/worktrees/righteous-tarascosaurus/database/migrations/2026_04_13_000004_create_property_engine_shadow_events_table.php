<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_engine_shadow_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ulke_id')->nullable()->index();
            $table->string('mode')->default('shadow'); // shadow, live
            $table->string('env')->default('production'); // production, staging
            $table->string('context_hash')->index();
            $table->string('v2_signature')->nullable();
            $table->string('v3_signature')->nullable();
            $table->boolean('match')->default(false)->index();
            $table->text('error_v2')->nullable();
            $table->text('error_v3')->nullable();
            $table->unsignedInteger('latency_ms_v2')->nullable();
            $table->unsignedInteger('latency_ms_v3')->nullable();
            $table->unsignedInteger('rule_count_v2')->nullable();
            $table->unsignedInteger('rule_count_v3')->nullable();
            $table->timestamps();

            $table->index(['match', 'created_at']);
            $table->index(['mode', 'env', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_engine_shadow_events');
    }
};
