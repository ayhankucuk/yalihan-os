<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('test_entities');
        Schema::create('test_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->json('payload')->nullable();
            $table->json('published_payload')->nullable();
            $table->string('governance_state')->default('draft');
            $table->unsignedBigInteger('ulke_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_entities');
    }
};
